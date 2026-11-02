<?php
$pageTitle = 'NSBM NewsDesk - My Submissions';
$pageHeadContent = <<<'HTML'
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..500,0..1,0" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>body { display: flex; flex-direction: column; min-height: 100vh; }</style>
HTML;

if (!defined('NEWS_DESK_ASSET_BASE_URL')) {
    define('NEWS_DESK_ASSET_BASE_URL', 'updated');
}
define('NEWS_DESK_ALLOW_GUEST_PREVIEW', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
$userId = is_int($sessionUserId) && $sessionUserId > 0 ? $sessionUserId : 0;
$submissions = [];
$databaseError = false;

try {
    $pdo = require __DIR__ . '/database.php';
    $statement = $pdo->prepare(
        'SELECT id, title, summary, cover_image_url, status,
                rejection_reason, submission_date, category_name
         FROM my_submissions_view
         WHERE author_id = :author_id
         ORDER BY submission_date DESC, id DESC'
    );
    $statement->execute(['author_id' => $userId]);
    $submissions = $statement->fetchAll();
} catch (PDOException | RuntimeException $exception) {
    error_log('Unable to load my submissions: ' . $exception->getMessage());
    $databaseError = true;
}

if (!$databaseError) {
    $_SESSION['submission_count'] = count($submissions);
}

$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$statusDetails = [
    'draft' => ['Draft', 'draft', 'edit_note'],
    'pending_review' => ['Pending Review', 'pending_review', 'schedule'],
    'approved' => ['Approved', 'approved', 'check_circle'],
    'rejected' => ['Needs Revision', 'rejected', 'error'],
    'archived' => ['Archived', 'archived', 'inventory_2'],
];

$safeImageUrl = static function ($imageUrl): string {
    $imageUrl = trim((string)$imageUrl);
    if ($imageUrl !== '' && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string)parse_url($imageUrl, PHP_URL_SCHEME));
        if ($scheme === 'https' || $scheme === 'http') {
            return $imageUrl;
        }
    } elseif (preg_match('/^(?!\\/\\/)[A-Za-z0-9._\\/-]+$/', $imageUrl)) {
        return $imageUrl;
    }

    return 'images/nsbm-campus-aerial.jpg';
};

require __DIR__ . '/updated/header-user.php';
?>

<div class="container">
    <section class="page-head">
        <div>
            <div class="eyebrow"><span class="material-symbols-outlined">article</span>AUTHOR PORTAL</div>
            <h1>My Submissions</h1>
            <p>Track your editorial pitches, faculty review status, and publication milestones.</p>
        </div>
        <a href="submit_article.html" class="btn btn-primary btn-lg">
            <span class="material-symbols-outlined">add_circle</span>New Submission
        </a>
    </section>

    <section class="stats">
        <div class="stat">
            <div>
                <span class="stat-label">Total Submissions</span>
                <span class="stat-value" id="statTotal">00</span>
            </div>
            <span class="stat-icon neutral"><span class="material-symbols-outlined">folder</span></span>
        </div>
        <div class="stat">
            <div>
                <span class="stat-label">Published / Approved</span>
                <span class="stat-value green" id="statApproved">00</span>
            </div>
            <span class="stat-icon green"><span class="material-symbols-outlined">check_circle</span></span>
        </div>
        <div class="stat">
            <div>
                <span class="stat-label">Pending Review</span>
                <span class="stat-value amber" id="statPending">00</span>
            </div>
            <span class="stat-icon amber"><span class="material-symbols-outlined">schedule</span></span>
        </div>
        <div class="stat">
            <div>
                <span class="stat-label">Needs Revision</span>
                <span class="stat-value red" id="statRejected">00</span>
            </div>
            <span class="stat-icon red"><span class="material-symbols-outlined">error</span></span>
        </div>
    </section>

    <section class="history">
        <div class="history-head">
            <h2>Article History</h2>
            <div class="filter">
                <span class="material-symbols-outlined">filter_list</span>
                <select id="statusFilter" aria-label="Filter submissions by status">
                    <option value="all">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="pending_review">Pending Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Needs Revision</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
        </div>

        <div class="article-list" id="articleList">
            <?php foreach ($submissions as $submission):
                $status = (string)$submission['status'];
                $details = $statusDetails[$status] ?? ['Unknown', 'archived', 'help'];
                $timestamp = strtotime((string)$submission['submission_date']);
                $formattedDate = $timestamp === false ? 'Date unavailable' : date('M j, Y', $timestamp);
            ?>
                <article class="article<?= $status === 'rejected' ? ' is-rejected' : '' ?>" data-status="<?= $escape($status) ?>">
                    <div class="article-row">
                        <img
                            class="thumb"
                            src="<?= $escape($safeImageUrl($submission['cover_image_url'])) ?>"
                            alt="<?= $escape($submission['title']) ?>"
                            loading="lazy"
                        >
                        <div class="article-body">
                            <div class="meta">
                                <span class="badge <?= $escape($details[1]) ?>">
                                    <span class="material-symbols-outlined"><?= $escape($details[2]) ?></span><?= $escape($details[0]) ?>
                                </span>
                                <span class="meta-text">&bull; <?= $escape($submission['category_name']) ?></span>
                                <span class="meta-text">&bull; Submitted <?= $escape($formattedDate) ?></span>
                            </div>
                            <h3><?= $escape($submission['title']) ?></h3>
                            <p><?= $escape($submission['summary']) ?></p>
                        </div>
                    </div>
                    <?php if ($status === 'rejected' && !empty($submission['rejection_reason'])): ?>
                        <div class="rejection-note">
                            <div class="rejection-title"><span class="material-symbols-outlined">error</span>Reviewer Feedback</div>
                            <p><?= nl2br($escape($submission['rejection_reason'])) ?></p>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <div
            class="empty"
            id="emptyState"
            role="<?= $databaseError ? 'alert' : 'status' ?>"
            <?= $submissions && !$databaseError ? 'style="display:none"' : '' ?>
        ><?php
            if ($databaseError) {
                echo 'Unable to load submissions. Check the database configuration and try again.';
            } elseif ($userId === 0) {
                echo 'Sign in to see submissions linked to your account.';
            } else {
                echo 'No submissions found for this filter.';
            }
        ?></div>

        <div class="history-foot">
            <span id="showingText">Showing 0-0 of 0 submissions</span>
            <nav class="pager" id="pager" aria-label="Submission pages"></nav>
        </div>
    </section>
</div>

<script src="main.js"></script>
<?php require __DIR__ . '/updated/footer-user.php'; ?>
