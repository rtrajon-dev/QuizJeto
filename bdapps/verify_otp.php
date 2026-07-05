<?php

header('Content-Type: application/json');

$user_otp = isset($_POST['Otp']) ? trim($_POST['Otp']) : '';
$referenceNo = isset($_POST['referenceNo']) ? trim($_POST['referenceNo']) : '';

if (empty($user_otp) || empty($referenceNo)) {
    echo json_encode(array(
        'statusCode' => 'FAILED',
        'message' => 'Missing OTP or referenceNo',
        'statusDetail' => 'OTP and reference number are required'
    ));
    exit;
}

$config = require __DIR__ . '/../config.php';
$requestData = array(
    "applicationId" => $config['bdapps']['app_id'],
    "password" => $config['bdapps']['password'],
    "referenceNo" => $referenceNo,
    "otp" => $user_otp
);

$requestJson = json_encode($requestData);

$url = "https://developer.bdapps.com/subscription/otp/verify";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "Content-Length: " . strlen($requestJson)
));
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$responseJson = curl_exec($ch);

if ($responseJson === false) {
    $error = curl_error($ch);
    curl_close($ch);
    echo json_encode(array(
        'statusCode' => 'FAILED',
        'message' => 'Connection error: ' . $error,
        'statusDetail' => 'Unable to connect to BDApps server'
    ));
    exit;
}

curl_close($ch);

$response = json_decode($responseJson, true);

if ($response === null) {
    echo json_encode(array(
        'statusCode' => 'FAILED',
        'message' => 'Invalid API response',
        'statusDetail' => 'Failed to parse BDApps response',
        'rawResponse' => $responseJson
    ));
    exit;
}

// Return the response from BDApps
echo json_encode(array(
    'statusCode' => isset($response['statusCode']) ? $response['statusCode'] : 'FAILED',
    'statusDetail' => isset($response['statusDetail']) ? $response['statusDetail'] : '',
    'subscriptionStatus' => isset($response['subscriptionStatus']) ? $response['subscriptionStatus'] : '',
    'subscriberId' => isset($response['subscriberId']) ? $response['subscriberId'] : '',
    'version' => isset($response['version']) ? $response['version'] : ''
));

?>