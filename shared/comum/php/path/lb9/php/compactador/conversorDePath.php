<?php

function minPathExtend($arquivo, $tipo)
{
    preg_match('#^(comum|config)\/(.*)#', $arquivo, $nome);

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

function pathExtendedToCache($filePath, $tipo = null)
{
    if (!$tipo) {
        $tipo = pathinfo($filePath, PATHINFO_EXTENSION);
    }

    return preg_replace('#\/(?:(?:comum|site)\/estatico\/' . $tipo . '|\/config)#', "/cache/$tipo", $filePath);
}

function minPathToCache($filePath, $tipo)
{
    return '/cache/' . $tipo . '/' . preg_replace('#^(comum|config)\/#', '', $filePath) . '.' . $tipo;
}
