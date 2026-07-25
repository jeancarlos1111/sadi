<?php

declare(strict_types=1);

namespace App\Core;

use App\Database\Connection;
use PDO;

class Correlativo
{
    /**
     * Obtiene el siguiente correlativo para un documento dado en un año fiscal específico.
     * Ejemplo: Correlativo::next('comprobante_retencion', 2026); => "20260700000042"
     * 
     * Implementa un bloqueo (FOR UPDATE) para garantizar secuencialidad atómica
     * en entornos concurrentes.
     */
    public static function next(string $tipoDocumento, int $anio, int $padLength = 6): string
    {
        $db = Connection::getInstance();
        
        // Crear tabla si no existe
        $db->exec("
            CREATE TABLE IF NOT EXISTS documento_correlativo (
                tipo_documento VARCHAR(50) NOT NULL,
                anio INTEGER NOT NULL,
                ultimo_valor INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (tipo_documento, anio)
            )
        ");

        $db->beginTransaction();

        try {
            // Intentar insertar el registro inicial si no existe
            $stmtInsert = $db->prepare("
                INSERT INTO documento_correlativo (tipo_documento, anio, ultimo_valor) 
                VALUES (?, ?, 0) 
                ON CONFLICT DO NOTHING
            ");
            $stmtInsert->execute([$tipoDocumento, $anio]);

            // Leer y bloquear la fila
            $stmtSelect = $db->prepare("
                SELECT ultimo_valor FROM documento_correlativo 
                WHERE tipo_documento = ? AND anio = ? 
                FOR UPDATE
            ");
            $stmtSelect->execute([$tipoDocumento, $anio]);
            $ultimoValor = (int)$stmtSelect->fetchColumn();

            $nuevoValor = $ultimoValor + 1;

            // Actualizar la fila
            $stmtUpdate = $db->prepare("
                UPDATE documento_correlativo 
                SET ultimo_valor = ? 
                WHERE tipo_documento = ? AND anio = ?
            ");
            $stmtUpdate->execute([$nuevoValor, $tipoDocumento, $anio]);

            $db->commit();
            
            // Formatear dependiendo del tipo
            if ($tipoDocumento === 'comprobante_retencion') {
                // Formato SENIAT: AAAAMMXXXXXXXX (A=Año, M=Mes, X=Correlativo 8 dígitos)
                $mes = date('m');
                return sprintf('%04d%02d%08d', $anio, $mes, $nuevoValor);
            }
            
            return sprintf('%04d%0' . $padLength . 'd', $anio, $nuevoValor);

        } catch (\Exception $e) {
            $db->rollBack();
            throw new \RuntimeException("Error al generar correlativo para {$tipoDocumento}: " . $e->getMessage());
        }
    }
}
