<?php
$a = new autenticador($this->guardiao);
$a->acesso(2);

if(DEBUG){
	$conteudo = '<div style="position: absolute;right: 10px;top: 45px;color: #368241;">debug mode</div>';
}else{
	$conteudo = '';
}
     
$this->prepara('_adm', [
    'js' => ['comum/geral','comum/md5', 'comum/web', 'comum/form', 'comum/lista','comum/adm_7'],
    'noCache' => true,
    'conteudo' => $conteudo,
    'css' => ['comum/adm']
]);