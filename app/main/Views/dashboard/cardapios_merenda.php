<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/merenda/CardapioModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Permitir acesso para ADM (geral), ADM_MERENDA e NUTRICIONISTA
$tipoUsuario = strtolower($_SESSION['tipo'] ?? '');
if (!isset($_SESSION['tipo']) || (!eAdm() && $tipoUsuario !== 'adm_merenda' && $tipoUsuario !== 'nutricionista')) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$cardapioModel = new CardapioModel();

// Buscar escola selecionada da sessão (selecionada no dashboard) - apenas para nutricionista
$escolaId = null;
$escolaNome = null;

if ($tipoUsuario === 'nutricionista') {
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
}

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
        // Log para debug
        error_log("cardapios_merenda.php - criar_cardapio - SESSION usuario_id: " . var_export($_SESSION['usuario_id'] ?? 'não definido', true));
        error_log("cardapios_merenda.php - criar_cardapio - SESSION tipo: " . var_export($_SESSION['tipo'] ?? 'não definido', true));
        error_log("cardapios_merenda.php - criar_cardapio - SESSION logado: " . var_export($_SESSION['logado'] ?? 'não definido', true));
        
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $pessoaId = $_SESSION['pessoa_id'] ?? null;
        
        // Verificar se o usuario_id existe e é válido
        if ($usuarioId) {
            $usuarioId = (int)$usuarioId;
            // Verificar se o usuário existe no banco antes de passar para o modelo
            try {
                $sqlCheck = "SELECT id, username, ativo, pessoa_id FROM usuario WHERE id = :usuario_id";
                $stmtCheck = $conn->prepare($sqlCheck);
                $stmtCheck->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmtCheck->execute();
                $usuarioCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                // Se não encontrou, tentar buscar pelo pessoa_id
                if (!$usuarioCheck && $pessoaId) {
                    error_log("cardapios_merenda.php - Usuário ID $usuarioId não encontrado, tentando buscar por pessoa_id: $pessoaId");
                    $sqlCheckPessoa = "SELECT id, username, ativo, pessoa_id FROM usuario WHERE pessoa_id = :pessoa_id";
                    $stmtCheckPessoa = $conn->prepare($sqlCheckPessoa);
                    $stmtCheckPessoa->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                    $stmtCheckPessoa->execute();
                    $usuarioCheck = $stmtCheckPessoa->fetch(PDO::FETCH_ASSOC);
                    
                    if ($usuarioCheck) {
                        $usuarioId = (int)$usuarioCheck['id'];
                        error_log("cardapios_merenda.php - Usuário encontrado por pessoa_id: usuario_id={$usuarioId}");
                    }
                }
                
                if (!$usuarioCheck) {
                    error_log("cardapios_merenda.php - ERRO: Usuário ID $usuarioId não encontrado no banco (tentou também pessoa_id: $pessoaId)");
                    echo json_encode(['success' => false, 'message' => "Erro: Usuário não encontrado no banco de dados. Por favor, faça login novamente."]);
                    exit;
                }
                
                error_log("cardapios_merenda.php - Usuário verificado: ID={$usuarioCheck['id']}, Username={$usuarioCheck['username']}, Ativo={$usuarioCheck['ativo']}, Pessoa_ID={$usuarioCheck['pessoa_id']}");
            } catch (Exception $e) {
                error_log("cardapios_merenda.php - Erro ao verificar usuário: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erro ao verificar usuário: ' . $e->getMessage()]);
                exit;
            }
        } elseif ($pessoaId) {
            // Se não tem usuario_id mas tem pessoa_id, tentar buscar
            try {
                $sqlCheckPessoa = "SELECT id, username, ativo, pessoa_id FROM usuario WHERE pessoa_id = :pessoa_id";
                $stmtCheckPessoa = $conn->prepare($sqlCheckPessoa);
                $stmtCheckPessoa->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                $stmtCheckPessoa->execute();
                $usuarioCheck = $stmtCheckPessoa->fetch(PDO::FETCH_ASSOC);
                
                if ($usuarioCheck) {
                    $usuarioId = (int)$usuarioCheck['id'];
                    error_log("cardapios_merenda.php - Usuário encontrado por pessoa_id: usuario_id={$usuarioId}");
                } else {
                    error_log("cardapios_merenda.php - ERRO: Nenhum usuário encontrado para pessoa_id: $pessoaId");
                    echo json_encode(['success' => false, 'message' => "Erro: Usuário não encontrado no banco de dados. Por favor, faça login novamente."]);
                    exit;
                }
            } catch (Exception $e) {
                error_log("cardapios_merenda.php - Erro ao buscar usuário por pessoa_id: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erro ao verificar usuário: ' . $e->getMessage()]);
                exit;
            }
        }
        
        $dados = [
            'escola_id' => $_POST['escola_id'] ?? null,
            'mes' => $_POST['mes'] ?? date('m'),
            'ano' => $_POST['ano'] ?? date('Y'),
            'itens' => json_decode($_POST['itens'] ?? '[]', true),
            'criado_por' => $usuarioId
        ];
        
        if ($dados['escola_id'] && !empty($dados['itens'])) {
            if (!$dados['criado_por']) {
                error_log("cardapios_merenda.php - ERRO: criado_por não definido");
                echo json_encode(['success' => false, 'message' => 'Usuário não identificado. Faça login novamente.']);
                exit;
            }
            $resultado = $cardapioModel->criar($dados);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'aprovar_cardapio' && !empty($_POST['cardapio_id'])) {
        $resultado = $cardapioModel->aprovar($_POST['cardapio_id']);
        echo json_encode($resultado);
        exit;
    }
    
    if ($_POST['acao'] === 'recusar_cardapio' && !empty($_POST['cardapio_id'])) {
        $observacoes = $_POST['observacoes'] ?? '';
        $resultado = $cardapioModel->rejeitar($_POST['cardapio_id'], $observacoes);
        echo json_encode($resultado);
        exit;
    }
    
    if ($_POST['acao'] === 'publicar_cardapio' && !empty($_POST['cardapio_id'])) {
        $resultado = $cardapioModel->enviar($_POST['cardapio_id']);
        echo json_encode($resultado);
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
    
    if ($_POST['acao'] === 'cancelar_publicacao' && !empty($_POST['cardapio_id'])) {
        $resultado = $cardapioModel->cancelarEnvio($_POST['cardapio_id']);
        echo json_encode($resultado);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_cardapios') {
        $filtros = [];
        
        // Para nutricionista, usar escola da sessão automaticamente
        if ($tipoUsuario === 'nutricionista') {
            if (isset($_SESSION['escola_selecionada_nutricionista_id']) && !empty($_SESSION['escola_selecionada_nutricionista_id'])) {
                $filtros['escola_id'] = $_SESSION['escola_selecionada_nutricionista_id'];
            }
            // Removido filtro criado_por para mostrar todos os cardápios da escola
        } else if ($tipoUsuario === 'adm_merenda') {
            // Para ADM_MERENDA, mostrar todos os status EXCETO RASCUNHO
            // Se não houver filtro de status específico, mostrar todos exceto RASCUNHO
            if (empty($_GET['status'])) {
                // Não aplicar filtro de status aqui - vamos filtrar depois para excluir RASCUNHO
            } else {
                // Se houver filtro de status, aplicar normalmente (mas não permitir RASCUNHO)
                if ($_GET['status'] !== 'RASCUNHO') {
                    $filtros['status'] = $_GET['status'];
                } else {
                    // Se tentar filtrar por RASCUNHO, retornar vazio
                    echo json_encode(['success' => true, 'cardapios' => []]);
                    exit;
                }
            }
            // Permitir filtro manual de escola
            if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        } else {
            // Para ADM (geral) e NUTRICIONISTA, permitir filtro manual
            // Quando não há filtro de status, mostrar TODOS os status (incluindo REJEITADO)
            if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        }
        
        if (!empty($_GET['mes'])) $filtros['mes'] = $_GET['mes'];
        if (!empty($_GET['ano'])) $filtros['ano'] = $_GET['ano'];
        // Se não for ADM_MERENDA, permitir filtro de status manual
        // Quando não há filtro, mostrar TODOS os status (incluindo REJEITADO)
        if ($tipoUsuario !== 'adm_merenda' && !empty($_GET['status'])) {
            $filtros['status'] = $_GET['status'];
        }
        
        $cardapios = $cardapioModel->listar($filtros);
        
        // Para ADM_MERENDA, filtrar RASCUNHO se não houver filtro de status específico
        if ($tipoUsuario === 'adm_merenda' && empty($_GET['status'])) {
            $cardapios = array_filter($cardapios, function($cardapio) {
                return strtoupper($cardapio['status'] ?? '') !== 'RASCUNHO';
            });
            // Reindexar array após filtro
            $cardapios = array_values($cardapios);
        }
        
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
        $escolaId = $_GET['escola_id'] ?? null;
        
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
            // Primeiro, identificar quais produtos estão no estoque da escola
            $sqlProdutosEscola = "SELECT DISTINCT p.id as produto_id
                                  FROM produto p
                                  INNER JOIN pacote_escola_item pei ON p.id = pei.produto_id
                                  INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                                  WHERE p.ativo = 1 
                                  AND pe.escola_id = :escola_id
                                  AND (SELECT SUM(pei2.quantidade) 
                                       FROM pacote_escola_item pei2 
                                       INNER JOIN pacote_escola pe2 ON pei2.pacote_id = pe2.id 
                                       WHERE pei2.produto_id = p.id AND pe2.escola_id = :escola_id) > 0";
            
            $stmtProdutosEscola = $conn->prepare($sqlProdutosEscola);
            $stmtProdutosEscola->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmtProdutosEscola->execute();
            $produtosEscola = $stmtProdutosEscola->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($produtosEscola)) {
                echo json_encode(['success' => true, 'produtos' => []]);
                exit;
            }
            
            // Agora buscar todos os lotes desses produtos do estoque_central
            $placeholders = implode(',', array_fill(0, count($produtosEscola), '?'));
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
                    INNER JOIN estoque_central ec ON p.id = ec.produto_id
                    WHERE p.id IN ($placeholders)
                    AND p.ativo = 1
                    AND ec.quantidade > 0
                    ORDER BY p.nome ASC, ec.validade ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($produtosEscola);
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
            error_log("Erro AJAX ao buscar produtos do estoque: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor', 'produtos' => []]);
        }
        exit;
    }
}

// Buscar cardápios para exibição inicial conforme o tipo de usuário
$filtrosInicial = ['ano' => date('Y')];
if ($tipoUsuario === 'nutricionista') {
    // Nutricionista vê todos os cardápios da escola selecionada
    if (isset($escolaId) && $escolaId) {
        $filtrosInicial['escola_id'] = $escolaId;
    }
    // Removido filtro criado_por para mostrar todos os cardápios da escola
} else if ($tipoUsuario === 'adm_merenda') {
    // ADM_MERENDA vê todos os cardápios EXCETO RASCUNHO (não aplicar filtro de status aqui)
    // O filtro será aplicado depois
}
$cardapios = $cardapioModel->listar($filtrosInicial);

// Para ADM_MERENDA, filtrar RASCUNHO da listagem inicial
if ($tipoUsuario === 'adm_merenda') {
    $cardapios = array_filter($cardapios, function($cardapio) {
        return strtoupper($cardapio['status'] ?? '') !== 'RASCUNHO';
    });
    // Reindexar array após filtro
    $cardapios = array_values($cardapios);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápios - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="js/modal-alerts.js"></script>
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
    <?php 
    if (eAdm()) {
        include 'components/sidebar_adm.php';
    } elseif ($tipoUsuario === 'nutricionista') {
        include 'components/sidebar_nutricionista.php';
    } else {
        include 'components/sidebar_merenda.php';
    }
    ?>
    
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
                            <?php if (eAdm() || $tipoUsuario === 'adm_merenda') { ?>
                                <!-- Para ADM, texto simples com padding para alinhamento -->
                                <div class="text-right px-4 py-2">
                                    <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                    <p class="text-xs text-gray-500">Órgão Central</p>
                                </div>
                            <?php } elseif ($tipoUsuario === 'nutricionista') { ?>
                                <!-- Para nutricionista, mostrar escola selecionada -->
                                <div class="bg-primary-green text-white px-5 py-2.5 rounded-lg shadow-md text-sm font-semibold">
                                    <span><?= htmlspecialchars($escolaNome ?? 'Nenhuma escola selecionada') ?></span>
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
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Cardápios Escolares</h2>
                        <p class="text-gray-600 mt-1">Cadastre e gerencie os cardápios das escolas</p>
                    </div>
                    <?php if ($tipoUsuario === 'nutricionista' || eAdm()): ?>
                    <button onclick="abrirModalNovoCardapio()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Cardápio</span>
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-<?= ($tipoUsuario === 'nutricionista') ? '3' : '4' ?> gap-4">
                        <?php if ($tipoUsuario !== 'nutricionista'): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarCardapios()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
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
                                <?php if ($tipoUsuario === 'nutricionista' || eAdm()): ?>
                                    <option value="RASCUNHO">Rascunho</option>
                                <?php endif; ?>
                                <option value="APROVADO">Aprovado</option>
                                <option value="PUBLICADO">Publicado</option>
                                <option value="REJEITADO">Rejeitado</option>
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
                                                $statusCardapio = strtoupper($cardapio['status'] ?? 'RASCUNHO');
                                                echo $statusCardapio === 'APROVADO' ? 'bg-green-100 text-green-800' : 
                                                    ($statusCardapio === 'PUBLICADO' ? 'bg-blue-100 text-blue-800' : 
                                                    ($statusCardapio === 'RASCUNHO' ? 'bg-yellow-100 text-yellow-800' : 
                                                    ($statusCardapio === 'REJEITADO' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')));
                                            ?>">
                                                <?= htmlspecialchars($cardapio['status'] ?? 'RASCUNHO') ?>
                                            </span>
                                            <button onclick="verDetalhesCardapio(<?= $cardapio['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Ver
                                            </button>
                                            <?php if ($tipoUsuario === 'adm_merenda' && strtoupper($cardapio['status'] ?? '') === 'PUBLICADO'): ?>
                                                <button onclick="aprovarCardapio(<?= $cardapio['id'] ?>)" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                    Aprovar
                                                </button>
                                                <button onclick="recusarCardapio(<?= $cardapio['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
                                                    Recusar
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($tipoUsuario === 'nutricionista' && strtoupper($cardapio['status'] ?? '') === 'PUBLICADO' && $cardapio['criado_por'] == $_SESSION['usuario_id']): ?>
                                                <button onclick="cancelarPublicacaoCardapio(<?= $cardapio['id'] ?>)" class="text-orange-600 hover:text-orange-700 font-medium text-sm">
                                                    Cancelar Publicação
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($tipoUsuario === 'adm_merenda' && strtoupper($cardapio['status'] ?? '') === 'RASCUNHO'): ?>
                                                <button onclick="editarCardapio(<?= $cardapio['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                    Editar
                                                </button>
                                                <button onclick="publicarCardapio(<?= $cardapio['id'] ?>)" class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                    Enviar
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($tipoUsuario === 'nutricionista' && $cardapio['status'] === 'RASCUNHO' && $cardapio['criado_por'] == $_SESSION['usuario_id']): ?>
                                                <button onclick="editarCardapio(<?= $cardapio['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                    Editar
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
        <div class="bg-primary-green text-white p-6 flex items-center justify-between shadow-lg">
            <h3 class="text-2xl font-bold">Novo Cardápio</h3>
            <button onclick="fecharModalNovoCardapio()" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <div class="max-w-6xl mx-auto space-y-6">
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações do Cardápio</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                            <select id="cardapio-escola-id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" onchange="atualizarProdutosEstoque(this.value)">
                                <option value="">Selecione uma escola</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
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
                    <p id="mensagem-sem-itens" class="text-center text-gray-500 py-8">
                        Nenhum item adicionado. Clique em "Adicionar Item" para começar.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 border-t border-gray-200 p-6">
            <div class="max-w-6xl mx-auto flex space-x-3">
                <button onclick="fecharModalNovoCardapio()" class="flex-1 px-6 py-3 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarCardapio()" class="flex-1 px-6 py-3 text-white bg-primary-green hover:bg-green-700 rounded-lg font-medium transition-colors">
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
        let produtosDisponiveis = <?= json_encode($produtos) ?>; // Produtos disponíveis (será atualizado quando escola for selecionada)
        let itensCardapio = []; // Array para armazenar os itens do cardápio com identificador_unico
        let cardapioEditandoId = null; // ID do cardápio sendo editado

        function abrirModalNovoCardapio() {
            cardapioEditandoId = null;
            document.getElementById('modal-novo-cardapio').classList.remove('hidden');
            document.querySelector('#modal-novo-cardapio h3').textContent = 'Novo Cardápio';
            itemIndex = 0;
            itensCardapio = [];
            document.getElementById('itens-cardapio-container').innerHTML = '';
            document.getElementById('mensagem-sem-itens').classList.remove('hidden');
            // Resetar produtos para lista completa inicialmente
            produtosDisponiveis = <?= json_encode($produtos) ?>;
            // Limpar seleção de escola
            document.getElementById('cardapio-escola-id').value = '';
            document.getElementById('cardapio-mes').value = '<?= date('m') ?>';
            document.getElementById('cardapio-ano').value = '<?= date('Y') ?>';
            // Mostrar mensagem de sem itens
            const mensagemSemItens = document.getElementById('mensagem-sem-itens');
            if (mensagemSemItens) {
                mensagemSemItens.classList.remove('hidden');
            }
        }

        function fecharModalNovoCardapio() {
            document.getElementById('modal-novo-cardapio').classList.add('hidden');
            cardapioEditandoId = null;
            itemIndex = 0;
            itensCardapio = [];
            document.getElementById('itens-cardapio-container').innerHTML = '';
            document.getElementById('mensagem-sem-itens').classList.remove('hidden');
        }
        
        function editarCardapio(id) {
            fetch(`cardapios_merenda.php?acao=buscar_cardapio&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.cardapio) {
                        const c = data.cardapio;
                        
                        // Verificar se está como RASCUNHO
                        if (c.status !== 'RASCUNHO') {
                            showWarningAlert('Apenas cardápios em rascunho podem ser editados.', 'Atenção');
                            return;
                        }
                        
                        cardapioEditandoId = id;
                        document.querySelector('#modal-novo-cardapio h3').textContent = 'Editar Cardápio';
                        
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
                                    
                                    // Encontrar o produto correspondente
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
                        showErrorAlert('Erro ao carregar cardápio para edição', 'Erro');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showErrorAlert('Erro ao carregar cardápio', 'Erro');
                });
        }

        function atualizarProdutosEstoque(escolaId) {
            if (!escolaId) {
                // Se não há escola selecionada, usar todos os produtos
                produtosDisponiveis = <?= json_encode($produtos) ?>;
                atualizarSelectsProduto();
                return;
            }

            fetch(`?acao=buscar_produtos_estoque&escola_id=${escolaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        produtosDisponiveis = data.produtos;
                        atualizarSelectsProduto();
                        console.log('Produtos atualizados para escola:', escolaId, produtosDisponiveis);
                    } else {
                        console.error('Erro ao buscar produtos do estoque:', data.message);
                        produtosDisponiveis = [];
                        atualizarSelectsProduto();
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição AJAX para produtos do estoque:', error);
                    produtosDisponiveis = [];
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
            // Atualizar todos os selects de produto existentes
            document.querySelectorAll('.produto-select').forEach(select => {
                const valorAtual = select.value;
                const itemIndex = select.dataset.itemIndex;
                const itemAtual = itensCardapio.find(i => i.id === `item-${itemIndex}`);
                
                // Identificadores únicos já selecionados em outros itens (exceto o item atual)
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
                    
                    // Adicionar data attributes
                    if (estoque > 0) {
                        option.setAttribute('data-estoque', estoque);
                    }
                    option.setAttribute('data-produto-id', p.produto_id || p.id);
                    option.setAttribute('data-estoque-id', p.estoque_id || 0);
                    
                    // Montar texto do option com nome, quantidade e validade
                    let textoOption = `${p.nome} (${p.unidade_medida})`;
                    
                    // Adicionar quantidade em estoque
                    if (estoque > 0) {
                        const qtdFormatada = formatarQuantidade(estoque, p.unidade_medida);
                        textoOption += ` - Estoque: ${qtdFormatada}`;
                    }
                    
                    // Adicionar validade
                    if (p.validade_formatada) {
                        textoOption += ` - Validade: ${p.validade_formatada}`;
                    } else if (p.validade) {
                        try {
                            const dataValidade = new Date(p.validade);
                            if (!isNaN(dataValidade.getTime())) {
                                textoOption += ` - Validade: ${dataValidade.toLocaleDateString('pt-BR')}`;
                            }
                        } catch (e) {
                            // Ignorar erro de formatação
                        }
                    }
                    
                    textoOption += motivo;
                    option.textContent = textoOption;
                    select.appendChild(option);
                });
                
                // Atualizar max do input de quantidade se houver produto selecionado
                if (valorAtual && itemAtual && itemAtual.identificador_unico) {
                    atualizarMaxQuantidade(itemIndex);
                } else {
                    // Limpar max do input de quantidade
                    const quantidadeInput = document.querySelector(`.quantidade-input[data-item-index="${itemIndex}"]`);
                    if (quantidadeInput) {
                        quantidadeInput.removeAttribute('max');
                        quantidadeInput.removeAttribute('data-estoque-max');
                        quantidadeInput.value = '';
                    }
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
            
            // Verificar se já foi adicionado
            if (produtoJaAdicionado(identificadorUnico, itemId)) {
                showWarningAlert('Este produto já foi adicionado ao cardápio. Você pode adicionar o mesmo produto de um lote diferente.', 'Atenção');
                const select = document.querySelector(`.produto-select[data-item-index="${itemIndex}"]`);
                if (select && itemAtual) {
                    select.value = itemAtual.identificador_unico || '';
                }
                return;
            }
            
            // Buscar produto selecionado
            const produtoSelecionado = produtosDisponiveis.find(p => {
                const idUnico = p.identificador_unico || `${p.produto_id}:${p.estoque_id || 0}`;
                return idUnico === identificadorUnico;
            });
            
            if (!produtoSelecionado) {
                return;
            }
            
            // Atualizar item
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
                
                // Se o valor atual for maior que o estoque, ajustar
                const valorAtual = parseFloat(quantidadeInput.value || 0);
                if (valorAtual > estoqueDisponivel) {
                    quantidadeInput.value = estoqueDisponivel;
                    showInfoAlert(`A quantidade foi ajustada para o máximo disponível em estoque: ${formatarQuantidade(estoqueDisponivel, '')}`, 'Ajuste Automático');
                }
            } else {
                quantidadeInput.removeAttribute('max');
                quantidadeInput.removeAttribute('data-estoque-max');
            }
        }

        function adicionarItemCardapio() {
            const container = document.getElementById('itens-cardapio-container');
            const mensagemSemItens = document.getElementById('mensagem-sem-itens');
            
            // Ocultar mensagem se houver itens
            if (mensagemSemItens) {
                mensagemSemItens.classList.add('hidden');
            }
            
            const itemId = `item-${itemIndex}`;
            
            // Adicionar item ao array
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
            
            // Buscar identificadores já selecionados
            const outrosIdentificadoresSelecionados = itensCardapio
                .filter(i => i.id !== itemId && i.identificador_unico)
                .map(i => String(i.identificador_unico));
            
            // Montar options com informações de estoque e validade
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
                
                // Adicionar quantidade em estoque
                if (estoque > 0) {
                    const qtdFormatada = formatarQuantidade(estoque, p.unidade_medida);
                    textoOption += ` - Estoque: ${qtdFormatada}`;
                    dataEstoque = `data-estoque="${estoque}"`;
                }
                
                // Adicionar validade
                if (p.validade_formatada) {
                    textoOption += ` - Validade: ${p.validade_formatada}`;
                } else if (p.validade) {
                    try {
                        const dataValidade = new Date(p.validade);
                        if (!isNaN(dataValidade.getTime())) {
                            textoOption += ` - Validade: ${dataValidade.toLocaleDateString('pt-BR')}`;
                        }
                    } catch (e) {
                        // Ignorar erro de formatação
                    }
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
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
            
            // Atualizar quantidade no array
            const itemAtual = itensCardapio.find(i => i.id === itemId);
            if (itemAtual) {
                itemAtual.quantidade = quantidade;
            }
            
            if (estoqueMax > 0 && quantidade > estoqueMax) {
                input.value = estoqueMax;
                if (itemAtual) {
                    itemAtual.quantidade = estoqueMax;
                }
                showWarningAlert(`A quantidade não pode ser maior que o estoque disponível (${formatarQuantidade(estoqueMax, '')}).`, 'Validação');
                input.focus();
            }
        }

        function removerItemCardapio(index) {
            const itemId = `item-${index}`;
            const item = document.getElementById(itemId);
            if (item) {
                item.remove();
            }
            
            // Remover do array
            itensCardapio = itensCardapio.filter(i => i.id !== itemId);
            
            // Atualizar selects para habilitar produtos removidos
            atualizarSelectsProduto();
            
            // Mostrar mensagem se não houver mais itens
            const container = document.getElementById('itens-cardapio-container');
            const mensagemSemItens = document.getElementById('mensagem-sem-itens');
            if (container && mensagemSemItens && container.children.length === 0) {
                mensagemSemItens.classList.remove('hidden');
            }
        }

        function salvarCardapio() {
            const escolaId = document.getElementById('cardapio-escola-id').value;
            const mes = document.getElementById('cardapio-mes').value;
            const ano = document.getElementById('cardapio-ano').value;
            
            if (!escolaId || !mes || !ano) {
                showWarningAlert('Por favor, preencha todos os campos obrigatórios.', 'Validação');
                return;
            }
            
            const itens = [];
            let erroValidacao = false;
            let mensagemErro = '';
            
            // Validar identificadores únicos duplicados
            const identificadoresUnicos = [];
            for (const item of itensCardapio) {
                if (item.identificador_unico && item.produto_id) {
                    if (identificadoresUnicos.includes(item.identificador_unico)) {
                        erroValidacao = true;
                        mensagemErro = 'Há produtos duplicados no cardápio. Por favor, remova as duplicatas.';
                        break;
                    }
                    identificadoresUnicos.push(item.identificador_unico);
                }
            }
            
            if (!erroValidacao) {
                // Coletar itens válidos
                itensCardapio.forEach(item => {
                    const quantidadeInput = document.querySelector(`.quantidade-input[data-item-index="${item.id.replace('item-', '')}"]`);
                    const quantidade = quantidadeInput ? parseFloat(quantidadeInput.value || 0) : 0;
                    
                    if (item.identificador_unico && item.produto_id && quantidade > 0) {
                        const estoqueMax = quantidadeInput ? parseFloat(quantidadeInput.getAttribute('data-estoque-max') || 0) : 0;
                        
                        // Validar se a quantidade não excede o estoque
                        if (estoqueMax > 0 && quantidade > estoqueMax) {
                            erroValidacao = true;
                            const produtoSelecionado = produtosDisponiveis.find(p => {
                                const idUnico = p.identificador_unico || `${p.produto_id || p.id}:${p.estoque_id || 0}`;
                                return idUnico === item.identificador_unico;
                            });
                            const nomeProduto = produtoSelecionado ? produtoSelecionado.nome : 'Produto';
                            mensagemErro = `A quantidade de "${nomeProduto}" (${formatarQuantidade(quantidade, '')}) excede o estoque disponível (${formatarQuantidade(estoqueMax, '')}).`;
                            return;
                        }
                        
                        itens.push({
                            produto_id: item.produto_id,
                            quantidade: quantidade
                        });
                    }
                });
            }
            
            if (erroValidacao) {
                showErrorAlert(mensagemErro, 'Erro');
                return;
            }
            
            if (itens.length === 0) {
                showWarningAlert('Adicione pelo menos um item ao cardápio.', 'Validação');
                return;
            }
            
            const formData = new FormData();
            if (cardapioEditandoId) {
                formData.append('acao', 'editar_cardapio');
                formData.append('cardapio_id', cardapioEditandoId);
            } else {
                formData.append('acao', 'criar_cardapio');
            }
            formData.append('escola_id', escolaId);
            formData.append('mes', mes);
            formData.append('ano', ano);
            formData.append('itens', JSON.stringify(itens));
            
            fetch('cardapios_merenda.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAlert(cardapioEditandoId ? 'Cardápio atualizado com sucesso!' : 'Cardápio criado com sucesso!', 'Sucesso');
                    fecharModalNovoCardapio();
                    filtrarCardapios();
                } else {
                    showErrorAlert('Erro ao salvar cardápio: ' + (data.message || 'Erro desconhecido'), 'Erro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao salvar cardápio.', 'Erro');
            });
        }

        function filtrarCardapios() {
            const filtroEscola = document.getElementById('filtro-escola');
            const escolaId = filtroEscola ? filtroEscola.value : '';
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
                        
                        // Para ADM_MERENDA, filtrar RASCUNHO também no frontend (segurança adicional)
                        let cardapiosFiltrados = data.cardapios;
                        const tipoUsuarioAtual = '<?= $tipoUsuario ?>';
                        if (tipoUsuarioAtual === 'adm_merenda') {
                            cardapiosFiltrados = cardapiosFiltrados.filter(c => {
                                const status = (c.status || '').toUpperCase();
                                return status !== 'RASCUNHO';
                            });
                        }
                        
                        // Debug: verificar se há cardápios REJEITADOS
                        const rejeitados = cardapiosFiltrados.filter(c => (c.status || '').toUpperCase() === 'REJEITADO');
                        console.log('Total de cardápios:', cardapiosFiltrados.length);
                        console.log('Cardápios REJEITADOS:', rejeitados.length);
                        
                        if (cardapiosFiltrados.length === 0) {
                            container.innerHTML = '<div class="text-center py-12"><p class="text-gray-600">Nenhum cardápio encontrado.</p></div>';
                            return;
                        }
                        
                        cardapiosFiltrados.forEach(cardapio => {
                            const statusCardapio = (cardapio.status || 'RASCUNHO').toUpperCase();
                            const statusClass = {
                                'RASCUNHO': 'bg-yellow-100 text-yellow-800',
                                'APROVADO': 'bg-green-100 text-green-800',
                                'PUBLICADO': 'bg-blue-100 text-blue-800',
                                'REJEITADO': 'bg-red-100 text-red-800'
                            }[statusCardapio] || 'bg-gray-100 text-gray-800';
                            const mesNome = new Date(2000, cardapio.mes - 1).toLocaleString('pt-BR', { month: 'long' });
                            const tipoUsuarioAtual = '<?= $tipoUsuario ?>';
                            
                            let botoesAcoes = `<button onclick="verDetalhesCardapio(${cardapio.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">Ver</button>`;
                            
                            // Botões para ADM_MERENDA
                            if (tipoUsuarioAtual === 'adm_merenda') {
                                if (statusCardapio === 'PUBLICADO') {
                                    botoesAcoes += `
                                        <button onclick="aprovarCardapio(${cardapio.id})" class="text-green-600 hover:text-green-700 font-medium text-sm">Aprovar</button>
                                        <button onclick="recusarCardapio(${cardapio.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">Recusar</button>
                                    `;
                                } else if (statusCardapio === 'RASCUNHO') {
                                    botoesAcoes += `
                                        <button onclick="editarCardapio(${cardapio.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">Editar</button>
                                        <button onclick="publicarCardapio(${cardapio.id})" class="text-green-600 hover:text-green-700 font-medium text-sm">Enviar</button>
                                    `;
                                }
                            }
                            
                            // Botões para nutricionista
                            if (tipoUsuarioAtual === 'nutricionista') {
                                if (statusCardapio === 'RASCUNHO' && cardapio.criado_por == <?= $_SESSION['usuario_id'] ?? 0 ?>) {
                                    botoesAcoes += `<button onclick="editarCardapio(${cardapio.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">Editar</button>`;
                                } else if (statusCardapio === 'PUBLICADO' && cardapio.criado_por == <?= $_SESSION['usuario_id'] ?? 0 ?>) {
                                    botoesAcoes += `<button onclick="cancelarPublicacaoCardapio(${cardapio.id})" class="text-orange-600 hover:text-orange-700 font-medium text-sm">Cancelar Publicação</button>`;
                                }
                            }
                            
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
                                            ${botoesAcoes}
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
            if (!confirm('Deseja realmente aprovar este cardápio?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'aprovar_cardapio');
            formData.append('cardapio_id', id);
            
            fetch('cardapios_merenda.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAlert('Cardápio aprovado com sucesso!', 'Sucesso');
                    filtrarCardapios();
                } else {
                    showErrorAlert('Erro ao aprovar cardápio: ' + (data.message || 'Erro desconhecido'), 'Erro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao aprovar cardápio.', 'Erro');
            });
        }
        
        function recusarCardapio(id) {
            const observacoes = prompt('Informe o motivo da recusa (opcional):');
            if (observacoes === null) {
                return; // Usuário cancelou
            }
            
            if (!confirm('Deseja realmente recusar este cardápio?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'recusar_cardapio');
            formData.append('cardapio_id', id);
            formData.append('observacoes', observacoes || '');
            
            fetch('cardapios_merenda.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAlert('Cardápio recusado com sucesso!', 'Sucesso');
                    filtrarCardapios();
                } else {
                    showErrorAlert('Erro ao recusar cardápio: ' + (data.message || 'Erro desconhecido'), 'Erro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao recusar cardápio.', 'Erro');
            });
        }
        
        function publicarCardapio(id) {
            if (!confirm('Deseja realmente publicar este cardápio? Ele será enviado para aprovação.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'publicar_cardapio');
            formData.append('cardapio_id', id);
            
            fetch('cardapios_merenda.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAlert('Cardápio publicado com sucesso!', 'Sucesso');
                    filtrarCardapios();
                } else {
                    showErrorAlert('Erro ao publicar cardápio: ' + (data.message || 'Erro desconhecido'), 'Erro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao publicar cardápio.', 'Erro');
            });
        }
        
        function cancelarPublicacaoCardapio(id) {
            if (!confirm('Deseja realmente cancelar a publicação deste cardápio? Ele voltará para o status de rascunho e poderá ser editado novamente.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'cancelar_publicacao');
            formData.append('cardapio_id', id);
            
            fetch('cardapios_merenda.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessAlert('Publicação do cardápio cancelada com sucesso! O cardápio voltou para rascunho.', 'Sucesso');
                    filtrarCardapios();
                } else {
                    showErrorAlert('Erro ao cancelar publicação: ' + (data.message || 'Erro desconhecido'), 'Erro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showErrorAlert('Erro ao cancelar publicação', 'Erro');
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
                        
                        showInfoAlert(`Cardápio: ${cardapio.escola_nome}\nPeríodo: ${mesNome}/${cardapio.ano}\nStatus: ${cardapio.status || 'RASCUNHO'}\n\nItens:\n${cardapio.itens ? cardapio.itens.map(i => `- ${i.produto_nome}: ${i.quantidade} ${i.unidade_medida}`).join('\n') : 'Nenhum item'}`, 'Detalhes do Cardápio');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showErrorAlert('Erro ao buscar detalhes do cardápio.', 'Erro');
                });
        }
        
        // Carregar cardápios ao iniciar a página (substituir renderização inicial por AJAX)
        document.addEventListener('DOMContentLoaded', function() {
            filtrarCardapios();
        });
    </script>
</body>
</html>

