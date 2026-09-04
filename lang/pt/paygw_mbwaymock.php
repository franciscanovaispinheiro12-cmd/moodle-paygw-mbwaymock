<?php

defined('MOODLE_INTERNAL') || die();

// Nome do plugin — aparece na lista de gateways no admin do Moodle
$string['pluginname'] = 'MBWay Mock';

// Descrição curta do plugin
$string['pluginname_desc'] = 'Simula o processamento de pagamentos MBWay através de um serviço mock.';

// Nome e descrição mostrados no seletor de pagamento
$string['gatewayname']        = 'MB WAY';
$string['gatewaydescription'] = '<style>
.custom-control.mbwaymock{margin:10px 0!important;padding-left:1.5rem!important}
.custom-control.mbwaymock .custom-control-label{display:block!important;padding:15px 15px 8px!important}
.custom-control.mbwaymock .custom-control-label *{margin-bottom:0!important}
.custom-control.mbwaymock .custom-control-label p.h3{font-size:1.2rem!important;font-weight:600!important}
</style>Pagamento instant&acirc;neo e seguro.<br>Confirme com o seu telem&oacute;vel em segundos.<br><div style="display:inline-flex;align-items:center;gap:4px;margin-top:6px;"><div style="border-top:1px solid #c8102e;border-bottom:1px solid #c8102e;padding:2px 5px;line-height:1;"><span style="font-size:10px;font-weight:600;color:#222;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;">MB</span></div><span style="font-size:9px;font-weight:300;color:#222;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;letter-spacing:.3px;">way</span></div>';

// --- Campos do formulário de configuração do gateway (admin) ---

//URL do mock service
$string['mock_url'] = 'URL do Serviço Mock';

// Texto de ajuda explicativo do campo URL
$string['mock_url_help'] = 'O endereço onde o serviço mock MBWay está a correr. Exemplo: https://moodle.a40.pt/mbway-mock';

//chave secreta
$string['secret_key'] = 'Chave Secreta';

// Texto de ajuda do campo chave secreta
$string['secret_key_help'] = 'Chave secreta partilhada utilizada para assinar e verificar a comunicação entre o Moodle e o serviço mock. Tem de ser idêntica em ambos os lados.';

//modo de simulação
$string['sim_mode'] = 'Modo de Simulação';

// Texto de ajuda do modo de simulação
$string['sim_mode_help'] = 'Controla como o serviço mock decide o resultado do pagamento.';

// Opções do modo de simulação

//resultado aleatório com probabilidades definidas
$string['sim_auto']        = 'Automático (80% sucesso, 15% falha, 5% pendente)';

//resultado controlado por ficheiro no servidor
$string['sim_manual']      = 'Manual (requer ficheiro de override)';

//todos os pagamentos falham (útil para testar tratamento de erros)
$string['sim_always_fail'] = 'Sempre Falha (para testes de erro)';

// --- Modal de pagamento (visível ao aluno durante o pagamento) ---

// Texto do botão de pagamento que o aluno vê no curso
$string['pay_with_mbway'] = 'Pagar com MBWay (Mock)';

// Mensagem mostrada enquanto o pagamento está a ser processado
$string['payment_pending'] = 'O seu pagamento está a ser processado. Por favor aguarde...';

// Mensagem de pagamento confirmado
$string['confirm_payment']     = 'Confirmar Pagamento';

// Etiqueta do campo de número de telemóvel no modal de pagamento
$string['phone_number'] = 'Número de telemóvel';

// Texto de ajuda do campo de telemóvel — indica o formato esperado
$string['phone_number_help'] = 'Introduza o seu número de telemóvel português com 9 dígitos (sem indicativo de país).';

// Instrução mostrada ao aluno após clicar em confirmar
$string['payment_instruction'] = 'Após confirmar, o pagamento será simulado. Por favor aguarde neste ecrã.';

// Título do aviso de ambiente de teste no modal de pagamento
$string['mock_info_title'] = 'Ambiente de Teste';

// Descrição do aviso de ambiente de teste — informa que não é pagamento real
$string['mock_info'] = 'Este é um gateway MBWay simulado. Não será processado nenhum pagamento real.';

// Mensagem de validação quando o número de telemóvel introduzido não é válido
$string['invalid_phone'] = 'Por favor introduza um número de telemóvel válido com 9 dígitos.';

// --- Mensagens de estado do pagamento --- 

// Mensagem de sucesso após pagamento confirmado
$string['payment_success'] = 'Pagamento efectuado com sucesso! Está agora inscrito no curso.';

// Mensagem de falha no pagamento
$string['payment_failed'] = 'O pagamento falhou. Por favor tente novamente ou contacte o suporte.';

// --- Mensagens de erro técnico ---

// Erro quando o Moodle não consegue ligar ao mock service
$string['mock_unreachable'] = 'Não foi possível ligar ao serviço mock MBWay. Por favor verifique a configuração.';

// Erro quando a assinatura HMAC do webhook não é válida
$string['invalid_signature'] = 'Assinatura do webhook inválida. Pedido rejeitado.';

// Erro quando a transação não é encontrada na base de dados
$string['transaction_not_found'] = 'Transação não encontrada.';

// --- RGPD — textos da página de privacidade ---

// Descrição geral dos dados pessoais guardados pelo plugin — página de privacidade RGPD
$string['privacy:metadata'] = 'O plugin MBWay Mock armazena referências de transações e estado do pagamento para processamento de inscrições.';

// Descrição da tabela de transações na página de privacidade RGPD
$string['privacy:metadata:transactions'] = 'Registos de transações de cada tentativa de pagamento.';

// Descrição do campo userid na tabela de transações
$string['privacy:metadata:transactions:userid'] = 'O ID do aluno que iniciou o pagamento.';

// Descrição do campo component na tabela de transações
$string['privacy:metadata:transactions:component'] = 'O componente Moodle que pediu o pagamento (ex: enrol_fee).';

// Descrição do campo paymentarea na tabela de transações
$string['privacy:metadata:transactions:paymentarea'] = 'A área de pagamento dentro do componente (ex: fee).';

// Descrição do campo itemid na tabela de transações
$string['privacy:metadata:transactions:itemid'] = 'O ID do item a pagar (ex: ID do curso).';

// Descrição do campo txn_ref na tabela de transações
$string['privacy:metadata:transactions:txn_ref'] = 'Referência única da transação gerada pelo plugin.';

// Descrição do campo amount na tabela de transações
$string['privacy:metadata:transactions:amount'] = 'Montante do pagamento.';

// Descrição do campo currency na tabela de transações
$string['privacy:metadata:transactions:currency'] = 'Código de moeda ISO 4217 (ex: EUR).';

// Descrição do campo status na tabela de transações
$string['privacy:metadata:transactions:status'] = 'Estado da transação: pending, success ou failed.';

// Descrição do campo timecreated na tabela de transações
$string['privacy:metadata:transactions:timecreated'] = 'Timestamp de criação da transação.';

// Descrição do campo timemodified na tabela de transações
$string['privacy:metadata:transactions:timemodified'] = 'Timestamp da última atualização de estado.';