<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\IndicadorProyecto;
use PDO;

class IndicadorProyectoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'indicador_proyecto';
    }

    public function findByProyectoId(int $proyectoId): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM indicador_proyecto WHERE id_proyecto = :id_proyecto AND eliminado = false");
        $stmt->execute(['id_proyecto' => $proyectoId]);

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new IndicadorProyecto(
                (int)$row['id_proyecto'],
                $row['indicador_eficacia'],
                $row['indicador_eficiencia'],
                $row['indicador_calidad'],
                $row['indicador_impacto'],
                $row['medio_verificacion'],
                (int)$row['id_indicador_proyecto']
            );
        }

        return $results;
    }

    public function save(IndicadorProyecto $item): bool
    {
        $data = [
            'id_proyecto' => $item->id_proyecto,
            'indicador_eficacia' => $item->indicador_eficacia,
            'indicador_eficiencia' => $item->indicador_eficiencia,
            'indicador_calidad' => $item->indicador_calidad,
            'indicador_impacto' => $item->indicador_impacto,
            'medio_verificacion' => $item->medio_verificacion,
        ];

        if ($item->id_indicador_proyecto) {
            return $this->query()->where('id_indicador_proyecto', '=', $item->id_indicador_proyecto)->update($data);
        } else {
            return $this->query()->insert($data);
        }
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_indicador_proyecto', '=', $id)->update(['eliminado' => 'true']);
    }
}
