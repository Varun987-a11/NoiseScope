<?php
// Start a session to store login status
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

// 1. Prepare SQL to fetch user details
$sql = "SELECT id, password_hash, is_admin FROM users WHERE username = '$safe_username' LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows == 1) {
    $user = $result->fetch_assoc();
    $hashed_password = $user['password_hash'];

    // 2. Verify the password
    if (password_verify($password, $hashed_password)) {
        // Successful login!
        
        // 3. Check if the user is an admin (optional step if all registered users are admins)
        if ($user['is_admin'] == 1) {
            // Set session variables to mark the user as logged in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = TRUE;
            
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Login successful."]);
        } else {
            // User is in the database but is not marked as an admin
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Access denied. Not an admin user."]);
        }
        
    } else {
        // Password did not match
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
    }
} else {
    // Username not found
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
}

$conn->close();
?>