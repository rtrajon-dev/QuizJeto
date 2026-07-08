<?php
/**
 * Set up the player's session after OTP verification and record them for the
 * leaderboard (hashed phone + masked form via upsert_user — the raw number is
 * never stored). Number verification/subscription is handled by bdapps.
 *
 * Input  (POST): user_mobile (required), display_name (optional)
 * Output (JSON): { "ok": true, "display_name": "<name or masked number>" }
 */

header('Content-type: application/json');

require_once __DIR__ . '/db.php';

// Ensure sessions persist on shared hosting (cPanel)
if (php_sapi_name() !== 'cli') {
    ini_set('session.save_path', __DIR__ . '/sessions');
    if (!is_dir(__DIR__ . '/sessions')) {
        @mkdir(__DIR__ . '/sessions', 0755, true);
    }
}
session_start();

$phone = isset($_POST['user_mobile'])  ? trim($_POST['user_mobile'])  : '';
$name  = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';

if (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
    http_response_code(422);
    echo json_encode(['error' => 'সঠিক মোবাইল নম্বর প্রয়োজন']);
    exit;
}

$name = mb_substr($name, 0, 30);

// display: chosen name, else a masked number for privacy (017•••678)
$display = $name !== ''
    ? $name
    : substr($phone, 0, 3) . '•••' . substr($phone, -3);

$_SESSION['phone']        = $phone;
$_SESSION['display_name'] = $name;     // raw (may be empty)
$_SESSION['display']      = $display;  // what to greet/show

// Record the player (hashed phone + masked form) so they can appear on the
// leaderboard. Never blocks registration if the DB write fails.
try {
    $uid = upsert_user(db(), $phone, $name);
    if ($uid) {
        $_SESSION['user_id'] = $uid;
    }
} catch (Throwable $e) {
    // ignore — session login still succeeds
}

echo json_encode(['ok' => true, 'display_name' => $display]);
