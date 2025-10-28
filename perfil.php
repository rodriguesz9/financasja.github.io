<?php
session_start();
require_once 'config/database.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Atualizar perfil
if ($_POST && isset($_POST['update_profile'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $data_nascimento = $_POST['data_nascimento'];
    $bio = trim($_POST['bio']);

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, telefone = ?, data_nascimento = ?, bio = ? WHERE id = ?");
    if ($stmt->execute([$nome, $email, $telefone, $data_nascimento, $bio, $user_id])) {
        $_SESSION['user_name'] = $nome;
        $success = "Perfil atualizado com sucesso!";
        // Recarregar dados
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Estatísticas do usuário
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM investments WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats_investments = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats_transactions = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$created_at = $stmt->fetch()['created_at'];
$days_member = floor((time() - strtotime($created_at)) / 86400);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - FinançasJá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 0;
            color: white;
            margin-bottom: -80px;
            border-radius: 0 0 50px 50px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .avatar-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            position: relative;
        }

        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            font-weight: bold;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .badge-profile {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: 3px solid white;
            font-size: 18px;
        }

        .stat-box {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .info-item {
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1rem;
            color: #212529;
        }
    </style>
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
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($user_name) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="profile.php"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Configurações</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-2">Meu Perfil</h1>
            <p class="lead">Gerencie suas informações pessoais</p>
        </div>
    </section>

    <div class="container" style="margin-top: 100px; margin-bottom: 50px;">
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Coluna Esquerda - Avatar e Stats -->
            <div class="col-lg-4">
                <!-- Avatar Card -->
                <div class="profile-card text-center">
                    <div class="avatar-container">
                        <div class="avatar">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <div class="badge-profile">
                            <i class="bi bi-check"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                    <p class="text-muted mb-3"><?= htmlspecialchars($user['email']) ?></p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-purple btn-modern btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="bi bi-pencil me-2"></i>Editar Perfil
                        </button>
                        <a href="settings.php" class="btn btn-outline-purple btn-modern btn-sm">
                            <i class="bi bi-gear me-2"></i>Configurações
                        </a>
                    </div>
                </div>

                <!-- Stats Card -->
                <div class="profile-card">
                    <h5 class="fw-bold mb-4"><i class="bi bi-graph-up text-purple me-2"></i>Estatísticas</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats_investments ?></div>
                                <div class="stat-label">Investimentos</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats_transactions ?></div>
                                <div class="stat-label">Transações</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="stat-box">
                                <div class="stat-number"><?= $days_member ?></div>
                                <div class="stat-label">Dias como Membro</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita - Informações -->
            <div class="col-lg-8">
                <!-- Informações Pessoais -->
                <div class="profile-card">
                    <h5 class="fw-bold mb-4"><i class="bi bi-person-badge text-purple me-2"></i>Informações Pessoais</h5>
                    
                    <div class="info-item">
                        <div class="info-label">Nome Completo</div>
                        <div class="info-value"><?= htmlspecialchars($user['name']) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Telefone</div>
                        <div class="info-value"><?= $user['telefone'] ? htmlspecialchars($user['telefone']) : '<span class="text-muted">Não informado</span>' ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Data de Nascimento</div>
                        <div class="info-value"><?= $user['data_nascimento'] ? date('d/m/Y', strtotime($user['data_nascimento'])) : '<span class="text-muted">Não informado</span>' ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Membro desde</div>
                        <div class="info-value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                    </div>
                </div>

                <!-- Biografia -->
                <div class="profile-card">
                    <h5 class="fw-bold mb-4"><i class="bi bi-file-text text-purple me-2"></i>Sobre Mim</h5>
                    <p class="text-muted mb-0">
                        <?= $user['bio'] ? nl2br(htmlspecialchars($user['bio'])) : '<em>Nenhuma biografia adicionada ainda. Clique em "Editar Perfil" para adicionar uma descrição sobre você.</em>' ?>
                    </p>
                </div>

                <!-- Atividade Recente -->
                <div class="profile-card">
                    <h5 class="fw-bold mb-4"><i class="bi bi-clock-history text-purple me-2"></i>Atividade Recente</h5>
                    <div class="timeline">
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-purple bg-opacity-10 rounded-circle p-3">
                                    <i class="bi bi-plus-circle text-purple"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="fw-bold mb-1">Conta Criada</h6>
                                <p class="text-muted mb-0">
                                    <small><?= date('d/m/Y \à\s H:i', strtotime($user['created_at'])) ?></small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Perfil -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil text-purple me-2"></i>Editar Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nome Completo</label>
                                <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Telefone</label>
                                <input type="tel" class="form-control" name="telefone" value="<?= htmlspecialchars($user['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Data de Nascimento</label>
                                <input type="date" class="form-control" name="data_nascimento" value="<?= $user['data_nascimento'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Biografia</label>
                            <textarea class="form-control" name="bio" rows="4" placeholder="Conte um pouco sobre você..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <small class="text-muted">Máximo 500 caracteres</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="update_profile" class="btn btn-purple btn-modern">
                            <i class="bi bi-check-lg me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand mb-3">FinançasJá</div>
                    <p>Sua plataforma completa para gestão financeira pessoal com inteligência artificial.</p>
                </div>
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="fw-bold mb-3">Plataforma</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><a href="dashboard.php" class="footer-link">Dashboard</a></li>
                                <li class="mb-2"><a href="investments.php" class="footer-link">Investimentos</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold mb-3">Conta</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><a href="profile.php" class="footer-link">Perfil</a></li>
                                <li class="mb-2"><a href="settings.php" class="footer-link">Configurações</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold mb-3">Suporte</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><a href="support.php" class="footer-link">Ajuda</a></li>
                                <li class="mb-2"><a href="about.php" class="footer-link">Sobre</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(138, 43, 226, 0.2);">
            <div class="text-center">
                <p class="mb-0">&copy; 2025 FinançasJá. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
</body>

</html>