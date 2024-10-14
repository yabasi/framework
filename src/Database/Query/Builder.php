<?php

namespace Yabasi\Database\Query;

use Yabasi\Database\Connection;

class Builder
{
    protected Connection $connection;
    protected string $table;
    protected array $wheres = [];
    protected array $orders = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected $from;

    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function where($column, $operator = null, $value = null): self
    {
        // If only two parameters are given, assume the operator is '='
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value');
        return $this;
    }

    public function max($column)
    {
        if (!$this->from) {
            throw new \RuntimeException("No table specified. Call from() before max().");
        }
        $query = "SELECT MAX({$column}) as max_value FROM {$this->from}";
        $result = $this->connection->select($query);
        return $result[0]['max_value'] ?? null;
    }

    public function from($table)
    {
        $this->from = $table;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $query = $this->toSql();
        $bindings = $this->getBindings();
        return $this->connection->select($query, $bindings);
    }

    public function first()
    {
        $result = $this->limit(1)->get();
        return $result ? $result[0] : null;
    }

    public function insert(array $values): int
    {
        $query = "INSERT INTO {$this->table} (" . implode(', ', array_keys($values)) . ") VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
        return $this->connection->insert($query, array_values($values));
    }

    public function update(array $values): int
    {
        $set = implode(', ', array_map(fn($key) => "{$key} = ?", array_keys($values)));
        $query = "UPDATE {$this->table} SET {$set}" . $this->compileWheres();
        return $this->connection->update($query, array_merge(array_values($values), $this->getWhereBindings()));
    }

    public function delete(): int
    {
        $query = "DELETE FROM {$this->table}" . $this->compileWheres();
        return $this->connection->delete($query, $this->getWhereBindings());
    }

    protected function toSql(): string
    {
        $query = "SELECT * FROM {$this->table}";
        $query .= $this->compileWheres();
        $query .= $this->compileOrders();
        $query .= $this->compileLimit();
        $query .= $this->compileOffset();
        return $query;
    }

    protected function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $wheresClauses = array_map(function ($where) {
            return "{$where['column']} {$where['operator']} ?";
        }, $this->wheres);

        return ' WHERE ' . implode(' AND ', $wheresClauses);
    }

    protected function compileOrders(): string
    {
        if (empty($this->orders)) {
            return '';
        }

        $orderClauses = array_map(function ($order) {
            return "{$order['column']} {$order['direction']}";
        }, $this->orders);

        return ' ORDER BY ' . implode(', ', $orderClauses);
    }

    protected function compileLimit(): string
    {
        return $this->limit !== null ? " LIMIT {$this->limit}" : '';
    }

    protected function compileOffset(): string
    {
        return $this->offset !== null ? " OFFSET {$this->offset}" : '';
    }

    protected function getBindings(): array
    {
        return $this->getWhereBindings();
    }

    protected function getWhereBindings(): array
    {
        return array_column($this->wheres, 'value');
    }

    public function pluck(string $column): array
    {
        $results = $this->get();
        return array_column($results, $column);
    }
}