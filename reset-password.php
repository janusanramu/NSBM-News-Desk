<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

$token = posted_string('token');
if ($token === '') {
    $queryToken = $_GET['token'] ?? '';
    $token = is_string($queryToken) ? $queryToken : '';
}
$validToken = preg_match('/\A[0-9a-f]{64}\z/', $token) === 1;
$status = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post();
    verify_csrf();
    $password = posted_string('password');
    $confirm = posted_string('confirm');

    if (!$validToken) {
        $status = 'invalid';
    } elseif (strlen($password) < 8 || strlen($password) > 72 || $password !== $confirm) {
        $status = 'password';
    } else {
        try {
            $pdo = database();
            $pdo->beginTransaction();
            $query = $pdo->prepare(
                'SELECT user_id FROM password_reset_tokens
                 WHERE token_hash = :token_hash AND expires_at > CURRENT_TIMESTAMP
                 LIMIT 1 FOR UPDATE'
            );
            $query->execute(['token_hash' => hash('sha256', $token)]);
            $reset = $query->fetch();
            if (!$reset) {
                $pdo->rollBack();
                $status = 'invalid';
            } else {
                $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :user_id');
                $update->execute([
                    'hash' => password_hash($password, PASSWORD_DEFAULT),
                    'user_id' => $reset['user_id'],
                ]);
                $delete = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
                $delete->execute(['user_id' => $reset['user_id']]);
                $pdo->commit();
                $status = 'success';
            }
        } catch (PDOException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('NewsDesk password update database error: ' . $exception->getMessage());
            $status = 'unavailable';
        } catch (RuntimeException $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('NewsDesk password update configuration error: ' . $exception->getMessage());
            $status = 'unavailable';
        }
    }
} elseif (!$validToken) {
    $status = 'invalid';
} else {
    try {
        $query = database()->prepare(
            'SELECT id FROM password_reset_tokens
             WHERE token_hash = :token_hash AND expires_at > CURRENT_TIMESTAMP LIMIT 1'
        );
        $query->execute(['token_hash' => hash('sha256', $token)]);
        if (!$query->fetch()) {
            $status = 'invalid';
        }
    } catch (PDOException $exception) {
        error_log('NewsDesk password reset validation database error: ' . $exception->getMessage());
        $status = 'unavailable';
    } catch (RuntimeException $exception) {
        error_log('NewsDesk password reset validation configuration error: ' . $exception->getMessage());
        $status = 'unavailable';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password | NSBM NewsDesk</title>
  <link rel="stylesheet" href="register.css">
</head>
<body class="theme-dark">
<main class="auth-hero register">
  <section class="glass-card login-panel" style="max-width:560px;margin:10vh auto;padding:32px">
    <h1>Reset your password</h1>
    <?php if ($status === 'success'): ?>
      <p role="status">Your password has been updated. <a href="login.php">Sign in</a></p>
    <?php elseif ($status === 'invalid'): ?>
      <p role="alert">This reset link is invalid or has expired. <a href="forgot-password.php">Request another link</a>.</p>
    <?php elseif ($status === 'unavailable'): ?>
      <p role="alert">Password reset is temporarily unavailable. Please try again later.</p>
    <?php else: ?>
      <form method="POST" action="reset-password.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field">
          <label for="password">New password</label>
          <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        </div>
        <div class="field">
          <label for="confirm">Confirm new password</label>
          <input id="confirm" name="confirm" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        </div>
        <?php if ($status === 'password'): ?><p role="alert">Enter matching passwords between 8 and 72 characters.</p><?php endif; ?>
        <button type="submit" class="btn-primary">Update password</button>
      </form>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
