<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);

$c->autenticador->acesso(2);

$autorizacao = isset($_SESSION['autorizacao']) ? (int) $_SESSION['autorizacao'] : 0;
$c->observador->query(
    "SELECT m, eval FROM script WHERE (autorizacao <= ?) ORDER BY ordem, m",
    false,
    'i',
    [$autorizacao]
);
$c->observador->envia('Autenticado', 'ok');

$c->observador->erro('Acesso Negado');
