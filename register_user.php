<?php
/**
 * Store the player's display name in their SESSION only (never in the database).
 * Called after OTP verification. If the session later expires, they simply
 * enter the name again next time.
 *
 * Input  (POST): user_mobile (required), display_name (optional)
 * Output (JSON): { "ok": true, "display_name": "<name or masked number>" }
 *
 * NOTE: number verification/subscription is handled by bdapps; nothing about
 * the real user is persisted in SQLite.
 */

header('Content-type: application/json');
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

// session only — no DB
$_SESSION['phone']        = $phone;
$_SESSION['display_name'] = $name;     // raw (may be empty)
$_SESSION['display']      = $display;  // what to greet/show

echo json_encode(['ok' => true, 'display_name' => $display]);
