CREATE DATABASE IF NOT EXISTS nsbm_newsdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nsbm_newsdesk;

CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    category VARCHAR(80) NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    summary VARCHAR(300) NOT NULL,
    content LONGTEXT NOT NULL,
    cover_image VARCHAR(255) NULL,
    status ENUM('Draft', 'Pending Review', 'Published') NOT NULL DEFAULT 'Pending Review',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional sample rows for testing
INSERT INTO articles (title, category, author_name, summary, content, status)
VALUES
    ('Campus Innovation Week Begins', 'Campus Life', 'Nimali Silva', 'Students launched a campus-wide innovation event with workshops and networking.', 'The event brought together students, staff, and entrepreneurs for a series of collaborative activities.', 'Published'),
    ('Research Fellows Present New Findings', 'Research', 'Kavindu Perera', 'A new study explored sustainable campus initiatives and community engagement.', 'Researchers shared early findings and proposed actions for future implementation.', 'Pending Review')
ON DUPLICATE KEY UPDATE title = VALUES(title);
