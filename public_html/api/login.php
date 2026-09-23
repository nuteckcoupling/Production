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

$stmt = $conn->prepare("SELECT u.id, u.username, u.password_hash, u.role, u.operator_id, o.name AS operator_name
                        FROM users u
                        LEFT JOIN operators o ON o.id = u.operator_id
                        WHERE u.username = ?
                          AND u.status = 'Active'
                          AND (u.operator_id IS NULL OR o.status = 'Active')
                        LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    json_response(['error' => 'Invalid username or Password / PIN']);
    exit;
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'username' => $user['username'],
    'role' => $user['role'],
    'operator_id' => $user['operator_id'] === null ? null : (int)$user['operator_id'],
    'operator_name' => $user['operator_name']
];

$update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$update->bind_param("i", $_SESSION['user']['id']);
$update->execute();

json_response(['success' => true, 'user' => $_SESSION['user']]);

$update->close();
$stmt->close();
$conn->close();
?>
