<?php

function cssParse($texto, &$elemento, &$vetorOrdenado)
{

    preg_match_all('#.+\{[^\}]*\}#', $texto, $m);

    $elemento = [];

    $vetor   = $m[0];
    $tamanho = count($vetor);
    for ($z = 0; $z < $tamanho; $z++) {
        preg_match('#(.*)\{([^\}]*)\}#', $vetor[$z], $i);
        $item     = explode(',', trim($i[1]));
        $atributo = explode(';', trim($i[2]));

        $numeroDeAtributos = count($atributo);
        $numeroDeItens     = count($item);

        for ($w = 0; $w < $numeroDeAtributos; $w++) {
            $atributo[$w] = trim($atributo[$w]);

            $atr    = explode(':', $atributo[$w]);
            $atr[0] = trim($atr[0]);
            if (isset($atr[1])) {
                $atr[1] = trim($atr[1]);
                $atr[1] = preg_replace('# *(,) *#', '$1', $atr[1]);
                $atr[1] = preg_replace('#\b0\.\b#', '.', $atr[1]);
            } else {
                $atr[1] = '';
            }
            if ($atr[0] != '') {
                for ($y = 0; $y < $numeroDeItens; $y++) {
                    $nomeDoElemento = preg_replace('# *(~|\+) *#', '$1', trim($item[$y]));

                    if (in_array($atr[0], ['padding', 'margin'], true)) {
                        $elemento[$nomeDoElemento][$atr[0] . '-left']   = $atr[1];
                        $elemento[$nomeDoElemento][$atr[0] . '-right']  = $atr[1];
                        $elemento[$nomeDoElemento][$atr[0] . '-top']    = $atr[1];
                        $elemento[$nomeDoElemento][$atr[0] . '-bottom'] = $atr[1];
                    } else {
                        $elemento[$nomeDoElemento][$atr[0]] = $atr[1];
                    }
                }
            }
        }
    }

    $vetorQuantidadeDeAtributos  = [];
    $quantidadeMaximaDeElementos = 0;
    foreach ($elemento as $nome => $e) {
        $pos = count($e);
        if ($pos > $quantidadeMaximaDeElementos) {
            $quantidadeMaximaDeElementos = $pos;
        }

        if (!isset($vetorQuantidadeDeAtributos[$pos])) {
            $vetorQuantidadeDeAtributos[$pos] = [];
        }
        array_push($vetorQuantidadeDeAtributos[$pos], $nome);
    }

    $vetorOrdenado = [];
    for ($z = $quantidadeMaximaDeElementos; $z > 0; $z--) {
        if (array_key_exists($z, $vetorQuantidadeDeAtributos)) {
            $quantidade = count($vetorQuantidadeDeAtributos[$z]);
            for ($x = 0; $x < $quantidade; $x++) {
                array_push($vetorOrdenado, $vetorQuantidadeDeAtributos[$z][$x]);
            }
        }
    }
}

