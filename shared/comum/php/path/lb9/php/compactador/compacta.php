<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/autoload.php';
require __DIR__ . '/conversorDePath.php';
require __DIR__ . '/compactaCSS.php';
require __DIR__ . '/compactaJS.php';

$c = new controlador(observador: true, autenticador: true);
$c->autenticador->acesso(2);
$o = $c->observador;

const ALTERA_TUDO = false;

$alterou_JS_root  = false;
$alterou_CSS_root = false;
$modificado       = '';

$termo = [
    'css_root' => ['comum/fixo', 'comum/artigo'],
    'js_root' => [],
];

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
    global $termo, $alterou_JS_root, $alterou_CSS_root;

    $tipo    = pathinfo($file, PATHINFO_EXTENSION);
    $destino = pathExtendedToCache($origem . '/' . $file, $tipo);
    $destinoDir = dirname($destino);

    switch ($tipo) {
        case 'js':
            if (isset($termo['js_root']) && in_array($origem . "/$file", $termo['js_root'], true)) {
                $alterou_JS_root = true;
            }
            $conteudo = compactaJS($origem . "/$file");
            break;

        case 'css':
            if (isset($termo['css_root']) && in_array($origem . "/$file", $termo['css_root'], true)) {
                $alterou_CSS_root = true;
            }
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

function monta_path($tipo)
{
    global $termo;

    if (isset($termo[$tipo . '_root'])) {
        foreach ($termo[$tipo . '_root'] as $pos => $arquivo) {
            $termo[$tipo . '_root'][$pos] = $_SERVER['DOCUMENT_ROOT'] . minPathExtend($arquivo, $tipo);
        }
    }
}

function monta_principal($tipo)
{
    global $termo;

    $conteudo = '';
    foreach ($termo[$tipo . '_root'] as $arquivo) {
        $destino = pathExtendedToCache($arquivo);
        if ($conteudo != '') {
            $conteudo = $conteudo . "\n";
        }

        $conteudo = $conteudo . file_get_contents($destino);
    }
    return $conteudo;
}

monta_path('js');
monta_path('css');

verifica($_SERVER['DOCUMENT_ROOT'] . '/config');
verifica($_SERVER['DOCUMENT_ROOT'] . '/comum/estatico/js');
verifica($_SERVER['DOCUMENT_ROOT'] . '/site/estatico/js');
verifica($_SERVER['DOCUMENT_ROOT'] . '/comum/estatico/css');
verifica($_SERVER['DOCUMENT_ROOT'] . '/site/estatico/css');

if ($modificado != '') {
    $modificado = $modificado . ']';
    $msg        = "Arquivo(s) $modificado compactado(s).";
} else {
    $msg = "Nenhum arquivo precisou ser compactado.";
}

if ($alterou_JS_root && !empty($termo['js_root'])) {
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/js/principal.js', monta_principal('js'));
    $msg = $msg . " RECRIADO: principal.js.";
}

if ($alterou_CSS_root && !empty($termo['css_root'])) {
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/css/principal.css', monta_principal('css'));
    $msg = $msg . " RECRIADO: principal.css.";
}

$o->envia($msg, 'ok');
