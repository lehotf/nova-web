<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

function adminSetDebugState(bool $active): bool
{
    $configFile = $_SERVER['DOCUMENT_ROOT'] . '/config.php';
    if (!is_file($configFile) || !is_readable($configFile) || !is_writable($configFile)) {
        return false;
    }

    $content = (string) file_get_contents($configFile);
    if ($content === '') {
        return false;
    }

    $replacement = "define('DEBUG', " . ($active ? 'true' : 'false') . ');';
    $updated = preg_replace(
        "/define\\('DEBUG',\\s*(true|false)\\);/i",
        $replacement,
        $content,
        1,
        $count
    );

    if ($updated === null || $count !== 1) {
        return false;
    }

    return file_put_contents($configFile, $updated, LOCK_EX) !== false;
}

$payload = $c->observador->valida([
    'active' => ['tipo' => 'numero']
]);
$next = !(bool) DEBUG;
if (array_key_exists('active', $payload)) {
    $next = (bool) $payload['active'];
}

if (!adminSetDebugState($next)) {
    $c->observador->r['debug'] = [
        'active' => (bool) DEBUG,
        'source' => 'config'
    ];
    $c->observador->erro('Não foi possível atualizar DEBUG no config.php do site.');
}

$c->observador->r['debug'] = [
    'active' => $next,
    'source' => 'config'
];
$c->observador->envia($next ? 'Debug ativado no config.php.' : 'Debug desativado no config.php.');
