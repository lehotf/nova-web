/**
 * Envia dados usando Fetch API.
 *
 * Uso moderno:
 * - `await send('/api.php')`
 * - `await send('/api.php', { acao: 'listar' })`
 * - `await send('/api.php', formData)`
 * - `await send('/api.php', { acao: 'listar' }, { method: 'POST' })`
 *
 * Comportamento:
 * - usa `POST` por padrão
 * - envia objeto simples como JSON
 * - envia `FormData` sem sobrescrever o `Content-Type`
 * - lança erro em falha HTTP, rede ou resposta inválida
 * - aceita apenas respostas JSON no padrão atual do `observador`
 * - retorna o envelope normalizado (`ok`, `message`, `data`, `error`)
 *
 * @param {string} url Destino da requisição
 * @param {*} payload Payload JSON puro ou `FormData`
 * @param {Object} options Configurações de transporte
 * @returns {Promise<*>}
 */
async function send(url, payload, options) {
    var requestUrl = String(url || '').trim();
    var config = options || {};

    if (!requestUrl) {
        throw new Error('Destino de envio não informado.');
    }

    beginSendLoadingToast();
    try {
        var fetchOptions = buildSendFetchOptions(payload, config);
        var response = await fetch(requestUrl, fetchOptions);
        var parsedResponse = await parseSendFetchResponse(response);

        if (!response.ok) {
            throw createSendHttpError(response, parsedResponse);
        }

        return parsedResponse;
    } catch (error) {
        notifySendMessage(error.message || 'Falha no envio.', 'erro', 1);
        throw error;
    } finally {
        endSendLoadingToast();
    }
}

var sendLoadingToastCounter = 0;
var sendLoadingToastElement = null;
var sendLoadingToastHideTimer = null;
var sendLoadingToastRemoveTimer = null;

function beginSendLoadingToast() {
    if (typeof document === 'undefined' || !document.body) {
        return;
    }

    sendLoadingToastCounter += 1;

    if (sendLoadingToastCounter === 1) {
        showSendLoadingToast();
    }
}

function endSendLoadingToast() {
    if (typeof document === 'undefined' || !document.body) {
        return;
    }

    if (sendLoadingToastCounter > 0) {
        sendLoadingToastCounter -= 1;
    }

    if (sendLoadingToastCounter === 0) {
        hideSendLoadingToast();
    }
}

function showSendLoadingToast() {
    ensureSendToastStyles();

    var container = ensureSendLoadingToastContainer();
    if (!container) {
        return;
    }

    clearTimeout(sendLoadingToastHideTimer);
    clearTimeout(sendLoadingToastRemoveTimer);
    sendLoadingToastHideTimer = null;
    sendLoadingToastRemoveTimer = null;

    if (sendLoadingToastElement && sendLoadingToastElement.parentNode) {
        sendLoadingToastElement.style.animation = 'sendToastSlideIn 220ms ease';
        return;
    }

    var toast = document.createElement('div');
    toast.className = 'send-toast send-toast-info send-toast-loading';
    toast.innerHTML = getSendLoadingToastIcon() + '<span>conectando ao servidor</span>';
    container.appendChild(toast);
    sendLoadingToastElement = toast;
}

function hideSendLoadingToast() {
    if (!sendLoadingToastElement) {
        return;
    }

    clearTimeout(sendLoadingToastHideTimer);
    clearTimeout(sendLoadingToastRemoveTimer);
    sendLoadingToastHideTimer = null;
    sendLoadingToastRemoveTimer = null;

    var toast = sendLoadingToastElement;
    if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
    }

    sendLoadingToastElement = null;
}

function buildSendFetchOptions(payload, options) {
    var method = String(options.method || 'POST').toUpperCase();
    var headers = new Headers(options.headers || {});
    var body = null;

    headers.set('Accept', 'application/json');

    if (payload instanceof FormData) {
        body = payload;
    } else if (payload !== undefined) {
        body = JSON.stringify(payload);
        headers.set('Content-Type', 'application/json; charset=UTF-8');
    } else if (options.body !== undefined) {
        body = options.body;
    }

    return {
        method: method,
        headers: headers,
        body: body,
        cache: options.cache || 'no-store',
        credentials: options.credentials || 'same-origin'
    };
}

async function parseSendFetchResponse(response) {
    var contentType = String(response.headers.get('content-type') || '').toLowerCase();
    var text = await response.text();

    if (text === '') {
        throw new Error('O servidor respondeu sem corpo JSON.');
    }

    if (contentType.indexOf('application/json') === -1) {
        throw new Error('O servidor respondeu fora do contrato JSON do observador.');
    }

    try {
        return normalizeSendResponsePayload(JSON.parse(text));
    } catch (error) {
        console.error('Resposta inválida do servidor:', text, error);
        throw new Error('O servidor respondeu em formato inválido.');
    }
}

function normalizeSendResponsePayload(payload) {
    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
        throw new Error('Resposta fora do padrão do observador.');
    }

    if (typeof payload.ok !== 'boolean') {
        throw new Error('Resposta fora do padrão do observador.');
    }

    var data = payload.data && typeof payload.data === 'object' && !Array.isArray(payload.data)
        ? payload.data
        : {};
    var error = payload.error && typeof payload.error === 'object' && !Array.isArray(payload.error)
        ? payload.error
        : {};
    var message = typeof payload.message === 'string'
        ? payload.message
        : (typeof error.message === 'string' ? error.message : '');
    var normalized = {
        ok: payload.ok,
        message: message,
        data: data,
        error: error
    };

    return normalized;
}

