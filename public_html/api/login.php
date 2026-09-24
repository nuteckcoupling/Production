<?php
require __DIR__ . "/bootstrap.php";

$data = json_input();
$username = strtolower(trim($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    json_response(['error' => 'Username and Password / PIN are required']);
    exit;
}

$stmt = $conn->prepare("SELECT u.id, u.username, u.password_hash, u.role, u.operator_id, u.status,
                               u.failed_login_attempts, u.locked_until, u.must_change_password, u.session_version,
                               o.name AS operator_name, o.status AS operator_status,
                               (u.locked_until IS NOT NULL AND u.locked_until > NOW()) AS is_locked,
                               GREATEST(TIMESTAMPDIFF(SECOND, NOW(), u.locked_until), 0) AS lock_seconds
                        FROM users u
                        LEFT JOIN operators o ON o.id = u.operator_id
                        WHERE u.username = ?
                        LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && (int)$user['is_locked'] === 1) {
    $minutes = max(1, (int)ceil(((int)$user['lock_seconds']) / 60));
    audit_log($conn, null, 'Authentication', 'Login Blocked', 'Login blocked for locked account', 'user', (int)$user['id'], $username);
    json_error('Account temporarily locked. Try again in about ' . $minutes . ' minute(s).', 423);
}

if ($user && $user['locked_until'] !== null) {
    $reset = $conn->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
    $reset->bind_param('i', $user['id']);
    $reset->execute();
    $reset->close();
    $user['failed_login_attempts'] = 0;
}

if (!$user || $user['status'] !== 'Active' || ($user['operator_id'] !== null && $user['operator_status'] !== 'Active') || !password_verify($password, $user['password_hash'])) {
    if ($user && $user['status'] === 'Active') {
        $attempts = (int)$user['failed_login_attempts'] + 1;
        if ($attempts >= LOGIN_MAX_FAILED_ATTEMPTS) {
            $failed = $conn->prepare("UPDATE users SET failed_login_attempts = ?, last_failed_login = NOW(), locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?");
            $lock_minutes = LOGIN_LOCK_MINUTES;
            $failed->bind_param('iii', $attempts, $lock_minutes, $user['id']);
        } else {
            $failed = $conn->prepare("UPDATE users SET failed_login_attempts = ?, last_failed_login = NOW() WHERE id = ?");
            $failed->bind_param('ii', $attempts, $user['id']);
        }
        $failed->execute();
        $failed->close();
    }
    audit_log($conn, null, 'Authentication', 'Login Failed', 'Failed login attempt', 'user', null, $username);
    if (isset($attempts) && $attempts >= LOGIN_MAX_FAILED_ATTEMPTS) {
        json_error('Too many failed attempts. Account locked for ' . LOGIN_LOCK_MINUTES . ' minutes.', 423);
    }
    json_error('Invalid username or Password / PIN', 401);
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'username' => $user['username'],
    'role' => $user['role'],
    'operator_id' => $user['operator_id'] === null ? null : (int)$user['operator_id'],
    'operator_name' => $user['operator_name'],
    'must_change_password' => (bool)$user['must_change_password'],
    'session_version' => (int)$user['session_version']
];
$_SESSION['last_activity_at'] = time();

$update = $conn->prepare("UPDATE users SET last_login = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
$update->bind_param("i", $_SESSION['user']['id']);
$update->execute();

audit_log($conn, $_SESSION['user'], 'Authentication', 'Login', 'User logged in', 'user', (int)$user['id']);

json_response(['success' => true, 'user' => $_SESSION['user']]);

$update->close();
$stmt->close();
$conn->close();
?>
