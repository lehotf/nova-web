<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/core/montador/pesquisa.php';

$tag = $this->comando[0];

if (isset($this->comando[1]) && ($this->comando[1] != 'amp')){
	$pagina = $this->comando[1] - 1;
}else{
	$pagina = 0;
}

$pesquisa = pesquisaTag([$tag], ['max' => 24, 'offset' => $pagina * 24]);
$conteudo = '<div class="folha_de_papel padding50"><h1 class="titulotag">' . $GLOBALS['legenda'] . '</h1></div>';

global $next_page;

$pp = ($pagina > 0) ? '<a href="/tag/' . $tag . '/' . ($pagina) . '" class="nextpage">ANTERIOR</a>' : '';
$np = ($next_page) ? '<a href="/tag/' . $tag . '/' . ($pagina + 2) . '" class="nextpage">PROXIMA</a>' : '';

$bar = ($pp || $np) ? '<div class="divisor_fixo pagebar"><div>' . $pp . '</div><div>' . $np . '</div></div>' : '';

$conteudo .= $pesquisa . $bar;

$this->prepara('index', [
    'conteudo' => $conteudo,
    'structured'   => '<meta name="robots" content="noindex">',
    'sidebar'     => '<div class="divisor_fixo"><div id="arranhaceu">' . adsense('retangulo') . '</div></div>'
]);