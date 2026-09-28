<?php
require_once __DIR__ . "/config.php";

$application_timezone_name = getenv('PRODUCTION_TIMEZONE') ?: 'Asia/Kolkata';
try {
    $application_timezone = new DateTimeZone($application_timezone_name);
    date_default_timezone_set($application_timezone_name);
    $database_timezone_offset = (new DateTimeImmutable('now', $application_timezone))->format('P');
    $conn->query("SET time_zone = '" . $conn->real_escape_string($database_timezone_offset) . "'");
} catch (Throwable $error) {
    json_server_error('Application timezone setup', $error, 'Unable to initialize application timezone');
}

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/shift_helpers.php";
require_once __DIR__ . "/machine_helpers.php";
require_once __DIR__ . "/staff_helpers.php";
require_once __DIR__ . "/audit_helpers.php";
require_once __DIR__ . "/backup_helpers.php";
require_once __DIR__ . "/system_settings_helpers.php";
?>
