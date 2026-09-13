<?php
/**
 * Database Connection Helper (PDO)
 * Supports MySQL and SQLite with automatic fallback.
 */

function get_db_connection(): ?PDO {
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }

    $driver = getenv('DB_DRIVER') ?: 'auto'; // 'mysql', 'sqlite', or 'auto'

    // Try MySQL if explicitly configured or auto
    if ($driver === 'mysql' || $driver === 'auto') {
        $host   = getenv('DB_HOST') ?: '127.0.0.1';
        $port   = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'kkc_db';
        $user   = getenv('DB_USER') ?: 'root';
        $pass   = getenv('DB_PASS') ?: '';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // MySQL unavailable
            if ($driver === 'mysql') {
                $pdo = null;
                return null;
            }
        }
    }

    // SQLite Fallback (Zero-configuration persistent database)
    try {
        $sqlitePath = __DIR__ . '/kkc.sqlite';
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        return $pdo;
    } catch (PDOException $e) {
        $pdo = null;
        return null;
    }
}

/**
 * Check if the database has tables initialized
 */
function is_db_initialized(?PDO $pdo): bool {
    if (!$pdo) return false;
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='products'");
        } else {
            $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
        }
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}
