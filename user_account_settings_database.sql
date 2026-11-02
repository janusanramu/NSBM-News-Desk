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

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `student_id` VARCHAR(50) DEFAULT NULL AFTER `email`,
    ADD COLUMN IF NOT EXISTS `faculty` VARCHAR(120) DEFAULT NULL AFTER `student_id`,
    ADD COLUMN IF NOT EXISTS `degree_program` VARCHAR(190) DEFAULT NULL AFTER `faculty`,
    ADD COLUMN IF NOT EXISTS `batch_year` SMALLINT UNSIGNED DEFAULT NULL AFTER `degree_program`;

CREATE TABLE IF NOT EXISTS `user_news_preferences` (
    `user_id` INT NOT NULL,
    `category` VARCHAR(32) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `category`),
    CONSTRAINT `fk_user_news_preferences_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
    `user_id` INT NOT NULL,
    `news_notifications` TINYINT(1) NOT NULL DEFAULT 1,
    `submission_notifications` TINYINT(1) NOT NULL DEFAULT 1,
    `comment_notifications` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_user_notification_preferences_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
