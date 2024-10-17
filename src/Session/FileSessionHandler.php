<?php

namespace Yabasi\Session;

class FileSessionHandler implements SessionHandlerInterface
{
    private $savePath;

    public function __construct($savePath = null)
    {
        $this->savePath = $savePath ?: sys_get_temp_dir();
    }

    public function open($savePath, $sessionName): bool
    {
        $this->savePath = $savePath ?: $this->savePath;
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777, true);
        }
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string|false
    {
        return (string)@file_get_contents("$this->savePath/sess_$id");
    }

    public function write($id, $data): bool
    {
        return file_put_contents("$this->savePath/sess_$id", $data) !== false;
    }

    public function destroy($id): bool
    {
        $file = "$this->savePath/sess_$id";
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }

    public function gc($maxlifetime): int|false
    {
        foreach (glob("$this->savePath/sess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }
        return true;
    }
}