<?php

namespace Yabasi\Database;

use PDO;

class SchemaBuilder
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function disableForeignKeyConstraints()
    {
        $this->connection->statement('SET FOREIGN_KEY_CHECKS=0');
    }

    public function enableForeignKeyConstraints()
    {
        $this->connection->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function getColumnListing(string $table): array
    {
        $sql = "SHOW COLUMNS FROM `{$table}`";
        $stmt = $this->connection->getPdo()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getColumnType(string $table, string $column): string
    {
        $sql = "SHOW COLUMNS FROM `{$table}` WHERE Field = :column";
        $stmt = $this->connection->getPdo()->prepare($sql);
        $stmt->execute(['column' => $column]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['Type'] : '';
    }
}