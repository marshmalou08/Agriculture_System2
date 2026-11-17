<?php
/**
 * Database
 * Owns the single PDO connection to Supabase PostgreSQL.
 * Credentials come from environment variables or the ".env" file (see load_env()).
 */
final class Database
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
        // Not instantiable: use Database::connection().
    }

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        load_env();
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT') ?: '5432';
        $name = getenv('DB_NAME') ?: 'postgres';
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASSWORD');
        $ssl  = getenv('DB_SSLMODE') ?: 'require';

        if (!$host || !$user) {
            error_log('UmaLink: database settings are missing. Create the .env file.');
            http_response_code(500);
            exit('The system is not configured yet. Please contact the administrator.');
        }
        try {
            self::$pdo = new PDO(
                "pgsql:host=$host;port=$port;dbname=$name;sslmode=$ssl",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // The real reason goes to the server log only, never to the browser.
            error_log('UmaLink DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Unable to connect to the database. Please try again later.');
        }
        return self::$pdo;
    }
}
