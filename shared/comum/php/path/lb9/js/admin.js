(function registerAdminModule() {
    class AdminApp extends window.BaseModule {
        constructor(root = document) {
            super(root);
            this.apiBase = '/comum/php/path/lb9/php';

            this.optionList = this.root.querySelector('#adminOptionList');
            this.searchInput = this.root.querySelector('#adminSearch');
            this.panelGeral = this.root.querySelector('#adminPanelGeral');
            this.panelTags = this.root.querySelector('#adminPanelTags');
            this.panelAcessos = this.root.querySelector('#adminPanelAcessos');
            this.toggleCacheBtn = this.root.querySelector('#toggleCacheBtn');
            this.toggleCacheLabel = this.root.querySelector('#toggleCacheLabel');
            this.toggleCacheHint = this.root.querySelector('#toggleCacheHint');
            this.commandButtons = Array.from(this.root.querySelectorAll('.admin-command-btn'));
            this.tagsList = this.root.querySelector('#adminTagsList');
            this.tagSearchInput = this.root.querySelector('#adminTagSearch');
            this.tagForm = this.root.querySelector('#adminTagForm');
            this.tagIdInput = this.root.querySelector('#adminTagId');
            this.tagNomeInput = this.root.querySelector('#adminTagNome');
            this.tagDestaqueInput = this.root.querySelector('#adminTagDestaque');
            this.tagMeta = this.root.querySelector('#adminTagMeta');
            this.tagDeleteBtn = this.root.querySelector('#adminTagDeleteBtn');
            this.tagDeleteModal = this.root.querySelector('#adminTagDeleteModal');
            this.tagDeleteTitle = this.root.querySelector('#adminTagDeleteTitle');
            this.tagDeleteMessage = this.root.querySelector('#adminTagDeleteMessage');
            this.tagDeleteCancel = this.root.querySelector('#adminTagDeleteCancel');
            this.tagDeleteConfirm = this.root.querySelector('#adminTagDeleteConfirm');
            this.accessList = this.root.querySelector('#adminAccessList');
            this.accessSearchInput = this.root.querySelector('#adminAccessSearch');
            this.accessTypeInput = this.root.querySelector('#adminAccessType');
            this.accessRefreshBtn = this.root.querySelector('#adminAccessRefresh');
            this.accessClearBtn = this.root.querySelector('#adminAccessClear');
            this.accessSummary = this.root.querySelector('#adminAccessSummary');
            this.accessClearModal = this.root.querySelector('#adminAccessClearModal');
            this.accessClearTitle = this.root.querySelector('#adminAccessClearTitle');
            this.accessClearMessage = this.root.querySelector('#adminAccessClearMessage');
            this.accessClearCancel = this.root.querySelector('#adminAccessClearCancel');
            this.accessClearConfirm = this.root.querySelector('#adminAccessClearConfirm');
            this.toastContainer = this.root.querySelector('#adminToastContainer');

            this.activeOption = 'geral';
            this.cacheState = false;
            this.tags = [];
            this.filteredTags = [];
            this.accessLogs = [];
            this.filteredAccessLogs = [];
            this.activeTagId = null;
            this.pendingDeleteTagId = null;
            this.pendingClearAccessLogs = false;
            this.commandEndpoints = {
                rebuild_all: `${this.apiBase}/admin_rebuild_all.php`,
                cache_templates: `${this.apiBase}/gerador/cacheTemplates.php`,
                ultimos_links: `${this.apiBase}/ultimos.php`,
                compact_assets: `${this.apiBase}/compactador/compacta.php`,
                sitemap: `${this.apiBase}/sitemap.php`,
                clear_cache: `${this.apiBase}/clear_cache.php`
            };
            this.tagsEndpoint = `${this.apiBase}/admin_tags.php`;
            this.accessEndpoint = `${this.apiBase}/admin_acessos.php`;
            this.clearAccessEndpoint = `${this.apiBase}/admin_clear_acessos.php`;

            this.attachEvents();
            this.initialize();
        }

        attachEvents() {
            this.searchInput?.addEventListener('input', () => {
                this.filterOptions();
            });
            this.tagSearchInput?.addEventListener('input', () => {
                this.filterTags();
            });
            this.tagSearchInput?.addEventListener('keydown', (event) => {
                this.handleTagSearchKeydown(event);
            });
            this.accessSearchInput?.addEventListener('input', () => {
                this.filterAccessLogs();
            });
            this.accessTypeInput?.addEventListener('change', () => {
                this.filterAccessLogs();
            });
            this.accessRefreshBtn?.addEventListener('click', () => {
                this.loadAccessLogs();
            });
            this.accessClearBtn?.addEventListener('click', () => {
                this.openClearAccessLogsConfirm();
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

            this.tagsList?.addEventListener('click', (event) => {
                const row = event.target.closest('[data-tag-id]');
                if (!row) return;
                this.openTag(Number(row.dataset.tagId));
            });

            this.tagForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                this.saveTag();
            });

            this.tagDeleteBtn?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                this.openDeleteTagConfirm();
            });

            const deleteOverlay = this.tagDeleteModal?.querySelector('.modal-overlay');
            const clearAccessOverlay = this.accessClearModal?.querySelector('.modal-overlay');
            window.AppUtils.bindModalListeners([
                { element: this.tagDeleteCancel, event: 'click', handler: (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    this.closeDeleteTagConfirm(false);
                } },
                { element: this.tagDeleteConfirm, event: 'click', handler: (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    this.closeDeleteTagConfirm(true);
                } },
                { element: deleteOverlay, event: 'click', handler: () => this.closeDeleteTagConfirm(false) },
                { element: this.accessClearCancel, event: 'click', handler: (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    this.closeClearAccessLogsConfirm(false);
                } },
                { element: this.accessClearConfirm, event: 'click', handler: (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    this.closeClearAccessLogsConfirm(true);
                } },
                { element: clearAccessOverlay, event: 'click', handler: () => this.closeClearAccessLogsConfirm(false) }
            ]);

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
                return;
            }

            if (this.activeOption === 'tags') {
                this.loadTags();
                return;
            }

            if (this.activeOption === 'acessos') {
                this.loadAccessLogs();
            }
        }

        renderOptionState() {
            const buttons = this.optionList?.querySelectorAll('[data-option]') || [];
            buttons.forEach((button) => {
                button.classList.toggle('active', button.dataset.option === this.activeOption);
            });

            this.panelGeral?.classList.toggle('hidden', this.activeOption !== 'geral');
            this.panelTags?.classList.toggle('hidden', this.activeOption !== 'tags');
            this.panelAcessos?.classList.toggle('hidden', this.activeOption !== 'acessos');
        }

        loadGeneral() {
            send({
                url: `${this.apiBase}/admin_cache_status.php`,
                method: 'POST',
                dados: {},
                r: this,
                f: function(payload) {
                    const status = payload?.cabecalho?.status || '';
                    const message = payload?.cabecalho?.msg || 'Erro ao carregar estado do cache';
                    const cache = payload?.dados?.cache;

                    if (status !== 'ok' || !cache) {
                        this.showToast(message, 'error');
                        return;
                    }

                    this.cacheState = !!cache.active;
                    this.renderCacheState(cache.source || 'config');
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
                }
            });
        }

        runCommand(commandId) {
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

                this.executeCommandRequest(commandId, (payload) => {
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
                });
            };

            runStep(0);
        }

        executeCommandRequest(commandId, callback) {
            send({
                url: this.commandEndpoints[commandId],
                method: 'POST',
                dados: {},
                r: this,
                f: callback
            });
        }

        setCommandButtonsDisabled(commandIds, disabled) {
            commandIds.forEach((commandId) => {
                const button = this.commandButtons.find((item) => item.dataset.command === commandId);
                if (button) {
                    button.disabled = disabled;
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

        async loadTags() {
            if (this.tagsList) {
                this.tagsList.innerHTML = '<p class="empty-message">Carregando tags...</p>';
            }

            try {
                const url = new URL(this.tagsEndpoint, window.location.origin);
                url.searchParams.set('acao', 'listar');
                url.searchParams.set('_ts', String(Date.now()));

                const res = await fetch(url.toString(), {
                    cache: 'no-store'
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();
                if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao carregar tags');

                this.tags = Array.isArray(data.tags) ? data.tags : [];
                this.filterTags();

                if (this.activeTagId) {
                    const atualizada = this.tags.find((tag) => Number(tag.id) === Number(this.activeTagId));
                    if (atualizada) {
                        this.fillTagForm(atualizada);
                    } else {
                        this.openNewTag();
                    }
                } else {
                    this.openNewTag();
                }
            } catch (err) {
                console.error('Erro ao carregar tags:', err);
                if (this.tagsList) {
                    this.tagsList.innerHTML = '<p class="empty-message">Não foi possível carregar as tags.</p>';
                }
                this.showToast('Erro ao carregar tags', 'error');
            }
        }

        async loadAccessLogs() {
            if (this.accessList) {
                this.accessList.innerHTML = '<p class="empty-message">Carregando acessos...</p>';
            }
            this.setAccessButtonsDisabled(true);

            try {
                const url = new URL(this.accessEndpoint, window.location.origin);
                url.searchParams.set('limit', '500');
                url.searchParams.set('_ts', String(Date.now()));

                const res = await fetch(url.toString(), { cache: 'no-store' });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();
                if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao carregar acessos');

                this.accessLogs = Array.isArray(data.logs) ? data.logs : [];
                this.renderAccessSummary(data.resumo || {});
                this.filterAccessLogs();
            } catch (err) {
                console.error('Erro ao carregar acessos:', err);
                if (this.accessList) {
                    this.accessList.innerHTML = '<p class="empty-message">Não foi possível carregar os acessos.</p>';
                }
                this.showToast(err.message || 'Erro ao carregar acessos', 'error');
            } finally {
                this.setAccessButtonsDisabled(false);
            }
        }

        openClearAccessLogsConfirm() {
            this.pendingClearAccessLogs = true;
            window.AppUtils.showDeleteConfirm({
                modal: this.accessClearModal,
                titleElement: this.accessClearTitle,
                messageElement: this.accessClearMessage,
                actionElement: this.accessClearConfirm,
                title: 'Limpar acessos',
                message: 'Deseja realmente apagar os arquivos de log de acessos e acessos negados?',
                onEscape: () => this.closeClearAccessLogsConfirm(false)
            });
        }

        async closeClearAccessLogsConfirm(confirmado) {
            window.AppUtils.closeDeleteConfirm(this.accessClearModal);
            if (!confirmado) {
                this.pendingClearAccessLogs = false;
                return;
            }

            await this.clearAccessLogs();
        }

        async clearAccessLogs() {
            if (!this.pendingClearAccessLogs) {
                return;
            }

            this.pendingClearAccessLogs = false;
            this.setAccessButtonsDisabled(true);

            try {
                const res = await fetch(this.clearAccessEndpoint, {
                    method: 'POST',
                    cache: 'no-store'
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();
                if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao limpar acessos');

                this.showToast(data.mensagem || 'Logs de acesso removidos.', 'success');
                await this.loadAccessLogs();
            } catch (err) {
                console.error('Erro ao limpar acessos:', err);
                this.showToast(err.message || 'Erro ao limpar acessos', 'error');
                this.setAccessButtonsDisabled(false);
            }
        }

        setAccessButtonsDisabled(disabled) {
            if (this.accessRefreshBtn) {
                this.accessRefreshBtn.disabled = disabled;
            }
            if (this.accessClearBtn) {
                this.accessClearBtn.disabled = disabled;
            }
        }

        renderAccessSummary(summary) {
            if (!this.accessSummary) return;

            const acessos = Number(summary?.acessos?.total || 0);
            const negados = Number(summary?.acessos_negados?.total || 0);
            this.accessSummary.textContent = `${acessos} acesso(s) e ${negados} negado(s) carregados.`;
        }

        filterAccessLogs() {
            const query = this.normalizeSearch(this.accessSearchInput?.value || '');
            const type = this.accessTypeInput?.value || 'todos';

            this.filteredAccessLogs = this.accessLogs.filter((item) => {
                const matchesType = type === 'todos' || item.tipo === type;
                const haystack = this.normalizeSearch([
                    item.tipo_label,
                    item.data,
                    item.hora,
                    item.ip,
                    item.rota,
                    item.status,
                    item.raw
                ].join(' '));
                return matchesType && (!query || haystack.includes(query));
            });

            this.renderAccessList();
        }

        renderAccessList() {
            if (!this.accessList) return;

            if (!this.accessLogs.length) {
                this.accessList.innerHTML = '<p class="empty-message">Nenhum log encontrado.</p>';
                return;
            }

            if (!this.filteredAccessLogs.length) {
                this.accessList.innerHTML = '<p class="empty-message">Nenhum acesso encontrado para esse filtro.</p>';
                return;
            }

            this.accessList.innerHTML = this.filteredAccessLogs.map((item) => {
                const typeClass = item.tipo === 'acessos_negados' ? ' denied' : '';
                const status = item.status ? `<span class="admin-access-status">${this.escapeHtml(item.status)}</span>` : '';

                return `
                    <div class="admin-access-row${typeClass}">
                        <div class="admin-access-main">
                            <span class="admin-access-type">${this.escapeHtml(item.tipo_label || '')}</span>
                            <span class="admin-access-route">${this.escapeHtml(item.rota || item.raw || '')}</span>
                            ${status}
                        </div>
                        <div class="admin-access-meta">
                            <span>${this.escapeHtml(`${item.data || ''} ${item.hora || ''}`.trim())}</span>
                            <span>${this.escapeHtml(item.ip || '')}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        filterTags() {
            const query = this.normalizeSearch(this.tagSearchInput?.value || '');
            this.filteredTags = query
                ? this.tags.filter((tag) => {
                    const nome = this.normalizeSearch(tag.nome || '');
                    const path = this.normalizeSearch(tag.path || '');
                    return nome.includes(query) || path.includes(query);
                })
                : [...this.tags];

            this.renderTagsList();
        }

        async handleTagSearchKeydown(event) {
            if (event.key !== 'Enter') return;

            event.preventDefault();

            const nome = String(this.tagSearchInput?.value || '').trim();
            if (!nome) return;

            const existente = this.tags.find((tag) => this.normalizeSearch(tag.nome || '') === this.normalizeSearch(nome));
            if (existente) {
                this.openTag(Number(existente.id));
                this.showToast('Tag existente selecionada.', 'info');
                return;
            }

            const path = this.slugify(nome);
            if (!path) {
                this.showToast('Não foi possível gerar o path da tag.', 'error');
                return;
            }

            try {
                const res = await fetch(this.tagsEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'inserir',
                        nome,
                        path,
                        destaque: 0
                    })
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();
                if (!data.sucesso || !data.tag) throw new Error(data.mensagem || 'Erro ao criar tag');

                this.activeTagId = Number(data.tag.id);
                if (this.tagSearchInput) {
                    this.tagSearchInput.value = '';
                }
                await this.loadTags();
                this.showToast('Tag criada com sucesso.', 'success');
            } catch (err) {
                console.error('Erro ao criar tag:', err);
                this.showToast(err.message || 'Erro ao criar tag', 'error');
            }
        }

        renderTagsList() {
            if (!this.tagsList) return;

            if (!this.tags.length) {
                this.tagsList.innerHTML = '<p class="empty-message">Nenhuma tag cadastrada.</p>';
                return;
            }

            if (!this.filteredTags.length) {
                this.tagsList.innerHTML = '<p class="empty-message">Nenhuma tag encontrada para esse filtro.</p>';
                return;
            }

            this.tagsList.innerHTML = this.filteredTags.map((tag) => {
                const id = Number(tag.id) || 0;
                const activeClass = Number(this.activeTagId) === id ? ' active' : '';
                const links = Number(tag.total_links || 0);

                return `
                    <button type="button" class="admin-tag-row${activeClass}" data-tag-id="${this.escapeHtml(String(id))}">
                        <span class="admin-tag-row-title">${this.escapeHtml(tag.nome || '')}</span>
                        <span class="admin-tag-row-meta">${this.escapeHtml(String(links))} artigo(s) vinculado(s)</span>
                    </button>
                `;
            }).join('');
        }

        openNewTag() {
            this.activeTagId = null;
            if (this.tagForm) this.tagForm.reset();
            if (this.tagIdInput) this.tagIdInput.value = '';
            if (this.tagDestaqueInput) this.tagDestaqueInput.checked = false;
            if (this.tagMeta) this.tagMeta.textContent = 'Nova tag. Informe nome e path, ou deixe o path ser gerado automaticamente.';
            this.tagDeleteBtn?.classList.add('hidden');
            this.renderTagsList();
            this.tagNomeInput?.focus();
        }

        openTag(tagId) {
            const tag = this.tags.find((item) => Number(item.id) === Number(tagId));
            if (!tag) return;

            this.fillTagForm(tag);
            this.renderTagsList();
        }

        fillTagForm(tag) {
            this.activeTagId = Number(tag.id) || null;
            if (this.tagIdInput) this.tagIdInput.value = tag.id || '';
            if (this.tagNomeInput) this.tagNomeInput.value = tag.nome || '';
            if (this.tagDestaqueInput) this.tagDestaqueInput.checked = Number(tag.destaque) === 1;
            if (this.tagMeta) {
                this.tagMeta.textContent = `ID ${tag.id} · /${tag.path || ''} · ${Number(tag.total_links || 0)} artigo(s) vinculado(s).`;
            }
            this.tagDeleteBtn?.classList.remove('hidden');
        }

        async saveTag() {
            const nome = String(this.tagNomeInput?.value || '').trim();
            const destaque = this.tagDestaqueInput?.checked ? 1 : 0;
            const path = this.slugify(nome);

            if (!nome) {
                this.tagNomeInput?.focus();
                this.showToast('Informe o nome da tag.', 'error');
                return;
            }

            if (!path) {
                this.tagNomeInput?.focus();
                this.showToast('Não foi possível gerar o path da tag.', 'error');
                return;
            }

            const payload = {
                acao: this.tagIdInput?.value ? 'atualizar' : 'inserir',
                id: this.tagIdInput?.value || '',
                nome,
                path,
                destaque
            };

            try {
                const res = await fetch(this.tagsEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();
                if (!data.sucesso || !data.tag) throw new Error(data.mensagem || 'Erro ao salvar tag');

                this.activeTagId = Number(data.tag.id);
                await this.loadTags();
                this.showToast('Tag salva com sucesso.', 'success');
            } catch (err) {
                console.error('Erro ao salvar tag:', err);
                this.showToast(err.message || 'Erro ao salvar tag', 'error');
            }
        }

        openDeleteTagConfirm() {
            const id = Number(this.tagIdInput?.value || 0);
            if (!id) return;

            this.pendingDeleteTagId = id;
            const tag = this.tags.find((item) => Number(item.id) === id);
            const nome = tag?.nome || 'esta tag';
            window.AppUtils.showDeleteConfirm({
                modal: this.tagDeleteModal,
                titleElement: this.tagDeleteTitle,
                messageElement: this.tagDeleteMessage,
                actionElement: this.tagDeleteConfirm,
                title: 'Excluir tag',
                message: `Deseja realmente excluir a tag "${nome}"? Os vínculos com artigos também serão removidos.`,
                onEscape: () => this.closeDeleteTagConfirm(false)
            });
        }

        async closeDeleteTagConfirm(confirmado) {
            window.AppUtils.closeDeleteConfirm(this.tagDeleteModal);
            if (!confirmado) {
                this.pendingDeleteTagId = null;
                return;
            }

            await this.deleteTag(this.pendingDeleteTagId);
        }

        async deleteTag(tagId = null) {
            const id = Number(tagId || this.tagIdInput?.value || 0);
            if (!id) {
                this.pendingDeleteTagId = null;
                return;
            }

            try {
                const res = await fetch(`${this.tagsEndpoint}?acao=excluir`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'excluir',
                        id
                    })
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();
                if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao excluir tag');

                this.tags = this.tags.filter((item) => Number(item.id) !== id);
                this.filteredTags = this.filteredTags.filter((item) => Number(item.id) !== id);
                this.activeTagId = null;
                this.pendingDeleteTagId = null;
                this.openNewTag();
                this.renderTagsList();
                this.showToast('Tag excluída com sucesso.', 'success');
            } catch (err) {
                console.error('Erro ao excluir tag:', err);
                this.pendingDeleteTagId = null;
                this.showToast(err.message || 'Erro ao excluir tag', 'error');
            }
        }

        normalizeSearch(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        }

        slugify(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-')
                .slice(0, 25);
        }
    }

    window.AppModules.register('AdminApp', AdminApp);
})();
