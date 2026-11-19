<?php
declare(strict_types=1);
function load_project_environment(): void
{
    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        error_log('NewsDesk could not read the project environment file.');
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!preg_match('/\A[A-Z_][A-Z0-9_]*\z/', $name)) {
            continue;
        }
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}
load_project_environment();
if (is_file(__DIR__ . '/../../app/local-config.php')) { require_once __DIR__ . '/../../app/local-config.php'; }

$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
session_start();

if (!function_exists("mb_strlen")) { function mb_strlen(string $s): int { return strlen($s); } }
if (!function_exists("mb_substr")) { function mb_substr(string $s,int $start,int $length): string { return substr($s,$start,$length); } }
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function posted_string(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? $value : '';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method not allowed.');
    }
}

function verify_csrf(): void
{
    $provided = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!is_string($provided) || !is_string($expected) || $expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(400);
        exit('Your session expired. Go back, refresh the page, and try again.');
    }
}

function redirect(string $path): void
{
    header('Location: ' . $path, true, 303);
    exit;
}

function require_user_role(string $role, string $loginPath): array
{
    $user = $_SESSION['user'] ?? null;
    if (!is_array($user) || !isset($user['role']) || !is_string($user['role'])) {
        redirect($loginPath);
    }

    if (!hash_equals($role, $user['role'])) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }

    return $user;
}

function admin_session_is_valid(array $user): bool
{
    $now = time();
    $authenticatedAt = (int) ($user['authenticated_at'] ?? 0);
    $lastActivity = (int) ($user['last_activity'] ?? 0);
    if (($user['role'] ?? '') !== 'admin' || !in_array(($user['auth_method'] ?? ''), ['env', 'db'], true)
        || $authenticatedAt < 1 || $lastActivity < 1
        || $now - $lastActivity > 1800 || $now - $authenticatedAt > 28800) {
        return false;
    }

    $_SESSION['user']['last_activity'] = $now;
    return true;
}

function require_admin(string $loginPath = 'admin-login.php'): array
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    $user = require_user_role('admin', $loginPath);
    if (!admin_session_is_valid($user)) {
        unset($_SESSION['user']);
        redirect($loginPath);
    }
    return $_SESSION['user'];
}

function admin_login_rate_limit(bool $recordFailure = false, bool $clear = false): int
{
    $address = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = hash('sha256', $address);
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nsbm-admin-login-' . $key . '.json';
    $file = @fopen($path, 'c+');
    if ($file === false) {
        throw new RuntimeException('Admin login rate limiting is unavailable.');
    }

    try {
        if (!flock($file, LOCK_EX)) {
            throw new RuntimeException('Admin login rate limiting is unavailable.');
        }
        rewind($file);
        $stored = json_decode((string) stream_get_contents($file), true);
        $now = time();
        $attempts = is_array($stored) ? array_values(array_filter(
            $stored,
            static fn ($value): bool => is_int($value) && $value > $now - 900
        )) : [];
        if ($clear) {
            $attempts = [];
        } elseif ($recordFailure) {
            $attempts[] = $now;
        }

        $remaining = count($attempts) >= 5 ? max(1, 900 - ($now - $attempts[0])) : 0;
        rewind($file);
        if (!ftruncate($file, 0) || fwrite($file, json_encode($attempts, JSON_THROW_ON_ERROR)) === false) {
            throw new RuntimeException('Admin login rate limiting is unavailable.');
        }
        fflush($file);
        flock($file, LOCK_UN);
        return $remaining;
    } finally {
        fclose($file);
    }
}

function is_nsbm_email(string $email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $domain = strtolower(substr(strrchr($email, '@'), 1));
    return preg_match('/(?:[a-z0-9-]+\.)*nsbm\.ac\.lk\z/', $domain) === 1;
}

function database(): PDO
{
    if (defined('NSBM_DATABASE_CONFIG')) {
        $config = NSBM_DATABASE_CONFIG;
        if (!is_array($config)) {
            throw new RuntimeException('Database configuration must be an array.');
        }
        $host = $config['host'] ?? false;
        $name = $config['name'] ?? false;
        $user = $config['user'] ?? false;
        $password = $config['password'] ?? false;
        $port = $config['port'] ?? '3306';
    } else {
        // Older separate constants (NSBM_DB_*) and plain environment variables still work.
        $host = defined('NSBM_DB_HOST') ? NSBM_DB_HOST : getenv('DB_HOST');
        $name = defined('NSBM_DB_NAME') ? NSBM_DB_NAME : getenv('DB_NAME');
        $user = defined('NSBM_DB_USER') ? NSBM_DB_USER : getenv('DB_USER');
        $password = defined('NSBM_DB_PASSWORD') ? NSBM_DB_PASSWORD : getenv('DB_PASSWORD');
        $port = defined('NSBM_DB_PORT') ? NSBM_DB_PORT : getenv('DB_PORT');
    }

    if (!is_string($host) || $host === '' || !is_string($name) || $name === ''
        || !is_string($user) || $user === '' || !is_string($password)) {
        throw new RuntimeException('Database environment variables are not configured.');
    }

    $port = !is_string($port) || $port === '' ? '3306' : $port;
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
