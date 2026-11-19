<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}
require_once __DIR__ . '/../NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';

function ask(string $prompt): string
{
    fwrite(STDOUT, $prompt);
    return trim((string) fgets(STDIN));
}

function ask_secret(): string
{
    if (PHP_OS_FAMILY !== 'Windows') {
        fwrite(STDERR, "Run this setup from Windows PowerShell to enter the password securely.\n");
        exit(1);
    }
    $command = '$secure = Read-Host "Admin login password (12+ characters)" -AsSecureString; '
        . '$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure); '
        . 'try { [Console]::Out.Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)) } '
        . 'finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }';
    $process = proc_open(
        ['powershell.exe', '-NoProfile', '-Command', $command],
        [0 => STDIN, 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start the hidden password prompt.');
    }
    $password = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0 || !is_string($password) || $password === '') {
        throw new RuntimeException(trim((string) $error) ?: 'The hidden password prompt failed.');
    }
    return $password;
}

try {
    $db = database();
    $username = ask('Admin username [admin]: ');
    $username = $username === '' ? 'admin' : $username;
    $email = strtolower(ask('Admin NSBM email: '));
    if (!preg_match('/\A[A-Za-z0-9._@+-]{3,64}\z/', $username) || !is_nsbm_email($email)) {
        throw new RuntimeException('Enter a valid username and NSBM admin email.');
    }
    $name = ask('Admin full name [NSBM NewsDesk Administrator]: ');
    $name = $name === '' ? 'NSBM NewsDesk Administrator' : $name;
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        throw new RuntimeException('Enter a valid admin name (2–100 characters).');
    }
    $password = ask_secret();
    if (strlen($password) < 12 || strlen($password) > 72) {
        throw new RuntimeException('The admin login password must be 12–72 characters.');
    }

    $db->beginTransaction();
    $query = $db->prepare('SELECT id,role FROM users WHERE email=? LIMIT 1');
    $query->execute([$email]);
    $account = $query->fetch();
    if ($account && $account['role'] !== 'admin') {
        throw new RuntimeException('That email belongs to a non-admin account. No account or role was changed; use a different admin email.');
    }
    if (!$account) {
        $query = $db->prepare("INSERT INTO users (name,email,password,role,faculty,terms_accepted_at) VALUES (?,?,?,'admin','Staff',NOW())");
        $query->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    }

    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    $existing = is_file($path) ? file_get_contents($path) : '';
    if ($existing === false) {
        throw new RuntimeException('Could not read the existing environment file.');
    }
    $lines = preg_split('/\r\n|\n|\r/', $existing) ?: [];
    $lines = array_values(array_filter($lines, static function (string $line): bool {
        return preg_match('/^\s*ADMIN_(?:USERNAME|EMAIL|PASSWORD_HASH)\s*=/', $line) !== 1;
    }));
    $lines[] = 'ADMIN_USERNAME=' . $username;
    $lines[] = 'ADMIN_EMAIL=' . $email;
    $lines[] = 'ADMIN_PASSWORD_HASH=' . password_hash($password, PASSWORD_DEFAULT);
    $contents = implode(PHP_EOL, $lines) . PHP_EOL;
    if (file_put_contents($path, $contents, LOCK_EX) === false) {
        throw new RuntimeException('Could not write the project .env file.');
    }
    if (DIRECTORY_SEPARATOR !== '\\' && !chmod($path, 0600)) {
        error_log('NewsDesk could not restrict .env file permissions.');
    }
    $db->commit();
    fwrite(STDOUT, "Admin login configured. The plain password was not saved; sign in at /admin-login.php.\n");
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
