# Calculadora de saldo devedor de empréstimo

Este documento descreve a lógica usada em `saldo_devedor.php` e `saldo_devedor.js`.

## Entradas

- `data_contratacao`
- `data_primeira_parcela`
- `valor_liberado`
- `valor_parcela`
- `prazo`

## Modelo

O cálculo trabalha com datas reais e usa duas leituras distintas:

- **taxa contratual**: taxa implícita das parcelas sobre o valor liberado;
- **taxa efetiva**: taxa implícita das mesmas parcelas, mas considerando o IOF como custo reduzindo o valor líquido recebido.

O saldo devedor atual é calculado a partir do saldo nominal do contrato até a data de referência, descontando as parcelas já vencidas e capitalizando a taxa contratual diária implícita.

As parcelas seguintes são projetadas sempre no mesmo dia do mês da primeira parcela. Quando o mês não possui esse dia, usa-se o último dia disponível do mês.

## IOF

As alíquotas ficam centralizadas em constantes no backend:

- `IOF_ALIQUOTA_FIXA_PORCENTAGEM = 0.38`
- `IOF_ALIQUOTA_DIARIA_PORCENTAGEM = 0.0082`
- `IOF_LIMITE_DIAS_DIARIOS = 365`

Essas constantes foram deixadas isoladas para facilitar atualização futura.
