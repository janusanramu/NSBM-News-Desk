<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed']);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload) || !is_string($payload['id'] ?? null)) {
    respond(400, ['success' => false, 'message' => 'Invalid request']);
}

$sessionToken = $_SESSION['csrf_token'] ?? '';
$requestToken = $payload['csrfToken'] ?? '';
if (!is_string($requestToken) || $sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
    respond(403, ['success' => false, 'message' => 'Invalid request token']);
}

try {
    require __DIR__ . '/database.php';
    $statement = $pdo->prepare('DELETE FROM bookmarks WHERE id = :id');
    $statement->execute(['id' => $payload['id']]);
    if ($statement->rowCount() !== 1) {
        respond(404, ['success' => false, 'message' => 'Bookmark not found']);
    }
} catch (PDOException $exception) {
    respond(500, ['success' => false, 'message' => 'Database operation failed']);
}

respond(200, ['success' => true]);