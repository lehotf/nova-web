<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'investimento_inicial' => ['tipo' => 'numero', 'min' => 0],
    'aplicacao_mensal' => ['tipo' => 'numero', 'min' => 0],
    'numero_meses' => ['tipo' => 'numero', 'min' => 1, 'max' => 600],
    'taxa_juros_mensal' => ['tipo' => 'numero', 'min' => 0, 'max' => 2]
]);

$investimentoInicial = (float) ($payload['investimento_inicial'] ?? 0);
$aplicacaoMensal = (float) ($payload['aplicacao_mensal'] ?? 0);
$numeroMeses = (int) ($payload['numero_meses'] ?? 0);
$taxaJurosMensal = (float) ($payload['taxa_juros_mensal'] ?? 0);

$taxaDecimal = $taxaJurosMensal / 100;
$totalInvestido = $investimentoInicial + ($aplicacaoMensal * $numeroMeses);
$valorFuturo = $investimentoInicial;
$relatorio = [];
$mesAtualSimulacao = (int) date('n');
$anoAtualSimulacao = (int) date('Y');

for ($mes = 1; $mes <= $numeroMeses; $mes++) {
    $aporteMes = ($mes === 1) ? $investimentoInicial + $aplicacaoMensal : $aplicacaoMensal;
    $valorFuturo += $aplicacaoMensal;
    $rendimento = $valorFuturo * $taxaDecimal;
    $valorFuturo += $rendimento;

    $relatorio[] = [
        'mes_relativo' => $mes,
        'mes_ano' => str_pad((string) $mesAtualSimulacao, 2, '0', STR_PAD_LEFT) . '/' . $anoAtualSimulacao,
        'aporte' => round($aporteMes, 2),
        'rendimento' => round($rendimento, 2),
        'saldo_final' => round($valorFuturo, 2)
    ];

    $mesAtualSimulacao++;
    if ($mesAtualSimulacao > 12) {
        $mesAtualSimulacao = 1;
        $anoAtualSimulacao++;
    }
}

$jurosAcumulados = $valorFuturo - $totalInvestido;
$valorLiquido = $valorFuturo;

$db = $c->db;
$res = $db->query("SELECT valor, data FROM indices WHERE codigo = 189 LIMIT 1");
$igpmMensal = 0;
$igpmData = '';
if ($res instanceof mysqli_result && ($row = $res->fetch_assoc())) {
    $igpmMensal = (float) $row['valor'];
    $time = strtotime($row['data']);
    $igpmData = $time ? date('m/Y', $time) : $row['data'];
}
$igpmDecimal = $igpmMensal / 100;
$inflacaoPeriodoDecimal = pow(1 + $igpmDecimal, $numeroMeses) - 1;

$valorRealInvestido = $investimentoInicial + $aplicacaoMensal;
for ($mes = 2; $mes <= $numeroMeses; $mes++) {
    $valorRealInvestido += $aplicacaoMensal / pow(1 + $igpmDecimal, $mes - 1);
}

$valorRealFinal = $valorLiquido / (1 + $inflacaoPeriodoDecimal);
$rentabilidadeRealLiquida = $valorRealFinal - $valorRealInvestido;

$o->r['resultado'] = [
    'investimento_inicial' => round($investimentoInicial, 2),
    'aplicacao_mensal' => round($aplicacaoMensal, 2),
    'numero_meses' => $numeroMeses,
    'taxa_juros_mensal' => round($taxaJurosMensal, 4),
    'taxa_decimal' => round($taxaDecimal, 8),
    'total_investido' => round($totalInvestido, 2),
    'juros_acumulados' => round($jurosAcumulados, 2),
    'imposto_resgate' => 0,
    'valor_futuro' => round($valorFuturo, 2),
    'valor_liquido' => round($valorLiquido, 2),
    'igpm_mensal' => round($igpmMensal, 4),
    'igpm_data' => $igpmData,
    'inflacao_periodo_decimal' => round($inflacaoPeriodoDecimal, 6),
    'valor_real_investido' => round($valorRealInvestido, 2),
    'valor_real_final' => round($valorRealFinal, 2),
    'rentabilidade_real_liquida' => round($rentabilidadeRealLiquida, 2),
    'categoria_produto' => 'lci_lca',
    'observacoes_modelo' => [
        'Simulação voltada para LCI/LCA com aportes no início de cada mês.',
        'O produto foi tratado como isento de imposto de renda.',
        'Por isso, a rentabilidade projetada já é líquida de IR.',
        'A inflação foi projetada com repetição da última taxa mensal de IGP-M encontrada na base.'
    ],
    'metodologia' => 'aportes_no_inicio_do_mes',
    'relatorio' => $relatorio
];

$o->envia('Simulação calculada com sucesso.');
