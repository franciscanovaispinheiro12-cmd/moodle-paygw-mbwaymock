<?php
defined('MOODLE_INTERNAL') || die();

$functions = [

    'paygw_mbwaymock_create_payment' => [
        'classname'     => 'paygw_mbwaymock\external\create_payment',
        'methodname'    => 'execute',
        'description'   => 'Inicia um pedido de pagamento MBWay Mock.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'paygw_mbwaymock_check_payment' => [
        'classname'     => 'paygw_mbwaymock\external\check_payment',
        'methodname'    => 'execute',
        'description'   => 'Verifica o estado de um pagamento MBWay Mock.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'paygw_mbwaymock_send_comprovativo' => [
        'classname'     => 'paygw_mbwaymock\external\send_comprovativo',
        'methodname'    => 'execute',
        'description'   => 'Envia comprovativo de pagamento por email.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

];