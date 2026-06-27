<?php
/**
 * Database helper for QuizJeto.
 *
 * Call db() to get a shared PDO connection. On first run with SQLite, the
 * database file is created automatically and seeded from database/schema.sql
 * + database/seed.sql — so there is nothing to set up manually.
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

    if ($db['connection'] === 'sqlite') {
        $path = $db['sqlite_path'];
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $needsInit = !file_exists($path) || filesize($path) === 0;

        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');

        if ($needsInit) {
            $schema = file_get_contents(__DIR__ . '/database/schema.sql');
            $seed   = file_get_contents(__DIR__ . '/database/seed.sql');
            $pdo->exec($schema);
            if ($seed !== false && trim($seed) !== '') {
                $pdo->exec($seed);
            }
        }
    } else {
        // MySQL (production)
        $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['user'], $db['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    return $pdo;
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
