<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\RequisicionBienes;
use Exception;
use PDO;

class RequisicionBienesRepository extends Repository
{
    protected function getTable(): string
    {
        return 'requisicion_bienes';
    }

    /**
     * @return array
     */
    public function all(string $search = '', string $mes = ''): array
    {
        $db = $this->getPdo();
        $sql = "
            SELECT 
                R.id_requisicion_bienes, R.fecha_rb, R.concepto_rb, R.id_estructura_presupuestaria,
                modulo_presupuesto_buscar_formatear_estructura_presupuestaria(R.id_estructura_presupuestaria) AS programatica 
            FROM requisicion_bienes AS R
            WHERE R.eliminado = false
        ";

        if ($mes !== '' && $mes >= '01' && $mes <= '12') {
            $sql .= " AND to_char(R.fecha_rb, 'MM') = :mes";
        }

        if ($search !== '') {
            $sql .= " AND (
                modulo_presupuesto_buscar_formatear_estructura_presupuestaria(R.id_estructura_presupuestaria) ILIKE :search
                OR R.concepto_rb ILIKE :search
                OR to_char(R.fecha_rb, 'DD/MM/YYYY') ILIKE :search
                OR CAST(R.id_requisicion_bienes AS TEXT) ILIKE :search
            )";
        }
        $sql .= " ORDER BY R.fecha_rb DESC, R.id_requisicion_bienes ASC";

        $stmt = $db->prepare($sql);
        if ($mes !== '' && $mes >= '01' && $mes <= '12') {
            $stmt->bindValue(':mes', $mes);
        }
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $req = new RequisicionBienes(
                $row['fecha_rb'],
                $row['concepto_rb'],
                (int)$row['id_estructura_presupuestaria'],
                [],
                (int)$row['id_requisicion_bienes']
            );
            $results[] = [
                'entity' => $req,
                'programatica' => $row['programatica'],
            ];
        }

        return $results;
    }

    public function findById(int $id): ?RequisicionBienes
    {
        $db = $this->getPdo();

        // Fetch main record
        $stmt = $db->prepare("SELECT * FROM requisicion_bienes WHERE id_requisicion_bienes = :id AND eliminado = false");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Fetch articles
        $stmtArt = $db->prepare("
            SELECT AR.id_articulo, AR.cantidad_arb, A.denominacion_a, UDM.denominacion_udm 
            FROM articulo_requisicion_bienes AS AR
            JOIN articulo AS A ON AR.id_articulo = A.id_articulo
            JOIN unidades_de_medida AS UDM ON A.id_unidades_de_medida = UDM.id_unidades_de_medida
            WHERE AR.id_requisicion_bienes = :id
        ");
        $stmtArt->execute(['id' => $id]);
        $articulos = $stmtArt->fetchAll(PDO::FETCH_ASSOC);

        return new RequisicionBienes(
            $row['fecha_rb'],
            $row['concepto_rb'],
            (int)$row['id_estructura_presupuestaria'],
            $articulos,
            (int)$row['id_requisicion_bienes']
        );
    }

    public function save(RequisicionBienes $item): bool
    {
        $db = $this->getPdo();

        try {
            $db->beginTransaction();

            if ($item->id) {
                // Update Requisicion
                $stmt = $db->prepare("
                    UPDATE requisicion_bienes SET 
                        fecha_rb = :fecha, concepto_rb = :concepto, id_estructura_presupuestaria = :estructura
                    WHERE id_requisicion_bienes = :id
                ");
                $stmt->execute([
                    'fecha'      => $item->fecha,
                    'concepto'   => $item->concepto,
                    'estructura' => $item->idEstructuraPresupuestaria,
                    'id'         => $item->id,
                ]);

                // Delete old articles
                $stmtDel = $db->prepare("DELETE FROM articulo_requisicion_bienes WHERE id_requisicion_bienes = :id");
                $stmtDel->execute(['id' => $item->id]);

                $idRequisicion = $item->id;
            } else {
                // Insert Requisicion
                $stmt = $db->prepare("
                    INSERT INTO requisicion_bienes 
                    (fecha_rb, concepto_rb, id_estructura_presupuestaria)
                    VALUES (:fecha, :concepto, :estructura)
                ");
                $stmt->execute([
                    'fecha'      => $item->fecha,
                    'concepto'   => $item->concepto,
                    'estructura' => $item->idEstructuraPresupuestaria,
                ]);

                $idRequisicion = (int)$db->lastInsertId();
            }

            // Insert new articles
            $stmtArt = $db->prepare("
                INSERT INTO articulo_requisicion_bienes 
                (id_requisicion_bienes, id_articulo, cantidad_arb)
                VALUES (:req, :art, :cant)
            ");

            foreach ($item->articulos as $art) {
                $stmtArt->execute([
                    'req'  => $idRequisicion,
                    'art'  => $art['id_articulo'],
                    'cant' => $art['cantidad'],
                ]);
            }

            $db->commit();

            return true;
        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_requisicion_bienes', '=', $id)->update(['eliminado' => 1]);
    }
}
