<?php
/**
 * Central configuration loader for QuizJeto.
 *
 * Reads quizjeto/.env once and exposes values via the env() helper, then
 * returns a structured config array. Every file (the bdapps scripts, future
 * quiz backend, etc.) should `require` this instead of hardcoding credentials.
 *
 * Usage:
 *   $config = require __DIR__ . '/../config.php';   // from inside a subfolder
 *   $appId  = $config['bdapps']['app_id'];
 */

if (!function_exists('env')) {
    /**
     * Read a key from quizjeto/.env (parsed once and cached).
     */
    function env($key, $default = null)
    {
        static $vars = null;

        if ($vars === null) {
            $vars = [];
            $path = __DIR__ . '/.env';
            if (is_readable($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                        continue;
                    }
                    list($k, $v) = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v);
                    // strip optional surrounding quotes
                    if (strlen($v) >= 2 &&
                        ($v[0] === '"' || $v[0] === "'") &&
                        substr($v, -1) === $v[0]) {
                        $v = substr($v, 1, -1);
                    }
                    $vars[$k] = $v;
                }
            }
        }

        return array_key_exists($key, $vars) ? $vars[$key] : $default;
    }
}

return [
    'bdapps' => [
        'app_id'   => env('BDAPPS_APP_ID', ''),
        'password' => env('BDAPPS_PASSWORD', ''),
        'app_name' => env('BDAPPS_APP_NAME', 'QuizJeto'),
        'verify_ssl' => filter_var(env('BDAPPS_VERIFY_SSL', 'false'), FILTER_VALIDATE_BOOLEAN),
    ],

    'endpoints' => [
        'otp_request' => env('BDAPPS_OTP_REQUEST_URL', 'https://developer.bdapps.com/subscription/otp/request'),
        'otp_verify'  => env('BDAPPS_OTP_VERIFY_URL',  'https://developer.bdapps.com/subscription/otp/verify'),
        'sub_send'    => env('BDAPPS_SUBSCRIPTION_URL', 'https://developer.bdapps.com/subscription/send'),
        'sub_status'  => env('BDAPPS_SUBSCRIPTION_STATUS_URL', 'https://developer.bdapps.com/subscription/getstatus'),
        'sms_send'    => env('BDAPPS_SMS_URL', 'https://developer.bdapps.com/sms/send'),
        'charging'    => env('BDAPPS_CHARGING_URL', ''),
        'ussd'        => env('BDAPPS_USSD_URL', ''),
    ],

    'db' => [
        'connection'  => env('DB_CONNECTION', 'sqlite'),
        // SQLite: path is resolved relative to this config.php's folder
        'sqlite_path' => __DIR__ . '/' . ltrim(env('DB_SQLITE_PATH', 'database/quizjeto.sqlite'), '/'),
        // MySQL (used later when DB_CONNECTION=mysql)
        'host' => env('DB_HOST', '127.0.0.1'),
        'name' => env('DB_NAME', 'quizjeto'),
        'user' => env('DB_USER', 'root'),
        'pass' => env('DB_PASS', ''),
    ],
];
