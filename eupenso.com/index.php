<?php
require 'comum/php/autoload.php';

$contador_de_tempo = new contador_de_tempo();
$c = new controlador(guardiao: true);
$c->carrega_pagina($contador_de_tempo);
$contador_de_tempo->stop();
