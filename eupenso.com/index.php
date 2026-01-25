<?php
require 'comum/php/guardiao.php';


require 'comum/php/controlador.php';
require 'config/config.php';

$guardiao = new Guardiao();


$controlador = new Controlador($guardiao);
$contadorDeTempo->echo_time();
