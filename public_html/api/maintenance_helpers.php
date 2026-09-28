<?php
function maintenance_upload_directory(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'breakdowns';
}

function ensure_maintenance_upload_directory(): string
{
    $directory = maintenance_upload_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create breakdown photo directory');
    }
    return $directory;
}

function store_breakdown_photo(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Breakdown photo upload failed', 400);
    $size = (int)($file['size'] ?? 0);
    if ($size < 1 || $size > 5 * 1024 * 1024) throw new RuntimeException('Breakdown photo must be 5 MB or smaller', 400);
    $temporary = (string)($file['tmp_name'] ?? '');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Breakdown photo must be JPG or PNG', 400);
    $stored_name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $path = ensure_maintenance_upload_directory() . DIRECTORY_SEPARATOR . $stored_name;
    if (!move_uploaded_file($temporary, $path)) throw new RuntimeException('Unable to store breakdown photo');
    @chmod($path, 0640);
    return [
        'stored_name' => $stored_name,
        'path' => $path,
        'original_name' => substr(basename((string)($file['name'] ?? 'breakdown-photo')), 0, 255),
        'mime_type' => $mime,
        'file_size' => $size
    ];
}

function maintenance_status_is_open(string $status): bool
{
    return in_array($status, ['Open', 'Assigned', 'In Progress', 'Repair Completed'], true);
}
?>
