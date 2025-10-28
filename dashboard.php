<?php
session_start();
require_once 'config/database.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Exportar relatório
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $stmt = $pdo->prepare("SELECT id, tipo, categoria, descricao, valor, data_transacao, created_at FROM transactions WHERE user_id = ? ORDER BY data_transacao DESC");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio_transacoes.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Tipo', 'Categoria', 'Descrição', 'Valor', 'Data Transação', 'Criado Em']);

    foreach ($transactions as $row) {
        fputcsv($output, [
            $row['id'],
            $row['tipo'],
            $row['categoria'],
            $row['descricao'],
            $row['valor'],
            $row['data_transacao'],
            $row['created_at']
        ]);
    }
    exit();
}

// Processar exclusão de transação
if (isset($_GET['delete_transaction'])) {
    $transaction_id = (int)$_GET['delete_transaction'];
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$transaction_id, $user_id]);
    header('Location: dashboard.php');
    exit();
}

// Processar exclusão de orçamento
if (isset($_GET['delete_budget'])) {
    $budget_id = (int)$_GET['delete_budget'];
    $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
    $stmt->execute([$budget_id, $user_id]);
    $_SESSION['success_message'] = 'Orçamento excluído com sucesso!';
    header('Location: dashboard.php');
    exit();
}

// Processar nova transação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $tipo = $_POST['tipo'];
    $categoria = $_POST['categoria'];
    $descricao = $_POST['descricao'];
    $valor = (float)$_POST['valor'];
    $data = $_POST['data'];

    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, tipo, categoria, descricao, valor, data_transacao) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $tipo, $categoria, $descricao, $valor, $data])) {
        $_SESSION['success_message'] = 'Transação adicionada com sucesso!';
        header('Location: dashboard.php');
        exit();
    }
}

// Processar edição de transação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaction'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    $tipo = $_POST['tipo'];
    $categoria = $_POST['categoria'];
    $descricao = $_POST['descricao'];
    $valor = (float)$_POST['valor'];
    $data = $_POST['data'];

    $stmt = $pdo->prepare("UPDATE transactions SET tipo = ?, categoria = ?, descricao = ?, valor = ?, data_transacao = ? WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$tipo, $categoria, $descricao, $valor, $data, $transaction_id, $user_id])) {
        $_SESSION['success_message'] = 'Transação atualizada com sucesso!';
        header('Location: dashboard.php');
        exit();
    }
}

// Processar nova meta de orçamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_budget'])) {
    $categoria = $_POST['categoria'];
    $meta_valor = (float)$_POST['meta_valor'];
    $mes = $_POST['mes'];

    $stmt = $pdo->prepare("INSERT INTO budgets (user_id, categoria, meta_valor, mes) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $categoria, $meta_valor, $mes])) {
        $_SESSION['success_message'] = 'Meta de orçamento adicionada com sucesso!';
        header('Location: dashboard.php');
        exit();
    }
}

// Processar edição de orçamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_budget'])) {
    $budget_id = (int)$_POST['budget_id'];
    $categoria = $_POST['categoria'];
    $meta_valor = (float)$_POST['meta_valor'];
    $mes = $_POST['mes'];

    $stmt = $pdo->prepare("UPDATE budgets SET categoria = ?, meta_valor = ?, mes = ? WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$categoria, $meta_valor, $mes, $budget_id, $user_id])) {
        $_SESSION['success_message'] = 'Orçamento atualizado com sucesso!';
        header('Location: dashboard.php');
        exit();
    }
}

// Calcular totais
$stmt = $pdo->prepare("SELECT 
    COALESCE(SUM(CASE WHEN tipo = 'receita' THEN valor END), 0) as total_receitas,
    COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor END), 0) as total_despesas
    FROM transactions WHERE user_id = ? AND MONTH(data_transacao) = MONTH(CURRENT_DATE())");
$stmt->execute([$user_id]);
$totals = $stmt->fetch();

