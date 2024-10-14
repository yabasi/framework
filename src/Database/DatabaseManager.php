<?php

namespace Yabasi\Database;

class DatabaseManager
{
    protected Connection $connection;
    protected string $dumpPath;

    public function __construct(Connection $connection, string $dumpPath = null)
    {
        $this->connection = $connection;
        $this->dumpPath = $dumpPath ?? BASE_PATH . '/data/database/dumps';
    }

    public function dump(): string
    {
        $tables = $this->getTables();
        $dump = '';

        foreach ($tables as $table) {
            $dump .= $this->getTableStructure($table);
            $dump .= $this->getTableData($table);
        }

        $filename = 'yabasi_dump_' . date('Y-m-d_H-i-s') . '.sql';
        $fullPath = $this->dumpPath . '/' . $filename;

        if (!is_dir($this->dumpPath)) {
            mkdir($this->dumpPath, 0755, true);
        }

        file_put_contents($fullPath, $dump);

        return $fullPath;
    }

    public function restore(string $dumpFile): void
    {
        if (!file_exists($dumpFile)) {
            throw new \RuntimeException("Dump file not found: $dumpFile");
        }

        $sql = file_get_contents($dumpFile);
        $this->connection->statement($sql);
    }

    protected function getTables(): array
    {
        $result = $this->connection->select("SHOW TABLES");
        return array_column($result, 'Tables_in_' . $this->connection->getDatabaseName());
    }

    protected function getTableStructure(string $table): string
    {
        $result = $this->connection->select("SHOW CREATE TABLE `$table`");
        return $result[0]['Create Table'] . ";\n\n";
    }

    protected function getTableData(string $table): string
    {
        $data = $this->connection->select("SELECT * FROM `$table`");
        $dump = '';

        foreach ($data as $row) {
            $values = array_map(function ($value) {
                return $this->connection->getPdo()->quote($value);
            }, $row);
            $dump .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
        }

        return $dump . "\n";
    }
}