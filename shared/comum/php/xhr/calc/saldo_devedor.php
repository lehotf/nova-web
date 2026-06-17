<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'data_contratacao' => ['tipo' => 'string'],
    'data_primeira_parcela' => ['tipo' => 'string'],
    'valor_liberado' => ['tipo' => 'numero', 'min' => 0, 'max' => 2000000],
    'valor_parcela' => ['tipo' => 'numero', 'min' => 0],
    'prazo' => ['tipo' => 'numero', 'min' => 1, 'max' => 150]
]);

const IOF_ALIQUOTA_FIXA_PORCENTAGEM = 0.38;
const IOF_ALIQUOTA_DIARIA_PORCENTAGEM = 0.0082;
const IOF_LIMITE_DIAS_DIARIOS = 365;
const VALOR_SOLICITADO_MAXIMO = 2000000;
const PRAZO_MAXIMO = 150;

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

function calcularJurosCarencia(float $valorFinanciado, float $taxaMensalPercentual, int $diasCarencia): float
{
    if ($diasCarencia <= 0) {
        return 0.0;
    }

    return $valorFinanciado * (pow(1 + ($taxaMensalPercentual / 100), $diasCarencia / 30) - 1);
}

function calcularPrazoMedioIofPorValorPresente(
    array $datasParcelas,
    DateTimeImmutable $dataContrato,
    float $taxaMensalPercentual
): array {
    $taxaMensal = $taxaMensalPercentual / 100;
    $somaPesos = 0.0;
    $somaDiasPonderados = 0.0;
    $detalhes = [];

    foreach ($datasParcelas as $indice => $dataParcela) {
        $diasIof = min(IOF_LIMITE_DIAS_DIARIOS, max(0, diasEntre($dataContrato, $dataParcela)));
        $pesoValorPresente = 1 / pow(1 + $taxaMensal, $indice + 1);

        $somaPesos += $pesoValorPresente;
        $somaDiasPonderados += $diasIof * $pesoValorPresente;

        $detalhes[] = [
            'parcela' => $indice + 1,
            'data_parcela' => $dataParcela->format('Y-m-d'),
            'data_parcela_br' => $dataParcela->format('d/m/Y'),
            'dias' => $diasIof,
            'peso_valor_presente' => round($pesoValorPresente, 10)
        ];
    }

    return [
        'prazo_medio_dias' => $somaPesos > 0 ? $somaDiasPonderados / $somaPesos : 0.0,
        'soma_pesos' => $somaPesos,
        'detalhes' => $detalhes
    ];
}

function calcularIofContratoLegal(
    float $valorSolicitado,
    array $datasParcelas,
    DateTimeImmutable $dataContrato,
    float $taxaMensalPercentual
): array
{
    $aliquotaFixa = IOF_ALIQUOTA_FIXA_PORCENTAGEM / 100;
    $aliquotaDiaria = IOF_ALIQUOTA_DIARIA_PORCENTAGEM / 100;
    $prazoMedio = calcularPrazoMedioIofPorValorPresente($datasParcelas, $dataContrato, $taxaMensalPercentual);
    $aliquotaTotal = $aliquotaFixa + ($aliquotaDiaria * $prazoMedio['prazo_medio_dias']);

    if ($aliquotaTotal >= 1) {
        $GLOBALS['o']->erro('Não foi possível calcular o IOF com os dados informados.');
    }

    $valorFinanciado = $valorSolicitado / (1 - $aliquotaTotal);
    $iofFixo = $valorFinanciado * $aliquotaFixa;
    $iofDiario = $valorFinanciado * $aliquotaDiaria * $prazoMedio['prazo_medio_dias'];
    $iofTotal = $valorFinanciado - $valorSolicitado;
    $detalhes = [];

    foreach ($prazoMedio['detalhes'] as $detalhe) {
        $participacao = $prazoMedio['soma_pesos'] > 0 ? $detalhe['peso_valor_presente'] / $prazoMedio['soma_pesos'] : 0.0;
        $baseIofParcela = $valorFinanciado * $participacao;
        $detalhe['base_iof_parcela'] = round($baseIofParcela, 2);
        $detalhe['iof_diario'] = round($baseIofParcela * $aliquotaDiaria * $detalhe['dias'], 2);
        $detalhes[] = $detalhe;
    }

    return [
        'iof_fixo' => $iofFixo,
        'iof_diario' => $iofDiario,
        'iof_total' => $iofTotal,
        'aliquota_total_percentual' => $aliquotaTotal * 100,
        'prazo_medio_dias' => $prazoMedio['prazo_medio_dias'],
        'detalhes' => $detalhes,
        'metodo' => 'iof_por_dentro_prazo_medio_valor_presente'
    ];
}

