<?php

namespace Yabasi\Session;

class FileSessionHandler implements SessionHandlerInterface
{
    private $savePath;

    public function open($save_path, $name): bool
    {
        $this->savePath = $save_path;
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777);
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
        return file_put_contents("$this->savePath/sess_$id", $data) === false ? false : true;
    }

    public function destroy($id): bool
    {
        $file = "$this->savePath/sess_$id";
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }

    public function gc($max_lifetime): int|false
    {
        foreach (glob("$this->savePath/sess_*") as $file) {
            if (filemtime($file) + $max_lifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }
        return true;
    }
}