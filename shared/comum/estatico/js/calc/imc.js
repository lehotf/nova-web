(function () {
    var WIDGET_ID = 'calc-imc-widget';
    var RESULT_ID = 'calc-imc-resultado';
    var ENDPOINT_ACTION = 'comum/calc/imc';

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
            '      <span class="calc-rf__label">Peso (kg)</span>' +
            '      <input class="calc-rf__input" type="text" name="peso" inputmode="decimal" placeholder="Ex.: 75,50" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Altura (m)</span>' +
            '      <input class="calc-rf__input" type="text" name="altura" inputmode="decimal" placeholder="Ex.: 1,75" />' +
            '    </label>' +
            '  </div>' +
            '  <div class="calc-rf__actions">' +
            '    <button class="calc-rf__button" type="submit">Calcular IMC</button>' +
            '    <div class="calc-rf__status" aria-live="polite"></div>' +
            '  </div>' +
            '</form>' +
            '<div class="calc-rf__results" id="' + RESULT_ID + '" aria-live="polite">' +
            '  <div class="calc-rf__result-main">Seu IMC é <span class="calc-rf__result-value">-</span></div>' +
            '  <div class="calc-rf__cards">' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Classificação</span><span class="calc-rf__card-value" data-role="classificacao">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Peso informado</span><span class="calc-rf__card-value" data-role="peso">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Altura informada</span><span class="calc-rf__card-value" data-role="altura">-</span></div>' +
            '  </div>' +
            '  <div class="calc-rf__footnote" data-role="resumo"></div>' +
            '  <div class="calc-rf__footnote" style="font-size: 15px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px; border: 1px solid rgba(216,159,233,0.1); color: #f3f3f3;">' +
            '    <b style="color: #d89fe9; display: block; margin-bottom: 5px; font-size: 17px;">Aviso Importante:</b>' +
            '    Este cálculo é apenas uma estimativa a título de curiosidade e não leva em conta fatores fundamentais como massa muscular, densidade óssea ou histórico clínico. ' +
            '    <span style="color: #fff; font-weight: 700;">Todos os assuntos relacionados à saúde devem ser tratados com especialistas e profissionais da área médica.</span>' +
            '  </div>' +
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
        var classValue = wrapper.querySelector('[data-role="classificacao"]');
        var pesoValue = wrapper.querySelector('[data-role="peso"]');
        var alturaValue = wrapper.querySelector('[data-role="altura"]');
        var summary = wrapper.querySelector('[data-role="resumo"]');

        var decimalFields = [form.elements.peso, form.elements.altura];
        decimalFields.forEach(function (field) {
            field.addEventListener('input', function () {
                field.value = formatCurrencyInput(field.value);
            });
            field.addEventListener('blur', function () {
                field.value = formatCurrencyInput(field.value);
            });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var peso = onlyDigits(form.elements.peso.value);
            var altura = onlyDigits(form.elements.altura.value);

            if (!peso || peso <= 0 || !altura || altura <= 0) {
                results.classList.remove('is-visible');
                setStatus(status, 'Informe peso e altura válidos.', true);
                return;
            }

            button.disabled = true;
            setStatus(status, 'Calculando...', false);

            window.send({
                a: ENDPOINT_ACTION,
                dados: { peso: parseInt(peso, 10), altura: parseInt(altura, 10) },
                f: function (response) {
                    button.disabled = false;

                    if (!response || !response.cabecalho || response.cabecalho.status !== 'ok' || !response.dados || !response.dados.resultado) {
                        setStatus(status, extractMessage(response) || 'Erro ao calcular.', true);
                        results.classList.remove('is-visible');
                        return;
                    }

                    var res = response.dados.resultado;
                    
                    resultValue.textContent = res.imc.toLocaleString('pt-BR');
                    classValue.textContent = res.classificacao;
                    pesoValue.textContent = res.peso.toLocaleString('pt-BR') + ' kg';
                    alturaValue.textContent = res.altura.toLocaleString('pt-BR') + ' m';
                    
                    summary.textContent = 'Com ' + res.peso.toLocaleString('pt-BR') + 'kg e ' + res.altura.toLocaleString('pt-BR') + 'm, seu IMC indica: ' + res.classificacao + '.';
                        
                    results.classList.add('is-visible');
                    setStatus(status, response.cabecalho.msg || 'Cálculo concluído.', false);
                    scrollToResults(results);
                }
            });
        });
    }

    function scrollToResults(results) {
        if (!results) return;
        window.requestAnimationFrame(function () {
            results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    function focusFirstField(wrapper) {
        var form = wrapper && wrapper.querySelector('.calc-rf__form');
        var firstField;
        if (!form || !form.elements || !form.elements.length) return;
        firstField = Array.prototype.find.call(form.elements, function (field) {
            return field && typeof field.focus === 'function' && !field.disabled && field.type !== 'hidden';
        });
        if (firstField) window.requestAnimationFrame(function () { firstField.focus(); });
    }

    function setStatus(node, message, isError) {
        node.textContent = message || '';
        node.classList.toggle('calc-rf__status--error', !!isError);
    }

    function extractMessage(response) {
        return (response && response.cabecalho && response.cabecalho.msg) || '';
    }

    function onlyDigits(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    function formatCurrencyInput(value) {
        var digits = onlyDigits(value);
        if (!digits) return '';
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
