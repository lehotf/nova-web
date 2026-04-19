# Matemática, Lógica e Limites da Calculadora de CDB

Este documento descreve a modelagem adotada na calculadora implementada em `cdb.php` e `cdb.js`.

O objetivo da ferramenta é fornecer uma **simulação educacional e financeiramente coerente** para **CDBs tributados pela tabela regressiva de longo prazo**, usando uma **aproximação mensal**. Ela também modela o efeito do **vencimento obrigatório**: quando o horizonte escolhido ultrapassa o prazo do CDB, a calculadora força **resgate com imposto** e, se ainda houver meses restantes, faz o **reinvestimento do valor líquido**.

## 1. Escopo exato da calculadora

Esta calculadora foi modelada para:

- CDB com tributação regressiva equivalente à tabela de longo prazo;
- aportes realizados no início de cada mês;
- rentabilidade informada como taxa efetiva mensal fixa;
- vencimento expresso em anos, com valor padrão de 5 anos;
- reinvestimento automático após vencimentos intermediários;
- inflação projetada pela repetição da última taxa mensal de IGP-M disponível.

Ela não foi modelada para:

- CDBs isentos, incentivados ou com regime tributário distinto;
- marcação a mercado;
- liquidez diária com contagem fiscal exata por dias corridos;
- taxas pós-fixadas ligadas a CDI, IPCA ou percentuais variáveis;
- curva futura de inflação.

## 2. Regra tributária utilizada

O CDB desta calculadora **não possui come-cotas**.

O imposto só aparece em dois momentos:

- no vencimento obrigatório do CDB, quando o papel precisa ser resgatado;
- no resgate final da simulação.

A tabela usada é a mesma do fundo de longo prazo, aproximada em meses:

- até 6 meses: `22,5%`;
- de 7 a 12 meses: `20%`;
- de 13 a 24 meses: `17,5%`;
- acima de 24 meses: `15%`.

Observação importante:

- a legislação tributária trabalha com dias;
- o motor aqui trabalha com meses;
- portanto, esta é uma aproximação mensal prudente, não uma apuração fiscal literal.

## 3. Estratégia matemática do motor

## 3.1. Rentabilidade bruta teórica

Antes de considerar impostos, o sistema calcula uma trajetória bruta de capitalização:

`VF_m = (VF_{m-1} + Aporte_m) * (1 + i)`

Onde:

- `VF_m` é o valor ao final do mês `m`;
- `i` é a taxa de juros mensal em formato decimal.

Esse cálculo gera:

- `valor_futuro_bruto`;
- `rentabilidade_bruta_total`.

Esses números servem como referência para medir a diferença entre o cenário sem impostos e o cenário com tributação.

## 3.2. Sistema de baldes mensais

Para lidar com a tributação regressiva de aportes feitos em meses diferentes, o backend usa um sistema de **baldes**:

- baldes `1` a `24`: representam aportes com idade de até `24 meses`;
- balde `25`: acumula tudo que já ultrapassou `24 meses`.

Cada balde guarda:

- `investido`: principal daquele grupo;
- `saldo`: valor atualizado do grupo após rendimentos.

Como não existe come-cotas, não há necessidade de rastrear lucro já tributado ou base fiscal semestral.

## 3.3. Envelhecimento mensal

A cada mês:

1. o conteúdo do balde `24` migra para o balde `25`;
2. os baldes `23..1` avançam uma posição;
3. o novo aporte entra no balde `1`;
4. aplica-se a rentabilidade mensal em todos os baldes com saldo positivo.

Essa estrutura permite estimar a alíquota correta de cada grupo no momento do resgate.

## 3.4. Resgate no vencimento

Quando o mês da simulação alcança um múltiplo do prazo do CDB, o sistema trata isso como **vencimento obrigatório**.

Nesse evento:

1. calcula o lucro de cada balde:
   `lucro_balde = max(0, saldo - investido)`
2. aplica a alíquota regressiva correspondente à idade aproximada do balde;
3. soma o imposto do evento;
4. apura o saldo líquido após o resgate.

Se ainda houver meses restantes após esse vencimento:

- o valor líquido é reinvestido integralmente;
- esse reinvestimento vira novo principal;
- a idade fiscal volta a zero para o novo ciclo.

