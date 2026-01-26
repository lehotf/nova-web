<?php
require $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/observador.php';
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autenticador.php';

$o = new observador();

define('LOCKOUT_FILE', $_SERVER['DOCUMENT_ROOT'] . '/log/login/' . $o->guardiao->getIp());
define('TEMPO_LIMITE', 15);

function lockout()
{
    global $tempo;

    if (file_exists(LOCKOUT_FILE)) {
        $tempo = time() - filemtime(LOCKOUT_FILE);
        touch(LOCKOUT_FILE);
        if ($tempo < TEMPO_LIMITE) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/sistema/acessos', date('d/m/Y H:i:s') . ' ' . USER_IP . ' ' . $_SERVER['REQUEST_URI'] . ' - Tentativa de login' . "\n", FILE_APPEND);
            return true;
        }
    } else {
        touch(LOCKOUT_FILE);
    }
    return false;
}

if (lockout()) {
	$o->erro("Acesso Negado (".$tempo.")");
} else {
    $login = $o->texto("login");
    $senha = $o->texto("senha");

    if ($a->login($login, $senha)) {
        $o->query("SELECT m, eval FROM script WHERE (autorizacao <= " . $_SESSION["autorizacao"] . ") order by ordem, m", false);
        $o->responde("Autenticado");
    } else {
        $o->responde("Acesso Negado");
    }
}
