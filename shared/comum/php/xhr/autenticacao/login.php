<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$guardiao = new guardiao();
$o = new observador();
$a = new autenticador();

function lockout($ip)
{
    global $tempo;
    $lockout_file = $_SERVER['DOCUMENT_ROOT'] . '/log/login/' . $ip;
    $tempo_limite = 15;

    if (file_exists($lockout_file)) {
        $tempo = time() - filemtime($lockout_file);
        touch($lockout_file);
        if ($tempo < $tempo_limite) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/sistema/acessos', date('d/m/Y H:i:s') . ' ' . $ip . ' ' . $_SERVER['REQUEST_URI'] . ' - Tentativa de login' . "\n", FILE_APPEND);
            return true;
        }
    } else {
        touch($lockout_file);
    }
    return false;
}

if (lockout($guardiao->getIp())) {
	$o->erro("Acesso Negado (".$tempo.")");
} else {
    $login = $o->texto("login");
    $senha = $o->texto("senha");

    if ($a->login($login, $senha)) {
        $o->query("SELECT m, eval FROM script WHERE (autorizacao <= " . $_SESSION["autorizacao"] . ") order by ordem, m", false);
        $o->envia("Autenticado");
    } else {
        $o->envia("Acesso Negado");
    }
}
