<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/CustoMerendaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'adm_merenda') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$custoModel = new CustoMerendaModel();

// Buscar fornecedores
$sqlFornecedores = "SELECT id, nome FROM fornecedor WHERE ativo = 1 ORDER BY nome ASC";
$stmtFornecedores = $conn->prepare($sqlFornecedores);
$stmtFornecedores->execute();
$fornecedores = $stmtFornecedores->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos
$sqlProdutos = "SELECT id, nome, unidade_medida, estoque_minimo FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_POST['acao'] === 'registrar_entrada') {
        try {
            $conn->beginTransaction();
            
            // Buscar ou criar produto
            $produtoNome = trim($_POST['produto_nome'] ?? '');
            $produtoId = $_POST['produto_id'] ?? null;
            
            if (empty($produtoNome)) {
                throw new Exception('Nome do produto é obrigatório.');
            }
            
            // Se não tem ID, buscar pelo nome
            if (empty($produtoId)) {
                $sqlBuscar = "SELECT id FROM produto WHERE LOWER(nome) = LOWER(:nome) AND ativo = 1 LIMIT 1";
                $stmtBuscar = $conn->prepare($sqlBuscar);
                $stmtBuscar->bindParam(':nome', $produtoNome);
                $stmtBuscar->execute();
                $produtoExistente = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
                
                if ($produtoExistente) {
                    $produtoId = $produtoExistente['id'];
                } else {
                    // Criar novo produto
                    $codigo = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $produtoNome), 0, 3)) . date('YmdHis');
                    $sqlNovoProduto = "INSERT INTO produto (codigo, nome, unidade_medida, estoque_minimo, ativo) 
                                      VALUES (:codigo, :nome, 'UN', 0, 1)";
                    $stmtNovoProduto = $conn->prepare($sqlNovoProduto);
                    $stmtNovoProduto->bindParam(':codigo', $codigo);
                    $stmtNovoProduto->bindParam(':nome', $produtoNome);
                    $stmtNovoProduto->execute();
                    $produtoId = $conn->lastInsertId();
                }
            }
            
            // Preparar variáveis
            $quantidade = $_POST['quantidade'] ?? 0;
            $lote = $_POST['lote'] ?? null;
            $fornecedorId = !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null;
            $notaFiscal = $_POST['nota_fiscal'] ?? null;
            $valorUnitario = !empty($_POST['valor_unitario']) ? floatval($_POST['valor_unitario']) : null;
            $valorTotal = ($valorUnitario !== null && $quantidade > 0) ? ($quantidade * $valorUnitario) : null;
            $validade = !empty($_POST['validade']) ? $_POST['validade'] : null;
            
            // 1. REGISTRAR NO ESTOQUE CENTRAL (SEM VALORES - apenas dados do produto)
            $sqlEstoque = "INSERT INTO estoque_central (produto_id, quantidade, lote, fornecedor_id, nota_fiscal, 
                          valor_unitario, valor_total, validade, criado_em)
                          VALUES (:produto_id, :quantidade, :lote, :fornecedor_id, :nota_fiscal, 
                          NULL, NULL, :validade, NOW())";
            
            $stmtEstoque = $conn->prepare($sqlEstoque);
            $stmtEstoque->bindParam(':produto_id', $produtoId);
            $stmtEstoque->bindParam(':quantidade', $quantidade);
            $stmtEstoque->bindParam(':lote', $lote);
            $stmtEstoque->bindParam(':fornecedor_id', $fornecedorId);
            $stmtEstoque->bindParam(':nota_fiscal', $notaFiscal);
            $stmtEstoque->bindParam(':validade', $validade);
            $stmtEstoque->execute();
            
            // 2. REGISTRAR EM CUSTOS (COM VALORES - separado do estoque)
            if ($valorUnitario !== null && $valorUnitario > 0 && $quantidade > 0) {
                $sqlProdutoNome = "SELECT nome FROM produto WHERE id = :id";
                $stmtProdutoNome = $conn->prepare($sqlProdutoNome);
                $stmtProdutoNome->bindParam(':id', $produtoId);
                $stmtProdutoNome->execute();
                $produtoInfo = $stmtProdutoNome->fetch(PDO::FETCH_ASSOC);
                
                $descricao = "Entrada de estoque: " . ($produtoInfo['nome'] ?? $produtoNome);
                if ($lote) {
                    $descricao .= " - Lote: " . $lote;
                }
                if ($notaFiscal) {
                    $descricao .= " - NF: " . $notaFiscal;
                }
                
                $dataAtual = date('Y-m-d');
                $mesAtual = date('n');
                $anoAtual = date('Y');
                
                // EDIT: validar e normalizar o usuário que está registrando
                $usuarioId = null;
                if (!empty($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) {
                    $usuarioIdTmp = (int) $_SESSION['usuario_id'];
                    $stmtUser = $conn->prepare("SELECT id FROM usuario WHERE id = :id LIMIT 1");
                    $stmtUser->bindValue(':id', $usuarioIdTmp, PDO::PARAM_INT);
                    $stmtUser->execute();
                    if ($stmtUser->fetchColumn()) {
                        $usuarioId = $usuarioIdTmp;
                    }
                }
                $sqlCusto = "INSERT INTO custo_merenda (escola_id, tipo, descricao, produto_id, fornecedor_id,
                            quantidade, valor_unitario, valor_total, data, mes, ano, observacoes, registrado_por, registrado_em)
                            VALUES (NULL, 'COMPRA_PRODUTOS', :descricao, :produto_id, :fornecedor_id,
                            :quantidade, :valor_unitario, :valor_total, :data, :mes, :ano, :observacoes, :registrado_por, NOW())";
                
                $observacoes = "Entrada de produto no estoque central";
                if ($validade) {
                    $observacoes .= " - Validade: " . date('d/m/Y', strtotime($validade));
                }
                if ($notaFiscal) {
                    $observacoes .= " - Nota Fiscal: " . $notaFiscal;
                }
                
                $stmtCusto = $conn->prepare($sqlCusto);
                $stmtCusto->bindParam(':descricao', $descricao);
                $stmtCusto->bindParam(':produto_id', $produtoId);

                // EDIT: fornecedor_id pode ser nulo; ajustar tipo de binding
                if (!empty($fornecedorId) && is_numeric($fornecedorId)) {
                    $stmtCusto->bindValue(':fornecedor_id', (int) $fornecedorId, PDO::PARAM_INT);
                } else {
                    $stmtCusto->bindValue(':fornecedor_id', null, PDO::PARAM_NULL);
                }

                $stmtCusto->bindParam(':quantidade', $quantidade);
                $stmtCusto->bindParam(':valor_unitario', $valorUnitario);
                $stmtCusto->bindParam(':valor_total', $valorTotal);
                $stmtCusto->bindParam(':data', $dataAtual);
                $stmtCusto->bindParam(':mes', $mesAtual, PDO::PARAM_INT);
                $stmtCusto->bindParam(':ano', $anoAtual, PDO::PARAM_INT);
                $stmtCusto->bindParam(':observacoes', $observacoes);

                // EDIT: registrado_por com binding correto (INT ou NULL)
                if ($usuarioId !== null) {
                    $stmtCusto->bindValue(':registrado_por', $usuarioId, PDO::PARAM_INT);
                } else {
                    $stmtCusto->bindValue(':registrado_por', null, PDO::PARAM_NULL);
                }

                $stmtCusto->execute();
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'id' => $conn->lastInsertId()], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_custos') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['mes'])) $filtros['mes'] = $_GET['mes'];
        if (!empty($_GET['ano'])) $filtros['ano'] = $_GET['ano'];
        if (!empty($_GET['data_inicio'])) $filtros['data_inicio'] = $_GET['data_inicio'];
        if (!empty($_GET['data_fim'])) $filtros['data_fim'] = $_GET['data_fim'];
        
        $custos = $custoModel->listar($filtros);
        echo json_encode(['success' => true, 'custos' => $custos]);
        exit;
    }
    
    if ($_GET['acao'] === 'calcular_totais') {
        $totais = $custoModel->calcularTotal(
            $_GET['escola_id'] ?? null,
            $_GET['mes'] ?? null,
            $_GET['ano'] ?? null
        );
        echo json_encode(['success' => true, 'totais' => $totais]);
        exit;
    }
}

