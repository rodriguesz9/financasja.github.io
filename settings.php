<?php
// settings.php - Página de Configurações do Usuário

session_start();
require_once 'config/database.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Processar mudança de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = 'As senhas novas não coincidem!';
    } else {
        // Verificar senha antiga
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (password_verify($old_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $_SESSION['success_message'] = 'Senha atualizada com sucesso!';
                header('Location: settings.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'Erro ao atualizar senha!';
            }
        } else {
            $_SESSION['error_message'] = 'Senha antiga incorreta!';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - FinançasJá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <style>
        /* Estilos semelhantes ao dashboard.php */
        .settings-card {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
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
                    <!-- Itens semelhantes -->
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item active" href="settings.php"><i class="bi bi-gear me-2"></i>Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-modern py-4">
        <div class="container">
            <h1 class="display-5 fw-bold mb-3">Configurações</h1>
            <p class="lead mb-0">Ajuste suas preferências e segurança</p>
        </div>
    </section>

    <div class="container mt-4 mb-5">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="settings-card">
            <h4 class="fw-bold mb-4">Mudar Senha</h4>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Senha Antiga</label>
                    <input type="password" class="form-control" name="old_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nova Senha</label>
                    <input type="password" class="form-control" name="new_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Confirmar Nova Senha</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-purple btn-modern">
                    <i class="bi bi-lock me-2"></i>Atualizar Senha
                </button>
            </form>
        </div>
    </div>

    <!-- Footer semelhante -->
    <footer class="footer-modern">
        <!-- Conteúdo do footer igual ao dashboard -->
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>