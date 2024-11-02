<?php

namespace Yabasi\Database\Schema;

use Yabasi\Database\Connection;

class Schema
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function hasTable(string $table): bool
    {
        if ($this->connection->getPdo() === null) {
            return false;
        }

        $sql = "SHOW TABLES LIKE :table";
        $statement = $this->connection->getPdo()->prepare($sql);
        $statement->execute(['table' => $table]);
        return $statement->rowCount() > 0;
    }

    public function create(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = $this->toSql($blueprint);
        $this->connection->getPdo()->exec($sql);
    }

    protected function toSql(Blueprint $blueprint): string
    {
        $table = $blueprint->getTable();
        $columns = $blueprint->getColumns();

        $columnDefinitions = [];
        foreach ($columns as $column) {
            $columnDefinitions[] = $this->getColumnDefinition($column);
        }

        return sprintf(
            "CREATE TABLE IF NOT EXISTS `%s` (%s) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            $table,
            implode(", ", $columnDefinitions)
        );
    }

    protected function getColumnDefinition(array $column): string
    {
        $sql = "`{$column['name']}` " . $this->getColumnType($column);

        if (isset($column['length']) && !in_array($column['type'], ['text', 'mediumtext', 'longtext'])) {
            $sql .= "({$column['length']})";
        }

        if (isset($column['unsigned']) && $column['unsigned']) {
            $sql .= " UNSIGNED";
        }

        if (isset($column['nullable']) && $column['nullable']) {
            $sql .= " NULL";
        } else {
            $sql .= " NOT NULL";
        }

        if (isset($column['autoIncrement']) && $column['autoIncrement']) {
            $sql .= " AUTO_INCREMENT";
        }

        if (isset($column['default'])) {
            $sql .= " DEFAULT " . $this->getDefaultValue($column['default']);
        } elseif (isset($column['nullable']) && $column['nullable']) {
            $sql .= " DEFAULT NULL";
        }

        if (isset($column['primary']) && $column['primary']) {
            $sql .= " PRIMARY KEY";
        }

        return $sql;
    }

    protected function getColumnType(array $column): string
    {
        return match ($column['type']) {
            'id' => 'BIGINT',
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'integer' => 'INT',
            'bigInteger' => 'BIGINT',
            'boolean' => 'TINYINT(1)',
            'date' => 'DATE',
            'dateTime' => 'DATETIME',
            'timestamp' => 'TIMESTAMP',
            'time' => 'TIME',
            'float' => 'FLOAT',
            'decimal' => 'DECIMAL',
            'json' => 'JSON',
            default => 'VARCHAR',
        };
    }

    protected function getDefaultValue($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        if ($value === 'CURRENT_TIMESTAMP') {
            return $value;
        }

        return "'" . addslashes($value) . "'";
    }

    public function table(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $this->build($blueprint);
    }

    protected function build(Blueprint $blueprint): void
    {
        $sql = $this->toSql($blueprint);
        $this->connection->getPdo()->exec($sql);
    }

    public function drop(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`";
        $this->connection->getPdo()->exec($sql);
    }

    public function dropIfExists(string $table): void
    {
        $this->drop($table);
    }
}