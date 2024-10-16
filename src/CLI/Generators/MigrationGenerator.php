<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

class MigrationGenerator
{
    protected Filesystem $filesystem;
    protected string $migrationPath;
    protected string $vendorPath;
    protected string $namespace = 'Yabasi\\Migrations';

    public function __construct(Filesystem $filesystem, string $vendorPath, string $migrationPath = null)
    {
        $this->filesystem = $filesystem;
        $this->vendorPath = $vendorPath;
        $this->migrationPath = $migrationPath ?? BASE_PATH . '/app/Migrations';
    }

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

    protected function getTableName(string $name): string
    {
        return Str::snake(Str::pluralStudly($name));
    }

    protected function getClassName(string $name): string
    {
        $timestamp = date('YmdHis');
        return Str::studly($name) . 'Migration' . $timestamp;
    }

    protected function getFileName(string $name): string
    {
        $timestamp = date('YmdHis');
        $baseName = Str::studly($name);
        return "{$baseName}Migration{$timestamp}.php";
    }

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

    protected function populateStub(string $stub, string $className, string $tableName): string
    {
        $replacements = [
            '{{ namespace }}' => $this->namespace,
            '{{ class }}' => $className,
            '{{ table }}' => $tableName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function getStubPath(string $stubName): string
    {
        return $this->vendorPath . $stubName;
    }
}