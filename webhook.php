<?php
define('NO_DEBUG_DISPLAY', true);
require_once(__DIR__ . '/../../../config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo 'Method Not Allowed';
    exit;
}

$raw_body  = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

error_log('[paygw_mbwaymock] webhook chamado. body_len=' . strlen($raw_body));

if (empty($raw_body)) {
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

$data = json_decode($raw_body, true);
if (!$data || !isset($data['txn_ref'], $data['status'], $data['amount'], $data['currency'], $data['timestamp'])) {
    http_response_code(400);
    error_log('[paygw_mbwaymock] webhook: JSON inválido ou campos em falta.');
    echo 'Bad Request';
    exit;
}

// -------------------------------------------------------
// PROTEÇÃO ANTI-REPLAY POR TIMESTAMP (±5 minutos)
// -------------------------------------------------------
$timestamp = (int) $data['timestamp'];
if (abs(time() - $timestamp) > 300) {
    http_response_code(400);
    error_log('[paygw_mbwaymock] webhook: timestamp fora do intervalo. ts=' . $timestamp . ' now=' . time());
    echo 'Timestamp expired';
    exit;
}

$txn_ref  = $data['txn_ref'];
$status   = $data['status'];
$amount   = (float) $data['amount'];
$currency = $data['currency'];

error_log('[paygw_mbwaymock] webhook: txn_ref=' . $txn_ref . ' status=' . $status);

// Obter transação
$txn = $DB->get_record('paygw_mbwaymock_transactions', ['txn_ref' => $txn_ref]);
if (!$txn) {
    http_response_code(404);
    error_log('[paygw_mbwaymock] webhook: transação não encontrada.');
    echo 'Not found';
    exit;
}

// Verificar assinatura HMAC
try {
    $config     = (object) \core_payment\helper::get_gateway_configuration(
        $txn->component, $txn->paymentarea, $txn->itemid, 'mbwaymock'
    );
    $secret_key = $config->secret_key ?? '';
} catch (\Throwable $e) {
    http_response_code(500);
    error_log('[paygw_mbwaymock] webhook: erro config: ' . $e->getMessage());
    echo 'Config error';
    exit;
}

$expected = hash_hmac('sha256', $raw_body, $secret_key);
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    error_log('[paygw_mbwaymock] webhook: assinatura inválida.');
    echo 'Unauthorized';
    exit;
}

// Idempotência
if ($txn->status !== 'pending') {
    http_response_code(200);
    error_log('[paygw_mbwaymock] webhook: já processado (status=' . $txn->status . ').');
    echo 'Already processed';
    exit;
}

// Verificar valor
if (abs($txn->amount - $amount) > 0.01) {
    http_response_code(400);
    error_log('[paygw_mbwaymock] webhook: valor divergente. BD=' . $txn->amount . ' webhook=' . $amount);
    echo 'Amount mismatch';
    exit;
}

// -------------------------------------------------------
// SE SUCESSO: usar deliver_order() da API oficial do Moodle
// -------------------------------------------------------
if ($status === 'success') {
    try {

        // 1. Obter o id da conta de pagamento associada a este item
        $payable   = \core_payment\helper::get_payable($txn->component, $txn->paymentarea, (int) $txn->itemid);
        $accountid = $payable->get_account_id();

        // 2. Registar o pagamento na tabela payments do Moodle
        $paymentid = \core_payment\helper::save_payment(
            $accountid,
            $txn->component,
            $txn->paymentarea,
            (int) $txn->itemid,
            (int) $txn->userid,
            (float) $txn->amount,
            $txn->currency,
            'mbwaymock'
        );
        error_log('[paygw_mbwaymock] webhook: save_payment ok. paymentid=' . $paymentid);

        // 2. Entregar a ordem via API oficial — invoca deliver_order() do componente
        //    (para enrol_fee isto inscreve o utilizador corretamente via Moodle)
        $delivered = \core_payment\helper::deliver_order(
            $txn->component,
            $txn->paymentarea,
            (int) $txn->itemid,
            $paymentid,
            (int) $txn->userid
        );

        if ($delivered) {
            error_log('[paygw_mbwaymock] webhook: deliver_order() OK. userid=' . $txn->userid);
        } else {
            // deliver_order() retornou false — fallback para insert direto
            error_log('[paygw_mbwaymock] webhook: deliver_order() retornou false. A usar fallback direto.');
            self_enrol_direct($txn);
        }

    } catch (\Throwable $e) {
        // deliver_order() lançou exceção — fallback para insert direto
        error_log('[paygw_mbwaymock] webhook: deliver_order() excepção: ' . $e->getMessage() . '. A usar fallback.');
        try {
            self_enrol_direct($txn);
        } catch (\Throwable $e2) {
            http_response_code(500);
            error_log('[paygw_mbwaymock] webhook: fallback também falhou: ' . $e2->getMessage());
            echo 'Error';
            exit;
        }
    }
}

// Actualizar estado na tabela de transações do plugin
$DB->update_record('paygw_mbwaymock_transactions', (object) [
    'id'           => $txn->id,
    'status'       => $status,
    'timemodified' => time(),
]);

error_log('[paygw_mbwaymock] webhook: estado actualizado para "' . $status . '".');
http_response_code(200);
echo 'OK';
exit;

// -------------------------------------------------------
// Fallback: inscrição direta na BD (usado só se deliver_order falhar)
// -------------------------------------------------------
function self_enrol_direct(object $txn): void {
    global $DB;
    $now = time();

    $existing = $DB->get_record('user_enrolments', [
        'enrolid' => (int) $txn->itemid,
        'userid'  => (int) $txn->userid,
    ]);

    if ($existing) {
        error_log('[paygw_mbwaymock] fallback: utilizador já inscrito.');
        return;
    }

    $DB->insert_record('user_enrolments', (object) [
        'status'       => 0,
        'enrolid'      => (int) $txn->itemid,
        'userid'       => (int) $txn->userid,
        'timestart'    => 0,
        'timeend'      => 0,
        'modifierid'   => (int) $txn->userid,
        'timecreated'  => $now,
        'timemodified' => $now,
    ]);

    $enrol_instance = $DB->get_record('enrol', ['id' => (int) $txn->itemid]);
    if (!$enrol_instance) { return; }

    $context      = $DB->get_record('context', ['contextlevel' => 50, 'instanceid' => (int) $enrol_instance->courseid]);
    $student_role = $DB->get_record('role', ['shortname' => 'student']);

    if ($context && $student_role) {
        $existing_role = $DB->get_record('role_assignments', [
            'roleid'    => $student_role->id,
            'contextid' => $context->id,
            'userid'    => (int) $txn->userid,
        ]);
        if (!$existing_role) {
            $DB->insert_record('role_assignments', (object) [
                'roleid'       => $student_role->id,
                'contextid'    => $context->id,
                'userid'       => (int) $txn->userid,
                'timemodified' => $now,
                'modifierid'   => (int) $txn->userid,
                'component'    => '',
                'itemid'       => 0,
                'sortorder'    => 0,
            ]);
        }
    }
    error_log('[paygw_mbwaymock] fallback: inscrição direta concluída. userid=' . $txn->userid);
}