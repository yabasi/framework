<?php

namespace Yabasi\Security;

use Yabasi\Http\Request;
use Yabasi\Session\SessionManager;

class CsrfProtection
{
    protected SessionManager $session;
    protected string $tokenKey = '_csrf_token';
    protected int $tokenLifetime = 3600; // 1 saat

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set($this->tokenKey, [
            'token' => $token,
            'expires' => time() + $this->tokenLifetime
        ]);
        return $token;
    }

    public function validateToken(string $token): bool
    {
        $storedData = $this->session->get($this->tokenKey);
        if (!$storedData || !is_array($storedData)) {
            return false;
        }

        if (time() > $storedData['expires']) {
            $this->session->remove($this->tokenKey);
            return false;
        }

        return hash_equals($storedData['token'], $token);
    }

    public function getTokenFromRequest(Request $request): ?string
    {
        return $request->input('_csrf_token') ?? $request->header('X-CSRF-TOKEN');
    }

    public function refreshToken(): void
    {
        $this->generateToken();
    }
}