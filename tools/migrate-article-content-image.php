<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__.'/../NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';

$db = database();
if ($db->query("SHOW COLUMNS FROM articles LIKE 'content_image_url'")->fetch()) {
    fwrite(STDOUT, "The articles.content_image_url column already exists.\n");
    exit(0);
}

$db->exec('ALTER TABLE articles ADD COLUMN content_image_url VARCHAR(1000) NULL AFTER cover_image_url');
fwrite(STDOUT, "Added the articles.content_image_url column.\n");
