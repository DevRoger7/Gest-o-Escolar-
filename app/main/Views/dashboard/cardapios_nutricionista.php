<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');
require_once('../../Models/merenda/CardapioModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é NUTRICIONISTA
if (!eNutricionista() && !eAdm()) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$cardapioModel = new CardapioModel();

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'criar_cardapio') {
        $dados = [
            'escola_id' => $_POST['escola_id'] ?? null,
            'mes' => $_POST['mes'] ?? date('m'),
            'ano' => $_POST['ano'] ?? date('Y'),
            'itens' => json_decode($_POST['itens'] ?? '[]', true),
            'criado_por' => $_SESSION['usuario_id'] ?? null,
            'status' => $_POST['status'] ?? 'PUBLICADO' // PUBLICADO por padrão, ou RASCUNHO se especificado
        ];
        
        if ($dados['escola_id']) {
            $resultado = $cardapioModel->criar($dados);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'editar_cardapio' && !empty($_POST['cardapio_id'])) {
        $dados = [
            'escola_id' => $_POST['escola_id'] ?? null,
            'mes' => $_POST['mes'] ?? date('m'),
            'ano' => $_POST['ano'] ?? date('Y'),
            'itens' => json_decode($_POST['itens'] ?? '[]', true)
        ];
        
        $resultado = $cardapioModel->atualizar($_POST['cardapio_id'], $dados);
        echo json_encode($resultado);
        exit;
    }
    
    if ($_POST['acao'] === 'enviar_cardapio' && !empty($_POST['cardapio_id'])) {
        $resultado = $cardapioModel->enviar($_POST['cardapio_id']);
        echo json_encode($resultado);
        exit;
    }
    
    if ($_POST['acao'] === 'excluir_cardapio' && !empty($_POST['cardapio_id'])) {
        $resultado = $cardapioModel->excluir($_POST['cardapio_id']);
        echo json_encode($resultado);
        exit;
    }
    
    if ($_POST['acao'] === 'cancelar_envio' && !empty($_POST['cardapio_id'])) {
        $resultado = $cardapioModel->cancelarEnvio($_POST['cardapio_id']);
        echo json_encode($resultado);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_cardapios') {
        $filtros = [];
        // Mostrar TODOS os cardápios (removido filtro criado_por)
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
    
    // Endpoint para buscar produtos do estoque da escola selecionada
    if ($_GET['acao'] === 'buscar_produtos_estoque') {
        $escolaId = $_GET['escola_id'] ?? $_SESSION['escola_selecionada_nutricionista_id'] ?? null;
        
        if (!$escolaId) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma escola selecionada', 'produtos' => []]);
            exit;
        }
        
        try {
            // Verificar se a coluna estoque_central_id existe
            try {
                $checkColumn = $conn->query("SHOW COLUMNS FROM pacote_escola_item LIKE 'estoque_central_id'");
                $columnExists = $checkColumn->rowCount() > 0;
            } catch (Exception $e) {
                $columnExists = false;
            }
            
            // Buscar produtos que estão no estoque da escola, com seus lotes do estoque_central
            $sql = "SELECT 
                        p.id as produto_id,
                        ec.id as estoque_id,
                        p.nome, 
                        p.unidade_medida,
                        ec.quantidade as estoque_quantidade,
                        ec.validade,
                        COALESCE(ec.lote, 'Sem lote') as lote,
                        CONCAT(p.id, ':', ec.id) as identificador_unico
                    FROM produto p
                    INNER JOIN pacote_escola_item pei ON p.id = pei.produto_id
                    INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                    INNER JOIN estoque_central ec ON p.id = ec.produto_id AND pei.estoque_central_id = ec.id
                    WHERE p.ativo = 1 
                    AND pe.escola_id = :escola_id
                    AND ec.quantidade > 0
                    GROUP BY p.id, ec.id, p.nome, p.unidade_medida, ec.quantidade, ec.validade, ec.lote
                    ORDER BY p.nome ASC, ec.validade ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->execute();
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatar validade para exibição
            foreach ($produtos as &$produto) {
                if (!empty($produto['validade']) && $produto['validade'] !== '0000-00-00' && $produto['validade'] !== '0000-00-00 00:00:00') {
                    try {
                        $dataValidade = new DateTime($produto['validade']);
                        $produto['validade_formatada'] = $dataValidade->format('d/m/Y');
                    } catch (Exception $e) {
                        $produto['validade_formatada'] = null;
                    }
                } else {
                    $produto['validade_formatada'] = null;
                }
            }
            
            echo json_encode(['success' => true, 'produtos' => $produtos]);
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos do estoque: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar produtos', 'produtos' => []]);
        }
        exit;
    }
}

// Buscar escola selecionada da sessão (selecionada no dashboard)
$escolaId = null;
$escolaNome = null;

