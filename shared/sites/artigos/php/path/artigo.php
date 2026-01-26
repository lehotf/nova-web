<?php

$dados = prepare_select($db, "id, artigo, titulo, subtitulo, thumb, duracao, datePublished, dateModified, amp, keywords from links where path = ?", 's', $comando);

if ($dados) {
    require 'comum/php/include/pesquisa.php';
    require 'comum/php/include/ad.php';    
    require 'comum/php/include/texto.php';

    $montador = new monta_artigo($db, $this->guardiao, $this->cache, $this->amp);
    $resultado = $montador->montar($dados);

    $this->prepara($resultado['pagina'], $resultado['dados']);

} else {
    $this->localizaPath($comando);
}
