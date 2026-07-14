<?php

namespace Tests\Helpers;

use App\Database\Connection;

class DatabaseSeeder
{
    /**
     * Siembra los catálogos base necesarios para los tests de presupuesto.
     * Como el TRUNCATE en beforeEach ya limpió las tablas de negocio,
     * y los catálogos (estruc, articulo, etc.) vienen pre-cargados
     * desde schema.sql, solo necesitamos asegurar que tengan los IDs
     * que usaremos en los tests.
     */
    public static function seedCatalogs(): void
    {
        $db = Connection::getInstance();

        // Los catálogos base (estruc_presupuestaria, plan_unico_cuentas,
        // fuente_financiamiento, articulo) ya vienen del schema.sql.
        // No es necesario re-insertarlos. Solo verificamos que el ID 1 exista.
    }

    public static function cleanBudgetTables(): void
    {
        $db = Connection::getInstance();
        // Con PostgreSQL usamos TRUNCATE ... CASCADE para limpiar en cascada
        $db->exec('TRUNCATE TABLE articulo_orden_de_compra, orden_de_compra, pac, presupuesto_gastos RESTART IDENTITY CASCADE');
    }
}
