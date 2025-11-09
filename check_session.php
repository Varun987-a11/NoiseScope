<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "logged_in",
        "user_type" => $_SESSION['user_type'],
        "username" => $_SESSION['username']
    ]);
} else {
    echo json_encode(["status" => "logged_out"]);
}
?>