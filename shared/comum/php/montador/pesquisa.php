<?php
define("THUMB_PATH", "/cache/img/upload/t/");

function image($classe, $thumb, $duracao, $titulo, $thumb_titulo)
{
    global $amp;
    $duracao     = ($duracao != '00:00:00') ? '<div class="duracao">' . preg_replace('#^00.#', '', $duracao) . '</div>' : '';
    $imgTag      = $amp ? 'amp-img width="193" height="73" layout="responsive"' : 'img';
    $imgTagClose = $amp ? '</amp-img>' : '';

    if ($thumb_titulo != '') {
        if ($classe == 'c50') {
            $thumb_titulo = '<div class="t_th1">' . $thumb_titulo . '</div>';
        } else {
            $thumb_titulo = '<div class="t_th2">' . $thumb_titulo . '</div>';
        }
    }

    if ($thumb == '') {
        global $cor;
        if (!isset($cor)) {
            $cor = '';
        } else {
            $cor = ($cor == '') ? ' cor2' : '';
        }
        $img = '<div class="noimage' . $cor . '"><div>' . $titulo . '</div></div>';
    } else {
        if ($classe == 'c50') {
            $img = '<' . $imgTag . ' class="img_fit" src="' . THUMB_PATH . $thumb . "g.jpg" . '">' . $imgTagClose;
        } else {
            $img = '<' . $imgTag . ' class="img_fit" src="' . THUMB_PATH . $thumb . ".jpg" . '">' . $imgTagClose;
        }
    }
    return '<div class="entry-image">' . $thumb_titulo . $duracao . $img . '</div>';
}

function modulo($param)
{
    global $removedLinks;
    global $amp;

    if (!$param['links']) {
        return;
    }

    $conteudo = '<div class="modulo">';

    foreach ($param['links'] as $key => $link) {

        if ($removedLinks != '') {
            $removedLinks .= ',';
        }
        $removedLinks .= $link['id'];

        if ($amp) {
            $link['path'] .= '/amp';
        }
        $thumb = $link['thumb'] ? $link['thumb'] : '';

        $conteudo .= '<div class="' . $param['classe'] . '"><a href="' . $link['path'] . '#content"><div class="link_container">' . image($param['classe'], $thumb, $link['duracao'], $link['titulo'], $link['thumb_titulo_html']) . '<div class="legenda"><div>' . $link['titulo'] . '</div><div>' . $link['subtitulo'] . '</div></div></div></a></div>';

    }
    $conteudo .= '<div class="clear"></div></div>';

    return $conteudo;
}

function getTagsID($tags)
{
    $strWhere = "(path = '" . url_amigavel($tags[0]) . "')";
    for ($z = 1; $z < count($tags); $z++) {
        $strWhere .= " and (path = '" . url_amigavel($tags[$z]) . "')";
    }
    $tags = v_select("nome, id from tags where $strWhere");
    if (count($tags) == 1) {
        global $legenda;
        $legenda = $tags[0]['nome'];
    }

    return $tags;

}

function removeItemDuplicado($vetor, $max)
{

    $id_anterior = -1;
    foreach ($vetor as $key => $item) {
        if ($id_anterior != $item['id']) {
            $id_anterior = $item['id'];
        } else {
            unset($vetor[$key]);
        }
    }
    array_splice($vetor, $max);
    return $vetor;
}

