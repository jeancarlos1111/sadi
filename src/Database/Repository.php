<?php

declare(strict_types=1);

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
     * Retorna el pool de conexiones de Postgres para operaciones concurrentes (Fibers)
     */
    public function getAsyncPool(): \Amp\Postgres\PostgresConnectionPool
    {
        return \App\Database\AsyncConnection::getPool();
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

    /**
     * Método de paginación genérico de respaldo.
     * Carga todos los registros y pagina en memoria.
     * Soporta cualquier cantidad de argumentos (los dos últimos deben ser $page y $perPage)
     */
    public function paginate(...$args): array
    {
        $perPage = 15;
        $page = 1;

        $count = count($args);
        if ($count >= 2 && is_int($args[$count - 1]) && is_int($args[$count - 2])) {
            $perPage = array_pop($args);
            $page = array_pop($args);
        }

        $all = call_user_func_array([$this, 'all'], $args);

        $total = count($all);
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $data = array_slice($all, $offset, $perPage);

        return [
            'data' => $data,
            'total' => $total,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
        ];
    }
}
