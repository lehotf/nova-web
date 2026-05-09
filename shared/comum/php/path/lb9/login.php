<?php
$redirectPadrao = '/comum/php/path/lb9/a.php';
$apiAutenticacao = '/comum/php/path/lb9/php/autenticacao';
$redirect = isset($_GET['redirect']) ? (string) $_GET['redirect'] : $redirectPadrao;
const LB9_LOGIN_ASSET_VERSION = '1.0.1';

function lb9LoginAssetVersion(string $path): string
{
    return $path . '?v=' . LB9_LOGIN_ASSET_VERSION;
}

if (!preg_match('#^/(?!/)[A-Za-z0-9/_?.=&-]*$#', $redirect)) {
    $redirect = $redirectPadrao;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LB9 | Login</title>
    <link rel="stylesheet" href="<?= lb9LoginAssetVersion('/comum/php/path/lb9/css/login.css') ?>">
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

    <script src="<?= lb9LoginAssetVersion('/comum/estatico/js/send.js') ?>"></script>
    <script>
        window.lb9LoginRedirect = <?php echo json_encode($redirect, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        window.lb9AuthApiBase = <?php echo json_encode($apiAutenticacao, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="<?= lb9LoginAssetVersion('/comum/php/path/lb9/js/login.js') ?>"></script>
</body>
</html>
