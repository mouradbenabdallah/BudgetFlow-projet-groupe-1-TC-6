<?php

class Auth
{
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id'], $_SESSION['role']);
    }

    public static function getUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'name' => (string) ($_SESSION['name'] ?? ''),
            'email' => (string) ($_SESSION['email'] ?? ''),
            'role' => (string) ($_SESSION['role'] ?? 'user'),
        ];
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            self::redirect('/login');
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();

        $user = self::getUser();
        if ($user !== null && $user['role'] === $role) {
            return;
        }

        if ($user !== null && $user['role'] === 'admin') {
            self::redirect('/admin');
        }

        self::redirect('/dashboard');
    }

    private static function redirect(string $path): void
    {
        header('Location: ' . $path, true, 302);
        throw new RedirectException();
    }
}
