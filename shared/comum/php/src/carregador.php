<?php

class carregador
{
    public $amp;
    public $comando;
    private $guardiao;
    private $logger;
    private $cache;
    private $urlBase;
    private $urlSemBarra;
/**
 * [__construct description]
 * @param Guardiao $guardiao Objeto guardiao que é passado durante a criação
 * @param Cache $cache Objeto cache que é passado durante a criação
 * 1- Verifica se a página é AMP
 * 2- Identifica qual o comando dado pelo usuário
 * 3- Executa o comando
 */
    public function __construct($guardiao, $cache, $logger)
    {
        $this->guardiao = $guardiao;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->urlBase = $this->guardiao->getUrl();
        $this->verificaAMP();
        $comando = $this->identificaComando();
        $this->executaPadrao($comando);
    }

  /**
     * Converte um nome lógico de asset em path real do arquivo fonte.
     */
    private function minPathExtend($arquivo, $tipo)
    {
        preg_match('#^(comum|config)\/(.*)#', $arquivo, $nome);

        $path = (count($nome) > 1) ? $nome[1] : '';

        switch ($path) {
            case 'comum':
                return '/comum/estatico/' . $tipo . '/' . $nome[2] . '.' . $tipo;

            case 'config':
                return '/config/' . $nome[2] . '.' . $tipo;

            default:
                return '/site/estatico/' . $tipo . '/' . $arquivo . '.' . $tipo;
        }
    }

     /**
     * Converte um nome lógico de asset em path cacheado.
     */
    private function minPathToCache($filePath, $tipo)
    {
        return '/cache/' . $tipo . '/' . preg_replace('#^(comum|config)\/#', '', $filePath) . '.' . $tipo;
    }

/**
 * [verificaAMP description]
 * 1- Verifica se a página é AMP
 * 2- Caso a página seja AMP preenche $this->amp como TRUE
 * 3- Remove a terminologia /amp após a análise
 * @todo Verificar possibilidade de remover o $amp global
 */
    public function verificaAMP(): void
    {
        global $amp;

        if (basename($this->urlBase) == 'amp') {
            $this->amp = true;
            $amp = true;
            $this->urlBase = str_replace('/amp', '', $this->urlBase);
            if ($this->urlBase === '') {
                $this->urlBase = '/';
            }
        } else {
            $this->amp = false;
            $amp = false;
        }
    }
/**
 * [getAlternativeLink description]
 * @return string Retorna o link alternativo, referente a página AMP ou a página canonical
 */
    private function getAlternativeLink()
    {
        if ($this->amp) {
            if ($this->urlBase == '/') {
                return '<link rel="canonical" href="/">';
            } else {
                return '<link rel="canonical" href="' . $this->urlBase . '">';
            }
        } else {
            if ($this->urlBase == '/') {
                return '<link rel="amphtml" href="/amp">';
            } else {
                return '<link rel="amphtml" href="' . $this->urlBase . '/amp' . '">';
            }
        }
    }

    public function prepara($tipo, $param = null): void
    {
        $tipo = $this->amp ? ($tipo . '_amp') : $tipo;

        if (isset($param['js'])) {
            $param['javascript'] = $this->montaPath($param['js'], 'js');
        }

        if (isset($param['noCache'])) {
            $this->cache->setCache(false);
            unset($param['noCache']);
        }

        if ($this->cache->getCache() && ! DEBUG) {
            $this->logger->acesso('cache criado');
            if (! $this->amp) {
                $param['javascript'] = isset($param['javascript']) ? $param['javascript'] . adsense('pagelevel') : adsense('pagelevel');
            }
        }

        if (isset($param['css'])) {
            $param['css'] = $this->montaPath($param['css'], 'css');
        }

        if (! isset($param['image'])) {
            $param['image'] = DNS_SITE . '/cache/img/openfbimg.jpg';
        }

        $param['site_name'] = SITE_NAME;
        $param['site']      = SITE;

        if (AMP) {
            if (! array_key_exists('alternative_link', $param)) {
                $param['alternative_link'] = $this->getAlternativeLink();
            }
        }

        $texto = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/template/' . $tipo . '.html');

        $param['url'] = DNS_SITE . $this->urlBase;
        $funcao       = function ($matches) use ($param) {
            if (isset($param[$matches[1]])) {
                return $param[$matches[1]];
            } else {
                return '';
            }
        };

        $texto = preg_replace_callback('#\[([a-zA-Z_]{3,})\]#', $funcao, $texto);

        if (! $this->cache->getCache()) {
            $texto = str_replace(adsense('article'), '', $texto);
            $texto = str_replace(adsense('feed'), '', $texto);
            $texto = str_replace(adsense('arranhaceu'), '', $texto);
        }

        $this->cache->salvar($texto);
    }

