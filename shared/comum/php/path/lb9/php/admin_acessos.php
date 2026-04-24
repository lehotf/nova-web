<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

$docRoot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 6);
$baseDir = rtrim($docRoot, '/') . '/cache/sistema';

$files = [
    'acessos' => $baseDir . '/acessos',
    'acessos_negados' => $baseDir . '/acessos_negados',
];

try {
    $payload = [];

    foreach ($files as $type => $path) {
        $payload[$type] = [
            'path' => $path,
            'conteudo' => readAccessFile($path),
        ];
    }

    echo json_encode([
        'sucesso' => true,
        'arquivos' => $payload,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao carregar logs de acesso.',
        'detalhe' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function readAccessFile(string $path): string
{
    if (!is_file($path) || !is_readable($path)) {
        return '';
    }

    $content = file_get_contents($path);

    return $content === false ? '' : $content;
}
