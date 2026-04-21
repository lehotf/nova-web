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
    'modo_taxa' => ['tipo' => 'texto'],
    'modalidade_tributacao' => ['tipo' => 'texto']
]);

$investimentoInicial = (float) ($payload['investimento_inicial'] ?? 0);
$aplicacaoMensal = (float) ($payload['aplicacao_mensal'] ?? 0);
$numeroMeses = (int) ($payload['numero_meses'] ?? 0);
$modoTaxa = ($payload['modo_taxa'] ?? 'taxa') === 'cdi' ? 'cdi' : 'taxa';
$taxaJurosMensalInformada = (float) ($payload['taxa_juros_mensal'] ?? 0);
$percentualCdi = (float) ($payload['percentual_cdi'] ?? 0);
$modalidadeTributacao = strtolower(trim((string) ($payload['modalidade_tributacao'] ?? 'regressiva')));

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

if (!in_array($modalidadeTributacao, ['progressiva', 'regressiva'], true)) {
    $o->resposta(status: 'erro', msg: 'Modalidade de tributação inválida.');
}

$taxaDecimal = $taxaJurosMensal / 100;
$totalInvestido = $investimentoInicial + ($aplicacaoMensal * $numeroMeses);

function criarBaldesPrevidencia(): array
{
    $baldes = [];
    for ($i = 1; $i <= 121; $i++) {
        $baldes[$i] = [
            'investido' => 0.0,
            'saldo' => 0.0
        ];
    }

    return $baldes;
}

function avancarBaldesPrevidencia(array $baldes): array
{
    $baldes[121]['investido'] += $baldes[120]['investido'];
    $baldes[121]['saldo'] += $baldes[120]['saldo'];

    for ($i = 120; $i >= 2; $i--) {
        $baldes[$i] = $baldes[$i - 1];
    }

    $baldes[1] = [
        'investido' => 0.0,
        'saldo' => 0.0
    ];

    return $baldes;
}

function somarSaldoBaldesPrevidencia(array $baldes): float
{
    return (float) array_sum(array_column($baldes, 'saldo'));
}

function aliquotaRegressivaPrevidenciaPorMes(int $idadeMeses): float
{
    if ($idadeMeses <= 24) {
        return 0.35;
    }
    if ($idadeMeses <= 48) {
        return 0.30;
    }
    if ($idadeMeses <= 72) {
        return 0.25;
    }
    if ($idadeMeses <= 96) {
        return 0.20;
    }
    if ($idadeMeses <= 120) {
        return 0.15;
    }

    return 0.10;
}

function calcularImpostoPrevidencia(array $baldes, string $modalidadeTributacao): array
{
    $imposto = 0.0;
    $detalhamento = [];
    $agrupado = [];

    foreach ($baldes as $idade => $balde) {
        if ($balde['saldo'] <= 0) {
            continue;
        }

        $lucro = max(0, $balde['saldo'] - $balde['investido']);
        if ($lucro <= 0) {
            continue;
        }

        $aliquota = $modalidadeTributacao === 'progressiva'
            ? 0.15
            : aliquotaRegressivaPrevidenciaPorMes((int) $idade);

        $impostoBalde = $lucro * $aliquota;
        $imposto += $impostoBalde;

        if ($modalidadeTributacao === 'regressiva') {
            $faixa = $idade <= 24 ? 'Até 2 anos (35%)'
                : ($idade <= 48 ? 'De 2 a 4 anos (30%)'
                : ($idade <= 72 ? 'De 4 a 6 anos (25%)'
                : ($idade <= 96 ? 'De 6 a 8 anos (20%)'
                : ($idade <= 120 ? 'De 8 a 10 anos (15%)' : 'Acima de 10 anos (10%)'))));
            
            if (!isset($agrupado[$faixa])) {
                $agrupado[$faixa] = [
                    'faixa' => $faixa,
                    'lucro' => 0.0,
                    'imposto' => 0.0,
                    'aliquota' => round($aliquota * 100, 2)
                ];
            }
            $agrupado[$faixa]['lucro'] += $lucro;
            $agrupado[$faixa]['imposto'] += $impostoBalde;
        }
    }

    if ($modalidadeTributacao === 'regressiva') {
        foreach ($agrupado as $item) {
            $detalhamento[] = [
                'faixa' => $item['faixa'],
                'lucro' => round($item['lucro'], 2),
                'imposto' => round($item['imposto'], 2),
                'aliquota' => $item['aliquota']
            ];
        }
    }

    $saldoBruto = somarSaldoBaldesPrevidencia($baldes);

    return [
        'saldo_bruto' => $saldoBruto,
        'imposto' => $imposto,
        'saldo_liquido' => $saldoBruto - $imposto,
        'detalhamento' => $detalhamento
    ];
}

