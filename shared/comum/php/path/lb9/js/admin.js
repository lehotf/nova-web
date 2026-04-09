(function registerAdminModule() {
    class AdminApp extends window.BaseModule {
        constructor(root = document) {
            super(root);
            this.apiBase = '/comum/php/path/lb9/php';

            this.optionList = this.root.querySelector('#adminOptionList');
            this.searchInput = this.root.querySelector('#adminSearch');
            this.toggleCacheBtn = this.root.querySelector('#toggleCacheBtn');
            this.toggleCacheLabel = this.root.querySelector('#toggleCacheLabel');
            this.toggleCacheHint = this.root.querySelector('#toggleCacheHint');
            this.commandButtons = Array.from(this.root.querySelectorAll('.admin-command-btn'));
            this.toastContainer = this.root.querySelector('#adminToastContainer');

            this.activeOption = 'geral';
            this.cacheState = false;
            this.commandEndpoints = {
                rebuild_all: `${this.apiBase}/admin_rebuild_all.php`,
                cache_templates: `${this.apiBase}/gerador/cacheTemplates.php`,
                ultimos_links: `${this.apiBase}/ultimos.php`,
                compact_assets: `${this.apiBase}/compactador/compacta.php`,
                sitemap: `${this.apiBase}/sitemap.php`,
                clear_cache: `${this.apiBase}/clear_cache.php`
            };

            this.attachEvents();
            this.initialize();
        }

        attachEvents() {
            this.searchInput?.addEventListener('input', () => {
                this.filterOptions();
            });

            this.optionList?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-option]');
                if (!button) return;
                this.openOption(button.dataset.option || 'geral');
            });

            this.toggleCacheBtn?.addEventListener('click', () => {
                this.toggleCache();
            });

            this.commandButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    this.runCommand(button.dataset.command || '');
                });
            });
        }

        initialize() {
            this.renderOptionState();
            this.loadGeneral();
        }

        filterOptions() {
            const query = String(this.searchInput?.value || '').trim().toLowerCase();
            const buttons = this.optionList?.querySelectorAll('[data-option]') || [];

            buttons.forEach((button) => {
                const visible = button.textContent.toLowerCase().includes(query);
                button.classList.toggle('hidden', !visible);
            });
        }

        openOption(optionId) {
            this.activeOption = optionId || 'geral';
            this.renderOptionState();

            if (this.activeOption === 'geral') {
                this.loadGeneral();
            }
        }

        renderOptionState() {
            const buttons = this.optionList?.querySelectorAll('[data-option]') || [];
            buttons.forEach((button) => {
                button.classList.toggle('active', button.dataset.option === this.activeOption);
            });
        }

        loadGeneral() {
            send({
                url: `${this.apiBase}/admin_cache_status.php`,
                method: 'POST',
                dados: {},
                r: this,
                f: function(payload) {
                    if (!payload?.sucesso) {
                        this.showToast(payload?.mensagem || 'Erro ao carregar estado do cache', 'error');
                        return;
                    }

                    this.cacheState = !!payload.cache?.active;
                    this.renderCacheState(payload.cache?.source || 'config');
                }
            });
        }

        toggleCache() {
            if (!this.toggleCacheBtn) return;

            this.toggleCacheBtn.disabled = true;

            send({
                url: `${this.apiBase}/admin_toggle_cache.php`,
                method: 'POST',
                dados: {},
                r: this,
                f: function(payload) {
                    this.toggleCacheBtn.disabled = false;

                    if (!payload?.sucesso) {
                        this.showToast(payload?.mensagem || 'Erro ao alternar cache', 'error');
                        return;
                    }

                    this.cacheState = !!payload.cache?.active;
                    this.renderCacheState(payload.cache?.source || 'config');
                    this.showToast(payload?.mensagem || 'Estado do cache alterado.', 'success');
                }
            });
        }

        runCommand(commandId) {
            if (!commandId || !this.commandEndpoints[commandId]) {
                this.showToast('Comando administrativo inválido.', 'error');
                return;
            }

            if (commandId !== 'cache_templates' && commandId !== 'compact_assets' && commandId !== 'ultimos_links' && commandId !== 'sitemap' && commandId !== 'clear_cache') {
                const button = this.commandButtons.find((item) => item.dataset.command === commandId);
                if (!button) return;

                button.disabled = true;

                window.setTimeout(() => {
                    button.disabled = false;
                    this.showToast(`Ação preparada para ${this.getCommandLabel(commandId)}. Backend pendente.`, 'info');
                }, 180);
                return;
            }

            const button = this.commandButtons.find((item) => item.dataset.command === commandId);
            if (!button) return;

            button.disabled = true;

            send({
                url: this.commandEndpoints[commandId],
                method: 'POST',
                dados: {},
                r: this,
                f: function(payload) {
                    button.disabled = false;
                    const status = payload?.cabecalho?.status || '';
                    const message = payload?.cabecalho?.msg || `${this.getCommandLabel(commandId)} executado.`;

                    if (status !== 'ok') {
                        this.showToast(message || `Erro ao executar ${this.getCommandLabel(commandId)}.`, 'error');
                        return;
                    }

                    this.showToast(message, 'success');
                }
            });
        }

        getCommandLabel(commandId) {
            const labels = {
                rebuild_all: 'Reconstruir TUDO',
                cache_templates: 'Cache Templates',
                ultimos_links: 'Últimos Links',
                compact_assets: 'Compactar JS/CSS',
                sitemap: 'Sitemap',
                clear_cache: 'Limpar Cache'
            };

            return labels[commandId] || commandId;
        }

        renderCacheState(sourceLabel) {
            const activeText = this.cacheState ? 'Cache ativo' : 'Cache desativado';
            const actionText = this.cacheState ? 'Desativar cache' : 'Ativar cache';

            if (this.toggleCacheLabel) {
                this.toggleCacheLabel.textContent = activeText;
            }

            if (this.toggleCacheBtn) {
                this.toggleCacheBtn.textContent = actionText;
                this.toggleCacheBtn.classList.toggle('is-active', this.cacheState);
            }

            if (this.toggleCacheHint) {
                this.toggleCacheHint.textContent = `Estado atual lido diretamente do arquivo de configuração (${sourceLabel}).`;
            }
        }
    }

    window.AppModules.register('AdminApp', AdminApp);
})();
