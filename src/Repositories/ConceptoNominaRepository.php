<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\ConceptoNomina;
use PDO;

class ConceptoNominaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'concepto_nomina';
    }

    /**
     * @return ConceptoNomina[]
     */
    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT * FROM concepto_nomina WHERE eliminado = 0 ORDER BY tipo_concepto ASC, id_concepto ASC");

        return array_map(fn ($row) => $this->mapRowToEntity($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?ConceptoNomina
    {
        $row = $this->query()->where('id_concepto', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function save(ConceptoNomina $item): bool
    {
        $data = [
            'codigo' => $item->codigo,
            'descripcion' => $item->descripcion,
            'tipo_concepto' => $item->tipo,
            'formula_valor' => $item->formulaValor,
            'es_porcentaje' => (int)$item->esPorcentaje,
            'formula_expr' => $item->formulaExpr,
        ];

        if ($item->id) {
            return $this->query()->where('id_concepto', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        if ($id) {
            $item->id = (int)$id;

            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_concepto', '=', $id)->update(['eliminado' => 1]);
    }

    private function mapRowToEntity(array $row): ConceptoNomina
    {
        return new ConceptoNomina(
            $row['codigo'],
            $row['descripcion'],
            $row['tipo_concepto'],
            (float)$row['formula_valor'],
            (bool)$row['es_porcentaje'],
            $row['formula_expr'] ?? null,
            (int)$row['id_concepto']
        );
    }
}
