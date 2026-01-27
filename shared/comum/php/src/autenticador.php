<?php
class autenticador
{

    private $o;
    private $db;
    private $link;

    public function __construct($observador)
    {
        $this->db = $observador->db;
        $this->o = $observador;
        $this->link = $this->db->link;
    }

    /**
     * Caso a variável $_SESSION['acessos'] esteja contida em $acesso
     * o acesso será autorizado. Caso contrário, será testado se o cookie
     * do usuário contém dados de acesso. Para isso será utilizada a função
     * verifica_cookie(). Não encontrada informação de login, será redirecionado
     * para página de erro.
     * @param string e $acesso
     */
    public function acesso($acesso)
    {       
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->o->erro('Acesso Negado');
        }

        if (!isset($_SESSION['autorizacao'])) {
           if (!$this->cookie()) {
               $this->o->erro('Acesso Negado');
           }
        }

        if ($_SESSION['autorizacao'] < $acesso) {
            $this->o->erro('Acesso Negado');
        }

    }

    /**
     *
     *  @return bool
     */
    public function login($login, $senha)
    {
        $login = $this->db->protege($login);
        $result = $this->link->query("SELECT id, nome, senha, autorizacao, idioma from usuario where login = '$login'");
        if (!$result) {
            $this->o->guardiao->adicionarListaNegra();
            $this->o->erro($this->link->error, 500);
        }

        $bd_senha = '';
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $nome = $row['nome'];
            $bd_senha = $row['senha'];
            $id = $row['id'];
            $autorizacao = $row['autorizacao'];
            $idioma = $row['idioma'];
        }

        if ((md5($bd_senha . md5($_SERVER['REMOTE_ADDR']))) == ($senha)) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION['nome'] = $nome;
            $_SESSION['autorizacao'] = $autorizacao;
            $_SESSION['id'] = $id;
            $_SESSION['idioma'] = $idioma;
            setcookie('login', $login, time() + 2592000, '/');
            setcookie('token', $senha, time() + 2592000, '/');
            if ($this->o) {
                $this->o->dados['nome'] = $nome;
                $this->o->dados['idioma'] = $idioma;
            }

            return true;
        } else {
            setcookie('token', '');
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['autorizacao'] = 0;
            }
            $this->o->guardiao->adicionarListaNegra();
            return false;
        }
    }

    public function cookie()
    {
        /*
         * To change this template, choose Tools | Templates
         * and open the template in the editor.
         */
        if (!isset($_COOKIE['token'])) {
            $this->o->erro('Você não está autenticado');
        }

        $login = $this->db->protege($_COOKIE['login']);
        $token = $this->db->protege($_COOKIE['token']);

        return $this->login($login, $token);
    }

}
