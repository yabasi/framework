<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

class MiddlewareGenerator
{
    protected Filesystem $filesystem;
    protected string $vendorPath;

    public function __construct(Filesystem $filesystem, string $vendorPath)
    {
        $this->filesystem = $filesystem;
        $this->vendorPath = $vendorPath;
    }

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

    protected function getMiddlewareName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'Middleware') ? $name : $name . 'Middleware';
    }

    protected function getPath(string $name): string
    {
        return BASE_PATH . "/app/Middleware/{$name}.php";
    }

    protected function getStub(): string
    {
        $stubPath = $this->getStubPath('middleware.stub');

        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }

    protected function populateStub(string $stub, string $middlewareName, string $namespace): string
    {
        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $middlewareName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function getStubPath(string $stubName): string
    {
        return $this->vendorPath . $stubName;
    }
}