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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int) $_SESSION['user_id'];
$success = '';
$error = '';
$facultyDegrees = [
    'Faculty of Computing' => [
        'BSc (Hons) Computer Science',
        'BSc (Hons) Software Engineering',
        'BSc (Hons) Data Science',
        'BSc (Hons) Cyber Security',
        'BSc (Hons) Information Systems'
    ],
    'Faculty of Business' => [
        'BSc (Hons) Business Management',
        'BSc (Hons) Accounting and Finance',
        'BSc (Hons) Marketing Management'
    ],
    'Faculty of Engineering' => ['BSc (Hons) Engineering'],
    'Faculty of Science' => ['BSc (Hons) Science'],
    'Faculty of Postgraduate & Professional Advancement' => ['BA (Hons) English']
];
$categoryLabels = [
    'academic' => 'Academic',
    'events' => 'Events',
    'sports' => 'Sports',
    'general' => 'General News'
];
$categoryDescriptions = [
    'academic' => 'Exams, academic updates and university notices',
    'events' => 'Campus events, workshops and competitions',
    'sports' => 'Sports news, matches and student achievements',
    'general' => 'Other news and updates from NSBM'
];

try {
    $userStmt = $pdo->prepare(
        'SELECT id, full_name, email, student_id, faculty, degree_program,
                batch_year, password_hash, profile_picture
         FROM users
         WHERE id = ?
         LIMIT 1'
    );
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    $preferenceStmt = $pdo->prepare(
        'SELECT category FROM user_news_preferences WHERE user_id = ?'
    );
    $notificationStmt = $pdo->prepare(
        'SELECT news_notifications, submission_notifications, comment_notifications
         FROM user_notification_preferences
         WHERE user_id = ?
         LIMIT 1'
    );
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Unable to load account settings. Import the account settings database schema and try again.');
}

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$preferenceStmt->execute([$userId]);
$selectedCategories = $preferenceStmt->fetchAll(PDO::FETCH_COLUMN);
if (!$selectedCategories) {
    $selectedCategories = ['academic', 'events', 'general'];
}

$notificationStmt->execute([$userId]);
$notifications = $notificationStmt->fetch(PDO::FETCH_ASSOC);
if (!$notifications) {
    $notifications = [
        'news_notifications' => 1,
        'submission_notifications' => 1,
        'comment_notifications' => 0
    ];
}

