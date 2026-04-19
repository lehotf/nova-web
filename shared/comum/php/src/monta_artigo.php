<?php

class monta_artigo
{
    private $db;
    private $guardiao;
    private $amp;
    public $removedLinks;
    public $tags;
    public $legenda;
    public $cor;
    public $next_page;
    public $script;

    public function __construct($db, $guardiao, $amp)
    {
        $this->db = $db;
        $this->guardiao = $guardiao;
        $this->amp = (bool) $amp;
        $this->removedLinks = '';
        $this->tags = null;
        $this->legenda = '';
        $this->cor = null;
        $this->next_page = null;
        $this->script = [];
    }

    private function publicPathSql($column = 'links.path')
    {
        return "CASE WHEN LEFT($column, 1) = '/' THEN $column ELSE CONCAT('/', $column) END";
    }

    private function normalizeStoredPath($path)
    {
        return ltrim((string) $path, '/');
    }

    private function buildThumbTitleHtml($texto)
    {
        $texto = (string) $texto;
        if ($texto === '') {
            return '';
        }

        $linhas = preg_split("/\r\n|\r|\n/", $texto);
        if (!$linhas) {
            return '';
        }

        $html = '';
        $contador = 0;

        foreach ($linhas as $linha) {
            $contador++;
            $conteudo = $linha;

            if (function_exists('verifica_fonte')) {
                $conteudo = verifica_fonte($conteudo);
            }
            if (function_exists('substitui')) {
                $conteudo = substitui($conteudo);
            }
            if (function_exists('substitui_nbsp')) {
                $conteudo = substitui_nbsp($conteudo);
            }

            $html .= '<div class="th_l' . $contador . '">' . $conteudo . '</div>';
        }

        return $html;
    }

    public function renderThumbTitleHtml($texto)
    {
        return $this->buildThumbTitleHtml($texto);
    }

    public function montaArtigoHtml($texto)
    {
        $texto = $this->extraiScripts($texto);

        if ($this->amp) {
            return converte($texto, false, true);
        }

        return converte($texto);
    }

    public function extraiScripts($texto)
    {
        $this->script = [];

        $texto = preg_replace_callback('/js\[(.*?)\]/', function ($match) {
            $nomes = array_filter(array_map('trim', explode(',', $match[1])), 'strlen');

            if ($nomes) {
                foreach ($nomes as $nome) {
                    $this->script[] = 'calc/' . $nome;
                }
            }

            return '';
        }, $texto);

        return trim($texto);
    }

    public function montaAmpScript($duracao)
    {
        if (! $this->amp) {
            return '';
        }

        if ($duracao != '00:00:00') {
            return '<script async custom-element="amp-youtube" src="https://cdn.ampproject.org/v0/amp-youtube-0.1.js"></script>';
        }

        return '';
    }

    public function montaModulos()
    {
        return '<div class="interesse">Talvez seja de seu interesse</div>' . $this->showRelatedLinks($this->db, $this->guardiao->getUrl());
    }

    public function normalizaSubtitulo($subtitulo)
    {
        if (strpos('.?!', substr($subtitulo, -1)) === false) {
            $subtitulo .= '.';
        }

        return $subtitulo;
    }

    public function montaTimestamp($datePublished, $dateModified)
    {
        $timestamp = '<span>Publicado em ' . DateTime::createFromFormat('Y-m-d H:i:s', $datePublished)->format('d/m/Y') . '</span>';

        if ($dateModified != $datePublished) {
            $timestamp .= '<span>Última atualização em ' . DateTime::createFromFormat('Y-m-d H:i:s', $dateModified)->format('d/m/Y \à\s H:i:s') . '</span>';
        }

        return $timestamp;
    }

    public function montaContato()
    {
        return '<div class="contato"><b>Contato:</b> Caso você tenha identificado algum erro ou imprecisão no conteúdo acima, por gentileza, considere informar <a target="_blank" href="https://docs.google.com/forms/d/e/1FAIpQLScX2d2cqz6DoJQga6S988u9Ci895meVc5zNehAXcevhvfOpiw/viewform?entry.278388629=' . SITE . $this->guardiao->getUrl() . '">clicando aqui</a>. Você poderá utilizar o mesmo link caso queira entrar em contato por qualquer outro motivo.</div>';
    }

