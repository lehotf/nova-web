(function initAdminGeneral(global) {
    const mixins = global.LB9AdminMixins = global.LB9AdminMixins || {};

    mixins.general = {
        async loadGeneral() {
            try {
                const payload = await send(`${this.apiBase}/admin_cache_status.php`, {});
                const status = payload?.cabecalho?.status || '';
                const message = payload?.cabecalho?.msg || 'Erro ao carregar estado do cache';
                const cache = payload?.dados?.cache;

                if (status !== 'ok' || !cache) {
                    this.showToast(message, 'error');
                    return;
                }

                this.cacheState = !!cache.active;
                this.renderCacheState(cache.source || 'config');
            } catch (error) {
                this.showToast(error.message || 'Erro ao carregar estado do cache', 'error');
            }
        },

        async toggleCache() {
            if (!this.toggleCacheBtn) return;

            this.toggleCacheBtn.disabled = true;

            try {
                const payload = await send(`${this.apiBase}/admin_toggle_cache.php`, {});
                const status = payload?.cabecalho?.status || '';
                const message = payload?.cabecalho?.msg || 'Erro ao alternar cache';
                const cache = payload?.dados?.cache;

                if (status !== 'ok' || !cache) {
                    this.showToast(message, 'error');
                    return;
                }

                this.cacheState = !!cache.active;
                this.renderCacheState(cache.source || 'config');
                this.showToast(message || 'Estado do cache alterado.', 'success');
            } catch (error) {
                this.showToast(error.message || 'Erro ao alternar cache', 'error');
            } finally {
                this.toggleCacheBtn.disabled = false;
            }
        },

        async runCommand(commandId) {
            if (!commandId || !this.commandEndpoints[commandId]) {
                this.showToast('Comando administrativo inválido.', 'error');
                return;
            }

            if (commandId === 'rebuild_all') {
                this.runRebuildAll();
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

            try {
                const payload = await send(this.commandEndpoints[commandId], {});
                const status = payload?.cabecalho?.status || '';
                const message = payload?.cabecalho?.msg || `${this.getCommandLabel(commandId)} executado.`;

                if (status !== 'ok') {
                    this.showToast(message || `Erro ao executar ${this.getCommandLabel(commandId)}.`, 'error');
                    return;
                }

                this.showToast(message, 'success');
            } catch (error) {
                this.showToast(error.message || `Erro ao executar ${this.getCommandLabel(commandId)}.`, 'error');
            } finally {
                button.disabled = false;
            }
        },

        runRebuildAll() {
            const rebuildButton = this.commandButtons.find((item) => item.dataset.command === 'rebuild_all');
            if (!rebuildButton) return;

            const steps = ['compact_assets', 'cache_templates'];
            const originalLabel = rebuildButton.textContent;

            rebuildButton.disabled = true;
            this.setCommandButtonsDisabled(steps, true);

            const runStep = (index) => {
                if (index >= steps.length) {
                    rebuildButton.disabled = false;
                    this.setCommandButtonsDisabled(steps, false);
                    rebuildButton.textContent = originalLabel;
                    this.showToast('Reconstrução concluída com sucesso.', 'success');
                    return;
                }

                const commandId = steps[index];
                rebuildButton.textContent = `Executando: ${this.getCommandLabel(commandId)}`;

                this.executeCommandRequest(commandId).then((payload) => {
                    const status = payload?.cabecalho?.status || '';
                    const message = payload?.cabecalho?.msg || `${this.getCommandLabel(commandId)} executado.`;

                    if (status !== 'ok') {
                        rebuildButton.disabled = false;
                        this.setCommandButtonsDisabled(steps, false);
                        rebuildButton.textContent = originalLabel;
                        this.showToast(message || `Erro ao executar ${this.getCommandLabel(commandId)}.`, 'error');
                        return;
                    }

                    runStep(index + 1);
                }).catch((error) => {
                    rebuildButton.disabled = false;
                    this.setCommandButtonsDisabled(steps, false);
                    rebuildButton.textContent = originalLabel;
                    this.showToast(error.message || `Erro ao executar ${this.getCommandLabel(commandId)}.`, 'error');
                });
            };

            runStep(0);
        },

        async executeCommandRequest(commandId) {
            return await send(this.commandEndpoints[commandId], {});
        },

        setCommandButtonsDisabled(commandIds, disabled) {
            commandIds.forEach((commandId) => {
                const button = this.commandButtons.find((item) => item.dataset.command === commandId);
                if (button) {
                    button.disabled = disabled;
                }
            });
        },

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
        },

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
    };
})(window);
