<?php
declare(strict_types=1);

function database(): PDO
{
    $host = getenv('NSBM_DB_HOST') ?: '127.0.0.1';
    $name = getenv('NSBM_DB_NAME') ?: 'nsbm_newsdesk';
    $user = getenv('NSBM_DB_USER') ?: 'root';
    $password = getenv('NSBM_DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
