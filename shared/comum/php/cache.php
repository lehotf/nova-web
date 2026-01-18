<?php
/**
 * Classe GerenciadorCache
 *
 * Responsavel por localizar e retornar paginas HTML ja geradas
 * no diretorio de cache do site atual.
 */
class GerenciadorCache
{
    /**
     * @var string Diretorio base do cache HTML do site
     */
    private $diretorioCache;

    /**
     * @var bool Define se o cache deve ser usado
     */
    private $cacheAtivo = true;

    /**
     * @param string $diretorioCache Caminho do diretorio de cache HTML
     * @param bool $cacheAtivo Se true, o cache sera utilizado
     */
    public function __construct($diretorioCache, $cacheAtivo = true)
    {
        $this->diretorioCache = rtrim($diretorioCache, '/');
        $this->cacheAtivo = (bool) $cacheAtivo;
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
     * @param string $url URL da pagina solicitada
     * @return string|false Conteudo do cache ou false se nao existir
     */
    public function buscar($url)
    {
        if (! $this->cacheAtivo) {
            return false;
        }

        $caminhoArquivo = $this->montarCaminho($url);

        if (is_file($caminhoArquivo)) {
            return file_get_contents($caminhoArquivo);
        }

        return false;
    }

    /**
     * Monta o caminho do arquivo de cache com base na URL
     *
     * @param string $url
     * @return string
     */
    private function montarCaminho($url)
    {
        $url = $this->limparUrl($url);

        if ($url === '/') {
            return $this->diretorioCache . '/root';
        }

        $arquivo = str_replace('/', '.', ltrim($url, '/'));
        return $this->diretorioCache . '/' . $arquivo;
    }

    /**
     * Limpa a URL removendo query string e caracteres nao permitidos
     *
     * @param string $url
     * @return string
     */
    private function limparUrl($url)
    {
        $url = strtok($url, '?');
        $url = preg_replace('#[^a-zA-Z0-9\-\/]#', '', $url);

        if ($url === '') {
            return '/';
        }

        if ($url[0] !== '/') {
            $url = '/' . $url;
        }

        return $url;
    }
}
