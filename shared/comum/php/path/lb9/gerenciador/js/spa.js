(function initSpaRouter() {
    const getModuleClass = (name) => window.AppModules?.get(name) || null;

    const moduleRegistry = {
        artigos: {
            view: document.getElementById('spaArtigosView'),
            template: document.getElementById('spaArtigosTemplate'),
            constructor: getModuleClass('ArtigosApp'),
            instance: null
        }
    };

    function normalizeRoute() {
        const match = window.location.hash.match(/^#\/([a-z0-9_-]+)$/i);
        const moduleId = match ? match[1] : '';

        if (moduleRegistry[moduleId]) {
            return moduleId;
        }

        // Rota padrão: primeiro módulo registrado
        const defaultId = Object.keys(moduleRegistry)[0] || 'artigos';
        window.location.replace(`#/${defaultId}`);
        return defaultId;
    }

    function ensureModuleInstance(moduleId) {
        const entry = moduleRegistry[moduleId];
        if (!entry || entry.instance) return;
        if (!entry.view || !entry.template || typeof entry.constructor !== 'function') return;

        entry.view.innerHTML = '';
        entry.view.appendChild(entry.template.content.cloneNode(true));
        entry.instance = new entry.constructor(entry.view);
    }

    function setModuleActive(moduleId) {
        Object.keys(moduleRegistry).forEach((id) => {
            const instance = moduleRegistry[id].instance;
            if (instance && typeof instance.setActive === 'function') {
                instance.setActive(moduleId === id);
            }
        });
    }

    function renderView(moduleId) {
        Object.keys(moduleRegistry).forEach((id) => {
            const entry = moduleRegistry[id];
            if (entry.view) {
                entry.view.classList.toggle('hidden', moduleId !== id);
            }
        });
    }

    function renderModule(moduleId) {
        document.body.dataset.module = moduleId;
        ensureModuleInstance(moduleId);
        setModuleActive(moduleId);
        renderView(moduleId);
    }

    function syncRoute() {
        renderModule(normalizeRoute());
    }

    if (!window.location.hash) {
        const defaultId = Object.keys(moduleRegistry)[0] || 'artigos';
        window.location.replace(`#/${defaultId}`);
    }

    window.addEventListener('hashchange', syncRoute);
    syncRoute();
})();
