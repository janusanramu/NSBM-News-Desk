<?php
require_once __DIR__ . '/config/admin_auth.php';
require_once __DIR__ . '/config/admin_db.php';

$adminId = (int) $_SESSION['admin_id'];
$adminStmt = $pdo->prepare(
    'SELECT id, full_name, email, contact_number, role, email_notifications,
            theme, two_factor_enabled, profile_picture
     FROM admins
     WHERE id = ?
     LIMIT 1'
);
$adminStmt->execute([$adminId]);
$admin = $adminStmt->fetch();

if (!$admin) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

$success = '';
$error = '';
$formValues = [
    'full_name' => $admin['full_name'],
    'email' => $admin['email'],
    'contact_number' => $admin['contact_number'] ?? '',
    'email_notifications' => !empty($admin['email_notifications']),
    'theme' => $admin['theme'],
];
$storedPhoto = $admin['profile_picture'] ?? '';
$photoPathIsValid = is_string($storedPhoto)
    && preg_match('~\Aassets/admin-profile-photos/[A-Za-z0-9_-]+\.(?:jpg|png|webp)\z~i', $storedPhoto)
    && is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedPhoto));
$photoUrl = $photoPathIsValid ? $storedPhoto : 'assets/profile.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Your session token was invalid. Refresh the page and try again.';
    } else {
        $formAction = $_POST['form_action'] ?? '';

        if ($formAction === 'profile') {
            $fullName = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $contactNumber = $_POST['contact_number'] ?? '';
            $theme = $_POST['theme'] ?? '';

            $fullName = is_string($fullName) ? trim($fullName) : '';
            $email = is_string($email) ? trim($email) : '';
            $contactNumber = is_string($contactNumber) ? trim($contactNumber) : '';
            $theme = is_string($theme) ? $theme : '';

            $formValues = [
                'full_name' => $fullName,
                'email' => $email,
                'contact_number' => $contactNumber,
                'email_notifications' => isset($_POST['email_notifications']),
                'theme' => $theme,
            ];

            if (
                $fullName === ''
                || mb_strlen($fullName) > 120
                || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || mb_strlen($email) > 190
                || mb_strlen($contactNumber) > 30
                || !in_array($theme, ['light', 'dark'], true)
            ) {
                $error = 'Enter a valid name, email address, contact number, and theme.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $update = $pdo->prepare(
                        'UPDATE admins
                         SET full_name = ?, email = ?, contact_number = ?,
                             email_notifications = ?, theme = ?
                         WHERE id = ?'
                    );
                    $update->execute([
                        $fullName,
                        $email,
                        $contactNumber,
                        $formValues['email_notifications'] ? 1 : 0,
                        $theme,
                        $adminId,
                    ]);

                    $log = $pdo->prepare(
                        'INSERT INTO admin_activity_logs (admin_id, action, description)
                         VALUES (?, ?, ?)'
                    );
                    $log->execute([
                        $adminId,
                        'Account settings updated',
                        'Updated profile information or account preferences.',
                    ]);
                    $pdo->commit();
                    $success = 'Your account settings have been saved.';
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log($exception->getMessage());
                    $error = 'Could not save changes. Check whether the email address is already in use.';
                }
            }
        } elseif ($formAction === 'password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($confirmPassword)) {
                $error = 'Enter valid password values.';
            } elseif ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $error = 'Complete all password fields.';
            } elseif (strlen($newPassword) < 12) {
                $error = 'Your new password must be at least 12 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'The new password and confirmation do not match.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $passwordStmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
                    $passwordStmt->execute([$adminId]);
                    $passwordRow = $passwordStmt->fetch();

                    if (!$passwordRow || !password_verify($currentPassword, $passwordRow['password_hash'])) {
                        $pdo->rollBack();
                        $error = 'Your current password is incorrect.';
                    } else {
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $update = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
                        $update->execute([$passwordHash, $adminId]);

                        $log = $pdo->prepare(
                            'INSERT INTO admin_activity_logs (admin_id, action, description)
                             VALUES (?, ?, ?)'
                        );
                        $log->execute([
                            $adminId,
                            'Password changed',
                            'Changed the account password.',
                        ]);
                        $pdo->commit();
                        $success = 'Password changed successfully.';
                    }
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log($exception->getMessage());
                    $error = 'Could not change your password. Please try again.';
                }
            }
        } elseif ($formAction === 'upload_photo' || $formAction === 'remove_photo') {
            $storedPhoto = $admin['profile_picture'] ?? '';
            $oldPhotoPath = is_string($storedPhoto)
                && preg_match('~\Aassets/admin-profile-photos/[A-Za-z0-9_-]+\.(?:jpg|png|webp)\z~i', $storedPhoto)
                ? __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedPhoto)
                : null;

            if ($formAction === 'remove_photo') {
                try {
                    $pdo->beginTransaction();
                    $update = $pdo->prepare('UPDATE admins SET profile_picture = NULL WHERE id = ?');
                    $update->execute([$adminId]);
                    $log = $pdo->prepare(
                        'INSERT INTO admin_activity_logs (admin_id, action, description)
                         VALUES (?, ?, ?)'
                    );
                    $log->execute([$adminId, 'Profile photo removed', 'Removed the administrator profile photo.']);
                    $pdo->commit();
                    $success = 'Your profile photo has been removed.';

                    if ($oldPhotoPath !== null && is_file($oldPhotoPath) && !unlink($oldPhotoPath)) {
                        error_log('Could not remove the previous administrator profile photo.');
                    }
                } catch (PDOException $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log($exception->getMessage());
                    $error = 'Unable to remove your profile photo. Please try again.';
                }
            } else {
                $file = $_FILES['profile_photo'] ?? null;

                if (
                    !is_array($file)
                    || !isset($file['error'])
                    || !is_int($file['error'])
                    || $file['error'] !== UPLOAD_ERR_OK
                ) {
                    $error = 'Choose a photo to upload. The maximum file size is 2 MB.';
                } elseif (
                    !isset($file['size'])
                    || !is_int($file['size'])
                    || $file['size'] < 1
                    || $file['size'] > 2 * 1024 * 1024
                ) {
                    $error = 'The photo must be no larger than 2 MB.';
                } elseif (
                    !isset($file['tmp_name'])
                    || !is_string($file['tmp_name'])
                    || !is_uploaded_file($file['tmp_name'])
                ) {
                    $error = 'The uploaded photo could not be verified.';
                } else {
                    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    $allowedTypes = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ];
                    $imageInfo = @getimagesize($file['tmp_name']);

                    if (
                        !is_string($mimeType)
                        || !isset($allowedTypes[$mimeType])
                        || $imageInfo === false
                        || ($imageInfo['mime'] ?? '') !== $mimeType
                        || ($imageInfo[0] ?? 0) < 1
                        || ($imageInfo[1] ?? 0) < 1
                        || ($imageInfo[0] ?? 0) > 6000
                        || ($imageInfo[1] ?? 0) > 6000
                        || ($imageInfo[0] * $imageInfo[1]) > 25000000
                    ) {
                        $error = 'Choose a valid JPEG, PNG, or WebP image.';
                    } else {
                        $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'assets'
                            . DIRECTORY_SEPARATOR . 'admin-profile-photos';

                        if (
                            !is_dir($uploadDirectory)
                            && !mkdir($uploadDirectory, 0755, true)
                            && !is_dir($uploadDirectory)
                        ) {
                            $error = 'The photo upload directory could not be created.';
                        } else {
                            try {
                                $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                                $targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
                                $storedPath = 'assets/admin-profile-photos/' . $fileName;

                                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                                    $error = 'The photo could not be uploaded. Please try again.';
                                } else {
                                    try {
                                        $pdo->beginTransaction();
                                        $update = $pdo->prepare('UPDATE admins SET profile_picture = ? WHERE id = ?');
                                        $update->execute([$storedPath, $adminId]);
                                        $log = $pdo->prepare(
                                            'INSERT INTO admin_activity_logs (admin_id, action, description)
                                             VALUES (?, ?, ?)'
                                        );
                                        $log->execute([
                                            $adminId,
                                            'Profile photo updated',
                                            'Updated the administrator profile photo.',
                                        ]);
                                        $pdo->commit();
                                        $success = 'Your profile photo has been updated.';

                                        if ($oldPhotoPath !== null && is_file($oldPhotoPath) && !unlink($oldPhotoPath)) {
                                            error_log('Could not remove the previous administrator profile photo.');
                                        }
                                    } catch (PDOException $exception) {
                                        if ($pdo->inTransaction()) {
                                            $pdo->rollBack();
                                        }
                                        if (is_file($targetPath) && !unlink($targetPath)) {
                                            error_log('Could not remove an uploaded administrator photo after a database error.');
                                        }
                                        error_log($exception->getMessage());
                                        $error = 'Unable to save your profile photo. Please try again.';
                                    }
                                }
                            } catch (Throwable $exception) {
                                error_log($exception->getMessage());
                                $error = 'The photo could not be processed. Please try again.';
                            }
                        }
                    }
                }
            }
        } else {
            $error = 'The requested settings action is not valid.';
        }
    }

    $adminStmt->execute([$adminId]);
    $admin = $adminStmt->fetch();

    if (!$admin) {
        session_destroy();
        header('Location: admin_login.php');
        exit;
    }

    if ($success !== '' && ($_POST['form_action'] ?? '') === 'profile') {
        $formValues = [
            'full_name' => $admin['full_name'],
            'email' => $admin['email'],
            'contact_number' => $admin['contact_number'] ?? '',
            'email_notifications' => !empty($admin['email_notifications']),
            'theme' => $admin['theme'],
        ];
    }

    $storedPhoto = $admin['profile_picture'] ?? '';
    $photoPathIsValid = is_string($storedPhoto)
        && preg_match('~\Aassets/admin-profile-photos/[A-Za-z0-9_-]+\.(?:jpg|png|webp)\z~i', $storedPhoto)
        && is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedPhoto));
    $photoUrl = $photoPathIsValid ? $storedPhoto : 'assets/profile.png';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#00563f">
    <title>Admin Account Settings | NSBM NewsDesk</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin_profile.css">
    <script src="admin_profile_photo.js" defer></script>
