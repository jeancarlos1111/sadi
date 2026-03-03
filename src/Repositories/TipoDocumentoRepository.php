<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\TipoDocumento;

class TipoDocumentoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'tipo_documento';
    }

    /**
     * @return TipoDocumento[]
     */
    public function all(): array
    {
        $rows = $this->query()
                     ->select('id_tipo_documento', 'denominacion_tipo_documento', 'afecta_presupuesto_tipo_documento', 'siglas_tipo_documento')
                     ->where('eliminado', '=', 0)
                     ->orderBy('denominacion_tipo_documento', 'ASC')
                     ->get();

        return array_map(fn ($row) => $this->mapRowToEntity($row), $rows);
    }

    public function findById(int $id): ?TipoDocumento
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_tipo_documento', '=', $id)
                    ->where('eliminado', '=', 0)
                    ->first();

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function save(TipoDocumento $item): bool
    {
        $afecta = $item->afectaPresupuesto ? 1 : 0;

        $data = [
            'denominacion_tipo_documento' => $item->denominacion,
            'afecta_presupuesto_tipo_documento' => $afecta,
            'siglas_tipo_documento' => $item->siglas,
        ];

        if ($item->id) {
            return $this->query()->where('id_tipo_documento', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_tipo_documento', '=', $id)->update(['eliminado' => 1]);
    }

    private function mapRowToEntity(array $row): TipoDocumento
    {
        return new TipoDocumento(
            $row['denominacion_tipo_documento'],
            (bool)$row['afecta_presupuesto_tipo_documento'],
            $row['siglas_tipo_documento'] ?: null,
            (int)$row['id_tipo_documento']
        );
    }
}