function calcularContratoComIofLegal(
    float $valorSolicitado,
    float $valorParcela,
    int $prazo,
    int $diasCarencia,
    array $datasParcelas,
    DateTimeImmutable $dataContrato
): array {
    $iofEstimado = $valorSolicitado * (IOF_ALIQUOTA_FIXA_PORCENTAGEM / 100);
    $taxaMensal = 0.0;
    $iofContrato = [];

    for ($i = 0; $i < 40; $i++) {
        $valorFinanciado = $valorSolicitado + $iofEstimado;
        $taxaMensal = resolverTaxaMensalContrato($valorFinanciado, $valorParcela, $prazo, $diasCarencia);
        $iofContrato = calcularIofContratoLegal(
            $valorSolicitado,
            $datasParcelas,
            $dataContrato,
            $taxaMensal
        );

        if (abs($iofContrato['iof_total'] - $iofEstimado) < 0.005) {
            break;
        }

        $iofEstimado = $iofContrato['iof_total'];
    }

    $valorFinanciado = $valorSolicitado + $iofContrato['iof_total'];
    $taxaMensal = resolverTaxaMensalContrato($valorFinanciado, $valorParcela, $prazo, $diasCarencia);
    $iofContrato = calcularIofContratoLegal(
        $valorSolicitado,
        $datasParcelas,
        $dataContrato,
        $taxaMensal
    );
    $valorFinanciado = $valorSolicitado + $iofContrato['iof_total'];
    $jurosCarencia = calcularJurosCarencia($valorFinanciado, $taxaMensal, $diasCarencia);
    $basePrestacoes = $valorFinanciado + $jurosCarencia;

    return [
        'iof' => $iofContrato,
        'taxa_mensal' => $taxaMensal,
        'juros_carencia' => $jurosCarencia,
        'base_prestacoes' => $basePrestacoes,
        'valor_financiado' => $valorSolicitado + $iofContrato['iof_total']
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
    $proximoMarco = null;
    $parcelasPagas = 0;

    foreach ($datasParcelas as $dataParcela) {
        if ($dataParcela > $dataReferencia) {
            $proximoMarco = $dataParcela;
            break;
        }

        $jurosPeriodo = round($saldo * $taxaMensal, 2);
        $saldo = round($saldo + $jurosPeriodo - $valorParcela, 2);
        $parcelasPagas++;
        $ultimoMarco = $dataParcela;
    }

    $diasRestantes = max(0, diasEntre($ultimoMarco, $dataReferencia));
    if ($diasRestantes > 0) {
        if (!$proximoMarco) {
            $proximoMarco = deslocarMeses($ultimoMarco, 1);
        }

        $diasCiclo = max(1, diasEntre($ultimoMarco, $proximoMarco));
        $saldo *= pow(1 + $taxaMensal, $diasRestantes / $diasCiclo);
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
    $o->erro('Informe um valor solicitado maior que zero.');
}

if ($valorLiberado > VALOR_SOLICITADO_MAXIMO) {
    $o->erro('O valor solicitado máximo permitido para esta simulação é de R$ 2.000.000,00.');
}

if ($valorParcela <= 0) {
    $o->erro('Informe um valor de parcela maior que zero.');
}

if ($prazo <= 0) {
    $o->erro('Informe um prazo válido.');
}

if ($prazo > PRAZO_MAXIMO) {
    $o->erro('O prazo máximo permitido para esta simulação é de 150 parcelas.');
}

$dataBaseCarencia = deslocarMeses($dataPrimeiraParcela, -1);
$diasCarencia = max(0, diasEntre($dataContratacao, $dataBaseCarencia));
$diasBaseIof = min(max(0, diasEntre($dataContratacao, $dataPrimeiraParcela)), IOF_LIMITE_DIAS_DIARIOS);
$datasParcelas = gerarParcelas($dataPrimeiraParcela, $prazo);

$contratoCalculado = calcularContratoComIofLegal(
    $valorLiberado,
    $valorParcela,
    $prazo,
    $diasCarencia,
    $datasParcelas,
    $dataContratacao
);
$iofContrato = $contratoCalculado['iof'];
$iofFixo = $iofContrato['iof_fixo'];
$iofDiario = $iofContrato['iof_diario'];
$iofTotal = $iofContrato['iof_total'];
$valorFinanciado = $contratoCalculado['valor_financiado'];

$taxaContratualMensal = $contratoCalculado['taxa_mensal'];
$taxaContratualMensalCalculada = $taxaContratualMensal;
$taxaContratualMensal = round($taxaContratualMensal, 2);

$taxaDiariaContratual = taxaMensalPercentualParaDiaria($taxaContratualMensal);
$jurosCarencia = calcularJurosCarencia($valorFinanciado, $taxaContratualMensal, $diasCarencia);
$basePrestacoes = $valorFinanciado + $jurosCarencia;
$taxaDiariaEfetiva = $taxaDiariaContratual;
$taxaEfetivaMensal = $taxaContratualMensal;
$dataInicialSaldo = $dataReferencia < $dataBaseCarencia ? $dataContratacao : $dataBaseCarencia;
$valorInicialSaldo = $dataReferencia < $dataBaseCarencia ? $valorFinanciado : $basePrestacoes;
$saldoAtual = calcularSaldoAtualComTaxaMensal(
    $dataInicialSaldo,
    $dataReferencia,
    $datasParcelas,
    $valorInicialSaldo,
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
    'valor_solicitado' => round($valorLiberado, 2),
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
    'iof_metodo' => $iofContrato['metodo'],
    'iof_prazo_medio_dias' => round($iofContrato['prazo_medio_dias'] ?? 0, 6),
    'iof_aliquota_total_percentual' => round($iofContrato['aliquota_total_percentual'] ?? 0, 10),
    'iof_fixo' => round($iofFixo, 2),
    'iof_diario' => round($iofDiario, 2),
    'iof_detalhes_parcelas' => $iofContrato['detalhes'],
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
        'O IOF foi calculado por dentro sobre o total financiado, com adicional fixo e alíquota diária limitada a 365 dias.',
        'O prazo médio do IOF é ponderado pelo valor presente das parcelas, usando a taxa contratual estimada.',
        'Após o último vencimento pago, o saldo é capitalizado proporcionalmente pelos dias até a data de referência.'
    ],
    'metodologia' => 'contratual_com_taxa_estimativa',
    'resultado_geral' => [
        'saldo_devedor_atual' => $saldoDevedorAtual,
        'taxa_juros_mensal' => round($taxaContratualMensal, 2)
    ]
];

$o->envia('Saldo devedor calculado com sucesso.');
