<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$o = new observador();
$o->acesso(2);

if ($o->autenticador->cookie()) {
    $autorizacao = isset($_SESSION['autorizacao']) ? (int) $_SESSION['autorizacao'] : 0;
    $o->query("SELECT m, eval FROM script WHERE (autorizacao <= $autorizacao) ORDER BY ordem, m", false);
    $o->envia('ok', 'Autenticado');
}

$o->erro('Acesso Negado');
