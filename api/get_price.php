<?php
header('Content-Type: application/json');
require_once '../config/gemini.php'; // Se você tem a constante API_KEY

$symbol = isset($_GET['symbol']) ? strtoupper($_GET['symbol']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'other';

if (empty($symbol)) {
    echo json_encode(['success' => false, 'message' => 'Símbolo não fornecido']);
    exit;
}

try {
    if ($type === 'stock') {
        // Ações brasileiras via Alpha Vantage
        $symbol_full = $symbol . '.SA';
        $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol={$symbol_full}&apikey=" . ALPHA_VANTAGE_KEY;

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (isset($data['Global Quote']['05. price'])) {
            $price = (float)$data['Global Quote']['05. price'];
            echo json_encode(['success' => true, 'price' => $price]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Preço não encontrado']);
        }

    } elseif ($type === 'crypto') {
        // Criptomoedas via CoinGecko
        $symbol_lower = strtolower($symbol);
        $url = "https://api.coingecko.com/api/v3/simple/price?ids={$symbol_lower}&vs_currencies=brl";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (isset($data[$symbol_lower]['brl'])) {
            $price = (float)$data[$symbol_lower]['brl'];
            echo json_encode(['success' => true, 'price' => $price]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Preço não encontrado']);
        }

    } else {
        // Outros investimentos: apenas simulação
        echo json_encode(['success' => false, 'message' => 'Tipo de investimento não suportado']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
