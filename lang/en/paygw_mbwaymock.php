<?php

defined('MOODLE_INTERNAL') || die();

// Nome do plugin — aparece na lista de gateways no admin do Moodle
$string['pluginname'] = 'MBWay Mock';

// Nome e descrição mostrados no seletor de pagamento
$string['gatewayname']        = 'MB WAY';
$string['gatewaydescription'] = '<style>
.custom-control.mbwaymock{margin:10px 0!important;padding-left:1.5rem!important}
.custom-control.mbwaymock .custom-control-label{display:block!important;padding:15px 15px 8px!important}
.custom-control.mbwaymock .custom-control-label *{margin-bottom:0!important}
.custom-control.mbwaymock .custom-control-label p.h3{font-size:1.2rem!important;font-weight:600!important}
</style>Instant and secure payment.<br>Confirm with your mobile phone in seconds.<br><div style="display:inline-flex;align-items:center;gap:4px;margin-top:6px;"><div style="border-top:1px solid #c8102e;border-bottom:1px solid #c8102e;padding:2px 5px;line-height:1;"><span style="font-size:10px;font-weight:600;color:#222;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;">MB</span></div><span style="font-size:9px;font-weight:300;color:#222;font-family:Helvetica Neue,Helvetica,Arial,sans-serif;letter-spacing:.3px;">way</span></div>';

// Descrição curta do plugin
$string['pluginname_desc'] = 'Simulates MBWay payment processing via a mock service. For development and testing purposes only.';

// --- Campos do formulário de configuração do gateway (admin) ---

//URL do mock service
$string['mock_url'] = 'Mock Service URL';

// Texto de ajuda explicativo do campo URL
$string['mock_url_help'] = 'The URL where the MBWay mock service is running. Example: https://moodle.a40.pt/mbway-mock';

//chave secreta
$string['secret_key'] = 'Secret Key';

// Texto de ajuda do campo chave secreta
$string['secret_key_help'] = 'Shared secret key used to sign and verify communication between Moodle and the mock service. Must be identical on both sides.';

//modo de simulação
$string['sim_mode'] = 'Simulation Mode';

// Texto de ajuda do modo de simulação
$string['sim_mode_help'] = 'Controls how the mock service decides the payment result.';

// Opções do modo de simulação

//resultado aleatório com probabilidades definidas
$string['sim_auto']        = 'Auto (80% success, 15% failed, 5% pending)';

//resultado controlado por ficheiro no servidor
$string['sim_manual']      = 'Manual (requires override file)';

//todos os pagamentos falham (útil para testar tratamento de erros)
$string['sim_always_fail'] = 'Always Fail (for error testing)';

// --- Mensagens visíveis ao aluno durante o pagamento ---

// Texto do botão de pagamento que o aluno vê no curso
$string['pay_with_mbway'] = 'Pay with MBWay (Mock)';

// Mensagem de processamento do pagamento
$string['payment_pending'] = 'Your payment is being processed. Please wait...';

// Mensagem de pagamento confirmado
$string['confirm_payment']     = 'Confirm Payment';

// Etiqueta do campo de número de telemóvel no modal de pagamento
$string['phone_number'] = 'Mobile phone number';

// Texto de ajuda do campo de telemóvel — indica o formato esperado
$string['phone_number_help'] = 'Enter your 9-digit Portuguese mobile number (without country code).';

// Instrução mostrada ao aluno após clicar em confirmar
$string['payment_instruction'] = 'After confirming, the payment will be simulated. Please wait on this screen.';

// Título do aviso de ambiente de teste no modal de pagamento
$string['mock_info_title'] = 'Test Environment';

// Descrição do aviso de ambiente de teste — informa que não é pagamento real
$string['mock_info'] = 'This is a simulated MBWay gateway. No real payment will be processed.';

// Mensagem de validação quando o número de telemóvel introduzido não é válido
$string['invalid_phone'] = 'Please enter a valid 9-digit mobile phone number.';


// --- Mensagens de estado do pagamento --- 

// Mensagem de sucesso no pagamento
$string['payment_success'] = 'Payment successful! You are now enrolled in the course.';

// Mensagem de falha no pagamento
$string['payment_failed'] = 'Payment failed. Please try again or contact support.';

// --- Mensagens de erro técnico ---

// Erro quando o Moodle não consegue ligar ao mock service
$string['mock_unreachable'] = 'Could not connect to the MBWay mock service. Please check the configuration.';

// Erro quando a assinatura HMAC do webhook não é válida
$string['invalid_signature'] = 'Invalid webhook signature. Request rejected.';

// Erro quando a transação não é encontrada na base de dados
$string['transaction_not_found'] = 'Transaction not found.';

// --- RGPD — textos da página de privacidade ---

// Descrição geral dos dados pessoais guardados pelo plugin — página de privacidade RGPD
$string['privacy:metadata'] = 'The MBWay Mock gateway plugin stores transaction references and payment status for enrolment processing.';

// Descrição da tabela de transações na página de privacidade RGPD
$string['privacy:metadata:transactions'] = 'Transaction records for each payment attempt.';

// Descrição do campo userid na tabela de transações
$string['privacy:metadata:transactions:userid'] = 'The ID of the student who initiated the payment.';

// Descrição do campo component na tabela de transações
$string['privacy:metadata:transactions:component'] = 'The Moodle component that requested the payment (e.g. enrol_fee).';

// Descrição do campo paymentarea na tabela de transações
$string['privacy:metadata:transactions:paymentarea'] = 'The payment area within the component (e.g. fee).';

// Descrição do campo itemid na tabela de transações
$string['privacy:metadata:transactions:itemid'] = 'The ID of the item being paid for (e.g. course ID).';

// Descrição do campo txn_ref na tabela de transações
$string['privacy:metadata:transactions:txn_ref'] = 'Unique transaction reference generated by the plugin.';

// Descrição do campo amount na tabela de transações
$string['privacy:metadata:transactions:amount'] = 'Payment amount.';

// Descrição do campo currency na tabela de transações
$string['privacy:metadata:transactions:currency'] = 'ISO 4217 currency code (e.g. EUR).';

// Descrição do campo status na tabela de transações
$string['privacy:metadata:transactions:status'] = 'Transaction status: pending, success, or failed.';

// Descrição do campo timecreated na tabela de transações
$string['privacy:metadata:transactions:timecreated'] = 'Timestamp when the transaction was created.';

// Descrição do campo timemodified na tabela de transações
$string['privacy:metadata:transactions:timemodified'] = 'Timestamp of the last status update.';