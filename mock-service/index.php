<?php

/**
 * MBWay Mock Service — Ponto de entrada principal
 *
 * Este script simula o comportamento de um gateway de pagamento MBWay real.
 * É um servidor HTTP independente do Moodle, escrito em PHP puro,
 * que pode ser executado em qualquer servidor com PHP >= 7.4.
 *
 * Endpoints implementados:
 *   POST /payment/request  — recebe pedido de pagamento do Moodle
 *   GET  /health           — verificação de disponibilidade do serviço
 *
 * Fluxo de um pagamento:
 *   1. Moodle envia POST /payment/request com payload assinado HMAC
 *   2. Mock valida a assinatura
 *   3. Mock aguarda MBWAY_DELAY_SECONDS (simula processamento)
 *   4. Mock decide o resultado consoante MBWAY_SIM_MODE
 *   5. Mock envia webhook POST ao Moodle com o resultado, assinado HMAC
 *   6. Moodle valida a assinatura e atualiza a matrícula do aluno
 *
 * Instalação:
 *   Ver mock-service/README.md para instruções completas.
 */

require_once __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// Router simples baseado no PATH_INFO / REQUEST_URI
// ---------------------------------------------------------------------------
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Normalizar o path — remover prefixo se o mock estiver numa subdirectoria
// Ex: /mbway-mock/payment/request → /payment/request
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($scriptDir && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}
$path = '/' . ltrim($path, '/');

// Roteamento
if ($method === 'POST' && $path === '/payment/request') {
    handle_payment_request();
} elseif ($method === 'GET' && $path === '/health') {
    handle_health_check();
} else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Endpoint not found', 'path' => $path]);
}


// ---------------------------------------------------------------------------
// Handler: POST /payment/request
// Recebe e processa um pedido de pagamento vindo do Moodle.
// ---------------------------------------------------------------------------
function handle_payment_request(): void {

    mock_log('info', 'Incoming payment request');

    // 1. Ler e validar o corpo JSON
    $raw_body = file_get_contents('php://input');
    $payload  = json_decode($raw_body, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($payload)) {
        http_response_code(400);
        json_response(['error' => 'Invalid JSON payload']);
        return;
    }

    // 2. Verificar campos obrigatórios
    $required = ['txn_ref', 'amount', 'currency', 'webhook_url', 'timestamp'];
    foreach ($required as $field) {
        if (!isset($payload[$field])) {
            http_response_code(400);
            json_response(['error' => "Missing field: $field"]);
            return;
        }
    }

    // 3. Verificar assinatura HMAC-SHA256 enviada pelo Moodle no header X-Signature
    // Garante que o pedido veio de uma fonte legítima (o Moodle) e não foi adulterado.
    $received_sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $expected_sig = hash_hmac('sha256', $raw_body, MBWAY_SECRET_KEY);

    if (!hash_equals($expected_sig, $received_sig)) {
        http_response_code(401);
        mock_log('error', 'Invalid HMAC signature for txn_ref: ' . ($payload['txn_ref'] ?? 'unknown'));
        json_response(['error' => 'Invalid signature']);
        return;
    }

    // 4. Proteção contra replay attacks — rejeitar pedidos com mais de 5 minutos
    if (abs(time() - (int) $payload['timestamp']) > 300) {
        http_response_code(400);
        mock_log('error', 'Replay attack detected for txn_ref: ' . $payload['txn_ref']);
        json_response(['error' => 'Request expired']);
        return;
    }

    $txn_ref     = $payload['txn_ref'];
    $amount      = (float) $payload['amount'];
    $currency    = $payload['currency'];
    $webhook_url = $payload['webhook_url'];

    mock_log('info', "Payment accepted. txn_ref=$txn_ref amount=$amount $currency");

    // 5. Responder imediatamente ao Moodle com 200 OK
    // O processamento é assíncrono — o resultado será enviado via webhook.
    http_response_code(200);
    json_response([
        'message' => 'Payment request received',
        'txn_ref' => $txn_ref,
        'status'  => 'pending',
    ]);

    // 6. Fechar a ligação HTTP mas continuar a executar o script
    // Isto permite simular processamento assíncrono sem bloquear o Moodle.
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request(); // Fecha a ligação em ambientes PHP-FPM
    } elseif (ob_get_level() > 0) {
        ob_end_flush();
        flush();
    }

    // 7. Aguardar o tempo de simulação (imita o processamento real do MBWay)
    sleep(MBWAY_DELAY_SECONDS);

    // 8. Decidir o resultado do pagamento
    $result_status = decide_payment_result($txn_ref);

    mock_log('info', "Payment result: txn_ref=$txn_ref status=$result_status");

    // 9. Enviar webhook ao Moodle com o resultado
    if ($result_status !== 'pending_forever') {
        send_webhook($webhook_url, $txn_ref, $result_status, $amount, $currency);
    } else {
        mock_log('info', "txn_ref=$txn_ref left as pending (timeout simulation)");
    }
}


