<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'investimento_inicial' => ['tipo' => 'numero', 'min' => 0],
    'aplicacao_mensal' => ['tipo' => 'numero', 'min' => 0],
    'numero_meses' => ['tipo' => 'numero', 'min' => 1, 'max' => 120],
    'taxa_juros_mensal' => ['tipo' => 'numero', 'min' => 0, 'max' => 2],
    'percentual_cdi' => ['tipo' => 'numero', 'min' => 0, 'max' => 150],
    'modo_taxa' => ['tipo' => 'texto']
]);

$investimentoInicial = (float) ($payload['investimento_inicial'] ?? 0);
$aplicacaoMensal = (float) ($payload['aplicacao_mensal'] ?? 0);
$numeroMeses = (int) ($payload['numero_meses'] ?? 0);
$modoTaxa = ($payload['modo_taxa'] ?? 'taxa') === 'cdi' ? 'cdi' : 'taxa';
$taxaJurosMensalInformada = (float) ($payload['taxa_juros_mensal'] ?? 0);
$percentualCdi = (float) ($payload['percentual_cdi'] ?? 0);
$db = $c->db;
$cdiMensal = 0.0;
$cdiData = '';
$taxaJurosMensal = $taxaJurosMensalInformada;

if ($modoTaxa === 'cdi') {
    $res = $db->query("SELECT valor, data FROM indices WHERE codigo = 4391 LIMIT 1");

    if (!($res instanceof mysqli_result) || !($row = $res->fetch_assoc())) {
        $o->erro('O CDI atual não pôde ser encontrado no banco de dados.');
    }

    $cdiMensal = (float) $row['valor'];
    $time = strtotime($row['data']);
    $cdiData = $time ? date('m/Y', $time) : $row['data'];
    $taxaJurosMensal = $cdiMensal * ($percentualCdi / 100);
}

$taxaDecimal = $taxaJurosMensal / 100;
$totalInvestido = $investimentoInicial + ($aplicacaoMensal * $numeroMeses);
$fundoCategoria = 'curto_prazo';

$mesAtualSimulacao = (int) date('n');
$anoAtualSimulacao = (int) date('Y');

$totalDescontadoComeCotas = 0;
$relatorio = [];

// Calcula rentabilidade bruta (sem impostos)
$valorFuturoBruto = $investimentoInicial;
for ($mes = 1; $mes <= $numeroMeses; $mes++) {
    $valorFuturoBruto += $aplicacaoMensal;
    $valorFuturoBruto *= (1 + $taxaDecimal);
}
$rentabilidadeBrutaTotal = $valorFuturoBruto - $totalInvestido;

// Consulta o valor do IGPM no banco de dados (codigo = 189)
$res = $db->query("SELECT valor, data FROM indices WHERE codigo = 189 LIMIT 1");
$igpmMensal = 0;
$igpmData = '';
if ($res instanceof mysqli_result && ($row = $res->fetch_assoc())) {
    $igpmMensal = (float) $row['valor'];
    $time = strtotime($row['data']);
    $igpmData = $time ? date('m/Y', $time) : $row['data'];
}
$igpmDecimal = $igpmMensal / 100;

// Calcula inflação do período e valores reais
$inflacaoPeriodoDecimal = pow(1 + $igpmDecimal, $numeroMeses) - 1;

$valorRealInvestido = $investimentoInicial + $aplicacaoMensal;
for ($mes = 2; $mes <= $numeroMeses; $mes++) {
    $valorRealInvestido += $aplicacaoMensal / pow(1 + $igpmDecimal, $mes - 1);
}

// Vetor de baldes para rastrear a idade dos aportes em aproximação mensal.
// Baldes 1..24 representam até 24 meses. O balde 25 acumula tudo acima de 24 meses.
// No fundo de curto prazo, a alíquota final estabiliza em 20% após 6 meses, mas a
// estrutura de baldes foi preservada para manter a mesma metodologia do simulador.
$baldes = [];
for ($i = 1; $i <= 25; $i++) {
    $baldes[$i] = [
        'investido' => 0,
        'saldo' => 0,
        'lucro_tributado' => 0,
        'base_atual' => 0
    ];
}

