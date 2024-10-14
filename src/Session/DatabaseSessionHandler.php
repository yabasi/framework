<?php

namespace Yabasi\Session;

use Yabasi\Database\Database;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    protected $db;

    public function __construct(Database $database)
    {
        $this->db = $database;
    }

    public function open($save_path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string|false
    {
        $result = $this->db->fetchOne("SELECT data FROM sessions WHERE id = ?", [$id]);
        if ($result) {
            return $result['data'];
        }
        return '';
    }

    public function write($id, $data): bool
    {
        $expires = time() + ini_get('session.gc_maxlifetime');
        $result = $this->db->execute(
            "REPLACE INTO sessions (id, data, expires) VALUES (?, ?, ?)",
            [$id, $data, $expires]
        );
        return $result !== false;
    }

    public function destroy($id): bool
    {
        $result = $this->db->execute("DELETE FROM sessions WHERE id = ?", [$id]);
        return $result !== false;
    }

    public function gc($max_lifetime): int|false
    {
        $old = time() - $max_lifetime;
        $result = $this->db->execute("DELETE FROM sessions WHERE expires < ?", [$old]);
        return $result;
    }
}