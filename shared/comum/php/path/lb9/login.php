<?php
$redirectPadrao = '/comum/php/path/lb9/a.php';
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
    <link rel="stylesheet" href="/comum/php/path/lb9/css/login.css">
</head>
<body>
    <main class="shell">
        <section class="panel">
            <div class="card">
                <h2>Entrar</h2>

                <form id="loginForm" novalidate>
                    <input type="text" id="login" name="login" autocomplete="username" placeholder="Login" autofocus required>

                    <input type="password" id="senha" name="senha" autocomplete="current-password" placeholder="Senha" required>

                    <button type="submit" id="submitBtn">Acessar painel</button>
                </form>
            </div>
        </section>
    </main>

    <script src="/comum/estatico/js/send.js"></script>
    <script src="/cache/js/md5.js"></script>
    <script>
        window.lb9LoginRedirect = <?php echo json_encode($redirect, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="/comum/php/path/lb9/js/login.js"></script>
</body>
</html>
