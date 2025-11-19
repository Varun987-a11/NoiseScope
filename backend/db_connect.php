<?php
// Configuration for MySQL connection (Standard XAMPP/WAMP defaults)
$servername = "localhost";
$username = "root";
$password = "vKs$135#"; // Change this if you have a MySQL password
$dbname = "noisescope";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Return a JSON error response instead of dying silently
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}
?>