<?php
// Iniciar output buffering para evitar output antes do JSON
if (!ob_get_level()) {
    ob_start();
}

require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é nutricionista
if (!eNutricionista() && !eAdm()) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

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

// Processar requisições AJAX (ANTES de qualquer output HTML)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'listar_estoque') {
    // Limpar qualquer output anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Buscar estoque da escola selecionada (usa a escola da sessão)
    {
        $escolaIdFiltro = $escolaId;
        
        // Validar se a escola existe
        if (!$escolaIdFiltro) {
            echo json_encode(['success' => false, 'message' => 'Nenhuma escola selecionada'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Verificar se a escola existe no banco
        try {
            $sqlCheck = "SELECT id FROM escola WHERE id = :escola_id";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':escola_id', $escolaIdFiltro, PDO::PARAM_INT);
            $stmtCheck->execute();
            if (!$stmtCheck->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Escola não encontrada'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao validar escola'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Verificar se a coluna estoque_central_id existe
        try {
            $checkColumn = $conn->query("SHOW COLUMNS FROM pacote_escola_item LIKE 'estoque_central_id'");
            $columnExists = $checkColumn->rowCount() > 0;
        } catch (Exception $e) {
            $columnExists = false;
        }
        
        if ($columnExists) {
            $sql = "SELECT 
                        pei.produto_id,
                        pei.estoque_central_id,
                        p.nome as produto_nome,
                        p.unidade_medida,
                        p.estoque_minimo,
                        SUM(pei.quantidade) as quantidade,
                        COALESCE(ec1.validade, ec2.validade) as validade,
                        COALESCE(ec1.lote, ec2.lote, 'Sem lote') as lote,
                        COALESCE(f1.nome, ec1.fornecedor, f2.nome, ec2.fornecedor, NULL) as fornecedor_nome,
                        MAX(pe.data_envio) as data_envio_mais_recente
                    FROM pacote_escola_item pei
                    INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                    INNER JOIN produto p ON pei.produto_id = p.id
                    LEFT JOIN estoque_central ec1 ON pei.estoque_central_id = ec1.id
                    LEFT JOIN fornecedor f1 ON ec1.fornecedor_id = f1.id
                    LEFT JOIN estoque_central ec2 ON pei.produto_id = ec2.produto_id 
                        AND ec2.quantidade > 0 
                        AND pei.estoque_central_id IS NULL
                        AND ec2.id = (
                            SELECT ec3.id 
                            FROM estoque_central ec3 
                            WHERE ec3.produto_id = pei.produto_id 
                            AND ec3.quantidade > 0 
                            ORDER BY ec3.validade ASC 
                            LIMIT 1
                        )
                    LEFT JOIN fornecedor f2 ON ec2.fornecedor_id = f2.id
                    WHERE pe.escola_id = :escola_id";
        } else {
            $sql = "SELECT 
                        pei.produto_id,
                        ec.id as estoque_central_id,
                        p.nome as produto_nome,
                        p.unidade_medida,
                        p.estoque_minimo,
                        SUM(pei.quantidade) as quantidade,
                        ec.validade,
                        COALESCE(ec.lote, 'Sem lote') as lote,
                        COALESCE(f.nome, ec.fornecedor, NULL) as fornecedor_nome,
                        MAX(pe.data_envio) as data_envio_mais_recente
                    FROM pacote_escola_item pei
                    INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                    INNER JOIN produto p ON pei.produto_id = p.id
                    LEFT JOIN estoque_central ec ON pei.produto_id = ec.produto_id AND ec.quantidade > 0
                    LEFT JOIN fornecedor f ON ec.fornecedor_id = f.id
                    WHERE pe.escola_id = :escola_id";
        }
        
        $params = [':escola_id' => $escolaIdFiltro];
        
        if (!empty($_GET['produto_id'])) {
            $sql .= " AND pei.produto_id = :produto_id";
            $params[':produto_id'] = $_GET['produto_id'];
        }
        
        // Agrupar por produto_id + estoque_central_id (ou lote) para mostrar cada lote separadamente
        if ($columnExists) {
            $sql .= " GROUP BY pei.produto_id, pei.estoque_central_id, p.nome, p.unidade_medida, p.estoque_minimo, 
                      COALESCE(ec1.validade, ec2.validade), COALESCE(ec1.lote, ec2.lote), COALESCE(f1.nome, f2.nome)
                      ORDER BY p.nome ASC, COALESCE(ec1.validade, ec2.validade) ASC";
        } else {
            $sql .= " GROUP BY pei.produto_id, ec.id, p.nome, p.unidade_medida, p.estoque_minimo, ec.validade, ec.lote, f.nome
                      ORDER BY p.nome ASC, ec.validade ASC";
        }
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $estoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Limpar valores vazios e formatar dados
        foreach ($estoque as &$item) {
            $item['id'] = null;
            $item['total_produto'] = $item['quantidade'];
            $item['criado_em'] = $item['data_envio_mais_recente'];
            
            if (isset($item['lote'])) {
                $lote = trim($item['lote']);
                if (empty($lote) || $lote === '' || $lote === 'Sem lote') {
                    $item['lote'] = null;
                } else {
                    $item['lote'] = $lote;
                }
            } else {
                $item['lote'] = null;
            }
            
            if (isset($item['fornecedor_nome'])) {
                $fornecedor = trim($item['fornecedor_nome']);
                if (empty($fornecedor) || $fornecedor === '') {
                    $item['fornecedor_nome'] = null;
                } else {
                    $item['fornecedor_nome'] = $fornecedor;
                }
            } else {
                $item['fornecedor_nome'] = null;
            }
            
            if (empty($item['validade']) || $item['validade'] === '0000-00-00' || $item['validade'] === '0000-00-00 00:00:00') {
                $item['validade'] = null;
            }
        }
        
        echo json_encode(['success' => true, 'estoque' => $estoque], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Buscar produtos para filtro
$sqlProdutos = "SELECT id, nome, unidade_medida, estoque_minimo FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Buscar estoque inicial - mostrar cada item separadamente (incluindo lotes diferentes)
$sqlEstoque = "SELECT 
                    pei.id as item_id,
                    pei.produto_id,
                    p.nome as produto_nome,
                    p.unidade_medida,
                    p.estoque_minimo,
                    pei.quantidade,
                    pei.estoque_central_id,
                    COALESCE(ec1.validade, ec2.validade, NULL) as validade,
                    COALESCE(ec1.lote, ec2.lote, 'Sem lote') as lote,
                    COALESCE(f1.nome, ec1.fornecedor, f2.nome, ec2.fornecedor, NULL) as fornecedor_nome,
                    pe.data_envio,
                    pe.id as pacote_id
                FROM pacote_escola_item pei
                INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                INNER JOIN produto p ON pei.produto_id = p.id
                LEFT JOIN estoque_central ec1 ON pei.estoque_central_id = ec1.id
                LEFT JOIN fornecedor f1 ON ec1.fornecedor_id = f1.id
                LEFT JOIN estoque_central ec2 ON pei.produto_id = ec2.produto_id 
                    AND ec2.quantidade > 0 
                    AND pei.estoque_central_id IS NULL
                    AND ec2.id = (
                        SELECT ec3.id 
                        FROM estoque_central ec3 
                        WHERE ec3.produto_id = pei.produto_id 
                        AND ec3.quantidade > 0 
                        ORDER BY ec3.validade ASC 
                        LIMIT 1
                    )
                LEFT JOIN fornecedor f2 ON ec2.fornecedor_id = f2.id
                WHERE pe.escola_id = :escola_id
                AND pei.quantidade > 0
                ORDER BY p.nome ASC,
                         CASE WHEN COALESCE(ec1.validade, ec2.validade) IS NULL THEN 1 ELSE 0 END ASC,
                         COALESCE(ec1.validade, ec2.validade) ASC,
                         pei.id ASC
                LIMIT 200";
$stmtEstoque = $conn->prepare($sqlEstoque);
$stmtEstoque->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
$stmtEstoque->execute();
$estoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);

// Verificar se há escola selecionada
if (!$escolaId) {
    error_log("AVISO - Nenhuma escola selecionada para exibir estoque");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Estoque da Escola') ?></title>
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
    <?php include 'components/sidebar_nutricionista.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Estoque da Escola</h1>
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
            <div class="max-w-[95%] mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Estoque de Produtos</h2>
                    <p class="text-gray-600 mt-1">Visualize o estoque disponível na escola: <?= htmlspecialchars($escolaNome) ?></p>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produto</label>
                            <select id="filtro-produto" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEstoque()">
                                <option value="">Todos os produtos</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="filtro-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEstoque()">
                                <option value="">Todos</option>
                                <option value="baixo">Estoque Baixo</option>
                                <option value="normal">Estoque Normal</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Estoque -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1200px]">
                            <thead>
                                <tr class="bg-gradient-to-r from-primary-green to-green-700 text-white">
                                    <th class="text-left py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                            Produto
                                        </div>
                                    </th>
                                    <th class="text-center py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center justify-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-5m-6 5v-5m6 5h-3m-3 0h3m-3-10h3m-3 0H9m0 0V7m0 10v-5"></path>
                                            </svg>
                                            Quantidade
                                        </div>
                                    </th>
                                    <th class="text-left py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Lote
                                        </div>
                                    </th>
                                    <th class="text-left py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            Fornecedor
                                        </div>
                                    </th>
                                    <th class="text-center py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center justify-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Validade
                                        </div>
                                    </th>
                                    <th class="text-center py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center justify-center">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Data de Entrada
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="lista-estoque" class="divide-y divide-gray-100">
                                <?php if (empty($estoque)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-16">
                                            <div class="flex flex-col items-center justify-center text-gray-400">
                                                <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                </svg>
                                                <p class="text-lg font-medium">Nenhum registro de estoque encontrado</p>
                                                <p class="text-sm mt-1">Os registros aparecerão aqui quando houver produtos enviados para a escola</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($estoque as $index => $item): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-150 <?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">
                                            <td class="py-4 px-6">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 w-10 h-10 bg-primary-green bg-opacity-10 rounded-lg flex items-center justify-center mr-3">
                                                        <svg class="w-5 h-5 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($item['produto_nome']) ?></div>
                                                        <div class="text-sm text-gray-500 mt-0.5"><?= htmlspecialchars($item['unidade_medida']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold text-sm border border-blue-200">
                                                    <?php
                                                    $unidade = strtoupper(trim($item['unidade_medida'] ?? ''));
                                                    $permiteDecimal = in_array($unidade, ['ML', 'L', 'G', 'KG', 'LT', 'LITRO', 'LITROS', 'MILILITRO', 'MILILITROS', 'GRAMA', 'GRAMAS', 'QUILO', 'QUILOS']);
                                                    $casasDecimais = $permiteDecimal ? 3 : 0;
                                                    echo number_format($item['quantidade'], $casasDecimais, ',', '.');
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <?php 
                                                $lote = trim($item['lote'] ?? '');
                                                if (!empty($lote) && $lote !== 'Sem lote' && $lote !== ''): ?>
                                                    <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 rounded-lg text-sm font-medium">
                                                        <?= htmlspecialchars($lote) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400 italic">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-6">
                                                <?php if (!empty($item['fornecedor_nome'])): ?>
                                                    <span class="text-gray-700 font-medium">
                                                        <?= htmlspecialchars($item['fornecedor_nome']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400 italic">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                <?php if (!empty($item['validade'])): ?>
                                                    <?php
                                                    try {
                                                        $dataValidade = new DateTime($item['validade']);
                                                        $hoje = new DateTime();
                                                        $diferenca = $hoje->diff($dataValidade);
                                                        $diasRestantes = $diferenca->days;
                                                        $cor = $diasRestantes <= 7 ? 'text-red-600 font-bold' : ($diasRestantes <= 30 ? 'text-orange-600 font-semibold' : 'text-gray-700');
                                                    ?>
                                                        <span class="<?= $cor ?>">
                                                            <?= $dataValidade->format('d/m/Y') ?>
                                                        </span>
                                                    <?php
                                                    } catch (Exception $e) {
                                                        echo '<span class="text-gray-400 italic">Não informada</span>';
                                                    }
                                                    ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400 italic">Não informada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                <?php if (!empty($item['data_envio'])): ?>
                                                    <?php
                                                    try {
                                                        $dataEnvio = new DateTime($item['data_envio']);
                                                        echo '<span class="text-gray-600 font-medium">' . $dataEnvio->format('d/m/Y') . '</span>';
                                                    } catch (Exception $e) {
                                                        echo '<span class="text-gray-400 italic">-</span>';
                                                    }
                                                    ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400 italic">-</span>
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
    
    <script>
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

        function filtrarEstoque() {
            const produtoId = document.getElementById('filtro-produto').value;
            const escolaId = '<?= $escolaId ?>'; // Usar a escola da sessão
            
            let url = '?acao=listar_estoque';
            if (escolaId) url += '&escola_id=' + escolaId;
            if (produtoId) url += '&produto_id=' + produtoId;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-estoque');
                        tbody.innerHTML = '';
                        
                        if (data.estoque.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="6" class="text-center py-16">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                            </svg>
                                            <p class="text-lg font-medium">Nenhum registro encontrado</p>
                                            <p class="text-sm mt-1">Os registros aparecerão aqui quando houver dados</p>
                                        </div>
                                    </td>
                                </tr>
                            `;
                            return;
                        }
                        
                        data.estoque.forEach((item, index) => {
                            // Formatar validade
                            let validade = null;
                            let corValidade = '';
                            let iconeValidade = '';
                            if (item.validade) {
                                try {
                                    const dataValidade = new Date(item.validade);
                                    if (!isNaN(dataValidade.getTime())) {
                                        validade = dataValidade.toLocaleDateString('pt-BR');
                                        const hoje = new Date();
                                        hoje.setHours(0, 0, 0, 0);
                                        const diasRestantes = Math.ceil((dataValidade - hoje) / (1000 * 60 * 60 * 24));
                                        
                                        if (diasRestantes < 0) {
                                            corValidade = 'bg-red-100 text-red-800 border-red-200';
                                            iconeValidade = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                                        } else if (diasRestantes <= 7) {
                                            corValidade = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                            iconeValidade = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                        } else {
                                            corValidade = 'bg-green-100 text-green-800 border-green-200';
                                            iconeValidade = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                        }
                                    }
                                } catch (e) {
                                    validade = item.validade;
                                }
                            }
                            
                            // Formatar data de entrada
                            let dataEntrada = '-';
                            if (item.criado_em) {
                                try {
                                    const data = new Date(item.criado_em);
                                    if (!isNaN(data.getTime())) {
                                        dataEntrada = data.toLocaleDateString('pt-BR');
                                    }
                                } catch (e) {
                                    dataEntrada = item.criado_em;
                                }
                            }
                            
                            // Formatar lote
                            let lote = item.lote ? item.lote.trim() : null;
                            if (!lote || lote === '' || lote === 'Sem lote') lote = '-';
                            
                            // Formatar fornecedor
                            let fornecedor = item.fornecedor_nome ? item.fornecedor_nome.trim() : null;
                            if (!fornecedor || fornecedor === '') fornecedor = '-';
                            
                            tbody.innerHTML += `
                                <tr class="hover:bg-gray-50 transition-colors duration-150 ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-10 h-10 bg-primary-green bg-opacity-10 rounded-lg flex items-center justify-center mr-3">
                                                <svg class="w-5 h-5 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">${item.produto_nome || '-'}</div>
                                                <div class="text-sm text-gray-500 mt-0.5">${item.unidade_medida || '-'}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold text-sm border border-blue-200">
                                            ${formatarQuantidade(item.quantidade || 0, item.unidade_medida)}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-md text-sm font-medium">
                                            ${lote}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-gray-700 font-medium">${fornecedor}</span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        ${validade ? `
                                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-semibold border ${corValidade}">
                                                ${iconeValidade}${validade}
                                            </span>
                                        ` : '<span class="text-gray-400 italic">Não informada</span>'}
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="text-gray-600 font-medium">${dataEntrada}</span>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar estoque:', error);
                });
        }
    </script>
    
    <?php include(__DIR__ . '/components/logout_modal.php'); ?>
</body>
</html>

