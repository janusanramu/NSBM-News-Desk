NSBM NewsDesk - Combined Admin Dashboard + Account Settings Backend

1. Back up your existing newsdesk database.
2. Extract this folder to C:\xampp\htdocs\NSBM-NewsDesk\admin\
   (or merge files carefully into your existing admin folder).
3. Start Apache and MySQL in XAMPP.
4. Import database.sql into the EXISTING newsdesk database via phpMyAdmin.
   It creates only admin_profile_settings; it does not create or replace users.
5. Check credentials in config.php.
6. Ensure the login system sets $_SESSION['user_id'] and $_SESSION['role']='admin'.
   If your login session uses other names, update index.php and api_admin_settings.php.
7. Adjust the login redirect path in index.php for your folder structure.
8. Visit http://localhost/NSBM-NewsDesk/admin/index.php

The existing users table MUST have id, name, email, password, role fields.
Password hashes must be compatible with password_verify().
Do not open index.php with file://; use XAMPP/Apache.
The article/comment dashboard still uses its existing local demo data; only
administrator account settings are connected to PHP/MySQL here.
Notification preferences are stored; sending notifications is separate.
Protect the site with HTTPS when deploying publicly.

FILES
index.php - Dashboard and account settings page with admin login guard
style.css, script.js - Existing dashboard styles and behavior
admin-settings.css, admin-settings.js - Settings UI and PHP integration
api_admin_settings.php - Authenticated account settings API
config.php - PDO connection
database.sql - New settings table
uploads/ - Profile photo storage (PHP execution disabled via .htaccess)
