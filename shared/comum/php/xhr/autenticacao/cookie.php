<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);

$c->autenticador->acesso(2);

if ($c->autenticador->cookie()) {
    $autorizacao = isset($_SESSION['autorizacao']) ? (int) $_SESSION['autorizacao'] : 0;
    $c->observador->query("SELECT m, eval FROM script WHERE (autorizacao <= $autorizacao) ORDER BY ordem, m", false);
    $c->observador->envia('ok', 'Autenticado');
}

$c->observador->erro('Acesso Negado');
