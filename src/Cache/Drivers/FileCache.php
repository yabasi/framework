<?php

namespace Yabasi\Cache\Drivers;

use Yabasi\Cache\CacheInterface;
use Yabasi\Config\Config;

class FileCache implements CacheInterface
{
    protected string $path;

    public function __construct(?Config $config = null)
    {
        $this->path = $path ?? BASE_PATH . '/storage/cache/data';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, $default = null)
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expiration'] !== null && time() > $data['expiration']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function put(string $key, $value, ?int $ttl = null): bool
    {
        return $this->set($key, $value, $ttl);
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'expiration' => $ttl ? time() + $ttl : null,
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->path . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function setMany(array $values, ?int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }

    public function deleteMany(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                return false;
            }
        }
        return true;
    }

    public function increment(string $key, $value = 1)
    {
        $current = $this->get($key, 0);
        if (!is_numeric($current)) {
            return false;
        }
        $new = $current + $value;
        return $this->set($key, $new) ? $new : false;
    }

    public function decrement(string $key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    protected function getFilePath(string $key): string
    {
        return $this->path . '/' . md5($key);
    }
}