<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'investimento_inicial' => ['tipo' => 'numero', 'min' => 0],
    'aplicacao_mensal' => ['tipo' => 'numero', 'min' => 0],
    'numero_meses' => ['tipo' => 'numero', 'min' => 1, 'max' => 120],
    'taxa_juros_mensal' => ['tipo' => 'numero', 'min' => 0, 'max' => 2],
    'prazo_vencimento_anos' => ['tipo' => 'numero', 'min' => 1, 'max' => 10]
]);

$investimentoInicial = (float) ($payload['investimento_inicial'] ?? 0);
$aplicacaoMensal = (float) ($payload['aplicacao_mensal'] ?? 0);
$numeroMeses = (int) ($payload['numero_meses'] ?? 0);
$taxaJurosMensal = (float) ($payload['taxa_juros_mensal'] ?? 0);
$prazoVencimentoAnos = (int) ($payload['prazo_vencimento_anos'] ?? 5);
$prazoVencimentoMeses = $prazoVencimentoAnos * 12;

$taxaDecimal = $taxaJurosMensal / 100;
$totalInvestido = $investimentoInicial + ($aplicacaoMensal * $numeroMeses);

function criarBaldes(): array
{
    $baldes = [];
    for ($i = 1; $i <= 25; $i++) {
        $baldes[$i] = [
            'investido' => 0.0,
            'saldo' => 0.0
        ];
    }

    return $baldes;
}

function avancarBaldes(array $baldes): array
{
    $baldes[25]['investido'] += $baldes[24]['investido'];
    $baldes[25]['saldo'] += $baldes[24]['saldo'];

    for ($i = 24; $i >= 2; $i--) {
        $baldes[$i] = $baldes[$i - 1];
    }

    $baldes[1] = [
        'investido' => 0.0,
        'saldo' => 0.0
    ];

    return $baldes;
}

function somarSaldoBaldes(array $baldes): float
{
    return (float) array_sum(array_column($baldes, 'saldo'));
}

function aliquotaIrCdbPorMes(int $idadeMeses): float
{
    if ($idadeMeses <= 6) {
        return 0.225;
    }
    if ($idadeMeses <= 12) {
        return 0.20;
    }
    if ($idadeMeses <= 24) {
        return 0.175;
    }

    return 0.15;
}

function calcularImpostoResgateBaldes(array $baldes): array
{
    $imposto = 0.0;

    foreach ($baldes as $idade => $balde) {
        if ($balde['saldo'] <= 0) {
            continue;
        }

        $lucro = max(0, $balde['saldo'] - $balde['investido']);
        $imposto += $lucro * aliquotaIrCdbPorMes((int) $idade);
    }

    $saldoBruto = somarSaldoBaldes($baldes);
    $saldoLiquido = $saldoBruto - $imposto;

    return [
        'saldo_bruto' => $saldoBruto,
        'imposto' => $imposto,
        'saldo_liquido' => $saldoLiquido
    ];
}

