<?php

$dados = $db->prepare_select("id, artigo, titulo, subtitulo, thumb, duracao, datePublished, dateModified, amp, keywords from links where path = ?", 's', $comando);

if ($dados) {
    require 'comum/php/include/ad.php';    
    require 'comum/php/include/texto.php';

    $montador = new monta_artigo($db, $this->guardiao, $this->cache, $this->amp);

    if (!$dados) {
        return null;
    }

    $artigo_html = $this->montaArtigoHtml($dados['artigo']);

    if ($this->nocache) {
        $pagina = '_artigo';
        $add    = '';
    } else {
        $pagina = 'artigo';
        $add    = '';
    }

    $amp_script = $this->montaAmpScript($dados['duracao']);
    $modulos = $this->montaModulos();

    $image = $this->thumbImage($dados['thumb']);

    $subtitulo = $this->normalizaSubtitulo($dados['subtitulo']);
    $description = $subtitulo . ' ' . $dados['keywords'];

    $structured = $this->structured(
        $dados['titulo'],
        $dados['datePublished'],
        $dados['dateModified'],
        $image,
        $description,
        $this->guardiao->getUrl()
        );

    $timestamp = $this->montaTimestamp($dados['datePublished'], $dados['dateModified']);
    $contato = $this->montaContato();
    $sidebar = $this->montaSidebar();
        

    if ($dados['amp'] == 0) {
        $dados_preparados['alternative_link'] = '';
    }

    $dados_preparados = [
        'structured'  => $structured,
        'amp_script'  => $amp_script,
        'titulo'      => $dados['titulo'],
        'subtitulo'   => $subtitulo,
        'timestamp'   => $timestamp,
        'description' => $description,
        'image'       => $image,
        'artigo'      => $artigo_html . $this->showTags() . $contato,
        'modulos'     => $add . $modulos,            
        'sidebar'     => $sidebar,
    ];


    $this->prepara([
        'pagina' => $pagina,
        'dados'  => $dados_preparados,
    ]);

} else {
    $this->localizaPath($comando);
}
