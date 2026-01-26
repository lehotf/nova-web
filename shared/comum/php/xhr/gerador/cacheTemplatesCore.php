<?php

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

function templateDirs()
{
    return [
        $_SERVER['DOCUMENT_ROOT'] . '/site/php/template',
        $_SERVER['DOCUMENT_ROOT'] . '/comum/php/template',
    ];
}

function rootFiles($type)
{
    if ($type === 'amp') {
        return ['amp.html', 'index_amp.html', 'root.html', 'index.html'];
    }

    return ['root.html', 'index.html'];
}

function carregaTemplate($file)
{
    foreach (templateDirs() as $dir) {
        $conteudo = @file_get_contents($dir . '/' . $file);
        if ($conteudo !== false) {
            return $conteudo;
        }
    }

    return false;
}

function templateMTime($file)
{
    foreach (templateDirs() as $dir) {
        $time = @filemtime($dir . '/' . $file);
        if ($time) {
            return $time;
        }
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

function removeAnalytics($texto)
{
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
        $arquivo = carregaTemplate($file);
        if ($arquivo !== false) {
            break;
        }
    }

    if ($arquivo === false) {
        $arquivo = '[conteudo]';
    }

    $arquivo = compacta($arquivo);
    $arquivo = substituiAssets($arquivo, $debug);
    $arquivo = removeAnalytics($arquivo);

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
        $rootTime = max($rootTime, templateMTime($file));
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
    $arquivo = substituiAssets($arquivo, $debug);
    $arquivo = removeAnalytics($arquivo);

    if (!$parametro || array_search('noRoot', $parametro) === false) {
        $root = rootTemplate($type, $debug);
        $arquivo = str_replace('[conteudo]', $arquivo, $root);
    }

    $lista = $lista ? ($lista . ', ' . $file) : $file;
    file_put_contents($destino, $arquivo);
}