function simularCdb(
    float $investimentoInicial,
    float $aplicacaoMensal,
    int $numeroMeses,
    float $taxaDecimal,
    int $prazoVencimentoMeses,
    bool $forcarResgatesObrigatorios,
    int $mesAtualSimulacao,
    int $anoAtualSimulacao
): array {
    $baldes = criarBaldes();
    $relatorio = [];
    $eventosResgate = [];
    $totalIrIntermediario = 0.0;
    $impostoResgateFinal = 0.0;
    $saldoBrutoFinal = 0.0;
    $saldoLiquidoFinal = 0.0;
    $houveResgateObrigatorio = false;

    for ($mes = 1; $mes <= $numeroMeses; $mes++) {
        $baldes = avancarBaldes($baldes);

        $aporteMes = ($mes === 1) ? $investimentoInicial + $aplicacaoMensal : $aplicacaoMensal;
        $baldes[1]['investido'] += $aporteMes;
        $baldes[1]['saldo'] += $aporteMes;

        $rendimento = 0.0;
        foreach ($baldes as &$balde) {
            if ($balde['saldo'] <= 0) {
                continue;
            }

            $rendimentoBalde = $balde['saldo'] * $taxaDecimal;
            $balde['saldo'] += $rendimentoBalde;
            $rendimento += $rendimentoBalde;
        }
        unset($balde);

        $saldoMes = somarSaldoBaldes($baldes);
        $resgateObrigatorioMes = false;
        $impostoResgateMes = 0.0;
        $saldoLiquidoReinvestido = 0.0;

        if ($forcarResgatesObrigatorios && $mes % $prazoVencimentoMeses === 0) {
            $houveResgateObrigatorio = true;
            $resgateObrigatorioMes = true;
            $apuracaoResgate = calcularImpostoResgateBaldes($baldes);
            $saldoMes = $apuracaoResgate['saldo_bruto'];
            $impostoResgateMes = $apuracaoResgate['imposto'];

            $evento = [
                'mes_relativo' => $mes,
                'mes_ano' => str_pad((string) $mesAtualSimulacao, 2, '0', STR_PAD_LEFT) . '/' . $anoAtualSimulacao,
                'saldo_bruto' => round($apuracaoResgate['saldo_bruto'], 2),
                'imposto' => round($apuracaoResgate['imposto'], 2),
                'saldo_liquido' => round($apuracaoResgate['saldo_liquido'], 2),
                'houve_reinvestimento' => $mes < $numeroMeses
            ];

            if ($mes < $numeroMeses) {
                $totalIrIntermediario += $apuracaoResgate['imposto'];
                $saldoLiquidoReinvestido = $apuracaoResgate['saldo_liquido'];
                $evento['tipo'] = 'intermediario';

                $baldes = criarBaldes();
                $baldes[1] = [
                    'investido' => $saldoLiquidoReinvestido,
                    'saldo' => $saldoLiquidoReinvestido
                ];
            } else {
                $impostoResgateFinal = $apuracaoResgate['imposto'];
                $saldoBrutoFinal = $apuracaoResgate['saldo_bruto'];
                $saldoLiquidoFinal = $apuracaoResgate['saldo_liquido'];
                $evento['tipo'] = 'final_vencimento';
            }

            $eventosResgate[] = $evento;
        }

        $relatorio[] = [
            'mes_relativo' => $mes,
            'mes_ano' => str_pad((string) $mesAtualSimulacao, 2, '0', STR_PAD_LEFT) . '/' . $anoAtualSimulacao,
            'aporte' => round($aporteMes, 2),
            'rendimento' => round($rendimento, 2),
            'resgate_obrigatorio' => $resgateObrigatorioMes,
            'imposto_resgate' => round($impostoResgateMes, 2),
            'saldo_final' => round($saldoMes, 2),
            'saldo_liquido_reinvestido' => round($saldoLiquidoReinvestido, 2)
        ];

        $mesAtualSimulacao++;
        if ($mesAtualSimulacao > 12) {
            $mesAtualSimulacao = 1;
            $anoAtualSimulacao++;
        }
    }

    if ($saldoBrutoFinal === 0.0 && $saldoLiquidoFinal === 0.0) {
        $apuracaoFinal = calcularImpostoResgateBaldes($baldes);
        $saldoBrutoFinal = $apuracaoFinal['saldo_bruto'];
        $impostoResgateFinal = $apuracaoFinal['imposto'];
        $saldoLiquidoFinal = $apuracaoFinal['saldo_liquido'];
    }

    return [
        'relatorio' => $relatorio,
        'eventos_resgate' => $eventosResgate,
        'total_ir_intermediario' => $totalIrIntermediario,
        'imposto_resgate_final' => $impostoResgateFinal,
        'imposto_total' => $totalIrIntermediario + $impostoResgateFinal,
        'saldo_bruto_final' => $saldoBrutoFinal,
        'saldo_liquido_final' => $saldoLiquidoFinal,
        'houve_resgate_obrigatorio' => $houveResgateObrigatorio,
        'quantidade_resgates_intermediarios' => count(array_filter($eventosResgate, static fn($evento) => $evento['tipo'] === 'intermediario')),
        'quantidade_vencimentos' => count($eventosResgate)
    ];
}

$mesAtualSimulacao = (int) date('n');
$anoAtualSimulacao = (int) date('Y');

