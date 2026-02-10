<?php

function converte_comando($texto)
{

    #convertendo todos os comandos inline
    $texto_anterior = '';
    $contador       = 0;
    while ($texto_anterior != $texto) {

        $texto_anterior = $texto;

        $texto = preg_replace_callback('#(\w*)\[([^\[\]]*)\]#', function ($match) {
            return converte_callback_comando($match);
        }, $texto);

        $contador++;
        if ($contador > 9) {
            die('Problema ao converter texto');
        }
    }
    #convertendo todos os comandos inline
    return $texto;
}

function coloca_linha_geral($texto)
{
    $linhas        = explode("\n", $texto);
    $texto_retorno = "";
    foreach ($linhas as $linha) {
        if (preg_match("/(?:^\<\/?(?:div|h1|h2|h3|blockquote|pre|amp))/", $linha)) {
            $texto_retorno .= $linha;
        } else {
            $texto_retorno .= "<p>" . $linha . "</p>";
        }
    }

    return $texto_retorno;
}

function coloca_linha_local($texto, $ignore = false)
{
    $linhas        = explode("\n", $texto);
    $texto_retorno = "";
    foreach ($linhas as $linha) {
        if ($linha == "") {
            continue;
        }
        if ($ignore) {
            $ignore = false;
            $texto_retorno .= $linha;
        } else {
            $texto_retorno .= "<p>" . $linha . "</p>";
        }

    }

    return $texto_retorno;
}

function converte($texto, $interno = false, $isAmp = false)
{
    global $amp;
    $amp = $isAmp;

    inicializa();
    $texto = trim($texto);

    #substituindo == por ¬
    #$texto = str_replace('==', '¬', $texto);

    #identificando as linhas
    if ($interno) {
        $texto = preg_replace('#(?:\\n)+#', "\n", $texto);
    } else {
        $texto = preg_replace('#(?:\\\\n)+#', "\n", $texto);
    }
    #identificando as linhas

    $texto = converte_comando($texto);
    monta_indice();
    $texto = preg_replace('@[\s\n]*#indice[\s\n]*@', finaliza_indice(), $texto);
    $texto = coloca_linha_geral($texto);

    return $texto;
}

function converte_callback_comando($match)
{
    $texto = $match[2];
    $comando = $match[1];

    $handlers = [
        '' => 'comando_padrao',
        'a' => 'comando_a',
        'add' => 'comando_add',
        'alerta' => 'comando_alerta',
        'aspas' => 'comando_aspas',
        'at' => 'comando_at',
        'at2' => 'comando_at2',
        'atencao' => 'comando_atencao',
        'b' => 'comando_b',
        'bl' => 'comando_bl',
        'code' => 'comando_code',
        'colchete' => 'comando_colchete',
        'd' => 'comando_d',
        'duvida' => 'comando_duvida',
        'enum' => 'comando_enum',
        'img' => 'comando_img',
        'link' => 'comando_link',
        'mono' => 'comando_mono',
        'nota' => 'comando_nota',
        'tabela' => 'comando_tabela',
        't' => 'comando_t',
        't2' => 'comando_t2',
        'u' => 'comando_u',
        'v' => 'comando_v',
        'video' => 'comando_video',
        'z' => 'comando_z',
    ];

    if (isset($handlers[$comando])) {
        $funcao = $handlers[$comando];
        return $funcao($texto);
    }

    return $comando . "[$texto]";
}

function comando_a($texto)
{
    return '<span class="amarelo">' . $texto . '</span>';
}

function comando_add($texto)
{
    return '<div class="add_article">' . adsense('article') . '</div>';
}

function comando_alerta($texto)
{
    return '<blockquote class="blv">' . coloca_linha_local($texto, true) . '</blockquote>';
}

function comando_aspas($texto)
{
    return '<blockquote>' . coloca_linha_local($texto) . '</blockquote>';
}

function comando_at($texto)
{
    return "<h2>$texto</h2>";
}

function comando_at2($texto)
{
    return "<h3>$texto</h3>";
}

function comando_atencao($texto)
{
    return '<div class="atencao"><span>Atenção:</span>' . coloca_linha_local($texto, true) . '</div>';
}

function comando_b($texto)
{
    return "<b>$texto</b>";
}

function comando_bl($texto)
{
    return comando_aspas($texto);
}

function comando_code($texto)
{
    return '<pre>' . coloca_linha_local($texto) . '</pre>';
}

function comando_colchete($texto)
{
    return '[' . $texto . ']';
}

function comando_d($texto)
{
    return '<span class="destaque">' . $texto . '</span>:';
}

function comando_duvida($texto)
{
    return '<div class="duvida"><span>Dúvida:</span>' . coloca_linha_local($texto, true) . '</div>';
}

function comando_enum($texto)
{
    return '<div class="enum">' . coloca_linha_local($texto) . '</div>';
}

function comando_img($texto)
{
    global $amp;
    if ($amp) {
        $imgsize = getimagesize($_SERVER['DOCUMENT_ROOT'] . '/cache/img/upload/a/' . $texto . '.jpg');
        return '<div class="img_container"><amp-img width="' . $imgsize[0] . '" height="' . $imgsize[1] . '" layout="responsive" src="/cache/img/upload/a/' . $texto . '.jpg"></amp-img></div>';
    }
    return '<div class="img_container"><img src="/cache/img/upload/a/' . $texto . '.jpg"></div>';
}

function comando_link($texto)
{
    $link = explode(',', $texto);
    if (count($link) > 1) {
        return '<a href="' . trim($link[0]) . '" target="_blank" rel="noopener noreferrer">' . trim($link[1]) . '</a>';
    }
    return '<a href="' . $texto . '" target="_blank" rel="noopener noreferrer">' . str_replace('http://', '', $texto) . '</a>';
}

