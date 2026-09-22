<?php
// ===== EDIT THESE 4 VALUES with your cPanel MySQL details =====
$DB_HOST = "localhost";
$DB_NAME = "prodction";   // cPanel DB names are usually prefixed, e.g. nuteck_production
$DB_USER = "root";
$DB_PASS = "";
// ================================================================

header("Content-Type: application/json");

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed: " . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");
?>
