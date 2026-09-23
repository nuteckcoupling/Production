<?php
header("Content-Type: application/json");

function json_response($payload, ?int $status = null): void
{
    if ($status !== null) {
        http_response_code($status);
    }
    echo json_encode($payload);
}

function json_error(string $message, int $status): void
{
    json_response(['error' => $message], $status);
    exit;
}

function json_input(): array
{
    $payload = json_decode(file_get_contents('php://input'), true);
    return is_array($payload) ? $payload : [];
}
?>