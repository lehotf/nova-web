<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
require 'cacheTemplatesCore.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);
$o = $c->observador;

$forcar = $o->numero('forcar');

gera_root(DEBUG, $forcar);
gera_root(DEBUG, $forcar, 'amp');

foreach (templateDirs() as $dir) {
    verifica($dir, DEBUG);
}

$msg = $lista ? "Arquivos recriados ($lista)" : 'Nenhum arquivo precisou ser recriado';
$o->envia($msg, 'ok');
