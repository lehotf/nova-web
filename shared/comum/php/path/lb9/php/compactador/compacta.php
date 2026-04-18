<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
require __DIR__ . '/conversorDePath.php';
require __DIR__ . '/compactaCSS.php';
require __DIR__ . '/compactaJS.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);
$o = $c->observador;

const ALTERA_TUDO = false;

$modificado = '';

function registraModificado($file)
{
    global $modificado;

    if ($modificado != '') {
        $modificado = $modificado . ", ";
    } else {
        $modificado = $modificado . "[";
    }

    $modificado = $modificado . $file;
}

function save($file, $origem)
{
    global $modificado;

    $tipo    = pathinfo($file, PATHINFO_EXTENSION);
    $destino = pathExtendedToCache($origem . '/' . $file, $tipo);
    $destinoDir = dirname($destino);

    switch ($tipo) {
        case 'js':
            $conteudo = compactaJS($origem . "/$file");
            break;

        case 'css':
            $conteudo = compactaCSS($origem . "/$file");
            break;

        default:
            return;
    }

    if (!file_exists($destinoDir)) {
        mkdir($destinoDir, 0755, true);
    }

    file_put_contents($destino, $conteudo);
    registraModificado($file);
}

function verifica($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $ffs = scandir($dir);

    foreach ($ffs as $file) {
        if ($file != '.' && $file != '..') {
            if (is_dir($dir . '/' . $file)) {
                verifica($dir . '/' . $file);
            } else {
                $extensao = pathinfo($file, PATHINFO_EXTENSION);
                if (!in_array($extensao, ['css', 'js'], true)) {
                    continue;
                }

                $destino = pathExtendedToCache($dir . '/' . $file);
                if ((!file_exists($destino)) || (filemtime($dir . '/' . $file) > filemtime($destino)) || ALTERA_TUDO) {
                    save($file, $dir);
                }
            }
        }
    }
}


verifica($_SERVER['DOCUMENT_ROOT'] . '/comum/estatico/js');
verifica($_SERVER['DOCUMENT_ROOT'] . '/comum/estatico/css');

if ($modificado != '') {
    $modificado = $modificado . ']';
    $msg        = "Arquivo(s) $modificado compactado(s).";
} else {
    $msg = "Nenhum arquivo precisou ser compactado.";
}

$o->envia($msg, 'ok');
