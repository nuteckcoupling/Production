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

function active_user_by_id(mysqli $conn, int $user_id): ?array
{
    $stmt = $conn->prepare("SELECT u.id, u.username, u.role, u.operator_id, o.name AS operator_name
                            FROM users u
                            LEFT JOIN operators o ON o.id = u.operator_id
                            WHERE u.id = ?
                              AND u.status = 'Active'
                              AND (u.operator_id IS NULL OR o.status = 'Active')
                            LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    if (!$row) return null;
    return [
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'role' => $row['role'],
        'operator_id' => $row['operator_id'] === null ? null : (int)$row['operator_id'],
        'operator_name' => $row['operator_name']
    ];
}

function end_invalid_session(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

function require_auth(): array
{
    $user = current_user();
    if ($user === null) {
        json_error('Login required', 401);
    }
    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        $user = active_user_by_id($conn, (int)$user['id']);
        if ($user === null) {
            end_invalid_session();
            json_error('Account is inactive. Please contact Admin.', 401);
        }
        $_SESSION['user'] = $user;
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
