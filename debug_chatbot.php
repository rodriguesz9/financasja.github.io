<?php
session_start();
require_once 'config/database.php';

// Só permitir para usuários logados
requireLogin();

// Verificar se o arquivo gemini.php existe
$geminiConfigExists = file_exists('config/gemini.php');

if ($geminiConfigExists) {
    require_once 'config/gemini.php';
    $gemini = new GeminiAPI();
    $isConfigured = $gemini->isConfigured();
} else {
    $isConfigured = false;
}

// Teste básico da API (se configurada)
$testResult = null;
$testError = null;

if ($_POST && isset($_POST['test_api']) && $geminiConfigExists && $isConfigured) {
    try {
        $gemini = new GeminiAPI();
        $testResult = $gemini->generateResponse("Como posso investir R$ 1000 reais de forma segura no Brasil?");
    } catch (Exception $e) {
        $testError = $e->getMessage();
    }
}

// Teste de conexão simples
$connectionTest = null;
$connectionError = null;

if ($_POST && isset($_POST['test_connection']) && $geminiConfigExists && $isConfigured) {
    try {
        $gemini = new GeminiAPI();
        $connectionTest = $gemini->testConnection();
    } catch (Exception $e) {
        $connectionError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Chatbot - FinanceApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-bug me-2"></i>Debug do Chatbot</h4>
                    </div>
                    <div class="card-body">
                        <h5>Status da Configuração</h5>
                        
                        <!-- Verificar arquivo gemini.php -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <?php if ($geminiConfigExists): ?>
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Arquivo config/gemini.php encontrado</span>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                    <span>Arquivo config/gemini.php NÃO encontrado</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Verificar configuração da API -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <?php if ($geminiConfigExists && $isConfigured): ?>
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>API Key configurada</span>
                                <?php elseif ($geminiConfigExists): ?>
                                    <i class="bi bi-exclamation-circle-fill text-warning me-2"></i>
                                    <span>API Key NÃO configurada (ainda está como 'SUA_API_KEY_AQUI')</span>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                    <span>Arquivo de configuração não encontrado</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Verificar cURL -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <?php if (function_exists('curl_init')): ?>
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>cURL habilitado</span>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                    <span>cURL NÃO habilitado no PHP</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Verificar constantes -->
                        <?php if ($geminiConfigExists): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <?php if (defined('GEMINI_API_KEY') && defined('GEMINI_API_URL')): ?>
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span>Constantes definidas</span>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                    <span>Constantes NÃO definidas</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <hr>

                        <!-- Teste da API -->
                        <?php if ($geminiConfigExists && $isConfigured): ?>
                        <h5>Teste da API</h5>
                        <div class="d-flex gap-2 mb-3">
                            <form method="POST" class="d-inline">
                                <button type="submit" name="test_connection" class="btn btn-outline-primary">
                                    <i class="bi bi-wifi me-2"></i>Teste de Conexão
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="test_api" class="btn btn-primary">
                                    <i class="bi bi-play-fill me-2"></i>Teste Completo
                                </button>
                            </form>
                        </div>

                        <?php if ($connectionTest): ?>
                            <div class="alert alert-success">
                                <h6><i class="bi bi-check-circle me-2"></i>Conexão OK!</h6>
                                <div class="border p-2 bg-light">
                                    <?= htmlspecialchars($connectionTest) ?>
                                </div>
                            </div>
                        <?php elseif ($connectionError): ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-x-circle me-2"></i>Erro na conexão:</h6>
                                <code><?= htmlspecialchars($connectionError) ?></code>
                            </div>
                        <?php endif; ?>

                        <?php if ($testResult): ?>
                            <div class="alert alert-success mt-3">
                                <h6><i class="bi bi-check-circle me-2"></i>Teste completo bem-sucedido!</h6>
                                <div class="border p-2 bg-light">
                                    <?= $testResult ?>
                                </div>
                            </div>
                        <?php elseif ($testError): ?>
                            <div class="alert alert-danger mt-3">
                                <h6><i class="bi bi-x-circle me-2"></i>Erro no teste:</h6>
                                <code><?= htmlspecialchars($testError) ?></code>
                            </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <hr>

                        <!-- Informações detalhadas -->
                        <h5>Informações do Sistema</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Versão PHP:</strong> <?= PHP_VERSION ?><br>
                                <strong>cURL:</strong> <?= function_exists('curl_init') ? 'Sim' : 'Não' ?><br>
                                <strong>OpenSSL:</strong> <?= extension_loaded('openssl') ? 'Sim' : 'Não' ?><br>
                            </div>
                            <div class="col-md-6">
                                <?php if ($geminiConfigExists && defined('GEMINI_API_KEY')): ?>
                                <strong>API Key:</strong> <?= substr(GEMINI_API_KEY, 0, 10) ?>...<br>
                                <strong>API URL:</strong> <?= defined('GEMINI_API_URL') ? 'Definida' : 'Não definida' ?><br>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr>

                        <!-- Instruções -->
                        <h5>Próximos Passos</h5>
                        <?php if (!$geminiConfigExists): ?>
                            <div class="alert alert-warning">
                                <h6>Arquivo não encontrado!</h6>
                                <p>Crie o arquivo <code>config/gemini.php</code> com o código fornecido anteriormente.</p>
                            </div>
                        <?php elseif (!$isConfigured): ?>
                            <div class="alert alert-warning">
                                <h6>API Key não configurada!</h6>
                                <p>Edite o arquivo <code>config/gemini.php</code> e substitua <code>'SUA_API_KEY_AQUI'</code> pela sua chave real da API.</p>
                                <ol>
                                    <li>Acesse: <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
                                    <li>Faça login e crie uma API Key</li>
                                    <li>Cole a chave no arquivo de configuração</li>
                                </ol>
                            </div>
                        <?php elseif ($testError): ?>
                            <div class="alert alert-danger">
                                <h6>Erro na API!</h6>
                                <p>Verifique:</p>
                                <ul>
                                    <li>Se sua API Key está correta</li>
                                    <li>Se você tem cota disponível na API</li>
                                    <li>Se sua conexão com internet está funcionando</li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h6>Tudo funcionando!</h6>
                                <p>O chatbot deveria estar funcionando com a API Gemini.</p>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-4">
                            <a href="chatbot.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar ao Chatbot
                            </a>
                            <a href="home.php" class="btn btn-primary ms-2">
                                <i class="bi bi-house me-2"></i>Ir para Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>