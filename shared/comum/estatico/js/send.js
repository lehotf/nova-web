/**
 * Envia e recebe dados via XMLHttpRequest.
 *
 * Compatibilidade mantida com a API antiga:
 * - `a`: ação relativa. Ex.: `comum/autenticacao/login`
 * - `f`: callback de retorno
 * - `dados`: payload enviado ao servidor
 * - `p`: parâmetro livre legado, preservado sem uso interno
 * - `cache`: quando verdadeiro, consulta/salva resposta no cache legado
 * - `tipo`: método HTTP legado
 * - `method`: método HTTP preferencial
 * - `file`: input[type=file] usado para upload
 * - `fileName`: nome opcional do arquivo principal no upload
 * - `r`: contexto (`this`) do callback
 * - `u`: ação quando não usar `a`
 * - `url`: URL absoluta/manual
 *
 * Comportamento:
 * - JSON é enviado com `Content-Type: application/json`
 * - respostas JSON inválidas são tratadas com erro amigável
 * - upload valida quantidade e presença de arquivos antes de enviar
 * - o objeto recebido em `param` não é mutado
 *
 * @param {Object} param Configuração do envio
 * @returns {XMLHttpRequest|null} Retorna o XHR criado ou `null` em retorno imediato por cache/erro de validação
 */
function send(param) {
    param = param || {};

    startSendProgress();

    var FILE_TYPE = '.php';
    var callback = typeof param.f === 'function'
        ? param.f
        : function(retorno) {
            if (retorno && retorno.cabecalho) {
                notifySendMessage(retorno.cabecalho.msg, retorno.cabecalho.status, 1);
            }
        };

    var callbackContext = param.r || null;
    var method = (param.method || param.tipo || 'POST').toUpperCase();
    var data = param.dados === undefined ? undefined : param.dados;
    var action = param.a || param.u || '';
    var resolved = resolveSendUrl(action, param.url, FILE_TYPE, data);
    var url = resolved.url;
    var payload = resolved.data;

    if (!url) {
        stopSendProgress();
        notifySendMessage('Destino de envio não informado.', 'erro', 1);
        return null;
    }

    if (param.cache && hasSendCache()) {
        var cacheDaPagina = cache.verifica(url, payload);
        if (cacheDaPagina) {
            invokeSendCallback(callback, callbackContext, cacheDaPagina);
            stopSendProgress();
            return null;
        }
    }

    var xhr = new XMLHttpRequest();
    xhr.timeout = 12000;

    xhr.onprogress = function(e) {
        updateProgressValue(e, 50, 50);
    };

    xhr.onload = function() {
        send_timer = setTimeout(function() {
            stopSendProgress();
        }, 3000);

        if (xhr.status !== 200) {
            notifySendMessage('Erro ' + xhr.status + ' recebido: ' + (xhr.statusText || 'falha na requisição'), 'erro');
            return;
        }

        if (xhr.responseText == null || xhr.responseText === '') {
            notifySendMessage('Falha na recepção de dados', 'erro');
            return;
        }

        var resposta;
        try {
            resposta = JSON.parse(xhr.responseText);
        } catch (erro) {
            console.error('Resposta inválida do servidor:', xhr.responseText, erro);
            notifySendMessage('O servidor respondeu em formato inválido.', 'erro', 1);
            return;
        }

        if (param.cache && hasSendCache()) {
            cache.salva(url, payload, resposta);
        }

        invokeSendCallback(callback, callbackContext, resposta);
    };

    xhr.onerror = function() {
        stopSendProgress();
        notifySendMessage('Falha de rede ao comunicar com o servidor.', 'erro', 1);
    };

    xhr.ontimeout = function() {
        stopSendProgress();
        notifySendMessage('Não foi possível comunicar com o servidor.', 'erro', 1);
    };

    if (param.file) {
        return sendFileRequest(xhr, method, url, payload, param.file, param.fileName);
    }

    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.send(JSON.stringify(payload === undefined ? {} : payload));

    return xhr;
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
 * Envia arquivos via FormData.
 *
 * Regras preservadas:
 * - aceita no máximo 2 arquivos
 * - se houver 2 arquivos, o maior continua indo em `img1`
 * - payload extra segue em `dados` serializado em JSON
 *
 * @param {XMLHttpRequest} xhr Instância da requisição
 * @param {string} method Método HTTP
 * @param {string} url Destino
 * @param {*} payload Dados auxiliares
 * @param {HTMLInputElement} fileInput Input de arquivo
 * @param {string} preferredFileName Nome opcional do arquivo principal
 * @returns {XMLHttpRequest|null}
 */
function sendFileRequest(xhr, method, url, payload, fileInput, preferredFileName) {
    var files = fileInput.files;

    if (!files || files.length === 0) {
        stopSendProgress();
        notifySendMessage('Selecione ao menos um arquivo.', 'erro', 1);
        return null;
    }

    if (files.length > 2) {
        stopSendProgress();
        notifySendMessage('Apenas é permitido selecionar até 2 arquivos', 'erro');
        return null;
    }

    xhr.upload.onprogress = function(e) {
        updateProgressValue(e, 0, 100);
    };

    var formData = new FormData();
    var orderedFiles = Array.prototype.slice.call(files);

    if (orderedFiles.length === 2 && orderedFiles[0].size < orderedFiles[1].size) {
        orderedFiles.reverse();
    }

    formData.append('img1', orderedFiles[0], preferredFileName || orderedFiles[0].name);
    if (orderedFiles[1]) {
        formData.append('img2', orderedFiles[1], orderedFiles[1].name);
    }

    if (payload !== undefined) {
        formData.append('dados', JSON.stringify(payload));
    }

    xhr.open(method, url, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.send(formData);

    return xhr;
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
