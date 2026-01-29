<?php

/**
 *  @var mysqli_result $link
 */
class database
{
    public $link;

    public function __construct($host, $login, $senha, $db)
    {
        $this->connect($host, $login, $senha, $db);
    }

    public function connect($host, $login, $senha, $db)
    {
        if ($this->link instanceof mysqli) {
            $this->link->close();
        }

        $this->link = new mysqli($host, $login, $senha, $db);
        if ($this->link->connect_error) {
            die('ErrDB:' . $this->link->connect_error);
        }
        $this->link->set_charset('utf8');
    }

    public function protege($string)
    {
        $strProt = $this->link->real_escape_string($string);
        return $strProt;
    }

    public function getID($valor, $tabela, $nome = 'nome')
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabela) || !preg_match('/^[a-zA-Z0-9_]+$/', $nome)) {
            return -1;
        }

        $stmt = $this->link->prepare("select id from $tabela where $nome = ?");
        if (!$stmt) {
            return -1;
        }
        $stmt->bind_param('s', $valor);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows) {
            $item = $result->fetch_array(MYSQLI_ASSOC);
            return $item['id'];
        }

        return -1;
    }
    public function prepare_select($query, $type, $param)
    {
        $statement = $this->link->prepare("select " . $query);
        if (!$statement) {
            return false;
        }
        $params = is_array($param) ? $param : [$param];
        $statement->bind_param($type, ...$params);
        $statement->execute();
        $result = $statement->get_result();

        if (($result) && ($result->num_rows)) {
            return $result->fetch_array(MYSQLI_ASSOC);
        } else {
            return false;
        }
    }

    public function select($query)
    {
        $result = $this->link->query("select " . $query) or die($this->link->error);

        if ($result) {
            if ($result->num_rows) {
                return $result->fetch_array(MYSQLI_ASSOC);
            }
            return false;
        }
    }

    public function v_select($query)
    {
        $result = $this->link->query("select " . $query) or die($this->link->error);

        if ($result) {
            if ($result->num_rows) {
                $vetor = [];
                foreach ($result as $linha) {
                    $vetor[] = $linha;
                }
                return $vetor;
            }
            return false;
        }
        return false;
    }

    public function f_select($query, $function)
    {
        $result = $this->link->query("select " . $query) or die($this->link->error);

        if ($result) {
            if ($result->num_rows) {
                foreach ($result as $linha) {
                    $function($linha);
                }
            }
        }
    }

    public function query($query)
    {
        return $this->link->query($query) or die($this->link->error);
    }

    public function queryXHR($query, $erroHandler = null)
    {
        $resultado = $this->link->query($query);
        if ($resultado) {
            return $resultado;
        }
        if ($erroHandler) {
            $erroHandler($this->link->error);
            return false;
        }
        die($this->link->error);
    }

    public function getId() {}
}

function url_amigavel($url)
{
    $url = strtolower($url);
    return strtr(

        $url,
        array(
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ç' => 'c',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            '*' => '',
            '%' => '',
            ' ' => '-',
            '/' => '-',
            '?' => '',
        )
    );
}
