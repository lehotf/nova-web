# Matemática, Lógica e Limites da Calculadora de Fundo de Renda Fixa de Longo Prazo

Este documento descreve, com precisão técnica, a modelagem adotada na calculadora implementada em `fundo_lp.php` e `fundo_lp.js`.

O objetivo da ferramenta é fornecer uma **simulação educacional e financeiramente coerente** para **fundos de renda fixa de longo prazo sujeitos a come-cotas**, usando uma **aproximação mensal**. Ela não pretende reproduzir cada detalhe operacional de uma administradora, de um custodiante ou da Receita Federal em nível diário.

## 1. Escopo exato da calculadora

Esta calculadora foi modelada para:

- fundo de renda fixa **de longo prazo**;
- incidência de **come-cotas semestral de 15%**;
- incidência de **IR regressivo final** compatível com a lógica de longo prazo;
- aportes realizados no **início de cada mês**;
- rentabilidade informada como **taxa efetiva mensal fixa**;
- inflação projetada pela **repetição da última taxa mensal de IGP-M disponível**.

Ela **não** foi modelada para:

- fundos de renda fixa de **curto prazo**;
- fundos isentos, incentivados ou com regime tributário distinto;
- rentabilidade diária;
- contagem fiscal exata por dias corridos ou úteis;
- atualização do cenário macroeconômico com curva futura de inflação.

## 2. Regra tributária utilizada

### 2.1. Come-cotas

Para fundos de longo prazo sujeitos a come-cotas, a calculadora considera a antecipação semestral de IR à alíquota de **15%** sobre o rendimento acumulado desde o último evento de tributação.

Na prática regulatória, essa cobrança ocorre no **último dia útil de maio e novembro**. Como o motor da calculadora é **mensal**, a implementação aproxima esse evento usando os meses `5` e `11`.

Isso significa:

- a simulação preserva a lógica econômica do come-cotas;
- a data exata do último dia útil não é reproduzida;
- o resultado é coerente como aproximação mensal, não como cálculo operacional diário.

### 2.2. IR no resgate

No resgate, a calculadora usa a estrutura regressiva de longo prazo:

- até 6 meses: `22,5%`;
- de 7 a 12 meses: `20%`;
- de 13 a 24 meses: `17,5%`;
- acima de 24 meses: `15%`.

Observação importante:

- a legislação tributária trabalha com **dias**;
- o motor aqui trabalha com **meses**;
- portanto, esta é uma **aproximação mensal prudente**, não uma apuração fiscal literal por dias corridos.

## 3. Estratégia matemática do motor

## 3.1. Rentabilidade bruta teórica

Antes de considerar impostos, o sistema calcula uma trajetória bruta de capitalização:

1. parte do investimento inicial;
2. em cada mês, adiciona o aporte mensal;
3. aplica a taxa mensal informada;
4. repete o processo até o fim do horizonte.

Em forma recursiva:

`VF_m = (VF_{m-1} + Aporte_m) * (1 + i)`

Onde:

- `VF_m` é o valor ao final do mês `m`;
- `i` é a taxa de juros mensal em formato decimal.

Esse cálculo gera:

- `valor_futuro_bruto`;
- `rentabilidade_bruta_total`.

Esses números são úteis como referência para explicar o efeito do come-cotas e do IR final.

## 3.2. Sistema de baldes mensais

Para lidar com a tributação regressiva de aportes feitos em meses diferentes, o backend usa um sistema de **baldes**.

### Estrutura

O vetor possui **25 posições**:

- baldes `1` a `24`: representam aportes com idade de até `24 meses`;
- balde `25`: acumula tudo que já ultrapassou `24 meses`.

Cada balde guarda:

- `investido`: principal historicamente alocado naquele grupo;
- `saldo`: valor atual do grupo após rendimentos e impostos;
- `lucro_tributado`: rendimento que já passou por come-cotas;
- `base_atual`: base de comparação para medir o rendimento desde o último come-cotas.

### Envelhecimento mensal

A cada mês:

1. o conteúdo do balde `24` migra para o balde `25`;
2. os baldes `23..1` avançam uma posição;
3. o novo aporte entra no balde `1`.

### Motivo do balde 25

Esse balde extra é necessário porque a faixa de `15%` só se aplica **acima de 24 meses**. Se o vetor terminasse em `24`, o algoritmo misturaria:

- aportes ainda na faixa de `17,5%`;
- aportes já elegíveis à faixa de `15%`.

## 3.3. Aplicação da rentabilidade

Em cada iteração mensal, o sistema percorre todos os baldes com saldo positivo:

`rendimento_balde = saldo_balde * taxa_mensal`

Depois:

`saldo_balde = saldo_balde + rendimento_balde`

A soma dos rendimentos dos baldes forma o rendimento total do mês.

## 3.4. Cálculo do come-cotas

Nos meses equivalentes a maio e novembro, para cada balde:

