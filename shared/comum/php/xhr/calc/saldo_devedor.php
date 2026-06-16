<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'data_contratacao' => ['tipo' => 'string'],
    'data_primeira_parcela' => ['tipo' => 'string'],
    'valor_liberado' => ['tipo' => 'numero', 'min' => 0],
    'valor_parcela' => ['tipo' => 'numero', 'min' => 0],
    'prazo' => ['tipo' => 'numero', 'min' => 1, 'max' => 600]
]);

const IOF_ALIQUOTA_FIXA_PORCENTAGEM = 0.38;
const IOF_ALIQUOTA_DIARIA_PORCENTAGEM = 0.0082;
const IOF_LIMITE_DIAS_DIARIOS = 365;
const IOF_PRAZO_MEDIO_48_MESES_DIAS = 293.08292682927;
const IOF_PESO_DIA_CARENCIA = 0.41523991710505;

function parseDataCalculadora(string $valor, string $mensagemErro): DateTimeImmutable
{
    $valor = trim($valor);
    $data = DateTimeImmutable::createFromFormat('!d/m/Y', $valor);
    $erros = DateTimeImmutable::getLastErrors();

    if ($data && ($erros === false || (is_array($erros) && $erros['warning_count'] === 0 && $erros['error_count'] === 0))) {
        return $data;
    }

    $GLOBALS['o']->erro($mensagemErro);
}

function diasEntre(DateTimeInterface $inicio, DateTimeInterface $fim): int
{
    $intervalo = $inicio->diff($fim);
    return (int) $intervalo->format('%r%a');
}

function gerarParcelas(DateTimeImmutable $dataPrimeiraParcela, int $prazo): array
{
    $datas = [];
    $diaBase = (int) $dataPrimeiraParcela->format('j');
    $anoBase = (int) $dataPrimeiraParcela->format('Y');
    $mesBase = (int) $dataPrimeiraParcela->format('n');

    for ($i = 0; $i < $prazo; $i++) {
        $ano = $anoBase + intdiv(($mesBase - 1) + $i, 12);
        $mes = ((($mesBase - 1) + $i) % 12) + 1;
        $dia = min($diaBase, cal_days_in_month(CAL_GREGORIAN, $mes, $ano));
        $datas[] = $dataPrimeiraParcela->setDate($ano, $mes, $dia);
    }

    return $datas;
}

function deslocarMeses(DateTimeImmutable $data, int $meses): DateTimeImmutable
{
    $ano = (int) $data->format('Y');
    $mes = (int) $data->format('n');
    $dia = (int) $data->format('j');

    $mesIndex = ($mes - 1) + $meses;
    $novoAno = $ano + intdiv($mesIndex, 12);
    $novoMes = ($mesIndex % 12 + 12) % 12 + 1;
    $novoDia = min($dia, cal_days_in_month(CAL_GREGORIAN, $novoMes, $novoAno));

    return $data->setDate($novoAno, $novoMes, $novoDia);
}

function valorPresenteParcelas(array $datasParcelas, float $valorParcela, DateTimeImmutable $dataContrato, float $taxaDiaria): float
{
    $vp = 0.0;

    foreach ($datasParcelas as $dataParcela) {
        $dias = max(0, diasEntre($dataContrato, $dataParcela));
        $vp += $valorParcela / pow(1 + $taxaDiaria, $dias);
    }

    return $vp;
}

function taxaDiariaParaTaxaMensalPercentual(float $taxaDiaria): float
{
    return (pow(1 + $taxaDiaria, 30) - 1) * 100;
}

function taxaMensalPercentualParaDiaria(float $taxaMensalPercentual): float
{
    return pow(1 + ($taxaMensalPercentual / 100), 1 / 30) - 1;
}

function valorPresenteParcelasMensais(float $valorParcela, int $prazo, float $taxaMensalPercentual): float
{
    if ($prazo <= 0) {
        return 0.0;
    }

    $taxaMensal = $taxaMensalPercentual / 100;

    if (abs($taxaMensal) < 0.0000001) {
        return $valorParcela * $prazo;
    }

    return $valorParcela * ((1 - pow(1 + $taxaMensal, -$prazo)) / $taxaMensal);
}

