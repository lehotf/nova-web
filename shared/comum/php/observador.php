<?php
require $_SERVER['DOCUMENT_ROOT'] . '/comum/php/db.php';

class observador
{
    private $db;
    private $link;
    public $input;
    public $dados;

    public function __construct()
    {
        $this->db = new database('localhost', BD_LOGIN, BD_SENHA, BD);
        $this->link = $this->db->link;
        $this->input = $this->carregar_json();
        $this->dados = $this->sanitiza($this->input);
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

    private function sanitiza($valor)
    {
        if (is_array($valor)) {
            $limpo = [];
            foreach ($valor as $chave => $item) {
                $limpo[$chave] = $this->sanitiza($item);
            }
            return $limpo;
        }

        if (is_string($valor)) {
            return $this->db->protege($valor);
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

    public function salva($tabela, $dados = null, $campoId = 'id')
    {
        if (!$this->nomeValido($tabela) || !$this->nomeValido($campoId)) {
            $this->erro('tabela ou campo invalido');
        }

        $dados = is_array($dados) ? $dados : $this->dados;
        $dados = $this->sanitiza($dados);

        if (array_key_exists($campoId, $dados)) {
            $id = $dados[$campoId];
            unset($dados[$campoId]);

            $sets = [];
            foreach ($dados as $campo => $valor) {
                if (!$this->nomeValido($campo)) {
                    continue;
                }
                $sets[] = "`$campo`='" . $valor . "'";
            }

            if (!$sets) {
                return $id;
            }

            $sql = "UPDATE `$tabela` SET " . implode(',', $sets) . " WHERE `$campoId`='" . $id . "'";
            $this->query($sql);
            return $id;
        }

        $campos = [];
        $valores = [];
        foreach ($dados as $campo => $valor) {
            if (!$this->nomeValido($campo)) {
                continue;
            }
            $campos[] = "`$campo`";
            $valores[] = "'" . $valor . "'";
        }

        if (!$campos) {
            return null;
        }

        $sql = "INSERT INTO `$tabela` (" . implode(',', $campos) . ") VALUES (" . implode(',', $valores) . ")";
        $this->query($sql);
        return $this->link->insert_id;
    }

    public function query($query)
    {
        $resultado = $this->link->query($query);
        if ($resultado) {
            return $resultado;
        }
        $this->erro($this->link->error, 500);
    }

    public function responde($dados = null, $status = 'ok', $msg = null, $codigo = 200)
    {
        $resp = ['cabecalho' => ['status' => $status]];
        if ($msg !== null) {
            $resp['cabecalho']['msg'] = $msg;
        }
        if ($dados !== null) {
            $resp['dados'] = $dados;
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code($codigo);
        die(json_encode($resp));
    }

    public function erro($msg, $codigo = 400)
    {
        $this->responde(null, 'erro', $msg, $codigo);
    }
}
