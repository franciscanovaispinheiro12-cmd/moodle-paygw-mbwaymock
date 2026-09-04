<?php
namespace paygw_mbwaymock\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

class send_comprovativo extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'txn_ref'      => new external_value(PARAM_ALPHANUMEXT, 'Referência da transação'),
            'billing_type' => new external_value(PARAM_ALPHA, 'Tipo: personal ou company'),
        ]);
    }

    public static function execute(string $txn_ref, string $billing_type): array {
        global $DB, $USER, $CFG;
        error_log('[paygw_mbwaymock] send_comprovativo: INICIADO. txn_ref=' . $txn_ref . ' billing_type=' . $billing_type);
        $params = self::validate_parameters(self::execute_parameters(), [
            'txn_ref'      => $txn_ref,
            'billing_type' => $billing_type,
        ]);

        $txn = $DB->get_record('paygw_mbwaymock_transactions', [
            'txn_ref' => $params['txn_ref'],
            'userid'  => $USER->id,
        ]);
        if (!$txn) {
            throw new \moodle_exception('transaction_not_found', 'paygw_mbwaymock');
        }

        $user         = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);
        $profile      = self::get_profile_fields($USER->id);
        $billing_type = $params['billing_type'];

        // Nome do curso
        $course_name = '';
        try {
            $enrol = $DB->get_record('enrol', ['id' => (int)$txn->itemid]);
            if ($enrol) {
                $course = $DB->get_record('course', ['id' => $enrol->courseid]);
                $course_name = $course ? $course->fullname : '';
            }
        } catch (\Throwable $e) {
            error_log('[paygw_mbwaymock] curso: ' . $e->getMessage());
        }

        // Dados de faturação
        if ($billing_type === 'company') {
            $tipo      = 'Empresa';
            $nif_label = 'NIF da Empresa';
            $nif_val   = $profile['nifempresa'] ?: '—';
        } else {
            $tipo      = 'Pessoal';
            $nif_label = 'NIF';
            $nif_val   = $profile['nif'] ?: '—';
        }

        $morada  = $profile['morada'] ?: '—';
        $cp      = $profile['codigopostal'] ?: '';
        $loc     = $profile['localidade'] ?: '';
        $address = trim($morada . ($cp || $loc ? ', ' . trim($cp . ' ' . $loc) : ''));

        $amount_str = number_format((float)$txn->amount, 2, ',', '.') . ' ' . $txn->currency;
        $date_str   = date('d/m/Y H:i', $txn->timecreated ?? time());

        $from = \core_user::get_noreply_user();

        // --- Email ao formando ---
        $subject_user = 'Comprovativo de Pagamento — ' . ($course_name ?: 'CITEVE Academia');
        $html_user = '
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
  <div style="background:#1e3a5f;padding:16px 24px;border-radius:8px 8px 0 0;">
    <span style="color:#fff;font-size:20px;font-weight:bold;">CITEVE Academia</span>
  </div>
  <div style="border:1px solid #e5e7eb;border-top:none;padding:24px;border-radius:0 0 8px 8px;">
    <p>Olá, <b>' . htmlspecialchars(fullname($user)) . '</b>,</p>
    <p>O seu pagamento foi processado com sucesso.</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0;">
      <tr style="background:#f9fafb;"><td style="padding:8px;font-weight:bold;width:40%;">Referência</td><td style="padding:8px;">' . htmlspecialchars($txn->txn_ref) . '</td></tr>
      <tr><td style="padding:8px;font-weight:bold;">Curso</td><td style="padding:8px;">' . htmlspecialchars($course_name ?: '—') . '</td></tr>
      <tr style="background:#f9fafb;"><td style="padding:8px;font-weight:bold;">Valor</td><td style="padding:8px;">' . $amount_str . '</td></tr>
      <tr><td style="padding:8px;font-weight:bold;">Data</td><td style="padding:8px;">' . $date_str . '</td></tr>
    </table>
    <div style="background:#fef9c3;border:1px solid #fde047;padding:12px 16px;border-radius:6px;">
      <b>⚠ Este documento não substitui fatura.</b><br>
      A fatura será emitida pelos serviços administrativos da Academia CITEVE.
    </div>
    <p style="margin-top:20px;color:#6b7280;font-size:12px;">Academia CITEVE  · academia@citeve.pt</p>
  </div>
