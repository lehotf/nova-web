<?php
$redirectPadrao = '/comum/php/path/lb9/gerenciador/a.php';
$redirect = isset($_GET['redirect']) ? (string) $_GET['redirect'] : $redirectPadrao;

if (!preg_match('#^/[A-Za-z0-9/_?.=&-]*$#', $redirect)) {
    $redirect = $redirectPadrao;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LB9 | Login</title>
    <style>
        :root {
            color-scheme: dark;
            --bg-0: #08111f;
            --bg-1: #0d1728;
            --bg-2: #15233a;
            --line: rgba(163, 184, 213, 0.18);
            --text: #f5f7fb;
            --muted: #9aa8bf;
            --accent: #62d0ff;
            --accent-2: #3c8cff;
            --success: #7ee0a0;
            --danger: #ff8f8f;
            --shadow: 0 28px 90px rgba(0, 0, 0, 0.45);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", "Trebuchet MS", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(98, 208, 255, 0.2), transparent 34%),
                radial-gradient(circle at bottom right, rgba(60, 140, 255, 0.22), transparent 30%),
                linear-gradient(135deg, var(--bg-0), #050a12 58%, #0a1321);
            color: var(--text);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .shell {
            width: min(100%, 460px);
            background: rgba(8, 14, 24, 0.78);
            border: 1px solid var(--line);
            border-radius: 28px;
            overflow: hidden;
            backdrop-filter: blur(18px);
            box-shadow: var(--shadow);
        }

        .panel {
            padding: 42px 36px 34px;
            background: linear-gradient(180deg, rgba(12, 20, 34, 0.96), rgba(7, 12, 22, 0.98));
            display: flex;
            align-items: center;
        }

        .card {
            width: 100%;
        }

        .card h2 {
            margin: 0 0 24px;
            font-size: 29px;
            letter-spacing: -0.03em;
            text-align: center;
        }

        form {
            display: grid;
            gap: 18px;
        }

        label {
            display: grid;
            gap: 8px;
            font-size: 14px;
            color: #d9e4f5;
        }

        input {
            width: 100%;
            border: 1px solid rgba(154, 168, 191, 0.2);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            border-radius: 16px;
            padding: 15px 16px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.18s ease, transform 0.18s ease, background 0.18s ease;
        }

        input:focus {
            border-color: rgba(98, 208, 255, 0.7);
            background: rgba(255, 255, 255, 0.07);
            transform: translateY(-1px);
        }

        button {
            border: 0;
            border-radius: 16px;
            padding: 16px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            color: #05101c;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            box-shadow: 0 14px 28px rgba(60, 140, 255, 0.25);
            transition: transform 0.18s ease, filter 0.18s ease, opacity 0.18s ease;
        }

        button:hover:not(:disabled) {
            transform: translateY(-1px);
            filter: brightness(1.04);
        }

        button:disabled {
            opacity: 0.72;
            cursor: wait;
        }

        .feedback {
            min-height: 24px;
            font-size: 14px;
            color: var(--muted);
        }

        .feedback.error {
            color: var(--danger);
        }

        .feedback.success {
            color: var(--success);
        }

        @media (max-width: 920px) {
            .panel {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="panel">
            <div class="card">
                <h2>Entrar</h2>

                <form id="loginForm" novalidate>
                    <label>
                        Login
                        <input type="text" id="login" name="login" autocomplete="username" required>
                    </label>

                    <label>
                        Senha
                        <input type="password" id="senha" name="senha" autocomplete="current-password" required>
                    </label>

                    <button type="submit" id="submitBtn">Acessar painel</button>
                    <div id="feedback" class="feedback" aria-live="polite"></div>
                </form>
            </div>
        </section>
    </main>

    <script src="/comum/estatico/js/send.js"></script>
    <script src="/cache/js/md5.js"></script>
    <script>
        const form = document.getElementById('loginForm');
        const loginInput = document.getElementById('login');
        const senhaInput = document.getElementById('senha');
        const submitBtn = document.getElementById('submitBtn');
        const feedback = document.getElementById('feedback');
        const redirect = <?php echo json_encode($redirect, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

        function setFeedback(message, type = '') {
            feedback.textContent = message;
            feedback.className = `feedback${type ? ' ' + type : ''}`;
        }

        window._m = function(message, status) {
            setFeedback(message || '', status === 'ok' ? 'success' : 'error');
            if (status !== 'ok') {
                submitBtn.disabled = false;
            }
        };

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
                setFeedback('Preencha login e senha.', 'error');
                return;
            }

            submitBtn.disabled = true;
            setFeedback('Validando acesso...');

            send({
                a: 'comum/autenticacao/get_token',
                method: 'POST',
                dados: { login: login },
                f: function(tokenPayload) {
                    const token = tokenPayload?.dados?.token;

                    if (!token) {
                        setFeedback('Não foi possível iniciar a autenticação.', 'error');
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
                                setFeedback(message, 'error');
                                submitBtn.disabled = false;
                                senhaInput.select();
                                return;
                            }

                            setFeedback('Autenticado.', 'success');
                            window.location.href = redirect;
                        }
                    });
                }
            });
        });

        autenticarComCookie();
    </script>
</body>
</html>
