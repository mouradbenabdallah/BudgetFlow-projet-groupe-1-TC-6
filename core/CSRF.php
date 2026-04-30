<?php

class CSRF
{
    public static function generateToken(): string
    {
        self::ensureSessionStarted();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validateToken(string $token): bool
    {
        self::ensureSessionStarted();

        if (empty($_SESSION['csrf_token']) || $token === '') {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function getTokenField(): string
    {
        $token = htmlspecialchars(self::generateToken(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    private static function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
