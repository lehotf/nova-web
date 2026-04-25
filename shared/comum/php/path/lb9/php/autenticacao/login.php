<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(guardiao: true, logger: true, autenticador: true, observador: true);

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (!$isHttps && !LOCALHOST) {
    $c->observador->erro('Login disponível apenas via HTTPS.');
}

$login = $c->observador->texto("login");
$senha = $c->observador->texto("senha");

if ($c->autenticador->login($login, $senha)) {
    $c->observador->envia("Autenticado");
} else {
    $c->observador->erro("Acesso Negado");
}
