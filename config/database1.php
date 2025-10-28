<?php
// config/database.php
$host = 'sql101.infinityfree.com';
$dbname = 'if0_40062711_financas_app';
$username = 'if0_40062711';
$password = 'NDzaCZ0r9rCQvH';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco: " . $e->getMessage());
}

// Função para validar se o usuário está logado
function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

// Função para redirecionar se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
?>