function comparaElementos(&$elemento, &$vetorOrdenado, &$vetor_final, $margem)
{

    for ($z = 0; $z < count($vetorOrdenado); $z++) {
        $atributos                = [];
        $numAtributosEmComumFixed = 0;
        $elementoComparado1       = $vetorOrdenado[$z];

        $totalAtributos1 = count($elemento[$elementoComparado1]);
        if ($totalAtributos1 < $margem) {
            break;
        }
        if ($z < (count($vetorOrdenado) - 1)) {
            for ($x = $z + 1; $x < count($vetorOrdenado); $x++) {
                $elementoComparado2 = $vetorOrdenado[$x];
                $elementosEmComum   = [];

                if (count($atributos) > 0) {
                    $atributosComparados = $atributos;
                } else {
                    $atributosComparados = $elemento[$elementoComparado1];
                }

                foreach ($atributosComparados as $key => $value) {
                    if ((array_key_exists($key, $elemento[$elementoComparado2])) && ($elemento[$elementoComparado1][$key] == $elemento[$elementoComparado2][$key])) {
                        array_push($elementosEmComum, $key);
                    }
                }
                $numAtributosEmComum = count($elementosEmComum);
                if ($numAtributosEmComum == 0) {
                    break;
                }

                $totalAtributos2 = count($elemento[$elementoComparado2]);

                if ($totalAtributos2 < $numAtributosEmComumFixed) {
                    break;
                }

                if ($margem > -1) {
                    if (($totalAtributos1 - $numAtributosEmComum) > $margem) {
                        break;
                    }
                }

                if (($totalAtributos1 > $numAtributosEmComum) || ($totalAtributos2 > $numAtributosEmComum)) {
                    if ($elementosEmComum < 2) {
                        break;
                    } else {
                        $bytes1 = strlen($elementoComparado1) + strlen($elementoComparado2) + 3;
                        $bytes2 = 0;

                        for ($yy = 0; $yy < $numAtributosEmComum; $yy++) {
                            $atributo = $elementosEmComum[$yy] . ":" . $elemento[$elementoComparado1][$elementosEmComum[$yy]] . ";";

                            $bytes2 = $bytes2 + strlen($atributo) + 1;
                        }

                        if ($totalAtributos1 == $numAtributosEmComum) {
                            $bytes1 = $bytes1 - strlen($elementoComparado1) - 1;
                        }

                        if ($totalAtributos2 == $numAtributosEmComum) {
                            $bytes1 = $bytes1 - strlen($elementoComparado2) - 1;
                        }

                        if ($bytes1 >= $bytes2) {
                            break;
                        }
                    }
                }

                $array_element = [];
                if (count($atributos) > 0) {
                    array_push($vetor_final[count($vetor_final) - 1]['elemento'], $elementoComparado2);
                    foreach ($elementosEmComum as $key) {
                        unset($elemento[$elementoComparado2][$key]);
                    }
                } else {
                    $numAtributosEmComumFixed = $numAtributosEmComum;

                    foreach ($elementosEmComum as $key) {
                        if (!array_key_exists($key, $atributos)) {
                            $atributos[$key] = $elemento[$elementoComparado1][$key];
                        }
                        unset($elemento[$elementoComparado2][$key]);
                    }
                    $array_element['elemento'] = [$elementoComparado1, $elementoComparado2];
                    $array_element['atributo'] = $atributos;
                    array_push($vetor_final, $array_element);
                }

                if (count($elemento[$elementoComparado2]) == 0) {

                    unset($elemento[$elementoComparado2]);
                    array_splice($vetorOrdenado, $x, 1);
                    $x = $x - 1;
                }
            }
        }

        foreach ($atributos as $key => $valor) {
            unset($elemento[$elementoComparado1][$key]);
        }

        if (count($elemento[$elementoComparado1]) == 0) {
            unset($elemento[$elementoComparado1]);
            array_splice($vetorOrdenado, $z, 1);
            $x = $x - 1;
        }

    }
}

