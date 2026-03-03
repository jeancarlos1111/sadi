<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\TipoOrganizacion;

class TipoOrganizacionRepository extends Repository
{
    protected function getTable(): string
    {
        return 'tipo_organizacion';
    }

    /**
     * @return TipoOrganizacion[]
     */
    public function all(): array
    {
        $rows = $this->query()
                     ->select('id_tipo_organizacion', 'nombre_tipo_organizacion')
                     ->where('eliminado', '=', 0)
                     ->orderBy('nombre_tipo_organizacion', 'ASC')
                     ->get();

        return array_map(fn ($row) => new TipoOrganizacion((int)$row['id_tipo_organizacion'], $row['nombre_tipo_organizacion']), $rows);
    }
}
