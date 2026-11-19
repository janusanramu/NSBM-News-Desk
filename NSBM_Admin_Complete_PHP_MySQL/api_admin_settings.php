<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';
ini_set('display_errors','0');
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function respond(int $status, array $body): never { http_response_code($status); echo json_encode($body); exit; }
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') respond(401,['error'=>'Administrator login required.']);
$userId=(int)$_SESSION['user_id'];
if ($userId < 1) respond(401,['error'=>'Invalid session.']);
if (empty($_SESSION['admin_settings_csrf'])) $_SESSION['admin_settings_csrf']=bin2hex(random_bytes(32));
$pdo=db();
function settings(PDO $pdo,int $id): array {
  $q=$pdo->prepare('SELECT u.name, u.email, s.contact_number, s.photo_path, s.interface_theme, s.compact_tables, s.notify_email, s.notify_articles, s.notify_comments, s.notify_announcements FROM users u LEFT JOIN admin_profile_settings s ON s.user_id=u.id WHERE u.id=? AND u.role=?');
  $q->execute([$id,'admin']); $row=$q->fetch();
  if (!$row) respond(403,['error'=>'Administrator account not found.']);
  return ['asName'=>$row['name'],'asEmail'=>$row['email'],'asPhone'=>$row['contact_number']??'', 'asTheme'=>$row['interface_theme']??'light', 'asCompact'=>(bool)($row['compact_tables']??0),'asNotifyEmail'=>(bool)($row['notify_email']??1),'asNotifyArticles'=>(bool)($row['notify_articles']??1),'asNotifyComments'=>(bool)($row['notify_comments']??1),'asNotifyAnnouncements'=>(bool)($row['notify_announcements']??0),'photo'=>$row['photo_path']??''];
}
if ($_SERVER['REQUEST_METHOD']==='GET') respond(200,['csrf'=>$_SESSION['admin_settings_csrf'],'values'=>settings($pdo,$userId)]);
if ($_SERVER['REQUEST_METHOD']!=='POST') respond(405,['error'=>'Method not allowed.']);
if (!hash_equals($_SESSION['admin_settings_csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??'')) respond(403,['error'=>'Security token expired. Reload the page.']);
$action=$_POST['action']??'save';
if ($action==='photo') {
  if (!isset($_FILES['photo']) || $_FILES['photo']['error']!==UPLOAD_ERR_OK) respond(422,['error'=>'Please select a valid photo.']);
  $file=$_FILES['photo']; if ($file['size']>2097152 || $file['size']<1) respond(422,['error'=>'Photo must be under 2 MB.']);
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
  $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
  if (!$ext || !getimagesize($file['tmp_name'])) respond(422,['error'=>'Use a valid JPG, PNG or WebP photo.']);
  $name=bin2hex(random_bytes(18)).'.'.$ext; $path=__DIR__.'/uploads/'.$name;
  if (!move_uploaded_file($file['tmp_name'],$path)) respond(500,['error'=>'Unable to upload photo.']);
  $url='uploads/'.$name;
  $q=$pdo->prepare('INSERT INTO admin_profile_settings (user_id,photo_path) VALUES (?,?) ON DUPLICATE KEY UPDATE photo_path=VALUES(photo_path)');
  try { $q->execute([$userId,$url]); } catch(Throwable $e) { @unlink($path); throw $e; }
  respond(200,['message'=>'Photo updated.','values'=>settings($pdo,$userId)]);
}
if ($action==='remove_photo') {
  $q=$pdo->prepare('INSERT INTO admin_profile_settings(user_id,photo_path) VALUES (?,NULL) ON DUPLICATE KEY UPDATE photo_path=NULL'); $q->execute([$userId]);
  respond(200,['message'=>'Photo removed.','values'=>settings($pdo,$userId)]);
}
if ($action==='password') {
  $current=(string)($_POST['current']??''); $new=(string)($_POST['new']??''); $confirm=(string)($_POST['confirm']??'');
  if (strlen($new)<12 || strlen($new)>256 || $new!==$confirm || $current==='') respond(422,['error'=>'Enter current password and matching new password (12–256 characters).']);
  $q=$pdo->prepare('SELECT password FROM users WHERE id=? AND role=?'); $q->execute([$userId,'admin']); $hash=$q->fetchColumn();
  if (!$hash || !password_verify($current,(string)$hash)) respond(422,['error'=>'Current password is incorrect.']);
  $q=$pdo->prepare('UPDATE users SET password=? WHERE id=? AND role=?'); $q->execute([password_hash($new,PASSWORD_DEFAULT),$userId,'admin']);
  session_regenerate_id(true);
  respond(200,['message'=>'Password changed successfully.']);
}
if ($action!=='save') respond(422,['error'=>'Invalid action.']);
$name=trim((string)($_POST['asName']??'')); $email=trim((string)($_POST['asEmail']??'')); $phone=trim((string)($_POST['asPhone']??''));
$theme=(string)($_POST['asTheme']??'light');
if ($name==='' || mb_strlen($name)>120 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190 || strlen($phone)>30 || !in_array($theme,['light','dark','system'],true)) respond(422,['error'=>'Check name, email, phone and theme fields.']);
$flags=[];foreach(['asCompact','asNotifyEmail','asNotifyArticles','asNotifyComments','asNotifyAnnouncements'] as $k) $flags[$k]=($_POST[$k]??'0')==='1'?1:0;
try {
  $pdo->beginTransaction();
  $q=$pdo->prepare('UPDATE users SET name=?, email=? WHERE id=? AND role=?');$q->execute([$name,$email,$userId,'admin']);
  $q=$pdo->prepare('INSERT INTO admin_profile_settings (user_id,contact_number,interface_theme,compact_tables,notify_email,notify_articles,notify_comments,notify_announcements) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE contact_number=VALUES(contact_number),interface_theme=VALUES(interface_theme),compact_tables=VALUES(compact_tables),notify_email=VALUES(notify_email),notify_articles=VALUES(notify_articles),notify_comments=VALUES(notify_comments),notify_announcements=VALUES(notify_announcements)');
  $q->execute([$userId,$phone,$theme,$flags['asCompact'],$flags['asNotifyEmail'],$flags['asNotifyArticles'],$flags['asNotifyComments'],$flags['asNotifyAnnouncements']]);
  $pdo->commit();
} catch(PDOException $e) {if($pdo->inTransaction())$pdo->rollBack(); if($e->getCode()==='23000')respond(409,['error'=>'This email address is already in use.']);respond(500,['error'=>'Could not save settings.']);}
respond(200,['message'=>'Settings saved successfully.','values'=>settings($pdo,$userId)]);
