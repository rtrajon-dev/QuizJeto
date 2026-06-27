<?php
/**
 * Step 2 of OTP flow: verify the OTP the user typed.
 *
 * Input  (POST): Otp = the code, referenceNo = value returned by send_otp.php
 * Output (JSON): { "subscriptionStatus": "REGISTERED" | ... } on success
 *                { "error": "..." }                            on failure
 *
 * Credentials come from quizjeto/.env via config.php — nothing is hardcoded,
 * and the OTP is never written to disk.
 */

header('Content-type: application/json');

$config   = require __DIR__ . '/../config.php';
$bdapps   = $config['bdapps'];
$endpoint = $config['endpoints']['otp_verify'];

// --- validate input ---
$otp = isset($_POST['Otp']) ? trim($_POST['Otp']) : '';
$referenceNo = isset($_POST['referenceNo']) ? trim($_POST['referenceNo']) : '';

if (!preg_match('/^\d{4,6}$/', $otp) || $referenceNo === '') {
    http_response_code(422);
    echo json_encode(['error' => 'সঠিক OTP ও রেফারেন্স দিন']);
    exit;
}

if ($bdapps['app_id'] === '' || $bdapps['password'] === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured: set BDAPPS_APP_ID and BDAPPS_PASSWORD in .env']);
    exit;
}

// --- build request ---
$requestData = [
    'applicationId' => $bdapps['app_id'],
    'password'      => $bdapps['password'],
    'referenceNo'   => $referenceNo,
    'otp'           => $otp,
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

if (!is_array($response) || !isset($response['subscriptionStatus'])) {
    http_response_code(400);
    echo json_encode([
        'error'  => 'OTP verification failed',
        'detail' => is_array($response) ? ($response['statusDetail'] ?? 'Invalid or expired OTP') : 'Invalid response',
    ]);
    exit;
}

// success — return the subscription status (e.g. "REGISTERED")
echo json_encode(['subscriptionStatus' => $response['subscriptionStatus']]);
