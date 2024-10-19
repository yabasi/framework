<?php

namespace Yabasi\Cache\Drivers;

use Yabasi\Cache\CacheInterface;
use Yabasi\Config\Config;

/**
 * FileCache class implements file-based caching mechanism.
 *
 * This class provides a file-based implementation of the CacheInterface,
 * storing cached items as serialized data in files on the local filesystem.
 */
class FileCache implements CacheInterface
{
    /** @var string The base path for storing cache files */
    protected string $path;

    /**
     * FileCache constructor.
     *
     * @param Config|null $config Configuration object, used to set cache path
     */
    public function __construct(?Config $config = null)
    {
        $this->path = $config ? $config->get('cache.stores.file.path', BASE_PATH . '/storage/cache/data') : BASE_PATH . '/storage/cache/data';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'expiration' => $ttl ? time() + $ttl : null,
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMany(array $values, ?int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMany(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, $value = 1): bool|int
    {
        $current = $this->get($key, 0);
        if (!is_numeric($current)) {
            return false;
        }
        $new = $current + $value;
        return $this->set($key, $new) ? $new : false;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, $value = 1): bool|int
    {
        return $this->increment($key, -$value);
    }

    /**
     * Get the file path for a given cache key.
     *
     * @param string $key The cache key
     * @return string The file path
     */
    protected function getFilePath(string $key): string
    {
        return $this->path . '/' . md5($key);
    }
}