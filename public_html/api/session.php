<?php
require __DIR__ . "/bootstrap.php";

$user = validated_session_user($conn);
json_response([
    'authenticated' => $user !== null,
    'user' => $user
]);
$conn->close();
?>
