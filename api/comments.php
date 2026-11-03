<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestArticle(): string
{
    $article = $_GET['article'] ?? '';
    if (!is_string($article) || !preg_match('/\A[a-z]+\z/', $article)) {
        respond(400, ['error' => 'A valid article slug is required.']);
    }

    return $article;
}

try {
    require_once __DIR__ . '/db.php';
    $pdo = database();
    $method = $_SERVER['REQUEST_METHOD'] ?? '';

    if ($method !== 'GET' && $method !== 'POST') {
        header('Allow: GET, POST');
        respond(405, ['error' => 'Method not allowed.']);
    }

    $article = requestArticle();
    $articleQuery = $pdo->prepare('SELECT id FROM articles WHERE slug = :slug');
    $articleQuery->execute(['slug' => $article]);
    $articleId = $articleQuery->fetchColumn();

    if ($articleId === false) {
        respond(404, ['error' => 'Article not found.']);
    }

    if ($method === 'GET') {
        $query = $pdo->prepare(
            'SELECT id, author_name, body, created_at
             FROM comments
             WHERE article_id = :article_id
             ORDER BY id DESC
             LIMIT 100'
        );
        $query->execute(['article_id' => $articleId]);
        respond(200, ['comments' => $query->fetchAll()]);
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== 0) {
        respond(415, ['error' => 'Content-Type must be application/json.']);
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    $body = is_array($payload) ? ($payload['body'] ?? null) : null;
    if (!is_string($body)) {
        respond(400, ['error' => 'Comment text is required.']);
    }

    $body = trim($body);
    $characterCount = preg_match_all('/./us', $body);
    if ($body === '' || $characterCount === false || $characterCount > 2000) {
        respond(422, ['error' => 'Comments must contain 1 to 2000 characters.']);
    }

    $insert = $pdo->prepare(
        'INSERT INTO comments (article_id, author_name, body)
         VALUES (:article_id, :author_name, :body)'
    );
    $insert->execute([
        'article_id' => $articleId,
        'author_name' => 'You',
        'body' => $body,
    ]);

    $query = $pdo->prepare(
        'SELECT id, author_name, body, created_at FROM comments WHERE id = :id'
    );
    $query->execute(['id' => $pdo->lastInsertId()]);
    respond(201, ['comment' => $query->fetch()]);
} catch (Throwable $error) {
    error_log($error->__toString());
    respond(500, ['error' => 'The comment service is temporarily unavailable.']);
}
