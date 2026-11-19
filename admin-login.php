<?php
declare(strict_types=1);
require_once __DIR__ . '/NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';
require_once __DIR__ . '/app/admin-config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post();
    verify_csrf();
    $identity = strtolower(trim(posted_string('identity')));
    $password = posted_string('password');
    try {
        if (admin_login_rate_limit() > 0) {
            redirect('admin-login.php?error=throttled');
        }
        $db = database();
        $admin = null;
        $authMethod = '';
        $passwordUsable = $password !== '' && strlen($password) <= 72;

        $username = trim(ADMIN_USERNAME);
        $adminEmail = strtolower(trim(ADMIN_EMAIL));
        $passwordHash = trim(ADMIN_PASSWORD_HASH);
        $envReady = $username !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)
            && $passwordHash !== '' && (password_get_info($passwordHash)['algoName'] ?? 'unknown') !== 'unknown';
        $isPrimaryIdentity = $envReady && (hash_equals(strtolower($username), $identity) || hash_equals($adminEmail, $identity));

        if ($isPrimaryIdentity) {
            // Primary administrator from the server configuration.
            if ($passwordUsable && password_verify($password, $passwordHash)) {
                $query = $db->prepare("SELECT id,name,email,role FROM users WHERE email=? AND role='admin' LIMIT 1");
                $query->execute([$adminEmail]);
                $row = $query->fetch();
                if ($row) {
                    $admin = $row;
                    $authMethod = 'env';
                }
            }
        } elseif ($passwordUsable && filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            // Other administrators created in Admin Management.
            $query = $db->prepare("SELECT id,name,email,password,role FROM users WHERE email=? AND role='admin' LIMIT 1");
            $query->execute([$identity]);
            $row = $query->fetch();
            if ($row && password_verify($password, (string) $row['password'])) {
                $admin = $row;
                $authMethod = 'db';
            }
        }

        if (!$admin) {
            admin_login_rate_limit(true);
            redirect('admin-login.php?error=invalid');
        }

        admin_login_rate_limit(false, true);
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        $now = time();
        $_SESSION['user'] = [
            'id' => (int) $admin['id'],
            'name' => (string) $admin['name'],
            'email' => (string) $admin['email'],
            'role' => 'admin',
            'auth_method' => $authMethod,
            'authenticated_at' => $now,
            'last_activity' => $now,
        ];
        redirect('admin.php');
    } catch (PDOException $exception) {
        error_log('NewsDesk admin login database error: ' . $exception->getMessage());
        redirect('admin-login.php?error=unavailable');
    } catch (RuntimeException $exception) {
        error_log('NewsDesk admin login configuration error: ' . $exception->getMessage());
        redirect('admin-login.php?error=unavailable');
    }
}
if (($_SESSION['user']['role'] ?? '') === 'admin' && admin_session_is_valid($_SESSION['user'])) {
    redirect('admin.php');
}

$notice = match ($_GET['error'] ?? '') {
    'invalid' => 'The admin username/email or password is incorrect.',
    'throttled' => 'Too many unsuccessful attempts. Try again in 15 minutes.',
    'setup' => 'Admin login setup is incomplete. Run tools/configure-admin-login.php from the project folder, then try again.',
    'unavailable' => 'Admin sign-in is temporarily unavailable. Please try again later.',
    default => '',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0..1,0" rel="stylesheet">
<link rel="stylesheet" href="NSBM-News-Desk-feature-login-register-page/login.css?v=alerts-1">
<title>Admin Login | NSBM NewsDesk</title>
<link rel="stylesheet" href="app/icons.css?v=1">
</head>
<body class="theme-dark">
<main class="auth-hero login">
  <div class="login-shell">
    <aside class="login-feature" aria-label="NSBM NewsDesk administration">
      <div class="feature-top"><span class="material-symbols-outlined">auto_stories</span> NSBM NEWSDESK</div>
      <div class="feature-art feature-video" aria-label="NSBM logo animation">
        <video class="mouse-scrub-video" autoplay loop muted playsinline preload="metadata" aria-label="Animated NSBM logo">
          <source src="NSBM-News-Desk-feature-login-register-page/videos/NSBM_Logo_Transparent.webm" type="video/webm">
        </video>
      </div>
      <div class="feature-copy"><span class="feature-kicker">ADMINISTRATION PORTAL</span>
        <h3>LEAD.<br>REVIEW. PUBLISH.</h3>
      </div>
    </aside>
    <section class="glass-card login-panel">
      <a class="login-back-home" href="index.php">
        <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
        <span>Back to Home</span>
      </a>
      <div class="logo-tile"><span class="material-symbols-outlined">admin_panel_settings</span></div>
      <h1>Admin Login</h1>
      <p class="sub">Sign in to manage NSBM NewsDesk.</p>
      <?php if ($notice !== ''): ?>
      <div class="login-alert login-alert--error" role="alert">
        <span class="material-symbols-outlined" aria-hidden="true">error</span>
        <span><?= e($notice) ?></span>
      </div>
      <?php endif; ?>
      <form id="adminLoginForm" action="admin-login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="field">
          <label for="identity">Email / Username</label>
          <div class="input-wrap">
            <span class="material-symbols-outlined">person</span>
            <input type="text" id="identity" name="identity" maxlength="254" autocomplete="username" placeholder="Enter your admin email or username" required>
          </div>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <span class="material-symbols-outlined">lock</span>
            <input type="password" id="password" name="password" maxlength="72" autocomplete="current-password" placeholder="Enter your password" required>
            <button type="button" class="toggle-pass" data-target="password" aria-label="Show password">
              <span class="material-symbols-outlined">visibility</span>
            </button>
          </div>
        </div>
        <button type="submit" class="btn-primary">Sign In <span class="material-symbols-outlined">arrow_forward</span></button>
      </form>
      <p class="switch-line">Student? <a href="NSBM-News-Desk-feature-login-register-page/login.php">Go to Student Login</a></p>
    </section>
  </div>
</main>
<script src="NSBM-News-Desk-feature-login-register-page/js/admin-login.js" defer></script>
</body>
</html>
