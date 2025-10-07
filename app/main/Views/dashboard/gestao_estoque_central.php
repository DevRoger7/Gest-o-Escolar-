<?php
// Iniciar sessão
session_start();

// Verificar se o usuário está logado e tem permissão para acessar esta página
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADM') {
    header('Location: ../auth/login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// Funções para gerenciamento de estoque
function listarItensEstoque($busca = '')
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $sql = "SELECT ec.id, p.nome, p.codigo, ec.quantidade, p.unidade_medida, 
                       p.estoque_minimo, ec.atualizado_em as data_aquisicao,
                       ec.lote as fornecedor, 'Almoxarifado Central' as localizacao, 
                       'ativo' as status, ec.atualizado_em,
                       'Estoque Central' as escola_nome, p.codigo as categoria,
                       0 as valor_unitario, '' as descricao
                FROM estoque_central ec
                INNER JOIN produto p ON ec.produto_id = p.id
                WHERE 1=1";

        if (!empty($busca)) {
            $sql .= " AND (p.nome LIKE :busca OR p.codigo LIKE :busca)";
        }

        $sql .= " ORDER BY p.nome ASC";

        $stmt = $conn->prepare($sql);

        if (!empty($busca)) {
            $busca = "%{$busca}%";
            $stmt->bindParam(':busca', $busca);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao listar itens: " . $e->getMessage());
        return [];
    }
}

function cadastrarItemEstoque($dados)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Primeiro, inserir o produto
        $codigo = strtoupper(substr($dados['nome'], 0, 3)) . date('YmdHis'); // Gerar código único
        $stmt = $conn->prepare("INSERT INTO produto (codigo, nome, unidade_medida, estoque_minimo) 
                                VALUES (:codigo, :nome, :unidade_medida, :estoque_minimo)");
        
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':unidade_medida', $dados['unidade_medida']);
        $stmt->bindParam(':estoque_minimo', $dados['estoque_minimo']);
        
        $stmt->execute();
        $produto_id = $conn->lastInsertId();

        // Depois, inserir no estoque_central
        $stmt = $conn->prepare("INSERT INTO estoque_central (produto_id, quantidade, lote, validade, atualizado_em) 
                                VALUES (:produto_id, :quantidade, :lote, :validade, CURRENT_DATE)");
        
        $stmt->bindParam(':produto_id', $produto_id);
        $stmt->bindParam(':quantidade', $dados['quantidade']);
        $stmt->bindParam(':lote', $dados['fornecedor'] ?? '');
        $stmt->bindParam(':validade', $dados['validade'] ?? null);

        $stmt->execute();

        $conn->commit();

        return ['status' => true, 'mensagem' => 'Produto cadastrado com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao cadastrar produto: ' . $e->getMessage()];
    }
}

function excluirItemEstoque($id)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Primeiro, obter o produto_id
        $stmt = $conn->prepare("SELECT produto_id FROM estoque_central WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $produto_id = $result['produto_id'];
            
            // Excluir do estoque_central
            $stmt = $conn->prepare("DELETE FROM estoque_central WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Excluir o produto (se não estiver sendo usado em outras escolas)
            $stmt = $conn->prepare("DELETE FROM produto WHERE id = :produto_id");
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->execute();
        }

        $conn->commit();

        return ['status' => true, 'mensagem' => 'Produto excluído com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao excluir produto: ' . $e->getMessage()];
    }
}

function atualizarItemEstoque($id, $dados)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Primeiro, obter o produto_id
        $stmt = $conn->prepare("SELECT produto_id FROM estoque_central WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $produto_id = $result['produto_id'];
            
            // Atualizar dados do produto
            $stmt = $conn->prepare("UPDATE produto SET 
                                    nome = :nome, 
                                    unidade_medida = :unidade_medida,
                                    estoque_minimo = :estoque_minimo
                                    WHERE id = :produto_id");

            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->bindParam(':nome', $dados['nome']);
            $stmt->bindParam(':unidade_medida', $dados['unidade_medida']);
            $stmt->bindParam(':estoque_minimo', $dados['estoque_minimo']);
            
            $stmt->execute();

            // Atualizar dados do estoque_central
            $stmt = $conn->prepare("UPDATE estoque_central SET 
                                    quantidade = :quantidade, 
                                    lote = :lote,
                                    validade = :validade,
                                    atualizado_em = CURRENT_DATE
                                    WHERE id = :id");

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':quantidade', $dados['quantidade']);
            $stmt->bindParam(':lote', $dados['fornecedor'] ?? '');
            $stmt->bindParam(':validade', $dados['validade'] ?? null);

            $stmt->execute();
        }

        $conn->commit();

        return ['status' => true, 'mensagem' => 'Produto atualizado com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao atualizar produto: ' . $e->getMessage()];
    }
}

