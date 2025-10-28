<?php
session_start();
require_once 'config/database.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Buscar dados resumidos do usuário
$stmt = $pdo->prepare("SELECT 
    COALESCE(SUM(CASE WHEN tipo = 'receita' THEN valor END), 0) as total_receitas,
    COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor END), 0) as total_despesas,
    COUNT(*) as total_transacoes
    FROM transactions WHERE user_id = ? AND MONTH(data_transacao) = MONTH(CURRENT_DATE())");
$stmt->execute([$user_id]);
$financial_summary = $stmt->fetch();

$saldo_mensal = $financial_summary['total_receitas'] - $financial_summary['total_despesas'];

// Buscar investimentos
$stmt = $pdo->prepare("SELECT COUNT(*) as total_investimentos, 
    COALESCE(SUM(quantidade * preco_compra), 0) as total_investido 
    FROM investments WHERE user_id = ?");
$stmt->execute([$user_id]);
$investment_summary = $stmt->fetch();

// Buscar última transação
$stmt = $pdo->prepare("SELECT categoria, valor, tipo, data_transacao FROM transactions 
    WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$last_transaction = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início - FinançasJá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-modern sticky-top">
    <div class="container">
        <a class="navbar-brand" href="home.php">
            <i class="bi bi-gem me-2"></i>FinançasJá
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
                    <a class="nav-link" href="conversabot.php">
                        <i class="bi bi-robot me-1"></i>Assistente IA
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="education.php">
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

    <div class="container-fluid p-0">
        <!-- Hero Section -->
        <section class="hero-modern">
            <div class="container">
                <div class="hero-content text-center">
                    <div class="row align-items-center">
                        <div class="col-lg-8 mx-auto">
                            <h1 class="display-4 fw-bold mb-4 fade-in-up">
                                Olá, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?>!
                            </h1>
                            <p class="lead mb-4 fade-in-up" style="animation-delay: 0.2s;">
                                Sua vida financeira em um só lugar. Controle, invista e aprenda com inteligência artificial.
                            </p>
                            <div class="d-flex gap-3 justify-content-center fade-in-up" style="animation-delay: 0.4s;">
                                <a href="dashboard.php" class="btn btn-purple btn-modern">
                                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                </a>
                                <a href="conversabot.php" class="btn btn-outline-purple btn-modern">
                                    <i class="bi bi-robot me-2"></i>Assistente IA
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Cards -->
        <section class="py-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="stat-card-modern success">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Receitas (Mês)</h6>
                                    <h3 class="text-success mb-0">R$ <?= number_format($financial_summary['total_receitas'], 2, ',', '.') ?></h3>
                                </div>
                                <div class="text-success" style="font-size: 2.5rem;">
                                    <i class="bi bi-arrow-up-circle-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card-modern danger">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Despesas (Mês)</h6>
                                    <h3 class="text-danger mb-0">R$ <?= number_format($financial_summary['total_despesas'], 2, ',', '.') ?></h3>
                                </div>
                                <div class="text-danger" style="font-size: 2.5rem;">
                                    <i class="bi bi-arrow-down-circle-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card-modern <?= $saldo_mensal >= 0 ? 'info' : 'warning' ?>">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Saldo (Mês)</h6>
                                    <h3 class="<?= $saldo_mensal >= 0 ? 'text-info' : 'text-warning' ?> mb-0">
                                        R$ <?= number_format($saldo_mensal, 2, ',', '.') ?>
                                    </h3>
                                </div>
                                <div class="<?= $saldo_mensal >= 0 ? 'text-info' : 'text-warning' ?>" style="font-size: 2.5rem;">
                                    <i class="bi bi-wallet-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card-modern">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Investido</h6>
                                    <h3 class="text-purple mb-0">R$ <?= number_format($investment_summary['total_investido'], 2, ',', '.') ?></h3>
                                </div>
                                <div class="text-purple" style="font-size: 2.5rem;">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Features -->
        <section class="py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-gradient">Suas Funcionalidades</h2>
                    <p class="text-muted">Tudo que você precisa para controlar suas finanças</p>
                </div>
                
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card-modern h-100" onclick="window.location.href='dashboard.php'" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="text-purple mb-3" style="font-size: 3rem;">
                                    <i class="bi bi-speedometer2"></i>
                                </div>
                                <h5 class="fw-bold mb-3">Gerenciador Financeiro</h5>
                                <p class="text-muted mb-4">Controle completo de receitas e despesas com análises em tempo real.</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge badge-purple"><?= $financial_summary['total_transacoes'] ?> transações</span>
                                    <i class="bi bi-arrow-right text-purple"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card-modern h-100" onclick="window.location.href='investments.php'" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="text-success mb-3" style="font-size: 3rem;">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <h5 class="fw-bold mb-3">Carteira de Investimentos</h5>
                                <p class="text-muted mb-4">Acompanhe ações, fundos e criptomoedas em tempo real com APIs integradas.</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge badge-modern" style="background: var(--success);"><?= $investment_summary['total_investimentos'] ?> ativos</span>
                                    <i class="bi bi-arrow-right text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card-modern h-100" onclick="window.location.href='conversabot.php'" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="text-info mb-3" style="font-size: 3rem;">
                                    <i class="bi bi-robot"></i>
                                </div>
                                <h5 class="fw-bold mb-3">Assistente IA</h5>
                                <p class="text-muted mb-4">Consultoria financeira 24/7 com inteligência artificial avançada.</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge badge-modern" style="background: var(--info);">Online</span>
                                    <i class="bi bi-arrow-right text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Academia Financeira -->
        <section class="py-5" style="background: rgba(138, 43, 226, 0.05);">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <h2 class="fw-bold mb-4">Academia Financeira</h2>
                        <p class="text-muted mb-4">Aprenda sobre investimentos, planejamento financeiro e muito mais com nossos cursos especializados.</p>
                        <div class="d-flex gap-3">
                            <a href="education.php" class="btn btn-purple btn-modern">
                                <i class="bi bi-mortarboard me-2"></i>Começar Agora
                            </a>
                            <div class="d-flex align-items-center text-muted">
                                <i class="bi bi-star-fill text-warning me-1"></i>
                                <span>4.9/5 avaliação</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="card-modern text-center p-3">
                                    <i class="bi bi-book-fill text-purple fs-2 mb-2"></i>
                                    <h6>Módulo 1</h6>
                                    <small class="text-muted">Fundamentos</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card-modern text-center p-3">
                                    <i class="bi bi-graph-up text-success fs-2 mb-2"></i>
                                    <h6>Módulo 2</h6>
                                    <small class="text-muted">Investimentos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="py-5">
            <div class="container">
                <h3 class="fw-bold mb-4">Ações Rápidas</h3>
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="dashboard.php" class="card-modern d-block text-decoration-none">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-plus-circle-fill text-success fs-3 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Nova Transação</h6>
                                    <small class="text-muted">Adicionar receita/despesa</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="investments.php" class="card-modern d-block text-decoration-none">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-graph-up-arrow text-info fs-3 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Novo Investimento</h6>
                                    <small class="text-muted">Adicionar à carteira</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="conversabot.php" class="card-modern d-block text-decoration-none">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-chat-dots-fill text-purple fs-3 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Consultar IA</h6>
                                    <small class="text-muted">Tirar dúvidas financeiras</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="education.php" class="card-modern d-block text-decoration-none">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-book-fill text-warning fs-3 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Aprender</h6>
                                    <small class="text-muted">Cursos e materiais</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animações suaves nos cards
            const cards = document.querySelectorAll('.card-modern');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                    }
                });
            }, { threshold: 0.1 });

            cards.forEach((card) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });

            // Atalhos de teclado
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case '1':
                            e.preventDefault();
                            window.location.href = 'dashboard.php';
                            break;
                        case '2':
                            e.preventDefault();
                            window.location.href = 'investments.php';
                            break;
                        case '3':
                            e.preventDefault();
                            window.location.href = 'conversabot.php';
                            break;
                        case '4':
                            e.preventDefault();
                            window.location.href = 'education.php';
                            break;
                    }
                }
            });
        });
    </script>
    <!-- Footer -->
