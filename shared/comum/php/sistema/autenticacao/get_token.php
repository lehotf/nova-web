<?php
require $_SERVER['DOCUMENT_ROOT'].'/config/config.php';
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/observador.php';

$o = new observador();
$o->dados["token"] = md5($_SERVER["REMOTE_ADDR"]);
$o->responde($o->dados);