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
