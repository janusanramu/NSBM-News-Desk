CREATE DATABASE IF NOT EXISTS nsbm_newsdesk
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nsbm_newsdesk;

CREATE TABLE IF NOT EXISTS articles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(50) NOT NULL,
  title VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_articles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  article_id INT UNSIGNED NOT NULL,
  author_name VARCHAR(80) NOT NULL,
  body VARCHAR(2000) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comments_article_created (article_id, created_at),
  CONSTRAINT fk_comments_article
    FOREIGN KEY (article_id) REFERENCES articles (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO articles (slug, title) VALUES
  ('computing', 'NSBM Computing Students Develop Digital Platform for Recreation Center Management'),
  ('business', 'NSBM 6th Student Research Symposium in Marketing, Tourism and Multimedia'),
  ('engineering', 'NSBM Opens Rs. 720 Million Civil Engineering Laboratory Complex'),
  ('events', 'NSBM Green University Celebrates 10 Years of Excellence and National Impact'),
  ('sports', 'NSBM Cricket Team Crowned Champions Again at KDU Inter-University T20 Championship 2026'),
  ('notices', 'End Semester Examination Timetable Released for All Faculties')
ON DUPLICATE KEY UPDATE title = VALUES(title);
