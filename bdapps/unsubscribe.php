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

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

function callBdapps(string $url, array $requestData): array {
    $requestJson = json_encode($requestData);
    if ($requestJson === false) {
        return ['ok' => false, 'error' => 'Failed to encode request'];
    }

    $ch = curl_init();
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Unable to initialize cURL'];
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Content-Length: " . strlen($requestJson)
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $responseJson = curl_exec($ch);
    $curlError = curl_error($ch);

    if ($responseJson === false) {
        return ['ok' => false, 'error' => "cURL failed: $curlError"];
    }

    $response = json_decode($responseJson, true);
    if (!is_array($response)) {
        return ['ok' => false, 'error' => 'Invalid response', 'raw' => $responseJson];
    }

    return ['ok' => true, 'data' => $response, 'raw' => $responseJson];
}

require_once __DIR__ . '/../db.php';

// The number comes from the session, never from the request — otherwise anyone
// could unsubscribe any number.
$phone = $_SESSION['phone'] ?? '';
if ($phone === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// subscription/send takes the masked subscriberId (from otp/verify), not tel:88...
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
        'success' => false,
        'error' => 'No subscriberId on file. Please verify your number again, or send STOP quizjeeto to 21213.',
    ]);
    exit;
}

$config = require __DIR__ . '/../config.php';
$appId = $config['bdapps']['app_id'];
$password = $config['bdapps']['password'];

$requestData = array(
    'applicationId' => $appId,
    'password' => $password,
    'subscriberId' => $subscriberId,
    'version' => '1.0',
    'action' => '0',
);

$result = callBdapps('https://developer.bdapps.com/subscription/send', $requestData);

if (!$result['ok']) {
    echo json_encode([
        'success' => false,
        'error' => $result['error'],
        'action' => '0',
    ]);
    exit;
}

$response = $result['data'];
$statusCode = strtoupper((string)($response['statusCode'] ?? ''));
$subscriptionStatus = $response['subscriptionStatus'] ?? 'UNKNOWN';

$success =
    $statusCode === 'S1000' ||
    strtoupper((string)$subscriptionStatus) === 'UNREGISTERED';

echo json_encode([
    'success' => $success,
    'action' => '0',
    'version' => '1.0',
    'statusCode' => $response['statusCode'] ?? null,
    'statusDetail' => $response['statusDetail'] ?? null,
    'subscriptionStatus' => $subscriptionStatus,
    'rawResponse' => $result['raw'] ?? null,
]);

?>