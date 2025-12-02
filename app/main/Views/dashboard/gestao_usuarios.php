<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar permissão usando o sistema de permissões
if (!temPermissao('cadastrar_pessoas') && !eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// Funções para gerenciamento de usuários
function listarUsuarios($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT u.id, u.role as tipo, u.ativo, 
                   CASE WHEN u.ativo = 0 THEN 1 ELSE 0 END as bloqueado, 
                   u.ultimo_login, u.created_at as data_criacao, u.username,
                   p.nome, p.cpf, p.email, p.telefone 
            FROM usuario u 
            JOIN pessoa p ON u.pessoa_id = p.id 
            WHERE 1=1";
    
    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR p.email LIKE :busca OR u.username LIKE :busca)";
    }
    
    $sql .= " ORDER BY p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obterEstatisticasUsuarios() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Estatísticas gerais
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN u.ativo = 1 THEN 1 ELSE 0 END) as ativos,
                SUM(CASE WHEN u.ativo = 0 THEN 1 ELSE 0 END) as bloqueados,
                SUM(CASE WHEN u.role = 'ADM' THEN 1 ELSE 0 END) as adm,
                SUM(CASE WHEN u.role = 'GESTAO' THEN 1 ELSE 0 END) as gestao,
                SUM(CASE WHEN u.role = 'PROFESSOR' THEN 1 ELSE 0 END) as professor,
                SUM(CASE WHEN u.role = 'ALUNO' THEN 1 ELSE 0 END) as aluno,
                SUM(CASE WHEN u.role = 'NUTRICIONISTA' THEN 1 ELSE 0 END) as nutricionista,
                SUM(CASE WHEN u.role = 'ADM_MERENDA' THEN 1 ELSE 0 END) as adm_merenda
            FROM usuario u";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obterEstatisticasGestores() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Estatísticas específicas de gestores
    $sql = "SELECT 
                COUNT(*) as total_gestores,
                SUM(CASE WHEN u.ativo = 1 THEN 1 ELSE 0 END) as gestores_ativos,
                SUM(CASE WHEN u.ativo = 0 THEN 1 ELSE 0 END) as gestores_bloqueados,
                SUM(CASE WHEN u.ultimo_login IS NOT NULL THEN 1 ELSE 0 END) as gestores_com_login,
                SUM(CASE WHEN u.ultimo_login IS NULL THEN 1 ELSE 0 END) as gestores_sem_login
            FROM usuario u
            WHERE u.role = 'GESTAO'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obterDadosUsuarioLogado($usuarioId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT 
                u.id as usuario_id,
                u.username,
                u.role as tipo,
                u.ativo,
                u.ultimo_login,
                u.created_at as data_criacao,
                p.id as pessoa_id,
                p.nome,
                p.cpf,
                p.email,
                p.telefone,
                p.data_nascimento,
                p.sexo,
                p.endereco,
                p.cep,
                p.cidade,
                p.estado
            FROM usuario u 
            JOIN pessoa p ON u.pessoa_id = p.id 
            WHERE u.id = :usuario_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar dados do usuário logado
$dadosUsuario = null;
if (isset($_SESSION['usuario_id'])) {
    $dadosUsuario = obterDadosUsuarioLogado($_SESSION['usuario_id']);
}

function listarGestores() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT u.id, u.role as tipo, u.ativo, 
                   CASE WHEN u.ativo = 0 THEN 1 ELSE 0 END as bloqueado, 
                   u.ultimo_login, u.created_at as data_criacao, u.username,
                   p.nome, p.cpf, p.email, p.telefone 
            FROM usuario u 
            JOIN pessoa p ON u.pessoa_id = p.id 
            WHERE u.role = 'GESTAO'
            ORDER BY p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cadastrarUsuario($dados) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Remover caracteres especiais do CPF (pontos e traço)
    $cpfLimpo = preg_replace('/[^0-9]/', '', $dados['cpf']);
    
    // Verificar se CPF já existe
    $stmt = $conn->prepare("SELECT id FROM pessoa WHERE cpf = :cpf");
    $stmt->bindParam(':cpf', $cpfLimpo);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return ['status' => false, 'mensagem' => 'CPF já cadastrado no sistema.'];
    }
    
    // Verificar se email já existe
    $stmt = $conn->prepare("SELECT id FROM pessoa WHERE email = :email");
    $stmt->bindParam(':email', $dados['email']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return ['status' => false, 'mensagem' => 'E-mail já cadastrado no sistema.'];
    }
    
    // Verificar se username já existe
    $username = strtolower(explode(' ', $dados['nome'])[0]); // Usar o primeiro nome como username
    $stmt = $conn->prepare("SELECT id FROM usuario WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Se o username já existe, adiciona um número ao final
        $count = 1;
        $newUsername = $username . $count;
        
        while (true) {
            $stmt = $conn->prepare("SELECT id FROM usuario WHERE username = :username");
            $stmt->bindParam(':username', $newUsername);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                $username = $newUsername;
                break;
            }
            
            $count++;
            $newUsername = $username . $count;
        }
    }
    
    try {
        // Iniciar transação
        $conn->beginTransaction();
        
        // Tratar data de nascimento - converter string vazia para NULL
        $dataNascimento = !empty($dados['data_nascimento']) ? $dados['data_nascimento'] : null;
        
        // Inserir na tabela pessoa
        $stmt = $conn->prepare("INSERT INTO pessoa (nome, cpf, email, telefone, data_nascimento, tipo) 
                                VALUES (:nome, :cpf, :email, :telefone, :data_nascimento, 'FUNCIONARIO')");
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cpf', $cpfLimpo);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':data_nascimento', $dataNascimento);
        
        $stmt->execute();
        $pessoaId = $conn->lastInsertId();
        
        // Hash da senha
        $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
        
        // Inserir na tabela usuario
        $stmt = $conn->prepare("INSERT INTO usuario (pessoa_id, username, senha_hash, role, ativo) 
                                VALUES (:pessoa_id, :username, :senha_hash, :role, 1)");
        
        $stmt->bindParam(':pessoa_id', $pessoaId);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':senha_hash', $senhaHash);
        $stmt->bindParam(':role', $dados['tipo']);
        
        $stmt->execute();
        
        // Confirmar transação
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Usuário cadastrado com sucesso!'];
    } catch (PDOException $e) {
        // Reverter transação em caso de erro
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao cadastrar usuário: ' . $e->getMessage()];
    }
}

