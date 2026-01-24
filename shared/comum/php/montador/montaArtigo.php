<?php

class MontaArtigo
{
    private $db;
    private $guardiao;
    private $cache;
    private $amp;

    public function __construct($db, $guardiao, $cache, $amp)
    {
        $this->db = $db;
        $this->guardiao = $guardiao;
        $this->cache = $cache;
        $this->amp = (bool) $amp;
    }

    public function montar($dados)
    {
        if (!$dados) {
            return null;
        }

        $artigo_html = $this->montaArtigoHtml($dados['artigo']);

        if (! $this->cache->getCache()) {
            $pagina = '_artigo';
            $add    = '';
        } else {
            $pagina = 'artigo';
            $add    = '';
        }

        $amp_script = $this->montaAmpScript($dados['duracao']);
        $modulos = $this->montaModulos();

        $image = thumbImage($dados['thumb']);

        $subtitulo = $this->normalizaSubtitulo($dados['subtitulo']);
        $description = $subtitulo . ' ' . $dados['keywords'];

        $structured = structured(
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

        $dados_preparados = [
            'structured'  => $structured,
            'amp_script'  => $amp_script,
            'titulo'      => $dados['titulo'],
            'subtitulo'   => $subtitulo,
            'timestamp'   => $timestamp,
            'description' => $description,
            'image'       => $image,
            'artigo'      => $artigo_html . showTags() . $contato,
            'modulos'     => $add . $modulos,            
            'sidebar'     => $sidebar,
        ];

        if ($dados['amp'] == 0) {
            $dados_preparados['alternative_link'] = '';
        }

        return [
            'pagina' => $pagina,
            'dados'  => $dados_preparados,
        ];
    }

    private function montaArtigoHtml($texto)
    {
        if ($this->amp) {
            return converte($texto, false, true);
        }

        return converte($texto);
    }

    private function montaAmpScript($duracao)
    {
        if (! $this->amp) {
            return '';
        }

        if ($duracao != '00:00:00') {
            return '<script async custom-element="amp-youtube" src="https://cdn.ampproject.org/v0/amp-youtube-0.1.js"></script>';
        }

        return '';
    }

    private function montaModulos()
    {
        return '<div class="interesse">Talvez seja de seu interesse</div>' . showRelatedLinks($this->db, $this->guardiao->getUrl());
    }

    private function normalizaSubtitulo($subtitulo)
    {
        if (strpos('.?!', substr($subtitulo, -1)) === false) {
            $subtitulo .= '.';
        }

        return $subtitulo;
    }

    private function montaTimestamp($datePublished, $dateModified)
    {
        $timestamp = '<span>Publicado em ' . DateTime::createFromFormat('Y-m-d H:i:s', $datePublished)->format('d/m/Y') . '</span>';

        if ($dateModified != $datePublished) {
            $timestamp .= '<span>Última atualização em ' . DateTime::createFromFormat('Y-m-d H:i:s', $dateModified)->format('d/m/Y \à\s H:i:s') . '</span>';
        }

        return $timestamp;
    }

    private function montaContato()
    {
        return '<div class="contato"><b>Contato:</b> Caso você tenha identificado algum erro ou imprecisão no conteúdo acima, por gentileza, considere informar <a target="_blank" href="https://docs.google.com/forms/d/e/1FAIpQLScX2d2cqz6DoJQga6S988u9Ci895meVc5zNehAXcevhvfOpiw/viewform?entry.278388629=' . SITE . $this->guardiao->getUrl() . '">clicando aqui</a>. Você poderá utilizar o mesmo link caso queira entrar em contato por qualquer outro motivo.</div>';
    }

    private function montaSidebar()
    {
        return '<div class="divisor_fixo"><div id="arranhaceu">' . adsense('arranhaceu') . '</div></div><div class="divisor sticky20">' . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/elementos/ultimos' . ($this->amp ? '_amp' : '')) . '</div>';
    }
}
