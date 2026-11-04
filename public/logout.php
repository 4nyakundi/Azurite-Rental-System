<?php
require_once __DIR__ . '/../includes/helpers.php';
confirm_mutation('Sign out of Azurite?');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
redirect(public_base() . 'login.php');
