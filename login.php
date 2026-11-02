<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/user_db.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedEmail = $_POST['email'] ?? '';
    $email = is_string($submittedEmail) ? trim($submittedEmail) : '';
    $password = $_POST['password'] ?? '';

    if ($email === '' || !is_string($password) || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, password_hash
             FROM users
             WHERE email = ?
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: index.php');
            exit;
        }

        $error = 'Invalid email or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#00563f">
    <title>User Login | NSBM NewsDesk</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin_profile.css">
</head>
<body>
<main class="dashboard admin-settings">
    <header class="settings-banner">
        <span class="settings-eyebrow">NSBM NEWSDESK</span>
        <h1>User Login</h1>
        <p>Sign in to view your dashboard and account settings.</p>
    </header>
    <section class="card settings-card login-card">
        <?php if ($error !== ''): ?>
            <p class="settings-message is-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form method="post" class="settings-form-grid">
            <div class="settings-field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" maxlength="255" autocomplete="username" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="settings-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <div class="settings-actions">
                <button class="profile-link primary" type="submit">Sign in</button>
            </div>
        </form>
        <p class="settings-preview-note"><a href="admin_login.php">Administrator sign in</a></p>
    </section>
</main>
</body>
</html>
