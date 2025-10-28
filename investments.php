<?php
session_start();
require_once 'config/database.php';

requireLogin();

// Nova chave da API BRAPI
define('BRAPI_TOKEN', 'm17pMcSDMTBk7FqjrGvyAW');
define('ALPHA_VANTAGE_KEY', 'IISMG43OG2HR2DM2');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Deletar investimento
if (isset($_GET['delete_investment'])) {
    $investmentId = (int)$_GET['delete_investment'];
    $stmt = $pdo->prepare("SELECT * FROM investments WHERE id = ? AND user_id = ?");
    $stmt->execute([$investmentId, $user_id]);
    $investment = $stmt->fetch();

    if ($investment) {
        $stmt = $pdo->prepare("DELETE FROM investments WHERE id = ?");
        $stmt->execute([$investmentId]);
    }
    header('Location: investments.php');
    exit();
}

// Editar investimento
if ($_POST && isset($_POST['edit_investment'])) {
    $investmentId = (int)$_POST['investment_id'];
    $tipo = $_POST['tipo_investimento'];
    $nome = strtoupper(trim($_POST['nome_ativo']));
    $quantidade = (float)$_POST['quantidade'];
    $preco = (float)$_POST['preco_compra'];
    $data = $_POST['data_compra'];

    $stmt = $pdo->prepare("UPDATE investments SET tipo_investimento = ?, nome_ativo = ?, quantidade = ?, preco_compra = ?, data_compra = ? WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$tipo, $nome, $quantidade, $preco, $data, $investmentId, $user_id])) {
        header('Location: investments.php');
        exit();
    }
}

// Adicionar investimento
if ($_POST && isset($_POST['add_investment'])) {
    $tipo = $_POST['tipo_investimento'];
    $nome = strtoupper(trim($_POST['nome_ativo']));
    $quantidade = (float)$_POST['quantidade'];
    $preco = (float)$_POST['preco_compra'];
    $data = $_POST['data_compra'];

    $stmt = $pdo->prepare("INSERT INTO investments (user_id, tipo_investimento, nome_ativo, quantidade, preco_compra, data_compra) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $tipo, $nome, $quantidade, $preco, $data])) {
        header('Location: investments.php');
        exit();
    }
}