</head>
<body>
<main class="dashboard admin-profile admin-settings">
    <header class="settings-banner">
        <span class="settings-eyebrow">ADMINISTRATION</span>
        <h1>Account Settings</h1>
        <p>Manage your personal information, security and preferences.</p>
    </header>

    <nav class="tabs" aria-label="Administrator navigation">
        <a class="tab" href="admin_profile.php">
            <span class="tab-icon">▦</span> Profile Overview
        </a>
        <a class="tab active" aria-current="page" href="admin_account_settings.php">
            <span class="tab-icon">⚙</span> Account Settings
        </a>
        <a class="tab" href="index.php">
            <span class="tab-icon">▤</span> Dashboard
        </a>
    </nav>

    <div class="settings-stack">
        <?php if ($success !== ''): ?>
            <p class="settings-message is-success" role="status"><?= e($success) ?></p>
        <?php elseif ($error !== ''): ?>
            <p class="settings-message is-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <section class="card settings-card photo-settings-card">
            <header class="settings-card-heading">
                <h2>Profile Photo</h2>
                <p>Upload a JPEG, PNG, or WebP image (maximum 2 MB).</p>
            </header>
            <div class="photo-settings-content">
                <img class="settings-photo-preview" src="<?= e($photoUrl) ?>" alt="Current admin profile photo" id="profilePhotoPreview">
                <div class="photo-settings-controls">
                    <form method="post" enctype="multipart/form-data" class="photo-upload-form">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="form_action" value="upload_photo">
                        <label class="profile-link photo-choose-button" for="profile_photo">Choose photo</label>
                        <input id="profile_photo" class="photo-file-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" aria-describedby="photoFileName photoUploadFeedback" required>
                        <span class="photo-file-name" id="photoFileName">No file selected</span>
                        <button class="profile-link primary" type="submit">Upload Photo</button>
                    </form>
                    <?php if ($photoUrl !== 'assets/profile.png'): ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="form_action" value="remove_photo">
                            <button class="profile-link photo-remove-button" type="submit">Remove Photo</button>
                        </form>
                    <?php endif; ?>
                    <p class="photo-upload-feedback" id="photoUploadFeedback" role="status" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <form method="post" class="card settings-card">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="form_action" value="profile">
            <header class="settings-card-heading">
                <h2>Personal Information</h2>
                <p>Update your administrator contact details.</p>
            </header>
            <div class="settings-form-grid">
                <div class="settings-field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" type="text" maxlength="120" value="<?= e($formValues['full_name']) ?>" autocomplete="name" required>
                </div>
                <div class="settings-field">
                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" maxlength="190" value="<?= e($formValues['email']) ?>" autocomplete="email" required>
                </div>
                <div class="settings-field">
                    <label for="contact_number">Contact number</label>
                    <input id="contact_number" name="contact_number" type="tel" maxlength="30" value="<?= e($formValues['contact_number']) ?>" placeholder="Enter contact number" autocomplete="tel">
                </div>
                <div class="settings-field">
                    <label for="role">Role</label>
                    <input id="role" type="text" value="<?= e(ucfirst($admin['role'])) ?>" disabled>
                    <small>Your role can only be changed by an authorized system administrator.</small>
                </div>
            </div>

            <div class="settings-preferences">
                <header class="settings-card-heading">
                    <h2>Account Preferences</h2>
                    <p>Choose how you want to use NewsDesk.</p>
                </header>
                <label class="settings-preference" for="email_notifications">
                    <span>
                        <strong>Email notifications</strong>
                        <small>Receive important admin updates by email.</small>
                    </span>
                    <input id="email_notifications" name="email_notifications" type="checkbox" value="1" <?= $formValues['email_notifications'] ? 'checked' : '' ?>>
                </label>
                <div class="settings-field settings-theme-field">
                    <label for="theme">Interface theme</label>
                    <select id="theme" name="theme">
                        <option value="light" <?= $formValues['theme'] === 'light' ? 'selected' : '' ?>>Light</option>
                        <option value="dark" <?= $formValues['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option>
                    </select>
                    <small>The preference is saved; applying a full dark theme requires additional CSS.</small>
                </div>
            </div>

            <div class="settings-actions">
                <a class="profile-link" href="admin_profile.php">Cancel</a>
                <button class="profile-link primary" type="submit">Save Changes</button>
            </div>
        </form>

        <section class="card settings-card">
            <header class="settings-card-heading">
                <h2>Security</h2>
                <p>Change your password to help protect your account.</p>
            </header>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="form_action" value="password">
                <div class="settings-form-grid">
                    <div class="settings-field">
                        <label for="current_password">Current password</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="settings-field">
                        <label for="new_password">New password</label>
                        <input id="new_password" name="new_password" type="password" minlength="12" autocomplete="new-password" required>
                        <small>Use at least 12 characters.</small>
                    </div>
                    <div class="settings-field">
                        <label for="confirm_password">Confirm new password</label>
                        <input id="confirm_password" name="confirm_password" type="password" minlength="12" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="settings-actions">
                    <button class="profile-link primary" type="submit">Change Password</button>
                </div>
            </form>

            <div class="settings-security-status">
                <span>
                    <strong>Two-factor authentication (2FA)</strong>
                    <small>Status: <?= !empty($admin['two_factor_enabled']) ? 'Enabled' : 'Not enabled' ?>. 2FA changes require a verified authenticator or one-time-code flow.</small>
                </span>
                <span class="security-indicator <?= !empty($admin['two_factor_enabled']) ? 'is-enabled' : '' ?>">
                    <?= !empty($admin['two_factor_enabled']) ? 'Enabled' : 'Not enabled' ?>
                </span>
            </div>
        </section>
    </div>
</main>
</body>
</html>
