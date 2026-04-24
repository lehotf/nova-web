<?php
class autenticador
{

    private $observador;
    private $db;
    private $guardiao;

    public function __construct(database $db, observador $observador = null, guardiao $guardiao = null)
    {
        $this->db = $db;
        $this->observador = $observador;
        $this->guardiao = $guardiao;
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

        if ((session_status() !== PHP_SESSION_ACTIVE) || !isset($_SESSION['autorizacao'])) {
           $this->cookie();           
        }

        if ($_SESSION['autorizacao'] < $acesso) {
            $this->acesso_negado();
        }

    }



    /**
     *
     *  @return bool
     */
    public function login($login, $senha)
    {
        $row = $this->db->select(
            "id, nome, senha, autorizacao from usuario where login = ?",
            's',
            $login
        );

        if (!$row) {
            $this->guardiao->adicionarListaNegra();
            return false;
        }

        $nome = $row['nome'];
        $bd_senha = $row['senha'];
        $id = $row['id'];
        $autorizacao = $row['autorizacao'];
        
        if ((md5($bd_senha . md5($_SERVER['REMOTE_ADDR']))) == ($senha)) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION['nome'] = $nome;
            $_SESSION['autorizacao'] = $autorizacao;
            $_SESSION['id'] = $id;
            setcookie('login', $login, time() + 2592000, '/');
            setcookie('token', $senha, time() + 2592000, '/');
            return true;
        } else {
            setcookie('token', '');
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['autorizacao'] = 0;
            }
            $this->guardiao->adicionarListaNegra();
            return false;
        }
    }

    public function cookie()
    {
    
        if (!isset($_COOKIE['token'])) {
            $this->acesso_negado();
        }

        $login = $_COOKIE['login'];
        $token = $_COOKIE['token'];

        if (!$this->login($login, $token)) {
            $this->acesso_negado();
        }
    }


    public function acesso_negado()
    {
            if (!$this->observador) {
                http_response_code(404);
                die();
            }

            $this->observador->erro('Você não está autenticado');
    }

}
