CREATE DATABASE IF NOT EXISTS nsbm_newsdesk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nsbm_newsdesk;

CREATE TABLE IF NOT EXISTS bookmarks (
    id VARCHAR(32) PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    meta VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    author_code VARCHAR(10) NOT NULL,
    author_name VARCHAR(150) NOT NULL,
    read_url VARCHAR(500) NOT NULL DEFAULT '#',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO bookmarks
    (id, category, image, meta, title, description, author_code, author_name, read_url, sort_order)
VALUES
    ('b1', 'Campus Life', 'images/campus life.jpeg', 'Oct 24, 2026 · 4 min read',
     'Annual Green Walk 2026 Announced with Massive Student Participation',
     'Join the largest campus event of the year celebrating sustainability, unity, and student spirit across all faculties at NSBM Green University.',
     'JD', 'Sahan Jayasinghe', '#', 1),
    ('b2', 'academic', 'images/lecturehall.jpg', 'Oct 27, 2026 · 4 min read',
     'NSBM Green University Hosts Guest Lecture on the Importance of Surveying in Construction Projects',
     'NSBM Green University hosted a guest lecture on construction surveying, where students gained valuable knowledge about modern surveying practices, construction planning and industry development.',
     'AS', 'Tharindu Silva', '#', 2),
    ('b3', 'Research', 'images/research.jpeg', 'Oct 26, 2026 · 6 min read',
     'NSBM Unveils Model of Proposed Multibillion Medical and Research Hospital',
     'NSBM researchers are conducting research on AI-assisted medical image analysis to support the early detection of cervical precancerous conditions. This research highlights the role of artificial intelligence in improving healthcare technology.',
     'AS', 'Dineth kaveesha', '#', 3),
    ('b4', 'Clubs & Societies', 'images/culture.jpg', 'February 11, 2026 · 3 min read',
     'Harithabhinandana 2026 – A Celebration of Culture',
     'NSBM celebrated Sri Lankan culture and creativity through traditional performances, singing, drama, and student talent.',
     'CS', 'Anjal Mendis', '#', 4),
    ('b5', 'Sports', 'images/sport.jpg', 'March 24, 2026 · 4 min read',
     'NSBM Sports Fiesta 2026',
     'Students showcased their sporting talents across 20 sports, celebrating teamwork, unity, and sportsmanship.',
     'ST', 'Kavindu Perera', '#', 5);