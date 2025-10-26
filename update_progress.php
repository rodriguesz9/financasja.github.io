<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$lesson_id = $_POST['lesson_id'] ?? null;

if (!$lesson_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID da aula não informado']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT completed FROM course_progress WHERE user_id = ? AND lesson_id = ?");
    $stmt->execute([$user_id, $lesson_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Alternar status (se estiver completo, desmarca; se não, marca)
        $new_status = $existing['completed'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE course_progress SET completed = ?, completed_at = NOW() WHERE user_id = ? AND lesson_id = ?");
        $stmt->execute([$new_status, $user_id, $lesson_id]);
    } else {
        // Inserir novo progresso
        $stmt = $pdo->prepare("INSERT INTO course_progress (user_id, lesson_id, completed, completed_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$user_id, $lesson_id]);
        $new_status = 1;
    }

    echo json_encode(['success' => true, 'completed' => $new_status]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
