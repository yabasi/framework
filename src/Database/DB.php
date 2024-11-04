<?php

namespace Yabasi\Database;

use Yabasi\Application;
use Yabasi\Database\Query\Builder;

class DB
{
    protected static ?Connection $connection = null;

    protected static function getConnection(): Connection
    {
        if (static::$connection === null) {
            static::$connection = Application::getInstance()->make(Connection::class);
        }

        return static::$connection;
    }

    public static function select(string $query, array $bindings = []): array
    {
        return static::getConnection()->select($query, $bindings);
    }

    public static function insert(string $query, array $bindings = []): int
    {
        return static::getConnection()->insert($query, $bindings);
    }

    public static function update(string $query, array $bindings = []): int
    {
        return static::getConnection()->update($query, $bindings);
    }

    public static function delete(string $query, array $bindings = []): int
    {
        return static::getConnection()->delete($query, $bindings);
    }

    public static function statement(string $query, array $bindings = []): bool
    {
        return static::getConnection()->statement($query, $bindings);
    }

    public static function table(string $table): Builder
    {
        return static::getConnection()->table($table);
    }

    public static function beginTransaction(): bool
    {
        return static::getConnection()->getPdo()->beginTransaction();
    }

    public static function commit(): bool
    {
        return static::getConnection()->getPdo()->commit();
    }

    public static function rollBack(): bool
    {
        return static::getConnection()->getPdo()->rollBack();
    }

    public static function transaction(\Closure $callback)
    {
        static::beginTransaction();

        try {
            $result = $callback();
            static::commit();
            return $result;
        } catch (\Exception $e) {
            static::rollBack();
            throw $e;
        }
    }
}