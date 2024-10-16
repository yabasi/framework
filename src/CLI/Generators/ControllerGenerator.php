<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

class ControllerGenerator
{
    protected Filesystem $filesystem;
    protected string $vendorPath;

    public function __construct(Filesystem $filesystem, string $vendorPath)
    {
        $this->filesystem = $filesystem;
        $this->vendorPath = $vendorPath;
    }

    public function generate(string $name, bool $resourceful = false): string
    {
        $controllerName = $this->getControllerName($name);
        $namespace = "Yabasi\\Controllers";
        $path = $this->getPath($controllerName);

        if ($this->filesystem->exists($path)) {
            throw new \RuntimeException("Controller {$controllerName} already exists!");
        }

        $stub = $this->getStub($resourceful);
        $content = $this->populateStub($stub, $controllerName, $namespace);

        $this->filesystem->put($path, $content);

        return $path;
    }

    protected function getControllerName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'Controller') ? $name : $name . 'Controller';
    }

    protected function getPath(string $name): string
    {
        return BASE_PATH . "/app/Controllers/{$name}.php";
    }

    protected function getStub(bool $resourceful): string
    {
        $stubName = $resourceful ? 'controller.resourceful.stub' : 'controller.plain.stub';
        $stubPath = $this->getStubPath($stubName);

        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }

    protected function populateStub(string $stub, string $controllerName, string $namespace): string
    {
        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $controllerName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function getStubPath(string $stubName): string
    {
        $possiblePaths = [
            $this->vendorPath . '/yabasi/framework/src/CLI/stubs/' . $stubName,
            __DIR__ . '/../../../../stubs/' . $stubName,
            BASE_PATH . '/stubs/' . $stubName,
        ];

        foreach ($possiblePaths as $path) {
            echo "Checking path: $path\n";
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException("Stub file not found: {$stubName}");
    }
}