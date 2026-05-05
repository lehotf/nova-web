<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);
$o = $c->observador;

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

    $o->r['removidos'] = $removed;
    $o->envia($removed > 0 ? 'Logs de acesso removidos.' : 'Nenhum arquivo de log encontrado.');
} catch (Throwable $e) {
    $o->r['detalhe'] = $e->getMessage();
    $o->envia('Erro ao limpar logs de acesso.', 'erro');
}
