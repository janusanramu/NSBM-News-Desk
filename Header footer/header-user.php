<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) && !defined('NEWS_DESK_ALLOW_GUEST_PREVIEW')) {
    header('Location: login.php');
    exit;
}

$pageTitle = $pageTitle ?? 'NSBM NewsDesk';
$pageHeadContent = $pageHeadContent ?? '';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$fullName = $_SESSION['full_name'] ?? 'User';
$trimmedName = trim($fullName);
$initials = '';

foreach (array_slice(preg_split('/\s+/', $trimmedName) ?: [''], 0, 2) as $part) {
    $initials .= strtoupper(mb_substr($part, 0, 1));
}
$firstName = explode(' ', $trimmedName)[0] ?? '';
$lastName = trim(substr($trimmedName, strlen($firstName)));

$categories = [
    'Campus Life' => 'category.php?cat=campus-life',
    'Academics' => 'category.php?cat=academics',
    'Research' => 'category.php?cat=research',
    'Clubs & Societies' => 'category.php?cat=clubs-societies',
    'Sports' => 'category.php?cat=sports',
];

$categoryLinks = '';
foreach ($categories as $label => $url) {
    $categoryLinks .= '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
        . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>';
}

require_once __DIR__ . '/template-renderer.php';
newsdesk_render_html_template(
    __DIR__ . '/templates/header-user.html',
    [
        'PAGE_TITLE' => $pageTitle,
        'HOME_ACTIVE' => $currentPage === 'index.php' ? 'active' : '',
        'LATEST_ACTIVE' => $currentPage === 'latest-news.php' ? 'active' : '',
        'USER_INITIALS' => $initials,
        'USER_FIRST_NAME' => $firstName,
        'USER_LAST_NAME' => $lastName,
    ],
    [
        'CATEGORY_LINKS' => $categoryLinks,
        'PAGE_HEAD_CONTENT' => $pageHeadContent,
    ]
);
