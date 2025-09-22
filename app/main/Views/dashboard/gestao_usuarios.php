<?php
session_start();

// Configuração do banco de dados - ajustando caminho relativo
require_once '../../config/Database.php';

// Função para listar usuários
function listarUsuarios($busca = '') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT u.id, u.tipo, u.ultimo_login, u.bloqueado, u.data_criacao,
                       p.nome, p.cpf, p.email, p.telefone, p.endereco, p.data_nascimento
                FROM usuario u 
                INNER JOIN pessoa p ON u.pessoa_id = p.id";
        
        $params = [];
        if (!empty($busca)) {
            $sql .= " WHERE p.nome LIKE ? OR p.cpf LIKE ? OR p.email LIKE ?";
            $params = ["%$busca%", "%$busca%", "%$busca%"];
        }
        
        $sql .= " ORDER BY p.nome";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Função para cadastrar usuário
function cadastrarUsuario($dados) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar se CPF já existe
        $stmt = $conn->prepare("SELECT id FROM pessoa WHERE cpf = ?");
        $stmt->execute([$dados['cpf']]);
        if ($stmt->fetch()) {
            return ['sucesso' => false, 'erro' => 'CPF já cadastrado'];
        }
        
        // Verificar se email já existe
        $stmt = $conn->prepare("SELECT id FROM pessoa WHERE email = ?");
        $stmt->execute([$dados['email']]);
        if ($stmt->fetch()) {
            return ['sucesso' => false, 'erro' => 'Email já cadastrado'];
        }
        
        $conn->beginTransaction();
        
        // Inserir pessoa
        $stmt = $conn->prepare("INSERT INTO pessoa (nome, cpf, email, telefone, endereco, data_nascimento) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $dados['nome'],
            $dados['cpf'],
            $dados['email'],
            $dados['telefone'] ?? null,
            $dados['endereco'] ?? null,
            $dados['data_nascimento'] ?? null
        ]);
        
        $pessoa_id = $conn->lastInsertId();
        
        // Inserir usuário
        $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuario (pessoa_id, senha, tipo) VALUES (?, ?, ?)");
        $stmt->execute([$pessoa_id, $senha_hash, $dados['tipo']]);
        
        $conn->commit();
        
        return ['sucesso' => true, 'usuario_id' => $conn->lastInsertId()];
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        return ['sucesso' => false, 'erro' => 'Erro ao cadastrar: ' . $e->getMessage()];
    }
}

// Função para excluir usuário
function excluirUsuario($id) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Buscar pessoa_id
        $stmt = $conn->prepare("SELECT pessoa_id FROM usuario WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            return ['sucesso' => false, 'erro' => 'Usuário não encontrado'];
        }
        
        $conn->beginTransaction();
        
        // Excluir usuário
        $stmt = $conn->prepare("DELETE FROM usuario WHERE id = ?");
        $stmt->execute([$id]);
        
        // Excluir pessoa
        $stmt = $conn->prepare("DELETE FROM pessoa WHERE id = ?");
        $stmt->execute([$usuario['pessoa_id']]);
        
        $conn->commit();
        
        return ['sucesso' => true];
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        return ['sucesso' => false, 'erro' => 'Erro ao excluir: ' . $e->getMessage()];
    }
}

// Processar ações
$mensagem = '';
$tipo_mensagem = '';

if ($_POST) {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'cadastrar':
                $resultado = cadastrarUsuario($_POST);
                if ($resultado['sucesso']) {
                    $mensagem = 'Usuário cadastrado com sucesso!';
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = $resultado['erro'];
                    $tipo_mensagem = 'error';
                }
                break;
                
            case 'excluir':
                $resultado = excluirUsuario($_POST['id']);
                if ($resultado['sucesso']) {
                    $mensagem = 'Usuário excluído com sucesso!';
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = $resultado['erro'];
                    $tipo_mensagem = 'error';
                }
                break;
        }
    }
}

// Buscar usuários
$busca = $_GET['busca'] ?? '';
$usuarios = listarUsuarios($busca);