$form = [
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'faculty' => $user['faculty'] ?? '',
    'degree' => $user['degree_program'] ?? '',
    'batch_year' => $user['batch_year'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Your session token was invalid. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_settings') {
            $form['full_name'] = trim((string) ($_POST['fullName'] ?? ''));
            $form['email'] = trim((string) ($_POST['email'] ?? ''));
            $form['faculty'] = trim((string) ($_POST['faculty'] ?? ''));
            $form['degree'] = trim((string) ($_POST['degree'] ?? ''));
            $batchYear = filter_var($_POST['batchYear'] ?? null, FILTER_VALIDATE_INT);
            $form['batch_year'] = $batchYear === false ? '' : $batchYear;
            $categories = $_POST['categories'] ?? [];
            $categoriesValid = is_array($categories);
            if ($categoriesValid) {
                foreach ($categories as $category) {
                    if (!is_string($category) || !array_key_exists($category, $categoryLabels)) {
                        $categoriesValid = false;
                        break;
                    }
                }
            }
            $currentPassword = (string) ($_POST['currentPassword'] ?? '');
            $newPassword = (string) ($_POST['newPassword'] ?? '');
            $confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

            if ($form['full_name'] === '' || mb_strlen($form['full_name']) > 150) {
                $error = 'Enter your name using no more than 150 characters.';
            } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 255) {
                $error = 'Enter a valid email address.';
            } elseif (!isset($facultyDegrees[$form['faculty']])) {
                $error = 'Select a valid faculty.';
            } elseif (!in_array($form['degree'], $facultyDegrees[$form['faculty']], true)) {
                $error = 'Select a valid degree programme for your faculty.';
            } elseif ($batchYear === false || $batchYear < 2015 || $batchYear > (int) date('Y')) {
                $error = 'Select a valid batch year.';
            } elseif (!$categoriesValid) {
                $error = 'One or more selected news categories are invalid.';
            } elseif ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
                if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                    $error = 'Fill in all three password fields to change your password.';
                } elseif (!password_verify($currentPassword, $user['password_hash'])) {
                    $error = 'Your current password is incorrect.';
                } elseif (strlen($newPassword) < 8) {
                    $error = 'Your new password must be at least 8 characters.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'The new password and confirmation do not match.';
                }
            }

            if ($error === '') {
                try {
                    $pdo->beginTransaction();
                    if ($newPassword !== '') {
                        $update = $pdo->prepare(
                            'UPDATE users
                             SET full_name = ?, email = ?, faculty = ?,
                                 degree_program = ?, batch_year = ?, password_hash = ?
                             WHERE id = ?'
                        );
                        $update->execute([
                            $form['full_name'],
                            $form['email'],
                            $form['faculty'],
                            $form['degree'],
                            $batchYear,
                            password_hash($newPassword, PASSWORD_DEFAULT),
                            $userId
                        ]);
                    } else {
                        $update = $pdo->prepare(
                            'UPDATE users
                             SET full_name = ?, email = ?, faculty = ?,
                                 degree_program = ?, batch_year = ?
                             WHERE id = ?'
                        );
                        $update->execute([
                            $form['full_name'],
                            $form['email'],
                            $form['faculty'],
                            $form['degree'],
                            $batchYear,
                            $userId
                        ]);
                    }

                    $deletePreferences = $pdo->prepare(
                        'DELETE FROM user_news_preferences WHERE user_id = ?'
                    );
                    $deletePreferences->execute([$userId]);
                    $insertPreference = $pdo->prepare(
                        'INSERT INTO user_news_preferences (user_id, category) VALUES (?, ?)'
                    );
                    foreach (array_unique($categories) as $category) {
                        $insertPreference->execute([$userId, $category]);
                    }

                    $saveNotifications = $pdo->prepare(
                        'INSERT INTO user_notification_preferences
                            (user_id, news_notifications, submission_notifications, comment_notifications)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                            news_notifications = VALUES(news_notifications),
                            submission_notifications = VALUES(submission_notifications),
                            comment_notifications = VALUES(comment_notifications)'
                    );
                    $saveNotifications->execute([
                        $userId,
                        isset($_POST['newsNotifications']) ? 1 : 0,
                        isset($_POST['submissionNotifications']) ? 1 : 0,
                        isset($_POST['commentNotifications']) ? 1 : 0
                    ]);
                    $pdo->commit();
                    $success = 'Your account settings have been saved.';
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if ($exception->getCode() === '23000') {
                        $error = 'That email address is already associated with an account.';
                    } else {
                        error_log($exception->getMessage());
                        $error = 'Unable to save your account settings. Please try again.';
                    }
                }
            }
        } elseif ($action === 'upload_photo' || $action === 'remove_photo') {
            $oldPhoto = $user['profile_picture'];
            $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile-photos';

            if ($action === 'remove_photo') {
                try {
                    $update = $pdo->prepare('UPDATE users SET profile_picture = NULL WHERE id = ?');
                    $update->execute([$userId]);
                    $success = 'Your profile photo has been removed.';
                } catch (PDOException $exception) {
                    error_log($exception->getMessage());
                    $error = 'Unable to remove your profile photo. Please try again.';
                }
            } else {
                $file = $_FILES['profile_photo'] ?? null;
                if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Choose a photo to upload. The maximum file size is 2 MB.';
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    $error = 'The photo must be no larger than 2 MB.';
                } else {
                    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    $allowedTypes = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp'
                    ];
                    if (!isset($allowedTypes[$mimeType]) || @getimagesize($file['tmp_name']) === false) {
                        $error = 'Choose a valid JPEG, PNG, or WebP image.';
                    } elseif (
                        !is_dir($uploadDirectory)
                        && !mkdir($uploadDirectory, 0755, true)
                        && !is_dir($uploadDirectory)
                    ) {
                        $error = 'The photo upload directory could not be created.';
                    } else {
                        $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                        $targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
                        $storedPath = 'uploads/profile-photos/' . $fileName;
                        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $error = 'The photo could not be uploaded. Please try again.';
                        } else {
                            try {
                                $update = $pdo->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
                                $update->execute([$storedPath, $userId]);
                                $success = 'Your profile photo has been updated.';
                            } catch (PDOException $exception) {
                                if (is_file($targetPath) && !unlink($targetPath)) {
                                    error_log('Could not remove uploaded profile photo after a database error.');
                                }
                                error_log($exception->getMessage());
                                $error = 'Unable to save your profile photo. Please try again.';
                            }
                        }
                    }
                }
            }

            if (
                $error === ''
                && $oldPhoto
                && preg_match('~\Auploads/profile-photos/[A-Za-z0-9_-]+\.(?:jpg|png|webp)\z~i', $oldPhoto)
            ) {
                $oldPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldPhoto);
                if (is_file($oldPath) && !unlink($oldPath)) {
                    error_log('Could not remove the previous profile photo.');
                }
            }
        } else {
            $error = 'That account settings action is not supported.';
        }

        if ($error === '') {
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            $preferenceStmt->execute([$userId]);
            $selectedCategories = $preferenceStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!$selectedCategories) {
                $selectedCategories = ['academic', 'events', 'general'];
            }
            $notificationStmt->execute([$userId]);
            $notifications = $notificationStmt->fetch(PDO::FETCH_ASSOC) ?: [
                'news_notifications' => 1,
                'submission_notifications' => 1,
                'comment_notifications' => 0
            ];
        }
    }
}

