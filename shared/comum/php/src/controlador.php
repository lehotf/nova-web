<?php

class cache
{
    /**
     * @var string Diretorio base do cache HTML do site
     */
    private $diretorioCache;

    /**
     * @var bool Define se o cache deve ser usado
     */
    private $cacheAtivo;
    private $guardiao;

    /**
     * @param bool $cacheAtivo Se true, o cache sera utilizado
     */
    public function __construct($cacheAtivo, $guardiao)
    {
        $this->cacheAtivo = (bool) $cacheAtivo;
        $this->diretorioCache = 'cache/html';
        $this->guardiao = $guardiao;
    }

    /**
     * Define se o cache deve ser usado
     *
     * @param bool $cacheAtivo
     * @return void
     */
    public function setCache($cacheAtivo)
    {
        $this->cacheAtivo = (bool) $cacheAtivo;
    }

    /**
     * Retorna se o cache esta ativado
     *
     * @return bool
     */
    public function getCache()
    {
        return $this->cacheAtivo;
    }

    /**
     * Retorna o conteudo do cache, se existir
     *
     * @return string|false Conteudo do cache ou false se nao existir
     */
    public function buscar()
    {
        if (! $this->cacheAtivo) {
            return false;
        }

        $caminhoArquivo = $this->montarCaminho();

        if (is_file($caminhoArquivo)) {
            return file_get_contents($caminhoArquivo);
        }

        return false;
    }

    public function salvar($conteudo)
    {
        if (! $this->cacheAtivo) {
            echo $conteudo;
            return;
        }

        $caminhoArquivo = $this->montarCaminho();
        file_put_contents($caminhoArquivo, $conteudo);
        echo $conteudo;
    }

    /**
     * Monta o caminho do arquivo de cache com base na URL
     *
     * @param string $url
     * @return string
     */
    private function montarCaminho()
    {
        $url = $this->guardiao->getUrl();

        if ($url === '/') {
            return $this->diretorioCache . '/root';
        }

        $arquivo = str_replace('/', '.', ltrim($url, '/'));
        return $this->diretorioCache . '/' . $arquivo;
    }
}


class controlador
{
    public $guardiao;
    public $logger;
    public $cache;
    public $contador_de_tempo;
    public $autenticador;
    public $observador;
    public $db;

    public function __construct(bool $guardiao = false, bool $logger = false, bool $autenticador = false, bool $observador = false, bool $db = false)
    {
        if ($db) {
            $this->db = new database('localhost', BD_LOGIN, BD_SENHA, BD);
        }

        if ($logger) {
            $this->logger = new logger();
        }

        if ($guardiao) {            
            if (!$this->logger) {
                $this->logger = new logger();
            }
            $this->guardiao = new guardiao($this->logger);
        }

        if ($observador) {
            $this->observador = new observador($db);
        }

        if ($autenticador) {
            if (!$this->observador) {
                $this->observador = new observador($db);
            }
            $this->autenticador = new autenticador($this->observador);
        }
    }


    public function carrega_pagina($contador_de_tempo)
    {
        $this->contador_de_tempo = $contador_de_tempo;
        $this->cache = new cache(CACHE_ATIVO, $this->guardiao);        
        $this->verificaCache();

        $c = new carregador($this->guardiao, $this->cache, $this->logger);        
    }   

    private function verificaCache()
    {
        $conteudo = $this->cache->buscar();

        if ($conteudo !== false) {
            $this->logger->acesso();
            echo $conteudo;
            $this->contador_de_tempo->stop();
            die();
        }
    }
}
