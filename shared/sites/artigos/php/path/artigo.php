<?php

$dados = prepare_select($db, "id, artigo, titulo, subtitulo, thumb, duracao, datePublished, dateModified, amp, keywords from links where path = ?", 's', $comando);

if ($dados) {
    require CAMINHO . '/comum/php/montador/pesquisa.php';
    require CAMINHO . '/comum/php/sistema/tool/texto.php';

    if ($this->amp) {
        $artigo_html = converte($dados['artigo'], false, true);
    } else {
        $artigo_html = converte($dados['artigo']);
    }

    if (! $this->cache->getCache()) {
        $pagina = '_artigo';
        $add    = '';
    } else {
        $pagina = 'artigo';
        #$add    = '<div class="divisor">' . adsense('article') . '</div>';
        $add    = '';
    }

    $amp_script = '';
    if ($this->amp) {
        if ($dados['duracao'] != '00:00:00') {
            $amp_script .= '<script async custom-element="amp-youtube" src="https://cdn.ampproject.org/v0/amp-youtube-0.1.js"></script>';
        }
    }

    $modulos = '<div class="interesse">Talvez seja de seu interesse</div>' . showRelatedLinks($db, $this->guardiao->getUrl());
//$modulos .= fb_comment(DNS_SITE . $this->url, FACEBOOK_ID);

    if ($dados['keywords'] != '') {
        //$modulos .= '<div id="keywords" class="divisor_fixo">' . showKeywords($dados['keywords']) . '</div>';
    }

//$youtube = '<div class="divisor white"><div id="rodape"><a href="http://www.youtube.com/' . YOUTUBE_CHANNEL . '" target="_blank"><img src="/cache/img/acesse.png"/></a><a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u=' . DNS_SITE . $this->url . '" target="_blank"><img src="/cache/img/facebook.png"/></a></div></div>';

//Coloca thumb personalizado na imagem de compartilhamento do facebook
    $image = thumbImage($dados['thumb']);

    if (strpos('.?!', substr($dados['subtitulo'], -1)) === false) {
        $dados['subtitulo'] .= '.';
    }

    $description = $dados['subtitulo'] . ' ' . $dados['keywords'];

    $structured = structured($dados['titulo'], $dados['datePublished'], $dados['dateModified'], $image, $description, $this->guardiao->getUrl());

//Colocando a data
    $timestamp = '<span>Publicado em ' . DateTime::createFromFormat("Y-m-d H:i:s", $dados['datePublished'])->format('d/m/Y') . '</span>';

    if ($dados['dateModified'] != $dados['datePublished']) {
        $timestamp .= '<span>Última atualização em ' . DateTime::createFromFormat("Y-m-d H:i:s", $dados['dateModified'])->format('d/m/Y \à\s H:i:s') . '</span>';
    }

    $contato = '<div class="contato"><b>Contato:</b> Caso você tenha identificado algum erro ou imprecisão no conteúdo acima, por gentileza, considere informar <a target="_blank" href="https://docs.google.com/forms/d/e/1FAIpQLScX2d2cqz6DoJQga6S988u9Ci895meVc5zNehAXcevhvfOpiw/viewform?entry.278388629=' . SITE . $this->guardiao->getUrl() . '">clicando aqui</a>. Você poderá utilizar o mesmo link caso queira entrar em contato por qualquer outro motivo.</div>';

    $dados_preparados = [
        //'searchscript' => "<script>function search(link){document.getElementById('gsc-i-id1').value = link.innerHTML;document.getElementsByClassName('gsc-search-button gsc-search-button-v2')[0].click();}</script>",
        'structured'  => $structured,
        'amp_script'  => $amp_script,
        'titulo'      => $dados['titulo'],
        'subtitulo'   => $dados['subtitulo'],
        'timestamp'   => $timestamp,
        'description' => $description,
        'image'       => $image,
        'artigo'      => $artigo_html . showTags() . $contato,
        'modulos'     => $add . $modulos,
        'fbID'        => FACEBOOK_ID,
        'sidebar'     => '<div class="divisor_fixo"><div id="arranhaceu">' . adsense('arranhaceu') . '</div></div><div class="divisor sticky20">' . file_get_contents(CAMINHO . '/cache/elementos/ultimos' . ($this->amp ? '_amp' : '')) . '</div>',
    ];

    if ($dados['amp'] == 0) {
        $dados_preparados['alternative_link'] = '';
    }

    $this->prepara($pagina, $dados_preparados);

} else {
    $this->localizaPath($comando);
}
