<?php

namespace Yabasi\Session;

use Yabasi\Config\Config;

class SessionManager
{
    protected $session;
    protected $config;
    protected $securityHandler;
    protected $sessionHandler;
    protected $started = false;

    public function __construct(Config $config, SecurityHandler $securityHandler)
    {
        $this->config = $config;
        $this->securityHandler = $securityHandler;
        $this->initializeSession();
    }

    protected function initializeSession(): void
    {
        $sessionConfig = $this->config->get('session', []);

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = $sessionConfig['lifetime'] ?? 120;
        $path = $sessionConfig['path'] ?? '/tmp';

        ini_set('session.gc_maxlifetime', $lifetime * 60);
        ini_set('session.save_path', $path);

        SecurityHandler::setSecureCookieParams();
        $this->setSessionHandler($sessionConfig['driver'] ?? 'file');
    }

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return true;
        }

        session_start();
        $this->started = true;
        $this->session = $_SESSION;

        $this->securityHandler->setSessionIdentifiers();
        SecurityHandler::preventSessionFixation();

        if (!$this->securityHandler->validateSession()) {
            $this->regenerate();
        }

        return true;
    }

    public function save(): void
    {
        if ($this->started) {
            session_write_close();
            $this->started = false;
        }
    }

    public function getId(): string
    {
        return session_id();
    }

    public function regenerate(): bool
    {
        return session_regenerate_id(true);
    }

    public function getSessionConfig(): array
    {
        $defaultConfig = [
            'name' => 'YABASI_SESSION',
            'lifetime' => 120,
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        $config = $this->config->get('session', []);
        return array_merge($defaultConfig, $config);
    }

    protected function setSessionHandler(string $driver): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $this->sessionHandler = match ($driver) {
            'database' => new DatabaseSessionHandler($this->config->get('database')),
            default => new FileSessionHandler($this->config->get('session.path', '/tmp')),
        };

        session_set_save_handler($this->sessionHandler, true);
    }

    protected function checkSessionLifetime(): void
    {
        if (!$this->session->has('_last_activity')) {
            $this->session->set('_last_activity', time());
        } elseif (time() - $this->session->get('_last_activity') > $this->config->get('session.lifetime', 1440)) {
            $this->session->regenerate();
            $this->session->set('_last_activity', time());
        }
    }

    public function all(): array
    {
        return $_SESSION;
    }

    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set($key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove($key): void
    {
        unset($_SESSION[$key]);
    }

    public function has($key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function clear(): void
    {
        if ($this->started) {
            $_SESSION = [];
        }
    }

    public function destroy(): void
    {
        $this->clear();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        $this->started = false;
    }

    public function flash($key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash($key, $default = null)
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }

        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash($key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function ageFlashData(): void
    {
        $_SESSION['_flash'] = $_SESSION['_flash'] ?? [];
    }
}