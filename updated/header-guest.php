<?php
$pageTitle = $pageTitle ?? 'NSBM NewsDesk';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$categories = [
    'Campus Headlines' => 'category.php?cat=campus-headlines',
    'Computing Faculty News' => 'category.php?cat=computing',
    'Business & Innovation' => 'category.php?cat=business',
    'Engineering Research' => 'category.php?cat=engineering',
    'Clubs & Event Calendars' => 'category.php?cat=clubs',
];

$categoryLinks = '';
foreach ($categories as $label => $url) {
    $categoryLinks .= '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
        . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>';
}

require_once __DIR__ . '/template-renderer.php';
newsdesk_render_html_template(
    __DIR__ . '/templates/header-guest.html',
    [
        'PAGE_TITLE' => $pageTitle,
        'HOME_ACTIVE' => $currentPage === 'index.php' ? 'active' : '',
        'LATEST_ACTIVE' => $currentPage === 'latest-news.php' ? 'active' : '',
    ],
    ['CATEGORY_LINKS' => $categoryLinks]
);
