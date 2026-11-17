<?php
/**
 * Environment loading and the db() shortcut. The connection itself is made by the
 * Database class (classes/Database.php).
 * Credentials come from environment variables or from the ".env" file.
 * Nothing sensitive is hard-coded here.
 */
function load_env(): void
{
    $file = __DIR__ . '/../.env';
    if (!is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

/** Shortcut for the shared PDO connection (see the Database class). */
function db(): PDO
{
    return Database::connection();
}
