<?php
// Note: Session check must happen in admin_dashboard.php before calling this API
require 'db_connect.php';

header('Content-Type: application/json');

// Join noise_data with users to get the username for the admin table
$sql = "
    SELECT 
        nd.id, 
        nd.location_name, 
        nd.latitude, 
        nd.longitude, 
        nd.avg_noise_level_db, 
        nd.environment_type, 
        nd.timestamp, 
        COALESCE(u.username, 'Anonymous') AS submitter 
    FROM noise_data nd
    LEFT JOIN users u ON nd.user_id = u.id
    ORDER BY nd.timestamp DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
} else {
    echo json_encode([]); // Return empty array if no data
}

$conn->close();
?>