<?php
const ATTACHMENT_MAX_BYTES = 10485760;

function attachment_storage_root(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage';
}

function ensure_attachment_directory(): string
{
    $directory = attachment_storage_root() . DIRECTORY_SEPARATOR . 'attachments';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create attachment directory');
    }
    return $directory;
}

function store_secure_attachment(array $file): array
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) throw new RuntimeException('Select a file to upload', 400);
    if ($error !== UPLOAD_ERR_OK) throw new RuntimeException('File upload failed', 400);
    $size = (int)($file['size'] ?? 0);
    if ($size < 1 || $size > ATTACHMENT_MAX_BYTES) {
        throw new RuntimeException('File must be 10 MB or smaller', 400);
    }
    $temporary = (string)($file['tmp_name'] ?? '');
    if ($temporary === '' || !is_uploaded_file($temporary)) throw new RuntimeException('Invalid uploaded file', 400);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
    $extensions = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Only PDF, JPG and PNG files are allowed', 400);
    $stored_name = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
    $relative_path = 'attachments/' . $stored_name;
    $path = ensure_attachment_directory() . DIRECTORY_SEPARATOR . $stored_name;
    if (!move_uploaded_file($temporary, $path)) throw new RuntimeException('Unable to store attachment');
    @chmod($path, 0640);
    return [
        'stored_name' => $stored_name,
        'relative_path' => $relative_path,
        'path' => $path,
        'original_name' => substr(basename((string)($file['name'] ?? 'attachment')), 0, 255),
        'mime_type' => $mime,
        'file_size' => $size
    ];
}

function attachment_entity_exists(mysqli $conn, string $entity_type, int $entity_id): bool
{
    $tables = ['production_job' => 'production_jobs', 'part' => 'parts', 'maintenance_ticket' => 'maintenance_tickets'];
    if (!isset($tables[$entity_type])) return false;
    $stmt = $conn->prepare('SELECT id FROM ' . $tables[$entity_type] . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $entity_id);
    $stmt->execute();
    $exists = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $exists;
}

function attachment_can_read(array $user, string $entity_type): bool
{
    $role = (string)($user['role'] ?? '');
    if ($role === 'Admin') return true;
    if ($role === 'Operator/Supervisor') return true;
    return $role === 'Maintenance' && $entity_type === 'maintenance_ticket';
}

function attachment_can_upload(array $user, string $entity_type, string $category): bool
{
    $role = (string)($user['role'] ?? '');
    $allowed = [
        'production_job' => ['Job Drawing', 'Job Photo', 'QC Inspection'],
        'part' => ['Part Photo'],
        'maintenance_ticket' => ['Breakdown Before', 'Breakdown After']
    ];
    if (!in_array($category, $allowed[$entity_type] ?? [], true)) return false;
    if ($entity_type === 'maintenance_ticket') {
        return ($role === 'Operator/Supervisor' && $category === 'Breakdown Before')
            || ($role === 'Maintenance' && $category === 'Breakdown After');
    }
    return $role === 'Operator/Supervisor';
}

function attachment_absolute_path(string $relative_path): string
{
    $normalized = str_replace('\\', '/', $relative_path);
    if (!preg_match('#^(attachments|uploads/breakdowns)/[a-zA-Z0-9._-]+$#', $normalized)) {
        throw new RuntimeException('Invalid attachment path');
    }
    return attachment_storage_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
}
?>