function getLinksFromTagsID($tagsID, $offset, $max, $root, $order = '')
{
    if (!$tagsID) {
        header('Location: /');
        die();
    }

    global $removedLinks;

    $taglist = '';

    foreach ($tagsID as $tag) {
        if ($taglist != '') {
            $taglist .= ',';
        }
        $taglist .= $tag['id'];
    }

    if ($taglist) {
        $tagWhere = "and (links_tags.tagID in (" . $taglist . "))";
    } else {
        $tagWhere = '';
    }

    if ($order != '') {
        $order = $order . ',';
    }

    if (strlen($removedLinks) > 0) {
        $remove = ' and linkID NOT IN (' . $removedLinks . ')';
    } else {
        $remove = '';
    }

    if (count($tagsID) > 1) {
        return removeItemDuplicado(v_select("id, CONCAT(links.basepath,links.path) as path, links.thumb_titulo_html, links.thumb, links.duracao, links.titulo, links.subtitulo from links_tags inner join links on links.id = links_tags.linkID where (links.publicado = 1 and links.search = 1 $tagWhere$root$remove) order by ${order}links.datePublished desc, links.id DESC LIMIT $offset, " . (2 * $max)), $max);
    } else {
        return v_select("id, CONCAT(links.basepath,links.path) as path, links.thumb_titulo_html, links.thumb, links.duracao, links.titulo, links.subtitulo from links_tags inner join links on links.id = links_tags.linkID where (links.publicado = 1 and links.search = 1 $tagWhere$root$remove) order by ${order}links.datePublished desc, links.id DESC LIMIT $offset, $max");
    }

}

function listaItem($param = [])
{
    global $removedLinks;
    $max    = isset($param['max']) ? $param['max'] : '4';
    $root   = isset($param['root']) ? 'and links.root = 1' : '';
    $offset = isset($param['offset']) ? $param['offset'] : 0;
    $order  = isset($param['order']) ? $param['order'] : null;

    if (strlen($removedLinks) > 0) {
        $remove = ' and id NOT IN (' . $removedLinks . ')';
    } else {
        $remove = '';
    }

    $links = v_select("id, CONCAT(links.basepath,links.path) as path, links.thumb_titulo_html, links.thumb, links.duracao, links.titulo, links.subtitulo from links where (links.publicado = 1 and links.search = 1 $root$remove) order by ${order}links.datePublished desc, links.id DESC LIMIT $offset, $max");

    if (count($links) == $max) {
        $GLOBALS['next_page'] = $offset + $max + 1;
    }

    if ($links) {
        $modulo = [
            'classe' => 'c25',
            'links'  => $links,
        ];
        return modulo($modulo);
    }

    return '';
}

function pesquisaTag($tags, $param = [])
{
    $max    = isset($param['max']) ? $param['max'] : '4';
    $root   = isset($param['root']) ? 'and links.root = 1' : '';
    $offset = isset($param['offset']) ? $param['offset'] : 0;
    $order  = isset($param['order']) ? $param['order'] : null;

    if ($tags) {
        if (!isset($param['id'])) {
            $tagsID = getTagsID($tags);
        } else {
            $tagsID = $tags;
        }
    } else {
        $tagsID = [0];
    }

    $links = getLinksFromTagsID($tagsID, $offset, $max, $root, $order);

    if ($links) {

        if (count($links) == $max) {
            $GLOBALS['next_page'] = $offset + $max + 1;
        }

        $modulo = [
            'classe' => 'c25',
            'links'  => $links,
        ];

        return modulo($modulo);
    }

    return '';
}

function showTextLinks($max)
{
    global $removedLinks;

    $links = v_select("id, CONCAT(links.basepath,links.path) as path, links.thumb_titulo_html, links.titulo, links.subtitulo from links where (links.publicado = 1 and links.search = 1 and id NOT IN (" . $removedLinks . ")) order by links.datePublished desc, links.id DESC LIMIT $max");

    $texto = '<div class="divisor"><div class="textLink">';

    if ($links) {
        foreach ($links as $key => $link) {
            $texto .= '<a href="' . $link['path'] . '#content">' . $link['titulo'] . '<div>' . $link['subtitulo'] . '</div></a>';
        }
    }

    $texto .= '</div></div>';

    return $texto;

}

function pesquisaByTagID($vetID, $param = [])
{
    $param['campo'] = 'id';
    return pesquisaTag($vetID, $param);
}

