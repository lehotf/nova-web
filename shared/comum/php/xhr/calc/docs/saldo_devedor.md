# Calculadora de saldo devedor de empréstimo

Este documento descreve a lógica usada em `saldo_devedor.php` e `saldo_devedor.js`.

## Entradas

- `data_contratacao`
- `data_primeira_parcela`
- `valor_liberado`
- `valor_parcela`
- `prazo`

## Modelo

O cálculo trabalha com datas reais e segue esta ordem:

- calcula a carência somente quando a primeira parcela foi postergada além do primeiro vencimento normal;
- calcula o IOF a partir do valor solicitado, prazo e carência;
- monta o valor financiado como valor solicitado + IOF;
- estima a taxa contratual mensal implícita pelas parcelas, pelo prazo e pelo valor financiado;
- calcula o saldo devedor atual pela evolução mensal da tabela Price.

O saldo devedor atual é calculado a partir do saldo nominal do contrato, aplicando a taxa mensal e abatendo a prestação em cada vencimento já ocorrido. Os juros de cada ciclo mensal são arredondados em centavos, como no demonstrativo bancário. Após o último vencimento pago, o saldo remanescente é capitalizado proporcionalmente pelos dias até a data de referência.

As parcelas seguintes são projetadas sempre no mesmo dia do mês da primeira parcela. Quando o mês não possui esse dia, usa-se o último dia disponível do mês.

## IOF

As alíquotas ficam centralizadas em constantes no backend:

- `IOF_ALIQUOTA_FIXA_PORCENTAGEM = 0.38`
- `IOF_ALIQUOTA_DIARIA_PORCENTAGEM = 0.0082`
- `IOF_LIMITE_DIAS_DIARIOS = 365`

Essas constantes foram deixadas isoladas para facilitar atualização futura.
