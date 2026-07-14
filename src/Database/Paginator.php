<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

class Paginator
{
    /**
     * Paginates a raw SQL query.
     *
     * @param  PDO    $db       The PDO connection
     * @param  string $sql      The SELECT query without LIMIT or OFFSET
     * @param  array  $bindings The PDO bindings for the query
     * @param  int    $page     The current page number (1-indexed)
     * @param  int    $perPage  Number of items per page
     * @return array  [ 'data' => array, 'total' => int, 'current_page' => int, 'last_page' => int, 'per_page' => int ]
     */
    public static function paginateRaw(PDO $db, string $sql, array $bindings = [], int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        // 1. Calculate Total Records
        // Wrap the original query in a subquery to count results reliably
        $countSql = "SELECT COUNT(*) FROM ($sql) AS count_tbl";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($bindings);
        $total = (int) $stmtCount->fetchColumn();

        // 2. Fetch Paginated Records
        $paginatedSql = $sql . " LIMIT $perPage OFFSET $offset";
        $stmt = $db->prepare($paginatedSql);
        $stmt->execute($bindings);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return [
            'data'         => $data,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'per_page'     => $perPage,
        ];
    }
}
