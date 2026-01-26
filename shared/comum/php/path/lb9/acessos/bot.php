<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/autenticador.php';
$a->acesso(2);

$titulo     = "ADICIONAR bot";
$explicacao = 'Adicionar novo bot ao index';

define("LOG_FOLDER", $_SERVER['DOCUMENT_ROOT'] . '/log/bot/detectado');

function verifica($dir)
{
    if (!file_exists($dir)) {
        return '';
    }

    $lista = [];
    $ffs   = scandir($dir);

    foreach ($ffs as $file) {
        if ($file != '.' && $file != '..') {

            if (is_dir($dir . '/' . $file)) {
                verifica($dir . '/' . $file);
            } else {
                $lista[] = $file;
            }
        }
    }
    return $lista;
}

$lista    = verifica(LOG_FOLDER);
$conteudo = '<div><form action="/comum/php/sistema/acesso/add_bot.php"method="get"><input type="text" name="ip_manual"></form></div><div id="lista_mask">';

$time = time();
foreach ($lista as $ip) {

    preg_match('#(\d+)\.(\d+)\.(\d+)\.\d+#', $ip, $m);
    $subnet = $m[1] . '.' . $m[2] . '.' . $m[3];
    $mask =  $m[1] . '.' . $m[2];

    $conteudo .= '<a href="javascript:verifica_ip(\'' . $ip . '\')">' . $ip . '</a><a class="mask_ok" href="/comum/php/sistema/acesso/add_bot.php?ip=' . $ip . '&action=mask&time=' . $time . '">'.$mask.'</a><a class="mask_ok" href="/comum/php/sistema/acesso/add_bot.php?ip=' . $ip . '&action=subnet&time=' . $time . '">'.$subnet.'</a><a class="mask_del"href="/comum/php/sistema/acesso/add_bot.php?ip=' . $ip . '&action=del&time=' . $time . '">DEL</a><br>';
}

$conteudo .= "</div>";

if (isset($_GET['msg'])) {
    $tipo_erro = (isset($_GET['tipo']) ? $_GET['tipo'] : 'comum');
    $conteudo  = '<div class="adm_msg_' . $tipo_erro . '">' . $_GET['msg'] . '</div>' . $conteudo;
}

$this->prepara('_adm', [
    'titulo'     => $titulo,
    'explicacao' => $explicacao,
    'conteudo'   => $conteudo,
    'noCache'    => true,
    'js'         => ['comum/geral', 'comum/modulo', 'comum/geomask'],
    'css'        => ['comum/adm'],
]);