$saldo = $totals['total_receitas'] - $totals['total_despesas'];

// Buscar últimas transações
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY data_transacao DESC, created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll();

// Buscar gastos por categoria (últimos 30 dias)
$stmt = $pdo->prepare("SELECT categoria, SUM(valor) as total FROM transactions 
    WHERE user_id = ? AND tipo = 'despesa' AND data_transacao >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) 
    GROUP BY categoria ORDER BY total DESC LIMIT 5");
$stmt->execute([$user_id]);
$expenses_by_category = $stmt->fetchAll();

// Buscar metas de orçamento para o mês atual
$current_month = date('Y-m');
$stmt = $pdo->prepare("SELECT id, categoria, meta_valor FROM budgets WHERE user_id = ? AND mes = ?");
$stmt->execute([$user_id, $current_month]);
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular progresso para cada meta
$budget_progress = [];
foreach ($budgets as $budget) {
    $stmt = $pdo->prepare("SELECT SUM(valor) as gasto FROM transactions 
        WHERE user_id = ? AND tipo = 'despesa' AND categoria = ? AND DATE_FORMAT(data_transacao, '%Y-%m') = ?");
    $stmt->execute([$user_id, $budget['categoria'], $current_month]);
    $gasto = $stmt->fetchColumn() ?? 0;

    $progress = ($gasto / $budget['meta_valor']) * 100;
    $budget_progress[$budget['categoria']] = [
        'id' => $budget['id'],
        'meta' => $budget['meta_valor'],
        'gasto' => $gasto,
        'progress' => min($progress, 100)
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FinançasJá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <style>
        .opcoes {
            background-color: white;
            color: black;
        }

        img {
            loading: lazy;
        }

        .progress {
            height: 20px;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .expense-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .expense-item:last-child {
            border-bottom: none;
        }

        .budget-section .card-body {
            padding: 2rem;
        }

        .budget-item {
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .budget-item:last-child {
            border-bottom: none;
        }

        #transr {
            margin: 15px;
        }

        .opc {
            color: black;
        }

        .action-btn-group {
            display: flex;
            gap: 0.5rem;
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
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">
                            <i class="bi bi-house-fill me-1"></i>Início
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
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
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Perfil</a></li>
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

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="container-fluid p-0">
        <section class="hero-modern py-4">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-5 fw-bold mb-3">Dashboard Financeiro</h1>
                        <p class="lead mb-0">Controle completo das suas receitas e despesas em tempo real</p>
                    </div>
                    <div class="col-lg-4 text-end action-buttons">
                        <button class="btn btn-purple btn-modern" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                            <i class="bi bi-plus-lg me-2"></i>Nova Transação
                        </button>
                        <a href="dashboard.php?export=csv" class="btn btn-outline-purple btn-modern">
                            <i class="bi bi-download me-2"></i>Exportar Relatório
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-4">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="stat-card-modern success">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Receitas (Mês)</h6>
                                    <h3 class="text-success mb-0">R$ <?= number_format($totals['total_receitas'], 2, ',', '.') ?></h3>
                                </div>
                                <div class="text-success" style="font-size: 2.5rem;">
                                    <i class="bi bi-arrow-up-circle-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card-modern danger">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Despesas (Mês)</h6>
                                    <h3 class="text-danger mb-0">R$ <?= number_format($totals['total_despesas'], 2, ',', '.') ?></h3>
                                </div>
                                <div class="text-danger" style="font-size: 2.5rem;">
                                    <i class="bi bi-arrow-down-circle-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card-modern <?= $saldo >= 0 ? 'info' : 'warning' ?>">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Saldo (Mês)</h6>
                                    <h3 class="<?= $saldo >= 0 ? 'text-info' : 'text-warning' ?> mb-0">
                                        R$ <?= number_format($saldo, 2, ',', '.') ?>
                                    </h3>
                                    <small class="text-muted"><?= $saldo >= 0 ? 'Superávit' : 'Déficit' ?></small>
                                </div>
                                <div class="<?= $saldo >= 0 ? 'text-info' : 'text-warning' ?>" style="font-size: 2.5rem;">
                                    <i class="bi bi-wallet-fill"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-4">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card-modern">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="fw-bold mb-0">Visão Geral Mensal</h4>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-purple btn-sm" onclick="changeChartType('bar')">
                                            <i class="bi bi-bar-chart-fill"></i>
                                        </button>
                                        <button class="btn btn-outline-purple btn-sm" onclick="changeChartType('doughnut')">
                                            <i class="bi bi-pie-chart-fill"></i>
                                        </button>
                                    </div>
                                </div>
                                <div style="height: 300px; position: relative;">
                                    <canvas id="monthlyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card-modern">
                            <div class="card-body">
                                <h5 class="fw-bold mb-4">Gastos por Categoria</h5>
                                <?php if (empty($expenses_by_category)): ?>
                                    <div class="text-center text-muted">
                                        <i class="bi bi-pie-chart fs-1 mb-3"></i>
                                        <p>Nenhuma despesa ainda</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($expenses_by_category as $expense): ?>
                                        <div class="expense-item d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <?php
                                                    $icon = match ($expense['categoria']) {
                                                        'Alimentação' => 'bi-cup-hot-fill',
                                                        'Transporte' => 'bi-car-front-fill',
                                                        'Moradia' => 'bi-house-fill',
                                                        'Lazer' => 'bi-controller',
                                                        'Saúde' => 'bi-heart-pulse-fill',
                                                        'Educação' => 'bi-book-fill',
                                                        default => 'bi-circle-fill'
                                                    };

                                                    $color = match ($expense['categoria']) {
                                                        'Alimentação' => 'text-warning',
                                                        'Transporte' => 'text-info',
                                                        'Moradia' => 'text-primary',
                                                        'Lazer' => 'text-success',
                                                        'Saúde' => 'text-danger',
                                                        'Educação' => 'text-purple',
                                                        default => 'text-muted'
                                                    };
                                                    ?>
                                                    <i class="bi <?= $icon ?> <?= $color ?> fs-5"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($expense['categoria']) ?></h6>
                                                    <small class="text-muted">Últimos 30 dias</small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <strong>R$ <?= number_format($expense['total'], 2, ',', '.') ?></strong>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Seção: Budget Planner -->
        <section class="py-4">
            <div class="container">
                <div class="card-modern budget-section">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold mb-0">Planejamento de Orçamento</h4>
                            <button class="btn btn-purple btn-modern" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
                                <i class="bi bi-plus-lg me-2"></i>Nova Meta
                            </button>
                        </div>

                        <?php if (empty($budgets)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-wallet text-muted" style="font-size: 4rem;"></i>
                                <h4 class="text-muted mt-3">Nenhuma meta de orçamento</h4>
                                <p class="text-muted mb-4">Adicione metas para controlar seus gastos</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($budget_progress as $categoria => $data): ?>
                                <div class="budget-item">
                                    <div class="d-flex justify-content-between mb-2 align-items-center">
                                        <span><?= htmlspecialchars($categoria) ?></span>
                                        <div class="action-btn-group">
                                            <span class="me-3">R$ <?= number_format($data['gasto'], 2, ',', '.') ?> / R$ <?= number_format($data['meta'], 2, ',', '.') ?></span>
                                            <button class="btn btn-sm btn-outline-info" onclick="editBudget(<?= $data['id'] ?>, '<?= htmlspecialchars($categoria) ?>', <?= $data['meta'] ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="?delete_budget=<?= $data['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este orçamento?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar <?= $data['progress'] > 100 ? 'bg-danger' : ($data['progress'] > 80 ? 'bg-warning' : 'bg-success') ?>"
                                            role="progressbar"
                                            style="width: <?= $data['progress'] ?>%"
                                            aria-valuenow="<?= $data['progress'] ?>"
                                            aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-4">
            <div class="container">
                <div class="card-modern">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold mb-0" id="transr">Transações Recentes</h4>
                        </div>

                        <?php if (empty($recent_transactions)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-receipt text-muted" style="font-size: 4rem;"></i>
                                <h4 class="text-muted mt-3">Nenhuma transação ainda</h4>
                                <p class="text-muted mb-4">Comece adicionando sua primeira receita ou despesa</p>
                                <button class="btn btn-purple btn-modern" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                                    <i class="bi bi-plus-lg me-2"></i>Primeira Transação
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Data</th>
                                            <th>Descrição</th>
                                            <th>Categoria</th>
                                            <th>Tipo</th>
                                            <th class="text-end">Valor</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_transactions as $transaction): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?= date('d/m/Y', strtotime($transaction['data_transacao'])) ?></strong>
                                                        <br><small class="text-muted"><?= date('H:i', strtotime($transaction['created_at'])) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($transaction['descricao']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-modern badge-purple"><?= htmlspecialchars($transaction['categoria']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $transaction['tipo'] == 'receita' ? 'bg-success' : 'bg-danger' ?>">
                                                        <i class="bi <?= $transaction['tipo'] == 'receita' ? 'bi-arrow-up' : 'bi-arrow-down' ?> me-1"></i>
                                                        <?= ucfirst($transaction['tipo']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="<?= $transaction['tipo'] == 'receita' ? 'text-success' : 'text-danger' ?>">
                                                        <?= $transaction['tipo'] == 'receita' ? '+' : '-' ?>R$ <?= number_format($transaction['valor'], 2, ',', '.') ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <div class="action-btn-group">
                                                        <button class="btn btn-sm btn-outline-info" 
                                                            onclick='editTransaction(<?= json_encode($transaction) ?>)'>
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction(<?= $transaction['id'] ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-4 mb-5">
            <div class="container">
                <h4 class="fw-bold mb-4">Ações Rápidas</h4>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card-modern text-center" style="cursor: pointer;" onclick="openTransactionModal('receita')">
                            <div class="card-body p-4">
                                <i class="bi bi-plus-circle-fill text-success fs-1 mb-3"></i>
                                <h6 class="fw-bold">Nova Receita</h6>
                                <p class="text-muted small mb-0">Adicionar entrada de dinheiro</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-modern text-center" style="cursor: pointer;" onclick="openTransactionModal('despesa')">
                            <div class="card-body p-4">
                                <i class="bi bi-dash-circle-fill text-danger fs-1 mb-3"></i>
                                <h6 class="fw-bold">Nova Despesa</h6>
                                <p class="text-muted small mb-0">Registrar um gasto</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-modern text-center" style="cursor: pointer;" onclick="window.location.href='investments.php'">
                            <div class="card-body p-4">
                                <i class="bi bi-graph-up text-info fs-1 mb-3"></i>
                                <h6 class="fw-bold">Ver Investimentos</h6>
                                <p class="text-muted small mb-0">Acompanhar carteira</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-modern text-center" style="cursor: pointer;" onclick="window.location.href='conversabot.php'">
                            <div class="card-body p-4">
                                <i class="bi bi-robot text-purple fs-1 mb-3"></i>
                                <h6 class="fw-bold">Consultoria IA</h6>
                                <p class="text-muted small mb-0">Tirar dúvidas financeiras</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal para Nova Transação -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--dark-bg); color: white; border: 1px solid var(--primary-purple);">
                <div class="modal-header" style="border-bottom: 1px solid var(--primary-purple);">
                    <h5 class="modal-title">Nova Transação</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="dashboard.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="tipo" id="receita" value="receita" required>
                                <label class="btn btn-outline-success" for="receita">
                                    <i class="bi bi-arrow-up me-2"></i>Receita
                                </label>

                                <input type="radio" class="btn-check" name="tipo" id="despesa" value="despesa" required>
                                <label class="btn btn-outline-danger" for="despesa">
                                    <i class="bi bi-arrow-down me-2"></i>Despesa
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoria</label>
                            <select class="form-control-modern" name="categoria" required>
                                <option value="">Selecione o tipo primeiro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control-modern" name="descricao" placeholder="Ex: Almoço no restaurante" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor (R$)</label>
                            <input type="number" step="0.01" class="form-control-modern" name="valor" placeholder="0,00" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data</label>
                            <input type="date" class="form-control-modern" name="data" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--primary-purple);">
                        <button type="button" class="btn btn-outline-purple btn-modern" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_transaction" class="btn btn-purple btn-modern">
                            <i class="bi bi-check-lg me-2"></i>Salvar Transação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Transação -->
    <div class="modal fade" id="editTransactionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--dark-bg); color: white; border: 1px solid var(--primary-purple);">
                <div class="modal-header" style="border-bottom: 1px solid var(--primary-purple);">
                    <h5 class="modal-title">Editar Transação</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="transaction_id" id="edit_transaction_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="tipo" id="edit_receita" value="receita" required>
                                <label class="btn btn-outline-success" for="edit_receita">
                                    <i class="bi bi-arrow-up me-2"></i>Receita
                                </label>

                                <input type="radio" class="btn-check" name="tipo" id="edit_despesa" value="despesa" required>
                                <label class="btn btn-outline-danger" for="edit_despesa">
                                    <i class="bi bi-arrow-down me-2"></i>Despesa
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoria</label>
                            <select class="form-control-modern" name="categoria" id="edit_categoria" required>
                                <option value="">Selecione o tipo primeiro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control-modern" name="descricao" id="edit_descricao" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor (R$)</label>
                            <input type="number" step="0.01" class="form-control-modern" name="valor" id="edit_valor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data</label>
                            <input type="date" class="form-control-modern" name="data" id="edit_data" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--primary-purple);">
                        <button type="button" class="btn btn-outline-purple btn-modern" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="edit_transaction" class="btn btn-purple btn-modern">
                            <i class="bi bi-check-lg me-2"></i>Atualizar Transação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Nova Meta de Orçamento -->
    <div class="modal fade" id="addBudgetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--dark-bg); color: white; border: 1px solid var(--primary-purple);">
                <div class="modal-header" style="border-bottom: 1px solid var(--primary-purple);">
                    <h5 class="modal-title">Nova Meta de Orçamento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="dashboard.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Categoria</label>
                            <select class="form-control-modern" name="categoria" required>
                                <option value="" class="opc">Selecione a categoria</option>
                                <option value="Alimentação" class="opc">🍽️ Alimentação</option>
                                <option value="Transporte" class="opc">🚗 Transporte</option>
                                <option value="Moradia" class="opc">🏠 Moradia</option>
                                <option value="Lazer" class="opc">🎮 Lazer</option>
                                <option value="Saúde" class="opc">❤️ Saúde</option>
                                <option value="Educação" class="opc">📚 Educação</option>
                                <option value="Outros" class="opc">📦 Outros</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor Meta (R$)</label>
                            <input type="number" step="0.01" class="form-control-modern" name="meta_valor" placeholder="0,00" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mês</label>
                            <input type="month" class="form-control-modern" name="mes" value="<?= date('Y-m') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--primary-purple);">
                        <button type="button" class="btn btn-outline-purple btn-modern" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_budget" class="btn btn-purple btn-modern">
                            <i class="bi bi-check-lg me-2"></i>Salvar Meta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Orçamento -->
    <div class="modal fade" id="editBudgetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--dark-bg); color: white; border: 1px solid var(--primary-purple);">
                <div class="modal-header" style="border-bottom: 1px solid var(--primary-purple);">
                    <h5 class="modal-title">Editar Meta de Orçamento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="budget_id" id="edit_budget_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Categoria</label>
                            <select class="form-control-modern" name="categoria" id="edit_budget_categoria" required>
                                <option value="">Selecione a categoria</option>
                                <option value="Alimentação">🍽️ Alimentação</option>
                                <option value="Transporte">🚗 Transporte</option>
                                <option value="Moradia">🏠 Moradia</option>
                                <option value="Lazer">🎮 Lazer</option>
                                <option value="Saúde">❤️ Saúde</option>
                                <option value="Educação">📚 Educação</option>
                                <option value="Outros">📦 Outros</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor Meta (R$)</label>
                            <input type="number" step="0.01" class="form-control-modern" name="meta_valor" id="edit_budget_meta_valor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mês</label>
                            <input type="month" class="form-control-modern" name="mes" id="edit_budget_mes" value="<?= date('Y-m') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--primary-purple);">
                        <button type="button" class="btn btn-outline-purple btn-modern" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="edit_budget" class="btn btn-purple btn-modern">
                            <i class="bi bi-check-lg me-2"></i>Atualizar Meta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let monthlyChart;

            // Definir categorias separadas por tipo
            const categoriasReceita = [
                { value: "Salário", text: "💼 Salário" },
                { value: "13º Salário", text: "🎁 13º Salário" },
                { value: "Aluguel de Imóveis", text: "🏡 Aluguel de Imóveis" },
                { value: "Freelance", text: "💻 Freelance" },
                { value: "Investimentos", text: "📈 Investimentos" },
                { value: "Outras Receitas", text: "💰 Outras Receitas" }
            ];

            const categoriasDespesa = [
                { value: "Alimentação", text: "🍽️ Alimentação" },
                { value: "Transporte", text: "🚗 Transporte" },
                { value: "Moradia", text: "🏠 Moradia" },
                { value: "Lazer", text: "🎮 Lazer" },
                { value: "Saúde", text: "❤️ Saúde" },
                { value: "Educação", text: "📚 Educação" },
                { value: "Contas", text: "🧾 Contas (água, luz, internet)" },
                { value: "Compras", text: "🛍️ Compras" },
                { value: "Outras Despesas", text: "📦 Outras Despesas" }
            ];

            // Função para atualizar as opções de categoria
            function atualizarCategorias(modalId = 'addTransactionModal') {
                const modal = document.getElementById(modalId);
                const tipoSelecionado = modal.querySelector('input[name="tipo"]:checked');
                const selectCategoria = modal.querySelector('select[name="categoria"]');

                if (!selectCategoria) return;

                if (!tipoSelecionado) {
                    selectCategoria.innerHTML = '<option value="">Selecione o tipo primeiro</option>';
                    return;
                }

                // Limpar opções existentes
                selectCategoria.innerHTML = '<option value="">Selecione a categoria</option>';

                // Adicionar opções baseadas no tipo selecionado
                const categorias = tipoSelecionado.value === 'receita' ? categoriasReceita : categoriasDespesa;

                categorias.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.value;
                    option.textContent = cat.text;
                    option.className = 'opcoes';
                    selectCategoria.appendChild(option);
                });
            }

            // Adicionar event listeners aos radio buttons (modal de adicionar)
            const radiosTipo = document.querySelectorAll('#addTransactionModal input[name="tipo"]');
            radiosTipo.forEach(radio => {
                radio.addEventListener('change', () => atualizarCategorias('addTransactionModal'));
            });

            // Adicionar event listeners aos radio buttons (modal de editar)
            const radiosTipoEdit = document.querySelectorAll('#editTransactionModal input[name="tipo"]');
            radiosTipoEdit.forEach(radio => {
                radio.addEventListener('change', () => atualizarCategorias('editTransactionModal'));
            });

            // Função para abrir modal com tipo pré-selecionado
            window.openTransactionModal = function(tipo) {
                const modal = new bootstrap.Modal(document.getElementById('addTransactionModal'));
                modal.show();
                setTimeout(() => {
                    document.getElementById(tipo).checked = true;
                    atualizarCategorias('addTransactionModal');
                }, 200);
            };

            // Função para editar transação
            window.editTransaction = function(transaction) {
                document.getElementById('edit_transaction_id').value = transaction.id;
                
                // Selecionar tipo
                if (transaction.tipo === 'receita') {
                    document.getElementById('edit_receita').checked = true;
                } else {
                    document.getElementById('edit_despesa').checked = true;
                }
                
                // Atualizar categorias e selecionar categoria
                atualizarCategorias('editTransactionModal');
                setTimeout(() => {
                    document.getElementById('edit_categoria').value = transaction.categoria;
                }, 100);
                
                document.getElementById('edit_descricao').value = transaction.descricao;
                document.getElementById('edit_valor').value = transaction.valor;
                document.getElementById('edit_data').value = transaction.data_transacao;
                
                const modal = new bootstrap.Modal(document.getElementById('editTransactionModal'));
                modal.show();
            };

            // Função para editar orçamento
            window.editBudget = function(id, categoria, metaValor) {
                document.getElementById('edit_budget_id').value = id;
                document.getElementById('edit_budget_categoria').value = categoria;
                document.getElementById('edit_budget_meta_valor').value = metaValor;
                document.getElementById('edit_budget_mes').value = '<?= date('Y-m') ?>';
                
                const modal = new bootstrap.Modal(document.getElementById('editBudgetModal'));
                modal.show();
            };

            // Dados para o gráfico
            const chartData = {
                labels: ['Receitas', 'Despesas'],
                datasets: [{
                    data: [<?= $totals['total_receitas'] ?>, <?= $totals['total_despesas'] ?>],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            };

            // Inicializar gráfico
            function initChart() {
                const ctx = document.getElementById('monthlyChart').getContext('2d');
                monthlyChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: 'white',
                                    padding: 20,
                                    font: {
                                        size: 14
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Alterar tipo do gráfico
            window.changeChartType = function(type) {
                monthlyChart.destroy();
                const ctx = document.getElementById('monthlyChart').getContext('2d');
                monthlyChart = new Chart(ctx, {
                    type: type,
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: 'white',
                                    padding: 20,
                                    font: {
                                        size: 14
                                    }
                                }
                            }
                        },
                        scales: type === 'bar' ? {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: 'white'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: 'white'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        } : {}
                    }
                });
            };

            // Excluir transação
            window.deleteTransaction = function(id) {
                if (confirm('Tem certeza que deseja excluir esta transação?')) {
                    window.location.href = `dashboard.php?delete_transaction=${id}`;
                }
            };

            // Inicializar gráfico
            initChart();

            // Animações dos cards
            const cards = document.querySelectorAll('.stat-card-modern, .card-modern');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';

                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>

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

                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Plataforma</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="dashboard.php" class="footer-link">Dashboard</a></li>
                        <li class="mb-2"><a href="investments.php" class="footer-link">Investimentos</a></li>
                        <li class="mb-2"><a href="conversabot.php" class="footer-link">Assistente IA</a></li>
                        <li class="mb-2"><a href="education.php" class="footer-link">Academia</a></li>
                    </ul>
                </div>

                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Recursos</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="quiz.php" class="footer-link">Quiz Financeiro</a></li>
                        <li class="mb-2"><a href="exercicios.php" class="footer-link">Exercícios</a></li>
                        <li class="mb-2"><a href="plans.php" class="footer-link">Planos</a></li>
                        <li class="mb-2"><a href="plans1.php" class="footer-link">Planos Premium</a></li>
                    </ul>
                </div>

                <div class="col-lg-2">
                    <h6 class="fw-bold mb-3">Suporte</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="support.php" class="footer-link">Central de Ajuda</a></li>
                        <li class="mb-2"><a href="support1.php" class="footer-link">Contato</a></li>
                        <li class="mb-2"><a href="about.php" class="footer-link">Sobre Nós</a></li>
                        <li class="mb-2"><a href="about1.php" class="footer-link">Nossa História</a></li>
                    </ul>
                </div>

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