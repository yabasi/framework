<?php

namespace Yabasi\Database\Migrations;

use Yabasi\Database\Connection;
use Yabasi\Filesystem\Filesystem;

class Migrator
{
    protected Connection $connection;
    protected Filesystem $filesystem;
    protected string $migrationPath;
    protected string $table = 'migrations';
    protected array $loadedMigrations = [];

    public function __construct(Connection $connection, Filesystem $filesystem, ?string $migrationPath = null)
    {
        $this->connection = $connection;
        $this->filesystem = $filesystem;
        $this->migrationPath = $migrationPath ?? BASE_PATH . '/app/Migrations';
        $this->createMigrationsTable();
    }

    protected function createMigrationsTable(): void
    {
        $schema = $this->connection->schema();
        if (!$schema->hasTable($this->table)) {
            $schema->create($this->table, function ($table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function getMigrations(): array
    {
        $files = $this->filesystem->glob($this->migrationPath . '/*.php');
        return array_map(function($file) {
            return pathinfo($file, PATHINFO_FILENAME);
        }, $files);
    }

    public function getRunMigrations(): array
    {
        return $this->connection->table($this->table)
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('migration');
    }

    public function resolve($file): string
    {
        $class = str_replace('.php', '', $file);
        return "Yabasi\\Migrations\\{$class}";
    }

    public function runPending(): void
    {
        $migrations = $this->getMigrations();

        if (empty($migrations)) {
            echo "Nothing to migrate.\n";
            return;
        }

        $this->runMigrations($migrations);
    }

    protected function runMigrations(array $migrations): void
    {
        $batch = $this->getNextBatchNumber();

        foreach ($migrations as $file) {
            $this->runUp($file, $batch);
        }
    }

    protected function runUp($file, $batch): void
    {
        $migrationName = pathinfo($file, PATHINFO_FILENAME);
        $className = "Yabasi\\Migrations\\{$migrationName}";
        $migrationFile = $this->migrationPath . '/' . $file;

        if (!file_exists($migrationFile) && file_exists($migrationFile . '.php')) {
            $migrationFile .= '.php';
        }

        if (!file_exists($migrationFile)) {
            throw new \RuntimeException("Migration file not found: {$migrationFile}");
        }

        require_once $migrationFile;

        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class {$className} not found in file {$migrationFile}");
        }

        $migration = new $className();

        if (!$migration instanceof MigrationInterface) {
            throw new \RuntimeException("Migration class {$className} must implement MigrationInterface");
        }

        $migration->up($this->connection);
        $this->log($file, $batch);
    }

    public function rollback(): void
    {
        $lastBatch = $this->getLastBatchNumber();
        $migrations = $this->getMigrationsForRollback($lastBatch);

        foreach ($migrations as $migration) {
            $this->runDown($migration);
        }
    }

    public function runDown($file): void
    {
        $migrationClass = $this->resolve($file);
        $migrationFile = $this->migrationPath . '/' . $file . '.php';

        if (!isset($this->loadedMigrations[$migrationClass])) {
            if (!class_exists($migrationClass)) {
                require_once $migrationFile;
            }
            $this->loadedMigrations[$migrationClass] = true;
        }

        $instance = new $migrationClass();

        $this->connection->getSchemaBuilder()->disableForeignKeyConstraints();

        $instance->down($this->connection);

        $this->connection->getSchemaBuilder()->enableForeignKeyConstraints();

        $this->delete($file);
    }

    public function log(string $file, int $batch): void
    {
        $this->connection->table($this->table)->insert([
            'migration' => $file,
            'batch' => $batch,
        ]);
    }

    public function delete(string $file): void
    {
        $this->connection->table($this->table)
            ->where('migration', $file)
            ->delete();
    }

    protected function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    protected function getLastBatchNumber(): int
    {
        return (int) $this->connection->table($this->table)->from($this->table)->max('batch') ?? 0;
    }

    protected function getMigrationsForRollback(int $batch): array
    {
        return $this->connection->table($this->table)
            ->where('batch', '=', $batch)
            ->orderBy('migration', 'desc')
            ->pluck('migration');
    }

    public function getMigrationBatches(): array
    {
        $migrations = $this->connection->table($this->table)
            ->orderBy('batch', 'desc')
            ->orderBy('migration', 'desc')
            ->get();

        $batches = [];
        foreach ($migrations as $migration) {
            $batches[$migration['batch']][] = $migration['migration'];
        }

        return $batches;
    }
}