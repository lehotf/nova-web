<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true);
$o = $c->observador;

$payload = $o->valida([
    'data_contratacao' => ['tipo' => 'string'],
    'data_primeira_parcela' => ['tipo' => 'string'],
    'tipo_valor' => ['tipo' => 'string'],
    'valor_liberado' => ['tipo' => 'numero', 'min' => 0, 'max' => 2000000],
    'valor_parcela' => ['tipo' => 'numero', 'min' => 0],
    'prazo' => ['tipo' => 'numero', 'min' => 1, 'max' => 150],
    'renovacao' => ['tipo' => 'numero', 'min' => 0, 'max' => 1],
    'valor_troco' => ['tipo' => 'numero', 'min' => 0, 'max' => 2000000]
]);

const IOF_ALIQUOTA_FIXA_PORCENTAGEM = 0.38;
const IOF_ALIQUOTA_DIARIA_PORCENTAGEM = 0.0082;
const IOF_LIMITE_DIAS_DIARIOS = 365;
const VALOR_SOLICITADO_MAXIMO = 2000000;
const PRAZO_MAXIMO = 150;
const IOF_RENOVACAO_DIAS_COMPLEMENTARES_MAXIMO = 7.3;

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
    float $valorBaseIof,
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

    $valorBaseIofFinanciado = $valorBaseIof / (1 - $aliquotaTotal);
    $iofFixo = $valorBaseIofFinanciado * $aliquotaFixa;
    $iofDiario = $valorBaseIofFinanciado * $aliquotaDiaria * $prazoMedio['prazo_medio_dias'];
    $iofTotal = $valorBaseIofFinanciado - $valorBaseIof;
    $detalhes = [];

    foreach ($prazoMedio['detalhes'] as $detalhe) {
        $participacao = $prazoMedio['soma_pesos'] > 0 ? $detalhe['peso_valor_presente'] / $prazoMedio['soma_pesos'] : 0.0;
        $baseIofParcela = $valorBaseIofFinanciado * $participacao;
        $detalhe['base_iof_parcela'] = round($baseIofParcela, 2);
        $detalhe['iof_diario'] = round($baseIofParcela * $aliquotaDiaria * $detalhe['dias'], 2);
        $detalhes[] = $detalhe;
    }

    return [
        'valor_base_iof' => $valorBaseIof,
        'valor_base_iof_financiado' => $valorBaseIofFinanciado,
        'iof_fixo' => $iofFixo,
        'iof_diario' => $iofDiario,
        'iof_total' => $iofTotal,
        'aliquota_total_percentual' => $aliquotaTotal * 100,
        'prazo_medio_dias' => $prazoMedio['prazo_medio_dias'],
        'detalhes' => $detalhes,
        'metodo' => 'iof_por_dentro_prazo_medio_valor_presente'
    ];
}

function calcularIofComplementarRenovacao(float $valorSolicitado, float $valorTroco, bool $isRenovacao): array
{
    if (!$isRenovacao) {
        return [
            'valor_saldo_renovado' => 0.0,
            'dias_complementares' => 0.0,
            'iof_complementar' => 0.0
        ];
    }

    $valorSaldoRenovado = max(0.0, $valorSolicitado - $valorTroco);

    if ($valorSaldoRenovado <= 0) {
        return [
            'valor_saldo_renovado' => 0.0,
            'dias_complementares' => 0.0,
            'iof_complementar' => 0.0
        ];
    }

    $diasComplementares = $valorTroco > 0
        ? min(IOF_RENOVACAO_DIAS_COMPLEMENTARES_MAXIMO, $valorSaldoRenovado / $valorTroco)
        : IOF_RENOVACAO_DIAS_COMPLEMENTARES_MAXIMO;

    return [
        'valor_saldo_renovado' => $valorSaldoRenovado,
        'dias_complementares' => $diasComplementares,
        'iof_complementar' => $valorSaldoRenovado * (IOF_ALIQUOTA_DIARIA_PORCENTAGEM / 100) * $diasComplementares
    ];
}

