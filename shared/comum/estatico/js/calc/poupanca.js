(function () {
    var WIDGET_ID = 'calc-poupanca-widget';
    var RESULT_ID = 'calc-poupanca-resultado';
    var ENDPOINT_ACTION = 'comum/calc/poupanca';
    
    var currencyFormatter = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
    
    var numberFormatter = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
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
            '      <span class="calc-rf__label">Aplicação inicial</span>' +
            '      <input class="calc-rf__input" type="text" name="investimento_inicial" inputmode="decimal" placeholder="Ex.: 10.000,00" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Aplicação mensal</span>' +
            '      <input class="calc-rf__input" type="text" name="aplicacao_mensal" inputmode="decimal" placeholder="Ex.: 500,00" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Prazo (em meses)</span>' +
            '      <input class="calc-rf__input" type="number" name="numero_meses" min="1" step="1" placeholder="Ex.: 24" />' +
            '    </label>' +
            '  </div>' +
            '  <div class="calc-rf__actions">' +
            '    <button class="calc-rf__button" type="submit">Calcular Rendimento</button>' +
            '    <div class="calc-rf__status" aria-live="polite"></div>' +
            '  </div>' +
            '</form>' +
            '<div class="calc-rf__results" id="' + RESULT_ID + '" aria-live="polite">' +
            '  <div class="calc-rf__result-main">Valor projetado ao final do período <span class="calc-rf__result-value">-</span></div>' +
            '  <div class="calc-rf__cards">' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Total investido</span><span class="calc-rf__card-value" data-role="investido">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Rendimento da Poupança</span><span class="calc-rf__card-value" data-role="juros">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Taxa mensal da poupança</span><span class="calc-rf__card-value" data-role="taxa">-</span></div>' +
            '  </div>' +
            '  <div class="calc-rf__footnote" data-role="resumo"></div>' +
            '</div>';

        articleBody.insertBefore(wrapper, articleBody.firstChild);

        bindCalculator(wrapper);
    }

    function bindCalculator(wrapper) {
        var form = wrapper.querySelector('.calc-rf__form');
        var button = wrapper.querySelector('.calc-rf__button');
        var status = wrapper.querySelector('.calc-rf__status');
        var results = wrapper.querySelector('.calc-rf__results');
        
        var resultValue = wrapper.querySelector('.calc-rf__result-value');
        var investedValue = wrapper.querySelector('[data-role="investido"]');
        var earnedValue = wrapper.querySelector('[data-role="juros"]');
        var rateValue = wrapper.querySelector('[data-role="taxa"]');
        var summary = wrapper.querySelector('[data-role="resumo"]');
        
        var currencyFields = [
            form.elements.investimento_inicial,
            form.elements.aplicacao_mensal
        ];

        currencyFields.forEach(function (field) {
            field.addEventListener('input', function () {
                field.value = formatCurrencyInput(field.value);
            });
            field.addEventListener('blur', function () {
                field.value = formatCurrencyInput(field.value);
            });
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
                        setStatus(status, extractMessage(response) || 'Não foi possível calcular a rentabilidade.', true);
                        results.classList.remove('is-visible');
                        return;
                    }

                    var result = response.dados.resultado;
                    
                    resultValue.textContent = currencyFormatter.format(result.valor_futuro || 0);
                    investedValue.textContent = currencyFormatter.format(result.total_investido || 0);
                    earnedValue.textContent = currencyFormatter.format(result.juros_acumulados || 0);
                    
                    // A taxa que o backend encontrou no DB
                    rateValue.textContent = numberFormatter.format(result.taxa_juros_mensal || 0) + '%';
                    
                    summary.textContent = 'Em ' + result.numero_meses + ' meses, com aporte inicial de ' +
                        currencyFormatter.format(result.investimento_inicial || 0) +
                        ' e aplicações mensais de ' + currencyFormatter.format(result.aplicacao_mensal || 0) +
                        ', na Poupança.';
                        
                    results.classList.add('is-visible');
                    setStatus(status, response.cabecalho.msg || 'Cálculo concluído com sucesso.', false);
                    scrollToResults(results);
                }
            });
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

        if (!isFinite(investimentoInicial) || investimentoInicial < 0) {
            return null;
        }

        if (!isFinite(aplicacaoMensal) || aplicacaoMensal < 0) {
            return null;
        }

        if (!isFinite(numeroMeses) || numeroMeses < 1) {
            return null;
        }

        return {
            investimento_inicial: investimentoInicial,
            aplicacao_mensal: aplicacaoMensal,
            numero_meses: numeroMeses
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

        if (!integerPart && decimalPart !== '00') {
           integerPart = '0';
        } else if (!integerPart) {
           integerPart = '';
        }

        return integerPart ? integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + decimalPart : '';
    }

    boot();
})();
