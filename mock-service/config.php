<?php

/**
 * Configuração do Mock Service MBWay.
 *
 * ATENÇÃO: Em produção, nunca versionar este ficheiro com valores reais.
 * Usar variáveis de ambiente ou um ficheiro .env separado do repositório.
 * O .gitignore já exclui config.local.php para valores locais.
 */

// Chave secreta partilhada com o Moodle.
// TEM DE SER IDÊNTICA ao campo "Secret Key" configurado em:
// Moodle → Admin → Pagamentos → Contas de pagamento → MBWay Mock → Secret Key
define('MBWAY_SECRET_KEY', 'chave_secreta_de_desenvolvimento_123');

// Modo de simulação. Valores possíveis:
//   'auto'        — 80% sucesso, 15% falha, 5% pendente (depois resolve)
//   'always_fail' — todos os pagamentos falham (útil para testar tratamento de erros)
//   'manual'      — lê o ficheiro /tmp/mbway_override.json para decidir o resultado
define('MBWAY_SIM_MODE', 'auto');

// Atraso simulado em segundos antes de enviar o webhook ao Moodle.
// Simula o tempo de processamento real do MBWay (3-8 segundos típico).
define('MBWAY_DELAY_SECONDS', 5);

// Probabilidades no modo 'auto' (devem somar 100)
define('MBWAY_PROB_SUCCESS', 80);
define('MBWAY_PROB_FAILED',  15);
define('MBWAY_PROB_PENDING',  5); // ficará pendente para sempre (simula timeout)

// Logging: true para escrever logs detalhados em error_log()
define('MBWAY_DEBUG', true);

// Ficheiro de override para modo 'manual'
// Conteúdo esperado: {"status": "success"} ou {"status": "failed"}
define('MBWAY_OVERRIDE_FILE', '/tmp/mbway_override.json');

// Carregar configuração local se existir (substitui os valores acima)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
