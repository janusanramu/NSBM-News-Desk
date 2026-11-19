CREATE DATABASE IF NOT EXISTS nsbm_newsdesk
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nsbm_newsdesk;

CREATE TABLE IF NOT EXISTS users (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  student_id VARCHAR(50) NULL,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  faculty VARCHAR(80) NULL,
  degree VARCHAR(120) NULL,
  intake VARCHAR(4) NULL,
  terms_accepted_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_student_id (student_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_password_reset_token_hash (token_hash),
  KEY idx_password_reset_user (user_id),
  KEY idx_password_reset_expiry (expires_at),
  CONSTRAINT fk_password_reset_user
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  description VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name),
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_active (is_active, name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS articles (
  id INT NOT NULL AUTO_INCREMENT,
  author_id INT NULL,
  reviewed_by INT NULL,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(240) NOT NULL,
  slug VARCHAR(260) NOT NULL,
  summary VARCHAR(600) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  cover_image_url VARCHAR(1000) NULL,
  content_image_url VARCHAR(1000) NULL,
  image_alt VARCHAR(255) NULL,
  status ENUM('draft', 'pending_review', 'published', 'rejected', 'archived')
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
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_articles_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_articles_category
    FOREIGN KEY (category_id) REFERENCES categories (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookmarks (
  user_id INT NOT NULL,
  article_id INT NOT NULL,
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

CREATE TABLE IF NOT EXISTS comments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  article_id INT NOT NULL,
  user_id INT NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  body TEXT NOT NULL,
  status ENUM('pending', 'approved', 'removed') NOT NULL DEFAULT 'pending',
  reviewed_by INT NULL,
  reviewed_at DATETIME NULL,
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
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comments_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reports (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reporter_user_id INT NULL,
  reporter_email VARCHAR(254) NULL,
  article_id INT NULL,
  comment_id BIGINT UNSIGNED NULL,
  reason VARCHAR(120) NOT NULL,
  details TEXT NULL,
  status ENUM('open', 'reviewing', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
  reviewed_by INT NULL,
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reports_status_created (status, created_at),
  KEY idx_reports_article (article_id),
  KEY idx_reports_comment (comment_id),
  KEY idx_reports_reporter (reporter_user_id, created_at),
  CONSTRAINT fk_reports_reporter
    FOREIGN KEY (reporter_user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_reports_article
    FOREIGN KEY (article_id) REFERENCES articles (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_reports_comment
    FOREIGN KEY (comment_id) REFERENCES comments (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_reports_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  author_id INT NOT NULL,
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

CREATE TABLE IF NOT EXISTS admin_settings (
  user_id INT NOT NULL,
  contact_number VARCHAR(30) NULL,
  profile_photo_url VARCHAR(1000) NULL,
  theme ENUM('light', 'dark', 'system') NOT NULL DEFAULT 'dark',
  compact_tables TINYINT(1) NOT NULL DEFAULT 0,
  notify_email TINYINT(1) NOT NULL DEFAULT 1,
  notify_articles TINYINT(1) NOT NULL DEFAULT 1,
  notify_comments TINYINT(1) NOT NULL DEFAULT 1,
  notify_announcements TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_admin_settings_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS article_views (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  article_id INT NOT NULL,
  user_id INT NULL,
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

INSERT IGNORE INTO categories (name, slug, description) VALUES
  ('Campus Life', 'campus-life', 'Campus events, student life, and university updates.'),
  ('Events', 'events', 'University events, activities, and campus celebrations.'),
  ('Notices', 'notices', 'Official notices and important university announcements.'),
  ('Academics', 'academics', 'Academic news, examinations, and faculty updates.'),
  ('Research', 'research', 'Research, innovation, and new discoveries.'),
  ('Clubs & Societies', 'clubs-societies', 'Student clubs, societies, and their events.'),
  ('Sports', 'sports', 'University teams, tournaments, and sports events.'),
  ('Computing', 'computing', 'News from computing and information technology.'),
  ('Business', 'business', 'Business, entrepreneurship, and management news.'),
  ('Engineering', 'engineering', 'Engineering projects, research, and faculty news.');


-- Shared cross-device category subscriptions
CREATE TABLE IF NOT EXISTS category_subscriptions (
  user_id INT NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  notifications TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(user_id,category_id),
  CONSTRAINT fk_category_subs_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_category_subs_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_notification_reads (
  user_id INT NOT NULL,
  article_id INT NOT NULL,
  read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, article_id),
  CONSTRAINT fk_notification_reads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notification_reads_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Repair step: adds any column an older copy of the database is missing.
-- (MariaDB / XAMPP: ADD COLUMN IF NOT EXISTS skips columns that already exist.)
-- ---------------------------------------------------------------
ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(100) NOT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS student_id VARCHAR(50) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(150) NOT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('student', 'admin') NOT NULL DEFAULT 'student';
ALTER TABLE users ADD COLUMN IF NOT EXISTS faculty VARCHAR(80) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS degree VARCHAR(120) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS intake VARCHAR(4) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS terms_accepted_at DATETIME NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE password_reset_tokens ADD COLUMN IF NOT EXISTS user_id INT NOT NULL;
ALTER TABLE password_reset_tokens ADD COLUMN IF NOT EXISTS token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL;
ALTER TABLE password_reset_tokens ADD COLUMN IF NOT EXISTS expires_at DATETIME NOT NULL;
ALTER TABLE password_reset_tokens ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS name VARCHAR(100) NOT NULL;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS slug VARCHAR(120) NOT NULL;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS description VARCHAR(500) NULL;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS author_id INT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS reviewed_by INT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS category_id INT UNSIGNED NOT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS title VARCHAR(240) NOT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS slug VARCHAR(260) NOT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS summary VARCHAR(600) NOT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS body MEDIUMTEXT NOT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS cover_image_url VARCHAR(1000) NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS content_image_url VARCHAR(1000) NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS image_alt VARCHAR(255) NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS status ENUM('draft', 'pending_review', 'published', 'rejected', 'archived') NOT NULL DEFAULT 'draft';
ALTER TABLE articles ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS reading_time_minutes SMALLINT UNSIGNED NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS view_count BIGINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS submitted_at DATETIME NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS published_at DATETIME NULL;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE bookmarks ADD COLUMN IF NOT EXISTS user_id INT NOT NULL;
ALTER TABLE bookmarks ADD COLUMN IF NOT EXISTS article_id INT NOT NULL;
ALTER TABLE bookmarks ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS article_id INT NOT NULL;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS user_id INT NOT NULL;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS parent_id BIGINT UNSIGNED NULL;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS body TEXT NOT NULL;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'removed') NOT NULL DEFAULT 'pending';
ALTER TABLE comments ADD COLUMN IF NOT EXISTS reviewed_by INT NULL;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS reporter_user_id INT NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS reporter_email VARCHAR(254) NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS article_id INT NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS comment_id BIGINT UNSIGNED NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS reason VARCHAR(120) NOT NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS details TEXT NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS status ENUM('open', 'reviewing', 'resolved', 'dismissed') NOT NULL DEFAULT 'open';
ALTER TABLE reports ADD COLUMN IF NOT EXISTS reviewed_by INT NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS author_id INT NOT NULL;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS title VARCHAR(240) NOT NULL;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS body TEXT NOT NULL;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS category ENUM('academic', 'administration', 'clubs', 'campus', 'other') NOT NULL DEFAULT 'other';
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS priority ENUM('normal', 'high', 'urgent') NOT NULL DEFAULT 'normal';
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS attachment_url VARCHAR(1000) NULL;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS action_label VARCHAR(80) NULL;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS action_url VARCHAR(1000) NULL;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft';
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS published_at DATETIME NULL;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS user_id INT NOT NULL;
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS contact_number VARCHAR(30) NULL;
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS profile_photo_url VARCHAR(1000) NULL;
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS theme ENUM('light', 'dark', 'system') NOT NULL DEFAULT 'dark';
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS compact_tables TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS notify_email TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS notify_articles TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS notify_comments TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS notify_announcements TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE admin_settings ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE article_views ADD COLUMN IF NOT EXISTS article_id INT NOT NULL;
ALTER TABLE article_views ADD COLUMN IF NOT EXISTS user_id INT NULL;
ALTER TABLE article_views ADD COLUMN IF NOT EXISTS viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS email VARCHAR(254) NOT NULL;
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE newsletter_subscribers ADD COLUMN IF NOT EXISTS unsubscribed_at DATETIME NULL;
ALTER TABLE category_subscriptions ADD COLUMN IF NOT EXISTS user_id INT NOT NULL;
ALTER TABLE category_subscriptions ADD COLUMN IF NOT EXISTS category_id INT UNSIGNED NOT NULL;
ALTER TABLE category_subscriptions ADD COLUMN IF NOT EXISTS notifications TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE category_subscriptions ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE user_notification_reads ADD COLUMN IF NOT EXISTS user_id INT NOT NULL;
ALTER TABLE user_notification_reads ADD COLUMN IF NOT EXISTS article_id INT NOT NULL;
ALTER TABLE user_notification_reads ADD COLUMN IF NOT EXISTS read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- The original archive's published stories populate both Home and News.
-- Reimporting is safe: fixed unique slugs prevent duplicated stories.
INSERT IGNORE INTO articles (category_id,title,slug,summary,body,cover_image_url,status,published_at,submitted_at,reading_time_minutes) SELECT id,'NSBM Computing Students Develop Digital Platform for Recreation Center Management','nsbm-original-story-1','Three talented undergraduates from our Faculty of Computing took the initiative to design, develop, and deploy the NSBM Recreation Center Management Platform.','Three talented undergraduates from our Faculty of Computing took the initiative to design, develop, and deploy the NSBM Recreation Center Management Platform.','news/assets/images/nsbm-computing-students-recreation-center-platform-3.jpg','published','2026-10-24 12:00:00','2026-10-24 12:00:00',3 FROM categories WHERE name='Computing';
INSERT IGNORE INTO articles (category_id,title,slug,summary,body,cover_image_url,status,published_at,submitted_at,reading_time_minutes) SELECT id,'NSBM 6th Student Research Symposium in Marketing, Tourism and Multimedia','nsbm-original-story-2','NSBM Green University successfully hosted the 6th Student Research Symposium in Marketing, Tourism and Multimedia (SRSMTM 2026) on 8 October 2026','NSBM Green University successfully hosted the 6th Student Research Symposium in Marketing, Tourism and Multimedia (SRSMTM 2026) on 8 October 2026','news/assets/images/nsbm-research-symposium-marketing-tourism-multimedia-7-1200x600.jpg','published','2026-10-23 12:00:00','2026-10-23 12:00:00',3 FROM categories WHERE name='Business';
INSERT IGNORE INTO articles (category_id,title,slug,summary,body,cover_image_url,status,published_at,submitted_at,reading_time_minutes) SELECT id,'Opens Rs. 720 Million Civil Engineering Laboratory Complex','nsbm-original-story-3','NSBM Green University ceremonially opened its state-of-the-art Civil Engineering Laboratory Complex','NSBM Green University ceremonially opened its state-of-the-art Civil Engineering Laboratory Complex','news/assets/images/nsbm-civil-engineering-laboratory-complex-720-million-2.jpg','published','2026-10-22 12:00:00','2026-10-22 12:00:00',3 FROM categories WHERE name='Engineering';
INSERT IGNORE INTO articles (category_id,title,slug,summary,body,cover_image_url,status,published_at,submitted_at,reading_time_minutes) SELECT id,'NSBM Green University Celebrates 10 Years of Excellence and National Impact','nsbm-original-story-4','NSBM Green University unveiled the architectural model of its proposed multibillion-rupee Medical and Research Hospital on 26 October 2026.','NSBM Green University unveiled the architectural model of its proposed multibillion-rupee Medical and Research Hospital on 26 October 2026.','news/assets/images/nsbm-green-university-10-years-excellence-national-impact-5-1037x600.jpg','published','2026-10-21 12:00:00','2026-10-21 12:00:00',3 FROM categories WHERE name='Events';
INSERT IGNORE INTO articles (category_id,title,slug,summary,body,cover_image_url,status,published_at,submitted_at,reading_time_minutes) SELECT id,'NSBM Cricket Team Crowned Champions Again at KDU Inter-University T20 Championship 2026','nsbm-original-story-5','NSBM Green University has once again made its mark in university cricket, with the NSBM Cricket Team securing the KDU Leaders’ Trophy Inter-University T20 Cricket Championship 2026','NSBM Green University has once again made its mark in university cricket, with the NSBM Cricket Team securing the KDU Leaders’ Trophy Inter-University T20 Cricket Championship 2026','news/assets/images/nsbm-cricket-kdu-t20-champions-2026-1200x600.jpg','published','2026-10-20 12:00:00','2026-10-20 12:00:00',3 FROM categories WHERE name='Sports';
INSERT IGNORE INTO articles (category_id,title,slug,summary,body,cover_image_url,status,published_at,submitted_at,reading_time_minutes) SELECT id,'Batch End Semester Examination Timetable Released for All Faculties','nsbm-original-story-6','The end semester examination timetable has been released for all faculties at NSBM Green University. Please check the official website for the detailed schedule.','The end semester examination timetable has been released for all faculties at NSBM Green University. Please check the official website for the detailed schedule.','news/assets/images/NSBM-Reopen-11.jpg','published','2026-10-19 12:00:00','2026-10-19 12:00:00',3 FROM categories WHERE name='Notices';
