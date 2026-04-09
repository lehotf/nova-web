(function initAppUtils(global) {
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function sanitizeFilename(text, maxLength = 120) {
        return String(text || '')
            .replace(/[<>:"/\\|?*]/g, '')
            .replace(/[\x00-\x1F]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .substring(0, maxLength);
    }

    function getToastIcon(type) {
        switch (type) {
            case 'success':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-8.93"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
            case 'error':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
            default:
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
        }
    }

    function showToast({
        toastContainer,
        message,
        type = 'info',
        durationMs = 2800,
        removeDelayMs = 220
    }) {
        if (!toastContainer) return;

        toastContainer.innerHTML = '';

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `${getToastIcon(type)}<span>${escapeHtml(message)}</span>`;
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastSlideOut var(--transition-normal)';
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, removeDelayMs);
        }, durationMs);
    }

    function showDeleteConfirm({
        modal,
        titleElement,
        messageElement,
        actionElement,
        title,
        message,
        onEscape = null
    }) {
        if (titleElement) titleElement.textContent = title || '';
        if (messageElement) messageElement.textContent = message || '';
        openModal({
            modal,
            focusTarget: actionElement,
            focusDelayMs: 40,
            closeOnEscape: true,
            onEscape
        });
    }

    function closeDeleteConfirm(modal) {
        closeModal(modal);
    }

    function rememberModalFocus(modal, triggerElement = null) {
        if (!modal) return;
        const target = triggerElement instanceof HTMLElement
            ? triggerElement
            : document.activeElement;
        modal.__focusReturnTarget = target instanceof HTMLElement ? target : null;
    }

    function isElementFocusable(element) {
        if (!(element instanceof HTMLElement)) return false;
        if (element.hasAttribute('disabled')) return false;
        if (element.getAttribute('aria-hidden') === 'true') return false;
        if (element.tabIndex < 0) return false;
        return element.getClientRects().length > 0;
    }

    function getModalFocusableElements(modal) {
        if (!modal) return [];
        const selectors = [
            'a[href]',
            'button',
            'input',
            'select',
            'textarea',
            '[tabindex]'
        ].join(',');

        return Array.from(modal.querySelectorAll(selectors))
            .filter((element) => isElementFocusable(element));
    }

    function setupModalFocusTrap(modal, {
        closeOnEscape = false,
        onEscape = null
    } = {}) {
        if (!modal) return;

        if (typeof modal.__focusTrapCleanup === 'function') {
            modal.__focusTrapCleanup();
        }

        const keydownHandler = (event) => {
            if (!modal.classList.contains('hidden')) {
                if (event.key === 'Tab') {
                    const focusables = getModalFocusableElements(modal);
                    if (!focusables.length) {
                        event.preventDefault();
                        return;
                    }

                    const first = focusables[0];
                    const last = focusables[focusables.length - 1];
                    const active = document.activeElement;

                    if (event.shiftKey) {
                        if (active === first || !modal.contains(active)) {
                            event.preventDefault();
                            last.focus();
                        }
                        return;
                    }

                    if (active === last || !modal.contains(active)) {
                        event.preventDefault();
                        first.focus();
                    }
                    return;
                }

                if (event.key === 'Escape' && closeOnEscape) {
                    event.preventDefault();
                    if (typeof onEscape === 'function') {
                        onEscape();
                    } else {
                        closeModal(modal);
                    }
                }
            }
        };

        document.addEventListener('keydown', keydownHandler, true);
        modal.__focusTrapCleanup = () => {
            document.removeEventListener('keydown', keydownHandler, true);
            modal.__focusTrapCleanup = null;
        };
    }

    function teardownModalFocusTrap(modal) {
        if (!modal) return;
        if (typeof modal.__focusTrapCleanup === 'function') {
            modal.__focusTrapCleanup();
        }
    }

    function restoreModalFocus(modal) {
        if (!modal) return;
        const target = modal.__focusReturnTarget;
        modal.__focusReturnTarget = null;

        if (!(target instanceof HTMLElement)) return;
        if (!target.isConnected || target.hasAttribute('disabled')) return;

        setTimeout(() => {
            if (!target.isConnected || target.hasAttribute('disabled')) return;
            if (target.getClientRects().length === 0) return;
            target.focus();
        }, 0);
    }

    function openModal({
        modal,
        focusTarget = null,
        focusDelayMs = 0,
        rememberFocus = true,
        trapFocus = true,
        closeOnEscape = false,
        onEscape = null
    }) {
        if (!modal) return;
        if (rememberFocus) rememberModalFocus(modal);
        modal.classList.remove('hidden');
        if (trapFocus) {
            setupModalFocusTrap(modal, { closeOnEscape, onEscape });
        }
        if (focusTarget instanceof HTMLElement) {
            setTimeout(() => focusTarget.focus(), focusDelayMs);
        } else if (trapFocus) {
            setTimeout(() => {
                if (modal.classList.contains('hidden')) return;
                const active = document.activeElement;
                if (active && modal.contains(active)) return;
                const focusables = getModalFocusableElements(modal);
                if (focusables[0]) {
                    focusables[0].focus();
                }
            }, 0);
        }
    }

    function closeModal(modal, { restoreFocus = true } = {}) {
        if (!modal) return;
        teardownModalFocusTrap(modal);
        modal.classList.add('hidden');
        if (restoreFocus) restoreModalFocus(modal);
    }

    function bindModalListeners(bindings) {
        const safeBindings = Array.isArray(bindings) ? bindings : [];

        safeBindings.forEach(({ element, event, handler, options }) => {
            if (!element || !event || typeof handler !== 'function') return;
            element.addEventListener(event, handler, options);
        });

        return function cleanup() {
            safeBindings.forEach(({ element, event, handler, options }) => {
                if (!element || !event || typeof handler !== 'function') return;
                element.removeEventListener(event, handler, options);
            });
        };
    }

    global.AppUtils = {
        bindModalListeners,
        closeDeleteConfirm,
        closeModal,
        escapeHtml,
        getToastIcon,
        openModal,
        rememberModalFocus,
        restoreModalFocus,
        sanitizeFilename,
        showDeleteConfirm,
        showToast
    };
})(window);
