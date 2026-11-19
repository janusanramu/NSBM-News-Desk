NSBM NewsDesk authentication and role routing

The PHP/MySQL backend is the authentication source for the connected frontend.
It provides registration, login, password reset, session creation, CSRF checks,
and password hashing. Registration creates member accounts; public forms cannot
create administrator accounts.

Requirements:
- PHP 8+ with PDO MySQL
- MySQL or MariaDB
- A web server that executes PHP

Local setup:
1. Import `database.sql` using phpMyAdmin's Import tab or the MySQL command line.
   The script creates the `newsdesk` database and its authentication, category,
   article, bookmark, comment, report, announcement, admin-settings, view, and
   newsletter tables. It can be imported again without replacing existing rows.
   If `newsdesk` already exists, the script adds missing tables but does not
   change the structure of existing tables. Article IDs and their related
   foreign-key columns use signed `INT` to match the existing `articles.id`.
2. In phpMyAdmin, select the `newsdesk` database and open its SQL tab. Add the
   two registration fields required by the PHP backend:
   ALTER TABLE users
     ADD COLUMN faculty VARCHAR(80) NULL,
     ADD COLUMN terms_accepted_at DATETIME NULL;
   This preserves existing rows. Skip this only if both columns already exist.
3. The password reset token table must reference the existing signed INT user id.
   It has already been created using `user_id INT NOT NULL`.
4. In a PowerShell window, configure the local connection. Replace the
   password placeholder with your own MySQL password; do not save it in project files:
   $env:DB_HOST = "127.0.0.1"
   $env:DB_PORT = "3306"
   $env:DB_NAME = "newsdesk"
   $env:DB_USER = "root"
   $env:DB_PASSWORD = "YOUR_LOCAL_MYSQL_PASSWORD"
   $env:APP_BASE_URL = "http://127.0.0.1:8000/NSBM-News-Desk-feature-login-register-page"
   $env:MAIL_FROM = "newsdesk@localhost"
5. Start the PHP server from the workspace root:
   php -S 127.0.0.1:8000 -t .
6. Open http://127.0.0.1:8000/NSBM-NewsDesk-Connected/home/index.html.

Sign-in and registration links in the connected frontend route to login.php and
register.php. A successful member login opens
NSBM-NewsDesk-Connected/home/registered.php. That page requires a member session.
An account with the database role "admin" opens
NSBM-NewsDesk-Admin-dashboard/index.php, which requires an admin session.
The .html admin URL redirects to the protected PHP page.

To grant administrator access, register the account first, then assign its role
through a trusted database administrator tool, for example:
  UPDATE users SET role = 'admin' WHERE email = 'admin-address@nsbm.ac.lk';
Use the exact email of the intended administrator. Never expose database
credentials or provide role selection in public registration.

Password reset tokens are stored hashed and expire after one hour. Reset email
delivery requires PHP mail() to be configured and APP_BASE_URL/MAIL_FROM to be
valid. The database schema includes tables for news, bookmarks, submissions,
comments, reports, announcements, and admin settings, but these features are
not yet connected to the pages; their displayed data remains frontend previews.
