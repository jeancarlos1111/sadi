<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\FuenteFinanciamiento;
use PDO;

class FuenteFinanciamientoRepository extends Repository
{
    protected function getTable(): string
    {
        return 'fuente_financiamiento';
    }

    public function all(string $search = ''): array
    {
        $db = $this->getPdo();
        $sql = "SELECT * FROM fuente_financiamiento WHERE eliminado = false";
        if ($search !== '') {
            $sql .= " AND denominacion ILIKE :s";
        }
        $sql .= " ORDER BY denominacion ASC";

        $stmt = $db->prepare($sql);
        if ($search !== '') {
            $stmt->bindValue(':s', "%$search%");
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = new FuenteFinanciamiento(
                $row['denominacion'],
                (int)$row['id_fuente_financiamiento']
            );
        }

        return $results;
    }

    public function allAsync(): array
    {
        $result = $this->getAsyncPool()->query("SELECT * FROM fuente_financiamiento WHERE eliminado = false ORDER BY denominacion ASC");
        $results = [];
        foreach ($result as $row) {
            $results[] = new FuenteFinanciamiento(
                $row['denominacion'],
                (int)$row['id_fuente_financiamiento']
            );
        }
        return $results;
    }

    public function findById(int $id): ?FuenteFinanciamiento
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM fuente_financiamiento WHERE id_fuente_financiamiento = :id AND eliminado = false");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new FuenteFinanciamiento(
            $row['denominacion'],
            (int)$row['id_fuente_financiamiento']
        );
    }

    public function save(FuenteFinanciamiento $item): bool
    {
        if ($item->id) {
            return $this->query()
                ->where('id_fuente_financiamiento', '=', $item->id)
                ->update([
                    'denominacion' => $item->denominacion,
                ]);
        } else {
            return $this->query()->insert([
                'denominacion' => $item->denominacion,
            ]);
        }
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_fuente_financiamiento', '=', $id)->update(['eliminado' => 'true']);
    }
}
