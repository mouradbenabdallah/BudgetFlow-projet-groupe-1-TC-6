<?php

/**
 * Database Singleton
 * 
 * Provides a shared PDO instance with proper error handling.
 * Implements Singleton pattern to avoid creating multiple database connections.
 * 
 * Usage: $pdo = Database::getInstance();
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation.
     * Singleton pattern requires private constructor.
     */
    private function __construct()
    {
    }

    /**
     * Private clone method to prevent cloning of the instance.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of the Database instance.
     * 
     * @throws RuntimeException Always throws exception.
     */
    public function __wakeup(): void
    {
        throw new RuntimeException('La désérialisation de Database est interdite.');
    }

    /**
     * Get the shared PDO database instance.
     * 
     * Creates a new PDO connection if one doesn't exist yet.
     * Uses PostgreSQL with error mode set to exceptions.
     * 
     * @return PDO The shared database connection instance
     * @throws PDOException If connection fails
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/config.php';
            $database = $config['database'];

            // Build PostgreSQL DSN (Data Source Name)
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
                        // Throw exceptions on database errors
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        // Return results as associative arrays by default
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        // Use real prepared statements (don't emulate)
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
