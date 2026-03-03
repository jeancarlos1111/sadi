<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {

            // Usamos SQLite para entorno de desarrollo fácil (SADI Piloto)
            // En Producción esto cambiaría a pgsql:host=...
            $dbPath = dirname(__DIR__, 2) . '/database/sadi.sqlite';
            $dsn = "sqlite:" . $dbPath;

            try {
                // Instanciar PDO
                self::$instance = new PDO($dsn);

                // Configurar manejo de errores
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Evitar bloqueos "Database is locked" en entorno multi-proceso
                self::$instance->exec("PRAGMA busy_timeout = 5000;");

                // TRUCO SQLITE PARA COMPATIBILIDAD CON SCHEMAS POSTGRESQL:
                // En SIGAFS todo tiene el prefijo "tabla", "tabla", etc.
                // SQLite soporta adjuntar la MISMA base de datos bajo diferentes "alias" (schemas).
                // Hacemos esto para que los querys de PHP no se rompan sin tener que reescribir todo el SQL original.

                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_compras");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_proveedor");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_cuentas_por_pagar");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_cxp");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_presupuesto");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_inventario");

                // Módulos futuros comentados para no exceder el límite de 10 ATTACH de SQLite
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_banco");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_retenciones");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_caja");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_contabilidad");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_nomina");
                // self::$instance->exec("ATTACH DATABASE '$dbPath' AS modulo_administrador");

                // SQLite no tiene ILIKE nativo, registramos una funcion simple si la base de datos lo permite
                // O usamos COLLATE NOCASE. Por ahora creamos un UDF simple para ILIKE mapeado a LIKE
                self::$instance->sqliteCreateFunction('ILIKE', function ($a, $b) {
                    return stripos($a, str_replace('%', '', $b)) !== false ? 1 : 0;
                }, 2);

                // Función mock para buscar_formatear_estructura_presupuestaria
                self::$instance->sqliteCreateFunction('modulo_presupuesto_buscar_formatear_estructura_presupuestaria', function ($id) {
                    return "ESTRUCTURA-MOCK-" . $id;
                }, 1);

            } catch (PDOException $e) {
                // En desarrollo mostramos el error, en prod deberíamos loguearlo
                die("Error de conexión SQLite: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
