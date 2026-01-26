<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/autenticador.php';
$a->acesso(2,1);

require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/montador/lista.php';

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
                $lista[] = ['nome' => $file, 'path' => 'comum/php/sistema/remove/cache.php?p='.$file];
            }
        }
    }
    
    return $lista;
}

$conteudo = lista([
    'id'      => 'listaDeTags',
    'link'    => '/',
    'dados'   => verifica($_SERVER['DOCUMENT_ROOT'] . '/cache/html'),
    'colunas' => 3,
]);

$titulo     = "Cache existente";
$explicacao = 'Observe os arquivos que foram gravados no cache';

$this->prepara('_adm', [
    'titulo'     => $titulo,
    'explicacao' => $explicacao,
    'conteudo'   => $conteudo,
    'css'        => ['comum/adm'],
    'noCache'    => true,
]);
