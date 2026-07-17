<?php

namespace App\Config;

use PDO;
use PDOException;

/**
 * Database connection singleton
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '5432';
            $dbname = $_ENV['DB_DATABASE'] ?? 'home_library';
            $username = $_ENV['DB_USERNAME'] ?? 'library_user';
            $password = $_ENV['DB_PASSWORD'] ?? 'library_secret';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

            try {
                self::$instance = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]);
                // Set UTF-8 encoding
                self::$instance->exec("SET NAMES 'utf8'");
                self::$instance->exec("SET client_encoding TO 'UTF8'");
            } catch (PDOException $e) {
                http_response_code(500);
                // Log the real error server-side
                error_log('Database connection failed: ' . $e->getMessage());
                // Return generic message to client
                echo json_encode(['error' => 'Database connection failed. Please contact the administrator.']);
                exit();
            }
        }

        return self::$instance;
    }

    /**
     * Close the database connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}
