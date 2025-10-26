<?php
session_start();
require_once 'config/database.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Criar tabelas necessárias
try {
    // Tabela de progresso (corrigir nome da tabela original)
    $pdo->exec("CREATE TABLE IF NOT EXISTS course_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        lesson_id VARCHAR(50) NOT NULL,
        completed BOOLEAN DEFAULT FALSE,
        completed_at TIMESTAMP NULL,
        UNIQUE KEY unique_progress (user_id, lesson_id)
    )");
    
    // Tabela de certificados
    $pdo->exec("CREATE TABLE IF NOT EXISTS course_certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        module_id VARCHAR(50) NOT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_certificate (user_id, module_id)
    )");
    
    // Tabela de quiz
    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        nivel INT NOT NULL,
        acertos INT NOT NULL,
        total INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Ignorar erro
}

// Marcar aula como completa/incompleta
if (isset($_POST['toggle_lesson'])) {
    $lesson_id = $_POST['lesson_id'];
    $module_id = $_POST['module_id'] ?? '';

    // Buscar status atual
    $stmt = $pdo->prepare("SELECT completed FROM course_progress WHERE user_id = ? AND lesson_id = ?");
    $stmt->execute([$user_id, $lesson_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        // Se existe, inverte o status (1 vira 0, 0 vira 1)
        $new_status = ($current['completed'] == 1) ? 0 : 1;
        $new_date = ($new_status == 1) ? date('Y-m-d H:i:s') : null;
        
        $stmt = $pdo->prepare("UPDATE course_progress SET completed = ?, completed_at = ? WHERE user_id = ? AND lesson_id = ?");
        $stmt->execute([$new_status, $new_date, $user_id, $lesson_id]);
    } else {
        // Se não existe, cria como completo
        $stmt = $pdo->prepare("INSERT INTO course_progress (user_id, lesson_id, completed, completed_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$user_id, $lesson_id]);
    }

    // Verificar se o módulo foi completado
    if ($module_id) {
        checkModuleCompletion($pdo, $user_id, $module_id);
    }

    header('Location: education.php');
    exit();
}

// Função para verificar conclusão do módulo
function checkModuleCompletion($pdo, $user_id, $module_id) {
    global $courses;
    
    if (!isset($courses[$module_id])) return;
    
    $module = $courses[$module_id];
    $lesson_ids = array_column($module['lessons'], 'id');
    
    // Contar aulas completas do módulo
    $placeholders = str_repeat('?,', count($lesson_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_progress 
                          WHERE user_id = ? AND lesson_id IN ($placeholders) AND completed = TRUE");
    $stmt->execute(array_merge([$user_id], $lesson_ids));
    $completed_count = $stmt->fetchColumn();
    
    // Se completou todas as aulas, gerar certificado
    if ($completed_count == count($lesson_ids)) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO course_certificates (user_id, module_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $module_id]);
    }
}

// Buscar aulas completas
$stmt = $pdo->prepare("SELECT lesson_id, completed FROM course_progress WHERE user_id = ?");
$stmt->execute([$user_id]);
$progress_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$completed_lessons = [];
foreach ($progress_data as $row) {
    if ($row['completed'] == 1 || $row['completed'] == '1' || $row['completed'] === true) {
        $completed_lessons[] = $row['lesson_id'];
    }
}

// Buscar certificados
$stmt = $pdo->prepare("SELECT module_id, completed_at FROM course_certificates WHERE user_id = ?");
$stmt->execute([$user_id]);
$certificates = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// CURSOS
$courses = [
    'module_1' => [
        'title' => 'Fundamentos das Finanças Pessoais',
        'icon' => 'piggy-bank-fill',
        'color' => 'success',
        'quiz_nivel' => 1,
        'lessons' => [
            ['id' => 'm1_l1', 'title' => 'Curso de finanças pessoais: controle financeiro', 'youtube' => 'https://www.youtube.com/watch?v=HjVaW41ucMc', 'desc' => 'Aprenda como ter controle financeiro e investir o seu dinheiro.'],
            ['id' => 'm1_l2', 'title' => 'Os tipos de receitas de um profissional', 'youtube' => 'https://www.youtube.com/watch?v=5qjfdz02lXY', 'desc' => 'Entenda os diferentes tipos de receitas e como gerenciá-las.'],
            ['id' => 'm1_l3', 'title' => 'Os principais tipos de despesas', 'youtube' => 'https://www.youtube.com/watch?v=KV37n4l8nGY', 'desc' => 'Conheça as categorias de despesas e como controlá-las.'],
            ['id' => 'm1_l4', 'title' => 'Controle financeiro para freelancer', 'youtube' => 'https://www.youtube.com/watch?v=q0XI-lfoWTs', 'desc' => 'Técnicas específicas para quem trabalha como freelancer.'],
            ['id' => 'm1_l5', 'title' => 'Reserva de emergência: o que é e como fazer?', 'youtube' => 'https://www.youtube.com/watch?v=FIbNFAw1C_w', 'desc' => 'A importância e estratégias para construir sua reserva.'],
            ['id' => 'm1_l6', 'title' => 'Instabilidade financeira: como lidar?', 'youtube' => 'https://www.youtube.com/watch?v=FLTiYZdMuTI', 'desc' => 'Aprenda a navegar em momentos de incerteza financeira.'],
        ]
    ],
    'module_2' => [
        'title' => 'Planejamento e Orçamento',
        'icon' => 'clipboard-check',
        'color' => 'info',
        'quiz_nivel' => 2,
        'lessons' => [
            ['id' => 'm2_l1', 'title' => 'Riscos da falta de planejamento', 'youtube' => 'https://www.youtube.com/watch?v=I4Z2-xatfFE', 'desc' => 'Entenda os perigos de não planejar suas finanças.'],
            ['id' => 'm2_l2', 'title' => 'Planilha para orçamento doméstico', 'youtube' => 'https://www.youtube.com/watch?v=d40sN3vGa2o', 'desc' => 'Como criar e usar planilhas para controle financeiro.'],
            ['id' => 'm2_l3', 'title' => 'Planilha para controle financeiro', 'youtube' => 'https://www.youtube.com/watch?v=jFmQ_bRugKM', 'desc' => 'Ferramentas práticas para monitorar suas finanças.'],
            ['id' => 'm2_l4', 'title' => 'Os perigos das dívidas', 'youtube' => 'https://www.youtube.com/watch?v=G5rnCijgyiw', 'desc' => 'Como as dívidas afetam sua saúde financeira.'],
            ['id' => 'm2_l5', 'title' => 'Dívida no cartão de crédito', 'youtube' => 'https://www.youtube.com/watch?v=domq4G0nHSY', 'desc' => 'Pagamento mínimo ou parcelamento? Entenda a melhor opção.'],
            ['id' => 'm2_l6', 'title' => 'Como pagar dívidas e limpar o nome', 'youtube' => 'https://www.youtube.com/watch?v=kz1gt10qyw0', 'desc' => 'Estratégias para sair do vermelho.'],
        ]
    ],
    'module_3' => [
        'title' => 'Introdução aos Investimentos',
        'icon' => 'graph-up-arrow',
        'color' => 'warning',
        'quiz_nivel' => 3,
        'lessons' => [
            ['id' => 'm3_l1', 'title' => 'Dicas para não se endividar', 'youtube' => 'https://www.youtube.com/watch?v=hmnUF2qq60E', 'desc' => 'Prevenção é o melhor remédio financeiro.'],
            ['id' => 'm3_l2', 'title' => 'Por que investir meu dinheiro?', 'youtube' => 'https://www.youtube.com/watch?v=rt-RRKkUpxU', 'desc' => 'A importância de fazer seu dinheiro trabalhar para você.'],
            ['id' => 'm3_l3', 'title' => 'Banco ou corretora: onde investir?', 'youtube' => 'https://www.youtube.com/watch?v=bOQuEsx_YRk', 'desc' => 'Compare as opções e escolha a melhor para você.'],
            ['id' => 'm3_l4', 'title' => 'Taxa Selic, inflação e juros reais', 'youtube' => 'https://www.youtube.com/watch?v=dhd0ErwqUk4', 'desc' => 'Entenda os indicadores econômicos essenciais.'],
            ['id' => 'm3_l5', 'title' => 'Perfil de investidor', 'youtube' => 'https://www.youtube.com/watch?v=OZd3I_QEYmI', 'desc' => 'Descubra se você é conservador, moderado ou agressivo.'],
            ['id' => 'm3_l6', 'title' => 'O que é renda fixa?', 'youtube' => 'https://www.youtube.com/watch?v=E6BqS9HQzNQ', 'desc' => 'Aprenda a investir com segurança.'],
        ]
    ],
    'module_4' => [
        'title' => 'Renda Fixa e Títulos',
        'icon' => 'bank',
        'color' => 'primary',
        'quiz_nivel' => 3,
        'lessons' => [
            ['id' => 'm4_l1', 'title' => 'A poupança ainda vale a pena?', 'youtube' => 'https://www.youtube.com/watch?v=mnv9HZdden4', 'desc' => 'Análise crítica do investimento mais tradicional.'],
            ['id' => 'm4_l2', 'title' => 'Títulos públicos: Tesouro Direto', 'youtube' => 'https://www.youtube.com/watch?v=Wd3yWtH82ic', 'desc' => 'Como investir em títulos do governo.'],
            ['id' => 'm4_l3', 'title' => 'Títulos bancários: CDB, LCA e LCI', 'youtube' => 'https://www.youtube.com/watch?v=1_EY1wW1cbc', 'desc' => 'Entenda as opções de renda fixa dos bancos.'],
            ['id' => 'm4_l4', 'title' => 'Títulos privados: debêntures, CRA e CRI', 'youtube' => 'https://www.youtube.com/watch?v=VQNubjKl9yc', 'desc' => 'Investimentos em títulos de empresas privadas.'],
        ]
    ],
    'module_5' => [
        'title' => 'Fundos de Investimento',
        'icon' => 'briefcase-fill',
        'color' => 'danger',
        'quiz_nivel' => 4,
        'lessons' => [
            ['id' => 'm5_l1', 'title' => 'O que são fundos de investimentos?', 'youtube' => 'https://www.youtube.com/watch?v=R2G33ELy8HM', 'desc' => 'Introdução aos fundos e como funcionam.'],
            ['id' => 'm5_l2', 'title' => 'Tipos e estratégias dos fundos', 'youtube' => 'https://www.youtube.com/watch?v=Dm_FYK6eaOg', 'desc' => 'Conheça as diferentes categorias de fundos.'],
            ['id' => 'm5_l3', 'title' => 'Como analisar indicadores de fundos', 'youtube' => 'https://www.youtube.com/watch?v=TBbnttrlzZE', 'desc' => 'Aprenda a comparar e escolher os melhores fundos.'],
            ['id' => 'm5_l4', 'title' => 'Fundos imobiliários (FIIs)', 'youtube' => 'https://www.youtube.com/watch?v=ugL_p4xym2c', 'desc' => 'Como investir no mercado imobiliário via fundos.'],
            ['id' => 'm5_l5', 'title' => 'Fundos de tijolo', 'youtube' => 'https://www.youtube.com/watch?v=6e_tx4h12mg', 'desc' => 'FIIs que pagam dividendos mensais.'],
            ['id' => 'm5_l6', 'title' => 'Fundos de papel', 'youtube' => 'https://www.youtube.com/watch?v=WXjomuCDs9o', 'desc' => 'Investindo em FIIs de CRI e LCI.'],
        ]
    ],
    'module_6' => [
        'title' => 'Bolsa de Valores e Ações',
        'icon' => 'bar-chart-fill',
        'color' => 'success',
        'quiz_nivel' => 5,
        'lessons' => [
            ['id' => 'm6_l1', 'title' => 'Tipos de fundos imobiliários', 'youtube' => 'https://www.youtube.com/watch?v=i9XT6yg6eoU', 'desc' => 'Desenvolvimento e FOFs explicados.'],
            ['id' => 'm6_l2', 'title' => 'Como analisar fundos imobiliários', 'youtube' => 'https://www.youtube.com/watch?v=_03lcnQiHlU', 'desc' => 'Principais indicadores de FIIs.'],
            ['id' => 'm6_l3', 'title' => 'Como funciona a Bolsa de Valores', 'youtube' => 'https://www.youtube.com/watch?v=ov0n9hs7SeA', 'desc' => 'Entenda o mercado de ações.'],
            ['id' => 'm6_l4', 'title' => 'Principais setores da Bolsa', 'youtube' => 'https://www.youtube.com/watch?v=B7CN2N1bd9k', 'desc' => 'Conheça os setores econômicos negociados.'],
            ['id' => 'm6_l5', 'title' => 'Análise fundamentalista vs técnica', 'youtube' => 'https://www.youtube.com/watch?v=3LRQzOMa46s', 'desc' => 'Duas formas de analisar ações.'],
            ['id' => 'm6_l6', 'title' => 'Value Investing', 'youtube' => 'https://www.youtube.com/watch?v=Oh6aHryWAmE', 'desc' => 'Estratégia de investimento em valor.'],
        ]
    ],
    'module_7' => [
        'title' => 'Investimentos Avançados',
        'icon' => 'lightning-charge-fill',
        'color' => 'dark',
        'quiz_nivel' => 6,
        'lessons' => [
            ['id' => 'm7_l1', 'title' => 'Como analisar ações na bolsa', 'youtube' => 'https://www.youtube.com/watch?v=1o-LxtTgihE', 'desc' => 'Técnicas de análise de ações.'],
            ['id' => 'm7_l2', 'title' => 'COE: Certificado de Operações Estruturadas', 'youtube' => 'https://www.youtube.com/watch?v=lDshAnz7TxY', 'desc' => 'Entenda se COE vale a pena.'],
            ['id' => 'm7_l3', 'title' => 'Fundos Alternativos', 'youtube' => 'https://www.youtube.com/watch?v=xwRIbognuIw', 'desc' => 'Private Equity, Venture Capital e FIDC.'],
            ['id' => 'm7_l4', 'title' => 'Mercado de derivativos', 'youtube' => 'https://www.youtube.com/watch?v=BibJpkLZA94', 'desc' => 'Ações, índices, commodities e juros.'],
            ['id' => 'm7_l5', 'title' => 'Mercado Forex', 'youtube' => 'https://www.youtube.com/watch?v=z-ebbTacucA', 'desc' => 'Como negociar moedas internacionais.'],
            ['id' => 'm7_l6', 'title' => 'Criptomoedas e Bitcoin', 'youtube' => 'https://www.youtube.com/watch?v=j1nakeUCwIA', 'desc' => 'Vale a pena investir em criptomoedas?'],
        ]
    ],
    'module_8' => [
        'title' => 'Gestão de Carteira',
        'icon' => 'wallet2',
        'color' => 'info',
        'quiz_nivel' => 6,
        'lessons' => [
            ['id' => 'm8_l1', 'title' => 'Investimentos Esportivos', 'youtube' => 'https://www.youtube.com/watch?v=L46S2_WwCxw', 'desc' => 'É possível ganhar dinheiro? Análise completa.'],
            ['id' => 'm8_l2', 'title' => 'Como montar uma carteira', 'youtube' => 'https://www.youtube.com/watch?v=veoELni5hsw', 'desc' => 'Passo a passo para criar sua carteira de investimentos.'],
            ['id' => 'm8_l3', 'title' => 'A importância dos aportes mensais', 'youtube' => 'https://www.youtube.com/watch?v=esN7m_i17IM', 'desc' => 'Como os aportes regulares potencializam seus investimentos.'],
            ['id' => 'm8_l4', 'title' => 'Como revisar uma carteira', 'youtube' => 'https://www.youtube.com/watch?v=soQanKWv4Rk', 'desc' => 'Aprenda a fazer o rebalanceamento da sua carteira.'],
        ]
    ]
];

// Calcular progresso
$module_progress = [];
$total_lessons = 0;
$total_completed = 0;
$total_modules = count($courses);
$completed_modules = 0;

foreach ($courses as $module_id => $module) {
    $module_total = count($module['lessons']);
    $module_completed = 0;
    
    foreach ($module['lessons'] as $lesson) {
        if (in_array($lesson['id'], $completed_lessons)) {
            $module_completed++;
            $total_completed++;
        }
        $total_lessons++;
    }
    
    $percentage = $module_total > 0 ? round(($module_completed / $module_total) * 100) : 0;
    $is_module_completed = $percentage == 100;
    
    if ($is_module_completed) {
        $completed_modules++;
    }
    
    $module_progress[$module_id] = [
        'completed' => $module_completed,
        'total' => $module_total,
        'percentage' => $percentage,
        'is_completed' => $is_module_completed
    ];
}

$overall_progress = $total_lessons > 0 ? round(($total_completed / $total_lessons) * 100) : 0;
$all_courses_completed = $completed_modules == $total_modules;

// Função para extrair ID do vídeo YouTube
function getYouTubeEmbedUrl($url) {
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\?]+)/', $url, $matches);
    return isset($matches[1]) ? "https://www.youtube.com/embed/{$matches[1]}" : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academia Financeira - FinanceApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .lesson-item {
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-radius: 15px;
            background: white;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .lesson-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .lesson-item.completed {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-color: #28a745;
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 15px;
            background: #000;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .checkbox-custom {
            width: 24px;
            height: 24px;
            cursor: pointer;
        }
        
        .quiz-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
            z-index: 10;
        }
        
        .certificate-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(253, 160, 133, 0.3);
            z-index: 10;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            position: absolute;
            animation: confetti-fall 3s linear forwards;
        }
        
        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .celebration-modal {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .trophy-icon {
            font-size: 5rem;
            animation: trophy-bounce 1s ease-in-out infinite;
        }
        
        @keyframes trophy-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .stars {
            font-size: 2rem;
            animation: stars-twinkle 1.5s ease-in-out infinite;
        }
        
        @keyframes stars-twinkle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        
        .progress-ring {
            width: 120px;
            height: 120px;
        }
        
        .module-completed-badge {
            position: relative;
            overflow: hidden;
        }
        
        .module-completed-badge::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 4rem;
            color: rgba(40, 167, 69, 0.1);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-modern sticky-top">
        <div class="container">
            <a class="navbar-brand" href="home.php">
                <i class="bi bi-gem me-2"></i>FinancasJa
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">
                            <i class="bi bi-house-fill me-1"></i>Início
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="investments.php">
                            <i class="bi bi-graph-up me-1"></i>Investimentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="chatbot.php">
                            <i class="bi bi-robot me-1"></i>Assistente IA
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="education.php">
                            <i class="bi bi-mortarboard me-1"></i>Academia
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Mais
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="about.php"><i class="bi bi-info-circle me-2"></i>Sobre</a></li>
                            <li><a class="dropdown-item" href="plans.php"><i class="bi bi-star me-2"></i>Planos</a></li>
                            <li><a class="dropdown-item" href="support.php"><i class="bi bi-headset me-2"></i>Suporte</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($user_name) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-modern">
        <div class="container">
            <div class="hero-content">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-5 fw-bold mb-3 fade-in-up">Academia Financeira</h1>
                        <p class="lead mb-4 fade-in-up" style="animation-delay: 0.2s;">
                            Aprenda educação financeira com especialistas através de vídeos práticos
                        </p>
                    </div>
                    <div class="col-lg-4">
                        <div class="card-modern text-center p-4">
                            <div class="mb-2">
                                <i class="bi bi-trophy-fill text-warning" style="font-size: 3rem;"></i>
                            </div>
                            <h2 class="display-4 fw-bold text-purple mb-0"><?= $overall_progress ?>%</h2>
                            <p style="color: #6c757d;" class="mb-1">Progresso Geral</p>
                            <small style="color: #6c757d;"><?= $total_completed ?> de <?= $total_lessons ?> aulas concluídas</small>
                            <div class="mt-3">
                                <div class="d-flex justify-content-center gap-2">
                                    <span class="badge bg-success"><?= $completed_modules ?> módulos completos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container mt-5 mb-5">
        <!-- Botão de Exercícios -->
        <div class="mb-4">
            <a href="exercicios.php" class="btn btn-purple btn-modern btn-lg w-100">
                <i class="bi bi-clipboard-check me-2"></i>Fazer Exercícios e Testar Conhecimentos
            </a>
        </div>

        <!-- Módulos -->
        <?php foreach ($courses as $module_id => $module): ?>
            <?php 
            $is_module_complete = $module_progress[$module_id]['is_completed'];
            $has_certificate = isset($certificates[$module_id]);
            ?>
            <div class="card-modern mb-4 position-relative <?= $is_module_complete ? 'module-completed-badge' : '' ?>">
                <?php if ($has_certificate): ?>
                    <div class="certificate-badge">
                        <i class="bi bi-award-fill me-1"></i>Certificado Obtido
                    </div>
                <?php endif; ?>
                
                <?php if (isset($module['quiz_nivel'])): ?>
                    <a href="quiz.php?nivel=<?= $module['quiz_nivel'] ?>" class="quiz-badge text-decoration-none">
                        <i class="bi bi-lightning-fill me-1"></i>Quiz Nível <?= $module['quiz_nivel'] ?>
                    </a>
                <?php endif; ?>
                
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0">
                            <i class="bi bi-<?= $module['icon'] ?> text-<?= $module['color'] ?> me-2"></i>
                            <?= $module['title'] ?>
                            <?php if ($is_module_complete): ?>
                                <i class="bi bi-check-circle-fill text-success ms-2"></i>
                            <?php endif; ?>
                        </h4>
                        <span class="badge badge-<?= $module['color'] ?> fs-6">
                            <?= $module_progress[$module_id]['percentage'] ?>% Completo
                        </span>
                    </div>
                    
                    <div class="progress mb-4" style="height: 12px; border-radius: 10px;">
                        <div class="progress-bar bg-<?= $module['color'] ?>" 
                             style="width: <?= $module_progress[$module_id]['percentage'] ?>%"></div>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($module['lessons'] as $lesson): ?>
                            <?php 
                            $is_completed = in_array($lesson['id'], $completed_lessons);
                            $embed_url = getYouTubeEmbedUrl($lesson['youtube']);
                            ?>
                            <div class="col-md-6">
                                <div class="lesson-item <?= $is_completed ? 'completed' : '' ?>" 
                                     data-bs-toggle="modal" 
                                     data-bs-target="#lessonModal"
                                     data-lesson-id="<?= $lesson['id'] ?>"
                                     data-module-id="<?= $module_id ?>"
                                     data-lesson-title="<?= htmlspecialchars($lesson['title']) ?>"
                                     data-lesson-desc="<?= htmlspecialchars($lesson['desc']) ?>"
                                     data-lesson-url="<?= $embed_url ?>"
                                     data-lesson-completed="<?= $is_completed ? '1' : '0' ?>">
                                    <div class="d-flex align-items-start">
                                        <form method="POST" class="me-3" onclick="event.stopPropagation()">
                                            <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                                            <input type="hidden" name="module_id" value="<?= $module_id ?>">
                                            <input type="checkbox" 
                                                   name="toggle_lesson" 
                                                   class="checkbox-custom form-check-input"
                                                   <?= $is_completed ? 'checked' : '' ?>
                                                   onchange="this.form.submit()">
                                        </form>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($lesson['title']) ?></h6>
                                            <p class="mb-2" style="color: #6c757d; font-size: 0.9rem;">
                                                <?= htmlspecialchars($lesson['desc']) ?>
                                            </p>
                                            <small class="text-purple">
                                                <i class="bi bi-play-circle me-1"></i>Clique para assistir
                                            </small>
                                        </div>
                                        <i class="bi bi-play-circle-fill fs-2 ms-2 <?= $is_completed ? 'text-success' : 'text-purple' ?>"></i>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Info Card -->
        <div class="card-modern" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body p-4 text-center">
                <i class="bi bi-info-circle-fill fs-1 mb-3"></i>
                <h5 class="fw-bold mb-3">Como Usar a Academia</h5>
                <p class="mb-0">
                    Assista às aulas em sequência, marque como concluídas conforme avança e teste seus conhecimentos 
                    com os exercícios ao final de cada módulo. Bons estudos!
                </p>
            </div>
        </div>
    </div>

    <!-- Modal para assistir vídeo -->
    <div class="modal fade" id="lessonModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="lessonTitle">Aula</h5>
                        <p class="mb-0" style="color: #6c757d;" id="lessonDesc"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="video-container" id="videoContainer">
                        <!-- Vídeo será carregado aqui -->
                    </div>
                    <div class="mt-4">
                        <form method="POST" id="completeForm">
                            <input type="hidden" name="lesson_id" id="modalLessonId">
                            <input type="hidden" name="module_id" id="modalModuleId">
                            <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: rgba(138, 43, 226, 0.1);">
                                <div class="form-check mb-0">
                                    <input type="checkbox" name="toggle_lesson" class="form-check-input" id="modalCompleteCheck" onchange="this.form.submit()">
                                    <label class="form-check-label fw-bold" for="modalCompleteCheck">
                                        <i class="bi bi-check-circle me-2"></i><span id="checkboxLabel">Marcar como concluída</span>
                                    </label>
                                </div>
                                <span id="lessonStatus" class="badge"></span>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Você pode marcar e desmarcar a aula a qualquer momento
                            </small>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Parabéns por Módulo Completo -->
    <div class="modal fade" id="moduleCompleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content celebration-modal border-0">
                <div class="modal-body text-center p-5">
                    <div class="stars mb-3">✨ ⭐ ✨</div>
                    <i class="bi bi-trophy-fill text-warning trophy-icon mb-4"></i>
                    <h2 class="fw-bold mb-3">Parabéns!</h2>
                    <p class="fs-5 mb-4">
                        Você concluiu o módulo <strong id="completedModuleName"></strong>!
                    </p>
                    <div class="mb-4">
                        <i class="bi bi-award-fill fs-1 text-warning"></i>
                        <p class="mt-2 mb-0">Certificado de conclusão obtido!</p>
                    </div>
                    <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-right me-2"></i>Continuar Aprendendo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Parabéns por Todos os Cursos -->
    <div class="modal fade" id="allCoursesCompleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content celebration-modal border-0">
                <div class="modal-body text-center p-5">
                    <div class="stars mb-3" style="font-size: 3rem;">✨ ⭐ 🎉 ⭐ ✨</div>
                    <i class="bi bi-trophy-fill text-warning mb-4" style="font-size: 8rem;"></i>
                    <h1 class="fw-bold mb-3">MISSÃO CUMPRIDA!</h1>
                    <h4 class="mb-4">Você completou TODOS os módulos da Academia Financeira!</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div class="bg-white bg-opacity-25 rounded p-3">
                                <i class="bi bi-book-fill fs-1 mb-2"></i>
                                <p class="mb-0 fw-bold"><?= $total_modules ?> Módulos</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-25 rounded p-3">
                                <i class="bi bi-play-circle-fill fs-1 mb-2"></i>
                                <p class="mb-0 fw-bold"><?= $total_lessons ?> Aulas</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-25 rounded p-3">
                                <i class="bi bi-award-fill fs-1 mb-2"></i>
                                <p class="mb-0 fw-bold"><?= $total_modules ?> Certificados</p>
                            </div>
                        </div>
                    </div>
                    <p class="fs-5 mb-4">
                        Você agora possui conhecimento completo em educação financeira!<br>
                        Continue praticando e investindo em seu futuro financeiro.
                    </p>
                    <button type="button" class="btn btn-light btn-lg me-2" data-bs-dismiss="modal">
                        <i class="bi bi-house-fill me-2"></i>Ir para Início
                    </button>
                    <a href="investments.php" class="btn btn-warning btn-lg">
                        <i class="bi bi-graph-up me-2"></i>Ver Investimentos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Verificar se deve mostrar modal de celebração
        <?php if ($all_courses_completed && isset($_GET['celebrate'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('allCoursesCompleteModal'));
            modal.show();
            createConfetti();
        });
        <?php endif; ?>

        // Carregar vídeo no modal
        const lessonModal = document.getElementById('lessonModal');
        lessonModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const lessonId = button.getAttribute('data-lesson-id');
            const moduleId = button.getAttribute('data-module-id');
            const lessonTitle = button.getAttribute('data-lesson-title');
            const lessonDesc = button.getAttribute('data-lesson-desc');
            const lessonUrl = button.getAttribute('data-lesson-url');
            const isCompleted = button.getAttribute('data-lesson-completed') === '1';
            
            document.getElementById('lessonTitle').textContent = lessonTitle;
            document.getElementById('lessonDesc').textContent = lessonDesc;
            document.getElementById('videoContainer').innerHTML = 
                `<iframe src="${lessonUrl}?autoplay=1" allowfullscreen allow="autoplay"></iframe>`;
            document.getElementById('modalLessonId').value = lessonId;
            document.getElementById('modalModuleId').value = moduleId;
            document.getElementById('modalCompleteCheck').checked = isCompleted;
            
            // Atualizar label e badge do status
            updateLessonStatus(isCompleted);
        });
        
        // Função para atualizar o status visual da aula
        function updateLessonStatus(isCompleted) {
            const label = document.getElementById('checkboxLabel');
            const badge = document.getElementById('lessonStatus');
            
            if (isCompleted) {
                label.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Aula concluída';
                badge.className = 'badge bg-success';
                badge.innerHTML = '<i class="bi bi-check-lg me-1"></i>Completo';
            } else {
                label.innerHTML = '<i class="bi bi-circle me-2"></i>Marcar como concluída';
                badge.className = 'badge bg-secondary';
                badge.innerHTML = '<i class="bi bi-clock me-1"></i>Pendente';
            }
        }
        
        // Atualizar status ao mudar o checkbox (antes de submeter)
        document.getElementById('modalCompleteCheck').addEventListener('change', function() {
            updateLessonStatus(this.checked);
        });
        
        // Limpar vídeo ao fechar modal
        lessonModal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('videoContainer').innerHTML = '';
        });

        // Função para criar confete
        function createConfetti() {
            const colors = ['#f0f', '#0ff', '#ff0', '#f00', '#0f0', '#00f'];
            const confettiCount = 100;
            
            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 5000);
                }, i * 30);
            }
        }

        // Detectar conclusão de módulo (pode ser melhorado com AJAX)
        <?php 
        // Verificar se algum módulo foi completado nesta requisição
        if (isset($_POST['toggle_lesson']) && isset($_POST['module_id'])) {
            $check_module = $_POST['module_id'];
            if ($module_progress[$check_module]['is_completed'] && !isset($certificates[$check_module])) {
                echo "
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('completedModuleName').textContent = '{$courses[$check_module]['title']}';
                    const modal = new bootstrap.Modal(document.getElementById('moduleCompleteModal'));
                    modal.show();
                    createConfetti();
                    
                    // Redirecionar com parâmetro celebrate se todos os cursos foram completados
                    setTimeout(() => {
                        if ({$all_courses_completed}) {
                            window.location.href = 'education.php?celebrate=1';
                        }
                    }, 3000);
                });
                ";
            }
        }
        ?>
    </script>
</body>
</html>