// Funções para gerenciamento de pacotes
function listarPacotes($busca = '')
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT p.id, p.nome, p.descricao, p.criado_em as data_criacao,
                   pr.nome as produto_nome, pr.codigo as produto_codigo
            FROM pacote p
            INNER JOIN produto pr ON p.produto_id = pr.id
            WHERE 1=1";

    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE :busca OR p.descricao LIKE :busca OR pr.nome LIKE :busca)";
    }

    $sql .= " ORDER BY p.criado_em DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarCestasPendentes($busca = '')
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT pc.id, pc.data_criacao as data_pedido, pc.status,
                   e.nome as escola_nome, e.municipio,
                   p.nome as pacote_nome,
                   u.username as solicitado_por
            FROM pedido_cesta pc
            INNER JOIN escola e ON pc.escola_id = e.id
            INNER JOIN pacote p ON pc.nutricionista_id = p.criado_por
            INNER JOIN usuario u ON pc.nutricionista_id = u.id
            WHERE pc.status = 'ENVIADO'";

    if (!empty($busca)) {
        $sql .= " AND (e.nome LIKE :busca OR p.nome LIKE :busca OR u.username LIKE :busca)";
    }

    $sql .= " ORDER BY pc.data_criacao ASC";

    $stmt = $conn->prepare($sql);

    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cadastrarPacote($dados)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Primeiro, criar um produto base para o pacote
        $codigo = strtoupper(substr($dados['nome'], 0, 3)) . date('YmdHis');
        $stmt = $conn->prepare("INSERT INTO produto (codigo, nome, unidade_medida, estoque_minimo) 
                                VALUES (:codigo, :nome, 'UNIDADE', 0)");
        
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->execute();
        $produto_id = $conn->lastInsertId();

        // Depois, criar o pacote
        $stmt = $conn->prepare("INSERT INTO pacote (produto_id, nome, descricao, criado_por) 
                                VALUES (:produto_id, :nome, :descricao, 1)");
        
        $stmt->bindParam(':produto_id', $produto_id);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        
        $stmt->execute();

        $conn->commit();

        return ['status' => true, 'mensagem' => 'Pacote cadastrado com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao cadastrar pacote: ' . $e->getMessage()];
    }
}

function aprovarCesta($cesta_id, $observacoes = '')
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE pedido_cesta SET 
                                status = 'APROVADO', 
                                data_aprovacao = CURRENT_TIMESTAMP,
                                aprovado_por = 1
                                WHERE id = :id");

        $stmt->bindParam(':id', $cesta_id);
        $stmt->execute();

        $conn->commit();

        return ['status' => true, 'mensagem' => 'Cesta aprovada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao aprovar cesta: ' . $e->getMessage()];
    }
}

function rejeitarCesta($cesta_id, $motivo = '')
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE pedido_cesta SET 
                                status = 'REJEITADO', 
                                data_aprovacao = CURRENT_TIMESTAMP,
                                aprovado_por = 1
                                WHERE id = :id");

        $stmt->bindParam(':id', $cesta_id);
        $stmt->execute();

        $conn->commit();

        return ['status' => true, 'mensagem' => 'Cesta rejeitada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao rejeitar cesta: ' . $e->getMessage()];
    }
}

// Processar formulários
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        // Cadastrar novo item
        if ($_POST['acao'] === 'cadastrar') {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'descricao' => $_POST['descricao'] ?? '',
                'categoria' => $_POST['categoria'] ?? '',
                'quantidade' => $_POST['quantidade'] ?? 0,
                'unidade_medida' => $_POST['unidade_medida'] ?? '',
                'valor_unitario' => $_POST['valor_unitario'] ?? 0,
                'data_aquisicao' => $_POST['data_aquisicao'] ?? null,
                'fornecedor' => $_POST['fornecedor'] ?? '',
                'localizacao' => $_POST['localizacao'] ?? '',
                'estoque_minimo' => $_POST['estoque_minimo'] ?? 0,
                'status' => $_POST['status'] ?? 'ativo',
                'obs' => $_POST['obs'] ?? ''
            ];

            $resultado = cadastrarItemEstoque($dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }

        // Editar item
        if ($_POST['acao'] === 'editar' && isset($_POST['id'])) {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'descricao' => $_POST['descricao'] ?? '',
                'categoria' => $_POST['categoria'] ?? '',
                'quantidade' => $_POST['quantidade'] ?? 0,
                'unidade_medida' => $_POST['unidade_medida'] ?? '',
                'valor_unitario' => $_POST['valor_unitario'] ?? 0,
                'data_aquisicao' => $_POST['data_aquisicao'] ?? null,
                'fornecedor' => $_POST['fornecedor'] ?? '',
                'localizacao' => $_POST['localizacao'] ?? '',
                'estoque_minimo' => $_POST['estoque_minimo'] ?? 0,
                'status' => $_POST['status'] ?? 'ativo',
                'obs' => $_POST['obs'] ?? ''
            ];

            $resultado = atualizarItemEstoque($_POST['id'], $dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }

        // Excluir item
        if ($_POST['acao'] === 'excluir' && isset($_POST['id'])) {
            $resultado = excluirItemEstoque($_POST['id']);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }

        // Cadastrar novo pacote
        if ($_POST['acao'] === 'cadastrar_pacote') {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'descricao' => $_POST['descricao'] ?? '',
                'valor_total' => $_POST['valor_total'] ?? 0
            ];

            $resultado = cadastrarPacote($dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }

        // Aprovar cesta
        if ($_POST['acao'] === 'aprovar_cesta' && isset($_POST['cesta_id'])) {
            $resultado = aprovarCesta($_POST['cesta_id'], $_POST['observacoes'] ?? '');
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }

        // Rejeitar cesta
        if ($_POST['acao'] === 'rejeitar_cesta' && isset($_POST['cesta_id'])) {
            $resultado = rejeitarCesta($_POST['cesta_id'], $_POST['motivo'] ?? '');
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
    }
}

