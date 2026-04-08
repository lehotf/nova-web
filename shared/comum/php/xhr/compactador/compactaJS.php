<?php
set_time_limit(2);

$compatibilityChar = '';
$exception         = "#\b(?:this|document)\b#";
function montaOperator()
{
    global $operator;
    $aux = [
        '\+',
        '-',
        '<',
        '>',
        '\*',
        '=',
        '%',
        '\[',
        '\]',
        '\(',
        '\)',
        ':',
        '\|',
        '&',
        ',',
        '\?',
        ';',
        '{',
        '}',
        '\/',
        '!',
    ];
    $r = '';
    foreach ($aux as $item) {
        if ($r != '') {
            $r .= '|';
        }

        $r .= $item;
    }
    $operator = $r;
}
function adicionaVar($varTeste)
{
    global $var, $varAtual, $compatibilityChar, $exception;

    if ($varTeste == '') {
        return;
    }

    if (preg_match($exception, $varTeste)) {
        return;
    }

    if (isset($var[$varTeste])) {
        return;
    }

    $var[$varTeste] = $compatibilityChar . $varAtual++;
    return $var[$varTeste];
}
function verificaVar_callback($m2, $m3)
{
    if ($m2) {
        adicionaVar($m2);
    } else {
        if ($m3) {

            while (preg_match('#{[^{}]*}|\([^\(\)]*\)|\[[^\[\]]*\]#', $m3)) {
                $m3 = preg_replace('#{[^{}]*}|\([^\(\)]*\)|\[[^\[\]]*\]#', '', $m3);
            }

            $vetor = explode(',', $m3);
            foreach ($vetor as $item) {
                preg_match('#(?<!\[)\b\w+\b#', $item, $vetVar);
                if (isset($vetVar[0])) {
                    adicionaVar($vetVar[0]);
                }
            }
        }
    }
}
function verificaVar($t)
{
    preg_match_all('#((?:var|let|const)\s+(\b\w+\b)\s+in\s+[^\)]+|(?:var|let)\s([^;]+);)#', $t, $match);

    $tamanho = count($match[0]);
    for ($z = 0; $z < $tamanho; $z++) {
        verificaVar_callback($match[2][$z], $match[3][$z]);
    }
}
function replaceRegex_callback($m)
{

    $aux = explode('/g,', $m[0]);
    if (count($aux) > 1) {
        $ocorrencia = '';
        for ($z = 0; $z < count($aux); $z++) {
            if ($z < count($aux) - 1) {
                $aux2 = $aux[$z] . '/g,';
            } else {
                $aux2 = $aux[$z];
            }

            $ocorrencia .= preg_replace_callback('#(^|\[|\(|=)\/[^\n]*\/g?#', 'guarda_regex', $aux2);
        }
        return $ocorrencia;
    } else {
        return guarda_regex($m);
    }
}
function guarda_regex($m)
{
    global $regex;
    $num = count($regex);
    array_push($regex, $m[0]);
    return '[x' . $num . ']';
}
function replaceRegex($t)
{
    return preg_replace_callback('#(?<=[\[\(=])(?:\s*)\/[^\n]*\/#', 'replaceRegex_callback', $t);
}
function replaceText_callback($m)
{
    if ($m[0] == '""') {
        return $m[0];
    }

    global $text;
    $num = count($text);
    array_push($text, $m[0]);
    return '[t' . $num . ']';
}
function replaceText($t)
{
    return preg_replace_callback("#([\"'])(?:\\\\?.)*?\\1#", 'replaceText_callback', $t);
}
function removeChaves($m)
{
    global $chave;

    $num = count($chave);
    array_push($chave, $m[0]);
    return '[c' . $num . ']';
}
function removeFunction($m)
{
    global $funcao;

    $num = count($funcao);
    array_push($funcao, $m[0]);
    return '[f' . $num . ']';
}
function recolocaChaves_callback($m)
{
    global $chave;
    return $chave[$m[1]];
}
function recolocaChaves($t)
{
    $a = '';
    while ($a != $t) {
        $a = $t;
        $t = preg_replace_callback('#\[c(\d+)\]#', 'recolocaChaves_callback', $t);
    }
    return $t;
}
function removeFunctionChaves($m)
{
    global $funcao, $chaves;

    $t = preg_replace_callback('#\[c(\d+)\]#', 'recolocaChaves_callback', $m[0]);

    $num = count($funcao);
    array_push($funcao, $t);
    return '[f' . $num . ']';
}
function verificaEscopo(&$t)
{
    $r = '';
    while ($r != $t) {
        $r = $t;
        $t = preg_replace_callback('#function\s*[^\(]*\([^\)]*\)\s*\[[^\]]+\]#', 'removeFunctionChaves', $t);
        $t = preg_replace_callback('#function\s*[^\(]*\([^\)]*\)\s*{[^{}]*}#', 'removeFunction', $t);
        $t = preg_replace_callback('#{[^{}]*}#', 'removeChaves', $t);
    }
}
function r2f($m)
{
    global $funcao;
    verificaVar($funcao[$m[1]]);
    preg_match_all('#function\s*([^\(]*)\(([^\)]*)\)#', $funcao[$m[1]], $f);
    if ($f[1][0] != '') {
        adicionaVar($f[1][0]);
    }

    if ($f[2][0] != '') {
        verificaVar_callback(null, $f[2][0]);
    }

    return $funcao[$m[1]];
}
function recolocaFuncao_callback($m)
{
    global $funcao, $var;

    $var = [];

    $a = $funcao[$m[1]];
    verificaVar($a);

    $r = '';
    while ($r != $a) {
        $r = $a;
        $a = preg_replace_callback('#\[f(\d+)\]#', 'r2f', $a);
    }

    $a = replaceVar($a);

    return $a;
}
function recolocaFuncoes($t)
{
    $t = preg_replace_callback('#\[f(\d+)\]#', 'recolocaFuncao_callback', $t);

    return $t;
}
function reconstroiArquivo($t)
{
    global $funcao;

    foreach ($funcao as &$item) {
        $item = recolocaChaves($item);
    }

    $t = recolocaChaves($t);
    $t = recolocaFuncoes($t);

    return $t;
}
function replaceVar_callback($m)
{
    global $var;
    return $var[$m[0]];
}
function replaceVar($t)
{
    global $var;
    $texto = '';
    foreach ($var as $key => $value) {
        if ($texto != '') {
            $texto .= "|";
        }

        $texto .= $key;
    }

    if ($texto == '') {
        return $t;
    } else {
        $texto = '(' . $texto . ')';

        return preg_replace_callback('#(?<!\.)\b' . $texto . '\b#', 'replaceVar_callback', $t);
    }
}

