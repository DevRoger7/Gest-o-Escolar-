<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/CustoMerendaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$custoModel = new CustoMerendaModel();

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'buscar_custos') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['mes'])) $filtros['mes'] = $_GET['mes'];
        if (!empty($_GET['ano'])) $filtros['ano'] = $_GET['ano'];
        
        $custos = $custoModel->listar($filtros);
        $totais = $custoModel->calcularTotal($filtros['escola_id'] ?? null, $filtros['mes'] ?? null, $filtros['ano'] ?? null);
        
        echo json_encode(['success' => true, 'custos' => $custos, 'totais' => $totais]);
        exit;
    }
}

$custosMes = $custoModel->listar(['mes' => date('n'), 'ano' => date('Y')]);
$totaisMes = $custoModel->calcularTotal(null, date('n'), date('Y'));
$totalGeral = array_sum(array_column($totaisMes, 'total_custos'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Financeiros - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.12) 0%, rgba(220, 38, 38, 0.06) 100%);
            border-right: 3px solid #dc2626;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.08) 0%, rgba(220, 38, 38, 0.04) 100%);
            transform: translateX(4px);
        }
        .mobile-menu-overlay { transition: opacity 0.3s ease-in-out; }
        @media (max-width: 1023px) {
            .sidebar-mobile { transform: translateX(-100%); }
            .sidebar-mobile.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_adm.php'; ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Relatórios Financeiros</h1>
                    </div>
                    <div class="w-10"></div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Relatórios Financeiros</h2>
                    <p class="text-gray-600 mt-1">Acompanhe os custos e despesas do sistema</p>
                </div>
                
                <!-- Cards de Resumo -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-teal-100 rounded-xl">
                                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-1">R$ <?= number_format($totalGeral, 2, ',', '.') ?></h3>
                        <p class="text-gray-600 text-sm">Total do Mês</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-blue-100 rounded-xl">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-1"><?= count($custosMes) ?></h3>
                        <p class="text-gray-600 text-sm">Registros do Mês</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-green-100 rounded-xl">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-1">R$ <?= number_format($totalGeral / max(count($custosMes), 1), 2, ',', '.') ?></h3>
                        <p class="text-gray-600 text-sm">Média por Registro</p>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="buscarRelatorio()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mês</label>
                            <select id="filtro-mes" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="buscarRelatorio()">
                                <option value="">Todos</option>
                                <?php 
                                $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == date('m') ? 'selected' : '' ?>><?= $meses[$i - 1] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano</label>
                            <select id="filtro-ano" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="buscarRelatorio()">
                                <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                                    <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="buscarRelatorio()" class="w-full bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg font-medium">
                                Gerar Relatório
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Tabela de Custos -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Tipo</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Descrição</th>
                                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Valor Total</th>
                                </tr>
                            </thead>
                            <tbody id="lista-custos">
                                <?php if (empty($custosMes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-gray-600">
                                            Nenhum registro de custo encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($custosMes as $custo): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= date('d/m/Y', strtotime($custo['data'])) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($custo['escola_nome'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800">
                                                    <?= htmlspecialchars($custo['tipo'] ?? 'OUTROS') ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($custo['descricao'] ?? '-') ?></td>
                                            <td class="py-3 px-4 text-right font-medium">
                                                R$ <?= number_format($custo['valor_total'] ?? 0, 2, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmar Saída</h3>
                    <p class="text-sm text-gray-600">Tem certeza que deseja sair do sistema?</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <button onclick="window.closeLogoutModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button onclick="window.logout()" class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors duration-200">
                    Sim, Sair
                </button>
            </div>
        </div>
    </div>
    
    <script>
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };
        
        window.confirmLogout = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        };
        
        window.closeLogoutModal = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        };
        
        window.logout = function() {
            window.location.href = '../auth/logout.php';
        };

        function buscarRelatorio() {
            const escolaId = document.getElementById('filtro-escola').value;
            const mes = document.getElementById('filtro-mes').value;
            const ano = document.getElementById('filtro-ano').value;
            
            let url = '?acao=buscar_custos';
            if (escolaId) url += '&escola_id=' + escolaId;
            if (mes) url += '&mes=' + mes;
            if (ano) url += '&ano=' + ano;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-custos');
                        tbody.innerHTML = '';
                        
                        if (data.custos.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-gray-600">Nenhum registro encontrado.</td></tr>';
                            return;
                        }
                        
                        data.custos.forEach(custo => {
                            const dataFormatada = new Date(custo.data).toLocaleDateString('pt-BR');
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">${dataFormatada}</td>
                                    <td class="py-3 px-4">${custo.escola_nome || '-'}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800">
                                            ${custo.tipo || 'OUTROS'}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">${custo.descricao || '-'}</td>
                                    <td class="py-3 px-4 text-right font-medium">
                                        R$ ${parseFloat(custo.valor_total || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar relatório:', error);
                });
        }
    </script>
</body>
</html>

