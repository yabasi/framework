<?php

namespace Yabasi\CLI\Generators;

use Yabasi\Filesystem\Filesystem;
use Yabasi\Support\Str;

/**
 * ControllerGenerator class for generating controller files.
 *
 * This class is responsible for creating new controller files
 * based on user input and predefined stubs.
 */
class ControllerGenerator
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
     * ControllerGenerator constructor.
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
     * Generate a new controller file.
     *
     * @param string $name The name of the controller
     * @param bool $resourceful Whether to generate a resourceful controller
     * @return string The path of the generated controller file
     * @throws \RuntimeException If the controller already exists
     */
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

    /**
     * Get the full controller name.
     *
     * @param string $name The base name of the controller
     * @return string The full controller name
     */
    protected function getControllerName(string $name): string
    {
        $name = ucfirst($name);
        return Str::endsWith($name, 'Controller') ? $name : $name . 'Controller';
    }

    /**
     * Get the path for the new controller file.
     *
     * @param string $name The name of the controller
     * @return string The full path for the controller file
     */
    protected function getPath(string $name): string
    {
        return BASE_PATH . "/app/Controllers/{$name}.php";
    }

    /**
     * Get the appropriate stub content.
     *
     * @param bool $resourceful Whether to use the resourceful controller stub
     * @return string The content of the stub file
     * @throws \RuntimeException If the stub file is not found
     */
    protected function getStub(bool $resourceful): string
    {
        $stubName = $resourceful ? 'controller.resourceful.stub' : 'controller.plain.stub';
        $stubPath = $this->getStubPath($stubName);

        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }

    /**
     * Populate the stub with actual values.
     *
     * @param string $stub The stub content
     * @param string $controllerName The name of the controller
     * @param string $namespace The namespace for the controller
     * @return string The populated stub content
     */
    protected function populateStub(string $stub, string $controllerName, string $namespace): string
    {
        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $controllerName,
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