function calcularIofContrato(float $valorSolicitado, int $prazo, int $diasCarencia): array
{
    $aliquotaFixa = IOF_ALIQUOTA_FIXA_PORCENTAGEM / 100;
    $aliquotaDiaria = IOF_ALIQUOTA_DIARIA_PORCENTAGEM / 100;
    $prazoMedioSemCarencia = IOF_PRAZO_MEDIO_48_MESES_DIAS * ($prazo / 48);
    $prazoMedio = min(IOF_LIMITE_DIAS_DIARIOS, $prazoMedioSemCarencia + ($diasCarencia * IOF_PESO_DIA_CARENCIA));
    $iofFixo = $valorSolicitado * $aliquotaFixa;
    $iofDiario = $valorSolicitado * $aliquotaDiaria * $prazoMedio;

    return [
        'iof_fixo' => $iofFixo,
        'iof_diario' => $iofDiario,
        'iof_total' => $iofFixo + $iofDiario,
        'prazo_medio_dias' => $prazoMedio
    ];
}

function valorPresenteBaseContrato(
    float $valorParcela,
    int $prazo,
    float $taxaMensalPercentual,
    int $diasCarencia
): float {
    $vpParcelas = valorPresenteParcelasMensais($valorParcela, $prazo, $taxaMensalPercentual);
    $taxaMensal = $taxaMensalPercentual / 100;
    $fatorCarencia = $diasCarencia > 0 ? pow(1 + $taxaMensal, $diasCarencia / 30) : 1.0;

    return $fatorCarencia > 0 ? $vpParcelas / $fatorCarencia : $vpParcelas;
}

function resolverTaxaMensalContrato(float $valorFinanciado, float $valorParcela, int $prazo, int $diasCarencia): float
{
    $vpZero = valorPresenteBaseContrato($valorParcela, $prazo, 0.0, $diasCarencia);

    if ($valorFinanciado > $vpZero) {
        $GLOBALS['o']->erro('O valor financiado é maior que o valor presente das parcelas. Revise os dados informados.');
    }

    if (abs($valorFinanciado - $vpZero) < 0.000001) {
        return 0.0;
    }

    $min = 0.0;
    $max = 1.0;
    $vpMax = valorPresenteBaseContrato($valorParcela, $prazo, $max, $diasCarencia);

    while ($vpMax > $valorFinanciado && $max < 100) {
        $max *= 2;
        $vpMax = valorPresenteBaseContrato($valorParcela, $prazo, $max, $diasCarencia);
    }

    if ($vpMax > $valorFinanciado) {
        $GLOBALS['o']->erro('Não foi possível calcular a taxa mensal com os dados informados.');
    }

    for ($i = 0; $i < 120; $i++) {
        $meio = ($min + $max) / 2;
        $vpMeio = valorPresenteBaseContrato($valorParcela, $prazo, $meio, $diasCarencia);

        if ($vpMeio > $valorFinanciado) {
            $min = $meio;
        } else {
            $max = $meio;
        }
    }

    return ($min + $max) / 2;
}

