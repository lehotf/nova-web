<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$docRoot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 6);
$baseDir = rtrim($docRoot, '/') . '/cache/sistema';
$limit = max(1, min(1000, (int) ($_GET['limit'] ?? 300)));

$files = [
    'acessos' => $baseDir . '/acessos',
    'acessos_negados' => $baseDir . '/acessos_negados',
];

try {
    $logs = [];
    $summary = [];

    foreach ($files as $type => $path) {
        $entries = readAccessLogFile($path, $type, $limit);
        $summary[$type] = [
            'path' => $path,
            'exists' => is_file($path),
            'total' => count($entries),
        ];
        array_push($logs, ...$entries);
    }

    usort($logs, static function (array $a, array $b): int {
        return strcmp($b['sort_key'], $a['sort_key']);
    });

    echo json_encode([
        'sucesso' => true,
        'logs' => array_slice($logs, 0, $limit),
        'resumo' => $summary,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao carregar logs de acesso.',
        'detalhe' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function readAccessLogFile(string $path, string $type, int $limit): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $lines = tailAccessLogLines($path, $limit);
    $entries = [];

    foreach ($lines as $index => $line) {
        $entries[] = parseAccessLogLine($line, $type, $index);
    }

    return $entries;
}

function tailAccessLogLines(string $path, int $limit): array
{
    $handle = fopen($path, 'rb');
    if (!$handle) {
        return [];
    }

    $chunkSize = 8192;
    $buffer = '';
    $lines = [];

    fseek($handle, 0, SEEK_END);
    $position = ftell($handle);

    while ($position > 0 && count($lines) <= $limit) {
        $readSize = min($chunkSize, $position);
        $position -= $readSize;
        fseek($handle, $position);

        $buffer = fread($handle, $readSize) . $buffer;
        $lines = preg_split('/\r\n|\n|\r/', $buffer);
    }

    fclose($handle);

    if (!is_array($lines)) {
        return [];
    }

    $lines = array_values(array_filter(array_map('trim', $lines), static fn($line) => $line !== ''));

    return array_slice($lines, -$limit);
}

function parseAccessLogLine(string $line, string $type, int $index): array
{
    $line = trim($line);
    $entry = [
        'tipo' => $type,
        'tipo_label' => $type === 'acessos_negados' ? 'Negado' : 'Acesso',
        'data' => '',
        'hora' => '',
        'ip' => '',
        'rota' => '',
        'status' => '',
        'raw' => $line,
        'sort_key' => sprintf('0000-00-00 00:00:00.%06d', $index),
    ];

    if (!preg_match('/^(\d{2}\/\d{2}\/\d{4})\s+(\d{2}:\d{2}:\d{2})\s+(\S+)\s+(\S+)(?:\s+(.+))?$/', $line, $matches)) {
        return $entry;
    }

    $date = DateTime::createFromFormat('d/m/Y H:i:s', $matches[1] . ' ' . $matches[2]);
    $entry['data'] = $matches[1];
    $entry['hora'] = $matches[2];
    $entry['ip'] = $matches[3];
    $entry['rota'] = $matches[4];
    $entry['status'] = trim($matches[5] ?? '');
    $entry['sort_key'] = ($date ? $date->format('Y-m-d H:i:s') : '0000-00-00 00:00:00') . sprintf('.%06d', $index);

    return $entry;
}
