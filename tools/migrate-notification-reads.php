<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__.'/../NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';

database()->exec(
    'CREATE TABLE IF NOT EXISTS user_notification_reads (
        user_id INT NOT NULL,
        article_id INT NOT NULL,
        read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, article_id),
        CONSTRAINT fk_notification_reads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_notification_reads_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);
fwrite(STDOUT, "The user_notification_reads table is ready.\n");
