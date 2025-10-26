<?php
header('Content-Type: application/json');

// Lê mensagem do usuário
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['message'])) {
    echo json_encode(['success' => false, 'error' => 'Mensagem ausente']);
    exit;
}

$userMessage = $input['message'];

// Chave hipotética
$apiKey = "AIzaSyDhXD12RxHK-2PVHt1y3uliu3D56g5oCRw";

// Endpoint Gemini
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";

// Payload
$payload = [
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
