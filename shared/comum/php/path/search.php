<?php

$conteudo = '<div class="divisor_fixo margin_bottom_20"><div id="realsearch"><script async src="https://cse.google.com/cse.js?cx='.SEARCH.'"></script><div class="gcse-search"></div></div></div>';

$this->prepara('index', [
    'conteudo'   => $conteudo,
    'structured' => '<meta name="robots" content="noindex">',
    'css' => ['comum/search'],
    'alternative_link' => '',
    'sidebar'     => '<div class="divisor_fixo"><div id="arranhaceu">' . adsense('retangulo') . '</div></div>'
]);
