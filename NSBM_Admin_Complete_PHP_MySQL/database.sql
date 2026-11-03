-- Run in phpMyAdmin after backing up your existing newsdesk database.
-- Assumes your existing users table has id, name, email, password, role.
-- No existing users or articles are deleted or modified.
USE newsdesk;
CREATE TABLE IF NOT EXISTS admin_profile_settings (
  user_id INT NOT NULL PRIMARY KEY,
  contact_number VARCHAR(30) NOT NULL DEFAULT '',
  photo_path VARCHAR(255) DEFAULT NULL,
  interface_theme ENUM('light','dark','system') NOT NULL DEFAULT 'light',
  compact_tables TINYINT(1) NOT NULL DEFAULT 0,
  notify_email TINYINT(1) NOT NULL DEFAULT 1,
  notify_articles TINYINT(1) NOT NULL DEFAULT 1,
  notify_comments TINYINT(1) NOT NULL DEFAULT 1,
  notify_announcements TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_admin_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
