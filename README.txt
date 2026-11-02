NSBM NewsDesk — Authentication pages and backend

Pages: login.php, register.php, forgot-password.php, reset-password.php, terms.html, guidelines.html
Theme: #064E3B, #3BB54A, #FFFFFF
The legacy login.html, register.html, and forgot-password.html paths redirect to their PHP pages.

Login/Register retain the supplied transparent WebM logo video and their existing hover movement.
Terms/Guidelines/Forgot Password use CSS animated icons, rings and glows, plus a lightweight mouse-follow JS. No video on these three pages.

The authentication pages require PHP 8+ with PDO MySQL and a MySQL/MariaDB database.
Import database.sql with `mysql -u root -p < database.sql`, then configure these environment variables
for the PHP process:
  DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
  APP_BASE_URL (the public HTTPS URL of this folder, without a trailing slash;
                localhost HTTP is allowed for development)
  MAIL_FROM (verified sender address for password-reset email)

The web server must route .php files through PHP. PHP mail() must be configured for password reset.
Keep PHP files, includes/, database.sql, CSS, JS, and videos together in the website root. The site's
images/logo.png and index.html are expected to be supplied by the main site.

For local development in PowerShell, set the DB_* and APP_BASE_URL/MAIL_FROM environment variables,
then run `php -S localhost:8000` from this folder. Use `APP_BASE_URL=http://localhost:8000` locally.
The reset email cannot be tested unless mail() is configured. Login stores the signed-in user in the PHP
session and redirects to index.html.

Passwords are hashed, forms use CSRF tokens, and reset tokens are stored hashed and expire after one
hour. Public registration cannot set an administrator role. Assign role='admin' only through a trusted
database administrator tool. Do not put database credentials in public files.