// Verificar se há usuários cadastrados
if (empty($usuarios)) {
    // Inserir um usuário de teste para verificar se a tabela está funcionando
    $dados = [
        'nome' => 'Usuário Teste',
        'cpf' => '123.456.789-00',
        'email' => 'teste@exemplo.com',
        'senha' => '123456',
        'tipo' => 'admin'
    ];
    cadastrarUsuario($dados);
    
    // Buscar usuários novamente
    $usuarios = listarUsuarios($busca);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - SIGAE</title>

    <!-- Favicon -->
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <link rel="shortcut icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <link rel="apple-touch-icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png">

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
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Back Button -->
                <a href="dashboard.php" class="flex items-center space-x-2 text-gray-600 hover:text-primary-green transition-colors duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span class="font-medium">Voltar ao Dashboard</span>
                </a>

                <!-- Page Title -->
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-primary-green rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Gestão de Usuários</h1>
                        <p class="text-sm text-gray-500">Sistema de Gestão Escolar</p>
                    </div>
                </div>

                <!-- User Info -->
                <div class="flex items-center space-x-3">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo $_SESSION['nome'] ?? 'Usuário'; ?></p>
                        <p class="text-xs text-gray-500"><?php echo ucfirst($_SESSION['tipo'] ?? 'admin'); ?></p>
                    </div>
                    <div class="w-8 h-8 bg-primary-green rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-medium"><?php echo substr($_SESSION['nome'] ?? 'U', 0, 1); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container with Sidebar -->
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Menu</h2>
                <button id="closeSidebar" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <nav class="mt-6 px-3">
                <div class="space-y-2">
                    <!-- Gestão de Usuários -->
                    <div class="mb-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Gestão de Usuários</h3>
                        <a href="#" onclick="showTab('listar')" class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 hover:text-primary-green transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            Listar Usuários
                        </a>
                        <a href="#" onclick="showTab('cadastrar')" class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 hover:text-primary-green transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Cadastrar Usuário
                        </a>
                    </div>

                    <!-- Navegação Principal -->
                    <div class="mb-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Navegação</h3>
                        <a href="dashboard.php" class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 hover:text-primary-green transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                            </svg>
                            Dashboard
                        </a>
                        <a href="#" class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 hover:text-primary-green transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Estoque
                        </a>
                        <a href="#" class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 hover:text-primary-green transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            Biblioteca
                        </a>
                        <a href="#" class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 hover:text-primary-green transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Relatórios
                        </a>
                    </div>

                    <!-- Configurações -->
                    <div class="mb-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Sistema</h3>
                        <a href="#" class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100 hover:text-primary-green transition-colors duration-200">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Configurações
                        </a>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Overlay for mobile -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-40 lg:hidden hidden"></div>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-0">
            <!-- Mobile menu button -->
            <div class="lg:hidden bg-white border-b border-gray-200 px-4 py-3">
                <button id="openSidebar" class="p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-green">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Gestão de Usuários</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Alert Messages -->
        <?php if ($mensagem): ?>
            <div class="mb-6">
                <div class="<?php echo $tipo_mensagem === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?> px-4 py-3 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php if ($tipo_mensagem === 'success'): ?>
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            <?php else: ?>
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium"><?php echo $mensagem; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 relative overflow-hidden hover-lift fade-in-up">
                <div class="absolute top-0 right-0 w-20 h-20 bg-blue-100 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-1"><?php echo count($usuarios); ?></h3>
                    <p class="text-gray-600 text-sm">Total de Usuários</p>
                </div>
            </div>

            <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 relative overflow-hidden hover-lift fade-in-up" style="animation-delay: 0.1s">
                <div class="absolute top-0 right-0 w-20 h-20 bg-red-100 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-100 rounded-xl">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-1"><?php echo count(array_filter($usuarios, fn($u) => $u['tipo'] === 'admin')); ?></h3>
                    <p class="text-gray-600 text-sm">Administradores</p>
                </div>
            </div>

            <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 relative overflow-hidden hover-lift fade-in-up" style="animation-delay: 0.2s">
                <div class="absolute top-0 right-0 w-20 h-20 bg-green-100 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-100 rounded-xl">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-1"><?php echo count(array_filter($usuarios, fn($u) => $u['tipo'] === 'funcionario')); ?></h3>
                    <p class="text-gray-600 text-sm">Funcionários</p>
                </div>
            </div>

            <div class="card-hover bg-white rounded-2xl p-6 shadow-lg border border-gray-100 relative overflow-hidden hover-lift fade-in-up" style="animation-delay: 0.3s">
                <div class="absolute top-0 right-0 w-20 h-20 bg-purple-100 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-1"><?php echo count(array_filter($usuarios, fn($u) => $u['tipo'] === 'nutricionista')); ?></h3>
                    <p class="text-gray-600 text-sm">Nutricionistas</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 mb-8">
            <button onclick="showTab('cadastrar')" class="group bg-gradient-to-r from-primary-green to-green-600 text-white px-6 py-3 rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Novo Usuário</span>
            </button>
            
            <button onclick="showTab('listar')" class="group bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span>Listar Usuários</span>
            </button>
        </div>

        <!-- Tab Content -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <!-- Tab Listar -->
            <div id="tab-listar" class="tab-content">
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
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
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
                                            <?php echo htmlspecialchars($usuario['cpf']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($usuario['email']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php 
                                                    switch($usuario['tipo']) {
                                                        case 'admin': echo 'bg-purple-100 text-purple-800'; break;
                                                        case 'professor': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'aluno': echo 'bg-green-100 text-green-800'; break;
                                                        case 'nutricionista': echo 'bg-pink-100 text-pink-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                ?>">
                                                <?php echo ucfirst(htmlspecialchars($usuario['tipo'])); ?>
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
                                                <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                                    <input type="hidden" name="acao" value="excluir">
                                                    <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination or additional controls could be added here -->
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
                                <input type="text" id="telefone" name="telefone" placeholder="(85) 99999-9999"
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
                                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Usuário *</label>
                                <select id="tipo" name="tipo" required
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                                    <option value="">Selecione...</option>
                                    <option value="funcionario">Funcionário</option>
                                    <option value="nutricionista">Nutricionista</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">Senha *</label>
                                <input type="password" id="senha" name="senha" required minlength="6"
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green">
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-4">
                            <button type="button" onclick="showTab('listar')" 
                                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Cadastrar Usuário</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showTab(tabName) {
            // Esconder todas as tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Mostrar tab selecionada
            document.getElementById('tab-' + tabName).classList.remove('hidden');
        }

        // Mostrar tab listar por padrão
        document.addEventListener('DOMContentLoaded', function() {
            showTab('listar');
        });

        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });

        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });

        // Controle do menu lateral
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const openSidebarBtn = document.getElementById('openSidebar');
        const closeSidebarBtn = document.getElementById('closeSidebar');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        }

        openSidebarBtn.addEventListener('click', openSidebar);
        closeSidebarBtn.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);

        // Destacar item ativo do menu lateral
        function setActiveMenuItem() {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                link.classList.remove('bg-primary-green', 'text-white');
                link.classList.add('text-gray-700');
            });
        }

        // Marcar item ativo baseado na tab atual
        function updateSidebarActiveState(activeTab) {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                link.classList.remove('bg-primary-green', 'text-white');
                link.classList.add('text-gray-700');
                
                if ((activeTab === 'listar' && link.textContent.includes('Listar')) ||
                    (activeTab === 'cadastrar' && link.textContent.includes('Cadastrar'))) {
                    link.classList.add('bg-primary-green', 'text-white');
                    link.classList.remove('text-gray-700');
                }
            });
        }

        // Atualizar estado ativo quando trocar de tab
        const originalShowTab = window.showTab;
        window.showTab = function(tab) {
            originalShowTab(tab);
            updateSidebarActiveState(tab);
        };

        // Definir estado inicial
        updateSidebarActiveState('listar');
    </script>
        </div>
    </div>
</body>
</html>