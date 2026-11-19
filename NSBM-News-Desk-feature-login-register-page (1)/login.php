<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post();
    verify_csrf();
    $email = strtolower(trim(posted_string('email')));
    $password = posted_string('password');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || strlen($password) > 72) {
        redirect('login.php?error=invalid');
    }

    try {
        $pdo = database();
        $query = $pdo->prepare(
            'SELECT id, name, email, password, role, account_status FROM users WHERE email = :email LIMIT 1'
        );
        $query->execute(['email' => $email]);
        $user = $query->fetch();
        if (!$user || $user['role'] !== 'student' || !password_verify($password, $user['password'])) {
            redirect('login.php?error=invalid');
        }

        if (($user['account_status'] ?? 'approved') === 'pending') {
            redirect('login.php?error=pending');
        }
        if (($user['account_status'] ?? 'approved') === 'rejected') {
            redirect('login.php?error=rejected');
        }

        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $update = $pdo->prepare('UPDATE users SET password = :hash WHERE id = :id');
            $update->execute([
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $user['id'],
            ]);
        }
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => 'student',
        ];
        redirect('../student-home.php');
    } catch (PDOException $exception) {
        error_log('NewsDesk login database error: ' . $exception->getMessage());
        redirect('login.php?error=unavailable');
    } catch (RuntimeException $exception) {
        error_log('NewsDesk login configuration error: ' . $exception->getMessage());
        redirect('login.php?error=unavailable');
    }
}

$notice = match ($_GET['error'] ?? '') {
    'invalid' => 'The email or password is incorrect.',
    'pending' => 'Your account is awaiting administrator approval. You can sign in once your NSBM student status is confirmed.',
    'rejected' => 'Your registration was not approved. Please contact the NewsDesk team at newsdesk@nsbm.ac.lk.',
    'unavailable' => 'Sign-in is temporarily unavailable. Please try again later.',
    default => '',
};
$noticeType = 'error';
if ($notice === '' && !empty($_SESSION['registration_pending'])) {
    unset($_SESSION['registration_pending']);
    $notice = 'Registration received. An administrator will confirm that you are an NSBM student. You can sign in once your account is approved.';
    $noticeType = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&family=Roboto+Mono&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0..1,0" rel="stylesheet">
<link rel="stylesheet" href="login.css?v=alerts-1">
<title>Login | NSBM NewsDesk</title>

<link rel="stylesheet" href="../app/icons.css?v=1">
</head>

<body class="theme-dark">

<main class="auth-hero login">
  <div class="login-shell">
    <aside class="login-feature" aria-label="Welcome to NSBM NewsDesk">
      <div class="feature-top"><span class="material-symbols-outlined">auto_stories</span> NSBM NEWSDESK</div>
      <div class="feature-art feature-video" aria-label="NSBM campus logo animation">
        <video class="mouse-scrub-video" autoplay loop muted playsinline preload="metadata" aria-label="Animated NSBM logo">
          <source src="videos/NSBM_Logo_Transparent.webm" type="video/webm">
        </video>
      </div>
      <div class="feature-copy"><span class="feature-kicker">YOUR CAMPUS. YOUR VOICE.</span>
        <h3>EXPLORE.<br>LEARN. GROW.</h3>
       
      </div>
    </aside>
    <section class="glass-card login-panel">
      <a class="login-back-home" href="../index.php">
        <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
        <span>Back to Home</span>
      </a>
      <div class="logo-tile"><span class="material-symbols-outlined">newspaper</span></div>
      <h1>Welcome Back</h1>
      <p class="sub">Sign in to access your bookmarks, submissions, and campus news feed.</p>
    <?php if ($notice !== ''): ?>
    <div class="login-alert login-alert--<?= e($noticeType) ?>" role="<?= $noticeType === 'error' ? 'alert' : 'status' ?>">
      <span class="material-symbols-outlined" aria-hidden="true"><?= $noticeType === 'error' ? 'error' : 'check_circle' ?></span>
      <span><?= e($notice) ?></span>
    </div>
    <?php endif; ?>
    <form id="loginForm" action="login.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="field">
        <label for="email">Outlook Email</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">mail</span>
          <input type="email" id="email" name="email" autocomplete="username" placeholder="e.g. username@students.nsbm.ac.lk" required>
        </div>
        <div class="error-msg"></div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">lock</span>
          <input type="password" id="password" name="password" maxlength="72" autocomplete="current-password" placeholder="*************" required>
          <button type="button" class="toggle-pass" data-target="password" aria-label="Show password">
            <span class="material-symbols-outlined">visibility</span>
          </button>
        </div>
        <div class="error-msg"></div>
      </div>

      <div class="row-between">
        <label class="check"><input type="checkbox" name="remember"> Remember me</label>
        <a href="forgot-password.php">Forgot password?</a>
      </div>

      <button type="submit" class="btn-primary">Sign In <span class="material-symbols-outlined">arrow_forward</span></button>
    </form>
      <p class="switch-line">Don't have an account? <a href="register.php">Register here</a></p>
      <p class="switch-line">Administrator? <a href="../admin-login.php">Admin Login</a></p>
    </section>
  </div>
</main>

<script src="js/login.js" defer></script>
</body>
</html>
