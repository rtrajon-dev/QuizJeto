<?php
/**
 * Log the player out and send them back to the login (register) screen.
 *
 * Required by bdapps guideline #14: a functional Logout that clears the
 * session and redirects to the login page — no exceptions.
 */

session_start();

// Clear all session data and the session cookie.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: /#register');
exit;
