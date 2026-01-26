<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/autenticador.php';
$a->acesso(2,1);

$this->prepara('_adm', [
    'titulo'     => 'Editando Link',
    'explicacao' => 'Utilize a ferramenta abaixo para editar um link existente',
    'js'         => ['comum/geral', 'comum/modulo', 'comum/cadastraLink', 'comum/form', 'comum/cortaThumb', 'comum/editaLink'],
    'css'        => ['comum/adm'],
    'noCache'    => true,
]);
