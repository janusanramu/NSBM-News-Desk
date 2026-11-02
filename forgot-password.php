<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post();
    verify_csrf();
    $email = strtolower(trim(posted_string('email')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect('forgot-password.php?status=sent');
    }

    $baseUrl = rtrim((string) getenv('APP_BASE_URL'), '/');
    $mailFrom = (string) getenv('MAIL_FROM');
    $urlParts = parse_url($baseUrl);
    $isLocalHttp = is_array($urlParts)
        && ($urlParts['scheme'] ?? '') === 'http'
        && in_array(strtolower((string) ($urlParts['host'] ?? '')), ['localhost', '127.0.0.1', '::1'], true);
    if (
        !filter_var($baseUrl, FILTER_VALIDATE_URL) ||
        !is_array($urlParts) ||
        (($urlParts['scheme'] ?? '') !== 'https' && !$isLocalHttp) ||
        empty($urlParts['host']) ||
        isset($urlParts['query']) || isset($urlParts['fragment']) ||
        !filter_var($mailFrom, FILTER_VALIDATE_EMAIL) ||
        !function_exists('mail')
    ) {
        error_log('NewsDesk password reset requires a valid APP_BASE_URL and MAIL_FROM.');
        redirect('forgot-password.php?error=unavailable');
    }

    try {
        $pdo = database();
        $pdo->exec('DELETE FROM password_reset_tokens WHERE expires_at <= CURRENT_TIMESTAMP');
        $query = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $query->execute(['email' => $email]);
        $user = $query->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id')
                ->execute(['user_id' => $user['id']]);
            $insert = $pdo->prepare(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                 VALUES (:user_id, :token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR))'
            );
            $insert->execute(['user_id' => $user['id'], 'token_hash' => $tokenHash]);
            $resetUrl = $baseUrl . '/reset-password.php?token=' . rawurlencode($token);
            $message = "Use this link within one hour to reset your NSBM NewsDesk password:\n\n"
                . $resetUrl . "\n\nIf you did not request this, ignore this email.";
            $headers = 'From: ' . $mailFrom . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            if (!mail($email, 'Reset your NSBM NewsDesk password', $message, $headers)) {
                $pdo->prepare('DELETE FROM password_reset_tokens WHERE token_hash = :token_hash')
                    ->execute(['token_hash' => $tokenHash]);
                error_log('NewsDesk password reset email could not be sent.');
                redirect('forgot-password.php?error=unavailable');
            }
        }
        redirect('forgot-password.php?status=sent');
    } catch (PDOException $exception) {
        error_log('NewsDesk password reset database error: ' . $exception->getMessage());
        redirect('forgot-password.php?error=unavailable');
    } catch (RuntimeException $exception) {
        error_log('NewsDesk password reset configuration error: ' . $exception->getMessage());
        redirect('forgot-password.php?error=unavailable');
    }
}

$notice = match ($_GET['error'] ?? '') {
    'unavailable' => 'Password reset is temporarily unavailable. Please try again later.',
    default => (($_GET['status'] ?? '') === 'sent'
        ? 'If an account exists for that email, password reset instructions have been sent.'
        : ''),
};
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
<link rel="stylesheet" href="forgot-password.css">
<title>Forgot Password | NSBM NewsDesk</title>

