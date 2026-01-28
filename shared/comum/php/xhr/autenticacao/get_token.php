<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);

$c->observador->r["token"] = md5($_SERVER["REMOTE_ADDR"]);
$c->observador->envia();