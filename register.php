<?php
require 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if (empty($username) || empty($password) || strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Username and a password of at least 6 characters are required."]);
    exit();
}

// 1. Check if username already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["status" => "error", "message" => "Username already taken."]);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// 2. Hash the password for secure storage
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$role = 'contributor'; // New users are contributors

// 3. Insert the new user
$stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $passwordHash, $role);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Registration successful!"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Registration failed: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>