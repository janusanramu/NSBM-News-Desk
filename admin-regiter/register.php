<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid request.']);
    exit;
}

$fullName = trim($input['fullName'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$adminId = trim($input['adminId'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';
$terms = $input['terms'] ?? false;

if ($fullName === '' || $email === '' || $adminId === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['message' => 'Enter a valid email address.']);
    exit;
}

if (strlen($password) < 8 || $password !== $confirmPassword) {
    http_response_code(422);
    echo json_encode(['message' => 'Passwords must match and contain at least 8 characters.']);
    exit;
}

if (!$terms) {
    http_response_code(422);
    echo json_encode(['message' => 'Please accept the terms and conditions.']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $statement = $pdo->prepare(
        'INSERT INTO admins (full_name, email, admin_id, password_hash) VALUES (:full_name, :email, :admin_id, :password_hash)'
    );
    $statement->execute([
        'full_name' => $fullName,
        'email' => $email,
        'admin_id' => $adminId,
        'password_hash' => $passwordHash,
    ]);

    $_SESSION['admin_id'] = $pdo->lastInsertId();
    $_SESSION['admin_name'] = $fullName;
    $_SESSION['admin_email'] = $email;

    http_response_code(201);
    echo json_encode(['message' => 'Admin account created successfully.']);
} catch (PDOException $exception) {
    if ((int) $exception->getCode() === 23000) {
        http_response_code(409);
        echo json_encode(['message' => 'Email address or Admin ID is already registered.']);
        exit;
    }

    error_log('Admin registration failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Registration could not be completed.']);
}
