<?php
declare(strict_types=1);

$localConfigPath = __DIR__ . '/database.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
if (!is_array($localConfig)) {
    throw new RuntimeException('database.local.php must return a configuration array.');
}

$host = $localConfig['host'] ?? (getenv('NSBM_DB_HOST') ?: '127.0.0.1');
$database = $localConfig['database'] ?? (getenv('NSBM_DB_NAME') ?: 'nsbm_newsdesk');
$username = $localConfig['username'] ?? (getenv('NSBM_DB_USER') ?: 'root');
$password = $localConfig['password'] ?? getenv('NSBM_DB_PASSWORD');

if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
    throw new RuntimeException('Invalid NSBM_DB_NAME value.');
}

$dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

return new PDO($dsn, $username, $password === false ? '' : $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
