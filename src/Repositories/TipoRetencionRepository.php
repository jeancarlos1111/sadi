<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\TipoRetencion;
use PDO;

class TipoRetencionRepository extends Repository
{
    protected function getTable(): string
    {
        return 'tipo_retencion';
    }

    public function findById(int $id): ?TipoRetencion
    {
        $row = $this->query()->where('id_tipo_retencion', '=', $id)->first();
        if (!$row) {
            return null;
        }
        return $this->mapRowToEntity($row);
    }
    
    public function findByCodigo(string $codigo): ?TipoRetencion
    {
        $row = $this->query()->where('codigo', '=', $codigo)->first();
        if (!$row) {
            return null;
        }
        return $this->mapRowToEntity($row);
    }

    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "SELECT * FROM tipo_retencion";
        
        if ($search !== '') {
            $sql .= " WHERE (codigo ILIKE :search OR denominacion ILIKE :search)";
        }
        $sql .= " ORDER BY denominacion ASC";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = $this->mapRowToEntity($row);
        }
        return $results;
    }
    
    public function allActivos(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT * FROM tipo_retencion WHERE activo = true ORDER BY denominacion ASC");
        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = $this->mapRowToEntity($row);
        }
        return $results;
    }

    public function save(TipoRetencion $tipo): int
    {
        $data = [
            'codigo' => $tipo->codigo,
            'denominacion' => $tipo->denominacion,
            'porcentaje' => $tipo->porcentaje,
            'sustraendo' => $tipo->sustraendo,
            'aplica_a' => $tipo->aplicaA,
            'activo' => $tipo->activo ? 'true' : 'false',
        ];

        if ($tipo->id > 0) {
            $this->query()->where('id_tipo_retencion', '=', $tipo->id)->update($data);
            return $tipo->id;
        }

        return (int)$this->query()->insert($data);
    }
    
    public function toggleActivo(int $id): void
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("UPDATE tipo_retencion SET activo = NOT activo WHERE id_tipo_retencion = ?");
        $stmt->execute([$id]);
    }

    private function mapRowToEntity(array $row): TipoRetencion
    {
        return new TipoRetencion(
            (int)$row['id_tipo_retencion'],
            $row['codigo'],
            $row['denominacion'],
            (float)$row['porcentaje'],
            (float)$row['sustraendo'],
            $row['aplica_a'],
            (bool)$row['activo']
        );
    }
}
