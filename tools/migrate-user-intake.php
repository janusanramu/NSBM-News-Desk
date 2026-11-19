<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__.'/../NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';

$db = database();
$columns = [
    'student_id' => 'VARCHAR(50) NULL AFTER name',
    'degree' => 'VARCHAR(120) NULL AFTER faculty',
    'intake' => 'VARCHAR(4) NULL AFTER degree',
];
foreach ($columns as $name => $definition) {
    if ($db->query("SHOW COLUMNS FROM users LIKE '$name'")->fetch()) {
        fwrite(STDOUT, "The users.$name column already exists.\n");
        continue;
    }

    $db->exec("ALTER TABLE users ADD COLUMN $name $definition");
    fwrite(STDOUT, "Added the users.$name column.\n");
}

if ($db->query("SHOW INDEX FROM users WHERE Key_name = 'uq_users_student_id'")->fetch()) {
    fwrite(STDOUT, "The users.student_id unique index already exists.\n");
} else {
    $db->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_student_id (student_id)');
    fwrite(STDOUT, "Added the users.student_id unique index.\n");
}
