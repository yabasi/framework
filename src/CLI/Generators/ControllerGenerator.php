<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

class ControllerGenerator
{
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
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
        return $this->filesystem->get(BASE_PATH . "/app/Core/CLI/stubs/{$stubName}");
    }

    protected function populateStub(string $stub, string $controllerName, string $namespace): string
    {
        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $controllerName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }
}