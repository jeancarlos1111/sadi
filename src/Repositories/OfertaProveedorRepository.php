<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\OfertaProveedor;
use PDO;

class OfertaProveedorRepository extends Repository
{
    protected function getTable(): string
    {
        return 'oferta_proveedor';
    }

    public function getByProceso(int $idProceso): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("
            SELECT o.*, p.razon_social, p.rnc, p.fecha_vencimiento_rnc 
            FROM oferta_proveedor o
            JOIN proveedor p ON o.id_proveedor = p.id_proveedor
            WHERE o.id_proceso = ? AND o.eliminado = false
            ORDER BY o.monto_ofertado ASC
        ");
        $stmt->execute([$idProceso]);

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $entity = $this->mapRowToEntity($row);
            $results[] = [
                'entity' => $entity,
                'razon_social' => $row['razon_social'],
                'rnc' => $row['rnc'],
                'fecha_vencimiento_rnc' => $row['fecha_vencimiento_rnc']
            ];
        }
        return $results;
    }

    public function findById(int $id): ?OfertaProveedor
    {
        $row = $this->query()->where('id_oferta', '=', $id)->where('eliminado', '=', 'false')->first();
        if (!$row) {
            return null;
        }
        return $this->mapRowToEntity($row);
    }

    public function save(OfertaProveedor $oferta): int
    {
        $data = [
            'id_proceso' => $oferta->idProceso,
            'id_proveedor' => $oferta->idProveedor,
            'fecha_presentacion' => $oferta->fechaPresentacion,
            'monto_ofertado' => $oferta->montoOfertado,
            'descripcion_oferta' => $oferta->descripcionOferta,
            'cumple_tecnicamente' => $oferta->cumpleTecnicamente ? 'true' : 'false',
            'es_ganador' => $oferta->esGanador ? 'true' : 'false',
            'observaciones' => $oferta->observaciones,
        ];

        if ($oferta->id > 0) {
            $this->query()->where('id_oferta', '=', $oferta->id)->update($data);
            return $oferta->id;
        }

        return (int)$this->query()->insert($data);
    }

    public function marcarGanador(int $idOferta): void
    {
        $this->query()->where('id_oferta', '=', $idOferta)->update(['es_ganador' => 'true']);
    }

    public function delete(int $id): void
    {
        $this->query()->where('id_oferta', '=', $id)->update(['eliminado' => 'true']);
    }

    private function mapRowToEntity(array $row): OfertaProveedor
    {
        return new OfertaProveedor(
            (int)$row['id_oferta'],
            (int)$row['id_proceso'],
            (int)$row['id_proveedor'],
            $row['fecha_presentacion'],
            (float)$row['monto_ofertado'],
            $row['descripcion_oferta'],
            (bool)$row['cumple_tecnicamente'],
            (bool)$row['es_ganador'],
            $row['observaciones'],
            (bool)$row['eliminado']
        );
    }
}
