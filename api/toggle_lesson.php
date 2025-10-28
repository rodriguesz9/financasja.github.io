<?php
// api/toggle_lesson.php - Criar este arquivo na pasta api/
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Receber dados
$data = json_decode(file_get_contents('php://input'), true);
$lesson_id = $data['lesson_id'] ?? '';
$module_id = $data['module_id'] ?? '';

if (empty($lesson_id)) {
    echo json_encode(['success' => false, 'error' => 'lesson_id obrigatório']);
    exit;
}

try {
    // Buscar status atual
    $stmt = $pdo->prepare("SELECT CAST(completed AS UNSIGNED) as completed FROM course_progress WHERE user_id = ? AND lesson_id = ?");
    $stmt->execute([$user_id, $lesson_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    $new_status = 0;
    
    if ($current !== false) {
        // Inverte o status
        $new_status = ($current['completed'] == 1) ? 0 : 1;
        $new_date = ($new_status == 1) ? date('Y-m-d H:i:s') : null;
        
        $stmt = $pdo->prepare("UPDATE course_progress SET completed = ?, completed_at = ? WHERE user_id = ? AND lesson_id = ?");
        $stmt->execute([$new_status, $new_date, $user_id, $lesson_id]);
    } else {
        // Cria como completo
        $new_status = 1;
        $stmt = $pdo->prepare("INSERT INTO course_progress (user_id, lesson_id, completed, completed_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$user_id, $lesson_id]);
    }

    // Buscar progresso atualizado do módulo
    $module_progress = getModuleProgress($pdo, $user_id, $module_id);
    
    // Verificar se módulo foi completado
    $module_completed = checkModuleCompletion($pdo, $user_id, $module_id);
    
    // Buscar progresso geral
    $overall = getOverallProgress($pdo, $user_id);
    
    echo json_encode([
        'success' => true,
        'completed' => $new_status == 1,
        'module_progress' => $module_progress,
        'module_completed' => $module_completed,
        'overall_progress' => $overall,
        'all_courses_completed' => $overall['all_completed']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getModuleProgress($pdo, $user_id, $module_id) {
    // Incluir definição de cursos
    include '../config/courses.php';
    
    if (!isset($courses[$module_id])) {
        return ['completed' => 0, 'total' => 0, 'percentage' => 0];
    }
    
    $module = $courses[$module_id];
    $lesson_ids = array_column($module['lessons'], 'id');
    $total = count($lesson_ids);
    
    // Contar completas
    $placeholders = str_repeat('?,', count($lesson_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_progress 
                          WHERE user_id = ? AND lesson_id IN ($placeholders) AND CAST(completed AS UNSIGNED) = 1");
    $stmt->execute(array_merge([$user_id], $lesson_ids));
    $completed = $stmt->fetchColumn();
    
    return [
        'completed' => $completed,
        'total' => $total,
        'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0
    ];
}

function checkModuleCompletion($pdo, $user_id, $module_id) {
    include '../config/courses.php';
    
    if (!isset($courses[$module_id])) return false;
    
    $module = $courses[$module_id];
    $lesson_ids = array_column($module['lessons'], 'id');
    
    $placeholders = str_repeat('?,', count($lesson_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_progress 
                          WHERE user_id = ? AND lesson_id IN ($placeholders) AND CAST(completed AS UNSIGNED) = 1");
    $stmt->execute(array_merge([$user_id], $lesson_ids));
    $completed_count = $stmt->fetchColumn();
    
    if ($completed_count == count($lesson_ids)) {
        // Verificar se já tem certificado
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_certificates WHERE user_id = ? AND module_id = ?");
        $stmt->execute([$user_id, $module_id]);
        $has_certificate = $stmt->fetchColumn() > 0;
        
        if (!$has_certificate) {
            $stmt = $pdo->prepare("INSERT INTO course_certificates (user_id, module_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $module_id]);
            return [
                'just_completed' => true,
                'module_name' => $courses[$module_id]['title']
            ];
        }
    }
    
    return false;
}

function getOverallProgress($pdo, $user_id) {
    include '../config/courses.php';
    
    $total_lessons = 0;
    $total_completed = 0;
    $completed_modules = 0;
    $total_modules = count($courses);
    
    foreach ($courses as $module_id => $module) {
        $lesson_ids = array_column($module['lessons'], 'id');
        $total_lessons += count($lesson_ids);
        
        $placeholders = str_repeat('?,', count($lesson_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_progress 
                              WHERE user_id = ? AND lesson_id IN ($placeholders) AND CAST(completed AS UNSIGNED) = 1");
        $stmt->execute(array_merge([$user_id], $lesson_ids));
        $module_completed_count = $stmt->fetchColumn();
        
        $total_completed += $module_completed_count;
        
        if ($module_completed_count == count($lesson_ids)) {
            $completed_modules++;
        }
    }
    
    return [
        'total_lessons' => $total_lessons,
        'total_completed' => $total_completed,
        'completed_modules' => $completed_modules,
        'total_modules' => $total_modules,
        'percentage' => $total_lessons > 0 ? round(($total_completed / $total_lessons) * 100) : 0,
        'all_completed' => $completed_modules == $total_modules
    ];
}
?>