CREATE DATABASE IF NOT EXISTS nsbm_newsdesk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nsbm_newsdesk;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    admin_id VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email),
    UNIQUE KEY uq_admins_admin_id (admin_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS articles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(160) NOT NULL,
    author VARCHAR(120) NOT NULL,
    category VARCHAR(80) NOT NULL,
    article_date DATE NOT NULL,
    status ENUM('Pending', 'Draft', 'Published', 'Rejected') NOT NULL DEFAULT 'Pending',
    description TEXT NOT NULL,
    rejection_reason TEXT NULL,
    author_batch VARCHAR(80) NULL,
    department VARCHAR(120) NULL,
    email VARCHAR(190) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_articles_status_date (status, article_date),
    INDEX idx_articles_category (category)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Pending', 'Draft', 'Published') NOT NULL DEFAULT 'Draft',
    announcement_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_announcements_status_date (status, announcement_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    article_id INT UNSIGNED NULL,
    author VARCHAR(120) NOT NULL,
    comment_text TEXT NOT NULL,
    status ENUM('pending', 'approved', 'removed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_comments_status (status),
    CONSTRAINT fk_comments_article
        FOREIGN KEY (article_id) REFERENCES articles (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO articles
    (title, author, category, article_date, status, description, author_batch, department, email)
SELECT 'AI & Robotics Exhibition 2026', 'Tharindu Silva', 'Research', '2026-08-24', 'Pending', 'A student exhibition showcasing artificial intelligence and robotics projects.', 'Batch 24', 'Computing', 'student@nsbm.ac.lk'
WHERE NOT EXISTS (SELECT 1 FROM articles WHERE title = 'AI & Robotics Exhibition 2026');

INSERT INTO articles
    (title, author, category, article_date, status, description, author_batch, department, email)
SELECT 'Inter-University Cricket Championship', 'Kavindu Perera', 'Sports', '2026-08-23', 'Pending', 'Information about the inter-university cricket championship.', 'Batch 24', 'Computing', 'student@nsbm.ac.lk'
WHERE NOT EXISTS (SELECT 1 FROM articles WHERE title = 'Inter-University Cricket Championship');

INSERT INTO articles
    (title, author, category, article_date, status, description, author_batch, department, email)
SELECT 'Annual Gavel Club Poetry Slam', 'Anjal Mendis', 'Clubs & Societies', '2026-08-22', 'Pending', 'A poetry event organised for the campus community.', 'Batch 24', 'Computing', 'student@nsbm.ac.lk'
WHERE NOT EXISTS (SELECT 1 FROM articles WHERE title = 'Annual Gavel Club Poetry Slam');

INSERT INTO articles
    (title, author, category, article_date, status, description, author_batch, department, email)
SELECT 'Green University Tree Planting', 'Sahan Jayasinghe', 'Campus Life', '2026-08-21', 'Pending', 'A campus tree-planting and environmental awareness initiative.', 'Batch 24', 'Computing', 'student@nsbm.ac.lk'
WHERE NOT EXISTS (SELECT 1 FROM articles WHERE title = 'Green University Tree Planting');

INSERT INTO articles
    (title, author, category, article_date, status, description)
SELECT 'Library Extended Hours', 'Student Affairs', 'Academics', '2026-08-20', 'Published', 'The library will remain open longer during the examination period.'
WHERE NOT EXISTS (SELECT 1 FROM articles WHERE title = 'Library Extended Hours');

INSERT INTO announcements (title, message, status, announcement_date)
SELECT 'Library Extended Hours', 'The library will remain open longer during the examination period.', 'Published', '2026-08-20'
WHERE NOT EXISTS (SELECT 1 FROM announcements WHERE title = 'Library Extended Hours');

INSERT INTO announcements (title, message, status, announcement_date)
SELECT 'Career Fair 2026', 'Students are invited to attend the university career fair.', 'Published', '2026-08-18'
WHERE NOT EXISTS (SELECT 1 FROM announcements WHERE title = 'Career Fair 2026');

INSERT INTO announcements (title, message, status, announcement_date)
SELECT 'Sports Festival Registration', 'Registration is open for the upcoming campus sports festival.', 'Published', '2026-08-15'
WHERE NOT EXISTS (SELECT 1 FROM announcements WHERE title = 'Sports Festival Registration');