function calcularIofDiarioDetalhado(
    float $valorLiberado,
    float $valorParcela,
    array $datasParcelas,
    DateTimeImmutable $dataContrato,
    float $taxaDiariaContratual
): array {
    $saldo = $valorLiberado;
    $dataAnterior = $dataContrato;
    $iofDiarioTotal = 0.0;
    $amortizacaoTotal = 0.0;
    $detalhes = [];

    foreach ($datasParcelas as $indice => $dataParcela) {
        $diasPeriodo = max(0, diasEntre($dataAnterior, $dataParcela));
        $saldoAntesPagamento = $saldo;

        if ($diasPeriodo > 0) {
            $saldoAntesPagamento = $saldo * pow(1 + $taxaDiariaContratual, $diasPeriodo);
        }

        $jurosPeriodo = $saldoAntesPagamento - $saldo;
        $amortizacao = max(0.0, $valorParcela - $jurosPeriodo);
        $amortizacao = min($amortizacao, $saldoAntesPagamento);
        $diasIof = min(IOF_LIMITE_DIAS_DIARIOS, max(0, diasEntre($dataContrato, $dataParcela)));
        $iofParcela = $amortizacao * (IOF_ALIQUOTA_DIARIA_PORCENTAGEM / 100) * $diasIof;

        $iofDiarioTotal += $iofParcela;
        $amortizacaoTotal += $amortizacao;
        $saldo = max(0.0, $saldoAntesPagamento - $valorParcela);
        $dataAnterior = $dataParcela;

        $detalhes[] = [
            'parcela' => $indice + 1,
            'data_parcela' => $dataParcela->format('Y-m-d'),
            'data_parcela_br' => $dataParcela->format('d/m/Y'),
            'dias' => $diasIof,
            'amortizacao' => round($amortizacao, 2),
            'juros_periodo' => round($jurosPeriodo, 2),
            'iof' => round($iofParcela, 2)
        ];
    }

    return [
        'iof_diario_total' => $iofDiarioTotal,
        'amortizacao_total' => $amortizacaoTotal,
        'detalhes' => $detalhes
    ];
}

function calcularSaldoAtualComTaxaMensal(
    DateTimeImmutable $dataContrato,
    DateTimeImmutable $dataReferencia,
    array $datasParcelas,
    float $valorFinanciado,
    float $valorParcela,
    float $taxaMensalPercentual
): array {
    $saldo = $valorFinanciado;
    $taxaMensal = $taxaMensalPercentual / 100;
    $ultimoMarco = $dataContrato;
    $parcelasPagas = 0;

    foreach ($datasParcelas as $dataParcela) {
        if ($dataParcela > $dataReferencia) {
            break;
        }

        $jurosPeriodo = round($saldo * $taxaMensal, 2);
        $saldo = round($saldo + $jurosPeriodo - $valorParcela, 2);
        $parcelasPagas++;
        $ultimoMarco = $dataParcela;
    }

    $diasRestantes = max(0, diasEntre($ultimoMarco, $dataReferencia));
    if ($diasRestantes > 0) {
        $saldo *= pow(1 + $taxaMensal, $diasRestantes / 30);
    }

    return [
        'saldo' => max(0.0, $saldo),
        'parcelas_pagas' => $parcelasPagas
    ];
}

function resolverTaxaDiaria(array $datasParcelas, float $valorBase, float $valorParcela, DateTimeImmutable $dataContrato): float
{
    $vpZero = valorPresenteParcelas($datasParcelas, $valorParcela, $dataContrato, 0.0);

    if ($valorBase > $vpZero) {
        $GLOBALS['o']->erro('O valor liberado é maior que o valor presente das parcelas. Revise os dados informados.');
    }

    if (abs($valorBase - $vpZero) < 0.000001) {
        return 0.0;
    }

    $min = 0.0;
    $max = 0.01;
    $vpMax = valorPresenteParcelas($datasParcelas, $valorParcela, $dataContrato, $max);

    while ($vpMax > $valorBase && $max < 10) {
        $max *= 2;
        $vpMax = valorPresenteParcelas($datasParcelas, $valorParcela, $dataContrato, $max);
    }

    if ($vpMax > $valorBase) {
        $GLOBALS['o']->erro('Não foi possível calcular uma taxa positiva com os dados informados.');
    }

    for ($i = 0; $i < 120; $i++) {
        $meio = ($min + $max) / 2;
        $vpMeio = valorPresenteParcelas($datasParcelas, $valorParcela, $dataContrato, $meio);

        if ($vpMeio > $valorBase) {
            $min = $meio;
        } else {
            $max = $meio;
        }
    }

    return ($min + $max) / 2;
}