// Referência bruta sem impostos.
$valorFuturoBruto = $investimentoInicial;
for ($mes = 1; $mes <= $numeroMeses; $mes++) {
    $valorFuturoBruto += $aplicacaoMensal;
    $valorFuturoBruto *= (1 + $taxaDecimal);
}
$rentabilidadeBrutaTotal = $valorFuturoBruto - $totalInvestido;

$simulacaoComVencimentos = simularCdb(
    $investimentoInicial,
    $aplicacaoMensal,
    $numeroMeses,
    $taxaDecimal,
    $prazoVencimentoMeses,
    true,
    $mesAtualSimulacao,
    $anoAtualSimulacao
);

$simulacaoSemVencimentos = simularCdb(
    $investimentoInicial,
    $aplicacaoMensal,
    $numeroMeses,
    $taxaDecimal,
    $prazoVencimentoMeses,
    false,
    $mesAtualSimulacao,
    $anoAtualSimulacao
);

$valorFuturo = $simulacaoComVencimentos['saldo_bruto_final'];
$valorLiquido = $simulacaoComVencimentos['saldo_liquido_final'];
$jurosAcumulados = $valorFuturo - $totalInvestido;
$impactoResgatesObrigatorios = $valorLiquido - $simulacaoSemVencimentos['saldo_liquido_final'];

// Consulta IGP-M.
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
    'prazo_vencimento_anos' => $prazoVencimentoAnos,
    'prazo_vencimento_meses' => $prazoVencimentoMeses,
    'taxa_juros_mensal' => round($taxaJurosMensal, 4),
    'taxa_decimal' => round($taxaDecimal, 8),
    'total_investido' => round($totalInvestido, 2),
    'juros_acumulados' => round($jurosAcumulados, 2),
    'imposto_resgates_intermediarios' => round($simulacaoComVencimentos['total_ir_intermediario'], 2),
    'imposto_resgate_final' => round($simulacaoComVencimentos['imposto_resgate_final'], 2),
    'imposto_total' => round($simulacaoComVencimentos['imposto_total'], 2),
    'valor_futuro' => round($valorFuturo, 2),
    'valor_liquido' => round($valorLiquido, 2),
    'valor_futuro_bruto' => round($valorFuturoBruto, 2),
    'rentabilidade_bruta_total' => round($rentabilidadeBrutaTotal, 2),
    'valor_liquido_sem_vencimentos_intermediarios' => round($simulacaoSemVencimentos['saldo_liquido_final'], 2),
    'impacto_resgates_obrigatorios' => round($impactoResgatesObrigatorios, 2),
    'houve_resgate_obrigatorio' => $simulacaoComVencimentos['houve_resgate_obrigatorio'],
    'quantidade_resgates_intermediarios' => $simulacaoComVencimentos['quantidade_resgates_intermediarios'],
    'quantidade_vencimentos' => $simulacaoComVencimentos['quantidade_vencimentos'],
    'eventos_resgate' => $simulacaoComVencimentos['eventos_resgate'],
    'igpm_mensal' => round($igpmMensal, 4),
    'igpm_data' => $igpmData,
    'inflacao_periodo_decimal' => round($inflacaoPeriodoDecimal, 6),
    'valor_real_investido' => round($valorRealInvestido, 2),
    'valor_real_final' => round($valorRealFinal, 2),
    'rentabilidade_real_liquida' => round($rentabilidadeRealLiquida, 2),
    'categoria_produto' => 'cdb',
    'observacoes_modelo' => [
        'Simulação voltada para CDB com tributação regressiva de longo prazo aproximada em meses.',
        'Não há come-cotas; o imposto é apurado nos resgates obrigatórios por vencimento e no resgate final.',
        'Quando o horizonte ultrapassa o vencimento, a simulação força resgate líquido e reinvestimento no mesmo mês.',
        'A inflação foi projetada com repetição da última taxa mensal de IGP-M encontrada na base.'
    ],
    'metodologia' => 'aportes_no_inicio_do_mes',
    'relatorio' => $simulacaoComVencimentos['relatorio']
];

$o->envia('Simulação calculada com sucesso.');