// Buscar custos do mês atual
$custosMes = $custoModel->listar(['mes' => date('n'), 'ano' => date('Y')]);
$totaisMes = $custoModel->calcularTotal(null, date('n'), date('Y'));
$totalGeral = array_sum(array_column($totaisMes, 'total_custos'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custos - SIGEA</title>
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
        /* Estilos para a tabela de custos */
        table {
            border-collapse: separate;
            border-spacing: 0;
        }
        table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        table tbody tr {
            transition: all 0.2s ease;
        }
        table tbody tr:hover {
            background-color: #f9fafb;
            transform: scale(1.01);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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
                        <h1 class="text-xl font-semibold text-gray-800">Monitoramento de Custos</h1>
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
                                            <?php echo !empty($_SESSION['escola_atual']) ? htmlspecialchars($_SESSION['escola_atual']) : 'N/A'; ?>
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
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Custos da Merenda Escolar</h2>
                    <p class="text-gray-600 mt-1">
                        Registre e acompanhe os custos da merenda escolar. <strong>Compras são centralizadas</strong> 
                        (sem escola específica) e os produtos são <strong>distribuídos gratuitamente</strong> para as escolas. 
                        Registre custos locais (preparo, desperdício) apenas quando necessário.
                    </p>
                </div>
                
                <!-- Cards de Resumo -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-yellow-100 rounded-xl">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 id="total-mes" class="text-2xl font-bold text-gray-800 mb-1">R$ <?= number_format($totalGeral, 2, ',', '.') ?></h3>
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
                        <h3 id="total-registros" class="text-2xl font-bold text-gray-800 mb-1"><?= count($custosMes) ?></h3>
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
                        <h3 id="media-registro" class="text-2xl font-bold text-gray-800 mb-1">R$ <?= number_format($totalGeral / max(count($custosMes), 1), 2, ',', '.') ?></h3>
                        <p class="text-gray-600 text-sm">Média por Registro</p>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mês</label>
                            <select id="filtro-mes" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCustos()">
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
                            <select id="filtro-ano" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCustos()">
                                <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                                    <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex items-end space-x-3">
                            <button onclick="filtrarCustos()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                                Filtrar
                            </button>
                            <button onclick="abrirModalNovaEntrada()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Nova Entrada</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Custos -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="border-b-2 border-gray-300 bg-gray-50">
                                    <th class="text-center py-4 px-4 font-semibold text-gray-700 text-sm uppercase tracking-wider">Data</th>
                                    <th class="text-center py-4 px-4 font-semibold text-gray-700 text-sm uppercase tracking-wider">Origem</th>
                                    <th class="text-center py-4 px-4 font-semibold text-gray-700 text-sm uppercase tracking-wider">Tipo</th>
                                    <th class="text-left py-4 px-4 font-semibold text-gray-700 text-sm uppercase tracking-wider">Descrição</th>
                                    <th class="text-center py-4 px-4 font-semibold text-gray-700 text-sm uppercase tracking-wider">Valor Total</th>
                                </tr>
                            </thead>
                            <tbody id="lista-custos" class="divide-y divide-gray-200">
                                <?php if (empty($custosMes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-16 text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <p class="text-lg font-medium">Nenhum registro de custo encontrado</p>
                                                <p class="text-sm mt-1">Os registros aparecerão aqui quando forem cadastrados</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($custosMes as $custo): 
                                        $escolaNome = $custo['escola_nome'] ?? ($custo['escola_id'] === null ? 'Compra Centralizada' : '-');
                                        $tipoClass = ($custo['tipo'] === 'COMPRA_PRODUTOS' && $custo['escola_id'] === null) 
                                            ? 'bg-blue-100 text-blue-800 border border-blue-200' 
                                            : 'bg-gray-100 text-gray-800 border border-gray-200';
                                    ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150">
                                            <td class="py-4 px-4 text-center text-sm text-gray-700 font-medium whitespace-nowrap">
                                                <?= date('d/m/Y', strtotime($custo['data'])) ?>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($escolaNome) ?>
                                                    </span>
                                                    <?php if ($custo['escola_id'] === null): ?>
                                                        <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            Central
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?= $tipoClass ?>">
                                                    <?= htmlspecialchars($custo['tipo'] ?? 'OUTROS') ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-sm text-gray-700">
                                                <div class="max-w-md">
                                                    <?= htmlspecialchars($custo['descricao'] ?? '-') ?>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <span class="text-sm font-bold text-gray-900">
                                                    R$ <?= number_format($custo['valor_total'] ?? 0, 2, ',', '.') ?>
                                                </span>
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

        function filtrarCustos() {
            const mes = document.getElementById('filtro-mes').value;
            const ano = document.getElementById('filtro-ano').value;
            
            let url = '?acao=listar_custos';
            if (mes) url += '&mes=' + mes;
            if (ano) url += '&ano=' + ano;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-custos');
                        tbody.innerHTML = '';
                        
                        if (data.custos.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="5" class="text-center py-16 text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <p class="text-lg font-medium">Nenhum registro encontrado</p>
                                            <p class="text-sm mt-1">Os registros aparecerão aqui quando forem cadastrados</p>
                                        </div>
                                    </td>
                                </tr>
                            `;
                            // Atualiza métricas com zero
                            const totalMesEl = document.getElementById('total-mes');
                            const totalRegEl = document.getElementById('total-registros');
                            const mediaEl = document.getElementById('media-registro');
                            if (totalMesEl) totalMesEl.textContent = 'R$ 0,00';
                            if (totalRegEl) totalRegEl.textContent = '0';
                            if (mediaEl) mediaEl.textContent = 'R$ 0,00';
                            return;
                        }
                        
                        data.custos.forEach(custo => {
                            const dataFormatada = new Date(custo.data).toLocaleDateString('pt-BR');
                            const escolaNome = custo.escola_nome || (custo.escola_id === null ? 'Compra Centralizada' : '-');
                            const tipoClass = custo.tipo === 'COMPRA_PRODUTOS' && custo.escola_id === null 
                                ? 'bg-blue-100 text-blue-800 border border-blue-200' 
                                : 'bg-gray-100 text-gray-800 border border-gray-200';
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150">
                                    <td class="py-4 px-4 text-center text-sm text-gray-700 font-medium whitespace-nowrap">
                                        ${dataFormatada}
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="text-sm font-medium text-gray-900">
                                                ${escolaNome}
                                            </span>
                                            ${custo.escola_id === null ? '<span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Central</span>' : ''}
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${tipoClass}">
                                            ${custo.tipo || 'OUTROS'}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-gray-700">
                                        <div class="max-w-md">
                                            ${custo.descricao || '-'}
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="text-sm font-bold text-gray-900">
                                            R$ ${parseFloat(custo.valor_total || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                        </span>
                                    </td>
                                </tr>
                            `;
                        });
                        // Atualiza métricas
                        const total = data.custos.reduce((acc, c) => acc + parseFloat(c.valor_total || 0), 0);
                        const qtd = data.custos.length;
                        const media = qtd > 0 ? (total / qtd) : 0;
                        const totalMesEl = document.getElementById('total-mes');
                        const totalRegEl = document.getElementById('total-registros');
                        const mediaEl = document.getElementById('media-registro');
                        if (totalMesEl) totalMesEl.textContent = 'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        if (totalRegEl) totalRegEl.textContent = String(qtd);
                        if (mediaEl) mediaEl.textContent = 'R$ ' + media.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar custos:', error);
                });
        }
        
        function abrirModalNovaEntrada() {
            document.getElementById('modal-nova-entrada').classList.remove('hidden');
            document.getElementById('form-entrada').reset();
            document.getElementById('entrada-produto-id').value = '';
            document.getElementById('sugestoes-produto').classList.add('hidden');
            produtoSelecionado = null;
        }

        function fecharModalNovaEntrada() {
            document.getElementById('modal-nova-entrada').classList.add('hidden');
        }

        // Variáveis para autocomplete de produtos
        let produtosDisponiveis = [
            <?php 
            foreach ($produtos as $produto): 
                echo '{id: ' . $produto['id'] . ', nome: "' . htmlspecialchars($produto['nome'], ENT_QUOTES) . '", unidade: "' . htmlspecialchars($produto['unidade_medida'], ENT_QUOTES) . '"},';
            endforeach; 
            ?>
        ];
        let sugestaoAtivaProduto = -1;
        let produtoSelecionado = null;

        // Função para buscar produtos
        function buscarProdutos(termo) {
            const campoProduto = document.getElementById('entrada-produto-nome');
            const sugestoes = document.getElementById('sugestoes-produto');
            const produtoIdInput = document.getElementById('entrada-produto-id');
            const termoLower = termo.toLowerCase().trim();
            
            if (!termoLower) {
                sugestoes.classList.add('hidden');
                produtoIdInput.value = '';
                produtoSelecionado = null;
                return;
            }
            
            const produtosFiltrados = produtosDisponiveis.filter(p => 
                p.nome.toLowerCase().includes(termoLower)
            );
            
            if (produtosFiltrados.length === 0) {
                sugestoes.innerHTML = '<div class="p-3 text-gray-500 text-sm">Nenhum produto encontrado. O produto será criado automaticamente.</div>';
                sugestoes.classList.remove('hidden');
                produtoIdInput.value = '';
                produtoSelecionado = null;
                return;
            }
            
            sugestoes.innerHTML = '';
            produtosFiltrados.forEach((produto, index) => {
                const item = document.createElement('div');
                item.className = 'p-3 cursor-pointer hover:bg-gray-100 sugestao-item-produto';
                item.setAttribute('data-id', produto.id);
                item.setAttribute('data-nome', produto.nome);
                item.setAttribute('data-unidade', produto.unidade);
                item.innerHTML = `<div class="font-medium">${produto.nome}</div><div class="text-sm text-gray-500">${produto.unidade}</div>`;
                item.addEventListener('click', () => selecionarProduto(produto.id, produto.nome, produto.unidade));
                sugestoes.appendChild(item);
            });
            
            sugestoes.classList.remove('hidden');
            sugestaoAtivaProduto = -1;
        }

        function selecionarProduto(id, nome, unidade) {
            document.getElementById('entrada-produto-nome').value = nome;
            document.getElementById('entrada-produto-id').value = id;
            document.getElementById('sugestoes-produto').classList.add('hidden');
            produtoSelecionado = {id: id, nome: nome, unidade: unidade};
            sugestaoAtivaProduto = -1;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const campoProduto = document.getElementById('entrada-produto-nome');
            const sugestoes = document.getElementById('sugestoes-produto');
            
            if (campoProduto) {
                campoProduto.addEventListener('input', function() {
                    buscarProdutos(this.value);
                });
                
                document.addEventListener('click', function(e) {
                    if (!campoProduto.contains(e.target) && !sugestoes.contains(e.target)) {
                        sugestoes.classList.add('hidden');
                    }
                });
            }
        });

        function salvarEntrada() {
            const formData = new FormData();
            formData.append('acao', 'registrar_entrada');
            formData.append('produto_nome', document.getElementById('entrada-produto-nome').value);
            formData.append('produto_id', document.getElementById('entrada-produto-id').value);
            formData.append('quantidade', document.getElementById('entrada-quantidade').value);
            formData.append('lote', document.getElementById('entrada-lote').value);
            formData.append('fornecedor_id', document.getElementById('entrada-fornecedor-id').value);
            formData.append('nota_fiscal', document.getElementById('entrada-nota-fiscal').value);
            formData.append('valor_unitario', document.getElementById('entrada-valor-unitario').value);
            formData.append('validade', document.getElementById('entrada-validade').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Entrada registrada com sucesso!');
                    fecharModalNovaEntrada();
                    filtrarCustos(); // Recarregar lista de custos
                } else {
                    alert('Erro: ' + (data.message || 'Erro ao registrar entrada'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar requisição');
            });
        }
    </script>
    
    <!-- Modal Nova Entrada -->
    <div id="modal-nova-entrada" class="fixed inset-0 bg-white z-[60] hidden flex flex-col">
        <div class="bg-indigo-600 text-white p-6 flex items-center justify-between shadow-lg">
            <h3 class="text-2xl font-bold">Nova Entrada de Estoque</h3>
            <button onclick="fecharModalNovaEntrada()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <form id="form-entrada" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produto *</label>
                            <input type="text" id="entrada-produto-nome" required 
                                   placeholder="Digite o nome do produto..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   autocomplete="off">
                            <input type="hidden" id="entrada-produto-id" value="">
                            <div id="sugestoes-produto" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade *</label>
                            <input type="number" step="0.001" min="0" id="entrada-quantidade" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lote</label>
                            <input type="text" id="entrada-lote" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fornecedor</label>
                            <select id="entrada-fornecedor-id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione um fornecedor</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <option value="<?= $fornecedor['id'] ?>"><?= htmlspecialchars($fornecedor['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nota Fiscal</label>
                            <input type="text" id="entrada-nota-fiscal" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Valor Unitário (R$)</label>
                            <input type="number" step="0.01" min="0" id="entrada-valor-unitario" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">O valor será registrado automaticamente em custos quando informado</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Validade</label>
                            <input type="date" id="entrada-validade" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-200 p-6">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalNovaEntrada()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarEntrada()" class="flex-1 px-6 py-3 text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg font-medium transition-colors">
                    Salvar Entrada
                </button>
            </div>
        </div>
    </div>
</body>
</html>