function calcularSaldoAtual(
    DateTimeImmutable $dataContrato,
    DateTimeImmutable $dataReferencia,
    array $datasParcelas,
    float $valorLiberado,
    float $valorParcela,
    float $taxaDiaria
): array {
    $saldo = $valorLiberado;
    $ultimoMarco = $dataContrato;
    $parcelasPagas = 0;

    foreach ($datasParcelas as $dataParcela) {
        if ($dataParcela > $dataReferencia) {
            break;
        }

        $dias = max(0, diasEntre($ultimoMarco, $dataParcela));
        if ($dias > 0) {
            $saldo *= pow(1 + $taxaDiaria, $dias);
        }

        $saldo -= $valorParcela;
        $parcelasPagas++;
        $ultimoMarco = $dataParcela;
    }

    $diasRestantes = max(0, diasEntre($ultimoMarco, $dataReferencia));
    if ($diasRestantes > 0) {
        $saldo *= pow(1 + $taxaDiaria, $diasRestantes);
    }

    return [
        'saldo' => max(0.0, $saldo),
        'parcelas_pagas' => $parcelasPagas
    ];
}

$dataContratacao = parseDataCalculadora(
    (string) ($payload['data_contratacao'] ?? ''),
    'Informe uma data válida de contratação no formato DD/MM/AAAA.'
);

$dataPrimeiraParcela = parseDataCalculadora(
    (string) ($payload['data_primeira_parcela'] ?? ''),
    'Informe uma data válida da primeira parcela no formato DD/MM/AAAA.'
);

if ($dataPrimeiraParcela < $dataContratacao) {
    $o->erro('A data da primeira parcela não pode ser anterior à data de contratação.');
}

$dataReferencia = new DateTimeImmutable('today');
if ($dataReferencia < $dataContratacao) {
    $o->erro('A data de contratação não pode ser futura.');
}

$valorLiberado = (float) ($payload['valor_liberado'] ?? 0);
$valorParcela = (float) ($payload['valor_parcela'] ?? 0);
$prazo = (int) ($payload['prazo'] ?? 0);

if ($valorLiberado <= 0) {
    $o->erro('Informe um valor liberado/financiado maior que zero.');
}

if ($valorParcela <= 0) {
    $o->erro('Informe um valor de parcela maior que zero.');
}

if ($prazo <= 0) {
    $o->erro('Informe um prazo válido.');
}

$dataBaseCarencia = deslocarMeses($dataPrimeiraParcela, -1);
$diasCarencia = max(0, diasEntre($dataContratacao, $dataBaseCarencia));
$diasBaseIof = min(max(0, diasEntre($dataContratacao, $dataPrimeiraParcela)), IOF_LIMITE_DIAS_DIARIOS);
$datasParcelas = gerarParcelas($dataPrimeiraParcela, $prazo);

$iofContrato = calcularIofContrato($valorLiberado, $prazo, $diasCarencia);
$iofFixo = $iofContrato['iof_fixo'];
$iofDiario = $iofContrato['iof_diario'];
$iofTotal = $iofContrato['iof_total'];
$valorFinanciado = $valorLiberado + $iofTotal;

$taxaContratualMensal = resolverTaxaMensalContrato($valorFinanciado, $valorParcela, $prazo, $diasCarencia);
$taxaContratualEstimativa = $taxaContratualMensal;
$taxaContratualMensalCalculada = $taxaContratualMensal;
$taxaContratualMensal = round($taxaContratualMensal, 2);

$taxaDiariaContratual = taxaMensalPercentualParaDiaria($taxaContratualMensal);
$fatorCarencia = $diasCarencia > 0
    ? pow(1 + ($taxaContratualMensal / 100), $diasCarencia / 30) - 1
    : 0.0;
$jurosCarencia = $valorFinanciado * $fatorCarencia;
$basePrestacoes = $valorFinanciado + $jurosCarencia;
$iofDiarioDetalhado = [
    'iof_diario_total' => $iofDiario,
    'amortizacao_total' => 0.0,
    'detalhes' => []
];
$baseEfetiva = $valorFinanciado;
$taxaDiariaEfetiva = $taxaDiariaContratual;
$taxaEfetivaMensal = $taxaContratualMensal;
$saldoAtual = calcularSaldoAtualComTaxaMensal(
    $dataContratacao,
    $dataReferencia,
    $datasParcelas,
    $valorFinanciado,
    $valorParcela,
    $taxaContratualMensal
);

