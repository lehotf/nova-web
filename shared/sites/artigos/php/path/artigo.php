<?php

$dados = prepare_select($db, "id, artigo, titulo, subtitulo, thumb, duracao, datePublished, dateModified, amp, keywords from links where path = ?", 's', $comando);

if ($dados) {
    require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/montador/pesquisa.php';
    require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/montador/montaArtigo.php';
    require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/sistema/tool/texto.php';

    $montador = new MontaArtigo($db, $this->guardiao, $this->cache, $this->amp);
    $resultado = $montador->montar($dados);

    $this->prepara($resultado['pagina'], $resultado['dados']);

} else {
    $this->localizaPath($comando);
}
