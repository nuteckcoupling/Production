<?php
header("Content-Type: application/json");
require "auth.php";

$user = current_user();
echo json_encode([
    'authenticated' => $user !== null,
    'user' => $user
]);
?>
