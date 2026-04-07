class ArtigosApp extends window.BaseModule {
    constructor(root = document) {
        super(root);
        this.isActive = true;
        this.artigos = [];
        this.artigoAtual = null;

        // Sidebar
        this.artigoSearch  = this.root.querySelector('#artigoSearch');
        this.artigoList    = this.root.querySelector('#artigoList');
        this.novoArtigoBtn = this.root.querySelector('#novoArtigoBtn');

        // Editor
        this.editorPlaceholder = this.root.querySelector('#editorPlaceholder');
        this.artigoForm        = this.root.querySelector('#artigoForm');
        this.btnExcluir        = this.root.querySelector('#btnExcluir');

        // Campos
        this.artigoId       = this.root.querySelector('#artigoId');
        this.artigoTitulo   = this.root.querySelector('#artigoTitulo');
        this.artigoPath      = this.root.querySelector('#artigoPath');
        this.artigoSubtitulo = this.root.querySelector('#artigoSubtitulo');
        this.artigoData     = this.root.querySelector('#artigoData');
        this.artigoKeywords = this.root.querySelector('#artigoKeywords');
        this.artigoConteudo = this.root.querySelector('#artigoConteudo');

        // Flags
        this.flagUltimos  = this.root.querySelector('#flagUltimos');
        this.flagRoot     = this.root.querySelector('#flagRoot');
        this.flagPublicado = this.root.querySelector('#flagPublicado');
        this.flagSearch   = this.root.querySelector('#flagSearch');
        this.flagAmp      = this.root.querySelector('#flagAmp');

        // Modal de confirmação
        this.deleteConfirmModal   = this.root.querySelector('#deleteConfirmModal');
        this.deleteConfirmTitle   = this.root.querySelector('#deleteConfirmTitle');
        this.deleteConfirmMessage = this.root.querySelector('#deleteConfirmMessage');
        this.deleteConfirmCancel  = this.root.querySelector('#deleteConfirmCancel');
        this.deleteConfirmAction  = this.root.querySelector('#deleteConfirmAction');

        // Toast
        this.toastContainer = this.root.querySelector('#toastContainer');

        this.attachEvents();
        this.initialize();
    }

    attachEvents() {
        const deleteOverlay = this.deleteConfirmModal?.querySelector('.modal-overlay');

        this.artigoSearch.addEventListener('input', () => this.filtrarLista());
        this.artigoSearch.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.buscarNoBanco();
            }
        });

        this.novoArtigoBtn?.addEventListener('click', () => this.abrirNovoArtigo());

        this.artigoForm?.addEventListener('submit', (e) => this.salvarArtigo(e));
        this.btnExcluir?.addEventListener('click', () => this.abrirConfirmacaoExclusao());

        // Auto-path a partir do título (somente em novo artigo)
        this.artigoTitulo?.addEventListener('input', () => {
            if (!this.artigoAtual?.id) {
                this.artigoPath.value = this.gerarSlug(this.artigoTitulo.value);
            }
        });

        window.AppUtils.bindModalListeners([
            { element: this.deleteConfirmCancel,  event: 'click', handler: () => this.closeDeleteConfirm(false) },
            { element: this.deleteConfirmAction,  event: 'click', handler: () => this.closeDeleteConfirm(true) },
            { element: deleteOverlay,             event: 'click', handler: () => this.closeDeleteConfirm(false) }
        ]);
    }

    async initialize() {
        await this.carregarArtigos();
    }

    // ── Listagem ──────────────────────────────────────────────────────────

    async carregarArtigos(termo = '') {
        try {
            const params = new URLSearchParams({ acao: 'listar' });
            if (termo) params.set('termo', termo);

            const res = await fetch(`php/artigos.php?${params}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao carregar artigos');

            this.artigos = data.artigos || [];
            this.renderizarLista();
        } catch (err) {
            console.error('Erro ao carregar artigos:', err);
            this.showToast('Erro ao carregar artigos', 'error');
        }
    }

    filtrarLista() {
        const q = this.normalizeSearch(this.artigoSearch.value.trim());
        const filtrados = this.artigos.filter((a) =>
            this.normalizeSearch(a.titulo).includes(q)
        );
        this.renderizarLista(filtrados);
    }

    async buscarNoBanco() {
        await this.carregarArtigos(this.artigoSearch.value.trim());
    }

    renderizarLista(lista = this.artigos) {
        if (!lista.length) {
            const texto = this.artigos.length
                ? 'Nenhum artigo encontrado para a pesquisa.'
                : 'Nenhum artigo cadastrado.';
            this.artigoList.innerHTML = `<p class="empty-message">${texto}</p>`;
            return;
        }

        this.artigoList.innerHTML = lista
            .map((a) => {
                const activeClass = this.artigoAtual?.id == a.id ? ' active' : '';
                const pub = a.publicado == 1 ? '' : ' <span class="badge-rascunho">rascunho</span>';
                return `<button class="model-name${activeClass}" data-id="${this.escapeHtml(String(a.id))}">${this.escapeHtml(a.titulo)}${pub}</button>`;
            })
            .join('');

        this.artigoList.querySelectorAll('.model-name').forEach((btn) => {
            btn.addEventListener('click', () => this.abrirArtigo(btn.dataset.id));
        });
    }

    // ── Edição ────────────────────────────────────────────────────────────

    async abrirArtigo(id) {
        try {
            const res = await fetch(`php/artigos.php?acao=obter&id=${encodeURIComponent(id)}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao carregar artigo');

            this.artigoAtual = data.artigo;
            this.preencherFormulario(data.artigo);
            this.renderizarLista();
        } catch (err) {
            console.error('Erro ao abrir artigo:', err);
            this.showToast('Erro ao abrir artigo', 'error');
        }
    }

    abrirNovoArtigo() {
        this.artigoAtual = {};
        this.limparFormulario();
        this.editorPlaceholder.classList.add('hidden');
        this.artigoForm.classList.remove('hidden');
        this.artigoTitulo?.focus();
        this.renderizarLista();
    }

    preencherFormulario(artigo) {
        this.artigoId.value        = artigo.id           || '';
        this.artigoTitulo.value    = artigo.titulo        || '';
        this.artigoPath.value      = artigo.path          || '';
        this.artigoSubtitulo.value = artigo.subtitulo     || '';
        this.artigoData.value      = artigo.datePublished ? artigo.datePublished.substring(0, 10) : '';
        this.artigoKeywords.value  = artigo.keywords      || '';
        this.artigoConteudo.value  = artigo.conteudo      || '';

        this.flagPublicado.checked = artigo.publicado == 1;
        this.flagUltimos.checked   = artigo.ultimos   == 1;
        this.flagSearch.checked    = artigo.search    == 1;
        this.flagAmp.checked       = artigo.amp       == 1;

        if (this.artigoId.value) {
            this.btnExcluir?.classList.remove('hidden');
        } else {
            this.btnExcluir?.classList.add('hidden');
        }

        this.editorPlaceholder.classList.add('hidden');
        this.artigoForm.classList.remove('hidden');
    }

    limparFormulario() {
        this.artigoId.value        = '';
        this.artigoTitulo.value    = '';
        this.artigoPath.value      = '';
        this.artigoSubtitulo.value = '';
        this.artigoData.value      = new Date().toISOString().substring(0, 10);
        this.artigoKeywords.value  = '';
        this.artigoConteudo.value  = '';

        this.flagPublicado.checked = true;
        this.flagUltimos.checked   = false;
        this.flagRoot.checked      = false;
        this.flagSearch.checked    = true;
        this.flagAmp.checked       = false;

        this.btnExcluir?.classList.add('hidden');
    }

    // ── Salvar ────────────────────────────────────────────────────────────

    async salvarArtigo(event) {
        if (event) event.preventDefault();

        const titulo = this.artigoTitulo.value.trim();
        if (!titulo) {
            this.artigoTitulo.focus();
            this.showToast('Informe o título do artigo', 'error');
            return;
        }

        const payload = {
            acao:       this.artigoId.value ? 'atualizar' : 'inserir',
            id:         this.artigoId.value || '',
            titulo,
            path:       this.artigoPath.value.trim(),
            subtitulo:  this.artigoSubtitulo.value.trim(),
            data:       this.artigoData.value,
            keywords:   this.artigoKeywords.value.trim(),
            conteudo:   this.artigoConteudo.value.trim(),
            publicado:  this.flagPublicado.checked ? 1 : 0,
            ultimos:    this.flagUltimos.checked   ? 1 : 0,
            root:       this.flagRoot.checked      ? 1 : 0,
            search:     this.flagSearch.checked    ? 1 : 0,
            amp:        this.flagAmp.checked       ? 1 : 0
        };

        try {
            const res = await fetch('php/artigos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao salvar');

            if (data.id) {
                this.artigoId.value = data.id;
                if (this.artigoAtual) this.artigoAtual.id = data.id;
            }

            this.showToast('Artigo salvo com sucesso!', 'success');
            await this.carregarArtigos(this.artigoSearch.value.trim());
        } catch (err) {
            console.error('Erro ao salvar artigo:', err);
            this.showToast('Erro ao salvar artigo', 'error');
        }
    }

    // ── Excluir ───────────────────────────────────────────────────────────

    abrirConfirmacaoExclusao() {
        if (!this.artigoId.value) return;
        this.deleteConfirmModal.classList.remove('hidden');
    }

    async closeDeleteConfirm(confirmado) {
        this.deleteConfirmModal.classList.add('hidden');
        
        if (confirmado) {
            await this.excluirArtigo();
        }
    }

    async excluirArtigo() {
        const id = this.artigoId.value;
        if (!id) return;

        try {
            const res = await fetch('php/artigos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'excluir', id })
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao excluir');

            this.showToast('Artigo excluído com sucesso!', 'success');
            
            // Voltar pro placeholder e recarregar lista
            this.artigoForm.classList.add('hidden');
            this.editorPlaceholder.classList.remove('hidden');
            this.artigoAtual = null;
            await this.carregarArtigos(this.artigoSearch.value.trim());

        } catch (err) {
            console.error('Erro ao excluir artigo:', err);
            this.showToast('Erro ao excluir artigo', 'error');
        }
    }

    // ── Utilidades ───────────────────────────────────────────────────────

    gerarSlug(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    normalizeSearch(text) {
        return String(text || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    setActive(active) {
        this.isActive = Boolean(active);
        if (!this.isActive) {
            this.closeDeleteConfirm(false);
        }
    }
}

window.AppModules.register('ArtigosApp', ArtigosApp);
