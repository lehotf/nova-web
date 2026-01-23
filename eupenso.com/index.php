<?php

require 'comum/php/guardiao.php';
define('DEBUG', true);

$guardiao = new Guardiao();

require 'comum/php/controlador.php';
require 'config/config.php';

$controlador = new Controlador($guardiao);

