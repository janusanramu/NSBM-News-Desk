CREATE DATABASE IF NOT EXISTS `newsdesk_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `newsdesk_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `profile_picture` VARCHAR(255) DEFAULT NULL,
    `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dashboard_articles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `status` ENUM('draft', 'pending', 'published', 'rejected') NOT NULL DEFAULT 'pending',
    `views` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `published_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `dashboard_articles_user_status` (`user_id`, `status`),
    CONSTRAINT `fk_dashboard_articles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dashboard_bookmarks` (
    `user_id` INT NOT NULL,
    `article_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `article_id`),
    KEY `dashboard_bookmarks_article` (`article_id`),
    CONSTRAINT `fk_dashboard_bookmarks_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dashboard_bookmarks_article`
        FOREIGN KEY (`article_id`) REFERENCES `dashboard_articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dashboard_activity` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `activity_type` ENUM('published', 'bookmarked', 'profile_updated', 'submitted') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` VARCHAR(1000) NOT NULL DEFAULT '',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `dashboard_activity_user_created` (`user_id`, `created_at`),
    CONSTRAINT `fk_dashboard_activity_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dashboard_categories` (
    `slug` VARCHAR(64) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `description` VARCHAR(255) NOT NULL DEFAULT '',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`slug`),
    UNIQUE KEY `dashboard_categories_name_unique` (`name`)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

INSERT IGNORE INTO `dashboard_categories` (`slug`, `name`, `description`) VALUES
    ('campus', 'Campus News', 'Campus updates and student life'),
    ('events', 'Events', 'University events and activities'),
    ('academic', 'Academic News', 'Academic announcements and updates'),
    ('sports', 'Sports', 'Sports events and achievements'),
    ('technology', 'Technology', 'Technology and innovation news'),
    ('clubs', 'Clubs & Societies', 'Student clubs and society activities');

CREATE TABLE IF NOT EXISTS `user_category_subscriptions` (
    `user_id` INT NOT NULL,
    `category_slug` VARCHAR(64) NOT NULL,
    `notifications_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `category_slug`),
    KEY `user_category_subscriptions_category` (`category_slug`),
    CONSTRAINT `fk_user_category_subscriptions_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_category_subscriptions_category`
        FOREIGN KEY (`category_slug`) REFERENCES `dashboard_categories` (`slug`) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
