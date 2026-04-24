/**
 * Envia dados usando Fetch API.
 *
 * Uso moderno:
 * - `await send('/api.php')`
 * - `await send('/api.php', { acao: 'listar' })`
 * - `await send('/api.php', { formData })`
 *
 * Comportamento:
 * - usa `POST` por padrão
 * - envia `dados` como JSON
 * - envia `formData` sem sobrescrever o `Content-Type`
 * - lança erro em falha HTTP, rede ou resposta inválida
 * - retorna o corpo já processado (`json` ou `text`)
 *
 * @param {string} url Destino da requisição
 * @param {*} payloadOrOptions Payload JSON puro ou configuração de envio
 * @returns {Promise<*>}
 */
async function send(url, payloadOrOptions) {
    var requestUrl = String(url || '').trim();
    var config = normalizeSendConfig(payloadOrOptions);

    if (!requestUrl) {
        throw new Error('Destino de envio não informado.');
    }

    startSendProgress();

    try {
        var fetchOptions = buildSendFetchOptions(config);
        var response = await fetch(requestUrl, fetchOptions);

        if (!response.ok) {
            throw new Error('Erro ' + response.status + ' recebido: ' + (response.statusText || 'falha na requisição'));
        }

        return await parseSendFetchResponse(response);
    } catch (error) {
        notifySendMessage(error.message || 'Falha no envio.', 'erro', 1);
        throw error;
    } finally {
        stopSendProgress();
    }
}

