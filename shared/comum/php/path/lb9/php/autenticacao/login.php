<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(guardiao: true, logger: true, autenticador: true, observador: true);

$login = $c->observador->texto("login");
$senha = $c->observador->texto("senha");

if ($c->autenticador->login($login, $senha)) {
    $c->observador->envia("Autenticado");
} else {
    $c->observador->envia("Acesso Negado");
}
