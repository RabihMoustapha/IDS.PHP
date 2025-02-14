<?php
header('Content-Type: application/json');
include '../db.php';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        handleCreate($pdo, $input);
        break;
    default:
        echo json_encode(['message' => 'Invalid request method']);
        break;
}

function handleCreate($pdo, $input)
{
    if (empty($input['email']) || empty($input['password']) || empty($input['name'])) {
        echo json_encode(['success' => false, 'message' => 'All fields (email, password, name) are required.']);
        return;
    }
    $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        return;
    }

    $checkEmailQuery = 'SELECT COUNT(*) FROM profile WHERE email = :email';
    $stmtCheck = $pdo->prepare($checkEmailQuery);
    $stmtCheck->bindParam(':email', $email);
    $stmtCheck->execute();
    $emailExists = $stmtCheck->fetchColumn();

    if ($emailExists > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists.']);
        return;
    }

    $sql = 'INSERT INTO profile (email, password, name) VALUES (:email, :password, :name)';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $input['password']);
    $stmt->bindParam(':name', $input['name']);

    if ($stmt->execute()) {
        $token = bin2hex(random_bytes(16));
        echo json_encode(['success' => true, 'message' => 'Account created successfully', 'token' => $token]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Account creation failed.']);
    }
}
