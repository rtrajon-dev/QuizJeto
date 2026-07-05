<?php
/**
 * Return the subscription status of the currently logged-in player (guideline #5).
 *
 * The number is read from the SESSION, not from client input.
 */

header('Content-Type: application/json');
session_start();

$phone = $_SESSION['phone'] ?? '';
if (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
    http_response_code(403);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$subscriberId = 'tel:88' . $phone;
$config = require __DIR__ . '/../config.php';

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
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($requestJson),
]);

$responseJson = curl_exec($ch);
$curlError    = curl_error($ch);
curl_close($ch);

if ($responseJson === false) {
    echo json_encode(['error' => 'cURL failed', 'details' => $curlError]);
    exit;
}

$response = json_decode($responseJson, true);
if (!is_array($response)) {
    echo json_encode(['error' => 'Invalid response']);
    exit;
}

$status = strtoupper(trim($response['subscriptionStatus'] ?? ''));

echo json_encode([
    'subscriptionStatus' => $status,
    'isSubscribed'       => ($status === 'REGISTERED'),
    'statusCode'         => $response['statusCode'] ?? null,
    'statusDetail'       => $response['statusDetail'] ?? null,
]);
