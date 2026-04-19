<?php
//https://www3.bcb.gov.br/sgspub/localizarseries/localizarSeries.do?method=prepararTelaLocalizarSeries

ini_set('mysql.connect_timeout', 300);
ini_set('default_socket_timeout', 300);

date_default_timezone_set("America/Sao_Paulo");

if (file_exists("/home/leo")) {
    define("BD_LOGIN", "root");
} else {
    define("BD_LOGIN", "eupens16_site");
}

const BD_SENHA = "segredo";
const BD       = "eupens16_calculatudo";
const BD_DNS   = "localhost";

$numero_de_dias = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

$horario_de_atualizacao = date("d/m/Y H:i");
$mes_atual              = date('m');
$ano_atual              = date("Y");

$mes_cdi = $mes_atual - 1;

if ($mes_cdi == 0) {
    $mes_cdi   = 12;
    $ano_atual = $ano_atual - 1;
}

$data_cdi = $numero_de_dias[$mes_cdi - 1] . '/';

if ($mes_cdi < 10) {
    $mes_cdi = '0' . $mes_cdi;
}

$data_cdi = $data_cdi . $mes_cdi . '/' . $ano_atual;




function atualiza($codigo, $data=null)
{

    global $horario_de_atualizacao;
    try {
        // Criamos a instância e dizemos qual o endpoint do serviço
        $soap = new SoapClient("https://www3.bcb.gov.br/sgspub/JSP/sgsgeral/FachadaWSSGS.wsdl");
        // getValor é um método disponível do serviço

        if ($data) {
            $resultado = $soap->getValor($codigo, $data);
            $data = substr($data, 3);
            $valor = floatval($resultado);                    
        } else {
            $resultado = $soap->getUltimoValorVO($codigo);
            // exibimos o valor
            $valor = $resultado->ultimoValor->valor;
            $mes   = $resultado->ultimoValor->mes;
            if ($mes < 10) {
                $mes = "0" . $mes;
            }

            $data = $mes . "/" . $resultado->ultimoValor->ano;
        }

        $l = new mysqli(BD_DNS, BD_LOGIN, BD_SENHA, BD);
        $l->query("UPDATE `indices` SET `valor`=$valor,`data`='$data', `atualizacao` = '$horario_de_atualizacao' WHERE `codigo` = $codigo");
    } catch (SoapFault $fault) {
        echo "Erro: " . $fault->faultcode . " - " . $fault->faultstring;
    }
}

function atualiza_com_data_precisa($codigo)
{
    global $horario_de_atualizacao;
    try {
        // Criamos a instância e dizemos qual o endpoint do serviço
        $soap = new SoapClient("https://www3.bcb.gov.br/sgspub/JSP/sgsgeral/FachadaWSSGS.wsdl");
        // getValor é um método disponível do serviço
        $resultado = $soap->getUltimoValorVO($codigo);
        // exibimos o valor
        $valor = $resultado->ultimoValor->valor;
        $dia   = $resultado->ultimoValor->dia;
        $mes   = $resultado->ultimoValor->mes;

        if ($dia < 10) {
            $dia = "0" . $dia;
        }

        if ($mes < 10) {
            $mes = "0" . $mes;
        }

        $data = $dia . "/" . $mes . "/" . $resultado->ultimoValor->ano;
        $l    = new mysqli(BD_DNS, BD_LOGIN, BD_SENHA, BD);
        $l->query("UPDATE `indices` SET `valor`=$valor,`data`='$data', `atualizacao` = '$horario_de_atualizacao' WHERE `codigo` = $codigo");
    } catch (SoapFault $fault) {
        echo "Erro: " . $fault->faultcode . " - " . $fault->faultstring;
    }
}

//IGPM
atualiza(189);

//POUPANÇA
atualiza_com_data_precisa(195);

//CDI
atualiza(4391, $data_cdi);

//INPC
atualiza(188);

//IPCA
atualiza(433);

//USD
atualiza_com_data_precisa(1);
