<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/autenticador.php';
$a->acesso(2,1);

require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/montador/lista.php';

if (isset($this->comando[0]) && ($this->comando[0] == 'remove')) {
    $onclick = function ($item) {return 'removeLink(' . $item['path'] . ', this)';};
    $titulo = "Removendo Link";
    $menu   = '<div class="menu_topo"><a class="botao_topo" href="/adm/editaLink">Cadastrar Novo Link</a><a class="botao_topo" href="/adm/gerenciaLink">Gerenciar Link</a></div>';
    $link = null;
} else {
    $link   = '/adm/editaLink/';
    $titulo = "Links existentes";
    $menu   = '<div class="menu_topo"><a class="botao_topo" href="/adm/editaLink">Cadastrar Novo Link</a><a class="botao_topo" href="/adm/gerenciaLink/remove">Remover Link</a><a href="/adm/gerenciaLink/bd/eupenso">Eu Penso</a><a href="/adm/gerenciaLink/bd/quemoleza">Que Moleza</a><a href="/adm/gerenciaLink/bd/faqbrasil">FAQ Brasil</a></div>';
}

if (isset($this->comando[0]) && ($this->comando[0] == 'bd')){    
    if (isset($this->comando[1])){ 
        global $db;       
        $db->connect($this->comando[1]);
        $link .= 'bd/'.$this->comando[1].'/';        
    }
}

$conteudo = relaciona([
    'id'      => 'listaDeLinks',
    'link'    => $link,
    'onclick' => isset($onclick)?$onclick:null,
    'select'  => "path as nome, id as path from links",
    'colunas' => 3,
    'where1'  => 'publicado = 0',
    'where2'  => 'publicado = 1',
    'order'   => 'order by titulo',
]);

$conteudo = $menu . $conteudo;

$this->prepara('_adm', [
    'titulo'     => $titulo,    
    'conteudo'   => $conteudo,
    'css'        => ['comum/adm'],
    'js'         => ['comum/geral', 'comum/form', 'comum/removeLink'],
    'noCache'    => true,
]);