// Buscar investimentos
$stmt = $pdo->prepare("SELECT * FROM investments WHERE user_id = ? ORDER BY data_compra DESC");
$stmt->execute([$user_id]);
$investments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_invested = 0;
foreach ($investments as $investment) {
    $total_invested += $investment['quantidade'] * $investment['preco_compra'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investimentos - FinançasJá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Cards de cotação - Tamanho compacto */
        .currency-card {
            border-radius: 15px;
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            background-size: cover;
            width: 600px;
            background-position: center;
            min-height: 180px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .currency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .currency-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }

        .currency-card-content {
            position: relative;
            z-index: 2;
        }

        #dollar-card {
            background-image: url('https://upload.wikimedia.org/wikipedia/commons/thumb/a/a4/Flag_of_the_United_States.svg/1200px-Flag_of_the_United_States.svg.png');
        }

        #euro-card {
            background-image: url('https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/Flag_of_Europe.svg/1280px-Flag_of_Europe.svg.png');
        }

        #bitcoin-card {
            background: linear-gradient(135deg, #f7931a 0%, #ff6b00 100%);
        }

        .currency-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .currency-value {
            font-size: 1.8rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .currency-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .update-time {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 10px;
        }

        .spinner-modern {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 200px;
            margin-top: 15px;
        }

        .chart-mini {
            max-height: 150px;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .currency-card {
                min-height: 160px;
            }

            .currency-value {
                font-size: 1.5rem;
            }

            .currency-icon {
                font-size: 2.5rem;
            }
        }

        /* Expandable row */
        .expandable-content {
            display: none;
            background: #f8f9fa;
            padding: 20px;
        }

        .expandable-content.show {
            display: table-row;
        }

        .expand-btn {
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .expand-btn.active {
            transform: rotate(180deg);
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
                        <a class="nav-link active" href="investments.php">
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

    <!-- Hero Section -->
    <section class="hero-modern">
        <div class="container">
            <div class="hero-content">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-5 fw-bold mb-3 fade-in-up">Carteira de Investimentos</h1>
                        <p class="lead mb-4 fade-in-up" style="animation-delay: 0.2s;">Acompanhe seus ativos e cotações em tempo real</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <button class="btn btn-purple btn-modern" data-bs-toggle="modal" data-bs-target="#addInvestmentModal">
                            <i class="bi bi-plus-lg me-2"></i>Novo Investimento
                        </button>
                        <a href="ativos.php" class="btn btn-outline-purple btn-modern mt-3">
                            <i class="bi bi-list-ul me-2"></i>Ver Lista de Ativos da B3
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container mt-4 mb-5">
        <!-- Cotações de Moedas -->
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">Cotações em Tempo Real</h3>
                <button class="btn btn-outline-purple btn-modern" onclick="refreshCurrencies()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Atualizar
                </button>
            </div>

            <div class="row g-4 align-items-stretch">
                <!-- DÓLAR -->
                <div class="col-md-4 d-flex">
                    <div class="currency-card" id="dollar-card">
                        <div class="currency-card-content">
                            <div class="currency-icon">💵</div>
                            <h5 class="fw-bold mb-3">Dólar (USD)</h5>
                            <div class="currency-value-wrapper">
                                <div class="currency-label">Compra</div>
                                <div class="currency-value">R$ <span id="dollar_compra"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div class="currency-value-wrapper">
                                <div class="currency-label">Venda</div>
                                <div class="currency-value">R$ <span id="dollar_venda"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div class="update-time">Atualizado: <span id="dollar_update">-</span></div>
                        </div>
                    </div>
                </div>

                <!-- EURO -->
                <div class="col-md-4 d-flex">
                    <div class="currency-card" id="euro-card">
                        <div class="currency-card-content">
                            <div class="currency-icon">💶</div>
                            <h5 class="fw-bold mb-3">Euro (EUR)</h5>
                            <div class="currency-value-wrapper">
                                <div class="currency-label">Compra</div>
                                <div class="currency-value">R$ <span id="euro_compra"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div class="currency-value-wrapper">
                                <div class="currency-label">Venda</div>
                                <div class="currency-value">R$ <span id="euro_venda"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div class="update-time">Atualizado: <span id="euro_update">-</span></div>
                        </div>
                    </div>
                </div>

                <!-- BITCOIN -->
                <div class="col-md-4 d-flex">
                    <div class="currency-card" id="bitcoin-card">
                        <div class="currency-card-content">
                            <div class="currency-icon">₿</div>
                            <h5 class="fw-bold mb-3">Bitcoin (BTC)</h5>
                            <div class="currency-value-wrapper">
                                <div class="currency-label">Preço Atual</div>
                                <div class="currency-value">R$ <span id="bitcoin_preco"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div class="currency-value-wrapper">
                                <div class="currency-label">&nbsp;</div>
                                <div class="currency-value">&nbsp;</div>
                            </div>
                            <div class="update-time">Atualizado: <span id="bitcoin_update">-</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card-modern success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1" style="color: #6c757d;">Total Investido</h6>
                            <h3 class="text-success mb-0">R$ <?= number_format($total_invested, 2, ',', '.') ?></h3>
                        </div>
                        <div class="text-success" style="font-size: 2.5rem;">
                            <i class="bi bi-piggy-bank-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card-modern info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1" style="color: #6c757d;">Valor Atual</h6>
                            <h3 class="text-info mb-0" id="currentValue"><span class="spinner-modern"></span></h3>
                        </div>
                        <div class="text-info" style="font-size: 2.5rem;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card-modern">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1" style="color: #6c757d;">Rentabilidade</h6>
                            <h3 class="text-purple mb-0" id="profitability"><span class="spinner-modern"></span></h3>
                        </div>
                        <div class="text-purple" style="font-size: 2.5rem;">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card-modern warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1" style="color: #6c757d;">Ativos</h6>
                            <h3 class="text-warning mb-0"><?= count($investments) ?></h3>
                        </div>
                        <div class="text-warning" style="font-size: 2.5rem;">
                            <i class="bi bi-collection-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carteira -->
        <div class="card-modern mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0"><i class="bi bi-wallet2 text-purple me-2"></i>Minha Carteira</h4>
                    <button class="btn btn-outline-purple btn-modern" onclick="refreshPrices()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Atualizar Cotações
                    </button>
                </div>

                <?php if (empty($investments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-graph-up text-purple" style="font-size: 5rem; opacity: 0.3;"></i>
                        <h4 style="color: #6c757d;" class="mt-4">Nenhum investimento encontrado</h4>
                        <p style="color: #6c757d;" class="mb-4">Comece adicionando seu primeiro investimento</p>
                        <button class="btn btn-purple btn-modern" data-bs-toggle="modal" data-bs-target="#addInvestmentModal">
                            <i class="bi bi-plus-lg me-2"></i>Adicionar Investimento
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th></th>
                                    <th>Ativo</th>
                                    <th>Tipo</th>
                                    <th>Quantidade</th>
                                    <th>Preço Médio</th>
                                    <th>Investido</th>
                                    <th>Cotação Atual</th>
                                    <th>Valor Atual</th>
                                    <th>Resultado</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($investments as $inv): ?>
                                    <tr data-investment-id="<?= $inv['id'] ?>" data-symbol="<?= htmlspecialchars($inv['nome_ativo']) ?>" data-type="<?= htmlspecialchars($inv['tipo_investimento']) ?>" data-quantity="<?= $inv['quantidade'] ?>" data-buy-price="<?= $inv['preco_compra'] ?>">
                                        <td>
                                            <i class="bi bi-chevron-down expand-btn" onclick="toggleChart(<?= $inv['id'] ?>, '<?= htmlspecialchars($inv['nome_ativo']) ?>', '<?= htmlspecialchars($inv['tipo_investimento']) ?>')"></i>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($inv['nome_ativo']) ?></strong>
                                            <br><small style="color: #6c757d;"><?= date('d/m/Y', strtotime($inv['data_compra'])) ?></small>
                                        </td>
                                        <td><span class="badge badge-purple"><?= htmlspecialchars($inv['tipo_investimento']) ?></span></td>
                                        <td><?= number_format($inv['quantidade'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($inv['preco_compra'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($inv['quantidade'] * $inv['preco_compra'], 2, ',', '.') ?></td>
                                        <td class="current-price"><span class="spinner-modern" style="border-color: #6c757d; border-top-color: #667eea;"></span></td>
                                        <td class="current-value">-</td>
                                        <td class="investment-result">-</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($inv)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="?delete_investment=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este investimento?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr class="expandable-content" id="chart-row-<?= $inv['id'] ?>">
                                        <td colspan="10">
                                            <div class="chart-container">
                                                <canvas id="chart-<?= $inv['id'] ?>"></canvas>
                                            </div>
                                            <div class="text-center mt-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    <strong>Gráfico dos últimos 7 dias</strong> - 
                                                    Preços de fechamento diário (~18h, horário de Brasília)
                                                </small>
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

        <!-- Como Usar e Avisos -->
        <div class="row g-4">
            <div class="col-lg-9">
                <div class="card-modern h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="bi bi-info-circle-fill text-purple me-2"></i>Como Usar</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-purple bg-opacity-10 rounded-circle p-3">
                                            <i class="bi bi-1-circle text-purple fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold">Adicione um Investimento</h6>
                                        <p style="color: #ffffffff;" class="mb-0">Clique em "Novo Investimento" e preencha as informações do ativo.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-purple bg-opacity-10 rounded-circle p-3">
                                            <i class="bi bi-2-circle text-purple fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold">Visualize Gráficos</h6>
                                        <p style="color: #ffffffff;" class="mb-0">Clique na seta para ver o gráfico de 7 dias do ativo.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-purple bg-opacity-10 rounded-circle p-3">
                                            <i class="bi bi-3-circle text-purple fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold">Edite Investimentos</h6>
                                        <p style="color: #fffdfdff;" class="mb-0">Use o botão de lápis para editar os dados do investimento.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="bg-purple bg-opacity-10 rounded-circle p-3">
                                            <i class="bi bi-4-circle text-purple fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold">Acompanhe</h6>
                                        <p style="color: #ffffffff;" class="mb-0">O sistema atualiza automaticamente as cotações.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="card-modern h-100" style="background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%); color: white;">
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                        <i class="bi bi-exclamation-triangle-fill fs-1 mb-3"></i>
                        <h5 class="fw-bold mb-3">AVISO IMPORTANTE</h5>
                        <p class="mb-0">
                            As cotações são aproximadas e para fins informativos. Use fontes oficiais para operações reais.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar -->
    <div class="modal fade" id="addInvestmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle text-purple me-2"></i>Novo Investimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Investimento</label>
                            <select class="form-select" name="tipo_investimento" required>
                                <option value="">Selecione o tipo</option>
                                <option value="Ação">Ação</option>
                                <option value="FII">Fundo Imobiliário (FII)</option>
                                <option value="Cripto">Criptomoeda</option>
                                <option value="Renda Fixa">Renda Fixa</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome do Ativo</label>
                            <input type="text" class="form-control" name="nome_ativo" placeholder="Ex: PETR4, MXRF11, BTC" required>
                            <small style="color: #6c757d;">Digite o código da ação, FII ou criptomoeda</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Quantidade</label>
                            <input type="number" step="0.01" class="form-control" name="quantidade" placeholder="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Preço de Compra (R$)</label>
                            <input type="text" class="form-control" id="preco_compra" placeholder="25,50" required>
                            <small style="color: #6c757d;">Digite o valor (exemplo: 25,50)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Data da Compra</label>
                            <input type="date" class="form-control" name="data_compra" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_investment" class="btn btn-purple btn-modern">
                            <i class="bi bi-check-lg me-2"></i>Adicionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div class="modal fade" id="editInvestmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil text-purple me-2"></i>Editar Investimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="investment_id" id="edit_investment_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Investimento</label>
                            <select class="form-select" name="tipo_investimento" id="edit_tipo_investimento" required>
                                <option value="">Selecione o tipo</option>
                                <option value="Ação">Ação</option>
                                <option value="FII">Fundo Imobiliário (FII)</option>
                                <option value="Cripto">Criptomoeda</option>
                                <option value="Renda Fixa">Renda Fixa</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nome do Ativo</label>
                            <input type="text" class="form-control" name="nome_ativo" id="edit_nome_ativo" placeholder="Ex: PETR4, MXRF11, BTC" required>
                            <small style="color: #6c757d;">Digite o código da ação, FII ou criptomoeda</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Quantidade</label>
                            <input type="number" step="0.01" class="form-control" name="quantidade" id="edit_quantidade" placeholder="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Preço de Compra (R$)</label>
                            <input type="text" class="form-control" id="edit_preco_compra" placeholder="25,50" required>
                            <small style="color: #6c757d;">Digite o valor (exemplo: 25,50)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Data da Compra</label>
                            <input type="date" class="form-control" name="data_compra" id="edit_data_compra" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="edit_investment" class="btn btn-purple btn-modern">
                            <i class="bi bi-check-lg me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ========== COTAÇÕES DE MOEDAS (ATUALIZADO) ==========
        const EXCHANGERATE_URL = 'https://open.er-api.com/v6/latest/USD';

        async function getDollarExchangeRates() {
            try {
                const response = await fetch(EXCHANGERATE_URL);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();

                if (data.result === 'success' && data.rates && data.rates.BRL) {
                    const rate = data.rates.BRL;
                    const spread = 0.02;
                    const compra = rate * (1 - spread / 2);
                    const venda = rate * (1 + spread / 2);

                    document.getElementById("dollar_compra").innerText = compra.toFixed(2);
                    document.getElementById("dollar_venda").innerText = venda.toFixed(2);
                } else {
                    throw new Error("Dados inválidos");
                }
            } catch (error) {
                console.error("Erro ao obter Dólar:", error);
                document.getElementById("dollar_compra").innerText = "N/A";
                document.getElementById("dollar_venda").innerText = "N/A";
            } finally {
                document.getElementById("dollar_update").innerText = new Date().toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }

        async function getEuroExchangeRates() {
            try {
                const response = await fetch(EXCHANGERATE_URL);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();

                if (data.result === 'success' && data.rates && data.rates.EUR && data.rates.BRL) {
                    const usdToBrl = data.rates.BRL;
                    const usdToEur = data.rates.EUR;
                    const eurToBrl = usdToBrl / usdToEur;
                    const spread = 0.02;
                    const compra = eurToBrl * (1 - spread / 2);
                    const venda = eurToBrl * (1 + spread / 2);

                    document.getElementById("euro_compra").innerText = compra.toFixed(2);
                    document.getElementById("euro_venda").innerText = venda.toFixed(2);
                } else {
                    throw new Error("Dados inválidos");
                }
            } catch (error) {
                console.error("Erro ao obter Euro:", error);
                document.getElementById("euro_compra").innerText = "N/A";
                document.getElementById("euro_venda").innerText = "N/A";
            } finally {
                document.getElementById("euro_update").innerText = new Date().toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }

        async function getBitcoinPrice() {
            const url = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=brl";
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();

                if (data.bitcoin?.brl) {
                    document.getElementById("bitcoin_preco").innerText = data.bitcoin.brl.toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    document.getElementById("bitcoin_preco").innerText = "N/A";
                }
            } catch (error) {
                console.error("Erro ao obter Bitcoin:", error);
                document.getElementById("bitcoin_preco").innerText = "Erro";
            } finally {
                document.getElementById("bitcoin_update").innerText = new Date().toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }

        function refreshCurrencies() {
            getDollarExchangeRates();
            getEuroExchangeRates();
            getBitcoinPrice();
        }

        // ========== SISTEMA DE INVESTIMENTOS ==========
        const BRAPI_TOKEN = '<?= BRAPI_TOKEN ?>';
        const BRAPI_URL = 'https://brapi.dev/api/quote';
        const totalInvested = <?= (float)$total_invested ?>;
        let totalCurrentValue = 0;
        let priceCache = {};
        let chartInstances = {};

        const CRYPTO_MAP = {
            'BTC': 'bitcoin',
            'ETH': 'ethereum',
            'USDT': 'tether',
            'BNB': 'binancecoin',
            'XRP': 'ripple',
            'ADA': 'cardano',
            'DOGE': 'dogecoin',
            'SOL': 'solana',
            'DOT': 'polkadot',
            'MATIC': 'matic-network',
            'LTC': 'litecoin',
            'SHIB': 'shiba-inu',
            'UNI': 'uniswap',
            'LINK': 'chainlink',
            'AVAX': 'avalanche-2'
        };

        // ========== FUNÇÕES DE EDIÇÃO ==========
        function openEditModal(investment) {
            document.getElementById('edit_investment_id').value = investment.id;
            document.getElementById('edit_tipo_investimento').value = investment.tipo_investimento;
            document.getElementById('edit_nome_ativo').value = investment.nome_ativo;
            document.getElementById('edit_quantidade').value = investment.quantidade;
            
            // Formatar preço para exibição
            const precoFormatado = parseFloat(investment.preco_compra).toFixed(2).replace('.', ',');
            document.getElementById('edit_preco_compra').value = precoFormatado;
            
            document.getElementById('edit_data_compra').value = investment.data_compra;
            
            const modal = new bootstrap.Modal(document.getElementById('editInvestmentModal'));
            modal.show();
        }

        // ========== FUNÇÕES DE GRÁFICO (USA YAHOO FINANCE) ==========
        async function toggleChart(investmentId, symbol, type) {
            const chartRow = document.getElementById(`chart-row-${investmentId}`);
            const expandBtn = event.target;
            
            if (chartRow.classList.contains('show')) {
                chartRow.classList.remove('show');
                expandBtn.classList.remove('active');
                return;
            }
            
            chartRow.classList.add('show');
            expandBtn.classList.add('active');
            
            // Se o gráfico já existe, não precisa recarregar
            if (chartInstances[investmentId]) {
                return;
            }
            
            // Buscar dados históricos
            await loadChartData(investmentId, symbol, type);
        }

        async function loadChartData(investmentId, symbol, type) {
            const canvas = document.getElementById(`chart-${investmentId}`);
            const ctx = canvas.getContext('2d');
            
            // Adiciona mensagem de carregamento
            ctx.font = '14px Arial';
            ctx.fillStyle = '#667eea';
            ctx.textAlign = 'center';
            ctx.fillText('Carregando dados...', canvas.width / 2, canvas.height / 2);
            
            try {
                let chartData = { labels: [], data: [] };
                
                console.log(`Carregando dados para: ${symbol} (${type})`);
                
                if (type === 'Ação' || type === 'FII') {
                    // USA YAHOO FINANCE PARA GRÁFICOS
                    chartData = await fetchStockHistoricalDataYahoo(symbol);
                } else if (type === 'Cripto') {
                    chartData = await fetchCryptoHistoricalData(symbol);
                } else {
                    // Renda Fixa não tem variação
                    chartData = generateFlatData();
                }
                
                console.log(`Dados recebidos para ${symbol}:`, chartData);
                
                // Verifica se tem dados válidos
                if (!chartData.labels || chartData.labels.length === 0 || !chartData.data || chartData.data.length === 0) {
                    throw new Error('Dados vazios ou inválidos');
                }
                
                if (chartInstances[investmentId]) {
                    chartInstances[investmentId].destroy();
                }
                
                chartInstances[investmentId] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: `${symbol} - Preço de Fechamento`,
                            data: chartData.data,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            title: {
                                display: true,
                                text: 'Últimos 7 dias - Preços de Fechamento',
                                font: {
                                    size: 12
                                },
                                color: '#6c757d'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        return 'Fechamento: R$ ' + context.parsed.y.toFixed(2);
                                    },
                                    footer: function(tooltipItems) {
                                        return 'Horário: ~18h (fechamento B3)';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) {
                                        return 'R$ ' + value.toFixed(2);
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Data',
                                    color: '#6c757d'
                                }
                            }
                        }
                    }
                });
                
                console.log(`✓ Gráfico criado com sucesso para ${symbol}`);
            } catch (error) {
                console.error(`Erro ao carregar gráfico de ${symbol}:`, error);
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.font = '14px Arial';
                ctx.fillStyle = '#dc3545';
                ctx.textAlign = 'center';
                ctx.fillText(`Erro ao carregar dados de ${symbol}`, canvas.width / 2, canvas.height / 2 - 10);
                ctx.fillText('Dados históricos não disponíveis', canvas.width / 2, canvas.height / 2 + 10);
            }
        }

        async function fetchStockHistoricalDataYahoo(symbol) {
            try {
                // USA YAHOO FINANCE PARA DADOS HISTÓRICOS
                const symbolYahoo = `${symbol}.SA`; // Adiciona .SA para ações brasileiras
                const endDate = Math.floor(Date.now() / 1000);
                const startDate = endDate - (7 * 24 * 60 * 60); // 7 dias atrás
                
                const url = `https://query1.finance.yahoo.com/v8/finance/chart/${symbolYahoo}?period1=${startDate}&period2=${endDate}&interval=1d`;
                
                console.log(`📊 Buscando gráfico de ${symbol} via Yahoo Finance...`);
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`Yahoo Finance retornou status ${response.status}`);
                }
                
                const data = await response.json();
                
                // Extrai dados do Yahoo Finance
                if (data.chart && data.chart.result && data.chart.result[0]) {
                    const result = data.chart.result[0];
                    const timestamps = result.timestamp;
                    const quotes = result.indicators.quote[0];
                    
                    if (timestamps && quotes && quotes.close) {
                        const labels = timestamps.map(ts => {
                            const date = new Date(ts * 1000);
                            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                        });
                        
                        // Remove valores null e usa preço de abertura se close for null
                        const prices = quotes.close.map((price, index) => {
                            return price !== null ? price : (quotes.open[index] || 0);
                        });
                        
                        console.log(`✓ Gráfico obtido com sucesso via Yahoo Finance para ${symbol}`);
                        return { labels, data: prices };
                    }
                }
                
                throw new Error('Formato de dados inválido do Yahoo Finance');
            } catch (error) {
                console.error(`❌ Erro Yahoo Finance para gráfico de ${symbol}:`, error);
                // Fallback - simula dados baseado no preço atual do BRAPI
                try {
                    const currentPrice = await fetchBrazilianStockPrice(symbol);
                    if (currentPrice) {
                        console.log(`⚠ Simulando gráfico para ${symbol} baseado no preço atual`);
                        return generateSimulatedData(currentPrice);
                    }
                } catch (e) {
                    console.error('Falha no fallback');
                }
                return generateFlatData();
            }
        }
        
        function generateSimulatedData(basePrice) {
            const labels = [];
            const data = [];
            const today = new Date();
            
            // Gera 7 dias de dados com pequena variação aleatória
            for (let i = 6; i >= 0; i--) {
                const date = new Date(today);
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
                
                // Variação de -2% a +2% em relação ao preço base
                const variation = (Math.random() - 0.5) * 0.04;
                const price = basePrice * (1 + variation);
                data.push(parseFloat(price.toFixed(2)));
            }
            
            return { labels, data };
        }

        async function fetchCryptoHistoricalData(symbol) {
            try {
                const coinId = CRYPTO_MAP[symbol] || symbol.toLowerCase();
                const url = `https://api.coingecko.com/api/v3/coins/${coinId}/market_chart?vs_currency=brl&days=7`;
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error('Erro ao buscar dados de cripto');
                }
                
                const data = await response.json();
                
                if (data.prices && data.prices.length > 0) {
                    const labels = data.prices.map(item => {
                        const date = new Date(item[0]);
                        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                    });
                    
                    const prices = data.prices.map(item => item[1]);
                    
                    // Pegar apenas 1 ponto por dia
                    const dailyData = [];
                    const dailyLabels = [];
                    for (let i = 0; i < labels.length; i += Math.floor(labels.length / 7)) {
                        dailyLabels.push(labels[i]);
                        dailyData.push(prices[i]);
                    }
                    
                    return { labels: dailyLabels, data: dailyData };
                }
                
                throw new Error('Dados não disponíveis');
            } catch (error) {
                console.error('Erro CoinGecko histórico:', error);
                return generateFlatData();
            }
        }

        function generateFlatData() {
            const labels = [];
            const data = [];
            const today = new Date();
            
            for (let i = 6; i >= 0; i--) {
                const date = new Date(today);
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
                data.push(100); // Valor fixo para renda fixa
            }
            
            return { labels, data };
        }

        // ========== FUNÇÕES DE PREÇO (USA BRAPI) ==========
        async function fetchBrazilianStockPrice(symbol) {
            if (priceCache[symbol]) {
                const cacheAge = Date.now() - priceCache[symbol].timestamp;
                if (cacheAge < 300000) return priceCache[symbol].price;
            }

            try {
                console.log(`💰 Buscando preço atual de ${symbol} via BRAPI...`);
                const response = await fetch(`${BRAPI_URL}/${symbol}?token=${BRAPI_TOKEN}`);
                if (!response.ok) return null;
                const data = await response.json();

                if (data.results && data.results[0]?.regularMarketPrice) {
                    const price = data.results[0].regularMarketPrice;
                    priceCache[symbol] = {
                        price,
                        timestamp: Date.now()
                    };
                    console.log(`✓ Preço atual obtido via BRAPI: R$ ${price.toFixed(2)}`);
                    return price;
                }
            } catch (error) {
                console.error(`❌ Erro BRAPI preço ${symbol}:`, error);
            }
            return null;
        }

        async function fetchCryptoPrice(symbol) {
            const coinId = CRYPTO_MAP[symbol] || symbol.toLowerCase();
            if (priceCache[symbol]) {
                const cacheAge = Date.now() - priceCache[symbol].timestamp;
                if (cacheAge < 300000) return priceCache[symbol].price;
            }

            try {
                const url = `https://api.coingecko.com/api/v3/simple/price?ids=${coinId}&vs_currencies=brl`;
                const response = await fetch(url);
                if (!response.ok) return null;
                const data = await response.json();

                if (data[coinId]?.brl) {
                    const price = data[coinId].brl;
                    priceCache[symbol] = {
                        price,
                        timestamp: Date.now()
                    };
                    return price;
                }
            } catch (error) {
                console.error(`Erro CoinGecko ${symbol}:`, error);
            }
            return null;
        }

        async function fetchPrice(symbol, type) {
            if (type === 'Cripto') return await fetchCryptoPrice(symbol);
            if (type === 'Ação' || type === 'FII') return await fetchBrazilianStockPrice(symbol);
            return null;
        }

        async function updateInvestmentRow(row) {
            const symbol = row.dataset.symbol.toUpperCase().trim();
            const type = row.dataset.type;
            const quantity = parseFloat(row.dataset.quantity);
            const buyPrice = parseFloat(row.dataset.buyPrice);
            const investedAmount = quantity * buyPrice;

            let currentPrice = buyPrice;
            if (type !== 'Renda Fixa') {
                currentPrice = await fetchPrice(symbol, type) || buyPrice;
            }

            const currentValue = quantity * currentPrice;
            const profit = currentValue - investedAmount;
            const profitPercentage = investedAmount > 0 ? (profit / investedAmount) * 100 : 0;

            row.querySelector('.current-price').innerHTML = currentPrice === buyPrice && type !== 'Renda Fixa' ?
                `<span class="text-muted">R$ ${currentPrice.toFixed(2)}</span><br><small class="text-warning"><i class="bi bi-exclamation-circle"></i> Sem cotação</small>` :
                `R$ ${currentPrice.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            row.querySelector('.current-value').textContent = `R$ ${currentValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            const resultClass = profit >= 0 ? 'text-success' : 'text-danger';
            const resultIcon = profit >= 0 ? '↑' : '↓';
            const resultText = profit >= 0 ? 'Alta' : 'Queda';
            row.querySelector('.investment-result').innerHTML = `
                <div class="${resultClass}">
                    <strong>${profit >= 0 ? '+' : ''}R$ ${profit.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                    <br><small>${resultIcon} ${resultText} ${profitPercentage.toFixed(2)}%</small>
                </div>
            `;

            totalCurrentValue += currentValue;
        }

        async function refreshPrices() {
            totalCurrentValue = 0;
            priceCache = {};
            const rows = document.querySelectorAll('tbody tr[data-investment-id]');
            if (rows.length === 0) return;

            for (const row of rows) {
                row.querySelector('.current-price').innerHTML = '<span class="spinner-modern" style="border-color: #6c757d; border-top-color: #667eea;"></span>';
                row.querySelector('.current-value').innerHTML = '<span class="spinner-modern" style="border-color: #6c757d; border-top-color: #667eea;"></span>';
                row.querySelector('.investment-result').innerHTML = '<span class="spinner-modern" style="border-color: #6c757d; border-top-color: #667eea;"></span>';
            }

            document.getElementById('currentValue').innerHTML = '<span class="spinner-modern"></span>';
            document.getElementById('profitability').innerHTML = '<span class="spinner-modern"></span>';

            for (const row of rows) {
                await updateInvestmentRow(row);
                await new Promise(r => setTimeout(r, 100));
            }

            const profit = totalCurrentValue - totalInvested;
            const profitPercentage = totalInvested > 0 ? (profit / totalInvested) * 100 : 0;

            document.getElementById('currentValue').textContent = `R$ ${totalCurrentValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            const profitClass = profit >= 0 ? 'text-success' : 'text-danger';
            const profitIcon = profit >= 0 ? '↑' : '↓';
            const profitText = profit >= 0 ? 'Alta' : 'Queda';
            document.getElementById('profitability').innerHTML = `
                <span class="${profitClass}">
                    ${profitIcon} ${profitText} ${profit >= 0 ? '+' : ''}${profitPercentage.toFixed(2)}%
                </span>
            `;
        }

        // ========== FORMATAÇÃO DO CAMPO DE PREÇO ==========
        function setupPriceInput(inputId) {
            const precoInput = document.getElementById(inputId);
            
            if (precoInput) {
                precoInput.addEventListener('input', function(e) {
                    let value = e.target.value;
                    value = value.replace(/\D/g, '');

                    if (value.length > 0) {
                        value = (parseInt(value) / 100).toFixed(2);
                        value = value.replace('.', ',');
                        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    }

                    e.target.value = value;
                });

                precoInput.closest('form').addEventListener('submit', function(e) {
                    const valorFormatado = precoInput.value;
                    const valorNumerico = valorFormatado.replace(/\./g, '').replace(',', '.');

                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'preco_compra';
                    hiddenInput.value = valorNumerico;
                    this.appendChild(hiddenInput);

                    precoInput.removeAttribute('name');
                });
            }
        }

        // ========== INICIALIZAÇÃO ==========
        document.addEventListener('DOMContentLoaded', function() {
            setupPriceInput('preco_compra');
            setupPriceInput('edit_preco_compra');

            refreshCurrencies();
            setInterval(refreshCurrencies, 60000);

            const rows = document.querySelectorAll('tbody tr[data-investment-id]');
            if (rows.length > 0) {
                refreshPrices();
                setInterval(refreshPrices, 300000);
            }

            const btn = document.querySelector('button[onclick="refreshPrices()"]');
            if (btn) {
                btn.onclick = (e) => {
                    e.preventDefault();
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-modern me-2"></span>Atualizando...';
                    refreshPrices().finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Atualizar Cotações';
                    });
                };
            }
        });
    </script>

    <!-- Footer -->
    <footer class="footer-modern">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand mb-3">FinançasJá</div>
                    <p>Sua plataforma completa para gestão financeira pessoal com inteligência artificial.</p>
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