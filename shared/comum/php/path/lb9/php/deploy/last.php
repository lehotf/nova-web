<?php
declare(strict_types=1);
$stateFile = __DIR__ . '/last_deploy';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function normalize_commit(string $commit): string
{
    return trim($commit);
}

function is_valid_commit(string $commit): bool
{
    return $commit !== '' && preg_match('/\A[0-9a-f]{7,40}\z/i', $commit) === 1;
}

function read_state(string $stateFile): string
{
    if (!is_file($stateFile)) {
        return '';
    }

    $content = file_get_contents($stateFile);
    if ($content === false) {
        return '';
    }

    return normalize_commit($content);
}

function write_state(string $stateFile, string $commit): bool
{
    $payload = $commit . PHP_EOL;
    return file_put_contents($stateFile, $payload, LOCK_EX) !== false;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'POST') {
    $commit = normalize_commit((string)($_POST['commit'] ?? ''));

    if (!is_valid_commit($commit)) {
        http_response_code(400);
        echo "commit invalido\n";
        exit;
    }

    if (!write_state($stateFile, $commit)) {
        http_response_code(500);
        echo "falha ao salvar last_deploy\n";
        exit;
    }

    echo $commit . PHP_EOL;
    exit;
}

$storedCommit = read_state($stateFile);

if ($storedCommit === '') {
    http_response_code(204);
    exit;
}

echo $storedCommit . PHP_EOL;
