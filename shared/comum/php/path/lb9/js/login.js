(function initLb9Login() {
    const form = document.getElementById('loginForm');
    const loginInput = document.getElementById('login');
    const senhaInput = document.getElementById('senha');
    const submitBtn = document.getElementById('submitBtn');

    if (!form || !loginInput || !senhaInput || !submitBtn) {
        return;
    }

    const redirect = window.lb9LoginRedirect || '/comum/php/path/lb9/a.php';
    const apiBase = window.lb9AuthApiBase || '/comum/php/path/lb9/php/autenticacao';

    async function autenticarComCookie() {
        try {
            const payload = await send(`${apiBase}/cookie.php`, {});
            const status = payload?.ok;
            const message = payload?.message || '';

            if (status || message === 'Autenticado') {
                window.location.href = redirect;
            }
        } catch (error) {}
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const login = loginInput.value.trim();
        const senha = senhaInput.value;

        if (!login || !senha) {
            notifySendMessage('Preencha login e senha.', 'erro');
            return;
        }

        submitBtn.disabled = true;

        try {
            const tokenPayload = await send(`${apiBase}/get_token.php`, { login: login });
            const token = tokenPayload?.data?.token;

            if (!token) {
                notifySendMessage('Não foi possível iniciar a autenticação.', 'erro');
                submitBtn.disabled = false;
                return;
            }

            const payload = await send(`${apiBase}/login.php`, {
                login: login,
                senha: md5(md5(senha) + token)
            });
            const message = payload?.message || 'Falha ao autenticar.';

            if (!payload?.ok) {
                throw new Error(message);
            }

            notifySendMessage(message || 'Autenticado.', 'ok');
            window.location.href = redirect;
        } catch (error) {
            submitBtn.disabled = false;
            senhaInput.select();
            notifySendMessage(error.message || 'Falha ao autenticar.', 'erro');
        }
    });

    autenticarComCookie();
})();
