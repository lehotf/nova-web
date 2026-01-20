<?php

/**
 *  @var mysqli_result $link
 */
class database
{
    public $link;

    public function __construct()
    {
        $this->connect(BD);
    }

    public function connect($db)
    {
        global $l;

        /** @var string $php_errormsg */

        if ($l) {
            $l->close();
        }

        $l = new mysqli(BD_DNS, BD_LOGIN, BD_SENHA, $db);
        if (!$l) {
            die("ErrDB:" . $php_errormsg);
        }

        $this->link = $l;
        $this->link->set_charset('utf8');
    }

    public function protege($string)
    {
        $strProt = $this->link->real_escape_string($string);
        return $strProt;
    }

    public function getID($valor, $tabela, $nome = 'nome')
    {
        global $db;

        /** @var string $query */

        $result = $db->link->query("select id from $tabela where $nome = '$valor'" . $query) or die($db->link->error);
        if ($result) {
            $item = $result->fetch_array(MYSQLI_ASSOC);
            return $item['id'];
        } else {
            return -1;
        }
    }
}


function prepare_select($query, $type, $param)
{
    global $db;

    $statement = $db->link->prepare("select " . $query);
    $statement->bind_param($type, $param);
    $statement->execute();
    $result = $statement->get_result();

    if (($result) && ($result->num_rows)) {
        return $result->fetch_array(MYSQLI_ASSOC);
    } else {
        return false;
    }
}

function select($query)
{
    global $db;

    $result = $db->link->query("select " . $query) or die($db->link->error);

    if ($result) {
        if ($result->num_rows) {
            return $result->fetch_array(MYSQLI_ASSOC);
        }
        return false;
    }
}

function v_select($query)
{
    global $db;

    $result = $db->link->query("select " . $query) or die($db->link->error);

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

function f_select($query, $function)
{
    global $db;

    $result = $db->link->query("select " . $query) or die($db->link->error);

    if ($result) {
        if ($result->num_rows) {
            foreach ($result as $linha) {
                $function($linha);
            }
        }
    }
}

function query($query)
{
    global $db;
    return $db->link->query($query) or die($db->link->error);
}

function queryXHR($query)
{
    global $db;
    global $o;
    return $db->link->query($query) or $o->envia->erro($db->link->error);
}

function getId() {}

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
