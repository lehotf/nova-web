<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);

$c->autenticador->acesso(2);

$c->observador->envia('Autenticado', 'ok');


