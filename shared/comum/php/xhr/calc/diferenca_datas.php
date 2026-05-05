<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'data_inicial' => ['tipo' => 'string'],
    'data_final'   => ['tipo' => 'string']
]);

$data_inicial_str = $payload['data_inicial'] ?? '';
$data_final_str   = $payload['data_final'] ?? '';

if (!$data_inicial_str || !$data_final_str) {
    $o->erro('Por favor, informe ambas as datas.');
}

try {
    $data_inicial = new DateTime($data_inicial_str);
    $data_final   = new DateTime($data_final_str);
} catch (Exception $e) {
    $o->erro('Formato de data inválido.');
}

$intervalo = $data_inicial->diff($data_final);

$total_dias = $intervalo->days;
if ($data_inicial > $data_final) {
    $total_dias = -$total_dias;
}

$o->r['resultado'] = [
    'anos'       => $intervalo->y,
    'meses'      => $intervalo->m,
    'dias'       => $intervalo->d,
    'total_dias' => $total_dias,
    'inversa'    => $data_inicial > $data_final
];

$o->envia('Diferença entre datas calculada com sucesso.');
