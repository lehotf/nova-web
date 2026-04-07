<?php
require $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';

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
        'monta_artigo'      => 'monta_artigo.php',
    ];

    if (isset($map[$class])) {
        require __DIR__ . '/src/' . $map[$class];
    }
}

spl_autoload_register('class_autoload');