    public function montaSidebar()
    {
        return '<div class="divisor_fixo"><div id="arranhaceu">' . adsense('arranhaceu') . '</div></div><div class="divisor sticky20">' . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/elementos/ultimos' . ($this->amp ? '_amp' : '')) . '</div>';
    }

    public function image($classe, $thumb, $duracao, $titulo, $thumb_titulo)
    {
        $thumb_path  = "/cache/img/upload/t/";
        $duracao     = ($duracao != '00:00:00') ? '<div class="duracao">' . preg_replace('#^00.#', '', $duracao) . '</div>' : '';
        $imgTag      = $this->amp ? 'amp-img width="193" height="73" layout="responsive"' : 'img';
        $imgTagClose = $this->amp ? '</amp-img>' : '';

        if ($thumb_titulo != '') {
            if ($classe == 'c50') {
                $thumb_titulo = '<div class="t_th1">' . $thumb_titulo . '</div>';
            } else {
                $thumb_titulo = '<div class="t_th2">' . $thumb_titulo . '</div>';
            }
        }

        if ($thumb == '') {
            if ($this->cor === null) {
                $this->cor = '';
            } else {
                $this->cor = ($this->cor == '') ? ' cor2' : '';
            }
            $img = '<div class="noimage' . $this->cor . '"><div>' . $titulo . '</div></div>';
        } else {
            if ($classe == 'c50') {
                $img = '<' . $imgTag . ' class="img_fit" src="' . $thumb_path . $thumb . "g.jpg" . '">' . $imgTagClose;
            } else {
                $img = '<' . $imgTag . ' class="img_fit" src="' . $thumb_path . $thumb . ".jpg" . '">' . $imgTagClose;
            }
        }
        return '<div class="entry-image">' . $thumb_titulo . $duracao . $img . '</div>';
    }

    public function modulo($param)
    {
        if (!$param['links']) {
            return;
        }

        $conteudo = '<div class="modulo">';

        foreach ($param['links'] as $key => $link) {
            if ($this->removedLinks != '') {
                $this->removedLinks .= ',';
            }
            $this->removedLinks .= $link['id'];

            if ($this->amp) {
                $link['path'] .= '/amp';
            }
            $thumb = $link['thumb'] ? $link['thumb'] : '';

            $conteudo .= '<div class="' . $param['classe'] . '"><a href="' . $link['path'] . '#content"><div class="link_container">' . $this->image($param['classe'], $thumb, $link['duracao'], $link['titulo'], $this->buildThumbTitleHtml($link['thumb_titulo'] ?? '')) . '<div class="legenda"><div>' . $link['titulo'] . '</div><div>' . $link['subtitulo'] . '</div></div></div></a></div>';
        }
        $conteudo .= '<div class="clear"></div></div>';

        return $conteudo;
    }

    public function getTagsID($db, $tags)
    {
        if (!$tags) {
            return false;
        }

        $tagsNormalizadas = [];
        foreach ($tags as $tag) {
            $tagsNormalizadas[] = url_amigavel($tag);
        }

        $strWhere = implode(' and ', array_fill(0, count($tagsNormalizadas), '(path = ?)'));
        $tags = $db->v_select(
            "nome, id from tags where $strWhere",
            str_repeat('s', count($tagsNormalizadas)),
            $tagsNormalizadas
        );

        if (count($tags) == 1) {
            $this->legenda = $tags[0]['nome'];
        }

        return $tags;
    }

    public function removeItemDuplicado($vetor, $max)
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

