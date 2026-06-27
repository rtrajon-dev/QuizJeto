<?php
/**
 * Step 1 of OTP flow: request an OTP be sent to the user's phone.
 *
 * Input  (POST): user_mobile = 11-digit BD number, e.g. 01812345678
 * Output (JSON): { "referenceNo": "..." }  on success
 *                { "error": "..." }         on failure
 *
 * Credentials come from quizjeto/.env via config.php — nothing is hardcoded here.
 */

header('Content-type: application/json');

$config    = require __DIR__ . '/../config.php';
$bdapps    = $config['bdapps'];
$endpoint  = $config['endpoints']['otp_request'];

// --- validate input ---
$mobile = isset($_POST['user_mobile']) ? trim($_POST['user_mobile']) : '';
if (!preg_match('/^01[3-9]\d{8}$/', $mobile)) {
    http_response_code(422);
    echo json_encode(['error' => 'সঠিক ১১-সংখ্যার মোবাইল নম্বর দিন']);
    exit;
}

if ($bdapps['app_id'] === '' || $bdapps['password'] === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured: set BDAPPS_APP_ID and BDAPPS_PASSWORD in .env']);
    exit;
}

$subscriberId = 'tel:88' . $mobile;

// --- build request ---
$requestData = [
    'applicationId'   => $bdapps['app_id'],
    'password'        => $bdapps['password'],
    'subscriberId'    => $subscriberId,
    'applicationHash' => $bdapps['app_name'],
    'applicationMetaData' => [
        'client' => 'WEBAPP',
        'device' => 'web',
        'os'     => 'web',
        'appCode' => '',
    ],
];
$requestJson = json_encode($requestData);

// --- call bdapps ---
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $bdapps['verify_ssl']);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($requestJson),
]);

$responseJson = curl_exec($ch);

if ($responseJson === false) {
    $err = curl_error($ch);
    curl_close($ch);
    http_response_code(502);
    echo json_encode(['error' => 'Network error contacting bdapps', 'detail' => $err]);
    exit;
}
curl_close($ch);

$response = json_decode($responseJson, true);

if (!is_array($response) || !isset($response['referenceNo'])) {
    http_response_code(502);
    echo json_encode([
        'error'  => 'OTP request failed',
        'detail' => is_array($response) ? ($response['statusDetail'] ?? 'Unknown') : 'Invalid response',
    ]);
    exit;
}

// success — return only the referenceNo the client needs for step 2
echo json_encode(['referenceNo' => $response['referenceNo']]);
