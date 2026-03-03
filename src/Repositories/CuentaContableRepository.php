<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\CuentaContable;

class CuentaContableRepository extends Repository
{
    protected function getTable(): string
    {
        return 'cuenta_contable';
    }

    /**
     * @return CuentaContable[]
     */
    public function all(string $search = ''): array
    {
        $query = $this->query()
                      ->select('id_cuenta_contable', 'codigo_cuenta', 'denominacion_cuenta', 'tipo_cuenta')
                      ->where('eliminado', '=', 0)
                      ->orderBy('codigo_cuenta', 'ASC');

        if ($search !== '') {
            $db = $this->getPdo();
            $sql = "
                SELECT id_cuenta_contable, codigo_cuenta, denominacion_cuenta, tipo_cuenta 
                FROM cuenta_contable 
                WHERE eliminado = false 
                AND (codigo_cuenta ILIKE :search OR denominacion_cuenta ILIKE :search)
                ORDER BY codigo_cuenta ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } else {
            $rows = $query->get();
        }

        return array_map(fn ($row) => $this->mapRowToEntity($row), $rows);
    }

    private function mapRowToEntity(array $row): CuentaContable
    {
        return new CuentaContable(
            (int)$row['id_cuenta_contable'],
            $row['codigo_cuenta'],
            $row['denominacion_cuenta'],
            $row['tipo_cuenta']
        );
    }
}