    public function getLinksFromTagsID($db, $tagsID, $offset, $max, $root, $order = '')
    {
        if (!$tagsID) {
            header('Location: /');
            die();
        }

        $tagIds = [];
        foreach ($tagsID as $tag) {
            if (is_array($tag) && array_key_exists('id', $tag)) {
                $tagIds[] = (int) $tag['id'];
            } else {
                $tagIds[] = (int) $tag;
            }
        }

        $tipos = '';
        $params = [];

        if ($tagIds) {
            $tagWhere = "and (links_tags.tagID in (" . implode(',', array_fill(0, count($tagIds), '?')) . "))";
            $tipos .= str_repeat('i', count($tagIds));
            $params = array_merge($params, $tagIds);
        } else {
            $tagWhere = '';
        }

        if ($order != '') {
            $order = $order . ',';
        }

        $removed = array_values(array_filter(array_map('intval', explode(',', (string) $this->removedLinks))));
        if ($removed) {
            $remove = ' and linkID NOT IN (' . implode(',', array_fill(0, count($removed), '?')) . ')';
            $tipos .= str_repeat('i', count($removed));
            $params = array_merge($params, $removed);
        } else {
            $remove = '';
        }

        if (count($tagsID) > 1) {
            $params2 = array_merge($params, [(int) $offset, (int) (2 * $max)]);
            return $this->removeItemDuplicado(
                $db->v_select(
                    "id, " . $this->publicPathSql('links.path') . " as path, links.thumb_titulo, links.thumb, links.duracao, links.titulo, links.subtitulo from links_tags inner join links on links.id = links_tags.linkID where (links.publicado = 1 and links.search = 1 $tagWhere$root$remove) order by ${order}links.datePublished desc, links.id DESC LIMIT ?, ?",
                    $tipos . 'ii',
                    $params2
                ),
                $max
            );
        } else {
            $params2 = array_merge($params, [(int) $offset, (int) $max]);
            return $db->v_select(
                "id, " . $this->publicPathSql('links.path') . " as path, links.thumb_titulo, links.thumb, links.duracao, links.titulo, links.subtitulo from links_tags inner join links on links.id = links_tags.linkID where (links.publicado = 1 and links.search = 1 $tagWhere$root$remove) order by ${order}links.datePublished desc, links.id DESC LIMIT ?, ?",
                $tipos . 'ii',
                $params2
            );
        }
    }

    public function listaItem($db, $param = [])
    {
        $max    = isset($param['max']) ? $param['max'] : '4';
        $root   = isset($param['root']) ? 'and links.root = 1' : '';
        $offset = isset($param['offset']) ? $param['offset'] : 0;
        $order  = isset($param['order']) ? $param['order'] : null;

        $removed = array_values(array_filter(array_map('intval', explode(',', (string) $this->removedLinks))));
        $tipos = '';
        $params = [];

        if ($removed) {
            $remove = ' and id NOT IN (' . implode(',', array_fill(0, count($removed), '?')) . ')';
            $tipos .= str_repeat('i', count($removed));
            $params = array_merge($params, $removed);
        } else {
            $remove = '';
        }

        $params = array_merge($params, [(int) $offset, (int) $max]);
        $links = $db->v_select(
            "id, " . $this->publicPathSql('links.path') . " as path, links.thumb_titulo, links.thumb, links.duracao, links.titulo, links.subtitulo from links where (links.publicado = 1 and links.search = 1 $root$remove) order by ${order}links.datePublished desc, links.id DESC LIMIT ?, ?",
            $tipos . 'ii',
            $params
        );

        if (count($links) == $max) {
            $this->next_page = $offset + $max + 1;
        }

        if ($links) {
            $modulo = [
                'classe' => 'c25',
                'links'  => $links,
            ];
            return $this->modulo($modulo);
        }

        return '';
    }

    public function pesquisaTag($db, $tags, $param = [])
    {
        $max    = isset($param['max']) ? $param['max'] : '4';
        $root   = isset($param['root']) ? 'and links.root = 1' : '';
        $offset = isset($param['offset']) ? $param['offset'] : 0;
        $order  = isset($param['order']) ? $param['order'] : null;

        if ($tags) {
            if (!isset($param['id'])) {
                $tagsID = $this->getTagsID($db, $tags);
            } else {
                $tagsID = $tags;
            }
        } else {
            $tagsID = [0];
        }

        $links = $this->getLinksFromTagsID($db, $tagsID, $offset, $max, $root, $order);

        if ($links) {
            if (count($links) == $max) {
                $this->next_page = $offset + $max + 1;
            }

            $modulo = [
                'classe' => 'c25',
                'links'  => $links,
            ];

            return $this->modulo($modulo);
        }

        return '';
    }

