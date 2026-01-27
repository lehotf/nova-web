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
    private $guardiao;
    private $logger;
    private $cache;
    private $contador_de_tempo;

    public function __construct($guardiao, $logger, $contador_de_tempo)
    {
        $this->guardiao = $guardiao;
        $this->logger = $logger;
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
