CREATE DATABASE IF NOT EXISTS nsbm_newsdesk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nsbm_newsdesk;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    contact_number VARCHAR(30) DEFAULT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'admin',
    password_hash VARCHAR(255) NOT NULL,
    last_login DATETIME DEFAULT NULL,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    theme ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(120) NOT NULL,
    description VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON DELETE CASCADE
);

-- Do not use a real password in this sample.
-- Create an administrator through a secure setup script using password_hash().
-- Example activity rows can be inserted after creating an admin:
-- INSERT INTO admin_activity_logs (admin_id, action, description)
-- VALUES (1, 'Article approved', 'Approved article: Campus News Update');
