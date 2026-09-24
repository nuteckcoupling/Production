<?php
require_once __DIR__ . "/response.php";

// ===== EDIT THESE 4 VALUES with your cPanel MySQL details =====
$DB_HOST = getenv('PRODUCTION_DB_HOST') ?: "localhost";
$DB_NAME = getenv('PRODUCTION_DB_NAME') ?: "prodction";   // cPanel DB names are usually prefixed, e.g. nuteck_production
$DB_USER = getenv('PRODUCTION_DB_USER') ?: "root";
$DB_PASS = getenv('PRODUCTION_DB_PASS') !== false ? getenv('PRODUCTION_DB_PASS') : "";
// ================================================================

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    json_error("DB connection failed: " . $conn->connect_error, 500);
}
$conn->set_charset("utf8mb4");
?>
