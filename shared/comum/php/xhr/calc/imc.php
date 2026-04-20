<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'peso'   => ['tipo' => 'numero', 'min' => 10], // Mínimo 0,10 kg em formato inteiro
    'altura' => ['tipo' => 'numero', 'min' => 10]  // Mínimo 0,10 m em formato inteiro
]);

$pesoReal   = (float) ($payload['peso'] ?? 0) / 100;
$alturaReal = (float) ($payload['altura'] ?? 0) / 100;

if ($pesoReal <= 0 || $alturaReal <= 0) {
    $o->erro('Informe peso e altura válidos.');
}

$imc = $pesoReal / ($alturaReal * $alturaReal);

$classificacao = '';
if ($imc < 18.5) {
    $classificacao = 'Abaixo do peso';
} elseif ($imc < 25) {
    $classificacao = 'Peso normal';
} elseif ($imc < 30) {
    $classificacao = 'Sobrepeso';
} elseif ($imc < 35) {
    $classificacao = 'Obesidade Grau I';
} elseif ($imc < 40) {
    $classificacao = 'Obesidade Grau II';
} else {
    $classificacao = 'Obesidade Grau III';
}

$o->r['resultado'] = [
    'imc' => round($imc, 2),
    'classificacao' => $classificacao,
    'peso' => $pesoReal,
    'altura' => $alturaReal
];

$o->envia('IMC calculado com sucesso.');