    private function montaObjeto($path, $tipo)
    {
        switch ($tipo) {
            case 'js':
                return '<script src="' . $path . '"></script>';
                break;
            case 'css':
                return '<link rel="STYLESHEET" type="text/css" href="' . $path . '"/>';
                //return '<link rel="preload" href="' . $path . '" as="style" onload="this.rel=\'stylesheet\'"><noscript><link rel="stylesheet" href="' . $path . '"></noscript>';
                break;
        }
    }

    public function montaPath($vetor, $tipo)
    {
        $codigo = '';
        if (DEBUG) {
            foreach ($vetor as $arquivo) {
                $codigo = $codigo . $this->montaObjeto($this->minPathExtend($arquivo, $tipo), $tipo);
            }
        } else {
            foreach ($vetor as $arquivo) {
                $codigo = $codigo . $this->montaObjeto($this->minPathToCache($arquivo, $tipo), $tipo);
            }
        }
        return $codigo;
    }



    public function localizaPath($comando)
    {
        $caminho = $_SERVER['DOCUMENT_ROOT'] . '/site/php/path/';

        // Tenta primeiro o comando direto
        if (file_exists($caminho . $comando . '.php')) {
            require $caminho . $comando . '.php';
            return;
        }

        // Tenta a URL completa
        if (file_exists($caminho . $this->urlSemBarra . '.php')) {
            require $caminho . $this->urlSemBarra . '.php';
            return;
        }

        // Tenta remover segmentos do final progressivamente
        $partes = explode('/', $this->urlSemBarra);
        while (count($partes) > 1) {
            array_pop($partes);
            $tentativa = implode('/', $partes);
            if (file_exists($caminho . $tentativa . '.php')) {
                require $caminho . $tentativa . '.php';
                return;
            }
        }

        // Se não encontrou, tenta no comum
        $this->localizaPathComum($comando);
    }

    public function localizaPathComum($comando)
    {
        $caminho = $_SERVER['DOCUMENT_ROOT'] . '/comum/php/path/';

        // Tenta primeiro o comando direto
        if (file_exists($caminho . $comando . '.php')) {
            require $caminho . $comando . '.php';
            return;
        }

        // Tenta a URL completa
        if (file_exists($caminho . $this->urlSemBarra . '.php')) {
            require $caminho . $this->urlSemBarra . '.php';
            return;
        }

        // Tenta remover segmentos do final progressivamente
        $partes = explode('/', $this->urlSemBarra);
        while (count($partes) > 1) {
            array_pop($partes);
            $tentativa = implode('/', $partes);
            if (file_exists($caminho . $tentativa . '.php')) {
                require $caminho . $tentativa . '.php';
                return;
            }
        }

        // Se não encontrou em lugar nenhum, retorna 404
        $this->guardiao->pnf();
    }

    private function identificaComando()
    {
        if (basename($this->urlBase) == 'nocache') {
            $this->urlBase = str_replace('/nocache', '', $this->urlBase);
            if ($this->urlBase === '') {
                $this->urlBase = '/';
            }
            $this->cache->setCache(false);
        }

        $this->urlSemBarra = ltrim($this->urlBase, '/');
        $this->comando     = explode('/', $this->urlSemBarra);

        if ($this->comando[0] == '') {
            return 'root';
        } else {
            return array_shift($this->comando);
        }
    }

    private function executaPadrao($comando)
    {
        $db = new database('localhost', BD_LOGIN, BD_SENHA, BD);
            
        if ($comando == 'root') {
            require 'site/php/path/root.php';
        } else {
            require 'site/php/path/' . PADRAO . '.php';
        }
    }
}
