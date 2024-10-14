<?php

namespace Yabasi\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function singleton($abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function bind($abstract, $concrete = null, $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * @throws Exception
     */
    public function make($abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        if (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    public function has($abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * @throws Exception
     */
    public function get($abstract)
    {
        try {
            return $this->make($abstract);
        } catch (Exception $e) {
            if (isset($this->bindings[$abstract])) {
                return $this->make($this->bindings[$abstract]['concrete']);
            }

            if ($abstract === 'config') {
                return null;
            }
            throw new Exception("Unable to resolve {$abstract}: " . $e->getMessage());
        }
    }

    protected function getConcrete($abstract)
    {
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    protected function build($concrete)
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * @throws Exception
     */
    protected function resolveDependencies($dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                    throw new Exception("Unresolvable dependency: {$dependency->getName()} in {$dependency->getDeclaringClass()->getName()}");
                }
            } else {
                try {
                    $results[] = $this->make($type->getName());
                } catch (Exception $e) {
                    throw new Exception("Failed to resolve {$type->getName()}: " . $e->getMessage());
                }
            }
        }

        return $results;
    }
}