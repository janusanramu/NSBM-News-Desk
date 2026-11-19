-- Import into your existing newsdesk database. This does NOT replace your users table.
CREATE DATABASE IF NOT EXISTS newsdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE newsdesk;
-- Existing project users table must have: id, name, email, password (password_hash).
CREATE TABLE IF NOT EXISTS user_settings (
 user_id INT NOT NULL PRIMARY KEY,
 student_id VARCHAR(40) NOT NULL DEFAULT '',
 faculty VARCHAR(150) NOT NULL DEFAULT '',
 degree VARCHAR(180) NOT NULL DEFAULT '',
 batch_year SMALLINT UNSIGNED NULL,
 photo_path VARCHAR(255) NULL,
 news_notifications TINYINT(1) NOT NULL DEFAULT 1,
 submission_notifications TINYINT(1) NOT NULL DEFAULT 1,
 comment_notifications TINYINT(1) NOT NULL DEFAULT 0,
 email_notifications TINYINT(1) NOT NULL DEFAULT 1,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_user_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS user_reading_preferences (
 user_id INT NOT NULL, topic VARCHAR(60) NOT NULL,
 PRIMARY KEY(user_id,topic),
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS news_categories (
 id INT AUTO_INCREMENT PRIMARY KEY,
 slug VARCHAR(60) NOT NULL UNIQUE,
 name VARCHAR(120) NOT NULL,
 description VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB;
INSERT IGNORE INTO news_categories(slug,name,description) VALUES
('campus','Campus News','Campus updates and student life'),
('events','Events','University events and activities'),
('academic','Academic News','Academic announcements and updates'),
('sports','Sports','Sports events and achievements'),
('technology','Technology','Technology and innovation news'),
('clubs','Clubs & Societies','Student clubs and society activities');
CREATE TABLE IF NOT EXISTS user_news_subscriptions (
 user_id INT NOT NULL, category_id INT NOT NULL, notifications TINYINT(1) NOT NULL DEFAULT 1,
 PRIMARY KEY(user_id,category_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(category_id) REFERENCES news_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;
