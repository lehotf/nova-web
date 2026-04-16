<?php
$dados = $this->db->select("id, artigo, titulo, subtitulo, thumb, duracao, datePublished, dateModified, amp, keywords from links where path = ?", 's', ltrim($comando, '/'));

if ($dados) {        
    require 'comum/php/include/texto.php';

    $montador = new monta_artigo($this->db, $this->guardiao, $this->amp);

    if (!$dados) {
        return null;
    }

    $artigo_html = $montador->montaArtigoHtml($dados['artigo']);

    if (!CACHE_ATIVO) {
        $pagina = '_artigo';
        $add    = '';
    } else {
        $pagina = 'artigo';
        $add    = '<div class="divisor">' . adsense('article') . '</div>';
    }

    $amp_script = $montador->montaAmpScript($dados['duracao']);
    $modulos = $montador->montaModulos();

    $image = $montador->thumbImage($dados['thumb']);

    $subtitulo = $montador->normalizaSubtitulo($dados['subtitulo']);
    $description = $subtitulo . ' ' . $dados['keywords'];

    $structured = $montador->structured(
        $dados['titulo'],
        $dados['datePublished'],
        $dados['dateModified'],
        $image,
        $description,
        $this->guardiao->getUrl()
        );

    $timestamp = $montador->montaTimestamp($dados['datePublished'], $dados['dateModified']);
    $contato = $montador->montaContato();
    $sidebar = $montador->montaSidebar();
        
    $dados_preparados = [        
        'structured'  => $structured,
        'amp_script'  => $amp_script,
        'titulo'      => $dados['titulo'],
        'subtitulo'   => $subtitulo,
        'timestamp'   => $timestamp,
        'description' => $description,
        'image'       => $image,
        'artigo'      => $artigo_html . $montador->showTags() . $contato,
        'modulos'     => $add . $modulos,            
        'sidebar'     => $sidebar                
    ];

    if ($montador->script) {
        $dados_preparados['js'] = $montador->script;
    }

    if ($dados['amp'] == 0) {
        $dados_preparados['alternative_link'] = '';
    }


    $this->prepara($pagina, $dados_preparados);

} else {    
    $this->localizaPath($comando);
}
