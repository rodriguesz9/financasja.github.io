<?php
session_start();
require_once 'config/database.php';

requireLogin();

// Verificar se o arquivo existe
if (!file_exists('config/gemini.php')) {
    die('Arquivo config/gemini.php não encontrado!');
}

require_once 'config/gemini.php';

// Teste detalhado da API
$testResults = [];

// 1. Verificar se as constantes estão definidas
$testResults['constants'] = [
    'GEMINI_API_KEY_defined' => defined('GEMINI_API_KEY'),
    'GEMINI_API_URL_defined' => defined('GEMINI_API_URL'),
    'api_key_value' => defined('GEMINI_API_KEY') ? (GEMINI_API_KEY === 'SUA_API_KEY_AQUI' ? 'NOT_CONFIGURED' : substr(GEMINI_API_KEY, 0, 20) . '...') : 'UNDEFINED',
    'api_url_value' => defined('GEMINI_API_URL') ? GEMINI_API_URL : 'UNDEFINED'
];

// 2. Teste de conectividade básica
$testResults['connectivity'] = [
    'curl_available' => function_exists('curl_init'),
    'openssl_available' => extension_loaded('openssl'),
    'internet_connection' => false
];

// Teste de conexão com internet
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $testResults['connectivity']['internet_connection'] = ($httpCode == 200);
    $testResults['connectivity']['google_response_code'] = $httpCode;
}

// 3. Teste da API Gemini com requisição manual
$testResults['gemini_api'] = [
    'manual_test_attempted' => false,
    'manual_test_success' => false,
    'manual_test_error' => '',
    'manual_test_response' => '',
    'http_code' => 0
];

if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== 'SUA_API_KEY_AQUI' && function_exists('curl_init')) {
    $testResults['gemini_api']['manual_test_attempted'] = true;
    
    // Requisição manual para testar a API
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => 'Responda apenas "OK" para testar a conexão.'
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'maxOutputTokens' => 10,
        ]
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => GEMINI_API_URL . '?key=' . GEMINI_API_KEY,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_VERBOSE => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $testResults['gemini_api']['http_code'] = $httpCode;
    
    if ($curlError) {
        $testResults['gemini_api']['manual_test_error'] = 'cURL Error: ' . $curlError;
    } elseif ($httpCode !== 200) {
        $testResults['gemini_api']['manual_test_error'] = 'HTTP Error: ' . $httpCode;
        $testResults['gemini_api']['manual_test_response'] = $response;
    } else {
        $responseData = json_decode($response, true);
        if ($responseData) {
            $testResults['gemini_api']['manual_test_success'] = true;
            $testResults['gemini_api']['manual_test_response'] = $response;
        } else {
            $testResults['gemini_api']['manual_test_error'] = 'Invalid JSON response';
            $testResults['gemini_api']['manual_test_response'] = $response;
        }
    }
}

// 4. Teste usando a classe GeminiAPI
$testResults['gemini_class'] = [
    'class_exists' => class_exists('GeminiAPI'),
    'class_test_attempted' => false,
    'class_test_success' => false,
    'class_test_error' => '',
    'class_test_response' => ''
];

