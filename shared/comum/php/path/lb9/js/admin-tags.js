(function initAdminTags(global) {
    const mixins = global.LB9AdminMixins = global.LB9AdminMixins || {};

    mixins.tags = {
        async loadTags() {
            if (this.tagsList) {
                this.tagsList.innerHTML = '<p class="empty-message">Carregando tags...</p>';
            }

            try {
                const data = await this.apiSend(this.tagsEndpoint, {
                    acao: 'listar',
                    _ts: String(Date.now())
                });
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
        },

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
        },

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
                const data = await this.apiSend(this.tagsEndpoint, {
                    acao: 'inserir',
                    nome,
                    path,
                    destaque: 0
                });
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
        },

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
        },

        openNewTag() {
            this.activeTagId = null;
            if (this.tagForm) this.tagForm.reset();
            if (this.tagIdInput) this.tagIdInput.value = '';
            if (this.tagDestaqueInput) this.tagDestaqueInput.checked = false;
            if (this.tagMeta) this.tagMeta.textContent = 'Nova tag. Informe nome e path, ou deixe o path ser gerado automaticamente.';
            this.tagDeleteBtn?.classList.add('hidden');
            this.renderTagsList();
            this.tagNomeInput?.focus();
        },

        openTag(tagId) {
            const tag = this.tags.find((item) => Number(item.id) === Number(tagId));
            if (!tag) return;

            this.fillTagForm(tag);
            this.renderTagsList();
        },

        fillTagForm(tag) {
            this.activeTagId = Number(tag.id) || null;
            if (this.tagIdInput) this.tagIdInput.value = tag.id || '';
            if (this.tagNomeInput) this.tagNomeInput.value = tag.nome || '';
            if (this.tagDestaqueInput) this.tagDestaqueInput.checked = Number(tag.destaque) === 1;
            if (this.tagMeta) {
                this.tagMeta.textContent = `ID ${tag.id} · /${tag.path || ''} · ${Number(tag.total_links || 0)} artigo(s) vinculado(s).`;
            }
            this.tagDeleteBtn?.classList.remove('hidden');
        },

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
                const data = await this.apiSend(this.tagsEndpoint, payload);
                if (!data.sucesso || !data.tag) throw new Error(data.mensagem || 'Erro ao salvar tag');

                this.activeTagId = Number(data.tag.id);
                await this.loadTags();
                this.showToast('Tag salva com sucesso.', 'success');
            } catch (err) {
                console.error('Erro ao salvar tag:', err);
                this.showToast(err.message || 'Erro ao salvar tag', 'error');
            }
        },

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
        },

        async closeDeleteTagConfirm(confirmado) {
            window.AppUtils.closeDeleteConfirm(this.tagDeleteModal);
            if (!confirmado) {
                this.pendingDeleteTagId = null;
                return;
            }

            await this.deleteTag(this.pendingDeleteTagId);
        },

        async deleteTag(tagId = null) {
            const id = Number(tagId || this.tagIdInput?.value || 0);
            if (!id) {
                this.pendingDeleteTagId = null;
                return;
            }

            try {
                const data = await this.apiSend(this.tagsEndpoint, {
                    acao: 'excluir',
                    id
                });
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
    };
})(window);