1. calcula-se o rendimento acumulado desde a última base:
   `rendimento_desde_base = saldo - base_atual`
2. se esse rendimento for positivo, aplica-se:
   `come_cotas = rendimento_desde_base * 0,15`
3. o valor é abatido do saldo;
4. a base é redefinida para o saldo pós-tributação;
5. o rendimento tributado é acumulado em `lucro_tributado`.

Essa modelagem é importante porque o resgate final não deve cobrar novamente o total já antecipado. O que se cobra ao fim é:

- a alíquota cheia sobre o rendimento ainda não alcançado pelo come-cotas;
- mais a diferença entre a alíquota final e os `15%` já antecipados sobre o rendimento que já sofreu come-cotas.

## 3.5. IR complementar no resgate

No resgate, cada balde é tributado conforme sua idade mensal aproximada:

- `1..6` meses: `22,5%`;
- `7..12` meses: `20%`;
- `13..24` meses: `17,5%`;
- `25` (mais de 24 meses): `15%`.

Para cada balde:

### Parcela recente

É o rendimento ainda não alcançado pelo come-cotas:

`rendimento_recente = max(0, saldo - base_atual)`

Sobre essa parcela aplica-se a alíquota cheia do prazo.

### Parcela já alcançada pelo come-cotas

Essa parcela está registrada em `lucro_tributado`.

Como ela já sofreu antecipação de `15%`, o resgate só cobra:

`aliquota_final - 15%`

quando essa diferença for positiva.

Isso evita dupla tributação econômica da mesma base de rendimento.

## 4. Inflação e valor real

## 4.1. Fonte adotada

O backend consulta a tabela `indices` para buscar o valor mais recente de IGP-M armazenado sob o código `189`.

## 4.2. Projeção inflacionária

O sistema supõe, como simplificação, que essa taxa mensal recente se repete por todo o horizonte da simulação:

`inflacao_periodo = (1 + igpm_mensal)^n - 1`

Onde `n` é o número de meses.

Essa é uma hipótese de projeção, não uma previsão econômica robusta.

## 4.3. Valor real final

Para obter o valor real final:

`valor_real_final = valor_liquido / (1 + inflacao_periodo)`

Esse é o procedimento financeiramente correto para trazer um valor futuro a poder de compra presente sob a taxa projetada.

## 4.4. Valor real investido

O sistema também calcula um “valor real investido” aproximado, trazendo cada aporte mensal a valor presente com a mesma hipótese de inflação mensal constante.

Isso permite comparar:

- o poder de compra do total aportado ao longo do tempo;
- o poder de compra do valor líquido final.

Novamente, trata-se de uma métrica econômica de análise real, não de uma regra tributária.

## 5. O que o frontend comunica ao usuário

O frontend foi ajustado para deixar claras as seguintes ressalvas:

- a calculadora é voltada a **fundo de renda fixa de longo prazo**;
- o IR regressivo foi **aproximado em meses**;
- o come-cotas semestral é modelado em base mensal;
- a inflação é projetada pela repetição da última taxa mensal disponível.

Essa prudência é importante para não prometer exatidão legal ou operacional maior do que o modelo realmente entrega.

## 6. Interpretação correta dos campos exibidos

- `Total investido`: soma nominal do investimento inicial com todos os aportes mensais.
- `Rentabilidade do Fundo`: saldo final antes do IR de resgate, já refletindo o efeito do come-cotas ao longo do caminho.
- `Come-cotas (já retido)`: total estimado de IR antecipado ao longo da simulação.
- `IR no Resgate (a deduzir)`: imposto complementar devido no encerramento da posição.
- `Valor líquido projetado`: valor final após o IR complementar.
- `Valor real final`: valor líquido expresso em poder de compra presente, pela hipótese inflacionária adotada.

## 7. Limites conhecidos do modelo

Os principais limites assumidos conscientemente são:

- aproximação por meses em vez de dias;
- aportes no início do mês;
- taxa de juros mensal fixa;
- inflação mensal constante ao longo de todo o horizonte;
- ausência de diferenciação entre fundos de curto prazo, fundos exclusivos, ETFs, fundos incentivados ou estruturas especiais.

## 8. Conclusão

Para o escopo de uma calculadora educacional de **fundo de renda fixa de longo prazo**, a modelagem é coerente, desde que se respeitem estas premissas:

- o modelo é **mensal**;
- o come-cotas é **aproximado** em maio e novembro;
- a tributação regressiva é **aproximada** em faixas mensais;
- a inflação é uma **projeção simplificada**.

Dentro dessas hipóteses, o algoritmo consegue representar adequadamente:

- o custo de oportunidade do come-cotas;
- a diferença entre imposto antecipado e imposto complementar;
- a degradação inflacionária do poder de compra;
- a heterogeneidade de prazo dos aportes mensais por meio do sistema de baldes.
