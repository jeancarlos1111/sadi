<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\IndicadorAccionCentralizada;
use PDO;

class IndicadorAccionCentralizadaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'indicador_accion_centralizada';
    }

    public function findByAccionCentralizadaId(int $accionId): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM indicador_accion_centralizada WHERE id_accion_centralizada = :id_accion_centralizada AND eliminado = false");
        $stmt->execute(['id_accion_centralizada' => $accionId]);

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new IndicadorAccionCentralizada(
                (int)$row['id_accion_centralizada'],
                $row['indicador_eficacia'],
                $row['indicador_eficiencia'],
                $row['indicador_calidad'],
                $row['indicador_impacto'],
                $row['medio_verificacion'],
                (int)$row['id_indicador_ac']
            );
        }

        return $results;
    }

    public function save(IndicadorAccionCentralizada $item): bool
    {
        $data = [
            'id_accion_centralizada' => $item->id_accion_centralizada,
            'indicador_eficacia' => $item->indicador_eficacia,
            'indicador_eficiencia' => $item->indicador_eficiencia,
            'indicador_calidad' => $item->indicador_calidad,
            'indicador_impacto' => $item->indicador_impacto,
            'medio_verificacion' => $item->medio_verificacion,
        ];

        if ($item->id_indicador_ac) {
            return $this->query()->where('id_indicador_ac', '=', $item->id_indicador_ac)->update($data);
        } else {
            return $this->query()->insert($data);
        }
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_indicador_ac', '=', $id)->update(['eliminado' => 'true']);
    }
}
