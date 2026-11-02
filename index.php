<?php
// NSBM NewsDesk - Admin Dashboard (PHP Version)
require __DIR__ . '/db.php';

$totalArticles = (int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn();
$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'Pending'")->fetchColumn();
$pendingStatement = $pdo->prepare(
    'SELECT title, author, category, article_date FROM articles WHERE status = :status ORDER BY article_date DESC, id DESC'
);
$pendingStatement->execute(['status' => 'Pending']);
$pendingArticles = $pendingStatement->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NSBM NewsDesk - Admin Dashboard (PHP)</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="main-header">
    <div class="header-container">
        <a href="index.php" class="brand">
            <img src="nsbmlogo.jpg" alt="NSBM Logo">
            <div class="brand-text">
                <h2>NSBM <span>NewsDesk</span></h2>
                <p>NSBM Green University</p>
            </div>
        </a>
        <nav class="main-navigation">
            <a href="index.php" class="nav-link active">Home</a>
            <a href="#" class="nav-link">Latest News</a>
            <a href="#" class="nav-link">Categories <span class="arrow">⌄</span></a>
        </nav>
        <div class="header-actions">
            <form class="search-box" id="headerSearch">
                <span class="search-icon">⌕</span>
                <input type="search" id="searchInput" placeholder="Search news..." aria-label="Search news">
            </form>
            <a href="#" class="submit-button"><span>+</span> Submit Article</a>
            <button class="notification-button" type="button" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span class="notification-dot"></span></button>
            <button class="profile-button" type="button"><span class="profile-circle">A</span><span class="profile-text"><strong>Admin</strong><small>Administrator</small></span><span class="arrow">⌄</span></button>
        </div>
    </div>
</header>
<div class="layout">
    <aside class="sidebar" id="adminSidebar">
        <div class="sidebar-heading">ADMIN PORTAL</div>
        <a href="#dashboard" class="side-link active" data-page="dashboard"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a>
        <a href="#articles" class="side-link" data-page="articles"><i class="fa-solid fa-file-lines"></i> Manage Articles</a>
        <a href="#comments" class="side-link" data-page="comments"><i class="fa-solid fa-comments"></i><span>Manage Comments</span></a>
        <a href="#categories" class="side-link" data-page="categories"><i class="fa-solid fa-layer-group"></i> Categories</a>
        <a href="#announcements" class="side-link" data-page="announcements"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
        <a href="#reports" class="side-link" data-page="reports"><i class="fa-solid fa-chart-column"></i> Reports</a>
        <div class="system-card">
            <h3><i class="fa-solid fa-circle-check"></i> System Status</h3>
            <img src="nsbm-campus.jpg.jpeg" alt="NSBM Green University campus">
            <strong>NSBM Green University</strong>
            <p>Campus system status preview.</p>
            <div class="system-line"><span><i class="fa-solid fa-wifi"></i> Campus Network</span><span class="online">Online*</span></div>
            <div class="system-line"><span><i class="fa-solid fa-database"></i> Database</span><span class="online">Connected</span></div>
            <small>*Demo status only; not a live system check.</small>
        </div>
        <div class="sidebar-bottom">
            <strong>Green Minds, Brighter Futures</strong>
            <p>NSBM NewsDesk Admin Portal</p>
        </div>
    </aside>
    <main class="main-content">
        <section id="dashboardPage" class="page-section">
            <div class="page-heading">
                <div>
                    <h1>Admin Overview</h1>
                    <p>Welcome back, Administrator. Here's what's happening across NSBM NewsDesk today.</p>
                </div>
                <button class="primary-button" id="quickAnnouncement"><i class="fa-solid fa-plus"></i> New Announcement</button>
            </div>
            <div class="stats-grid">
                <article class="stat-card"><span>TOTAL ARTICLES</span><i class="fa-solid fa-file-lines stat-icon green"></i><h2><?= $totalArticles ?></h2></article>
                <article class="stat-card"><span>READERSHIP</span><i class="fa-solid fa-eye stat-icon blue"></i><h2>45.2K</h2></article>
                <article class="stat-card"><span>STUDENTS</span><i class="fa-solid fa-users stat-icon purple"></i><h2>3,850</h2></article>
                <article class="stat-card"><span>PENDING REVIEW</span><i class="fa-solid fa-clock stat-icon red"></i><h2><?= $pendingCount ?></h2></article>
            </div>
            <section class="content-card">
                <div class="card-heading">
                    <div>
                        <h2>Pending Article Submissions</h2>
                        <p>Review student articles before publishing.</p>
                    </div>
                    <span class="count-badge" id="pendingLabel"><?= $pendingCount ?> Pending</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Article Title</th>
                                <th>Author / Club</th>
                                <th>Category</th>
                                <th>Submitted Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($pendingCount === 0): ?>
                            <tr><td colspan="4">No pending submissions.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pendingArticles as $article): ?>
                                <tr>
                                    <td><?= htmlspecialchars($article['title']) ?></td>
                                    <td><?= htmlspecialchars($article['author']) ?></td>
                                    <td><?= htmlspecialchars($article['category']) ?></td>
                                    <td><?= htmlspecialchars($article['article_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>
</div>
<footer class="site-footer">
    <div>
        <strong>NSBM NewsDesk</strong>
        <p>The official campus news and club-post portal for NSBM Green University, Sri Lanka. Keeping the academic community connected and informed.</p>
    </div>
    <div>
        <strong>Quick Links</strong>
        <p><a href="index.php">Home</a></p>
        <p><a href="#articles">Latest News</a></p>
        <p><a href="#articles">Submit an Article</a></p>
        <p><a href="#articles">Bookmark</a></p>
    </div>
    <div>
        <strong>Contact</strong>
        <p>NSBM Green University</p>
        <p>Homagama, Sri Lanka</p>
        <p>Email: <a href="mailto:newsdesk@nsbm.ac.lk">newsdesk@nsbm.ac.lk</a></p>
    </div>
</footer>
</body>
</html>
