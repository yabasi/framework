<?php

namespace Yabasi\Database;

use PDO;
use PDOException;
use Yabasi\Logging\Logger;
use Yabasi\Support\Collection;

class QueryBuilder
{
    // Database connection and logger
    protected Connection $connection;
    protected Logger $logger;

    // Query components
    protected array $bindings = [];
    protected array $query = [
        'type' => 'select',
        'columns' => ['*'],
        'from' => '',
        'where' => [],
        'order' => [],
        'limit' => null,
        'offset' => null
    ];

    // Eager loading and model properties
    protected array $eagerLoad = [];
    protected $model;

    /**
     * Constructor
     */
    public function __construct(Connection $connection, Logger $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * Set the model for the query
     */
    public function setModel($model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the associated model
     */
    public function getModel()
    {
        return $this->model;
    }

    // Query Builders

    /**
     * Set the columns to be selected
     */
    public function select($columns = ['*']): static
    {
        $this->query['type'] = 'select';
        $this->query['columns'] = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Set the table for the query
     */
    public function from($table): static
    {
        $this->query['from'] = $table;
        return $this;
    }

    /**
     * Add a where clause to the query
     * @param string $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function where(string $column, string $operator = null, mixed $value = null): static
    {
        if (func_num_args() == 2) {
            list($value, $operator) = [$operator, '='];
        }

        $this->query['where'][] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        if ($value !== null) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    /**
     * Add a where in clause to the query
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereIn(string $column, array $values): self
    {
        $this->query['where'][] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values
        ];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Add an order by clause to the query
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->query['order'][] = compact('column', 'direction');
        return $this;
    }

    /**
     * Set the limit for the query
     */
    public function limit($limit)
    {
        $this->query['limit'] = $limit;
        return $this;
    }

    /**
     * Set the offset for the query
     */
    public function offset($offset)
    {
        $this->query['offset'] = $offset;
        return $this;
    }

    /**
     * Add eager loading constraints to the query
     */
    public function with($relations)
    {
        $this->eagerLoad = array_merge($this->eagerLoad, is_string($relations) ? func_get_args() : $relations);
        return $this;
    }

    // CRUD Operations

    /**
     * Prepare an insert query
     */
    public function insert(array $data)
    {
        $this->query['type'] = 'insert';
        $this->query['data'] = $data;
        return $this;
    }

    /**
     * Prepare an update query
     */
    public function update(array $data)
    {
        $this->query['type'] = 'update';
        $this->query['data'] = $data;
        return $this;
    }

    /**
     * Prepare a delete query
     */
    public function delete()
    {
        $this->query['type'] = 'delete';
        return $this;
    }

    // Query Execution

    /**
     * Execute the query
     */
    public function execute()
    {
        $sql = $this->toSql();
        $stmt = $this->connection->getPdo()->prepare($sql);
        $this->bindValues($stmt);

        try {
            $this->logger->info("Executing SQL: " . $sql);
            $result = $stmt->execute();
            if ($result === false) {
                throw new PDOException("Query execution failed");
            }

            if ($this->query['type'] === 'insert') {
                return $this->connection->getPdo()->lastInsertId();
            }

            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logger->error("Query execution error: " . $e->getMessage() . "\nSQL: " . $sql);
            throw $e;
        }
    }

    /**
     * Execute the query and return the results
     */
    protected function executeQuery()
    {
        try {
            $sql = $this->toSql();
            $stmt = $this->connection->getPdo()->prepare($sql);
            $this->bindValues($stmt);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error("Database query error: " . $e->getMessage() . "\nSQL: " . $sql);
            throw $e;
        }
    }

    // SQL Compilation

    /**
     * Build the complete SQL query string
     */
    public function toSql()
    {
        $sql = '';
        switch ($this->query['type']) {
            case 'select':
                $sql = $this->compileSelect();
                break;
            case 'insert':
                $sql = $this->compileInsert();
                break;
            case 'update':
                $sql = $this->compileUpdate();
                break;
            case 'delete':
                $sql = $this->compileDelete();
                break;
        }

        $this->logger->info("Generated SQL: " . $sql);
        return $sql;
    }

    protected function compileSelect()
    {
        $sql = $this->compileSelectColumns();
        $sql .= $this->compileFrom();
        $sql .= $this->compileJoins();
        $sql .= $this->compileWhere();
        $sql .= $this->compileGroupBy();
        $sql .= $this->compileHaving();
        $sql .= $this->compileOrderBy();
        $sql .= $this->compileLimit();
        $sql .= $this->compileOffset();
        return $sql;
    }

    protected function compileSelectColumns()
    {
        return "SELECT " . implode(', ', $this->query['columns']);
    }

    protected function compileFrom()
    {
        if (empty($this->query['from'])) {
            throw new \RuntimeException("No table specified for the query.");
        }
        return " FROM " . $this->query['from'];
    }

    protected function compileJoins()
    {
        if (empty($this->query['joins'])) {
            return '';
        }

        return ' ' . implode(' ', $this->query['joins']);
    }

    protected function compileWhere()
    {
        if (empty($this->query['where'])) {
            return '';
        }

        $conditions = [];

        foreach ($this->query['where'] as $where) {
            if ($where['type'] === 'basic') {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
            } elseif ($where['type'] === 'in') {
                $placeholders = implode(',', array_fill(0, count($where['values']), '?'));
                $conditions[] = "{$where['column']} IN ($placeholders)";
            }
        }

        return " WHERE " . implode(' AND ', $conditions);
    }

    protected function compileGroupBy()
    {
        if (empty($this->query['group'])) {
            return '';
        }

        return " GROUP BY " . implode(', ', $this->query['group']);
    }

    protected function compileHaving()
    {
        if (empty($this->query['having'])) {
            return '';
        }

        return " HAVING " . implode(' AND ', $this->query['having']);
    }

    protected function compileOrderBy()
    {
        if (empty($this->query['order'])) {
            return '';
        }

        $orders = array_map(function ($order) {
            return "{$order['column']} {$order['direction']}";
        }, $this->query['order']);

        return " ORDER BY " . implode(', ', $orders);
    }

    protected function compileLimit()
    {
        return isset($this->query['limit']) ? " LIMIT {$this->query['limit']}" : '';
    }

    protected function compileOffset()
    {
        return $this->query['offset'] !== null ? " OFFSET {$this->query['offset']}" : '';
    }

    protected function compileInsert()
    {
        $table = $this->query['from'];
        $columns = implode(', ', array_keys($this->query['data']));
        $values = implode(', ', array_fill(0, count($this->query['data']), '?'));

        return "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
    }

    protected function compileUpdate()
    {
        $table = $this->query['from'];
        $sets = [];
        foreach ($this->query['data'] as $column => $value) {
            $sets[] = "{$column} = ?";
        }
        $set = implode(', ', $sets);

        $sql = "UPDATE {$table} SET {$set}";
        $sql .= $this->compileWhere();

        return $sql;
    }

    protected function compileDelete()
    {
        $table = $this->query['from'];
        $sql = "DELETE FROM {$table}";
        $sql .= $this->compileWhere();

        return $sql;
    }

    // Query Results

    /**
     * Execute the query and get the results
     * @return Collection
     */
    public function get(): Collection
    {
        $results = $this->executeQuery();
        $hydratedResults = $this->hydrateResults($results);
        return new Collection($hydratedResults);
    }

    /**
     * Get the first result from the query
     * @return Model|null
     */
    public function first(): ?Model
    {
        $result = $this->get()->first();
        return $result !== false ? $result : null;
    }

    /**
     * Get the count of the query results
     */
    public function count($column = '*')
    {
        $originalColumns = $this->query['columns'];
        $this->query['columns'] = ["COUNT($column) as count"];

        $result = $this->executeQuery();

        // Restore original columns
        $this->query['columns'] = $originalColumns;

        return $result ? (int) $result[0]['count'] : 0;
    }

    // Helper Methods

    protected function hydrateResults(array $results): array
    {
        if (!$this->model) {
            return $results;
        }

        return array_map(function ($item) {
            $modelClass = get_class($this->model);
            return new $modelClass($item);
        }, $results);
    }

    /**
     * Perform eager loading on the given models
     */
    public function eagerLoadRelations(array $models)
    {
        foreach ($this->eagerLoad as $relation) {
            $this->model->$relation()->addEagerConstraints($models);
            $this->model->$relation()->initRelation($models, $relation);
            $results = $this->model->$relation()->getResults();
            $this->model->$relation()->match($models, new Collection($results), $relation);
        }

        return $models;
    }

    /**
     * Bind the query values to the prepared statement
     */
    protected function bindValues($stmt)
    {
        $index = 1;
        if ($this->query['type'] === 'insert' || $this->query['type'] === 'update') {
            foreach ($this->query['data'] as $value) {
                $stmt->bindValue($index++, $value, $this->getPdoParamType($value));
            }
        }

        if (!empty($this->query['where'])) {
            foreach ($this->query['where'] as $condition) {
                if ($condition['type'] === 'basic' && $condition['value'] !== null) {
                    $stmt->bindValue($index++, $condition['value'], $this->getPdoParamType($condition['value']));
                } elseif ($condition['type'] === 'in') {
                    foreach ($condition['values'] as $value) {
                        $stmt->bindValue($index++, $value, $this->getPdoParamType($value));
                    }
                }
            }
        }
    }

    /**
     * Get the PDO data type for a given value
     */
    protected function getPdoParamType($value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            is_null($value) => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }
}
