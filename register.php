<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_POST) {
    $nome = trim($_POST['nome']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($nome) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Preencha todos os campos';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'As senhas não coincidem';
    } else {
        // Verificar se o email já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Este email já está cadastrado';
        } else {
            // Cadastrar usuário
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nome, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$nome, $email, $hashedPassword])) {
                // Redireciona imediatamente para login
                header('Location: login.php?cadastro=sucesso');
                exit();
            } else {
                $error = 'Erro ao criar conta. Tente novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - FinançasJá</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <style>
        body {
            background: linear-gradient(135deg, #170027ff 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="register-card p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-bar-chart-fill text-primary fs-1"></i>
                        <h2 class="fw-bold mt-2">FinançasJá</h2>
                        <p class="text-muted">Crie sua conta</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Criar Conta</button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">Já tem conta? <a href="login.php">Faça login</a></p>
                        <a href="index.php" class="btn btn-link">Voltar ao início</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>