<?php

namespace App\Database;

use PDO;

/**
 * Patrón Repository Base.
 * Separa la lógica de la capa de datos de la representación de las Entidades y Controladores.
 */
abstract class Repository
{
    protected Connection $dbSingleton;

    public function __construct()
    {
        // En una implementación DI pura esto debería inyectarse (ej: PDO inyectado)
        // Actualmente inyectamos al propio Builder
    }

    public function getPdo(): PDO
    {
        return Connection::getInstance();
    }

    /**
     * Retorna un nuevo QueryBuilder asociado a la tabla principal del repositorio
     */
    protected function query(): QueryBuilder
    {
        return QueryBuilder::table($this->getTable());
    }

    /**
     * Las clases hijas deben retornar el nombre de la tabla base
     * (Ej: 'proveedor', 'beneficiario', 'comprobante_retencion')
     */
    abstract protected function getTable(): string;
}
