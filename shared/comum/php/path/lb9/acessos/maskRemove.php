<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/autenticador.php';
$a->acesso(2, 1);

$titulo     = "REMOVER geoMask";
$explicacao = 'Máscara de IP por localização';

define("LOG_FOLDER", $_SERVER['DOCUMENT_ROOT'] . '/log/mask/alertas');

function verifica($dir)
{
    if (!file_exists($dir)) {
        return '';
    }

    $lista=[];
    $ffs = scandir($dir);

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

function getIPfromMaskFile($mask)
{

    $ipsFromMask = explode("\n", file_get_contents(LOG_FOLDER . '/' . $mask));
    $ipsFromMask = array_unique($ipsFromMask);
    $listagem    = '<div>';
    foreach ($ipsFromMask as $ip) {
        if ($ip == "") {
            continue;
        }

        $listagem .= '<a href="javascript:verifica_ip(\'' . $ip . '\')">' . $ip . '</a>';
    }
    $listagem .= '</div>';
    return $listagem;
}

$lista    = verifica(LOG_FOLDER);
$conteudo = '<div id="lista_mask"><div><form action="/comum/php/sistema/acesso/remove_geomask.php"method="get"><input type="text" name="ip_manual"></form></div>';

$time = time();
foreach ($lista as $mask) {    
    $conteudo .= '<p>' . $mask . '</p><a class="mask_ok" href="/comum/php/sistema/acesso/remove_geomask.php?mask=' . $mask . '&action=ok&time='.$time.'">OK - Remover</a><a class="mask_del"href="/comum/php/sistema/acesso/remove_geomask.php?mask=' . $mask . '&action=del&time='.$time.'">DEL - Manter</a>';
    $conteudo .= getIPfromMaskFile($mask);
}

$conteudo .= "</div>";

if (isset($_GET['msg'])) {
    $tipo_erro = (isset($_GET['tipo']) ? $_GET['tipo'] : 'comum');
    $conteudo  = '<div class="adm_msg_' . $tipo_erro . '">' . $_GET['msg'] . '</div>'.$conteudo;
}

$this->prepara('_adm', [
    'titulo'     => $titulo,
    'explicacao' => $explicacao,
    'conteudo'   => $conteudo,
    'noCache'    => true,
    'js'         => ['comum/geral', 'comum/modulo','comum/geomask'],
    'css'        => ['comum/adm'],
]);
