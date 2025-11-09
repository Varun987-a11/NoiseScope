<?php
require 'db_connect.php'; // Reuse your database connection script

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit();
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? $data['password'] : '';

// 1. Basic Validation
if (empty($username) || empty($password) || strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Username and Password (min 6 chars) are required."]);
    exit();
}

// 2. Security: Sanitize username
$safe_username = $conn->real_escape_string($username);

// 3. Security: Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// 4. Insert into database
// Note: We set is_admin = TRUE for any user registering on this page/endpoint
$sql = "INSERT INTO users (username, password_hash, is_admin) 
        VALUES ('$safe_username', '$password_hash', TRUE)";

if ($conn->query($sql) === TRUE) {
    http_response_code(201); // Created
    echo json_encode(["status" => "success", "message" => "Admin registered successfully."]);
} else {
    http_response_code(500);
    // Check for Duplicate Username error (Error Code 1062)
    if ($conn->errno == 1062) {
        echo json_encode(["status" => "error", "message" => "Registration failed: This username is already taken."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $conn->error]);
    }
}

$conn->close();
?>