<footer class="footer-modern">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand mb-3">FinançasJá</div>
                <p>
                    Sua plataforma completa para gestão financeira pessoal com inteligência artificial.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>

            <!-- Plataforma -->
            <div class="col-lg-2">
                <h6 class="fw-bold mb-3">Plataforma</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="dashboard.php" class="footer-link">Dashboard</a></li>
                    <li class="mb-2"><a href="investments.php" class="footer-link">Investimentos</a></li>
                    <li class="mb-2"><a href="conversabot.php" class="footer-link">Assistente IA</a></li>
                    <li class="mb-2"><a href="education.php" class="footer-link">Academia</a></li>
                </ul>
            </div>

            <!-- Recursos -->
            <div class="col-lg-2">
                <h6 class="fw-bold mb-3">Recursos</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="quiz.php" class="footer-link">Quiz Financeiro</a></li>
                    <li class="mb-2"><a href="exercicios.php" class="footer-link">Exercícios</a></li>
                    <li class="mb-2"><a href="plans.php" class="footer-link">Planos</a></li>
                    <li class="mb-2"><a href="plans1.php" class="footer-link">Planos Premium</a></li>
                </ul>
            </div>

            <!-- Suporte -->
            <div class="col-lg-2">
                <h6 class="fw-bold mb-3">Suporte</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="support.php" class="footer-link">Central de Ajuda</a></li>
                    <li class="mb-2"><a href="support1.php" class="footer-link">Contato</a></li>
                    <li class="mb-2"><a href="about.php" class="footer-link">Sobre Nós</a></li>
                    <li class="mb-2"><a href="about1.php" class="footer-link">Nossa História</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div class="col-lg-2">
                <h6 class="fw-bold mb-3">Conta</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="login.php" class="footer-link">Login</a></li>
                    <li class="mb-2"><a href="register.php" class="footer-link">Criar Conta</a></li>
                    <li class="mb-2"><a href="logout.php" class="footer-link">Sair</a></li>
                    <li class="mb-2"><a href="home.php" class="footer-link">Início</a></li>
                </ul>
            </div>
        </div>

        <hr class="my-4" style="border-color: rgba(138, 43, 226, 0.2);">

        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">&copy; 2025 FinançasJá. Todos os direitos reservados.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">Feito com <i class="bi bi-heart-fill text-danger"></i> para sua liberdade financeira</p>
            </div>
        </div>
    </div>
</footer>

</body>
</html>