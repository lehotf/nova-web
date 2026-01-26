<?php
require $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/observador.php';
require_once 'cacheTemplatesCore.php';

$o = new observador();
$o->acesso(2);

$forcar = $o->numero('forcar');

gera_root(DEBUG, $forcar);
gera_root(DEBUG, $forcar, 'amp');

foreach (templateDirs() as $dir) {
    verifica($dir, DEBUG);
}

$msg = $lista ? "Arquivos recriados ($lista)" : 'Nenhum arquivo precisou ser recriado';
$o->responde(['lista' => $lista], 'ok', $msg);
