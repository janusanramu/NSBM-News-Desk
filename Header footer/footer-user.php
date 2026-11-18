<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$submissionCount = (int)($_SESSION['submission_count'] ?? 14);
$bookmarkCount = (int)($_SESSION['bookmark_count'] ?? 38);

require_once __DIR__ . '/template-renderer.php';
newsdesk_render_html_template(
    __DIR__ . '/templates/footer-user.html',
    [
        'CURRENT_YEAR' => date('Y'),
        'SUBMISSION_COUNT' => $submissionCount,
        'BOOKMARK_COUNT' => $bookmarkCount,
    ]
);
