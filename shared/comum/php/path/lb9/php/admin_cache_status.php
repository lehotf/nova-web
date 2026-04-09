<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

function adminCurrentCacheState(): array
{
    return [
        'active' => (bool) CACHE_ATIVO,
        'source' => 'config'
    ];
}

$c->observador->r['cache'] = adminCurrentCacheState();
$c->observador->envia('Estado atual do cache carregado.');