$mesAtualSimulacao = (int) date('n');
$anoAtualSimulacao = (int) date('Y');
$baldes = criarBaldesPrevidencia();
$relatorio = [];

$valorFuturoBruto = $investimentoInicial;
for ($mes = 1; $mes <= $numeroMeses; $mes++) {
    $valorFuturoBruto += $aplicacaoMensal;
    $valorFuturoBruto *= (1 + $taxaDecimal);
}
$rentabilidadeBrutaTotal = $valorFuturoBruto - $totalInvestido;

for ($mes = 1; $mes <= $numeroMeses; $mes++) {
    $baldes = avancarBaldesPrevidencia($baldes);

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

    $saldoMes = somarSaldoBaldesPrevidencia($baldes);

    $relatorio[] = [
        'mes_relativo' => $mes,
        'mes_ano' => str_pad((string) $mesAtualSimulacao, 2, '0', STR_PAD_LEFT) . '/' . $anoAtualSimulacao,
        'aporte' => round($aporteMes, 2),
        'rendimento' => round($rendimento, 2),
        'saldo_final' => round($saldoMes, 2)
    ];

    $mesAtualSimulacao++;
    if ($mesAtualSimulacao > 12) {
        $mesAtualSimulacao = 1;
        $anoAtualSimulacao++;
    }
}

$apuracaoFinal = calcularImpostoPrevidencia($baldes, $modalidadeTributacao);
$valorFuturo = $apuracaoFinal['saldo_bruto'];
$impostoResgate = $apuracaoFinal['imposto'];
$valorLiquido = $apuracaoFinal['saldo_liquido'];
$jurosAcumulados = $valorFuturo - $totalInvestido;

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
    'modo_taxa' => $modoTaxa,
    'taxa_juros_mensal_informada' => round($taxaJurosMensalInformada, 4),
    'percentual_cdi' => round($percentualCdi, 4),
    'cdi_mensal' => round($cdiMensal, 4),
    'cdi_data' => $cdiData,
    'taxa_juros_mensal' => round($taxaJurosMensal, 4),
    'taxa_decimal' => round($taxaDecimal, 8),
    'modalidade_tributacao' => $modalidadeTributacao,
    'total_investido' => round($totalInvestido, 2),
    'juros_acumulados' => round($jurosAcumulados, 2),
    'imposto_resgate' => round($impostoResgate, 2),
    'valor_futuro' => round($valorFuturo, 2),
    'valor_liquido' => round($valorLiquido, 2),
    'valor_futuro_bruto' => round($valorFuturoBruto, 2),
    'rentabilidade_bruta_total' => round($rentabilidadeBrutaTotal, 2),
    'detalhamento_tributacao' => $apuracaoFinal['detalhamento'],
    'igpm_mensal' => round($igpmMensal, 4),
    'igpm_data' => $igpmData,
    'inflacao_periodo_decimal' => round($inflacaoPeriodoDecimal, 6),
    'valor_real_investido' => round($valorRealInvestido, 2),
    'valor_real_final' => round($valorRealFinal, 2),
    'rentabilidade_real_liquida' => round($rentabilidadeRealLiquida, 2),
    'categoria_produto' => 'previdencia',
    'observacoes_modelo' => [
        'Simulação voltada para previdência com aportes no início de cada mês.',
        'Na modalidade progressiva, a calculadora aplica 15% sobre o lucro no momento do resgate.',
        'Na modalidade regressiva, a alíquota varia por faixa de tempo em aproximação mensal.',
        $modoTaxa === 'cdi'
            ? 'A taxa mensal usada na simulação foi obtida a partir do percentual do CDI informado e do último CDI mensal encontrado na tabela indices (código 4391).'
            : 'A taxa mensal usada na simulação foi a taxa de juros mensal informada diretamente pelo usuário.',
        'A inflação foi projetada com repetição da última taxa mensal de IGP-M encontrada na base.'
    ],
    'metodologia' => 'aportes_no_inicio_do_mes',
    'relatorio' => $relatorio
];

$o->envia('Simulação calculada com sucesso.');
