<?php
/** Read-only XAMPP / NSBM NewsDesk database check. Run from Windows CMD, never through a public web URL. */
declare(strict_types=1);
if(PHP_SAPI !== 'cli'){http_response_code(403);exit('Command line only.');}
require_once __DIR__.'/../NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';
echo "NSBM NewsDesk installation check\n";
echo "PHP: ".PHP_VERSION."\n";
echo "PDO MySQL: ".(in_array('mysql',PDO::getAvailableDrivers(),true)?'available':'MISSING')."\n";
try{
 $db=database();
 echo "Database: connected\n";
 foreach(['users','categories','articles','bookmarks','category_subscriptions','announcements'] as $table){
  $count=$db->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
  echo $table.': '.(int)$count." rows\n";
 }
 echo "Finished. Your existing data was not changed.\n";
}catch(Throwable $e){fwrite(STDERR,"Database or schema check failed: ".$e->getMessage()."\n");exit(1);}
