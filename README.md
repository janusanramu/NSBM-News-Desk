# NSBM-News-Desk

## My Submissions database setup

1. Start Apache and MySQL in XAMPP.
2. In phpMyAdmin, import `header_footer_DB.sql` to create the shared `nsbm_newsdesk` database and core tables.
3. Import `my_submissions_db.sql` separately. It creates the My Submissions view in that shared database; it does not create duplicate article or user data.
4. Copy `database.local.php.example` to `database.local.php` and enter your MySQL username and password. The local config is ignored by Git. Alternatively, set `NSBM_DB_HOST`, `NSBM_DB_NAME`, `NSBM_DB_USER`, and `NSBM_DB_PASSWORD` as environment variables.
5. Ensure the login page sets `$_SESSION['user_id']` to the matching `users.id`. The submissions page only displays articles whose `author_id` matches that session user.