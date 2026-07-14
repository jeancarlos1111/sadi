<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\AccionCentralizada;
use PDO;

class AccionCentralizadaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'accion_centralizada';
    }

    public function all(string $search = ''): array
    {
        $sql = "SELECT * FROM accion_centralizada WHERE eliminado = false";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (codigo_accion_centralizada LIKE :search OR denominacion LIKE :search)";
            $params['search'] = "%$search%";
        }

        $sql .= " ORDER BY codigo_accion_centralizada ASC";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new AccionCentralizada(
                $row['codigo_accion_centralizada'],
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
                $row['indicador_eficacia'],
                $row['indicador_eficiencia'],
                $row['indicador_calidad'],
                $row['indicador_impacto'],
                $row['medio_verificacion'],
                $row['id_unidad_administrativa'] ? (int)$row['id_unidad_administrativa'] : null,
                (int)$row['id_accion_centralizada']
            );
        }

        return $results;
    }

    public function allAsync(): array
    {
        $sql = "SELECT * FROM accion_centralizada WHERE eliminado = false ORDER BY codigo_accion_centralizada ASC";
        $result = $this->getAsyncPool()->query($sql);

        $results = [];
        foreach ($result as $row) {
            $results[] = new AccionCentralizada(
                $row['codigo_accion_centralizada'],
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
                $row['indicador_eficacia'],
                $row['indicador_eficiencia'],
                $row['indicador_calidad'],
                $row['indicador_impacto'],
                $row['medio_verificacion'],
                $row['id_unidad_administrativa'] ? (int)$row['id_unidad_administrativa'] : null,
                (int)$row['id_accion_centralizada']
            );
        }

        return $results;
    }

    public function findById(int $id): ?AccionCentralizada
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM accion_centralizada WHERE id_accion_centralizada = :id AND eliminado = false");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new AccionCentralizada(
            $row['codigo_accion_centralizada'],
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
            $row['indicador_eficacia'],
            $row['indicador_eficiencia'],
            $row['indicador_calidad'],
            $row['indicador_impacto'],
            $row['medio_verificacion'],
            $row['id_unidad_administrativa'] ? (int)$row['id_unidad_administrativa'] : null,
            (int)$row['id_accion_centralizada']
        );
    }

    public function save(AccionCentralizada $a): int
    {
        if ($a->id_accion_centralizada) {
            $stmt = $this->getPdo()->prepare("UPDATE accion_centralizada SET 
                codigo_accion_centralizada = :codigo, 
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
                cant_ejecutada_trim_iv = :e4,
                indicador_eficacia = :iefi,
                indicador_eficiencia = :iefe,
                indicador_calidad = :ical,
                indicador_impacto = :iimp,
                medio_verificacion = :mver,
                id_unidad_administrativa = :iua
                WHERE id_accion_centralizada = :id");
            $stmt->execute([
                'codigo' => $a->codigo_accion_centralizada,
                'denominacion' => $a->denominacion,
                'um' => $a->unidad_medida,
                'ai' => $a->anio_inicio,
                'ac' => $a->anio_culm,
                'p1' => $a->cant_programada_trim_i,
                'e1' => $a->cant_ejecutada_trim_i,
                'p2' => $a->cant_programada_trim_ii,
                'e2' => $a->cant_ejecutada_trim_ii,
                'p3' => $a->cant_programada_trim_iii,
                'e3' => $a->cant_ejecutada_trim_iii,
                'p4' => $a->cant_programada_trim_iv,
                'e4' => $a->cant_ejecutada_trim_iv,
                'iefi' => $a->indicador_eficacia,
                'iefe' => $a->indicador_eficiencia,
                'ical' => $a->indicador_calidad,
                'iimp' => $a->indicador_impacto,
                'mver' => $a->medio_verificacion,
                'iua' => $a->id_unidad_administrativa,
                'id' => $a->id_accion_centralizada,
            ]);

            return $a->id_accion_centralizada;
        } else {
            $stmt = $this->getPdo()->prepare("INSERT INTO accion_centralizada (
                codigo_accion_centralizada, denominacion, unidad_medida, anio_inicio, anio_culm, 
                cant_programada_trim_i, cant_ejecutada_trim_i, cant_programada_trim_ii, 
                cant_ejecutada_trim_ii, cant_programada_trim_iii, cant_ejecutada_trim_iii, 
                cant_programada_trim_iv, cant_ejecutada_trim_iv,
                indicador_eficacia, indicador_eficiencia, indicador_calidad, indicador_impacto,
                medio_verificacion, id_unidad_administrativa
            ) VALUES (
                :codigo, :denominacion, :um, :ai, :ac, 
                :p1, :e1, :p2, :e2, :p3, :e3, :p4, :e4,
                :iefi, :iefe, :ical, :iimp, :mver, :iua
            )");
            $stmt->execute([
                'codigo' => $a->codigo_accion_centralizada,
                'denominacion' => $a->denominacion,
                'um' => $a->unidad_medida,
                'ai' => $a->anio_inicio,
                'ac' => $a->anio_culm,
                'p1' => $a->cant_programada_trim_i,
                'e1' => $a->cant_ejecutada_trim_i,
                'p2' => $a->cant_programada_trim_ii,
                'e2' => $a->cant_ejecutada_trim_ii,
                'p3' => $a->cant_programada_trim_iii,
                'e3' => $a->cant_ejecutada_trim_iii,
                'p4' => $a->cant_programada_trim_iv,
                'e4' => $a->cant_ejecutada_trim_iv,
                'iefi' => $a->indicador_eficacia,
                'iefe' => $a->indicador_eficiencia,
                'ical' => $a->indicador_calidad,
                'iimp' => $a->indicador_impacto,
                'mver' => $a->medio_verificacion,
                'iua' => $a->id_unidad_administrativa,
            ]);

            return (int)$this->getPdo()->lastInsertId();
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->getPdo()->prepare("UPDATE accion_centralizada SET eliminado = true WHERE id_accion_centralizada = :id");
        $stmt->execute(['id' => $id]);
    }
}
