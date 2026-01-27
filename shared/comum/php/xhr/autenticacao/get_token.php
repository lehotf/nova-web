<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$o = new observador();
$o->r["token"] = md5($_SERVER["REMOTE_ADDR"]);
$o->envia();