function somarIofComplementarRenovacao(array $iofContrato, array $iofComplementarRenovacao): array
{
    $iofComplementar = $iofComplementarRenovacao['iof_complementar'] ?? 0.0;

    $iofContrato['iof_complementar_renovacao'] = $iofComplementar;
    $iofContrato['iof_total'] += $iofComplementar;
    $iofContrato['renovacao_dias_complementares'] = $iofComplementarRenovacao['dias_complementares'] ?? 0.0;
    $iofContrato['renovacao_saldo_base_complementar'] = $iofComplementarRenovacao['valor_saldo_renovado'] ?? 0.0;

    if ($iofComplementar > 0) {
        $iofContrato['metodo'] .= '_com_complementar_renovacao';
    }

    return $iofContrato;
}

function criarIofZerado(string $metodo): array
{
    return [
        'valor_base_iof' => 0.0,
        'valor_base_iof_financiado' => 0.0,
        'iof_fixo' => 0.0,
        'iof_diario' => 0.0,
        'iof_total' => 0.0,
        'iof_complementar_renovacao' => 0.0,
        'renovacao_dias_complementares' => 0.0,
        'renovacao_saldo_base_complementar' => 0.0,
        'aliquota_total_percentual' => 0.0,
        'prazo_medio_dias' => 0.0,
        'detalhes' => [],
        'metodo' => $metodo
    ];
}

