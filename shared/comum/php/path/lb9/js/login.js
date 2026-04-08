(function initLb9Login() {
    const form = document.getElementById('loginForm');
    const loginInput = document.getElementById('login');
    const senhaInput = document.getElementById('senha');
    const submitBtn = document.getElementById('submitBtn');

    if (!form || !loginInput || !senhaInput || !submitBtn) {
        return;
    }

    const redirect = window.lb9LoginRedirect || '/comum/php/path/lb9/gerenciador/a.php';

    function autenticarComCookie() {
        send({
            a: 'comum/autenticacao/cookie',
            method: 'POST',
            dados: {},
            f: function(payload) {
                const status = payload?.cabecalho?.status || '';
                const message = payload?.cabecalho?.msg || '';

                if (status === 'ok' || status === 'Autenticado' || message === 'Autenticado') {
                    window.location.href = redirect;
                }
            }
        });
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const login = loginInput.value.trim();
        const senha = senhaInput.value;

        if (!login || !senha) {
            notifySendMessage('Preencha login e senha.', 'erro');
            return;
        }

        submitBtn.disabled = true;

        send({
            a: 'comum/autenticacao/get_token',
            method: 'POST',
            dados: { login: login },
            f: function(tokenPayload) {
                const token = tokenPayload?.dados?.token;

                if (!token) {
                    notifySendMessage('Não foi possível iniciar a autenticação.', 'erro');
                    submitBtn.disabled = false;
                    return;
                }

                send({
                    a: 'comum/autenticacao/login',
                    method: 'POST',
                    dados: {
                        login: login,
                        senha: md5(md5(senha) + token)
                    },
                    f: function(payload) {
                        const message = payload?.cabecalho?.msg || 'Falha ao autenticar.';

                        if (message !== 'Autenticado') {
                            notifySendMessage(message, 'erro');
                            submitBtn.disabled = false;
                            senhaInput.select();
                            return;
                        }

                        notifySendMessage('Autenticado.', 'ok');
                        window.location.href = redirect;
                    }
                });
            }
        });
    });

    autenticarComCookie();
})();
