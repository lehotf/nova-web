(function () {
    var WIDGET_ID = 'calc-lca-widget';
    var RESULT_ID = 'calc-lca-resultado';
    var ENDPOINT_ACTION = 'comum/calc/lca';
    var currencyFormatter = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
    var numberFormatter = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
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
            '      <input class="calc-rf__input" type="number" name="numero_meses" min="1" max="600" step="1" placeholder="Ex.: 24" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Taxa de juros mensal (%)</span>' +
            '      <input class="calc-rf__input" type="text" name="taxa_juros_mensal" inputmode="decimal" placeholder="Ex.: 0,85" />' +
            '    </label>' +
            '  </div>' +
            '  <p class="calc-rf__disclaimer" style="margin: 8px 0 0; font-size: 13px; line-height: 1.5; opacity: 0.85;">Simulação educacional para <strong>LCI/LCA</strong>, <strong>sem prazo final obrigatório</strong> e <strong>isenta de imposto de renda</strong>. Por isso, a rentabilidade projetada já representa o valor líquido.</p>' +
            '  <div class="calc-rf__actions">' +
            '    <button class="calc-rf__button" type="submit">Calcular</button>' +
            '    <div class="calc-rf__status" aria-live="polite"></div>' +
            '  </div>' +
            '</form>' +
            '<div class="calc-rf__results" id="' + RESULT_ID + '" aria-live="polite">' +
            '  <div class="calc-rf__result-main">Valor líquido projetado <span class="calc-rf__result-value">-</span></div>' +
            '  <div class="calc-rf__cards">' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Total investido</span><span class="calc-rf__card-value" data-role="investido">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Rentabilidade líquida</span><span class="calc-rf__card-value" data-role="juros">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">IR no resgate</span><span class="calc-rf__card-value" data-role="imposto_resgate">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Taxa mensal</span><span class="calc-rf__card-value" data-role="taxa">-</span></div>' +
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
        var impostoResgateValue = wrapper.querySelector('[data-role="imposto_resgate"]');
        var rateValue = wrapper.querySelector('[data-role="taxa"]');
        var summary = wrapper.querySelector('[data-role="resumo"]');
        var relatorioContainer = wrapper.querySelector('[data-role="relatorio"]');
        var currencyFields = [
            form.elements.investimento_inicial,
            form.elements.aplicacao_mensal
        ];
        var rateField = form.elements.taxa_juros_mensal;

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

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var payload = buildPayload(form);
            if (!payload) {
                results.classList.remove('is-visible');
                setStatus(status, 'Preencha os campos com valores válidos para calcular.', true);
                return;
            }

            button.disabled = true;
            setStatus(status, 'Calculando...', false);

            window.send({
                a: ENDPOINT_ACTION,
                dados: payload,
                f: function (response) {
                    button.disabled = false;

                    if (!response || !response.cabecalho || response.cabecalho.status !== 'ok' || !response.dados || !response.dados.resultado) {
                        setStatus(status, extractMessage(response) || 'Não foi possível calcular agora.', true);
                        results.classList.remove('is-visible');
                        return;
                    }

                    var result = response.dados.resultado;
                    renderResult(result, {
                        results: results,
                        resultValue: resultValue,
                        investedValue: investedValue,
                        earnedValue: earnedValue,
                        impostoResgateValue: impostoResgateValue,
                        rateValue: rateValue,
                        summary: summary,
                        relatorioContainer: relatorioContainer,
                        status: status,
                        response: response
                    });
                }
            });
        });
    }

    function renderResult(result, nodes) {
        var totalInvestido = result.total_investido || 0;
        var jurosAcumulados = result.juros_acumulados || 0;
        var valorLiquido = result.valor_liquido || 0;
        var percentualRentabilidadeLiquida = totalInvestido > 0 ? (jurosAcumulados / totalInvestido) * 100 : 0;
        var inflacaoPeriodoPercentual = (result.inflacao_periodo_decimal || 0) * 100;
        var percentualRentabilidadeRealLiquida = result.valor_real_investido > 0 ? ((result.rentabilidade_real_liquida || 0) / result.valor_real_investido) * 100 : 0;
        var rentRealFmt = (result.rentabilidade_real_liquida || 0) > 0
            ? '<strong class="calc-rf__text-green">' + currencyFormatter.format(result.rentabilidade_real_liquida || 0) + '</strong>'
            : '<strong>' + currencyFormatter.format(result.rentabilidade_real_liquida || 0) + '</strong>';
        var rentLiqFmt = jurosAcumulados > 0
            ? '<strong class="calc-rf__text-green">' + currencyFormatter.format(jurosAcumulados) + '</strong>'
            : '<strong>' + currencyFormatter.format(jurosAcumulados) + '</strong>';

        nodes.resultValue.textContent = currencyFormatter.format(valorLiquido);
        nodes.investedValue.textContent = currencyFormatter.format(totalInvestido);
        nodes.earnedValue.textContent = currencyFormatter.format(jurosAcumulados);
        nodes.impostoResgateValue.textContent = currencyFormatter.format(result.imposto_resgate || 0);
        nodes.rateValue.textContent = numberFormatter.format(result.taxa_juros_mensal || 0) + '%';

        nodes.earnedValue.classList.toggle('calc-rf__text-green', jurosAcumulados > 0);

        nodes.summary.textContent = 'Em ' + result.numero_meses + ' meses, com aporte inicial de ' +
            currencyFormatter.format(result.investimento_inicial || 0) +
            ' e aplicações mensais de ' + currencyFormatter.format(result.aplicacao_mensal || 0) +
            ', a simulação considera LCI/LCA isenta de IR. Por isso, o valor projetado já é líquido.';

        nodes.relatorioContainer.innerHTML = '<div class="calc-rf__report-content">' +
            '<h3>Explicação Detalhada</h3>' +
            '<p><strong>Importante:</strong> esta calculadora foi modelada para <strong>LCI/LCA</strong> sem prazo final obrigatório e com <strong>isenção de imposto de renda</strong>. Por isso, não há cálculo de IR nem necessidade de separar aportes por faixas tributárias.</p>' +
            '<p>Caso você resolva investir um valor inicial de <strong>' + currencyFormatter.format(result.investimento_inicial || 0) + '</strong> e realizar aplicações mensais de <strong>' + currencyFormatter.format(result.aplicacao_mensal || 0) + '</strong> a uma rentabilidade de <strong>' + numberFormatter.format(result.taxa_juros_mensal || 0) + '%</strong> ao mês, teremos o seguinte:</p>' +
            '<p>Ao final do período de <strong>' + result.numero_meses + ' meses</strong> você terá investido um total de <strong>' + currencyFormatter.format(totalInvestido) + '</strong>.</p>' +
            '<p>Como o produto foi tratado como isento de imposto, a rentabilidade obtida já é líquida. Neste exemplo, o ganho projetado é de ' + rentLiqFmt + ', e o valor líquido final é de <strong>' + currencyFormatter.format(valorLiquido) + '</strong>. Isso corresponde a <strong>' + numberFormatter.format(percentualRentabilidadeLiquida) + '%</strong> do valor investido.</p>' +
            '<h4>Considerando a Inflação:</h4>' +
            '<p>Considerando o IGP-M mensal mais recente disponível na base, de <strong>' + numberFormatter.format(result.igpm_mensal || 0) + '%</strong> (' + (result.igpm_data || '-') + '), a inflação projetada para o período, por repetição dessa taxa ao longo de toda a simulação, é de aproximadamente <strong>' + numberFormatter.format(inflacaoPeriodoPercentual) + '%</strong>.</p>' +
            '<p>Isto significa que o valor real do seu dinheiro, já descontando a inflação, no final do período, terá o poder de compra equivalente a <strong>' + currencyFormatter.format(result.valor_real_final || 0) + '</strong> nos dias de hoje.</p>' +
            '<p>Considerando essa metodologia, o valor total real investido será de <strong>' + currencyFormatter.format(result.valor_real_investido || 0) + '</strong>.</p>' +
            '<p>A rentabilidade real líquida, então, será de ' + rentRealFmt + ', equivalente a <strong>' + numberFormatter.format(percentualRentabilidadeRealLiquida) + '%</strong> do valor real investido.</p>' +
            '<p style="margin-top: 30px; font-size: 14px; color: #dbdbdb; padding: 15px; border-radius: 8px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);"><strong>Atenção:</strong> Esta calculadora foi desenvolvida exclusivamente para fins educacionais e de simulação. Caso necessite de cálculos exatos para tomada de decisão financeira, consulte um especialista qualificado.</p>' +
            '</div>';

        nodes.results.classList.add('is-visible');
        setStatus(nodes.status, nodes.response.cabecalho.msg || 'Cálculo concluído.', false);
        scrollToResults(nodes.results);
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
        var taxaJurosMensal = parseLocaleNumber(form.elements.taxa_juros_mensal.value);

        if (!isFinite(investimentoInicial) || investimentoInicial < 0) {
            return null;
        }

        if (!isFinite(aplicacaoMensal) || aplicacaoMensal < 0) {
            return null;
        }

        if (!isFinite(numeroMeses) || numeroMeses < 1 || numeroMeses > 600) {
            return null;
        }

        if (!isFinite(taxaJurosMensal) || taxaJurosMensal < 0 || taxaJurosMensal > 2) {
            return null;
        }

        return {
            investimento_inicial: investimentoInicial,
            aplicacao_mensal: aplicacaoMensal,
            numero_meses: numeroMeses,
            taxa_juros_mensal: taxaJurosMensal
        };
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

    boot();
})();
