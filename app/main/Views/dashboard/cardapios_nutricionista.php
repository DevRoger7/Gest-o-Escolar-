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
            'semanas' => json_decode($_POST['semanas'] ?? '[]', true),
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
            'itens' => json_decode($_POST['itens'] ?? '[]', true),
            'semanas' => json_decode($_POST['semanas'] ?? '[]', true)
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
            
            // Primeiro, identificar quais produtos estão no estoque da escola (via pacote_escola)
            $sqlProdutosEscola = "SELECT DISTINCT p.id as produto_id
                                  FROM produto p
                                  INNER JOIN pacote_escola_item pei ON p.id = pei.produto_id
                                  INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                                  WHERE p.ativo = 1 
                                  AND pe.escola_id = :escola_id
                                  AND pei.quantidade > 0";
            
            $stmtProdutosEscola = $conn->prepare($sqlProdutosEscola);
            $stmtProdutosEscola->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmtProdutosEscola->execute();
            $produtosEscola = $stmtProdutosEscola->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($produtosEscola)) {
                echo json_encode(['success' => true, 'produtos' => [], 'message' => 'Nenhum produto encontrado no estoque da escola']);
                exit;
            }
            
            // Agora buscar todos os lotes desses produtos
            $placeholders = [];
            $params = [':escola_id' => $escolaId];
            foreach ($produtosEscola as $index => $produtoId) {
                $placeholder = ':produto_id_' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $produtoId;
            }
            $placeholdersStr = implode(',', $placeholders);
            
            // Buscar produtos do pacote_escola_item com informações de lotes quando disponíveis
            if ($columnExists) {
                // Se a coluna existe, buscar com LEFT JOIN no estoque_central
                $sql = "SELECT 
                            p.id as produto_id,
                            pei.id as item_id,
                            COALESCE(pei.estoque_central_id, 0) as estoque_id,
                            p.nome, 
                            p.unidade_medida,
                            pei.quantidade as estoque_quantidade,
                            COALESCE(ec.validade, NULL) as validade,
                            COALESCE(ec.lote, 'Sem lote') as lote,
                            CONCAT(p.id, ':', COALESCE(pei.estoque_central_id, 0), ':', pei.id) as identificador_unico,
                            (SELECT COALESCE(SUM(pei2.quantidade), 0)
                             FROM pacote_escola_item pei2
                             INNER JOIN pacote_escola pe2 ON pei2.pacote_id = pe2.id
                             WHERE pei2.produto_id = p.id AND pe2.escola_id = :escola_id) as quantidade_total_produto
                        FROM produto p
                        INNER JOIN pacote_escola_item pei ON p.id = pei.produto_id
                        INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                        LEFT JOIN estoque_central ec ON pei.estoque_central_id = ec.id
                        WHERE p.id IN ($placeholdersStr)
                        AND p.ativo = 1
                        AND pe.escola_id = :escola_id
                        AND pei.quantidade > 0
                        ORDER BY p.nome ASC, 
                                 CASE WHEN ec.validade IS NULL THEN 1 ELSE 0 END ASC,
                                 ec.validade ASC,
                                 pei.id ASC";
            } else {
                // Se a coluna não existe, buscar diretamente do pacote_escola_item
                $sql = "SELECT 
                            p.id as produto_id,
                            pei.id as item_id,
                            0 as estoque_id,
                            p.nome, 
                            p.unidade_medida,
                            pei.quantidade as estoque_quantidade,
                            NULL as validade,
                            'Sem lote' as lote,
                            CONCAT(p.id, ':0:', pei.id) as identificador_unico,
                            (SELECT COALESCE(SUM(pei2.quantidade), 0)
                             FROM pacote_escola_item pei2
                             INNER JOIN pacote_escola pe2 ON pei2.pacote_id = pe2.id
                             WHERE pei2.produto_id = p.id AND pe2.escola_id = :escola_id) as quantidade_total_produto
                        FROM produto p
                        INNER JOIN pacote_escola_item pei ON p.id = pei.produto_id
                        INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                        WHERE p.id IN ($placeholdersStr)
                        AND p.ativo = 1
                        AND pe.escola_id = :escola_id
                        AND pei.quantidade > 0
                        ORDER BY p.nome ASC, pei.id ASC";
            }
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key === ':escola_id') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                }
            }
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
            
            error_log("Produtos encontrados para escola $escolaId: " . count($produtos));
            if (empty($produtos)) {
                error_log("Nenhum produto encontrado. Produtos da escola: " . count($produtosEscola));
            }
            
            echo json_encode(['success' => true, 'produtos' => $produtos, 'debug' => [
                'escola_id' => $escolaId,
                'produtos_escola_count' => count($produtosEscola),
                'produtos_estoque_count' => count($produtos),
                'column_exists' => $columnExists
            ]]);
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos do estoque: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar produtos: ' . $e->getMessage(), 'produtos' => []]);
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
    // Primeiro identificar produtos da escola
    try {
        $sqlProdutosEscola = "SELECT DISTINCT p.id as produto_id
                              FROM produto p
                              INNER JOIN pacote_escola_item pei ON p.id = pei.produto_id
                              INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                              WHERE p.ativo = 1 
                              AND pe.escola_id = :escola_id
                              AND (SELECT COALESCE(SUM(pei2.quantidade), 0) 
                                   FROM pacote_escola_item pei2 
                                   INNER JOIN pacote_escola pe2 ON pei2.pacote_id = pe2.id 
                                   WHERE pei2.produto_id = p.id AND pe2.escola_id = :escola_id) > 0";
        
        $stmtProdutosEscola = $conn->prepare($sqlProdutosEscola);
        $stmtProdutosEscola->bindParam(':escola_id', $escolaSelecionadaId, PDO::PARAM_INT);
        $stmtProdutosEscola->execute();
        $produtosEscolaIds = $stmtProdutosEscola->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($produtosEscolaIds)) {
            // Buscar produtos do estoque_central
            // Usar parâmetros nomeados
            if (empty($produtosEscolaIds)) {
                $produtos = [];
            } else {
                $placeholders = [];
                $params = [];
                foreach ($produtosEscolaIds as $index => $produtoId) {
                    $placeholder = ':produto_id_' . $index;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $produtoId;
                }
                $placeholdersStr = implode(',', $placeholders);
                
                $sqlProdutos = "SELECT 
                                    p.id as produto_id,
                                    ec.id as estoque_id,
                                    p.id, 
                                    p.nome, 
                                    p.unidade_medida,
                                    ec.quantidade as estoque_quantidade,
                                    ec.validade,
                                    COALESCE(ec.lote, 'Sem lote') as lote,
                                    CONCAT(p.id, ':', ec.id) as identificador_unico
                                FROM produto p
                                INNER JOIN estoque_central ec ON p.id = ec.produto_id
                                WHERE p.id IN ($placeholdersStr)
                                AND p.ativo = 1
                                AND ec.quantidade > 0
                                ORDER BY p.nome ASC, ec.validade ASC";
                
                $stmtProdutos = $conn->prepare($sqlProdutos);
                foreach ($params as $key => $value) {
                    $stmtProdutos->bindValue($key, $value, PDO::PARAM_INT);
                }
                $stmtProdutos->execute();
                $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Se não encontrou produtos no estoque_central, buscar diretamente do pacote_escola_item
            if (empty($produtos)) {
                error_log("Nenhum produto encontrado no estoque_central, buscando diretamente do pacote_escola_item");
                
                // Usar parâmetros nomeados
                $placeholdersAlt = [];
                $paramsAlt = [':escola_id' => $escolaSelecionadaId];
                foreach ($produtosEscolaIds as $index => $produtoId) {
                    $placeholder = ':produto_id_alt_' . $index;
                    $placeholdersAlt[] = $placeholder;
                    $paramsAlt[$placeholder] = $produtoId;
                }
                $placeholdersStrAlt = implode(',', $placeholdersAlt);
                
                $sqlProdutosAlt = "SELECT 
                                    p.id as produto_id,
                                    0 as estoque_id,
                                    p.id, 
                                    p.nome, 
                                    p.unidade_medida,
                                    COALESCE(SUM(pei.quantidade), 0) as estoque_quantidade,
                                    NULL as validade,
                                    'Sem lote' as lote,
                                    CONCAT(p.id, ':0') as identificador_unico
                                FROM produto p
                                INNER JOIN pacote_escola_item pei ON p.id = pei.produto_id
                                INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                                WHERE p.id IN ($placeholdersStrAlt)
                                AND p.ativo = 1
                                AND pe.escola_id = :escola_id
                                GROUP BY p.id, p.nome, p.unidade_medida
                                HAVING estoque_quantidade > 0
                                ORDER BY p.nome ASC";
                
                $stmtProdutosAlt = $conn->prepare($sqlProdutosAlt);
                foreach ($paramsAlt as $key => $value) {
                    if ($key === ':escola_id') {
                        $stmtProdutosAlt->bindValue($key, $value, PDO::PARAM_INT);
                    } else {
                        $stmtProdutosAlt->bindValue($key, $value, PDO::PARAM_INT);
                    }
                }
                $stmtProdutosAlt->execute();
                $produtos = $stmtProdutosAlt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Formatar validade
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
        } else {
            $produtos = [];
        }
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
    
    <!-- Modal Visualizar Cardápio -->
    <div id="modal-visualizar-cardapio" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-green to-green-600 text-white p-6 flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold">Detalhes do Cardápio</h3>
                    <p class="text-green-100 text-sm mt-1" id="modal-cardapio-escola">Carregando...</p>
                </div>
                <button onclick="fecharModalVisualizarCardapio()" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div id="modal-cardapio-content">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green"></div>
                        <p class="text-gray-600 mt-4">Carregando detalhes do cardápio...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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
                    
                    <!-- Seção de Semanas -->
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">Semanas do Cardápio</h4>
                                <p class="text-sm text-gray-600 mt-1">Configure as semanas do mês e adicione observações específicas</p>
                            </div>
                            <button type="button" onclick="adicionarSemana()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Adicionar Semana</span>
                            </button>
                        </div>
                        
                        <div id="semanas-container" class="space-y-4">
                            <!-- Semanas serão adicionadas aqui -->
                        </div>
                        
                        <div id="mensagem-sem-semanas" class="text-center py-6 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-sm">Nenhuma semana configurada. Clique em "Adicionar Semana" para começar.</p>
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
        
        // Gerenciamento de semanas
        let semanaIndex = 0;
        let semanasCardapio = [];
        
        // Função para calcular datas da semana baseado em um domingo selecionado
        function calcularDatasSemanaAPartirDomingo(dataDomingo) {
            if (!dataDomingo) return null;
            
            const domingo = new Date(dataDomingo);
            
            // Garantir que é domingo (dia 0)
            if (domingo.getDay() !== 0) {
                // Ajustar para o domingo anterior
                const diasAjuste = domingo.getDay();
                domingo.setDate(domingo.getDate() - diasAjuste);
            }
            
            // Sábado é 6 dias depois
            const sabado = new Date(domingo);
            sabado.setDate(sabado.getDate() + 6);
            
            return {
                inicio: domingo.toISOString().split('T')[0],
                fim: sabado.toISOString().split('T')[0]
            };
        }
        
        // Função para encontrar o próximo domingo disponível
        function encontrarProximoDomingo(mes, ano) {
            const primeiroDia = new Date(ano, mes - 1, 1);
            const diaSemana = primeiroDia.getDay(); // 0 = Domingo, 1 = Segunda, etc.
            
            // Se não for domingo, encontrar o próximo
            let proximoDomingo = new Date(primeiroDia);
            if (diaSemana !== 0) {
                const diasParaDomingo = 7 - diaSemana;
                proximoDomingo.setDate(primeiroDia.getDate() + diasParaDomingo);
            }
            
            return proximoDomingo.toISOString().split('T')[0];
        }
        
        function adicionarSemana() {
            const container = document.getElementById('semanas-container');
            const mensagemSemSemanas = document.getElementById('mensagem-sem-semanas');
            
            if (mensagemSemSemanas) {
                mensagemSemSemanas.classList.add('hidden');
            }
            
            const mes = parseInt(document.getElementById('cardapio-mes').value) || new Date().getMonth() + 1;
            const ano = parseInt(document.getElementById('cardapio-ano').value) || new Date().getFullYear();
            
            // Verificar quantas semanas já existem
            const semanasExistentes = semanasCardapio.map(s => s.numero_semana);
            let proximaSemana = 1;
            while (semanasExistentes.includes(proximaSemana)) {
                proximaSemana++;
            }
            
            if (proximaSemana > 5) {
                alert('Máximo de 5 semanas permitidas por mês.');
                return;
            }
            
            const semanaId = `semana-${semanaIndex}`;
            const proximoDomingo = encontrarProximoDomingo(mes, ano);
            const datas = calcularDatasSemanaAPartirDomingo(proximoDomingo);
            
            semanasCardapio.push({
                id: semanaId,
                numero_semana: proximaSemana,
                observacao: '',
                data_inicio: datas.inicio,
                data_fim: datas.fim,
                data_domingo: proximoDomingo
            });
            
            const div = document.createElement('div');
            div.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50';
            div.id = semanaId;
            
            div.innerHTML = `
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h5 class="font-semibold text-gray-900 mb-2">Semana ${proximaSemana}</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Domingo da Semana *</label>
                                <input 
                                    type="date" 
                                    class="data-domingo-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                    value="${proximoDomingo}"
                                    data-semana-index="${semanaIndex}"
                                    onchange="atualizarDataSemana(${semanaIndex}, this.value)"
                                    required
                                />
                                <p class="text-xs text-gray-500 mt-1">Selecione o domingo para calcular a semana</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Período da Semana</label>
                                <div class="px-3 py-2 bg-gray-100 rounded-lg text-sm text-gray-700">
                                    <span class="periodo-semana-${semanaIndex}">${new Date(datas.inicio).toLocaleDateString('pt-BR')} a ${new Date(datas.fim).toLocaleDateString('pt-BR')}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="removerSemana(${semanaIndex})" class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm ml-3" title="Remover semana">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Observação da Semana</label>
                    <textarea 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" 
                        rows="3" 
                        placeholder="Adicione observações específicas para esta semana (ex: substituições, adaptações, etc.)"
                        onchange="atualizarObservacaoSemana(${semanaIndex}, this.value)"
                    ></textarea>
                </div>
            `;
            
            container.appendChild(div);
            semanaIndex++;
            
            // Atualizar selects de semana em todos os itens
            atualizarSelectsSemana();
        }
        
        function atualizarDataSemana(index, dataDomingo) {
            const semanaId = `semana-${index}`;
            const semana = semanasCardapio.find(s => s.id === semanaId);
            
            if (!semana) return;
            
            // Validar se é domingo
            const data = new Date(dataDomingo);
            if (data.getDay() !== 0) {
                alert('Por favor, selecione um domingo. A data será ajustada automaticamente.');
                // Ajustar para o domingo anterior
                const diasAjuste = data.getDay();
                data.setDate(data.getDate() - diasAjuste);
                dataDomingo = data.toISOString().split('T')[0];
                
                // Atualizar o input
                const input = document.querySelector(`.data-domingo-input[data-semana-index="${index}"]`);
                if (input) {
                    input.value = dataDomingo;
                }
            }
            
            const datas = calcularDatasSemanaAPartirDomingo(dataDomingo);
            if (datas) {
                semana.data_domingo = dataDomingo;
                semana.data_inicio = datas.inicio;
                semana.data_fim = datas.fim;
                
                // Atualizar exibição do período
                const periodoSpan = document.querySelector(`.periodo-semana-${index}`);
                if (periodoSpan) {
                    periodoSpan.textContent = `${new Date(datas.inicio).toLocaleDateString('pt-BR')} a ${new Date(datas.fim).toLocaleDateString('pt-BR')}`;
                }
                
                // Atualizar selects de semana nos itens
                atualizarSelectsSemana();
            }
        }
        
        function atualizarObservacaoSemana(index, observacao) {
            const semanaId = `semana-${index}`;
            const semana = semanasCardapio.find(s => s.id === semanaId);
            if (semana) {
                semana.observacao = observacao;
            }
        }
        
        function removerSemana(index) {
            const semanaId = `semana-${index}`;
            const semana = semanasCardapio.find(s => s.id === semanaId);
            
            if (semana && confirm(`Deseja realmente remover a Semana ${semana.numero_semana}? Os itens associados a esta semana também serão removidos.`)) {
                const div = document.getElementById(semanaId);
                if (div) {
                    div.remove();
                }
                
                semanasCardapio = semanasCardapio.filter(s => s.id !== semanaId);
                
                // Remover itens associados a esta semana
                const numeroSemana = semana.numero_semana;
                itensCardapio = itensCardapio.filter(item => item.numero_semana !== numeroSemana);
                
                // Atualizar interface de itens
                atualizarInterfaceItens();
                
                const container = document.getElementById('semanas-container');
                const mensagemSemSemanas = document.getElementById('mensagem-sem-semanas');
                if (container && mensagemSemSemanas && container.children.length === 0) {
                    mensagemSemSemanas.classList.remove('hidden');
                }
            }
        }
        
        function atualizarInterfaceItens() {
            // Recriar interface de itens removendo os que não têm semana válida
            const container = document.getElementById('itens-cardapio-container');
            const itensValidos = itensCardapio.filter(item => {
                if (!item.numero_semana) return true; // Itens sem semana são válidos
                return semanasCardapio.some(s => s.numero_semana === item.numero_semana);
            });
            
            // Se houver diferença, recarregar
            if (itensValidos.length !== itensCardapio.length) {
                itensCardapio = itensValidos;
                // Recriar interface seria complexo, então apenas alertamos
                console.log('Alguns itens foram removidos por não terem semana válida');
            }
            
            // Atualizar selects de semana em todos os itens
            atualizarSelectsSemana();
        }
        
        function atualizarSelectsSemana() {
            const semanasOptions = semanasCardapio.map(s => {
                const dataInicio = new Date(s.data_inicio).toLocaleDateString('pt-BR');
                const dataFim = new Date(s.data_fim).toLocaleDateString('pt-BR');
                return `<option value="${s.numero_semana}">Semana ${s.numero_semana} (${dataInicio} a ${dataFim})</option>`;
            }).join('');
            
            document.querySelectorAll('.semana-select').forEach(select => {
                const valorAtual = select.value;
                select.innerHTML = '<option value="">Sem semana específica</option>' + semanasOptions;
                if (valorAtual) {
                    select.value = valorAtual;
                }
            });
        }
        
        // Atualizar semanas quando mês/ano mudar
        document.addEventListener('DOMContentLoaded', function() {
            const mesSelect = document.getElementById('cardapio-mes');
            const anoSelect = document.getElementById('cardapio-ano');
            
            if (mesSelect) {
                mesSelect.addEventListener('change', function() {
                    recalcularDatasSemanas();
                });
            }
            
            if (anoSelect) {
                anoSelect.addEventListener('change', function() {
                    recalcularDatasSemanas();
                });
            }
        });
        
        function recalcularDatasSemanas() {
            // Não recalcular automaticamente - o usuário escolhe o domingo
            // Apenas atualizar os selects se necessário
            atualizarSelectsSemana();
        }
        
        // Função para atualizar produtos quando a escola for selecionada
        function atualizarProdutosEstoque(escolaId) {
            const escolaIdParam = escolaId || document.getElementById('cardapio-escola-id')?.value || '<?= $escolaId ?? '' ?>';
            
            if (!escolaIdParam) {
                produtosDisponiveis = [];
                produtos = [];
                atualizarSelectsProduto();
                return;
            }
            
            console.log('Buscando produtos para escola:', escolaIdParam);
            
            fetch(`cardapios_nutricionista.php?acao=buscar_produtos_estoque&escola_id=${escolaIdParam}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Resposta não é JSON:', text.substring(0, 500));
                            throw new Error('Resposta do servidor não é JSON válido');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    if (data.success) {
                        produtosDisponiveis = data.produtos || [];
                        produtos = produtosDisponiveis;
                        atualizarSelectsProduto();
                        console.log('Produtos atualizados:', produtosDisponiveis.length, 'produtos disponíveis no estoque');
                        if (data.debug) {
                            console.log('Debug:', data.debug);
                        }
                        if (produtosDisponiveis.length === 0) {
                            console.warn('Nenhum produto encontrado no estoque da escola');
                            alert('Nenhum produto encontrado no estoque desta escola. Verifique se há produtos cadastrados no pacote da escola.');
                        }
                    } else {
                        console.warn('Aviso:', data.message);
                        alert('Erro ao buscar produtos: ' + (data.message || 'Erro desconhecido'));
                        produtosDisponiveis = [];
                        produtos = [];
                        atualizarSelectsProduto();
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar produtos:', error);
                    alert('Erro ao buscar produtos do estoque. Verifique o console para mais detalhes.');
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
                    const estoqueLote = parseFloat(p.estoque_quantidade || p.quantidade_estoque || 0);
                    // Usar quantidade_total_produto se disponível, senão usar estoque do lote
                    const estoqueTotal = parseFloat(p.quantidade_total_produto || estoqueLote || 0);
                    const semEstoque = estoqueTotal <= 0;
                    const desabilitado = jaSelecionado || semEstoque;
                    let motivo = '';
                    if (jaSelecionado) motivo = ' - Já adicionado';
                    else if (semEstoque) motivo = ' - Sem estoque';
                    
                    const option = document.createElement('option');
                    option.value = identificadorUnico;
                    option.disabled = desabilitado;
                    if (identificadorAtual) option.selected = true;
                    
                    // Armazenar tanto o estoque do lote quanto o total
                    if (estoqueLote > 0) {
                        option.setAttribute('data-estoque', estoqueLote);
                    }
                    if (estoqueTotal > 0) {
                        option.setAttribute('data-estoque-total', estoqueTotal);
                    }
                    option.setAttribute('data-produto-id', p.produto_id);
                    option.setAttribute('data-estoque-id', p.estoque_id);
                    
                    let textoOption = `${p.nome} (${p.unidade_medida})`;
                    // Mostrar quantidade total do produto
                    if (estoqueTotal > 0) {
                        const qtdFormatada = formatarQuantidade(estoqueTotal, p.unidade_medida);
                        textoOption += ` - Estoque Total: ${qtdFormatada}`;
                        // Se houver lote específico, mostrar também
                        if (p.lote && p.lote !== 'Sem lote' && estoqueLote < estoqueTotal) {
                            const qtdLoteFormatada = formatarQuantidade(estoqueLote, p.unidade_medida);
                            textoOption += ` (Lote: ${qtdLoteFormatada})`;
                        }
                    }
                    if (p.validade_formatada) {
                        textoOption += ` - Validade: ${p.validade_formatada}`;
                    }
                    if (p.lote && p.lote !== 'Sem lote') {
                        textoOption += ` - Lote: ${p.lote}`;
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
        
        function selecionarSemanaItem(itemIndex, numeroSemana) {
            const itemId = `item-${itemIndex}`;
            const itemAtual = itensCardapio.find(i => i.id === itemId);
            
            if (itemAtual) {
                itemAtual.numero_semana = numeroSemana ? parseInt(numeroSemana) : null;
            }
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
                quantidade: '',
                numero_semana: null
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
            
            // Criar select de semanas
            const semanasOptions = semanasCardapio.map(s => 
                `<option value="${s.numero_semana}">Semana ${s.numero_semana} (${new Date(s.data_inicio).toLocaleDateString('pt-BR')} - ${new Date(s.data_fim).toLocaleDateString('pt-BR')})</option>`
            ).join('');
            
            div.innerHTML = `
                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Semana</label>
                        <select class="semana-select w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-green focus:border-transparent" data-item-index="${itemIndex}" onchange="selecionarSemanaItem('${itemIndex}', this.value)">
                            <option value="">Sem semana específica</option>
                            ${semanasOptions}
                        </select>
                    </div>
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
                    quantidade: quantidade,
                    numero_semana: item.numero_semana || null
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
            
            // Preparar semanas para envio
            const semanas = semanasCardapio.map(s => {
                // Garantir que as datas estão corretas baseadas no domingo selecionado
                let dataInicio = s.data_inicio;
                let dataFim = s.data_fim;
                
                if (s.data_domingo) {
                    const datas = calcularDatasSemanaAPartirDomingo(s.data_domingo);
                    if (datas) {
                        dataInicio = datas.inicio;
                        dataFim = datas.fim;
                    }
                }
                
                return {
                    numero_semana: s.numero_semana,
                    observacao: s.observacao || '',
                    data_inicio: dataInicio || null,
                    data_fim: dataFim || null
                };
            });
            
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
            formData.append('semanas', JSON.stringify(semanas));
            
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
                .then(async response => {
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Resposta não é JSON:', text.substring(0, 200));
                        throw new Error('Resposta do servidor não é válida.');
                    }
                    return response.json();
                })
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
                const isRejeitado = statusCardapio === 'REJEITADO';
                const isAprovado = statusCardapio === 'APROVADO';
                
                let botoesAcoes = `<button onclick="visualizarCardapio(${c.id})" class="text-blue-600 hover:text-blue-900 mr-2">Ver</button>`;
                
                // Botões para rascunhos - TODOS os rascunhos podem ser editados, enviados e excluídos
                if (isRascunho) {
                    botoesAcoes += `
                        <button onclick="editarCardapio(${c.id})" class="text-green-600 hover:text-green-900 mr-2">Editar</button>
                        <button onclick="enviarCardapio(${c.id})" class="text-blue-600 hover:text-blue-900 mr-2">Publicar</button>
                        <button onclick="excluirCardapio(${c.id})" class="text-red-600 hover:text-red-900">Excluir</button>
                    `;
                }
                
                // Botões para cardápios publicados (não aprovados) - podem ser cancelados ou excluídos
                if (isPublicado) {
                    botoesAcoes += `
                        <button onclick="cancelarEnvioCardapio(${c.id})" class="text-orange-600 hover:text-orange-900 mr-2">Cancelar Publicação</button>
                        <button onclick="excluirCardapio(${c.id})" class="text-red-600 hover:text-red-900">Excluir</button>
                    `;
                }
                
                // Botões para cardápios rejeitados (não aprovados) - podem ser excluídos
                if (isRejeitado) {
                    botoesAcoes += `<button onclick="excluirCardapio(${c.id})" class="text-red-600 hover:text-red-900">Excluir</button>`;
                }
                
                // Cardápios aprovados não podem ser excluídos
                
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
            document.getElementById('semanas-container').innerHTML = '';
            document.getElementById('mensagem-sem-semanas').classList.remove('hidden');
            document.getElementById('modal-titulo').textContent = 'Novo Cardápio';
            itensCardapio = [];
            itemIndex = 0;
            semanasCardapio = [];
            semanaIndex = 0;
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
            // Abrir modal
            const modal = document.getElementById('modal-visualizar-cardapio');
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Mostrar loading
            document.getElementById('modal-cardapio-content').innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green"></div>
                    <p class="text-gray-600 mt-4">Carregando detalhes do cardápio...</p>
                </div>
            `;
            
            fetch(`cardapios_nutricionista.php?acao=buscar_cardapio&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.cardapio) {
                        const cardapio = data.cardapio;
                        const mesNome = new Date(2000, cardapio.mes - 1).toLocaleString('pt-BR', { month: 'long' });
                        
                        // Atualizar header
                        document.getElementById('modal-cardapio-escola').textContent = cardapio.escola_nome || 'Escola não informada';
                        
                        // Status badge
                        const statusClass = {
                            'RASCUNHO': 'bg-yellow-100 text-yellow-800',
                            'APROVADO': 'bg-green-100 text-green-800',
                            'PUBLICADO': 'bg-blue-100 text-blue-800',
                            'REJEITADO': 'bg-red-100 text-red-800'
                        }[cardapio.status?.toUpperCase()] || 'bg-gray-100 text-gray-800';
                        
                        // Organizar itens por semana
                        const itensPorSemana = {};
                        if (cardapio.itens && cardapio.itens.length > 0) {
                            cardapio.itens.forEach(item => {
                                const semanaKey = item.numero_semana || 'sem_semana';
                                if (!itensPorSemana[semanaKey]) {
                                    itensPorSemana[semanaKey] = {
                                        numero: item.numero_semana,
                                        observacao: item.semana_observacao,
                                        itens: []
                                    };
                                }
                                itensPorSemana[semanaKey].itens.push(item);
                            });
                        }
                        
                        // Buscar informações das semanas
                        const semanasInfo = {};
                        if (cardapio.semanas && cardapio.semanas.length > 0) {
                            cardapio.semanas.forEach(semana => {
                                semanasInfo[semana.numero_semana] = semana;
                            });
                        }
                        
                        // Construir HTML do conteúdo
                        let contentHtml = `
                            <div class="space-y-6">
                                <!-- Informações Gerais -->
                                <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Informações Gerais
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-500">Escola</p>
                                            <p class="text-base font-medium text-gray-900">${cardapio.escola_nome || 'N/A'}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Período</p>
                                            <p class="text-base font-medium text-gray-900">${mesNome}/${cardapio.ano}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Status</p>
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium ${statusClass}">
                                                ${cardapio.status || 'RASCUNHO'}
                                            </span>
                                        </div>
                                    </div>
                                    ${cardapio.observacoes ? `
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <p class="text-sm text-gray-500">Observações</p>
                                            <p class="text-sm text-gray-700 mt-1">${cardapio.observacoes}</p>
                                        </div>
                                    ` : ''}
                                </div>
                        `;
                        
                        // Adicionar semanas e itens
                        if (Object.keys(itensPorSemana).length > 0 || (cardapio.semanas && cardapio.semanas.length > 0)) {
                            // Ordenar semanas
                            const semanasOrdenadas = Object.keys(itensPorSemana).sort((a, b) => {
                                if (a === 'sem_semana') return 1;
                                if (b === 'sem_semana') return -1;
                                return parseInt(a) - parseInt(b);
                            });
                            
                            semanasOrdenadas.forEach(semanaKey => {
                                const semanaData = itensPorSemana[semanaKey];
                                const semanaInfo = semanasInfo[semanaData.numero] || {};
                                
                                const dataInicio = semanaInfo.data_inicio ? new Date(semanaInfo.data_inicio + 'T00:00:00').toLocaleDateString('pt-BR') : '';
                                const dataFim = semanaInfo.data_fim ? new Date(semanaInfo.data_fim + 'T00:00:00').toLocaleDateString('pt-BR') : '';
                                
                                contentHtml += `
                                    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200">
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                ${semanaData.numero ? `Semana ${semanaData.numero}` : 'Itens Gerais'}
                                                ${dataInicio && dataFim ? `<span class="text-sm font-normal text-gray-500 ml-2">(${dataInicio} a ${dataFim})</span>` : ''}
                                            </h4>
                                        </div>
                                        ${semanaData.observacao || semanaInfo.observacao ? `
                                            <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                                <p class="text-sm text-blue-800"><strong>Observação da Semana:</strong> ${semanaData.observacao || semanaInfo.observacao}</p>
                                            </div>
                                        ` : ''}
                                        ${semanaData.itens && semanaData.itens.length > 0 ? `
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        ${semanaData.itens.map(item => `
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="px-4 py-3 text-sm text-gray-900">${item.produto_nome || 'N/A'}</td>
                                                                <td class="px-4 py-3 text-sm text-gray-700 text-center font-medium">${item.quantidade || 0} ${item.unidade_medida || ''}</td>
                                                            </tr>
                                                        `).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        ` : '<p class="text-sm text-gray-500 text-center py-4">Nenhum item cadastrado para esta semana</p>'}
                                    </div>
                                `;
                            });
                        } else {
                            contentHtml += `
                                <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-200 text-center">
                                    <p class="text-gray-500">Nenhum item cadastrado neste cardápio</p>
                                </div>
                            `;
                        }
                        
                        contentHtml += `
                            </div>
                        `;
                        
                        document.getElementById('modal-cardapio-content').innerHTML = contentHtml;
                    } else {
                        document.getElementById('modal-cardapio-content').innerHTML = `
                            <div class="text-center py-8">
                                <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-gray-600">Erro ao carregar detalhes do cardápio</p>
                                <p class="text-sm text-gray-500 mt-2">${data.message || 'Cardápio não encontrado'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('modal-cardapio-content').innerHTML = `
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-600">Erro ao buscar detalhes do cardápio</p>
                            <p class="text-sm text-gray-500 mt-2">${error.message || 'Erro desconhecido'}</p>
                        </div>
                    `;
                });
        }
        
        function fecharModalVisualizarCardapio() {
            const modal = document.getElementById('modal-visualizar-cardapio');
            modal.style.display = 'none';
            modal.classList.add('hidden');
            modal.classList.remove('flex');
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
                        
                        // Limpar semanas e itens atuais
                        document.getElementById('semanas-container').innerHTML = '';
                        document.getElementById('mensagem-sem-semanas').classList.add('hidden');
                        semanasCardapio = [];
                        semanaIndex = 0;
                        
                        // Adicionar semanas do cardápio
                        if (c.semanas && c.semanas.length > 0) {
                            c.semanas.forEach(semana => {
                                adicionarSemana();
                                const ultimaSemana = semanasCardapio[semanasCardapio.length - 1];
                                if (ultimaSemana) {
                                    ultimaSemana.numero_semana = semana.numero_semana;
                                    ultimaSemana.observacao = semana.observacao || '';
                                    ultimaSemana.data_inicio = semana.data_inicio || null;
                                    ultimaSemana.data_fim = semana.data_fim || null;
                                    
                                    // Usar data_inicio como domingo se disponível
                                    if (semana.data_inicio) {
                                        const dataDomingo = new Date(semana.data_inicio);
                                        ultimaSemana.data_domingo = dataDomingo.toISOString().split('T')[0];
                                        
                                        // Atualizar input de data
                                        const semanaDiv = document.getElementById(ultimaSemana.id);
                                        if (semanaDiv) {
                                            const dataInput = semanaDiv.querySelector('.data-domingo-input');
                                            if (dataInput) {
                                                dataInput.value = ultimaSemana.data_domingo;
                                            }
                                            
                                            const periodoSpan = semanaDiv.querySelector(`.periodo-semana-${semanaIndex - 1}`);
                                            if (periodoSpan) {
                                                periodoSpan.textContent = `${new Date(semana.data_inicio).toLocaleDateString('pt-BR')} a ${new Date(semana.data_fim).toLocaleDateString('pt-BR')}`;
                                            }
                                            
                                            const textarea = semanaDiv.querySelector('textarea');
                                            if (textarea) {
                                                textarea.value = semana.observacao || '';
                                            }
                                        }
                                    }
                                }
                            });
                        }
                        
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
                                    const semanaSelect = document.querySelector(`.semana-select[data-item-index="${ultimoIndex}"]`);
                                    const produtoSelect = document.querySelector(`.produto-select[data-item-index="${ultimoIndex}"]`);
                                    const quantidadeInput = document.querySelector(`.quantidade-input[data-item-index="${ultimoIndex}"]`);
                                    
                                    // Associar semana se houver
                                    if (semanaSelect && item.numero_semana) {
                                        semanaSelect.value = item.numero_semana;
                                        selecionarSemanaItem(ultimoIndex, item.numero_semana);
                                    }
                                    
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
        
        // Fechar modal de visualização ao clicar fora dele
        const modalVisualizar = document.getElementById('modal-visualizar-cardapio');
        if (modalVisualizar) {
            modalVisualizar.addEventListener('click', function(e) {
                if (e.target === modalVisualizar) {
                    fecharModalVisualizarCardapio();
                }
            });
        }
        
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