function buildSendFetchOptions(options) {
    var method = String(options.method || options.tipo || 'POST').toUpperCase();
    var headers = new Headers(options.headers || {});
    var body = null;

    headers.set('Accept', 'application/json');

    if (options.formData instanceof FormData) {
        body = options.formData;
    } else if (options.dados !== undefined) {
        body = JSON.stringify(options.dados);
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

function normalizeSendConfig(payloadOrOptions) {
    var options = payloadOrOptions || {};

    if (options instanceof FormData) {
        return { formData: options };
    }

    if (!isSendOptionsObject(options)) {
        return { dados: options };
    }

    if (options.formData instanceof FormData || options.body !== undefined || options.method || options.tipo || options.headers || options.cache || options.credentials) {
        return options;
    }

    return { dados: options };
}

function isSendOptionsObject(value) {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
}

async function parseSendFetchResponse(response) {
    if (response.status === 204) {
        return null;
    }

    var contentType = String(response.headers.get('content-type') || '').toLowerCase();
    var text = await response.text();

    if (text === '') {
        return null;
    }

    if (contentType.indexOf('application/json') !== -1 || text.charAt(0) === '{' || text.charAt(0) === '[') {
        try {
            return JSON.parse(text);
        } catch (error) {
            console.error('Resposta inválida do servidor:', text, error);
            throw new Error('O servidor respondeu em formato inválido.');
        }
    }

    return text;
}

/**
 * Resolve a URL final do endpoint e normaliza o payload.
 *
 * Regras legadas:
 * - ações iniciadas por `comum/` apontam para `/comum/php/xhr/`
 * - demais ações usam `/site/php/`
 * - quando não existe `a`, usa `url` manual e injeta `xhr=1`
 *
 * @param {string} action Ação relativa
 * @param {string} manualUrl URL explícita
 * @param {string} fileType Sufixo do endpoint
 * @param {*} data Dados originais
 * @returns {{url: string, data: *}}
 */
function resolveSendUrl(action, manualUrl, fileType, data) {
    var normalizedData = cloneSendData(data);

    if (action) {
        if (/^comum\//.test(action)) {
            return {
                url: '/comum/php/xhr/' + action.replace(/^comum\//, '') + fileType,
                data: normalizedData
            };
        }

        return {
            url: '/site/php/' + action + fileType,
            data: normalizedData
        };
    }

    if (normalizedData === undefined || normalizedData === null || typeof normalizedData !== 'object' || Array.isArray(normalizedData)) {
        normalizedData = {};
    }
    normalizedData.xhr = 1;

    return {
        url: manualUrl || '',
        data: normalizedData
    };
}

/**
 * Cria uma cópia rasa do payload para evitar efeitos colaterais.
 *
 * @param {*} data Dados recebidos pela chamada
 * @returns {*} Clone seguro para uso interno
 */
function cloneSendData(data) {
    if (data === undefined) {
        return undefined;
    }

    if (data === null) {
        return null;
    }

    if (Array.isArray(data)) {
        return data.slice();
    }

    if (typeof data === 'object') {
        var cloned = {};
        for (var key in data) {
            if (Object.prototype.hasOwnProperty.call(data, key)) {
                cloned[key] = data[key];
            }
        }
        return cloned;
    }

    return data;
}

/**
 * Atualiza a barra de progresso quando o navegador informa o tamanho total.
 *
 * @param {ProgressEvent} event Evento de progresso
 * @param {number} base Percentual inicial
 * @param {number} scale Percentual máximo adicional
 */
function updateProgressValue(event, base, scale) {
    var progress = document.getElementById('progress_value');
    if (!progress || !event.lengthComputable || event.total <= 0) {
        return;
    }

    progress.style.width = (base + ((event.loaded / event.total) * scale)) + '%';
}

/**
 * Executa callback respeitando o contexto legado opcional.
 *
 * @param {Function} callback Função de retorno
 * @param {*} context Contexto `this`
 * @param {*} response Resposta recebida
 */
function invokeSendCallback(callback, context, response) {
    if (context) {
        callback.call(context, response);
        return;
    }

    callback(response);
}

/**
 * Envia multipart/form-data.
 *
 * @param {XMLHttpRequest} xhr Instância da requisição
 * @param {string} method Método HTTP
 * @param {string} url Destino
 * @param {*} payload Dados auxiliares
 * @param {Object} param Configuração original do envio
 * @returns {XMLHttpRequest|null}
 */
function sendMultipartRequest(xhr, method, url, payload, param) {
    var formData = param.formData instanceof FormData
        ? param.formData
        : buildSendFormData(payload, param);

    if (!formData) {
        return null;
    }

    xhr.upload.onprogress = function(e) {
        updateProgressValue(e, 0, 100);
    };

    xhr.open(method, url, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.send(formData);

    return xhr;
}

function buildSendFormData(payload, param) {
    var files = resolveSendFiles(param);
    if (!files.length) {
        stopSendProgress();
        notifySendMessage('Selecione ao menos um arquivo.', 'erro', 1);
        return null;
    }

    var formData = new FormData();
    appendPayloadToFormData(formData, payload);

    for (var i = 0; i < files.length; i++) {
        var item = files[i];
        formData.append(item.fieldName, item.file, item.fileName);
    }

    return formData;
}

function resolveSendFiles(param) {
    if (Array.isArray(param.files) && param.files.length) {
        return normalizeSendFiles(param.files);
    }

    if (!param.file || !param.file.files || !param.file.files.length) {
        return [];
    }

    var files = [];
    for (var i = 0; i < param.file.files.length; i++) {
        files.push({
            file: param.file.files[i],
            fieldName: param.fileFieldName || 'arquivo',
            fileName: i === 0 && param.fileName ? param.fileName : param.file.files[i].name
        });
    }

    return normalizeSendFiles(files);
}

function normalizeSendFiles(files) {
    var normalized = [];

    for (var i = 0; i < files.length; i++) {
        var item = files[i] || {};
        var file = item.file || null;

        if (!file) {
            continue;
        }

        normalized.push({
            file: file,
            fieldName: item.fieldName || 'arquivo',
            fileName: item.fileName || file.name
        });
    }

    return normalized;
}

function appendPayloadToFormData(formData, payload, prefix) {
    if (payload === undefined) {
        return;
    }

    if (payload === null) {
        formData.append(prefix || 'valor', '');
        return;
    }

    if (Array.isArray(payload)) {
        for (var i = 0; i < payload.length; i++) {
            appendPayloadToFormData(formData, payload[i], (prefix || 'itens') + '[]');
        }
        return;
    }

    if (typeof payload === 'object') {
        for (var key in payload) {
            if (!Object.prototype.hasOwnProperty.call(payload, key)) {
                continue;
            }

            var fieldName = prefix ? (prefix + '[' + key + ']') : key;
            appendPayloadToFormData(formData, payload[key], fieldName);
        }
        return;
    }

    formData.append(prefix || 'valor', String(payload));
}

function failSendRequest(errorCallback, context, message, extra) {
    stopSendProgress();
    notifySendMessage(message, 'erro', 1);

    if (!errorCallback) {
        return;
    }

    var payload = extra && typeof extra === 'object' ? cloneSendData(extra) : {};
    payload = payload || {};
    payload.message = message;

    invokeSendCallback(errorCallback, context, payload);
}

/**
 * Inicia o indicador visual de progresso se a implementação existir.
 */
function startSendProgress() {
    if (typeof create_line_progress === 'function') {
        create_line_progress();
    }
}

/**
 * Finaliza o indicador visual de progresso se a implementação existir.
 */
function stopSendProgress() {
    if (typeof hide_line_progress === 'function') {
        hide_line_progress();
    }
}

/**
 * Verifica se o objeto de cache legado está disponível.
 *
 * @returns {boolean}
 */
function hasSendCache() {
    return typeof cache === 'object'
        && cache !== null
        && typeof cache.verifica === 'function'
        && typeof cache.salva === 'function';
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
        '.send-toast svg {' +
            'width: 20px;' +
            'height: 20px;' +
            'flex-shrink: 0;' +
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
