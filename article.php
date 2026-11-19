<?php
/** Database-backed article detail, styled to match the team's original News article view. */
declare(strict_types=1);
require_once __DIR__.'/app/render.php';
function seeded_article_source(string $slug,array $article,string $news): ?array
{
 $sections=[
  'nsbm-original-story-1'=>'computing',
  'nsbm-original-story-2'=>'business',
  'nsbm-original-story-3'=>'engineering',
  'nsbm-original-story-4'=>'events',
  'nsbm-original-story-5'=>'sports',
  'nsbm-original-story-6'=>'notices',
 ];
 if(!isset($sections[$slug]))return null;
 $source=file_get_contents(__DIR__.'/news/before_login_news.html');
 if($source===false)throw new RuntimeException('Could not read the original article content.');
 $document=new DOMDocument();
 $previous=libxml_use_internal_errors(true);
 $loaded=$document->loadHTML('<?xml encoding="UTF-8">'.$source);
 libxml_clear_errors();
 libxml_use_internal_errors($previous);
 if(!$loaded)throw new RuntimeException('Could not parse the original article content.');
 $xpath=new DOMXPath($document);
 $section=$document->getElementById($sections[$slug]);
 if(!$section)throw new RuntimeException('Original article section was not found.');
 $bodyNodes=$xpath->query('.//article[contains(concat(" ",normalize-space(@class)," ")," article-body ")]',$section);
 $bioNodes=$xpath->query('.//section[contains(concat(" ",normalize-space(@class)," ")," author-bio ")]',$section);
 $heroNodes=$xpath->query('.//header[contains(concat(" ",normalize-space(@class)," ")," article-hero ")]',$section);
 $discussionNodes=$xpath->query('.//section[contains(concat(" ",normalize-space(@class)," ")," discussion ")]',$section);
 $body=$bodyNodes?$bodyNodes->item(0):null;
 $bio=$bioNodes?$bioNodes->item(0):null;
 $hero=$heroNodes?$heroNodes->item(0):null;
 $discussion=$discussionNodes?$discussionNodes->item(0):null;
 if(!$body||!$hero||!$discussion)throw new RuntimeException('Original article content was not found.');
 foreach($xpath->query('.//img[@src]',$section) as $image){
  $src=$image->getAttribute('src');
  if(preg_match('~^assets/images/([a-zA-Z0-9%._/-]+)$~',$src)){
   $image->setAttribute('src',url_for('news/'.$src));
  }
 }
 $heroStyle=$hero->getAttribute('style');
 $heroStyle=preg_replace_callback(
  '~url\((["\']?)assets/images/([a-zA-Z0-9%._/-]+)\1\)~',
  static fn(array $match):string=>'url('.$match[1].url_for('news/assets/images/'.$match[2]).$match[1].')',
  $heroStyle
 );
 if($heroStyle!==null)$hero->setAttribute('style',$heroStyle);
 $backLinks=$xpath->query('.//a[contains(concat(" ",normalize-space(@class)," ")," back-link ")]',$hero);
 if($backLinks&&$backLinks->length)$backLinks->item(0)->setAttribute('href',url_for($news));
 $titles=$xpath->query('.//h1',$hero);
 if($titles&&$titles->length)$titles->item(0)->textContent=(string)$article['title'];
 $meta=$xpath->query('.//span[contains(concat(" ",normalize-space(@class)," ")," article-meta ")]',$hero);
 if($meta&&$meta->length){
  $meta->item(0)->textContent='◷ '.substr((string)$article['published_at'],0,10).' • ◎ '.number_format((int)$article['view_count']+1).' views';
 }
 $innerHtml=static function(DOMNode $node)use($document):string{
  $html='';
  foreach($node->childNodes as $child)$html.=$document->saveHTML($child);
  return $html;
 };
 $outerHtml=static fn(DOMNode $node):string=>$document->saveHTML($node);
 return ['hero'=>$innerHtml($hero),'hero_style'=>$hero->getAttribute('style'),'body'=>$innerHtml($body),'bio'=>$bio?$outerHtml($bio):'','discussion'=>$outerHtml($discussion)];
}
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id||$id<1){http_response_code(404);exit('Article not found.');}
try{
 $db=database();
 $stmt=$db->prepare("SELECT a.id,a.title,a.slug,a.summary,a.body,a.cover_image_url,a.content_image_url,a.image_alt,a.published_at,a.view_count,c.name category,c.slug category_slug,u.name author,s.profile_photo_url author_photo_url FROM articles a JOIN categories c ON c.id=a.category_id LEFT JOIN users u ON u.id=a.author_id LEFT JOIN admin_settings s ON s.user_id=u.id WHERE a.id=? AND a.status='published'");
 $stmt->execute([$id]);$article=$stmt->fetch();
 if(!$article){http_response_code(404);exit('Article not found.');}
 $db->prepare('UPDATE articles SET view_count=view_count+1 WHERE id=?')->execute([$id]);
}catch(Throwable $ex){error_log('NewsDesk article detail: '.$ex->getMessage());http_response_code(503);exit('Campus news is temporarily unavailable.');}
$role=app_role();$news=$role==='student'?'student-news.php':($role==='admin'?'admin-news.php':'news.php');
$articleCategory=newsdesk_category_group((string)$article['category_slug']);
if($articleCategory==='academics'&&preg_match('/\\bresearch\\b/i',(string)$article['title']))$articleCategory='research';
$articleCategoryLabel=newsdesk_category_groups()[$articleCategory]['name']??(string)$article['category'];
$image=(string)($article['cover_image_url']??'');
if(!preg_match('~^(news/assets/images/|home/images/|uploads/covers/)[a-zA-Z0-9%._/-]+$~',$image))$image='home/images/nsbm-campus-aerial.jpg';
$contentImage=(string)($article['content_image_url']??'');
if(!preg_match('~^uploads/article-content/[a-f0-9]{24,32}\.(?:jpg|png|webp)$~',$contentImage))$contentImage='';
$author=(string)($article['author']?:'NSBM NewsDesk');$initials='';foreach(array_slice(preg_split('/\s+/',trim($author)),0,2) as $part)$initials.=strtoupper(substr($part,0,1));$authorPhoto=(string)($article['author_photo_url']??'');if(!preg_match('~^uploads/(?:profile|avatars)/[a-f0-9]{24,32}\\.(?:jpg|png|webp)$~',$authorPhoto))$authorPhoto='';
$paragraphs=preg_split('/\R{2,}/',trim((string)$article['body']))?:[];
$isSeededStory=preg_match('/^nsbm-original-story-[1-6]$/',(string)$article['slug'])===1;
$lead=$isSeededStory?(array_shift($paragraphs)?:$article['summary']):$article['summary'];
$sourceContent=$isSeededStory?seeded_article_source((string)$article['slug'],$article,$news):null;
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($article['title'])?> | NSBM NewsDesk</title>
<link rel="stylesheet" href="<?=e(url_for('Header footer/header.css?v=20261107-icons1-2'))?>">
<link rel="stylesheet" href="<?=e(url_for('Header footer/footer.css'))?>">
<link rel="stylesheet" href="<?=e(url_for('app/bridge.css'))?>">
<link rel="stylesheet" href="<?=e(url_for('app/avatar-editor.css'))?>">
<link rel="stylesheet" href="<?=e(url_for('app/icons.css?v=1'))?>">
<link rel="stylesheet" href="<?=e(url_for('app/original-news-article.css?v=20261107-content-image'))?>">
<link rel="stylesheet" href="<?=e(url_for('app/article.css?v=20261107-content-image'))?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></head>
<body class="newsdesk-integrated" data-base="<?=e(app_base())?>" data-role="<?=e($role)?>" data-page-type="article">