// Verificar se há escola selecionada na sessão
if (isset($_SESSION['escola_selecionada_nutricionista_id']) && !empty($_SESSION['escola_selecionada_nutricionista_id'])) {
    $escolaId = (int)$_SESSION['escola_selecionada_nutricionista_id'];
    $escolaNome = $_SESSION['escola_selecionada_nutricionista_nome'] ?? 'Escola não encontrada';
    
    // Verificar se a escola ainda existe no banco
    try {
        $sql = "SELECT id, nome FROM escola WHERE id = :escola_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
        $stmt->execute();
        $escola = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($escola) {
            $escolaNome = $escola['nome'];
        } else {
            // Escola não encontrada, limpar sessão
            unset($_SESSION['escola_selecionada_nutricionista_id']);
            unset($_SESSION['escola_selecionada_nutricionista_nome']);
            $escolaId = null;
            $escolaNome = 'Nenhuma escola selecionada';
        }
    } catch (Exception $e) {
        error_log("Erro ao verificar escola: " . $e->getMessage());
    }
} else {
    // Se não há escola selecionada, buscar a primeira ativa
    try {
        $sql = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC LIMIT 1";
        $stmt = $conn->query($sql);
        $escola = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($escola) {
            $escolaId = (int)$escola['id'];
            $escolaNome = $escola['nome'];
            
            // Salvar na sessão
            $_SESSION['escola_selecionada_nutricionista_id'] = $escolaId;
            $_SESSION['escola_selecionada_nutricionista_nome'] = $escolaNome;
        } else {
            $escolaNome = 'Nenhuma escola disponível';
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar primeira escola: " . $e->getMessage());
        $escolaNome = 'Erro ao carregar escola';
    }
}

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos para cardápio - apenas os que estão no estoque da escola selecionada
$escolaSelecionadaId = $_SESSION['escola_selecionada_nutricionista_id'] ?? null;
$produtos = [];

if ($escolaSelecionadaId) {
    // Buscar produtos que têm estoque na escola selecionada
    $sqlProdutos = "SELECT DISTINCT 
                        p.id, 
                        p.nome, 
                        p.unidade_medida,
                        COALESCE(SUM(pei.quantidade), 0) as quantidade_estoque
                    FROM produto p
                    INNER JOIN pacote_escola_item pei ON p.id = pei.produto_id
                    INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                    WHERE p.ativo = 1 
                    AND pe.escola_id = :escola_id
                    GROUP BY p.id, p.nome, p.unidade_medida
                    HAVING quantidade_estoque > 0
                    ORDER BY p.nome ASC";
    
    try {
        $stmtProdutos = $conn->prepare($sqlProdutos);
        $stmtProdutos->bindParam(':escola_id', $escolaSelecionadaId, PDO::PARAM_INT);
        $stmtProdutos->execute();
        $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao buscar produtos do estoque: " . $e->getMessage());
        $produtos = [];
    }
} else {
    // Se não há escola selecionada, não mostrar produtos
    error_log("AVISO - Nenhuma escola selecionada para buscar produtos do estoque");
    $produtos = [];
}

// Buscar cardápios para exibição inicial - TODOS os cardápios
$filtrosInicial = [
    'ano' => date('Y')
];
$cardapios = $cardapioModel->listar($filtrosInicial);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Cardápios Nutricionais') ?></title>
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
            background: linear-gradient(90deg, rgba(236, 72, 153, 0.12) 0%, rgba(236, 72, 153, 0.06) 100%);
            border-right: 3px solid #ec4899;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(236, 72, 153, 0.08) 0%, rgba(236, 72, 153, 0.04) 100%);
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
    <?php include 'components/sidebar_nutricionista.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Cardápios Nutricionais</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Mostrar apenas o nome da escola selecionada (sem dropdown) -->
                        <div class="bg-primary-green text-white px-5 py-2.5 rounded-lg shadow-md text-sm font-semibold">
                            <span><?= htmlspecialchars($escolaNome ?? 'Nenhuma escola selecionada') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Gestão de Cardápios</h2>
                        <p class="text-gray-600 mt-1">Crie e gerencie cardápios conforme normas nutricionais</p>
                    </div>
                    <?php if (isset($_SESSION['adc_cardapio'])) { ?>
                    <button onclick="abrirModalNovoCardapio()" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Cardápio</span>
                    </button>
                    <?php } ?>
                </div>
                
                <!-- Informações sobre Normas Nutricionais -->
                <div class="bg-gradient-to-r from-pink-50 to-purple-50 rounded-2xl p-6 shadow-lg mb-6 border border-pink-200">
                    <div class="flex items-start space-x-4">
                        <div class="p-3 bg-pink-100 rounded-xl">
                            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Normas Nutricionais</h3>
                            <p class="text-sm text-gray-600 mb-2">Ao criar cardápios, certifique-se de seguir as diretrizes do PNAE (Programa Nacional de Alimentação Escolar):</p>
                            <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                <li>Variedade de alimentos ao longo da semana</li>
                                <li>Valor nutricional adequado para cada faixa etária</li>
                                <li>Respeito à sazonalidade dos alimentos</li>
                                <li>Consideração de necessidades especiais (alergias, restrições)</li>
                            </ul>
                        </div>
                    </div>
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
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano</label>
                            <select id="filtro-ano" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="<?= date('Y') - 1 ?>"><?= date('Y') - 1 ?></option>
                                <option value="<?= date('Y') ?>" selected><?= date('Y') ?></option>
                                <option value="<?= date('Y') + 1 ?>"><?= date('Y') + 1 ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="filtro-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="">Todos</option>
                                <option value="RASCUNHO">Rascunho</option>
                                <option value="APROVADO">Aprovado</option>
                                <option value="PUBLICADO">Publicado</option>
                                <option value="REJEITADO">Rejeitado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Cardápios -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Itens</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-cardapios" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        Carregando cardápios...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Novo Cardápio -->
    <div id="modal-novo-cardapio" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="bg-primary-green text-white p-6 flex items-center justify-between rounded-t-2xl">
                <h3 class="text-2xl font-bold" id="modal-titulo">Novo Cardápio</h3>
                <button onclick="fecharModalNovoCardapio()" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="space-y-6">
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações do Cardápio</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                                <select id="cardapio-escola-id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" onchange="atualizarProdutosEstoque(this.value)">
                                    <option value="">Selecione uma escola</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <option value="<?= $escola['id'] ?>" <?= ($escolaId == $escola['id']) ? 'selected' : '' ?>><?= htmlspecialchars($escola['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mês *</label>
                                <select id="cardapio-mes" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <?php 
                                    $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                                    for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == date('m') ? 'selected' : '' ?>><?= $meses[$i - 1] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ano *</label>
                                <select id="cardapio-ano" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <?php for ($i = date('Y'); $i <= date('Y') + 1; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">Itens do Cardápio</h4>
                            <button type="button" onclick="adicionarItemCardapio()" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Adicionar Item</span>
                            </button>
                        </div>
                        
                        <div id="itens-cardapio-container" class="space-y-3">
                            <!-- Itens serão adicionados aqui -->
                        </div>
                        
                        <div id="mensagem-sem-itens" class="text-center py-8 text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p>Nenhum item adicionado ainda. Clique em "Adicionar Item" para começar.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 p-6 flex justify-end space-x-3 rounded-b-2xl border-t border-gray-200">
                <button onclick="fecharModalNovoCardapio()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                    Cancelar
                </button>
                <button id="btn-salvar-rascunho" onclick="salvarCardapio(true)" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Salvar como Rascunho
                </button>
                <button id="btn-publicar-cardapio" onclick="salvarCardapio(false)" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors">
                    Publicar Cardápio
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Produtos disponíveis no estoque da escola selecionada
        let produtosDisponiveis = <?= json_encode($produtos) ?>;
        let produtos = produtosDisponiveis; // Compatibilidade
        let itemIndex = 0;
        let itensCardapio = [];
        let cardapioEditandoId = null;
        
        // Função para atualizar produtos quando a escola for selecionada
        function atualizarProdutosEstoque(escolaId) {
            const escolaIdParam = escolaId || document.getElementById('cardapio-escola-id')?.value || '<?= $escolaId ?? '' ?>';
            
            if (!escolaIdParam) {
                produtosDisponiveis = [];
                produtos = [];
                atualizarSelectsProduto();
                return;
            }
            
            fetch(`cardapios_nutricionista.php?acao=buscar_produtos_estoque&escola_id=${escolaIdParam}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        produtosDisponiveis = data.produtos;
                        produtos = produtosDisponiveis;
                        atualizarSelectsProduto();
                        console.log('Produtos atualizados:', produtosDisponiveis.length, 'produtos disponíveis no estoque');
                    } else {
                        console.warn('Aviso:', data.message);
                        produtosDisponiveis = [];
                        produtos = [];
                        atualizarSelectsProduto();
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar produtos:', error);
                    produtosDisponiveis = [];
                    produtos = [];
                    atualizarSelectsProduto();
                });
        }
        
        // Função para formatar quantidade baseado na unidade de medida
        function formatarQuantidade(quantidade, unidadeMedida) {
            if (!quantidade && quantidade !== 0) return '0';
            
            const unidade = (unidadeMedida || '').toUpperCase().trim();
            const permiteDecimal = ['ML', 'L', 'G', 'KG', 'LT', 'LITRO', 'LITROS', 'MILILITRO', 'MILILITROS', 'GRAMA', 'GRAMAS', 'QUILO', 'QUILOS'].includes(unidade);
            const casasDecimais = permiteDecimal ? 3 : 0;
            
            return parseFloat(quantidade).toLocaleString('pt-BR', {
                minimumFractionDigits: casasDecimais,
                maximumFractionDigits: casasDecimais
            });
        }
        
        function produtoJaAdicionado(identificadorUnico, itemIdExcluir) {
            if (!identificadorUnico) return false;
            return itensCardapio.some(item => 
                item.id !== itemIdExcluir && 
                item.identificador_unico && 
                item.identificador_unico == identificadorUnico
            );
        }
        
        function atualizarSelectsProduto() {
            document.querySelectorAll('.produto-select').forEach(select => {
                const valorAtual = select.value;
                const itemIndex = select.dataset.itemIndex;
                const itemAtual = itensCardapio.find(i => i.id === `item-${itemIndex}`);
                
                const outrosIdentificadoresSelecionados = itensCardapio
                    .filter(i => i.id !== `item-${itemIndex}` && i.identificador_unico)
                    .map(i => String(i.identificador_unico));
                
                select.innerHTML = '<option value="">Selecione um produto</option>';
                produtosDisponiveis.forEach(p => {
                    const identificadorUnico = p.identificador_unico || `${p.produto_id}:${p.estoque_id || 0}`;
                    const identificadorAtual = itemAtual && itemAtual.identificador_unico == identificadorUnico;
                    const jaSelecionado = outrosIdentificadoresSelecionados.includes(String(identificadorUnico));
                    const estoque = parseFloat(p.estoque_quantidade || p.quantidade_estoque || 0);
                    const semEstoque = estoque <= 0;
                    const desabilitado = jaSelecionado || semEstoque;
                    let motivo = '';
                    if (jaSelecionado) motivo = ' - Já adicionado';
                    else if (semEstoque) motivo = ' - Sem estoque';
                    
                    const option = document.createElement('option');
                    option.value = identificadorUnico;
                    option.disabled = desabilitado;
                    if (identificadorAtual) option.selected = true;
                    
                    if (estoque > 0) {
                        option.setAttribute('data-estoque', estoque);
                    }
                    option.setAttribute('data-produto-id', p.produto_id);
                    option.setAttribute('data-estoque-id', p.estoque_id);
                    
                    let textoOption = `${p.nome} (${p.unidade_medida})`;
                    if (estoque > 0) {
                        const qtdFormatada = formatarQuantidade(estoque, p.unidade_medida);
                        textoOption += ` - Estoque: ${qtdFormatada}`;
                    }
                    if (p.validade_formatada) {
                        textoOption += ` - Validade: ${p.validade_formatada}`;
                    }
                    textoOption += motivo;
                    option.textContent = textoOption;
                    select.appendChild(option);
                });
                
                if (valorAtual && itemAtual && itemAtual.identificador_unico) {
                    atualizarMaxQuantidade(itemIndex);
                }
            });
        }
        
        function selecionarProdutoCardapio(itemIndex, identificadorUnico) {
            const itemId = `item-${itemIndex}`;
            const itemAtual = itensCardapio.find(i => i.id === itemId);
            
            if (!identificadorUnico) {
                if (itemAtual) {
                    itemAtual.identificador_unico = '';
                    itemAtual.produto_id = '';
                    itemAtual.estoque_id = '';
                }
                atualizarSelectsProduto();
                return;
            }
            
            if (produtoJaAdicionado(identificadorUnico, itemId)) {
                alert('Este produto já foi adicionado ao cardápio. Você pode adicionar o mesmo produto de um lote diferente.');
                const select = document.querySelector(`.produto-select[data-item-index="${itemIndex}"]`);
                if (select && itemAtual) {
                    select.value = itemAtual.identificador_unico || '';
                }
                return;
            }
            
            const produtoSelecionado = produtosDisponiveis.find(p => {
                const idUnico = p.identificador_unico || `${p.produto_id}:${p.estoque_id || 0}`;
                return idUnico === identificadorUnico;
            });
            
            if (!produtoSelecionado) return;
            
            if (!itemAtual) {
                itensCardapio.push({
                    id: itemId,
                    identificador_unico: identificadorUnico,
                    produto_id: produtoSelecionado.produto_id || produtoSelecionado.id,
                    estoque_id: produtoSelecionado.estoque_id || 0,
                    quantidade: ''
                });
            } else {
                itemAtual.identificador_unico = identificadorUnico;
                itemAtual.produto_id = produtoSelecionado.produto_id || produtoSelecionado.id;
                itemAtual.estoque_id = produtoSelecionado.estoque_id || 0;
            }
            
            atualizarSelectsProduto();
            atualizarMaxQuantidade(itemIndex);
        }
        
        function atualizarMaxQuantidade(itemIndex) {
            const quantidadeInput = document.querySelector(`.quantidade-input[data-item-index="${itemIndex}"]`);
            const produtoSelect = document.querySelector(`.produto-select[data-item-index="${itemIndex}"]`);
            
            if (!quantidadeInput || !produtoSelect) return;
            
            const optionSelecionada = produtoSelect.options[produtoSelect.selectedIndex];
            const estoqueDisponivel = optionSelecionada ? parseFloat(optionSelecionada.getAttribute('data-estoque') || 0) : 0;
            
            if (estoqueDisponivel > 0) {
                quantidadeInput.setAttribute('max', estoqueDisponivel);
                quantidadeInput.setAttribute('data-estoque-max', estoqueDisponivel);
                
                const valorAtual = parseFloat(quantidadeInput.value || 0);
                if (valorAtual > estoqueDisponivel) {
                    quantidadeInput.value = estoqueDisponivel;
                }
            } else {
                quantidadeInput.removeAttribute('max');
                quantidadeInput.removeAttribute('data-estoque-max');
            }
        }
        
        function adicionarItemCardapio() {
            const container = document.getElementById('itens-cardapio-container');
            const mensagemSemItens = document.getElementById('mensagem-sem-itens');
            
            if (mensagemSemItens) {
                mensagemSemItens.classList.add('hidden');
            }
            
            const itemId = `item-${itemIndex}`;
            
            itensCardapio.push({
                id: itemId,
                identificador_unico: '',
                produto_id: '',
                estoque_id: '',
                quantidade: ''
            });
            
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-3 p-4 bg-gray-50 rounded-lg border border-gray-200';
            div.id = itemId;
            
            const outrosIdentificadoresSelecionados = itensCardapio
                .filter(i => i.id !== itemId && i.identificador_unico)
                .map(i => String(i.identificador_unico));
            
            const optionsHtml = produtosDisponiveis.map(p => {
                const identificadorUnico = p.identificador_unico || `${p.produto_id || p.id}:${p.estoque_id || 0}`;
                const jaSelecionado = outrosIdentificadoresSelecionados.includes(String(identificadorUnico));
                const estoque = parseFloat(p.estoque_quantidade || p.quantidade_estoque || 0);
                const semEstoque = estoque <= 0;
                const desabilitado = jaSelecionado || semEstoque;
                let motivo = '';
                if (jaSelecionado) motivo = ' - Já adicionado';
                else if (semEstoque) motivo = ' - Sem estoque';
                
                let textoOption = `${p.nome} (${p.unidade_medida})`;
                let dataEstoque = '';
                
                if (estoque > 0) {
                    const qtdFormatada = formatarQuantidade(estoque, p.unidade_medida);
                    textoOption += ` - Estoque: ${qtdFormatada}`;
                    dataEstoque = `data-estoque="${estoque}"`;
                }
                
                if (p.validade_formatada) {
                    textoOption += ` - Validade: ${p.validade_formatada}`;
                }
                
                textoOption += motivo;
                
                return `<option value="${identificadorUnico}" ${dataEstoque} data-produto-id="${p.produto_id || p.id}" data-estoque-id="${p.estoque_id || 0}" ${desabilitado ? 'disabled' : ''}>${textoOption}</option>`;
            }).join('');
            
            div.innerHTML = `
                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Produto *</label>
                        <select class="produto-select w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-green focus:border-transparent" data-item-index="${itemIndex}" onchange="selecionarProdutoCardapio('${itemIndex}', this.value)">
                            <option value="">Selecione...</option>
                            ${optionsHtml}
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Quantidade *</label>
                        <input type="number" step="0.001" min="0" class="quantidade-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-green focus:border-transparent" placeholder="Quantidade" data-item-index="${itemIndex}" onchange="validarQuantidadeEstoque(this)" oninput="validarQuantidadeEstoque(this)">
                    </div>
                </div>
                <button type="button" onclick="removerItemCardapio(${itemIndex})" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm" title="Remover item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            `;
            container.appendChild(div);
            itemIndex++;
        }
        
        function validarQuantidadeEstoque(input) {
            const itemIndex = input.dataset.itemIndex;
            const itemId = `item-${itemIndex}`;
            const quantidade = parseFloat(input.value || 0);
            const estoqueMax = parseFloat(input.getAttribute('data-estoque-max') || 0);
            
            const itemAtual = itensCardapio.find(i => i.id === itemId);
            if (itemAtual) {
                itemAtual.quantidade = quantidade;
            }
            
            if (estoqueMax > 0 && quantidade > estoqueMax) {
                input.value = estoqueMax;
                if (itemAtual) {
                    itemAtual.quantidade = estoqueMax;
                }
                alert(`A quantidade não pode ser maior que o estoque disponível (${formatarQuantidade(estoqueMax, '')}).`);
                input.focus();
            }
        }
        
        function removerItemCardapio(index) {
            const itemId = `item-${index}`;
            const item = document.getElementById(itemId);
            if (item) {
                item.remove();
            }
            
            itensCardapio = itensCardapio.filter(i => i.id !== itemId);
            atualizarSelectsProduto();
            
            const container = document.getElementById('itens-cardapio-container');
            const mensagemSemItens = document.getElementById('mensagem-sem-itens');
            if (container && mensagemSemItens && container.children.length === 0) {
                mensagemSemItens.classList.remove('hidden');
            }
        }
        
        function salvarCardapio(comoRascunho = false) {
            const escolaId = document.getElementById('cardapio-escola-id').value;
            const mes = document.getElementById('cardapio-mes').value;
            const ano = document.getElementById('cardapio-ano').value;
            
            if (!escolaId || !mes || !ano) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            const itens = [];
            let erroValidacao = false;
            let mensagemErro = '';
            
            const identificadoresUnicosVerificados = new Set();
            for (const item of itensCardapio) {
                const quantidadeInput = document.querySelector(`.quantidade-input[data-item-index="${item.id.replace('item-', '')}"]`);
                const quantidade = quantidadeInput ? parseFloat(quantidadeInput.value || 0) : 0;

                if (!item.identificador_unico || !item.produto_id) {
                    if (!comoRascunho) {
                        erroValidacao = true;
                        mensagemErro = 'Por favor, selecione um produto para todos os itens.';
                        break;
                    }
                    continue;
                }

                if (quantidade <= 0) {
                    if (!comoRascunho) {
                        erroValidacao = true;
                        mensagemErro = 'Por favor, informe uma quantidade válida para todos os itens.';
                        break;
                    }
                    continue;
                }

                if (identificadoresUnicosVerificados.has(item.identificador_unico)) {
                    erroValidacao = true;
                    mensagemErro = 'Há produtos duplicados no cardápio. Por favor, remova as duplicatas ou selecione lotes diferentes.';
                    break;
                }
                identificadoresUnicosVerificados.add(item.identificador_unico);

                const estoqueMax = quantidadeInput ? parseFloat(quantidadeInput.getAttribute('data-estoque-max') || 0) : 0;
                if (estoqueMax > 0 && quantidade > estoqueMax) {
                    erroValidacao = true;
                    const produtoSelecionado = produtosDisponiveis.find(p => p.identificador_unico === item.identificador_unico);
                    const nomeProduto = produtoSelecionado ? produtoSelecionado.nome : 'Produto';
                    mensagemErro = `A quantidade de "${nomeProduto}" (${formatarQuantidade(quantidade, '')}) excede o estoque disponível (${formatarQuantidade(estoqueMax, '')}).`;
                    break;
                }
                
                itens.push({
                    produto_id: item.produto_id,
                    estoque_id: item.estoque_id,
                    quantidade: quantidade
                });
            }
            
            if (erroValidacao) {
                alert(mensagemErro);
                return;
            }

            if (itens.length === 0 && !comoRascunho) {
                alert('Adicione pelo menos um item ao cardápio.');
                return;
            }
            
            const formData = new FormData();
            if (cardapioEditandoId) {
                formData.append('acao', 'editar_cardapio');
                formData.append('cardapio_id', cardapioEditandoId);
            } else {
                formData.append('acao', 'criar_cardapio');
                // Definir status: RASCUNHO se comoRascunho, senão PUBLICADO
                formData.append('status', comoRascunho ? 'RASCUNHO' : 'PUBLICADO');
            }
            formData.append('escola_id', escolaId);
            formData.append('mes', mes);
            formData.append('ano', ano);
            formData.append('itens', JSON.stringify(itens));
            
            fetch('cardapios_nutricionista.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const mensagem = cardapioEditandoId 
                        ? 'Cardápio atualizado com sucesso!' 
                        : (comoRascunho ? 'Cardápio salvo como rascunho com sucesso!' : 'Cardápio criado e publicado com sucesso!');
                    alert(mensagem);
                    fecharModalNovoCardapio();
                    filtrarCardapios();
                } else {
                    alert('Erro ao salvar cardápio: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar cardápio.');
            });
        }
        
        // Atualizar produtos ao carregar a página se não houver produtos
        if (produtosDisponiveis.length === 0) {
            atualizarProdutosEstoque();
        }
        
        function filtrarCardapios() {
            const escolaId = document.getElementById('filtro-escola').value;
            const mes = document.getElementById('filtro-mes').value;
            const ano = document.getElementById('filtro-ano').value;
            const status = document.getElementById('filtro-status').value;
            
            const params = new URLSearchParams();
            if (escolaId) params.append('escola_id', escolaId);
            if (mes) params.append('mes', mes);
            if (ano) params.append('ano', ano);
            if (status) params.append('status', status);
            params.append('acao', 'listar_cardapios');
            
            fetch('cardapios_nutricionista.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderizarCardapios(data.cardapios);
                    }
                })
                .catch(error => console.error('Erro:', error));
        }
        
        function renderizarCardapios(cardapios) {
            const tbody = document.getElementById('lista-cardapios');
            if (cardapios.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum cardápio encontrado.</td></tr>';
                return;
            }
            
            const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            const usuarioIdLogado = <?= $_SESSION['usuario_id'] ?? 0 ?>;
            
            tbody.innerHTML = cardapios.map(c => {
                const statusCardapio = (c.status || '').toUpperCase();
                const statusClass = {
                    'RASCUNHO': 'bg-yellow-100 text-yellow-800',
                    'APROVADO': 'bg-green-100 text-green-800',
                    'PUBLICADO': 'bg-purple-100 text-purple-800',
                    'REJEITADO': 'bg-red-100 text-red-800'
                }[statusCardapio] || 'bg-gray-100 text-gray-800';
                
                const mesNome = meses[c.mes - 1] || c.mes;
                const criadoPeloUsuario = c.criado_por == usuarioIdLogado;
                const isRascunho = statusCardapio === 'RASCUNHO';
                const isPublicado = statusCardapio === 'PUBLICADO';
                
                let botoesAcoes = `<button onclick="visualizarCardapio(${c.id})" class="text-blue-600 hover:text-blue-900 mr-2">Ver</button>`;
                
                // Botões para rascunhos - TODOS os rascunhos podem ser editados e enviados
                if (isRascunho) {
                    botoesAcoes += `
                        <button onclick="editarCardapio(${c.id})" class="text-green-600 hover:text-green-900 mr-2">Editar</button>
                        <button onclick="enviarCardapio(${c.id})" class="text-blue-600 hover:text-blue-900 mr-2">Publicar</button>
                    `;
                    // Apenas o criador pode excluir
                    if (criadoPeloUsuario) {
                        botoesAcoes += `<button onclick="excluirCardapio(${c.id})" class="text-red-600 hover:text-red-900">Excluir</button>`;
                    }
                }
                
                // Botão para cancelar envio de cardápios publicados criados pelo usuário (volta para rascunho)
                // Permitir cancelar para qualquer cardápio publicado (não apenas os criados pelo usuário)
                if (isPublicado) {
                    botoesAcoes += `<button onclick="cancelarEnvioCardapio(${c.id})" class="text-orange-600 hover:text-orange-900 mr-2">Cancelar Publicação</button>`;
                }
                
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${c.escola_nome || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${mesNome}/${c.ano}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">${c.status || 'RASCUNHO'}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${c.total_itens || 0} itens</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            ${botoesAcoes}
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        function abrirModalNovoCardapio() {
            // Limpar formulário
            document.getElementById('cardapio-escola-id').value = '';
            document.getElementById('cardapio-mes').value = '<?= date('m') ?>';
            document.getElementById('cardapio-ano').value = '<?= date('Y') ?>';
            document.getElementById('itens-cardapio-container').innerHTML = '';
            document.getElementById('mensagem-sem-itens').classList.remove('hidden');
            document.getElementById('modal-titulo').textContent = 'Novo Cardápio';
            itensCardapio = [];
            itemIndex = 0;
            cardapioEditandoId = null;
            
            // Mostrar botão "Publicar Cardápio" quando for novo cardápio
            const btnPublicar = document.getElementById('btn-publicar-cardapio');
            if (btnPublicar) {
                btnPublicar.style.display = 'block';
            }
            
            // Atualizar produtos da escola selecionada
            const escolaId = '<?= $escolaId ?? '' ?>';
            if (escolaId) {
                document.getElementById('cardapio-escola-id').value = escolaId;
                atualizarProdutosEstoque(escolaId);
            }
            
            document.getElementById('modal-novo-cardapio').classList.remove('hidden');
        }
        
        function fecharModalNovoCardapio() {
            document.getElementById('modal-novo-cardapio').classList.add('hidden');
            cardapioEditandoId = null;
            // Mostrar botão "Publicar Cardápio" novamente ao fechar
            const btnPublicar = document.getElementById('btn-publicar-cardapio');
            if (btnPublicar) {
                btnPublicar.style.display = 'block';
            }
        }
        
        function visualizarCardapio(id) {
            fetch(`cardapios_nutricionista.php?acao=buscar_cardapio&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.cardapio) {
                        const c = data.cardapio;
                        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                        const mesNome = meses[c.mes - 1] || c.mes;
                        
                        let itensHtml = '';
                        if (c.itens && c.itens.length > 0) {
                            itensHtml = c.itens.map(item => `
                                <tr>
                                    <td class="px-4 py-2">${item.produto_nome || 'N/A'}</td>
                                    <td class="px-4 py-2">${item.quantidade || 0} ${item.unidade_medida || ''}</td>
                                </tr>
                            `).join('');
                        } else {
                            itensHtml = '<tr><td colspan="2" class="px-4 py-2 text-center text-gray-500">Nenhum item cadastrado</td></tr>';
                        }
                        
                        alert(`Cardápio: ${c.escola_nome}\nPeríodo: ${mesNome}/${c.ano}\nStatus: ${c.status}\nItens: ${c.itens ? c.itens.length : 0}`);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar detalhes do cardápio');
                });
        }
        
        function editarCardapio(id) {
            fetch(`cardapios_nutricionista.php?acao=buscar_cardapio&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.cardapio) {
                        const c = data.cardapio;
                        
                        // Verificar se está como RASCUNHO (qualquer nutricionista pode editar rascunhos)
                        if (c.status !== 'RASCUNHO') {
                            alert('Apenas cardápios em rascunho podem ser editados.');
                            return;
                        }
                        
                        cardapioEditandoId = id;
                        document.getElementById('modal-titulo').textContent = 'Editar Cardápio';
                        
                        // Ocultar botão "Publicar Cardápio" quando estiver editando
                        const btnPublicar = document.getElementById('btn-publicar-cardapio');
                        if (btnPublicar) {
                            btnPublicar.style.display = 'none';
                        }
                        
                        // Preencher formulário
                        document.getElementById('cardapio-escola-id').value = c.escola_id;
                        document.getElementById('cardapio-mes').value = c.mes;
                        document.getElementById('cardapio-ano').value = c.ano;
                        
                        // Atualizar produtos da escola
                        atualizarProdutosEstoque(c.escola_id);
                        
                        // Limpar itens atuais
                        document.getElementById('itens-cardapio-container').innerHTML = '';
                        document.getElementById('mensagem-sem-itens').classList.add('hidden');
                        itensCardapio = [];
                        itemIndex = 0;
                        
                        // Adicionar itens do cardápio
                        if (c.itens && c.itens.length > 0) {
                            setTimeout(() => {
                                c.itens.forEach(item => {
                                    adicionarItemCardapio();
                                    const ultimoIndex = itemIndex - 1;
                                    const produtoSelect = document.querySelector(`.produto-select[data-item-index="${ultimoIndex}"]`);
                                    const quantidadeInput = document.querySelector(`.quantidade-input[data-item-index="${ultimoIndex}"]`);
                                    
                                    // Encontrar o produto correspondente - usar o primeiro disponível se não encontrar exato
                                    const produto = produtosDisponiveis.find(p => p.produto_id == item.produto_id);
                                    if (produto && produtoSelect) {
                                        const identificadorUnico = produto.identificador_unico || `${produto.produto_id}:${produto.estoque_id || 0}`;
                                        produtoSelect.value = identificadorUnico;
                                        selecionarProdutoCardapio(ultimoIndex, identificadorUnico);
                                        
                                        if (quantidadeInput) {
                                            quantidadeInput.value = item.quantidade;
                                            validarQuantidadeEstoque(quantidadeInput);
                                        }
                                    } else if (produtoSelect) {
                                        // Se não encontrou o produto exato, tentar adicionar sem lote específico
                                        const produtoGenerico = produtosDisponiveis.find(p => p.produto_id == item.produto_id);
                                        if (produtoGenerico) {
                                            const identificadorUnico = produtoGenerico.identificador_unico || `${produtoGenerico.produto_id}:${produtoGenerico.estoque_id || 0}`;
                                            produtoSelect.value = identificadorUnico;
                                            selecionarProdutoCardapio(ultimoIndex, identificadorUnico);
                                            
                                            if (quantidadeInput) {
                                                quantidadeInput.value = item.quantidade;
                                                validarQuantidadeEstoque(quantidadeInput);
                                            }
                                        }
                                    }
                                });
                            }, 500);
                        }
                        
                        document.getElementById('modal-novo-cardapio').classList.remove('hidden');
                    } else {
                        alert('Erro ao carregar cardápio para edição');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar cardápio');
                });
        }
        
        function enviarCardapio(id) {
            if (!confirm('Deseja realmente publicar este cardápio? Ele será enviado para aprovação do administrador.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'enviar_cardapio');
            formData.append('cardapio_id', id);
            
            fetch('cardapios_nutricionista.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cardápio publicado com sucesso! Aguardando aprovação do administrador.');
                    filtrarCardapios();
                } else {
                    alert('Erro ao publicar cardápio: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao publicar cardápio');
            });
        }
        
        function excluirCardapio(id) {
            if (!confirm('Deseja realmente excluir este cardápio? Esta ação não pode ser desfeita.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'excluir_cardapio');
            formData.append('cardapio_id', id);
            
            fetch('cardapios_nutricionista.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cardápio excluído com sucesso!');
                    filtrarCardapios();
                } else {
                    alert('Erro ao excluir cardápio: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir cardápio');
            });
        }
        
        function cancelarEnvioCardapio(id) {
            if (!confirm('Deseja realmente cancelar a publicação deste cardápio? Ele voltará para o status de rascunho e poderá ser editado novamente.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'cancelar_envio');
            formData.append('cardapio_id', id);
            
            fetch('cardapios_nutricionista.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Publicação do cardápio cancelada com sucesso! O cardápio voltou para rascunho.');
                    filtrarCardapios();
                } else {
                    alert('Erro ao cancelar publicação: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao cancelar publicação');
            });
        }
        
        // Carregar cardápios ao iniciar
        filtrarCardapios();
        
        // Funções do modal de logout
        window.confirmLogout = function() {
            // Função auxiliar para mostrar o modal
            function showLogoutModal() {
                // Tentar várias formas de encontrar o modal
                let modal = document.getElementById('logoutModal');
                if (!modal) {
                    modal = document.querySelector('#logoutModal');
                }
                if (!modal) {
                    modal = document.querySelector('[id="logoutModal"]');
                }
                if (!modal && document.body) {
                    modal = document.body.querySelector('#logoutModal');
                }
                
                if (modal) {
                    modal.style.display = 'flex';
                    modal.classList.remove('hidden');
                    return true;
                }
                return false;
            }
            
            // Tentar mostrar o modal imediatamente
            if (showLogoutModal()) {
                return;
            }
            
            // Se não encontrou, aguardar um pouco e tentar novamente
            setTimeout(function() {
                if (!showLogoutModal()) {
                    // Se ainda não encontrou, criar dinamicamente
                    if (window.createLogoutModal) {
                        window.createLogoutModal();
                    } else {
                        console.error('Função createLogoutModal não está disponível');
                    }
                }
            }, 200);
        };
        
        // Função para criar o modal dinamicamente se não existir
        window.createLogoutModal = function() {
            // Verificar se já existe
            let existingModal = document.getElementById('logoutModal');
            if (existingModal) {
                existingModal.style.display = 'flex';
                existingModal.classList.remove('hidden');
                return;
            }
            
            // Criar o modal
            const modal = document.createElement('div');
            modal.id = 'logoutModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[60] items-center justify-center p-4';
            modal.style.display = 'flex';
            modal.innerHTML = `
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
            `;
            
            // Adicionar ao body
            document.body.appendChild(modal);
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
                const logoutUrl = '../auth/logout.php';
                console.log('Redirecionando para:', logoutUrl);
                window.location.href = logoutUrl;
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
                alert('Erro ao fazer logout. Por favor, tente novamente.');
            }
        };
    </script>
    
    <!-- Logout Confirmation Modal -->
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
</body>
</html>

