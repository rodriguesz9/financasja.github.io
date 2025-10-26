<?php
session_start();

// Mensagem de cadastro bem-sucedido
$success = '';
if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso') {
    $success = 'Conta criada com sucesso! Faça login para continuar.';
}

$error = '';
// resto do código do login...
require_once 'config/database.php';

$error = '';

if ($_POST) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, nome, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            header('Location: home.php');
            exit();
        } else {
            $error = 'Email ou senha incorretos';
        }
    } else {
        $error = 'Preencha todos os campos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FinanceApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #170027ff 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
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
                <div class="login-card p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-bar-chart-fill text-primary fs-1"></i>
                        <h2 class="fw-bold mt-2">FinanceApp</h2>
                        <p class="text-muted">Faça login em sua conta</p>
                    </div>
                    <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">Não tem conta? <a href="register.php">Cadastre-se</a></p>
                        <a href="index.php" class="btn btn-link">Voltar ao início</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

</body>
</html>