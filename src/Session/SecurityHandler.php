<?php

namespace Yabasi\Session;

class SecurityHandler
{
    public static function setSecureCookieParams(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $currentCookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $currentCookieParams['lifetime'],
            'path' => $currentCookieParams['path'],
            'domain' => $currentCookieParams['domain'],
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    public static function preventSessionFixation(): void
    {
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } elseif (time() - $_SESSION['_created'] > 3600) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    public function validateSession(): bool
    {
        if (!isset($_SESSION['_client_ip']) || !isset($_SESSION['_user_agent'])) {
            return false;
        }

        if ($_SESSION['_client_ip'] !== $_SERVER['REMOTE_ADDR']) {
            return false;
        }

        if ($_SESSION['_user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            return false;
        }

        return true;
    }

    public function setSessionIdentifiers(): void
    {
        $_SESSION['_client_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
}