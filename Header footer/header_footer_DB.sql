-- NSBM NewsDesk database
-- Import this file with phpMyAdmin or: mysql -u root -p < database.sql
-- Requires MySQL 8.0+ (or a compatible MariaDB release).

CREATE DATABASE IF NOT EXISTS nsbm_newsdesk
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nsbm_newsdesk;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student', 'editor', 'admin') NOT NULL DEFAULT 'student',
  faculty VARCHAR(120) NULL,
  student_id VARCHAR(50) NULL,
  account_status ENUM('pending', 'active', 'suspended') NOT NULL DEFAULT 'pending',
  email_verified_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_student_id (student_id),
  KEY idx_users_role_status (role, account_status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  description VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name),
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_active (is_active, name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS articles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  author_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  title VARCHAR(240) NOT NULL,
  slug VARCHAR(260) NOT NULL,
  summary VARCHAR(600) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  cover_image_url VARCHAR(1000) NULL,
  image_alt VARCHAR(255) NULL,
  status ENUM('draft', 'pending_review', 'approved', 'rejected', 'archived')
    NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  reading_time_minutes SMALLINT UNSIGNED NULL,
  view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  rejection_reason TEXT NULL,
  submitted_at DATETIME NULL,
  reviewed_at DATETIME NULL,
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_articles_slug (slug),
  KEY idx_articles_status_published (status, published_at),
  KEY idx_articles_category_status (category_id, status, published_at),
  KEY idx_articles_author_status (author_id, status, created_at),
  KEY idx_articles_featured (is_featured, status, published_at),
  CONSTRAINT fk_articles_author
    FOREIGN KEY (author_id) REFERENCES users (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_articles_category
    FOREIGN KEY (category_id) REFERENCES categories (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_articles_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS comments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  article_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  body TEXT NOT NULL,
  status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comments_article_status_created (article_id, status, created_at),
  KEY idx_comments_user_created (user_id, created_at),
  KEY idx_comments_parent (parent_id),
  CONSTRAINT fk_comments_article
    FOREIGN KEY (article_id) REFERENCES articles (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comments_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comments_parent
    FOREIGN KEY (parent_id) REFERENCES comments (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookmarks (
  user_id BIGINT UNSIGNED NOT NULL,
  article_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, article_id),
  KEY idx_bookmarks_article (article_id),
  CONSTRAINT fk_bookmarks_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bookmarks_article
    FOREIGN KEY (article_id) REFERENCES articles (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  author_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(240) NOT NULL,
  body TEXT NOT NULL,
  category ENUM('academic', 'administration', 'clubs', 'campus', 'other')
    NOT NULL DEFAULT 'other',
  priority ENUM('normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  attachment_url VARCHAR(1000) NULL,
  action_label VARCHAR(80) NULL,
  action_url VARCHAR(1000) NULL,
  status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_announcements_status_dates (status, published_at, expires_at),
  KEY idx_announcements_priority (priority, published_at),
  CONSTRAINT fk_announcements_author
    FOREIGN KEY (author_id) REFERENCES users (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feed_subscriptions (
  user_id BIGINT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_feed_subscriptions_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(254) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  unsubscribed_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_newsletter_email (email),
  KEY idx_newsletter_active (is_active, subscribed_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS article_views (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  article_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_article_views_article_time (article_id, viewed_at),
  KEY idx_article_views_user_time (user_id, viewed_at),
  CONSTRAINT fk_article_views_article
    FOREIGN KEY (article_id) REFERENCES articles (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_article_views_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (name, slug, description) VALUES
  ('Campus Life', 'campus-life', 'Campus events, student life, and university updates.'),
  ('Academics', 'academics', 'Academic news, examinations, and faculty updates.'),
  ('Research', 'research', 'Research, innovation, and new discoveries.'),
  ('Clubs & Societies', 'clubs-societies', 'Student clubs, societies, and their events.'),
  ('Sports', 'sports', 'University teams, tournaments, and sports events.'),
  ('Computing', 'computing', 'News from computing and information technology.'),
  ('Business', 'business', 'Business, entrepreneurship, and management news.'),
  ('Engineering', 'engineering', 'Engineering projects, research, and faculty news.'),
  ('Student Clubs', 'student-clubs', 'Student-led club and society news.');