// Buscar dados
$busca = $_GET['busca'] ?? '';
$itensEstoque = listarItensEstoque($busca);
$pacotes = listarPacotes($busca);
$cestasPendentes = listarCestasPendentes($busca);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Estoque de Alimentos - SIGAE</title>
    
    <!-- Favicon -->
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'secondary-green': '#4A7C59',
                        'accent-orange': '#FF6B35',
                        'accent-red': '#D62828',
                        'light-green': '#A8D5BA',
                        'warm-orange': '#FF8C42'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }

        // ===== PREVENÇÃO DE ERROS DE EXTENSÕES =====
        window.addEventListener('error', function(e) {
            if (e.message && (
                e.message.includes('content-all.js') ||
                e.message.includes('Could not establish connection') ||
                e.message.includes('Receiving end does not exist') ||
                e.message.includes('message channel closed')
            )) {
                e.preventDefault();
                return false;
            }
        });

        window.addEventListener('unhandledrejection', function(e) {
            if (e.reason && (
                e.reason.message && (
                    e.reason.message.includes('content-all.js') ||
                    e.reason.message.includes('Could not establish connection') ||
                    e.reason.message.includes('Receiving end does not exist') ||
                    e.reason.message.includes('message channel closed')
                )
            )) {
                e.preventDefault();
                return false;
            }
        });
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="global-theme.css" rel="stylesheet">
    
    <!-- Theme Manager -->
    <script src="theme-manager.js"></script>
    
    <!-- VLibras -->
    <div id="vlibras-widget" vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper>
            <div class="vw-plugin-top-wrapper"></div>
        </div>
    </div>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        function initializeVLibras() {
            if (localStorage.getItem('vlibras-enabled') !== 'false') {
                if (window.VLibras) {
                    new window.VLibras.Widget('https://vlibras.gov.br/app');
                }
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeVLibras);
        } else {
            initializeVLibras();
        }
    </script>
    
    <style>
        .tab-active {
            border-bottom: 2px solid #2D5A27;
            color: #2D5A27;
            font-weight: 600;
        }

        #vlibras-widget.disabled {
            display: none !important;
        }
        
        #vlibras-widget.enabled {
            display: block !important;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes checkmark {
            0% {
                stroke-dashoffset: 100;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }
        
        @keyframes scaleIn {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .modal-sucesso-show {
            animation: slideInDown 0.4s ease-out;
        }
        
        .checkmark-circle {
            animation: scaleIn 0.5s ease-out;
        }
        
        .checkmark-check {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: checkmark 0.6s ease-out 0.3s forwards;
        }
        
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }

        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }

        #sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-right: 1px solid #e2e8f0;
        }

        .menu-item {
            transition: all 0.2s ease;
        }

        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }

        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        
        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 999 !important;
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                height: 100vh !important;
                width: 16rem !important;
            }

            .sidebar-mobile.open {
                transform: translateX(0) !important;
                z-index: 999 !important;
            }
        }

        .content-dimmed {
            opacity: 0.5 !important;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }

        /* Tema Escuro */
        [data-theme="dark"] {
            --bg-primary: #0a0a0a;
            --bg-secondary: #1a1a1a;
            --bg-tertiary: #2a2a2a;
            --bg-quaternary: #3a3a3a;
            --text-primary: #ffffff;
            --text-secondary: #e0e0e0;
            --text-muted: #b0b0b0;
            --border-color: #404040;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.6);
            --primary-green: #4ade80;
            --primary-green-hover: #22c55e;
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }

        [data-theme="dark"] .bg-white {
            background: linear-gradient(145deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .text-gray-800,
        [data-theme="dark"] .text-gray-700,
        [data-theme="dark"] .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .text-gray-500 {
            color: #c0c0c0 !important;
        }

        [data-theme="dark"] input,
        [data-theme="dark"] select,
        [data-theme="dark"] textarea {
            background: var(--bg-quaternary) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .border-gray-300 {
            border-color: var(--border-color) !important;
        }

        /* Badge de status */
        .badge-ativo {
            background: #22c55e;
            color: white;
        }

        .badge-inativo {
            background: #ef4444;
            color: white;
        }

        .badge-pendente {
            background: #f59e0b;
            color: white;
        }

        .badge-aprovado {
            background: #22c55e;
            color: white;
        }

        .badge-rejeitado {
            background: #ef4444;
            color: white;
        }

        .badge-estoque-baixo {
            background: #f59e0b;
            color: white;
        }

        /* Alertas de estoque baixo */
        .alerta-estoque-baixo {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        [data-theme="dark"] .alerta-estoque-baixo {
            background: #78350f;
            border-left-color: #fbbf24;
        }

        /* Estilos para tabs */
        .tab-active {
            border-bottom: 2px solid #2D5A27;
            color: #2D5A27;
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }

        /* Sidebar mobile */
        .sidebar-mobile {
            transform: translateX(0);
        }

        .sidebar-mobile.hidden {
            transform: translateX(-100%);
        }

        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }

        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }

        .content-dimmed {
            filter: blur(2px);
        }

        .menu-item {
            transition: all 0.2s ease;
            border-radius: 0.5rem;
        }

        .menu-item:hover {
            background-color: #f3f4f6;
            color: #2D5A27;
            transform: translateX(2px);
        }

        .menu-item.active {
            background-color: #2D5A27;
            color: white;
        }

        .menu-item.active:hover {
            background-color: #1e3d1a;
            color: white;
        }

        /* Modo escuro - menu items */
        [data-theme="dark"] .menu-item {
            color: #e5e7eb;
        }

        [data-theme="dark"] .menu-item:hover {
            background-color: #374151;
            color: #10b981;
        }

        [data-theme="dark"] .menu-item.active {
            background-color: #2D5A27;
            color: white;
        }

        [data-theme="dark"] .menu-item.active:hover {
            background-color: #1e3d1a;
            color: white;
        }

        @media (max-width: 1024px) {
            .sidebar-mobile {
                transform: translateX(-100%);
            }
            
            .sidebar-mobile.open {
                transform: translateX(0);
            }
        }

        /* Garantir que a sidebar seja visível */
        #sidebar {
            display: block !important;
        }

        /* Desktop - sidebar sempre visível */
        @media (min-width: 1025px) {
            #sidebar {
                transform: translateX(0) !important;
            }
        }

        /* Modo escuro - sidebar */
        [data-theme="dark"] #sidebar {
            background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
            border-right: 1px solid #374151;
        }

        [data-theme="dark"] .border-gray-200 {
            border-color: #374151;
        }

        [data-theme="dark"] .text-gray-800 {
            color: #f9fafb;
        }

        [data-theme="dark"] .text-gray-500 {
            color: #9ca3af;
        }

        [data-theme="dark"] .text-gray-700 {
            color: #d1d5db;
        }

        /* Modo escuro - botão de logout */
        [data-theme="dark"] .text-red-600 {
            color: #f87171;
        }

        [data-theme="dark"] .hover\:bg-red-50:hover {
            background-color: #7f1d1d;
        }

        [data-theme="dark"] .hover\:text-red-700:hover {
            color: #fca5a5;
        }

        /* Modo escuro - conteúdo principal */
        [data-theme="dark"] .bg-gray-50 {
            background-color: #111827;
        }

        [data-theme="dark"] .bg-white {
            background-color: #1f2937;
            border: 1px solid #374151;
        }

        [data-theme="dark"] .text-gray-800 {
            color: #f9fafb;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #9ca3af;
        }

        [data-theme="dark"] .text-gray-500 {
            color: #6b7280;
        }

        [data-theme="dark"] .border-gray-200 {
            border-color: #374151;
        }

        [data-theme="dark"] .bg-gray-50 {
            background-color: #374151;
        }

        /* Modo escuro - tabs */
        [data-theme="dark"] .tab-button {
            color: #9ca3af;
        }

        [data-theme="dark"] .tab-button:hover {
            color: #d1d5db;
            border-color: #6b7280;
        }

        [data-theme="dark"] .tab-button.tab-active {
            color: #10b981;
            border-color: #10b981;
        }

        /* Modo escuro - cards */
        [data-theme="dark"] .bg-white {
            background: linear-gradient(145deg, #1f2937 0%, #111827 100%);
            border: 1px solid #374151;
        }

        /* Modo escuro - tabelas */
        [data-theme="dark"] .bg-gray-50 {
            background-color: #374151;
        }

        [data-theme="dark"] .divide-gray-200 {
            border-color: #374151;
        }

        [data-theme="dark"] .hover\:bg-gray-50:hover {
            background-color: #4b5563;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Overlay para Mobile -->
    <div id="mobileOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg sidebar-transition z-50 lg:translate-x-0 sidebar-mobile">
        <!-- Logo e Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-lg font-bold text-gray-800">SIGAE</h1>
                    <p class="text-xs text-gray-500">Maranguape</p>
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center">
                    <span class="text-2 font-bold text-white" id="profileInitials"><?php
                        // Pega as 2 primeiras letras do nome da sessão
                        $nome = $_SESSION['nome'] ?? '';
                        $iniciais = '';
                        if (strlen($nome) >= 2) {
                            $iniciais = strtoupper(substr($nome, 0, 2));
                        } elseif (strlen($nome) == 1) {
                            $iniciais = strtoupper($nome);
                        } else {
                            $iniciais = 'US'; // Fallback para "User"
                        }
                        echo $iniciais;
                    ?></span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800" id="userName"><?= $_SESSION['nome'] ?? 'Usuário' ?></p>
                    <p class="text-xs text-gray-500"><?= $_SESSION['tipo'] ?? 'Funcionário' ?></p>
                </div>
            </div>
        </div>

        <nav class="p-4">
            <ul class="space-y-2">
                <li>
                    <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="gestao_escolas.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <span>Escolas</span>
                    </a>
                </li>
                <li>
                    <a href="gestao_usuarios.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span>Usuários</span>
                    </a>
                </li>


                <li id="estoque-central-menu">
                    <a href="gestao_estoque_central.php" class="menu-item active flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Estoque Central</span>
                    </a>
                </li>

            </ul>
        </nav>

        <!-- Logout -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
            <button onclick="confirmLogout()" class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Sair</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen content-transition">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16 header-content">
                    <!-- Mobile Menu Button - APENAS NO MOBILE -->
                    <button onclick="window.toggleSidebar();" class="lg:hidden mobile-menu-btn p-4 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-green transition-all duration-200 flex items-center justify-center" aria-label="Abrir menu">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Page Title - Centered on mobile -->
                    <div class="flex-1 text-center lg:text-left lg:flex-none">
                        <div class="flex items-center justify-center lg:justify-start">
                            <!-- Logo apenas no mobile -->
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-8 h-8 object-contain lg:hidden">
                            <!-- Título sempre visível -->
                            <h1 class="text-xl font-semibold text-gray-800 ml-2" id="pageTitle">Gestão de Estoque Central</h1>
                        </div>
                    </div>

                    <!-- User Actions -->
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'ADM') { ?>
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

                        <!-- User Profile Button -->
                        <button onclick="openUserProfile()" class="p-2 text-gray-600 bg-gray-100 hover:text-gray-900 hover:bg-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-green transition-colors duration-200" aria-label="Abrir perfil do usuário e configurações de acessibilidade" title="Perfil e Acessibilidade (Alt+A)">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="p-6">
            <?php if (!empty($mensagem)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $tipoMensagem === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
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
                        <span><?php echo htmlspecialchars($mensagem); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex space-x-8">
                    <button onclick="showTab('lista')" class="tab-button tab-active py-4 px-1 border-b-2 border-primary-green text-primary-green font-medium text-sm">
                        Lista de Itens
                    </button>
                    <button onclick="showTab('cadastro')" class="tab-button py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm">
                        Novo Item
                    </button>
                    <button onclick="showTab('pacotes')" class="tab-button py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm">
                        Pacotes de Merenda
                    </button>
                    <button onclick="showTab('cestas')" class="tab-button py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm">
                        Aprovar Cestas
                    </button>
                </nav>
            </div>

            <!-- Tab: Lista de Itens -->
            <div id="tab-lista" class="tab-content active">
                <!-- Busca -->
                <div class="mb-6">
                    <form method="GET" class="flex gap-4">
                        <div class="flex-1">
                            <input type="text" name="busca" placeholder="Buscar por nome, descrição, categoria ou fornecedor..." 
                                   value="<?php echo htmlspecialchars($busca); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                        </div>
                        <button type="submit" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                        <?php if (!empty($busca)): ?>
                            <a href="?" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Limpar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Estatísticas rápidas -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm">Total de Itens</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo count($itensEstoque); ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <?php
                    $itensAtivos = array_filter($itensEstoque, function($item) {
                        return $item['status'] === 'ativo';
                    });
                    $itensEstoqueBaixo = array_filter($itensEstoque, function($item) {
                        return $item['quantidade'] <= $item['estoque_minimo'];
                    });
                    $valorTotal = array_sum(array_map(function($item) {
                        return $item['quantidade'] * $item['valor_unitario'];
                    }, $itensEstoque));
                    ?>

                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm">Itens Ativos</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo count($itensAtivos); ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm">Estoque Baixo</p>
                                <p class="text-2xl font-bold text-orange-600"><?php echo count($itensEstoqueBaixo); ?></p>
                            </div>
                            <div class="bg-orange-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm">Valor Total</p>
                                <p class="text-2xl font-bold text-primary-green">R$ <?php echo number_format($valorTotal, 2, ',', '.'); ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas de estoque baixo -->
                <?php if (count($itensEstoqueBaixo) > 0): ?>
                    <div class="mb-6 alerta-estoque-baixo p-4 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-orange-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <h3 class="font-bold text-orange-800 mb-2">Atenção: Itens com estoque baixo</h3>
                                <div class="text-sm text-orange-700">
                                    <?php foreach ($itensEstoqueBaixo as $item): ?>
                                        <div class="mb-1">
                                            <strong><?php echo htmlspecialchars($item['nome']); ?></strong> - 
                                            Quantidade: <?php echo $item['quantidade']; ?> <?php echo htmlspecialchars($item['unidade_medida']); ?> 
                                            (Mínimo: <?php echo $item['estoque_minimo']; ?>)
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tabela -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Unit.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Localização</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($itensEstoque)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                            <p class="text-lg font-medium">Nenhum item encontrado</p>
                                            <p class="text-sm mt-1">Clique em "Novo Item" para cadastrar o primeiro item do estoque</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($itensEstoque as $item): ?>
                                        <?php
                                        $valorTotal = $item['quantidade'] * $item['valor_unitario'];
                                        $estoqueBaixo = $item['quantidade'] <= $item['estoque_minimo'];
                                        ?>
                                        <tr class="hover:bg-gray-50 <?php echo $estoqueBaixo ? 'bg-orange-50' : ''; ?>">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['nome']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['categoria']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($item['codigo']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo $item['quantidade']; ?> <?php echo htmlspecialchars($item['unidade_medida']); ?>
                                                </div>
                                                <?php if ($estoqueBaixo): ?>
                                                    <span class="text-xs text-orange-600 font-medium">Estoque baixo!</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary-green">
                                                R$ <?php echo number_format($valorTotal, 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($item['localizacao']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full badge-<?php echo $item['status']; ?>">
                                                    <?php echo ucfirst($item['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="editarItem(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                        class="text-primary-green hover:text-secondary-green mr-3">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="excluirItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nome']); ?>')" 
                                                        class="text-accent-red hover:text-red-700">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab: Cadastro -->
            <div id="tab-cadastro" class="tab-content">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Cadastrar Novo Alimento</h3>
                    
                    <form method="POST" id="formCadastro">
                        <input type="hidden" name="acao" value="cadastrar">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nome -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Alimento *</label>
                                <input type="text" name="nome" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Categoria -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Código do Produto</label>
                                <input type="text" name="codigo" placeholder="Será gerado automaticamente"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent" readonly>
                                <p class="text-xs text-gray-500 mt-1">Código único gerado automaticamente</p>
                            </div>

                            <!-- Quantidade -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade *</label>
                                <input type="number" name="quantidade" required min="0" step="0.01"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Unidade de Medida -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Unidade de Medida *</label>
                                <select name="unidade_medida" required 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="KG">Quilograma (KG)</option>
                                    <option value="L">Litro (L)</option>
                                    <option value="UN">Unidade (UN)</option>
                                    <option value="CX">Caixa (CX)</option>
                                    <option value="PC">Pacote (PC)</option>
                                    <option value="DZ">Dúzia (DZ)</option>
                                </select>
                            </div>

                            <!-- Estoque Mínimo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estoque Mínimo *</label>
                                <input type="number" name="estoque_minimo" required min="0" step="0.01"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Fornecedor/Lote -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fornecedor/Lote</label>
                                <input type="text" name="fornecedor" placeholder="Ex: Fornecedor ABC - Lote 001"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Localização -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Localização *</label>
                                <input type="text" name="localizacao" required placeholder="Ex: Almoxarifado A, Prateleira 3"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Observações/Descrição -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações/Descrição</label>
                                <textarea name="obs" rows="3" placeholder="Informações adicionais sobre o produto"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-4">
                            <button type="button" onclick="limparFormulario()" 
                                    class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                Limpar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green transition">
                                Cadastrar Item
                            </button>
                        </div>
                    </form>

                    <!-- Formulário de Pacote -->
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-800 mb-6">Criar Novo Pacote de Merenda</h4>
                        
                        <form method="POST" id="formPacote">
                            <input type="hidden" name="acao" value="cadastrar_pacote">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Nome do Pacote -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Pacote *</label>
                                    <input type="text" name="nome" required placeholder="Ex: Pacote Básico - Merenda Escolar"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>

                                <!-- Descrição -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição do Conteúdo *</label>
                                    <textarea name="descricao" rows="4" required 
                                              placeholder="Descreva detalhadamente o que contém este pacote de merenda. Ex: 2kg de arroz, 1kg de feijão, 500g de macarrão, 1kg de carne, 2L de leite, etc."
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"></textarea>
                                </div>

                                <!-- Valor Total -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor Total *</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-500">R$</span>
                                        <input type="number" name="valor_total" required min="0" step="0.01"
                                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-4">
                                <button type="button" onclick="limparFormularioPacote()" 
                                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                    Limpar
                                </button>
                                <button type="submit" 
                                        class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green transition">
                                    Criar Pacote
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab: Edição (Hidden, mostrado via JS) -->
            <div id="tab-edicao" class="tab-content">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Editar Item</h3>
                        <button onclick="cancelarEdicao()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form method="POST" id="formEdicao">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nome -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Alimento *</label>
                                <input type="text" name="nome" id="edit_nome" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Categoria -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Código do Produto</label>
                                <input type="text" name="codigo" id="edit_codigo" readonly
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                                <p class="text-xs text-gray-500 mt-1">Código único (não editável)</p>
                            </div>

                            <!-- Descrição -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                                <textarea name="descricao" id="edit_descricao" rows="3" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"></textarea>
                            </div>

                            <!-- Quantidade -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade *</label>
                                <input type="number" name="quantidade" id="edit_quantidade" required min="0" step="0.01"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Unidade de Medida -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Unidade de Medida *</label>
                                <select name="unidade_medida" id="edit_unidade_medida" required 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <option value="UN">Unidade (UN)</option>
                                    <option value="CX">Caixa (CX)</option>
                                    <option value="PC">Pacote (PC)</option>
                                    <option value="KG">Quilograma (KG)</option>
                                    <option value="L">Litro (L)</option>
                                    <option value="M">Metro (M)</option>
                                    <option value="M2">Metro Quadrado (M²)</option>
                                    <option value="DZ">Dúzia (DZ)</option>
                                    <option value="RL">Rolo (RL)</option>
                                </select>
                            </div>

                            <!-- Valor Unitário -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Valor Unitário *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500">R$</span>
                                    <input type="number" name="valor_unitario" id="edit_valor_unitario" required min="0" step="0.01"
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                </div>
                            </div>

                            <!-- Estoque Mínimo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estoque Mínimo *</label>
                                <input type="number" name="estoque_minimo" id="edit_estoque_minimo" required min="0" step="0.01"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Data de Aquisição -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Aquisição</label>
                                <input type="date" name="data_aquisicao" id="edit_data_aquisicao"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Fornecedor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fornecedor</label>
                                <input type="text" name="fornecedor" id="edit_fornecedor"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Localização -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Localização *</label>
                                <input type="text" name="localizacao" id="edit_localizacao" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>

                            <!-- Observações -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações/Descrição</label>
                                <textarea name="obs" id="edit_obs" rows="3" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-4">
                            <button type="button" onclick="cancelarEdicao()" 
                                    class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green transition">
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab: Pacotes de Merenda -->
            <div id="tab-pacotes" class="tab-content">
                <!-- Busca -->
                <div class="mb-6">
                    <form method="GET" class="flex gap-4">
                        <div class="flex-1">
                            <input type="text" name="busca" placeholder="Buscar pacotes..." 
                                   value="<?php echo htmlspecialchars($busca); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                        </div>
                        <button type="submit" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                        <?php if (!empty($busca)): ?>
                            <a href="?" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Limpar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabela de Pacotes -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pacote</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto Base</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Criação</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($pacotes)): ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                            </svg>
                                            <p class="text-lg font-medium">Nenhum pacote encontrado</p>
                                            <p class="text-sm mt-1">Clique em "Novo Pacote" para criar o primeiro pacote</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pacotes as $pacote): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($pacote['nome']); ?></div>
                                                <?php if (!empty($pacote['descricao'])): ?>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($pacote['descricao'], 0, 100)) . (strlen($pacote['descricao']) > 100 ? '...' : ''); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($pacote['produto_nome']); ?>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($pacote['produto_codigo']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo date('d/m/Y', strtotime($pacote['data_criacao'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab: Aprovar Cestas -->
            <div id="tab-cestas" class="tab-content">
                <!-- Busca -->
                <div class="mb-6">
                    <form method="GET" class="flex gap-4">
                        <div class="flex-1">
                            <input type="text" name="busca" placeholder="Buscar cestas por escola ou pacote..." 
                                   value="<?php echo htmlspecialchars($busca); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                        </div>
                        <button type="submit" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                        <?php if (!empty($busca)): ?>
                            <a href="?" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Limpar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabela de Cestas -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pacote</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitado Por</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($cestasPendentes)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <p class="text-lg font-medium">Nenhuma cesta pendente</p>
                                            <p class="text-sm mt-1">Todas as cestas foram processadas</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cestasPendentes as $cesta): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cesta['escola_nome']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($cesta['municipio']); ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cesta['pacote_nome']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($cesta['solicitado_por']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo date('d/m/Y', strtotime($cesta['data_pedido'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="aprovarCesta(<?php echo $cesta['id']; ?>)" 
                                                        class="text-green-600 hover:text-green-800 mr-3">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="rejeitarCesta(<?php echo $cesta['id']; ?>)" 
                                                        class="text-red-600 hover:text-red-800">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
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

    <!-- Modal de Exclusão -->
    <div id="modalExclusao" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-accent-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Confirmar Exclusão</h3>
            <p class="text-gray-600 text-center mb-6">
                Tem certeza que deseja excluir o item <strong id="nomeItemExclusao"></strong>? Esta ação não pode ser desfeita.
            </p>
            <form method="POST" id="formExclusao">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" id="idItemExclusao">
                <div class="flex space-x-4">
                    <button type="button" onclick="fecharModalExclusao()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-accent-red text-white rounded-lg hover:bg-red-700 transition">
                        Excluir
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Aprovação -->
    <div id="modalAprovacao" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Aprovar Cesta</h3>
            <p class="text-gray-600 text-center mb-6">
                Tem certeza que deseja aprovar esta cesta de merenda?
            </p>
            <form method="POST" id="formAprovacao">
                <input type="hidden" name="acao" value="aprovar_cesta">
                <input type="hidden" name="cesta_id" id="cesta_id_aprovacao">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações (opcional)</label>
                    <textarea name="observacoes" rows="3" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"></textarea>
                </div>
                <div class="flex space-x-4">
                    <button type="button" onclick="fecharModalAprovacao()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Aprovar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Rejeição -->
    <div id="modalRejeicao" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Rejeitar Cesta</h3>
            <p class="text-gray-600 text-center mb-6">
                Tem certeza que deseja rejeitar esta cesta de merenda?
            </p>
            <form method="POST" id="formRejeicao">
                <input type="hidden" name="acao" value="rejeitar_cesta">
                <input type="hidden" name="cesta_id" id="cesta_id_rejeicao">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Rejeição *</label>
                    <textarea name="motivo" rows="3" required 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"></textarea>
                </div>
                <div class="flex space-x-4">
                    <button type="button" onclick="fecharModalRejeicao()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Rejeitar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const main = document.querySelector('main');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
                
                if (main) {
                    main.classList.toggle('content-dimmed');
                }
            }
        }

        // Fechar sidebar ao clicar fora (mobile)
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const main = document.querySelector('main');
            
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(event.target) && !main.contains(event.target)) {
                    sidebar.classList.remove('open');
                    overlay.classList.add('hidden');
                    main.classList.remove('content-dimmed');
                }
            }
        });

        // Confirmar logout
        function confirmLogout() {
            if (confirm('Tem certeza que deseja sair?')) {
                window.location.href = '../auth/login.php?logout=1';
            }
        }

        function openUserProfile() {
            // Implementar modal de perfil do usuário
            alert('Modal de perfil do usuário - Em desenvolvimento');
        }

        // Aplicar tema persistente
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Aplicar classes do Tailwind para tema escuro
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
                document.body.classList.add('bg-gray-900', 'text-white');
            } else {
                document.documentElement.classList.remove('dark');
                document.body.classList.remove('bg-gray-900', 'text-white');
            }
        });

        // Toggle Theme
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            updateThemeIcon(newTheme);
        }

        function updateThemeIcon(theme) {
            const lightIcon = document.getElementById('theme-icon-light');
            const darkIcon = document.getElementById('theme-icon-dark');
            
            if (theme === 'dark') {
                lightIcon.classList.remove('hidden');
                darkIcon.classList.add('hidden');
            } else {
                lightIcon.classList.add('hidden');
                darkIcon.classList.remove('hidden');
            }
        }

        // Inicializar tema
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
        });

        // Gerenciar Tabs
        function showTab(tabName) {
            // Esconder todas as tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remover classe ativa de todos os botões
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('tab-active', 'border-primary-green', 'text-primary-green');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar tab selecionada
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Ativar botão correspondente
            const activeBtn = event.target;
            activeBtn.classList.add('tab-active', 'border-primary-green', 'text-primary-green');
            activeBtn.classList.remove('border-transparent', 'text-gray-500');
        }

        // Editar Item
        function editarItem(item) {
            // Preencher formulário de edição
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_nome').value = item.nome;
            document.getElementById('edit_categoria').value = item.categoria;
            document.getElementById('edit_descricao').value = item.descricao || '';
            document.getElementById('edit_quantidade').value = item.quantidade;
            document.getElementById('edit_unidade_medida').value = item.unidade_medida;
            document.getElementById('edit_valor_unitario').value = item.valor_unitario;
            document.getElementById('edit_estoque_minimo').value = item.estoque_minimo;
            document.getElementById('edit_data_aquisicao').value = item.data_aquisicao || '';
            document.getElementById('edit_fornecedor').value = item.fornecedor || '';
            document.getElementById('edit_localizacao').value = item.localizacao;
            document.getElementById('edit_status').value = item.status;
            document.getElementById('edit_obs').value = item.obs || '';
            
            // Mostrar tab de edição
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById('tab-edicao').classList.add('active');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Cancelar Edição
        function cancelarEdicao() {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById('tab-lista').classList.add('active');
        }

        // Excluir Item
        function excluirItem(id, nome) {
            document.getElementById('idItemExclusao').value = id;
            document.getElementById('nomeItemExclusao').textContent = nome;
            document.getElementById('modalExclusao').classList.remove('hidden');
        }

        function fecharModalExclusao() {
            document.getElementById('modalExclusao').classList.add('hidden');
        }

        // Limpar Formulário
        function limparFormulario() {
            document.getElementById('formCadastro').reset();
        }

        // Limpar Formulário de Pacote
        function limparFormularioPacote() {
            document.getElementById('formPacote').reset();
        }

        // Aprovar Cesta
        function aprovarCesta(cestaId) {
            document.getElementById('cesta_id_aprovacao').value = cestaId;
            document.getElementById('modalAprovacao').classList.remove('hidden');
        }

        function fecharModalAprovacao() {
            document.getElementById('modalAprovacao').classList.add('hidden');
        }

        // Rejeitar Cesta
        function rejeitarCesta(cestaId) {
            document.getElementById('cesta_id_rejeicao').value = cestaId;
            document.getElementById('modalRejeicao').classList.remove('hidden');
        }

        function fecharModalRejeicao() {
            document.getElementById('modalRejeicao').classList.add('hidden');
        }

        // Fechar modais ao clicar fora
        document.getElementById('modalExclusao')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalExclusao();
            }
        });

        document.getElementById('modalAprovacao')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalAprovacao();
            }
        });

        document.getElementById('modalRejeicao')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalRejeicao();
            }
        });
    </script>
</body>
</html>

