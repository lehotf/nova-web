<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(guardiao: true, logger: true, autenticador: true, observador: true);

function lockout($ip, $logger)
{    
    $lockout_file = $_SERVER['DOCUMENT_ROOT'] . '/log/login/' . $ip;
    $tempo_limite = 15;
    $tempo_restante = 0;

    if (file_exists($lockout_file)) {
        $tempo = time() - filemtime($lockout_file);
        touch($lockout_file);
        if ($tempo < $tempo_limite) {
            $tempo_restante = $tempo_limite - $tempo;
            $logger->acesso('Tentativa de login bloqueada');
            return $tempo_restante;
        }
    } else {
        touch($lockout_file);
    }

    return 0;
}

if ($tempo_restante = lockout($c->guardiao->getIp(), $c->logger)) {
    $c->observador->erro("Acesso Negado ({$tempo_restante})");
} else {
    $login = $c->observador->texto("login");
    $senha = $c->observador->texto("senha");

    if ($c->autenticador->login($login, $senha)) {
        $c->observador->envia("Autenticado");
    } else {
        $c->guardiao->adicionarListaNegra();
        $c->observador->envia("Acesso Negado");
    }
}