function comparaAtributos(&$elemento, &$atributo, &$vetor_final)
{

    foreach ($elemento as $nomeElemento => &$atributos) {

        if ((array_key_exists('margin-top', $atributos) + array_key_exists('margin-left', $atributos) + array_key_exists('margin-right', $atributos) + array_key_exists('margin-bottom', $atributos)) == 4) {
            atribute4values('margin', $atributos);
        }

        if ((array_key_exists('padding-top', $atributos) + array_key_exists('padding-left', $atributos) + array_key_exists('padding-right', $atributos) + array_key_exists('padding-bottom', $atributos)) == 4) {
            atribute4values('padding', $atributos);
        }

        foreach ($atributos as $nomeAtributo => $valorAtributo) {
            $atributoCompleto = "$nomeAtributo:$valorAtributo";
            if (array_key_exists($atributoCompleto, $atributo)) {
                array_push($atributo[$atributoCompleto], $nomeElemento);
            } else {
                $atributo[$atributoCompleto] = [$nomeElemento];
            }
        }
    }

    foreach ($atributo as $nomeAtributo => $vetorElemento) {

        if (count($vetorElemento) == 1) {
            continue;
        }

        $bytes2 = (strlen($nomeAtributo) + 1) * (count($vetorElemento) - 1);
        $bytes1 = 0;

        foreach ($vetorElemento as $nomeElemento) {
            $bytes1 = $bytes1 + strlen($nomeElemento);
        }

        $bytes1 = $bytes1 + 2;

        if ($bytes1 >= $bytes2) {
            continue;
        }

        $array_element             = [];
        $array_element['elemento'] = [];
        $atributoExploded          = explode(":", $nomeAtributo);

        foreach ($vetorElemento as $nomeElemento) {
            array_push($array_element['elemento'], $nomeElemento);
            unset($elemento[$nomeElemento][$atributoExploded[0]]);

            if (count($elemento[$nomeElemento]) == 0) {
                unset($elemento[$nomeElemento]);
            }
        }

        $array_element['atributo'][$atributoExploded[0]] = $atributoExploded[1];
        array_push($vetor_final, $array_element);
    }
}

function toVetorResultado(&$elemento, &$vetor_final, &$vetor_resultado)
{
    foreach ($elemento as $nomeElemento => $atributos) {
        $array_element = [
            'elemento' => [$nomeElemento],
            'atributo' => $atributos,
        ];

        array_push($vetor_resultado, $array_element);
    }

    foreach ($vetor_final as $item) {
        array_push($vetor_resultado, $item);
    }

    $elemento    = [];
    $vetor_final = [];

}

function font_face_escape($m)
{
    global $font_face_escaped;
    $font_face_escaped .= $m[0];
    return '';
}

function compactaCSS($arquivo)
{
    $handle = fopen($arquivo, "r") or die("Unable to open file!");
    $texto  = fread($handle, filesize($arquivo));
    fclose($handle);

    $texto = preg_replace_callback('#@font-face *{[^}]*\s*}#', 'font_face_escape', $texto);

    global $font_face_escaped;
    $font_face_escaped = preg_replace('#([{;:,])\s+#', "$1", $font_face_escaped);
    $font_face_escaped = preg_replace('#\s+([{;:,])#', "$1", $font_face_escaped);

    $texto = preg_replace('#\/\*(?:[\s\S]*?)\*\/#', "", $texto);

    $vetor_resultado_media = [];

    preg_match_all('#@media([^{]+)\{([\s\S]+?})\s*}#', $texto, $m);

    for ($z = 0; $z < count($m[1]); $z++) {

        $mediaElement = $m[1][$z];

        cssParse($m[2][$z], $elemento, $vetorOrdenado);
        $vetor_final = [];
        comparaElementos($elemento, $vetorOrdenado, $vetor_final, 0);
        comparaElementos($elemento, $vetorOrdenado, $vetor_final, 1);
        comparaElementos($elemento, $vetorOrdenado, $vetor_final, 2);
        comparaElementos($elemento, $vetorOrdenado, $vetor_final, -1);

        $atributo = [];
        comparaAtributos($elemento, $atributo, $vetor_final);

        $vetor_resultado = [];
        toVetorResultado($elemento, $vetor_final, $vetor_resultado);
        aprimora($vetor_resultado);

        $array_element = [
            'mediaElement'    => $mediaElement,
            'vetor_resultado' => $vetor_resultado,
        ];
        array_push($vetor_resultado_media, $array_element);
    }
    $texto = preg_replace('#@media([^{]+)\{([\s\S]+?})\s*}#', '', $texto);

    $vetor_final = [];
    cssParse($texto, $elemento, $vetorOrdenado);
    comparaElementos($elemento, $vetorOrdenado, $vetor_final, 0);
    comparaElementos($elemento, $vetorOrdenado, $vetor_final, 1);
    comparaElementos($elemento, $vetorOrdenado, $vetor_final, 2);

    $atributo = [];
    comparaAtributos($elemento, $atributo, $vetor_final);

    $vetor_resultado = [];
    toVetorResultado($elemento, $vetor_final, $vetor_resultado);
    aprimora($vetor_resultado);

    $texto = '';
    foreach ($vetor_resultado as $grupo) {
        $nome = '';
        foreach ($grupo['elemento'] as $nomeElemento) {
            if ($nome != '') {
                $nome = $nome . ',';
            }
            $nome = $nome . $nomeElemento;
        }

        $valor = '';
        foreach ($grupo['atributo'] as $nomeAtr => $valorAtr) {
            if ($valor != '') {
                $valor = $valor . ';';
            }
            $valor = $valor . $nomeAtr . ':' . $valorAtr;
        }

        $texto = $texto . $nome . '{' . $valor . '}';
    }

    foreach ($vetor_resultado_media as $media) {
        $texto           = $texto . '@media ' . trim($media['mediaElement']) . '{';
        $vetor_resultado = $media['vetor_resultado'];
        foreach ($vetor_resultado as $grupo) {
            $nome = '';
            foreach ($grupo['elemento'] as $nomeElemento) {
                if ($nome != '') {
                    $nome = $nome . ',';
                }
                $nome = $nome . $nomeElemento;
            }

            $valor = '';
            foreach ($grupo['atributo'] as $nomeAtr => $valorAtr) {
                $valor = $valor . $nomeAtr . ':' . $valorAtr . ';';
            }

            $texto = $texto . $nome . '{' . $valor . '}';
        }
        $texto = $texto . '}';
    }

    return $font_face_escaped . $texto;
}

