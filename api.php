<?php
declare(strict_types=1);
require_once __DIR__.'/NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function reply(array $data, int $code=200): never { http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit; }
function current_user(): array { $u=$_SESSION['user']??null;if(!is_array($u)||empty($u['id']))reply(['error'=>'Please log in first.'],401);if(($u['role']??'')==='admin'&&!admin_session_is_valid($u))reply(['error'=>'Administrator sign-in required.'],401);return $u; }
function admin_required(array $u): void {if(($u['role']??'')!=='admin'||!admin_session_is_valid($u))reply(['error'=>'Administrator sign-in required.'],403);}
function token_required(): void { $got=$_SERVER['HTTP_X_CSRF_TOKEN']??''; $expected=$_SESSION['csrf_token']??'';if(!is_string($got)||!is_string($expected)||$expected===''||!hash_equals($expected,$got))reply(['error'=>'Session expired. Refresh the page and retry.'],403); }
function request_body(): array {$data=json_decode(file_get_contents('php://input'),true);if(!is_array($data))reply(['error'=>'Invalid JSON request.'],400);return $data;}
function strval_trim(array $obj,string $key,int $max=1000): string {$v=$obj[$key]??'';if(!is_string($v)||mb_strlen($v)>$max)reply(['error'=>'Invalid '.$key.'.'],422);return trim($v);}
function table_articles(PDO $db,string $where='',array $params=[],int $limit=100,bool $withAuthor=false): array {
 $q=$db->prepare("SELECT a.id,a.author_id,a.title,a.slug,a.summary,a.body,a.cover_image_url,a.content_image_url,a.image_alt,a.status,a.is_featured,a.view_count,a.created_at,a.submitted_at,a.published_at,a.rejection_reason,c.id category_id,c.name category,c.slug category_slug,COALESCE(u.name,'NSBM NewsDesk') author,u.email author_email".($withAuthor?',u.faculty author_faculty,u.degree author_degree,u.intake author_intake':'').",s.profile_photo_url author_photo_url FROM articles a JOIN categories c ON c.id=a.category_id LEFT JOIN users u ON u.id=a.author_id LEFT JOIN admin_settings s ON s.user_id=u.id $where ORDER BY COALESCE(a.published_at,a.created_at) DESC,a.id DESC LIMIT $limit");$q->execute($params);return $q->fetchAll();
}
function category_id(PDO $db,$provided): int {if(is_numeric($provided)){$q=$db->prepare('SELECT id FROM categories WHERE id=? AND is_active=1');$q->execute([(int)$provided]);}else{$q=$db->prepare('SELECT id FROM categories WHERE (name=? OR slug=?) AND is_active=1');$q->execute([(string)$provided,(string)$provided]);} $id=(int)$q->fetchColumn();if(!$id)reply(['error'=>'Select a valid category.'],422);return $id;}
function slugify(string $text): string {$base=strtolower((string)preg_replace('/[^a-z0-9]+/i','-',mb_substr($text,0,70)));return trim($base,'-').'-'.bin2hex(random_bytes(5));}
function check_image(string $url): string {if($url==='' || preg_match('~^uploads/(?:covers|article-content|avatars|profile)/[a-f0-9]{24,32}\.(?:jpg|png|webp)$~',$url)||preg_match('~^(?:news|home)/assets?/images/[a-zA-Z0-9%._/-]+$~',$url)||preg_match('~^news/assets/images/[a-zA-Z0-9%._/-]+$~',$url))return $url;reply(['error'=>'Please upload your image using the image uploader.'],422);}
function check_content_image(string $url): string {if($url===''||preg_match('~^uploads/article-content/[a-f0-9]{24,32}\.(?:jpg|png|webp)$~',$url))return $url;reply(['error'=>'Please upload your article content image using the image uploader.'],422);}
function save_image(string $folder,int $maxBytes=4*1024*1024): string {
 if(empty($_FILES['image']))reply(['error'=>'Select an image to upload.'],422);
 $file=$_FILES['image'];if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){$message=in_array($file['error'],[UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE],true)?'Image exceeds the server upload limit.':'Image upload failed.';reply(['error'=>$message],422);}
 if(!is_uploaded_file($file['tmp_name'])||$file['size']<=0||$file['size']>$maxBytes)reply(['error'=>'Image must be no larger than '.(int)($maxBytes/1024/1024).' MB.'],422);
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;$originalExt=strtolower(pathinfo((string)($file['name']??''),PATHINFO_EXTENSION));$validExtensions=['jpg'=>['jpg','jpeg'],'png'=>['png'],'webp'=>['webp']];$dimensions=@getimagesize($file['tmp_name']);if(!$ext||!in_array($originalExt,$validExtensions[$ext]??[],true)||!is_array($dimensions)||(int)$dimensions[0]<1||(int)$dimensions[1]<1||(int)$dimensions[0]*(int)$dimensions[1]>50000000)reply(['error'=>'Choose a valid JPG, JPEG, PNG or WebP photo smaller than 50 megapixels.'],422);
 $dir=__DIR__.'/uploads/'.$folder;if(!is_dir($dir)&&!mkdir($dir,0755,true))reply(['error'=>'Cannot create upload directory.'],503);
 $name=bin2hex(random_bytes(16)).'.'.$ext;$path=$dir.'/'.$name;if(!move_uploaded_file($file['tmp_name'],$path))reply(['error'=>'Image upload failed.'],503);return 'uploads/'.$folder.'/'.$name;
}
try {
 $action=(string)($_GET['action']??'');$method=$_SERVER['REQUEST_METHOD']??'GET';
 if($action==='session' && $method==='GET')reply(['role'=>($_SESSION['user']['role']??'guest'),'user'=>$_SESSION['user']??null,'csrf'=>csrf_token()]);
 $db=database();
 if($action==='health' && $method==='GET')reply(['ok'=>true,'database'=>'connected','application'=>'NSBM NewsDesk']);
 if($action==='categories' && $method==='GET'){
  $rows=$db->query('SELECT c.id,c.name,c.slug,c.description,c.is_active,(SELECT COUNT(*) FROM articles a WHERE a.category_id=c.id AND a.status="published") article_count FROM categories c ORDER BY c.name')->fetchAll();reply(['categories'=>$rows]);
 }
 if($action==='published' && $method==='GET'){
  $q=trim((string)($_GET['q']??''));$category=trim((string)($_GET['category']??''));$filters=["a.status='published'"];$params=[];
  if($q!==''){$filters[]='(a.title LIKE ? OR a.summary LIKE ? OR a.body LIKE ?)';$term='%'.mb_substr($q,0,80).'%';$params=array_merge($params,[$term,$term,$term]);}
  $groups=['campus-life'=>['campus-life','events','notices'],'academics'=>['academics','business','computing','engineering'],'research'=>['research'],'clubs-societies'=>['clubs-societies'],'sports'=>['sports']];
  $categoryKey=strtolower($category);
  if($category!==''&&$categoryKey!=='all'&&isset($groups[$categoryKey])){
   $slugs=$groups[$categoryKey];$placeholders=implode(',',array_fill(0,count($slugs),'?'));$condition="c.slug IN ($placeholders)";
   if($categoryKey==='research')$condition="($condition OR a.title LIKE ?)";
   $filters[]=$condition;$params=array_merge($params,$slugs);
   if($categoryKey==='research')$params[]='%research%';
  }elseif($category!==''&&$categoryKey!=='all'){$filters[]='(c.slug=? OR c.name=?)';$params[]=mb_substr($category,0,120);$params[]=mb_substr($category,0,120);}
  reply(['articles'=>table_articles($db,'WHERE '.implode(' AND ',$filters),$params,200)]);
 }
 if($action==='article' && $method==='GET'){
  $id=(int)($_GET['id']??0);if(!$id)reply(['error'=>'Invalid article.'],422);$q=$db->prepare("SELECT a.id,a.author_id,a.title,a.slug,a.summary,a.body,a.cover_image_url,a.content_image_url,a.image_alt,a.status,a.published_at,a.view_count,c.id category_id,c.name category,u.name author,s.profile_photo_url author_photo_url FROM articles a JOIN categories c ON c.id=a.category_id LEFT JOIN users u ON u.id=a.author_id LEFT JOIN admin_settings s ON s.user_id=u.id WHERE a.id=? AND a.status='published'");$q->execute([$id]);$a=$q->fetch();if(!$a)reply(['error'=>'Article not found.'],404);$db->prepare('UPDATE articles SET view_count=view_count+1 WHERE id=?')->execute([$id]);reply(['article'=>$a]);
 }
 if($action==='newsletter' && $method==='POST'){
  token_required();$d=request_body();$email=strtolower(strval_trim($d,'email',254));if(!filter_var($email,FILTER_VALIDATE_EMAIL))reply(['error'=>'Enter a valid email address.'],422);
  $db->prepare('INSERT INTO newsletter_subscribers(email,is_active) VALUES (?,1) ON DUPLICATE KEY UPDATE is_active=1,unsubscribed_at=NULL')->execute([$email]);reply(['ok'=>true]);
 }
 // Guests can read approved comments on a published article; posting still requires login.
 if($action==='comments' && $method==='GET' && empty($_SESSION['user'])){
  $article=(int)($_GET['article_id']??0);if($article<1)reply(['error'=>'Select an article.'],422);
  $q=$db->prepare("SELECT cm.id,cm.article_id,cm.body,cm.status,cm.created_at,u.name author,s.profile_photo_url author_photo_url FROM comments cm JOIN users u ON u.id=cm.user_id LEFT JOIN admin_settings s ON s.user_id=u.id JOIN articles a ON a.id=cm.article_id WHERE cm.article_id=? AND cm.status='approved' AND a.status='published' ORDER BY cm.created_at DESC LIMIT 100");$q->execute([$article]);reply(['comments'=>$q->fetchAll()]);
 }
 $u=current_user();$uid=(int)$u['id'];$admin=($u['role']??'')==='admin';
 if($action==='pending_students' && $method==='GET'){
  admin_required($u);$status=(string)($_GET['status']??'pending');if(!in_array($status,['pending','rejected'],true))$status='pending';
  $q=$db->prepare("SELECT id,name,student_id,email,faculty,degree,intake,created_at FROM users WHERE role='student' AND account_status=? ORDER BY created_at DESC LIMIT 200");$q->execute([$status]);
  $pendingCount=(int)$db->query("SELECT COUNT(*) FROM users WHERE role='student' AND account_status='pending'")->fetchColumn();
  reply(['students'=>$q->fetchAll(),'pending_count'=>$pendingCount]);
 }
 if($action==='student_review' && $method==='POST'){
  admin_required($u);token_required();$d=request_body();$id=(int)($d['id']??0);$decision=(string)($d['decision']??'');
  if(!$id||!in_array($decision,['approve','reject'],true))reply(['error'=>'Invalid review action.'],422);
  $q=$db->prepare("UPDATE users SET account_status=?,approved_at=NOW(),approved_by=? WHERE id=? AND role='student'");$q->execute([$decision==='approve'?'approved':'rejected',$uid,$id]);
  if(!$q->rowCount())reply(['error'=>'Student account not found.'],404);reply(['ok'=>true]);
 }
 if($action==='stats' && $method==='GET'){
  admin_required($u);$stats=[];
  $stats['students']=(int)$db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
  $stats['readership']=(int)$db->query('SELECT COALESCE(SUM(view_count),0) FROM articles')->fetchColumn();
  $stats['admins']=(int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
  $stats['articles']=(int)$db->query('SELECT COUNT(*) FROM articles')->fetchColumn();
  $stats['pending']=(int)$db->query("SELECT COUNT(*) FROM articles WHERE status='pending_review'")->fetchColumn();
  $stats['published']=(int)$db->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();
  $stats['new_admins']=(int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin' AND created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')")->fetchColumn();
  reply(['stats'=>$stats]);
 }
 if($action==='categories' && $method==='POST'){
  admin_required($u);token_required();$d=request_body();$name=strval_trim($d,'name',100);$description=strval_trim($d,'description',500);$id=(int)($d['id']??0);
  if(mb_strlen($name)<2)reply(['error'=>'Category name must contain at least two characters.'],422);
  $slug=strtolower((string)preg_replace('/[^a-z0-9]+/i','-',str_replace('&','and',$name)));$slug=trim($slug,'-');if($slug==='')reply(['error'=>'Use a simple category name.'],422);
  if($id)$db->prepare('UPDATE categories SET name=?,slug=?,description=? WHERE id=?')->execute([$name,$slug,$description,$id]);
  else $db->prepare('INSERT INTO categories(name,slug,description) VALUES (?,?,?)')->execute([$name,$slug,$description]);
  reply(['ok'=>true]);
 }
 if($action==='categories' && $method==='DELETE'){
  admin_required($u);token_required();$d=request_body();$id=(int)($d['id']??0);if($id<=0)reply(['error'=>'Invalid category.'],422);
  $q=$db->prepare('SELECT COUNT(*) FROM articles WHERE category_id=?');$q->execute([$id]);if((int)$q->fetchColumn()>0)reply(['error'=>'This category contains articles and cannot be removed.'],409);
  $db->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);reply(['ok'=>true]);
 }
 if($action==='articles' && $method==='GET')reply(['articles'=>$admin?table_articles($db,'',[],300,true):table_articles($db,'WHERE a.author_id=?',[$uid],200)]);
 if($action==='bookmarks' && $method==='GET'){
  $q=$db->prepare("SELECT a.id,a.title,a.summary,a.cover_image_url,a.created_at,a.published_at,c.name category,c.slug category_slug,u.name author,s.profile_photo_url author_photo_url FROM bookmarks b JOIN articles a ON a.id=b.article_id JOIN categories c ON c.id=a.category_id LEFT JOIN users u ON u.id=a.author_id LEFT JOIN admin_settings s ON s.user_id=u.id WHERE b.user_id=? AND a.status='published' ORDER BY b.created_at DESC");$q->execute([$uid]);reply(['bookmarks'=>$q->fetchAll()]);
 }
 if($action==='bookmarks' && $method==='POST'){
  token_required();$d=request_body();$id=(int)($d['article_id']??0);if(!$id)reply(['error'=>'Invalid article.'],422);
  if(!empty($d['saved']))$db->prepare("INSERT IGNORE INTO bookmarks(user_id,article_id) SELECT ?,id FROM articles WHERE id=? AND status='published'")->execute([$uid,$id]);
  else $db->prepare('DELETE FROM bookmarks WHERE user_id=? AND article_id=?')->execute([$uid,$id]);reply(['ok'=>true]);
 }
 if($action==='subscriptions' && $method==='GET'){
  $q=$db->prepare('SELECT category_id,notifications FROM category_subscriptions WHERE user_id=?');$q->execute([$uid]);reply(['subscriptions'=>$q->fetchAll()]);
 }
 if($action==='subscriptions' && $method==='POST'){
  token_required();$d=request_body();$id=category_id($db,$d['category_id']??0);if(!empty($d['subscribed']))$db->prepare('INSERT INTO category_subscriptions(user_id,category_id,notifications) VALUES (?,?,?) ON DUPLICATE KEY UPDATE notifications=VALUES(notifications)')->execute([$uid,$id,!empty($d['notifications'])?1:0]);
  else $db->prepare('DELETE FROM category_subscriptions WHERE user_id=? AND category_id=?')->execute([$uid,$id]);reply(['ok'=>true]);
 }
 if($action==='notifications' && $method==='GET'){
  $q=$db->prepare("SELECT DISTINCT a.id,a.title,a.published_at,c.name category,(r.article_id IS NOT NULL) is_read FROM category_subscriptions s JOIN articles a ON a.category_id=s.category_id AND a.status='published' AND a.published_at>=s.created_at LEFT JOIN user_notification_reads r ON r.user_id=s.user_id AND r.article_id=a.id JOIN categories c ON c.id=a.category_id WHERE s.user_id=? AND s.notifications=1 ORDER BY a.published_at DESC,a.id DESC LIMIT 20");$q->execute([$uid]);$items=$q->fetchAll();$unread=0;
  foreach($items as &$item){$item['read']=(bool)$item['is_read'];unset($item['is_read']);if(!$item['read'])$unread++;}unset($item);
  reply(['notifications'=>$items,'unread_count'=>$unread]);
 }
 if($action==='notifications' && $method==='POST'){
  token_required();$db->prepare("INSERT INTO user_notification_reads(user_id,article_id) SELECT ?,a.id FROM category_subscriptions s JOIN articles a ON a.category_id=s.category_id AND a.status='published' AND a.published_at>=s.created_at WHERE s.user_id=? AND s.notifications=1 GROUP BY a.id ON DUPLICATE KEY UPDATE read_at=CURRENT_TIMESTAMP")->execute([$uid,$uid]);reply(['ok'=>true]);
 }
 if($action==='profile' && $method==='GET'){
  $q=$db->prepare('SELECT u.id,u.name,u.student_id,u.email,u.faculty,u.degree,u.intake,u.role,u.created_at,s.profile_photo_url FROM users u LEFT JOIN admin_settings s ON s.user_id=u.id WHERE u.id=?');$q->execute([$uid]);$p=$q->fetch();reply(['profile'=>$p]);
 }
 if($action==='profile' && $method==='POST'){
  token_required();$d=request_body();$name=strval_trim($d,'name',100);$faculty=strval_trim($d,'faculty',80);$degree=strval_trim($d,'degree',120);$intake=strval_trim($d,'intake',4);if(mb_strlen($name)<2)reply(['error'=>'Name must be at least two characters.'],422);if($intake!==''&&!preg_match('/\A\d{2}\.\d\z/',$intake))reply(['error'=>'Select a valid intake.'],422);
  $db->prepare('UPDATE users SET name=?,faculty=?,degree=?,intake=? WHERE id=?')->execute([$name,$faculty,$degree?:null,$intake?:null,$uid]);$_SESSION['user']['name']=$name;reply(['ok'=>true]);
 }
 if($action==='password' && $method==='POST'){
  token_required();$d=request_body();$old=(string)($d['current']??'');$next=(string)($d['next']??'');if(strlen($next)<8||strlen($next)>72)reply(['error'=>'New password must be 8–72 characters.'],422);
  $q=$db->prepare('SELECT password FROM users WHERE id=?');$q->execute([$uid]);$hash=$q->fetchColumn();if(!$hash||!password_verify($old,$hash))reply(['error'=>'Current password is incorrect.'],403);
  $db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($next,PASSWORD_DEFAULT),$uid]);reply(['ok'=>true]);
 }
 if($action==='avatar' && $method==='POST'){
  token_required();$path=save_image('profile',5*1024*1024);$q=$db->prepare('SELECT profile_photo_url FROM admin_settings WHERE user_id=?');$q->execute([$uid]);$old=(string)($q->fetchColumn()?:'');
  try{$db->prepare('INSERT INTO admin_settings(user_id,profile_photo_url) VALUES (?,?) ON DUPLICATE KEY UPDATE profile_photo_url=VALUES(profile_photo_url)')->execute([$uid,$path]);}catch(Throwable $error){if(is_file(__DIR__.'/'.$path)&&!unlink(__DIR__.'/'.$path))error_log('NewsDesk could not remove failed profile upload: '.$path);throw $error;}
  if(preg_match('~^uploads/(?:profile|avatars)/[a-f0-9]{24,32}\.(?:jpg|png|webp)$~',$old)&&is_file(__DIR__.'/'.$old)&&!unlink(__DIR__.'/'.$old))error_log('NewsDesk could not remove replaced profile image: '.$old);
  reply(['ok'=>true,'url'=>$path]);
 }
 if($action==='avatar' && $method==='DELETE'){
  token_required();$q=$db->prepare('SELECT profile_photo_url FROM admin_settings WHERE user_id=?');$q->execute([$uid]);$old=(string)($q->fetchColumn()?:'');
  $db->prepare('UPDATE admin_settings SET profile_photo_url=NULL WHERE user_id=?')->execute([$uid]);
  if(preg_match('~^uploads/(?:profile|avatars)/[a-f0-9]{24,32}\.(?:jpg|png|webp)$~',$old)&&is_file(__DIR__.'/'.$old)&&!unlink(__DIR__.'/'.$old))error_log('NewsDesk could not remove profile image: '.$old);
  reply(['ok'=>true]);
 }
 if($action==='cover' && $method==='POST'){
  token_required();$path=save_image('covers');reply(['ok'=>true,'url'=>$path]);
 }
 if($action==='article-content-image' && $method==='POST'){
  token_required();$path=save_image('article-content');reply(['ok'=>true,'url'=>$path]);
 }
 if($action==='articles' && $method==='POST'){
  token_required();$d=request_body();$title=strval_trim($d,'title',240);$body=strval_trim($d,'body',200000);$summary=strval_trim($d,'summary',600);if(mb_strlen($title)<4||mb_strlen($body)<10)reply(['error'=>'Title needs 4+ characters and article needs 10+ characters.'],422);
  $cat=category_id($db,$d['category']??'Academics');$summary=$summary?:mb_substr($body,0,200);$image=array_key_exists('cover_image_url',$d)?check_image((string)$d['cover_image_url']):null;$contentImage=array_key_exists('content_image_url',$d)?check_content_image((string)$d['content_image_url']):null;$id=(int)($d['id']??0);$status=(string)($d['status']??($admin?'draft':'pending_review'));
  if(!$admin&&!in_array($status,['draft','pending_review'],true))reply(['error'=>'Invalid submission status.'],422);
   if($id){$sql=$admin?'UPDATE articles SET title=?,body=?,summary=?,category_id=?,cover_image_url=COALESCE(?,cover_image_url),content_image_url=COALESCE(?,content_image_url),status=?,submitted_at=NOW() WHERE id=?':"UPDATE articles SET title=?,body=?,summary=?,category_id=?,cover_image_url=COALESCE(?,cover_image_url),content_image_url=COALESCE(?,content_image_url),published_at=IF(status='published' AND ?='pending_review',NULL,published_at),status=?,submitted_at=NOW() WHERE id=? AND author_id=? AND status<>'archived'";
    $args=$admin?[$title,$body,$summary,$cat,$image,$contentImage,$status,$id]:[$title,$body,$summary,$cat,$image,$contentImage,$status,$status,$id,$uid];$q=$db->prepare($sql);$q->execute($args);if(!$q->rowCount())reply(['error'=>'This article cannot be edited.'],403);
  }else{
    $status=$admin?'draft':$status;$q=$db->prepare('INSERT INTO articles(author_id,category_id,title,slug,summary,body,cover_image_url,content_image_url,status,submitted_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())');$q->execute([$uid,$cat,$title,slugify($title),$summary,$body,$image,$contentImage,$status]);$id=(int)$db->lastInsertId();
  }reply(['ok'=>true,'id'=>$id]);
 }
 if($action==='articles' && $method==='DELETE'){
  token_required();$d=request_body();$id=(int)($d['id']??0);if(!$id)reply(['error'=>'Invalid article.'],422);
  $q=$db->prepare($admin?'DELETE FROM articles WHERE id=?':"DELETE FROM articles WHERE id=? AND author_id=? AND status<>'archived'");$q->execute($admin?[$id]:[$id,$uid]);if(!$q->rowCount())reply(['error'=>'Article cannot be deleted.'],403);reply(['ok'=>true]);
 }
 if($action==='moderate' && $method==='POST'){
  admin_required($u);token_required();$d=request_body();$id=(int)($d['id']??0);$status=(string)($d['status']??'');$map=['Published'=>'published','Pending'=>'pending_review','Rejected'=>'rejected','Draft'=>'draft','Archived'=>'archived'];if(!$id||!isset($map[$status]))reply(['error'=>'Invalid moderation action.'],422);
  $reason=strval_trim($d,'reason',2000);$q=$db->prepare('UPDATE articles SET status=?,rejection_reason=?,reviewed_by=?,reviewed_at=NOW(),published_at=IF(?="published",NOW(),published_at) WHERE id=?');$q->execute([$map[$status],$reason,$uid,$map[$status],$id]);reply(['ok'=>true]);
 }
 if($action==='announcements' && $method==='GET'){
  $q=$db->query($admin?'SELECT id,title,body message,status,DATE(created_at) date FROM announcements ORDER BY created_at DESC LIMIT 200':"SELECT id,title,body message,status,DATE(created_at) date FROM announcements WHERE status='published' ORDER BY created_at DESC LIMIT 200");reply(['announcements'=>$q->fetchAll()]);
 }
 if($action==='admin_sync' && $method==='POST'){
  admin_required($u);token_required();$d=request_body();$arts=$d['articles']??[];$anns=$d['announcements']??[];if(!is_array($arts)||count($arts)>400||!is_array($anns)||count($anns)>250)reply(['error'=>'Too many records.'],422);
  $db->beginTransaction();$articleMap=[];$annMap=[];
  try{
   foreach($arts as $a){if(!is_array($a))continue;$title=strval_trim($a,'title',240);if(mb_strlen($title)<4)continue;$id=(int)($a['id']??0);$cat=category_id($db,$a['category']??'Academics');$summary=strval_trim($a,'description',600);$body=strval_trim($a,'body',200000)?:$summary;$status=['Published'=>'published','Pending'=>'pending_review','Rejected'=>'rejected','Draft'=>'draft','Archived'=>'archived'][(string)($a['status']??'Draft')]??'draft';
    if($id>0){$q=$db->prepare('UPDATE articles SET title=?,summary=?,body=?,category_id=?,status=?,rejection_reason=?,reviewed_by=?,reviewed_at=NOW(),published_at=IF(?="published",COALESCE(published_at,NOW()),published_at) WHERE id=?');$q->execute([$title,$summary?:mb_substr($body,0,200),$body,$cat,$status,strval_trim($a,'reason',2000),$uid,$status,$id]);}
    else{$q=$db->prepare('INSERT INTO articles(author_id,category_id,title,slug,summary,body,status,published_at) VALUES (?,?,?,?,?,?,?,IF(?="published",NOW(),NULL))');$q->execute([$uid,$cat,$title,slugify($title),$summary?:mb_substr($body,0,200),$body,$status,$status]);$articleMap[(string)($a['client_id']??'new')]=(int)$db->lastInsertId();}
   }
   foreach(($d['deleted_articles']??[]) as $id){$id=(int)$id;if($id>0)$db->prepare('DELETE FROM articles WHERE id=?')->execute([$id]);}
   foreach($anns as $a){if(!is_array($a))continue;$title=strval_trim($a,'title',240);$message=strval_trim($a,'message',10000);if(!$title||!$message)continue;$id=(int)($a['id']??0);$status=(($a['status']??'Draft')==='Published')?'published':'draft';
    if($id>0)$db->prepare('UPDATE announcements SET title=?,body=?,status=?,published_at=IF(?="published",COALESCE(published_at,NOW()),NULL) WHERE id=?')->execute([$title,$message,$status,$status,$id]);
    else{$q=$db->prepare('INSERT INTO announcements(author_id,title,body,status,published_at) VALUES (?,?,?,?,IF(?="published",NOW(),NULL))');$q->execute([$uid,$title,$message,$status,$status]);$annMap[(string)($a['client_id']??'new')]=(int)$db->lastInsertId();}
   }
   foreach(($d['deleted_announcements']??[]) as $id){$id=(int)$id;if($id>0)$db->prepare('DELETE FROM announcements WHERE id=?')->execute([$id]);}
   $db->commit();reply(['ok'=>true,'article_ids'=>$articleMap,'announcement_ids'=>$annMap]);
  }catch(Throwable $ex){$db->rollBack();throw $ex;}
 }
 if($action==='admins' && $method==='GET'){
  admin_required($u);$rows=$db->query("SELECT id,name,email,faculty,DATE(created_at) created_at FROM users WHERE role='admin' ORDER BY id DESC")->fetchAll();reply(['admins'=>$rows]);
 }
 if($action==='admins' && $method==='POST'){
  admin_required($u);token_required();$d=request_body();$name=strval_trim($d,'name',100);$email=strtolower(strval_trim($d,'email',150));$pass=(string)($d['password']??'');if(mb_strlen($name)<2||!is_nsbm_email($email)||strlen($pass)<12||strlen($pass)>72)reply(['error'=>'Provide a name, NSBM email and password of at least 12 characters.'],422);
  $q=$db->prepare("INSERT INTO users(name,email,password,role,faculty) VALUES (?,?,?,'admin','Staff')");$q->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);reply(['ok'=>true]);
 }
 if($action==='admins' && $method==='DELETE'){
  admin_required($u);token_required();$d=request_body();$id=(int)($d['id']??0);if(!$id||$id===$uid)reply(['error'=>'You cannot remove your own account.'],422);$q=$db->prepare("DELETE FROM users WHERE id=? AND role='admin'");$q->execute([$id]);reply(['ok'=>true]);
 }
 if($action==='admin_settings' && $method==='GET'){
  admin_required($u);$q=$db->prepare('SELECT * FROM admin_settings WHERE user_id=?');$q->execute([$uid]);reply(['settings'=>$q->fetch()?:null]);
 }
 if($action==='admin_settings' && $method==='POST'){
  admin_required($u);token_required();$d=request_body();$phone=strval_trim($d,'contact_number',30);$theme=(string)($d['theme']??'dark');if(!in_array($theme,['dark','light','system'],true))$theme='dark';
  $db->prepare('INSERT INTO admin_settings(user_id,contact_number,theme,compact_tables,notify_email,notify_articles,notify_comments,notify_announcements) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE contact_number=VALUES(contact_number),theme=VALUES(theme),compact_tables=VALUES(compact_tables),notify_email=VALUES(notify_email),notify_articles=VALUES(notify_articles),notify_comments=VALUES(notify_comments),notify_announcements=VALUES(notify_announcements)')->execute([$uid,$phone,$theme,(int)!empty($d['compact_tables']),(int)!empty($d['notify_email']),(int)!empty($d['notify_articles']),(int)!empty($d['notify_comments']),(int)!empty($d['notify_announcements'])]);reply(['ok'=>true]);
 }
 if($action==='comments' && $method==='GET'){
  $id=(int)($_GET['article_id']??0);  $q=$db->prepare($admin&&$id===0?"SELECT cm.id,cm.article_id,cm.body,cm.status,cm.created_at,u.name author,s.profile_photo_url author_photo_url,a.title article_title FROM comments cm JOIN users u ON u.id=cm.user_id LEFT JOIN admin_settings s ON s.user_id=u.id JOIN articles a ON a.id=cm.article_id ORDER BY cm.created_at DESC LIMIT 250":"SELECT cm.id,cm.article_id,cm.body,cm.status,cm.created_at,u.name author,s.profile_photo_url author_photo_url FROM comments cm JOIN users u ON u.id=cm.user_id LEFT JOIN admin_settings s ON s.user_id=u.id WHERE cm.article_id=? AND cm.status='approved' ORDER BY cm.created_at DESC LIMIT 100");$q->execute($admin&&$id===0?[]:[$id]);reply(['comments'=>$q->fetchAll()]);
 }
 if($action==='comments' && $method==='POST'){
  token_required();$d=request_body();$id=(int)($d['article_id']??0);$body=strval_trim($d,'body',3000);if(!$id||mb_strlen($body)<2)reply(['error'=>'Enter a comment.'],422);
  $q=$db->prepare("INSERT INTO comments(article_id,user_id,body,status) SELECT id,?,?,'pending' FROM articles WHERE id=? AND status='published'");$q->execute([$uid,$body,$id]);if(!$q->rowCount())reply(['error'=>'Article unavailable.'],404);reply(['ok'=>true,'message'=>'Comment sent for moderation.']);
 }
 if($action==='comment_moderate' && $method==='POST'){
  admin_required($u);token_required();$d=request_body();$id=(int)($d['id']??0);$status=(string)($d['status']??'');if(!$id||!in_array($status,['approved','removed','pending'],true))reply(['error'=>'Invalid moderation.'],422);$db->prepare('UPDATE comments SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?')->execute([$status,$uid,$id]);reply(['ok'=>true]);
 }
 if($action==='reports' && $method==='GET'){
  admin_required($u);reply(['reports'=>$db->query('SELECT id,reason,details,status,created_at FROM reports ORDER BY created_at DESC LIMIT 200')->fetchAll()]);
 }
 if($action==='report' && $method==='POST'){
  token_required();$d=request_body();$article=(int)($d['article_id']??0);$reason=strval_trim($d,'reason',120);$details=strval_trim($d,'details',2000);if(!$article||!$reason)reply(['error'=>'Select a reason.'],422);$db->prepare('INSERT INTO reports(reporter_user_id,article_id,reason,details) VALUES (?,?,?,?)')->execute([$uid,$article,$reason,$details]);reply(['ok'=>true]);
 }
 if($action==='report_moderate' && $method==='POST'){
  admin_required($u);token_required();$d=request_body();$id=(int)($d['id']??0);$status=(string)($d['status']??'');if(!$id||!in_array($status,['open','reviewing','resolved','dismissed'],true))reply(['error'=>'Invalid report action.'],422);$db->prepare('UPDATE reports SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?')->execute([$status,$uid,$id]);reply(['ok'=>true]);
 }
 reply(['error'=>'Unknown action or method.'],404);
} catch(PDOException $e){error_log('NewsDesk SQL: '.$e->getMessage());$local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);reply(['error'=>'Database unavailable or missing tables. Check nsbm_newsdesk import and app/local-config.php.'.($local?' [Details: '.$e->getMessage().']':'')],503);}
catch(Throwable $e){error_log('NewsDesk API: '.$e->getMessage());$local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);reply(['error'=>'Server could not complete the request.'.($local?' [Details: '.$e->getMessage().']':'')],500);}
