<?php
// ============================================================
// classes/privacy/provider.php — Conformidade RGPD/GDPR
// ============================================================
// O Moodle verifica a existência desta classe em TODOS os plugins.
// Sem ela, aparece um aviso de "plugin não-conforme com privacidade"
// no painel de administração.
//
// Esta classe implementa 4 interfaces que permitem ao Moodle:
//   1. Listar que dados pessoais o plugin guarda (get_metadata)
//   2. Listar utilizadores com dados no plugin
//   3. Exportar dados de um utilizador (direito à portabilidade, Art. 20 RGPD)
//   4. Apagar dados de um utilizador (direito ao apagamento, Art. 17 RGPD)
// ============================================================

namespace paygw_mbwaymock\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Declara que tabelas guardam dados pessoais e o quê.
     * Visível em: Moodle Admin → Privacidade → Resumo de dados
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'paygw_mbwaymock_transactions',
            [
                'userid'       => 'privacy:metadata:transactions:userid',
                'component'    => 'privacy:metadata:transactions:component',
                'paymentarea'  => 'privacy:metadata:transactions:paymentarea',
                'itemid'       => 'privacy:metadata:transactions:itemid',
                'txn_ref'      => 'privacy:metadata:transactions:txn_ref',
                'amount'       => 'privacy:metadata:transactions:amount',
                'currency'     => 'privacy:metadata:transactions:currency',
                'status'       => 'privacy:metadata:transactions:status',
                'timecreated'  => 'privacy:metadata:transactions:timecreated',
                'timemodified' => 'privacy:metadata:transactions:timemodified',
            ],
            'privacy:metadata:transactions'
        );
        return $collection;
    }

    /**
     * Devolve os contextos onde o utilizador tem dados.
     * Pagamentos são no contexto do sistema (não por curso).
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Lista todos os utilizadores com dados no contexto dado.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }
        $sql = 'SELECT DISTINCT userid FROM {paygw_mbwaymock_transactions}';
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Exporta os dados do utilizador (direito à portabilidade).
     * Chamado quando o utilizador pede os seus dados ao Moodle.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid  = $contextlist->get_user()->id;
        $context = \context_system::instance();

        $transactions = $DB->get_records(
            'paygw_mbwaymock_transactions',
            ['userid' => $userid],
            'timecreated DESC'
        );

        if (empty($transactions)) {
            return;
        }

        // Formatar os dados para exportação legível
        $data = array_map(function ($txn) {
            return [
                'txn_ref'      => $txn->txn_ref,
                'amount'       => $txn->amount . ' ' . $txn->currency,
                'status'       => $txn->status,
                'component'    => $txn->component,
                'timecreated'  => userdate($txn->timecreated),
                'timemodified' => userdate($txn->timemodified),
            ];
        }, $transactions);

        writer::with_context($context)->export_data(
            [get_string('pluginname', 'paygw_mbwaymock')],
            (object) ['transactions' => array_values($data)]
        );
    }

    /**
     * Apaga todos os dados num contexto (ex: apagar o sistema).
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context instanceof \context_system) {
            $DB->delete_records('paygw_mbwaymock_transactions');
        }
    }

    /**
     * Apaga os dados de um utilizador específico.
     * Chamado quando o utilizador exerce o direito ao apagamento (Art. 17 RGPD).
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $DB->delete_records('paygw_mbwaymock_transactions',
            ['userid' => $contextlist->get_user()->id]);
    }

    /**
     * Apaga dados de uma lista de utilizadores num contexto.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $userids = $userlist->get_userids();
        if (!empty($userids)) {
            [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $DB->delete_records_select(
                'paygw_mbwaymock_transactions',
                "userid $insql",
                $params
            );
        }
    }
}