<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/ConsumoDiarioModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'adm_merenda') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$consumoModel = new ConsumoDiarioModel();

// Buscar todas as escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar turmas
$sqlTurmas = "SELECT t.id, CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as nome, t.escola_id 
              FROM turma t WHERE t.ativo = 1 ORDER BY t.serie, t.letra";
$stmtTurmas = $conn->prepare($sqlTurmas);
$stmtTurmas->execute();
$turmas = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos
$sqlProdutos = "SELECT id, nome, unidade_medida FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'registrar_consumo') {
        $dados = [
            'escola_id' => $_POST['escola_id'] ?? null,
            'turma_id' => $_POST['turma_id'] ?? null,
            'data' => $_POST['data'] ?? date('Y-m-d'),
            'turno' => $_POST['turno'] ?? null,
            'total_alunos' => $_POST['total_alunos'] ?? 0,
            'alunos_atendidos' => $_POST['alunos_atendidos'] ?? 0,
            'observacoes' => $_POST['observacoes'] ?? null,
            'itens' => json_decode($_POST['itens'] ?? '[]', true)
        ];
        
        if ($dados['escola_id'] && $dados['data']) {
            $resultado = $consumoModel->registrar($dados);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_consumo') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['data'])) $filtros['data'] = $_GET['data'];
        if (!empty($_GET['data_inicio'])) $filtros['data_inicio'] = $_GET['data_inicio'];
        if (!empty($_GET['data_fim'])) $filtros['data_fim'] = $_GET['data_fim'];
        
        $consumos = $consumoModel->listar($filtros);
        echo json_encode(['success' => true, 'consumos' => $consumos]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_turmas_escola' && !empty($_GET['escola_id'])) {
        $escolaId = $_GET['escola_id'];
        $sql = "SELECT t.id, CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as nome 
                FROM turma t WHERE t.escola_id = :escola_id AND t.ativo = 1 ORDER BY t.serie, t.letra";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':escola_id', $escolaId);
        $stmt->execute();
        $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'turmas' => $turmas]);
        exit;
    }
}

