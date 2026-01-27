<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(guardiao: true, logger: true, autenticador: true, observador: true);

function lockout($ip)
{    
    $lockout_file = $_SERVER['DOCUMENT_ROOT'] . '/log/login/' . $ip;
    $tempo_limite = 15;

    if (file_exists($lockout_file)) {
        $tempo = time() - filemtime($lockout_file);
        touch($lockout_file);
        if ($tempo < $tempo_limite) {
            $c->logger->acesso('Tentativa de login bloqueada');
            return true;
        }
    } else {
        touch($lockout_file);
    }
    return false;
}

if (lockout($c->guardiao->getIp())) {
	$c->observador->erro("Acesso Negado (".$tempo.")");
} else {
    $login = $c->observador->texto("login");
    $senha = $c->observador->texto("senha");

    if ($c->autenticador->login($login, $senha)) {
        $c->observador->query("SELECT m, eval FROM script WHERE (autorizacao <= " . $_SESSION["autorizacao"] . ") order by ordem, m", false);
        $c->observador->envia("Autenticado");
    } else {
        $c->guardiao->adicionarListaNegra();
        $c->observador->envia("Acesso Negado");
    }
}
