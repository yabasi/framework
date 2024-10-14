<?php

use Yabasi\Application;
use Yabasi\Support\Collection;

if (!function_exists('collect')) {
    function collect($items = [])
    {
        return new Collection($items);
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = [], string $locale = null): string
    {
        return app('translator')->get($key, $replace, $locale);
    }
}

if (!function_exists('app')) {
    function app($abstract = null)
    {
        $container = Application::getInstance()->getContainer();
        if (is_null($abstract)) {
            return $container;
        }
        return $container->get($abstract);
    }
}

if (!function_exists('class_basename')) {
    function class_basename($class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}