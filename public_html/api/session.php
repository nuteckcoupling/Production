<?php
require __DIR__ . "/bootstrap.php";

$user = current_user();
if ($user !== null) {
    $user = active_user_by_id($conn, (int)$user['id']);
    if ($user === null) {
        end_invalid_session();
    } else {
        $_SESSION['user'] = $user;
    }
}
json_response([
    'authenticated' => $user !== null,
    'user' => $user
]);
$conn->close();
?>
