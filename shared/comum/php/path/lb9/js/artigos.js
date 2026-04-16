class ArtigosApp extends window.BaseModule {
    constructor(root = document) {
        super(root);
        this.apiBase = '/comum/php/path/lb9/php';
        this.isActive = true;
        this.artigos = [];
        this.artigoAtual = null;
        this.thumbObjectUrl = null;
        this.thumbDragState = null;
        this.thumbCropState = this.getThumbCropDefaults();
        this.lastConteudoSelectionStart = 0;
        this.lastConteudoSelectionEnd = 0;
        this.destaquesConfig = [];

        // Sidebar
        this.artigoSearch = this.root.querySelector('#artigoSearch');
        this.artigoList = this.root.querySelector('#artigoList');
        this.novoArtigoBtn = this.root.querySelector('#novoArtigoBtn');

        // Editor
        this.editorPlaceholder = this.root.querySelector('#editorPlaceholder');
        this.artigoForm = this.root.querySelector('#artigoForm');
        this.btnExcluir = this.root.querySelector('#btnExcluir');
        this.btnThumb = this.root.querySelector('#btnThumb');
        this.btnArtigoConfiguracoes = this.root.querySelector('#btnArtigoConfiguracoes');
        this.thumbStatus = this.root.querySelector('#thumbStatus');
        this.thumbPreview = this.root.querySelector('#thumbPreview');
        this.thumbPreviewEmpty = this.root.querySelector('#thumbPreviewEmpty');

        // Campos
        this.artigoId = this.root.querySelector('#artigoId');
        this.artigoTitulo = this.root.querySelector('#artigoTitulo');
        this.artigoPath = this.root.querySelector('#artigoPath');
        this.artigoSubtitulo = this.root.querySelector('#artigoSubtitulo');
        this.artigoData = this.root.querySelector('#artigoData');
        this.artigoKeywords = this.root.querySelector('#artigoKeywords');
        this.artigoDuracao = this.root.querySelector('#artigoDuracao');
        this.artigoConteudo = this.root.querySelector('#artigoConteudo');
        this.btnImagemArtigo = this.root.querySelector('#btnImagemArtigo');
        this.artigoImagemUpload = this.root.querySelector('#artigoImagemUpload');

        // Flags
        this.flagUltimos = this.root.querySelector('#flagUltimos');
        this.flagRoot = this.root.querySelector('#flagRoot');
        this.flagPublicado = this.root.querySelector('#flagPublicado');
        this.flagSearch = this.root.querySelector('#flagSearch');
        this.flagAmp = this.root.querySelector('#flagAmp');

        // Modal de thumb
        this.thumbUploadModal = this.root.querySelector('#thumbUploadModal');
        this.thumbUploadInput = this.root.querySelector('#thumbUploadInput');
        this.thumbQualidadeG = this.root.querySelector('#thumbQualidadeG');
        this.thumbQualidadeP = this.root.querySelector('#thumbQualidadeP');
        this.thumbCropArea = this.root.querySelector('#thumbCropArea');
        this.thumbCropPlaceholder = this.root.querySelector('#thumbCropPlaceholder');
        this.thumbUploadCancel = this.root.querySelector('#thumbUploadCancel');
        this.thumbUploadAction = this.root.querySelector('#thumbUploadAction');

        // Modal de confirmação
        this.deleteConfirmModal = this.root.querySelector('#deleteConfirmModal');
        this.deleteConfirmTitle = this.root.querySelector('#deleteConfirmTitle');
        this.deleteConfirmMessage = this.root.querySelector('#deleteConfirmMessage');
        this.deleteConfirmCancel = this.root.querySelector('#deleteConfirmCancel');
        this.deleteConfirmAction = this.root.querySelector('#deleteConfirmAction');

        // Modal de configurações
        this.artigoConfigModal = this.root.querySelector('#artigoConfigModal');
        this.artigoConfigThumbTitulo = this.root.querySelector('#artigoConfigThumbTitulo');
        this.artigoConfigNotice = this.root.querySelector('#artigoConfigNotice');
        this.artigoConfigDestaques = this.root.querySelector('#artigoConfigDestaques');
        this.artigoConfigCancel = this.root.querySelector('#artigoConfigCancel');
        this.artigoConfigSave = this.root.querySelector('#artigoConfigSave');

        // Toast
        this.toastContainer = this.root.querySelector('#toastContainer');

        this.attachEvents();
        this.initialize();
    }

    attachEvents() {
        const deleteOverlay = this.deleteConfirmModal?.querySelector('.modal-overlay');
        const thumbOverlay = this.thumbUploadModal?.querySelector('.modal-overlay');
        const configOverlay = this.artigoConfigModal?.querySelector('.modal-overlay');

        document.addEventListener('keydown', (e) => {
            if (e.key === 'F1' && this.isActive) {
                e.preventDefault();
                this.abrirConfiguracoesArtigo();
            }
        });

        this.artigoSearch?.addEventListener('input', () => this.filtrarLista());
        this.artigoSearch?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.buscarNoBanco();
            }
        });

        this.novoArtigoBtn?.addEventListener('click', () => this.abrirNovoArtigo());
        this.artigoForm?.addEventListener('submit', (e) => this.salvarArtigo(e));
        this.btnExcluir?.addEventListener('click', () => this.abrirConfirmacaoExclusao());
        this.btnThumb?.addEventListener('click', () => this.abrirUploadThumb());
        this.btnArtigoConfiguracoes?.addEventListener('click', () => this.abrirConfiguracoesArtigo());
        this.btnImagemArtigo?.addEventListener('click', () => this.abrirUploadImagemArtigo());
        this.thumbUploadInput?.addEventListener('change', (e) => this.handleThumbFileChange(e));
        this.artigoImagemUpload?.addEventListener('change', (e) => this.handleArtigoImageChange(e));
        this.thumbUploadAction?.addEventListener('click', () => this.enviarThumb());
        this.artigoConfigSave?.addEventListener('click', () => this.salvarConfiguracoesArtigo());

        this.thumbCropArea?.addEventListener('wheel', (e) => this.handleThumbWheel(e), { passive: false });
        this.thumbCropArea?.addEventListener('mousedown', (e) => this.iniciarArrasteThumb(e));
        this.thumbCropArea?.addEventListener('mousemove', (e) => this.moverThumb(e));
        this.thumbCropArea?.addEventListener('mouseup', () => this.encerrarArrasteThumb());
        this.thumbCropArea?.addEventListener('mouseleave', () => this.encerrarArrasteThumb());

        this.artigoTitulo?.addEventListener('input', () => {
            if (!this.artigoAtual?.id) {
                this.artigoPath.value = this.gerarSlug(this.artigoTitulo.value);
            }
        });
        this.artigoDuracao?.addEventListener('input', () => {
            this.artigoDuracao.value = this.aplicarMascaraDuracao(this.artigoDuracao.value);
        });

        this.artigoConteudo?.addEventListener('click', () => this.salvarSelecaoConteudo());
        this.artigoConteudo?.addEventListener('keyup', () => this.salvarSelecaoConteudo());
        this.artigoConteudo?.addEventListener('select', () => this.salvarSelecaoConteudo());
        this.artigoConteudo?.addEventListener('blur', () => this.salvarSelecaoConteudo());
        this.artigoConteudo?.addEventListener('keydown', (e) => this.handleConteudoKeydown(e));
        this.artigoConteudo?.addEventListener('paste', (e) => this.handleConteudoPaste(e));

        window.AppUtils.bindModalListeners([
            { element: this.deleteConfirmCancel, event: 'click', handler: () => this.closeDeleteConfirm(false) },
            { element: this.deleteConfirmAction, event: 'click', handler: () => this.closeDeleteConfirm(true) },
            { element: deleteOverlay, event: 'click', handler: () => this.closeDeleteConfirm(false) },
            { element: this.thumbUploadCancel, event: 'click', handler: () => this.fecharUploadThumb() },
            { element: thumbOverlay, event: 'click', handler: () => this.fecharUploadThumb() },
            { element: this.artigoConfigCancel, event: 'click', handler: () => this.fecharConfiguracoesArtigo() },
            { element: configOverlay, event: 'click', handler: () => this.fecharConfiguracoesArtigo() }
        ]);
    }

    async initialize() {
        this.atualizarEstadoThumb();
        await this.carregarArtigos();
    }

    async carregarArtigos(termo = '') {
        try {
            const params = new URLSearchParams({ acao: 'listar' });
            if (termo) params.set('termo', termo);

            const res = await fetch(`${this.apiBase}/artigos.php?${params}`);
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

    async abrirArtigo(id) {
        try {
            const res = await fetch(`${this.apiBase}/artigos.php?acao=obter&id=${encodeURIComponent(id)}`);
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
        this.artigoId.value = artigo.id || '';
        this.artigoTitulo.value = artigo.titulo || '';
        this.artigoPath.value = artigo.path || '';
        this.artigoSubtitulo.value = artigo.subtitulo || '';
        this.artigoData.value = artigo.datePublished ? this.formatarDataBr(artigo.datePublished.substring(0, 10)) : '';
        this.artigoKeywords.value = artigo.keywords || '';
        this.artigoDuracao.value = artigo.duracao && artigo.duracao !== '00:00:00' ? artigo.duracao : '';
        this.artigoConteudo.value = artigo.conteudo || '';
        if (this.artigoAtual) {
            this.artigoAtual.thumb_titulo = artigo.thumb_titulo || '';
        }

        this.flagPublicado.checked = artigo.publicado == 1;
        this.flagUltimos.checked = artigo.ultimos == 1;
        this.flagRoot.checked = artigo.root == 1;
        this.flagSearch.checked = artigo.search == 1;
        this.flagAmp.checked = artigo.amp == 1;

        if (this.artigoId.value) {
            this.btnExcluir?.classList.remove('hidden');
        } else {
            this.btnExcluir?.classList.add('hidden');
        }

        this.atualizarEstadoThumb(artigo);

        this.editorPlaceholder.classList.add('hidden');
        this.artigoForm.classList.remove('hidden');
    }

    limparFormulario() {
        this.artigoId.value = '';
        this.artigoTitulo.value = '';
        this.artigoPath.value = '';
        this.artigoSubtitulo.value = '';
        this.artigoData.value = this.dataAtualBr();
        this.artigoKeywords.value = '';
        this.artigoDuracao.value = '';
        this.artigoConteudo.value = '';

        this.flagPublicado.checked = false;
        this.flagUltimos.checked = false;
        this.flagRoot.checked = false;
        this.flagSearch.checked = true;
        this.flagAmp.checked = false;

        this.btnExcluir?.classList.add('hidden');
        this.atualizarEstadoThumb({});
        this.lastConteudoSelectionStart = 0;
        this.lastConteudoSelectionEnd = 0;
        this.destaquesConfig = [];
        if (this.artigoAtual) {
            this.artigoAtual.thumb_titulo = '';
        }
    }

    async salvarArtigo(event) {
        if (event) event.preventDefault();

        const titulo = this.artigoTitulo.value.trim();
        if (!titulo) {
            this.artigoTitulo.focus();
            this.showToast('Informe o título do artigo', 'error');
            return;
        }

        const dataIso = this.normalizarDataParaIso(this.artigoData.value);
        if (!dataIso) {
            this.artigoData.focus();
            this.showToast('Informe a data no formato dd/mm/aaaa', 'error');
            return;
        }

        const duracao = this.normalizarDuracao(this.artigoDuracao.value);
        if (!duracao) {
            this.artigoDuracao.focus();
            this.showToast('Informe a duração no formato hh:mm:ss', 'error');
            return;
        }

        this.artigoData.value = this.formatarDataBr(dataIso);
        this.artigoDuracao.value = duracao;

        const payload = {
            acao: this.artigoId.value ? 'atualizar' : 'inserir',
            id: this.artigoId.value || '',
            titulo,
            thumb_titulo: this.artigoAtual?.thumb_titulo || '',
            path: this.artigoPath.value.trim(),
            subtitulo: this.artigoSubtitulo.value.trim(),
            data: dataIso,
            keywords: this.artigoKeywords.value.trim(),
            duracao,
            conteudo: this.artigoConteudo.value.trim(),
            publicado: this.flagPublicado.checked ? 1 : 0,
            ultimos: this.flagUltimos.checked ? 1 : 0,
            root: this.flagRoot.checked ? 1 : 0,
            search: this.flagSearch.checked ? 1 : 0,
            amp: this.flagAmp.checked ? 1 : 0
        };

        try {
            const res = await fetch(`${this.apiBase}/artigos.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao salvar');

            if (data.id) {
                this.artigoId.value = data.id;
                if (!this.artigoAtual) this.artigoAtual = {};
                this.artigoAtual.id = data.id;
                if (this.artigoAtual.thumb === undefined) this.artigoAtual.thumb = 0;
            }

            this.showToast('Artigo salvo com sucesso!', 'success');
            this.atualizarEstadoThumb(this.artigoAtual || {});
            await this.carregarArtigos(this.artigoSearch.value.trim());
        } catch (err) {
            console.error('Erro ao salvar artigo:', err);
            this.showToast('Erro ao salvar artigo', 'error');
        }
    }

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
            const res = await fetch(`${this.apiBase}/artigos.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ acao: 'excluir', id })
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao excluir');

            this.showToast('Artigo excluído com sucesso!', 'success');
            this.artigoForm.classList.add('hidden');
            this.editorPlaceholder.classList.remove('hidden');
            this.artigoAtual = null;
            this.limparFormulario();
            await this.carregarArtigos(this.artigoSearch.value.trim());
        } catch (err) {
            console.error('Erro ao excluir artigo:', err);
            this.showToast('Erro ao excluir artigo', 'error');
        }
    }

    handleConteudoKeydown(event) {
        if (event.key === 'Tab') {
            event.preventDefault();
            this.artigoForm?.requestSubmit();
            return;
        }

        if ((event.ctrlKey || event.metaKey) && String(event.key).toLowerCase() === 'i') {
            event.preventDefault();
            this.abrirUploadImagemArtigo();
            return;
        }

        setTimeout(() => this.salvarSelecaoConteudo(), 0);
    }

    handleConteudoPaste(event) {
        const texto = event.clipboardData?.getData('text/plain') || '';
        const videoId = this.extrairYoutubeVideoId(texto);
        if (!videoId) return;

        event.preventDefault();
        this.inserirNoConteudo(`video[${videoId}]`);
    }

    salvarSelecaoConteudo() {
        if (!this.artigoConteudo) return;
        this.lastConteudoSelectionStart = this.artigoConteudo.selectionStart ?? 0;
        this.lastConteudoSelectionEnd = this.artigoConteudo.selectionEnd ?? this.lastConteudoSelectionStart;
    }

    abrirUploadImagemArtigo() {
        if (!this.artigoId.value) {
            this.showToast('Salve o artigo antes de enviar imagens', 'error');
            return;
        }

        this.salvarSelecaoConteudo();
        if (this.artigoImagemUpload) {
            this.artigoImagemUpload.value = '';
            this.artigoImagemUpload.click();
        }
    }

    async handleArtigoImageChange(event) {
        const file = event.target?.files?.[0];
        if (!file) return;

        const isSupported = /image\/(jpeg|png)/i.test(file.type) || /\.(jpe?g|png)$/i.test(file.name);
        if (!isSupported) {
            this.showToast('Use uma imagem JPG ou PNG', 'error');
            this.artigoImagemUpload.value = '';
            return;
        }

        if (file.size > 2_000_000) {
            this.showToast('Tamanho máximo de arquivo: 2MB', 'error');
            this.artigoImagemUpload.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('acao', 'upload_imagem_artigo');
        formData.append('id', this.artigoId.value);
        formData.append('imagem', file);

        if (this.btnImagemArtigo) this.btnImagemArtigo.disabled = true;

        try {
            const res = await fetch(`${this.apiBase}/artigos.php`, {
                method: 'POST',
                body: formData
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao enviar imagem');

            this.inserirMarcacaoImagem(data.name);
            this.showToast('Imagem enviada e inserida no texto', 'success');
        } catch (err) {
            console.error('Erro ao enviar imagem do artigo:', err);
            this.showToast(err.message || 'Erro ao enviar imagem', 'error');
        } finally {
            if (this.btnImagemArtigo) this.btnImagemArtigo.disabled = false;
            if (this.artigoImagemUpload) this.artigoImagemUpload.value = '';
        }
    }

    inserirMarcacaoImagem(nomeImagem) {
        if (!this.artigoConteudo || !nomeImagem) return;
        this.inserirNoConteudo(`img[${nomeImagem}]`);
    }

    inserirNoConteudo(textoInserido) {
        if (!this.artigoConteudo || !textoInserido) return;

        const inicio = Math.max(0, this.lastConteudoSelectionStart || 0);
        const fim = Math.max(inicio, this.lastConteudoSelectionEnd || inicio);
        const textoAtual = this.artigoConteudo.value || '';

        this.artigoConteudo.value = `${textoAtual.slice(0, inicio)}${textoInserido}${textoAtual.slice(fim)}`;
        const posicaoFinal = inicio + textoInserido.length;
        this.artigoConteudo.focus();
        this.artigoConteudo.setSelectionRange(posicaoFinal, posicaoFinal);
        this.salvarSelecaoConteudo();
    }

    extrairYoutubeVideoId(texto) {
        const valor = String(texto || '').trim();
        if (!valor) return '';

        const matchDireto = valor.match(/^[a-zA-Z0-9_-]{11}$/);
        if (matchDireto) return matchDireto[0];

        const padroes = [
            /(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?[^ \n\r\t]*v=([a-zA-Z0-9_-]{11})/i,
            /(?:https?:\/\/)?(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]{11})/i,
            /(?:https?:\/\/)?(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/i,
            /(?:https?:\/\/)?(?:www\.)?youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/i
        ];

        for (const padrao of padroes) {
            const match = valor.match(padrao);
            if (match?.[1]) return match[1];
        }

        return '';
    }

    abrirUploadThumb() {
        if (!this.artigoId.value) {
            this.showToast('Salve o artigo antes de enviar a thumb', 'error');
            return;
        }

        this.resetThumbModal({ preserveFile: false });
        window.AppUtils.openModal({
            modal: this.thumbUploadModal,
            focusTarget: this.thumbUploadInput,
            focusDelayMs: 40,
            closeOnEscape: true,
            onEscape: () => this.fecharUploadThumb()
        });
    }

    fecharUploadThumb() {
        window.AppUtils.closeModal(this.thumbUploadModal);
        this.resetThumbModal({ preserveFile: false });
    }

    async abrirConfiguracoesArtigo() {
        if (this.artigoConfigThumbTitulo) {
            this.artigoConfigThumbTitulo.value = this.artigoAtual?.thumb_titulo || '';
        }
        this.renderizarConfiguracoesArtigo();

        window.AppUtils.openModal({
            modal: this.artigoConfigModal,
            focusTarget: this.artigoConfigSave,
            focusDelayMs: 40,
            closeOnEscape: true,
            onEscape: () => this.fecharConfiguracoesArtigo()
        });

        await this.carregarConfiguracoesArtigo();
    }

    fecharConfiguracoesArtigo() {
        window.AppUtils.closeModal(this.artigoConfigModal);
    }

    async carregarConfiguracoesArtigo() {
        const params = new URLSearchParams({ acao: 'listar_destaques' });
        if (this.artigoId?.value) {
            params.set('id', this.artigoId.value);
        }

        try {
            const res = await fetch(`${this.apiBase}/artigos.php?${params}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao carregar configurações');

            this.destaquesConfig = Array.isArray(data.destaques) ? data.destaques : [];
            if (this.artigoConfigThumbTitulo) {
                this.artigoConfigThumbTitulo.value = data.thumb_titulo || '';
            }
            if (this.artigoAtual) {
                this.artigoAtual.thumb_titulo = data.thumb_titulo || '';
            }
            this.renderizarConfiguracoesArtigo();
        } catch (err) {
            console.error('Erro ao carregar configurações do artigo:', err);
            this.showToast('Erro ao carregar configurações', 'error');
        }
    }

    renderizarConfiguracoesArtigo() {
        if (!this.artigoConfigDestaques || !this.artigoConfigNotice) return;

        const artigoId = Number(this.artigoId?.value || 0);
        const artigoSalvo = artigoId > 0;

        this.artigoConfigNotice.textContent = artigoSalvo
            ? 'Escolha em quais posições de destaque este artigo deve aparecer.'
            : 'Salve o artigo antes de definir posição de destaque.';
        this.artigoConfigNotice.classList.remove('hidden');

        if (this.artigoConfigThumbTitulo) {
            this.artigoConfigThumbTitulo.disabled = !artigoSalvo;
        }

        if (this.artigoConfigSave) {
            this.artigoConfigSave.disabled = !artigoSalvo;
        }

        if (!this.destaquesConfig.length) {
            this.artigoConfigDestaques.innerHTML = '<div class="config-option"><div class="config-option-body"><span class="config-option-title">Nenhuma posição cadastrada.</span><span class="config-option-meta">Cadastre registros em links_destaques para habilitar esta configuração.</span></div></div>';
            return;
        }

        this.artigoConfigDestaques.innerHTML = this.destaquesConfig
            .map((item) => {
                const selecionado = Number(item.selecionado) === 1;
                const ocupadoPorOutro = Number(item.linkID) > 0 && Number(item.linkID) !== artigoId;
                const classes = [
                    'config-option',
                    selecionado ? 'is-selected' : '',
                    !artigoSalvo ? 'is-disabled' : ''
                ].filter(Boolean).join(' ');

                let meta = `ID da posição: ${this.escapeHtml(String(item.id))}`;
                if (ocupadoPorOutro) {
                    const titulo = item.artigoTitulo ? ` (${this.escapeHtml(item.artigoTitulo)})` : '';
                    meta += ` · atualmente no artigo ${this.escapeHtml(String(item.linkID))}${titulo}`;
                } else if (selecionado) {
                    meta += ' · já vinculada a este artigo';
                } else {
                    meta += ' · disponível';
                }

                return `
                    <label class="${classes}">
                        <input type="checkbox" class="config-destaque-checkbox" value="${this.escapeHtml(String(item.id))}" ${selecionado ? 'checked' : ''} ${artigoSalvo ? '' : 'disabled'}>
                        <div class="config-option-body">
                            <span class="config-option-title">${this.escapeHtml(item.nome || `Posição ${item.id}`)}</span>
                            <span class="config-option-meta">${meta}</span>
                        </div>
                    </label>
                `;
            })
            .join('');
    }

    async salvarConfiguracoesArtigo() {
        const artigoId = Number(this.artigoId?.value || 0);
        if (!artigoId) {
            this.showToast('Salve o artigo antes de alterar os destaques', 'error');
            return;
        }

        const destaqueIds = Array.from(this.artigoConfigDestaques?.querySelectorAll('.config-destaque-checkbox:checked') || [])
            .map((input) => Number(input.value))
            .filter((id) => id > 0);
        const thumbTitulo = this.artigoConfigThumbTitulo?.value.trim() || '';

        if (this.artigoConfigSave) {
            this.artigoConfigSave.disabled = true;
        }

        try {
            const res = await fetch(`${this.apiBase}/artigos.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: 'salvar_destaques',
                    id: artigoId,
                    destaque_ids: destaqueIds,
                    thumb_titulo: thumbTitulo
                })
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao salvar configurações');

            this.destaquesConfig = Array.isArray(data.destaques) ? data.destaques : this.destaquesConfig;
            if (this.artigoConfigThumbTitulo) {
                this.artigoConfigThumbTitulo.value = data.thumb_titulo || '';
            }
            if (!this.artigoAtual) {
                this.artigoAtual = {};
            }
            this.artigoAtual.thumb_titulo = data.thumb_titulo || '';
            this.renderizarConfiguracoesArtigo();
            this.showToast('Configurações salvas com sucesso!', 'success');
            this.fecharConfiguracoesArtigo();
        } catch (err) {
            console.error('Erro ao salvar configurações do artigo:', err);
            this.showToast(err.message || 'Erro ao salvar configurações', 'error');
        } finally {
            if (this.artigoConfigSave) {
                this.artigoConfigSave.disabled = false;
            }
        }
    }

    resetThumbModal({ preserveFile = false } = {}) {
        this.encerrarArrasteThumb();
        this.thumbCropState = this.getThumbCropDefaults();
        this.renderizarCropThumb();

        if (!preserveFile && this.thumbUploadInput) {
            this.thumbUploadInput.value = '';
        }

        if (this.thumbQualidadeG && !this.thumbQualidadeG.value) {
            this.thumbQualidadeG.value = '75';
        }

        if (this.thumbQualidadeP && !this.thumbQualidadeP.value) {
            this.thumbQualidadeP.value = '75';
        }
    }

    getThumbCropDefaults() {
        return {
            file: null,
            imageLoaded: false,
            displayWidth: 1280,
            displayHeight: 720,
            prop: 1,
            bx: 0,
            by: 0,
            sourceUrl: ''
        };
    }

    async handleThumbFileChange(event) {
        const file = event.target?.files?.[0];
        if (!file) {
            this.thumbCropState = this.getThumbCropDefaults();
            this.renderizarCropThumb();
            return;
        }

        const isSupported = /image\/(jpeg|png)/i.test(file.type) || /\.(jpe?g|png)$/i.test(file.name);
        if (!isSupported) {
            this.showToast('Use uma imagem JPG ou PNG', 'error');
            this.thumbUploadInput.value = '';
            return;
        }

        if (file.size > 2_000_000) {
            this.showToast('Tamanho máximo de arquivo: 2MB', 'error');
            this.thumbUploadInput.value = '';
            return;
        }

        this.revokeThumbObjectUrl();
        this.thumbObjectUrl = URL.createObjectURL(file);

        try {
            const image = await this.carregarImagem(this.thumbObjectUrl);
            const baseWidth = 1280;
            let displayWidth = baseWidth;
            let displayHeight = image.height * (displayWidth / image.width);
            const prop = displayHeight / displayWidth;

            if (displayHeight < 720) {
                displayHeight = 720;
                displayWidth = displayHeight / prop;
            }

            this.thumbCropState = {
                file,
                imageLoaded: true,
                displayWidth,
                displayHeight,
                prop,
                bx: 0,
                by: 0,
                sourceUrl: this.thumbObjectUrl
            };
            this.renderizarCropThumb();
        } catch (err) {
            console.error('Erro ao carregar imagem da thumb:', err);
            this.showToast('Não foi possível carregar a imagem', 'error');
        }
    }

    carregarImagem(src) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = src;
        });
    }

    handleThumbWheel(event) {
        if (!this.thumbCropState.imageLoaded) return;

        event.preventDefault();

        let displayWidth = this.thumbCropState.displayWidth - Math.floor(event.deltaY / (event.shiftKey ? 6 : 1));
        if (displayWidth < 1280) displayWidth = 1280;

        let displayHeight = displayWidth * this.thumbCropState.prop;
        if (displayHeight < 720) {
            displayHeight = 720;
            displayWidth = displayHeight / this.thumbCropState.prop;
        }

        this.thumbCropState.displayWidth = displayWidth;
        this.thumbCropState.displayHeight = displayHeight;
        this.clampThumbOffsets();
        this.renderizarCropThumb();
    }

    iniciarArrasteThumb(event) {
        if (!this.thumbCropState.imageLoaded) return;

        event.preventDefault();
        this.thumbDragState = {
            startX: event.clientX,
            startY: event.clientY,
            baseX: this.thumbCropState.bx,
            baseY: this.thumbCropState.by
        };
        this.thumbCropArea?.classList.add('is-dragging');
    }

    moverThumb(event) {
        if (!this.thumbDragState || !this.thumbCropState.imageLoaded) return;

        this.thumbCropState.bx = this.thumbDragState.baseX + (event.clientX - this.thumbDragState.startX);
        this.thumbCropState.by = this.thumbDragState.baseY + (event.clientY - this.thumbDragState.startY);
        this.clampThumbOffsets();
        this.renderizarCropThumb();
    }

    encerrarArrasteThumb() {
        if (!this.thumbDragState) return;
        this.thumbDragState = null;
        this.thumbCropArea?.classList.remove('is-dragging');
        this.clampThumbOffsets();
        this.renderizarCropThumb();
    }

    clampThumbOffsets() {
        if (!this.thumbCropState.imageLoaded) return;

        const minX = 1280 - this.thumbCropState.displayWidth;
        const minY = 720 - this.thumbCropState.displayHeight;

        if (this.thumbCropState.bx > 0) this.thumbCropState.bx = 0;
        if (this.thumbCropState.by > 0) this.thumbCropState.by = 0;
        if (this.thumbCropState.bx < minX) this.thumbCropState.bx = minX;
        if (this.thumbCropState.by < minY) this.thumbCropState.by = minY;
    }

    renderizarCropThumb() {
        if (!this.thumbCropArea) return;

        if (!this.thumbCropState.imageLoaded || !this.thumbCropState.sourceUrl) {
            this.thumbCropArea.style.backgroundImage = 'none';
            this.thumbCropArea.style.backgroundSize = '';
            this.thumbCropArea.style.backgroundPositionX = '0px';
            this.thumbCropArea.style.backgroundPositionY = '0px';
            this.thumbCropPlaceholder?.classList.remove('hidden');
            return;
        }

        this.thumbCropPlaceholder?.classList.add('hidden');
        this.thumbCropArea.style.backgroundImage = `url("${this.thumbCropState.sourceUrl}")`;
        this.thumbCropArea.style.backgroundSize = `${this.thumbCropState.displayWidth}px ${this.thumbCropState.displayHeight}px`;
        this.thumbCropArea.style.backgroundPositionX = `${this.thumbCropState.bx}px`;
        this.thumbCropArea.style.backgroundPositionY = `${this.thumbCropState.by}px`;
    }

    async enviarThumb() {
        const id = this.artigoId.value;
        if (!id) {
            this.showToast('Salve o artigo antes de enviar a thumb', 'error');
            return;
        }

        if (!this.thumbCropState.file || !this.thumbCropState.imageLoaded) {
            this.showToast('Selecione uma imagem para a thumb', 'error');
            return;
        }

        this.clampThumbOffsets();
        this.renderizarCropThumb();

        const formData = new FormData();
        formData.append('acao', 'upload_thumb');
        formData.append('id', id);
        formData.append('bx', String(Math.round(this.thumbCropState.bx)));
        formData.append('by', String(Math.round(this.thumbCropState.by)));
        formData.append('width', String(Math.round(this.thumbCropState.displayWidth)));
        formData.append('qualidade_g', this.thumbQualidadeG?.value || '75');
        formData.append('qualidade_p', this.thumbQualidadeP?.value || '75');
        formData.append('thumb', this.thumbCropState.file);

        this.thumbUploadAction.disabled = true;

        try {
            const res = await fetch(`${this.apiBase}/artigos.php`, {
                method: 'POST',
                body: formData
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!data.sucesso) throw new Error(data.mensagem || 'Erro ao enviar thumb');

            if (!this.artigoAtual) this.artigoAtual = {};
            this.artigoAtual.id = id;
            this.artigoAtual.thumb = data.id || id;

            this.atualizarEstadoThumb(this.artigoAtual, data.timestamp || Date.now());
            this.showToast('Thumb enviada com sucesso!', 'success');
            this.fecharUploadThumb();
        } catch (err) {
            console.error('Erro ao enviar thumb:', err);
            this.showToast(err.message || 'Erro ao enviar thumb', 'error');
        } finally {
            this.thumbUploadAction.disabled = false;
        }
    }

    atualizarEstadoThumb(artigo = {}, cacheBuster = Date.now()) {
        const id = artigo?.id || this.artigoId?.value || '';
        const thumb = artigo?.thumb || 0;
        const possuiThumb = Boolean(Number(thumb) > 0 || String(thumb) === String(id));

        if (!id) {
            if (this.thumbStatus) this.thumbStatus.textContent = 'Salve o artigo para liberar o upload.';
            this.setThumbPreviewVisible(false);
            return;
        }

        if (possuiThumb) {
            if (this.thumbStatus) this.thumbStatus.textContent = '';
            if (this.thumbPreview) {
                this.thumbPreview.src = `/cache/img/upload/t/${id}.jpg?v=${cacheBuster}`;
            }
            this.setThumbPreviewVisible(true);
            return;
        }

        if (this.thumbStatus) this.thumbStatus.textContent = `Artigo ${id} salvo. Faça o upload da thumb.`;
        this.setThumbPreviewVisible(false);
    }

    setThumbPreviewVisible(visible) {
        this.thumbPreview?.classList.toggle('hidden', !visible);
        this.thumbPreviewEmpty?.classList.toggle('hidden', visible);
        if (!visible && this.thumbPreview) {
            this.thumbPreview.removeAttribute('src');
        }
    }

    revokeThumbObjectUrl() {
        if (this.thumbObjectUrl) {
            URL.revokeObjectURL(this.thumbObjectUrl);
            this.thumbObjectUrl = null;
        }
    }

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

    formatarDataBr(dataIso) {
        const match = String(dataIso || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return '';
        return `${match[3]}/${match[2]}/${match[1]}`;
    }

    normalizarDataParaIso(data) {
        const valor = String(data || '').trim();
        if (!valor) return '';

        let match = valor.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (match) {
            const iso = `${match[3]}-${match[2]}-${match[1]}`;
            return this.validarDataIso(iso) ? iso : '';
        }

        match = valor.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (match) {
            return this.validarDataIso(valor) ? valor : '';
        }

        return '';
    }

    validarDataIso(dataIso) {
        const match = String(dataIso || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return false;
        const data = new Date(`${dataIso}T00:00:00`);
        if (Number.isNaN(data.getTime())) return false;
        return data.toISOString().substring(0, 10) === dataIso;
    }

    dataAtualBr() {
        const agora = new Date();
        const ano = agora.getFullYear();
        const mes = String(agora.getMonth() + 1).padStart(2, '0');
        const dia = String(agora.getDate()).padStart(2, '0');
        return `${dia}/${mes}/${ano}`;
    }

    aplicarMascaraDuracao(valor) {
        const numeros = String(valor || '').replace(/\D/g, '').substring(0, 6);
        if (!numeros) return '';

        const partes = [];
        if (numeros.length > 0) partes.push(numeros.substring(0, Math.min(2, numeros.length)));
        if (numeros.length > 2) partes.push(numeros.substring(2, Math.min(4, numeros.length)));
        if (numeros.length > 4) partes.push(numeros.substring(4, Math.min(6, numeros.length)));
        return partes.join(':');
    }

    normalizarDuracao(valor) {
        const texto = String(valor || '').trim();
        if (!texto) return '00:00:00';

        const numeros = texto.replace(/\D/g, '');
        if (!numeros) return '00:00:00';
        if (numeros.length !== 6) return '';

        const horas = Number(numeros.substring(0, 2));
        const minutos = Number(numeros.substring(2, 4));
        const segundos = Number(numeros.substring(4, 6));

        if (Number.isNaN(horas) || Number.isNaN(minutos) || Number.isNaN(segundos)) return '';
        if (minutos > 59 || segundos > 59) return '';

        return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
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
            this.fecharUploadThumb();
            this.fecharConfiguracoesArtigo();
        }
    }
}

window.AppModules.register('ArtigosApp', ArtigosApp);
