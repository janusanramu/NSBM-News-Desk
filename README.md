# NSBM NewsDesk

## Run with PHP and MySQL (XAMPP)

1. Copy the project folder into XAMPP's `htdocs` directory and start Apache and MySQL.
2. Open phpMyAdmin, select **Import**, and import `database.sql`. It creates the `nsbm_newsdesk` database and seeds the six article slugs used by the site.
3. If your MySQL credentials differ from the XAMPP defaults, set `NSBM_DB_HOST`, `NSBM_DB_NAME`, `NSBM_DB_USER`, and `NSBM_DB_PASS` in Apache's environment. The defaults are `127.0.0.1`, `nsbm_newsdesk`, `root`, and an empty password.
4. Open the page through Apache, for example `http://localhost/trial/index.html`. Do not open the HTML file directly from the filesystem; the comment API requires PHP.

The PHP API in `api/comments.php` loads and stores comments in MySQL. The `articles` table validates article slugs, and comments are linked to articles with a foreign key. PHP needs the PDO MySQL extension enabled.