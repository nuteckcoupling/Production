<?php
require_once __DIR__ . "/response.php";
require_once __DIR__ . "/error_helpers.php";

// ===== EDIT THESE 4 VALUES with your cPanel MySQL details =====
$DB_HOST = getenv('PRODUCTION_DB_HOST') ?: "localhost";
$DB_NAME = getenv('PRODUCTION_DB_NAME') ?: "prodction";   // cPanel DB names are usually prefixed, e.g. nuteck_production
$DB_USER = getenv('PRODUCTION_DB_USER') ?: "root";
$DB_PASS = getenv('PRODUCTION_DB_PASS') !== false ? getenv('PRODUCTION_DB_PASS') : "";
// ================================================================

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    $reference = application_log_error('Database connection', $conn->connect_error);
    json_error("Database connection failed. Reference: " . $reference, 500);
}
$conn->set_charset("utf8mb4");
?>
