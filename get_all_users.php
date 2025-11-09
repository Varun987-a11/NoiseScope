<?php
// backend/get_all_users.php
require 'db_connect.php'; 

header('Content-Type: application/json');

if ($conn->connect_error) {
    http_response_code(500); 
    echo json_encode([]);
    exit();
}

$sql = "SELECT id, username, is_admin, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

$users = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $row['is_admin'] = (int)$row['is_admin']; 
        $users[] = $row;
    }
}

echo json_encode($users);
$conn->close();
?>