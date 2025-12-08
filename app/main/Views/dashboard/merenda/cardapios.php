<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/CardapioModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'adm_merenda') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$cardapioModel = new CardapioModel();

// Buscar todas as escolas (ADM_MERENDA tem acesso a todas)
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos para cardápio
$sqlProdutos = "SELECT id, nome, unidade_medida FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'criar_cardapio') {
        $dados = [
            'escola_id' => $_POST['escola_id'] ?? null,
            'mes' => $_POST['mes'] ?? date('m'),
            'ano' => $_POST['ano'] ?? date('Y'),
            'itens' => json_decode($_POST['itens'] ?? '[]', true)
        ];
        
        if ($dados['escola_id'] && !empty($dados['itens'])) {
            $resultado = $cardapioModel->criar($dados);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'aprovar_cardapio' && !empty($_POST['cardapio_id'])) {
        $resultado = $cardapioModel->aprovar($_POST['cardapio_id']);
        echo json_encode(['success' => $resultado]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_cardapios') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['mes'])) $filtros['mes'] = $_GET['mes'];
        if (!empty($_GET['ano'])) $filtros['ano'] = $_GET['ano'];
        if (!empty($_GET['status'])) $filtros['status'] = $_GET['status'];
        
        $cardapios = $cardapioModel->listar($filtros);
        echo json_encode(['success' => true, 'cardapios' => $cardapios]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_cardapio' && !empty($_GET['id'])) {
        $cardapio = $cardapioModel->buscarPorId($_GET['id']);
        echo json_encode(['success' => true, 'cardapio' => $cardapio]);
        exit;
    }
}

// Buscar cardápios para exibição inicial
$filtrosInicial = ['ano' => date('Y')];
$cardapios = $cardapioModel->listar($filtrosInicial);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápios - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        .mobile-menu-overlay {
            transition: opacity 0.3s ease-in-out;
        }
        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
            }
            .sidebar-mobile.open {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../../components/sidebar_merenda.php'; ?>
    
    <!-- Main Content -->
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Cardápios</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'ADM' || $_SESSION['tipo'] === 'ADM_MERENDA') { ?>
                                <!-- Para ADM, texto simples com padding para alinhamento -->
                                <div class="text-right px-4 py-2">
                                    <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                    <p class="text-xs text-gray-500">Órgão Central</p>
                                </div>
                            <?php } else { ?>
                                <!-- Para outros usuários, card verde com ícone -->
                                <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="text-sm font-semibold">
                                            <?php echo $_SESSION['escola_atual'] ?? 'Escola Municipal'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Cardápios Escolares</h2>
                        <p class="text-gray-600 mt-1">Cadastre e gerencie os cardápios das escolas</p>
                    </div>
                    <button onclick="abrirModalNovoCardapio()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Cardápio</span>
                    </button>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mês</label>
                            <select id="filtro-mes" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="">Todos os meses</option>
                                <?php 
                                $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == date('m') ? 'selected' : '' ?>><?= $meses[$i - 1] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano</label>
                            <select id="filtro-ano" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                                    <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="filtro-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="">Todos</option>
                                <option value="RASCUNHO">Rascunho</option>
                                <option value="APROVADO">Aprovado</option>
                                <option value="PUBLICADO">Publicado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Cardápios -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div id="lista-cardapios" class="space-y-4">
                        <!-- Cardápios serão carregados aqui -->
                        <?php if (empty($cardapios)): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-gray-600">Nenhum cardápio encontrado.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cardapios as $cardapio): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($cardapio['escola_nome']) ?></h3>
                                            <p class="text-sm text-gray-600"><?php 
                                            $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                            echo $meses[$cardapio['mes'] - 1] . '/' . $cardapio['ano']; 
                                            ?></p>
                                            <p class="text-xs text-gray-500 mt-1">Criado por: <?= htmlspecialchars($cardapio['criado_por_nome'] ?? 'N/A') ?></p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php
                                                echo $cardapio['status'] === 'APROVADO' ? 'bg-green-100 text-green-800' : 
                                                    ($cardapio['status'] === 'PUBLICADO' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');
                                            ?>">
                                                <?= htmlspecialchars($cardapio['status'] ?? 'RASCUNHO') ?>
                                            </span>
                                            <button onclick="verDetalhesCardapio(<?= $cardapio['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Ver Detalhes
                                            </button>
                                            <?php if ($cardapio['status'] === 'RASCUNHO'): ?>
                                                <button onclick="aprovarCardapio(<?= $cardapio['id'] ?>)" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                    Aprovar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Novo Cardápio -->
    <div id="modal-novo-cardapio" class="fixed inset-0 bg-white z-[60] hidden flex flex-col">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-6 flex items-center justify-between shadow-lg">
            <h3 class="text-2xl font-bold">Novo Cardápio</h3>
            <button onclick="fecharModalNovoCardapio()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg p-6 shadow-sm mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="cardapio-escola-id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione uma escola</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mês</label>
                            <select id="cardapio-mes" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <?php 
                                $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == date('m') ? 'selected' : '' ?>><?= $meses[$i - 1] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano</label>
                            <select id="cardapio-ano" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <?php for ($i = date('Y'); $i <= date('Y') + 1; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold text-gray-900">Itens do Cardápio</h4>
                        <button onclick="adicionarItemCardapio()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            Adicionar Item
                        </button>
                    </div>
                    
                    <div id="itens-cardapio-container" class="space-y-3">
                        <!-- Itens serão adicionados aqui -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 border-t border-gray-200 p-6">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalNovoCardapio()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarCardapio()" class="flex-1 px-6 py-3 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition-colors">
                    Salvar Cardápio
                </button>
            </div>
        </div>
    </div>
    
    <!-- Logout Modal -->
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

        // Funções para Cardápios
        let itemIndex = 0;
        const produtos = <?= json_encode($produtos) ?>;

        function abrirModalNovoCardapio() {
            document.getElementById('modal-novo-cardapio').classList.remove('hidden');
            itemIndex = 0;
            document.getElementById('itens-cardapio-container').innerHTML = '';
            adicionarItemCardapio();
        }

        function fecharModalNovoCardapio() {
            document.getElementById('modal-novo-cardapio').classList.add('hidden');
        }

        function adicionarItemCardapio() {
            const container = document.getElementById('itens-cardapio-container');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-3 p-3 border border-gray-200 rounded-lg';
            div.id = `item-${itemIndex}`;
            div.innerHTML = `
                <select class="produto-select flex-1 px-3 py-2 border border-gray-300 rounded-lg" data-item-index="${itemIndex}">
                    <option value="">Selecione um produto</option>
                    ${produtos.map(p => `<option value="${p.id}">${p.nome} (${p.unidade_medida})</option>`).join('')}
                </select>
                <input type="number" step="0.001" min="0" class="quantidade-input w-32 px-3 py-2 border border-gray-300 rounded-lg" placeholder="Quantidade" data-item-index="${itemIndex}">
                <button onclick="removerItemCardapio(${itemIndex})" class="text-red-600 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
            itemIndex++;
        }

        function removerItemCardapio(index) {
            const item = document.getElementById(`item-${index}`);
            if (item) {
                item.remove();
            }
        }

        function salvarCardapio() {
            const escolaId = document.getElementById('cardapio-escola-id').value;
            const mes = document.getElementById('cardapio-mes').value;
            const ano = document.getElementById('cardapio-ano').value;
            
            if (!escolaId || !mes || !ano) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            const itens = [];
            document.querySelectorAll('.produto-select').forEach(select => {
                const produtoId = select.value;
                const quantidadeInput = document.querySelector(`.quantidade-input[data-item-index="${select.dataset.itemIndex}"]`);
                const quantidade = quantidadeInput ? quantidadeInput.value : '';
                
                if (produtoId && quantidade) {
                    itens.push({
                        produto_id: produtoId,
                        quantidade: parseFloat(quantidade)
                    });
                }
            });
            
            if (itens.length === 0) {
                alert('Adicione pelo menos um item ao cardápio.');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'criar_cardapio');
            formData.append('escola_id', escolaId);
            formData.append('mes', mes);
            formData.append('ano', ano);
            formData.append('itens', JSON.stringify(itens));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cardápio criado com sucesso!');
                    fecharModalNovoCardapio();
                    filtrarCardapios();
                } else {
                    alert('Erro ao criar cardápio: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao criar cardápio.');
            });
        }

        function filtrarCardapios() {
            const escolaId = document.getElementById('filtro-escola').value;
            const mes = document.getElementById('filtro-mes').value;
            const ano = document.getElementById('filtro-ano').value;
            const status = document.getElementById('filtro-status').value;
            
            let url = '?acao=listar_cardapios';
            if (escolaId) url += '&escola_id=' + escolaId;
            if (mes) url += '&mes=' + mes;
            if (ano) url += '&ano=' + ano;
            if (status) url += '&status=' + status;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('lista-cardapios');
                        container.innerHTML = '';
                        
                        if (data.cardapios.length === 0) {
                            container.innerHTML = '<div class="text-center py-12"><p class="text-gray-600">Nenhum cardápio encontrado.</p></div>';
                            return;
                        }
                        
                        data.cardapios.forEach(cardapio => {
                            const statusClass = cardapio.status === 'APROVADO' ? 'bg-green-100 text-green-800' : 
                                             (cardapio.status === 'PUBLICADO' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');
                            const mesNome = new Date(2000, cardapio.mes - 1).toLocaleString('pt-BR', { month: 'long' });
                            
                            container.innerHTML += `
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900">${cardapio.escola_nome}</h3>
                                            <p class="text-sm text-gray-600">${mesNome}/${cardapio.ano}</p>
                                            <p class="text-xs text-gray-500 mt-1">Criado por: ${cardapio.criado_por_nome || 'N/A'}</p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium ${statusClass}">
                                                ${cardapio.status || 'RASCUNHO'}
                                            </span>
                                            <button onclick="verDetalhesCardapio(${cardapio.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Ver Detalhes
                                            </button>
                                            ${cardapio.status === 'RASCUNHO' ? `<button onclick="aprovarCardapio(${cardapio.id})" class="text-green-600 hover:text-green-700 font-medium text-sm">Aprovar</button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar cardápios:', error);
                });
        }

        function aprovarCardapio(id) {
            if (!confirm('Deseja aprovar este cardápio?')) return;
            
            const formData = new FormData();
            formData.append('acao', 'aprovar_cardapio');
            formData.append('cardapio_id', id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cardápio aprovado com sucesso!');
                    filtrarCardapios();
                } else {
                    alert('Erro ao aprovar cardápio.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao aprovar cardápio.');
            });
        }

        function verDetalhesCardapio(id) {
            fetch('?acao=buscar_cardapio&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.cardapio) {
                        const cardapio = data.cardapio;
                        const mesNome = new Date(2000, cardapio.mes - 1).toLocaleString('pt-BR', { month: 'long' });
                        let itensHtml = '';
                        
                        if (cardapio.itens && cardapio.itens.length > 0) {
                            itensHtml = cardapio.itens.map(item => `
                                <tr>
                                    <td class="px-4 py-2">${item.produto_nome}</td>
                                    <td class="px-4 py-2 text-center">${item.quantidade} ${item.unidade_medida}</td>
                                </tr>
                            `).join('');
                        } else {
                            itensHtml = '<tr><td colspan="2" class="px-4 py-2 text-center text-gray-500">Nenhum item cadastrado</td></tr>';
                        }
                        
                        alert(`
Cardápio: ${cardapio.escola_nome}
Período: ${mesNome}/${cardapio.ano}
Status: ${cardapio.status || 'RASCUNHO'}

Itens:
${cardapio.itens ? cardapio.itens.map(i => `- ${i.produto_nome}: ${i.quantidade} ${i.unidade_medida}`).join('\n') : 'Nenhum item'}
                        `);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao buscar detalhes do cardápio.');
                });
        }
    </script>
</body>
</html>

