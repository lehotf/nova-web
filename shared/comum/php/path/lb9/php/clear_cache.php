<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

$o = $c->observador;
$cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/cache/html';

if (!is_dir($cacheDir)) {
    $o->envia('Diretório de cache HTML não existe.', 'ok');
}

try {
    clearCacheDirectory($cacheDir);
} catch (RuntimeException $e) {
    $o->erro($e->getMessage());
}

if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
    $o->erro('Não foi possível recriar o diretório de cache HTML.');
}

$o->envia('Cache HTML limpo.', 'ok');

function clearCacheDirectory(string $dir): void
{
    $items = scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            clearCacheDirectory($path);
            if (!@rmdir($path)) {
                throw new RuntimeException('Não foi possível remover diretório de cache: ' . $path);
            }
            continue;
        }

        if (!@unlink($path)) {
            throw new RuntimeException('Não foi possível remover arquivo de cache: ' . $path);
        }
    }
}
