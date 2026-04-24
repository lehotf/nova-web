(function () {
    var WIDGET_ID = 'calc-diferenca-datas-widget';
    var RESULT_ID = 'calc-diferenca-datas-resultado';
    var ENDPOINT_ACTION = 'comum/calc/diferenca_datas';

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
            '      <span class="calc-rf__label">Data inicial</span>' +
            '      <input class="calc-rf__input" type="text" name="data_inicial" placeholder="Ex.: 01/01/2024" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Data final</span>' +
            '      <input class="calc-rf__input" type="text" name="data_final" placeholder="Ex.: 31/12/2024" />' +
            '    </label>' +
            '  </div>' +
            '  <div class="calc-rf__actions">' +
            '    <button class="calc-rf__button" type="submit">Calcular Diferença</button>' +
            '    <div class="calc-rf__status" aria-live="polite"></div>' +
            '  </div>' +
            '</form>' +
            '<div class="calc-rf__results" id="' + RESULT_ID + '" aria-live="polite">' +
            '  <div class="calc-rf__result-main">Diferença entre as datas <span class="calc-rf__result-value">-</span></div>' +
            '  <div class="calc-rf__footnote" data-role="resumo"></div>' +
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
        var summary = wrapper.querySelector('[data-role="resumo"]');

        var dateFields = [form.elements.data_inicial, form.elements.data_final];
        dateFields.forEach(function (field) {
            field.addEventListener('input', function () {
                field.value = formatDateInput(field.value);
            });
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            var d1 = parseDate(form.elements.data_inicial.value);
            var d2 = parseDate(form.elements.data_final.value);

            if (!d1 || !d2) {
                results.classList.remove('is-visible');
                setStatus(status, 'Informe as datas no formato DD/MM/AAAA.', true);
                return;
            }

            button.disabled = true;
            setStatus(status, 'Calculando...', false);

            try {
                var response = await window.send(buildEndpointUrl(ENDPOINT_ACTION), {
                    data_inicial: d1,
                    data_final: d2
                });

                if (!response || !response.cabecalho || response.cabecalho.status !== 'ok' || !response.dados || !response.dados.resultado) {
                    setStatus(status, extractMessage(response) || 'Não foi possível calcular a diferença.', true);
                    results.classList.remove('is-visible');
                    return;
                }

                var result = response.dados.resultado;
                var totalDias = Math.abs(result.total_dias);

                resultValue.textContent = totalDias.toLocaleString('pt-BR') + (totalDias === 1 ? ' dia' : ' dias');
                summary.textContent = 'A diferença total entre ' + form.elements.data_inicial.value + ' e ' + form.elements.data_final.value + ' é de ' + totalDias.toLocaleString('pt-BR') + (totalDias === 1 ? ' dia.' : ' dias.');

                results.classList.add('is-visible');
                setStatus(status, response.cabecalho.msg || 'Cálculo concluído com sucesso.', false);
                scrollToResults(results);
            } catch (error) {
                setStatus(status, error.message || 'Não foi possível calcular a diferença.', true);
                results.classList.remove('is-visible');
            } finally {
                button.disabled = false;
            }
        });
    }

    function buildEndpointUrl(action) {
        return '/comum/php/xhr/' + String(action || '').replace(/^comum\//, '') + '.php';
    }

    function formatDateInput(value) {
        var digits = value.replace(/\D/g, '').slice(0, 8);
        if (digits.length >= 5) {
            return digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
        } else if (digits.length >= 3) {
            return digits.slice(0, 2) + '/' + digits.slice(2);
        }
        return digits;
    }

    function parseDate(value) {
        var parts = value.split('/');
        if (parts.length !== 3) return null;
        var day = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10);
        var year = parseInt(parts[2], 10);
        if (isNaN(day) || isNaN(month) || isNaN(year)) return null;
        return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
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

    boot();
})();