$photoPathIsValid = !empty($user['profile_picture'])
    && preg_match('~\Auploads/profile-photos/[A-Za-z0-9_-]+\.(?:jpg|png|webp)\z~i', $user['profile_picture'])
    && is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $user['profile_picture']));
$photoUrl = $photoPathIsValid ? $user['profile_picture'] : 'p2.jpg';
$initials = '';
foreach (preg_split('/\s+/', trim($user['full_name'])) as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}
$initials = mb_substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | NSBM NewsDesk</title>
    <link rel="stylesheet" href="user_account_settings.css">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="footer.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="brand">
            <img src="nsbm-logo.png" alt="NSBM Green University logo" class="brand-logo">
            <span class="brand-text">
                <span class="brand-title">NSBM <span class="accent">NewsDesk</span></span>
                <span class="brand-sub">NSBM Green University</span>
            </span>
        </a>
        <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <nav class="main-nav" id="mainNav">
            <a href="index.php" class="nav-link active"><i class="fa-solid fa-house"></i> Home</a>
            <a href="#" class="nav-link">Latest News</a>
            <div class="dropdown">
                <button class="nav-link dropdown-toggle" type="button" aria-haspopup="true">
                    Categories <i class="fa-solid fa-chevron-down chevron"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="#">Campus Headlines</a>
                    <a href="#">Computing Faculty News</a>
                    <a href="#">Business &amp; Innovation</a>
                    <a href="#">Engineering Research</a>
                    <a href="#">Clubs &amp; Event Calendars</a>
                </div>
            </div>
        </nav>
        <form class="search-box" action="index.php" method="get">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" name="q" placeholder="Search news..." aria-label="Search">
        </form>
        <div class="header-actions">
            <a href="#" class="btn btn-green"><i class="fa-solid fa-plus"></i> Submit Article</a>
            <button class="icon-btn" type="button" aria-label="Notifications">
                <i class="fa-regular fa-bell"></i><span class="notif-dot"></span>
            </button>
            <span class="divider"></span>
            <div class="dropdown user-dropdown">
                <button class="user-toggle" type="button" aria-haspopup="true">
                    <span class="avatar"><?= e($initials) ?><span class="status-dot"></span></span>
                    <span class="user-name"><span><?= e($user['full_name']) ?></span></span>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </button>
                <div class="dropdown-menu dropdown-right">
                    <a href="index.php"><i class="fa-regular fa-user"></i> User Dashboard</a>
                    <a href="user_account_settings.php"><i class="fa-regular fa-file-lines"></i> Account Settings</a>
                    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="breaking-news">
    <span class="breaking-label">NEWS</span>
    <p>Stay connected with the latest news from NSBM.</p>
