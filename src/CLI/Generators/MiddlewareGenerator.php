<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

class MiddlewareGenerator
{
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
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
        return $this->filesystem->get(BASE_PATH . "/app/Core/CLI/stubs/middleware.stub");
    }

    protected function populateStub(string $stub, string $middlewareName, string $namespace): string
    {
        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $middlewareName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }
}