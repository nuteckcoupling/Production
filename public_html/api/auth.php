<?php
require_once __DIR__ . "/response.php";

const LOGIN_MAX_FAILED_ATTEMPTS = 5;
const LOGIN_LOCK_MINUTES = 15;
const SESSION_IDLE_TIMEOUT_SECONDS = 3600;

if (session_status() !== PHP_SESSION_ACTIVE) {
    $custom_session_path = getenv('PRODUCTION_SESSION_PATH');
    if ($custom_session_path) session_save_path($custom_session_path);
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
    $stmt = $conn->prepare("SELECT u.id, u.username, u.role, u.operator_id, u.must_change_password,
                                   u.session_version, o.name AS operator_name
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
        'operator_name' => $row['operator_name'],
        'must_change_password' => (bool)$row['must_change_password'],
        'session_version' => (int)$row['session_version']
    ];
}

function end_invalid_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

function validated_session_user(mysqli $conn): ?array
{
    $user = current_user();
    if ($user === null) return null;

    $last_activity = (int)($_SESSION['last_activity_at'] ?? 0);
    if ($last_activity > 0 && time() - $last_activity > SESSION_IDLE_TIMEOUT_SECONDS) {
        end_invalid_session();
        return null;
    }

    $fresh_user = active_user_by_id($conn, (int)$user['id']);
    if ($fresh_user === null || (int)($user['session_version'] ?? 0) !== $fresh_user['session_version']) {
        end_invalid_session();
        return null;
    }
    $_SESSION['user'] = $fresh_user;
    $_SESSION['last_activity_at'] = time();
    return $fresh_user;
}

function require_auth(bool $allow_password_change = false): array
{
    global $conn;
    $user = isset($conn) && $conn instanceof mysqli ? validated_session_user($conn) : current_user();
    if ($user === null) json_error('Login required or session expired', 401);
    if (!$allow_password_change && !empty($user['must_change_password'])) {
        json_error('Password change required before continuing', 428);
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
