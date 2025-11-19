<?php
require 'db_connect.php';

// Set response header to JSON
header('Content-Type: application/json');

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit();
}

// Get the raw JSON post data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Basic validation and sanitation
$location_name = isset($data['locationName']) ? trim($data['locationName']) : '';
$latitude = isset($data['latitude']) ? (float)$data['latitude'] : 0.0;
$longitude = isset($data['longitude']) ? (float)$data['longitude'] : 0.0;
$avg_noise_level_db = isset($data['avgNoiseLevel']) ? (int)$data['avgNoiseLevel'] : 0;
// --- CRITICAL FIX 1: Extract environmentType ---
$environment_type = isset($data['environmentType']) ? trim($data['environmentType']) : '';

// Sanitize inputs for SQL injection prevention
$location_name = $conn->real_escape_string($location_name);
// --- CRITICAL FIX 2: Sanitize environmentType ---
$environment_type = $conn->real_escape_string($environment_type);


// Check if all necessary data is present and valid
if (empty($location_name) || $avg_noise_level_db <= 0 || ($latitude === 0.0 && $longitude === 0.0) || empty($environment_type)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid or missing data (Location, Lat/Long, dB, or Environment Type missing)."]);
    exit();
}

// --- CRITICAL FIX 3: Update SQL INSERT statement ---
// Ensure the column name (environment_type) and its value ('$environment_type') are included.
$sql = "INSERT INTO noise_data (location_name, latitude, longitude, avg_noise_level_db, environment_type, timestamp) 
        VALUES ('$location_name', $latitude, $longitude, $avg_noise_level_db, '$environment_type', NOW())";

if ($conn->query($sql) === TRUE) {
    http_response_code(201); // 201 Created
    echo json_encode(["status" => "success", "message" => "Noise data recorded successfully."]);
} else {
    // This will now show the actual SQL error if there is one (e.g., if db_connect fails)
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error inserting record: " . $conn->error]);
}

$conn->close();
?>