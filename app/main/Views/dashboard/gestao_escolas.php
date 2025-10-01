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

if (isset($_POST['btngestor'])) {
    $escolaprofessor = $_POST['escola_professor'];
} else {
    # code...
}


// Funções para gerenciamento de escolas
function listarEscolas($busca = '')
{
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

function buscarGestores($busca = '')
{
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

function buscarGestoresNovo(): array
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT 
            g.id AS gestor_id,
            p.nome AS nome_gestor,
            p.telefone AS telefone_gestor,
            p.email AS email_gestor,
            p.cpf,
            g.cargo,
            g.ativo
        FROM 
            gestor g
        INNER JOIN 
            pessoa p ON g.pessoa_id = p.id
        WHERE 
            g.ativo = 1
        ORDER BY 
            p.nome";
    $stmt = $conn->prepare($sql);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



function cadastrarEscola($dados)
{
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

function excluirEscola($id)
{
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

function atualizarEscola($id, $dados)
{
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
    
    <!-- VLibras -->
    <div id="vlibras-widget" vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper>
            <div class="vw-plugin-top-wrapper"></div>
        </div>
    </div>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        // Inicializar VLibras apenas se estiver habilitado
        function initializeVLibras() {
            if (localStorage.getItem('vlibras-enabled') !== 'false') {
                if (window.VLibras) {
                    new window.VLibras.Widget('https://vlibras.gov.br/app');
                }
            }
        }
        
        // Aguardar o carregamento do script
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

        /* VLibras - Estilos para controle */
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
        
        /* Estilos para botões de salvar */
        button[type="submit"]:not(:disabled) {
            /* Animação removida */
        }
        
        button[type="submit"]:disabled {
            animation: none;
            box-shadow: none;
        }
        
        /* Estilos para Modal de Sucesso */
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

        /* Estilos específicos para o modal de perfil no tema escuro */
        [data-theme="dark"] #userProfileModal .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] #userProfileModal .text-gray-800 {
            color: #ffffff !important;
        }

        [data-theme="dark"] #userProfileModal .text-gray-700 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] #userProfileModal .text-gray-600 {
            color: #c0c0c0 !important;
        }

        [data-theme="dark"] #userProfileModal .text-gray-500 {
            color: #a0a0a0 !important;
        }

        [data-theme="dark"] #userProfileModal .bg-white {
            background-color: var(--bg-secondary) !important;
        }

        [data-theme="dark"] #userProfileModal .border-gray-200 {
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] #userProfileModal .bg-gray-50 {
            background-color: var(--bg-tertiary) !important;
        }

        /* Estilos específicos para o modal de logout no tema escuro */
        [data-theme="dark"] #logoutModal .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] #logoutModal .text-gray-600 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] #logoutModal .bg-white {
            background-color: var(--bg-secondary) !important;
        }

        /* Estilos específicos para o card do gestor no tema escuro */
        [data-theme="dark"] #gestor-atual-info {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%) !important;
            border-color: var(--border-color) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3) !important;
        }

        [data-theme="dark"] #gestor-atual-info:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4) !important;
        }

        [data-theme="dark"] #gestor-atual-info .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] #gestor-atual-info .text-gray-600 {
            color: #d1d5db !important;
        }

        [data-theme="dark"] #gestor-atual-info .text-gray-500 {
            color: #9ca3af !important;
        }

        [data-theme="dark"] #gestor-atual-info button {
            background-color: rgba(220, 38, 38, 0.1) !important;
            border-color: #dc2626 !important;
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #gestor-atual-info button:hover {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            border-color: #dc2626 !important;
        }

        /* ===== MELHORIAS DE RESPONSIVIDADE ===== */
        
        /* Mobile First - Breakpoints */
        @media (max-width: 640px) {
            /* Sidebar mobile */
            #sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 50;
            }
            
            #sidebar.mobile-open {
                transform: translateX(0);
            }
            
        /* Header mobile - FORÇA VISIBILIDADE */
            header {
            padding: 0.75rem 1rem !important;
            position: relative !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            background: white !important;
            border-bottom: 1px solid #e5e7eb !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
        }
        
        header .flex {
            min-height: 48px !important;
            align-items: center !important;
            display: flex !important;
            visibility: visible !important;
        }
        
        /* Botão menu MOBILE - FORÇA VISIBILIDADE */
        .mobile-menu-btn {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 999 !important;
            background: white !important;
            border: 1px solid #e5e7eb !important;
            position: relative !important;
            width: 40px !important;
            height: 40px !important;
        }
        
        /* Título centralizado */
        header h1 {
            font-size: 1.125rem !important;
            font-weight: 600 !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            }
            
            /* Cards responsivos */
            .card-hover {
                margin-bottom: 1rem;
            }
            
            /* Tabelas responsivas */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table-responsive table {
                min-width: 600px;
            }
            
            /* Modais mobile */
            .modal-content {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
                overflow-y: auto;
            }
            
            /* Formulários mobile */
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            /* Botões mobile */
            .btn-mobile {
                width: 100%;
                padding: 0.75rem;
                font-size: 1rem;
            }
        }
        
        /* CSS GLOBAL - FORÇA VISIBILIDADE DO HEADER MOBILE */
        @media (max-width: 1023px) {
            header {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 100 !important;
                background: white !important;
            }
            
            .mobile-menu-btn {
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        }

        /* Desktop - esconder botão menu */
        @media (min-width: 1024px) {
            .mobile-menu-btn {
                display: none !important;
            }
        }
        
        @media (min-width: 641px) and (max-width: 1024px) {
            /* Tablet */
            #sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
            
            .card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1025px) {
            /* Desktop */
            .card-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* ===== COMPONENTES RESPONSIVOS ===== */
        
        /* Grid responsivo para cards */
        .card-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: 1fr;
        }
        
        @media (min-width: 640px) {
            .card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .card-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Tabelas responsivas */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .table-responsive table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-responsive th,
        .table-responsive td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-responsive th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        /* Formulários responsivos */
        .form-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }
        
        @media (min-width: 640px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Botões responsivos */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        @media (min-width: 640px) {
            .btn-group {
                flex-direction: row;
            }
        }
        
        /* ===== MELHORIAS DE UX ===== */
        
        /* Loading states */
        .loading {
            position: relative;
            overflow: hidden;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Feedback visual */
        .success-feedback {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        .error-feedback {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        /* Estados de foco melhorados */
        .focus-visible {
            outline: 2px solid #2D5A27;
            outline-offset: 2px;
        }
        
        /* Microinterações */
        .micro-interaction {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .micro-interaction:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .micro-interaction:active {
            transform: translateY(0);
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
                    <a href="dashboard.php" onclick="showSection('dashboard')" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
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
                    <button onclick="toggleSidebar()" class="mobile-menu-btn p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-green" aria-label="Abrir menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Título centralizado -->
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Escolas</h1>
                    </div>
                    
                    <!-- Área direita -->
                    <div class="flex items-center space-x-4">
                        <!-- Escola atual (desktop) -->
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
                    <button onclick="showTab('tab-adicionar-gestor')" class="tab-btn py-4 px-1 focus:outline-none">
                        Adicionar Gestor
                    </button>
                    <button onclick="showTab('tab-lotacao')" class="tab-btn py-4 px-1 focus:outline-none">
                        Lotação do Corpo Docente
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

            <!-- Tab Adicionar Gestor -->
            <div id="tab-adicionar-gestor" class="tab-content hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Adicionar Gestor à Escola</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-blue-800">
                                <strong>Importante:</strong> Selecione uma escola primeiro e depois escolha o gestor desejado.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Passo 1: Seleção da Escola -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center space-x-2 mb-4">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">1</div>
                                <h3 class="text-lg font-medium text-gray-900">Selecionar Escola</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label for="escola_gestor" class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                                    <div class="relative">
                                        <input type="text" id="buscar_escola_gestor" placeholder="Digite o nome da escola..."
                                               class="block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green"
                                               autocomplete="off"
                                               oninput="buscarEscolasGestor(this.value)"
                                               onfocus="mostrarSugestoesGestor()"
                                               onblur="esconderSugestoesGestor()"
                                               onkeydown="navegarSugestoesGestor(event)">
                                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <!-- Lista de sugestões -->
                                        <div id="sugestoes_gestor" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                                            <!-- Sugestões serão inseridas aqui -->
                                        </div>
                                    </div>
                                    <input type="hidden" id="escola_gestor" name="escola_gestor" required>
                                </div>
                                
                                <!-- Informações da Escola Selecionada -->
                                <div id="info-escola-gestor" class="hidden">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Informações da Escola</h4>
                                    <div id="detalhes-escola-gestor" class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-600">
                                        <!-- Detalhes serão carregados aqui -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Passo 2: Seleção do Gestor -->
                        <div id="passo-selecionar-gestor" class="hidden bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center space-x-2 mb-4">
                                <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">2</div>
                                <h3 class="text-lg font-medium text-gray-900">Selecionar Gestor</h3>
                            </div>
                            
                            <form method="POST" id="form-adicionar-gestor">
                                <input type="hidden" name="acao" value="adicionar_gestor">
                                <input type="hidden" id="escola_id_gestor" name="escola_id">
                                
                                <div class="space-y-4">
                                    <!-- Busca de Gestores -->
                                    <div>
                                        <label for="buscar_gestor" class="block text-sm font-medium text-gray-700 mb-2">Buscar Gestor</label>
                                        <div class="relative">
                                            <input type="text" id="buscar_gestor" placeholder="Digite o nome do gestor..."
                                                   class="block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                                   oninput="buscarGestores(this.value)">
                                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                     <!-- Lista de Gestores Disponíveis -->
                                     <div>
                                         <h4 class="text-sm font-medium text-gray-700 mb-3">Gestores Disponíveis</h4>
                                         <div id="lista-gestores" class="space-y-3 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                                             <?php
                                             $gestores = buscarGestoresNovo();
                                             foreach ($gestores as $gestor):
                                             ?>
                                                 <div class="gestor-item group flex items-center space-x-3 p-4 bg-white border border-gray-200 rounded-lg hover:border-gray-300 hover:shadow-sm cursor-pointer transition-all duration-200"
                                                      onclick="selecionarGestor(<?php echo $gestor['gestor_id']; ?>, '<?php echo htmlspecialchars($gestor['nome_gestor']); ?>')">
                                                     <input type="radio" name="gestor_id" value="<?php echo $gestor['gestor_id']; ?>" 
                                                            id="gestor_<?php echo $gestor['gestor_id']; ?>" class="gestor-radio hidden">
                                                     
                                                     <!-- Avatar do Gestor -->
                                                     <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                                         <?php echo strtoupper(substr($gestor['nome_gestor'], 0, 2)); ?>
                                                     </div>
                                                     
                                                     <!-- Informações do Gestor -->
                                                     <div class="flex-1">
                                                         <div class="font-medium text-gray-900 group-hover:text-blue-600 transition-colors">
                                                             <?php echo htmlspecialchars($gestor['nome_gestor']); ?>
                                                         </div>
                                                         <div class="text-sm text-gray-500">ID: <?php echo $gestor['gestor_id']; ?></div>
                                                     </div>
                                                     
                                                     <!-- Ícone de seleção -->
                                                     <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center group-hover:border-blue-500 transition-colors">
                                                         <div class="w-2 h-2 bg-transparent rounded-full group-hover:bg-blue-500 transition-colors"></div>
                                                     </div>
                                                 </div>
                                             <?php endforeach; ?>
                                         </div>
                                     </div>
                                    
                                     <!-- Gestor Selecionado -->
                                     <div id="gestor-selecionado" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                                         <div class="flex items-center space-x-3">
                                             <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                                 <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                 </svg>
                                             </div>
                                             <div>
                                                 <div class="text-sm font-medium text-blue-800">Gestor selecionado:</div>
                                                 <div id="nome-gestor-selecionado" class="text-sm font-semibold text-blue-900"></div>
                                             </div>
                                         </div>
                                         <div class="mt-2 text-xs text-blue-600">
                                             💡 Dica: Clique novamente no gestor para deselecionar
                                         </div>
                                     </div>
                                </div>
                                
                                <!-- Tipo de Gestor -->
                                <div class="mt-6">
                                    <label for="tipo_gestor" class="block text-sm font-medium text-gray-700 mb-3">
                                        <span class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span>Tipo de Gestor</span>
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <select id="tipo_gestor" name="tipo_gestor" required
                                                class="block w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-all duration-200 appearance-none cursor-pointer hover:border-gray-400"
                                                onchange="validarSelecaoGestor()">
                                            <option value="">Selecione o tipo de gestor</option>
                                            <option value="diretor" class="py-2">
                                                <span class="flex items-center space-x-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                                    </svg>
                                                    Diretor
                                                </span>
                                            </option>
                                            <option value="vice_diretor">Vice-Diretor</option>
                                            <option value="coordenador">Coordenador Pedagógico</option>
                                            <option value="secretario">Secretário Escolar</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Selecione o cargo que o gestor exercerá na escola
                                    </div>
                                </div>

                                <!-- Botões de Ação -->
                                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                                    <button type="button" onclick="limparSelecaoGestor()" 
                                            class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 font-medium">
                                        <span class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <span>Limpar Seleção</span>
                                        </span>
                                    </button>
                                    <button type="submit" id="btn-adicionar-gestor" disabled
                                            class="px-6 py-2.5 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium shadow-sm hover:shadow-md">
                                        <span class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            <span>Adicionar Gestor</span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Lotação do Corpo Docente -->
            <div id="tab-lotacao" class="tab-content hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Lotação do Corpo Docente</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-blue-800">
                                <strong>Importante:</strong> Selecione uma escola para visualizar e gerenciar a lotação de professores e gestores.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Passo 1: Seleção da Escola -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center space-x-2 mb-4">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">1</div>
                                <h3 class="text-lg font-medium text-gray-900">Selecionar Escola</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label for="escola_lotacao" class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                                    <div class="relative">
                                        <input type="text" id="buscar_escola_lotacao" placeholder="Digite o nome da escola..."
                                               class="block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green"
                                               autocomplete="off"
                                               oninput="buscarEscolasLotacao(this.value)"
                                               onfocus="mostrarSugestoesLotacao()"
                                               onblur="esconderSugestoesLotacao()"
                                               onkeydown="navegarSugestoesLotacao(event)">
                                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <!-- Lista de sugestões -->
                                        <div id="sugestoes_lotacao" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                                            <!-- Sugestões serão inseridas aqui -->
                                        </div>
                                    </div>
                                    <input type="hidden" id="escola_lotacao" name="escola_lotacao" required>
                                </div>
                                
                                <!-- Informações da Escola Selecionada -->
                                <div id="info-escola-lotacao" class="hidden">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Informações da Escola</h4>
                                    <div id="detalhes-escola-lotacao" class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-600">
                                        <!-- Detalhes serão carregados aqui -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Passo 2: Gerenciamento da Lotação -->
                        <div id="secao-lotacao" class="hidden bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center space-x-2 mb-6">
                                <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">2</div>
                                <h3 class="text-lg font-medium text-gray-900">Gerenciar Lotação</h3>
                            </div>
                            
                            <!-- Título da Seção -->
                            <div class="border-b border-gray-200 mb-6">
                                <div class="flex items-center space-x-2 pb-4">
                                    <svg class="w-5 h-5 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900">Gerenciar Professores</h3>
                                </div>
                            </div>

                            <!-- Conteúdo da aba Professores -->
                            <div id="lotacao-professores" class="lotacao-tab-content">
                                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                                    <!-- Adicionar Professor -->
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                                        <div class="flex items-center space-x-2 mb-4">
                                            <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                            </div>
                                            <h4 class="text-lg font-medium text-gray-900">Adicionar Professor</h4>
                                        </div>
                                        
                                        <div class="space-y-4">
                                            <div>
                                                <label for="buscar_professor_lotacao" class="block text-sm font-medium text-gray-700 mb-2">Buscar Professor</label>
                                                <div class="relative">
                                                    <input type="text" id="buscar_professor_lotacao" placeholder="Digite o nome do professor..."
                                                           class="block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                                           oninput="buscarProfessoresLotacao(this.value)">
                                                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                    </svg>
                                                </div>
                                                <div id="resultados_professores_lotacao" class="mt-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg hidden">
                                                    <!-- Resultados da busca serão carregados aqui -->
                                                </div>
                                            </div>

                                            <div>
                                                <label for="disciplina_professor_lotacao" class="block text-sm font-medium text-gray-700 mb-2">Disciplina</label>
                                                <select id="disciplina_professor_lotacao" 
                                                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                    <option value="">Selecione uma disciplina...</option>
                                                    <option value="matematica">Matemática</option>
                                                    <option value="portugues">Português</option>
                                                    <option value="ciencias">Ciências</option>
                                                    <option value="historia">História</option>
                                                    <option value="geografia">Geografia</option>
                                                    <option value="educacao_fisica">Educação Física</option>
                                                    <option value="artes">Artes</option>
                                                    <option value="ingles">Inglês</option>
                                                    <option value="espanhol">Espanhol</option>
                                                </select>
                                            </div>

                                            <button type="button" onclick="lotarProfessor()" 
                                                    class="w-full bg-primary-green text-white px-4 py-2.5 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium flex items-center justify-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                <span>Lotar Professor</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Lista de Professores Lotados -->
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                                        <div class="flex items-center space-x-2 mb-4">
                                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <h4 class="text-lg font-medium text-gray-900">Professores Lotados</h4>
                                        </div>
                                        <div id="lista-professores-lotados" class="space-y-3 max-h-96 overflow-y-auto">
                                            <!-- Lista será carregada aqui -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
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
                        
                        <!-- Botões de Ação - Dados Básicos -->
                        <div id="botoes-dados-basicos" class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                            <button type="button" onclick="fecharModalEdicaoEscola()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                                Cancelar
                            </button>
                            <button type="submit" id="btn-salvar-dados-basicos" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green transition-colors duration-200">
                                Salvar Alterações
                            </button>
                        </div>
                    </div>
                    
                    <!-- Aba Gestor -->
                    <div id="aba-gestor" class="aba-edicao hidden flex-1 flex flex-col">
                        <div class="space-y-6">
                            <!-- Gestor Atual -->
                            <div id="gestor-atual-section">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Gestor Atual</h4>
                                <div id="gestor-atual-info" class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-14 h-14 bg-gradient-to-br from-primary-green to-green-600 rounded-full flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-lg" id="gestor-atual-iniciais">JD</span>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-semibold text-gray-900 text-lg" id="gestor-atual-nome">João da Silva</h5>
                                                <p class="text-sm text-gray-600 mb-1" id="gestor-atual-email">joao.silva@escola.edu.br</p>
                                                <p class="text-xs text-gray-500" id="gestor-atual-cpf">CPF: 123.456.789-00</p>
                                            </div>
                                        </div>
                                        <button type="button" onclick="removerGestorAtual()" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 hover:bg-red-100 hover:border-red-300 rounded-lg transition-all duration-200 flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <span>Remover</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Mensagem quando não há gestor -->
                            <div id="nenhum-gestor-section" class="hidden">
                                <div class="text-center py-8">
                                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Nenhum gestor definido</h4>
                                    <p class="text-gray-600 mb-4">Esta escola ainda não possui um gestor (diretor) definido.</p>
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <p class="text-sm text-blue-800">
                                                <strong>Nota:</strong> Para adicionar um gestor, use a aba "Adicionar Professor" na página principal.
                                            </p>
                                        </div>
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
                        
                        <!-- Botões de Ação - Corpo Docente -->
                        <div id="botoes-corpo-docente" class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6 hidden">
                            <button type="button" onclick="fecharModalEdicaoEscola()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                                Cancelar
                            </button>
                            <button type="submit" id="btn-salvar-corpo-docente" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green transition-colors duration-200">
                                Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso -->
    <div id="modalSucesso" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl modal-sucesso-show">
            <!-- Ícone de Sucesso -->
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <div class="checkmark-circle w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path class="checkmark-check" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Mensagem -->
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Sucesso!</h3>
                <p class="text-gray-600 text-lg mb-6">
                    Escola atualizada com sucesso!
                </p>
                
                <button onclick="fecharModalSucesso()" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                    Entendi
                </button>
            </div>
        </div>
    </div>
    
    <script>
        
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
            
            // Resetar estado dos botões
            desabilitarBotoesSalvar();
            
            // Resetar seleção de professores
            ocultarAdicionarProfessores();
            
            // Voltar para a primeira aba
            mostrarAbaEdicao('dados-basicos');
        }
        
        // Funções para Modal de Sucesso
        function mostrarModalSucesso() {
            const modal = document.getElementById('modalSucesso');
            modal.classList.remove('hidden');
            
            // Fechar automaticamente após 3 segundos
            setTimeout(() => {
                fecharModalSucesso();
            }, 3000);
        }
        
        function fecharModalSucesso() {
            const modal = document.getElementById('modalSucesso');
            modal.classList.add('hidden');
            // Recarregar a página após fechar o modal
            window.location.reload();
        }
        
        // Variável global para armazenar dados originais
        let dadosOriginaisEscola = {};

        function carregarDadosEscola(id) {
            const endpoint = `../../Controllers/gestao/EscolaController.php?id=${encodeURIComponent(id)}`;
            fetch(endpoint)
                .then(response => response.json())
                .then(data => {
                    if (!data || !data.status || !data.escola) {
                        console.error('Não foi possível carregar a escola.');
                        return;
                    }
                    const escola = data.escola;
                    // Preencher campos básicos
                    document.getElementById('edit_nome').value = escola.nome || '';
                    document.getElementById('edit_endereco').value = escola.endereco || '';
                    document.getElementById('edit_telefone').value = escola.telefone || '';
                    document.getElementById('edit_email').value = escola.email || '';
                    document.getElementById('edit_municipio').value = escola.municipio || '';
                    document.getElementById('edit_cep').value = escola.cep || '';
                    document.getElementById('edit_qtd_salas').value = escola.qtd_salas || '';
                    document.getElementById('edit_codigo').value = escola.codigo || '';
                    
                    // Armazenar dados originais para comparação
                    dadosOriginaisEscola = {
                        nome: escola.nome || '',
                        endereco: escola.endereco || '',
                        telefone: escola.telefone || '',
                        email: escola.email || '',
                        municipio: escola.municipio || '',
                        cep: escola.cep || '',
                        qtd_salas: escola.qtd_salas || '',
                        codigo: escola.codigo || ''
                    };
                    
                    // Configurar monitoramento de mudanças
                    configurarMonitoramentoMudancas();
                    
                    // Desabilitar botões inicialmente
                    desabilitarBotoesSalvar();
                })
                .catch(err => {
                    console.error('Erro ao carregar dados da escola:', err);
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
        
        // Função para configurar monitoramento de mudanças nos campos
        function configurarMonitoramentoMudancas() {
            const campos = [
                'edit_nome', 'edit_endereco', 'edit_telefone', 'edit_email',
                'edit_municipio', 'edit_cep', 'edit_qtd_salas', 'edit_codigo'
            ];
            
            campos.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo) {
                    campo.removeEventListener('input', verificarMudancas); // Remove listeners anteriores
                    campo.addEventListener('input', verificarMudancas);
                }
            });
        }
        
        // Função para verificar se houve mudanças
        function verificarMudancas() {
            const camposAtuais = {
                nome: document.getElementById('edit_nome').value || '',
                endereco: document.getElementById('edit_endereco').value || '',
                telefone: document.getElementById('edit_telefone').value || '',
                email: document.getElementById('edit_email').value || '',
                municipio: document.getElementById('edit_municipio').value || '',
                cep: document.getElementById('edit_cep').value || '',
                qtd_salas: document.getElementById('edit_qtd_salas').value || '',
                codigo: document.getElementById('edit_codigo').value || ''
            };
            
            // Comparar com dados originais
            let houveAlteracao = false;
            for (let campo in dadosOriginaisEscola) {
                if (camposAtuais[campo] !== dadosOriginaisEscola[campo]) {
                    houveAlteracao = true;
                    break;
                }
            }
            
            // Obter botão de salvar da aba Dados Básicos
            const botaoSalvarDadosBasicos = document.getElementById('btn-salvar-dados-basicos');
            
            if (houveAlteracao) {
                habilitarBotaoSalvar(botaoSalvarDadosBasicos);
            } else {
                desabilitarBotaoSalvar(botaoSalvarDadosBasicos);
            }
        }
        
        // Função para habilitar botão de salvar
        function habilitarBotaoSalvar(botao) {
            if (botao) {
                botao.disabled = false;
                botao.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-primary-green');
                botao.classList.add('bg-green-600', 'hover:bg-green-700', 'shadow-lg', 'transform', 'hover:scale-105', 'transition-all');
                botao.style.cursor = 'pointer';
            }
        }
        
        // Função para desabilitar botão de salvar
        function desabilitarBotaoSalvar(botao) {
            if (botao) {
                botao.disabled = true;
                botao.classList.remove('bg-green-600', 'hover:bg-green-700', 'shadow-lg', 'transform', 'hover:scale-105');
                botao.classList.add('opacity-50', 'cursor-not-allowed', 'bg-primary-green');
                botao.style.cursor = 'not-allowed';
            }
        }
        
        // Função para desabilitar todos os botões de salvar
        function desabilitarBotoesSalvar() {
            const botaoDadosBasicos = document.getElementById('btn-salvar-dados-basicos');
            desabilitarBotaoSalvar(botaoDadosBasicos);
            
            // Ocultar botões da aba Corpo Docente inicialmente
            const botoesCorpoDocente = document.getElementById('botoes-corpo-docente');
            if (botoesCorpoDocente) {
                botoesCorpoDocente.classList.add('hidden');
            }
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
            
            // Ocultar botões de ação
            const botoesCorpoDocente = document.getElementById('botoes-corpo-docente');
            if (botoesCorpoDocente) {
                botoesCorpoDocente.classList.add('hidden');
            }
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

            // Buscar professores no backend
            fetch('../../Controllers/gestao/ProfessorController.php')
                .then(resp => resp.json())
                .then(data => {
                    if (data && data.status && Array.isArray(data.professores)) {
                        renderizarProfessores(data.professores);
                    } else {
                        container.innerHTML = `
                            <div class="p-8 text-center">
                                <p class="text-gray-600">Nenhum professor disponível</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar professores:', error);
                    container.innerHTML = `
                        <div class="p-8 text-center">
                            <p class="text-red-600">Erro ao carregar professores</p>
                        </div>
                    `;
                });
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
            
            // Obter container de botões da aba Corpo Docente
            const botoesCorpoDocente = document.getElementById('botoes-corpo-docente');
            const botaoSalvar = document.getElementById('btn-salvar-corpo-docente');

            if (checkboxesSelecionados.length > 0) {
                resumoDiv.classList.remove('hidden');
                // Converter NodeList para Array antes de usar map
                listaDiv.innerHTML = Array.from(checkboxesSelecionados).map(checkbox => 
                    `<span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs mr-2 mb-1">${checkbox.dataset.professorNome}</span>`
                ).join('');
                
                // Mostrar botões de salvar quando há professores selecionados
                if (botoesCorpoDocente) {
                    botoesCorpoDocente.classList.remove('hidden');
                    habilitarBotaoSalvar(botaoSalvar);
                }
            } else {
                resumoDiv.classList.add('hidden');
                
                // Ocultar botões quando não há professores selecionados
                if (botoesCorpoDocente) {
                    botoesCorpoDocente.classList.add('hidden');
                }
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
                    const gestorIdField = document.getElementById('gestor_id');
                    if (gestorIdField) {
                        const gestorId = gestorIdField.value;
                        if (!gestorId) {
                            e.preventDefault();
                            alert('Por favor, selecione um gestor para a escola.');
                            const gestorSearchField = document.getElementById('gestor_search');
                            if (gestorSearchField) gestorSearchField.focus();
                            return false;
                        }
                    }
                });
            }
        });

        // Event listener para o formulário de edição
        document.addEventListener('DOMContentLoaded', function() {
            const formEdicao = document.getElementById('formEdicaoEscola');
            if (formEdicao) {
                formEdicao.addEventListener('submit', function(e) {
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
                    
                    const gestorIdField = document.getElementById('edit_gestor_id');
                    formData.append('gestor_id', gestorIdField ? gestorIdField.value || '' : '');
                    
                    // Enviar dados para o servidor
                    fetch('gestao_escolas.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Fechar modal de edição
                        fecharModalEdicaoEscola();
                        
                        // Mostrar modal de sucesso
                        mostrarModalSucesso();
                    })
                    .catch(error => {
                        console.error('Erro ao salvar alterações:', error);
                        alert('Erro ao salvar alterações. Tente novamente.');
                    });
                });
            }
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
        
        // Máscaras para campos - com verificação de existência
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara para CEP (cadastro)
            const cepField = document.getElementById('cep');
            if (cepField) {
                cepField.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 8) value = value.slice(0, 8);
                    
                    if (value.length > 5) {
                        value = value.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
                    }
                    
                    e.target.value = value;
                });
            }
            
            // Máscara para telefone (cadastro)
            const telefoneField = document.getElementById('telefone');
            if (telefoneField) {
                telefoneField.addEventListener('input', function (e) {
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
            }
            
            // Máscaras para campos de edição
            const editTelefoneField = document.getElementById('edit_telefone');
            if (editTelefoneField) {
                editTelefoneField.addEventListener('input', function (e) {
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
            }
            
            const editCepField = document.getElementById('edit_cep');
            if (editCepField) {
                editCepField.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 8) value = value.slice(0, 8);
                    
                    if (value.length > 5) {
                        value = value.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
                    }
                    
                    e.target.value = value;
                });
            }
        });
        
        // FORÇA VISIBILIDADE DO HEADER MOBILE
        function forceMobileHeaderVisibility() {
            const header = document.querySelector('header');
            const mobileBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth < 1024) {
                // Mobile - forçar visibilidade
                if (header) {
                    header.style.display = 'block';
                    header.style.visibility = 'visible';
                    header.style.opacity = '1';
                    header.style.position = 'sticky';
                    header.style.top = '0';
                    header.style.zIndex = '100';
                }
                if (mobileBtn) {
                    mobileBtn.style.display = 'flex';
                    mobileBtn.style.visibility = 'visible';
                    mobileBtn.style.opacity = '1';
                }
            } else {
                // Desktop - esconder botão mobile
                if (mobileBtn) {
                    mobileBtn.style.display = 'none';
                }
            }
        }

        // Executar na carga da página
        document.addEventListener('DOMContentLoaded', forceMobileHeaderVisibility);
        window.addEventListener('resize', forceMobileHeaderVisibility);
        
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');

            sidebar.classList.toggle('open');
            overlay.classList.toggle('hidden');
        }

        // Configurar event listeners após DOM carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Close sidebar when clicking overlay
            const mobileOverlay = document.getElementById('mobileOverlay');
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', function() {
                    toggleSidebar();
                });
            }
            
            // Event listeners para busca de gestores
            const gestorSearch = document.getElementById('gestor_search');
            if (gestorSearch) {
                gestorSearch.addEventListener('input', function(e) {
                    buscarGestores(e.target.value);
                });
            }
            
            // Event listeners para busca de gestores na edição
            const editGestorSearch = document.getElementById('edit_gestor_search');
            if (editGestorSearch) {
                editGestorSearch.addEventListener('input', function(e) {
                    buscarGestoresEdicao(e.target.value);
                });
            }
            
            // Fechar modal de edição clicando fora dele
            const modalEdicao = document.getElementById('modalEdicaoEscola');
            if (modalEdicao) {
                modalEdicao.addEventListener('click', function(e) {
                    if (e.target === this) {
                        fecharModalEdicaoEscola();
                    }
                });
            }
        });
        
        // Fechar resultados ao clicar fora
        document.addEventListener('click', function(e) {
            const gestorResults = document.getElementById('gestor_results');
            if (gestorResults && !e.target.closest('#gestor_search') && !e.target.closest('#gestor_results')) {
                gestorResults.classList.add('hidden');
            }
            
            const editGestorResults = document.getElementById('edit_gestor_results');
            if (editGestorResults && !e.target.closest('#edit_gestor_search') && !e.target.closest('#edit_gestor_results')) {
                editGestorResults.classList.add('hidden');
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

        // User Profile Modal Functions
        function openUserProfile() {
            // Load user data into profile modal
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            if (user.nome) {
                // Update profile information
                document.getElementById('profileName').textContent = user.nome;
                document.getElementById('profileFullName').textContent = user.nome;
                
                // Generate initials
                const initials = user.nome.split(' ').map(n => n[0]).join('').toUpperCase();
                document.getElementById('profileInitials').textContent = initials;
                
                // Update role in profile
                const roleNames = {
                    'ADM': 'Administrador',
                    'GESTAO': 'Gestão',
                    'PROFESSOR': 'Professor',
                    'ALUNO': 'Aluno',
                    'NUTRICIONISTA': 'Nutricionista',
                    'ADM_MERENDA': 'Administrador de Merenda'
                };
                
                const profileRole = document.getElementById('profileRole');
                if (profileRole) {
                    profileRole.textContent = roleNames[user.tipo] || 'Professor';
                }
            }
            document.getElementById('userProfileModal').classList.remove('hidden');
        }

        function closeUserProfile() {
            const modal = document.getElementById('userProfileModal');
            if (modal) modal.classList.add('hidden');
        }

        // ===== FUNÇÕES DE LOGOUT =====
        
        function confirmLogout() {
            const modal = document.getElementById('logoutModal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            if (modal) modal.classList.add('hidden');
        }

        function logout() {
            // Redirecionar para logout
            window.location.href = '../../Models/sessao/sessions.php?sair';
        }

        // Configurar modais após DOM carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Close user profile modal when clicking outside
            const userProfileModal = document.getElementById('userProfileModal');
            if (userProfileModal) {
                userProfileModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeUserProfile();
                    }
                });
            }

            // Close logout modal when clicking outside
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeLogoutModal();
                    }
                });
            }
            
            // Close modal de sucesso when clicking outside
            const modalSucesso = document.getElementById('modalSucesso');
            if (modalSucesso) {
                modalSucesso.addEventListener('click', function(e) {
                    if (e.target === this) {
                        fecharModalSucesso();
                    }
                });
            }
        });

        // Accessibility Functions
        function setContrast(contrast) {
            document.documentElement.setAttribute('data-contrast', contrast);

            // Update button states
            document.querySelectorAll('[id^="contrast-"]').forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
                btn.classList.add('border-gray-300', 'text-gray-700');
            });

            const activeBtn = document.getElementById(`contrast-${contrast}`);
            if (activeBtn) {
                activeBtn.classList.remove('border-gray-300', 'text-gray-700');
                activeBtn.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
            }

            // Save to localStorage
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.contrast = contrast;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }

        function setFontSize(size) {
            document.documentElement.setAttribute('data-font-size', size);

            // Update button states
            document.querySelectorAll('[id^="font-"]').forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
                btn.classList.add('border-gray-300', 'text-gray-700');
            });

            const activeBtn = document.getElementById(`font-${size}`);
            if (activeBtn) {
                activeBtn.classList.remove('border-gray-300', 'text-gray-700');
                activeBtn.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
            }

            // Save to localStorage
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.fontSize = size;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }

        function setReduceMotion(enabled) {
            if (enabled) {
                document.documentElement.setAttribute('data-reduce-motion', 'true');
                // Apply reduced motion styles
                const style = document.createElement('style');
                style.id = 'reduce-motion-styles';
                style.textContent = `
                    *, *::before, *::after {
                        animation-duration: 0.01ms !important;
                        animation-iteration-count: 1 !important;
                        transition-duration: 0.01ms !important;
                        scroll-behavior: auto !important;
                    }
                `;
                document.head.appendChild(style);
            } else {
                document.documentElement.removeAttribute('data-reduce-motion');
                const style = document.getElementById('reduce-motion-styles');
                if (style) {
                    style.remove();
                }
            }

            // Save to localStorage
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.reduceMotion = enabled;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }

        function toggleVLibras() {
            const vlibrasWidget = document.getElementById('vlibras-widget');
            const toggle = document.getElementById('vlibras-toggle');
            
            if (toggle.checked) {
                // Ativar VLibras
                vlibrasWidget.style.display = 'block';
                vlibrasWidget.classList.remove('disabled');
                vlibrasWidget.classList.add('enabled');
                localStorage.setItem('vlibras-enabled', 'true');
                
                // Reinicializar o widget se necessário
                if (window.VLibras && !window.vlibrasInstance) {
                    window.vlibrasInstance = new window.VLibras.Widget('https://vlibras.gov.br/app');
                }
            } else {
                // Desativar VLibras
                vlibrasWidget.style.display = 'none';
                vlibrasWidget.classList.remove('enabled');
                vlibrasWidget.classList.add('disabled');
                localStorage.setItem('vlibras-enabled', 'false');
                
                // Limpar instância se existir
                if (window.vlibrasInstance) {
                    window.vlibrasInstance = null;
                }
            }
        }

        function setKeyboardNavigation(enabled) {
            if (enabled) {
                document.documentElement.setAttribute('data-keyboard-nav', 'true');
                // Apply keyboard navigation styles
                const style = document.createElement('style');
                style.id = 'keyboard-nav-styles';
                style.textContent = `
                    .keyboard-nav button:focus,
                    .keyboard-nav a:focus,
                    .keyboard-nav input:focus,
                    .keyboard-nav select:focus,
                    .keyboard-nav textarea:focus {
                        outline: 3px solid #3b82f6 !important;
                        outline-offset: 2px !important;
                    }
                `;
                document.head.appendChild(style);
            } else {
                document.documentElement.removeAttribute('data-keyboard-nav');
                const style = document.getElementById('keyboard-nav-styles');
                if (style) {
                    style.remove();
                }
            }

            // Save to localStorage
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.keyboardNav = enabled;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }

        // Load accessibility settings on page load
        function loadAccessibilitySettings() {
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            
            // Load contrast setting
            if (settings.contrast) {
                setContrast(settings.contrast);
            }
            
            // Load font size setting
            if (settings.fontSize) {
                setFontSize(settings.fontSize);
            }
            
            // Load reduce motion setting
            if (settings.reduceMotion) {
                document.getElementById('reduce-motion').checked = true;
                setReduceMotion(true);
            }
            
            // Load keyboard navigation setting
            if (settings.keyboardNav) {
                document.getElementById('keyboard-nav').checked = true;
                setKeyboardNavigation(true);
            }
            
            // Load VLibras setting
            const vlibrasEnabled = localStorage.getItem('vlibras-enabled');
            const vlibrasToggle = document.getElementById('vlibras-toggle');
            const vlibrasWidget = document.getElementById('vlibras-widget');
            
            if (vlibrasToggle) {
                if (vlibrasEnabled === 'false') {
                    vlibrasToggle.checked = false;
                    vlibrasWidget.style.display = 'none';
                    vlibrasWidget.classList.remove('enabled');
                    vlibrasWidget.classList.add('disabled');
                } else {
                    vlibrasToggle.checked = true;
                    vlibrasWidget.style.display = 'block';
                    vlibrasWidget.classList.remove('disabled');
                    vlibrasWidget.classList.add('enabled');
                }
            }
        }

        // Initialize accessibility settings when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadAccessibilitySettings();
        });

        // Variáveis globais para lotação
        let professorSelecionadoLotacao = null;
        let gestorSelecionadoLotacao = null;
        let escolaAtualLotacao = null;

        // Função para alternar entre as abas de lotação (professores/gestores)
        function showLotacaoTab(tipo) {
            // Remover classe ativa de todos os botões
            document.querySelectorAll('.lotacao-tab-btn').forEach(btn => {
                btn.classList.remove('active', 'border-primary-green', 'text-primary-green');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Esconder todos os conteúdos
            document.querySelectorAll('.lotacao-tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Mostrar o conteúdo selecionado
            document.getElementById(`lotacao-${tipo}`).classList.remove('hidden');
            
            // Ativar o botão selecionado
            const btnAtivo = document.getElementById(`tab-${tipo}-btn`);
            btnAtivo.classList.add('active', 'border-primary-green', 'text-primary-green');
            btnAtivo.classList.remove('border-transparent', 'text-gray-500');
        }

        // Função para carregar informações da escola e lotação
        function carregarLotacaoEscola(escolaId) {
            if (!escolaId) {
                document.getElementById('info-escola-lotacao').classList.add('hidden');
                document.getElementById('secao-lotacao').classList.add('hidden');
                return;
            }

            escolaAtualLotacao = escolaId;

            // Obter o nome da escola selecionada do campo de busca
            const campoBusca = document.getElementById('buscar_escola_lotacao');
            const nomeEscola = campoBusca.value;
            
            // Carregar informações da escola
            const detalhesEscola = document.getElementById('detalhes-escola-lotacao');
            detalhesEscola.innerHTML = `
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-sm"><strong>Nome:</strong> ${nomeEscola}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        <span class="text-sm"><strong>Município:</strong> Maranguape - CE</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                        <span class="text-sm"><strong>ID:</strong> ${escolaId}</span>
                    </div>
                </div>
            `;
            
            // Mostrar informações da escola
            document.getElementById('info-escola-lotacao').classList.remove('hidden');
            
            // Mostrar seção de lotação
            document.getElementById('secao-lotacao').classList.remove('hidden');
            
            // Carregar dados da escola via AJAX (opcional)
            fetch(`../../Controllers/gestao/EscolaController.php?id=${escolaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const escola = data.escola;
                        // Atualizar com dados reais se disponíveis
                        detalhesEscola.innerHTML = `
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-sm"><strong>Nome:</strong> ${escola.nome}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-sm"><strong>Município:</strong> Maranguape - CE</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span class="text-sm"><strong>Código INEP:</strong> ${escola.codigo_inep || 'Não informado'}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                    <span class="text-sm"><strong>ID:</strong> ${escolaId}</span>
                                </div>
                            </div>
                        `;
                        
                        // Carregar listas de lotação
                        carregarProfessoresLotados();
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar escola:', error);
                    alert('Erro ao carregar informações da escola');
                });
        }

        // Função para buscar professores disponíveis para lotação
        function buscarProfessoresLotacao(termo) {
            if (termo.length < 2) {
                document.getElementById('resultados_professores_lotacao').classList.add('hidden');
                return;
            }

            fetch(`../../Controllers/gestao/ProfessorLotacaoController.php?acao=buscar_disponiveis&termo=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    const resultados = document.getElementById('resultados_professores_lotacao');
                    
                    if (data.success && data.professores.length > 0) {
                        let html = '';
                        data.professores.forEach(professor => {
                            html += `
                                <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100" 
                                     onclick="selecionarProfessorLotacao(${professor.id}, '${professor.nome}')">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900">${professor.nome}</p>
                                            <p class="text-sm text-gray-600">${professor.email}</p>
                                        </div>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Professor</span>
                                    </div>
                                </div>
                            `;
                        });
                        resultados.innerHTML = html;
                        resultados.classList.remove('hidden');
                    } else {
                        resultados.innerHTML = '<div class="p-3 text-gray-500 text-center">Nenhum professor encontrado</div>';
                        resultados.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar professores:', error);
                });
        }

        // Função para selecionar professor para lotação
        function selecionarProfessorLotacao(id, nome) {
            professorSelecionadoLotacao = { id, nome };
            document.getElementById('buscar_professor_lotacao').value = nome;
            document.getElementById('resultados_professores_lotacao').classList.add('hidden');
        }

        // Função para buscar gestores disponíveis para lotação
        function buscarGestoresLotacao(termo) {
            if (termo.length < 2) {
                document.getElementById('resultados_gestores_lotacao').classList.add('hidden');
                return;
            }

            fetch(`../../Controllers/gestao/GestorLotacaoController.php?acao=buscar_disponiveis&termo=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    const resultados = document.getElementById('resultados_gestores_lotacao');
                    
                    if (data.success && data.gestores.length > 0) {
                        let html = '';
                        data.gestores.forEach(gestor => {
                            html += `
                                <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100" 
                                     onclick="selecionarGestorLotacao(${gestor.id}, '${gestor.nome}')">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900">${gestor.nome}</p>
                                            <p class="text-sm text-gray-600">${gestor.email}</p>
                                        </div>
                                        <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">Gestor</span>
                                    </div>
                                </div>
                            `;
                        });
                        resultados.innerHTML = html;
                        resultados.classList.remove('hidden');
                    } else {
                        resultados.innerHTML = '<div class="p-3 text-gray-500 text-center">Nenhum gestor encontrado</div>';
                        resultados.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar gestores:', error);
                });
        }

        // Função para selecionar gestor para lotação
        function selecionarGestorLotacao(id, nome) {
            gestorSelecionadoLotacao = { id, nome };
            document.getElementById('buscar_gestor_lotacao').value = nome;
            document.getElementById('resultados_gestores_lotacao').classList.add('hidden');
        }

        // Função para lotar professor
        function lotarProfessor() {
            if (!professorSelecionadoLotacao) {
                alert('Selecione um professor primeiro');
                return;
            }

            if (!escolaAtualLotacao) {
                alert('Selecione uma escola primeiro');
                return;
            }

            const dataInicio = document.getElementById('data_inicio_professor').value;
            if (!dataInicio) {
                alert('Informe a data de início');
                return;
            }

            const formData = new FormData();
            formData.append('acao', 'lotar');
            formData.append('professor_id', professorSelecionadoLotacao.id);
            formData.append('escola_id', escolaAtualLotacao);
            formData.append('data_inicio', dataInicio);

            fetch('../../Controllers/gestao/ProfessorLotacaoController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Professor lotado com sucesso!');
                    // Limpar campos
                    document.getElementById('buscar_professor_lotacao').value = '';
                    document.getElementById('data_inicio_professor').value = '';
                    professorSelecionadoLotacao = null;
                    // Recarregar lista
                    carregarProfessoresLotados();
                } else {
                    alert('Erro ao lotar professor: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao lotar professor:', error);
                alert('Erro ao lotar professor');
            });
        }

        // Função para lotar gestor
        function lotarGestor() {
            if (!gestorSelecionadoLotacao) {
                alert('Selecione um gestor primeiro');
                return;
            }

            if (!escolaAtualLotacao) {
                alert('Selecione uma escola primeiro');
                return;
            }

            const cargo = document.getElementById('cargo_gestor').value;
            const dataInicio = document.getElementById('data_inicio_gestor').value;
            
            if (!cargo) {
                alert('Selecione o cargo');
                return;
            }

            if (!dataInicio) {
                alert('Informe a data de início');
                return;
            }

            const formData = new FormData();
            formData.append('acao', 'lotar');
            formData.append('gestor_id', gestorSelecionadoLotacao.id);
            formData.append('escola_id', escolaAtualLotacao);
            formData.append('cargo', cargo);
            formData.append('data_inicio', dataInicio);

            fetch('../../Controllers/gestao/GestorLotacaoController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Gestor lotado com sucesso!');
                    // Limpar campos
                    document.getElementById('buscar_gestor_lotacao').value = '';
                    document.getElementById('cargo_gestor').value = '';
                    document.getElementById('data_inicio_gestor').value = '';
                    gestorSelecionadoLotacao = null;
                    // Recarregar lista
                    carregarGestoresLotados();
                } else {
                    alert('Erro ao lotar gestor: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao lotar gestor:', error);
                alert('Erro ao lotar gestor');
            });
        }

        // Função para carregar professores lotados
        function carregarProfessoresLotados() {
            if (!escolaAtualLotacao) return;

            fetch(`../../Controllers/gestao/ProfessorLotacaoController.php?acao=listar_lotados&escola_id=${escolaAtualLotacao}`)
                .then(response => response.json())
                .then(data => {
                    const lista = document.getElementById('lista-professores-lotados');
                    
                    if (data.success && data.professores.length > 0) {
                        let html = '';
                        data.professores.forEach(professor => {
                            html += `
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900">${professor.nome}</p>
                                            <p class="text-sm text-gray-600">Início: ${professor.data_inicio}</p>
                                            ${professor.data_fim ? `<p class="text-sm text-red-600">Fim: ${professor.data_fim}</p>` : ''}
                                        </div>
                                        <div class="flex space-x-2">
                                            ${professor.ativo == 1 ? `
                                                <button onclick="removerLotacaoProfessor(${professor.id})" 
                                                        class="text-red-600 hover:text-red-800 text-sm">
                                                    Remover
                                                </button>
                                            ` : `
                                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Inativo</span>
                                            `}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        lista.innerHTML = html;
                    } else {
                        lista.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhum professor lotado</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar professores lotados:', error);
                });
        }

        // Função para carregar gestores lotados
        function carregarGestoresLotados() {
            if (!escolaAtualLotacao) return;

            fetch(`../../Controllers/gestao/GestorLotacaoController.php?acao=listar_lotados&escola_id=${escolaAtualLotacao}`)
                .then(response => response.json())
                .then(data => {
                    const lista = document.getElementById('lista-gestores-lotados');
                    
                    if (data.success && data.gestores.length > 0) {
                        let html = '';
                        data.gestores.forEach(gestor => {
                            html += `
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900">${gestor.nome}</p>
                                            <p class="text-sm text-gray-600">Cargo: ${gestor.cargo}</p>
                                            <p class="text-sm text-gray-600">Início: ${gestor.data_inicio}</p>
                                            ${gestor.data_fim ? `<p class="text-sm text-red-600">Fim: ${gestor.data_fim}</p>` : ''}
                                        </div>
                                        <div class="flex space-x-2">
                                            ${gestor.ativo == 1 ? `
                                                <button onclick="removerLotacaoGestor(${gestor.id})" 
                                                        class="text-red-600 hover:text-red-800 text-sm">
                                                    Remover
                                                </button>
                                            ` : `
                                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Inativo</span>
                                            `}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        lista.innerHTML = html;
                    } else {
                        lista.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhum gestor lotado</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar gestores lotados:', error);
                });
        }

        // Função para remover lotação de professor
        function removerLotacaoProfessor(lotacaoId) {
            if (!confirm('Tem certeza que deseja remover esta lotação?')) {
                return;
            }

            const formData = new FormData();
            formData.append('acao', 'remover');
            formData.append('lotacao_id', lotacaoId);

            fetch('../../Controllers/gestao/ProfessorLotacaoController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Lotação removida com sucesso!');
                    carregarProfessoresLotados();
                } else {
                    alert('Erro ao remover lotação: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao remover lotação:', error);
                alert('Erro ao remover lotação');
            });
        }

        // Função para remover lotação de gestor
        function removerLotacaoGestor(lotacaoId) {
            if (!confirm('Tem certeza que deseja remover esta lotação?')) {
                return;
            }

            const formData = new FormData();
            formData.append('acao', 'remover');
            formData.append('lotacao_id', lotacaoId);

            fetch('../../Controllers/gestao/GestorLotacaoController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Lotação removida com sucesso!');
                    carregarGestoresLotados();
                } else {
                    alert('Erro ao remover lotação: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao remover lotação:', error);
                alert('Erro ao remover lotação');
            });
        }

        // ===== FUNÇÕES PARA ADICIONAR GESTOR À ESCOLA =====
        
        // Função para carregar informações da escola selecionada
        function carregarInfoEscolaGestor(escolaId) {
            if (!escolaId) {
                document.getElementById('info-escola-gestor').classList.add('hidden');
                document.getElementById('passo-selecionar-gestor').classList.add('hidden');
                return;
            }

            // Obter o nome da escola selecionada do campo de busca
            const campoBusca = document.getElementById('buscar_escola_gestor');
            const nomeEscola = campoBusca.value;
             
             // Simular carregamento das informações da escola
             const detalhesEscola = document.getElementById('detalhes-escola-gestor');
             detalhesEscola.innerHTML = `
                 <div class="space-y-2">
                     <div class="flex items-center space-x-2">
                         <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                         <span class="text-sm"><strong>Nome:</strong> ${nomeEscola}</span>
                     </div>
                     <div class="flex items-center space-x-2">
                         <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                         <span class="text-sm"><strong>Município:</strong> Maranguape - CE</span>
                     </div>
                     <div class="flex items-center space-x-2">
                         <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                         <span class="text-sm"><strong>ID:</strong> ${escolaId}</span>
                     </div>
                 </div>
             `;
            
            // Mostrar informações da escola
            document.getElementById('info-escola-gestor').classList.remove('hidden');
            
            // Mostrar passo 2 (seleção do gestor)
            document.getElementById('passo-selecionar-gestor').classList.remove('hidden');
            
            // Definir o ID da escola no formulário
            document.getElementById('escola_id_gestor').value = escolaId;
        }

        // Função para buscar gestores
        function buscarGestores(termo) {
            const gestorItems = document.querySelectorAll('.gestor-item');
            const termoLower = termo.toLowerCase();
            
            gestorItems.forEach(item => {
                const nomeGestor = item.querySelector('.font-medium').textContent.toLowerCase();
                if (nomeGestor.includes(termoLower)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Função para selecionar/deselecionar um gestor
        function selecionarGestor(gestorId, nomeGestor) {
            const radioSelecionado = document.getElementById(`gestor_${gestorId}`);
            
            // Verificar se o gestor já está selecionado
            if (radioSelecionado && radioSelecionado.checked) {
                // Se já está selecionado, deselecionar
                deselecionarGestor();
                return;
            }
            
            // Desmarcar todos os radio buttons e remover estilos de seleção
            document.querySelectorAll('.gestor-radio').forEach(radio => {
                radio.checked = false;
                const gestorItem = radio.closest('.gestor-item');
                if (gestorItem) {
                    gestorItem.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50', 'border-blue-300');
                    gestorItem.classList.add('border-gray-200');
                    
                    // Resetar ícone de seleção
                    const iconContainer = gestorItem.querySelector('.w-6.h-6');
                    if (iconContainer) {
                        iconContainer.classList.remove('border-blue-500', 'bg-blue-500');
                        iconContainer.classList.add('border-gray-300');
                        const dot = iconContainer.querySelector('.w-2.h-2');
                        if (dot) {
                            dot.classList.remove('bg-white');
                            dot.classList.add('bg-transparent');
                        }
                    }
                }
            });
            
            // Marcar o selecionado
            if (radioSelecionado) {
                radioSelecionado.checked = true;
                const gestorItem = radioSelecionado.closest('.gestor-item');
                if (gestorItem) {
                    gestorItem.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50', 'border-blue-300');
                    gestorItem.classList.remove('border-gray-200');
                    
                    // Atualizar ícone de seleção
                    const iconContainer = gestorItem.querySelector('.w-6.h-6');
                    if (iconContainer) {
                        iconContainer.classList.add('border-blue-500', 'bg-blue-500');
                        iconContainer.classList.remove('border-gray-300');
                        const dot = iconContainer.querySelector('.w-2.h-2');
                        if (dot) {
                            dot.classList.add('bg-white');
                            dot.classList.remove('bg-transparent');
                        }
                    }
                }
            }
            
            // Mostrar gestor selecionado
            document.getElementById('nome-gestor-selecionado').textContent = nomeGestor;
            document.getElementById('gestor-selecionado').classList.remove('hidden');
            
            // Validar seleção completa
            validarSelecaoGestor();
        }

        // Função para deselecionar o gestor atual
        function deselecionarGestor() {
            // Desmarcar todos os radio buttons
            document.querySelectorAll('.gestor-radio').forEach(radio => {
                radio.checked = false;
                const gestorItem = radio.closest('.gestor-item');
                if (gestorItem) {
                    gestorItem.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50', 'border-blue-300');
                    gestorItem.classList.add('border-gray-200');
                    
                    // Resetar ícone de seleção
                    const iconContainer = gestorItem.querySelector('.w-6.h-6');
                    if (iconContainer) {
                        iconContainer.classList.remove('border-blue-500', 'bg-blue-500');
                        iconContainer.classList.add('border-gray-300');
                        const dot = iconContainer.querySelector('.w-2.h-2');
                        if (dot) {
                            dot.classList.remove('bg-white');
                            dot.classList.add('bg-transparent');
                        }
                    }
                }
            });
            
            // Ocultar gestor selecionado
            document.getElementById('gestor-selecionado').classList.add('hidden');
            
            // Validar seleção completa
            validarSelecaoGestor();
        }

        // Função para limpar seleção
        function limparSelecaoGestor() {
            try {
                // Primeiro deselecionar qualquer gestor selecionado
                deselecionarGestor();
                
                // Limpar campo de busca
                const buscarGestor = document.getElementById('buscar_gestor');
                if (buscarGestor) {
                    buscarGestor.value = '';
                }
                
                // Limpar tipo de gestor
                const tipoGestor = document.getElementById('tipo_gestor');
                if (tipoGestor) {
                    tipoGestor.value = '';
                }
                
                // Mostrar todos os gestores novamente
                document.querySelectorAll('.gestor-item').forEach(item => {
                    item.style.display = 'flex';
                });
                
                // Validar seleção
                validarSelecaoGestor();
            } catch (error) {
                console.error('Erro na função limparSelecaoGestor:', error);
            }
        }

        // Função para carregar gestor atual da escola
        function carregarGestorAtualEscola(escolaId) {
            // Simular carregamento do gestor atual
            // Em uma implementação real, você faria uma requisição AJAX aqui
            const gestorAtualDiv = document.getElementById('gestor-atual-escola');
            const infoGestorDiv = document.getElementById('info-gestor-atual');
            
            // Simular dados do gestor atual (substitua por dados reais)
            const gestorAtual = {
                nome: "João Silva",
                cargo: "Diretor",
                dataInicio: "2023-01-15"
            };
            
            if (gestorAtual.nome) {
                infoGestorDiv.innerHTML = `
                    <div class="space-y-1">
                        <div><strong>Nome:</strong> ${gestorAtual.nome}</div>
                        <div><strong>Cargo:</strong> ${gestorAtual.cargo}</div>
                        <div><strong>Desde:</strong> ${new Date(gestorAtual.dataInicio).toLocaleDateString('pt-BR')}</div>
                    </div>
                `;
                gestorAtualDiv.classList.remove('hidden');
                
                // Desabilitar opção de diretor se já existir um
                if (gestorAtual.cargo.toLowerCase() === 'diretor') {
                    document.getElementById('opcao-diretor').disabled = true;
                    document.getElementById('opcao-diretor').textContent = 'Diretor (já existe)';
                }
            } else {
                gestorAtualDiv.classList.add('hidden');
                // Habilitar opção de diretor se não existir
                document.getElementById('opcao-diretor').disabled = false;
                document.getElementById('opcao-diretor').textContent = 'Diretor';
            }
        }

        // Função para verificar cargo do gestor
        function verificarCargoGestor() {
            const cargoSelect = document.getElementById('cargo_gestor');
            const avisoDiv = document.getElementById('aviso-diretor');
            const opcaoDiretor = document.getElementById('opcao-diretor');
            
            if (cargoSelect.value === 'diretor' && opcaoDiretor.disabled) {
                avisoDiv.classList.remove('hidden');
                cargoSelect.value = ''; // Limpar seleção
            } else {
                avisoDiv.classList.add('hidden');
            }
        }

        // Array com todas as escolas para autocomplete
        let todasEscolas = [
            <?php
            $escolas = listarEscolas();
            $escolasJson = [];
            foreach ($escolas as $escola) {
                $escolasJson[] = '{id: ' . $escola['id'] . ', nome: "' . htmlspecialchars($escola['nome']) . '"}';
            }
            echo implode(',', $escolasJson);
            ?>
        ];

        // Variáveis para controle do autocomplete
        let sugestaoAtivaGestor = -1;
        let sugestaoAtivaLotacao = -1;

        // Função para buscar escolas na aba de gestor (autocomplete)
        function buscarEscolasGestor(termo) {
            const campoBusca = document.getElementById('buscar_escola_gestor');
            const sugestoes = document.getElementById('sugestoes_gestor');
            const termoLower = termo.toLowerCase().trim();
            
            // Limpar seleção anterior
            document.getElementById('escola_gestor').value = '';
            
            if (termo.length === 0) {
                sugestoes.classList.add('hidden');
                return;
            }
            
            // Filtrar escolas
            const escolasFiltradas = todasEscolas.filter(escola => 
                escola.nome.toLowerCase().includes(termoLower)
            ).sort((a, b) => {
                // Ordenar por posição do match
                const posA = a.nome.toLowerCase().indexOf(termoLower);
                const posB = b.nome.toLowerCase().indexOf(termoLower);
                return posA - posB;
            });
            
            if (escolasFiltradas.length === 0) {
                sugestoes.classList.add('hidden');
                return;
            }
            
            // Criar HTML das sugestões
            let htmlSugestoes = '';
            escolasFiltradas.forEach((escola, index) => {
                const nomeEscola = escola.nome;
                const termoRegex = new RegExp(`(${termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                const nomeDestacado = nomeEscola.replace(termoRegex, '<span style="color: #059669; font-weight: bold;">$1</span>');
                
                htmlSugestoes += `
                    <div class="sugestao-item px-4 py-2 cursor-pointer hover:bg-black hover:bg-opacity-5 dark:hover:bg-white dark:hover:bg-opacity-10 border-b border-gray-100 last:border-b-0 transition-all duration-200" 
                         data-index="${index}" 
                         data-id="${escola.id}" 
                         data-nome="${nomeEscola}"
                         onclick="selecionarEscolaGestor('${escola.id}', '${nomeEscola}')">
                        ${nomeDestacado}
                    </div>
                `;
            });
            
            sugestoes.innerHTML = htmlSugestoes;
            sugestoes.classList.remove('hidden');
            sugestaoAtivaGestor = -1;
        }

        // Função para selecionar escola no autocomplete gestor
        function selecionarEscolaGestor(id, nome) {
            document.getElementById('buscar_escola_gestor').value = nome;
            document.getElementById('escola_gestor').value = id;
            document.getElementById('sugestoes_gestor').classList.add('hidden');
            carregarInfoEscolaGestor(id);
        }

        // Função para mostrar sugestões gestor
        function mostrarSugestoesGestor() {
            const termo = document.getElementById('buscar_escola_gestor').value;
            if (termo.length > 0) {
                buscarEscolasGestor(termo);
            }
        }

        // Função para esconder sugestões gestor
        function esconderSugestoesGestor() {
            setTimeout(() => {
                document.getElementById('sugestoes_gestor').classList.add('hidden');
            }, 200);
        }

        // Função para navegar nas sugestões com teclado gestor
        function navegarSugestoesGestor(event) {
            const sugestoes = document.getElementById('sugestoes_gestor');
            const itens = sugestoes.querySelectorAll('.sugestao-item');
            
            if (itens.length === 0) return;
            
            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    sugestaoAtivaGestor = Math.min(sugestaoAtivaGestor + 1, itens.length - 1);
                    atualizarDestaqueGestor();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    sugestaoAtivaGestor = Math.max(sugestaoAtivaGestor - 1, -1);
                    atualizarDestaqueGestor();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (sugestaoAtivaGestor >= 0 && sugestaoAtivaGestor < itens.length) {
                        const item = itens[sugestaoAtivaGestor];
                        const id = item.getAttribute('data-id');
                        const nome = item.getAttribute('data-nome');
                        selecionarEscolaGestor(id, nome);
                    }
                    break;
                case 'Escape':
                    sugestoes.classList.add('hidden');
                    sugestaoAtivaGestor = -1;
                    break;
            }
        }

        // Função para atualizar destaque das sugestões gestor
        function atualizarDestaqueGestor() {
            const itens = document.querySelectorAll('#sugestoes_gestor .sugestao-item');
            itens.forEach((item, index) => {
                if (index === sugestaoAtivaGestor) {
                    item.classList.add('bg-black', 'bg-opacity-10', 'dark:bg-white', 'dark:bg-opacity-20');
                    item.classList.remove('hover:bg-black', 'hover:bg-opacity-5', 'dark:hover:bg-white', 'dark:hover:bg-opacity-10');
                } else {
                    item.classList.remove('bg-black', 'bg-opacity-10', 'dark:bg-white', 'dark:bg-opacity-20');
                    item.classList.add('hover:bg-black', 'hover:bg-opacity-5', 'dark:hover:bg-white', 'dark:hover:bg-opacity-10');
                }
            });
        }

        // Função para buscar escolas na aba de lotação (autocomplete)
        function buscarEscolasLotacao(termo) {
            const campoBusca = document.getElementById('buscar_escola_lotacao');
            const sugestoes = document.getElementById('sugestoes_lotacao');
            const termoLower = termo.toLowerCase().trim();
            
            // Limpar seleção anterior
            document.getElementById('escola_lotacao').value = '';
            
            if (termo.length === 0) {
                sugestoes.classList.add('hidden');
                return;
            }
            
            // Filtrar escolas
            const escolasFiltradas = todasEscolas.filter(escola => 
                escola.nome.toLowerCase().includes(termoLower)
            ).sort((a, b) => {
                // Ordenar por posição do match
                const posA = a.nome.toLowerCase().indexOf(termoLower);
                const posB = b.nome.toLowerCase().indexOf(termoLower);
                return posA - posB;
            });
            
            if (escolasFiltradas.length === 0) {
                sugestoes.classList.add('hidden');
                return;
            }
            
            // Criar HTML das sugestões
            let htmlSugestoes = '';
            escolasFiltradas.forEach((escola, index) => {
                const nomeEscola = escola.nome;
                const termoRegex = new RegExp(`(${termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                const nomeDestacado = nomeEscola.replace(termoRegex, '<span style="color: #059669; font-weight: bold;">$1</span>');
                
                htmlSugestoes += `
                    <div class="sugestao-item px-4 py-2 cursor-pointer hover:bg-black hover:bg-opacity-5 dark:hover:bg-white dark:hover:bg-opacity-10 border-b border-gray-100 last:border-b-0 transition-all duration-200" 
                         data-index="${index}" 
                         data-id="${escola.id}" 
                         data-nome="${nomeEscola}"
                         onclick="selecionarEscolaLotacao('${escola.id}', '${nomeEscola}')">
                        ${nomeDestacado}
                    </div>
                `;
            });
            
            sugestoes.innerHTML = htmlSugestoes;
            sugestoes.classList.remove('hidden');
            sugestaoAtivaLotacao = -1;
        }

        // Função para selecionar escola no autocomplete lotação
        function selecionarEscolaLotacao(id, nome) {
            document.getElementById('buscar_escola_lotacao').value = nome;
            document.getElementById('escola_lotacao').value = id;
            document.getElementById('sugestoes_lotacao').classList.add('hidden');
            carregarLotacaoEscola(id);
        }

        // Função para mostrar sugestões lotação
        function mostrarSugestoesLotacao() {
            const termo = document.getElementById('buscar_escola_lotacao').value;
            if (termo.length > 0) {
                buscarEscolasLotacao(termo);
            }
        }

        // Função para esconder sugestões lotação
        function esconderSugestoesLotacao() {
            setTimeout(() => {
                document.getElementById('sugestoes_lotacao').classList.add('hidden');
            }, 200);
        }

        // Função para navegar nas sugestões com teclado lotação
        function navegarSugestoesLotacao(event) {
            const sugestoes = document.getElementById('sugestoes_lotacao');
            const itens = sugestoes.querySelectorAll('.sugestao-item');
            
            if (itens.length === 0) return;
            
            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    sugestaoAtivaLotacao = Math.min(sugestaoAtivaLotacao + 1, itens.length - 1);
                    atualizarDestaqueLotacao();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    sugestaoAtivaLotacao = Math.max(sugestaoAtivaLotacao - 1, -1);
                    atualizarDestaqueLotacao();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (sugestaoAtivaLotacao >= 0 && sugestaoAtivaLotacao < itens.length) {
                        const item = itens[sugestaoAtivaLotacao];
                        const id = item.getAttribute('data-id');
                        const nome = item.getAttribute('data-nome');
                        selecionarEscolaLotacao(id, nome);
                    }
                    break;
                case 'Escape':
                    sugestoes.classList.add('hidden');
                    sugestaoAtivaLotacao = -1;
                    break;
            }
        }

        // Função para atualizar destaque das sugestões lotação
        function atualizarDestaqueLotacao() {
            const itens = document.querySelectorAll('#sugestoes_lotacao .sugestao-item');
            itens.forEach((item, index) => {
                if (index === sugestaoAtivaLotacao) {
                    item.classList.add('bg-black', 'bg-opacity-10', 'dark:bg-white', 'dark:bg-opacity-20');
                    item.classList.remove('hover:bg-black', 'hover:bg-opacity-5', 'dark:hover:bg-white', 'dark:hover:bg-opacity-10');
                } else {
                    item.classList.remove('bg-black', 'bg-opacity-10', 'dark:bg-white', 'dark:bg-opacity-20');
                    item.classList.add('hover:bg-black', 'hover:bg-opacity-5', 'dark:hover:bg-white', 'dark:hover:bg-opacity-10');
                }
            });
        }


        // Função para validar seleção de gestor
        function validarSelecaoGestor() {
            const gestorSelecionado = document.getElementById('gestor-selecionado');
            const tipoGestor = document.getElementById('tipo_gestor');
            const btnAdicionar = document.getElementById('btn-adicionar-gestor');
            
            if (gestorSelecionado && tipoGestor && btnAdicionar) {
                const gestorValido = !gestorSelecionado.classList.contains('hidden');
                const tipoValido = tipoGestor.value !== '';
                
                if (gestorValido && tipoValido) {
                    btnAdicionar.disabled = false;
                    btnAdicionar.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    btnAdicionar.disabled = true;
                    btnAdicionar.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        }

        // Função para mostrar tab (atualizada para incluir nova aba)
        function showTab(tabId) {
            try {
                // Esconder todas as tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                    tab.classList.add('hidden');
                });
                
                // Remover classe ativa de todos os botões
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('tab-active');
                });
                
                // Mostrar a tab selecionada
                const tabSelecionada = document.getElementById(tabId);
                if (tabSelecionada) {
                    tabSelecionada.classList.remove('hidden');
                    tabSelecionada.classList.add('active');
                }
                
                // Adicionar classe ativa ao botão clicado
                if (event && event.currentTarget) {
                    event.currentTarget.classList.add('tab-active');
                }
                
                // Resetar formulário quando mudar para a aba de adicionar gestor
                if (tabId === 'tab-adicionar-gestor') {
                    // Verificar se os elementos existem antes de tentar acessá-los
                    const elementosGestor = [
                        'escola_gestor',
                        'buscar_escola_gestor',
                        'info-escola-gestor', 
                        'passo-selecionar-gestor'
                    ];
                    
                    elementosGestor.forEach(id => {
                        const elemento = document.getElementById(id);
                        if (elemento) {
                            if (id === 'escola_gestor' || id === 'buscar_escola_gestor') {
                                elemento.value = '';
                                if (id === 'escola_gestor') {
                                    elemento.size = 1; // Resetar tamanho do select
                                }
                            } else {
                                elemento.classList.add('hidden');
                            }
                        }
                    });
                    
                    // Chamar limparSelecaoGestor apenas se os elementos existirem
                    setTimeout(() => {
                        try {
                            if (document.getElementById('buscar_gestor')) {
                                limparSelecaoGestor();
                            }
                        } catch (e) {
                            console.log('Elementos de gestor ainda não carregados');
                        }
                    }, 100);
                }
                
                // Resetar formulário quando mudar para a aba de lotação
                if (tabId === 'tab-lotacao') {
                    const elementosLotacao = [
                        'escola_lotacao',
                        'buscar_escola_lotacao',
                        'info-escola-lotacao',
                        'secao-lotacao'
                    ];
                    
                    elementosLotacao.forEach(id => {
                        const elemento = document.getElementById(id);
                        if (elemento) {
                            if (id === 'escola_lotacao' || id === 'buscar_escola_lotacao') {
                                elemento.value = '';
                                if (id === 'escola_lotacao') {
                                    elemento.size = 1; // Resetar tamanho do select
                                }
                            } else {
                                elemento.classList.add('hidden');
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Erro na função showTab:', error);
            }
        }
    </script>

    <!-- User Profile Modal -->
    <div id="userProfileModal" class="fixed inset-0 bg-white z-50 hidden">
        <div class="h-full w-full overflow-hidden">
            <div class="bg-white h-full w-full overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-primary-green text-white p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <span class="text-2xl font-bold text-white" id="profileInitials"><?php
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
                                <h2 class="text-2xl font-bold" id="profileName"><?php echo $_SESSION['nome']; ?></h2>
                                <p class="text-green-100" id="profileRole"><?php echo $_SESSION['tipo']; ?></p>
                            </div>
                        </div>
                        <button onclick="closeUserProfile()" class="p-2 hover:bg-white hover:bg-opacity-20 rounded-full transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto h-[calc(100vh-120px)]">
                    <!-- User Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Pessoais</h3>
                        <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                            <!-- ADM Simplified Info -->
                            <div class="bg-gray-50 p-6 rounded-xl">
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 bg-primary-green rounded-full flex items-center justify-center">
                                        <span class="text-2xl font-bold text-white" id="profileInitials"><?php
            $nome = $_SESSION['nome'] ?? '';
                            $iniciais = '';
                            if (strlen($nome) >= 2) {
                                $iniciais = strtoupper(substr($nome, 0, 2));
                            } elseif (strlen($nome) == 1) {
                                $iniciais = strtoupper($nome);
                            } else {
                                $iniciais = 'AD';
                            }
                            echo $iniciais;
                            ?></span>
                                    </div>
                                    <div>
                                        <h4 class="text-xl font-bold text-gray-900"><?php echo $_SESSION['nome']; ?></h4>
                                        <p class="text-primary-green font-medium">Administrador Geral</p>
                                        <p class="text-sm text-gray-600"><?php echo $_SESSION['email']; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php } else { ?>
                            <!-- Other Users Full Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Nome Completo</label>
                                <p class="text-gray-900 font-medium" id="profileFullName"><?php echo $_SESSION['nome']; ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">CPF</label>
                                <p class="text-gray-900 font-medium" id="profileCPF"><?php echo $_SESSION['cpf']; ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Email</label>
                                <p class="text-gray-900 font-medium" id="profileEmail"><?php echo $_SESSION['email']; ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Telefone</label>
                                <p class="text-gray-900 font-medium" id="profilePhone"><?php echo $_SESSION['telefone']; ?></p>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <!-- School Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4" id="schoolsTitle">
                            <?php
                            if ($_SESSION['tipo'] === 'ADM') {
                                echo 'Secretaria Municipal da Educação';
                            } else {
                                echo 'Escola Atual';
                            }
?>
                        </h3>
                        <div id="schoolsContainer">
                            <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                                <!-- ADM Specific Information -->
                                <div class="bg-gradient-to-r from-primary-green to-green-600 text-white p-6 rounded-xl">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xl font-bold">Secretaria Municipal da Educação</h4>
                                            <p class="text-green-100">Órgão Central de Gestão Educacional</p>
                                            <p class="text-green-200 text-sm mt-1">Responsável por todas as escolas municipais</p>
                                        </div>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <!-- Schools will be dynamically loaded here for other users -->
                            <?php } ?>
                        </div>
                    </div>

                    <!-- User Type Specific Information -->
                    <?php if ($_SESSION['tipo'] !== 'ADM') { ?>
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Gerais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Carga Horária Total</label>
                                <p class="text-gray-900 font-medium" id="profileWorkload">40h semanais</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Data de Admissão</label>
                                <p class="text-gray-900 font-medium" id="profileAdmission">15/03/2020</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Status</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800" id="profileStatus">
                                    Ativo
                                </span>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="text-sm font-medium text-gray-600">Total de Escolas</label>
                                <p class="text-gray-900 font-medium" id="totalSchools">1 escola</p>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <!-- Configurações de Acessibilidade -->
                    <div class="mb-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <div class="w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Configurações de Acessibilidade</h3>
                                <p class="text-xs text-gray-500">Personalize sua experiência</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Tema -->
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-2 mb-3">
                                    <div class="w-5 h-5 bg-yellow-100 rounded flex items-center justify-center">
                                        <svg class="w-3 h-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Tema Visual</h4>
                                        <p class="text-xs text-gray-500">Claro ou escuro</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button id="theme-light" class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                        <div class="flex items-center justify-center space-x-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                            <span>Claro</span>
                                        </div>
                                    </button>
                                    <button id="theme-dark" class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                        <div class="flex items-center justify-center space-x-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                            </svg>
                                            <span>Escuro</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Contraste -->
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-2 mb-3">
                                    <div class="w-5 h-5 bg-red-100 rounded flex items-center justify-center">
                                        <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Contraste</h4>
                                        <p class="text-xs text-gray-500">Ajustar cores</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="setContrast('normal')" id="contrast-normal" class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                        <span>Normal</span>
                                    </button>
                                    <button onclick="setContrast('high')" id="contrast-high" class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                        <span>Alto</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Tamanho da Fonte -->
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-2 mb-3">
                                    <div class="w-5 h-5 bg-green-100 rounded flex items-center justify-center">
                                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Tamanho da Fonte</h4>
                                        <p class="text-xs text-gray-500">Ajustar texto</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <button onclick="setFontSize('normal')" id="font-normal" class="px-2 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                        <span class="text-sm">A</span>
                                    </button>
                                    <button onclick="setFontSize('large')" id="font-large" class="px-2 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                        <span class="text-base">A</span>
                                    </button>
                                    <button onclick="setFontSize('larger')" id="font-larger" class="px-2 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                        <span class="text-lg">A</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Configurações Avançadas -->
                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-2 mb-3">
                                    <div class="w-5 h-5 bg-purple-100 rounded flex items-center justify-center">
                                        <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Configurações Avançadas</h4>
                                        <p class="text-xs text-gray-500">Opções extras</p>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <!-- Redução de Movimento -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-4 h-4 bg-blue-100 rounded flex items-center justify-center">
                                                <svg class="w-2 h-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Redução de Movimento</p>
                                                <p class="text-xs text-gray-500">Menos animações</p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="reduce-motion" onchange="setReduceMotion(this.checked)" class="sr-only peer">
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-500"></div>
                                        </label>
                                    </div>

                                    <!-- VLibras -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-4 h-4 bg-purple-100 rounded flex items-center justify-center">
                                                <svg class="w-2 h-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">VLibras (Libras)</p>
                                                <p class="text-xs text-gray-500">Tradução para Libras</p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="vlibras-toggle" class="sr-only peer" onchange="toggleVLibras()" checked>
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-500"></div>
                                        </label>
                                    </div>

                                    <!-- Navegação por Teclado -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-4 h-4 bg-green-100 rounded flex items-center justify-center">
                                                <svg class="w-2 h-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Navegação por Teclado</p>
                                                <p class="text-xs text-gray-500">Destacar foco</p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="keyboard-nav" onchange="setKeyboardNavigation(this.checked)" class="sr-only peer">
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-500"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
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
                <button onclick="closeLogoutModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button onclick="logout()" class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors duration-200">
                    Sim, Sair
                </button>
            </div>
        </div>
    </div>
</body>
</html>
