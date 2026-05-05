(function registerAdminModule(global) {
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
            this.accessFiles = {
                acessos: '',
                acessos_negados: ''
            };
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
            this.loadTags();
            this.loadAccessLogs();
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
    }

    const mixins = global.LB9AdminMixins || {};
    Object.assign(
        AdminApp.prototype,
        mixins.shared || {},
        mixins.general || {},
        mixins.tags || {},
        mixins.access || {}
    );

    window.AppModules.register('AdminApp', AdminApp);
})(window);
