<?php

namespace Yabasi\Database;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Yabasi\Config\Config;

class Database
{
    /** @var PDO */
    private $connection;

    /** @var array */
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Database constructor.
     *
     * @param Config $config
     * @throws RuntimeException
     */
    public function __construct(Config $config)
    {
        $this->connect($config);
    }

    /**
     * Prepare an SQL statement.
     *
     * @param string $sql
     * @return PDOStatement
     * @throws PDOException
     */
    public function prepare(string $sql): PDOStatement
    {
        return $this->connection->prepare($sql);
    }

    /**
     * Execute a prepared statement with parameters.
     *
     * @param PDOStatement $stmt
     * @param array $params
     * @return PDOStatement
     * @throws PDOException
     */
    public function executePrepared(PDOStatement $stmt, array $params = []): PDOStatement
    {
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Establish database connection.
     *
     * @param Config $config
     * @throws RuntimeException
     */
    private function connect(Config $config): void
    {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=utf8mb4",
            $config->get('database.host'),
            $config->get('database.name')
        );

        try {
            $this->connection = new PDO(
                $dsn,
                $config->get('database.user'),
                $config->get('database.pass'),
                $this->options
            );
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a query and return the statement.
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     * @throws PDOException
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row from the database.
     *
     * @param string $sql
     * @param array $params
     * @return array|false
     * @throws PDOException
     */
    public function fetchOne(string $sql, array $params = []): bool|array
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Fetch multiple rows from the database.
     *
     * @param string $sql
     * @param array $params
     * @return array
     * @throws PDOException
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param string $sql
     * @param array $params
     * @return int
     * @throws PDOException
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Start a transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Roll back a transaction.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Get the last inserted ID.
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Get the PDO instance.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->connection;
    }
}