<?php
$titulo = "Tags em destaque";

$this->prepara('_adm', [
    'titulo'   => $titulo,    
    'noCache'  => true,
    'js'       => ['comum/geral', 'comum/modulo', 'comum/form', 'comum/tagsEmDestaque'],
    'css'      => ['comum/adm'],
]);
