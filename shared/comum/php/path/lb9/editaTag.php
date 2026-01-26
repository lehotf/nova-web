<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/autenticador.php';
$a->acesso(2,1);

$this->prepara('_adm', [
    'titulo'     => 'Editando Tag',
    'explicacao' => 'Utilize a ferramenta abaixo para editar categorias no banco de dados.',
    'js'         => ['comum/geral', 'comum/modulo', 'comum/cadastraLink', 'comum/form', 'comum/cortaThumb', 'comum/editaTag'],
    'css'        => ['comum/adm'],
    'noCache'    => true,
]);
