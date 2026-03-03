<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Banco;
use PDO;

class BancoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'banco';
    }

    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT * FROM banco WHERE eliminado = 0 ORDER BY nombre_banco ASC");

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new Banco((int)$row['id_banco'], $row['nombre_banco']);
        }

        return $results;
    }

    public function find(int $id): ?Banco
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM banco WHERE id_banco = ? AND eliminado = 0");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Banco((int)$row['id_banco'], $row['nombre_banco']);
    }

    public function save(Banco $banco): bool
    {
        $db = $this->getPdo();
        if ($banco->id) {
            $stmt = $db->prepare("UPDATE banco SET nombre_banco = ? WHERE id_banco = ?");

            return $stmt->execute([$banco->nombreBanco, $banco->id]);
        }

        $stmt = $db->prepare("INSERT INTO banco (nombre_banco) VALUES (?)");
        $res = $stmt->execute([$banco->nombreBanco]);
        if ($res) {
            $banco->id = (int)$db->lastInsertId();

            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("UPDATE banco SET eliminado = 1 WHERE id_banco = ?");

        return $stmt->execute([$id]);
    }
}