function aprimora(&$vetor_resultado)
{

    foreach ($vetor_resultado as &$grupo) {
        $a = &$grupo['atributo'];

        foreach ($a as $nomeAtributo => $valor) {
            if (($valor == '0px') || ($valor == '0%')) {
                $a[$nomeAtributo] = '0';
            }

            if (isset($valor[0]) && ($valor[0] == '#')) {
                $a[$nomeAtributo] = preg_replace('#(\w)\1{5}#', '$1$1$1', $valor);
            }
        }

        if (isset($a['font-weight'])) {
            if ($a['font-weight'] == 'bold') {
                $a['font-weight'] = 700;
            }
            if ($a['font-weight'] == 'normal') {
                $a['font-weight'] = 400;
            }
        }

        if ((array_key_exists('margin-top', $a) + array_key_exists('margin-left', $a) + array_key_exists('margin-right', $a) + array_key_exists('margin-bottom', $a)) == 4) {
            atribute4values('margin', $a);
        }

        if ((array_key_exists('padding-top', $a) + array_key_exists('padding-left', $a) + array_key_exists('padding-right', $a) + array_key_exists('padding-bottom', $a)) == 4) {
            atribute4values('padding', $a);
        }

    }
}

function atribute4values($nome, &$a)
{
    $top = $a[$nome . '-top'];
    unset($a[$nome . '-top']);

    $left = $a[$nome . '-left'];
    unset($a[$nome . '-left']);

    $right = $a[$nome . '-right'];
    unset($a[$nome . '-right']);

    $bottom = $a[$nome . '-bottom'];
    unset($a[$nome . '-bottom']);

    if (($top == $left) && ($top == $right) && ($top == $bottom)) {
        $valor = $top;
    } else {
        if (($right == $left) && ($top == $bottom)) {
            $valor = "$top $left";
        } else {
            if ($right == $left) {
                $valor = "$top $left $bottom";
            } else {
                $valor = "$top $right $bottom $left";
            }
        }
    }

    $a[$nome] = $valor;
}
