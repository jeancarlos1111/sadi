<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\CuentaBancaria;
use PDO;

class CuentaBancariaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'cta_bancaria';
    }

    /**
     * @return CuentaBancaria[]
     */
    public function all(): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT C.id_cta_bancaria, C.id_banco, C.numero_cta_bancaria, B.nombre_banco 
            FROM cta_bancaria AS C
            JOIN banco AS B ON C.id_banco = B.id_banco
            WHERE C.eliminado = 0 AND B.eliminado = 0
            ORDER BY B.nombre_banco, C.numero_cta_bancaria
        ";
        $stmt = $db->query($sql);

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new CuentaBancaria(
                (int)$row['id_cta_bancaria'],
                (int)$row['id_banco'],
                $row['numero_cta_bancaria'],
                $row['nombre_banco']
            );
        }

        return $results;
    }

    public function find(int $id): ?CuentaBancaria
    {
        $db = $this->getPdo();
        $sql = "
            SELECT C.id_cta_bancaria, C.id_banco, C.numero_cta_bancaria, B.nombre_banco 
            FROM cta_bancaria AS C
            JOIN banco AS B ON C.id_banco = B.id_banco
            WHERE C.id_cta_bancaria = ? AND C.eliminado = 0
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new CuentaBancaria(
            (int)$row['id_cta_bancaria'],
            (int)$row['id_banco'],
            $row['numero_cta_bancaria'],
            $row['nombre_banco']
        );
    }

    public function save(CuentaBancaria $cta): bool
    {
        $db = $this->getPdo();
        $data = [
            'id_banco' => $cta->idBanco,
            'numero_cta_bancaria' => $cta->numeroCuenta,
        ];

        if ($cta->id) {
            $stmt = $db->prepare("UPDATE cta_bancaria SET id_banco = ?, numero_cta_bancaria = ? WHERE id_cta_bancaria = ?");

            return $stmt->execute([$cta->idBanco, $cta->numeroCuenta, $cta->id]);
        }

        $stmt = $db->prepare("INSERT INTO cta_bancaria (id_banco, numero_cta_bancaria) VALUES (?, ?)");
        $res = $stmt->execute([$cta->idBanco, $cta->numeroCuenta]);
        if ($res) {
            $cta->id = (int)$db->lastInsertId();

            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("UPDATE cta_bancaria SET eliminado = 1 WHERE id_cta_bancaria = ?");

        return $stmt->execute([$id]);
    }
}
