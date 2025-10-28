<?php
// investments_b3.php - Página de Ativos da B3 integrada ao sistema FinançasJá

session_start();
require_once 'config/database.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Aqui você pode adicionar lógica PHP específica para esta página, se necessário
// Por exemplo, carregar dados do usuário ou verificar permissões

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativos da B3 - FinançasJá</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet"> <!-- Assumindo que existe um style.css como no documento original -->
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff; /* Branco */
            color: #000000; /* Preto */
        }
        .navbar-modern {
            background-color: #820AD1; /* Roxo Nubank */
        }
        .navbar-modern .navbar-brand,
        .navbar-modern .nav-link {
            color: #ffffff !important;
        }
        .hero-modern {
            background: linear-gradient(135deg, #820AD1 0%, #6a1b9a 100%);
            color: white;
            padding: 60px 0;
        }
        .hero-content h1 {
            font-weight: 700;
            font-size: 2.5em;
        }
        .btn-purple {
            background-color: #820AD1;
            border-color: #820AD1;
            color: white;
        }
        .btn-purple:hover {
            background-color: #6a1b9a;
            border-color: #6a1b9a;
        }
        .btn-outline-purple {
            border-color: #820AD1;
            color: #820AD1;
        }
        .btn-outline-purple:hover {
            background-color: #820AD1;
            color: white;
        }
        .card-modern {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background-color: white;
        }
        .stat-card-modern {
            border-radius: 15px;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-card-modern.success {
            border-left: 5px solid #28a745;
        }
        .stat-card-modern.info {
            border-left: 5px solid #17a2b8;
        }
        .stat-card-modern.warning {
            border-left: 5px solid #ffc107;
        }
        main {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #820AD1; /* Roxo Nubank */
            color: #ffffff; /* Branco */
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            border-bottom: 1px solid #dddddd;
            padding: 12px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        img {
            width: 30px;
            height: 30px;
            border-radius: 4px;
        }
        .footer-modern {
            background-color: #820AD1;
            color: white;
            padding: 40px 0;
            margin-top: 40px;
        }
        .footer-modern a {
            color: white;
            text-decoration: none;
        }
        .footer-modern a:hover {
            text-decoration: underline;
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
        /* Responsividade adicional */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2em;
            }
            table {
                font-size: 0.9em;
            }
            .table-responsive {
                overflow-x: auto;
            }
        }
        .filter-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar similar à página fornecida -->
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

    <!-- Hero Section similar -->
    <section class="hero-modern">
        <div class="container">
            <div class="hero-content">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-5 fw-bold mb-3 fade-in-up">Ativos Brasileiros da B3</h1>
                        <p class="lead mb-4 fade-in-up" style="animation-delay: 0.2s;">Acompanhe ações, fundos (FIIs e ETFs) e BDRs em tempo real</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <button class="btn btn-purple btn-modern" onclick="refreshAtivos()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Atualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container mt-4 mb-5">
        <!-- Filtros de busca e tipo -->
        <div class="filter-group">
            <input type="text" id="search-input" class="form-control" placeholder="Buscar por ticker ou nome">
            <select id="type-filter" class="form-select">
                <option value="">Todos os tipos</option>
                <option value="stock">Ações</option>
                <option value="fund">Fundos</option>
                <option value="bdr">BDRs</option>
            </select>
            <button class="btn btn-outline-purple btn-modern" onclick="carregarAtivos()">
                <i class="bi bi-search me-2"></i>Buscar
            </button>
        </div>

        <!-- Carteira similar, com card-modern -->
        <div class="card-modern mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0"><i class="bi bi-wallet2 text-purple me-2"></i>Lista de Ativos</h4>
                    <button class="btn btn-outline-purple btn-modern" onclick="refreshAtivos()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Atualizar Lista
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="ativos-table">
                        <thead class="table-dark">
                            <tr>
                                <th>Ícone</th>
                                <th>Ticker</th>
                                <th>Nome</th>
                                <th>Setor</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dados serão inseridos aqui via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Aviso similar -->
        <div class="row g-4">
            <div class="col-lg-12">
                <div class="card-modern" style="background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%); color: white;">
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                        <i class="bi bi-exclamation-triangle-fill fs-1 mb-3"></i>
                        <h5 class="fw-bold mb-3">AVISO IMPORTANTE</h5>
                        <p class="mb-0">
                            As informações são aproximadas e para fins informativos. Use fontes oficiais para operações reais.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer completo, igual ao documento original -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function carregarAtivos() {
            try {
                const tableBody = document.querySelector('#ativos-table tbody');
                tableBody.innerHTML = ''; // Limpa a tabela

                const search = document.getElementById('search-input').value.trim();
                const type = document.getElementById('type-filter').value;

                let page = 1;
                let hasNextPage = true;
                let baseUrl = 'https://brapi.dev/api/quote/list?';
                if (search) baseUrl += `search=${encodeURIComponent(search)}&`;
                if (type) baseUrl += `type=${type}&`;
                baseUrl += 'limit=500';

                while (hasNextPage) {
                    const response = await fetch(`${baseUrl}&page=${page}`);
                    const data = await response.json();

                    data.stocks.forEach(ativo => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td><img src="${ativo.logo}" alt="Ícone de ${ativo.stock}" onerror="this.src='https://icons.brapi.dev/icons/default.svg'"></td>
                            <td>${ativo.stock}</td>
                            <td>${ativo.name}</td>
                            <td>${ativo.sector || 'N/A'}</td>
                            <td>${ativo.type}</td>
                        `;
                        tableBody.appendChild(row);
                    });

                    hasNextPage = data.hasNextPage;
                    page++;
                }
            } catch (error) {
                console.error('Erro ao carregar dados:', error);
            }
        }

        function refreshAtivos() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-modern me-2"></span>Atualizando...';
            carregarAtivos().finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Atualizar Lista';
            });
        }

        document.addEventListener('DOMContentLoaded', carregarAtivos);

        // Permitir busca ao pressionar Enter
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                carregarAtivos();
            }
        });
    </script>
</body>
</html>