<?php

class Guardiao
{
    const TTL = 30;
    const TTL_ACESSOS = 5;
    const MAX_ACESSOS = 5;
    private $ip;
    private $url;

    public function __construct()
    {
        $this->ip = $this->resolverIp();

        if ($this->ipEmListaNegra()) {
            $this->pnf();
        }
        $this->registrarAcesso();
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

    public function exibirRelatorio()
    {
        $info = apcu_cache_info(true);
        $entradas = isset($info['cache_list']) ? $info['cache_list'] : [];

        $negra = [];
        $branca = [];
        $acessos = [];

        foreach ($entradas as $item) {
            if (!isset($item['info'])) {
                continue;
            }
            $chave = $item['info'];
            if (strpos($chave, 'negra_') === 0) {
                $negra[] = $chave;
            } elseif (strpos($chave, 'branca_') === 0) {
                $branca[] = $chave;
            } elseif (strpos($chave, 'acesso_') === 0) {
                $acessos[] = $chave;
            }
        }

        echo 'Relatório do Guardião<BR>';
        echo 'IP atual: ' . $this->ip . '<BR>';
        echo 'URL atual: ' . $this->url . '<BR>';
        echo 'Lista negra: ' . count($negra) . '<BR>';
        echo 'Lista branca: ' . count($branca) . '<BR>';
        echo 'Acessos (janela 5s): ' . count($acessos) . '<BR>';
        echo 'IP atual na negra: ' . ($this->ipEmListaNegra() ? 'sim' : 'nao') . '<BR>';
        echo 'IP atual na branca: ' . ($this->ipEmListaBranca() ? 'sim' : 'nao') . '<BR>';
        $dadosAcesso = apcu_fetch($this->chaveListaAcessos());
        if (is_array($dadosAcesso)) {
            $inicio = isset($dadosAcesso['inicio']) ? $dadosAcesso['inicio'] : 0;
            $quantidade = isset($dadosAcesso['quantidade']) ? (int) $dadosAcesso['quantidade'] : 0;
            $agora = time();
            $restante = ($inicio > 0) ? max(0, self::TTL_ACESSOS - ($agora - $inicio)) : 0;
            echo 'Acessos IP atual: ' . $quantidade . '<BR>';
            echo 'Janela iniciada em: ' . ($inicio ? date('d/m/Y H:i:s', $inicio) : 'n/d') . '<BR>';
            echo 'Tempo restante da janela: ' . $restante . 's<BR>';
        } else {
            echo 'Acessos IP atual: 0<BR>';
            echo 'Janela iniciada em: n/d<BR>';
            echo 'Tempo restante da janela: 0s<BR>';
        }
        echo 'Chaves negra: ' . implode(', ', $negra) . '<BR>';
        echo 'Chaves branca: ' . implode(', ', $branca) . '<BR>';
        echo 'Chaves acessos: ' . implode(', ', $acessos) . '<BR>';
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
        if (DEBUG) {
            echo "DEBUG: PNF<BR>";            
            echo "URL: " . $this->url;
            $this->exibirRelatorio();
            die();
        }

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

    private function chaveListaAcessos()
    {
        return 'acesso_' . $this->ip;
    }

    private function registrarAcesso()
    {
        $chave = $this->chaveListaAcessos();
        $agora = time();

        if (apcu_exists($chave)) {
            $dados = apcu_fetch($chave);
            if (!is_array($dados)) {
                $dados = ['inicio' => $agora, 'quantidade' => 0];
            }
            $dados['quantidade'] = (int) $dados['quantidade'] + 1;
        } else {
            $dados = ['inicio' => $agora, 'quantidade' => 1];
        }

        apcu_store($chave, $dados, self::TTL_ACESSOS);

        $linha = date('d/m/Y H:i:s') . ' ' . $this->ip . ' ' . ($this->url ?? '') . ' qtd=' . $dados['quantidade'] . ' inicio=' . $dados['inicio'] . "\n";
        file_put_contents('cache/sistema/guardiao_debug', $linha, FILE_APPEND);

        if ($dados['quantidade'] >= self::MAX_ACESSOS) {
            $this->pnf();
        }
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
