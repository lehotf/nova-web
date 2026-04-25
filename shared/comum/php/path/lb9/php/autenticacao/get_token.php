<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);

$c->observador->erro('Endpoint desativado.');
