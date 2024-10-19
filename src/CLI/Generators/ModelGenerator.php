<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

/**
 * ModelGenerator class for generating model files.
 *
 * This class is responsible for creating new model files
 * based on user input and predefined stubs.
 */
class ModelGenerator
{
    /**
     * @var Filesystem The filesystem instance for file operations
     */
    protected Filesystem $filesystem;

    /**
     * @var string The path to the vendor directory containing stubs
     */
    protected string $vendorPath;

    /**
     * ModelGenerator constructor.
     *
     * @param Filesystem $filesystem The filesystem instance
     * @param string $vendorPath The path to the vendor directory
     */
    public function __construct(Filesystem $filesystem, string $vendorPath)
    {
        $this->filesystem = $filesystem;
        $this->vendorPath = $vendorPath;
    }

    /**
     * Generate a new model file.
     *
     * @param string $name The name of the model
     * @param string|null $table The name of the database table (optional)
     * @return string The path of the generated model file
     * @throws \RuntimeException If the model already exists
     */
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

    /**
     * Get the full model name.
     *
     * @param string $name The base name of the model
     * @return string The full model name
     */
    protected function getModelName(string $name): string
    {
        return ucfirst($name);
    }

    /**
     * Get the path for the new model file.
     *
     * @param string $name The name of the model
     * @return string The full path for the model file
     */
    protected function getPath(string $name): string
    {
        return BASE_PATH . "/app/Models/{$name}.php";
    }

    /**
     * Get the model stub content.
     *
     * @return string The content of the model stub file
     * @throws \RuntimeException If the stub file is not found
     */
    protected function getStub(): string
    {
        $stubPath = $this->getStubPath('model.stub');

        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }

    /**
     * Populate the stub with actual values.
     *
     * @param string $stub The stub content
     * @param string $modelName The name of the model
     * @param string $namespace The namespace for the model
     * @param string $tableName The name of the database table
     * @return string The populated stub content
     */
    protected function populateStub(string $stub, string $modelName, string $namespace, string $tableName): string
    {
        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $modelName,
            '{{ table }}' => $tableName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    /**
     * Generate a table name from the model name.
     *
     * @param string $modelName The name of the model
     * @return string The generated table name
     */
    protected function generateTableName(string $modelName): string
    {
        return Str::snake(Str::pluralStudly($modelName));
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