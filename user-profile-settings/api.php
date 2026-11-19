<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$id=require_login();
$action=$_GET['action']??'';
try {
 $pdo=db();
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals(csrf(),$_SERVER['HTTP_X_CSRF_TOKEN']??''))response(['error'=>'Session expired. Refresh the page.'],403);
  $data=($_POST && $action==='photo')?$_POST:json_decode(file_get_contents('php://input'),true);
  if(!is_array($data))$data=[];
 } else $data=[];
 $pdo->prepare('INSERT IGNORE INTO user_settings(user_id) VALUES(?)')->execute([$id]);
 $getSettings=function()use($pdo,$id):array{
  $q=$pdo->prepare('SELECT u.name,u.email,s.* FROM users u JOIN user_settings s ON u.id=s.user_id WHERE u.id=?');$q->execute([$id]);$r=$q->fetch();if(!$r)response(['error'=>'Account not found'],404);
  $prefs=$pdo->prepare('SELECT topic FROM user_reading_preferences WHERE user_id=?');$prefs->execute([$id]);$topics=$prefs->fetchAll(PDO::FETCH_COLUMN);
  $s=['fullName'=>$r['name'],'studentId'=>$r['student_id'],'email'=>$r['email'],'faculty'=>$r['faculty'],'degree'=>$r['degree'],'batchYear'=>(string)($r['batch_year']??'')];
  foreach(['academic','events','sports','general'] as $topic)$s['categories[]:'.$topic]=in_array($topic,$topics,true);
  foreach(['newsNotifications'=>'news_notifications','submissionNotifications'=>'submission_notifications','commentNotifications'=>'comment_notifications','emailNotifications'=>'email_notifications'] as $key=>$col)$s[$key.':on']=(bool)$r[$col];
  return [$s,$r['photo_path']];
 };
 if($action==='bootstrap' && $_SERVER['REQUEST_METHOD']==='GET'){
  [$settings,$photo]=$getSettings();
  $cats=$pdo->query('SELECT id,name,description FROM news_categories ORDER BY id')->fetchAll();
  $q=$pdo->prepare('SELECT category_id,notifications FROM user_news_subscriptions WHERE user_id=?');$q->execute([$id]);$followed=[];
  foreach($q as $r)$followed[(string)$r['category_id']]=['notifications'=>(bool)$r['notifications']];
  response(['csrf'=>csrf(),'settings'=>$settings,'photo'=>$photo,'categories'=>$cats,'followed'=>$followed]);
 }
 if($action==='subscription' && $_SERVER['REQUEST_METHOD']==='POST'){
  $cid=filter_var($data['category_id']??null,FILTER_VALIDATE_INT);if(!$cid)response(['error'=>'Invalid category'],422);
  $q=$pdo->prepare('SELECT 1 FROM news_categories WHERE id=?');$q->execute([$cid]);if(!$q->fetchColumn())response(['error'=>'Unknown category'],404);
  if(!empty($data['subscribe'])){$pdo->prepare('INSERT INTO user_news_subscriptions(user_id,category_id,notifications) VALUES(?,?,?) ON DUPLICATE KEY UPDATE notifications=VALUES(notifications)')->execute([$id,$cid,!empty($data['notifications'])?1:0]);}
  else $pdo->prepare('DELETE FROM user_news_subscriptions WHERE user_id=? AND category_id=?')->execute([$id,$cid]);
  response(['ok'=>true]);
 }
 if($action==='settings' && $_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim((string)($data['fullName']??''));$email=trim((string)($data['email']??''));
  $faculty=trim((string)($data['faculty']??''));$degree=trim((string)($data['degree']??''));$year=(string)($data['batchYear']??'');
  if(mb_strlen($name)<2||mb_strlen($name)>120||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)response(['error'=>'Enter a valid name and email address.'],422);
  if(strlen($faculty)>150||strlen($degree)>180||($year!==''&&(!ctype_digit($year)||(int)$year<2010||(int)$year>(int)date('Y'))))response(['error'=>'Check your faculty, degree and batch year.'],422);
  $current=(string)($data['currentPassword']??'');$next=(string)($data['newPassword']??'');$confirm=(string)($data['confirmPassword']??'');
  if($current!==''||$next!==''||$confirm!==''){
   if($current===''||strlen($next)<8||strlen($next)>128||$next!==$confirm)response(['error'=>'Complete all password fields with a matching new password (8–128 characters).'],422);
   $q=$pdo->prepare('SELECT password FROM users WHERE id=?');$q->execute([$id]);if(!password_verify($current,(string)$q->fetchColumn()))response(['error'=>'Current password is incorrect.'],422);
  }
  $topics=[];foreach(['academic','events','sports','general'] as $t)if(!empty($data['categories[]:'.$t]))$topics[]=$t;
  $pdo->beginTransaction();
  try{
   $q=$pdo->prepare('UPDATE users SET name=?,email=? WHERE id=?');$q->execute([$name,$email,$id]);
   if($next!==''){$q=$pdo->prepare('UPDATE users SET password=? WHERE id=?');$q->execute([password_hash($next,PASSWORD_DEFAULT),$id]);}
   $q=$pdo->prepare('UPDATE user_settings SET faculty=?,degree=?,batch_year=?,news_notifications=?,submission_notifications=?,comment_notifications=?,email_notifications=? WHERE user_id=?');
   $q->execute([$faculty,$degree,$year===''?null:(int)$year,!empty($data['newsNotifications:on'])?1:0,!empty($data['submissionNotifications:on'])?1:0,!empty($data['commentNotifications:on'])?1:0,!empty($data['emailNotifications:on'])?1:0,$id]);
   $pdo->prepare('DELETE FROM user_reading_preferences WHERE user_id=?')->execute([$id]);$q=$pdo->prepare('INSERT INTO user_reading_preferences(user_id,topic) VALUES(?,?)');foreach($topics as $t)$q->execute([$id,$t]);
   $pdo->commit();
  }catch(Throwable $e){$pdo->rollBack();throw $e;}
  [$settings]=$getSettings();response(['ok'=>true,'settings'=>$settings]);
 }
 if($action==='photo' && $_SERVER['REQUEST_METHOD']==='POST'){
  $f=$_FILES['photo']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK||$f['size']>2097152)response(['error'=>'Choose an image smaller than 2 MB.'],422);
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
  if(!$ext||!getimagesize($f['tmp_name']))response(['error'=>'Invalid image file.'],422);
  $file='uploads/'.bin2hex(random_bytes(16)).'.'.$ext;
  if(!move_uploaded_file($f['tmp_name'],__DIR__.'/'.$file))response(['error'=>'Unable to save photo.'],500);
  $q=$pdo->prepare('SELECT photo_path FROM user_settings WHERE user_id=?');$q->execute([$id]);$old=$q->fetchColumn();
  $pdo->prepare('UPDATE user_settings SET photo_path=? WHERE user_id=?')->execute([$file,$id]);
  if($old&&str_starts_with($old,'uploads/'))@unlink(__DIR__.'/'.$old);
  response(['ok'=>true,'photo'=>$file]);
 }
 if($action==='remove_photo' && $_SERVER['REQUEST_METHOD']==='POST'){
  $q=$pdo->prepare('SELECT photo_path FROM user_settings WHERE user_id=?');$q->execute([$id]);$old=$q->fetchColumn();
  $pdo->prepare('UPDATE user_settings SET photo_path=NULL WHERE user_id=?')->execute([$id]);
  if($old&&str_starts_with($old,'uploads/'))@unlink(__DIR__.'/'.$old);
  response(['ok'=>true]);
 }
 response(['error'=>'Unknown action'],404);
}catch(PDOException $e){error_log($e->getMessage());if($e->getCode()==='23000')response(['error'=>'That email address is already registered.'],409);response(['error'=>'Database request failed. Check the server configuration.'],500);}
catch(Throwable $e){error_log($e->getMessage());response(['error'=>'Unable to complete your request.'],500);}
