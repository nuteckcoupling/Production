<?php
function application_request_id(): string
{
    static $request_id = null;
    if ($request_id !== null) return $request_id;
    try {
        $request_id = bin2hex(random_bytes(6));
    } catch (Throwable $ignored) {
        $request_id = substr(hash('sha256', uniqid('', true)), 0, 12);
    }
    return $request_id;
}

function application_log_directory(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
}

function ensure_application_log_directory(): string
{
    $directory = application_log_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create application log directory');
    }
    return $directory;
}

function safe_error_text(string $message): string
{
    $message = preg_replace('/(password|passwd|pwd|secret|token)\s*[=:]\s*[^\s,;]+/i', '$1=[hidden]', $message);
    return substr((string)$message, 0, 500);
}

function application_log_error(string $context, Throwable|string $error, ?string $file = null, ?int $line = null): string
{
    $reference = application_request_id();
    try {
        $entry = [
            'timestamp' => date('c'),
            'reference' => $reference,
            'context' => substr($context, 0, 100),
            'type' => $error instanceof Throwable ? get_class($error) : 'PHP Error',
            'message' => safe_error_text($error instanceof Throwable ? $error->getMessage() : $error),
            'file' => basename($file ?? ($error instanceof Throwable ? $error->getFile() : '')),
            'line' => $line ?? ($error instanceof Throwable ? $error->getLine() : null),
            'path' => substr((string)($_SERVER['REQUEST_URI'] ?? 'CLI'), 0, 200),
            'user_id' => isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null
        ];
        $path = ensure_application_log_directory() . DIRECTORY_SEPARATOR . 'application.jsonl';
        file_put_contents($path, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
        @chmod($path, 0640);
    } catch (Throwable $logging_error) {
        error_log('Application error log failed: ' . safe_error_text($logging_error->getMessage()));
    }
    return $reference;
}

function json_server_error(string $context, Throwable $error, string $message = 'Unexpected server error'): void
{
    $reference = application_log_error($context, $error);
    json_error($message . '. Reference: ' . $reference, 500);
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) return false;
    application_log_error('PHP runtime', $message, $file, $line);
    return false;
});

set_exception_handler(function (Throwable $error): void {
    $reference = application_log_error('Uncaught exception', $error);
    if (!headers_sent()) {
        json_response(['error' => 'Unexpected server error. Reference: ' . $reference], 500);
    }
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
    application_log_error('Fatal PHP error', (string)$error['message'], (string)$error['file'], (int)$error['line']);
});
?>
