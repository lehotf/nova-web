(function () {
    var WIDGET_ID = 'calc-fundo-cp-widget';
    var RESULT_ID = 'calc-fundo-cp-resultado';
    var ENDPOINT_URL = '/comum/php/xhr/calc/fundo_cp.php';
    var currencyFormatter = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
    var numberFormatter = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    var preciseRateFormatter = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 4,
        maximumFractionDigits: 4
    });

    function boot() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init, { once: true });
            return;
        }

        init();
    }

    function init() {
        if (document.getElementById(WIDGET_ID)) {
            return;
        }

        var articleBody = document.querySelector('.div_corpo_artigo');
        if (!articleBody) {
            return;
        }

        renderCalculator(articleBody);
    }

    function renderCalculator(articleBody) {
        var wrapper = document.createElement('section');
        wrapper.className = 'calc-rf';
        wrapper.id = WIDGET_ID;
        wrapper.innerHTML = '' +
            '<form class="calc-rf__form" novalidate>' +
            '  <div class="calc-rf__grid">' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Investimento inicial</span>' +
            '      <input class="calc-rf__input" type="text" name="investimento_inicial" inputmode="decimal" placeholder="Ex.: 10.000,00" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Aplicação mensal</span>' +
            '      <input class="calc-rf__input" type="text" name="aplicacao_mensal" inputmode="decimal" placeholder="Ex.: 500,00" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Número de meses</span>' +
            '      <input class="calc-rf__input" type="number" name="numero_meses" min="1" max="120" step="1" placeholder="Ex.: 24" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Base da rentabilidade</span>' +
            '      <input type="hidden" name="modo_taxa" value="cdi" />' +
            '      <div class="calc-rf__toggle" role="group" aria-label="Base da rentabilidade">' +
            '        <button class="calc-rf__toggle-button" type="button" data-rate-mode="taxa" aria-pressed="false" tabindex="-1">Taxa Mensal</button>' +
            '        <button class="calc-rf__toggle-button is-active" type="button" data-rate-mode="cdi" aria-pressed="true" tabindex="-1">% do CDI</button>' +
            '      </div>' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label" data-role="taxa-label">Percentual do CDI (%)</span>' +
            '      <input class="calc-rf__input" type="text" name="valor_taxa" inputmode="decimal" placeholder="Ex.: 110,00" />' +
            '    </label>' +
            '  </div>' +
            '  <p class="calc-rf__disclaimer" style="margin: 8px 0 0; font-size: 13px; line-height: 1.5; opacity: 0.85;">Simulação educacional para <strong>fundo de renda fixa de curto prazo</strong>, com aproximação mensal do IR regressivo, come-cotas semestral de 20% e inflação projetada pela repetição da última taxa mensal de IGP-M disponível.</p>' +
            '  <div class="calc-rf__actions">' +
            '    <button class="calc-rf__button" type="submit">Calcular</button>' +
            '    <div class="calc-rf__status" aria-live="polite"></div>' +
            '  </div>' +
            '</form>' +
            '<div class="calc-rf__results" id="' + RESULT_ID + '" aria-live="polite">' +
            '  <div class="calc-rf__result-main">Valor líquido projetado <span class="calc-rf__result-value">-</span></div>' +
            '  <div class="calc-rf__cards">' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Total investido</span><span class="calc-rf__card-value" data-role="investido">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Rentabilidade do Fundo</span><span class="calc-rf__card-value" data-role="juros">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">IR no Resgate (a deduzir)</span><span class="calc-rf__card-value" data-role="imposto_resgate">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Come-cotas (já retido)</span><span class="calc-rf__card-value" data-role="come_cotas">-</span></div>' +
            '  </div>' +
            '  <div class="calc-rf__footnote" data-role="resumo"></div>' +
            '  <div class="calc-rf__report" data-role="relatorio" style="margin-top: 20px; font-size: 14px;"></div>' +
            '</div>';

        articleBody.insertBefore(wrapper, articleBody.firstChild);

        bindCalculator(wrapper);
        focusFirstField(wrapper);
    }

    function bindCalculator(wrapper) {
        var form = wrapper.querySelector('.calc-rf__form');
        var button = wrapper.querySelector('.calc-rf__button');
        var status = wrapper.querySelector('.calc-rf__status');
        var results = wrapper.querySelector('.calc-rf__results');
        var resultValue = wrapper.querySelector('.calc-rf__result-value');
        var investedValue = wrapper.querySelector('[data-role="investido"]');
        var earnedValue = wrapper.querySelector('[data-role="juros"]');
        var comeCotasValue = wrapper.querySelector('[data-role="come_cotas"]');
        var impostoResgateValue = wrapper.querySelector('[data-role="imposto_resgate"]');
        var summary = wrapper.querySelector('[data-role="resumo"]');
        var relatorioContainer = wrapper.querySelector('[data-role="relatorio"]');
        var rateLabel = wrapper.querySelector('[data-role="taxa-label"]');
        var rateModeButtons = wrapper.querySelectorAll('[data-rate-mode]');
        var currencyFields = [
            form.elements.investimento_inicial,
            form.elements.aplicacao_mensal
        ];
        var rateModeField = form.elements.modo_taxa;
        var rateField = form.elements.valor_taxa;

        currencyFields.forEach(function (field) {
            field.addEventListener('input', function () {
                field.value = formatCurrencyInput(field.value);
            });
            field.addEventListener('blur', function () {
                field.value = formatCurrencyInput(field.value);
            });
        });

        rateField.addEventListener('input', function () {
            rateField.value = formatRateInput(rateField.value);
        });
        rateField.addEventListener('blur', function () {
            rateField.value = formatRateInput(rateField.value);
        });
        rateModeButtons.forEach(function (buttonNode) {
            buttonNode.addEventListener('click', function () {
                var nextMode = buttonNode.getAttribute('data-rate-mode');
                if (rateModeField.value !== nextMode) {
                    rateField.value = '';
                }
                setRateMode(rateModeField, rateModeButtons, nextMode);
                syncRateFieldUi(rateModeField, rateField, rateLabel);
            });
        });
        setRateMode(rateModeField, rateModeButtons, rateModeField.value);
        syncRateFieldUi(rateModeField, rateField, rateLabel);

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            var payload = buildPayload(form);
            if (!payload) {
                results.classList.remove('is-visible');
                setStatus(status, 'Preencha os campos com valores válidos para calcular.', true);
                return;
            }

            button.disabled = true;
            setStatus(status, 'Calculando...', false);

            try {
                var response = await send(ENDPOINT_URL, payload);

                if (!response || !response.cabecalho || response.cabecalho.status !== 'ok' || !response.dados || !response.dados.resultado) {
                    setStatus(status, extractMessage(response) || 'Não foi possível calcular agora.', true);
                    results.classList.remove('is-visible');
                    return;
                }

                var result = response.dados.resultado;
                resultValue.textContent = currencyFormatter.format(result.valor_liquido || 0);
                investedValue.textContent = currencyFormatter.format(result.total_investido || 0);
                earnedValue.textContent = currencyFormatter.format(result.juros_acumulados || 0);
                comeCotasValue.textContent = currencyFormatter.format(result.total_descontado_come_cotas || 0);
                impostoResgateValue.textContent = currencyFormatter.format(result.imposto_resgate || 0);
                summary.textContent = 'Em ' + result.numero_meses + ' meses, com aporte inicial de ' +
                    currencyFormatter.format(result.investimento_inicial || 0) +
                    ' e aplicações mensais de ' + currencyFormatter.format(result.aplicacao_mensal || 0) +
                    ', esta estimativa considera um fundo de renda fixa de curto prazo com rentabilidade base de ' + buildRateReferenceText(result) + '. A "Rentabilidade do Fundo" já reflete a perda de cotas e o custo de oportunidade do come-cotas semestral de 20%.';

                var totalImposto = result.total_descontado_come_cotas + result.imposto_resgate;
                var aliquotaMediaIr = result.rentabilidade_bruta_total > 0 ? (totalImposto / result.rentabilidade_bruta_total) * 100 : 0;
                var rentabilidadeLiquida = result.valor_liquido - result.total_investido;
                var percentualRentabilidadeLiquida = result.total_investido > 0 ? (rentabilidadeLiquida / result.total_investido) * 100 : 0;
                var inflacaoPeriodoPercentual = result.inflacao_periodo_decimal * 100;
                var percentualRentabilidadeRealLiquida = result.valor_real_investido > 0 ? (result.rentabilidade_real_liquida / result.valor_real_investido) * 100 : 0;

                var saldoAntesDoResgate = result.total_investido + result.juros_acumulados;
                var percentualGanhoRelativo = result.total_investido > 0 ? (result.juros_acumulados / result.total_investido) * 100 : 0;

                    var jurosFmt = result.juros_acumulados > 0 ? '<strong class="calc-rf__text-green">' + currencyFormatter.format(result.juros_acumulados) + '</strong>' : '<strong>' + currencyFormatter.format(result.juros_acumulados) + '</strong>';
                    var percGanhoFmt = result.juros_acumulados > 0 ? '<strong class="calc-rf__text-green">' + numberFormatter.format(percentualGanhoRelativo) + '%</strong>' : '<strong>' + numberFormatter.format(percentualGanhoRelativo) + '%</strong>';
                    var comeCotasFmt = result.total_descontado_come_cotas > 0 ? '<strong class="calc-rf__text-red">' + currencyFormatter.format(result.total_descontado_come_cotas) + '</strong>' : '<strong>' + currencyFormatter.format(result.total_descontado_come_cotas) + '</strong>';
                    var impostoFmt = result.imposto_resgate > 0 ? '<strong class="calc-rf__text-red">' + currencyFormatter.format(result.imposto_resgate) + '</strong>' : '<strong>' + currencyFormatter.format(result.imposto_resgate) + '</strong>';
                    var rentLiqFmt = rentabilidadeLiquida > 0 ? '<strong class="calc-rf__text-green">' + currencyFormatter.format(rentabilidadeLiquida) + '</strong>' : '<strong>' + currencyFormatter.format(rentabilidadeLiquida) + '</strong>';
                    var percRentLiqFmt = rentabilidadeLiquida > 0 ? '<strong class="calc-rf__text-green">' + numberFormatter.format(percentualRentabilidadeLiquida) + '%</strong>' : '<strong>' + numberFormatter.format(percentualRentabilidadeLiquida) + '%</strong>';
                    var rentRealFmt = result.rentabilidade_real_liquida > 0 ? '<strong class="calc-rf__text-green">' + currencyFormatter.format(result.rentabilidade_real_liquida) + '</strong>' : (result.rentabilidade_real_liquida < 0 ? '<strong class="calc-rf__text-red">' + currencyFormatter.format(result.rentabilidade_real_liquida) + '</strong>' : '<strong>' + currencyFormatter.format(result.rentabilidade_real_liquida) + '</strong>');
                    var percRentRealFmt = result.rentabilidade_real_liquida > 0 ? '<strong class="calc-rf__text-green">' + numberFormatter.format(percentualRentabilidadeRealLiquida) + '%</strong>' : (result.rentabilidade_real_liquida < 0 ? '<strong class="calc-rf__text-red">' + numberFormatter.format(percentualRentabilidadeRealLiquida) + '%</strong>' : '<strong>' + numberFormatter.format(percentualRentabilidadeRealLiquida) + '%</strong>');

                    if (result.juros_acumulados > 0) earnedValue.classList.add('calc-rf__text-green'); else earnedValue.classList.remove('calc-rf__text-green');
                    if (result.total_descontado_come_cotas > 0) comeCotasValue.classList.add('calc-rf__text-red'); else comeCotasValue.classList.remove('calc-rf__text-red');
                    if (result.imposto_resgate > 0) impostoResgateValue.classList.add('calc-rf__text-red'); else impostoResgateValue.classList.remove('calc-rf__text-red');

                    earnedValue.style.color = '';
                    comeCotasValue.style.color = '';
                    impostoResgateValue.style.color = '';

                    var htmlRelatorio = '<div class="calc-rf__report-content">' +
                        '<h3>Explicação Detalhada</h3>' +
                        '<p><strong>Importante:</strong> esta calculadora foi modelada para <strong>fundo de renda fixa de curto prazo</strong>. O imposto regressivo foi aproximado em meses, embora a legislação use contagem em dias, e a inflação foi projetada pela repetição da última taxa mensal de IGP-M disponível na base.</p>' +
                        '<p>Caso você resolva investir um valor inicial de <strong>' + currencyFormatter.format(result.investimento_inicial) + '</strong> e realizar aplicações mensais de <strong>' + currencyFormatter.format(result.aplicacao_mensal) + '</strong> a uma rentabilidade efetiva de <strong>' + preciseRateFormatter.format(result.taxa_juros_mensal) + '%</strong> ao mês, teremos o seguinte:</p>' +
                        '<p>A base informada para a taxa foi <strong>' + buildRateReferenceText(result) + '</strong>.</p>' +
                        '<p>Ao final do período de <strong>' + result.numero_meses + ' meses</strong> você terá investido um total de <strong>' + currencyFormatter.format(result.total_investido) + '</strong>.</p>' +
                        '<p>Este investimento lhe dará uma rentabilidade de ' + jurosFmt + '; totalizando um valor final no fundo de <strong>' + currencyFormatter.format(saldoAntesDoResgate) + '</strong>. Esta rentabilidade representaria um ganho de ' + percGanhoFmt + ' em relação ao valor investido.</p>' +
                        '<p>Para fundos de renda fixa de curto prazo sujeitos a come-cotas, a legislação prevê antecipação semestral de IR, normalmente no último dia útil de maio e novembro. Nesta simulação mensal, essa cobrança foi aproximada nesses dois meses e calculada à alíquota de <strong>20%</strong> sobre o rendimento acumulado desde o último evento de come-cotas. A quantidade estimada de cotas perdidas no período foi equivalente a ' + comeCotasFmt + '.</p>' +
                        '<p>No resgate, calcula-se imposto complementar de ' + impostoFmt + '. Esse valor representa a diferença entre a alíquota final regressiva aplicável ao prazo do aporte e o que já foi antecipado pelo come-cotas. Nesta calculadora, essa tabela foi aproximada por meses: até 6 meses, 22,5%; acima de 6 meses, 20%. A alíquota média efetiva de IR neste exemplo foi de <strong>' + numberFormatter.format(aliquotaMediaIr) + '%</strong> sobre o ganho bruto estimado do fundo.</p>' +
                        '<p>Portanto, a rentabilidade líquida do investimento será de ' + rentLiqFmt + '. Percentualmente falando, isto corresponde a ' + percRentLiqFmt + ' do valor investido. O valor líquido que você receberá na conta é de <strong>' + currencyFormatter.format(result.valor_liquido) + '</strong>.</p>' +
                        '<h4>Considerando a Inflação:</h4>' +
                        '<p>Mas é preciso levar em consideração a inflação do período. Considerando o IGP-M mensal mais recente disponível na base, de <strong>' + numberFormatter.format(result.igpm_mensal) + '%</strong> (' + result.igpm_data + '), a inflação projetada para o período, por repetição dessa taxa ao longo de toda a simulação, é de aproximadamente <strong>' + numberFormatter.format(inflacaoPeriodoPercentual) + '%</strong>.</p>' +
                        '<p>Isto significa que o valor real do seu dinheiro, já descontando os impostos e a inflação, no final do período, terá o poder de compra equivalente a <strong>' + currencyFormatter.format(result.valor_real_final) + '</strong> nos dias de hoje. <small><em>(Nota: Em matemática financeira, para encontrar o valor real ou poder de compra de um montante futuro, não realizamos uma subtração simples da porcentagem. O correto é descapitalizar o valor, dividindo-o pelo fator de correção da inflação: <code>' + currencyFormatter.format(result.valor_liquido) + ' / (1 + ' + numberFormatter.format(inflacaoPeriodoPercentual) + '%)</code>).</em></small></p>' +
                        '<p>Em contrapartida, o valor total real investido não coincide com os <strong>' + currencyFormatter.format(result.total_investido) + '</strong> nominais aportados ao longo do tempo, porque cada aporte mensal é trazido a valor presente com base nessa mesma hipótese simplificadora de inflação mensal constante.</p>' +
                        '<p>Considerando essa metodologia, o valor total real investido será de <strong>' + currencyFormatter.format(result.valor_real_investido) + '</strong>.</p>' +
                        '<p>A rentabilidade real líquida, então, será de ' + rentRealFmt + ', já considerando o valor líquido final e a projeção inflacionária adotada.</p>' +
                        '<p>Isto significa uma rentabilidade real líquida, considerando impostos e inflação, de ' + percRentRealFmt + ' no período de <strong>' + result.numero_meses + ' meses</strong>.</p>' +
                        '</div>';

                    if (result.relatorio && result.relatorio.length > 0) {
                        var hasComeCotas = result.relatorio.some(function(l) { return l.desconto_come_cotas > 0; });
                        
                        if (hasComeCotas) {
                            htmlRelatorio += '<h4 class="calc-rf__mt-30">Detalhamento da Cobrança de Come-cotas</h4>' +
                                '<div class="calc-rf__table-wrapper">' +
                                '<table class="calc-rf__table">' +
                                '<thead>' +
                                '<tr><th>Mês/Ano</th><th>Come-cotas (20%)</th><th>Saldo Pós-Retenção</th></tr>' +
                                '</thead><tbody>';
                            
                            result.relatorio.forEach(function (linha) {
                                if (linha.desconto_come_cotas > 0) {
                                    htmlRelatorio += '<tr>' +
                                        '<td>' + linha.mes_ano + '</td>' +
                                        '<td class="calc-rf__text-red">-' + currencyFormatter.format(linha.desconto_come_cotas) + '</td>' +
                                        '<td>' + currencyFormatter.format(linha.saldo_final) + '</td>' +
                                        '</tr>';
                                }
                            });
                            
                            htmlRelatorio += '</tbody></table></div>';
                        }
                    }

                    htmlRelatorio += '<p style="margin-top: 30px; font-size: 14px; color: #dbdbdb; padding: 15px; border-radius: 8px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);"><strong>Atenção:</strong> Esta calculadora foi desenvolvida exclusivamente para fins educacionais e de simulação. Caso necessite de cálculos exatos para tomada de decisão financeira ou cumprimento de obrigações fiscais, consulte um especialista qualificado.</p>';

                    relatorioContainer.innerHTML = htmlRelatorio;
                results.classList.add('is-visible');
                setStatus(status, response.cabecalho.msg || 'Cálculo concluído.', false);
                scrollToResults(results);
            } catch (error) {
                setStatus(status, error.message || 'Não foi possível calcular agora.', true);
                results.classList.remove('is-visible');
            } finally {
                button.disabled = false;
            }
        });
    }

    function scrollToResults(results) {
        if (!results) {
            return;
        }

        window.requestAnimationFrame(function () {
            results.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    }

    function focusFirstField(wrapper) {
        var form = wrapper && wrapper.querySelector('.calc-rf__form');
        var firstField;

        if (!form || !form.elements || !form.elements.length) {
            return;
        }

        firstField = Array.prototype.find.call(form.elements, function (field) {
            return field && typeof field.focus === 'function' && !field.disabled && field.type !== 'hidden';
        });

        if (!firstField) {
            return;
        }

        window.requestAnimationFrame(function () {
            firstField.focus();
        });
    }

    function setStatus(node, message, isError) {
        node.textContent = message || '';
        node.classList.toggle('calc-rf__status--error', !!isError);
    }

    function extractMessage(response) {
        if (response && response.cabecalho && response.cabecalho.msg) {
            return response.cabecalho.msg;
        }

        return '';
    }

    function buildPayload(form) {
        var investimentoInicial = parseLocaleNumber(form.elements.investimento_inicial.value);
        var aplicacaoMensal = parseLocaleNumber(form.elements.aplicacao_mensal.value);
        var numeroMeses = parseInt(form.elements.numero_meses.value, 10);
        var modoTaxa = form.elements.modo_taxa.value === 'cdi' ? 'cdi' : 'taxa';
        var valorTaxa = parseLocaleNumber(form.elements.valor_taxa.value);

        if (!isFinite(investimentoInicial) || investimentoInicial < 0) {
            return null;
        }

        if (!isFinite(aplicacaoMensal) || aplicacaoMensal < 0) {
            return null;
        }

        if (!isFinite(numeroMeses) || numeroMeses < 1 || numeroMeses > 120) {
            return null;
        }

        if (!isFinite(valorTaxa)) {
            return null;
        }

        if (modoTaxa === 'taxa' && (valorTaxa < 0 || valorTaxa > 2)) {
            return null;
        }

        if (modoTaxa === 'cdi' && (valorTaxa < 0 || valorTaxa > 150)) {
            return null;
        }

        var payload = {
            investimento_inicial: investimentoInicial,
            aplicacao_mensal: aplicacaoMensal,
            numero_meses: numeroMeses,
            modo_taxa: modoTaxa
        };

        if (modoTaxa === 'cdi') {
            payload.percentual_cdi = valorTaxa;
        } else {
            payload.taxa_juros_mensal = valorTaxa;
        }

        return payload;
    }

    function parseLocaleNumber(value) {
        if (typeof value !== 'string') {
            return Number(value);
        }

        var normalized = value.trim().replace(/\s+/g, '');
        if (!normalized) {
            return NaN;
        }

        if (normalized.indexOf(',') !== -1) {
            normalized = normalized.replace(/\./g, '').replace(',', '.');
        }

        return Number(normalized);
    }

    function onlyDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function formatCurrencyInput(value) {
        var digits = onlyDigits(value);
        if (!digits) {
            return '';
        }

        var integerPart = digits.slice(0, -2) || '0';
        var decimalPart = digits.slice(-2).padStart(2, '0');
        integerPart = integerPart.replace(/^0+(?=\d)/, '');

        return integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + decimalPart;
    }

    function formatRateInput(value) {
        var digits = onlyDigits(value);
        if (!digits) {
            return '';
        }

        var integerPart = digits.slice(0, -2) || '0';
        var decimalPart = digits.slice(-2).padStart(2, '0');
        integerPart = integerPart.replace(/^0+(?=\d)/, '');

        return integerPart + ',' + decimalPart;
    }

    function syncRateFieldUi(rateModeField, rateField, rateLabel) {
        var isCdi = rateModeField && rateModeField.value === 'cdi';

        if (rateLabel) {
            rateLabel.textContent = isCdi ? 'Percentual do CDI (%)' : 'Taxa de juros mensal (%)';
        }

        if (rateField) {
            rateField.placeholder = isCdi ? 'Ex.: 110,00' : 'Ex.: 0,85';
        }
    }

    function setRateMode(rateModeField, rateModeButtons, mode) {
        var normalizedMode = mode === 'cdi' ? 'cdi' : 'taxa';

        if (rateModeField) {
            rateModeField.value = normalizedMode;
        }

        Array.prototype.forEach.call(rateModeButtons || [], function (buttonNode) {
            var isActive = buttonNode.getAttribute('data-rate-mode') === normalizedMode;
            buttonNode.classList.toggle('is-active', isActive);
            buttonNode.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function buildRateReferenceText(result) {
        if (result.modo_taxa === 'cdi') {
            return numberFormatter.format(result.percentual_cdi || 0) + '% do CDI de ' +
                preciseRateFormatter.format(result.cdi_mensal || 0) + '% a.m. (' + (result.cdi_data || '-') + ')';
        }

        return numberFormatter.format(result.taxa_juros_mensal || 0) + '% ao mês';
    }

    boot();
})();
