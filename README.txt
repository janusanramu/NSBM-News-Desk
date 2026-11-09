NSBM NewsDesk — 
REQUIRED existing users table columns: id (INT primary key), name, email, password (password_hash string).
If your users table uses other names/types, adapt api.php and the foreign keys in database.sql.
The sample news_categories table is independent from any existing categories table. Map it to your actual
news category IDs when integrating the whole project to avoid duplicate category sources.

Working backend: authenticated profile updates, password_verify/password_hash, reading preferences,
notification preference storage, photo upload/removal, category subscribe/unsubscribe and notification toggles.
Not included: email/in-app notification delivery, other dashboard tabs, user registration or login page.
For production: use HTTPS, restrict uploads at web-server level, review email change verification,
implement login rate limiting and configure your actual routing and existing categories schema.