<?=nav_markup('header',$role)?>
<div class="newsdesk-page"><div class="page article-page" data-article-id="<?=e((string)$id)?>">
<?php if($sourceContent): ?><header class="article-hero" style="<?=e($sourceContent['hero_style'])?>"><?=$sourceContent['hero']?></header><?php else: ?><header class="article-hero" style="--hero-img:url('<?=e(url_for($image))?>')">
<a class="back-link" href="<?=e(url_for($news))?>">← Back to News Archive</a>
<div class="hero-inner">
<div class="article-labels"><a class="category" href="<?=e(url_for($news.'?category='.$articleCategory))?>"><?=e(strtoupper($articleCategoryLabel))?></a><span class="article-meta"><?=e(substr((string)$article['published_at'],0,10))?> &nbsp;•&nbsp; ◎ <?=number_format((int)$article['view_count']+1)?> views</span></div>
<h1><?=e($article['title'])?></h1>
<div class="hero-bottom"><div class="author"><span class="author-avatar" data-avatar-url="<?=e($authorPhoto)?>"><?=e($initials)?></span><span><span class="author-name"><?=e($author)?></span><span class="author-role">NSBM NewsDesk contributor</span></span></div></div>
</div></header><?php endif; ?>
<main class="article-column">
<article class="article-body"><?php if($sourceContent): ?><?=$sourceContent['body']?><?php else: ?><p class="lead"><?=e($lead)?></p>
<?php foreach($paragraphs as $paragraph): $paragraph=trim($paragraph); if($isSeededStory&&str_starts_with($paragraph,'## ')): ?><h2><?=e(substr($paragraph,3))?></h2><?php else: ?><p><?=nl2br(e($paragraph))?></p><?php endif; endforeach; ?>
<?php endif; ?>
<?php if($contentImage!==''): ?><figure class="article-content-image"><img src="<?=e(url_for($contentImage))?>" alt="Additional image for <?=e($article['title'])?>" loading="lazy"></figure><?php endif; ?>
<?php if($role==='student'):?><div class="article-controls"><button id="articleBookmark" class="article-action" type="button">♡ Save Bookmark</button><button id="articleReport" class="article-action" type="button">Report Article</button></div><?php endif; ?>
</article>
<?php if($sourceContent&&$sourceContent['bio']!==''): ?><?=$sourceContent['bio']?><?php else: ?>
<section class="author-bio"><span class="author-avatar" data-avatar-url="<?=e($authorPhoto)?>"><?=e($initials)?></span><div><h2>About the author</h2><p><?=e($author)?> · NSBM NewsDesk contributor</p></div></section>
<?php endif; ?>
<?php if($role==='guest'&&$sourceContent): ?><?=$sourceContent['discussion']?><p><a href="<?=e(url_for('NSBM-News-Desk-feature-login-register-page/login.php'))?>">Sign in</a> to join the discussion.</p><?php else: ?>
<section class="discussion article-comments" aria-labelledby="discussionTitle"><h2 id="discussionTitle" class="discussion-title">Campus Discussion</h2><div id="commentsList" class="comment-list" aria-live="polite">Loading comments…</div>
<?php if($role==='student'):?><form id="commentForm"><label for="commentBody">Add a comment</label><textarea id="commentBody" minlength="2" maxlength="3000" required placeholder="Share your thoughts respectfully"></textarea><button class="article-action article-action-primary" type="submit">Submit Comment</button><p id="commentFeedback" role="status"></p></form><?php else:?><p><a href="<?=e(url_for('NSBM-News-Desk-feature-login-register-page/login.php'))?>">Sign in</a> to join the discussion.</p><?php endif; ?></section>
<?php endif; ?>
</main></div></div>
<?=nav_markup('footer',$role)?>
<script src="<?=e(url_for('app/bridge.js'))?>" defer></script>
<script src="<?=e(url_for('app/avatar-editor.js'))?>" defer></script>
<script src="<?=e(url_for('app/breaking.js?v=1'))?>" defer></script>
<script src="<?=e(url_for('app/article.js?v=merged-1'))?>" defer></script>
</body></html>
