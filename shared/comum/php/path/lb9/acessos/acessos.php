<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/autenticador.php';
$a->acesso(2);

$titulo     = "Acessos";
$explicacao = 'Estão listados abaixo os acessos recentes.';

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/cache/sistema/acessos')) {
    $acessos = '<div style="margin-top: 30px;font-size: 19px;font-weight: bold;margin-bottom: 21px;">ACESSOS PERMITIDOS</div><pre id="acesso">' . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/sistema/acessos') . "</pre>";
} else {
    $acessos = '';
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/cache/sistema/acessos_negados')) {
    $acessos_negados = '<div style="margin-top: 30px;font-size: 19px;font-weight: bold;margin-bottom: 21px;">ACESSOS NEGADOS</div><pre id="acesso_negado">' . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/sistema/acessos_negados') . "</pre>";
} else {
    $acessos_negados = '';
}

$this->prepara('_adm', [
    'titulo'     => $titulo,
    'explicacao' => $explicacao,
    'conteudo'   => $acessos . $acessos_negados,
    'noCache'    => true,
    'css'        => ['comum/adm'],
]);
