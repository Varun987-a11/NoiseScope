<?php
// backend/delete_submission.php
session_start();
require 'db_connect.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== TRUE) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Permission denied."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$id = isset($data['id']) ? (int)$data['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid submission ID provided."]);
    exit();
}

$stmt = $conn->prepare("DELETE FROM noise_data WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Submission deleted successfully."]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Submission not found or already deleted."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error during deletion."]);
}

$stmt->close();
$conn->close();
?>