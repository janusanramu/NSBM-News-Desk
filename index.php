<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/user_db.php';

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $payload = json_decode(file_get_contents('php://input'), true);

    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request body.']);
        exit;
    }

    if (
        !isset($payload['csrf_token'])
        || !is_string($payload['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $payload['csrf_token'])
    ) {
        http_response_code(403);
        echo json_encode(['error' => 'Your session token expired. Refresh the page and try again.']);
        exit;
    }

    $action = $payload['action'] ?? '';
    $categorySlug = $payload['category'] ?? '';
    if (
        !is_string($action)
        || !in_array($action, ['subscribe', 'unsubscribe', 'notifications'], true)
        || !is_string($categorySlug)
        || !preg_match('/\A[a-z0-9-]{1,64}\z/', $categorySlug)
    ) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid subscription request.']);
        exit;
    }

    try {
        $categoryCheck = $pdo->prepare(
            'SELECT slug FROM dashboard_categories WHERE slug = ? AND is_active = 1'
        );
        $categoryCheck->execute([$categorySlug]);
        if (!$categoryCheck->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'That news category is unavailable.']);
            exit;
        }

        if ($action === 'subscribe') {
            $subscription = $pdo->prepare(
                'INSERT INTO user_category_subscriptions (user_id, category_slug)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE notifications_enabled = VALUES(notifications_enabled)'
            );
            $subscription->execute([$userId, $categorySlug]);
        } elseif ($action === 'unsubscribe') {
            $subscription = $pdo->prepare(
                'DELETE FROM user_category_subscriptions WHERE user_id = ? AND category_slug = ?'
            );
            $subscription->execute([$userId, $categorySlug]);
        } else {
            if (!isset($payload['enabled']) || !is_bool($payload['enabled'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Choose whether notifications should be enabled.']);
                exit;
            }
            $subscriptionCheck = $pdo->prepare(
                'SELECT 1 FROM user_category_subscriptions
                 WHERE user_id = ? AND category_slug = ?'
            );
            $subscriptionCheck->execute([$userId, $categorySlug]);
            if (!$subscriptionCheck->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['error' => 'Subscribe to this category before changing notifications.']);
                exit;
            }
            $subscription = $pdo->prepare(
                'UPDATE user_category_subscriptions
                 SET notifications_enabled = ?
                 WHERE user_id = ? AND category_slug = ?'
            );
            $subscription->execute([$payload['enabled'] ? 1 : 0, $userId, $categorySlug]);
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Unable to save this subscription. Please try again.']);
    }
    exit;
}

try {
    $stmt = $pdo->prepare(
        'SELECT full_name, email, profile_picture, created_at
         FROM users
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $statsStmt = $pdo->prepare(
        'SELECT COUNT(*) AS submissions,
                COALESCE(SUM(CASE WHEN status = \'published\' THEN views ELSE 0 END), 0) AS reads
         FROM dashboard_articles
         WHERE user_id = ?'
    );
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $bookmarkStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM dashboard_bookmarks WHERE user_id = ?'
    );
    $bookmarkStmt->execute([$userId]);
    $savedCount = (int) $bookmarkStmt->fetchColumn();

    $activityStmt = $pdo->prepare(
        'SELECT activity_type, title, description, created_at
         FROM dashboard_activity
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 5'
    );
    $activityStmt->execute([$userId]);
    $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

    $categoriesStmt = $pdo->prepare(
        'SELECT c.slug, c.name, c.description, s.notifications_enabled
         FROM dashboard_categories c
         LEFT JOIN user_category_subscriptions s
           ON s.category_slug = c.slug AND s.user_id = ?
         WHERE c.is_active = 1
         ORDER BY c.name'
    );
    $categoriesStmt->execute([$userId]);
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load your dashboard. Import dashboard_database.sql and try again.');
}

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$profilePhoto = 'assets/profile.png';
if (
    !empty($user['profile_picture'])
    && preg_match(
        '~\Auploads/profile-photos/[A-Za-z0-9_-]+\.(?:jpg|png|webp)\z~i',
        $user['profile_picture']
    )
    && is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $user['profile_picture']))
) {
    $profilePhoto = $user['profile_picture'];
}

