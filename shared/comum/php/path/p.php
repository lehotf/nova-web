<?php
$pagina = $this->comando[0] ? ($this->comando[0] - 1) : 0;

/*
Lembrando que root indica que o link poderá aparecer na página principal e nas subsequentes. Publicado, é pre-requisito para ser visto, mas, também, para gerar linha no sitemap. 
Ou seja, o link pode ser publicado, mas não ser root. Significa que o artigo aparecerá nos buscadores e na busca do site, mas não aparecerá, naturalmente, na paginação do root.
*/
$montador = new monta_artigo($this->db, $this->guardiao, $this->amp);
$conteudo = $montador->listaItem($this->db, ['max' => 24, 'offset' => $pagina * 24, 'root' => true]);

$pp = ($pagina > 0) ? '<a href="/p/' . ($pagina) . ($this->amp ? '' : '') . '" class="nextpage">ANTERIOR</a>' : '';
$np = ($montador->next_page) ? '<a href="/p/' . ($pagina + 2) . ($this->amp ? '' : '') . '" class="nextpage">PROXIMA</a>' : '';

$bar1 = ($pp || $np) ? '<div class="divisor_fixo pagebar" style="margin-top:0"><div>' . $pp . '</div><div>' . $np . '</div></div>' : '';
$bar2 = ($pp || $np) ? '<div class="divisor_fixo pagebar"><div>' . $pp . '</div><div>' . $np . '</div></div>' : '';

$conteudo = $bar1 . $conteudo . $bar2;

$this->prepara('index', [
    'conteudo'   => $conteudo,
    'structured' => '<meta name="robots" content="noindex">',
    'sidebar'     => '<div class="divisor_fixo"><div id="arranhaceu">' . adsense('retangulo') . '</div></div>'
]);
