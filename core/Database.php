<?php

/**
 * Database Singleton
 *
 * Provides a shared PDO instance with proper error handling.
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('La désérialisation de Database est interdite.');
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/config.php';
            $database = $config['database'];

            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $database['host'],
                $database['port'],
                $database['name']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $database['user'],
                    $database['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(503);
                die('Service temporairement indisponible. Veuillez réessayer plus tard.');
            }
        }

        return self::$instance;
    }
}
