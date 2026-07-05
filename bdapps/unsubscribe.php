<?php
/**
 * Unsubscribe the currently logged-in player (guideline #5 & #6).
 *
 * The number is taken from the SESSION — never from client input — so a user
 * can only unsubscribe their own subscription. On success the session is
 * destroyed here (server side); the frontend then redirects to the login page.
 */

header('Content-Type: application/json');
session_start();

$phone = $_SESSION['phone'] ?? '';
if (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
    exit;
}

$subscriberId = 'tel:88' . $phone;
$config   = require __DIR__ . '/../config.php';

$requestData = [
    'applicationId' => $config['bdapps']['app_id'],
    'password'      => $config['bdapps']['password'],
    'subscriberId'  => $subscriberId,
    'version'       => '1.0',
    'action'        => '0', // 0 = unsubscribe (per bdapps SDK)
];

$requestJson = json_encode($requestData);

$ch = curl_init('https://developer.bdapps.com/subscription/send');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($requestJson),
]);

$responseJson = curl_exec($ch);
$curlError    = curl_error($ch);
curl_close($ch);

if ($responseJson === false) {
    echo json_encode(['ok' => false, 'error' => 'Connection error: ' . $curlError]);
    exit;
}

$response = json_decode($responseJson, true);
if (!is_array($response)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid response']);
    exit;
}

$statusCode = strtoupper((string)($response['statusCode'] ?? ''));
$status     = strtoupper((string)($response['subscriptionStatus'] ?? ''));
$success    = ($statusCode === 'S1000' || $status === 'UNREGISTERED');

if ($success) {
    // Guideline #6: unsubscribed users must be logged out automatically.
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

echo json_encode([
    'ok'                 => $success,
    'subscriptionStatus' => $status,
    'statusCode'         => $response['statusCode'] ?? null,
    'statusDetail'       => $response['statusDetail'] ?? null,
]);
