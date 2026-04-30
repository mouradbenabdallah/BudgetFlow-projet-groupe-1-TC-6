<?php

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

            // DSN PostgreSQL lu depuis config/config.php et les variables Docker.
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $database['host'],
                $database['port'],
                $database['name']
            );

            // Une seule connexion PDO partagée, avec erreurs en exceptions et fetch en tableau associatif.
            self::$instance = new PDO($dsn, $database['user'], $database['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$instance;
    }
}
