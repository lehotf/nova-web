<?php

require 'comum/php/montador/pesquisa.php';

$conteudo = modulo([
    'classe' => 'c50',
    'links'  => v_select('links.id, CONCAT(links.basepath,links.path) as path, links.thumb_titulo_html, links.duracao, links.thumb, links.titulo, links.subtitulo from links_destaques inner join links on links_destaques.linkID = links.id where links_destaques.id = 100 or links_destaques.id = 200'),
]);

/*
Lembrando que root indica que o link poderá aparecer na página principal e nas subsequentes. Publicado, é pre-requisito para ser visto, mas, também, para gerar linha no sitemap.
Ou seja, o link pode ser publicado, mas não ser root. Significa que o artigo aparecerá nos buscadores e na busca do site, mas não aparecerá, naturalmente, na paginação do root.
*/
$conteudo .= listaItem(['max' => MAX_IN_ROOT, 'root' => true]);

global $next_page;
if ($next_page) {
    $conteudo .= '<div class="divisor_fixo pagebar centered"><a href="/p/1'.($this->amp ? '/amp' : '').'#content" class="nextpage">TODOS OS ARTIGOS</a></div>';
}

$conteudo .= '<div class="divisor">' . adsense('article') . '</div>';

$conteudo .= showTextLinks(7);


//$conteudo .= '<div><div class="c50 divisor_fixo">'.ADD_FEED.'</div><div class="c50 divisor_fixo">'.ADD_FEED.'</div></div>';

$this->prepara('index', [
    'titulo'      => SITE_TITULO,
    'conteudo'    => $conteudo,
    'description' => DESCRICAO,
    'sidebar'     => '<div class="divisor_fixo"><div id="arranhaceu">' . adsense('arranhaceu') . '</div></div><div class="divisor sticky20">' . file_get_contents(CAMINHO . '/cache/elementos/ultimos' . ($this->amp ? '_amp' : '')) . '</div>',
]);
