<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'investimento_inicial' => ['tipo' => 'numero', 'min' => 0],
    'aplicacao_mensal' => ['tipo' => 'numero', 'min' => 0],
    'numero_meses' => ['tipo' => 'numero', 'min' => 1, 'max' => 600]
]);

$investimentoInicial = (float) ($payload['investimento_inicial'] ?? 0);
$aplicacaoMensal = (float) ($payload['aplicacao_mensal'] ?? 0);
$numeroMeses = (int) ($payload['numero_meses'] ?? 0);

// Conexão com o BD do controlador
$db = $c->db;

// Consulta o valor da taxa na tabela indices onde codigo = 195
$res = $db->query("SELECT valor FROM indices WHERE codigo = 195 LIMIT 1");

if (!($res instanceof mysqli_result) || !($row = $res->fetch_assoc())) {
    $o->erro('A taxa de juros atual da poupança não pôde ser encontrada no banco de dados.');
}

$taxaJurosMensal = (float) $row['valor'];
$taxaDecimal = $taxaJurosMensal / 100;

$totalInvestido = $investimentoInicial + ($aplicacaoMensal * $numeroMeses);
$valorFuturo = $investimentoInicial;

// Calcula os juros compostos considerando os aportes ao início de cada período
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

$o->envia('Rentabilidade da Poupança calculada com sucesso.');
