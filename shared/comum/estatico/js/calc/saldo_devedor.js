(function () {
    var CALCULATOR_VERSION = '1.0.10';
    var WIDGET_ID = 'calc-saldo-devedor-widget';
    var RESULT_ID = 'calc-saldo-devedor-resultado';
    var ENDPOINT_URL = '/comum/php/xhr/calc/saldo_devedor.php';
    var MAX_VALOR_SOLICITADO = 2000000;
    var MAX_PRAZO = 150;
    var currencyFormatter = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
    var rateFormatter = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    window.__SALDO_DEVEDOR_VERSION__ = CALCULATOR_VERSION;

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
            '      <span class="calc-rf__label">Data de contratação</span>' +
            '      <input class="calc-rf__input" type="text" name="data_contratacao" placeholder="Ex.: 01/01/2025" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Data da 1ª parcela</span>' +
            '      <input class="calc-rf__input" type="text" name="data_primeira_parcela" placeholder="Ex.: 01/03/2025" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Valor solicitado</span>' +
            '      <input class="calc-rf__input" type="text" name="valor_liberado" inputmode="decimal" placeholder="Ex.: 50.000,00" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Valor da parcela</span>' +
            '      <input class="calc-rf__input" type="text" name="valor_parcela" inputmode="decimal" placeholder="Ex.: 1.250,00" />' +
            '    </label>' +
            '    <label class="calc-rf__field">' +
            '      <span class="calc-rf__label">Prazo (número de parcelas)</span>' +
            '      <input class="calc-rf__input" type="number" name="prazo" min="1" max="150" step="1" placeholder="Ex.: 48" />' +
            '    </label>' +
            '  </div>' +
            '  <p class="calc-rf__disclaimer" style="margin: 8px 0 0; font-size: 13px; line-height: 1.5; opacity: 0.85;">Simulação educacional de <strong>saldo devedor de empréstimo</strong>. Use datas no formato <strong>DD/MM/AAAA</strong>. O IOF é calculado sobre o valor solicitado e a taxa contratual é estimada pelas parcelas.</p>' +
            '  <div class="calc-rf__actions">' +
            '    <button class="calc-rf__button" type="submit">Calcular Saldo Devedor</button>' +
            '    <div class="calc-rf__status" aria-live="polite"></div>' +
            '  </div>' +
            '</form>' +
            '<div class="calc-rf__results" id="' + RESULT_ID + '" aria-live="polite">' +
            '  <div class="calc-rf__result-main">Saldo devedor atual <span class="calc-rf__result-value">-</span></div>' +
            '  <div class="calc-rf__cards">' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Juros de carência</span><span class="calc-rf__card-value" data-role="juros_carencia">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Taxa de juros</span><span class="calc-rf__card-value" data-role="taxa_juros">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">IOF total</span><span class="calc-rf__card-value" data-role="iof_total">-</span></div>' +
            '    <div class="calc-rf__card"><span class="calc-rf__card-label">Parcelas restantes</span><span class="calc-rf__card-value" data-role="parcelas_restantes">-</span></div>' +
            '  </div>' +
            '  <div class="calc-rf__footnote" data-role="resumo"></div>' +
            '  <div class="calc-rf__footnote" data-role="detalhe"></div>' +
            '  <div class="calc-rf__report" data-role="relatorio" style="margin-top: 20px; font-size: 16px; line-height: 1.7;"></div>' +
            '</div>' +
            '<div class="calc-rf__version" style="margin-top: 10px; font-size: 12px; line-height: 1.4; opacity: 0.65;">Versão da calculadora: ' + CALCULATOR_VERSION + '</div>';

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
        var taxaJurosValue = wrapper.querySelector('[data-role="taxa_juros"]');
        var iofTotalValue = wrapper.querySelector('[data-role="iof_total"]');
        var jurosCarenciaValue = wrapper.querySelector('[data-role="juros_carencia"]');
        var parcelasRestantesValue = wrapper.querySelector('[data-role="parcelas_restantes"]');
        var summary = wrapper.querySelector('[data-role="resumo"]');
        var detail = wrapper.querySelector('[data-role="detalhe"]');
        var reportContainer = wrapper.querySelector('[data-role="relatorio"]');

        var currencyFields = [
            form.elements.valor_liberado,
            form.elements.valor_parcela
        ];
        var dateFields = [
            form.elements.data_contratacao,
            form.elements.data_primeira_parcela
        ];

        currencyFields.forEach(function (field) {
            field.addEventListener('input', function () {
                field.value = formatCurrencyInput(field.value);
            });
            field.addEventListener('blur', function () {
                field.value = formatCurrencyInput(field.value);
            });
        });

        dateFields.forEach(function (field) {
            field.addEventListener('input', function () {
                field.value = formatDateInput(field.value);
            });
        });

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

                renderResult(response.data.resultado, {
                    results: results,
                    resultValue: resultValue,
                    taxaJurosValue: taxaJurosValue,
                    iofTotalValue: iofTotalValue,
                    jurosCarenciaValue: jurosCarenciaValue,
                    parcelasRestantesValue: parcelasRestantesValue,
                    summary: summary,
                    detail: detail,
                    reportContainer: reportContainer,
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
        var taxaContratualMensal = result.taxa_contratual_mensal || 0;
        var iofTotal = result.iof_total || 0;
        var jurosCarencia = result.juros_carencia || 0;
        var saldoDevedor = result.saldo_devedor_atual || 0;
        var parcelasRestantes = result.parcelas_restantes || 0;
        var prazo = result.prazo || 0;
        var diasCarencia = result.dias_carencia || 0;
        var dataBaseCarencia = result.data_base_carencia_br || result.data_base_carencia || '-';

        nodes.resultValue.textContent = currencyFormatter.format(saldoDevedor);
        nodes.jurosCarenciaValue.textContent = currencyFormatter.format(jurosCarencia);
        if (nodes.taxaJurosValue) {
            nodes.taxaJurosValue.textContent = formatRate(taxaContratualMensal) + ' ao mês';
        }
        nodes.iofTotalValue.textContent = currencyFormatter.format(iofTotal);
        nodes.parcelasRestantesValue.textContent = Math.round(parcelasRestantes).toLocaleString('pt-BR');

        nodes.summary.textContent = 'Na data da simulação ' + (result.data_simulacao_br || result.data_referencia_br || '-') + ', restam ' +
            Math.round(parcelasRestantes).toLocaleString('pt-BR') + ' de ' + prazo.toLocaleString('pt-BR') +
            ' parcelas na simulação.';

        nodes.detail.textContent = 'Taxa contratual estimada: ' + formatRate(taxaContratualMensal) +
            ' ao mês. Juros de carência: ' + currencyFormatter.format(jurosCarencia) +
            '. Base de carência: ' + dataBaseCarencia + '. Dias de carência: ' + diasCarencia.toLocaleString('pt-BR') +
            (diasCarencia === 1 ? ' dia.' : ' dias.') +
            ' IOF considerado na simulação: ' +
            formatRate(result.iof_aliquota_fixa_percentual || 0) + ' fixa + ' +
            formatRate(result.iof_aliquota_diaria_percentual || 0) + ' ao dia.';

        if (nodes.reportContainer) {
            nodes.reportContainer.innerHTML = buildReportHtml(result, {
                taxaContratualMensal: taxaContratualMensal,
                jurosCarencia: jurosCarencia,
                iofTotal: iofTotal,
                saldoDevedor: saldoDevedor,
                parcelasRestantes: parcelasRestantes
            });
        }

        nodes.results.classList.add('is-visible');
        setStatus(nodes.status, nodes.response.message || 'Cálculo concluído.', false);
        scrollToResults(nodes.results);
    }

    function buildReportHtml(result, metrics) {
        var html = '<div class="calc-rf__report-content">' +
            '<h3 style="margin: 0 0 14px; font-size: 22px; line-height: 1.2;">Explicação detalhada</h3>' +
            '<p style="margin: 0 0 14px;">Esta calculadora estima o saldo devedor atual de um empréstimo a partir do valor solicitado, do valor da parcela, do prazo e da data da primeira parcela. O IOF e a taxa contratual são resolvidos em conjunto, considerando o total financiado.</p>' +
            '<p style="margin: 0 0 14px;">No cenário de contratação, o sistema também calcula o <strong>juros de carência</strong>, quando a primeira parcela foi postergada além do primeiro vencimento normal. O saldo devedor evolui mensalmente pela tabela Price, com arredondamento de juros em cada prestação.</p>' +
            '<p style="margin: 0 0 14px;">A taxa efetiva considera o IOF como custo adicional da operação. O total financiado corresponde ao valor solicitado mais o IOF, e esse total afeta o saldo final da simulação.</p>' +
            '<p style="margin: 0 0 14px;">Na data da simulação, o sistema estima um saldo devedor de <strong>' + currencyFormatter.format(metrics.saldoDevedor || 0) + '</strong>, com <strong>' + Math.round(metrics.parcelasRestantes || 0).toLocaleString('pt-BR') + '</strong> parcelas restantes.</p>' +
            '<p style="margin: 0 0 14px;">Os principais indicadores da simulação são: taxa de juros de <strong>' + rateFormatter.format(metrics.taxaContratualMensal || 0) + '% ao mês</strong> e IOF total de <strong>' + currencyFormatter.format(metrics.iofTotal || 0) + '</strong>.</p>';

        html += '<p style="margin: 18px 0 0; padding: 15px; border-radius: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); font-size: 15px;">' +
            '<strong>Atenção:</strong> esta calculadora é uma estimativa educacional e não substitui a leitura do contrato, do demonstrativo financeiro ou a conferência com a instituição credora.' +
            '</p></div>';

        return html;
    }

    function buildPayload(form) {
        var dataContratacao = parseDate(form.elements.data_contratacao.value);
        var dataPrimeiraParcela = parseDate(form.elements.data_primeira_parcela.value);
        var valorLiberado = parseLocaleNumber(form.elements.valor_liberado.value);
        var valorParcela = parseLocaleNumber(form.elements.valor_parcela.value);
        var prazo = parseInt(form.elements.prazo.value, 10);

        if (!dataContratacao || !dataPrimeiraParcela) {
            return null;
        }

        if (!isFinite(valorLiberado) || valorLiberado <= 0 || valorLiberado > MAX_VALOR_SOLICITADO) {
            return null;
        }

        if (!isFinite(valorParcela) || valorParcela <= 0) {
            return null;
        }

        if (!isFinite(prazo) || prazo < 1 || prazo > MAX_PRAZO) {
            return null;
        }

        return {
            data_contratacao: dataContratacao,
            data_primeira_parcela: dataPrimeiraParcela,
            valor_liberado: valorLiberado,
            valor_parcela: valorParcela,
            prazo: prazo
        };
    }

    function parseDate(value) {
        var parts = String(value || '').split('/');
        var day;
        var month;
        var year;

        if (parts.length !== 3) {
            return '';
        }

        day = parseInt(parts[0], 10);
        month = parseInt(parts[1], 10);
        year = parseInt(parts[2], 10);

        if (!isFinite(day) || !isFinite(month) || !isFinite(year)) {
            return '';
        }

        return String(day).padStart(2, '0') + '/' + String(month).padStart(2, '0') + '/' + String(year).padStart(4, '0');
    }

    function formatDateInput(value) {
        var digits = String(value || '').replace(/\D/g, '').slice(0, 8);

        if (digits.length >= 5) {
            return digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
        }

        if (digits.length >= 3) {
            return digits.slice(0, 2) + '/' + digits.slice(2);
        }

        return digits;
    }

    function parseLocaleNumber(value) {
        var normalized = String(value || '')
            .replace(/\s/g, '')
            .replace(/\./g, '')
            .replace(',', '.')
            .replace(/[^0-9.-]/g, '');

        if (normalized === '' || normalized === '-' || normalized === '.' || normalized === '-.') {
            return NaN;
        }

        return parseFloat(normalized);
    }

    function formatCurrencyInput(value) {
        var digits = String(value || '').replace(/\D/g, '');
        var integerPart;
        var decimalPart;
        var number;

        if (!digits) {
            return '';
        }

        if (digits.length === 1) {
            digits = '0' + digits;
        }

        integerPart = digits.slice(0, -2) || '0';
        decimalPart = digits.slice(-2);
        number = parseFloat(integerPart + '.' + decimalPart);

        if (!isFinite(number)) {
            return '';
        }

        return number.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatRate(value) {
        return rateFormatter.format(value || 0) + '%';
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

    boot();
})();
