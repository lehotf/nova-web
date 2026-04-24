(function initAdminAccess(global) {
    const mixins = global.LB9AdminMixins = global.LB9AdminMixins || {};

    mixins.access = {
        async loadAccessLogs() {
            if (this.accessList) {
                this.accessList.innerHTML = '<p class="empty-message">Carregando acessos...</p>';
            }
            this.setAccessButtonsDisabled(true);

            try {
                const data = await send(this.accessEndpoint, this.buildSendPayload({
                    limit: '500',
                    _ts: String(Date.now())
                }));
                if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao carregar acessos');

                const arquivos = data?.arquivos || {};
                this.accessFiles = {
                    acessos: String(arquivos?.acessos?.conteudo || ''),
                    acessos_negados: String(arquivos?.acessos_negados?.conteudo || '')
                };
                this.accessLogs = this.parseAccessFiles(this.accessFiles);
                this.renderAccessSummary();
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
        },

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
        },

        async closeClearAccessLogsConfirm(confirmado) {
            window.AppUtils.closeDeleteConfirm(this.accessClearModal);
            if (!confirmado) {
                this.pendingClearAccessLogs = false;
                return;
            }

            await this.clearAccessLogs();
        },

        async clearAccessLogs() {
            if (!this.pendingClearAccessLogs) {
                return;
            }

            this.pendingClearAccessLogs = false;
            this.setAccessButtonsDisabled(true);

            try {
                const data = await send(this.clearAccessEndpoint, this.buildSendPayload({}));
                if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao limpar acessos');

                this.showToast(data.mensagem || 'Logs de acesso removidos.', 'success');
                await this.loadAccessLogs();
            } catch (err) {
                console.error('Erro ao limpar acessos:', err);
                this.showToast(err.message || 'Erro ao limpar acessos', 'error');
                this.setAccessButtonsDisabled(false);
            }
        },

        setAccessButtonsDisabled(disabled) {
            if (this.accessRefreshBtn) {
                this.accessRefreshBtn.disabled = disabled;
            }
            if (this.accessClearBtn) {
                this.accessClearBtn.disabled = disabled;
            }
        },

        renderAccessSummary() {
            if (!this.accessSummary) return;

            const acessos = this.accessLogs.filter((item) => item.tipo === 'acessos').length;
            const negados = this.accessLogs.filter((item) => item.tipo === 'acessos_negados').length;
            this.accessSummary.textContent = `${acessos} acesso(s) e ${negados} negado(s) carregados.`;
        },

        parseAccessFiles(files) {
            return [
                ...this.parseAccessContent(files?.acessos || '', 'acessos'),
                ...this.parseAccessContent(files?.acessos_negados || '', 'acessos_negados')
            ];
        },

        parseAccessContent(content, type) {
            return String(content || '')
                .split(/\r\n|\n|\r/)
                .map((line) => line.trim())
                .filter((line) => line !== '')
                .map((line) => this.parseAccessLine(line, type));
        },

        parseAccessLine(line, type) {
            const item = {
                tipo: type,
                tipo_label: type === 'acessos_negados' ? 'Negado' : 'Acesso',
                data: '',
                hora: '',
                ip: '',
                rota: '',
                status: '',
                raw: line
            };
            const matches = String(line).match(/^(\d{2}\/\d{2}\/\d{4})\s+(\d{2}:\d{2}:\d{2})\s+(\S+)\s+(\S+)(?:\s+(.+))?$/);

            if (!matches) {
                return item;
            }

            item.data = matches[1] || '';
            item.hora = matches[2] || '';
            item.ip = matches[3] || '';
            item.rota = matches[4] || '';
            item.status = (matches[5] || '').trim();

            return item;
        },

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
        },

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

            const groupedLogs = {
                acessos: this.filteredAccessLogs.filter((item) => item.tipo === 'acessos'),
                acessos_negados: this.filteredAccessLogs.filter((item) => item.tipo === 'acessos_negados')
            };
            const sections = [];

            if (groupedLogs.acessos.length) {
                sections.push(this.renderAccessSection('Acessos', groupedLogs.acessos));
            }

            if (groupedLogs.acessos_negados.length) {
                sections.push(this.renderAccessSection('Acessos negados', groupedLogs.acessos_negados));
            }

            this.accessList.innerHTML = sections.join('');
        },

        renderAccessSection(title, logs) {
            return `
                <section class="admin-access-section">
                    <div class="admin-access-section-title">${this.escapeHtml(title)}</div>
                    ${logs.map((item) => {
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
                                <span>${this.escapeHtml(item.ip || '')}</span>
                                <span>${this.escapeHtml(`${item.data || ''} ${item.hora || ''}`.trim())}</span>
                            </div>
                        </div>
                    `;
            }).join('')}
                </section>
            `;
        }
    };
})(window);
