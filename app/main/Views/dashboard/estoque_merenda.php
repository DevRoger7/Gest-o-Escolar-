<?php
// Iniciar output buffering para evitar output antes do JSON
ob_start();

require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Permitir acesso para ADM (geral) e ADM_MERENDA
if (!isset($_SESSION['tipo']) || (!eAdm() && strtolower($_SESSION['tipo']) !== 'adm_merenda')) {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar fornecedores
$sqlFornecedores = "SELECT id, nome FROM fornecedor WHERE ativo = 1 ORDER BY nome ASC";
$stmtFornecedores = $conn->prepare($sqlFornecedores);
$stmtFornecedores->execute();
$fornecedores = $stmtFornecedores->fetchAll(PDO::FETCH_ASSOC);

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos
$sqlProdutos = "SELECT id, nome, unidade_medida, estoque_minimo FROM produto WHERE ativo = 1 ORDER BY nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    // Limpar qualquer output anterior (incluindo warnings/notices)
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Desabilitar exibição de erros para não quebrar o JSON
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
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
            // O estoque central armazena apenas informações físicas do produto
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
            $estoqueId = $conn->lastInsertId();
            
            // 2. REGISTRAR EM CUSTOS (COM VALORES - separado do estoque)
            // Os custos são registrados separadamente quando há valor unitário informado
            if ($valorUnitario !== null && $valorUnitario > 0 && $quantidade > 0) {
                // Buscar nome do produto para descrição
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
                $usuarioId = $_SESSION['usuario_id'] ?? null;
                
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
                $stmtCusto->bindParam(':fornecedor_id', $fornecedorId);
                $stmtCusto->bindParam(':quantidade', $quantidade);
                $stmtCusto->bindParam(':valor_unitario', $valorUnitario);
                $stmtCusto->bindParam(':valor_total', $valorTotal);
                $stmtCusto->bindParam(':data', $dataAtual);
                $stmtCusto->bindParam(':mes', $mesAtual);
                $stmtCusto->bindParam(':ano', $anoAtual);
                $stmtCusto->bindParam(':observacoes', $observacoes);
                $stmtCusto->bindParam(':registrado_por', $usuarioId);
                $stmtCusto->execute();
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'id' => $conn->lastInsertId()], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Erro inesperado: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    if ($_POST['acao'] === 'sincronizar_custos') {
        try {
            // IMPORTANTE: Esta função sincroniza apenas entradas ANTIGAS que ainda têm valores no estoque_central
            // Entradas novas já são registradas automaticamente em custos quando há valor_unitario informado
            // Buscar entradas antigas do estoque que têm valor mas não têm custo registrado
            $sql = "SELECT ec.*, p.nome as produto_nome
                    FROM estoque_central ec
                    INNER JOIN produto p ON ec.produto_id = p.id
                    WHERE ec.valor_total IS NOT NULL 
                    AND ec.valor_total > 0
                    AND NOT EXISTS (
                        SELECT 1 FROM custo_merenda cm 
                        WHERE cm.produto_id = ec.produto_id 
                        AND cm.data = DATE(ec.criado_em)
                        AND cm.valor_total = ec.valor_total
                        AND cm.descricao LIKE CONCAT('%', p.nome, '%')
                    )
                    ORDER BY ec.criado_em DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $conn->beginTransaction();
            $sincronizados = 0;
            $erros = [];
            
            foreach ($entradas as $entrada) {
                try {
                    $descricao = "Entrada de estoque: " . $entrada['produto_nome'];
                    if ($entrada['lote']) {
                        $descricao .= " - Lote: " . $entrada['lote'];
                    }
                    if ($entrada['nota_fiscal']) {
                        $descricao .= " - NF: " . $entrada['nota_fiscal'];
                    }
                    
                    $dataEntrada = date('Y-m-d', strtotime($entrada['criado_em']));
                    $mes = date('n', strtotime($entrada['criado_em']));
                    $ano = date('Y', strtotime($entrada['criado_em']));
                    $usuarioId = $_SESSION['usuario_id'] ?? null;
                    
                    $observacoes = "Sincronização automática do estoque central";
                    if ($entrada['validade']) {
                        $observacoes .= " - Validade: " . date('d/m/Y', strtotime($entrada['validade']));
                    }
                    
                    $sqlCusto = "INSERT INTO custo_merenda (escola_id, tipo, descricao, produto_id, fornecedor_id,
                                quantidade, valor_unitario, valor_total, data, mes, ano, observacoes, registrado_por, registrado_em)
                                VALUES (NULL, 'COMPRA_PRODUTOS', :descricao, :produto_id, :fornecedor_id,
                                :quantidade, :valor_unitario, :valor_total, :data, :mes, :ano, :observacoes, :registrado_por, NOW())";
                    
                    $stmtCusto = $conn->prepare($sqlCusto);
                    $stmtCusto->bindParam(':descricao', $descricao);
                    $stmtCusto->bindParam(':produto_id', $entrada['produto_id']);
                    $stmtCusto->bindParam(':fornecedor_id', $entrada['fornecedor_id']);
                    $stmtCusto->bindParam(':quantidade', $entrada['quantidade']);
                    $stmtCusto->bindParam(':valor_unitario', $entrada['valor_unitario']);
                    $stmtCusto->bindParam(':valor_total', $entrada['valor_total']);
                    $stmtCusto->bindParam(':data', $dataEntrada);
                    $stmtCusto->bindParam(':mes', $mes);
                    $stmtCusto->bindParam(':ano', $ano);
                    $stmtCusto->bindParam(':observacoes', $observacoes);
                    $stmtCusto->bindParam(':registrado_por', $usuarioId);
                    $stmtCusto->execute();
                    
                    $sincronizados++;
                } catch (Exception $e) {
                    $erros[] = "Erro ao sincronizar entrada ID {$entrada['id']}: " . $e->getMessage();
                }
            }
            
            $conn->commit();
            echo json_encode([
                'success' => true, 
                'message' => "Sincronização concluída! {$sincronizados} registro(s) criado(s).",
                'sincronizados' => $sincronizados,
                'erros' => $erros
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    // Limpar qualquer output anterior (incluindo warnings/notices)
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Desabilitar exibição de erros para não quebrar o JSON
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_GET['acao'] === 'listar_estoque') {
        $escolaId = $_GET['escola_id'] ?? null;
        
        // Se escola_id for "todos" ou vazio, mostrar estoque central
        // Se escola_id for específico, mostrar produtos dos pacotes enviados para aquela escola
        if (empty($escolaId) || $escolaId === 'todos') {
            // Estoque Central
            $sql = "SELECT ec.*, p.nome as produto_nome, p.unidade_medida, p.estoque_minimo, f.nome as fornecedor_nome
                    FROM estoque_central ec
                    INNER JOIN produto p ON ec.produto_id = p.id
                    LEFT JOIN fornecedor f ON ec.fornecedor_id = f.id
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($_GET['produto_id'])) {
                $sql .= " AND ec.produto_id = :produto_id";
                $params[':produto_id'] = $_GET['produto_id'];
            }
            
            if (!empty($_GET['fornecedor_id'])) {
                $sql .= " AND ec.fornecedor_id = :fornecedor_id";
                $params[':fornecedor_id'] = $_GET['fornecedor_id'];
            }
            
            $sql .= " ORDER BY ec.criado_em DESC";
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $estoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular totais por produto
            $sqlTotal = "SELECT produto_id, SUM(quantidade) as total_quantidade
                         FROM estoque_central
                         GROUP BY produto_id";
            $stmtTotal = $conn->prepare($sqlTotal);
            $stmtTotal->execute();
            $totais = [];
            while ($row = $stmtTotal->fetch(PDO::FETCH_ASSOC)) {
                $totais[$row['produto_id']] = $row['total_quantidade'];
            }
            
            foreach ($estoque as &$item) {
                $item['total_produto'] = $totais[$item['produto_id']] ?? 0;
            }
        } else {
            // Estoque da Escola (produtos dos pacotes enviados)
            $escolaId = (int)$escolaId;
            
            // Verificar se a coluna estoque_central_id existe
            try {
                $checkColumn = $conn->query("SHOW COLUMNS FROM pacote_escola_item LIKE 'estoque_central_id'");
                $columnExists = $checkColumn->rowCount() > 0;
            } catch (Exception $e) {
                $columnExists = false;
            }
            
            if ($columnExists) {
                // Se a coluna existe, agrupar por produto_id + estoque_central_id para mostrar cada lote separadamente
                $sql = "SELECT 
                            pei.produto_id,
                            pei.estoque_central_id,
                            p.nome as produto_nome,
                            p.unidade_medida,
                            p.estoque_minimo,
                            SUM(pei.quantidade) as quantidade,
                            COALESCE(ec1.validade, ec2.validade) as validade,
                            COALESCE(ec1.lote, ec2.lote, 'Sem lote') as lote,
                            COALESCE(f1.nome, f2.nome) as fornecedor_nome,
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
                // Se a coluna não existe, agrupar por produto_id + lote para mostrar cada lote separadamente
                $sql = "SELECT 
                            pei.produto_id,
                            ec.id as estoque_central_id,
                            p.nome as produto_nome,
                            p.unidade_medida,
                            p.estoque_minimo,
                            SUM(pei.quantidade) as quantidade,
                            ec.validade,
                            COALESCE(ec.lote, 'Sem lote') as lote,
                            f.nome as fornecedor_nome,
                            MAX(pe.data_envio) as data_envio_mais_recente
                        FROM pacote_escola_item pei
                        INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                        INNER JOIN produto p ON pei.produto_id = p.id
                        LEFT JOIN estoque_central ec ON pei.produto_id = ec.produto_id AND ec.quantidade > 0
                        LEFT JOIN fornecedor f ON ec.fornecedor_id = f.id
                        WHERE pe.escola_id = :escola_id";
            }
            
            $params = [':escola_id' => $escolaId];
            
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
                $item['id'] = null; // Não temos ID do estoque_central para itens de pacote
                $item['total_produto'] = $item['quantidade']; // Total já é a soma
                $item['criado_em'] = $item['data_envio_mais_recente'];
                
                // Limpar valores vazios (não usamos mais GROUP_CONCAT, então não precisa limpar vírgulas)
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
        }
        
        echo json_encode(['success' => true, 'estoque' => $estoque], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Buscar estoque inicial
$sqlEstoque = "SELECT ec.*, p.nome as produto_nome, p.unidade_medida, p.estoque_minimo, f.nome as fornecedor_nome
               FROM estoque_central ec
               INNER JOIN produto p ON ec.produto_id = p.id
               LEFT JOIN fornecedor f ON ec.fornecedor_id = f.id
               ORDER BY ec.criado_em DESC
               LIMIT 50";
$stmtEstoque = $conn->prepare($sqlEstoque);
$stmtEstoque->execute();
$estoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - SIGEA</title>
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
    <?php 
    // Mostrar sidebar correta baseada no tipo de usuário
    if (eAdm()) {
        include 'components/sidebar_adm.php';
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
                        <h1 class="text-xl font-semibold text-gray-800">Controle de Estoque</h1>
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
            <div class="max-w-[95%] mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 id="titulo-estoque" class="text-2xl font-bold text-gray-900">Estoque De Produtos</h2>
                        <p id="subtitulo-estoque" class="text-gray-600 mt-1">Gerencie entradas e saídas de produtos</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="sincronizarCustos()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2" title="Sincronizar entradas antigas do estoque para custos">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Sincronizar Custos</span>
                        </button>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEstoque()">
                                <option value="todos">Todos (Estoque Central)</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fornecedor</label>
                            <select id="filtro-fornecedor" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEstoque()">
                                <option value="">Todos os fornecedores</option>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <option value="<?= $fornecedor['id'] ?>"><?= htmlspecialchars($fornecedor['nome']) ?></option>
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
                                                <p class="text-sm mt-1">Os registros aparecerão aqui quando houver dados</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($estoque as $index => $item): 
                                        $validade = $item['validade'] ? date('d/m/Y', strtotime($item['validade'])) : null;
                                        $dataValidade = $item['validade'] ? new DateTime($item['validade']) : null;
                                        $hoje = new DateTime();
                                        $diasRestantes = $dataValidade ? $hoje->diff($dataValidade)->days : null;
                                        $corValidade = '';
                                        $iconeValidade = '';
                                        if ($validade && $diasRestantes !== null) {
                                            if ($diasRestantes < 0) {
                                                $corValidade = 'bg-red-100 text-red-800 border-red-200';
                                                $iconeValidade = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                                            } else if ($diasRestantes <= 7) {
                                                $corValidade = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                                $iconeValidade = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                            } else {
                                                $corValidade = 'bg-green-100 text-green-800 border-green-200';
                                                $iconeValidade = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                            }
                                        }
                                    ?>
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
                                                <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-md text-sm font-medium">
                                                    <?= htmlspecialchars($item['lote'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="text-gray-700 font-medium"><?= htmlspecialchars($item['fornecedor_nome'] ?? '-') ?></span>
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                <?php if ($validade): ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-semibold border <?= $corValidade ?>">
                                                        <?= $iconeValidade ?><?= $validade ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400 italic">Não informada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                <span class="text-gray-600 font-medium">
                                                    <?= $item['criado_em'] ? date('d/m/Y', strtotime($item['criado_em'])) : '-' ?>
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
            
            // Filtrar produtos
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
            
            // Mostrar sugestões
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

        // Função para selecionar produto
        function selecionarProduto(id, nome, unidade) {
            document.getElementById('entrada-produto-nome').value = nome;
            document.getElementById('entrada-produto-id').value = id;
            document.getElementById('sugestoes-produto').classList.add('hidden');
            produtoSelecionado = {id: id, nome: nome, unidade: unidade};
            sugestaoAtivaProduto = -1;
        }

        // Event listeners para o campo de produto
        document.addEventListener('DOMContentLoaded', function() {
            const campoProduto = document.getElementById('entrada-produto-nome');
            const sugestoes = document.getElementById('sugestoes-produto');
            
            if (campoProduto) {
                campoProduto.addEventListener('input', function(e) {
                    buscarProdutos(e.target.value);
                });
                
                campoProduto.addEventListener('keydown', function(e) {
                    const itens = document.querySelectorAll('.sugestao-item-produto');
                    if (itens.length === 0) return;
                    
                    switch(e.key) {
                        case 'ArrowDown':
                            e.preventDefault();
                            sugestaoAtivaProduto = (sugestaoAtivaProduto + 1) % itens.length;
                            itens[sugestaoAtivaProduto].scrollIntoView({block: 'nearest'});
                            atualizarDestaqueProduto();
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            sugestaoAtivaProduto = sugestaoAtivaProduto <= 0 ? itens.length - 1 : sugestaoAtivaProduto - 1;
                            itens[sugestaoAtivaProduto].scrollIntoView({block: 'nearest'});
                            atualizarDestaqueProduto();
                            break;
                        case 'Enter':
                            e.preventDefault();
                            if (sugestaoAtivaProduto >= 0 && itens[sugestaoAtivaProduto]) {
                                itens[sugestaoAtivaProduto].click();
                            }
                            break;
                        case 'Escape':
                            sugestoes.classList.add('hidden');
                            sugestaoAtivaProduto = -1;
                            break;
                    }
                });
                
                // Fechar sugestões ao clicar fora
                document.addEventListener('click', function(e) {
                    if (!campoProduto.contains(e.target) && !sugestoes.contains(e.target)) {
                        sugestoes.classList.add('hidden');
                    }
                });
            }
        });

        function atualizarDestaqueProduto() {
            const itens = document.querySelectorAll('.sugestao-item-produto');
            itens.forEach((item, index) => {
                if (index === sugestaoAtivaProduto) {
                    item.classList.add('bg-indigo-50', 'border-l-4', 'border-indigo-500');
                } else {
                    item.classList.remove('bg-indigo-50', 'border-l-4', 'border-indigo-500');
                }
            });
        }

        function salvarEntrada() {
            const produtoNome = document.getElementById('entrada-produto-nome').value.trim();
            const produtoId = document.getElementById('entrada-produto-id').value;
            
            if (!produtoNome) {
                alert('Por favor, digite o nome do produto.');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'registrar_entrada');
            formData.append('produto_nome', produtoNome);
            formData.append('produto_id', produtoId); // Pode estar vazio se for novo produto
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
                    filtrarEstoque();
                } else {
                    alert('Erro ao registrar entrada: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar entrada.');
            });
        }

        function sincronizarCustos() {
            if (!confirm('Deseja sincronizar as entradas antigas do estoque para os custos? Isso criará registros de custo para todas as entradas que ainda não foram sincronizadas.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'sincronizar_custos');
            
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span> Sincronizando...';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                if (data.success) {
                    alert(data.message || 'Sincronização concluída com sucesso!');
                    if (data.sincronizados > 0) {
                        filtrarEstoque();
                    }
                } else {
                    alert('Erro ao sincronizar: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                console.error('Erro:', error);
                alert('Erro ao sincronizar custos.');
            });
        }

        function filtrarEstoque() {
            const escolaId = document.getElementById('filtro-escola').value;
            const produtoId = document.getElementById('filtro-produto').value;
            const fornecedorId = document.getElementById('filtro-fornecedor').value;
            const selectEscola = document.getElementById('filtro-escola');
            const escolaSelecionada = selectEscola.options[selectEscola.selectedIndex].text;
            
            // Atualizar título
            if (escolaId === 'todos' || !escolaId) {
                document.getElementById('titulo-estoque').textContent = 'Estoque Central';
                document.getElementById('subtitulo-estoque').textContent = 'Gerencie entradas e saídas de produtos';
            } else {
                document.getElementById('titulo-estoque').textContent = 'Estoque da Escola';
                document.getElementById('subtitulo-estoque').textContent = `Produtos disponíveis em: ${escolaSelecionada}`;
            }
            
            let url = '?acao=listar_estoque';
            if (escolaId && escolaId !== 'todos') url += '&escola_id=' + escolaId;
            if (produtoId) url += '&produto_id=' + produtoId;
            if (fornecedorId) url += '&fornecedor_id=' + fornecedorId;
            
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
</body>
</html>

