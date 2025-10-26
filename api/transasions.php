<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

// Verificar se está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Buscar transações
        $month = $_GET['month'] ?? date('Y-m');
        
        $stmt = $pdo->prepare("
            SELECT 
                id, tipo, categoria, descricao, valor, data_transacao,
                DATE_FORMAT(data_transacao, '%Y-%m-%d') as data_formatada
            FROM transactions 
            WHERE user_id = ? AND DATE_FORMAT(data_transacao, '%Y-%m') = ?
            ORDER BY data_transacao DESC, created_at DESC
        ");
        $stmt->execute([$user_id, $month]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular totais
        $receitas = 0;
        $despesas = 0;
        
        foreach ($transactions as $transaction) {
            if ($transaction['tipo'] == 'receita') {
                $receitas += $transaction['valor'];
            } else {
                $despesas += $transaction['valor'];
            }
        }
        
        echo json_encode([
            'transactions' => $transactions,
            'summary' => [
                'receitas' => $receitas,
                'despesas' => $despesas,
                'saldo' => $receitas - $despesas
            ]
        ]);
        break;
        
    case 'POST':
        // Adicionar nova transação
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['tipo'], $data['categoria'], $data['valor'], $data['data_transacao'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados incompletos']);
            exit();
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, tipo, categoria, descricao, valor, data_transacao) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $user_id,
            $data['tipo'],
            $data['categoria'],
            $data['descricao'] ?? '',
            $data['valor'],
            $data['data_transacao']
        ]);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'message' => 'Transação adicionada com sucesso'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao adicionar transação']);
        }
        break;
        
    case 'PUT':
        // Atualizar transação
        $data = json_decode(file_get_contents('php://input'), true);
        $transaction_id = $_GET['id'] ?? null;
        
        if (!$transaction_id || !$data) {
            http_response_code(400);
            echo json_encode(['error' => 'ID da transação ou dados não fornecidos']);
            exit();
        }
        
        // Verificar se a transação pertence ao usuário
        $stmt = $pdo->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$transaction_id, $user_id]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Transação não encontrada']);
            exit();
        }
        
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET tipo = ?, categoria = ?, descricao = ?, valor = ?, data_transacao = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $success = $stmt->execute([
            $data['tipo'],
            $data['categoria'],
            $data['descricao'] ?? '',
            $data['valor'],
            $data['data_transacao'],
            $transaction_id,
            $user_id
        ]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Transação atualizada com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar transação']);
        }
        break;
        
    case 'DELETE':
        // Deletar transação
        $transaction_id = $_GET['id'] ?? null;
        
        if (!$transaction_id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID da transação não fornecido']);
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $success = $stmt->execute([$transaction_id, $user_id]);
        
        if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Transação deletada com sucesso']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Transação não encontrada']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        break;
}
?>