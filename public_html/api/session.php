<?php
require __DIR__ . "/auth.php";

$user = current_user();
json_response([
    'authenticated' => $user !== null,
    'user' => $user
]);
?>
