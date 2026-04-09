<?php

function adminJsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function adminReadJsonInput(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function adminCurrentCacheState(): array
{
    return [
        'active' => (bool) CACHE_ATIVO,
        'source' => 'config'
    ];
}

function adminConfigFile(): string
{
    return $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
}

function adminSetCacheState(bool $active): bool
{
    $configFile = adminConfigFile();
    if (!is_file($configFile) || !is_readable($configFile) || !is_writable($configFile)) {
        return false;
    }

    $content = (string) file_get_contents($configFile);
    if ($content === '') {
        return false;
    }

    $replacement = "define('CACHE_ATIVO', " . ($active ? 'true' : 'false') . ');';
    $updated = preg_replace(
        "/define\\('CACHE_ATIVO',\\s*(true|false)\\);/i",
        $replacement,
        $content,
        1,
        $count
    );

    if ($updated === null || $count !== 1) {
        return false;
    }

    return file_put_contents($configFile, $updated, LOCK_EX) !== false;
}