function excluirUsuario($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Buscar pessoa_id antes de excluir
        $stmt = $conn->prepare("SELECT pessoa_id FROM usuario WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            return ['status' => false, 'mensagem' => 'Usuário não encontrado.'];
        }
        
        $pessoaId = $usuario['pessoa_id'];
        
        // Iniciar transação
        $conn->beginTransaction();
        
        // Excluir usuário
        $stmt = $conn->prepare("DELETE FROM usuario WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Excluir pessoa
        $stmt = $conn->prepare("DELETE FROM pessoa WHERE id = :id");
        $stmt->bindParam(':id', $pessoaId);
        $stmt->execute();
        
        // Confirmar transação
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Usuário excluído com sucesso!'];
    } catch (PDOException $e) {
        // Reverter transação em caso de erro
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao excluir usuário: ' . $e->getMessage()];
    }
}

function atualizarUsuario($dados) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Log para depuração
    error_log("Função atualizarUsuario() iniciada com dados: " . json_encode($dados));
    
    // Remover caracteres especiais do CPF (pontos e traço)
    $cpfLimpo = preg_replace('/[^0-9]/', '', $dados['cpf']);
    error_log("CPF limpo: " . $cpfLimpo);
    
    try {
        // Iniciar transação
        $conn->beginTransaction();
        
        // Verificar se o CPF já existe (exceto para o próprio usuário)
        $stmt = $conn->prepare("SELECT id FROM pessoa WHERE cpf = :cpf AND id != :pessoa_id");
        $stmt->bindParam(':cpf', $cpfLimpo);
        $stmt->bindParam(':pessoa_id', $dados['pessoa_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['status' => false, 'mensagem' => 'CPF já cadastrado para outro usuário.'];
        }
        
        // Verificar se o email já existe (exceto para o próprio usuário)
        $stmt = $conn->prepare("SELECT id FROM pessoa WHERE email = :email AND id != :pessoa_id");
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':pessoa_id', $dados['pessoa_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['status' => false, 'mensagem' => 'E-mail já cadastrado para outro usuário.'];
        }
        
        // Verificar se o username já existe (exceto para o próprio usuário)
        $stmt = $conn->prepare("SELECT id FROM usuario WHERE username = :username AND id != :id");
        $stmt->bindParam(':username', $dados['username']);
        $stmt->bindParam(':id', $dados['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['status' => false, 'mensagem' => 'Username já cadastrado para outro usuário.'];
        }
        
        // Tratar data de nascimento - converter string vazia para NULL
        $dataNascimento = !empty($dados['data_nascimento']) ? $dados['data_nascimento'] : null;
        
        // Atualizar dados na tabela pessoa
        $stmt = $conn->prepare("UPDATE pessoa SET 
                                nome = :nome, 
                                cpf = :cpf, 
                                email = :email, 
                                telefone = :telefone, 
                                data_nascimento = :data_nascimento 
                                WHERE id = :pessoa_id");
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cpf', $cpfLimpo);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':data_nascimento', $dataNascimento);
        $stmt->bindParam(':pessoa_id', $dados['pessoa_id']);
        
        $stmt->execute();
        
        // Preparar a atualização na tabela usuario
        if (!empty($dados['senha'])) {
            // Se a senha foi fornecida, atualizar com a nova senha
            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE usuario SET 
                                    username = :username, 
                                    senha_hash = :senha_hash, 
                                    role = :role, 
                                    ativo = :ativo 
                                    WHERE id = :id");
            
            $stmt->bindParam(':senha_hash', $senhaHash);
        } else {
            // Se a senha não foi fornecida, manter a senha atual
            $stmt = $conn->prepare("UPDATE usuario SET 
                                    username = :username, 
                                    role = :role, 
                                    ativo = :ativo 
                                    WHERE id = :id");
        }
        
        $stmt->bindParam(':username', $dados['username']);
        $stmt->bindParam(':role', $dados['tipo']);
        $stmt->bindParam(':ativo', $dados['ativo']);
        $stmt->bindParam(':id', $dados['id']);
        
        $stmt->execute();
        
        // Confirmar transação
        $conn->commit();
        
        error_log("Transação confirmada com sucesso!");
        return ['status' => true, 'mensagem' => 'Usuário atualizado com sucesso!'];
    } catch (PDOException $e) {
        // Reverter transação em caso de erro
        $conn->rollBack();
        error_log("Erro na transação: " . $e->getMessage());
        error_log("SQL State: " . $e->errorInfo[0]);
        error_log("Error Code: " . $e->errorInfo[1]);
        error_log("Error Message: " . $e->errorInfo[2]);
        return ['status' => false, 'mensagem' => 'Erro ao atualizar usuário: ' . $e->getMessage()];
    }
}

// Processar formulários
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        // Cadastrar novo usuário
        if ($_POST['acao'] === 'cadastrar') {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'cpf' => $_POST['cpf'] ?? '',
                'email' => $_POST['email'] ?? '',
                'senha' => $_POST['senha'] ?? '',
                'tipo' => $_POST['tipo'] ?? 'funcionario',
                'telefone' => $_POST['telefone'] ?? '',
                'endereco' => $_POST['endereco'] ?? '',
                'data_nascimento' => $_POST['data_nascimento'] ?? null
            ];
            
            $resultado = cadastrarUsuario($dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
        
        // Editar usuário
        if ($_POST['acao'] === 'editar' && isset($_POST['id'])) {
            // Log para depuração
            error_log("Iniciando edição de usuário: " . $_POST['id']);
            
            $dados = [
                'id' => $_POST['id'],
                'pessoa_id' => $_POST['pessoa_id'],
                'nome' => $_POST['nome'] ?? '',
                'cpf' => $_POST['cpf'] ?? '',
                'email' => $_POST['email'] ?? '',
                'senha' => $_POST['senha'] ?? '',
                'tipo' => $_POST['tipo'] ?? '',
                'telefone' => $_POST['telefone'] ?? '',
                'username' => $_POST['username'] ?? '',
                'ativo' => $_POST['ativo'] ?? '1',
                'data_nascimento' => $_POST['data_nascimento'] ?? null
            ];
            
            // Log dos dados recebidos
            error_log("Dados para atualização: " . json_encode($dados));
            
            $resultado = atualizarUsuario($dados);
            
            // Log do resultado
            error_log("Resultado da atualização: " . json_encode($resultado));
            
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
        
        // Excluir usuário
        if ($_POST['acao'] === 'excluir' && isset($_POST['id'])) {
            $resultado = excluirUsuario($_POST['id']);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
    }
}

// Buscar usuários
$busca = $_GET['busca'] ?? '';
$usuarios = listarUsuarios($busca);
$estatisticas = obterEstatisticasUsuarios();
$estatisticasGestores = obterEstatisticasGestores();
$gestores = listarGestores();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - SIGEA</title>
    
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

    <!-- Script para toggleSidebar global -->
    <script>
        // Função SIMPLES para toggleSidebar
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const main = document.querySelector('main');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
                
                // Adicionar/remover opacidade no conteúdo principal (incluindo header)
                if (main) {
                    main.classList.toggle('content-dimmed');
                }
            }
        };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="global-theme.css" rel="stylesheet">
    <!-- User Profile Modal CSS -->
    
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

        /* Classe para reduzir opacidade do conteúdo principal quando menu está aberto */
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
            
            /* Header mobile */
            header {
                padding: 0.75rem 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 0.5rem;
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

    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden mobile-menu-overlay lg:hidden"></div>
    
    <!-- Sidebar -->
    <?php if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'ADM') { ?>
        <?php include('components/sidebar_adm.php'); ?>
    <?php } else { ?>
        <!-- Sidebar padrão para outros tipos de usuário -->
        <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg sidebar-transition z-50 lg:translate-x-0 sidebar-mobile">
            <!-- Logo e Header -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-10 h-10 object-contain">
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">SIGEA</h1>
                        <p class="text-xs text-gray-500">Maranguape</p>
                    </div>
                </div>
            </div>

            <!-- User Info -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center flex-shrink-0" style="aspect-ratio: 1; min-width: 2.5rem; min-height: 2.5rem; overflow: hidden;">
                        <span class="text-sm font-bold text-white">
                            <?php
                            $nome = $_SESSION['nome'] ?? '';
                            $iniciais = '';
                            if (strlen($nome) >= 2) {
                                $iniciais = strtoupper(substr($nome, 0, 2));
                            } elseif (strlen($nome) == 1) {
                                $iniciais = strtoupper($nome);
                            } else {
                                $iniciais = 'US';
                            }
                            echo $iniciais;
                            ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800"><?= $_SESSION['nome'] ?? 'Usuário' ?></p>
                        <p class="text-xs text-gray-500"><?= $_SESSION['tipo'] ?? 'Funcionário' ?></p>
                    </div>
                </div>
            </div>

            <nav class="p-4 overflow-y-auto sidebar-nav" style="max-height: calc(100vh - 200px); scroll-behavior: smooth;">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['tipo'] === 'GESTAO') { ?>
                    <li>
                        <a href="gestao_escolar.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            <span>Gestão Escolar</span>
                        </a>
                    </li>
                    <?php } ?>
                    <?php if (isset($_SESSION['Gerenciador de Usuarios'])) { ?>
                    <li>
                        <a href="../../subsystems/gerenciador_usuario/index.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>Gerenciador de Usuários</span>
                        </a>
                    </li>
                    <?php } ?>
                    <li>
                        <button onclick="window.confirmLogout()" class="menu-item w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span>Sair</span>
                        </button>
                    </li>
                </ul>
            </nav>
        </aside>
    <?php } ?>

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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Usuários</h1>
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
                        
                        <!-- User Profile Button -->
                        <button onclick="window.openUserProfile()" class="p-2 text-gray-600 bg-gray-100 rounded-full hover:bg-gray-200 transition-colors cursor-pointer" title="Perfil do Usuário">
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
            
            <!-- Cards de Estatísticas de Status -->
            <div class="mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total de Usuários -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total de Usuários</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $estatisticas['total'] ?? 0; ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Usuários Ativos -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Usuários Ativos</p>
                            <p class="text-3xl font-bold text-green-600"><?php echo $estatisticas['ativos'] ?? 0; ?></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php 
                                $percentualAtivos = $estatisticas['total'] > 0 ? round(($estatisticas['ativos'] / $estatisticas['total']) * 100, 1) : 0;
                                echo $percentualAtivos . '% do total';
                                ?>
                            </p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Usuários Bloqueados -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Usuários Bloqueados</p>
                            <p class="text-3xl font-bold text-red-600"><?php echo $estatisticas['bloqueados'] ?? 0; ?></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php 
                                $percentualBloqueados = $estatisticas['total'] > 0 ? round(($estatisticas['bloqueados'] / $estatisticas['total']) * 100, 1) : 0;
                                echo $percentualBloqueados . '% do total';
                                ?>
                            </p>
                        </div>
                        <div class="bg-red-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Último Login -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500 hover:shadow-lg transition-shadow duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Taxa de Atividade</p>
                            <p class="text-3xl font-bold text-purple-600">
                                <?php 
                                $taxaAtividade = $estatisticas['total'] > 0 ? round(($estatisticas['ativos'] / $estatisticas['total']) * 100, 0) : 0;
                                echo $taxaAtividade . '%';
                                ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Usuários ativos</p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Distribuição por Tipo -->
            <div class="mb-8 bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 text-primary-green mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Distribuição por Tipo de Usuário
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600"><?php echo $estatisticas['adm'] ?? 0; ?></p>
                        <p class="text-xs text-gray-600 mt-1">Administrador</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600"><?php echo $estatisticas['gestao'] ?? 0; ?></p>
                        <p class="text-xs text-gray-600 mt-1">Gestão</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-green-600"><?php echo $estatisticas['professor'] ?? 0; ?></p>
                        <p class="text-xs text-gray-600 mt-1">Professor</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $estatisticas['aluno'] ?? 0; ?></p>
                        <p class="text-xs text-gray-600 mt-1">Aluno</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-pink-600"><?php echo $estatisticas['nutricionista'] ?? 0; ?></p>
                        <p class="text-xs text-gray-600 mt-1">Nutricionista</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-orange-600"><?php echo $estatisticas['adm_merenda'] ?? 0; ?></p>
                        <p class="text-xs text-gray-600 mt-1">Adm. Merenda</p>
                    </div>
                </div>
            </div>
            
            <!-- Status Detalhado dos Gestores -->
            <div class="mb-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                        <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Status dos Usuários Gestores
                    </h3>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                        <?php echo $estatisticasGestores['total_gestores'] ?? 0; ?> Gestor(es)
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total de Gestores</p>
                                <p class="text-2xl font-bold text-blue-600"><?php echo $estatisticasGestores['total_gestores'] ?? 0; ?></p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-2">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Gestores Ativos</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo $estatisticasGestores['gestores_ativos'] ?? 0; ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php 
                                    $percentualAtivos = $estatisticasGestores['total_gestores'] > 0 ? round(($estatisticasGestores['gestores_ativos'] / $estatisticasGestores['total_gestores']) * 100, 1) : 0;
                                    echo $percentualAtivos . '%';
                                    ?>
                                </p>
                            </div>
                            <div class="bg-green-100 rounded-full p-2">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Gestores Bloqueados</p>
                                <p class="text-2xl font-bold text-red-600"><?php echo $estatisticasGestores['gestores_bloqueados'] ?? 0; ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php 
                                    $percentualBloqueados = $estatisticasGestores['total_gestores'] > 0 ? round(($estatisticasGestores['gestores_bloqueados'] / $estatisticasGestores['total_gestores']) * 100, 1) : 0;
                                    echo $percentualBloqueados . '%';
                                    ?>
                                </p>
                            </div>
                            <div class="bg-red-100 rounded-full p-2">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Com Acesso</p>
                                <p class="text-2xl font-bold text-purple-600"><?php echo $estatisticasGestores['gestores_com_login'] ?? 0; ?></p>
                                <p class="text-xs text-gray-500 mt-1">Já fizeram login</p>
                            </div>
                            <div class="bg-purple-100 rounded-full p-2">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Gestores -->
                <?php if (!empty($gestores)): ?>
                <div class="mt-6">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Lista de Gestores</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg shadow-sm">
                            <thead class="bg-blue-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Username</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Último Login</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($gestores as $gestor): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($gestor['nome']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($gestor['username']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($gestor['email']); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $gestor['bloqueado'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo $gestor['bloqueado'] ? 'Bloqueado' : 'Ativo'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?php 
                                        if ($gestor['ultimo_login']) {
                                            $dataLogin = new DateTime($gestor['ultimo_login']);
                                            echo $dataLogin->format('d/m/Y H:i');
                                        } else {
                                            echo '<span class="text-gray-400">Nunca acessou</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    <p class="text-gray-500">Nenhum gestor cadastrado no sistema</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <div class="flex space-x-8">
                    <button onclick="showTab('tab-listar')" class="tab-btn tab-active py-4 px-1 focus:outline-none">
                        Listar Usuários
                    </button>
                    <button onclick="showTab('tab-cadastrar')" class="tab-btn py-4 px-1 focus:outline-none">
                        Cadastrar Usuário
                    </button>
                </div>
            </div>
            
            <!-- Tab Contents -->
            <div id="tab-listar" class="tab-content active">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Lista de Usuários</h2>
                    
                    <!-- Search Box -->
                    <form method="GET" class="mb-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" name="busca" placeholder="Buscar por nome, CPF ou email..." 
                                   value="<?php echo htmlspecialchars($busca); ?>"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-green focus:border-primary-green">
                        </div>
                    </form>
                    
                    <!-- Tabela de Usuários -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Criação</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Nenhum usuário encontrado
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-primary-green rounded-full flex items-center justify-center">
                                                    <span class="text-white font-medium"><?php echo substr($usuario['nome'], 0, 1); ?></span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo !empty($usuario['telefone']) ? htmlspecialchars($usuario['telefone']) : 'Não informado'; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($usuario['username']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($usuario['cpf']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($usuario['email']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php 
                                                    switch($usuario['tipo']) {
                                                        case 'ADM': echo 'bg-purple-100 text-purple-800'; break;
                                                        case 'GESTAO': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'PROFESSOR': echo 'bg-green-100 text-green-800'; break;
                                                        case 'ALUNO': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'NUTRICIONISTA': echo 'bg-pink-100 text-pink-800'; break;
                                                        case 'ADM_MERENDA': echo 'bg-orange-100 text-orange-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                ?>">
                                                <?php echo htmlspecialchars($usuario['tipo']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $usuario['bloqueado'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                                <?php echo $usuario['bloqueado'] ? 'Bloqueado' : 'Ativo'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button class="text-blue-600 hover:text-blue-900" onclick="editarUsuario(<?php echo $usuario['id']; ?>)">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="abrirModalExclusaoUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>')" class="text-red-600 hover:text-red-900">
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
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Cadastrar Novo Usuário</h2>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="acao" value="cadastrar">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                                <input type="text" id="nome" name="nome" required
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                            </div>
                            
                            <div>
                                <label for="cpf" class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                                <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" required
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" id="email" name="email" required
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                            </div>
                            
                            <div>
                                <label for="telefone" class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                            </div>
                            
                            <div>
                                <label for="endereco" class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
                                <input type="text" id="endereco" name="endereco"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                            </div>
                            
                            <div>
                                <label for="data_nascimento" class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
                                <input type="date" id="data_nascimento" name="data_nascimento"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                            </div>
                            
                            <div>
                                <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">Senha *</label>
                                <input type="password" id="senha" name="senha" required
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                            </div>
                            
                            <div>
                                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Usuário *</label>
                                <select id="tipo" name="tipo" required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                    <option value="GESTAO">Gestão</option>
                                    <option value="PROFESSOR">Professor</option>
                                    <option value="ALUNO">Aluno</option>
                                    <option value="NUTRICIONISTA">Nutricionista</option>
                                    <option value="ADM_MERENDA">Administrador de Merenda</option>
                                    <option value="ADM">Administrador</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="reset" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green">
                                Limpar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green">
                                Cadastrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal de Edição de Usuário (Full Screen) -->
    <div id="editarUsuarioModal" class="fixed inset-0 bg-white z-50 hidden">
        <div class="h-full w-full overflow-hidden">
            <div class="bg-white h-full w-full overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-primary-green text-white p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-white">Editar Usuário</h3>
                                <p class="text-green-100">Modifique as informações do usuário</p>
                            </div>
                        </div>
                        <button onclick="fecharModalEdicao()" class="p-2 hover:bg-white hover:bg-opacity-20 rounded-full transition-colors duration-200">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="p-6 overflow-y-auto h-[calc(100vh-120px)]">
                    <form id="formEditarUsuario" method="POST" class="space-y-8">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" id="edit_id" name="id">
                        <input type="hidden" id="edit_pessoa_id" name="pessoa_id">
                        
                        <!-- Informações Pessoais -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-primary-green mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Informações Pessoais
                            </h4>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label for="edit_nome" class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                                    <input type="text" id="edit_nome" name="nome" required
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors duration-200">
                                </div>
                                
                                <div>
                                    <label for="edit_cpf" class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                                    <input type="text" id="edit_cpf" name="cpf" placeholder="000.000.000-00" required
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors duration-200">
                                </div>
                                
                                <div>
                                    <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                    <input type="email" id="edit_email" name="email" required
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors duration-200">
                                </div>
                                
                                <div>
                                    <label for="edit_telefone" class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                                    <input type="text" id="edit_telefone" name="telefone" placeholder="(00) 00000-0000"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors duration-200">
                                </div>
                                
                                <div>
                                    <label for="edit_data_nascimento" class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
                                    <input type="date" id="edit_data_nascimento" name="data_nascimento"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors duration-200">
                                </div>
                            </div>
                        </div>

                        <!-- Informações de Acesso -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-primary-green mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                Informações de Acesso
                            </h4>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label for="edit_username" class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                                    <input type="text" id="edit_username" name="username" required
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors duration-200">
                                </div>
                                
                                <div>
                                    <label for="edit_senha" class="block text-sm font-medium text-gray-700 mb-2">Nova Senha (deixe em branco para manter a atual)</label>
                                    <input type="password" id="edit_senha" name="senha"
                                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors duration-200">
                                </div>
                                
                                <div>
                                    <label for="edit_tipo" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Usuário *</label>
                                    <select id="edit_tipo" name="tipo" required
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors duration-200">
                                        <option value="GESTAO">Gestão</option>
                                        <option value="PROFESSOR">Professor</option>
                                        <option value="ALUNO">Aluno</option>
                                        <option value="NUTRICIONISTA">Nutricionista</option>
                                        <option value="ADM_MERENDA">Administrador de Merenda</option>
                                        <option value="ADM">Administrador</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="edit_ativo" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select id="edit_ativo" name="ativo" required
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-colors duration-200">
                                        <option value="1">Ativo</option>
                                        <option value="0">Bloqueado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal Actions -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <button type="button" onclick="fecharModalEdicao()" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green transition-colors duration-200 font-medium">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green transition-colors duration-200 font-medium">
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Exclusão de Usuário -->
    <div id="modalExclusaoUsuario" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirmar Exclusão</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Tem certeza que deseja excluir o usuário <strong id="nomeUsuarioExclusao"></strong>?
                </p>
                <p class="text-xs text-red-600 mb-6">
                    ⚠️ Esta ação não pode ser desfeita. Todos os dados do usuário serão perdidos permanentemente.
                </p>
                
                <div class="flex space-x-3 justify-center">
                    <button onclick="fecharModalExclusaoUsuario()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                        Cancelar
                    </button>
                    <form id="formExclusaoUsuario" method="POST" class="inline">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" id="idUsuarioExclusao">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Sim, Excluir
                        </button>
                    </form>
                </div>
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
        
        // Função para abrir o modal de edição e carregar os dados do usuário
        function editarUsuario(id) {
            // Fazer uma requisição AJAX para obter os dados do usuário
            fetch(`../../Controllers/gestao/UsuarioController.php?id=${id}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        // Preencher o formulário com os dados do usuário
                        document.getElementById('edit_id').value = data.usuario.id;
                        document.getElementById('edit_pessoa_id').value = data.usuario.pessoa_id;
                        document.getElementById('edit_nome').value = data.usuario.nome;
                        document.getElementById('edit_cpf').value = data.usuario.cpf;
                        document.getElementById('edit_email').value = data.usuario.email;
                        document.getElementById('edit_telefone').value = data.usuario.telefone || '';
                        document.getElementById('edit_username').value = data.usuario.username;
                        document.getElementById('edit_tipo').value = data.usuario.role;
                        document.getElementById('edit_ativo').value = data.usuario.ativo;
                        
                        // Formatar a data de nascimento para o formato do input date (YYYY-MM-DD)
                        if (data.usuario.data_nascimento) {
                            const dataNascimento = new Date(data.usuario.data_nascimento);
                            const ano = dataNascimento.getFullYear();
                            const mes = String(dataNascimento.getMonth() + 1).padStart(2, '0');
                            const dia = String(dataNascimento.getDate()).padStart(2, '0');
                            document.getElementById('edit_data_nascimento').value = `${ano}-${mes}-${dia}`;
                        } else {
                            document.getElementById('edit_data_nascimento').value = '';
                        }
                        
                        // Limpar o campo de senha, pois não queremos mostrar a senha atual
                        document.getElementById('edit_senha').value = '';
                        
                        // Abrir o modal
                        const modal = document.getElementById('editarUsuarioModal');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    } else {
                        alert('Erro ao obter dados do usuário: ' + data.mensagem);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição:', error);
                    alert('Erro ao obter dados do usuário. Verifique o console para mais detalhes.');
                });
        }
        
        // Função para fechar o modal de edição
        function fecharModalEdicao() {
            const modal = document.getElementById('editarUsuarioModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        // Função para abrir modal de exclusão de usuário
        function abrirModalExclusaoUsuario(id, nome) {
            document.getElementById('idUsuarioExclusao').value = id;
            document.getElementById('nomeUsuarioExclusao').textContent = nome;
            const modal = document.getElementById('modalExclusaoUsuario');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        // Função para fechar modal de exclusão de usuário
        function fecharModalExclusaoUsuario() {
            const modal = document.getElementById('modalExclusaoUsuario');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        // Fechar modal clicando fora dele
        document.getElementById('modalExclusaoUsuario').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalExclusaoUsuario();
            }
        });
        
        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})(\d{0,3}).*/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})(\d{0,3}).*/, '$1.$2');
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
        
        // Função toggleSidebar já definida globalmente

        // Close sidebar when clicking overlay
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('mobileOverlay');
            if (overlay) {
                overlay.addEventListener('click', function() {
                    const sidebar = document.getElementById('sidebar');
                    const main = document.querySelector('main');
                    
                    if (sidebar && sidebar.classList.contains('open')) {
                        sidebar.classList.remove('open');
                        overlay.classList.add('hidden');
                        
                        // Remover opacidade do conteúdo principal
                        if (main) {
                            main.classList.remove('content-dimmed');
                        }
                    }
                });
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
                        window.toggleSidebar();
                    }
                });
            });
            
            // Adicionar event listener para o formulário de edição
            const formEditarUsuario = document.getElementById('formEditarUsuario');
            if (formEditarUsuario) {
                formEditarUsuario.addEventListener('submit', function(event) {
                    // Log para depuração
                    console.log('Formulário de edição enviado');
                    
                    // Verificar se todos os campos obrigatórios estão preenchidos
                    const camposObrigatorios = ['edit_nome', 'edit_cpf', 'edit_email', 'edit_username', 'edit_tipo'];
                    let camposValidos = true;
                    
                    camposObrigatorios.forEach(campo => {
                        const input = document.getElementById(campo);
                        if (!input.value.trim()) {
                            camposValidos = false;
                            input.classList.add('border-red-500');
                        } else {
                            input.classList.remove('border-red-500');
                        }
                    });
                    
                    if (!camposValidos) {
                        event.preventDefault();
                        alert('Por favor, preencha todos os campos obrigatórios.');
                        return false;
                    }
                    
                    // Confirmar envio
                    if (!confirm('Tem certeza que deseja salvar as alterações?')) {
                        event.preventDefault();
                        return false;
                    }
                    
                    // Permitir o envio do formulário
                    return true;
                });
            }
        });

        // User Profile Modal Functions - Removed (using component JS file instead)



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
        
        // Funções para sidebar e logout
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
        
        window.openUserProfile = function() {
            const modal = document.getElementById('userProfileModal');
            if (modal) {
                modal.style.display = 'block';
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevenir scroll do body
            }
        };
        
        window.closeUserProfile = function() {
            const modal = document.getElementById('userProfileModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto'; // Restaurar scroll do body
            }
        };
        
        // Fechar modal ao pressionar ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.closeUserProfile();
            }
        });
        
        // Fechar sidebar ao clicar no overlay
        const overlay = document.getElementById('mobileOverlay');
        if (overlay) {
            overlay.addEventListener('click', function() {
                window.toggleSidebar();
            });
        }
        
        // Manter posição do scroll do sidebar ao navegar
        (function() {
            const sidebarNav = document.querySelector('.sidebar-nav') || document.querySelector('nav');
            if (!sidebarNav) return;
            
            // Salvar posição do scroll antes de navegar
            const sidebarLinks = sidebarNav.querySelectorAll('a[href]');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Salvar posição do scroll no sessionStorage
                    sessionStorage.setItem('sidebarScroll', sidebarNav.scrollTop);
                });
            });
            
            // Restaurar posição do scroll após carregar a página
            window.addEventListener('load', function() {
                const savedScroll = sessionStorage.getItem('sidebarScroll');
                if (savedScroll !== null) {
                    sidebarNav.scrollTop = parseInt(savedScroll, 10);
                }
            });
            
            // Também restaurar no DOMContentLoaded para ser mais rápido
            document.addEventListener('DOMContentLoaded', function() {
                const savedScroll = sessionStorage.getItem('sidebarScroll');
                if (savedScroll !== null) {
                    sidebarNav.scrollTop = parseInt(savedScroll, 10);
                }
            });
        })();
    </script>
    
    <!-- Modal de Logout -->
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
    
    <!-- Modal Full Screen de Perfil do Usuário -->
    <div id="userProfileModal" class="fixed inset-0 bg-white z-[70] hidden overflow-y-auto" style="display: none;">
        <!-- Header do Modal -->
        <div class="sticky top-0 bg-gradient-to-r from-primary-green to-green-700 text-white shadow-lg z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="window.closeUserProfile()" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <h2 class="text-2xl font-bold">Meu Perfil</h2>
                    </div>
                    <button onclick="window.closeUserProfile()" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo do Modal -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($dadosUsuario): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Coluna Esquerda - Informações Principais -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Card de Informações Pessoais -->
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 flex items-center space-x-2">
                                <svg class="w-6 h-6 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Informações Pessoais</span>
                            </h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                                <p class="text-gray-900 font-medium"><?= htmlspecialchars($dadosUsuario['nome'] ?? 'N/A') ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                                <p class="text-gray-900"><?= !empty($dadosUsuario['cpf']) ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $dadosUsuario['cpf']) : 'N/A' ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
                                <p class="text-gray-900"><?= !empty($dadosUsuario['data_nascimento']) ? date('d/m/Y', strtotime($dadosUsuario['data_nascimento'])) : 'N/A' ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                                <p class="text-gray-900"><?= !empty($dadosUsuario['sexo']) ? ($dadosUsuario['sexo'] === 'M' ? 'Masculino' : ($dadosUsuario['sexo'] === 'F' ? 'Feminino' : 'Outro')) : 'N/A' ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card de Contato -->
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center space-x-2">
                            <svg class="w-6 h-6 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>Informações de Contato</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                <p class="text-gray-900"><?= htmlspecialchars($dadosUsuario['email'] ?? 'N/A') ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                                <p class="text-gray-900"><?= !empty($dadosUsuario['telefone']) ? preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $dadosUsuario['telefone']) : 'N/A' ?></p>
                            </div>
                            <?php if (!empty($dadosUsuario['endereco'])): ?>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                                <p class="text-gray-900">
                                    <?= htmlspecialchars($dadosUsuario['endereco']) ?>
                                    <?php if (!empty($dadosUsuario['cidade'])): ?>
                                        , <?= htmlspecialchars($dadosUsuario['cidade']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($dadosUsuario['estado'])): ?>
                                        - <?= htmlspecialchars($dadosUsuario['estado']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($dadosUsuario['cep'])): ?>
                                        | CEP: <?= preg_replace('/(\d{5})(\d{3})/', '$1-$2', $dadosUsuario['cep']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Card de Informações da Conta -->
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center space-x-2">
                            <svg class="w-6 h-6 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Informações da Conta</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                <p class="text-gray-900 font-mono"><?= htmlspecialchars($dadosUsuario['username'] ?? 'N/A') ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Usuário</label>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php 
                                    $tipo = $dadosUsuario['tipo'] ?? '';
                                    echo $tipo === 'ADM' ? 'bg-purple-100 text-purple-800' : 
                                         ($tipo === 'GESTAO' ? 'bg-blue-100 text-blue-800' : 
                                         ($tipo === 'PROFESSOR' ? 'bg-green-100 text-green-800' : 
                                         ($tipo === 'ADM_MERENDA' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'))); 
                                ?>">
                                    <?= htmlspecialchars($tipo) ?>
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status da Conta</label>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= ($dadosUsuario['ativo'] ?? 0) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= ($dadosUsuario['ativo'] ?? 0) ? 'Ativo' : 'Bloqueado' ?>
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Criação</label>
                                <p class="text-gray-900"><?= !empty($dadosUsuario['data_criacao']) ? date('d/m/Y H:i', strtotime($dadosUsuario['data_criacao'])) : 'N/A' ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Último Login</label>
                                <p class="text-gray-900"><?= !empty($dadosUsuario['ultimo_login']) ? date('d/m/Y H:i', strtotime($dadosUsuario['ultimo_login'])) : 'Nunca' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Coluna Direita - Avatar e Ações -->
                <div class="space-y-6">
                    <!-- Card de Avatar -->
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200 text-center">
                        <div class="flex justify-center mb-4">
                            <div class="w-32 h-32 bg-gradient-to-br from-primary-green to-green-700 rounded-full flex items-center justify-center text-white text-4xl font-bold shadow-lg">
                                <?php
                                $nome = $dadosUsuario['nome'] ?? 'U';
                                $iniciais = '';
                                if (strlen($nome) >= 2) {
                                    $iniciais = strtoupper(substr($nome, 0, 2));
                                } elseif (strlen($nome) == 1) {
                                    $iniciais = strtoupper($nome);
                                } else {
                                    $iniciais = 'US';
                                }
                                echo $iniciais;
                                ?>
                            </div>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2"><?= htmlspecialchars($dadosUsuario['nome'] ?? 'Usuário') ?></h3>
                        <p class="text-sm text-gray-600 mb-4"><?= htmlspecialchars($dadosUsuario['tipo'] ?? 'Funcionário') ?></p>
                        <div class="pt-4 border-t border-gray-200">
                            <button class="w-full px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                                Editar Perfil
                            </button>
                        </div>
                    </div>
                    
                    <!-- Card de Ações Rápidas -->
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ações Rápidas</h3>
                        <div class="space-y-2">
                            <button class="w-full text-left px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center space-x-3">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                <span class="text-gray-700">Alterar Senha</span>
                            </button>
                            <button class="w-full text-left px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center space-x-3">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="text-gray-700">Configurações</span>
                            </button>
                            <button onclick="window.closeUserProfile(); window.confirmLogout();" class="w-full text-left px-4 py-3 rounded-lg hover:bg-red-50 transition-colors flex items-center space-x-3 text-red-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span>Sair do Sistema</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Erro ao carregar perfil</h3>
                <p class="text-gray-600">Não foi possível carregar as informações do seu perfil.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- User Profile Modal Component -->

</body>
</html>