$saldoDevedorAtual = round($saldoAtual['saldo'], 2);
$parcelasPagas = $saldoAtual['parcelas_pagas'];
$parcelasRestantes = max(0, $prazo - $parcelasPagas);
$valorTotalParcelas = $valorParcela * $prazo;
$custoTotalEfetivo = $valorTotalParcelas + $iofTotal - $valorLiberado;
$custoTotalEfetivoPercentual = $valorLiberado > 0 ? ($custoTotalEfetivo / $valorLiberado) * 100 : 0;

$o->r['resultado'] = [
    'data_contratacao' => $dataContratacao->format('Y-m-d'),
    'data_contratacao_br' => $dataContratacao->format('d/m/Y'),
    'data_primeira_parcela' => $dataPrimeiraParcela->format('Y-m-d'),
    'data_primeira_parcela_br' => $dataPrimeiraParcela->format('d/m/Y'),
    'data_referencia' => $dataReferencia->format('Y-m-d'),
    'data_referencia_br' => $dataReferencia->format('d/m/Y'),
    'data_simulacao' => $dataReferencia->format('Y-m-d'),
    'data_simulacao_br' => $dataReferencia->format('d/m/Y'),
    'valor_liberado' => round($valorLiberado, 2),
    'valor_financiado' => round($valorFinanciado, 2),
    'valor_base_prestacoes' => round($basePrestacoes, 2),
    'valor_parcela' => round($valorParcela, 2),
    'prazo' => $prazo,
    'dias_carencia' => $diasCarencia,
    'data_base_carencia' => $dataBaseCarencia->format('Y-m-d'),
    'data_base_carencia_br' => $dataBaseCarencia->format('d/m/Y'),
    'iof_aliquota_fixa_percentual' => IOF_ALIQUOTA_FIXA_PORCENTAGEM,
    'iof_aliquota_diaria_percentual' => IOF_ALIQUOTA_DIARIA_PORCENTAGEM,
    'iof_dias_considerados' => $diasBaseIof,
    'iof_prazo_medio_dias' => round($iofContrato['prazo_medio_dias'], 6),
    'iof_fixo' => round($iofFixo, 2),
    'iof_diario' => round($iofDiario, 2),
    'iof_detalhes_parcelas' => $iofDiarioDetalhado['detalhes'],
    'iof_total' => round($iofTotal, 2),
    'juros_carencia' => round($jurosCarencia, 2),
    'taxa_contratual_diaria' => round($taxaDiariaContratual * 100, 10),
    'taxa_contratual_mensal_calculada' => round($taxaContratualMensalCalculada, 10),
    'taxa_contratual_mensal' => round($taxaContratualMensal, 2),
    'taxa_efetiva_diaria' => round($taxaDiariaEfetiva * 100, 10),
    'taxa_efetiva_mensal' => round($taxaEfetivaMensal, 2),
    'saldo_devedor_atual' => $saldoDevedorAtual,
    'parcelas_pagas' => $parcelasPagas,
    'parcelas_restantes' => $parcelasRestantes,
    'valor_total_parcelas' => round($valorTotalParcelas, 2),
    'custo_total_efetivo' => round($custoTotalEfetivo, 2),
    'custo_total_efetivo_percentual' => round($custoTotalEfetivoPercentual, 6),
    'observacoes_modelo' => [
        'O saldo devedor atual foi calculado pela evolução mensal da tabela Price, com arredondamento de juros a cada prestação.',
        'O juros de carência representa apenas o período entre a contratação e a primeira parcela, antes do ciclo normal de amortização.',
        'O IOF foi calculado antes da taxa mensal, a partir do valor solicitado, prazo e carência.',
        'O IOF foi parametrizado em constantes separadas para facilitar futuras alterações e conferências por contrato.',
        'Após o último vencimento pago, o saldo é capitalizado proporcionalmente pelos dias até a data de referência.'
    ],
    'metodologia' => 'contratual_com_taxa_estimativa',
    'resultado_geral' => [
        'saldo_devedor_atual' => $saldoDevedorAtual,
        'taxa_juros_mensal' => round($taxaContratualMensal, 2)
    ]
];

$o->envia('Saldo devedor calculado com sucesso.');
