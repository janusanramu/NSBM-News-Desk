NSBM NewsDesk — PHP/MySQL dashboard integration

1. Copy this folder into C:\xampp\htdocs\NSBM_NewsDesk_PHP_MySQL\
2. Start Apache and MySQL in XAMPP.
3. In phpMyAdmin select your EXISTING newsdesk database, then import database.sql.
   IMPORTANT: Back up your existing database first. The SQL adds tables; it does not replace users.
4. Update includes/config.php to match your MySQL credentials.
5. Your EXISTING login.php must set $_SESSION['user_id'] to the authenticated users.id after password_verify().
   If your login uses a different session key, update require_login() in includes/bootstrap.php.
6. Open http://localhost/NSBM_NewsDesk_PHP_MySQL/index.php AFTER logging in.
   If your login page is elsewhere, update the redirect in includes/bootstrap.php.
7. For Apache on Windows, allow PHP write access to uploads/.

REQUIRED existing users table columns: id (INT primary key), name, email, password (password_hash string).
If your users table uses other names/types, adapt api.php and the foreign keys in database.sql.
The sample news_categories table is independent from any existing categories table. Map it to your actual
news category IDs when integrating the whole project to avoid duplicate category sources.

Working backend: authenticated profile updates, password_verify/password_hash, reading preferences,
notification preference storage, photo upload/removal, category subscribe/unsubscribe and notification toggles.
Not included: email/in-app notification delivery, other dashboard tabs, user registration or login page.
For production: use HTTPS, restrict uploads at web-server level, review email change verification,
implement login rate limiting and configure your actual routing and existing categories schema.
