<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);

$db = $c->db;
$o = $c->observador;

$links = $db->v_select(
    "CASE WHEN LEFT(path, 1) = '/' THEN path ELSE CONCAT('/', path) END AS path,
     id,
     thumb_titulo_html,
     duracao,
     titulo,
     thumb,
     subtitulo
     FROM links
     WHERE publicado = 1
       AND ultimos = 1
       AND thumb > 0
     ORDER BY datePublished DESC, id DESC
     LIMIT 2"
);

$cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/cache/elementos';
if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
    $o->erro('Não foi possível preparar o diretório de cache dos últimos links.');
}

$renderer = new monta_artigo($db, null, false);
$conteudo = renderUltimosHtml($links ?: [], $renderer, false);
$conteudoAmp = renderUltimosHtml($links ?: [], new monta_artigo($db, null, true), true);

if (file_put_contents($cacheDir . '/ultimos', $conteudo, LOCK_EX) === false) {
    $o->erro('Não foi possível gravar o cache dos últimos links.');
}

if (file_put_contents($cacheDir . '/ultimos_amp', $conteudoAmp, LOCK_EX) === false) {
    $o->erro('Não foi possível gravar o cache AMP dos últimos links.');
}

$quantidade = is_array($links) ? count($links) : 0;
$sufixo = $quantidade === 1 ? '' : 's';
$o->envia("Últimos links atualizados ({$quantidade} item{$sufixo}).", 'ok');

function renderUltimosHtml(array $links, monta_artigo $renderer, bool $amp): string
{
    $conteudo = '';

    foreach ($links as $link) {
        $path = (string) ($link['path'] ?? '/');
        if ($amp) {
            $path .= '/amp';
        }

        $conteudo .= '<div class="ultimos_artigos"><a href="' . $path . '#content">'
            . $renderer->image('c25', (string) ($link['thumb'] ?? ''), (string) ($link['duracao'] ?? ''), (string) ($link['titulo'] ?? ''), (string) ($link['thumb_titulo_html'] ?? ''))
            . '<div class="legenda"><div>' . (string) ($link['titulo'] ?? '') . '</div><div>' . (string) ($link['subtitulo'] ?? '') . '</div></div></a></div>';
    }

    return '<div id="ultimos_container">' . $conteudo . '</div>';
}
