<?php
header('Content-Type: application/json');

// Lê mensagem do usuário
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['message'])) {
    echo json_encode(['success' => false, 'error' => 'Mensagem ausente']);
    exit;
}

$userMessage = $input['message'];

// Filtro de conteúdo: palavras-chave proibidas (exemplo simples para conteúdo inadequado)
$prohibitedWords = ['hack', 'illegal', 'crime', 'violencia', 'drogas', 'porn', 'odio', 'racismo'];
$messageLower = strtolower($userMessage);
$filtered = false;
foreach ($prohibitedWords as $word) {
    if (strpos($messageLower, $word) !== false) {
        $filtered = true;
        break;
    }
}

if ($filtered) {
    echo json_encode([
        'success' => true,
        'source' => 'local',
        'response' => 'Desculpe, não posso responder sobre tópicos inadequados ou não relacionados a finanças. Por favor, pergunte sobre planejamento financeiro, investimentos ou assuntos semelhantes.'
    ]);
    exit;
}

// Chave hipotética
$apiKey = "AIzaSyDhXD12RxHK-2PVHt1y3uliu3D56g5oCRw";

// Endpoint Gemini
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";

// Payload com instrução de sistema para focar em finanças e filtrar conteúdo inadequado
$payload = [
    "system_instruction" => [
        "parts" => [
            ["text" => "Você é um assistente financeiro especializado em finanças pessoais, investimentos, orçamento e educação financeira. Responda apenas a perguntas relacionadas a esses tópicos. Se a pergunta for sobre algo inadequado, ilegal ou fora do escopo, responda educadamente que não pode ajudar e sugira um tópico financeiro.
            INSTRUÇÕES IMPORTANTES:
        1. Responda SEMPRE em português brasileiro
        2. Use exemplos práticos com valores em reais (R$)
        3. Seja específico, didático e prático
        4. Inclua dicas acionáveis e concretas
        5. Adapte a linguagem para ser acessível
        6. Quando relevante, mencione aspectos específicos do Brasil (IR, FGC, Selic, etc.)
        7. Seja direto mas completo na resposta
        8. Use exemplos numéricos quando apropriado
        9. NUNCA seja genérico demais - seja específico e útil"]
        ]
    ],
    "contents" => [
        [
            "parts" => [
                [ "text" => $userMessage ]
            ]
        ]
    ]
];

// Faz chamada para Gemini
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "x-goog-api-key: $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode([
        'success' => false,
        'source' => 'fallback_gemini_error',
        'error_msg' => $curlErr
    ]);
    exit;
}

$data = json_decode($response, true);
$text = $data["candidates"][0]["content"]["parts"][0]["text"] ?? null;

if ($text) {
    echo json_encode([
        'success' => true,
        'source' => 'gemini',
        'response' => nl2br($text)
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'source' => 'fallback_gemini_error',
        'error_msg' => $response
    ]);
}