function pesquisaLink($texto, $param = [])
{

    if (strlen($texto) < 4) {
        return 'São necessários pelo menos 4 caracteres';
    }

    $max    = $param['max'] ? $param['max'] : '24';
    $offset = isset($param['offset']) ? $param['offset'] : 0;

    $links = v_select("id, CONCAT(links.basepath,links.path) as path, links.thumb_titulo_html, links.thumb, links.duracao, links.titulo, links.subtitulo from links where (links.publicado = 1 and links.search = 1 and (links.titulo like '%$texto%' or links.subtitulo like '%$texto%')) order by links.datePublished desc, links.id DESC LIMIT $offset, $max");
    //$links2 = v_select("CONCAT(basepath,path) as path, thumb, titulo, subtitulo from links where (titulo like '%$texto%' and publicado = 1 and thumb = '') order by data desc, id DESC LIMIT $max");

    if (($links) && (count($links) == $max)) {
        $GLOBALS['next_page'] = $offset + $max + 1;
    }

    if (!$links) {
        return '<div class="divisor_fixo">' . t('NO_RESULT') . '</div>';
    }

    return modulo(['classe' => 'c25', 'links' => $links]);
}

function getLinkIDFromPath($path)
{
    if (preg_match('/(\/\w+\/)([^\/]+)/', $path, $m)) {
        $basepath = $m[1];
        $path     = $m[2];
    } else {
        $basepath = '/';
        $path     = str_replace('/', '', $path);
    }
    return select("id from links where basepath = '$basepath' and path = '$path'")['id'];
}

function getTagsFromLinkID($linkID)
{    
    return v_select("tagID as id, tags.nome as nome, tags.path as path from links_tags inner join tags on links_tags.tagID = tags.id where linkID = $linkID order by nome");
}

function showRelatedLinks($path)
{
    $modulos = '';
    global $removedLinks;
    global $tags;

    $removedLinks = getLinkIDFromPath($path);
    
    if ($removedLinks){
        $tags         = getTagsFromLinkID($removedLinks);
    }
    
    if ($tags) {
        $modulos .= pesquisaByTagID($tags, ['max' => 8, 'id' => true]);
    } else {
        $modulos .= pesquisaTag(null, ['max' => 8]);
    }

    return '<!--googleoff: all-->' . $modulos . showTextLinks(4) . ' <!--googleon: all-->';
}

function showTags()
{
    global $tags;
    if ($tags) {
        $texto = '<div class="taglist">';

        if ($tags) {
            foreach ($tags as $tag) {
                $texto .= '<a href="/tag/' . $tag['path'] . '#content">' . $tag['nome'] . '</a>';
            }
        }
        $texto .= '</div>';
    } else {
        return '';
    }
    return $texto;
}

function showKeywords($keywords)
{
    $vet = explode(',', $keywords);

    $texto = '';
    foreach ($vet as $keyword) {
        $texto .= '<span onclick="search(this)">' . trim($keyword) . '</span>';
    }
    return $texto;
}

function thumbImage($thumb)
{
    if ($thumb) {
        $image = DNS_SITE . "/cache/img/upload/t/${thumb}amp.jpg";
    } else {
        $image = DNS_SITE . '/cache/img/openfbimg.jpg';
    }
    return $image;
}

function structured($titulo, $datePublished, $dateModified, $image, $description, $url)
{
    $datePublished = DateTime::createFromFormat("Y-m-d H:i:s", $datePublished)->format('Y-m-d\TH:i:s+00:00');
    $dateModified  = DateTime::createFromFormat("Y-m-d H:i:s", $dateModified)->format('Y-m-d\TH:i:s+00:00');

    if ($image != '') {
        $structuredImage = '"image": {"@type": "ImageObject","url": "' . $image . '","width": 1280, "height": 720},';
    } else {
        $structuredImage = '';
    }

    return '<script type="application/ld+json">{"@context": "http://schema.org","@type": "NewsArticle","headline": "' . $titulo . '",' . $structuredImage . '"author": {"@type": "Person","name": "' . SITE_NAME . '"},"mainEntityOfPage": {"@type": "WebPage","@id": "' . DNS_SITE . $url . '"},"publisher": {"@type": "Organization","name": "' . SITE_NAME . '","logo": {"@type": "ImageObject","url": "' . DNS_SITE . '/cache/img/logo.png","width": 174,"height": 60}},"datePublished": "' . $datePublished . '","dateModified": "' . $dateModified . '","description": "' . $description . '"}</script>';
}
