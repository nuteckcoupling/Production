<?php
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
        http_response_code(401);
        echo json_encode(['error' => 'Login required']);
        exit;
    }
    return $user;
}

function require_admin(): array
{
    $user = require_auth();
    if (($user['role'] ?? '') !== 'Admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
    return $user;
}

function require_operator_supervisor(): array
{
    $user = require_auth();
    if (($user['role'] ?? '') !== 'Operator/Supervisor') {
        http_response_code(403);
        echo json_encode(['error' => 'Operator/Supervisor access required']);
        exit;
    }
    return $user;
}
?>
