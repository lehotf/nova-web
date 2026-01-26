<?php

class guardiao
{
    const TTL = 30;
    private $ip;
    private $url;
    public $tempo;
    public $logger;
    private $emListaNegra = null;
    private $emListaBranca = null;

    public function __construct()
    {
        $this->tempo = new contador_de_tempo();
        $this->ip = $this->resolverIp();
        $this->url = $this->resolverUrl();
        $this->logger = new logger($this);

        if ($this->ipEmListaNegra()) {
            $this->pnf();
        }                
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function ipEmListaNegra()
    {
        if ($this->emListaNegra !== null) {
            return $this->emListaNegra;
        }

        $arquivo = $this->arquivoListaNegra();
        if (!is_file($arquivo)) {
            $this->emListaNegra = false;
            return false;
        }

        $agora = time();
        $modificacao = @filemtime($arquivo);

        if (($agora - $modificacao) > self::TTL) {
            @unlink($arquivo);
            $this->emListaNegra = false;
            return false;
        }

        touch($arquivo);
        $this->emListaNegra = true;
        return true;
    }

    public function adicionarListaNegra()
    {        
        touch($this->arquivoListaNegra());
        $this->emListaNegra = true;
    }

    public function ipEmListaBranca()
    {
        if ($this->emListaBranca !== null) {
            return $this->emListaBranca;
        }

        $arquivo = $this->arquivoListaBranca();
        if (!is_file($arquivo)) {
            $this->emListaBranca = false;
            return false;
        }

        $agora = time();
        $modificacao = @filemtime($arquivo);

        if (($agora - $modificacao) > 300) {
            @unlink($arquivo);
            $this->emListaBranca = false;
            return false;
        }

        touch($arquivo);
        $this->emListaBranca = true;
        return true;
    }


    public function adicionarListaBranca()
    {
        touch($this->arquivoListaBranca());
        $this->emListaBranca = true;
    }

    public function validarGooglebot()
    {
        $host = gethostbyaddr($this->ip);
        if (!$host) {
            return false;
        }

        $host = strtolower($host);
        if (!$this->hostGoogle($host)) {
            return false;
        }

        $ips = gethostbynamel($host);
        if (!$ips) {
            return false;
        }

        return in_array($this->ip, $ips, true);
    }

    public function pnf()
    {

        $this->logger->acesso_negado('PNF');
        http_response_code(404);

        if ($this->ipEmListaNegra()) {
            $this->adicionarListaNegra(); #Apenas para renovar o TTL                        
            die();
        }

        if ($this->ipEmListaBranca()) {
            $this->adicionarListaBranca(); #Apenas para renovar o TTL                        
            die();
        }

        if ($this->validarGooglebot()) {
            $this->adicionarListaBranca();                         
            die();
        } 
        
        $this->adicionarListaNegra();                
        die();
    }

    private function hostGoogle($host)
    {
        return (substr($host, -13) === '.googlebot.com') || (substr($host, -11) === '.google.com');
    }

    private function resolverIp()
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }


    private function arquivoListaNegra()
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/log/lista_negra/' . $this->nomeArquivoIp();
    }

    private function arquivoListaBranca()
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/log/lista_branca/' . $this->nomeArquivoIp();
    }

    private function nomeArquivoIp()
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $this->ip);
    }

    private function resolverUrl()
    {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $url = strtok($url, '?');
        $url = preg_replace('#[^a-zA-Z0-9\\-\\/]#', '', $url);

        if ($url === '') {
            return '/';
        }

        if ($url[0] !== '/') {
            $url = '/' . $url;
        }

        return $url;
    }

}


class contador_de_tempo
{
    private $time_start;
    private $time_end;
    private $tempo_total;

    public function __construct()
    {
        $this->time_start = microtime(true);
    }

    private function registra_fim()
    {
        $this->time_end    = microtime(true);        
        $this->tempo_total = number_format(($this->time_end - $this->time_start) * 1000, 2, ',', ".");        
    }

    public function stop()
    {
        $this->registra_fim();
        echo "<div id=\"tempo\">$this->tempo_total ms</div>";
    }

    public function get_time()
    {
        $this->registra_fim();
        return "<div id=\"tempo\">$this->tempo_total ms</div>";
    }
}



class logger
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
        if (DEBUG) {
            return;
        }

        $nota = $nota ? " [$nota]" : '';
        $linha = date('d/m/Y H:i:s') . ' ' . $this->guardiao->getIp() . ' ' . $this->guardiao->getUrl() . $nota . "\n";

        file_put_contents(
            $_SERVER['DOCUMENT_ROOT'].'/cache/sistema/acessos_negados',
            $linha,
            FILE_APPEND
        );
    }

    // Método normal de acesso (pode otimizar também se quiser)
    public function acesso($nota = null)
    {
        if (DEBUG) {
            return;
        }

        $nota = $nota ? " [$nota]" : '';
        if ($this->guardiao->ipEmListaBranca()) {
            $nota .= ' (BP)';
        }

        file_put_contents(
            $_SERVER['DOCUMENT_ROOT'].'/cache/sistema/acessos',
            date('d/m/Y H:i:s') . ' ' . $this->guardiao->getIp() . ' ' . $this->guardiao->getUrl() . $nota . "\n",
            FILE_APPEND
        );
    }
}
