<?php

namespace App\Repositories;

use PDO;
use App\Models\Proyecto;
use App\Database\Repository;

class ProyectoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'proyecto';
    }

    public function all(string $search = ''): array
    {
        $sql = "SELECT * FROM proyecto WHERE eliminado = 0";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (codigo_proyecto LIKE :search OR denominacion LIKE :search)";
            $params['search'] = "%$search%";
        }

        $sql .= " ORDER BY codigo_proyecto ASC";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new Proyecto(
                $row['codigo_proyecto'],
                $row['denominacion'],
                $row['unidad_medida'],
                $row['anio_inicio'],
                $row['anio_culm'],
                (float)$row['cant_programada_trim_i'],
                (float)$row['cant_ejecutada_trim_i'],
                (float)$row['cant_programada_trim_ii'],
                (float)$row['cant_ejecutada_trim_ii'],
                (float)$row['cant_programada_trim_iii'],
                (float)$row['cant_ejecutada_trim_iii'],
                (float)$row['cant_programada_trim_iv'],
                (float)$row['cant_ejecutada_trim_iv'],
                (int)$row['id_proyecto']
            );
        }
        return $results;
    }

    public function findById(int $id): ?Proyecto
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM proyecto WHERE id_proyecto = :id AND eliminado = 0");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new Proyecto(
            $row['codigo_proyecto'],
            $row['denominacion'],
            $row['unidad_medida'],
            $row['anio_inicio'],
            $row['anio_culm'],
            (float)$row['cant_programada_trim_i'],
            (float)$row['cant_ejecutada_trim_i'],
            (float)$row['cant_programada_trim_ii'],
            (float)$row['cant_ejecutada_trim_ii'],
            (float)$row['cant_programada_trim_iii'],
            (float)$row['cant_ejecutada_trim_iii'],
            (float)$row['cant_programada_trim_iv'],
            (float)$row['cant_ejecutada_trim_iv'],
            (int)$row['id_proyecto']
        );
    }

    public function save(Proyecto $p): void
    {
        if ($p->id_proyecto) {
            $stmt = $this->getPdo()->prepare("UPDATE proyecto SET 
                codigo_proyecto = :codigo, 
                denominacion = :denominacion,
                unidad_medida = :um,
                anio_inicio = :ai,
                anio_culm = :ac,
                cant_programada_trim_i = :p1,
                cant_ejecutada_trim_i = :e1,
                cant_programada_trim_ii = :p2,
                cant_ejecutada_trim_ii = :e2,
                cant_programada_trim_iii = :p3,
                cant_ejecutada_trim_iii = :e3,
                cant_programada_trim_iv = :p4,
                cant_ejecutada_trim_iv = :e4
                WHERE id_proyecto = :id");
            $stmt->execute([
                'codigo' => $p->codigo_proyecto,
                'denominacion' => $p->denominacion,
                'um' => $p->unidad_medida,
                'ai' => $p->anio_inicio,
                'ac' => $p->anio_culm,
                'p1' => $p->cant_programada_trim_i,
                'e1' => $p->cant_ejecutada_trim_i,
                'p2' => $p->cant_programada_trim_ii,
                'e2' => $p->cant_ejecutada_trim_ii,
                'p3' => $p->cant_programada_trim_iii,
                'e3' => $p->cant_ejecutada_trim_iii,
                'p4' => $p->cant_programada_trim_iv,
                'e4' => $p->cant_ejecutada_trim_iv,
                'id' => $p->id_proyecto
            ]);
        } else {
            $stmt = $this->getPdo()->prepare("INSERT INTO proyecto (
                codigo_proyecto, denominacion, unidad_medida, anio_inicio, anio_culm, 
                cant_programada_trim_i, cant_ejecutada_trim_i, cant_programada_trim_ii, 
                cant_ejecutada_trim_ii, cant_programada_trim_iii, cant_ejecutada_trim_iii, 
                cant_programada_trim_iv, cant_ejecutada_trim_iv
            ) VALUES (
                :codigo, :denominacion, :um, :ai, :ac, 
                :p1, :e1, :p2, :e2, :p3, :e3, :p4, :e4
            )");
            $stmt->execute([
                'codigo' => $p->codigo_proyecto,
                'denominacion' => $p->denominacion,
                'um' => $p->unidad_medida,
                'ai' => $p->anio_inicio,
                'ac' => $p->anio_culm,
                'p1' => $p->cant_programada_trim_i,
                'e1' => $p->cant_ejecutada_trim_i,
                'p2' => $p->cant_programada_trim_ii,
                'e2' => $p->cant_ejecutada_trim_ii,
                'p3' => $p->cant_programada_trim_iii,
                'e3' => $p->cant_ejecutada_trim_iii,
                'p4' => $p->cant_programada_trim_iv,
                'e4' => $p->cant_ejecutada_trim_iv
            ]);
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->getPdo()->prepare("UPDATE proyecto SET eliminado = 1 WHERE id_proyecto = :id");
        $stmt->execute(['id' => $id]);
    }
}
