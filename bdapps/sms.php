<?php
/**
 * Incoming (mobile-originated) SMS handler for bdapps.
 *
 * bdapps POSTs the incoming message here. We parse it and acknowledge.
 * No phone numbers, messages, or credentials are written to disk (guideline #9).
 */

ini_set('error_log', 'sms-app-error.log');
require 'sdk_file.php';

$config       = require __DIR__ . '/../config.php';
$appid        = $config['bdapps']['app_id'];
$apppassword  = $config['bdapps']['password'];

try {
    // Initialise the receiver with the incoming payload.
    $receiver = new SMSReceiver(file_get_contents('php://input'));
    $sender   = new SmsSender('https://developer.bdapps.com/sms/send', $appid, $apppassword);

    $message = trim($receiver->getMessage()); // text the user sent
    $address = $receiver->getAddress();       // masked subscriber address

    // (Business logic for handling the incoming keyword can go here.)

    http_response_code(200);
} catch (SMSServiceException $e) {
    // Log only the service error code/message — never subscriber data.
    error_log('SMS service error: ' . $e->getErrorCode() . ' ' . $e->getErrorMessage());
    http_response_code(200);
}
