<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'investimento_inicial' => ['tipo' => 'numero', 'min' => 0],
    'aplicacao_mensal' => ['tipo' => 'numero', 'min' => 0],
    'numero_meses' => ['tipo' => 'numero', 'min' => 1, 'max' => 600],
    'taxa_juros_mensal' => ['tipo' => 'numero', 'min' => 0, 'max' => 100]
]);

$investimentoInicial = (float) ($payload['investimento_inicial'] ?? 0);
$aplicacaoMensal = (float) ($payload['aplicacao_mensal'] ?? 0);
$numeroMeses = (int) ($payload['numero_meses'] ?? 0);
$taxaJurosMensal = (float) ($payload['taxa_juros_mensal'] ?? 0);

$taxaDecimal = $taxaJurosMensal / 100;
$totalInvestido = $investimentoInicial + ($aplicacaoMensal * $numeroMeses);
$valorFuturo = $investimentoInicial;

for ($mes = 1; $mes <= $numeroMeses; $mes++) {
    $valorFuturo += $aplicacaoMensal;
    $valorFuturo *= (1 + $taxaDecimal);
}

$jurosAcumulados = $valorFuturo - $totalInvestido;

$o->r['resultado'] = [
    'investimento_inicial' => round($investimentoInicial, 2),
    'aplicacao_mensal' => round($aplicacaoMensal, 2),
    'numero_meses' => $numeroMeses,
    'taxa_juros_mensal' => round($taxaJurosMensal, 4),
    'taxa_decimal' => round($taxaDecimal, 8),
    'total_investido' => round($totalInvestido, 2),
    'juros_acumulados' => round($jurosAcumulados, 2),
    'valor_futuro' => round($valorFuturo, 2),
    'metodologia' => 'aportes_no_inicio_do_mes'
];

$o->envia('Simulação calculada com sucesso.');
