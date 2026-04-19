(function () {
    var WIDGET_ID = 'calc-taxa-widget';
    var RESULT_ID = 'calc-taxa-resultado';
    
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
            '      <span class="calc-rf__label">Calcular taxa de juros mensal<br><small style="font-weight:normal;font-size:0.85em">(Informe a taxa anual %)</small></span>' +
            '      <input class="calc-rf__input" type="text" name="taxa_anual" inputmode="decimal" placeholder="Ex.: 10,50" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Calcular taxa de juros anual<br><small style="font-weight:normal;font-size:0.85em">(Informe a taxa mensal %)</small></span>' +
            '      <input class="calc-rf__input" type="text" name="taxa_mensal" inputmode="decimal" placeholder="Ex.: 0,85" />' +
            '    </label>' +
            '  </div>' +
            '  <div class="calc-rf__actions">' +
            '    <button class="calc-rf__button" type="submit">Calcular</button>' +
            '    <div class="calc-rf__status" aria-live="polite"></div>' +
            '  </div>' +
            '</form>' +
            '<div class="calc-rf__results" id="' + RESULT_ID + '" aria-live="polite">' +
            '  <div class="calc-rf__result-main">Taxa Equivalente <span class="calc-rf__result-value">-</span></div>' +
            '  <div class="calc-rf__cards">' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Taxa Informada</span><span class="calc-rf__card-value" data-role="taxa_informada_card">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Período Original</span><span class="calc-rf__card-value" data-role="periodo_original">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Período Equivalente</span><span class="calc-rf__card-value" data-role="periodo_equivalente">-</span></div>' +
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
        var taxaInformadaCard = wrapper.querySelector('[data-role="taxa_informada_card"]');
        var periodoOriginalCard = wrapper.querySelector('[data-role="periodo_original"]');
        var periodoEquivalenteCard = wrapper.querySelector('[data-role="periodo_equivalente"]');
        var summary = wrapper.querySelector('[data-role="resumo"]');
        
        var anualField = form.elements.taxa_anual;
        var mensalField = form.elements.taxa_mensal;
        
        anualField.addEventListener('input', function () {
            anualField.value = formatRateInput(anualField.value);
            if (anualField.value) {
                mensalField.value = '';
            }
        });
        anualField.addEventListener('blur', function () {
            anualField.value = formatRateInput(anualField.value);
        });

        mensalField.addEventListener('input', function () {
            mensalField.value = formatRateInput(mensalField.value);
            if (mensalField.value) {
                anualField.value = '';
            }
        });
        mensalField.addEventListener('blur', function () {
            mensalField.value = formatRateInput(mensalField.value);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var taxaAnual = parseLocaleNumber(anualField.value);
            var taxaMensal = parseLocaleNumber(mensalField.value);

            var informouAnual = isFinite(taxaAnual) && !isNaN(taxaAnual) && anualField.value.trim() !== '';
            var informouMensal = isFinite(taxaMensal) && !isNaN(taxaMensal) && mensalField.value.trim() !== '';

            if (!informouAnual && !informouMensal) {
                results.classList.remove('is-visible');
                setStatus(status, 'Preencha uma das taxas para calcular.', true);
                return;
            }

            button.disabled = true;
            setStatus(status, 'Calculando...', false);

            setTimeout(function() {
                button.disabled = false;
                
                var taxaCalculada = 0;
                var taxaInformada = 0;
                var nomeOriginal = '';
                var nomeEquivalente = '';
                var textoResumo = '';

                // Se informou a anual, calcula a mensal. Se não, é porque informou a mensal, aí calcula a anual.
                if (informouAnual) {
                    taxaInformada = taxaAnual;
                    var taxaDecimal = taxaInformada / 100;
                    taxaCalculada = (Math.pow(1 + taxaDecimal, 1 / 12) - 1) * 100;
                    
                    nomeOriginal = 'Ao ano (a.a.)';
                    nomeEquivalente = 'Ao mês (a.m.)';
                    textoResumo = 'Uma taxa de ' + numberFormatter.format(taxaInformada) + '% ao ano é equivalente a aproximadamente ' + numberFormatter.format(taxaCalculada) + '% ao mês no regime de juros compostos.';
                } else {
                    taxaInformada = taxaMensal;
                    var taxaDecimal = taxaInformada / 100;
                    taxaCalculada = (Math.pow(1 + taxaDecimal, 12) - 1) * 100;
                    
                    nomeOriginal = 'Ao mês (a.m.)';
                    nomeEquivalente = 'Ao ano (a.a.)';
                    textoResumo = 'Uma taxa de ' + numberFormatter.format(taxaInformada) + '% ao mês é equivalente a aproximadamente ' + numberFormatter.format(taxaCalculada) + '% ao ano no regime de juros compostos.';
                }

                resultValue.textContent = numberFormatter.format(taxaCalculada) + '%';
                taxaInformadaCard.textContent = numberFormatter.format(taxaInformada) + '%';
                periodoOriginalCard.textContent = nomeOriginal;
                periodoEquivalenteCard.textContent = nomeEquivalente;
                summary.textContent = textoResumo;
                
                results.classList.add('is-visible');
                setStatus(status, 'Cálculo concluído.', false);
                scrollToResults(results);
            }, 300);
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

    function formatRateInput(value) {
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

        return integerPart ? integerPart + ',' + decimalPart : '';
    }

    boot();
})();
