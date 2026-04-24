(function initAdminShared(global) {
    const mixins = global.LB9AdminMixins = global.LB9AdminMixins || {};

    mixins.shared = {
        async apiSend(url, dados = {}, extra = {}) {
            var payload = dados;

            if (extra.formData instanceof FormData) {
                payload = { formData: extra.formData };
            } else if (extra.file || extra.files) {
                payload = { formData: buildAdminFormData(dados, extra) };
            }

            const response = await send(url, payload);
            return this.normalizeObserverResponse(response);
        },

        normalizeSearch(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        },

        slugify(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-')
                .slice(0, 25);
        },

        normalizeObserverResponse(payload) {
            if (!payload || typeof payload !== 'object') {
                return {
                    sucesso: false,
                    mensagem: 'Resposta inválida do servidor'
                };
            }

            if (!payload.cabecalho || typeof payload.cabecalho !== 'object') {
                return {
                    sucesso: false,
                    mensagem: 'Resposta fora do padrão do observador'
                };
            }

            const cabecalho = payload.cabecalho || {};
            const dados = payload.dados || {};

            return {
                sucesso: cabecalho.status === 'ok',
                mensagem: cabecalho.msg || '',
                ...dados
            };
        }
    };

    function buildAdminFormData(dados, extra) {
        var formData = new FormData();
        var payload = dados || {};

        Object.keys(payload).forEach(function(key) {
            if (payload[key] !== undefined && payload[key] !== null) {
                formData.append(key, String(payload[key]));
            }
        });

        if (extra.file && extra.file.files && extra.file.files[0]) {
            formData.append(extra.fileFieldName || 'arquivo', extra.file.files[0], extra.file.files[0].name);
        }

        if (Array.isArray(extra.files)) {
            extra.files.forEach(function(item) {
                if (!item || !item.file) {
                    return;
                }

                formData.append(item.fieldName || 'arquivo', item.file, item.fileName || item.file.name);
            });
        }

        return formData;
    }
})(window);
