<?php
function audit_log(
    mysqli $conn,
    ?array $user,
    string $module,
    string $action,
    string $description,
    ?string $entity_type = null,
    ?int $entity_id = null,
    ?string $username_override = null
): void {
    try {
        $user_id = isset($user['id']) ? (int)$user['id'] : null;
        $username = substr($username_override ?? ($user['username'] ?? 'unknown'), 0, 50);
        $role = isset($user['role']) ? substr((string)$user['role'], 0, 50) : null;
        $module = substr(trim($module), 0, 60);
        $action = substr(trim($action), 0, 60);
        $description = substr(trim($description), 0, 500);
        $entity_type = $entity_type === null ? null : substr(trim($entity_type), 0, 60);
        $ip_address = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $ip_address = $ip_address === '' ? null : $ip_address;
        $stmt = $conn->prepare("INSERT INTO audit_logs
            (user_id, username, role, module, action, description, entity_type, entity_id, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issssssis', $user_id, $username, $role, $module, $action, $description,
            $entity_type, $entity_id, $ip_address);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $error) {
        error_log('Audit log write failed: ' . $error->getMessage());
    }
}
?>
