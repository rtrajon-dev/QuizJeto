<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure sessions persist on shared hosting (cPanel)
if (php_sapi_name() !== 'cli') {
    ini_set('session.save_path', __DIR__ . '/../sessions');
    if (!is_dir(__DIR__ . '/../sessions')) {
        @mkdir(__DIR__ . '/../sessions', 0755, true);
    }
}
session_start();

require_once __DIR__ . '/../db.php';

// The number comes from the session, never from the request — otherwise anyone
// could check the status of any number.
$phone = $_SESSION['phone'] ?? '';
if ($phone === '') {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// getStatus takes the masked subscriberId (from otp/verify), not tel:88...
$subscriberId = $_SESSION['subscriber_id'] ?? '';
if ($subscriberId === '') {
    try {
        $subscriberId = get_subscriber_id(db(), $phone) ?? '';
    } catch (Throwable $e) {
        $subscriberId = '';
    }
    if ($subscriberId !== '') {
        $_SESSION['subscriber_id'] = $subscriberId;
    }
}

if ($subscriberId === '') {
    echo json_encode([
        'error' => 'No subscriberId on file',
        'statusDetail' => 'Please verify your number again to refresh your subscription.',
    ]);
    exit;
}

$config = require __DIR__ . '/../config.php';
$requestData = [
    'version' => '1.0',
    'applicationId' => $config['bdapps']['app_id'],
    'password' => $config['bdapps']['password'],
    'subscriberId' => $subscriberId,
];

$requestJson = json_encode($requestData);

// BDApps subscription status API
$url = 'https://developer.bdapps.com/subscription/getStatus';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($requestJson),
]);

$responseJson = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($responseJson === false) {
    echo json_encode([
        'error' => 'cURL failed',
        'details' => $curlError,
    ]);
    exit;
}

$response = json_decode($responseJson, true);
if (!is_array($response)) {
    echo json_encode(['error' => 'Invalid response']);
    exit;
}

$status = strtoupper(trim($response['subscriptionStatus'] ?? ''));

// Per getStatus contract, subscription status is REGISTERED or UNREGISTERED.
$isSubscribed = ($status === 'REGISTERED');

echo json_encode([
    'subscriptionStatus' => $status,
    'isSubscribed' => $isSubscribed,
    'statusCode' => $response['statusCode'] ?? null,
    'statusDetail' => $response['statusDetail'] ?? null,
    'version' => $response['version'] ?? null,
]);
?>
