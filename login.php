<?php
require 'db_connect.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$target_role = $data['target_role'] ?? ''; // Added to know where the login came from

if (empty($username) || empty($password) || empty($target_role)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "All fields required."]);
    exit();
}

$stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

if ($user && password_verify($password, $user['password_hash'])) {
    // Successful login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    $redirect = '';
    
    // Check if the user's role matches the requested interface
    if ($target_role === 'admin' && $user['role'] === 'admin') {
        $redirect = 'admin_dashboard.php';
    } elseif ($target_role === 'user' && ($user['role'] === 'contributor' || $user['role'] === 'admin')) {
        $redirect = 'user_submit.php';
    } else {
        http_response_code(403); 
        echo json_encode(["status" => "error", "message" => "Access denied. Your role does not match this interface."]);
        exit();
    }

    echo json_encode(["status" => "success", "message" => "Login successful.", "redirect" => $redirect]);

} else {
    http_response_code(401); 
    echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
}
?>