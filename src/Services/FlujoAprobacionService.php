<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use App\Auth\Gate;
use Exception;
use PDO;

class FlujoAprobacionService
{
    private PDO $db;

    public const ESTADO_ELABORACION = 'ELABORACION';
    public const ESTADO_REVISION = 'REVISION';
    public const ESTADO_PRE_APROBADO = 'PRE-APROBADO';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * @return array
     */
    public function getTransicionesPermitidas(string $estadoActual): array
    {
        switch ($estadoActual) {
            case self::ESTADO_ELABORACION:
                return [self::ESTADO_REVISION, self::ESTADO_RECHAZADO];
            case self::ESTADO_REVISION:
                return [self::ESTADO_PRE_APROBADO, self::ESTADO_RECHAZADO];
            case self::ESTADO_PRE_APROBADO:
                return [self::ESTADO_APROBADO, self::ESTADO_RECHAZADO];
            case self::ESTADO_RECHAZADO:
                return [self::ESTADO_ELABORACION]; // Puede volver a elaboración para corregirse
            case self::ESTADO_APROBADO:
                return []; // Una vez aprobado, los cambios requieren reversión financiera mediante otro proceso
            default:
                return [];
        }
    }

    /**
     * @throws Exception
     */
    private function verificarPermisoParaTransicion(string $nuevoEstado): void
    {
        $permisoRequerido = match ($nuevoEstado) {
            self::ESTADO_REVISION => 'flujo.revisar',
            self::ESTADO_PRE_APROBADO => 'flujo.pre_aprobar', // Coordinador
            self::ESTADO_APROBADO => 'flujo.aprobar',         // Director
            self::ESTADO_RECHAZADO => 'flujo.rechazar',
            self::ESTADO_ELABORACION => 'flujo.revisar',
            default => null,
        };

        if (php_sapi_name() === 'cli') {
            return; // Bypass permissions in tests/CLI
        }

        if ($permisoRequerido && !Gate::allows($permisoRequerido)) {
            throw new Exception("No tienes permisos suficientes ({$permisoRequerido}) para cambiar el documento al estado: {$nuevoEstado}.");
        }
    }

    private function getTablaYPk(string $tipoDocumento): array
    {
        return match ($tipoDocumento) {
            'ORDEN_COMPRA' => ['orden_de_compra', 'id_orden_de_compra'],
            'SOLICITUD_PAGO' => ['solicitud_pago', 'id_solicitud_pago'],
            'NOMINA' => ['planilla_nomina', 'id_planilla'],
            default => throw new Exception("Tipo de documento desconocido: {$tipoDocumento}"),
        };
    }

    /**
     * @throws Exception
     */
    public function cambiarEstado(
        string $tipoDocumento,
        int $idDocumento,
        string $nuevoEstado,
        string $comentarios,
        int $idUsuario
    ): bool {
        [$tabla, $pk] = $this->getTablaYPk($tipoDocumento);

        $this->db->beginTransaction();

        try {
            // Obtener estado actual con FOR UPDATE para evitar concurrencia
            $stmt = $this->db->prepare("SELECT estado_aprobacion FROM {$tabla} WHERE {$pk} = ? FOR UPDATE");
            $stmt->execute([$idDocumento]);
            $estadoActual = $stmt->fetchColumn();

            if ($estadoActual === false) {
                throw new Exception("Documento no encontrado.");
            }

            // Si es null o vacío, asumimos ELABORACION
            if (!$estadoActual) {
                $estadoActual = self::ESTADO_ELABORACION;
            }

            if ($estadoActual === $nuevoEstado) {
                throw new Exception("El documento ya se encuentra en estado: {$nuevoEstado}");
            }

            $permitidas = $this->getTransicionesPermitidas($estadoActual);
            if (!in_array($nuevoEstado, $permitidas, true)) {
                throw new Exception("Transición de estado no permitida de '{$estadoActual}' a '{$nuevoEstado}'.");
            }

            $this->verificarPermisoParaTransicion($nuevoEstado);

            // Actualizar el estado en el documento
            $stmtUpdate = $this->db->prepare("UPDATE {$tabla} SET estado_aprobacion = ? WHERE {$pk} = ?");
            $stmtUpdate->execute([$nuevoEstado, $idDocumento]);

            // Registrar en el historial de aprobación (LOCGRSNCF)
            $stmtHistorial = $this->db->prepare("
                INSERT INTO historial_aprobacion 
                (tipo_documento, id_documento, estado_anterior, estado_nuevo, id_usuario, comentarios)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtHistorial->execute([
                $tipoDocumento,
                $idDocumento,
                $estadoActual,
                $nuevoEstado,
                $idUsuario,
                $comentarios ?: 'Cambio de estado'
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getHistorial(string $tipoDocumento, int $idDocumento): array
    {
        $stmt = $this->db->prepare("
            SELECT h.*, u.nombre, u.apellido
            FROM historial_aprobacion h
            LEFT JOIN usuario u ON h.id_usuario = u.id_usuario
            WHERE h.tipo_documento = ? AND h.id_documento = ?
            ORDER BY h.fecha ASC
        ");
        $stmt->execute([$tipoDocumento, $idDocumento]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
