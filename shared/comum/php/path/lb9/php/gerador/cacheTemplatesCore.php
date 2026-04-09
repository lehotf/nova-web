<?php

define('TEMPLATE_BASE_DIR', $_SERVER['DOCUMENT_ROOT'] . '/comum/php/template');
define('ROOT_TEMPLATE_DIR', $_SERVER['DOCUMENT_ROOT'] . '/config');

$lista = '';
$root = [];
$mudouRoot = false;

function compacta($texto)
{
    $texto = preg_replace('#\n#', '', $texto);
    $texto = preg_replace('#>[\s]+<#', '><', $texto);
    $texto = preg_replace('#\s\s+#', ' ', $texto);

    return $texto;
}

function rootFiles($type)
{
    if ($type === 'amp') {
        return ['amp.html', 'root.html'];
    }

    return ['root.html'];
}

function carregaTemplate($file)
{
    $conteudo = @file_get_contents(TEMPLATE_BASE_DIR . '/' . $file);
    if ($conteudo !== false) {
        return $conteudo;
    }

    return false;
}

function carregaRootTemplate($file)
{
    $conteudo = @file_get_contents(ROOT_TEMPLATE_DIR . '/' . $file);
    if ($conteudo !== false) {
        return $conteudo;
    }

    return false;
}

function templateMTime($file)
{
    $time = @filemtime(TEMPLATE_BASE_DIR . '/' . $file);
    if ($time) {
        return $time;
    }

    return 0;
}

function rootTemplateMTime($file)
{
    $time = @filemtime(ROOT_TEMPLATE_DIR . '/' . $file);
    if ($time) {
        return $time;
    }

    return 0;
}

function substituiAssets($texto, $debug)
{
    if ($debug) {
        return $texto;
    }

    return preg_replace('#(?:site|comum)\/estatico#', 'cache', $texto);
}

function removeConditionalBlocks($texto)
{
    if (!LOCALHOST) {
        return $texto;
    }

    return preg_replace('#<!--start-->[^!]*<!--end-->#', '', $texto);
}

function rootTemplate($type, $debug)
{
    global $root;
    if (isset($root[$type])) {
        return $root[$type];
    }

    $arquivo = false;
    foreach (rootFiles($type) as $file) {
        $arquivo = carregaRootTemplate($file);
        if ($arquivo !== false) {
            break;
        }
    }

    if ($arquivo === false) {
        $arquivo = '[conteudo]';
    }

    $arquivo = compacta($arquivo);
    $arquivo = removeConditionalBlocks($arquivo);
    $arquivo = substituiAssets($arquivo, $debug);
    $arquivo = resolveRootTerms($arquivo, $type, $debug);

    $root[$type] = $arquivo;
    return $arquivo;
}

function gera_root($debug, $forcar = false, $type = 'canonical')
{
    global $mudouRoot;

    $rootFile = ($type === 'amp') ? 'amp.html' : 'root.html';
    $cacheFile = $_SERVER['DOCUMENT_ROOT'] . '/cache/template/' . $rootFile;

    $rootTime = 0;
    foreach (rootFiles($type) as $file) {
        $rootTime = max($rootTime, rootTemplateMTime($file));
    }

    $cacheTime = @filemtime($cacheFile);
    $cacheTime = $cacheTime ? $cacheTime : 0;

    if (! $forcar && $rootTime && $rootTime <= $cacheTime) {
        return;
    }

    $mudouRoot = true;
    $conteudo = rootTemplate($type, $debug);
    file_put_contents($cacheFile, $conteudo);
}

function verifica($dir, $debug)
{
    global $mudouRoot;

    $items = @scandir($dir);
    if (!is_array($items)) {
        return;
    }
    foreach ($items as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            verifica($path, $debug);
            continue;
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== 'html') {
            continue;
        }

        if (in_array($file, ['root.html', 'amp.html', 'index_amp.html'], true)) {
            continue;
        }

        $cacheFile = $_SERVER['DOCUMENT_ROOT'] . '/cache/template/' . $file;
        $cacheTime = @filemtime($cacheFile);
        $cacheTime = $cacheTime ? $cacheTime : 0;

        if ($mudouRoot || filemtime($path) > $cacheTime) {
            gera($file, $dir, $debug);
            gera($file, $dir, $debug, 'amp');
        }
    }
}

