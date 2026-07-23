<?php
/**
 * Login for already-subscribed users (bdapps model: login = subscription
 * status check — checklist item 5).
 *
 * Verifies the number's subscription status with bdapps SERVER-SIDE, and only
 * creates the session if bdapps returns REGISTERED — so it can't be bypassed
 * by calling this endpoint with an arbitrary number.
 *
 * Input  (POST): user_mobile
 * Output (JSON): { ok: true, subscribed: true|false, subscriptionStatus }
 *   - subscribed:true  → session created, caller redirects into the app
 *   - subscribed:false → caller runs the subscribe + OTP flow instead
 */

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
session_start();

$digits = preg_replace('/\D+/', '', $_POST['user_mobile'] ?? '');
if (strpos($digits, '880') === 0 && strlen($digits) === 13) {
    $digits = '0' . substr($digits, 3);
} elseif (strpos($digits, '88') === 0 && strlen($digits) === 12) {
    $digits = '0' . substr($digits, 2);
}

if (!preg_match('/^01[3-9][0-9]{8}$/', $digits)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'সঠিক মোবাইল নম্বর দিন']);
    exit;
}

// getStatus takes the masked subscriberId bdapps returned at otp/verify, which
// can't be derived from a typed number — it has to be looked up by phone hash.
try {
    $subscriberId = get_subscriber_id(db(), $digits) ?? '';
} catch (Throwable $e) {
    $subscriberId = '';
}

if ($subscriberId === '') {
    // Never verified on this install (or a pre-masking user). We can't ask
    // bdapps about them, so hand the caller to the OTP flow — for a number
    // that's already subscribed bdapps answers E1351 "already registered",
    // which index.php treats as a login.
    echo json_encode([
        'ok'                 => true,
        'subscribed'         => false,
        'subscriptionStatus' => '',
    ]);
    exit;
}

$config = require __DIR__ . '/config.php';

$requestData = [
    'version'       => '1.0',
    'applicationId' => $config['bdapps']['app_id'],
    'password'      => $config['bdapps']['password'],
    'subscriberId'  => $subscriberId,
];
$requestJson = json_encode($requestData);

$ch = curl_init('https://developer.bdapps.com/subscription/getStatus');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($requestJson),
]);

$responseJson = curl_exec($ch);
$curlError    = curl_error($ch);
if (PHP_VERSION_ID < 80000) {
    curl_close($ch);   // no-op / deprecated on PHP 8+, needed on 7.x
}

if ($responseJson === false) {
    echo json_encode(['ok' => false, 'error' => 'সংযোগ সমস্যা: ' . $curlError]);
    exit;
}

$response = json_decode($responseJson, true);
if (!is_array($response)) {
    echo json_encode(['ok' => false, 'error' => 'সার্ভার সাড়া দেয়নি']);
    exit;
}

$status       = strtoupper(trim($response['subscriptionStatus'] ?? ''));
$isSubscribed = ($status === 'REGISTERED');

if ($isSubscribed) {
    // Log in — session only, no DB (same as register_user.php).
    $_SESSION['phone']         = $digits;
    $_SESSION['subscriber_id'] = $subscriberId;
    $_SESSION['display_name'] = $_SESSION['display_name'] ?? '';
    $_SESSION['display']      = $_SESSION['display']
        ?? (substr($digits, 0, 3) . '•••' . substr($digits, -3));
}

echo json_encode([
    'ok'                 => true,
    'subscribed'         => $isSubscribed,
    'subscriptionStatus' => $status,
]);
