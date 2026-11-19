<?php
declare(strict_types=1);
require_once __DIR__.'/../NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';
function app_base(): string {$path=str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/index.php'));return $path==='/'||$path==='.'?'/':rtrim($path,'/').'/';}
function url_for(string $path): string {return app_base().ltrim($path,'/');}
function app_user(): ?array {return isset($_SESSION['user'])&&is_array($_SESSION['user'])?$_SESSION['user']:null;}
function app_role(): string {return (app_user()['role']??'guest')==='admin'?'admin':((app_user()['role']??'guest')==='student'?'student':'guest');}
function newsdesk_category_groups(): array {
 return [
  'campus-life'=>['name'=>'Campus Life','slugs'=>['campus-life','events','notices']],
  'academics'=>['name'=>'Academics','slugs'=>['academics','business','computing','engineering']],
  'research'=>['name'=>'Research','slugs'=>['research']],
  'clubs-societies'=>['name'=>'Club & Societies','slugs'=>['clubs-societies']],
  'sports'=>['name'=>'Sports','slugs'=>['sports']],
 ];
}
function newsdesk_category_group(string $slug): string {
 foreach(newsdesk_category_groups() as $group=>$details)if(in_array(strtolower($slug),$details['slugs'],true))return $group;
 return strtolower($slug);
}
function nav_categories(string $role): string {
 $route=$role==='guest'?'news.php':($role==='admin'?'admin-news.php':'student-news.php');
 $s='<a href="'.e(url_for($route)).'">All</a>';
 foreach(newsdesk_category_groups() as $slug=>$group)$s.='<a href="'.e(url_for($route).'?category='.rawurlencode($slug)).'">'.e($group['name']).'</a>';
 return $s;
}
function nav_markup(string $name,string $role): string {
 $kind=$role==='guest'?'guest':'user';$s=file_get_contents(__DIR__.'/../Header footer/templates/'.$name.'-'.$kind.'.html');
 $tag=$name==='header'?'header':'footer';if(preg_match('~<'.$tag.'\b[\s\S]*?</'.$tag.'>~i',$s,$m))$s=$m[0];
 $u=app_user();$parts=preg_split('/\s+/',trim((string)($u['name']??'Student User')));$initials=strtoupper(substr($parts[0]??'S',0,1).substr(count($parts)>1?end($parts):'',0,1));
 $currentPage=basename((string)($_SERVER['SCRIPT_NAME']??''));
 $homeActive=in_array($currentPage,['index.php','student-home.php'],true)?'active':'';
 $latestActive=in_array($currentPage,['news.php','student-news.php','admin-news.php','article.php'],true)?'active':'';
 $s=strtr($s,['{{ASSET_BASE_URL}}'=>rtrim(url_for('Header footer'),'/'),'{{USER_INITIALS}}'=>e($initials),'{{USER_FIRST_NAME}}'=>e($parts[0]??'Student'),'{{USER_LAST_NAME}}'=>e(count($parts)>1?end($parts):''),'{{HOME_ACTIVE}}'=>$homeActive,'{{LATEST_ACTIVE}}'=>$latestActive,'{{SUBMISSION_COUNT}}'=>'0','{{BOOKMARK_COUNT}}'=>'0','{{CURRENT_YEAR}}'=>date('Y'),'{{CATEGORY_LINKS}}'=>nav_categories($role)]);
 $routeHome=$role==='guest'?'index.php':($role==='admin'?'admin.php':'student-home.php');$routeNews=$role==='guest'?'news.php':($role==='admin'?'admin-news.php':'student-news.php');$dash=$role==='admin'?'admin.php':'dashboard.php';
 $links=['index.php'=>$routeHome,'latest-news.php'=>$routeNews,'login.php'=>'NSBM-News-Desk-feature-login-register-page/login.php','register.php'=>'NSBM-News-Desk-feature-login-register-page/register.php','logout.php'=>'NSBM-News-Desk-feature-login-register-page/logout.php','submit-article.php'=>$role==='admin'?'admin.php#articles':'submit-article.php','profile.php'=>$dash.'#settings','my-submissions.php'=>$role==='admin'?'admin.php#articles':'my-submissions.php','bookmarks.php'=>$role==='admin'?'admin.php#admin-management':'bookmarks.php','notification-preferences.php'=>$dash.($role==='admin'?'#announcements':'#subscription'),'search.php'=>$routeNews,'privacy.php'=>'NSBM-News-Desk-feature-login-register-page/terms.html','terms.php'=>'NSBM-News-Desk-feature-login-register-page/terms.html'];
 $s=preg_replace_callback('~\b(href|action)="([^"]*)"~',static function($m)use($links){$url=$m[2];$file=explode('?',$url)[0];if(!isset($links[$file]))return $m[0];return $m[1].'="'.e(url_for($links[$file])).'"';},$s);
 $catRoute=$role==='guest'?'news.php':($role==='admin'?'admin-news.php':'student-news.php');
 $s=preg_replace_callback('~href="category\.php\?cat=([a-z-]+)"~i',static function($m)use($catRoute){$legacy=['campus-headlines'=>'campus-life','clubs'=>'clubs-societies'];$slug=$legacy[$m[1]]??newsdesk_category_group($m[1]);return 'href="'.e(url_for($catRoute.'?category='.$slug)).'"';},$s);

 if($name==='header' && $role!=='guest'){
  $dashboardActive=in_array($currentPage,['dashboard.php','admin.php'],true)?' active':'';
  $dashboard='<a class="nav-link dashboard-link'.$dashboardActive.'" href="'.e(url_for($dash)).'"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a>';
  if($role!=='admin')$s=str_replace('<div class="dropdown" id="categoriesDropdown">',$dashboard.'<div class="dropdown" id="categoriesDropdown">',$s);
  if($role==='admin'){
    // Admin's top navigation never needs a Dashboard link, including Latest News.
    $s=preg_replace('~<a\b(?=[^>]*\bclass=["\'][^"\']*\bnav-link\b)(?=[^>]*\bhref=["\'][^"\']*(?:admin\.php|dashboard\.php)(?:[?#][^"\']*)?["\'])[^>]*>\s*(?:<i\b[^>]*>\s*</i>\s*)?Dashboard\s*</a>~i','',$s);
    $s=str_replace('> Submit Article','> Manage Articles',$s);
    $s=str_replace('User Profile</a>','Admin Profile Settings</a>',$s);
    $s=str_replace('My Submissions</a>','Manage Articles</a>',$s);
    $s=str_replace('Saved Bookmarks</a>','Admin Management</a>',$s);
    $s=str_replace('<a href="'.e(url_for('admin.php#articles')).'"><i class="fa-regular fa-bookmark"></i> Admin Management</a>','<a href="'.e(url_for('admin.php#admin-management')).'"><i class="fa-regular fa-bookmark"></i> Admin Management</a>',$s);
  }else{
    $bookmark='<a href="'.e(url_for('dashboard.php#subscription')).'"><i class="fa-regular fa-bell"></i> My Subscriptions</a>';
    $s=str_replace('<a href="'.e(url_for('bookmarks.php')).'">', $bookmark.'<a href="'.e(url_for('bookmarks.php')).'">',$s);
  }
  $notification=$role==='admin'?'admin.php#comments':'dashboard.php#subscription';
  if($role==='student'){
    $notificationButton='<div class="notification-wrap"><button class="icon-btn notification-toggle" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="notificationMenu"><i class="fa-regular fa-bell"></i><span class="notif-dot" hidden></span></button><section class="notification-menu" id="notificationMenu" aria-label="Notifications" hidden><h2>Subscribed news</h2><div class="notification-items" aria-live="polite">Loading notifications…</div></section></div>';
    $s=preg_replace('~<button class="icon-btn" type="button" aria-label="Notifications">[\s\S]*?</button>~',$notificationButton,$s,1);
  }else{
    $s=preg_replace('~<button class="icon-btn" type="button" aria-label="Notifications">[\s\S]*?</button>~', '<a class="icon-btn" aria-label="Notifications" href="'.e(url_for($notification)).'"><i class="fa-regular fa-bell"></i><span class="notif-dot"></span></a>', $s,1);
  }
 }
 return $s;
}
/** Original animated logo, shown only on authenticated pages. */
function nsbm_preloader_markup(string $role): string {
 if(!in_array($role,['guest','admin','student'],true))return '';
 return '<div id="nsbm-page-preloader" role="status" aria-label="Loading NSBM NewsDesk"><div class="nsbm-loader"><span class="nsbm-pulse"></span><span class="nsbm-pulse"></span><img src="'.e(url_for('assets/images/NSBM NewsDesk Logo.png')).'" alt="NSBM NewsDesk logo" fetchpriority="high"></div></div>';
}
function rebase_assets(string $html,string $directory): string {
 $html=preg_replace_callback('~\b(src|href|poster)=("|\')(.*?)\2~i',static function($m)use($directory){$target=html_entity_decode($m[3],ENT_QUOTES);if($target===''||preg_match('~^(?:https?:|//|data:|mailto:|tel:|#|javascript:|/)~i',$target))return $m[0];if($m[1]==='href'&&preg_match('~\.(?:php|html)(?:[?#]|$)~i',$target))return $m[0];return $m[1].'='.$m[2].e(url_for($directory.'/'.$target)).$m[2];},$html);
 if($directory==='news')$html=preg_replace('~url\((["\']?)assets/images/~i','url($1'.url_for('news/assets/images/'),$html);
 return $html;
}
function render_original(string $file,string $directory,string $role): void {
 $raw=file_get_contents(__DIR__.'/../'.$file);if(!preg_match('~<head\b[^>]*>(.*?)</head>~is',$raw,$h)||!preg_match('~<body\b[^>]*>(.*?)</body>~is',$raw,$b))throw new RuntimeException('Missing HTML head or body: '.$file);
 $head=rebase_assets($h[1],$directory);$body=rebase_assets($b[1],$directory);
 if($directory==='NSBM_NewsDesk dasboard'){$head=preg_replace('~(href=["\'][^"\']*/style\.css)(?:\?[^"\']*)?(["\'])~i','$1?v=20261109-gap-fix$2',$head,1);$body=preg_replace('~(src=["\'][^"\']*/script\.js)(?:\?[^"\']*)?(["\'])~i','$1?v=20261107-profile-sync$2',$body,1);}
 if($directory==='NSBM-SubmitArticle-Redesign')$head=preg_replace('~(href=["\'][^"\']*/SubmitArticle\.css)(?:\?[^"\']*)?(["\'])~i','$1?v=20261109-search-fix$2',$head,1);
 if($directory==='NSBM-NewsDesk-Admin-dashboard')$body=preg_replace('~(src=["\'][^"\']*/assets/js/script\.js)(?:\?[^"\']*)?(["\'])~i','$1?v=20261105-category-groups$2',$body,1);
 if($directory==='news'){
   // The original archive used a separate hash router and a different set of static stories.
   // Feed now comes from MySQL, so keep its design but do not run the old router.
   $body=preg_replace('~<script\b[^>]*>[\s\S]*?</script>~i','',$body);
   $body=preg_replace('~<header\b[^>]*style="background:#102b4e[^>]*>[\s\S]*?</header>~i','',$body,1);
   $body=preg_replace('~<div class="page article-page"[\s\S]*$~i','',$body);
   $body=preg_replace('~<nav class="pagination"[\s\S]*?</nav>~i','',$body);
   $body=preg_replace('~Showing <strong id="shown-count">\d+</strong> of <strong>\d+</strong> articles~','Showing <strong id="shown-count">0</strong> articles',$body);
 }
 if($directory==='home'){
     // Remove original dummy news cards before the browser renders the page.
// Keep the first card as a template for feed.js.
$body=preg_replace_callback(
 '~(<div\s+class="card-grid"[^>]*>)([\s\S]*?)(</div>)~i',
 function($m){
   if(preg_match('~<article\b[^>]*class="[^"]*\bcard\b[^"]*"[^>]*>[\s\S]*?</article>~i',$m[2],$card)){
     return $m[1].$card[0].$m[3];
   }
   return $m[0];
 },
 $body,
 1
);
  // Preserve original styling; replace standalone demo scripts with the shared live feed.
  $body=preg_replace('~<script\b[^>]*>[\s\S]*?</script>~i','',$body);
  $body=preg_replace('~href=([\"\'])\.\./news/index\.html\1~i','href="'.e(url_for($role==='guest'?'news.php':'student-news.php')).'"',$body);
  $body=preg_replace('~href=([\"\'])\.\./article\.html\1~i','href="'.e(url_for('news.php')).'"',$body);
  if($role==='guest'){
   $body=preg_replace('~\s*<!-- Published Announcements Panel -->[\s\S]*?(?=<!-- Email Newsletter Subscribe Card -->)~','',$body,1);
  }
  // The original notice links were placeholders. Link them to the relevant published news categories instead.
  $body=preg_replace('~<a href="#" class="notice-action">Download PDF</a>~i','<a href="'.e(url_for('news.php?category=notices')).'" class="notice-action">View Notice</a>',$body);
  $body=preg_replace('~<a href="#" class="notice-action">View Details</a>~i','<a href="'.e(url_for('news.php?category=campus-life')).'" class="notice-action">View Details</a>',$body);
  $body=preg_replace('~<a href="#" class="notice-action">RSVP Now</a>~i','<a href="'.e(url_for('news.php?category=events')).'" class="notice-action">View Events</a>',$body);
 }
 if($directory==='NSBM-Bookmark')$body=preg_replace('~<script\b[^>]*>[\s\S]*?</script>~i','',$body);
 if($directory==='submissions'){
  $body=preg_replace('~<script\b[^>]*>[\s\S]*?</script>~i','',$body);
  $body=preg_replace('~<header\b[^>]*style="background:#102b4e[^>]*>[\s\S]*?</header>~i','',$body,1);
  $body=str_replace('href="SubmitArticle.html"','href="'.e(url_for('submit-article.php')).'"',$body);
  $body=str_replace('Submissions are populated by main.js from browser storage.','Your submissions are loaded from your NSBM NewsDesk account.',$body);
 }
 if($directory==='NSBM-SubmitArticle-Redesign'){
  $body=preg_replace('~<script\b[^>]*>[\s\S]*?</script>~i','',$body);
 }
 if($directory==='home' && $role!=='guest'){
  $body=str_replace('href="article-details.html"','href="'.e(url_for('student-news.php')).'"',$body);
  $body=str_replace('href="submit-article.html"','href="'.e(url_for('submit-article.php')).'"',$body);
  $body=preg_replace_callback('~href=([\"\'])category\.php\?cat=([a-z-]+)\1~i',static function($m){$legacy=['campus-headlines'=>'campus-life','clubs'=>'clubs-societies'];$slug=$legacy[$m[2]]??newsdesk_category_group($m[2]);return 'href="'.e(url_for('student-news.php?category='.$slug)).'"';},$body);
 }
 $base=app_base();echo '<!doctype html><html lang="en"><head>'.$head;
 if($directory==='home')echo '<link rel="stylesheet" href="'.e(url_for('app/preloader.css?v=3')).'">';
 foreach(['Header footer/header.css?v=20261107-notifications-2','Header footer/footer.css?v=20261109-footer-green','app/bridge.css?v=20261114-expand','app/avatar-editor.css','app/icons.css?v=1'] as $path)echo '<link rel="stylesheet" href="'.e(url_for($path)).'">';
 echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">';
 echo '</head><body class="newsdesk-integrated" data-page-type="'.e($directory).'" data-role="'.e($role).'" data-base="'.e($base).'">';
 if($directory==='home')echo nsbm_preloader_markup($role);
 echo nav_markup('header',$role);echo '<div class="newsdesk-page">'.$body.'</div>';
 if(!($role==='admin' && in_array($directory,['NSBM-NewsDesk-Admin-dashboard','news'],true)))echo nav_markup('footer',$role);
 if($directory==='home')echo '<script src="'.e(url_for('app/preloader.js?v=2')).'" defer></script>';
 foreach(['app/bridge.js?v=20261105-category-groups','app/avatar-editor.js','app/breaking.js?v=20261112-5','app/feed.js?v=20261114-expand'] as $js)echo '<script src="'.e(url_for($js)).'" defer></script>';
 echo '<script src="'.e(url_for('app/search-suggestions.js?v=20261107-search-suggest')).'" defer></script>';
 if($role==='student')echo '<script src="'.e(url_for('app/notifications.js?v=20261107-subscribed-news')).'" defer></script>';
 if($role==='student' && $directory==='NSBM_NewsDesk dasboard')echo '<script src="'.e(url_for('app/student-live.js?v=20261111-live')).'" defer></script>';
 if($role==='admin' && $directory==='NSBM-NewsDesk-Admin-dashboard')echo '<script src="'.e(url_for('app/admin-live.js?v=20261105-category-groups')).'" defer></script>';
 if($role==='student' && $directory==='NSBM-Bookmark')echo '<script src="'.e(url_for('NSBM-Bookmark/script.js?v=20261112-fix')).'" defer></script>';
 if($role==='student' && $directory==='submissions')echo '<script src="'.e(url_for('submissions/main.js?v=20261107-full-editor-edit')).'" defer></script>';
 if($role==='student' && $directory==='NSBM-SubmitArticle-Redesign')echo '<script src="'.e(url_for('NSBM-SubmitArticle-Redesign/SubmitArticle.js?v=20261107-full-editor-edit')).'" defer></script>';
 echo '</body></html>';
}
