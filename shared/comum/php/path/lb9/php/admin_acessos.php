<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);
$o = $c->observador;

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

    $o->r['arquivos'] = $payload;
    $o->envia('Logs de acesso carregados.');
} catch (Throwable $e) {
    $o->r['detalhe'] = $e->getMessage();
    $o->envia('Erro ao carregar logs de acesso.', 'erro');
}

function readAccessFile(string $path): string
{
    if (!is_file($path) || !is_readable($path)) {
        return '';
    }

    $content = file_get_contents($path);

    return $content === false ? '' : $content;
}
