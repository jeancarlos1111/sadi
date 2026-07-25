<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;
use App\Database\Connection;
use PDO;

class AuditoriaController extends BaseController
{
    public function index(): void
    {
        Gate::authorize('admin.usuarios.ver'); // Solo Admin puede ver esto

        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $db = Connection::getInstance();
        
        $where = [];
        $bindings = [];

        if (!empty($_GET['usuario'])) {
            $where[] = "usuario_nombre ILIKE :usuario";
            $bindings['usuario'] = "%" . $_GET['usuario'] . "%";
        }

        if (!empty($_GET['tabla'])) {
            $where[] = "tabla = :tabla";
            $bindings['tabla'] = $_GET['tabla'];
        }

        $whereClause = "";
        if (!empty($where)) {
            $whereClause = "WHERE " . implode(" AND ", $where);
        }

        // Total
        $sqlTotal = "SELECT COUNT(*) FROM auditoria_log $whereClause";
        $stmtTotal = $db->prepare($sqlTotal);
        $stmtTotal->execute($bindings);
        $total = (int)$stmtTotal->fetchColumn();

        // Data
        $sql = "SELECT * FROM auditoria_log $whereClause ORDER BY id_log DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($bindings as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->renderView('admin/auditoria/index', [
            'titulo' => 'Pista de Auditoría',
            'logs' => $logs,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
        ]);
    }
}
