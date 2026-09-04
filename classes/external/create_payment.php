<?php

namespace paygw_mbwaymock\external;

defined('MOODLE_INTERNAL') || die();

// Importações das classes base necessárias para criar um Web Service no Moodle
require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

/**
 * Web Service responsável por iniciar um pagamento MBWay Mock.
 * É chamado pelo JavaScript do browser quando o aluno clica em "Pagar com MBWay".
 * Herda tudo de external_api — classe base obrigatória para todos os Web Services Moodle.
 */
class create_payment extends external_api {

    /**
     * Define os parâmetros de entrada aceites por este Web Service.
     * O Moodle valida e limpa automaticamente os dados recebidos
     * com base nesta definição — proteção contra dados maliciosos.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([

            // component — identifica o plugin que originou o pagamento
            // Exemplo: enrol_fee (matrícula paga)
            'component' => new external_value(
                PARAM_COMPONENT,
                'Moodle component requesting payment'
            ),//Componente Moodle que pede o pagamento

            // paymentarea — sub-área do componente
            // Exemplo: fee
            'paymentarea' => new external_value(
                PARAM_AREA,
                'Payment area within the component'
            ),//Área de pagamento dentro do componente

            // itemid — ID do item a pagar (ex: ID do curso)
            'itemid' => new external_value(
                PARAM_INT,
                'ID of the item being paid for'
            ),//ID do item a pagar

            // description — descrição do pagamento mostrada ao aluno
            'description' => new external_value(
                PARAM_TEXT,
                'Payment description'
            ),//Descrição do pagamento
        ]);
    }

    /**
     * Lógica principal do Web Service.
     * Executado quando o browser chama este endpoint.
     *
     * @param string $component  Nome do componente Moodle
     * @param string $paymentarea Área de pagamento
     * @param int    $itemid     ID do item
     * @param string $description Descrição do pagamento
     * @return array Resultado com referência da transação e mensagem
     */
    public static function execute(
        string $component,
        string $paymentarea,
        int $itemid,
        string $description
    ): array {
        global $USER, $DB, $CFG;

        // 1. Validar e limpar os parâmetros recebidos
        // O Moodle verifica se os tipos correspondem ao definido em execute_parameters()
        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'component'   => $component,
                'paymentarea' => $paymentarea,
                'itemid'      => $itemid,
                'description' => $description,
            ]
        );

        // Obter configuração específica desta conta de pagamento (mock_url, secret_key, etc.)
        // get_gateway_configuration lê de payment_gateways.config, não de config_plugins
        $config = (object) \core_payment\helper::get_gateway_configuration(
            $component, $paymentarea, $itemid, 'mbwaymock'
        );
        $mock_url   = $config->mock_url   ?? '';
        $secret_key = $config->secret_key ?? '';

        // 3. Obter o valor e moeda a pagar definidos no curso
        $payable  = \core_payment\helper::get_payable($component, $paymentarea, $itemid);
        $amount   = $payable->get_amount();
        $currency = $payable->get_currency();

        // 4. Gerar referência única para esta transação
        // Formato: MBW- seguido de 6 caracteres hexadecimais aleatórios
        // Exemplo: MBW-A1B2C3
        $txn_ref = 'MBW-' . strtoupper(bin2hex(random_bytes(6)));

        // 5. Guardar a transação na base de dados com estado 'pending'
        // O estado será atualizado para 'success' ou 'failed' quando o webhook chegar
        $DB->insert_record('paygw_mbwaymock_transactions', (object) [
            'userid'      => $USER->id,
            'component'   => $component,
            'paymentarea' => $paymentarea,
            'itemid'      => $itemid,
            'txn_ref'     => $txn_ref,
            'amount'      => $amount,
            'currency'    => $currency,
            'status'      => 'pending',
            'timecreated' => time(),
            'timemodified'=> time(),
        ]);

        // 6. Construir o URL do webhook — é para aqui que o mock vai enviar
        // a notificação quando o pagamento for processado
        $webhook_url = (new \moodle_url(
            '/payment/gateway/mbwaymock/webhook.php'
        ))->out(false);

        // 7. Preparar o payload JSON para enviar ao mock service
        $payload = json_encode([
            'txn_ref'     => $txn_ref,
            'amount'      => $amount,
            'currency'    => $currency,
            'description' => $description,
            'webhook_url' => $webhook_url,// mock vai usar este URL para responder
            'timestamp'   => time(),
        ]);

        // 8. Assinar o payload com HMAC-SHA256
        // O mock verifica esta assinatura para confirmar que o pedido veio de uma fonte legítima (o Moodle) e não foi adulterado
        $signature = hash_hmac('sha256', $payload, $secret_key);

        // 9. Enviar pedido ao mock service via cURL
        $ch = curl_init($mock_url . '/payment/request');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Signature: ' . $signature,// assinatura no header HTTP
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response    = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 10. Verificar se o mock respondeu corretamente
        if ($http_status !== 200) {
            // Registar o erro no log do Moodle para debug
            error_log('[paygw_mbwaymock] Mock unreachable. Status: ' . $http_status);
            throw new \moodle_exception('mock_unreachable', 'paygw_mbwaymock');
        }

        // 11. Registar o evento no log para auditoria
        error_log('[paygw_mbwaymock] Payment request.'
        //Pagamento iniciado
            . ' txn_ref=' . $txn_ref
            . ' amount=' . $amount . ' ' . $currency
            . ' userid=' . $USER->id);

        return [
            'success' => true,
            'txn_ref' => $txn_ref,
            'message' => get_string('payment_pending', 'paygw_mbwaymock'),
        ];
    }

    /**
     * Define a estrutura dos dados devolvidos por este Web Service.
     * O Moodle valida a resposta com base nesta definição antes
     * de a enviar ao browser.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the request succeeded'), //Se o pedido foi aceite
            'txn_ref' => new external_value(PARAM_TEXT, 'Transaction reference'),//Referência da transação
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);//Mensagem de estado
    }
}