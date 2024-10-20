<?php

namespace Yabasi\Database;

use PDO;
use Yabasi\Config\Config;
use Yabasi\Database\Schema\Schema;
use Yabasi\Exceptions\DatabaseConnectionException;
use Yabasi\Logging\Logger;

class Connection
{
    protected ?PDO $pdo = null;
    protected array $config;
    protected Logger $logger;
    protected ?SchemaBuilder $schemaBuilder = null;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config->get('database', []);
        $this->logger = $logger;
    }

    public function getPdo(): ?PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createConnection();
        }

        return $this->pdo;
    }

    protected function createConnection(): ?PDO
    {
        $database = $this->config['database'] ?? '';
        if (empty($database)) {
            return null;
        }

        $dsn = $this->getDsn();
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $options = $this->config['options'] ?? [];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (\PDOException $e) {
            $this->logger->error("Database connection failed: " . $e->getMessage());
            throw new DatabaseConnectionException("Could not connect to the database. Please check your configuration.");
        }
    }

    protected function getDsn(): string
    {
        $driver = $this->config['driver'] ?? 'mysql';
        $host = $this->config['host'] ?? 'localhost';
        $database = $this->config['database'] ?? 'yabasi';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        return sprintf(
            '%s:host=%s;dbname=%s;charset=%s',
            $driver,
            $host,
            $database,
            $charset
        );
    }

    public function query(): ?QueryBuilder
    {
        if ($this->getPdo() === null) {
            return null;
        }
        return new QueryBuilder($this, $this->logger);
    }

    public function schema()
    {
        if ($this->getPdo() === null) {
            return null;
        }
        return new Schema($this);
    }

    public function table($table)
    {
        return new Query\Builder($this, $table);
    }

    public function getDatabaseName(): string
    {
        return $this->config->get('database.database');
    }

    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->getPdo()->prepare($query);
        $statement->execute($bindings);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(string $query, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $query, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        return $statement->rowCount();
    }

    public function delete(string $query, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        return $statement->rowCount();
    }

    public function statement($sql, $params = []): bool
    {
        try {
            $statement = $this->getPdo()->prepare($sql);
            return $statement->execute($params);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database error: " . $e->getMessage());
        }
    }

    public function getSchemaBuilder()
    {
        return new SchemaBuilder($this);
    }
}