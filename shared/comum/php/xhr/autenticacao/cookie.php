<?php
require $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/observador.php';


$o = new observador();
$o->acesso(2);

if ($o->autenticador->cookie()) {
    $autorizacao = isset($_SESSION['autorizacao']) ? (int) $_SESSION['autorizacao'] : 0;
    $resultado = $o->query("SELECT m, eval FROM script WHERE (autorizacao <= $autorizacao) ORDER BY ordem, m");

    $scripts = [];
    if ($resultado instanceof mysqli_result) {
        while ($linha = $resultado->fetch_array(MYSQLI_ASSOC)) {
            $scripts[] = $linha;
        }
    }

    $o->responde(['script' => $scripts], 'ok', 'Autenticado');
}

$o->erro('Acesso Negado');
