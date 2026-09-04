<?php
namespace paygw_mbwaymock;

defined('MOODLE_INTERNAL') || die();

class gateway extends \core_payment\gateway {

    public static function get_supported_currencies(): array {
        return ['EUR'];
    }

    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();

        $mform->addElement('text', 'mock_url', get_string('mock_url', 'paygw_mbwaymock'));
        $mform->setType('mock_url', PARAM_URL);
        $mform->addRule('mock_url', null, 'required', null, 'client');
        $mform->setDefault('mock_url', 'https://moodle.a40.pt/mbway-mock');
        $mform->addHelpButton('mock_url', 'mock_url', 'paygw_mbwaymock');

        $mform->addElement('text', 'secret_key', get_string('secret_key', 'paygw_mbwaymock'));
        $mform->setType('secret_key', PARAM_RAW);
        $mform->addRule('secret_key', null, 'required', null, 'client');
        $mform->addHelpButton('secret_key', 'secret_key', 'paygw_mbwaymock');

        $mform->addElement('select', 'sim_mode', get_string('sim_mode', 'paygw_mbwaymock'), [
            'auto'        => get_string('sim_auto',        'paygw_mbwaymock'),
            'manual'      => get_string('sim_manual',      'paygw_mbwaymock'),
            'always_fail' => get_string('sim_always_fail', 'paygw_mbwaymock'),
        ]);
        $mform->setDefault('sim_mode', 'auto');
        $mform->addHelpButton('sim_mode', 'sim_mode', 'paygw_mbwaymock');
    }

    public static function validate_gateway_form(
        \core_payment\form\account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
    }
}