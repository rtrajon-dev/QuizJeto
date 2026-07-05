<?php

header('Content-Type: application/json; charset=utf-8');

$rawMobile = $_POST['user_mobile'] ?? '';
$digits = preg_replace('/\D+/', '', $rawMobile);

// Accept 018xxxxxxxx, 88018xxxxxxxx, or 8818xxxxxxxx and normalize to 018xxxxxxxx
if (strpos($digits, '880') === 0 && strlen($digits) === 13) {
    $digits = '0' . substr($digits, 3);
} elseif (strpos($digits, '88') === 0 && strlen($digits) === 12) {
    $digits = '0' . substr($digits, 2);
}

// Validate Bangladesh mobile number
if (!preg_match('/^01[3-9][0-9]{8}$/', $digits)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid mobile number format',
        'referenceNo' => null
    ]);
    exit;
}

// bdapps subscriberId format
$user_mobile = 'tel:88' . $digits;

// Request data
$config = require __DIR__ . '/../config.php';
$requestData = [
    'applicationId' => $config['bdapps']['app_id'],
    'password' => $config['bdapps']['password'],
    'subscriberId' => $user_mobile,
    'applicationHash' => $config['bdapps']['app_hash'],
    'applicationMetaData' => [
        'client' => 'WEBAPP',
        'appCode' => 'https://quizjeto.patawise.com/'
    ]
];

$requestJson = json_encode($requestData);

$url = 'https://developer.bdapps.com/subscription/otp/request';
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($requestJson)
]);

$responseJson = curl_exec($ch);

if ($responseJson === false) {
    echo json_encode([
        'success' => false,
        'message' => 'cURL error: ' . curl_error($ch),
        'referenceNo' => null
    ]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if response looks like HTML (error page)
if (stripos($responseJson, '<html') !== false || stripos($responseJson, '<!DOCTYPE') !== false) {
    echo json_encode([
        'success' => false,
        'message' => 'Server returned HTML instead of JSON. HTTP code: ' . $httpCode,
        'referenceNo' => null,
        'rawResponse' => substr($responseJson, 0, 500) // First 500 chars
    ]);
    exit;
}

$response = json_decode($responseJson, true);

if (!is_array($response)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON in response',
        'raw' => substr($responseJson, 0, 500), // Show first 500 chars
        'referenceNo' => null,
        'httpCode' => $httpCode
    ]);
    exit;
}

$referenceNo = isset($response['referenceNo']) ? trim((string)$response['referenceNo']) : '';
$statusCode = isset($response['statusCode']) ? (string)$response['statusCode'] : '';
$statusDetail = isset($response['statusDetail']) ? (string)$response['statusDetail'] : '';
$version = isset($response['version']) ? (string)$response['version'] : '';

if ($referenceNo !== '') {
    echo json_encode([
        'success' => true,
        'referenceNo' => $referenceNo,
        'statusCode' => $statusCode,
        'statusDetail' => $statusDetail,
        'version' => $version
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => $statusDetail !== '' ? $statusDetail : 'OTP reference not returned',
    'referenceNo' => null,
    'statusCode' => $statusCode,
    'statusDetail' => $statusDetail,
    'version' => $version,
    'subscriberId' => $user_mobile
]);