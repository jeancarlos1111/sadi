<?php

namespace App\Database;

use PDO;

/**
 * Patrón Builder para construir consultas SQL programáticamente,
 * previendo Inyección SQL mediante bind values automáticos.
 */
class QueryBuilder
{
    private PDO $connection;
    private string $table = '';
    private array $selects = ['*'];
    private array $wheres = [];
    private array $bindings = [];
    private array $orderBys = [];
    private string $limit = '';

    public function __construct(?PDO $connection = null)
    {
        // Si no se inyecta directo, obtenemos del Singleton por defecto
        $this->connection = $connection ?? Connection::getInstance();
    }

    public static function table(string $table): self
    {
        $builder = new self();
        $builder->table = $table;

        return $builder;
    }

    public function select(string ...$columns): self
    {
        $this->selects = $columns;

        return $this;
    }

    public function where(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = "$column $operator ?";
        $this->bindings[] = $value;

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBys[] = "$column $direction";

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = " LIMIT $limit";

        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if (!empty($this->orderBys)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBys);
        }

        $sql .= $this->limit;

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();

        return count($results) > 0 ? $results[0] : null;
    }

    public function insert(array $data): string|false
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->connection->prepare($sql);
        if ($stmt->execute(array_values($data))) {
            return $this->connection->lastInsertId();
        }

        return false;
    }

    public function update(array $data): bool
    {
        $sets = [];
        $updateBindings = [];
        foreach ($data as $column => $value) {
            $sets[] = "$column = ?";
            $updateBindings[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $this->connection->prepare($sql);
        $allBindings = array_merge($updateBindings, $this->bindings);

        return $stmt->execute($allBindings);
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $stmt = $this->connection->prepare($sql);

        return $stmt->execute($this->bindings);
    }
}
