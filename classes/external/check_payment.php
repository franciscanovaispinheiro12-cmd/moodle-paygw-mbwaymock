<?php
// ============================================================
// classes/external/check_payment.php — Web Service: verificar estado
// ============================================================
// FLUXO ONDE ESTE FICHEIRO É CHAMADO:
//
//   Após o create_payment, o JavaScript fica em loop:
//     → chama check_payment(txn_ref) a cada 2 segundos
//     → enquanto status === 'pending', continua a fazer polling
//     → quando status === 'success' → resolve a Promise → Moodle matricula
//     → quando status === 'failed'  → mostra erro ao aluno
//
//   O estado muda de 'pending' para 'success'/'failed' quando
//   o webhook.php recebe e processa a notificação do mock.
// ============================================================

namespace paygw_mbwaymock\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

class check_payment extends external_api {

    /**
     * Parâmetros de entrada: apenas a referência da transação.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'txn_ref' => new external_value(
                PARAM_ALPHANUMEXT,
                'Referência da transação devolvida pelo create_payment'
            ),
        ]);
    }

    /**
     * Consulta o estado da transação na base de dados.
     *
     * SEGURANÇA: filtra por userid para garantir que um aluno
     * não consegue consultar o pagamento de outro aluno.
     */
    public static function execute(string $txn_ref): array {
        global $DB, $USER;

        // ── Passo 1: Validar parâmetros ───────────────────────────────
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['txn_ref' => $txn_ref]
        );

        // ── Passo 2: Consultar a transação na BD ──────────────────────
        // O filtro por userid é crítico: só o dono da transação pode consultá-la
        $txn = $DB->get_record('paygw_mbwaymock_transactions', [
            'txn_ref' => $params['txn_ref'],
            'userid'  => $USER->id,           // proteção: só as minhas transações
        ]);

        // ── Passo 3: Verificar se a transação existe ──────────────────
        if (!$txn) {
            throw new \moodle_exception('transaction_not_found', 'paygw_mbwaymock');
        }

        // ── Passo 4: Devolver o estado ao JavaScript ──────────────────
        // Apenas o estado é devolvido — sem expor dados sensíveis
        return [
            'status'  => $txn->status,   // 'pending' | 'success' | 'failed'
            'txn_ref' => $txn->txn_ref,
        ];
    }

    /**
     * Estrutura da resposta devolvida ao JavaScript.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status'  => new external_value(PARAM_ALPHA, 'Estado: pending | success | failed'),
            'txn_ref' => new external_value(PARAM_ALPHANUMEXT, 'Referência da transação'),
        ]);
    }
}