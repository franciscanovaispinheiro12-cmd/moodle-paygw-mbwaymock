<?php

defined('MOODLE_INTERNAL') || die();


// 'paygw' = payment gateway | 'mbwaymock' = nome do plugin
$plugin->component = 'paygw_mbwaymock';


$plugin->version = 2025051401;

$plugin->requires = 2022041900; // = Moodle 4.0 — necessário porque a Payment API só existe a partir daqui.

// Nível de maturidade do plugin
$plugin->maturity = MATURITY_BETA;// funcional mas ainda em fase de testes

// Versão legível — aparece no painel de administração do Moodle
$plugin->release = '1.0.0';