<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

$baseDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/cache/sistema';
$files = [
    $baseDir . '/acessos',
    $baseDir . '/acessos_negados',
];

try {
    $removed = 0;

    foreach ($files as $path) {
        if (!is_file($path)) {
            continue;
        }

        if (!@unlink($path)) {
            throw new RuntimeException('Nao foi possivel remover arquivo de log: ' . basename($path));
        }

        $removed++;
    }

    echo json_encode([
        'sucesso' => true,
        'mensagem' => $removed > 0 ? 'Logs de acesso removidos.' : 'Nenhum arquivo de log encontrado.',
        'removidos' => $removed,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao limpar logs de acesso.',
        'detalhe' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
