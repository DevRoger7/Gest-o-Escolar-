<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/PacoteEscolaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'adm_merenda') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$pacoteModel = new PacoteEscolaModel();

$mensagem = '';
$tipoMensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'criar') {
        $dados = [
            'descricao' => $_POST['descricao'] ?? null,
            'escola_id' => $_POST['escola_id'] ?? null,
            'data_envio' => $_POST['data_envio'] ?? date('Y-m-d'),
            'observacoes' => $_POST['observacoes'] ?? null,
            'itens' => json_decode($_POST['itens'] ?? '[]', true)
        ];
        
        $resultado = $pacoteModel->criar($dados);
        
        if ($resultado['success']) {
            $mensagem = $resultado['message'];
            $tipoMensagem = 'success';
        } else {
            $mensagem = $resultado['message'];
            $tipoMensagem = 'error';
        }
    } elseif ($_POST['acao'] === 'atualizar') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $dados = [
                'descricao' => $_POST['descricao'] ?? null,
                'escola_id' => $_POST['escola_id'] ?? null,
                'data_envio' => $_POST['data_envio'] ?? date('Y-m-d'),
                'observacoes' => $_POST['observacoes'] ?? null,
                'itens' => json_decode($_POST['itens'] ?? '[]', true)
            ];
            
            $resultado = $pacoteModel->atualizar($id, $dados);
            
            if ($resultado['success']) {
                $mensagem = $resultado['message'];
                $tipoMensagem = 'success';
            } else {
                $mensagem = $resultado['message'];
                $tipoMensagem = 'error';
            }
        }
    } elseif ($_POST['acao'] === 'excluir') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $resultado = $pacoteModel->excluir($id);
            if ($resultado['success']) {
                $mensagem = $resultado['message'];
                $tipoMensagem = 'success';
            } else {
                $mensagem = $resultado['message'];
                $tipoMensagem = 'error';
            }
        }
    }
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'buscar_pacote') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $pacote = $pacoteModel->buscarPorId($id);
            $itens = $pacote ? $pacoteModel->buscarItens($id) : [];
            echo json_encode(['success' => true, 'pacote' => $pacote, 'itens' => $itens]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID não informado']);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_estoque') {
        $produtoId = $_GET['produto_id'] ?? null;
        if ($produtoId) {
            $sqlEstoque = "SELECT COALESCE(SUM(ec.quantidade), 0) as quantidade_total
                          FROM produto p
                          LEFT JOIN estoque_central ec ON p.id = ec.produto_id
                          WHERE p.id = :produto_id AND p.ativo = 1
                          GROUP BY p.id";
            $stmtEstoque = $conn->prepare($sqlEstoque);
            $stmtEstoque->bindParam(':produto_id', $produtoId);
            $stmtEstoque->execute();
            $resultado = $stmtEstoque->fetch(PDO::FETCH_ASSOC);
            $quantidade = $resultado ? floatval($resultado['quantidade_total']) : 0;
            echo json_encode(['success' => true, 'quantidade' => $quantidade]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID do produto não informado']);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_estoques_todos') {
        $sqlEstoques = "SELECT p.id, COALESCE(SUM(ec.quantidade), 0) as quantidade_total
                       FROM produto p
                       LEFT JOIN estoque_central ec ON p.id = ec.produto_id
                       WHERE p.ativo = 1
                       GROUP BY p.id";
        $stmtEstoques = $conn->prepare($sqlEstoques);
        $stmtEstoques->execute();
        $estoques = [];
        while ($row = $stmtEstoques->fetch(PDO::FETCH_ASSOC)) {
            $estoques[$row['id']] = floatval($row['quantidade_total']);
        }
        echo json_encode(['success' => true, 'estoques' => $estoques]);
        exit;
    }
}

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos com lotes do estoque (cada lote aparece separadamente)
$sqlProdutos = "SELECT 
                    ec.id as estoque_id,
                    p.id as produto_id,
                    p.nome,
                    p.unidade_medida,
                    ec.quantidade as estoque_quantidade,
                    ec.validade,
                    ec.lote,
                    CONCAT(p.id, ':', ec.id) as identificador_unico
                FROM produto p
                INNER JOIN estoque_central ec ON p.id = ec.produto_id
                WHERE p.ativo = 1 
                AND ec.quantidade > 0
                ORDER BY p.nome ASC, ec.validade ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Buscar pacotes
$pacotes = $pacoteModel->listar();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacotes para Escolas - SIGEA</title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Pacotes para Escolas</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="hidden lg:block">
                            <div class="text-right px-4 py-2">
                                <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                <p class="text-xs text-gray-500">Órgão Central</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <?php if ($mensagem): ?>
                <div class="mb-6 p-4 rounded-lg <?= $tipoMensagem === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                    <div class="flex items-center">
                        <?php if ($tipoMensagem === 'success'): ?>
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        <?php else: ?>
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($mensagem) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Gerenciar Pacotes de Alimentos</h2>
                        <p class="text-gray-600 mt-1">Crie e gerencie pacotes de alimentos para distribuição nas escolas</p>
                    </div>
                    <button onclick="abrirModalNovoPacote()" class="bg-primary-green hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Pacote</span>
                    </button>
                </div>
                
                <!-- Lista de Pacotes -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="border-b-2 border-gray-300 bg-gray-50">
                                    <th class="text-center py-4 px-4 font-semibold text-gray-700 text-sm uppercase tracking-wider">Descrição</th>
                                    <th class="text-center py-4 px-4 font-semibold text-gray-700 text-sm uppercase tracking-wider">Escola</th>
                                    <th class="text-center py-4 px-4 font-semibold text-gray-700 text-sm uppercase tracking-wider">Data Envio</th>
                                    <th class="text-center py-4 px-4 font-semibold text-gray-700 text-sm uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-pacotes" class="divide-y divide-gray-200">
                                    <?php if (empty($pacotes)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-16 text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                                <p class="text-lg font-medium">Nenhum pacote cadastrado</p>
                                                <p class="text-sm mt-1">Clique em "Novo Pacote" para começar</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pacotes as $pacote): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150">
                                            <td class="py-4 px-4 text-center">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= !empty($pacote['descricao']) ? htmlspecialchars(substr($pacote['descricao'], 0, 50)) . (strlen($pacote['descricao']) > 50 ? '...' : '') : 'Sem descrição' ?>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-center text-sm text-gray-700">
                                                <?= htmlspecialchars($pacote['escola_nome'] ?? '-') ?>
                                            </td>
                                            <td class="py-4 px-4 text-center text-sm text-gray-700 whitespace-nowrap">
                                                <?= $pacote['data_envio'] ? date('d/m/Y', strtotime($pacote['data_envio'])) : '-' ?>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <button onclick="verDetalhes(<?= $pacote['id'] ?>)" class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                                                        Ver
                                                    </button>
                                                    <button onclick="editarPacote(<?= $pacote['id'] ?>)" class="px-3 py-1 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors text-sm font-medium">
                                                        Editar
                                                    </button>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir este pacote?')">
                                                        <input type="hidden" name="acao" value="excluir">
                                                        <input type="hidden" name="id" value="<?= $pacote['id'] ?>">
                                                        <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                                                            Excluir
                                                        </button>
                                                    </form>
                                                </div>
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
    
    <!-- Modal Novo/Editar Pacote -->
    <div id="modal-novo-pacote" class="fixed inset-0 bg-white z-[60] hidden flex flex-col">
        <div class="bg-primary-green text-white p-6 flex items-center justify-between shadow-lg">
            <h3 id="modal-titulo" class="text-2xl font-bold">Novo Pacote de Alimentos</h3>
            <button onclick="fecharModalNovoPacote()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <div class="max-w-4xl mx-auto">
                <form id="form-novo-pacote" class="space-y-6">
                    <input type="hidden" name="acao" id="form-acao" value="criar">
                    <input type="hidden" name="id" id="form-pacote-id" value="">
                    <input type="hidden" name="itens" id="itens-json">
                    
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações do Pacote</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                                <select name="escola_id" id="pacote-escola" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="">Selecione a escola...</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <option value="<?= $escola['id'] ?>">
                                            <?= htmlspecialchars($escola['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Envio *</label>
                                <input type="date" name="data_envio" id="pacote-data-envio" required
                                       value="<?= date('Y-m-d') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                                <textarea name="descricao" id="pacote-descricao" rows="3"
                                          placeholder="Descrição do pacote..."
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                                <textarea name="observacoes" id="pacote-observacoes" rows="3"
                                          placeholder="Observações adicionais sobre o pacote..."
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">Itens do Pacote</h4>
                            <button type="button" onclick="adicionarItem()" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Adicionar Item</span>
                            </button>
                        </div>
                        <div id="lista-itens" class="space-y-3">
                            <!-- Itens serão adicionados aqui via JavaScript -->
                        </div>
                        <p id="mensagem-sem-itens" class="text-center text-gray-500 py-8">
                            Nenhum item adicionado. Clique em "Adicionar Item" para começar.
                        </p>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-200 p-6">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalNovoPacote()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarPacote()" id="btn-salvar-pacote" class="flex-1 px-6 py-3 text-white bg-primary-green hover:bg-green-700 rounded-lg font-medium transition-colors">
                    Salvar Pacote
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Detalhes -->
    <div id="modal-detalhes" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Detalhes do Pacote</h3>
                <button onclick="fecharModalDetalhes()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="conteudo-detalhes">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
        </div>
    </div>
    
    <script>
        // Inicializar variáveis globais
        window.modoEdicao = false;
        window.pacoteIdEdicao = null;
        window.itensPacote = [];
        window.contadorItens = 0;
        window.estoquesProdutos = {};
        window.produtosDisponiveis = [];
        
        // Função global para abrir o modal de novo pacote
        window.abrirModalNovoPacote = function() {
            console.log('Função abrirModalNovoPacote() chamada');
            window.modoEdicao = false;
            window.pacoteIdEdicao = null;
            document.getElementById('modal-titulo').textContent = 'Novo Pacote de Alimentos';
            document.getElementById('modal-novo-pacote').classList.remove('hidden');
            document.getElementById('form-acao').value = 'criar';
            document.getElementById('form-pacote-id').value = '';
            document.getElementById('btn-salvar-pacote').textContent = 'Salvar Pacote';
            document.getElementById('form-novo-pacote').reset();
            window.itensPacote = [];
            window.contadorItens = 0;
            
            // Recarregar estoques ao abrir o modal
            fetch('?acao=buscar_estoques_todos')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.estoquesProdutos = data.estoques;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar estoques:', error);
                });
        }
        
        // Função para formatar quantidade baseado na unidade de medida
        function formatarQuantidade(quantidade, unidadeMedida) {
            if (!quantidade && quantidade !== 0) return '0';
            
            const unidade = (unidadeMedida || '').toUpperCase().trim();
            // Unidades que permitem decimais (líquidas e de peso)
            const permiteDecimal = ['ML', 'L', 'G', 'KG', 'LT', 'LITRO', 'LITROS', 'MILILITRO', 'MILILITROS', 'GRAMA', 'GRAMAS', 'QUILO', 'QUILOS'].includes(unidade);
            const casasDecimais = permiteDecimal ? 3 : 0;
            
            return parseFloat(quantidade).toLocaleString('pt-BR', {
                minimumFractionDigits: casasDecimais,
                maximumFractionDigits: casasDecimais
            });
        }
        
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
                modal.classList.add('flex');
            } else {
                console.error('Modal de logout não encontrado');
            }
        };
        
        window.closeLogoutModal = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        };
        
        window.logout = function() {
            try {
                window.location.href = '../auth/logout.php';
            } catch (e) {
                console.error('Erro ao fazer logout:', e);
            }
        };

        // Inicializar produtos disponíveis
        window.produtosDisponiveis = <?= json_encode($produtos) ?>;
        
        function editarPacote(id) {
            modoEdicao = true;
            pacoteIdEdicao = id;
            document.getElementById('modal-titulo').textContent = 'Editar Pacote de Alimentos';
            document.getElementById('form-acao').value = 'atualizar';
            document.getElementById('form-pacote-id').value = id;
            document.getElementById('btn-salvar-pacote').textContent = 'Atualizar Pacote';
            
            // Buscar dados do pacote
            fetch(`?acao=buscar_pacote&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const pacote = data.pacote;
                        const itens = data.itens || [];
                        
                        // Preencher formulário
                        document.getElementById('pacote-descricao').value = pacote.descricao || '';
                        document.getElementById('pacote-escola').value = pacote.escola_id || '';
                        document.getElementById('pacote-data-envio').value = pacote.data_envio || '';
                        document.getElementById('pacote-observacoes').value = pacote.observacoes || '';
                        
                        // Carregar itens
                        itensPacote = [];
                        contadorItens = 0;
                        itens.forEach(item => {
                            const itemId = 'item-' + (++contadorItens);
                            // Tentar encontrar o produto correspondente nos produtos disponíveis
                            const produtoEncontrado = produtosDisponiveis.find(p => p.produto_id == item.produto_id);
                            const identificadorUnico = produtoEncontrado ? produtoEncontrado.identificador_unico : `${item.produto_id}:0`;
                            itensPacote.push({
                                id: itemId,
                                identificador_unico: identificadorUnico,
                                produto_id: String(item.produto_id),
                                estoque_id: produtoEncontrado ? String(produtoEncontrado.estoque_id) : '',
                                quantidade: item.quantidade || ''
                            });
                        });
                        
                        // Recarregar estoques e atualizar lista
                        fetch('?acao=buscar_estoques_todos')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    estoquesProdutos = data.estoques;
                                    atualizarListaItens();
                                }
                            })
                            .catch(error => {
                                console.error('Erro ao carregar estoques:', error);
                                atualizarListaItens();
                            });
                        
                        document.getElementById('modal-novo-pacote').classList.remove('hidden');
                    } else {
                        alert('Erro ao carregar dados do pacote');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados do pacote');
                });
        }

        function fecharModalNovoPacote() {
            document.getElementById('modal-novo-pacote').classList.add('hidden');
        }

        function adicionarItem() {
            const itemId = 'item-' + (++contadorItens);
            itensPacote.push({
                id: itemId,
                identificador_unico: '',
                produto_id: '',
                estoque_id: '',
                quantidade: ''
            });
            atualizarListaItens();
        }
        
        function produtoJaAdicionado(identificadorUnico, itemIdExcluir) {
            if (!identificadorUnico) return false;
            return itensPacote.some(item => 
                item.id !== itemIdExcluir && 
                item.identificador_unico && 
                item.identificador_unico == identificadorUnico
            );
        }

        function removerItem(itemId) {
            itensPacote = itensPacote.filter(item => item.id !== itemId);
            atualizarListaItens();
        }

        function atualizarListaItens() {
            const listaItens = document.getElementById('lista-itens');
            const mensagemSemItens = document.getElementById('mensagem-sem-itens');
            
            if (itensPacote.length === 0) {
                listaItens.innerHTML = '';
                mensagemSemItens.classList.remove('hidden');
                return;
            }
            
            mensagemSemItens.classList.add('hidden');
            listaItens.innerHTML = '';
            
            itensPacote.forEach((item, index) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'flex items-center space-x-3 p-4 bg-gray-50 rounded-lg border border-gray-200';
                
                // Identificadores únicos já selecionados em outros itens (exceto o item atual)
                const outrosIdentificadoresSelecionados = itensPacote
                    .filter(i => i.id !== item.id && i.identificador_unico)
                    .map(i => String(i.identificador_unico));
                
                itemDiv.innerHTML = `
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Produto *</label>
                            <select id="select-produto-${item.id}" onchange="selecionarProduto('${item.id}', this.value)" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                <option value="">Selecione...</option>
                                ${produtosDisponiveis.map(p => {
                                    const identificadorAtual = item.identificador_unico == p.identificador_unico;
                                    const jaSelecionado = outrosIdentificadoresSelecionados.includes(String(p.identificador_unico));
                                    const estoque = parseFloat(p.estoque_quantidade) || 0;
                                    const semEstoque = estoque <= 0;
                                    const desabilitado = jaSelecionado || semEstoque;
                                    let motivo = '';
                                    if (jaSelecionado) motivo = ' - Já adicionado';
                                    else if (semEstoque) motivo = ' - Sem estoque';
                                    
                                    // Formatar validade para exibição
                                    let textoValidade = '';
                                    if (p.validade) {
                                        const dataValidade = new Date(p.validade);
                                        textoValidade = ` - Val: ${dataValidade.toLocaleDateString('pt-BR')}`;
                                    }
                                    
                                    return `<option value="${p.identificador_unico}" ${identificadorAtual ? 'selected' : ''} ${desabilitado ? 'disabled' : ''} data-produto-id="${p.produto_id}" data-estoque-id="${p.estoque_id}" data-estoque-quantidade="${p.estoque_quantidade}" data-validade="${p.validade || ''}">${p.nome} (${p.unidade_medida})${textoValidade}${motivo}</option>`;
                                }).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Quantidade *</label>
                            <input type="number" step="0.001" min="0" 
                                   id="input-quantidade-${item.id}"
                                   value="${item.quantidade}"
                                   onchange="atualizarItem('${item.id}', 'quantidade', this.value)"
                                   placeholder="0.000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            <div id="info-estoque-${item.id}" class="mt-1 text-xs"></div>
                        </div>
                    </div>
                    <button type="button" onclick="removerItem('${item.id}')" 
                            class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                `;
                listaItens.appendChild(itemDiv);
                
                // Se já tiver produto selecionado, buscar estoque
                if (item.identificador_unico) {
                    selecionarProduto(item.id, item.identificador_unico);
                }
            });
        }
        
        function selecionarProduto(itemId, identificadorUnico) {
            if (!identificadorUnico) {
                atualizarItem(itemId, 'identificador_unico', '');
                atualizarItem(itemId, 'produto_id', '');
                atualizarItem(itemId, 'estoque_id', '');
                const infoEstoque = document.getElementById(`info-estoque-${itemId}`);
                const inputQuantidade = document.getElementById(`input-quantidade-${itemId}`);
                if (infoEstoque) infoEstoque.innerHTML = '';
                if (inputQuantidade) {
                    inputQuantidade.disabled = false;
                    inputQuantidade.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    inputQuantidade.max = '';
                }
                return;
            }
            
            // Verificar se o lote já foi adicionado
            if (identificadorUnico && produtoJaAdicionado(identificadorUnico, itemId)) {
                alert('Este lote já foi adicionado ao pacote. Não é permitido adicionar o mesmo lote mais de uma vez.');
                const select = document.getElementById(`select-produto-${itemId}`);
                select.value = '';
                atualizarItem(itemId, 'identificador_unico', '');
                atualizarItem(itemId, 'produto_id', '');
                atualizarItem(itemId, 'estoque_id', '');
                return;
            }
            
            // Extrair dados do select
            const select = document.getElementById(`select-produto-${itemId}`);
            const option = select.options[select.selectedIndex];
            const produtoId = option.getAttribute('data-produto-id');
            const estoqueId = option.getAttribute('data-estoque-id');
            const estoqueQuantidade = parseFloat(option.getAttribute('data-estoque-quantidade')) || 0;
            const validade = option.getAttribute('data-validade');
            
            // Atualizar item
            atualizarItem(itemId, 'identificador_unico', identificadorUnico);
            atualizarItem(itemId, 'produto_id', produtoId);
            atualizarItem(itemId, 'estoque_id', estoqueId);
            
            const infoEstoque = document.getElementById(`info-estoque-${itemId}`);
            const inputQuantidade = document.getElementById(`input-quantidade-${itemId}`);
            
            if (estoqueQuantidade > 0) {
                // Buscar unidade de medida do produto selecionado
                const produtoSelecionado = produtosDisponiveis.find(p => p.identificador_unico === identificadorUnico);
                const unidadeProduto = produtoSelecionado ? produtoSelecionado.unidade_medida : '';
                let textoEstoque = `<span class="text-green-600 font-medium">Estoque disponível: ${formatarQuantidade(estoqueQuantidade, unidadeProduto)}`;
                if (validade) {
                    const dataValidade = new Date(validade);
                    const hoje = new Date();
                    hoje.setHours(0, 0, 0, 0);
                    const diasRestantes = Math.ceil((dataValidade - hoje) / (1000 * 60 * 60 * 24));
                    let corValidade = 'text-green-600';
                    if (diasRestantes < 0) corValidade = 'text-red-600';
                    else if (diasRestantes <= 7) corValidade = 'text-yellow-600';
                    textoEstoque += ` | Validade: <span class="${corValidade} font-semibold">${dataValidade.toLocaleDateString('pt-BR')}</span>`;
                }
                textoEstoque += '</span>';
                infoEstoque.innerHTML = textoEstoque;
                inputQuantidade.disabled = false;
                inputQuantidade.classList.remove('bg-gray-100', 'cursor-not-allowed');
                inputQuantidade.max = estoqueQuantidade;
            } else {
                infoEstoque.innerHTML = '<span class="text-red-600">Produto sem estoque disponível</span>';
                inputQuantidade.disabled = true;
                inputQuantidade.classList.add('bg-gray-100', 'cursor-not-allowed');
                inputQuantidade.max = 0;
            }
        }

        function atualizarItem(itemId, campo, valor) {
            const item = itensPacote.find(i => i.id === itemId);
            if (item) {
                item[campo] = valor;
                
                // Validar quantidade se for o campo quantidade
                if (campo === 'quantidade' && item.produto_id) {
                    const inputQuantidade = document.getElementById(`input-quantidade-${itemId}`);
                    const quantidade = parseFloat(valor) || 0;
                    const estoqueMax = parseFloat(inputQuantidade.max) || 0;
                    
                    if (quantidade > estoqueMax && estoqueMax > 0) {
                        const itemAtual = itensPacote.find(i => i.id === itemId);
                        const produtoSelecionado = produtosDisponiveis.find(p => p.identificador_unico === itemAtual.identificador_unico);
                        const unidadeProduto = produtoSelecionado ? produtoSelecionado.unidade_medida : '';
                        alert(`A quantidade não pode ser maior que o estoque disponível (${formatarQuantidade(estoqueMax, unidadeProduto)})`);
                        inputQuantidade.value = estoqueMax;
                        item.quantidade = estoqueMax;
                    }
                }
            }
        }

        function salvarPacote() {
            // Validar formulário
            const escolaId = document.getElementById('pacote-escola').value;
            
            if (!escolaId) {
                alert('Por favor, selecione uma escola');
                return;
            }
            
            // Validar itens
            const itensValidos = itensPacote.filter(item => item.identificador_unico && item.produto_id && item.quantidade && parseFloat(item.quantidade) > 0);
            
            if (itensValidos.length === 0) {
                alert('Por favor, adicione pelo menos um item ao pacote');
                return;
            }
            
            // Verificar lotes duplicados
            const identificadoresUnicos = [];
            for (const item of itensValidos) {
                if (identificadoresUnicos.includes(item.identificador_unico)) {
                    alert('Não é permitido adicionar o mesmo lote mais de uma vez');
                    return;
                }
                identificadoresUnicos.push(item.identificador_unico);
            }
            
            // Preparar dados
            const formData = new FormData();
            const acao = document.getElementById('form-acao').value;
            formData.append('acao', acao);
            
            if (acao === 'atualizar') {
                const pacoteId = document.getElementById('form-pacote-id').value;
                if (!pacoteId) {
                    alert('Erro: ID do pacote não encontrado');
                    return;
                }
                formData.append('id', pacoteId);
            }
            
            formData.append('descricao', document.getElementById('pacote-descricao').value);
            formData.append('escola_id', escolaId);
            formData.append('data_envio', document.getElementById('pacote-data-envio').value);
            formData.append('observacoes', document.getElementById('pacote-observacoes').value);
            formData.append('itens', JSON.stringify(itensValidos));
            
            // Enviar
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(data => {
                if (data) {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar pacote');
            });
        }

        function verDetalhes(id) {
            fetch(`?acao=buscar_pacote&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const pacote = data.pacote;
                        const itens = data.itens;
                        
                        let html = `
                            <div class="space-y-6">
                                <div class="bg-gradient-to-r from-primary-green to-green-700 rounded-lg p-6 text-white">
                                    <h4 class="text-xl font-bold mb-4">Informações do Pacote</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-white bg-opacity-10 rounded-lg p-4 backdrop-blur-sm">
                                            <p class="text-xs font-medium text-green-100 mb-1 uppercase tracking-wide">Escola</p>
                                            <p class="text-lg font-bold">${pacote.escola_nome || '-'}</p>
                                        </div>
                                        <div class="bg-white bg-opacity-10 rounded-lg p-4 backdrop-blur-sm">
                                            <p class="text-xs font-medium text-green-100 mb-1 uppercase tracking-wide">Data de Envio</p>
                                            <p class="text-lg font-bold">${pacote.data_envio ? new Date(pacote.data_envio).toLocaleDateString('pt-BR') : '-'}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                ${pacote.descricao ? `
                                    <div class="bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-4">
                                        <p class="text-sm font-semibold text-blue-800 mb-2 flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Descrição
                                        </p>
                                        <p class="text-gray-700 leading-relaxed">${pacote.descricao}</p>
                                    </div>
                                ` : ''}
                                
                                ${pacote.observacoes ? `
                                    <div class="bg-amber-50 border-l-4 border-amber-500 rounded-r-lg p-4">
                                        <p class="text-sm font-semibold text-amber-800 mb-2 flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Observações
                                        </p>
                                        <p class="text-gray-700 leading-relaxed">${pacote.observacoes}</p>
                                    </div>
                                ` : ''}
                                
                                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-lg font-bold text-gray-800 flex items-center">
                                                <svg class="w-6 h-6 mr-2 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                                Itens do Pacote
                                            </h5>
                                            <span class="bg-primary-green text-white px-4 py-1 rounded-full text-sm font-semibold">
                                                ${itens.length} ${itens.length === 1 ? 'item' : 'itens'}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full">
                                            <thead>
                                                <tr class="bg-gray-50 border-b-2 border-gray-200">
                                                    <th class="text-left py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">
                                                        <div class="flex items-center">
                                                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                            </svg>
                                                            Produto
                                                        </div>
                                                    </th>
                                                    <th class="text-center py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">
                                                        <div class="flex items-center justify-center">
                                                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-5m-6 5v-5m6 5h-3m-3 0h3m-3-10h3m-3 0H9m0 0V7m0 10v-5"></path>
                                                            </svg>
                                                            Quantidade
                                                        </div>
                                                    </th>
                                                    <th class="text-center py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">Unidade</th>
                                                    <th class="text-center py-4 px-6 font-bold text-gray-700 text-sm uppercase tracking-wider">
                                                        <div class="flex items-center justify-center">
                                                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            Validade
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                ${itens.map((item, index) => `
                                                    <tr class="hover:bg-gray-50 transition-colors duration-150 ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
                                                        <td class="py-4 px-6">
                                                            <div class="flex items-center">
                                                                <div class="flex-shrink-0 w-10 h-10 bg-primary-green bg-opacity-10 rounded-lg flex items-center justify-center mr-3">
                                                                    <svg class="w-5 h-5 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                                    </svg>
                                                                </div>
                                                                <span class="font-medium text-gray-900">${item.produto_nome || '-'}</span>
                                                            </div>
                                                        </td>
                                                        <td class="py-4 px-6 text-center">
                                                            <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-semibold text-sm">
                                                                ${formatarQuantidade(item.quantidade || 0, item.unidade_medida)}
                                                            </span>
                                                        </td>
                                                        <td class="py-4 px-6 text-center">
                                                            <span class="text-gray-600 font-medium">${item.unidade_medida || '-'}</span>
                                                        </td>
                                                        <td class="py-4 px-6 text-center">
                                                            ${item.validade_proxima ? (() => {
                                                                const validade = new Date(item.validade_proxima);
                                                                const hoje = new Date();
                                                                hoje.setHours(0, 0, 0, 0);
                                                                const diasRestantes = Math.ceil((validade - hoje) / (1000 * 60 * 60 * 24));
                                                                let cor = 'bg-green-100 text-green-800';
                                                                let icone = '';
                                                                if (diasRestantes < 0) {
                                                                    cor = 'bg-red-100 text-red-800';
                                                                    icone = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                                                                } else if (diasRestantes <= 7) {
                                                                    cor = 'bg-yellow-100 text-yellow-800';
                                                                    icone = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                                                } else {
                                                                    icone = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                                                }
                                                                return `<span class="inline-flex items-center px-3 py-1 ${cor} rounded-lg font-semibold text-sm">${icone}${validade.toLocaleDateString('pt-BR')}</span>`;
                                                            })() : '<span class="text-gray-400 italic">Não informada</span>'}
                                                        </td>
                                                    </tr>
                                                `).join('')}
                                                ${itens.length === 0 ? `
                                                    <tr>
                                                        <td colspan="4" class="py-12 text-center">
                                                            <div class="flex flex-col items-center justify-center text-gray-400">
                                                                <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                                </svg>
                                                                <p class="text-lg font-medium">Nenhum item encontrado</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ` : ''}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('conteudo-detalhes').innerHTML = html;
                        document.getElementById('modal-detalhes').classList.remove('hidden');
                        document.getElementById('modal-detalhes').style.display = 'flex';
                    } else {
                        alert('Erro ao carregar detalhes do pacote');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar detalhes');
                });
        }

        function fecharModalDetalhes() {
            document.getElementById('modal-detalhes').classList.add('hidden');
            document.getElementById('modal-detalhes').style.display = 'none';
        }
    </script>
    
    <?php include(__DIR__ . '/components/logout_modal.php'); ?>
</body>
</html>


