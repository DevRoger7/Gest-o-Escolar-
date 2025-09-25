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

// Funções para gerenciamento de escolas
function listarEscolas($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT e.id, e.nome, e.endereco, e.telefone, e.email, e.municipio, e.cep, e.qtd_salas, e.obs, e.codigo, e.criado_em as data_criacao,
                   p.nome as gestor_nome, p.email as gestor_email
            FROM escola e 
            LEFT JOIN gestor_lotacao gl ON e.id = gl.escola_id AND gl.responsavel = 1
            LEFT JOIN gestor g ON gl.gestor_id = g.id
            LEFT JOIN pessoa p ON g.pessoa_id = p.id
            WHERE 1=1";
    
    if (!empty($busca)) {
        $sql .= " AND (e.nome LIKE :busca OR e.endereco LIKE :busca OR e.email LIKE :busca OR e.municipio LIKE :busca OR p.nome LIKE :busca)";
    }
    
    $sql .= " ORDER BY e.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarGestores($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT u.id, p.nome, p.email, p.telefone, u.role
            FROM usuario u 
            JOIN pessoa p ON u.pessoa_id = p.id 
            WHERE u.role = 'GESTAO' AND u.ativo = 1";
    
    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE :busca OR p.email LIKE :busca)";
    }
    
    $sql .= " ORDER BY p.nome ASC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cadastrarEscola($dados) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Inserir escola
        $stmt = $conn->prepare("INSERT INTO escola (nome, endereco, telefone, email, municipio, cep, qtd_salas, obs, codigo) 
                                VALUES (:nome, :endereco, :telefone, :email, :municipio, :cep, :qtd_salas, :obs, :codigo)");
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':municipio', $dados['municipio']);
        $stmt->bindParam(':cep', $dados['cep']);
        $stmt->bindParam(':qtd_salas', $dados['qtd_salas']);
        $stmt->bindParam(':obs', $dados['obs']);
        $stmt->bindParam(':codigo', $dados['codigo']);
        
        $stmt->execute();
        $escolaId = $conn->lastInsertId();
        
        // Se um gestor (usuario) foi selecionado, criar a lotação mapeando para a tabela gestor
        if (!empty($dados['gestor_id'])) {
            // Primeiro, localizar o gestor.id correspondente ao usuario.id informado
            // Alguns bancos usam nomes no singular/plural. Tentamos encontrar a relação adequada.
            // 1) Tentar via tabela gestor com coluna usuario_id
            $gestorId = null;
            try {
                $stmt = $conn->prepare("SELECT id FROM gestor WHERE usuario_id = :usuario_id LIMIT 1");
                $stmt->bindParam(':usuario_id', $dados['gestor_id']);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $gestorId = (int)$row['id'];
                }
            } catch (PDOException $e) {
                // Se a tabela/coluna não existir, ignorar e tentar outro caminho
            }

            // 2) Caso não ache, tentar via ligação por pessoa: gestor.pessoa_id -> usuario.pessoa_id
            if ($gestorId === null) {
                try {
                    $stmt = $conn->prepare("SELECT g.id 
                                            FROM gestor g 
                                            INNER JOIN usuario u ON u.pessoa_id = g.pessoa_id 
                                            WHERE u.id = :usuario_id 
                                            LIMIT 1");
                    $stmt->bindParam(':usuario_id', $dados['gestor_id']);
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $gestorId = (int)$row['id'];
                    }
                } catch (PDOException $e) {
                    // Ignorar e continuar para mensagem de erro amigável
                }
            }

            if ($gestorId === null) {
                throw new PDOException('Gestor selecionado não possui cadastro válido em gestor.');
            }

            $stmt = $conn->prepare("INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel) 
                                    VALUES (:gestor_id, :escola_id, CURDATE(), 1)");
            $stmt->bindParam(':gestor_id', $gestorId);
            $stmt->bindParam(':escola_id', $escolaId);
            $stmt->execute();
        }
        
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Escola cadastrada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao cadastrar escola: ' . $e->getMessage()];
    }
}

function excluirEscola($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("DELETE FROM escola WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Escola excluída com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao excluir escola: ' . $e->getMessage()];
    }
}

function atualizarEscola($id, $dados) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Atualizar dados da escola
        $stmt = $conn->prepare("UPDATE escola SET 
                                nome = :nome, 
                                endereco = :endereco, 
                                telefone = :telefone, 
                                email = :email, 
                                municipio = :municipio, 
                                cep = :cep, 
                                qtd_salas = :qtd_salas, 
                                obs = :obs, 
                                codigo = :codigo 
                                WHERE id = :id");
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':endereco', $dados['endereco']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':municipio', $dados['municipio']);
        $stmt->bindParam(':cep', $dados['cep']);
        $stmt->bindParam(':qtd_salas', $dados['qtd_salas']);
        $stmt->bindParam(':obs', $dados['obs']);
        $stmt->bindParam(':codigo', $dados['codigo']);
        
        $stmt->execute();
        
        // Gerenciar lotação do gestor
        // Primeiro, remover lotação atual (se houver)
        $stmt = $conn->prepare("DELETE FROM gestor_lotacao WHERE escola_id = :escola_id AND responsavel = 1");
        $stmt->bindParam(':escola_id', $id);
        $stmt->execute();
        
        // Se um novo gestor foi selecionado, criar a lotação
        if (!empty($dados['gestor_id'])) {
            // Localizar o gestor.id correspondente ao usuario.id informado
            $gestorId = null;
            
            // 1) Tentar via tabela gestor com coluna usuario_id
            try {
                $stmt = $conn->prepare("SELECT id FROM gestor WHERE usuario_id = :usuario_id LIMIT 1");
                $stmt->bindParam(':usuario_id', $dados['gestor_id']);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $gestorId = (int)$row['id'];
                }
            } catch (PDOException $e) {
                // Se a tabela/coluna não existir, ignorar e tentar outro caminho
            }

            // 2) Caso não ache, tentar via ligação por pessoa: gestor.pessoa_id -> usuario.pessoa_id
            if ($gestorId === null) {
                try {
                    $stmt = $conn->prepare("SELECT g.id 
                                            FROM gestor g 
                                            INNER JOIN usuario u ON u.pessoa_id = g.pessoa_id 
                                            WHERE u.id = :usuario_id 
                                            LIMIT 1");
                    $stmt->bindParam(':usuario_id', $dados['gestor_id']);
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $gestorId = (int)$row['id'];
                    }
                } catch (PDOException $e) {
                    // Ignorar e continuar para mensagem de erro amigável
                }
            }

            if ($gestorId === null) {
                throw new PDOException('Gestor selecionado não possui cadastro válido em gestor.');
            }

            $stmt = $conn->prepare("INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel) 
                                    VALUES (:gestor_id, :escola_id, CURDATE(), 1)");
            $stmt->bindParam(':gestor_id', $gestorId);
            $stmt->bindParam(':escola_id', $id);
            $stmt->execute();
        }
        
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Escola atualizada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao atualizar escola: ' . $e->getMessage()];
    }
}