if (class_exists('GeminiAPI') && defined('GEMINI_API_KEY') && GEMINI_API_KEY !== 'SUA_API_KEY_AQUI') {
    $testResults['gemini_class']['class_test_attempted'] = true;
    
    try {
        $gemini = new GeminiAPI();
        $response = $gemini->testConnection();
        $testResults['gemini_class']['class_test_success'] = true;
        $testResults['gemini_class']['class_test_response'] = $response;
    } catch (Exception $e) {
        $testResults['gemini_class']['class_test_error'] = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Detalhado Gemini - FinanceApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="bi bi-bug-fill me-2"></i>Debug Detalhado - API Gemini</h4>
                    </div>
                    <div class="card-body">
                        <!-- Constantes e Configuração -->
                        <h5>1. Configuração</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <?php foreach ($testResults['constants'] as $key => $value): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($key) ?>:</strong></td>
                                    <td>
                                        <?php if (is_bool($value)): ?>
                                            <span class="badge <?= $value ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $value ? 'Sim' : 'Não' ?>
                                            </span>
                                        <?php else: ?>
                                            <code><?= htmlspecialchars($value) ?></code>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>

                        <hr>

                        <!-- Conectividade -->
                        <h5>2. Conectividade</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <?php foreach ($testResults['connectivity'] as $key => $value): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($key) ?>:</strong></td>
                                    <td>
                                        <?php if (is_bool($value)): ?>
                                            <span class="badge <?= $value ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $value ? 'OK' : 'Falhou' ?>
                                            </span>
                                        <?php else: ?>
                                            <code><?= htmlspecialchars($value) ?></code>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>

                        <hr>

                        <!-- Teste Manual da API -->
                        <h5>3. Teste Manual da API Gemini</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <?php foreach ($testResults['gemini_api'] as $key => $value): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($key) ?>:</strong></td>
                                    <td>
                                        <?php if (is_bool($value)): ?>
                                            <span class="badge <?= $value ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $value ? 'Sim' : 'Não' ?>
                                            </span>
                                        <?php elseif ($key === 'manual_test_response' && !empty($value)): ?>
                                            <div class="bg-light p-2 rounded">
                                                <small><?= htmlspecialchars(substr($value, 0, 500)) ?><?= strlen($value) > 500 ? '...' : '' ?></small>
                                            </div>
                                        <?php else: ?>
                                            <?= !empty($value) ? '<code>' . htmlspecialchars($value) . '</code>' : '<span class="text-muted">-</span>' ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>

                        <hr>

                        <!-- Teste da Classe -->
                        <h5>4. Teste da Classe GeminiAPI</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <?php foreach ($testResults['gemini_class'] as $key => $value): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($key) ?>:</strong></td>
                                    <td>
                                        <?php if (is_bool($value)): ?>
                                            <span class="badge <?= $value ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $value ? 'Sim' : 'Não' ?>
                                            </span>
                                        <?php else: ?>
                                            <?= !empty($value) ? '<code>' . htmlspecialchars($value) . '</code>' : '<span class="text-muted">-</span>' ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>

                        <hr>

                        <!-- Diagnóstico e Soluções -->
                        <h5>5. Diagnóstico e Soluções</h5>
                        
                        <?php if (!$testResults['constants']['GEMINI_API_KEY_defined'] || $testResults['constants']['api_key_value'] === 'NOT_CONFIGURED'): ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-x-circle me-2"></i>API Key não configurada</h6>
                                <p>Configure sua API Key no arquivo <code>config/gemini.php</code></p>
                                <ol>
                                    <li>Acesse: <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
                                    <li>Crie uma nova API Key</li>
                                    <li>Substitua 'SUA_API_KEY_AQUI' pela chave real</li>
                                </ol>
                            </div>
                        <?php elseif (!$testResults['connectivity']['internet_connection']): ?>
                            <div class="alert alert-warning">
                                <h6><i class="bi bi-wifi-off me-2"></i>Sem conexão com internet</h6>
                                <p>Verifique sua conexão com a internet</p>
                            </div>
                        <?php elseif (!$testResults['gemini_api']['manual_test_success']): ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Erro na API Gemini</h6>
                                <p><strong>Erro:</strong> <?= htmlspecialchars($testResults['gemini_api']['manual_test_error']) ?></p>
                                
                                <?php if ($testResults['gemini_api']['http_code'] == 400): ?>
                                    <p><strong>Possível causa:</strong> API Key inválida ou problema na requisição</p>
                                    <ul>
                                        <li>Verifique se a API Key está correta</li>
                                        <li>Confirme se a API Generative Language está ativada no Google Cloud</li>
                                    </ul>
                                <?php elseif ($testResults['gemini_api']['http_code'] == 403): ?>
                                    <p><strong>Possível causa:</strong> Sem permissão ou cota excedida</p>
                                    <ul>
                                        <li>Verifique se você tem cota disponível</li>
                                        <li>Confirme se a API Key tem as permissões corretas</li>
                                    </ul>
                                <?php elseif ($testResults['gemini_api']['http_code'] == 429): ?>
                                    <p><strong>Possível causa:</strong> Rate limit excedido</p>
                                    <ul>
                                        <li>Aguarde alguns minutos e tente novamente</li>
                                        <li>Considere implementar retry com backoff</li>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h6><i class="bi bi-check-circle me-2"></i>Tudo funcionando!</h6>
                                <p>A API Gemini deveria estar funcionando normalmente</p>
                            </div>
                        <?php endif; ?>

                        <!-- Informações adicionais -->
                        <div class="mt-4">
                            <h6>Informações do Sistema:</h6>
                            <ul>
                                <li><strong>PHP Version:</strong> <?= PHP_VERSION ?></li>
                                <li><strong>cURL Version:</strong> <?= function_exists('curl_version') ? curl_version()['version'] : 'N/A' ?></li>
                                <li><strong>OpenSSL:</strong> <?= OPENSSL_VERSION_TEXT ?? 'N/A' ?></li>
                                <li><strong>Timezone:</strong> <?= date_default_timezone_get() ?></li>
                                <li><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></li>
                            </ul>
                        </div>

                        <div class="text-center mt-4">
                            <a href="conversabot.php.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar ao Chatbot
                            </a>
                            <button class="btn btn-secondary ms-2" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Recarregar Teste
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>