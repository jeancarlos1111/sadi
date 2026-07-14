<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\PlanUnicoCuentas;

class PlanUnicoCuentasRepository extends Repository
{
    protected function getTable(): string
    {
        return 'plan_unico_cuentas';
    }

    /**
     * @return PlanUnicoCuentas[]
     */
    public function all(string $search = ''): array
    {
        $query = $this->query()
                      ->select('*')
                      ->where('eliminado', '=', 'false')
                      ->orderBy('codigo_plan_unico', 'ASC');

        if ($search !== '') {
            $db = $this->getPdo();
            $sql = "SELECT * FROM plan_unico_cuentas WHERE eliminado = false AND (codigo_plan_unico LIKE :s OR denominacion LIKE :s) ORDER BY codigo_plan_unico";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':s', "%$search%");
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } else {
            $rows = $query->get();
        }

        return array_map(fn ($row) => $this->mapRowToEntity($row), $rows);
    }

    public function allAsync(): array
    {
        $result = $this->getAsyncPool()->query("SELECT * FROM plan_unico_cuentas WHERE eliminado = false ORDER BY codigo_plan_unico ASC");

        $results = [];
        foreach ($result as $row) {
            $results[] = $this->mapRowToEntity($row);
        }

        return $results;
    }

    public function findById(int $id): ?PlanUnicoCuentas
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_codigo_plan_unico', '=', $id)
                    ->where('eliminado', '=', 'false')
                    ->first();

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function save(PlanUnicoCuentas $item): bool
    {
        $data = [
            'codigo_plan_unico' => $item->codigo,
            'denominacion'      => $item->denominacion,
        ];

        if ($item->id) {
            return $this->query()->where('id_codigo_plan_unico', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_codigo_plan_unico', '=', $id)->update(['eliminado' => 'true']);
    }

    private function mapRowToEntity(array $row): PlanUnicoCuentas
    {
        return new PlanUnicoCuentas(
            $row['codigo_plan_unico'],
            $row['denominacion'],
            (int)$row['id_codigo_plan_unico']
        );
    }
}
