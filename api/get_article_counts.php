<?php

header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

try {
    $pdo = getDbConnection();

    $stmt = $pdo->query(
        "SELECT
            SUM(CASE WHEN status = 'Pending Review' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) AS published
         FROM articles"
    );

    $row = $stmt->fetch();

    $pending = (int) ($row['pending'] ?? 0);
    $published = (int) ($row['published'] ?? 0);

    echo json_encode([
        'success' => true,
        'pending' => $pending,
        'published' => $published,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load article counts.'
    ]);
}
