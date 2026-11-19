<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure', '1');
session_start();
require_once __DIR__ . '/config.php';
function db(): PDO {static $db=null;if($db===null){$db=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}return $db;}
function require_login(): int {
 // Your existing login.php must set $_SESSION['user_id'] after password_verify().
 $id=(int)($_SESSION['user_id']??0);
 if($id<=0){if(str_ends_with($_SERVER['SCRIPT_NAME']??'','.php') && basename($_SERVER['SCRIPT_NAME'])==='api.php'){http_response_code(401);header('Content-Type: application/json');echo json_encode(['error'=>'Please sign in again.']);exit;}header('Location: login.php');exit;}
 return $id;
}
function csrf(): string {if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function response(array $data,int $code=200): never {http_response_code($code);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_SLASHES);exit;}