function createSendHttpError(response, parsedResponse) {
    var backendMessage = '';

    if (parsedResponse && typeof parsedResponse === 'object' && !Array.isArray(parsedResponse)) {
        if (typeof parsedResponse.message === 'string' && parsedResponse.message.trim() !== '') {
            backendMessage = parsedResponse.message.trim();
        } else if (parsedResponse.error && typeof parsedResponse.error.message === 'string' && parsedResponse.error.message.trim() !== '') {
            backendMessage = parsedResponse.error.message.trim();
        }
    }

    var fallbackMessage = 'Erro ' + response.status + ' recebido: ' + (response.statusText || 'falha na requisição');
    var error = new Error(backendMessage || fallbackMessage);

    error.response = response;
    error.payload = parsedResponse;

    return error;
}

/**
 * Emite mensagem de erro/sucesso usando um toast global compartilhado.
 * Se o DOM não estiver disponível, faz fallback para `console`.
 *
 * @param {string} message Mensagem a ser exibida
 * @param {string} status Status legado (`ok`, `erro`, etc.)
 * @param {number} legacyFlag Parâmetro legado opcional
 */
function notifySendMessage(message, status, legacyFlag) {
    var normalizedMessage = String(message || '').trim();
    if (!normalizedMessage) {
        return;
    }

    if (typeof document !== 'undefined' && document.body) {
        showSendToast(normalizedMessage, mapSendMessageType(status));
        return;
    }

    if (status === 'erro') {
        console.error(normalizedMessage, legacyFlag);
        return;
    }

    console.log(normalizedMessage, legacyFlag);
}

function mapSendMessageType(status) {
    if (status === 'ok' || status === 'success' || status === 'sucesso') {
        return 'success';
    }

    if (status === 'erro' || status === 'error' || status === 'falha') {
        return 'error';
    }

    return 'info';
}

function showSendToast(message, type) {
    ensureSendToastStyles();

    var container = ensureSendToastContainer();
    if (!container) {
        return;
    }

    container.innerHTML = '';

    var toast = document.createElement('div');
    toast.className = 'send-toast send-toast-' + type;
    toast.innerHTML = getSendToastIcon(type) + '<span>' + escapeSendMessage(message) + '</span>';
    container.appendChild(toast);

    window.setTimeout(function() {
        toast.style.animation = 'sendToastSlideOut 220ms ease';
        window.setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 220);
    }, 2800);
}

function ensureSendToastContainer() {
    var container = document.getElementById('sendToastContainer');
    if (container) {
        return container;
    }

    if (!document.body) {
        return null;
    }

    container = document.createElement('div');
    container.id = 'sendToastContainer';
    container.className = 'send-toast-container';
    document.body.appendChild(container);

    return container;
}

function ensureSendLoadingToastContainer() {
    var container = document.getElementById('sendLoadingToastContainer');
    if (container) {
        return container;
    }

    if (!document.body) {
        return null;
    }

    container = document.createElement('div');
    container.id = 'sendLoadingToastContainer';
    container.className = 'send-toast-container send-toast-loading-container';
    document.body.appendChild(container);

    return container;
}

function ensureSendToastStyles() {
    if (document.getElementById('sendToastStyles')) {
        return;
    }

    var style = document.createElement('style');
    style.id = 'sendToastStyles';
    style.textContent = '' +
        '@keyframes sendToastSlideIn {' +
            'from { opacity: 0; transform: translateY(20px); }' +
            'to { opacity: 1; transform: translateY(0); }' +
        '}' +
        '@keyframes sendToastSlideOut {' +
            'from { opacity: 1; transform: translateY(0); }' +
            'to { opacity: 0; transform: translateY(20px); }' +
        '}' +
        '.send-toast-container {' +
            'position: fixed;' +
            'bottom: 32px;' +
            'left: 50%;' +
            'transform: translateX(-50%);' +
            'z-index: 3000;' +
            'display: flex;' +
            'flex-direction: column;' +
            'align-items: center;' +
            'pointer-events: none;' +
        '}' +
        '.send-toast {' +
            'background: rgba(19, 26, 37, 0.96);' +
            'backdrop-filter: blur(20px);' +
            'border: 1px solid rgba(255, 255, 255, 0.1);' +
            'border-radius: 18px;' +
            'padding: 14px 18px;' +
            'color: #f5f7fb;' +
            'box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);' +
            'animation: sendToastSlideIn 220ms ease;' +
            'pointer-events: auto;' +
            'display: flex;' +
            'align-items: center;' +
            'gap: 10px;' +
            'min-width: 300px;' +
            'max-width: min(500px, calc(100vw - 32px));' +
        '}' +
        '.send-toast-loading {' +
            'border-left: 4px solid #f3c969;' +
        '}' +
        '.send-toast-loading svg {' +
            'animation: sendToastSpin 900ms linear infinite;' +
        '}' +
        '.send-toast svg {' +
            'width: 20px;' +
            'height: 20px;' +
            'flex-shrink: 0;' +
        '}' +
        '@keyframes sendToastSpin {' +
            'from { transform: rotate(0deg); }' +
            'to { transform: rotate(360deg); }' +
        '}' +
        '.send-toast-success { border-left: 4px solid #7ee0a0; }' +
        '.send-toast-error { border-left: 4px solid hsl(0, 75%, 60%); }' +
        '.send-toast-info { border-left: 4px solid #62d0ff; }';
    document.head.appendChild(style);
}

function escapeSendMessage(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function getSendToastIcon(type) {
    switch (type) {
        case 'success':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-8.93"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        case 'error':
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        default:
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
    }
}

function getSendLoadingToastIcon() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8" opacity="0.25"></circle><path d="M20 12a8 8 0 0 0-8-8"></path></svg>';
}