function comando_mono($texto)
{
    return '<span class="monospace">' . $texto . '</span>';
}

function comando_nota($texto)
{
    return '<div class="nota"><span>Nota:</span>' . coloca_linha_local($texto, true) . '</div>';
}

function comando_padrao($texto)
{
    $link = explode(',', $texto);
    if (count($link) > 1) {
        return '<a href="/' . url_amigavel(trim($link[0])) . '">' . trim($link[1]) . '</a>';
    }
    return '<a href="/' . url_amigavel($texto) . '">' . $texto . '</a>';
}

function comando_t($texto)
{
    global $codigo;
    $codigo[0]++;
    $codigo[1] = 0;
    adiciona_vetor_indice($texto, $codigo);
    return "<h2 id=\"titulo" . $codigo[0] . '.' . $codigo[1] . "\">$texto</h2>";
}

function comando_t2($texto)
{
    global $codigo;
    if ($codigo[0] == 0) {
        $codigo[0] = 1;
    }
    $codigo[1]++;
    adiciona_vetor_indice($texto, $codigo);
    return "<h3 id=\"titulo" . $codigo[0] . '.' . $codigo[1] . "\">$texto</h3>";
}

function comando_tabela($texto)
{
    return tabela($texto);
}

function comando_u($texto)
{
    return '<span class="underline">' . $texto . '</span>';
}

function comando_v($texto)
{
    return '<span class="verde">' . $texto . '</span>';
}

function comando_video($texto)
{
    global $amp;
    $video = explode(',', $texto);
    if (count($video) > 1) {
        return '<div class="videoWrapper"><iframe allowfullscreen="" frameborder="0" src="https://www.' . trim($video[1]) . '.com/embed/' . trim($video[0]) . '"></iframe></div>';
    }
    if ($amp) {
        return '<amp-youtube data-videoid="' . $texto . '" layout="responsive" width="480" height="270"></amp-youtube>';
    }
    return '<div class="videoWrapper"><iframe allowfullscreen="" frameborder="0" src="https://www.youtube.com/embed/' . $texto . '"></iframe></div>';
}

function comando_z($texto)
{
    return '<span class="azul">' . $texto . '</span>';
}

function monta_indice()
{
    global $indice;
    global $indice_vetor;
    global $codigo;
    global $titulobloco;

    if (count($indice_vetor) == 0) {
        return;
    }

    $simplificado = ($codigo[0] == 1);

    if ($simplificado) {
        $indice .= '<div class="titulobloco">';
        $titulobloco = 1;
    } else {
        $primeiro_elemento = $indice_vetor[0];
        //Verifica se é um tópico titulobloco
        if ($primeiro_elemento[1][1] != 0) {
            adiciona_indice('Introdução', [1, 0], false);
        }
    }

    foreach ($indice_vetor as $titulo) {
        adiciona_indice($titulo[0], $titulo[1], $simplificado);
    }
}

function finaliza_indice()
{
    global $indice;
    global $titulobloco;

    if ($titulobloco == 1) {
        $indice .= '</div>';
    }

    if ($indice != '') {
        return '<div id="indice">' . $indice . '</div>';
    } else {
        return '';
    }
}

function adiciona_vetor_indice($texto, $codigo)
{
    global $indice_vetor;
    array_push($indice_vetor, [$texto, $codigo]);
}

function adiciona_indice($texto, $codigo, $simplificado)
{
    global $indice;
    global $titulobloco;

    if ($simplificado) {
        $codigo_exibido = $codigo[1];
    } else {
        $codigo_exibido = $codigo[0] . '.' . $codigo[1];
    }

    if ($codigo[1] == 0) {
        //Estamos em um início de bloco
        $num = "<b>" . $codigo[0] . "</b>";
        if ($titulobloco == 1) {
            $indice .= '</div>';
        }
        $titulobloco = 1;
        $indice .= '<div class="titulobloco"><a class="indiceT1" href="#titulo' . $codigo[0] . '.' . $codigo[1] . '">' . $num . ' - ' . $texto . '</a>';
    } else {
        $num = "<b>" . $codigo_exibido . "</b>";
        $indice .= '<a class="indiceT2" href="#titulo' . $codigo[0] . '.' . $codigo[1] . '">' . $num . ' - ' . $texto . '</a>';
    }
}

function tabela($texto)
{

    $config = explode('#', $texto);
    $style  = [];

    if (count($config) > 1) {
        $texto = $config[1];
        for ($z = 0; $z < strlen($config[0]); $z++) {
            switch ($config[0][$z]) {
                case 'R':
                    $style[$z] = ' class="tright"';
                    break;
                case 'C':
                    $style[$z] = ' class="tcenter"';
                    break;
            }
        }
    }

    $linhas = explode(';', $texto);

    $tabela = '<table>';
    foreach ($linhas as $linha) {
        $colunas = explode('/', $linha);
        $tabela .= '<tr>';
        for ($z = 0; $z < count($colunas); $z++) {
            $coluna = explode('|', $colunas[$z]);

            if (!isset($style[$z])) {
                $style[$z] = '';
            }

            if (count($coluna) > 1) {
                $tabela .= '<td class="fixedcol" colspan="' . trim($coluna[1]) . '">' . trim($coluna[0]) . '</td>';
            } else {
                $tabela .= '<td' . $style[$z] . '>' . trim($coluna[0]) . '</td>';
            }

        }
        $tabela .= '</tr>';
    }
    $tabela .= '</table>';

    return $tabela;
}

function inicializa()
{
    global $indice;
    global $codigo;
    global $titulobloco;
    global $indice_vetor;

    $indice       = '';
    $codigo[0]    = 0;
    $codigo[1]    = 0;
    $titulobloco  = 0;
    $indice_vetor = [];

}
