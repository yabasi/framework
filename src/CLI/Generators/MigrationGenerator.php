<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

/**
 * MigrationGenerator class for generating migration files.
 *
 * This class is responsible for creating new database migration files
 * based on user input and predefined stubs.
 */
class MigrationGenerator
{
    /**
     * @var Filesystem The filesystem instance for file operations
     */
    protected Filesystem $filesystem;

    /**
     * @var string The path to the migrations directory
     */
    protected string $migrationPath;

    /**
     * @var string The path to the vendor directory containing stubs
     */
    protected string $vendorPath;

    /**
     * @var string The namespace for migration classes
     */
    protected string $namespace = 'Yabasi\\Migrations';

    /**
     * MigrationGenerator constructor.
     *
     * @param Filesystem $filesystem The filesystem instance
     * @param string $vendorPath The path to the vendor directory
     * @param string|null $migrationPath The path to the migrations directory (optional)
     */
    public function __construct(Filesystem $filesystem, string $vendorPath, string $migrationPath = null)
    {
        $this->filesystem = $filesystem;
        $this->vendorPath = $vendorPath;
        $this->migrationPath = $migrationPath ?? BASE_PATH . '/app/Migrations';
    }

    /**
     * Generate a new migration file.
     *
     * @param string $name The name of the migration
     * @param string $type The type of migration (default, create, update, delete)
     * @return string The path of the generated migration file
     * @throws \RuntimeException If the migration already exists
     */
    public function generate(string $name, string $type = 'default'): string
    {
        $className = $this->getClassName($name);
        $tableName = $this->getTableName($name);
        $fileName = $this->getFileName($name);
        $path = $this->migrationPath . '/' . $fileName;

        if ($this->filesystem->exists($path)) {
            throw new \RuntimeException("Migration {$fileName} already exists!");
        }

        $stub = $this->getStub($type);
        $content = $this->populateStub($stub, $className, $tableName);

        $this->filesystem->put($path, $content);

        return $path;
    }

    /**
     * Get the table name from the migration name.
     *
     * @param string $name The migration name
     * @return string The derived table name
     */
    protected function getTableName(string $name): string
    {
        return Str::snake(Str::pluralStudly($name));
    }

    /**
     * Get the class name for the migration.
     *
     * @param string $name The migration name
     * @return string The full class name for the migration
     */
    protected function getClassName(string $name): string
    {
        $timestamp = date('YmdHis');
        return Str::studly($name) . 'Migration' . $timestamp;
    }

    /**
     * Get the file name for the migration.
     *
     * @param string $name The migration name
     * @return string The file name for the migration
     */
    protected function getFileName(string $name): string
    {
        $timestamp = date('YmdHis');
        $baseName = Str::studly($name);
        return "{$baseName}Migration{$timestamp}.php";
    }

    /**
     * Get the appropriate stub content for the migration.
     *
     * @param string $type The type of migration
     * @return string The content of the stub file
     * @throws \RuntimeException If the stub file is not found
     */
    protected function getStub(string $type): string
    {
        $stubPath = $this->getStubPath("migration.{$type}.stub");

        if (!file_exists($stubPath)) {
            $stubPath = $this->getStubPath("migration.default.stub");
        }

        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }

    /**
     * Populate the stub with actual values.
     *
     * @param string $stub The stub content
     * @param string $className The name of the migration class
     * @param string $tableName The name of the database table
     * @return string The populated stub content
     */
    protected function populateStub(string $stub, string $className, string $tableName): string
    {
        $replacements = [
            '{{ namespace }}' => $this->namespace,
            '{{ class }}' => $className,
            '{{ table }}' => $tableName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Get the full path to a stub file.
     *
     * @param string $stubName The name of the stub file
     * @return string The full path to the stub file
     */
    protected function getStubPath(string $stubName): string
    {
        return $this->vendorPath . $stubName;
    }
}