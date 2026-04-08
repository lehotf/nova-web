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
                _m(retorno.cabecalho.msg, retorno.cabecalho.status, 1);
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
 * Emite mensagem de erro/sucesso usando a infraestrutura global, quando existir.
 * Se não existir, faz fallback para `console`.
 *
 * @param {string} message Mensagem a ser exibida
 * @param {string} status Status legado (`ok`, `erro`, etc.)
 * @param {number} legacyFlag Parâmetro legado opcional
 */
function notifySendMessage(message, status, legacyFlag) {
    if (typeof _m === 'function') {
        _m(message, status, legacyFlag);
        return;
    }

    if (status === 'erro') {
        console.error(message);
        return;
    }

    console.log(message);
}
