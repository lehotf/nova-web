(function initAppModules(global) {
    class BaseModule {
        constructor(root = document) {
            this.root = root;
            this.deleteConfirmResolver = null;
            this.toastDurationMs = 2800;
            this.toastRemoveDelayMs = 220;
        }

        sanitizeFilename(text, maxLength = 120) {
            return global.AppUtils.sanitizeFilename(text, maxLength);
        }

        escapeHtml(text) {
            return global.AppUtils.escapeHtml(text || '');
        }

        getToastIcon(type) {
            return global.AppUtils.getToastIcon(type);
        }

        showToast(message, type = 'info') {
            global.AppUtils.showToast({
                toastContainer: this.toastContainer,
                message,
                type,
                durationMs: this.toastDurationMs,
                removeDelayMs: this.toastRemoveDelayMs
            });
        }

        showDeleteConfirm(title, message) {
            global.AppUtils.showDeleteConfirm({
                modal: this.deleteConfirmModal,
                titleElement: this.deleteConfirmTitle,
                messageElement: this.deleteConfirmMessage,
                actionElement: this.deleteConfirmAction,
                title,
                message,
                onEscape: () => this.closeDeleteConfirm(false)
            });

            return new Promise((resolve) => {
                this.deleteConfirmResolver = resolve;
            });
        }

        closeDeleteConfirm(result) {
            global.AppUtils.closeDeleteConfirm(this.deleteConfirmModal);
            const resolver = this.deleteConfirmResolver;
            this.deleteConfirmResolver = null;
            if (resolver) resolver(result);
        }
    }

    const registry = new Map();

    function register(name, constructorFn) {
        if (!name || typeof constructorFn !== 'function') return;
        registry.set(String(name), constructorFn);
    }

    function get(name) {
        return registry.get(String(name)) || null;
    }

    function has(name) {
        return registry.has(String(name));
    }

    global.AppModules = {
        get,
        has,
        register
    };
    global.BaseModule = BaseModule;
})(window);
