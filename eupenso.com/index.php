<?php
require 'comum/php/guardiao.php';


require 'comum/php/controlador.php';
require 'config/config.php';

$guardiao = new Guardiao();
$controlador = new Controlador($guardiao);
$guardiao->tempo->stop();
