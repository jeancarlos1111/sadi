<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\ProcesoContratacion;
use PDO;

class ProcesoContratacionRepository extends Repository
{
    protected function getTable(): string
    {
        return 'proceso_contratacion';
    }

    public function findById(int $id): ?ProcesoContratacion
    {
        $row = $this->query()->where('id_proceso', '=', $id)->where('eliminado', '=', 'false')->first();
        if (!$row) {
            return null;
        }
        return $this->mapRowToEntity($row);
    }

    public function all(string $search = '', int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $db = $this->getPdo();
        
        $sql = "SELECT p.*, o.numero_orden 
                FROM proceso_contratacion p
                LEFT JOIN orden_de_compra o ON p.id_orden_de_compra = o.id_orden_de_compra
                WHERE p.eliminado = false";
        
        if ($search !== '') {
            $sql .= " AND (p.numero_expediente ILIKE :search OR p.descripcion ILIKE :search)";
        }
        $sql .= " ORDER BY p.id_proceso DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();
        
        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $entity = $this->mapRowToEntity($row);
            // Si necesitamos la orden de compra en la vista, podemos devolver un array o un DTO enriquecido
            // Para mantenerlo simple según la guía, devolvemos un array asociativo con datos combinados
            $results[] = [
                'entity' => $entity,
                'numero_orden' => $row['numero_orden'] ?? null
            ];
        }
        return $results;
    }

    public function countAll(string $search = ''): int
    {
        $db = $this->getPdo();
        $sql = "SELECT COUNT(*) FROM proceso_contratacion WHERE eliminado = false";
        if ($search !== '') {
            $sql .= " AND (numero_expediente ILIKE :search OR descripcion ILIKE :search)";
        }
        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function save(ProcesoContratacion $proceso): int
    {
        $data = [
            'numero_expediente' => $proceso->numeroExpediente,
            'descripcion' => $proceso->descripcion,
            'modalidad' => $proceso->modalidad,
            'monto_estimado' => $proceso->montoEstimado,
            'id_orden_de_compra' => $proceso->idOrdenCompra,
            'justificacion_legal' => $proceso->justificacionLegal,
            'crs_aplicable' => $proceso->crsAplicable ? 'true' : 'false',
            'numero_crs' => $proceso->numeroCrs,
            'estatus' => $proceso->estatus,
            'fecha_apertura' => $proceso->fechaApertura,
            'fecha_cierre' => $proceso->fechaCierre,
            'id_usuario_creador' => $proceso->idUsuarioCreador,
        ];

        if ($proceso->id > 0) {
            $this->query()->where('id_proceso', '=', $proceso->id)->update($data);
            return $proceso->id;
        }

        return (int)$this->query()->insert($data);
    }

    public function adjudicar(int $idProceso, int $idOfertaGanadora): void
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            // Marcar oferta como ganadora
            $stmt = $db->prepare("UPDATE oferta_proveedor SET es_ganador = true WHERE id_oferta = ?");
            $stmt->execute([$idOfertaGanadora]);

            // Cambiar estatus del proceso a ADJUDICADO
            $stmt2 = $db->prepare("UPDATE proceso_contratacion SET estatus = 'ADJUDICADO' WHERE id_proceso = ?");
            $stmt2->execute([$idProceso]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $this->query()->where('id_proceso', '=', $id)->update(['eliminado' => 'true']);
    }

    private function mapRowToEntity(array $row): ProcesoContratacion
    {
        return new ProcesoContratacion(
            (int)$row['id_proceso'],
            $row['numero_expediente'],
            $row['descripcion'],
            $row['modalidad'],
            (float)$row['monto_estimado'],
            $row['id_orden_de_compra'] ? (int)$row['id_orden_de_compra'] : null,
            $row['justificacion_legal'],
            (bool)$row['crs_aplicable'],
            $row['numero_crs'],
            $row['estatus'],
            $row['fecha_apertura'],
            $row['fecha_cierre'],
            $row['id_usuario_creador'] ? (int)$row['id_usuario_creador'] : null,
            (bool)$row['eliminado']
        );
    }
}
