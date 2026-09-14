<?php
/**
 * Database Connection Helper (PDO)
 * Direct persistent SQLite database
 */

function get_db_connection(): ?PDO {
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }

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
        $stmt = $pdo->query("SELECT 1 FROM products LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}
