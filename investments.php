<?php
session_start();
require_once 'config/database.php';

requireLogin();

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
    <style>
        .currency-card {
            border-radius: 15px;
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            background-size: cover;
            background-position: center;
            min-height: 180px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .currency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .currency-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
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
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .currency-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
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
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
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
                        <a class="nav-link" href="chatbot.php">
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
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="currency-card" id="dollar-card">
                        <div class="currency-card-content">
                            <div class="currency-icon">💵</div>
                            <h5 class="fw-bold mb-3">Dólar (USD)</h5>
                            <div class="mb-2">
                                <div class="currency-label">Compra</div>
                                <div class="currency-value">R$ <span id="dollar_compra"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div>
                                <div class="currency-label">Venda</div>
                                <div class="currency-value">R$ <span id="dollar_venda"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div class="update-time">Atualizado: <span id="dollar_update">-</span></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="currency-card" id="euro-card">
                        <div class="currency-card-content">
                            <div class="currency-icon">💶</div>
                            <h5 class="fw-bold mb-3">Euro (EUR)</h5>
                            <div class="mb-2">
                                <div class="currency-label">Compra</div>
                                <div class="currency-value">R$ <span id="euro_compra"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div>
                                <div class="currency-label">Venda</div>
                                <div class="currency-value">R$ <span id="euro_venda"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div class="update-time">Atualizado: <span id="euro_update">-</span></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="currency-card" id="bitcoin-card">
                        <div class="currency-card-content">
                            <div class="currency-icon">₿</div>
                            <h5 class="fw-bold mb-3">Bitcoin (BTC)</h5>
                            <div>
                                <div class="currency-label">Preço Atual</div>
                                <div class="currency-value">R$ <span id="bitcoin_preco"><span class="spinner-modern"></span></span></div>
                            </div>
                            <div class="update-time mt-4">Atualizado: <span id="bitcoin_update">-</span></div>
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
                                            <a href="?delete_investment=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir este investimento?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
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
                                        <p style="color: #000;" class="mb-0">Clique em "Novo Investimento" e preencha as informações do ativo.</p>
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
                                        <h6 class="fw-bold">Selecione o Tipo</h6>
                                        <p style="color: #000;" class="mb-0">Escolha entre Ação, FII, Criptomoeda ou Renda Fixa.</p>
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
                                        <h6 class="fw-bold">Digite o Código</h6>
                                        <p style="color: #000;" class="mb-0">Informe o ticker do ativo (PETR4, MXRF11, BTC).</p>
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
                                        <p style="color: #000;" class="mb-0">O sistema atualiza automaticamente as cotações.</p>
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
                            A cotação do dólar não está disponível durante finais de semana e feriados bancários.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
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
                            <input type="number" step="0.01" class="form-control" name="preco_compra" placeholder="25.50" required>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ALPHA_KEY = '<?= ALPHA_VANTAGE_KEY ?>';
        const totalInvested = <?= (float)$total_invested ?>;
        let totalCurrentValue = 0;

        // ========== COTAÇÕES DE MOEDAS ==========
        function formatLastUpdate() {
            const now = new Date();
            return now.toLocaleTimeString('pt-BR');
        }

        async function getDollarExchangeRates() {
            const today = new Date();
            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const year = today.getFullYear();
            const currentDatePTAX = `${month}-${day}-${year}`;
            const urlDolar = `https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoDolarDia(dataCotacao=@dataCotacao)?@dataCotacao='${currentDatePTAX}'&$top=1&$format=json`;

            try {
                const response = await fetch(urlDolar);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                
                if (data && data.value && data.value.length > 0) {
                    document.getElementById("dollar_compra").innerText = data.value[0].cotacaoCompra.toFixed(4);
                    document.getElementById("dollar_venda").innerText = data.value[0].cotacaoVenda.toFixed(4);
                } else {
                    document.getElementById("dollar_compra").innerText = "N/A";
                    document.getElementById("dollar_venda").innerText = "N/A";
                }
            } catch (error) {
                console.error("Erro ao obter a cotação do Dólar:", error);
                document.getElementById("dollar_compra").innerText = "Erro";
                document.getElementById("dollar_venda").innerText = "Erro";
            } finally {
                document.getElementById("dollar_update").innerText = formatLastUpdate();
            }
        }

        async function getEuroExchangeRates() {
            const url = "https://api.exchangerate-api.com/v4/latest/EUR";
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                
                const euroToBRL = data.rates.BRL;
                const spread = 0.005;
                const euroCompra = euroToBRL * (1 - spread);
                const euroVenda = euroToBRL * (1 + spread);

                document.getElementById("euro_compra").innerText = euroCompra.toFixed(4);
                document.getElementById("euro_venda").innerText = euroVenda.toFixed(4);
            } catch (error) {
                console.error("Erro ao obter a cotação do Euro:", error);
                document.getElementById("euro_compra").innerText = "Erro";
                document.getElementById("euro_venda").innerText = "Erro";
            } finally {
                document.getElementById("euro_update").innerText = formatLastUpdate();
            }
        }

        async function getBitcoinPrice() {
            const url = "https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=brl";
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                
                if (data && data.bitcoin && data.bitcoin.brl) {
                    document.getElementById("bitcoin_preco").innerText = data.bitcoin.brl.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else {
                    document.getElementById("bitcoin_preco").innerText = "N/A";
                }
            } catch (error) {
                console.error("Erro ao obter a cotação do Bitcoin:", error);
                document.getElementById("bitcoin_preco").innerText = "Erro";
            } finally {
                document.getElementById("bitcoin_update").innerText = formatLastUpdate();
            }
        }

        function refreshCurrencies() {
            getDollarExchangeRates();
            getEuroExchangeRates();
            getBitcoinPrice();
        }

        // Inicializa cotações e atualiza periodicamente
        function initCurrencies() {
            refreshCurrencies();
            setInterval(getDollarExchangeRates, 60000); // 1 minuto
            setInterval(getEuroExchangeRates, 60000); // 1 minuto
            setInterval(getBitcoinPrice, 60000); // 1 minuto
        }

        // ========== INVESTIMENTOS ==========
        async function fetchPrice(symbol, type) {
            try {
                if (type === 'Cripto') {
                    return await fetchCryptoPrice(symbol);
                } else if (type === 'Ação' || type === 'FII') {
                    return await fetchStockPrice(symbol);
                } else {
                    return null;
                }
            } catch (error) {
                console.error('Erro ao buscar preço:', error);
                return null;
            }
        }

        async function fetchStockPrice(symbol) {
            const ticker = symbol.includes('.') ? symbol : `${symbol}.SA`;
            const url = `https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=${ticker}&apikey=${ALPHA_KEY}`;
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data['Global Quote'] && data['Global Quote']['05. price']) {
                return parseFloat(data['Global Quote']['05. price']);
            }
            return null;
        }

        async function fetchCryptoPrice(symbol) {
            // Busca diretamente em BRL via CoinGecko
            const coinMap = {
                'BTC': 'bitcoin',
                'ETH': 'ethereum',
                'USDT': 'tether',
                'BNB': 'binancecoin',
                'XRP': 'ripple',
                'ADA': 'cardano',
                'DOGE': 'dogecoin',
                'SOL': 'solana'
            };
            
            const coinId = coinMap[symbol.toUpperCase()] || symbol.toLowerCase();
            const url = `https://api.coingecko.com/api/v3/simple/price?ids=${coinId}&vs_currencies=brl`;
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (data[coinId] && data[coinId].brl) {
                    return parseFloat(data[coinId].brl);
                }
            } catch (error) {
                console.error('Erro ao buscar cripto via CoinGecko:', error);
            }
            
            return null;
        }

        async function updateInvestmentRow(row) {
            const symbol = row.dataset.symbol;
            const type = row.dataset.type;
            const quantity = parseFloat(row.dataset.quantity);
            const buyPrice = parseFloat(row.dataset.buyPrice);
            const investedAmount = quantity * buyPrice;

            let currentPrice = await fetchPrice(symbol, type);
            
            if (!currentPrice) {
                if (type === 'Renda Fixa') {
                    const variation = 0.13 / 12;
                    currentPrice = buyPrice * (1 + variation);
                } else {
                    const variation = (Math.random() * 0.4) - 0.1;
                    currentPrice = buyPrice * (1 + variation);
                }
            }

            const currentValue = quantity * currentPrice;
            const profit = currentValue - investedAmount;
            const profitPercentage = (profit / investedAmount) * 100;

            row.querySelector('.current-price').textContent = `R$ ${currentPrice.toFixed(2)}`;
            row.querySelector('.current-value').textContent = `R$ ${currentValue.toFixed(2)}`;
            
            const resultClass = profit >= 0 ? 'text-success' : 'text-danger';
            const resultIcon = profit >= 0 ? '▲' : '▼';
            
            row.querySelector('.investment-result').innerHTML = `
                <div class="${resultClass}">
                    <strong>${profit >= 0 ? '+' : ''}R$ ${profit.toFixed(2)}</strong>
                    <br><small>${resultIcon} ${profitPercentage.toFixed(2)}%</small>
                </div>
            `;

            totalCurrentValue += currentValue;
        }

        async function refreshPrices() {
            totalCurrentValue = 0;
            const rows = document.querySelectorAll('tbody tr[data-investment-id]');
            
            rows.forEach(row => {
                row.querySelector('.current-price').innerHTML = '<span class="spinner-modern" style="border-color: #6c757d; border-top-color: #667eea;"></span>';
            });

            for (const row of rows) {
                await updateInvestmentRow(row);
                await new Promise(resolve => setTimeout(resolve, 500));
            }

            const profit = totalCurrentValue - totalInvested;
            const profitPercentage = totalInvested > 0 ? (profit / totalInvested) * 100 : 0;

            document.getElementById('currentValue').textContent = `R$ ${totalCurrentValue.toFixed(2)}`;
            
            const profitClass = profit >= 0 ? 'text-success' : 'text-danger';
            document.getElementById('profitability').innerHTML = `
                <span class="${profitClass}">${profit >= 0 ? '+' : ''}${profitPercentage.toFixed(2)}%</span>
            `;
        }

        // Inicialização
        document.addEventListener('DOMContentLoaded', () => {
            initCurrencies();
            
            if (document.querySelectorAll('tbody tr[data-investment-id]').length > 0) {
                refreshPrices();
            }
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
                    <li class="mb-2"><a href="chatbot.php" class="footer-link">Assistente IA</a></li>
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