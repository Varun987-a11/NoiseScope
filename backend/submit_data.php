<?php
require 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

// --- Rate limiting: max 1 submission per 30 seconds per IP ---
$ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR']);
$rate_check = $conn->prepare("SELECT submitted_at FROM noise_data WHERE ip_hash = ? ORDER BY submitted_at DESC LIMIT 1");
$rate_check->bind_param("s", $ip_hash);
$rate_check->execute();
$rate_result = $rate_check->get_result();
if ($rate_result->num_rows > 0) {
    $last = $rate_result->fetch_assoc();
    $seconds_since = time() - strtotime($last['submitted_at']);
    if ($seconds_since < 30) {
        http_response_code(429);
        echo json_encode(["status" => "error", "message" => "Please wait " . (30 - $seconds_since) . " seconds before submitting again."]);
        exit();
    }
}

// --- Parse incoming JSON ---
$data = json_decode(file_get_contents('php://input'), true);

$location_name    = isset($data['locationName'])    ? trim($data['locationName'])    : '';
$latitude         = isset($data['latitude'])         ? (float)$data['latitude']       : 0.0;
$longitude        = isset($data['longitude'])        ? (float)$data['longitude']      : 0.0;
$avg_db           = isset($data['avgNoiseLevel'])    ? (int)$data['avgNoiseLevel']    : 0;
$peak_db          = isset($data['peakNoiseLevel'])   ? (int)$data['peakNoiseLevel']   : $avg_db;
$environment_type = isset($data['environmentType'])  ? trim($data['environmentType']) : '';
$place_type       = isset($data['placeType'])        ? trim($data['placeType'])       : '';
$time_of_day      = isset($data['timeOfDay'])        ? trim($data['timeOfDay'])       : '';
$dominant_sound   = isset($data['dominantSound'])    ? trim($data['dominantSound'])   : '';
$weather          = isset($data['weather'])          ? trim($data['weather'])         : '';

// --- Validation ---
if (empty($location_name) || $avg_db <= 0 || ($latitude === 0.0 && $longitude === 0.0) || empty($environment_type)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit();
}

// --- Auto-compute time features for future ML ---
$hour_of_day = (int)date('G');       // 0–23
$day_of_week = (int)date('N');       // 1=Mon … 7=Sun
$day_type    = ($day_of_week >= 6) ? 'weekend' : 'weekday';

// --- Prepared statement INSERT ---
$stmt = $conn->prepare("
    INSERT INTO noise_data 
        (location_name, latitude, longitude, avg_noise_level_db, peak_db,
         environment_type, place_type, time_of_day, dominant_sound, weather,
         hour_of_day, day_of_week, day_type, ip_hash, submitted_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param(
    "sddiisssssiiis",
    $location_name, $latitude, $longitude, $avg_db, $peak_db,
    $environment_type, $place_type, $time_of_day, $dominant_sound, $weather,
    $hour_of_day, $day_of_week, $day_type, $ip_hash
);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(["status" => "success", "message" => "Noise data recorded successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error."]);
}

$stmt->close();
$conn->close();
?>