// Buscar consumos recentes
$consumosRecentes = $consumoModel->listar(['data_inicio' => date('Y-m-d', strtotime('-7 days'))]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumo Diário - SIGEA</title>
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
    <?php include 'components/sidebar_merenda.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Registro de Consumo Diário</h1>
                    </div>
                    <div class="w-10"></div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Consumo Diário</h2>
                        <p class="text-gray-600 mt-1">Registre o consumo diário de alimentos por escola e turma</p>
                    </div>
                    <button onclick="abrirModalRegistrarConsumo()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Registrar Consumo</span>
                    </button>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola-consumo" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarConsumo()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                            <input type="date" id="filtro-data-inicio" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarConsumo()" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                            <input type="date" id="filtro-data-fim" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarConsumo()" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarConsumo()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Consumos -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Registros Recentes</h3>
                    <div id="lista-consumos" class="space-y-4">
                        <?php if (empty($consumosRecentes)): ?>
                            <div class="text-center py-12">
                                <p class="text-gray-600">Nenhum registro de consumo encontrado.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($consumosRecentes as $consumo): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($consumo['escola_nome']) ?></h4>
                                            <p class="text-sm text-gray-600"><?= date('d/m/Y', strtotime($consumo['data'])) ?> - <?= htmlspecialchars($consumo['turno'] ?? 'N/A') ?></p>
                                            <?php if ($consumo['turma_nome']): ?>
                                                <p class="text-xs text-gray-500">Turma: <?= htmlspecialchars($consumo['turma_nome']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900"><?= $consumo['alunos_atendidos'] ?>/<?= $consumo['total_alunos'] ?> alunos</p>
                                            <p class="text-xs text-gray-500">Atendidos/Total</p>
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
    
    <!-- Modal Registrar Consumo -->
    <div id="modal-registrar-consumo" class="fixed inset-0 bg-white z-[60] hidden flex flex-col">
        <!-- Header -->
        <div class="bg-green-600 text-white p-6 flex items-center justify-between shadow-lg">
            <h3 class="text-2xl font-bold">Registrar Consumo Diário</h3>
            <button onclick="fecharModalRegistrarConsumo()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg p-6 shadow-sm mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="consumo-escola-id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="carregarTurmasEscola(this.value)">
                                <option value="">Selecione uma escola</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                            <input type="date" id="consumo-data" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Turma (Opcional)</label>
                            <select id="consumo-turma-id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione primeiro a escola</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Turno</label>
                            <select id="consumo-turno" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione</option>
                                <option value="MANHA">Manhã</option>
                                <option value="TARDE">Tarde</option>
                                <option value="NOITE">Noite</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total de Alunos</label>
                            <input type="number" id="consumo-total-alunos" min="0" value="0" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Alunos Atendidos</label>
                            <input type="number" id="consumo-alunos-atendidos" min="0" value="0" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                        <textarea id="consumo-observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold text-gray-900">Itens Consumidos</h4>
                        <button onclick="adicionarItemConsumo()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            Adicionar Item
                        </button>
                    </div>
                    
                    <div id="itens-consumo-container" class="space-y-3">
                        <!-- Itens serão adicionados aqui -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 border-t border-gray-200 p-6">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalRegistrarConsumo()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarConsumo()" class="flex-1 px-6 py-3 text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors">
                    Salvar Consumo
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

        // Funções para Consumo
        let itemConsumoIndex = 0;
        const produtos = <?= json_encode($produtos) ?>;

        function abrirModalRegistrarConsumo() {
            document.getElementById('modal-registrar-consumo').classList.remove('hidden');
            itemConsumoIndex = 0;
            document.getElementById('itens-consumo-container').innerHTML = '';
            adicionarItemConsumo();
        }

        function fecharModalRegistrarConsumo() {
            document.getElementById('modal-registrar-consumo').classList.add('hidden');
        }

        function carregarTurmasEscola(escolaId) {
            const turmaSelect = document.getElementById('consumo-turma-id');
            turmaSelect.innerHTML = '<option value="">Carregando...</option>';
            
            if (!escolaId) {
                turmaSelect.innerHTML = '<option value="">Selecione primeiro a escola</option>';
                return;
            }
            
            fetch('?acao=buscar_turmas_escola&escola_id=' + escolaId)
                .then(response => response.json())
                .then(data => {
                    turmaSelect.innerHTML = '<option value="">Todas as turmas (geral)</option>';
                    if (data.success && data.turmas.length > 0) {
                        data.turmas.forEach(turma => {
                            const option = document.createElement('option');
                            option.value = turma.id;
                            option.textContent = turma.nome;
                            turmaSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar turmas:', error);
                    turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                });
        }

        function adicionarItemConsumo() {
            const container = document.getElementById('itens-consumo-container');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-3 p-3 border border-gray-200 rounded-lg';
            div.id = `item-consumo-${itemConsumoIndex}`;
            div.innerHTML = `
                <select class="produto-consumo-select flex-1 px-3 py-2 border border-gray-300 rounded-lg" data-item-index="${itemConsumoIndex}">
                    <option value="">Selecione um produto</option>
                    ${produtos.map(p => `<option value="${p.id}">${p.nome} (${p.unidade_medida})</option>`).join('')}
                </select>
                <input type="number" step="0.001" min="0" class="quantidade-consumo-input w-32 px-3 py-2 border border-gray-300 rounded-lg" placeholder="Quantidade" data-item-index="${itemConsumoIndex}">
                <button onclick="removerItemConsumo(${itemConsumoIndex})" class="text-red-600 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
            itemConsumoIndex++;
        }

        function removerItemConsumo(index) {
            const item = document.getElementById(`item-consumo-${index}`);
            if (item) {
                item.remove();
            }
        }

        function salvarConsumo() {
            const escolaId = document.getElementById('consumo-escola-id').value;
            const turmaId = document.getElementById('consumo-turma-id').value;
            const data = document.getElementById('consumo-data').value;
            const turno = document.getElementById('consumo-turno').value;
            const totalAlunos = document.getElementById('consumo-total-alunos').value;
            const alunosAtendidos = document.getElementById('consumo-alunos-atendidos').value;
            const observacoes = document.getElementById('consumo-observacoes').value;
            
            if (!escolaId || !data || !totalAlunos || !alunosAtendidos) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            const itens = [];
            document.querySelectorAll('.produto-consumo-select').forEach(select => {
                const produtoId = select.value;
                const quantidadeInput = document.querySelector(`.quantidade-consumo-input[data-item-index="${select.dataset.itemIndex}"]`);
                const quantidade = quantidadeInput ? quantidadeInput.value : '';
                
                if (produtoId && quantidade) {
                    const produto = produtos.find(p => p.id == produtoId);
                    itens.push({
                        produto_id: produtoId,
                        quantidade: parseFloat(quantidade),
                        unidade_medida: produto ? produto.unidade_medida : null
                    });
                }
            });
            
            const formData = new FormData();
            formData.append('acao', 'registrar_consumo');
            formData.append('escola_id', escolaId);
            formData.append('turma_id', turmaId || '');
            formData.append('data', data);
            formData.append('turno', turno);
            formData.append('total_alunos', totalAlunos);
            formData.append('alunos_atendidos', alunosAtendidos);
            formData.append('observacoes', observacoes);
            formData.append('itens', JSON.stringify(itens));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Consumo registrado com sucesso!');
                    fecharModalRegistrarConsumo();
                    filtrarConsumo();
                } else {
                    alert('Erro ao registrar consumo: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar consumo.');
            });
        }

        function filtrarConsumo() {
            const escolaId = document.getElementById('filtro-escola-consumo').value;
            const dataInicio = document.getElementById('filtro-data-inicio').value;
            const dataFim = document.getElementById('filtro-data-fim').value;
            
            let url = '?acao=listar_consumo';
            if (escolaId) url += '&escola_id=' + escolaId;
            if (dataInicio) url += '&data_inicio=' + dataInicio;
            if (dataFim) url += '&data_fim=' + dataFim;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('lista-consumos');
                        container.innerHTML = '';
                        
                        if (data.consumos.length === 0) {
                            container.innerHTML = '<div class="text-center py-12"><p class="text-gray-600">Nenhum registro encontrado.</p></div>';
                            return;
                        }
                        
                        data.consumos.forEach(consumo => {
                            const dataFormatada = new Date(consumo.data).toLocaleDateString('pt-BR');
                            container.innerHTML += `
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-semibold text-gray-900">${consumo.escola_nome}</h4>
                                            <p class="text-sm text-gray-600">${dataFormatada} - ${consumo.turno || 'N/A'}</p>
                                            ${consumo.turma_nome ? `<p class="text-xs text-gray-500">Turma: ${consumo.turma_nome}</p>` : ''}
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900">${consumo.alunos_atendidos}/${consumo.total_alunos} alunos</p>
                                            <p class="text-xs text-gray-500">Atendidos/Total</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar consumos:', error);
                });
        }
    </script>
</body>
</html>

