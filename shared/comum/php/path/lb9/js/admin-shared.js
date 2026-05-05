(function initAdminShared(global) {
    const mixins = global.LB9AdminMixins = global.LB9AdminMixins || {};

    mixins.shared = {
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

        buildSendPayload(dados = {}, extra = {}) {
            if (extra.formData instanceof FormData) {
                return extra.formData;
            }

            if (extra.file || extra.files) {
                return buildAdminFormData(dados, extra);
            }

            return dados;
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
