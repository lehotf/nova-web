<?php

function class_autoload($class)
{
    static $map = [
        'observador'        => 'observador.php',
        'guardiao'          => 'guardiao.php',
        'database'          => 'db.php',
        'autenticador'      => 'autenticador.php',
        'carregador'        => 'carregador.php',
        'controlador'       => 'controlador.php',
        'contador_de_tempo' => 'guardiao.php',
        'logger'            => 'guardiao.php',
        'cache'             => 'controlador.php',
    ];

    if (isset($map[$class])) {
        require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/' . $map[$class];
    }
}

spl_autoload_register('class_autoload');
