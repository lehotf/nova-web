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

function adminConfigFile(): string
{
    return $_SERVER['DOCUMENT_ROOT'] . '/config.php';
}

function adminSetCacheState(bool $active): bool
{
    $configFile = adminConfigFile();
    if (!is_file($configFile) || !is_readable($configFile) || !is_writable($configFile)) {
        return false;
    }

    $content = (string) file_get_contents($configFile);
    if ($content === '') {
        return false;
    }

    $replacement = "define('CACHE_ATIVO', " . ($active ? 'true' : 'false') . ');';
    $updated = preg_replace(
        "/define\\('CACHE_ATIVO',\\s*(true|false)\\);/i",
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
$current = adminCurrentCacheState();
$next = !$current['active'];

if (array_key_exists('active', $payload)) {
    $next = (bool) $payload['active'];
}

if (!adminSetCacheState($next)) {
    $c->observador->r['cache'] = adminCurrentCacheState();
    $c->observador->erro('Não foi possível atualizar o config.php do site.');
}

$c->observador->r['cache'] = [
    'active' => $next,
    'source' => 'config'
];
$c->observador->envia($next ? 'Cache ativado no config.php.' : 'Cache desativado no config.php.');
