<?php
require 'comum/php/autoload.php';
require 'config/config.php';

$contador_de_tempo = new contador_de_tempo();
$logger = new logger();
$guardiao = new guardiao($logger);

$controlador = new controlador($guardiao, $logger, $contador_de_tempo);

$contador_de_tempo->stop();