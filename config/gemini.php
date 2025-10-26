<?php
// config/gemini.php

// SUBSTITUA 'SUA_API_KEY_AQUI' pela sua chave real da API do Gemini
define('GEMINI_API_KEY', 'AIzaSyDKJCbSc2TWtkJRQHCE2DzqCbXnje6W6T4');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent

');

class GeminiAPI {
    private $apiKey;
    private $apiUrl;
    
    public function __construct() {
        $this->apiKey = GEMINI_API_KEY;
        $this->apiUrl = GEMINI_API_URL;
    }
    
    /**
     * Envia uma pergunta para o Gemini e retorna a resposta
     */
    public function generateResponse($message) {
        // Contexto específico e detalhado para finanças
        $financialContext = "Você é um assistente financeiro especialista em finanças pessoais brasileiras, com conhecimento profundo sobre:
        
        - Investimentos no Brasil (Tesouro Direto, CDB, LCI/LCA, ações, FIIs, fundos)
        - Planejamento financeiro e orçamento familiar
        - Estratégias para sair de dívidas
        - Educação financeira prática
        - Mercado financeiro brasileiro atual
        
        INSTRUÇÕES IMPORTANTES:
        1. Responda SEMPRE em português brasileiro
        2. Use exemplos práticos com valores em reais (R$)
        3. Seja específico, didático e prático
        4. Use formatação com **negrito** para destacar pontos importantes
        5. Inclua dicas acionáveis e concretas
        6. Adapte a linguagem para ser acessível
        7. Quando relevante, mencione aspectos específicos do Brasil (IR, FGC, Selic, etc.)
        8. Seja direto mas completo na resposta
        9. Use exemplos numéricos quando apropriado
        10. NUNCA seja genérico demais - seja específico e útil
        
        Pergunta do usuário: ";
        
        $fullPrompt = $financialContext . $message;
        
        $data = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $fullPrompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.8,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 2048,
        'candidateCount' => 1,
    ],
    'safetySettings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ]
    ]
];

        
        try {
            $response = $this->makeRequest($data);
            return $this->parseResponse($response);
        } catch (Exception $e) {
            // Log do erro específico
            error_log("Erro específico do Gemini: " . $e->getMessage());
            throw $e; // Re-lançar para que o sistema use fallback
        }
    }
    
    /**
     * Faz a requisição para a API do Gemini
     */
    private function makeRequest($data) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '?key=' . $this->apiKey,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 45,  // Aumentado para 45 segundos
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'FinanceApp-Chatbot/1.0',
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }
        
        if ($httpCode !== 200) {
            $errorDetail = '';
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['error']['message'])) {
                    $errorDetail = ' - ' . $responseData['error']['message'];
                }
            }
            throw new Exception("Erro HTTP {$httpCode}{$errorDetail}");
        }
        
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
        }
        
        return $decodedResponse;
    }
    
    /**
     * Processa a resposta da API
     */
    private function parseResponse($response) {
        // Log da resposta completa para debug
        error_log("Resposta completa do Gemini: " . json_encode($response));
        
        if (!isset($response['candidates'])) {
            throw new Exception("Resposta inválida: candidates não encontrados");
        }
        
        if (empty($response['candidates'])) {
            throw new Exception("Nenhum candidate retornado pela API");
        }
        
        $candidate = $response['candidates'][0];
        
        // Verificar se foi bloqueado por segurança
        if (isset($candidate['finishReason']) && $candidate['finishReason'] === 'SAFETY') {
            throw new Exception("Resposta bloqueada por filtros de segurança");
        }
        
        if (!isset($candidate['content']['parts'][0]['text'])) {
            throw new Exception("Texto da resposta não encontrado na estrutura retornada");
        }
        
        $text = $candidate['content']['parts'][0]['text'];
        
        if (empty(trim($text))) {
            throw new Exception("Resposta vazia retornada pela API");
        }
        
        // Limpa e formata a resposta
        $text = trim($text);
        $text = $this->formatResponse($text);
        
        return $text;
    }
    
    /**
     * Formata a resposta para melhor exibição
     */
    private function formatResponse($text) {
        // Limpar caracteres especiais desnecessários
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        
        // Converter markdown para HTML
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/`(.*?)`/', '<code>$1</code>', $text);
        
        // Converter listas numeradas
        $text = preg_replace('/^(\d+)\.\s/m', '<br>$1. ', $text);
        
        // Converter listas com bullet points
        $text = preg_replace('/^[•\-\*]\s/m', '<br>• ', $text);
        
        // Converter quebras de linha
        $text = str_replace("\n", '<br>', $text);
        
        // Limpar múltiplas quebras de linha consecutivas
        $text = preg_replace('/(<br\s*\/?>){3,}/', '<br><br>', $text);
        
        // Adicionar espaçamento após títulos (linhas que terminam com :)
        $text = preg_replace('/([^<>]+):<br>/', '<strong>$1:</strong><br>', $text);
        
        // Remover quebras de linha no início
        $text = ltrim($text, '<br>');
        
        return $text;
    }
    
    /**
     * Resposta de fallback quando a API falha
     */
    private function getFallbackResponse($message) {
        $fallbackResponses = [
            'investimento' => 'Para começar a investir, considere primeiro criar uma reserva de emergência. Depois, estude sobre renda fixa (CDB, Tesouro Direto) antes de partir para renda variável.',
            'orçamento' => 'Para criar um orçamento eficaz: 1) Liste todas suas receitas, 2) Anote todos os gastos fixos, 3) Controle gastos variáveis, 4) Use a regra 50-30-20 (necessidades, desejos, poupança).',
            'poupança' => 'A poupança hoje rende cerca de 70% da Selic. Para valores maiores, considere CDB, LCI/LCA ou Tesouro Direto que podem oferecer melhor rentabilidade.',
            'default' => 'Desculpe, estou com dificuldades técnicas no momento. Posso te ajudar com dicas básicas sobre orçamento, investimentos, poupança ou planejamento financeiro. Tente reformular sua pergunta!'
        ];
        
        $message = strtolower($message);
        
        foreach ($fallbackResponses as $keyword => $response) {
            if ($keyword !== 'default' && strpos($message, $keyword) !== false) {
                return $response;
            }
        }
        
        return $fallbackResponses['default'];
    }
    
    /**
     * Verifica se a API está configurada corretamente
     */
    public function isConfigured() {
        return $this->apiKey !== 'SUA_API_KEY_AQUI' && !empty($this->apiKey);
    }
    
    /**
     * Teste específico para verificar se o Gemini está funcionando
     */
    public function testConnection() {
        if (!$this->isConfigured()) {
            throw new Exception("API Key não configurada");
        }
        
        $testMessage = "Responda apenas 'OK - Gemini funcionando' para confirmar que está operacional.";
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $testMessage
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 50,
            ]
        ];
        
        $response = $this->makeRequest($data);
        $result = $this->parseResponse($response);
        
        return $result;
    }
}
?>