</div>';

        $ok_user = email_to_user($user, $from, $subject_user, strip_tags($html_user), $html_user);
        error_log('[paygw_mbwaymock] email_user: ' . ($ok_user ? 'OK' : 'FALHOU') . ' para ' . $user->email);

        // --- Email à academia ---
        $subject_ac = 'Pedido de Fatura (' . $tipo . ') — ' . fullname($user) . ' — ' . ($course_name ?: 'curso');
        $html_ac = '
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">
  <div style="background:#1e3a5f;padding:16px 24px;border-radius:8px 8px 0 0;">
    <span style="color:#fff;font-size:18px;font-weight:bold;">Pedido de Fatura — ' . $tipo . '</span>
  </div>
  <div style="border:1px solid #e5e7eb;border-top:none;padding:24px;border-radius:0 0 8px 8px;">
    <table style="width:100%;border-collapse:collapse;">
      <tr style="background:#f9fafb;"><td style="padding:10px;font-weight:bold;width:40%;border-bottom:1px solid #e5e7eb;">Nome Completo</td><td style="padding:10px;border-bottom:1px solid #e5e7eb;">' . htmlspecialchars(fullname($user)) . '</td></tr>
      <tr><td style="padding:10px;font-weight:bold;border-bottom:1px solid #e5e7eb;">' . $nif_label . '</td><td style="padding:10px;border-bottom:1px solid #e5e7eb;">' . htmlspecialchars($nif_val) . '</td></tr>
      <tr style="background:#f9fafb;"><td style="padding:10px;font-weight:bold;border-bottom:1px solid #e5e7eb;">Morada</td><td style="padding:10px;border-bottom:1px solid #e5e7eb;">' . htmlspecialchars($address) . '</td></tr>
      <tr><td style="padding:10px;font-weight:bold;border-bottom:1px solid #e5e7eb;">Curso</td><td style="padding:10px;border-bottom:1px solid #e5e7eb;">' . htmlspecialchars($course_name ?: '—') . '</td></tr>
      <tr style="background:#f9fafb;"><td style="padding:10px;font-weight:bold;">Valor / Data</td><td style="padding:10px;">' . $amount_str . ' · ' . $date_str . '</td></tr>
    </table>
  </div>
</div>';

        // Destino academia
        $academia = $DB->get_record('user', ['email' => 'academia@citeve.pt', 'deleted' => 0]);
        if (!$academia) {
            $academia = new \stdClass();
            $academia->id         = -99;
            $academia->email      = 'academia@citeve.pt';
            $academia->firstname  = 'Academia';
            $academia->lastname   = 'CITEVE';
            $academia->username   = 'academia_citeve_noreply';
            $academia->mailformat  = 1;
            $academia->maildisplay = 0;
            $academia->deleted    = 0;
            $academia->suspended  = 0;
            $academia->auth       = 'manual';
            $academia->confirmed  = 1;
            $academia->lang       = 'pt';
            $academia->mnethostid = 1;
        }

        $ok_ac = email_to_user($academia, $from, $subject_ac, strip_tags($html_ac), $html_ac);
        error_log('[paygw_mbwaymock] email_academia: ' . ($ok_ac ? 'OK' : 'FALHOU') . ' para academia@citeve.pt');

        return ['success' => true, 'message' => 'Processado.'];
    }

    private static function get_profile_fields(int $userid): array {
        global $DB;
        $shortnames = ['nif', 'nifempresa', 'nomeempresa', 'morada', 'codigopostal', 'localidade'];
        $result = [];
        foreach ($shortnames as $sn) {
            $field = $DB->get_record('user_info_field', ['shortname' => $sn]);
            if ($field) {
                $data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
                $result[$sn] = $data ? trim($data->data) : '';
            } else {
                $result[$sn] = '';
            }
        }
        return $result;
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Sucesso'),
            'message' => new external_value(PARAM_TEXT, 'Mensagem'),
        ]);
    }
}