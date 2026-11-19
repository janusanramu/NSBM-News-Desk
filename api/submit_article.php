<?php

header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
    exit;
}

$allowedStatuses = ['Draft', 'Pending Review', 'Published'];
$status = trim((string) ($_POST['status'] ?? 'Pending Review'));
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'Pending Review';
}

$title = trim((string) ($_POST['articleTitle'] ?? ''));
$category = trim((string) ($_POST['category'] ?? ''));
$author = trim((string) ($_POST['author'] ?? ''));
$summary = trim((string) ($_POST['summary'] ?? ''));
$content = trim((string) ($_POST['content'] ?? ''));

if ($title === '' || $category === '' || $author === '' || $summary === '' || $content === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Please complete all required fields before submitting.'
    ]);
    exit;
}

$coverImageName = null;
if (isset($_FILES['coverImage']) && $_FILES['coverImage']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['coverImage'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowedTypes, true)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please upload a JPG, PNG, or WEBP image.'
        ]);
        exit;
    }

    if ((int) $file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Image size must be less than 5MB.'
        ]);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/articles';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = 'article_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($extension);
    $targetPath = $uploadDir . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to upload the cover image.'
        ]);
        exit;
    }

    $coverImageName = 'uploads/articles/' . $safeName;
}

try {
    $pdo = getDbConnection();

    $sql = "INSERT INTO articles (title, category, author_name, summary, content, cover_image, status, created_at, updated_at)
            VALUES (:title, :category, :author_name, :summary, :content, :cover_image, :status, NOW(), NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':category' => $category,
        ':author_name' => $author,
        ':summary' => $summary,
        ':content' => $content,
        ':cover_image' => $coverImageName,
        ':status' => $status,
    ]);

    $message = $status === 'Draft'
        ? 'Draft saved successfully!'
        : 'Article submitted successfully for review!';

    echo json_encode([
        'success' => true,
        'message' => $message,
        'status' => $status,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to save article. Please try again.'
    ]);
}
