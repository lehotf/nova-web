<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

$c->observador->r['debug'] = [
    'active' => (bool) DEBUG,
    'source' => 'config'
];
$c->observador->envia('Estado atual do debug carregado.');
