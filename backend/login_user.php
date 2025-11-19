<?php
session_start();
require 'db_connect.php'; 

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

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Both username and password are required."]);
    exit();
}

$safe_username = $conn->real_escape_string($username);

// Look for a user that is NOT an admin (is_admin = 0)
$sql = "SELECT id, password_hash, is_admin FROM users WHERE username = '$safe_username' AND is_admin = 0 LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows == 1) {
    $user = $result->fetch_assoc();
    $hashed_password = $user['password_hash'];

    if (password_verify($password, $hashed_password)) {
        // Successful contributor login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = FALSE; // Explicitly mark as non-admin
        
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Login successful.", "redirect" => "user_submit.php"]);
        
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
    }
} else {
    // This covers case where user doesn't exist OR user is an admin
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid username or password for contributor access."]);
}

$conn->close();
?>