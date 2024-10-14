<?php

namespace Yabasi\Filesystem;

class Filesystem
{
    /**
     * Dosyanın var olup olmadığını kontrol eder.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Dosyayı okur.
     *
     * @param string $path
     * @return string
     * @throws \RuntimeException
     */
    public function get(string $path): string
    {
        if (!$this->exists($path)) {
            throw new \RuntimeException("File does not exist at path {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Unable to read file at path {$path}");
        }

        return $contents;
    }

    /**
     * Dosyaya yazar.
     *
     * @param string $path
     * @param string $contents
     * @return bool
     */
    public function put(string $path, string $contents): bool
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($path, $contents) !== false;
    }

    public function glob(string $pattern, int $flags = 0): array
    {
        return glob($pattern, $flags);
    }

    /**
     * Dosyayı siler.
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        if (!$this->exists($path)) {
            return false;
        }

        return unlink($path);
    }

    /**
     * Dizini oluşturur.
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false): bool
    {
        return mkdir($path, $mode, $recursive);
    }

    /**
     * Dizinin içeriğini listeler.
     *
     * @param string $directory
     * @return array
     */
    public function files(string $directory): array
    {
        $files = [];

        foreach (new \DirectoryIterator($directory) as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Dosyayı kopyalar.
     *
     * @param string $path
     * @param string $target
     * @return bool
     */
    public function copy(string $path, string $target): bool
    {
        return copy($path, $target);
    }

    /**
     * Dosyayı taşır.
     *
     * @param string $path
     * @param string $target
     * @return bool
     */
    public function move(string $path, string $target): bool
    {
        return rename($path, $target);
    }

    public function copyDirectory(string $directory, string $destination, ?int $options = null): bool
    {
        if (!$this->isDirectory($directory)) {
            return false;
        }

        if (!$this->isDirectory($destination)) {
            $this->makeDirectory($destination, 0755, true);
        }

        $items = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            $target = $destination . '/' . $item->getBasename();

            if ($item->isDir()) {
                $this->copyDirectory($item->getPathname(), $target, $options);
            } else {
                copy($item->getPathname(), $target);
            }
        }

        return true;
    }

    public function isDirectory(string $path): bool
    {
        return is_dir($path);
    }
}