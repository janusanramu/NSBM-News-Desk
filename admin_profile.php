<?php
require_once __DIR__ . '/config/admin_auth.php';
require_once __DIR__ . '/config/admin_db.php';

$adminStmt = $pdo->prepare(
    'SELECT id, full_name, email, contact_number, role, last_login,
            two_factor_enabled, email_notifications, created_at, profile_picture
     FROM admins
     WHERE id = ?
     LIMIT 1'
);
$adminStmt->execute([(int) $_SESSION['admin_id']]);
$admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}
$storedPhoto = $admin['profile_picture'] ?? '';
$profilePhotoIsValid = is_string($storedPhoto)
    && preg_match('~\Aassets/admin-profile-photos/[A-Za-z0-9_-]+\.(?:jpg|png|webp)\z~i', $storedPhoto)
    && is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedPhoto));
$profilePhoto = $profilePhotoIsValid ? $storedPhoto : 'assets/profile.png';

$activityStmt = $pdo->prepare(
    'SELECT action, description, created_at
     FROM admin_activity_logs
     WHERE admin_id = ?
     ORDER BY created_at DESC
     LIMIT 8'
);
$activityStmt->execute([(int) $admin['id']]);
$activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

$activityCountStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM admin_activity_logs
     WHERE admin_id = ?'
);
$activityCountStmt->execute([(int) $admin['id']]);
$activityCount = (int) $activityCountStmt->fetchColumn();

$initials = '';
foreach (preg_split('/\s+/', trim($admin['full_name'])) as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
$initials = mb_substr($initials, 0, 2);

function adminActivityIcon(string $action): string
{
    $action = strtolower($action);
    if (str_contains($action, 'approv') || str_contains($action, 'publish')) {
        return '✓';
    }
    if (str_contains($action, 'reject')) {
        return '×';
    }
    if (str_contains($action, 'categor')) {
        return '#';
    }
    if (str_contains($action, 'announcement')) {
        return '!';
    }
    if (str_contains($action, 'password') || str_contains($action, 'security')) {
        return '⌑';
    }
    return '•';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#00563f">
    <title>Admin Profile | NSBM NewsDesk</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin_profile.css">
    <script src="admin_profile.js" defer></script>
</head>
<body>
<main class="dashboard admin-profile">
    <section class="profile-banner" aria-label="Administrator profile">
        <div class="profile-identity">
            <div class="avatar-wrap">
                <img class="avatar" src="<?= e($profilePhoto) ?>" alt="Admin profile picture">
                <span class="verified" aria-label="Administrator account">✓</span>
            </div>
            <div class="profile-copy">
                <div class="name-row">
                    <h1><?= e($admin['full_name']) ?></h1>
                    <span class="role"><?= e(ucfirst($admin['role'])) ?></span>
                </div>
                <p class="faculty"><?= e($admin['email']) ?></p>
                <p class="membership">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <?= $admin['last_login']
                        ? 'Last active ' . e(date('j M Y, g:i a', strtotime($admin['last_login'])))
                        : 'Last login not available' ?>
                </p>
            </div>
        </div>
        <div class="stats" aria-label="Administrator account summary">
            <div class="stat">
                <strong><?= number_format($activityCount) ?></strong>
                <span>ACTIVITY LOGS</span>
            </div>
            <div class="stat">
                <strong><?= !empty($admin['two_factor_enabled']) ? 'On' : 'Off' ?></strong>
                <span>TWO-FACTOR</span>
            </div>
            <div class="stat">
                <strong><?= !empty($admin['email_notifications']) ? 'On' : 'Off' ?></strong>
                <span>EMAIL ALERTS</span>
            </div>
        </div>
    </section>

    <nav class="tabs" aria-label="Administrator navigation">
        <a class="tab active" aria-current="page" href="admin_profile.php">
            <span class="tab-icon">▦</span> Profile Overview
        </a>
        <a class="tab" href="admin_account_settings.php">
            <span class="tab-icon">⚙</span> Account Settings
        </a>
        <a class="tab" href="admin.php">
            <span class="tab-icon">▤</span> Admin Panel
        </a>
    </nav>

    <div class="dashboard-grid">
        <section class="card activity-card">
            <header class="card-header">
                <h2><span class="heading-icon">◴</span> Recent Admin Activity</h2>
                <span class="period">Latest <?= count($activities) ?></span>
            </header>
            <?php if (!$activities): ?>
                <p class="activity-empty">No activity has been recorded for this account yet.</p>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($activities as $activity): ?>
                        <article class="activity">
                            <div class="activity-icon publish" aria-hidden="true">
                                <?= e(adminActivityIcon($activity['action'])) ?>
                            </div>
                            <div>
                                <h3><?= e($activity['action']) ?></h3>
                                <p><?= e($activity['description']) ?></p>
                                <time datetime="<?= e(date(DATE_ATOM, strtotime($activity['created_at']))) ?>">
                                    <?= e(date('j M Y, g:i a', strtotime($activity['created_at']))) ?>
                                </time>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card engagement-card">
            <header class="card-header">
                <h2><span class="heading-icon">♙</span> Account Details</h2>
            </header>
            <dl class="profile-details">
                <div><dt>Full name</dt><dd><?= e($admin['full_name']) ?></dd></div>
                <div><dt>Email address</dt><dd><?= e($admin['email']) ?></dd></div>
                <div><dt>Contact number</dt><dd><?= e($admin['contact_number'] ?: 'Not provided') ?></dd></div>
                <div><dt>Role</dt><dd><?= e(ucfirst($admin['role'])) ?></dd></div>
                <div>
                    <dt>Two-factor authentication</dt>
                    <dd>
                        <span class="security-indicator <?= !empty($admin['two_factor_enabled']) ? 'is-enabled' : '' ?>">
                            <?= !empty($admin['two_factor_enabled']) ? 'Enabled' : 'Not enabled' ?>
                        </span>
                    </dd>
                </div>
            </dl>
            <div class="profile-actions">
                <a class="profile-link primary" href="admin_account_settings.php">
                    Edit Account
                </a>
            </div>
        </section>
    </div>
</main>
</body>
</html>
