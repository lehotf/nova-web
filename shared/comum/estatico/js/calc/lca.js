(function () {
    var WIDGET_ID = 'calc-lca-widget';
    var RESULT_ID = 'calc-lca-resultado';
    var ENDPOINT_URL = '/comum/php/xhr/calc/lca.php';
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
            '      <input class="calc-rf__input" type="number" name="numero_meses" min="1" max="600" step="1" placeholder="Ex.: 24" />' +
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

                if (!response || !response.ok || !response.data || !response.data.resultado) {
                    setStatus(status, extractMessage(response) || 'Não foi possível calcular agora.', true);
                    results.classList.remove('is-visible');
                    return;
                }

                var result = response.data.resultado;
                renderResult(result, {
                    results: results,
                    resultValue: resultValue,
                    investedValue: investedValue,
                    earnedValue: earnedValue,
                    impostoResgateValue: impostoResgateValue,
                    summary: summary,
                    relatorioContainer: relatorioContainer,
                    status: status,
                    response: response
                });
            } catch (error) {
                setStatus(status, error.message || 'Não foi possível calcular agora.', true);
                results.classList.remove('is-visible');
            } finally {
                button.disabled = false;
            }
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

        nodes.earnedValue.classList.toggle('calc-rf__text-green', jurosAcumulados > 0);

        nodes.summary.textContent = 'Em ' + result.numero_meses + ' meses, com aporte inicial de ' +
            currencyFormatter.format(result.investimento_inicial || 0) +
            ' e aplicações mensais de ' + currencyFormatter.format(result.aplicacao_mensal || 0) +
            ', a simulação considera LCI/LCA isenta de IR com rentabilidade base de ' + buildRateReferenceText(result) + '. Por isso, o valor projetado já é líquido.';

        nodes.relatorioContainer.innerHTML = '<div class="calc-rf__report-content">' +
            '<h3>Explicação Detalhada</h3>' +
            '<p><strong>Importante:</strong> esta calculadora foi modelada para <strong>LCI/LCA</strong> sem prazo final obrigatório e com <strong>isenção de imposto de renda</strong>. Por isso, não há cálculo de IR nem necessidade de separar aportes por faixas tributárias.</p>' +
            '<p>Caso você resolva investir um valor inicial de <strong>' + currencyFormatter.format(result.investimento_inicial || 0) + '</strong> e realizar aplicações mensais de <strong>' + currencyFormatter.format(result.aplicacao_mensal || 0) + '</strong> a uma rentabilidade efetiva de <strong>' + preciseRateFormatter.format(result.taxa_juros_mensal || 0) + '%</strong> ao mês, teremos o seguinte:</p>' +
            '<p>A base informada para a taxa foi <strong>' + buildRateReferenceText(result) + '</strong>.</p>' +
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
        setStatus(nodes.status, nodes.response.message || 'Cálculo concluído.', false);
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
        if (!response || typeof response !== 'object') {
            return '';
        }

        if (typeof response.message === 'string' && response.message.trim() !== '') {
            return response.message;
        }

        if (response.error && typeof response.error.message === 'string') {
            return response.error.message;
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

        if (!isFinite(numeroMeses) || numeroMeses < 1 || numeroMeses > 600) {
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