</div>

<main class="container">
    <section class="page-heading">
        <div>
            <h1>Account Settings</h1>
            <p>Manage your profile, security and news preferences.</p>
        </div>
        <a href="index.php" class="back-button">← Back to Dashboard</a>
    </section>

    <?php if ($success !== ''): ?>
        <p class="settings-message" role="status"><?= e($success) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="settings-message settings-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <nav class="settings-tabs">
        <a href="#personal" class="tab active">Personal Information</a>
        <a href="#security" class="tab">Security</a>
        <a href="#preferences" class="tab">News Preferences</a>
        <a href="#notifications" class="tab">Notifications</a>
    </nav>

    <section class="settings-card" id="profile-photo">
        <div class="card-heading">
            <div class="heading-icon">◎</div>
            <div><h2>Profile Photo</h2><p>Upload or update the photo shown on your profile.</p></div>
        </div>
        <form action="user_account_settings.php" method="post" enctype="multipart/form-data" class="photo-form">
            <div class="photo-preview">
                <img src="<?= e($photoUrl) ?>" alt="Your current profile photo" class="settings-avatar" id="photoPreview">
            </div>
            <div class="photo-controls">
                <label for="profilePhoto">Choose a new photo</label>
                <input type="file" id="profilePhoto" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                <small>JPEG, PNG or WebP. Maximum file size: 2 MB.</small>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <button type="submit" name="action" value="upload_photo" class="save-button">Upload Photo</button>
                <?php if (!empty($user['profile_picture'])): ?>
                    <button type="submit" name="action" value="remove_photo" class="remove-button"
                            formnovalidate onclick="return confirm('Remove your profile photo?')">
                        Remove Photo
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <form action="user_account_settings.php" method="post" class="settings-form">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="save_settings">

        <section class="settings-card" id="personal">
            <div class="card-heading">
                <div class="heading-icon">♙</div>
                <div><h2>Personal Information</h2><p>Update your basic student account details.</p></div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" maxlength="150"
                           value="<?= e($form['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="studentId">Student ID</label>
                    <input type="text" id="studentId" value="<?= e($user['student_id'] ?: 'Not provided') ?>" readonly>
                    <small>Contact the administration to change your student ID.</small>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" maxlength="255"
                           value="<?= e($form['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="faculty">Faculty</label>
                    <select id="faculty" name="faculty" required>
                        <option value="">Select your faculty</option>
                        <?php foreach (array_keys($facultyDegrees) as $facultyName): ?>
                            <option value="<?= e($facultyName) ?>" <?= $form['faculty'] === $facultyName ? 'selected' : '' ?>>
                                <?= e($facultyName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label for="degree">Degree Programme</label>
                    <select id="degree" name="degree" required <?= isset($facultyDegrees[$form['faculty']]) ? '' : 'disabled' ?>>
                        <option value="">Select your faculty first</option>
                        <?php foreach ($facultyDegrees[$form['faculty']] ?? [] as $degreeName): ?>
                            <option value="<?= e($degreeName) ?>" <?= $form['degree'] === $degreeName ? 'selected' : '' ?>>
                                <?= e($degreeName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="batchYear">Batch Year</label>
                    <select id="batchYear" name="batchYear" required>
                        <option value="">Select your batch year</option>
                        <?php for ($year = (int) date('Y'); $year >= 2015; $year--): ?>
                            <option value="<?= $year ?>" <?= (string) $form['batch_year'] === (string) $year ? 'selected' : '' ?>>
                                <?= $year ?> Batch
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </section>

        <section class="settings-card" id="security">
            <div class="card-heading">
                <div class="heading-icon">♧</div>
                <div><h2>Change Password</h2><p>Use a strong password to protect your account.</p></div>
            </div>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword"
                           placeholder="Enter current password" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" minlength="8"
                           placeholder="Enter new password" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" minlength="8"
                           placeholder="Re-enter new password" autocomplete="new-password">
                </div>
            </div>
            <p class="help-text">Leave the password fields empty if you do not want to change your password.</p>
        </section>

        <section class="settings-card" id="preferences">
            <div class="card-heading">
                <div class="heading-icon">▤</div>
                <div><h2>News Preferences</h2><p>Select the categories you are interested in.</p></div>
            </div>
            <div class="preference-list">
                <?php foreach ($categoryLabels as $category => $label): ?>
                    <label class="preference-item">
                        <span><strong><?= e($label) ?></strong><small><?= e($categoryDescriptions[$category]) ?></small></span>
                        <input type="checkbox" name="categories[]" value="<?= e($category) ?>"
                               <?= in_array($category, $selectedCategories, true) ? 'checked' : '' ?>>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="settings-card" id="notifications">
            <div class="card-heading">
                <div class="heading-icon">♧</div>
                <div><h2>Notification Settings</h2><p>Choose which email updates you want to receive.</p></div>
            </div>
            <div class="preference-list">
                <label class="preference-item">
                    <span><strong>News Updates</strong><small>Receive updates about your selected categories.</small></span>
                    <input type="checkbox" name="newsNotifications" value="yes" <?= !empty($notifications['news_notifications']) ? 'checked' : '' ?>>
                </label>
                <label class="preference-item">
                    <span><strong>Article Submission Updates</strong><small>Know when your submitted articles are approved or rejected.</small></span>
                    <input type="checkbox" name="submissionNotifications" value="yes" <?= !empty($notifications['submission_notifications']) ? 'checked' : '' ?>>
                </label>
                <label class="preference-item">
                    <span><strong>Comment Notifications</strong><small>Receive updates about activity on your articles.</small></span>
                    <input type="checkbox" name="commentNotifications" value="yes" <?= !empty($notifications['comment_notifications']) ? 'checked' : '' ?>>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <a href="index.php" class="cancel-button">Cancel</a>
            <button type="submit" class="save-button">Save Changes</button>
        </div>
    </form>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col footer-brand">
            <a href="index.php" class="footer-logo">
                <img src="nsbm-logo.png" alt="NSBM Green University logo">
                <span class="footer-logo-text">
                    <span class="footer-logo-title">NSBM <span class="accent">NewsDesk</span></span>
                    <span class="footer-logo-sub">NSBM Green University</span>
                </span>
            </a>
            <p class="footer-desc">The official campus news, events, and club publication repository of NSBM Green University, Sri Lanka.</p>
        </div>
        <div class="footer-col footer-contact">
            <h4>Campus Contact</h4>
            <ul>
                <li><i class="fa-solid fa-location-dot"></i><span>NSBM Green University, Pitipana,<br>Homagama, Sri Lanka</span></li>
                <li><i class="fa-solid fa-envelope"></i><a href="mailto:newsdesk@nsbm.ac.lk">newsdesk@nsbm.ac.lk</a></li>
                <li><i class="fa-solid fa-phone"></i><a href="tel:+94115445000">+94 11 544 5000</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> NSBM NewsDesk &bull; NSBM Green University.</p>
        <div class="footer-legal"><a href="#">Privacy Policy</a><a href="#">Terms of Publication</a></div>
    </div>
</footer>
<script>
window.accountSettingsConfig = <?= json_encode(
    [
        'facultyDegrees' => $facultyDegrees,
        'selectedDegree' => $form['degree']
    ],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;
</script>
<script src="user_account_settings.js"></script>
</body>
</html>
