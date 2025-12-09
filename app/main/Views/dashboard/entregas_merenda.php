<?php
// Iniciar output buffering
ob_start();

require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/EntregaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'adm_merenda') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$entregaModel = new EntregaModel();

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar fornecedores
$sqlFornecedores = "SELECT id, nome FROM fornecedor WHERE ativo = 1 ORDER BY nome ASC";
$stmtFornecedores = $conn->prepare($sqlFornecedores);
$stmtFornecedores->execute();
$fornecedores = $stmtFornecedores->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos para itens da entrega
$sqlProdutos = "SELECT id, nome, unidade_medida FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    // Limpar qualquer output anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_POST['acao'] === 'registrar_recebimento') {
        $id = $_POST['entrega_id'] ?? null;
        $dados = [
            'data_entrega' => $_POST['data_entrega'] ?? date('Y-m-d'),
            'observacoes' => $_POST['observacoes'] ?? null
        ];
        
        $resultado = $entregaModel->registrarRecebimento($id, $dados);
        echo json_encode(['success' => $resultado], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($_POST['acao'] === 'criar_entrega') {
        try {
            $escolaId = $_POST['escola_id'] ?? null;
            $fornecedorId = $_POST['fornecedor_id'] ?? null;
            $dataPrevista = $_POST['data_prevista'] ?? null;
            $transportadora = $_POST['transportadora'] ?? null;
            $notaFiscal = $_POST['nota_fiscal'] ?? null;
            $observacoes = $_POST['observacoes'] ?? null;
            $pedidoCestaId = $_POST['pedido_cesta_id'] ?? null;
            
            if (empty($escolaId) || empty($dataPrevista)) {
                throw new Exception('Escola e Data Prevista são obrigatórios.');
            }
            
            // Processar itens (recebidos como JSON string)
            $itens = [];
            if (!empty($_POST['itens'])) {
                $itensJson = json_decode($_POST['itens'], true);
                if (is_array($itensJson)) {
                    foreach ($itensJson as $item) {
                        if (is_array($item)) {
                            $produtoId = $item['produto_id'] ?? null;
                            $quantidadeSolicitada = $item['quantidade_solicitada'] ?? null;
                            
                            if (!empty($produtoId) && !empty($quantidadeSolicitada)) {
                                $itens[] = [
                                    'produto_id' => $produtoId,
                                    'quantidade_solicitada' => $quantidadeSolicitada,
                                    'quantidade_entregue' => $item['quantidade_entregue'] ?? $quantidadeSolicitada
                                ];
                            }
                        }
                    }
                }
            }
            
            $dados = [
                'escola_id' => $escolaId,
                'fornecedor_id' => !empty($fornecedorId) ? $fornecedorId : null,
                'data_prevista' => $dataPrevista,
                'transportadora' => !empty($transportadora) ? $transportadora : null,
                'nota_fiscal' => !empty($notaFiscal) ? $notaFiscal : null,
                'observacoes' => !empty($observacoes) ? $observacoes : null,
                'pedido_cesta_id' => !empty($pedidoCestaId) ? $pedidoCestaId : null,
                'itens' => $itens
            ];
            
            $resultado = $entregaModel->criar($dados);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Erro inesperado: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_entregas') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['status'])) $filtros['status'] = $_GET['status'];
        if (!empty($_GET['data_prevista'])) $filtros['data_prevista'] = $_GET['data_prevista'];
        
        $entregas = $entregaModel->listar($filtros);
        echo json_encode(['success' => true, 'entregas' => $entregas], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Buscar entregas recentes
$entregasRecentes = $entregaModel->listar(['status' => 'AGENDADA']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entregas - SIGEA</title>
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
        .mobile-menu-overlay { transition: opacity 0.3s ease-in-out; }
        @media (max-width: 1023px) {
            .sidebar-mobile { transform: translateX(-100%); }
            .sidebar-mobile.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_merenda.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Acompanhamento de Entregas</h1>
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
                        <h2 class="text-2xl font-bold text-gray-900">Entregas de Alimentos</h2>
                        <p class="text-gray-600 mt-1">Gerencie e acompanhe entregas de alimentos</p>
                    </div>
                    <button onclick="abrirModalNovaEntrega()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Entrega</span>
                    </button>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEntregas()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="filtro-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEntregas()">
                                <option value="">Todos</option>
                                <option value="AGENDADA" selected>Agendadas</option>
                                <option value="EM_TRANSITO">Em Trânsito</option>
                                <option value="ENTREGUE">Entregues</option>
                                <option value="ATRASADA">Atrasadas</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Prevista</label>
                            <input type="date" id="filtro-data" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEntregas()">
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Entregas -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Fornecedor</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data Prevista</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Data Entrega</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-entregas">
                                <?php if (empty($entregasRecentes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-gray-600">
                                            Nenhuma entrega encontrada.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($entregasRecentes as $entrega): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= htmlspecialchars($entrega['escola_nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($entrega['fornecedor_nome'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= date('d/m/Y', strtotime($entrega['data_prevista'])) ?></td>
                                            <td class="py-3 px-4"><?= $entrega['data_entrega'] ? date('d/m/Y', strtotime($entrega['data_entrega'])) : '-' ?></td>
                                            <td class="py-3 px-4">
                                                <span class="px-3 py-1 rounded-full text-xs font-medium <?php
                                                    echo $entrega['status'] === 'ENTREGUE' ? 'bg-green-100 text-green-800' :
                                                        ($entrega['status'] === 'ATRASADA' ? 'bg-red-100 text-red-800' :
                                                        ($entrega['status'] === 'EM_TRANSITO' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'));
                                                ?>">
                                                    <?= htmlspecialchars($entrega['status'] ?? 'AGENDADA') ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if ($entrega['status'] !== 'ENTREGUE'): ?>
                                                    <button onclick="registrarRecebimento(<?= $entrega['id'] ?>)" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                        Registrar Recebimento
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-500 text-sm">Concluída</span>
                                                <?php endif; ?>
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
    
    <!-- Modal Nova Entrega -->
    <div id="modal-nova-entrega" class="fixed inset-0 bg-white z-[60] hidden flex flex-col">
        <div class="bg-teal-600 text-white p-6 flex items-center justify-between shadow-lg">
            <h3 class="text-2xl font-bold">Nova Entrega</h3>
            <button onclick="fecharModalNovaEntrega()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <form id="form-nova-entrega" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                            <select id="entrega-escola" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione uma escola</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fornecedor</label>
                            <select id="entrega-fornecedor" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione um fornecedor</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <option value="<?= $fornecedor['id'] ?>"><?= htmlspecialchars($fornecedor['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data Prevista *</label>
                            <input type="date" id="entrega-data-prevista" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Transportadora</label>
                            <input type="text" id="entrega-transportadora" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Nome da transportadora">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nota Fiscal</label>
                            <input type="text" id="entrega-nota-fiscal" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Número da nota fiscal">
                        </div>
                    </div>
                    
                    <!-- Itens da Entrega -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <label class="block text-sm font-medium text-gray-700">Produtos da Entrega</label>
                            <button type="button" onclick="adicionarItemEntrega()" class="text-teal-600 hover:text-teal-700 font-medium text-sm flex items-center space-x-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Adicionar Produto</span>
                            </button>
                        </div>
                        <div id="itens-entrega" class="space-y-3">
                            <!-- Itens serão adicionados aqui via JavaScript -->
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                        <textarea id="entrega-observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Observações sobre a entrega"></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-200 p-6">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalNovaEntrega()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarNovaEntrega()" class="flex-1 px-6 py-3 text-white bg-teal-600 hover:bg-teal-700 rounded-lg font-medium transition-colors">
                    Salvar Entrega
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Registrar Recebimento -->
    <div id="modal-recebimento" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Registrar Recebimento</h3>
            <form id="form-recebimento" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data de Entrega *</label>
                    <input type="date" id="recebimento-data" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea id="recebimento-observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="fecharModalRecebimento()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                        Cancelar
                    </button>
                    <button type="button" onclick="salvarRecebimento()" class="flex-1 px-4 py-2 text-white bg-teal-600 hover:bg-teal-700 rounded-lg font-medium transition-colors">
                        Salvar
                    </button>
                </div>
            </form>
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
        let entregaIdAtual = null;
        
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

        // Variáveis para nova entrega
        let itensEntrega = [];
        let produtosDisponiveis = [
            <?php 
            foreach ($produtos as $produto): 
                echo '{id: ' . $produto['id'] . ', nome: "' . htmlspecialchars($produto['nome'], ENT_QUOTES) . '", unidade: "' . htmlspecialchars($produto['unidade_medida'], ENT_QUOTES) . '"},';
            endforeach; 
            ?>
        ];
        
        function abrirModalNovaEntrega() {
            document.getElementById('modal-nova-entrega').classList.remove('hidden');
            document.getElementById('form-nova-entrega').reset();
            itensEntrega = [];
            atualizarListaItens();
        }
        
        function fecharModalNovaEntrega() {
            document.getElementById('modal-nova-entrega').classList.add('hidden');
            itensEntrega = [];
        }
        
        function adicionarItemEntrega() {
            itensEntrega.push({
                produto_id: '',
                quantidade_solicitada: '',
                quantidade_entregue: ''
            });
            atualizarListaItens();
        }
        
        function removerItemEntrega(index) {
            itensEntrega.splice(index, 1);
            atualizarListaItens();
        }
        
        function atualizarListaItens() {
            const container = document.getElementById('itens-entrega');
            container.innerHTML = '';
            
            if (itensEntrega.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">Nenhum produto adicionado. Clique em "Adicionar Produto" para começar.</p>';
                return;
            }
            
            itensEntrega.forEach((item, index) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'bg-gray-50 p-4 rounded-lg border border-gray-200';
                itemDiv.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produto</label>
                            <select onchange="itensEntrega[${index}].produto_id = this.value" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione um produto</option>
                                ${produtosDisponiveis.map(p => 
                                    `<option value="${p.id}" ${item.produto_id == p.id ? 'selected' : ''}>${p.nome} (${p.unidade})</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade Solicitada</label>
                            <input type="number" step="0.01" min="0" value="${item.quantidade_solicitada}" 
                                   onchange="itensEntrega[${index}].quantidade_solicitada = this.value"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="0.00">
                        </div>
                        <div class="flex items-end">
                            <button type="button" onclick="removerItemEntrega(${index})" class="w-full px-4 py-2 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg font-medium transition-colors">
                                Remover
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(itemDiv);
            });
        }
        
        function salvarNovaEntrega() {
            const escolaId = document.getElementById('entrega-escola').value;
            const fornecedorId = document.getElementById('entrega-fornecedor').value;
            const dataPrevista = document.getElementById('entrega-data-prevista').value;
            const transportadora = document.getElementById('entrega-transportadora').value;
            const notaFiscal = document.getElementById('entrega-nota-fiscal').value;
            const observacoes = document.getElementById('entrega-observacoes').value;
            
            if (!escolaId || !dataPrevista) {
                alert('Por favor, preencha os campos obrigatórios (Escola e Data Prevista).');
                return;
            }
            
            // Filtrar itens válidos
            const itensValidos = itensEntrega.filter(item => item.produto_id && item.quantidade_solicitada);
            
            const formData = new FormData();
            formData.append('acao', 'criar_entrega');
            formData.append('escola_id', escolaId);
            if (fornecedorId) formData.append('fornecedor_id', fornecedorId);
            formData.append('data_prevista', dataPrevista);
            if (transportadora) formData.append('transportadora', transportadora);
            if (notaFiscal) formData.append('nota_fiscal', notaFiscal);
            if (observacoes) formData.append('observacoes', observacoes);
            
            // Enviar itens como JSON string (mais confiável)
            formData.append('itens', JSON.stringify(itensValidos));
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span> Salvando...';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Resposta não é JSON válido. Resposta:', text);
                        throw new Error('Resposta não é JSON válido. Resposta: ' + text.substring(0, 200));
                    });
                }
                return response.json();
            })
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                if (data.success) {
                    alert('Entrega registrada com sucesso!');
                    fecharModalNovaEntrega();
                    filtrarEntregas();
                } else {
                    alert('Erro ao registrar entrega: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                console.error('Erro:', error);
                alert('Erro ao registrar entrega: ' + error.message);
            });
        }

        function filtrarEntregas() {
            const escolaId = document.getElementById('filtro-escola').value;
            const status = document.getElementById('filtro-status').value;
            const data = document.getElementById('filtro-data').value;
            
            let url = '?acao=listar_entregas';
            if (escolaId) url += '&escola_id=' + escolaId;
            if (status) url += '&status=' + status;
            if (data) url += '&data_prevista=' + data;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-entregas');
                        tbody.innerHTML = '';
                        
                        if (data.entregas.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-gray-600">Nenhuma entrega encontrada.</td></tr>';
                            return;
                        }
                        
                        data.entregas.forEach(entrega => {
                            const statusClass = entrega.status === 'ENTREGUE' ? 'bg-green-100 text-green-800' :
                                              (entrega.status === 'ATRASADA' ? 'bg-red-100 text-red-800' :
                                              (entrega.status === 'EM_TRANSITO' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'));
                            const dataPrevista = new Date(entrega.data_prevista).toLocaleDateString('pt-BR');
                            const dataEntrega = entrega.data_entrega ? new Date(entrega.data_entrega).toLocaleDateString('pt-BR') : '-';
                            
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">${entrega.escola_nome}</td>
                                    <td class="py-3 px-4">${entrega.fornecedor_nome || '-'}</td>
                                    <td class="py-3 px-4">${dataPrevista}</td>
                                    <td class="py-3 px-4">${dataEntrega}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium ${statusClass}">
                                            ${entrega.status || 'AGENDADA'}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        ${entrega.status !== 'ENTREGUE' ? `
                                            <button onclick="registrarRecebimento(${entrega.id})" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                Registrar Recebimento
                                            </button>
                                        ` : '<span class="text-gray-500 text-sm">Concluída</span>'}
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar entregas:', error);
                });
        }

        function registrarRecebimento(id) {
            entregaIdAtual = id;
            document.getElementById('modal-recebimento').classList.remove('hidden');
            document.getElementById('form-recebimento').reset();
            document.getElementById('recebimento-data').value = '<?= date('Y-m-d') ?>';
        }

        function fecharModalRecebimento() {
            document.getElementById('modal-recebimento').classList.add('hidden');
            entregaIdAtual = null;
        }

        function salvarRecebimento() {
            if (!entregaIdAtual) return;
            
            const formData = new FormData();
            formData.append('acao', 'registrar_recebimento');
            formData.append('entrega_id', entregaIdAtual);
            formData.append('data_entrega', document.getElementById('recebimento-data').value);
            formData.append('observacoes', document.getElementById('recebimento-observacoes').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Recebimento registrado com sucesso!');
                    fecharModalRecebimento();
                    filtrarEntregas();
                } else {
                    alert('Erro ao registrar recebimento.');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar recebimento.');
            });
        }
    </script>
</body>
</html>

