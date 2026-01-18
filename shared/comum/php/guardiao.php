<?php

class Guardiao
{
    const TTL = 30;
    private $ip;
    private $url;

    public function __construct()
    {
        $this->ip = $this->resolverIp();
        $this->url = $this->resolverUrl();
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
        $chave = $this->chaveListaNegra();
        if (apcu_exists($chave)) {            
            return true;
        }

        return false;
    }

    public function adicionarListaNegra()
    {
        return apcu_store($this->chaveListaNegra(), true, self::TTL);
    }

    public function ipEmListaBranca()
    {
        $chave = $this->chaveListaBranca();
        if (apcu_exists($chave)) {            
            return true;
        }

        return false;
    }


    public function adicionarListaBranca()
    {        
        return apcu_store($this->chaveListaBranca(), true, self::TTL);
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
        if ($this->ipEmListaNegra()) {
            $this->adicionarListaNegra(); #Apenas para renovar o TTL
            http_response_code(404);
            die();
        }

        if ($this->ipEmListaBranca()) {
            $this->adicionarListaBranca(); #Apenas para renovar o TTL
            http_response_code(404);
            die();
        }

        if ($this->validarGooglebot()) {
            $this->adicionarListaBranca(); 
            http_response_code(404);
            die();
        }

        $this->adicionarListaNegra();
        http_response_code(404);
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

    private function chaveListaNegra()
    {
        return 'negra_' . $this->ip;
    }

    private function chaveListaBranca()
    {
        return 'branca_' . $this->ip;
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