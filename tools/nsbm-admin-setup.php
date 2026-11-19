<?php
/** One-time local CLI account creation; never exposed as a web endpoint. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only.'); }
require_once __DIR__.'/../NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';
function question(string $label): string { echo $label;return trim((string)fgets(STDIN)); }
try {
 $db=database();
 $name=question('Administrator full name: ');
 $email=strtolower(question('NSBM email address: '));
 $password=question('Create a strong password (12+ characters): ');
 if(mb_strlen($name)<2||!is_nsbm_email($email)||strlen($password)<12||strlen($password)>72){fwrite(STDERR,"Invalid name, NSBM email, or password length.\n");exit(1);}
 $check=$db->prepare('SELECT id,role FROM users WHERE email=?');$check->execute([$email]);$existing=$check->fetch();
 if($existing){echo "This email already has an account. To promote it, use phpMyAdmin and verify account ownership first.\n";exit(1);}
 $q=$db->prepare("INSERT INTO users (name,email,password,role,faculty,terms_accepted_at) VALUES (?,?,?,'admin','Staff',NOW())");
 $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
 echo "Admin created (ID ".$db->lastInsertId()."). You can now log in using the email and password you chose.\n";
}catch(Throwable $e){fwrite(STDERR,"Could not create admin. Confirm app/local-config.php and import database.sql first.\n");exit(1);}