// ---------------------------------------------------------------------------
// Handler: GET /health
// Endpoint de verificação de saúde — usado para confirmar que o mock está ativo.
// ---------------------------------------------------------------------------
function handle_health_check(): void {
    json_response([
        'status'   => 'ok',
        'service'  => 'MBWay Mock',
        'version'  => '1.0.0',
        'sim_mode' => MBWAY_SIM_MODE,
        'time'     => date('Y-m-d H:i:s'),
    ]);
}


// ---------------------------------------------------------------------------
// Decide o resultado do pagamento consoante o modo de simulação configurado.
// ---------------------------------------------------------------------------
function decide_payment_result(string $txn_ref): string {
    switch (MBWAY_SIM_MODE) {

        case 'always_fail':
            return 'failed';

        case 'manual':
            // Lê o ficheiro de override para obter o resultado pretendido.
            // Útil para testes manuais controlados (ex: forçar falha).
            if (file_exists(MBWAY_OVERRIDE_FILE)) {
                $override = json_decode(file_get_contents(MBWAY_OVERRIDE_FILE), true);
                if (isset($override['status'])) {
                    mock_log('info', "Manual override: status={$override['status']} for txn_ref=$txn_ref");
                    return $override['status'];
                }
            }
            mock_log('warning', 'Manual mode but no override file found. Defaulting to success.');
            return 'success';

        case 'auto':
        default:
            // Resultado aleatório com as probabilidades configuradas
            $rand = random_int(1, 100);
            if ($rand <= MBWAY_PROB_SUCCESS) {
                return 'success';
            } elseif ($rand <= MBWAY_PROB_SUCCESS + MBWAY_PROB_FAILED) {
                return 'failed';
            } else {
                // Permanece pendente para sempre — simula timeout do utilizador
                return 'pending_forever';
            }
    }
}


// ---------------------------------------------------------------------------
// Envia o webhook ao Moodle com o resultado do pagamento.
// O payload é assinado com HMAC-SHA256 para que o Moodle possa verificar
// que o pedido veio do mock legítimo.
// ---------------------------------------------------------------------------
function send_webhook(
    string $webhook_url,
    string $txn_ref,
    string $status,
    float  $amount,
    string $currency
): void {

    $payload = json_encode([
        'txn_ref'   => $txn_ref,
        'status'    => $status,
        'amount'    => $amount,
        'currency'  => $currency,
        'timestamp' => time(),
    ]);

    // Assinar o payload — o Moodle verifica esta assinatura no webhook.php
    $signature = hash_hmac('sha256', $payload, MBWAY_SECRET_KEY);

    mock_log('info', "Sending webhook to $webhook_url for txn_ref=$txn_ref status=$status");

    $ch = curl_init($webhook_url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Signature: ' . $signature,
        ],
        CURLOPT_TIMEOUT        => 15,
        // Em desenvolvimento local com SSL auto-assinado, pode ser necessário desativar.
        // Em produção NUNCA desativar — manter sempre true.
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response    = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error  = curl_error($ch);
    curl_close($ch);

    if ($curl_error || $http_status !== 200) {
        mock_log('error', "Webhook delivery failed. HTTP=$http_status error=$curl_error response=$response");
    } else {
        mock_log('info', "Webhook delivered successfully. HTTP=$http_status txn_ref=$txn_ref");
    }
}


// ---------------------------------------------------------------------------
// Utilitários
// ---------------------------------------------------------------------------

function json_response(array $data): void {
    header('Content-Type: application/json');
    echo json_encode($data);
}

function mock_log(string $level, string $message): void {
    if (!MBWAY_DEBUG && $level === 'info') {
        return;
    }
    $entry = sprintf(
        '[MBWayMock][%s][%s] %s',
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message
    );
    error_log($entry);
}
