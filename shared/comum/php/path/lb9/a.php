<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Conteúdo — Painel Admin</title>
    <meta name="description" content="Painel de controle para administração de conteúdo do site">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/launcher.css">
    <link rel="stylesheet" href="css/styles.css">
    <script src="/comum/estatico/js/send.js"></script>
</head>
<body class="has-launcher" data-module="artigos">

    <nav id="appLauncher" class="app-launcher" aria-label="Navegação entre módulos"></nav>

    <section id="spaArtigosView" class="hidden"></section>
    <section id="spaAdminView" class="hidden"></section>

    <template id="spaArtigosTemplate">
        <div class="container">
            <main class="main-content">

                <!-- Sidebar -->
                <aside class="sidebar">
                    <div class="sidebar-header">
                        <div class="sidebar-top-row">
                            <input id="artigoSearch" class="search-input" type="text" placeholder="Pesquisar artigo...">
                            <button id="novoArtigoBtn" class="btn btn-icon" title="Novo Artigo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div id="artigoList" class="model-list">
                        <p class="empty-message">Carregando artigos...</p>
                    </div>
                </aside>

                <!-- Editor -->
                <section class="editor">
                    <div id="editorPlaceholder" class="editor-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <path d="M14 2v6h6"></path>
                            <line x1="8" y1="13" x2="16" y2="13"></line>
                            <line x1="8" y1="17" x2="14" y2="17"></line>
                            <line x1="8" y1="9" x2="12" y2="9"></line>
                        </svg>
                        <h2>Artigos do Site</h2>
                        <p>Selecione um artigo na lista ou crie um novo.</p>
                    </div>

                    <form id="artigoForm" class="task-form hidden">
                        <input type="hidden" id="artigoId">

                        <div class="form-grid">

                            <!-- Linha 1: Título (50%) | URL (35%) | Data (15%) -->
                            <div class="form-row-3col">
                                <div class="form-group">
                                    <label for="artigoTitulo">Título</label>
                                    <input id="artigoTitulo" type="text" placeholder="Título do artigo">
                                </div>
                                <div class="form-group">
                                    <label for="artigoPath">Path (URL)</label>
                                    <input id="artigoPath" type="text" placeholder="ex.: /artigos/meu-artigo">
                                </div>
                                <div class="form-group form-group-inline-action">
                                    <label for="artigoData">Data</label>
                                    <div class="form-input-with-action">
                                        <input id="artigoData" type="text" inputmode="numeric" placeholder="dd/mm/aaaa">
                                        <button type="button" id="btnArtigoConfiguracoes" class="field-action-btn" title="Configurações avançadas">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="3"></circle>
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Linha 2: Subtítulo (50%) | Keywords (35%) | Duração (15%) -->
                            <div class="form-row-3col">
                                <div class="form-group">
                                    <label for="artigoSubtitulo">Subtítulo</label>
                                    <input id="artigoSubtitulo" type="text" placeholder="Subtítulo / meta description">
                                </div>
                                <div class="form-group">
                                    <label for="artigoKeywords">Keywords</label>
                                    <input id="artigoKeywords" type="text" placeholder="palavra1, palavra2, palavra3">
                                </div>
                                <div class="form-group">
                                    <label for="artigoDuracao">Duração</label>
                                    <input id="artigoDuracao" type="text" inputmode="numeric" placeholder="00:00:00" maxlength="8">
                                </div>
                            </div>

                            <!-- Linha 4: Conteúdo (esq) | Flags + Salvar (dir) -->
                            <div class="form-row-description">

                                <div class="form-group form-group-description">
                                    <label for="artigoConteudo">Conteúdo</label>
                                    <textarea id="artigoConteudo" placeholder="Conteúdo do artigo (HTML ou texto)"></textarea>
                                </div>

                                <div class="form-group-flags-column">
                                    <!-- Label espaçador: empurra flags para baixo do label "Conteúdo", alinhando com o início do textarea -->
                                    <label class="flags-spacer-label">&nbsp;</label>

                                    <div class="flags-options">
                                        <label class="flag-option">
                                            <input type="checkbox" id="flagPublicado" checked>
                                            <span class="flag-label">Publicado</span>
                                        </label>
                                        <label class="flag-option">
                                            <input type="checkbox" id="flagUltimos">
                                            <span class="flag-label">Últimos</span>
                                        </label>
                                        <label class="flag-option">
                                            <input type="checkbox" id="flagRoot">
                                            <span class="flag-label">Root</span>
                                        </label>
                                        <label class="flag-option">
                                            <input type="checkbox" id="flagSearch" checked>
                                            <span class="flag-label">Search</span>
                                        </label>
                                        <label class="flag-option">
                                            <input type="checkbox" id="flagAmp">
                                            <span class="flag-label">AMP</span>
                                        </label>
                                    </div>

                                    <div class="content-side-actions">
                                        <input id="artigoImagemUpload" class="hidden" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                                        <button type="button" id="btnImagemArtigo" class="btn btn-secondary btn-small full-width">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                <polyline points="21 15 16 10 5 21"></polyline>
                                            </svg>
                                            Upload Imagem
                                        </button>
                                    </div>

                                    <div class="thumb-panel">
                                        <div class="thumb-panel-header">
                                            <span class="thumb-panel-title">Thumb</span>
                                            <span id="thumbStatus" class="thumb-status">Salve o artigo para liberar o upload.</span>
                                        </div>
                                        <div class="thumb-panel-body">
                                            <img id="thumbPreview" class="thumb-preview hidden" alt="Preview da thumb do artigo">
                                            <div id="thumbPreviewEmpty" class="thumb-preview-empty">Nenhuma thumb enviada.</div>
                                        </div>
                                        <button type="button" id="btnThumb" class="btn btn-secondary">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                            Upload da Thumb
                                        </button>
                                    </div>

                                    <div class="form-group-buttons">
                                        <button type="submit" class="btn btn-primary">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                                <polyline points="7 3 7 8 15 8"></polyline>
                                            </svg>
                                            Salvar Artigo
                                        </button>
                                        <button type="button" id="btnExcluir" class="btn btn-danger hidden">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                <path d="M10 11v6"></path>
                                                <path d="M14 11v6"></path>
                                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                            </svg>
                                            Excluir
                                        </button>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </form>
                </section>

            </main>
        </div>

        <div id="toastContainer" class="toast-container"></div>

        <div id="thumbUploadModal" class="modal hidden">
            <div class="modal-overlay"></div>
            <div class="modal-content thumb-modal-content">
                <div class="modal-icon modal-icon-input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                </div>
                <h2 class="modal-title">Upload da Thumb</h2>
                <p class="modal-message">Selecione a imagem, ajuste a posição com o mouse e use a roda para zoom. A thumb final será salva com o nome do ID do artigo.</p>

                <div class="thumb-modal-grid">
                    <div class="form-group">
                        <label for="thumbUploadInput">Imagem</label>
                        <input id="thumbUploadInput" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                    </div>
                    <div class="thumb-quality-grid">
                        <div class="form-group">
                            <label for="thumbQualidadeG">Qualidade G</label>
                            <input id="thumbQualidadeG" type="number" min="20" max="100" value="75">
                        </div>
                        <div class="form-group">
                            <label for="thumbQualidadeP">Qualidade P</label>
                            <input id="thumbQualidadeP" type="number" min="20" max="100" value="75">
                        </div>
                    </div>
                </div>

                <div class="thumb-crop-shell">
                    <div id="thumbCropArea" class="thumb-crop-area">
                        <div id="thumbCropPlaceholder" class="thumb-crop-placeholder">Selecione uma imagem para começar.</div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button id="thumbUploadCancel" class="btn btn-secondary">Cancelar</button>
                    <button id="thumbUploadAction" class="btn btn-primary">Salvar Thumb</button>
                </div>
            </div>
        </div>

        <div id="deleteConfirmModal" class="modal hidden">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-icon modal-icon-input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        <path d="M10 11v6"></path>
                        <path d="M14 11v6"></path>
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                    </svg>
                </div>
                <h2 id="deleteConfirmTitle" class="modal-title">Confirmar exclusão</h2>
                <p id="deleteConfirmMessage" class="modal-message">Deseja realmente excluir este item?</p>
                <div class="modal-actions">
                    <button id="deleteConfirmCancel" class="btn btn-secondary">Cancelar</button>
                    <button id="deleteConfirmAction" class="btn btn-danger">Excluir</button>
                </div>
            </div>
        </div>

        <div id="artigoConfigModal" class="modal hidden">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-icon modal-icon-input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </div>
                <h2 class="modal-title">Configurações do Artigo</h2>

                <div class="form-group">
                    <label for="artigoConfigThumbTitulo">Thumb Título</label>
                    <textarea id="artigoConfigThumbTitulo" class="config-thumb-titulo-input" rows="2" placeholder="Título exibido na thumb"></textarea>
                </div>
                <div id="artigoConfigNotice" class="config-notice hidden"></div>
                <div id="artigoConfigDestaques" class="config-options-list"></div>

                <div class="modal-actions">
                    <button id="artigoConfigCancel" class="btn btn-secondary">Fechar</button>
                    <button id="artigoConfigSave" class="btn btn-primary">Salvar Configurações</button>
                </div>
            </div>
        </div>
    </template>

    <template id="spaAdminTemplate">
        <div class="container">
            <main class="main-content">
                <aside class="sidebar">
                    <div class="sidebar-header">
                        <div class="sidebar-top-row">
                            <input id="adminSearch" class="search-input" type="text" placeholder="Pesquisar configuração...">
                        </div>
                    </div>
                    <div id="adminOptionList" class="model-list admin-option-list">
                        <button class="model-name admin-option active" data-option="geral">Geral</button>
                    </div>
                </aside>

                <section class="editor admin-editor">
                    <div class="admin-panel-shell">
                        <div class="task-form admin-form">
                            <div class="form-grid">
                                <div class="admin-action-card">
                                    <div>
                                        <div id="toggleCacheLabel" class="admin-action-title">Cache desativado</div>
                                        <div id="toggleCacheHint" class="admin-action-description">Estado atual lido diretamente do arquivo de configuração.</div>
                                    </div>
                                    <button type="button" id="toggleCacheBtn" class="btn btn-secondary">Ativar cache</button>
                                </div>

                                <div class="admin-actions-grid">
                                    <button type="button" class="btn btn-secondary admin-command-btn" data-command="rebuild_all">Reconstruir TUDO</button>
                                    <button type="button" class="btn btn-secondary admin-command-btn" data-command="cache_templates">Cache Templates</button>
                                    <button type="button" class="btn btn-secondary admin-command-btn" data-command="ultimos_links">Últimos Links</button>
                                    <button type="button" class="btn btn-secondary admin-command-btn" data-command="compact_assets">Compactar JS/CSS</button>
                                    <button type="button" class="btn btn-secondary admin-command-btn" data-command="sitemap">Sitemap</button>
                                    <button type="button" class="btn btn-secondary admin-command-btn" data-command="clear_cache">Limpar Cache</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <div id="adminToastContainer" class="toast-container"></div>
    </template>

    <script src="js/launcher.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/app-modules.js"></script>
    <script src="js/artigos.js"></script>
    <script src="js/admin.js"></script>
    <script src="js/spa.js"></script>

</body>
</html>
