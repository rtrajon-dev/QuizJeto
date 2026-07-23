<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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

$date_ = date("Y-m-d h:i:sa");

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

// Log OTP verification attempt
try {
    $myfile = fopen("OTP+RefNo.txt", "a+") or die("Unable to open file!");
    fwrite($myfile, "OTP:" . $user_otp . " RefNo:" . $referenceNo . " Date:" . $date_ . "\n");
    fclose($myfile);
} catch (Exception $e) {
    // Continue even if logging fails
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

// This response is the ONLY place bdapps gives us the masked subscriberId, and
// every later call (getStatus, subscription/send) must use it instead of the
// plain tel:88... number. Keep it in the session for this visit and persist it
// so the user can log in again later.
$maskedSubscriberId = isset($response['subscriberId']) ? trim((string) $response['subscriberId']) : '';
if ($maskedSubscriberId !== '') {
    $_SESSION['subscriber_id'] = $maskedSubscriberId;

    $pendingPhone = $_SESSION['pending_phone'] ?? '';
    if ($pendingPhone !== '') {
        try {
            require_once __DIR__ . '/../db.php';
            save_subscriber_id(db(), $pendingPhone, $maskedSubscriberId);
        } catch (Throwable $e) {
            // Session still has it — don't fail verification over a DB write.
        }
    }
}

// Return the response from BDApps
echo json_encode(array(
    'statusCode' => isset($response['statusCode']) ? $response['statusCode'] : 'FAILED',
    'statusDetail' => isset($response['statusDetail']) ? $response['statusDetail'] : '',
    'subscriptionStatus' => isset($response['subscriptionStatus']) ? $response['subscriptionStatus'] : '',
    'version' => isset($response['version']) ? $response['version'] : ''
));

?>