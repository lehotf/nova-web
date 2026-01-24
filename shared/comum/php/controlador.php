<?php

class Cache
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



class Logger
{
    /**
     * @var Guardiao
     */
    private $guardiao;

    public function __construct($guardiao)
    {
        $this->guardiao = $guardiao;
    }

    // Grava direto (primeiro acesso ou fallback)
    public function acesso_negado($nota = null)
    {
        if (!DEBUG) {
            $nota = $nota ? " [$nota]" : '';
            $linha = date('d/m/Y H:i:s') . ' ' . $this->guardiao->getIp() . ' ' . $this->guardiao->getUrl() . $nota . "\n";

            file_put_contents(
                'cache/sistema/acessos_negados',
                $linha,
                FILE_APPEND
            );
        }
    }

    // Adiciona ao buffer (acessos subsequentes)
    public function acesso_negado_buffer($nota = null)
    {
        if (!DEBUG) {

            $nota = $nota ? " [$nota]" : '';
            $linha = date('d/m/Y H:i:s') . ' ' . $this->guardiao->getIp() . ' ' . $this->guardiao->getUrl() . $nota . "\n";

            $buffer = apcu_fetch('log_buffer_negados') ?: [];
            $buffer[] = $linha;

            // Flush a cada 10 logs
            if (count($buffer) >= 10) {
                file_put_contents(
                    'cache/sistema/acessos_negados',
                    implode('', $buffer),
                    FILE_APPEND | LOCK_EX
                );
                apcu_delete('log_buffer_negados');
            } else {
                apcu_store('log_buffer_negados', $buffer, 30);
            }
        }
    }

    // Método normal de acesso (pode otimizar também se quiser)
    public function acesso($nota = null)
    {
        if (!DEBUG) {
            $nota = $nota ? " [$nota]" : '';
            if ($this->guardiao->ipEmListaBranca()) {
                $nota .= ' (BP)';
            }

            file_put_contents(
                'cache/sistema/acessos',
                date('d/m/Y H:i:s') . ' ' . $this->guardiao->getIp() . ' ' . $this->guardiao->getUrl() . $nota . "\n",
                FILE_APPEND
            );
        }
    }
}

class Controlador
{
    private $guardiao;
    private $cache;
    private $logger;

    public function __construct($guardiao)
    {
        $this->guardiao = $guardiao;
        $this->cache = new Cache(CACHE_ATIVO, $this->guardiao);
        $this->logger = new Logger($this->guardiao);
        $this->verificaCache();

        require 'comum/php/carregador.php';

        $c = new Carregador($this->guardiao, $this->logger, $this->cache);
    }


    private function verificaCache()
    {
        $conteudo = $this->cache->buscar();

        if ($conteudo !== false) {
            $this->logger->acesso();
            echo $conteudo;
            $this->guardiao->tempo->stop();
            die();
        }
    }
}
