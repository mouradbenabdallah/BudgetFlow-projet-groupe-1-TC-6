<?php

declare(strict_types=1);

/**
 * Session Manager
 *
 * Wrapper around PHP's native session with flash message support
 * and secure destruction.
 */
class Session
{
    /**
     * Start the session if not already active.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Store a flash message for the next request.
     *
     * @param string $type Message category (e.g., 'success', 'danger', 'warning', 'info')
     * @param string $message The flash message
     */
    public function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Retrieve and consume a flash message.
     *
     * @param string $type Message category
     * @return string|null The flash message or null if none exists
     */
    public function getFlash(string $type): ?string
    {
        if (!isset($_SESSION['flash'][$type])) {
            return null;
        }

        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);

        if (isset($_SESSION['flash']) && $_SESSION['flash'] === []) {
            unset($_SESSION['flash']);
        }

        return $message;
    }

    /**
     * Store a value in the session.
     *
     * @param string $key Session key
     * @param mixed $value Value to store
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve a value from the session with an optional default.
     *
     * @param string $key Session key
     * @param mixed $default Default value if key is missing
     * @return mixed The stored value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Destroy the session completely, clearing cookies and server data.
     */
    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
