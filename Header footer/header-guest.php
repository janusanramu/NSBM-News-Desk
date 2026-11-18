<?php
$pageTitle = $pageTitle ?? 'NSBM NewsDesk';
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

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
    __DIR__ . '/templates/header-guest.html',
    [
        'PAGE_TITLE' => $pageTitle,
        'HOME_ACTIVE' => $currentPage === 'index.php' ? 'active' : '',
        'LATEST_ACTIVE' => $currentPage === 'latest-news.php' ? 'active' : '',
    ],
    ['CATEGORY_LINKS' => $categoryLinks]
);
