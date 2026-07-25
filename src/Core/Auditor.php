<?php

declare(strict_types=1);

namespace App\Core;

use App\Database\Connection;
use PDO;

class Auditor
{
    /**
     * Registra una acción en la pista de auditoría.
     */
    public static function log(string $tabla, string $accion, int $idRegistro, ?array $datosAntes, ?array $datosDespues): void
    {
        $idUsuario = $_SESSION['usuario_id'] ?? null;
        $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Sistema/Desconocido';
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $db = Connection::getInstance();

        $stmt = $db->prepare("
            INSERT INTO auditoria_log (tabla, accion, id_registro, datos_antes, datos_despues, id_usuario, usuario_nombre, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $tabla,
            $accion,
            $idRegistro,
            $datosAntes ? json_encode($datosAntes, JSON_UNESCAPED_UNICODE) : null,
            $datosDespues ? json_encode($datosDespues, JSON_UNESCAPED_UNICODE) : null,
            $idUsuario,
            $usuarioNombre,
            $ip
        ]);
    }
}
