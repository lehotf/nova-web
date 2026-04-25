<?php

class observador
{
    public $db;
    public $input;
    public $dados;
    public $r; #dados para resposta
    private $instrucao;

    public function __construct($db)
    {
        $this->db = $db;
        $this->input = $this->carregar_json();
        $this->dados = $this->normaliza($this->input);
        $this->r = [];
    }

    private function carregar_json()
    {
        $conteudo = file_get_contents('php://input');
        if (!$conteudo) {
            return [];
        }

        $json = json_decode($conteudo, true);
        return is_array($json) ? $json : [];
    }

    private function normaliza($valor)
    {
        if (is_array($valor)) {
            $limpo = [];
            foreach ($valor as $chave => $item) {
                $limpo[$chave] = $this->normaliza($item);
            }
            return $limpo;
        }

        return $valor;
    }

    public function numero($campo, $min = 0, $max = 0)
    {
        if (!$this->dados || !array_key_exists($campo, $this->dados)) {
            $numero = 0;
        } else {
            $numero = is_numeric($this->dados[$campo]) ? $this->dados[$campo] + 0 : 0;
        }

        if ($min !== 0 && $numero < $min) {
            $this->erro($campo . ' menor que ' . $min);
        }

        if ($max !== 0 && $numero > $max) {
            $this->erro($campo . ' maior que ' . $max);
        }

        return $numero;
    }

    public function texto($campo)
    {
        if (!$this->dados || !array_key_exists($campo, $this->dados)) {
            $texto = '';
        } else {
            $texto = is_string($this->dados[$campo]) ? $this->dados[$campo] : '';
        }

        return $texto;
    }

    private function nomeValido($nome)
    {
        return preg_match('/^[a-zA-Z0-9_]+$/', $nome);
    }

    private function tipoValor($campo, $valor)
    {
        $tipo = $this->instrucao[$campo]['tipo'] ?? 'string';
        if ($tipo === 'numero') {
            return is_numeric($valor) ? $valor + 0 : 0;
        }
        return "'" . $valor . "'";
    }

    private function vetor($campo, $elementoUnico = false)
    {
        if (!$this->dados || !array_key_exists($campo, $this->dados)) {
            return false;
        }

        $vetor = $this->dados[$campo];
        if (!is_array($vetor) || empty($vetor)) {
            return false;
        }

        if ($elementoUnico) {
            return $vetor[0];
        }

        return $vetor;
    }

    private function getVetElements($nomeDoVetor, $elementos, $valores)
    {
        $set = [];
        if (is_array($valores)) {
            foreach ($valores as $valor) {
                $set[$valor] = true;
            }
        }

        foreach ($elementos as $elemento) {
            $this->dados[$elemento] = isset($set[$elemento]) ? 1 : 0;
            $this->instrucao[$elemento]['tipo'] = 'numero';
        }
    }

    public function valida($vet)
    {
        $this->instrucao = is_array($vet) ? $vet : [];

        if (!$this->dados) {
            return $this->dados;
        }

        foreach ($this->dados as $campoRecebido => $valor) {
            if ($campoRecebido === 'botao') {
                $this->instrucao[$campoRecebido] = ['salva' => false];
            }

            if (!array_key_exists($campoRecebido, $this->instrucao)) {
                $this->instrucao[$campoRecebido] = ['tipo' => 'string'];
            } else {
                if (!array_key_exists('tipo', $this->instrucao[$campoRecebido])) {
                    $this->instrucao[$campoRecebido]['tipo'] = 'string';
                }
            }

            if ($this->instrucao[$campoRecebido]['tipo'] === 'string') {
                $this->dados[$campoRecebido] = $this->texto($campoRecebido);
            } elseif ($this->instrucao[$campoRecebido]['tipo'] === 'numero') {
                $min = $this->instrucao[$campoRecebido]['min'] ?? 0;
                $max = $this->instrucao[$campoRecebido]['max'] ?? 0;
                $this->dados[$campoRecebido] = $this->numero($campoRecebido, $min, $max);
            } elseif ($this->instrucao[$campoRecebido]['tipo'] === 'vetor') {
                $elementoUnico = array_key_exists('elementoUnico', $this->instrucao[$campoRecebido]);
                $this->dados[$campoRecebido] = $this->vetor($campoRecebido, $elementoUnico);
                if (array_key_exists('elementos', $this->instrucao[$campoRecebido])) {
                    $this->instrucao[$campoRecebido]['salva'] = false;
                    $valores = is_array($this->dados[$campoRecebido]) ? $this->dados[$campoRecebido] : [];
                    $this->getVetElements($campoRecebido, $this->instrucao[$campoRecebido]['elementos'], $valores);
                }
            }
        }

        return $this->dados;
    }

