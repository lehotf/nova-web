<?php
require 'comum/php/guardiao.php';
$guardiao = new Guardiao();

require 'comum/php/controlador.php';
require 'config/config.php';

$controlador = new Controlador($guardiao);
$contadorDeTempo->echo_time();
