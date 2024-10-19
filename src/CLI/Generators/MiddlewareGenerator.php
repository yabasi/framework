<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

/**
 * MiddlewareGenerator class for generating middleware files.
 *
 * This class is responsible for creating new middleware files
 * based on user input and predefined stubs.
 */
class MiddlewareGenerator
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
     * MiddlewareGenerator constructor.
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
     * Generate a new middleware file.
     *
     * @param string $name The name of the middleware
     * @return string The path of the generated middleware file
     * @throws \RuntimeException If the middleware already exists
     */
    public function generate(string $name): string
    {
        $middlewareName = $this->getMiddlewareName($name);
        $namespace = "Yabasi\\Middleware";
        $path = $this->getPath($middlewareName);

        if ($this->filesystem->exists($path)) {
            throw new \RuntimeException("Middleware {$middlewareName} already exists!");
        }

        $stub = $this->getStub();
        $content = $this->populateStub($stub, $middlewareName, $namespace);

        $this->filesystem->put($path, $content);

        return $path;
    }

    /**
     * Get the full middleware name.
     *
     * @param string $name The base name of the middleware
     * @return string The full middleware name
     */
    protected function getMiddlewareName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'Middleware') ? $name : $name . 'Middleware';
    }

    /**
     * Get the path for the new middleware file.
     *
     * @param string $name The name of the middleware
     * @return string The full path for the middleware file
     */
    protected function getPath(string $name): string
    {
        return BASE_PATH . "/app/Middleware/{$name}.php";
    }

    /**
     * Get the middleware stub content.
     *
     * @return string The content of the middleware stub file
     * @throws \RuntimeException If the stub file is not found
     */
    protected function getStub(): string
    {
        $stubPath = $this->getStubPath('middleware.stub');

        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }

    /**
     * Populate the stub with actual values.
     *
     * @param string $stub The stub content
     * @param string $middlewareName The name of the middleware
     * @param string $namespace The namespace for the middleware
     * @return string The populated stub content
     */
    protected function populateStub(string $stub, string $middlewareName, string $namespace): string
    {
        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $middlewareName,
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