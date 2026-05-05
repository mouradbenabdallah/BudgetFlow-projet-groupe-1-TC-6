<?php

declare(strict_types=1);

/**
 * Auth Helper
 *
 * Static authentication utilities for session-based user checks
 * and role-based access control.
 */
class Auth
{
    /**
     * Check if a user is currently logged in.
     *
     * @return bool True if authenticated
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id'], $_SESSION['role']);
    }

    /**
     * Get the current user's session data.
     *
     * @return array{id: int, name: string, email: string, role: string}|null User data or null
     */
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

    /**
     * Require the user to be logged in, redirecting to login if not.
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            self::redirect('/login');
        }
    }

    /**
     * Require the user to have a specific role.
     * Admins are granted implicit access to user-level routes.
     *
     * @param string $role Required role ('user' or 'admin')
     */
    public static function requireRole(string $role): void
    {
        self::requireLogin();

        $user = self::getUser();
        if ($user === null) {
            self::redirect('/login');
        }

        $userRole = $user['role'] ?? 'user';

        if ($userRole === $role) {
            return;
        }

        if ($role === 'user' && $userRole === 'admin') {
            return;
        }

        if ($userRole === 'admin') {
            self::redirect('/admin');
        }

        self::redirect('/dashboard');
    }

    /**
     * Perform an HTTP 302 redirect. Throws RedirectException to halt execution.
     *
     * @param string $path Target URL path
     */
    private static function redirect(string $path): void
    {
        header('Location: ' . $path, true, 302);
        throw new RedirectException();
    }
}