function gera($file, $dir, $debug, $type = 'canonical')
{
    global $lista;

    $destino = $_SERVER['DOCUMENT_ROOT'] . '/cache/template/' . $file;
    if ($type === 'amp') {
        $destino = str_replace('.html', '_amp.html', $destino);
    }

    $arquivo = file_get_contents($dir . '/' . $file);
    if ($arquivo === false) {
        return;
    }

    $parametro = false;
    if (isset($arquivo[0]) && $arquivo[0] === '_') {
        $linha = strtok($arquivo, "\n");
        $arquivo = str_replace($linha . "\n", '', $arquivo);
        $parametro = explode(',', substr($linha, 1));
    }

    $arquivo = compacta($arquivo);
    $arquivo = removeConditionalBlocks($arquivo);
    $arquivo = substituiAssets($arquivo, $debug);
    if (!$parametro || array_search('noRoot', $parametro) === false) {
        $root = rootTemplate($type, $debug);
        $arquivo = str_replace('[conteudo]', $arquivo, $root);
    }

    $lista = $lista ? ($lista . ', ' . $file) : $file;
    file_put_contents($destino, $arquivo);
}

function resolveRootTerms($arquivo, $type, $debug)
{
    $termo = [
        'analytics' => LOCALHOST ? '' : ANALYTICS,
        'css_root' => buildRootCss($debug, $type),
        'js_root' => '',
        'destaque_tags' => buildTagLinks('nome, path FROM tags where destaque = 1 order by nome LIMIT 6', $type),
        'sidebar_tags' => buildTagLinks('nome, path FROM tags order by nome', $type),
    ];

    return preg_replace_callback('#\[([a-zA-Z_]{3,})\]#', function ($matches) use ($termo) {
        return array_key_exists($matches[1], $termo) ? $termo[$matches[1]] : $matches[0];
    }, $arquivo);
}

function buildRootCss($debug, $type)
{
    $arquivos = ['comum/fixo', 'comum/artigo'];

    if ($debug) {
        $codigo = '';
        foreach ($arquivos as $arquivo) {
            $codigo .= montaObjetoRoot(minPathExtendRoot($arquivo, 'css'), 'css');
        }
        return $codigo;
    }

    $css = '';
    foreach ($arquivos as $arquivo) {
        $path = $_SERVER['DOCUMENT_ROOT'] . minPathToCacheRoot($arquivo, 'css');
        $conteudo = @file_get_contents($path);
        if ($conteudo !== false) {
            $css .= $conteudo;
        }
    }

    if ($css === '') {
        return '';
    }

    return '<style' . ($type === 'amp' ? ' amp-custom' : '') . '>' . $css . '</style>';
}

function buildTagLinks($query, $type)
{
    $db = new database('localhost', BD_LOGIN, BD_SENHA, BD);
    $tags = $db->v_select($query);

    if ($tags === false || !is_array($tags)) {
        return '';
    }

    $html = '';
    foreach ($tags as $tag) {
        $nome = $tag['nome'] ?? '';
        $path = $tag['path'] ?? '';
        $html .= '<li><a href="/tag/' . $path . '#content">' . $nome . '</a></li>';
    }

    return $html;
}

function montaObjetoRoot($path, $tipo)
{
    if ($tipo === 'css') {
        return '<link rel="STYLESHEET" type="text/css" href="' . $path . '"/>';
    }

    return '<script src="' . $path . '"></script>';
}

function minPathExtendRoot($arquivo, $tipo)
{
    preg_match('#^(comum|config)/(.*)#', $arquivo, $nome);
    $path = (count($nome) > 1) ? $nome[1] : '';

    switch ($path) {
        case 'comum':
            return '/comum/estatico/' . $tipo . '/' . $nome[2] . '.' . $tipo;
        case 'config':
            return '/config/' . $nome[2] . '.' . $tipo;
        default:
            return '/site/estatico/' . $tipo . '/' . $arquivo . '.' . $tipo;
    }
}

function minPathToCacheRoot($filePath, $tipo)
{
    return '/cache/' . $tipo . '/' . preg_replace('#^(comum|config)/#', '', $filePath) . '.' . $tipo;
}