    public function showTextLinks($db, $max)
    {
        $removed = array_values(array_filter(array_map('intval', explode(',', (string) $this->removedLinks))));
        $tipos = '';
        $params = [];

        if ($removed) {
            $remove = ' and id NOT IN (' . implode(',', array_fill(0, count($removed), '?')) . ')';
            $tipos .= str_repeat('i', count($removed));
            $params = array_merge($params, $removed);
        } else {
            $remove = '';
        }

        $params[] = (int) $max;
        $links = $db->v_select(
            "id, " . $this->publicPathSql('links.path') . " as path, links.thumb_titulo, links.titulo, links.subtitulo from links where (links.publicado = 1 and links.search = 1 $remove) order by links.datePublished desc, links.id DESC LIMIT ?",
            $tipos . 'i',
            $params
        );

        $texto = '<div class="textLink">';

        if ($links) {
            foreach ($links as $key => $link) {
                $texto .= '<a href="' . $link['path'] . '#content">' . $link['titulo'] . '<div>' . $link['subtitulo'] . '</div></a>';
            }
        }

        $texto .= '</div>';

        return $texto;
    }

    public function pesquisaByTagID($db, $vetID, $param = [])
    {
        $param['campo'] = 'id';
        return $this->pesquisaTag($db, $vetID, $param);
    }

    public function pesquisaLink($db, $texto, $param = [])
    {
        if (strlen($texto) < 4) {
            return 'São necessários pelo menos 4 caracteres';
        }

        $max    = $param['max'] ? $param['max'] : '24';
        $offset = isset($param['offset']) ? $param['offset'] : 0;

        $busca = '%' . $texto . '%';
        $links = $db->v_select(
            "id, " . $this->publicPathSql('links.path') . " as path, links.thumb_titulo, links.thumb, links.duracao, links.titulo, links.subtitulo from links where (links.publicado = 1 and links.search = 1 and (links.titulo like ? or links.subtitulo like ?)) order by links.datePublished desc, links.id DESC LIMIT ?, ?",
            'ssii',
            [$busca, $busca, (int) $offset, (int) $max]
        );
        //$links2 = v_select("path, thumb, titulo, subtitulo from links where (titulo like '%$texto%' and publicado = 1 and thumb = '') order by data desc, id DESC LIMIT $max");

        if (($links) && (count($links) == $max)) {
            $this->next_page = $offset + $max + 1;
        }

        if (!$links) {
            return '<div class="divisor_fixo"></div>';
        }

        return $this->modulo(['classe' => 'c25', 'links' => $links]);
    }

    public function getLinkIDFromPath($db, $path)
    {        
        $resultado = $db->select(
            "id from links where path = ?",
            's',
            $this->normalizeStoredPath($path)
        );
        return $resultado ? $resultado['id'] : null;
    }

    public function getTagsFromLinkID($db, $linkID)
    {
        return $db->v_select(
            "tagID as id, tags.nome as nome, tags.path as path from links_tags inner join tags on links_tags.tagID = tags.id where linkID = ? order by nome",
            'i',
            [(int) $linkID]
        );
    }

    public function showRelatedLinks($db, $path)
    {
        $modulos = '';

        $this->removedLinks = $this->getLinkIDFromPath($db, $path);
        if (!$this->removedLinks) {
            $this->removedLinks = '';
        }

        if ($this->removedLinks) {
            $this->tags = $this->getTagsFromLinkID($db, $this->removedLinks);
        }

        if ($this->tags) {
            $modulos .= $this->pesquisaByTagID($db, $this->tags, ['max' => 8, 'id' => true]);
        } else {
            $modulos .= $this->pesquisaTag($db, null, ['max' => 8]);
        }

        return '<!--googleoff: all-->' . $modulos . $this->showTextLinks($db, 4) . ' <!--googleon: all-->';
    }

    public function showTags()
    {
        if ($this->tags) {
            $texto = '<div class="taglist">';

            if ($this->tags) {
                foreach ($this->tags as $tag) {
                    $texto .= '<a href="/tag/' . $tag['path'] . '#content">' . $tag['nome'] . '</a>';
                }
            }
            $texto .= '</div>';
        } else {
            return '';
        }
        return $texto;
    }

    public function showKeywords($keywords)
    {
        $vet = explode(',', $keywords);

        $texto = '';
        foreach ($vet as $keyword) {
            $texto .= '<span onclick="search(this)">' . trim($keyword) . '</span>';
        }
        return $texto;
    }

    public function thumbImage($thumb)
    {
        if ($thumb) {
            $image = DNS_SITE . "/cache/img/upload/t/${thumb}amp.jpg";
        } else {
            $image = DNS_SITE . '/cache/img/openfbimg.jpg';
        }
        return $image;
    }

    public function structured($titulo, $datePublished, $dateModified, $image, $description, $url)
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
}