    public function salva($tabela, $dados = null, $campoId = 'id')
    {
        if (!$this->nomeValido($tabela) || !$this->nomeValido($campoId)) {
            $this->erro('tabela ou campo invalido');
        }

        $dados = is_array($dados) ? $dados : $this->dados;
        $this->instrucao = is_array($this->instrucao) ? $this->instrucao : [];

        if (array_key_exists($campoId, $dados)) {
            $id = $dados[$campoId];
            unset($dados[$campoId]);

            $sets = [];
            $tipos = '';
            $params = [];
            foreach ($dados as $campo => $valor) {
                if (!$this->nomeValido($campo)) {
                    continue;
                }
                if (!array_key_exists($campo, $this->instrucao)) {
                    $this->instrucao[$campo] = ['tipo' => 'string'];
                } elseif (!array_key_exists('tipo', $this->instrucao[$campo])) {
                    $this->instrucao[$campo]['tipo'] = 'string';
                }

                if (array_key_exists('salva', $this->instrucao[$campo]) && $this->instrucao[$campo]['salva'] == false) {
                    continue;
                }

                $tipoCampo = $this->instrucao[$campo]['tipo'];
                if ($tipoCampo === 'numero') {
                    $valorNormalizado = is_numeric($valor) ? ($valor + 0) : 0;
                    $tipos .= is_float($valorNormalizado) ? 'd' : 'i';
                    $params[] = $valorNormalizado;
                } else {
                    $tipos .= 's';
                    $params[] = (string) $valor;
                }

                $sets[] = "`$campo`=?";
            }

            if (!$sets) {
                return $id;
            }

            $tipoId = $this->instrucao[$campoId]['tipo'] ?? (is_numeric($id) ? 'numero' : 'string');
            if ($tipoId === 'numero') {
                $idNormalizado = is_numeric($id) ? ($id + 0) : 0;
                $tipos .= is_float($idNormalizado) ? 'd' : 'i';
                $params[] = $idNormalizado;
            } else {
                $tipos .= 's';
                $params[] = (string) $id;
            }

            $sql = "UPDATE `$tabela` SET " . implode(',', $sets) . " WHERE `$campoId`=?";
            $this->query($sql, true, $tipos, $params);
            return $id;
        }

        $campos = [];
        $tipos = '';
        $params = [];
        foreach ($dados as $campo => $valor) {
            if (!$this->nomeValido($campo)) {
                continue;
            }
            if (!array_key_exists($campo, $this->instrucao)) {
                $this->instrucao[$campo] = ['tipo' => 'string'];
            } elseif (!array_key_exists('tipo', $this->instrucao[$campo])) {
                $this->instrucao[$campo]['tipo'] = 'string';
            }

            if (array_key_exists('salva', $this->instrucao[$campo]) && $this->instrucao[$campo]['salva'] == false) {
                continue;
            }

            $campos[] = "`$campo`";
            $tipoCampo = $this->instrucao[$campo]['tipo'];
            if ($tipoCampo === 'numero') {
                $valorNormalizado = is_numeric($valor) ? ($valor + 0) : 0;
                $tipos .= is_float($valorNormalizado) ? 'd' : 'i';
                $params[] = $valorNormalizado;
            } else {
                $tipos .= 's';
                $params[] = (string) $valor;
            }
        }

        if (!$campos) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($campos), '?'));
        $sql = "INSERT INTO `$tabela` (" . implode(',', $campos) . ") VALUES (" . $placeholders . ")";
        $this->query($sql, true, $tipos, $params);
        return $this->db->link->insert_id;
    }

    public function query($query, $campoItem = true, $tipo = '', $param = null)
    {
        if ($campoItem) {
            $campo = 'item';
        } else {
            preg_match('#from ([^ ]*)#', strtolower($query), $matches);
            $campo = $matches[1] ?? 'item';
        }

        $resultado = $this->db->query($query, $tipo, $param);
        if ($resultado instanceof mysqli_result && $resultado->num_rows) {
            $this->r[$campo] = [];
            while ($row = $resultado->fetch_array(MYSQLI_ASSOC)) {
                $this->r[$campo][] = $row;
            }
        }

        return $resultado;
    }

    public function envia($msg = null, $status = 'ok')
    {
        $statusCode = $status === 'ok' ? 200 : 400;
        $resp = ['ok' => $status === 'ok'];

        if ($status === 'ok') {
            if ($msg !== null) {
                $resp['message'] = $msg;
            }
            if (!empty($this->r)) {
                $resp['data'] = $this->r;
            }
        } else {
            $resp['error'] = ['message' => $msg !== null ? $msg : 'Erro na requisição.'];
            if (!empty($this->r)) {
                $resp['data'] = $this->r;
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        die(json_encode($resp));
    }

    public function erro($msg)
    {
        $this->envia($msg, 'erro');
    }
}
