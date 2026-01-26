<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/autenticador.php';
$a->acesso(2,1);

require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/montador/lista.php';

$conteudo = lista([
    'id'      => 'listaDeTags',
    'link'    => 'editaTag/',
    'select'  => "id as path, nome from tags order by nome",
    'colunas' => 3,
]);

$titulo     = "Tags existentes";
$explicacao = 'Selecione uma tag para editar.';
$conteudo   = '<input type="button" style="margin-bottom:30px" class="botao" value="Cadastrar Nova Tag" onclick="window.location.href=\'/adm/editaTag\'">' . $conteudo;

$this->prepara('_adm', [
    'titulo'     => $titulo,
    'explicacao' => $explicacao,
    'conteudo'   => $conteudo,
    'css'        => ['comum/adm'],
    'noCache'    => true,
]);
