<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Beneficiario;

class BeneficiarioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'beneficiario';
    }

    /**
     * @return Beneficiario[]
     */
    public function all(string $search = ''): array
    {
        $query = $this->query()
                      ->select('*')
                      ->where('eliminado', '=', 'false')
                      ->orderBy('apellidos', 'ASC')
                      ->orderBy('nombres', 'ASC');

        if ($search !== '') {
            $query->where('nombres', 'LIKE', "%$search%") // Nota: nuestro QB básico actual usa AND. Para un OR complejo podríamos necesitar expandir el QB.
                  // ->orWhere('apellidos', 'LIKE', "%$search%")
                  // Para propósitos de este demo y simplicidad del QB inicial, lo haremos con SQL crudo o expandiremos el QB.
                  // Vamos a dejar que el QB lo maneje con SQL en el "column" como workaround o ampliamos el QB.
            ;
        }

        // Por ahora, para mantener la lógica exacta del search con OR:
        if ($search !== '') {
            $db = $this->getPdo();
            $sql = "SELECT * FROM beneficiario WHERE eliminado = false AND (nombres ILIKE :s OR apellidos ILIKE :s OR cedula ILIKE :s) ORDER BY apellidos, nombres";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':s', "%$search%");
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } else {
            $rows = $query->get();
        }

        return array_map(fn ($row) => $this->mapRowToEntity($row), $rows);
    }

    public function paginate(...$args): array
    {
        $search = $args[0] ?? '';
        $page = $args[1] ?? 1;
        $perPage = $args[2] ?? 15;

        $db = $this->getPdo();
        $sql = "SELECT * FROM beneficiario WHERE eliminado = false";

        $bindings = [];
        if ($search !== '') {
            $sql .= " AND (nombres ILIKE :search OR apellidos ILIKE :search OR cedula ILIKE :search)";
            $bindings[':search'] = "%$search%";
        }
        $sql .= " ORDER BY apellidos ASC, nombres ASC";

        $paginator = \App\Database\Paginator::paginateRaw($db, $sql, $bindings, $page, $perPage);

        $results = [];
        foreach ($paginator['data'] as $row) {
            $results[] = $this->mapRowToEntity($row);
        }

        $paginator['data'] = $results;

        return $paginator;
    }

    public function findById(int $id): ?Beneficiario
    {
        $row = $this->query()
                    ->select('*')
                    ->where('id_beneficiario', '=', $id)
                    ->where('eliminado', '=', 'false')
                    ->first();

        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function save(Beneficiario $beneficiario): bool
    {
        $data = [
            'cedula' => $beneficiario->cedula,
            'nombres' => $beneficiario->nombres,
            'apellidos' => $beneficiario->apellidos,
            'direccion' => $beneficiario->direccion,
            'telefono' => $beneficiario->telefono,
            'email' => $beneficiario->email,
            'id_codigo_contable' => $beneficiario->idCodigoContable,
        ];

        if ($beneficiario->id) {
            $this->query()->where('id_beneficiario', '=', $beneficiario->id)->update($data);

            return true;
        }

        $id = $this->query()->insert($data);
        if ($id) {
            // Un entity puro idealmente tendría un setId, aquí asumimos inmutabilidad básica o que se recrea
            // pero para el MVC clásico solo retornamos bool
            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_beneficiario', '=', $id)->update(['eliminado' => 'true']);
    }

    private function mapRowToEntity(array $row): Beneficiario
    {
        return new Beneficiario(
            $row['cedula'],
            $row['nombres'],
            $row['apellidos'],
            $row['direccion'],
            $row['telefono'],
            $row['email'] ?? null,
            $row['id_codigo_contable'] ? (int)$row['id_codigo_contable'] : null,
            (int)$row['id_beneficiario']
        );
    }
}
