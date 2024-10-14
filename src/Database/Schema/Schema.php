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
        $sql = "SHOW TABLES LIKE :table";
        $statement = $this->connection->getPdo()->prepare($sql);
        $statement->execute(['table' => $table]);
        return $statement->rowCount() > 0;
    }

    public function create(string $table, \Closure $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = $this->toSql($blueprint);
        $this->connection->getPdo()->exec($sql);
    }

    public function table(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $this->build($blueprint);
    }

    public function drop(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`";
        $this->connection->getPdo()->exec($sql);
    }

    public function dropIfExists(string $table)
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`";
        $this->connection->getPdo()->exec($sql);
    }

    protected function build(Blueprint $blueprint): void
    {
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

        $columnsSql = implode(", ", $columnDefinitions);

        return "CREATE TABLE `{$table}` ({$columnsSql})";
    }

    protected function getPrimaryKeyDefinition(Blueprint $blueprint): ?string
    {
        $primaryKey = $blueprint->getPrimaryKey();
        if ($primaryKey) {
            return "PRIMARY KEY (`{$primaryKey}`)";
        }
        return null;
    }

    protected function getColumnDefinition(array $column): string
    {
        $sql = "`{$column['name']}` {$column['type']}";

        if (isset($column['length'])) {
            $sql .= "({$column['length']})";
        }

        if (isset($column['unsigned']) && $column['unsigned']) {
            $sql .= " UNSIGNED";
        }

        if (isset($column['autoIncrement']) && $column['autoIncrement']) {
            $sql .= " AUTO_INCREMENT PRIMARY KEY";
        }

        if (isset($column['nullable']) && $column['nullable']) {
            $sql .= " NULL";
        } else {
            $sql .= " NOT NULL";
        }

        if (isset($column['default'])) {
            $sql .= " DEFAULT '{$column['default']}'";
        }

        return $sql;
    }

    protected function getColumnType(string $type): string
    {
        $typeMap = [
            'id' => 'BIGINT',
            'bigInteger' => 'BIGINT',
            'integer' => 'INT',
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'boolean' => 'TINYINT(1)',
            'date' => 'DATE',
            'dateTime' => 'DATETIME',
            'timestamp' => 'TIMESTAMP',
            'decimal' => 'DECIMAL',
            'float' => 'FLOAT',
            'json' => 'JSON',
        ];

        return $typeMap[$type] ?? 'VARCHAR';
    }
}