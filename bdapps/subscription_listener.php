<?php
/**
 * Subscription notification listener.
 *
 * bdapps POSTs subscribe/unsubscribe notifications here. We parse and
 * acknowledge them. No subscriber IDs are written to public files (guideline #9).
 */

ini_set('error_log', 'sub-app-error.log');
require 'sdk_file.php';

date_default_timezone_set('Asia/Dhaka');

$body     = file_get_contents('php://input');
$response = json_decode($body);

if (is_object($response)) {
    // Available fields: timeStamp, status, applicationId, subscriberId, frequency.
    // (Persist to a protected store here if you need to track subscriptions.)
    $status = $response->status ?? null;
}

http_response_code(200);
echo json_encode(['ok' => true]);
