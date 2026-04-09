(function initLauncher() {
    const root = document.getElementById('appLauncher');
    if (!root) return;

    const modules = [
        {
            id: 'artigos',
            title: 'Artigos do Site',
            tooltip: 'Artigos',
            href: '#/artigos',
            icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="13" y2="17"></line></svg>'
        },
        {
            id: 'admin',
            title: 'Configurações',
            tooltip: 'Administração',
            href: '#/admin',
            icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33h.01A1.65 1.65 0 0 0 10 3.09V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>'
        }
    ];

    function getModuleFromHash() {
        const match = window.location.hash.match(/^#\/([a-z0-9_-]+)$/i);
        return match ? match[1] : '';
    }

    function getCurrentModule() {
        return getModuleFromHash() || modules[0].id;
    }

    root.innerHTML = modules
        .map((mod) => {
            const activeClass = mod.id === getCurrentModule() ? ' active' : '';
            return `<a class="launcher-item launcher-item-${mod.id}${activeClass}" href="${mod.href}" aria-label="${mod.title}" data-tooltip="${mod.tooltip}">${mod.icon}</a>`;
        })
        .join('');

    function updateActiveState() {
        const current = getCurrentModule();
        const links = root.querySelectorAll('.launcher-item');
        links.forEach((link) => {
            const isActive = link.classList.contains(`launcher-item-${current}`);
            link.classList.toggle('active', isActive);
        });
    }

    window.addEventListener('hashchange', updateActiveState);
})();
