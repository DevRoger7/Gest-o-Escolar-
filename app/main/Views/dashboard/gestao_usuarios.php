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
        
        // Inserir na tabela pessoa
        $stmt = $conn->prepare("INSERT INTO pessoa (nome, cpf, email, telefone, data_nascimento, tipo) 
                                VALUES (:nome, :cpf, :email, :telefone, :data_nascimento, 'FUNCIONARIO')");
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':cpf', $cpfLimpo);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':data_nascimento', $dados['data_nascimento']);
        
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
    
    // Remover caracteres especiais do CPF (pontos e traço)
    $cpfLimpo = preg_replace('/[^0-9]/', '', $dados['cpf']);
    
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
        $stmt->bindParam(':data_nascimento', $dados['data_nascimento']);
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
        
        return ['status' => true, 'mensagem' => 'Usuário atualizado com sucesso!'];
    } catch (PDOException $e) {
        // Reverter transação em caso de erro
        $conn->rollBack();
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
            
            $resultado = atualizarUsuario($dados);
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - SIGAE</title>
    
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
                    <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php if ($_SESSION['tipo'] === 'GESTAO') { ?>
                <li id="gestao-menu">
                    <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Gestão Escolar</span>
                    </a>
                </li>
                <?php } ?>
                <?php if ($_SESSION['tipo'] === 'ADM_MERENDA') { ?>
                <li id="merenda-menu">
                    <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
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
                    <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Relatórios</span>
                    </a>
                </li>
                <?php } ?>
                <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                <li id="escolas-menu">
                    <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <span>Escolas</span>
                    </a>
                </li>
                <li id="usuarios-menu">
                    <a href="gestao_usuarios.php" class="menu-item active flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span>Usuários</span>
                    </a>
                </li>
                <li id="estoque-central-menu">
                    <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
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
            <a href="../../Models/sessao/sessions.php?sair" class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Sair</span>
            </a>
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
    
    <!-- Modal de Edição de Usuário -->
    <div id="editarUsuarioModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-3xl w-full mx-4 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Editar Usuário</h3>
                <button onclick="fecharModalEdicao()" class="p-2 hover:bg-gray-100 rounded-full transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="formEditarUsuario" method="POST" class="space-y-6">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" id="edit_id" name="id">
                <input type="hidden" id="edit_pessoa_id" name="pessoa_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="edit_nome" class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                        <input type="text" id="edit_nome" name="nome" required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                    </div>
                    
                    <div>
                        <label for="edit_cpf" class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                        <input type="text" id="edit_cpf" name="cpf" placeholder="000.000.000-00" required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                    </div>
                    
                    <div>
                        <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" id="edit_email" name="email" required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                    </div>
                    
                    <div>
                        <label for="edit_telefone" class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                        <input type="text" id="edit_telefone" name="telefone" placeholder="(00) 00000-0000"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                    </div>
                    
                    <div>
                        <label for="edit_username" class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                        <input type="text" id="edit_username" name="username" required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                    </div>
                    
                    <div>
                        <label for="edit_data_nascimento" class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
                        <input type="date" id="edit_data_nascimento" name="data_nascimento"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                    </div>
                    
                    <div>
                        <label for="edit_senha" class="block text-sm font-medium text-gray-700 mb-2">Nova Senha (deixe em branco para manter a atual)</label>
                        <input type="password" id="edit_senha" name="senha"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                    </div>
                    
                    <div>
                        <label for="edit_tipo" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Usuário *</label>
                        <select id="edit_tipo" name="tipo" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
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
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                            <option value="1">Ativo</option>
                            <option value="0">Bloqueado</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="fecharModalEdicao()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Exclusão de Usuário -->
    <div id="modalExclusaoUsuario" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
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
            fetch(`obter_usuario.php?id=${id}`)
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
                        document.getElementById('editarUsuarioModal').classList.remove('hidden');
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
            document.getElementById('editarUsuarioModal').classList.add('hidden');
        }
        
        // Função para abrir modal de exclusão de usuário
        function abrirModalExclusaoUsuario(id, nome) {
            document.getElementById('idUsuarioExclusao').value = id;
            document.getElementById('nomeUsuarioExclusao').textContent = nome;
            document.getElementById('modalExclusaoUsuario').classList.remove('hidden');
        }
        
        // Função para fechar modal de exclusão de usuário
        function fecharModalExclusaoUsuario() {
            document.getElementById('modalExclusaoUsuario').classList.add('hidden');
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