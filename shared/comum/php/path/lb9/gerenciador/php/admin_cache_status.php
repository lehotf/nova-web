<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
require __DIR__ . '/admin_common.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

adminJsonResponse([
    'sucesso' => true,
    'mensagem' => 'Estado atual do cache carregado.',
    'cache' => adminCurrentCacheState()
]);
