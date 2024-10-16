<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

class ModelGenerator
{
    protected Filesystem $filesystem;
    protected string $vendorPath;

    public function __construct(Filesystem $filesystem, string $vendorPath)
    {
        $this->filesystem = $filesystem;
        $this->vendorPath = $vendorPath;
    }

    public function generate(string $name, ?string $table = null): string
    {
        $modelName = $this->getModelName($name);
        $namespace = "Yabasi\\Models";
        $path = $this->getPath($modelName);

        if ($this->filesystem->exists($path)) {
            throw new \RuntimeException("Model {$modelName} already exists!");
        }

        $stub = $this->getStub();
        $tableName = $table ?? $this->generateTableName($name);
        $content = $this->populateStub($stub, $modelName, $namespace, $tableName);

        $this->filesystem->put($path, $content);

        return $path;
    }

    protected function getModelName(string $name): string
    {
        return ucfirst($name);
    }

    protected function getPath(string $name): string
    {
        return BASE_PATH . "/app/Models/{$name}.php";
    }

    protected function getStub(): string
    {
        $stubPath = $this->getStubPath('model.stub');

        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }

    protected function populateStub(string $stub, string $modelName, string $namespace, string $tableName): string
    {
        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $modelName,
            '{{ table }}' => $tableName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function generateTableName(string $modelName): string
    {
        return Str::snake(Str::pluralStudly($modelName));
    }

    protected function getStubPath(string $stubName): string
    {
        return $this->vendorPath . $stubName;
    }
}