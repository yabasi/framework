<?php

namespace Yabasi\Config;

use Exception;

class Config
{
    private $config = [];
    private $configPath;

    /**
     * @throws Exception
     */
    public function __construct($configPath = null)
    {
        $this->configPath = $configPath ?: BASE_PATH . '/config/config.php';
        $this->loadConfigFile();
    }

    /**
     * @throws Exception
     */
    private function loadConfigFile(): void
    {
        if (file_exists($this->configPath)) {
            $this->config = require $this->configPath;
        } else {
            throw new Exception("Configuration file not found: {$this->configPath}");
        }
    }

    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $config = $this->config;

        foreach ($keys as $segment) {
            if (!isset($config[$segment])) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    public function set($key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $i => $subKey) {
            if ($i === count($keys) - 1) {
                $config[$subKey] = $value;
            } else {
                if (!isset($config[$subKey]) || !is_array($config[$subKey])) {
                    $config[$subKey] = [];
                }
                $config = &$config[$subKey];
            }
        }
    }

    public function all(): array
    {
        return $this->config;
    }
}