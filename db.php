<?php
/**
 * Database helper for QuizJeeto (MySQL).
 *
 * Import database/quizjeto.sql into your cPanel MySQL database once (it creates
 * every table and loads the question bank). This file just opens a shared PDO
 * connection using the DB_* values from .env.
 *
 * Usage:
 *   require_once __DIR__ . '/db.php';
 *   $rows = db()->query('SELECT * FROM questions')->fetchAll();
 */

function db()
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];

    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

/**
 * Create-or-update a player row keyed by a hash of their phone number, and
 * return the user id. The raw MSISDN is never stored — only its sha256 hash
 * (identity key) and a masked form (017•••678) for display.
 *
 * @param string $phone   11-digit 01XXXXXXXXX number (from the session, not client input)
 * @param string $display Chosen display name (may be empty)
 * @return int|null       The user's id, or null if $phone is not a valid number
 */
function upsert_user(PDO $pdo, string $phone, string $display = ''): ?int
{
    if (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
        return null;
    }

    $hash   = hash('sha256', $phone);
    $masked = substr($phone, 0, 3) . '•••' . substr($phone, -3);

    $sel = $pdo->prepare('SELECT id FROM users WHERE phone_hash = ?');
    $sel->execute([$hash]);
    $id = $sel->fetchColumn();

    if ($id !== false) {
        // Keep the newest display name (only overwrite when a non-empty one is given).
        if ($display !== '') {
            $upd = $pdo->prepare('UPDATE users SET display_name = ? WHERE id = ?');
            $upd->execute([$display, $id]);
        }
        return (int) $id;
    }

    $ins = $pdo->prepare(
        'INSERT INTO users (phone_hash, phone_masked, display_name) VALUES (?, ?, ?)'
    );
    $ins->execute([$hash, $masked, $display]);
    return (int) $pdo->lastInsertId();
}

/**
 * Convert ASCII digits in a string/number to Bengali numerals (০১২৩...).
 */
function bn($value)
{
    return strtr((string) $value, [
        '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
        '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
    ]);
}