function calcularContratoComIofLegal(
    float $valorSolicitado,
    float $valorBaseIof,
    float $valorParcela,
    int $prazo,
    int $diasCarencia,
    array $datasParcelas,
    DateTimeImmutable $dataContrato,
    array $iofComplementarRenovacao
): array {
    $iofEstimado = ($valorBaseIof * (IOF_ALIQUOTA_FIXA_PORCENTAGEM / 100)) + ($iofComplementarRenovacao['iof_complementar'] ?? 0.0);
    $taxaMensal = 0.0;
    $iofContrato = [];

    for ($i = 0; $i < 40; $i++) {
        $valorFinanciado = $valorSolicitado + $iofEstimado;
        $taxaMensal = resolverTaxaMensalContrato($valorFinanciado, $valorParcela, $prazo, $diasCarencia);
        $iofContrato = calcularIofContratoLegal(
            $valorBaseIof,
            $datasParcelas,
            $dataContrato,
            $taxaMensal
        );
        $iofContrato = somarIofComplementarRenovacao($iofContrato, $iofComplementarRenovacao);

        if (abs($iofContrato['iof_total'] - $iofEstimado) < 0.005) {
            break;
        }

        $iofEstimado = $iofContrato['iof_total'];
    }

    $valorFinanciado = $valorSolicitado + $iofContrato['iof_total'];
    $taxaMensal = resolverTaxaMensalContrato($valorFinanciado, $valorParcela, $prazo, $diasCarencia);
    $iofContrato = calcularIofContratoLegal(
        $valorBaseIof,
        $datasParcelas,
        $dataContrato,
        $taxaMensal
    );
    $iofContrato = somarIofComplementarRenovacao($iofContrato, $iofComplementarRenovacao);
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
$tipoValorInformado = (($payload['tipo_valor'] ?? 'financiado') === 'solicitado') ? 'solicitado' : 'financiado';
$isValorFinanciadoInformado = $tipoValorInformado === 'financiado';
$isRenovacao = !$isValorFinanciadoInformado && ((int) ($payload['renovacao'] ?? 0)) === 1;
$valorTroco = (float) ($payload['valor_troco'] ?? 0);

if ($valorLiberado <= 0) {
    $o->erro($isValorFinanciadoInformado ? 'Informe um valor financiado maior que zero.' : 'Informe um valor solicitado maior que zero.');
}

if ($valorLiberado > VALOR_SOLICITADO_MAXIMO) {
    $o->erro('O valor máximo permitido para esta simulação é de R$ 2.000.000,00.');
}

if ($valorParcela <= 0) {
    $o->erro('Informe um valor de parcela maior que zero.');
}

if ($isRenovacao && $valorTroco > $valorLiberado) {
    $o->erro('O valor do troco não pode ser maior que o valor solicitado.');
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

if ($isValorFinanciadoInformado) {
    $valorTroco = 0.0;
    $valorBaseIof = 0.0;
    $iofComplementarRenovacao = [
        'valor_saldo_renovado' => 0.0,
        'dias_complementares' => 0.0,
        'iof_complementar' => 0.0
    ];
    $iofContrato = criarIofZerado('valor_financiado_informado_iof_embutido');
    $iofFixo = 0.0;
    $iofDiario = 0.0;
    $iofTotal = 0.0;
    $valorFinanciado = $valorLiberado;
    $taxaContratualMensal = resolverTaxaMensalContrato($valorFinanciado, $valorParcela, $prazo, $diasCarencia);
} else {
    $valorBaseIof = $isRenovacao ? $valorTroco : $valorLiberado;
    $iofComplementarRenovacao = calcularIofComplementarRenovacao($valorLiberado, $valorBaseIof, $isRenovacao);

    $contratoCalculado = calcularContratoComIofLegal(
        $valorLiberado,
        $valorBaseIof,
        $valorParcela,
        $prazo,
        $diasCarencia,
        $datasParcelas,
        $dataContratacao,
        $iofComplementarRenovacao
    );
    $iofContrato = $contratoCalculado['iof'];
    $iofFixo = $iofContrato['iof_fixo'];
    $iofDiario = $iofContrato['iof_diario'];
    $iofTotal = $iofContrato['iof_total'];
    $valorFinanciado = $contratoCalculado['valor_financiado'];
    $taxaContratualMensal = $contratoCalculado['taxa_mensal'];
}

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
$custoTotalEfetivo = $isValorFinanciadoInformado ? $valorTotalParcelas - $valorFinanciado : $valorTotalParcelas + $iofTotal - $valorLiberado;
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
    'tipo_valor_informado' => $tipoValorInformado,
    'valor_informado' => round($valorLiberado, 2),
    'valor_solicitado' => $isValorFinanciadoInformado ? null : round($valorLiberado, 2),
    'valor_liberado' => round($valorLiberado, 2),
    'renovacao' => $isRenovacao,
    'valor_troco' => round($valorTroco, 2),
    'valor_base_iof' => round($valorBaseIof, 2),
    'valor_base_iof_financiado' => round($iofContrato['valor_base_iof_financiado'] ?? 0, 2),
    'valor_saldo_renovado' => round($iofComplementarRenovacao['valor_saldo_renovado'] ?? 0, 2),
    'iof_complementar_renovacao' => round($iofContrato['iof_complementar_renovacao'] ?? 0, 2),
    'renovacao_dias_complementares' => round($iofContrato['renovacao_dias_complementares'] ?? 0, 6),
    'renovacao_saldo_base_complementar' => round($iofContrato['renovacao_saldo_base_complementar'] ?? 0, 2),
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
        $isValorFinanciadoInformado ? 'Como foi informado o valor financiado, o IOF foi tratado como já embutido no valor de entrada e não foi recalculado.' : 'O IOF foi calculado por dentro sobre o total financiado, com adicional fixo e alíquota diária limitada a 365 dias.',
        $isValorFinanciadoInformado ? 'Nesse modo, a seleção entre empréstimo novo e renovação não altera o cálculo.' : ($isRenovacao ? 'Como a operação foi marcada como renovação, o IOF considera o valor do troco e uma estimativa operacional de IOF complementar sobre o saldo renovado.' : 'Como a operação não foi marcada como renovação, a base de IOF considerada foi o valor solicitado.'),
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