Se o vencimento coincidir com o último mês da simulação:

- há resgate e imposto;
- não há reinvestimento, porque o horizonte termina ali.

## 3.5. Resgate final da simulação

Se a simulação terminar antes do vencimento, ou entre dois vencimentos, a calculadora faz um resgate final do saldo remanescente para entregar ao usuário:

- `saldo_bruto_final`;
- `imposto_resgate_final`;
- `valor_liquido`.

Assim, o valor mostrado ao final sempre representa um valor **líquido de IR**.

## 3.6. Comparativo para medir o efeito dos vencimentos

Quando existem vencimentos dentro do horizonte, a calculadora roda também um cenário de comparação em que **não haveria vencimentos intermediários**.

Esse cenário não pretende representar um produto real. Ele existe apenas para medir, didaticamente, o efeito econômico de:

- antecipar imposto no meio do caminho;
- reiniciar o prazo fiscal após o reinvestimento.

O campo `impacto_resgates_obrigatorios` mostra a diferença entre:

- valor líquido com vencimentos reais;
- valor líquido do cenário comparativo sem vencimentos intermediários.

Se o horizonte escolhido for menor que o prazo do CDB:

- não há vencimento dentro da janela;
- esse impacto tende a zero.

## 4. Inflação e valor real

## 4.1. Fonte adotada

O backend consulta a tabela `indices` para buscar o valor mais recente de IGP-M armazenado sob o código `189`.

## 4.2. Projeção inflacionária

O sistema supõe, como simplificação, que essa taxa mensal recente se repete por todo o horizonte:

`inflacao_periodo = (1 + igpm_mensal)^n - 1`

Onde `n` é o número de meses.

## 4.3. Valor real final

Para obter o valor real final:

`valor_real_final = valor_liquido / (1 + inflacao_periodo)`

## 4.4. Valor real investido

O sistema também traz cada aporte mensal a valor presente com a mesma hipótese de inflação mensal constante.

Isso permite comparar:

- o poder de compra do total aportado;
- o poder de compra do valor líquido final.

## 5. O que o frontend comunica ao usuário

O frontend foi ajustado para deixar claras as seguintes ressalvas:

- a calculadora é voltada a CDB;
- não existe come-cotas;
- a tabela regressiva foi aproximada em meses;
- o vencimento obriga resgate com imposto;
- se houver meses restantes, o valor líquido é reinvestido;
- o relatório compara o cenário real com um cenário didático sem vencimentos intermediários para explicar o efeito do vencimento.

## 6. Interpretação correta dos campos exibidos

- `Total investido`: soma nominal do investimento inicial com todos os aportes mensais.
- `Rentabilidade do CDB`: saldo bruto final menos o total investido, já refletindo o efeito econômico de tributos pagos em vencimentos anteriores.
- `IR em resgates intermediários`: imposto pago nos vencimentos que ocorreram antes do último mês da simulação.
- `IR no resgate final`: imposto do último resgate considerado no cálculo.
- `Valor líquido projetado`: valor final após todos os impostos.
- `Impacto dos resgates obrigatórios`: diferença entre o cenário real com vencimentos e o cenário comparativo sem vencimentos intermediários.
- `Valor real final`: valor líquido expresso em poder de compra presente.

## 7. Limites conhecidos do modelo

Os principais limites assumidos conscientemente são:

- aproximação por meses em vez de dias;
- aportes no início do mês;
- taxa de juros mensal fixa;
- reinvestimento imediato no próprio mês do vencimento;
- inflação mensal constante ao longo de toda a simulação.

## 8. Conclusão

Para o escopo de uma calculadora educacional de **CDB**, a modelagem é coerente, desde que se respeitem estas premissas:

- o modelo é mensal;
- não há come-cotas;
- o imposto é cobrado nos resgates;
- o vencimento força resgate e, se necessário, reinvestimento;
- a inflação é uma projeção simplificada.

Dentro dessas hipóteses, o algoritmo consegue representar adequadamente:

- a tributação regressiva de aportes com idades diferentes;
- o impacto de antecipar imposto por vencimentos intermediários;
- o reinício do prazo fiscal após reinvestimento;
- a perda inflacionária do poder de compra.
