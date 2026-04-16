  <?php
// db_connect.php
// Loads configuration from external file (which is ignored in GitHub)

$config = include(__DIR__ . '/config.php');

// Create connection
$conn = new mysqli(
    $config['servername'],
    $config['username'],
    $config['password'],
    $config['dbname']
);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit();
}
?>