$memberSince = (new DateTimeImmutable($user['created_at']))->format('F Y');
$categoryData = [];
$subscriptionState = [];
foreach ($categories as $category) {
    $categoryData[] = [
        'id' => $category['slug'],
        'name' => $category['name'],
        'description' => $category['description']
    ];
    if ($category['notifications_enabled'] !== null) {
        $subscriptionState[$category['slug']] = [
            'notifications' => (bool) $category['notifications_enabled']
        ];
    }
}
$initials = '';
foreach (preg_split('/\s+/', trim($user['full_name'])) as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
$initials = mb_substr($initials, 0, 2);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#00563f">
  <title>NSBM NewsDesk | User Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <script src="script.js" defer></script>
</head>
<body>
  <main class="dashboard">
    <section class="profile-banner" aria-label="User profile">
      <div class="profile-identity">
        <div class="avatar-wrap">
          <img src="<?= e($profilePhoto) ?>" alt="Profile photo of <?= e($user['full_name']) ?>" class="avatar">
          <span class="verified" aria-label="Contributor badge">✿</span>
        </div>
        <div class="profile-copy">
          <div class="name-row">
            <h1><?= e($user['full_name']) ?></h1>
            <span class="role">Contributor</span>
          </div>
          <p class="faculty"><?= e($user['email']) ?></p>
          <p class="membership">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            Member since <?= e($memberSince) ?>
          </p>
        </div>
      </div>
      <div class="stats" aria-label="Profile statistics">
        <div class="stat"><strong><?= number_format((int) $stats['submissions']) ?></strong><span>SUBMISSIONS</span></div>
        <div class="stat"><strong><?= number_format((int) $stats['reads']) ?></strong><span>READS</span></div>
        <div class="stat"><strong class="saved-number"><?= number_format($savedCount) ?></strong><span>SAVED</span></div>
      </div>
    </section>

    <nav class="tabs" aria-label="Dashboard navigation">
      <button class="tab active" data-tab="overview" aria-current="page"><span class="tab-icon">▦</span> Overview</button>
      <button class="tab" data-tab="submissions"><span class="tab-icon">▤</span> My Submissions</button>
      <button class="tab" data-tab="bookmarks"><span class="tab-icon">♧</span> Saved Bookmarks</button>
      <button class="tab" data-tab="submit"><span class="tab-icon">▧</span> Submit Article</button>
      <button class="tab" data-tab="settings"><span class="tab-icon">⚙</span> Account Settings</button>
      <button class="tab" data-tab="subscription" aria-controls="subscription" aria-expanded="false"><span class="tab-icon">♧</span> My Subscriptions</button>
    </nav>

    <div class="dashboard-grid" id="overview" aria-label="Overview">
      <section class="card activity-card">
        <header class="card-header">
          <h2><span class="heading-icon">◴</span> Recent Activity</h2>
          <span class="period">Latest activity</span>
        </header>
        <div class="timeline">
          <?php if (!$activities): ?>
            <p class="subscription-note">No recent activity is available yet.</p>
          <?php else: ?>
            <?php foreach ($activities as $activity): ?>
              <?php
              $activityIcon = [
                  'published' => '↟',
                  'bookmarked' => '♧',
                  'profile_updated' => '✎',
                  'submitted' => '▤'
              ][$activity['activity_type']] ?? '•';
              $activityClass = [
                  'published' => 'publish',
                  'bookmarked' => 'bookmark',
                  'profile_updated' => 'edit',
                  'submitted' => 'publish'
              ][$activity['activity_type']] ?? '';
              ?>
              <article class="activity">
                <div class="activity-icon <?= e($activityClass) ?>" aria-hidden="true"><?= e($activityIcon) ?></div>
                <div>
                  <h3><?= e($activity['title']) ?></h3>
                  <p><?= e($activity['description']) ?></p>
                  <time datetime="<?= e(date(DATE_ATOM, strtotime($activity['created_at']))) ?>">
                    <?= e(date('j M Y, g:i a', strtotime($activity['created_at']))) ?>
                  </time>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
      <section class="card engagement-card">
        <header class="card-header"><h2><span class="heading-icon">↗</span> Engagement Score</h2></header>
        <p class="engagement-subtitle">Reader reach across all published student articles.</p>
        <div class="engagement-value"><strong><?= number_format((int) $stats['reads']) ?></strong></div>
        <p class="updated">Total reads across your published articles.</p>
      </section>
    </div>

    <section class="subscription-panel card" id="subscription" aria-label="My News Category Subscriptions" hidden>
      <div class="subscription-top">
        <div><span class="subscription-eyebrow">PERSONALIZED NEWS NOTIFICATIONS</span><h2>My Subscriptions</h2><p>Follow the news categories you care about and manage their notifications.</p></div>
        <span class="subscription-status" id="subscription-status">0 categories followed</span>
      </div>
      <div class="subscription-tools">
        <label for="category-search">Find a news category</label>
        <input id="category-search" type="search" placeholder="Search news categories..." autocomplete="off">
      </div>
      <div class="subscription-list" id="subscription-list" aria-live="polite"></div>
      <p class="subscription-note">Your subscriptions and category notification preferences are saved to your account.</p>
      <p class="subscription-feedback" id="subscription-feedback" role="status" aria-live="polite"></p>
    </section>
  </main>
  <script>
    window.dashboardConfig = <?= json_encode(
        [
            'csrfToken' => $_SESSION['csrf_token'],
            'categories' => $categoryData,
            'followed' => $subscriptionState
        ],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
  </script>
</body>
</html>
