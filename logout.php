<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_post();
verify_csrf();

$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $params['path'],
    'domain' => $params['domain'],
    'secure' => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => 'Lax',
]);
session_destroy();
redirect('login.php');
