<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
require __DIR__ . '/admin_common.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

$payload = adminReadJsonInput();
$current = adminCurrentCacheState();
$next = !$current['active'];

if (array_key_exists('active', $payload)) {
    $next = !empty($payload['active']);
}

if (!adminSetCacheState($next)) {
    adminJsonResponse([
        'sucesso' => false,
        'mensagem' => 'Não foi possível atualizar o config.php do site.',
        'cache' => adminCurrentCacheState()
    ], 500);
}

adminJsonResponse([
    'sucesso' => true,
    'mensagem' => $next ? 'Cache ativado no config.php.' : 'Cache desativado no config.php.',
    'cache' => [
        'active' => $next,
        'source' => 'config'
    ]
]);