<style>
/* Forgot password page: scoped styles, existing header/footer untouched */
.forgot-hero .login-shell{width:100%;}
.forgot-hero .forgot-feature{position:relative;overflow:hidden;min-height:490px;}
.forgot-hero .forgot-art{position:relative;display:grid;place-items:center;min-height:230px;margin:16px auto 8px;isolation:isolate;}
.forgot-hero .forgot-art-ring{width:172px;height:172px;display:grid;place-items:center;border-radius:50%;background:linear-gradient(145deg,#2ec58c,#075d42);border:1px solid rgba(255,255,255,.35);box-shadow:0 0 0 18px rgba(83,238,166,.08),0 20px 55px rgba(0,0,0,.22);}
.forgot-hero .forgot-art-ring .material-symbols-outlined{font-size:86px;color:#fff;}
.forgot-hero .forgot-orbit{position:absolute;width:12px;height:12px;background:#8bf6bf;border-radius:50%;box-shadow:0 0 22px #76edaa;}
.forgot-hero .orbit-one{top:14%;left:22%;}.forgot-hero .orbit-two{bottom:15%;right:21%;width:8px;height:8px;}
.forgot-hero .forgot-panel{display:flex;flex-direction:column;justify-content:center;}
.forgot-hero .forgot-helper{font-size:12px;line-height:1.6;margin-top:9px;color:#587467;}
.forgot-hero .switch-line a{display:inline-flex;align-items:center;justify-content:center;gap:6px;}
.forgot-hero .switch-line .material-symbols-outlined{font-size:18px;}
@media(max-width:700px){.forgot-hero .forgot-feature{min-height:350px}.forgot-hero .forgot-art{min-height:170px}.forgot-hero .forgot-art-ring{width:125px;height:125px}.forgot-hero .forgot-art-ring .material-symbols-outlined{font-size:62px}}
</style>
</head>

<body class="theme-dark">

<header class="site-header">
  <a href="index.html" class="brand">
    <img src="images/logo.png" alt="NSBM Green University">
    <span>NSBM NewsDesk</span>
  </a>
  <nav class="main-nav">
    <a href="index.html">Home</a>
    <a href="news.html">News</a>
    <a href="submit-article.html">Submit Article</a>
    <a href="bookmarks.html">My Bookmarks</a>
    <a href="submissions.html">My Submissions</a>
    <a href="admin.html">Admin Dashboard</a>
  </nav>
  <div class="search-box">
    <span class="material-symbols-outlined">search</span>
    <input type="text" placeholder="Search news...">
  </div>
  <button type="button" class="theme-toggle" aria-label="Switch to light theme" title="Toggle theme"><span class="material-symbols-outlined">light_mode</span></button>
  <button type="button" class="menu-toggle" aria-label="Open navigation" aria-expanded="false"><span class="material-symbols-outlined">menu</span></button>
  <a href="login.php" class="avatar-btn" aria-label="Account">
    <span class="material-symbols-outlined">person</span>
  </a>
</header>

<div class="ticker">
  <span class="badge">BREAKING</span>
  <div class="ticker-track">
    <div class="ticker-inner" id="tickerInner"></div>
  </div>
</div>

<main class="auth-hero login forgot-hero">
  <div class="login-shell">
    <aside class="login-feature forgot-feature" aria-label="Account recovery">
      <div class="feature-top"><span class="material-symbols-outlined">auto_stories</span> NSBM NEWSDESK</div>
      <div class="forgot-art" aria-hidden="true">
        <div class="forgot-art-ring"><span class="material-symbols-outlined">lock_reset</span></div>
        <span class="forgot-orbit orbit-one"></span><span class="forgot-orbit orbit-two"></span>
      </div>
      <div class="feature-copy">
        <span class="feature-kicker">YOUR CAMPUS. YOUR VOICE.</span>
        <h2>STAY<br>CONNECTED.</h2>
        <p>Get back to your campus stories, bookmarks, and community updates.</p>
      </div>
    </aside>
    <section class="glass-card login-panel forgot-panel">
      <div class="logo-tile"><span class="material-symbols-outlined">lock_reset</span></div>
      <h1>Forgot Password?</h1>
      <p class="sub">Enter your NSBM account email address and we'll help you reset your password.</p>
      <?php if ($notice !== ''): ?><p role="status" style="color:#b2f8cf;margin:0 0 16px"><?= e($notice) ?></p><?php endif; ?>
      <form id="forgotPasswordForm" action="forgot-password.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="field">
          <label for="email">Outlook Email</label>
          <div class="input-wrap">
            <span class="material-symbols-outlined">mail</span>
            <input type="email" id="email" name="email" autocomplete="email" placeholder="e.g. name@students.nsbm.ac.lk" required>
          </div>
          <p class="forgot-helper">Use the email address linked to your NSBM NewsDesk account.</p>
          <div class="error-msg" id="forgotEmailError" role="alert"></div>
        </div>
        <button type="submit" class="btn-primary">Send Reset Link <span class="material-symbols-outlined">arrow_forward</span></button>
      <p id="forgotStatus" class="forgot-status" role="status" aria-live="polite"></p>
      </form>
      <p class="switch-line"><a href="login.php"><span class="material-symbols-outlined">arrow_back</span> Back to Login</a></p>
    </section>
  </div>
</main>

<footer class="site-footer">
  <div class="footer-grid">
    <div>
      <h3>NSBM NewsDesk</h3>
      <p>The official campus news and club-post portal for NSBM Green University, Sri Lanka. Keeping the academic community connected and informed.</p>
    </div>
    <div>
      <h3>Quick Links</h3>
      <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="news.html">Latest News</a></li>
        <li><a href="submit-article.html">Submit an Article</a></li>
        <li><a href="bookmarks.html">Bookmarks</a></li>
      </ul>
    </div>
    <div class="footer-contact">
      <h3>Contact</h3>
      <p>NSBM Green University, Pitipana, Homagama, Sri Lanka</p>
      <p>Email: newsdesk@nsbm.ac.lk</p>
    </div>
  </div>
  <div class="footer-bottom">&copy; 2026 NSBM NewsDesk. All rights reserved.</div>
</footer>

<script src="js/script.js" defer></script>
<script src="js/forgot-password.js" defer></script>
<script src="js/illustration-motion.js" defer></script>
</body>
</html>
