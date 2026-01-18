<?php
require 'comum/php/guardiao.php';

$guardiao = new Guardiao();
if ($guardiao->ipEmListaNegra()) {
    $guardiao->pnf();
}

require 'comum/php/controlador.php';
require 'comum/config/config.php';
require 'config/config.php';

$controlador = new Controlador($guardiao);

