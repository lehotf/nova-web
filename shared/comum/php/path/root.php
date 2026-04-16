<?php
$montador = new monta_artigo($this->db, $this->guardiao, $this->amp);

$conteudo = $montador->modulo([
    'classe' => 'c50',
    'links'  => $this->db->v_select(
        "links.id, CASE WHEN LEFT(links.path, 1) = '/' THEN links.path ELSE CONCAT('/', links.path) END as path, links.thumb_titulo, links.duracao, links.thumb, links.titulo, links.subtitulo from links_destaques inner join links on links_destaques.linkID = links.id where links_destaques.id in (?, ?)",
        'ii',
        [100, 200]
    ),
]);

/*
Lembrando que root indica que o link poderá aparecer na página principal e nas subsequentes. Publicado, é pre-requisito para ser visto, mas, também, para gerar linha no sitemap.
Ou seja, o link pode ser publicado, mas não ser root. Significa que o artigo aparecerá nos buscadores e na busca do site, mas não aparecerá, naturalmente, na paginação do root.
*/
$conteudo .= $montador->listaItem($this->db, ['max' => MAX_IN_ROOT, 'root' => true]);

if ($montador->next_page) {
    $conteudo .= '<div class="divisor_fixo pagebar centered"><a href="/p/1'.($this->amp ? '/amp' : '').'#content" class="nextpage">TODOS OS ARTIGOS</a></div>';
}

$conteudo .= '<div class="divisor">' . adsense('article') . '</div>';

$conteudo .= $montador->showTextLinks($this->db, 7);


//$conteudo .= '<div><div class="c50 divisor_fixo">'.ADD_FEED.'</div><div class="c50 divisor_fixo">'.ADD_FEED.'</div></div>';

$this->prepara('index', [
    'titulo'      => SITE_TITULO,
    'conteudo'    => $conteudo,
    'description' => DESCRICAO,
    'sidebar'     => '<div class="divisor_fixo"><div id="arranhaceu">' . adsense('arranhaceu') . '</div></div><div class="divisor sticky20">' . file_get_contents('cache/elementos/ultimos' . ($this->amp ? '_amp' : '')) . '</div>',
]);