// Processar formulários
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        // Cadastrar nova escola
        if ($_POST['acao'] === 'cadastrar') {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'endereco' => $_POST['endereco'] ?? '',
                'telefone' => $_POST['telefone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'municipio' => $_POST['municipio'] ?? '',
                'cep' => $_POST['cep'] ?? '',
                'qtd_salas' => $_POST['qtd_salas'] ?? null,
                'obs' => $_POST['obs'] ?? '',
                'codigo' => $_POST['codigo'] ?? '',
                'gestor_id' => $_POST['gestor_id'] ?? null
            ];
            
            $resultado = cadastrarEscola($dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
        
        // Editar escola
        if ($_POST['acao'] === 'editar' && isset($_POST['id'])) {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'endereco' => $_POST['endereco'] ?? '',
                'telefone' => $_POST['telefone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'municipio' => $_POST['municipio'] ?? '',
                'cep' => $_POST['cep'] ?? '',
                'qtd_salas' => $_POST['qtd_salas'] ?? null,
                'obs' => $_POST['obs'] ?? '',
                'codigo' => $_POST['codigo'] ?? '',
                'gestor_id' => $_POST['gestor_id'] ?? null
            ];
            
            $resultado = atualizarEscola($_POST['id'], $dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
        
        // Excluir escola
        if ($_POST['acao'] === 'excluir' && isset($_POST['id'])) {
            $resultado = excluirEscola($_POST['id']);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
    }
}

// Buscar escolas
$busca = $_GET['busca'] ?? '';
$escolas = listarEscolas($busca);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Escolas - SIGAE</title>
    
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
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Theme Manager -->
    <script src="theme-manager.js"></script>
    
    <style>
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
        
        /* Estilos para o menu lateral */
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
            }

            .sidebar-mobile.open {
                transform: translateX(0);
            }
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
            --text-accent: #d0d0d0;
            --border-color: #404040;
            --border-light: #505050;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.6);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.7);
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

        [data-theme="dark"] .text-gray-800 {
            color: #ffffff !important;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .text-gray-500 {
            color: #c0c0c0 !important;
        }

        [data-theme="dark"] .text-gray-400 {
            color: #a0a0a0 !important;
        }

        [data-theme="dark"] .text-gray-300 {
            color: #d0d0d0 !important;
        }

        [data-theme="dark"] .text-gray-200 {
            color: #e8e8e8 !important;
        }

        [data-theme="dark"] .text-gray-100 {
            color: #f0f0f0 !important;
        }

        [data-theme="dark"] .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] .text-gray-700 {
            color: #d0d0d0 !important;
        }

        /* Corrigir hovers brancos no modo escuro */
        [data-theme="dark"] .hover\:bg-white:hover {
            background-color: #2a2a2a !important;
        }

        [data-theme="dark"] .hover\:bg-gray-50:hover {
            background-color: #333333 !important;
        }

        [data-theme="dark"] .hover\:bg-gray-100:hover {
            background-color: #3a3a3a !important;
        }

        [data-theme="dark"] .hover\:text-gray-900:hover {
            color: #ffffff !important;
        }

        [data-theme="dark"] .hover\:text-gray-800:hover {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .border-gray-200 {
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .border-gray-300 {
            border-color: var(--border-light) !important;
        }

        [data-theme="dark"] .border-gray-400 {
            border-color: var(--border-light) !important;
        }

        [data-theme="dark"] .bg-gray-50 {
            background: #2a2a2a !important;
            border: 1px solid #555555 !important;
        }

        [data-theme="dark"] .bg-gray-100 {
            background-color: #333333 !important;
        }

        [data-theme="dark"] .bg-gray-200 {
            background-color: #3a3a3a !important;
        }

        [data-theme="dark"] .bg-gray-300 {
            background-color: #404040 !important;
        }

        [data-theme="dark"] .shadow-lg {
            box-shadow: var(--shadow-lg) !important;
        }

        [data-theme="dark"] .shadow-sm {
            box-shadow: var(--shadow) !important;
        }

        [data-theme="dark"] #sidebar {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--border-color);
        }

        [data-theme="dark"] .menu-item {
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] .menu-item:hover {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .menu-item.active {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border-right: 3px solid var(--primary-green);
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] header {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border-bottom: 1px solid var(--border-color);
        }

        [data-theme="dark"] input,
        [data-theme="dark"] select,
        [data-theme="dark"] textarea {
            background-color: #2d2d2d !important;
            border-color: #555555 !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] input::placeholder,
        [data-theme="dark"] textarea::placeholder {
            color: #a0a0a0 !important;
        }

        [data-theme="dark"] input:focus,
        [data-theme="dark"] select:focus,
        [data-theme="dark"] textarea:focus {
            border-color: var(--primary-green) !important;
            box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.3) !important;
            background-color: #333333 !important;
        }

        /* Corrigir elementos específicos problemáticos */
        [data-theme="dark"] .bg-white {
            background-color: #2a2a2a !important;
        }

        [data-theme="dark"] .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] .text-gray-800 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .text-gray-700 {
            color: #d0d0d0 !important;
        }

        /* Corrigir tabelas */
        [data-theme="dark"] table {
            background-color: #2a2a2a !important;
        }

        [data-theme="dark"] th {
            background-color: #333333 !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] td {
            background-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] tr:hover td {
            background-color: #333333 !important;
        }

        /* Estilos para o formulário de cadastro no modo escuro */
        [data-theme="dark"] #tab-cadastrar .bg-white {
            background-color: var(--bg-secondary) !important;
        }
        [data-theme="dark"] #tab-cadastrar .text-gray-900 {
            color: var(--text-primary) !important;
        }
        [data-theme="dark"] #tab-cadastrar .text-gray-600 {
            color: var(--text-secondary) !important;
        }
        [data-theme="dark"] #tab-cadastrar .border-gray-200 {
            border-color: var(--border-color) !important;
        }
        [data-theme="dark"] #tab-cadastrar .hover\:bg-gray-50:hover {
            background-color: var(--bg-tertiary) !important;
        }
        [data-theme="dark"] #tab-cadastrar input,
        [data-theme="dark"] #tab-cadastrar select {
            background-color: var(--bg-tertiary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }
        [data-theme="dark"] #tab-cadastrar input::placeholder {
            color: var(--text-muted) !important;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden mobile-menu-overlay lg:hidden"></div>

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
                    <a href="#" onclick="showSection('dashboard')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['cadastrar_pessoas']) || isset($_SESSION['matricular_alunos']) || isset($_SESSION['acessar_registros']) || $_SESSION['tipo'] === 'ADM') { ?>
                <?php } ?>
                <?php if ($_SESSION['tipo'] === 'GESTAO') { ?>
                <li id="gestao-menu">
                    <a href="#" onclick="showSection('gestao')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Gestão Escolar</span>
                    </a>
                </li>
                <?php } ?>
                <?php if ($_SESSION['tipo'] === 'ADM_MERENDA') { ?>
                <li id="merenda-menu">
                    <a href="#" onclick="showSection('merenda')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Merenda</span>
                    </a>
                </li>
                <?php } ?>
                <?php if (isset($_SESSION['Gerenciador de Usuarios'])) { ?>
                    <li>
                        <a href="../../subsystems/gerenciador_usuario/index.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            <span>Gerenciador de Usuários</span>
                        </a>
                    </li>
                <?php } ?>
                <?php if (isset($_SESSION['Estoque'])) { ?>
                    <li>
                        <a href="../../subsystems/controle_de_estoque/default.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span>Controle de Estoque</span>
                        </a>
                    </li>
                <?php } ?>
                <?php if (isset($_SESSION['Biblioteca'])) { ?>
                    <li>
                        <a href="../../subsystems/biblioteca/default.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <span>Biblioteca</span>
                        </a>
                    </li>
                <?php } ?>
                <?php if (isset($_SESSION['Entrada/saída'])) { ?>
                    <li>
                        <a href="../../subsystems/entradasaida/app/main/views/inicio.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            <span>Entrada/Saída</span>
                        </a>
                    </li>
                <?php } ?>
                <?php if (isset($_SESSION['relatorio_geral']) || isset($_SESSION['gerar_relatorios_pedagogicos']) || $_SESSION['tipo'] === 'ADM') { ?>
                <li id="relatorios-menu">
                    <a href="#" onclick="showSection('relatorios')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Relatórios</span>
                    </a>
                </li>
                <?php } ?>
                <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                <li id="escolas-menu">
                    <a href="gestao_escolas.php" class="menu-item flex items-center active space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <span>Escolas</span>
                    </a>
                </li>
                <li id="usuarios-menu">
                    <a href="gestao_usuarios.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span>Usuários</span>
                    </a>
                </li>
                <li id="estoque-central-menu">
                    <a href="#" onclick="showSection('estoque-central')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Estoque Central</span>
                    </a>
                </li>
                <?php } ?>
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

    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30 ml-0 lg:ml-64 content-transition">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Mobile Menu Button -->
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-green" aria-label="Abrir menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Escolas</h1>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right hidden lg:block">
                            <p class="text-sm font-medium text-gray-800" id="currentSchool">
                                <?php 
                                if ($_SESSION['tipo'] === 'ADM') {
                                    echo 'Secretaria Municipal da Educação';
                                } else {
                                    echo $_SESSION['escola_atual'] ?? 'Escola Municipal';
                                }
                                ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php 
                                if ($_SESSION['tipo'] === 'ADM') {
                                    echo 'Órgão Central';
                                } else {
                                    echo 'Escola Atual';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="ml-0 lg:ml-64 content-transition px-4 sm:px-6 lg:px-8 py-8">
            <?php if (!empty($mensagem)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $tipoMensagem === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <div class="flex space-x-8">
                    <button onclick="showTab('tab-listar')" class="tab-btn tab-active py-4 px-1 focus:outline-none">
                        Listar Escolas
                    </button>
                    <button onclick="showTab('tab-cadastrar')" class="tab-btn py-4 px-1 focus:outline-none">
                        Cadastrar Nova Escola
                    </button>
                </div>
            </div>
            
            <!-- Tab Contents -->
            <div id="tab-listar" class="tab-content active">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Lista de Escolas</h2>
                    
                    <!-- Search Box -->
                    <form method="GET" class="mb-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" name="busca" placeholder="Buscar por nome, endereço ou gestor..." 
                                   value="<?php echo htmlspecialchars($busca); ?>"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-green focus:border-primary-green">
                        </div>
                    </form>
                    
                    <!-- Tabela de Escolas -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código INEP</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endereço</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gestor</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contato</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salas</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Criação</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($escolas)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Nenhuma escola encontrada
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($escolas as $escola): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-primary-green rounded-full flex items-center justify-center">
                                                    <span class="text-white font-medium"><?php echo substr($escola['nome'], 0, 1); ?></span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($escola['nome']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $escola['codigo'] ? htmlspecialchars($escola['codigo']) : 'N/A'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($escola['endereco']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $escola['gestor_nome'] ? htmlspecialchars($escola['gestor_nome']) : 'Não definido'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>
                                                <div><?php echo htmlspecialchars($escola['telefone']); ?></div>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($escola['email']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $escola['qtd_salas'] ? $escola['qtd_salas'] : 'N/A'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($escola['data_criacao'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="abrirModalEdicaoEscola(<?php echo $escola['id']; ?>, '<?php echo htmlspecialchars($escola['nome']); ?>')" class="text-blue-600 hover:text-blue-900">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="abrirModalExclusaoEscola(<?php echo $escola['id']; ?>, '<?php echo htmlspecialchars($escola['nome']); ?>')" class="text-red-600 hover:text-red-900">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
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

            <!-- Tab Cadastrar -->
            <div id="tab-cadastrar" class="tab-content hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Cadastrar Nova Escola</h2>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="acao" value="cadastrar">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Coluna Esquerda -->
                            <div class="space-y-6">
                                <div>
                                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">Nome da Escola *</label>
                                    <input type="text" id="nome" name="nome" required
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="telefone" class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                    <input type="text" id="telefone" name="telefone" placeholder="(00) 0000-0000"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="cep" class="block text-sm font-medium text-gray-700 mb-2">CEP <span class="text-red-500">*</span></label>
                                    <div class="flex space-x-2">
                                        <input type="text" id="cep" name="cep" required placeholder="00000-000" maxlength="9" onkeyup="formatarCEPCadastro(this)" onblur="buscarCEPCadastro(this.value)"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                        <button type="button" onclick="buscarCEPCadastro(document.getElementById('cep').value)" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div id="resultadoCEPCadastro" class="mt-2 text-sm text-gray-600 hidden"></div>
                                </div>
                                
                                <div>
                                    <label for="endereco" class="block text-sm font-medium text-gray-700 mb-2">Endereço *</label>
                                    <input type="text" id="endereco" name="endereco" required
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="qtd_salas" class="block text-sm font-medium text-gray-700 mb-2">Quantidade de Salas <span class="text-red-500">*</span></label>
                                    <input type="number" id="qtd_salas" name="qtd_salas" required min="1" placeholder="Ex: 12"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                            </div>
                            
                            <!-- Coluna Direita -->
                            <div class="space-y-6">
                                <div>
                                    <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">Código INEP</label>
                                    <input type="text" id="codigo" name="codigo" placeholder="Ex: 12345678"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" id="email" name="email"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="municipio" class="block text-sm font-medium text-gray-700 mb-2">Município *</label>
                                    <input type="text" id="municipio" name="municipio" required
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="gestor_search" class="block text-sm font-medium text-gray-700 mb-2">Selecionar Gestor <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="text" id="gestor_search" placeholder="Digite o nome do gestor..." 
                                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green"
                                               autocomplete="off">
                                        <input type="hidden" id="gestor_id" name="gestor_id" required>
                                        <div id="gestor_results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                                    </div>
                                    <div id="gestor_selected" class="mt-2 hidden">
                                        <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-3">
                                            <div>
                                                <span class="text-sm font-medium text-green-800" id="gestor_nome_selecionado"></span>
                                                <span class="text-xs text-green-600 block" id="gestor_email_selecionado"></span>
                                            </div>
                                            <button type="button" onclick="removerGestor()" class="text-green-600 hover:text-green-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="reset" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green">
                                Limpar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green">
                                Cadastrar Escola
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal de Exclusão de Escola -->
    <div id="modalExclusaoEscola" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirmar Exclusão</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Tem certeza que deseja excluir a escola <strong id="nomeEscolaExclusao"></strong>?
                </p>
                <p class="text-xs text-red-600 mb-6">
                    ⚠️ Esta ação não pode ser desfeita. Todos os dados relacionados à escola serão perdidos permanentemente.
                </p>
                
                <div class="flex space-x-3 justify-center">
                    <button onclick="fecharModalExclusaoEscola()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                        Cancelar
                    </button>
                    <form id="formExclusaoEscola" method="POST" class="inline">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" id="idEscolaExclusao">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Sim, Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Edição de Escola (Full Screen) -->
    <div id="modalEdicaoEscola" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white w-full h-full overflow-hidden flex flex-col">
            <!-- Header do Modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900" id="tituloModalEdicao">Editar Escola</h3>
                        <p class="text-sm text-gray-600">Gerencie as informações e corpo docente da escola</p>
                    </div>
                </div>
                <button onclick="fecharModalEdicaoEscola()" class="p-2 hover:bg-gray-200 rounded-full transition-colors duration-200">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal -->
            <div class="flex-1 overflow-y-auto p-6 flex flex-col">
                <form id="formEdicaoEscola" method="POST" class="flex flex-col flex-1 space-y-8">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" id="edit_escola_id">
                    
                    <!-- Tabs de Navegação -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button type="button" onclick="mostrarAbaEdicao('dados-basicos')" id="tab-dados-basicos" class="tab-edicao active py-2 px-1 border-b-2 border-primary-green font-medium text-sm text-primary-green">
                                Dados Básicos
                            </button>
                            <button type="button" onclick="mostrarAbaEdicao('gestor')" id="tab-gestor" class="tab-edicao py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Gestor
                            </button>
                            <button type="button" onclick="mostrarAbaEdicao('corpo-docente')" id="tab-corpo-docente" class="tab-edicao py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Corpo Docente
                            </button>
                        </nav>
                    </div>
                    
                    <!-- Aba Dados Básicos -->
                    <div id="aba-dados-basicos" class="aba-edicao flex-1 flex flex-col">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Coluna Esquerda -->
                            <div class="space-y-6">
                                <div>
                                    <label for="edit_nome" class="block text-sm font-medium text-gray-700 mb-2">Nome da Escola *</label>
                                    <input type="text" id="edit_nome" name="nome" required
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="edit_telefone" class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                    <input type="text" id="edit_telefone" name="telefone" placeholder="(00) 0000-0000"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="edit_cep" class="block text-sm font-medium text-gray-700 mb-2">CEP <span class="text-red-500">*</span></label>
                                    <div class="flex space-x-2">
                                        <input type="text" id="edit_cep" name="cep" required placeholder="00000-000" maxlength="9" onkeyup="formatarCEP(this)" onblur="buscarCEP(this.value)"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                        <button type="button" onclick="buscarCEP(document.getElementById('edit_cep').value)" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div id="resultadoCEP" class="mt-2 text-sm text-gray-600 hidden"></div>
                                </div>
                                
                                <div>
                                    <label for="edit_endereco" class="block text-sm font-medium text-gray-700 mb-2">Endereço *</label>
                                    <input type="text" id="edit_endereco" name="endereco" required
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="edit_qtd_salas" class="block text-sm font-medium text-gray-700 mb-2">Quantidade de Salas <span class="text-red-500">*</span></label>
                                    <input type="number" id="edit_qtd_salas" name="qtd_salas" required min="1" placeholder="Ex: 12"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                            </div>
                            
                            <!-- Coluna Direita -->
                            <div class="space-y-6">
                                <div>
                                    <label for="edit_codigo" class="block text-sm font-medium text-gray-700 mb-2">Código INEP</label>
                                    <input type="text" id="edit_codigo" name="codigo" placeholder="Ex: 12345678"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" id="edit_email" name="email"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                                <div>
                                    <label for="edit_municipio" class="block text-sm font-medium text-gray-700 mb-2">Município *</label>
                                    <input type="text" id="edit_municipio" name="municipio" required
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aba Gestor -->
                    <div id="aba-gestor" class="aba-edicao hidden flex-1 flex flex-col">
                        <div class="space-y-6">
                            <div>
                                <label for="edit_gestor_search" class="block text-sm font-medium text-gray-700 mb-2">Selecionar Gestor</label>
                                <div class="relative">
                                    <input type="text" id="edit_gestor_search" placeholder="Digite o nome do gestor..." 
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green"
                                           autocomplete="off">
                                    <input type="hidden" id="edit_gestor_id" name="gestor_id">
                                    <div id="edit_gestor_results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto"></div>
                                </div>
                                <div id="edit_gestor_selected" class="mt-2 hidden">
                                    <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-3">
                                        <div>
                                            <span class="text-sm font-medium text-green-800" id="edit_gestor_nome_selecionado"></span>
                                            <span class="text-xs text-green-600 block" id="edit_gestor_email_selecionado"></span>
                                        </div>
                                        <button type="button" onclick="removerGestorEdicao()" class="text-green-600 hover:text-green-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aba Corpo Docente -->
                    <div id="aba-corpo-docente" class="aba-edicao hidden flex-1 flex flex-col">
                        <div class="space-y-6 flex-1 flex flex-col">
                            <div class="flex items-center justify-between">
                                <h4 class="text-lg font-medium text-gray-900">Professores da Escola</h4>
                                <button type="button" onclick="mostrarAdicionarProfessores()" class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span>Adicionar Professor</span>
                                </button>
                            </div>
                            
                            <!-- Lista de Professores Atuais -->
                            <div id="lista-professores" class="space-y-3">
                                <!-- Professores serão carregados aqui via JavaScript -->
                            </div>

                            <!-- Seção Adicionar Professores (inicialmente oculta) -->
                            <div id="secao-adicionar-professores" class="hidden flex-1 flex flex-col">
                                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 flex-1 flex flex-col">
                                    <div class="flex items-center justify-between mb-4">
                                        <h5 class="text-lg font-semibold text-gray-900">Selecionar Professores</h5>
                                        <button type="button" onclick="ocultarAdicionarProfessores()" class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Search and Filter -->
                                    <div class="mb-6">
                                        <div class="flex flex-col sm:flex-row gap-4">
                                            <div class="flex-1">
                                                <div class="relative">
                                                    <input type="text" id="buscaProfessorEdicao" placeholder="Buscar professor por nome..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="sm:w-64">
                                                <select id="filtroDisciplinaEdicao" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                    <option value="">Todas as disciplinas</option>
                                                    <!-- Disciplinas serão carregadas dinamicamente do backend -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Teachers List -->
                                    <div class="mb-6 flex-1 flex flex-col">
                                        <div class="flex items-center justify-between mb-4">
                                            <h6 class="text-md font-semibold text-gray-900">Professores Disponíveis</h6>
                                            <div class="flex items-center space-x-2">
                                                <input type="checkbox" id="selecionarTodosEdicao" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                                <label for="selecionarTodosEdicao" class="text-sm text-gray-600">Selecionar todos</label>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-1 overflow-y-auto border border-gray-200 rounded-lg" id="listaProfessoresDisponiveisEdicao">
                                            <!-- Lista de professores será carregada aqui -->
                                        </div>
                                    </div>

                                    <!-- Selected Teachers Summary -->
                                    <div id="resumoProfessoresSelecionadosEdicao" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg hidden">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-green-800">Professores selecionados:</span>
                                        </div>
                                        <div id="listaProfessoresSelecionadosEdicao" class="text-sm text-green-700">
                                            <!-- Lista dos professores selecionados -->
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões de Ação -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-auto">
                        <button type="button" onclick="fecharModalEdicaoEscola()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green transition-colors duration-200">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <script>
        // Função para alternar entre as tabs
        function showTab(tabId) {
            // Esconder todas as tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remover classe ativa de todos os botões
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
            });
            
            // Mostrar a tab selecionada
            document.getElementById(tabId).classList.add('active');
            
            // Adicionar classe ativa ao botão clicado
            event.currentTarget.classList.add('tab-active');
        }
        
        // Função para abrir modal de exclusão de escola
        function abrirModalExclusaoEscola(id, nome) {
            document.getElementById('idEscolaExclusao').value = id;
            document.getElementById('nomeEscolaExclusao').textContent = nome;
            document.getElementById('modalExclusaoEscola').classList.remove('hidden');
        }
        
        // Função para fechar modal de exclusão de escola
        function fecharModalExclusaoEscola() {
            document.getElementById('modalExclusaoEscola').classList.add('hidden');
        }
        
        // Fechar modal clicando fora dele
        document.getElementById('modalExclusaoEscola').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalExclusaoEscola();
            }
        });
        
        // Função para buscar gestores
        function buscarGestores(termo) {
            if (termo.length < 2) {
                document.getElementById('gestor_results').classList.add('hidden');
                return;
            }
            
            fetch(`../../Controllers/gestao/GestorController.php?busca=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    const results = document.getElementById('gestor_results');
                    results.innerHTML = '';
                    
                    if (data.length === 0) {
                        results.innerHTML = '<div class="p-3 text-sm text-gray-500">Nenhum gestor encontrado</div>';
                    } else {
                        data.forEach(gestor => {
                            const div = document.createElement('div');
                            div.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0';
                            div.innerHTML = `
                                <div class="font-medium text-gray-900">${gestor.nome}</div>
                                <div class="text-sm text-gray-500">${gestor.email}</div>
                            `;
                            div.onclick = () => selecionarGestor(gestor);
                            results.appendChild(div);
                        });
                    }
                    
                    results.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erro ao buscar gestores:', error);
                });
        }
        
        // Função para selecionar gestor
        function selecionarGestor(gestor) {
            document.getElementById('gestor_id').value = gestor.id;
            document.getElementById('gestor_search').value = gestor.nome; // Mostrar o nome no input
            document.getElementById('gestor_nome_selecionado').textContent = gestor.nome;
            document.getElementById('gestor_email_selecionado').textContent = gestor.email;
            document.getElementById('gestor_results').classList.add('hidden');
            document.getElementById('gestor_selected').classList.remove('hidden');
        }
        
        // Função para remover gestor selecionado
        function removerGestor() {
            document.getElementById('gestor_id').value = '';
            document.getElementById('gestor_search').value = '';
            document.getElementById('gestor_selected').classList.add('hidden');
        }
        
        // Funções do Modal de Edição
        function abrirModalEdicaoEscola(id, nome) {
            document.getElementById('edit_escola_id').value = id;
            document.getElementById('tituloModalEdicao').textContent = `Editar Escola - ${nome}`;
            document.getElementById('modalEdicaoEscola').classList.remove('hidden');
            
            // Carregar dados da escola
            carregarDadosEscola(id);
        }
        
        function fecharModalEdicaoEscola() {
            document.getElementById('modalEdicaoEscola').classList.add('hidden');
        }
        
        function carregarDadosEscola(id) {
            // Fazer requisição AJAX para obter os dados da escola
            fetch(`../../Controllers/gestao/EscolaController.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        const escola = data.escola;
                        
                        // Preencher campos básicos da escola
                        document.getElementById('edit_escola_id').value = escola.id;
                        document.getElementById('edit_nome').value = escola.nome || '';
                        document.getElementById('edit_telefone').value = escola.telefone || '';
                        document.getElementById('edit_cep').value = escola.cep || '';
                        document.getElementById('edit_endereco').value = escola.endereco || '';
                        document.getElementById('edit_qtd_salas').value = escola.qtd_salas || '';
                        document.getElementById('edit_codigo').value = escola.codigo || '';
                        document.getElementById('edit_email').value = escola.email || '';
                        document.getElementById('edit_municipio').value = escola.municipio || '';
                        
                        // Preencher dados do gestor se existir
                        if (escola.gestor_usuario_id) {
                            document.getElementById('edit_gestor_id').value = escola.gestor_usuario_id;
                            document.getElementById('edit_gestor_search').value = escola.gestor_nome || '';
                            document.getElementById('edit_gestor_nome_selecionado').textContent = escola.gestor_nome || '';
                            document.getElementById('edit_gestor_email_selecionado').textContent = escola.gestor_email || '';
                            document.getElementById('edit_gestor_selected').classList.remove('hidden');
                        } else {
                            // Limpar campos do gestor se não houver
                            document.getElementById('edit_gestor_id').value = '';
                            document.getElementById('edit_gestor_search').value = '';
                            document.getElementById('edit_gestor_selected').classList.add('hidden');
                        }
                        
                        console.log('Dados da escola carregados com sucesso:', escola);
                    } else {
                        console.error('Erro ao carregar dados da escola:', data.mensagem);
                        alert('Erro ao carregar dados da escola: ' + data.mensagem);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    alert('Erro ao carregar dados da escola. Tente novamente.');
                });
        }
        
        function mostrarAbaEdicao(abaId) {
            // Esconder todas as abas
            document.querySelectorAll('.aba-edicao').forEach(aba => {
                aba.classList.add('hidden');
            });
            
            // Remover classe ativa de todos os botões
            document.querySelectorAll('.tab-edicao').forEach(btn => {
                btn.classList.remove('active', 'border-primary-green', 'text-primary-green');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar a aba selecionada
            document.getElementById(`aba-${abaId}`).classList.remove('hidden');
            
            // Adicionar classe ativa ao botão clicado
            const botaoAtivo = document.getElementById(`tab-${abaId}`);
            botaoAtivo.classList.add('active', 'border-primary-green', 'text-primary-green');
            botaoAtivo.classList.remove('border-transparent', 'text-gray-500');
        }
        
        function mostrarAdicionarProfessores() {
            // Mostrar seção de adicionar professores
            document.getElementById('secao-adicionar-professores').classList.remove('hidden');
            carregarDisciplinas();
            carregarProfessoresDisponiveisEdicao();
        }

        function carregarDisciplinas() {
            const selectDisciplinas = document.getElementById('filtroDisciplinaEdicao');
            
            // Limpar opções existentes (exceto "Todas as disciplinas")
            selectDisciplinas.innerHTML = '<option value="">Todas as disciplinas</option>';
            
            // Aqui você faria a requisição para o backend
            // fetch('buscar_disciplinas.php')
            //     .then(response => response.json())
            //     .then(disciplinas => {
            //         disciplinas.forEach(disciplina => {
            //             const option = document.createElement('option');
            //             option.value = disciplina.id;
            //             option.textContent = disciplina.nome;
            //             selectDisciplinas.appendChild(option);
            //         });
            //     })
            //     .catch(error => {
            //         console.error('Erro ao carregar disciplinas:', error);
            //     });
        }

        function ocultarAdicionarProfessores() {
            // Ocultar seção de adicionar professores
            document.getElementById('secao-adicionar-professores').classList.add('hidden');
            resetarSelecaoProfessores();
        }

        function resetarSelecaoProfessores() {
            // Reset form
            document.getElementById('buscaProfessorEdicao').value = '';
            document.getElementById('filtroDisciplinaEdicao').value = '';
            document.getElementById('selecionarTodosEdicao').checked = false;
            
            // Clear selections
            document.querySelectorAll('.checkbox-professor-edicao').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Hide summary
            document.getElementById('resumoProfessoresSelecionadosEdicao').classList.add('hidden');
        }

        function carregarProfessoresDisponiveisEdicao() {
            const container = document.getElementById('listaProfessoresDisponiveisEdicao');
            container.innerHTML = '';

            // Mostrar loading
            container.innerHTML = `
                <div class="p-8 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green mx-auto mb-4"></div>
                    <p class="text-gray-600">Carregando professores disponíveis...</p>
                </div>
            `;

            // Aqui você faria a requisição para o backend
            // fetch('buscar_professores.php')
            //     .then(response => response.json())
            //     .then(professores => {
            //         renderizarProfessores(professores);
            //     })
            //     .catch(error => {
            //         console.error('Erro ao carregar professores:', error);
            //         container.innerHTML = `
            //             <div class="p-8 text-center">
            //                 <p class="text-red-600">Erro ao carregar professores</p>
            //             </div>
            //         `;
            //     });

            // Por enquanto, mostrar mensagem de que não há professores
            setTimeout(() => {
                container.innerHTML = `
                    <div class="p-8 text-center">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <p class="text-gray-600">Nenhum professor disponível</p>
                        <p class="text-sm text-gray-500 mt-2">Os professores serão carregados do banco de dados</p>
                    </div>
                `;
            }, 1000);
        }

        function renderizarProfessores(professores) {
            const container = document.getElementById('listaProfessoresDisponiveisEdicao');
            container.innerHTML = '';

            if (professores.length === 0) {
                container.innerHTML = `
                    <div class="p-8 text-center">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <p class="text-gray-600">Nenhum professor disponível</p>
                    </div>
                `;
                return;
            }

            professores.forEach(professor => {
                const professorCard = document.createElement('div');
                professorCard.className = 'p-4 border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200';
                professorCard.innerHTML = `
                    <div class="flex items-center space-x-4">
                        <input type="checkbox" class="checkbox-professor-edicao w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green" 
                               data-professor-id="${professor.id}" data-professor-nome="${professor.nome}" data-professor-disciplina="${professor.disciplina || ''}">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="font-medium text-gray-900">${professor.nome}</h5>
                                    <p class="text-sm text-gray-600">${professor.disciplina ? obterNomeDisciplina(professor.disciplina) : 'Sem disciplina definida'}</p>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <p>${professor.email || 'Email não informado'}</p>
                                    <p>${professor.telefone || 'Telefone não informado'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(professorCard);
            });

            // Add event listeners
            configurarEventListenersProfessoresEdicao();
        }

        function obterNomeDisciplina(disciplina) {
            // Retorna o nome da disciplina como está no banco de dados
            // ou capitaliza a primeira letra se não houver mapeamento específico
            if (!disciplina) return 'Sem disciplina definida';
            return disciplina.charAt(0).toUpperCase() + disciplina.slice(1);
        }

        function configurarEventListenersProfessoresEdicao() {
            // Search functionality
            document.getElementById('buscaProfessorEdicao').addEventListener('input', filtrarProfessoresEdicao);
            document.getElementById('filtroDisciplinaEdicao').addEventListener('change', filtrarProfessoresEdicao);
            
            // Select all functionality
            document.getElementById('selecionarTodosEdicao').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.checkbox-professor-edicao');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                atualizarResumoProfessoresSelecionadosEdicao();
            });

            // Individual checkbox functionality
            document.querySelectorAll('.checkbox-professor-edicao').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    atualizarResumoProfessoresSelecionadosEdicao();
                    atualizarCheckboxSelecionarTodosEdicao();
                });
            });
        }

        function filtrarProfessoresEdicao() {
            const termoBusca = document.getElementById('buscaProfessorEdicao').value.toLowerCase();
            const filtroDisciplina = document.getElementById('filtroDisciplinaEdicao').value;
            const cardsProfessores = document.querySelectorAll('#listaProfessoresDisponiveisEdicao > div');

            cardsProfessores.forEach(card => {
                const nomeProfessor = card.querySelector('h5').textContent.toLowerCase();
                const disciplinaProfessor = card.querySelector('.checkbox-professor-edicao').dataset.professorDisciplina;
                
                const correspondeBusca = nomeProfessor.includes(termoBusca);
                const correspondeDisciplina = !filtroDisciplina || disciplinaProfessor === filtroDisciplina;
                
                if (correspondeBusca && correspondeDisciplina) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function atualizarResumoProfessoresSelecionadosEdicao() {
            const checkboxesSelecionados = document.querySelectorAll('.checkbox-professor-edicao:checked');
            const resumoDiv = document.getElementById('resumoProfessoresSelecionadosEdicao');
            const listaDiv = document.getElementById('listaProfessoresSelecionadosEdicao');

            if (checkboxesSelecionados.length > 0) {
                resumoDiv.classList.remove('hidden');
                listaDiv.innerHTML = checkboxesSelecionados.map(checkbox => 
                    `<span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs mr-2 mb-1">${checkbox.dataset.professorNome}</span>`
                ).join('');
            } else {
                resumoDiv.classList.add('hidden');
            }
        }

        function atualizarCheckboxSelecionarTodosEdicao() {
            const todosCheckboxes = document.querySelectorAll('.checkbox-professor-edicao');
            const checkboxesMarcados = document.querySelectorAll('.checkbox-professor-edicao:checked');
            const checkboxSelecionarTodos = document.getElementById('selecionarTodosEdicao');
            
            checkboxSelecionarTodos.checked = todosCheckboxes.length === checkboxesMarcados.length;
        }

        function adicionarProfessoresSelecionadosEdicao() {
            const checkboxesSelecionados = document.querySelectorAll('.checkbox-professor-edicao:checked');
            
            if (checkboxesSelecionados.length === 0) {
                alert('Por favor, selecione pelo menos um professor.');
                return;
            }

            const professoresSelecionados = Array.from(checkboxesSelecionados).map(checkbox => ({
                id: checkbox.dataset.professorId,
                nome: checkbox.dataset.professorNome,
                disciplina: checkbox.dataset.professorDisciplina
            }));

            // Aqui você faria a requisição para o backend
            console.log('Professores selecionados:', professoresSelecionados);
            
            // Simular sucesso
            alert(`${professoresSelecionados.length} professor(es) adicionado(s) com sucesso!`);
            ocultarAdicionarProfessores();
            
            // Recarregar a lista de professores da escola
            // carregarProfessoresEscola();
        }

        // Função para processar professores selecionados quando salvar
        function processarProfessoresSelecionados() {
            const checkboxesSelecionados = document.querySelectorAll('.checkbox-professor-edicao:checked');
            
            if (checkboxesSelecionados.length > 0) {
                const professoresSelecionados = Array.from(checkboxesSelecionados).map(checkbox => ({
                    id: checkbox.dataset.professorId,
                    nome: checkbox.dataset.professorNome,
                    disciplina: checkbox.dataset.professorDisciplina
                }));

                console.log('Professores a serem adicionados:', professoresSelecionados);
                // Aqui você faria a requisição para o backend para adicionar os professores
                
                return professoresSelecionados;
            }
            
            return [];
        }

        // Funções de CEP
        function formatarCEP(input) {
            let valor = input.value.replace(/\D/g, '');
            valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
            input.value = valor;
        }

        async function buscarCEP(cep) {
            const cepInput = document.getElementById('edit_cep');
            const resultadoCEP = document.getElementById('resultadoCEP');
            
            if (!cep || cep.length < 8) {
                resultadoCEP.classList.add('hidden');
                return;
            }

            // Limpar CEP para busca
            const cepLimpo = cep.replace(/\D/g, '');
            
            if (cepLimpo.length !== 8) {
                resultadoCEP.innerHTML = '<span class="text-red-600">CEP deve ter 8 dígitos</span>';
                resultadoCEP.classList.remove('hidden');
                return;
            }

            try {
                resultadoCEP.innerHTML = '<span class="text-blue-600">Buscando...</span>';
                resultadoCEP.classList.remove('hidden');

                const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                const data = await response.json();

                if (data.erro) {
                    resultadoCEP.innerHTML = '<span class="text-red-600">CEP não encontrado</span>';
                } else {
                    // Preencher campos automaticamente
                    document.getElementById('edit_endereco').value = `${data.logradouro}, ${data.bairro}`;
                    document.getElementById('edit_municipio').value = data.localidade;
                    
                    resultadoCEP.innerHTML = `
                        <span class="text-green-600">
                            <strong>${data.logradouro}</strong><br>
                            ${data.bairro} - ${data.localidade}/${data.uf}
                        </span>
                    `;
                }
            } catch (error) {
                resultadoCEP.innerHTML = '<span class="text-red-600">Erro ao buscar CEP</span>';
                console.error('Erro na busca do CEP:', error);
            }
        }

        // Funções de CEP para o formulário de cadastro
        function formatarCEPCadastro(input) {
            let valor = input.value.replace(/\D/g, '');
            valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
            input.value = valor;
        }

        async function buscarCEPCadastro(cep) {
            const cepInput = document.getElementById('cep');
            const resultadoCEP = document.getElementById('resultadoCEPCadastro');
            
            if (!cep || cep.length < 8) {
                resultadoCEP.classList.add('hidden');
                return;
            }

            // Limpar CEP para busca
            const cepLimpo = cep.replace(/\D/g, '');
            
            if (cepLimpo.length !== 8) {
                resultadoCEP.innerHTML = '<span class="text-red-600">CEP deve ter 8 dígitos</span>';
                resultadoCEP.classList.remove('hidden');
                return;
            }

            try {
                resultadoCEP.innerHTML = '<span class="text-blue-600">Buscando...</span>';
                resultadoCEP.classList.remove('hidden');

                const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                const data = await response.json();

                if (data.erro) {
                    resultadoCEP.innerHTML = '<span class="text-red-600">CEP não encontrado</span>';
                } else {
                    // Preencher campos automaticamente
                    document.getElementById('endereco').value = data.logradouro || '';
                    document.getElementById('municipio').value = data.localidade || '';
                    
                    resultadoCEP.innerHTML = `
                        <span class="text-green-600">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Endereço preenchido automaticamente
                        </span>
                    `;
                }
            } catch (error) {
                resultadoCEP.innerHTML = '<span class="text-red-600">Erro ao buscar CEP</span>';
                console.error('Erro na busca do CEP:', error);
            }
        }

        // Event listener para o formulário de cadastro
        document.addEventListener('DOMContentLoaded', function() {
            const formCadastro = document.querySelector('form[method="POST"]');
            if (formCadastro) {
                formCadastro.addEventListener('submit', function(e) {
                    // Validar se um gestor foi selecionado
                    const gestorId = document.getElementById('gestor_id').value;
                    if (!gestorId) {
                        e.preventDefault();
                        alert('Por favor, selecione um gestor para a escola.');
                        document.getElementById('gestor_search').focus();
                        return false;
                    }
                });
            }
        });

        // Event listener para o formulário de edição
        document.getElementById('formEdicaoEscola').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Coletar dados do formulário
            const formData = new FormData();
            formData.append('acao', 'editar');
            formData.append('id', document.getElementById('edit_escola_id').value);
            formData.append('nome', document.getElementById('edit_nome').value);
            formData.append('endereco', document.getElementById('edit_endereco').value);
            formData.append('telefone', document.getElementById('edit_telefone').value);
            formData.append('email', document.getElementById('edit_email').value);
            formData.append('municipio', document.getElementById('edit_municipio').value);
            formData.append('cep', document.getElementById('edit_cep').value);
            formData.append('qtd_salas', document.getElementById('edit_qtd_salas').value);
            formData.append('obs', '');
            formData.append('codigo', document.getElementById('edit_codigo').value);
            formData.append('gestor_id', document.getElementById('edit_gestor_id').value || '');
            
            // Enviar dados para o servidor
            fetch('gestao_escolas.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Verificar se houve sucesso (a página será recarregada)
                alert('Escola atualizada com sucesso!');
                fecharModalEdicaoEscola();
                // Recarregar a página para mostrar as alterações
                window.location.reload();
            })
            .catch(error => {
                console.error('Erro ao salvar alterações:', error);
                alert('Erro ao salvar alterações. Tente novamente.');
            });
        });
        
        // Funções para busca de gestor na edição
        function buscarGestoresEdicao(termo) {
            if (termo.length < 2) {
                document.getElementById('edit_gestor_results').classList.add('hidden');
                return;
            }
            
            fetch(`../../Controllers/gestao/GestorController.php?busca=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    const results = document.getElementById('edit_gestor_results');
                    results.innerHTML = '';
                    
                    if (data.length === 0) {
                        results.innerHTML = '<div class="p-3 text-sm text-gray-500">Nenhum gestor encontrado</div>';
                    } else {
                        data.forEach(gestor => {
                            const div = document.createElement('div');
                            div.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0';
                            div.innerHTML = `
                                <div class="font-medium text-gray-900">${gestor.nome}</div>
                                <div class="text-sm text-gray-500">${gestor.email}</div>
                            `;
                            div.onclick = () => selecionarGestorEdicao(gestor);
                            results.appendChild(div);
                        });
                    }
                    
                    results.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erro ao buscar gestores:', error);
                });
        }
        
        function selecionarGestorEdicao(gestor) {
            document.getElementById('edit_gestor_id').value = gestor.id;
            document.getElementById('edit_gestor_nome_selecionado').textContent = gestor.nome;
            document.getElementById('edit_gestor_email_selecionado').textContent = gestor.email;
            document.getElementById('edit_gestor_search').value = '';
            document.getElementById('edit_gestor_results').classList.add('hidden');
            document.getElementById('edit_gestor_selected').classList.remove('hidden');
        }
        
        function removerGestorEdicao() {
            document.getElementById('edit_gestor_id').value = '';
            document.getElementById('edit_gestor_search').value = '';
            document.getElementById('edit_gestor_selected').classList.add('hidden');
        }
        
        // Máscara para CEP
        document.getElementById('cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.slice(0, 8);
            
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
            }
            
            e.target.value = value;
        });
        
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
            }
            
            e.target.value = value;
        });
        
        // Máscaras para campos de edição
        document.getElementById('edit_telefone').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
            }
            
            e.target.value = value;
        });
        
        document.getElementById('edit_cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.slice(0, 8);
            
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
            }
            
            e.target.value = value;
        });
        
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');

            sidebar.classList.toggle('open');
            overlay.classList.toggle('hidden');
        }

        // Close sidebar when clicking overlay
        document.getElementById('mobileOverlay').addEventListener('click', function() {
            toggleSidebar();
        });
        
        // Event listeners para busca de gestores
        document.getElementById('gestor_search').addEventListener('input', function(e) {
            buscarGestores(e.target.value);
        });
        
        // Event listeners para busca de gestores na edição
        document.getElementById('edit_gestor_search').addEventListener('input', function(e) {
            buscarGestoresEdicao(e.target.value);
        });
        
        // Fechar resultados ao clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#gestor_search') && !e.target.closest('#gestor_results')) {
                document.getElementById('gestor_results').classList.add('hidden');
            }
            if (!e.target.closest('#edit_gestor_search') && !e.target.closest('#edit_gestor_results')) {
                document.getElementById('edit_gestor_results').classList.add('hidden');
            }
        });
        
        // Fechar modal de edição clicando fora dele
        document.getElementById('modalEdicaoEscola').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalEdicaoEscola();
            }
        });
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar event listeners para o menu lateral
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Se estiver no mobile, fechar o menu lateral
                    if (window.innerWidth < 1024) {
                        toggleSidebar();
                    }
                });
            });
        });
    </script>
</body>
</html>
