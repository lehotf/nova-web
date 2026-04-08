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
    <link rel="icon" type="image/svg+xml" href="favicon-tarefas.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/launcher.css">
    <link rel="stylesheet" href="css/comum.css">
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
                                <div class="form-group">
                                    <label for="artigoData">Data</label>
                                    <input id="artigoData" type="date">
                                </div>
                            </div>

                            <!-- Linha 2: Subtítulo (50%) | Keywords (50%) -->
                            <div class="form-row-2col">
                                <div class="form-group">
                                    <label for="artigoSubtitulo">Subtítulo</label>
                                    <input id="artigoSubtitulo" type="text" placeholder="Subtítulo / meta description">
                                </div>
                                <div class="form-group">
                                    <label for="artigoKeywords">Keywords</label>
                                    <input id="artigoKeywords" type="text" placeholder="palavra1, palavra2, palavra3">
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