for ($mes = 1; $mes <= $numeroMeses; $mes++) {
    // 1. Avança os baldes (idade dos aportes)
    $baldes[25]['investido'] += $baldes[24]['investido'];
    $baldes[25]['saldo'] += $baldes[24]['saldo'];
    $baldes[25]['lucro_tributado'] += $baldes[24]['lucro_tributado'];
    $baldes[25]['base_atual'] += $baldes[24]['base_atual'];
    
    for ($i = 24; $i >= 2; $i--) {
        $baldes[$i] = $baldes[$i - 1];
    }
    
    // 2. Entra novo aporte no mês 1
    $aporte_mes = ($mes == 1) ? $investimentoInicial + $aplicacaoMensal : $aplicacaoMensal;
    $baldes[1] = [
        'investido' => $aporte_mes,
        'saldo' => $aporte_mes,
        'lucro_tributado' => 0,
        'base_atual' => $aporte_mes
    ];
    
    // 3. Aplica rentabilidade
    $rendimento = 0;
    foreach ($baldes as $i => &$b) {
        if ($b['saldo'] > 0) {
            $rend_balde = $b['saldo'] * $taxaDecimal;
            $b['saldo'] += $rend_balde;
            $rendimento += $rend_balde;
        }
    }
    unset($b);
    
    // 4. Come-cotas
    $descontoComeCotas = 0;
    if ($mesAtualSimulacao == 5 || $mesAtualSimulacao == 11) {
        foreach ($baldes as $i => &$b) {
            $rend_balde_cc = $b['saldo'] - $b['base_atual'];
            if ($rend_balde_cc > 0) {
                $cc = $rend_balde_cc * 0.20;
                $b['saldo'] -= $cc;
                $b['lucro_tributado'] += $rend_balde_cc;
                $b['base_atual'] = $b['saldo'];
                $descontoComeCotas += $cc;
            }
        }
        unset($b);
        $totalDescontadoComeCotas += $descontoComeCotas;
    }
    
    $valorFuturo = array_sum(array_column($baldes, 'saldo'));
    
    $relatorio[] = [
        'mes_relativo' => $mes,
        'mes_ano' => str_pad((string)$mesAtualSimulacao, 2, '0', STR_PAD_LEFT) . '/' . $anoAtualSimulacao,
        'aporte' => round($aporte_mes, 2),
        'rendimento' => round($rendimento, 2),
        'desconto_come_cotas' => round($descontoComeCotas, 2),
        'saldo_final' => round($valorFuturo, 2)
    ];
    
    $mesAtualSimulacao++;
    if ($mesAtualSimulacao > 12) {
        $mesAtualSimulacao = 1;
        $anoAtualSimulacao++;
    }
}

// 5. Cálculo do IR no resgate
$imposto_resgate_total = 0;
foreach ($baldes as $age => $b) {
    if ($b['saldo'] <= 0) continue;
    
    if ($age <= 6) $aliquota = 0.225;
    else $aliquota = 0.20;
    
    $rendimento_recente = max(0, $b['saldo'] - $b['base_atual']);
    $imposto_recente = $rendimento_recente * $aliquota;
    
    $diferenca_aliquota = max(0, $aliquota - 0.20);
    $imposto_complementar = $b['lucro_tributado'] * $diferenca_aliquota;
    
    $imposto_resgate_total += ($imposto_recente + $imposto_complementar);
}

$valorLiquido = $valorFuturo - $imposto_resgate_total;
$jurosAcumulados = $valorFuturo - $totalInvestido; // Bruto de IR final, líquido de come cotas

$valorRealFinal = $valorLiquido / (1 + $inflacaoPeriodoDecimal);
$rentabilidadeRealLiquida = $valorRealFinal - $valorRealInvestido;

$o->r['resultado'] = [
    'investimento_inicial' => round($investimentoInicial, 2),
    'aplicacao_mensal' => round($aplicacaoMensal, 2),
    'numero_meses' => $numeroMeses,
    'modo_taxa' => $modoTaxa,
    'taxa_juros_mensal_informada' => round($taxaJurosMensalInformada, 4),
    'percentual_cdi' => round($percentualCdi, 4),
    'cdi_mensal' => round($cdiMensal, 4),
    'cdi_data' => $cdiData,
    'taxa_juros_mensal' => round($taxaJurosMensal, 4),
    'taxa_decimal' => round($taxaDecimal, 8),
    'total_investido' => round($totalInvestido, 2),
    'juros_acumulados' => round($jurosAcumulados, 2),
    'total_descontado_come_cotas' => round($totalDescontadoComeCotas, 2),
    'imposto_resgate' => round($imposto_resgate_total, 2),
    'valor_futuro' => round($valorFuturo, 2),
    'valor_liquido' => round($valorLiquido, 2),
    'rentabilidade_bruta_total' => round($rentabilidadeBrutaTotal, 2),
    'valor_futuro_bruto' => round($valorFuturoBruto, 2),
    'igpm_mensal' => round($igpmMensal, 4),
    'igpm_data' => $igpmData,
    'inflacao_periodo_decimal' => round($inflacaoPeriodoDecimal, 6),
    'valor_real_investido' => round($valorRealInvestido, 2),
    'valor_real_final' => round($valorRealFinal, 2),
    'rentabilidade_real_liquida' => round($rentabilidadeRealLiquida, 2),
    'categoria_fundo' => $fundoCategoria,
    'come_cotas_aliquota' => 0.20,
    'observacoes_modelo' => [
        'Simulação voltada para fundo de renda fixa de curto prazo sujeito a come-cotas.',
        'A tabela regressiva foi aproximada em meses, embora a legislação tributária utilize contagem em dias.',
        $modoTaxa === 'cdi'
            ? 'A taxa mensal usada na simulação foi obtida a partir do percentual do CDI informado e do último CDI mensal encontrado na tabela indices (código 4391).'
            : 'A taxa mensal usada na simulação foi a taxa de juros mensal informada diretamente pelo usuário.',
        'A inflação foi projetada com repetição da última taxa mensal de IGP-M encontrada na base.'
    ],
    'metodologia' => 'aportes_no_inicio_do_mes',
    'relatorio' => $relatorio
];

$o->envia('Simulação calculada com sucesso.');
