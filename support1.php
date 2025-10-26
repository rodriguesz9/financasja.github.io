<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_email = $is_logged_in ? ($_SESSION['user_email'] ?? '') : '';

$success_message = '';
$error_message = '';

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_support'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        // Aqui você pode salvar no banco ou enviar email
        $success_message = 'Mensagem enviada com sucesso! Retornaremos em até 24 horas.';
    } else {
        $error_message = 'Por favor, preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte - FinançasJá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-modern sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?= $is_logged_in ? 'home.php' : 'index.php' ?>">
                <i class="bi bi-gem me-2"></i>FinançasJá
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($is_logged_in): ?>
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
                            <a class="nav-link" href="education.php">
                                <i class="bi bi-mortarboard me-1"></i>Academia
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Mais
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="about.php"><i class="bi bi-info-circle me-2"></i>Sobre</a></li>
                            <li><a class="dropdown-item" href="plans.php"><i class="bi bi-star me-2"></i>Planos</a></li>
                            <li><a class="dropdown-item active" href="support.php"><i class="bi bi-headset me-2"></i>Suporte</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($user_name) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Configurações</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-purple btn-modern ms-2" href="register.php">
                                Começar Grátis
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-modern">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="display-4 fw-bold mb-4 fade-in-up">Central de Suporte</h1>
                <p class="lead mb-0 fade-in-up" style="animation-delay: 0.2s;">
                    Estamos aqui para ajudar você em qualquer momento
                </p>
            </div>
        </div>
    </section>

    <!-- Canais de Atendimento -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Como Podemos Ajudar?</h2>
                <p class="text-muted">Escolha o canal mais conveniente para você</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card-modern text-center h-100">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <i class="bi bi-chat-dots-fill text-purple" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Chat Online</h4>
                            <p class="text-muted mb-4">
                                Converse com nosso assistente IA ou com um atendente em tempo real
                            </p>
                            <a href="chatbot.php" class="btn btn-purple btn-modern">
                                <i class="bi bi-robot me-2"></i>Iniciar Chat
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-modern text-center h-100">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <i class="bi bi-envelope-fill text-info" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="fw-bold mb-3">E-mail</h4>
                            <p class="text-muted mb-4">
                                Envie sua dúvida por e-mail e responderemos em até 24 horas
                            </p>
                            <a href="#contact-form" class="btn btn-outline-info btn-modern">
                                <i class="bi bi-send me-2"></i>Enviar E-mail
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-modern text-center h-100">
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <i class="bi bi-telephone-fill text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Telefone</h4>
                            <p class="text-muted mb-4">
                                Ligue para nossa central de atendimento em horário comercial
                            </p>
                            <a href="tel:+551130000000" class="btn btn-outline-success btn-modern">
                                <i class="bi bi-telephone me-2"></i>(11) 3000-0000
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="py-5" style="background: rgba(138, 43, 226, 0.05);">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Perguntas Frequentes</h2>
                <p class="text-muted">Encontre respostas rápidas para as dúvidas mais comuns</p>
            </div>
            
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card-modern h-100">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-3">
                                        <i class="bi bi-question-circle text-purple me-2"></i>
                                        Como criar uma conta?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Clique em "Começar Grátis" no topo da página, preencha seus dados e pronto! 
                                        Você terá acesso imediato à plataforma.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card-modern h-100">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-3">
                                        <i class="bi bi-question-circle text-purple me-2"></i>
                                        Como adicionar transações?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        No Dashboard, clique em "Nova Transação", escolha o tipo (receita ou despesa), 
                                        preencha os detalhes e salve.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card-modern h-100">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-3">
                                        <i class="bi bi-question-circle text-purple me-2"></i>
                                        Como usar o Assistente IA?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Acesse a seção "Assistente IA" no menu e faça suas perguntas sobre finanças. 
                                        Nossa IA responde instantaneamente!
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card-modern h-100">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-3">
                                        <i class="bi bi-question-circle text-purple me-2"></i>
                                        Meus dados estão seguros?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Sim! Utilizamos criptografia SSL de nível bancário e seguimos todas as 
                                        normas da LGPD para proteger seus dados.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card-modern h-100">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-3">
                                        <i class="bi bi-question-circle text-purple me-2"></i>
                                        Como acompanhar investimentos?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Na seção "Investimentos", você pode adicionar seus ativos e acompanhar 
                                        cotações em tempo real com gráficos interativos.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card-modern h-100">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-3">
                                        <i class="bi bi-question-circle text-purple me-2"></i>
                                        Como fazer upgrade de plano?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Acesse "Planos" no menu, escolha o plano desejado e clique em "Assinar". 
                                        O upgrade é instantâneo!
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Formulário de Contato -->
    <section class="py-5" id="contact-form">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card-modern">
                        <div class="card-body p-5">
                            <div class="text-center mb-5">
                                <i class="bi bi-envelope-heart text-purple" style="font-size: 4rem;"></i>
                                <h3 class="fw-bold mt-3 mb-2">Envie sua Mensagem</h3>
                                <p class="text-muted">Preencha o formulário abaixo e entraremos em contato</p>
                            </div>

                            <?php if ($success_message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle-fill me-2"></i><?= $success_message ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($error_message): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error_message ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nome Completo</label>
                                        <input type="text" class="form-control-modern" name="name" 
                                               value="<?= htmlspecialchars($user_name) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">E-mail</label>
                                        <input type="email" class="form-control-modern" name="email" 
                                               value="<?= htmlspecialchars($user_email) ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Assunto</label>
                                        <select class="form-control-modern" name="subject" required>
                                            <option value="">Selecione o assunto</option>
                                            <option value="duvida">Dúvida sobre a plataforma</option>
                                            <option value="tecnico">Problema técnico</option>
                                            <option value="financeiro">Questão financeira</option>
                                            <option value="sugestao">Sugestão</option>
                                            <option value="outro">Outro assunto</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Mensagem</label>
                                        <textarea class="form-control-modern" name="message" rows="6" 
                                                  placeholder="Descreva sua dúvida ou problema..." required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="send_support" class="btn btn-purple btn-modern btn-lg w-100">
                                            <i class="bi bi-send-fill me-2"></i>Enviar Mensagem
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recursos Adicionais -->
    <section class="py-5" style="background: linear-gradient(135deg, var(--primary-purple), var(--accent-purple));">
        <div class="container">
            <div class="text-center text-white mb-5">
                <h2 class="fw-bold mb-3">Recursos de Ajuda</h2>
                <p class="mb-0">Explore nossos materiais de suporte</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="text-center text-white">
                        <div class="mb-3">
                            <i class="bi bi-book-fill" style="font-size: 3rem; opacity: 0.9;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Base de Conhecimento</h5>
                        <p style="opacity: 0.8;" class="mb-3">Tutoriais e guias completos</p>
                        <a href="education.php" class="btn btn-light btn-sm">Acessar</a>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="text-center text-white">
                        <div class="mb-3">
                            <i class="bi bi-play-circle-fill" style="font-size: 3rem; opacity: 0.9;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Vídeo Tutoriais</h5>
                        <p style="opacity: 0.8;" class="mb-3">Aprenda assistindo</p>
                        <a href="education.php" class="btn btn-light btn-sm">Ver Vídeos</a>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="text-center text-white">
                        <div class="mb-3">
                            <i class="bi bi-people-fill" style="font-size: 3rem; opacity: 0.9;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Comunidade</h5>
                        <p style="opacity: 0.8;" class="mb-3">Conecte-se com usuários</p>
                        <a href="#" class="btn btn-light btn-sm">Participar</a>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="text-center text-white">
                        <div class="mb-3">
                            <i class="bi bi-clock-history" style="font-size: 3rem; opacity: 0.9;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Status do Sistema</h5>
                        <p style="opacity: 0.8;" class="mb-3">Verifique a disponibilidade</p>
                        <a href="#" class="btn btn-light btn-sm">Verificar</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Horário de Atendimento -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">Horário de Atendimento</h2>
                    <div class="card-modern mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-chat-dots text-purple me-2"></i>
                                    <strong>Chat Online</strong>
                                </div>
                                <span class="badge bg-success">24/7 disponível</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-modern mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-telephone text-success me-2"></i>
                                    <strong>Telefone</strong>
                                </div>
                                <span class="text-muted">Seg-Sex: 9h às 18h</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-modern">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-envelope text-info me-2"></i>
                                    <strong>E-mail</strong>
                                </div>
                                <span class="text-muted">Resposta em 24h</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card-modern text-center p-5" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));">
                        <i class="bi bi-headset text-purple mb-3" style="font-size: 5rem;"></i>
                        <h4 class="fw-bold mb-3">Equipe Sempre Pronta</h4>
                        <p class="text-muted mb-4">
                            Nossa equipe de suporte está preparada para resolver qualquer dúvida ou 
                            problema que você possa ter. Sua satisfação é nossa prioridade!
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <div>
                                <h3 class="text-purple fw-bold mb-0">4.9/5</h3>
                                <small class="text-muted">Avaliação</small>
                            </div>
                            <div class="vr"></div>
                            <div>
                                <h3 class="text-success fw-bold mb-0">98%</h3>
                                <small class="text-muted">Satisfação</small>
                            </div>
                            <div class="vr"></div>
                            <div>
                                <h3 class="text-info fw-bold mb-0">&lt;2h</h3>
                                <small class="text-muted">Resposta</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand mb-3">
                        <i class="bi bi-gem me-2"></i>FinançasJá
                    </div>
                    <p class="text-muted">
                        Sua plataforma completa para gestão financeira pessoal com inteligência artificial.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-icon">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="bi bi-linkedin"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Plataforma</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="dashboard.php" class="footer-link">Dashboard</a></li>
                        <li class="mb-2"><a href="investments.php" class="footer-link">Investimentos</a></li>
                        <li class="mb-2"><a href="chatbot.php" class="footer-link">Assistente IA</a></li>
                        <li class="mb-2"><a href="education.php" class="footer-link">Academia</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Empresa</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="about.php" class="footer-link">Sobre</a></li>
                        <li class="mb-2"><a href="plans.php" class="footer-link">Planos</a></li>
                        <li class="mb-2"><a href="support.php" class="footer-link">Suporte</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Legal</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="footer-link">Privacidade</a></li>
                        <li class="mb-2"><a href="#" class="footer-link">Termos</a></li>
                        <li class="mb-2"><a href="#" class="footer-link">Segurança</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Contato</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2 text-muted"><i class="bi bi-envelope me-2"></i>contato@financasja.com</li>
                        <li class="mb-2 text-muted"><i class="bi bi-telephone me-2"></i>(11) 3000-0000</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(138, 43, 226, 0.2);">
            <div class="text-center">
                <p class="text-muted mb-0">&copy; 2024 FinançasJá. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            document.querySelectorAll('.card-modern').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.8s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>