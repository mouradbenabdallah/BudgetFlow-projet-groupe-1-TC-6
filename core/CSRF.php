<?php

declare(strict_types=1);

/**
 * CSRF Token Manager
 *
 * Generates and validates CSRF tokens with automatic expiration.
 * Tokens are stored in the session and rotated after use or expiration.
 */
class CSRF
{
    private const TOKEN_LIFETIME = 3600;

    /**
     * Generate a new CSRF token or return the current valid one.
     *
     * @return string The CSRF token
     */
    public static function generateToken(): string
    {
        self::ensureSessionStarted();

        $stored = $_SESSION['csrf'] ?? null;

        if (is_array($stored) && !empty($stored['token']) && !empty($stored['expires']) && (int) $stored['expires'] > time()) {
            return (string) $stored['token'];
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf'] = [
            'token' => $token,
            'expires' => time() + self::TOKEN_LIFETIME,
        ];

        return $token;
    }

    /**
     * Validate a CSRF token. Rotates the token after successful validation.
     *
     * @param string $token The token to validate
     * @return bool True if valid and not expired
     */
    public static function validateToken(string $token): bool
    {
        self::ensureSessionStarted();

        $stored = $_SESSION['csrf'] ?? null;

        if (!is_array($stored) || empty($stored['token']) || empty($stored['expires'])) {
            return false;
        }

        if ((int) $stored['expires'] <= time()) {
            unset($_SESSION['csrf']);
            return false;
        }

        if (!hash_equals($stored['token'], $token)) {
            return false;
        }

        self::rotateToken();

        return true;
    }

    /**
     * Generate a hidden HTML input field containing the CSRF token.
     *
     * @return string HTML input element
     */
    public static function getTokenField(): string
    {
        $token = htmlspecialchars(self::generateToken(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Rotate the current CSRF token to prevent reuse.
     */
    private static function rotateToken(): void
    {
        $newToken = bin2hex(random_bytes(32));
        $_SESSION['csrf'] = [
            'token' => $newToken,
            'expires' => time() + self::TOKEN_LIFETIME,
        ];
    }

    /**
     * Ensure the session has been started.
     */
    private static function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
