<?php

// db/access.php — q permissões o plugin introduz no sistema e quem as tem por omissão.


defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Permissão: configurar o gateway MBWay Mock no painel de administração
    // Nome no formato: paygw_mbwaymock/nome_da_permissao
    'paygw_mbwaymock/manage' => [

        'riskbitmask' => RISK_CONFIG,// RISK_CONFIG: quem tem esta permissão pode alterar configurações do sistema
        
        'captype' => 'write', // porque altera configurações (não é só leitura)

        'contextlevel' => CONTEXT_SYSTEM, // aplica-se ao Moodle inteiro, não a um curso específico

        // Quem tem esta permissão por omissão:
        // Apenas 'manager' (administrador do Moodle)
        // editingteacher foi excluído — professores não devem configurar gateways de pagamento
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];