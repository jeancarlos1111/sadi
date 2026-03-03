<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\TipoOperacionBancaria;
use PDO;

class TipoOperacionBancariaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'tipo_operacion_bancaria';
    }

    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT * FROM tipo_operacion_bancaria WHERE eliminado = 0 ORDER BY nombre_tipo_operacion_bancaria ASC");

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new TipoOperacionBancaria(
                (int)$row['id_tipo_operacion_bancaria'],
                $row['nombre_tipo_operacion_bancaria'],
                $row['acronimo_tipo_operacion_bancaria']
            );
        }

        return $results;
    }

    public function find(int $id): ?TipoOperacionBancaria
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM tipo_operacion_bancaria WHERE id_tipo_operacion_bancaria = ? AND eliminado = 0");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new TipoOperacionBancaria(
            (int)$row['id_tipo_operacion_bancaria'],
            $row['nombre_tipo_operacion_bancaria'],
            $row['acronimo_tipo_operacion_bancaria']
        );
    }

    public function save(TipoOperacionBancaria $item): bool
    {
        $db = $this->getPdo();
        if ($item->id) {
            $stmt = $db->prepare("UPDATE tipo_operacion_bancaria SET nombre_tipo_operacion_bancaria = ?, acronimo_tipo_operacion_bancaria = ? WHERE id_tipo_operacion_bancaria = ?");

            return $stmt->execute([$item->nombre, $item->acronimo, $item->id]);
        }

        $stmt = $db->prepare("INSERT INTO tipo_operacion_bancaria (nombre_tipo_operacion_bancaria, acronimo_tipo_operacion_bancaria) VALUES (?, ?)");
        $res = $stmt->execute([$item->nombre, $item->acronimo]);
        if ($res) {
            $item->id = (int)$db->lastInsertId();

            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("UPDATE tipo_operacion_bancaria SET eliminado = 1 WHERE id_tipo_operacion_bancaria = ?");

        return $stmt->execute([$id]);
    }
}