function recolocaRegex_callback($m)
{
    global $regex;
    return $regex[$m[1]];
}
function recolocaRegex($t)
{
    return preg_replace_callback('#\[x(\d+)\]#', 'recolocaRegex_callback', $t);
}
function recolocaTexto_callback($m)
{
    global $text;
    return $text[$m[1]];
}
function recolocaTexto($t)
{
    return preg_replace_callback('#\[t(\d+)\]#', 'recolocaTexto_callback', $t);
}
function recolocaObjectKey_callback($m)
{
    global $objectKey;
    return trim($objectKey[$m[1]]);
}
function recolocaObjectKey($t)
{
    return preg_replace_callback('#\[o(\d+)\]#', 'recolocaObjectKey_callback', $t);
}
function replaceObjectKey_callback($m)
{
    global $objectKey;
    $num = count($objectKey);
    array_push($objectKey, $m[1]);
    return '[o' . $num . ']';
}
function replaceObjectKey($t)
{
    return preg_replace_callback('#(?<=(?:\{|,))\s*\b(\w+)\b\s*(?=:)#', 'replaceObjectKey_callback', $t);
}
function removeEspacos($t)
{
    global $operator;
    return preg_replace('#\s+(?=(?:' . $operator . '))|(?<=(?:' . $operator . '))\s+#', "", $t);
}
function compactaJS($arquivo)
{

    global $var, $regex, $text, $escopo, $function, $varAtual, $objectKey, $nivel, $funcao, $chave;

    $varAtual  = 'a';
    $regex     = [];
    $text      = [];
    $escopo    = [];
    $function  = [];
    $objectKey = [];
    $chave     = [];
    $funcao    = [];

    $handle = fopen($arquivo, "r") or die("Unable to open file!");
    $t      = fread($handle, filesize($arquivo));
    fclose($handle);

    montaOperator();
    $t = replaceRegex($t);

    $t = replaceText($t);
    $t = preg_replace('#\/\/.*[\n\r]|\/\*(?:[\s\S]*?)\*\/#', "", $t);

    $t = replaceObjectKey($t);

    $t = str_replace('\n', '', $t);
    $t = removeEspacos($t);
    $t = preg_replace('#(?<=else)\s+#', ' ', $t);

    verificaEscopo($t);

    $t = reconstroiArquivo($t);

    $t = recolocaRegex($t);
    $t = recolocaTexto($t);
    $t = recolocaObjectKey($t);
    return trim($t);
}
