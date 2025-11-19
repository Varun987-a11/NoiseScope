<?php
// backend/get_data.php
require 'db_connect.php'; 

header('Content-Type: application/json');

if ($conn->connect_error) {
    http_response_code(500); 
    echo json_encode([]);
    exit();
}

// Select all columns from the noise_data table
$sql = "SELECT id, location_name, latitude, longitude, avg_noise_level_db, environment_type, timestamp FROM noise_data ORDER BY timestamp DESC";
$result = $conn->query($sql);

$data = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $row['latitude'] = (float)$row['latitude'];
        $row['longitude'] = (float)$row['longitude'];
        $row['avg_noise_level_db'] = (float)$row['avg_noise_level_db'];
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>