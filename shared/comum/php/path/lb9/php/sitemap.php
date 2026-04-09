<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

$db = $c->db;
$o = $c->observador;

$links = $db->v_select(
    "CASE WHEN LEFT(path, 1) = '/' THEN path ELSE CONCAT('/', path) END AS path
     FROM links
     WHERE publicado = 1
     ORDER BY datePublished DESC, id DESC"
);

$linhas = [rtrim((string) DNS_SITE, '/')];

foreach ($links ?: [] as $link) {
    $path = trim((string) ($link['path'] ?? ''));
    if ($path === '' || strpos($path, '/tag/') === 0) {
        continue;
    }

    $linhas[] = rtrim((string) DNS_SITE, '/') . $path;
}

$linhas = array_values(array_unique($linhas));
$destino = $_SERVER['DOCUMENT_ROOT'] . '/sitemap.txt';

if (file_put_contents($destino, implode("\n", $linhas), LOCK_EX) === false) {
    $o->erro('Não foi possível gravar o sitemap do site.');
}

$total = count($linhas) - 1;
$sufixo = $total === 1 ? '' : 's';
$o->envia("Sitemap refeito com {$total} link{$sufixo}.", 'ok');
