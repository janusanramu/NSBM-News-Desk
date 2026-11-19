<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $token = csrf_token();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Log Out | NSBM NewsDesk</title>
      <style>
        body{min-height:100vh;margin:0;display:grid;place-items:center;background:#071810;color:#eefcf5;font:16px Inter,system-ui,sans-serif}
        main{width:min(420px,calc(100% - 40px));padding:32px;border:1px solid #48e0a355;border-radius:18px;background:#0b2118;text-align:center}
        button{margin-top:16px;padding:12px 24px;border:0;border-radius:10px;background:#064e3b;color:white;font:inherit;font-weight:700;cursor:pointer}
        button:hover{background:#3bb54a;color:#063b2b}
      </style>
    </head>
    <body>
      <main>
        <h1>Log out?</h1>
        <p>Log out of your NSBM NewsDesk account on this browser.</p>
        <form action="logout.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
          <button type="submit">Log Out</button>
        </form>
      </main>
    </body>
    </html>
    <?php
    exit;
}

require_post();
verify_csrf();

$wasAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
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
redirect($wasAdmin ? '../admin-login.php' : '../index.php');
