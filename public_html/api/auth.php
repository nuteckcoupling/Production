<?php
require_once __DIR__ . "/response.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => false
    ]);
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): array
{
    $user = current_user();
    if ($user === null) {
        json_error('Login required', 401);
    }
    return $user;
}

function require_admin(): array
{
    $user = require_auth();
    if (($user['role'] ?? '') !== 'Admin') {
        json_error('Admin access required', 403);
    }
    return $user;
}

function require_operator_supervisor(): array
{
    $user = require_auth();
    if (($user['role'] ?? '') !== 'Operator/Supervisor') {
        json_error('Operator/Supervisor access required', 403);
    }
    return $user;
}
?>
