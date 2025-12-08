<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'atualizar_permissoes') {
        $usuarioId = $_POST['usuario_id'] ?? null;
        $permissoes = json_decode($_POST['permissoes'] ?? '[]', true);
        
        try {
            $conn->beginTransaction();
            
            // Verificar estrutura da tabela
            $sqlCheck = "SHOW COLUMNS FROM role_permissao LIKE 'usuario_id'";
            $stmtCheck = $conn->query($sqlCheck);
            $temUsuarioId = $stmtCheck->rowCount() > 0;
            
            // Buscar role do usuário
            $sqlUsuario = "SELECT role FROM usuario WHERE id = :usuario_id";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':usuario_id', $usuarioId);
            $stmtUsuario->execute();
            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }
            
            // Remover permissões existentes
            if ($temUsuarioId) {
            $sql = "DELETE FROM role_permissao WHERE usuario_id = :usuario_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuarioId);
            $stmt->execute();
            
            // Adicionar novas permissões
            foreach ($permissoes as $permissao) {
                $sql = "INSERT INTO role_permissao (usuario_id, permissao, ativo) VALUES (:usuario_id, :permissao, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':usuario_id', $usuarioId);
                $stmt->bindParam(':permissao', $permissao);
                $stmt->execute();
                }
            } else {
                // Se não tem usuario_id, atualizar por role
                $sql = "DELETE FROM role_permissao WHERE role = :role";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':role', $usuario['role']);
                $stmt->execute();
                
                // Adicionar novas permissões
                foreach ($permissoes as $permissao) {
                    $sql = "INSERT INTO role_permissao (role, permissao, ativo) VALUES (:role, :permissao, 1)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':role', $usuario['role']);
                    $stmt->bindParam(':permissao', $permissao);
                    $stmt->execute();
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_usuarios') {
        $sql = "SELECT u.id, u.username, u.role, p.nome 
                FROM usuario u
                INNER JOIN pessoa p ON u.pessoa_id = p.id
                WHERE u.ativo = 1";
        
        $params = [];
        
        // Filtro por busca (nome ou username)
        if (!empty($_GET['busca'])) {
            $sql .= " AND (p.nome LIKE :busca OR u.username LIKE :busca)";
            $params[':busca'] = "%{$_GET['busca']}%";
        }
        
        // Filtro por role
        if (!empty($_GET['role'])) {
            $sql .= " AND u.role = :role";
            $params[':role'] = $_GET['role'];
        }
        
        $sql .= " ORDER BY p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'usuarios' => $usuarios]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_permissoes' && !empty($_GET['usuario_id'])) {
        try {
            // Buscar informações do usuário
            $sqlUsuario = "SELECT u.id, u.username, u.role, p.nome, p.email 
                          FROM usuario u
                          INNER JOIN pessoa p ON u.pessoa_id = p.id
                          WHERE u.id = :usuario_id";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':usuario_id', $_GET['usuario_id']);
            $stmtUsuario->execute();
            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
                exit;
            }
            
            // Verificar se a tabela role_permissao tem coluna usuario_id ou apenas role
            $sqlCheck = "SHOW COLUMNS FROM role_permissao LIKE 'usuario_id'";
            $stmtCheck = $conn->query($sqlCheck);
            $temUsuarioId = $stmtCheck->rowCount() > 0;
            
            // Buscar permissões do usuário
            if ($temUsuarioId) {
                // Se a tabela tem usuario_id, buscar por usuario_id
        $sql = "SELECT permissao FROM role_permissao WHERE usuario_id = :usuario_id AND ativo = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $_GET['usuario_id']);
            } else {
                // Se não tem, buscar por role
                $sql = "SELECT permissao FROM role_permissao WHERE role = :role AND ativo = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':role', $usuario['role']);
            }
        $stmt->execute();
        $permissoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
            echo json_encode([
                'success' => true, 
                'permissoes' => $permissoes,
                'usuario' => $usuario
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Buscar roles disponíveis para o filtro
$sqlRoles = "SELECT DISTINCT role FROM usuario WHERE ativo = 1 ORDER BY role ASC";
$stmtRoles = $conn->prepare($sqlRoles);
$stmtRoles->execute();
$rolesDisponiveis = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

$sqlUsuarios = "SELECT u.id, u.username, u.role, p.nome 
                FROM usuario u
                INNER JOIN pessoa p ON u.pessoa_id = p.id
                WHERE u.ativo = 1
                ORDER BY p.nome ASC
                LIMIT 50";
$stmtUsuarios = $conn->prepare($sqlUsuarios);
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Permissões de Usuários') ?></title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.12) 0%, rgba(220, 38, 38, 0.06) 100%);
            border-right: 3px solid #dc2626;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.08) 0%, rgba(220, 38, 38, 0.04) 100%);
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
    <?php include 'components/sidebar_adm.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Permissões</h1>
                    </div>
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
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Permissões de Usuários</h2>
                    <p class="text-gray-600 mt-1">Defina permissões específicas para cada usuário do sistema</p>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Usuário</label>
                            <input type="text" id="filtro-busca" placeholder="Nome ou username..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                   onkeyup="filtrarUsuarios()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nível de Conta (Role)</label>
                            <select id="filtro-role" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                    onchange="filtrarUsuarios()">
                                <option value="">Todos os níveis</option>
                                <option value="ADM">Administrador (ADM)</option>
                                <option value="GESTAO">Gestão (GESTAO)</option>
                                <option value="PROFESSOR">Professor (PROFESSOR)</option>
                                <option value="ALUNO">Aluno (ALUNO)</option>
                                <option value="ADM_MERENDA">Administrador de Merenda (ADM_MERENDA)</option>
                                <option value="NUTRICIONISTA">Nutricionista (NUTRICIONISTA)</option>
                                <option value="RESPONSAVEL">Responsável (RESPONSAVEL)</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarUsuarios()" 
                                    class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Usuário</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Username</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nível de Conta</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-usuarios">
                                <?php if (empty($usuarios)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-12 text-gray-600">
                                            Nenhum usuário encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($usuario['nome']) ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="text-sm text-gray-600"><?= htmlspecialchars($usuario['username'] ?? '-') ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php
                                                $roleColors = [
                                                    'ADM' => 'bg-red-100 text-red-800',
                                                    'GESTAO' => 'bg-blue-100 text-blue-800',
                                                    'PROFESSOR' => 'bg-green-100 text-green-800',
                                                    'ALUNO' => 'bg-yellow-100 text-yellow-800',
                                                    'ADM_MERENDA' => 'bg-purple-100 text-purple-800',
                                                    'NUTRICIONISTA' => 'bg-pink-100 text-pink-800',
                                                    'RESPONSAVEL' => 'bg-indigo-100 text-indigo-800'
                                                ];
                                                $roleColor = $roleColors[$usuario['role']] ?? 'bg-gray-100 text-gray-800';
                                                $roleNames = [
                                                    'ADM' => 'Administrador',
                                                    'GESTAO' => 'Gestão',
                                                    'PROFESSOR' => 'Professor',
                                                    'ALUNO' => 'Aluno',
                                                    'ADM_MERENDA' => 'Adm. Merenda',
                                                    'NUTRICIONISTA' => 'Nutricionista',
                                                    'RESPONSAVEL' => 'Responsável'
                                                ];
                                                $roleName = $roleNames[$usuario['role']] ?? $usuario['role'];
                                                ?>
                                                <span class="px-2 py-1 rounded text-xs font-medium <?= $roleColor ?>">
                                                    <?= htmlspecialchars($roleName) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <button onclick="editarPermissoes(<?= $usuario['id'] ?>)" 
                                                        class="text-orange-600 hover:text-orange-700 font-medium text-sm hover:underline">
                                                    Editar Permissões
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
    
    <!-- Modal Editar Permissões -->
    <div id="modal-permissoes" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">Editar Permissões</h3>
                    <p id="usuario-info" class="text-sm text-gray-600 mt-1">Carregando informações do usuário...</p>
                </div>
                <button onclick="fecharModalPermissoes()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="loading-permissoes" class="text-center py-8">
                    <svg class="animate-spin h-8 w-8 text-orange-600 mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-600 mt-4">Carregando permissões...</p>
                </div>
                
                <div id="alerta-erro-permissoes" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"></div>
                
                <div id="permissoes-container" class="hidden space-y-6">
                <!-- Permissões serão carregadas aqui -->
            </div>
            </div>
            
            <!-- Footer do Modal (Sticky) -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-white sticky bottom-0 z-10">
                <button onclick="fecharModalPermissoes()" class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button onclick="salvarPermissoes()" id="btn-salvar-permissoes" class="px-6 py-3 text-white bg-orange-600 hover:bg-orange-700 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                    <span>Salvar Permissões</span>
                    <svg id="spinner-salvar-permissoes" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
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
        let usuarioIdAtual = null;
        
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

        function filtrarUsuarios() {
            const busca = document.getElementById('filtro-busca').value;
            const role = document.getElementById('filtro-role').value;
            
            let url = '?acao=listar_usuarios';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            if (role) url += '&role=' + encodeURIComponent(role);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-usuarios');
                        tbody.innerHTML = '';
                        
                        if (data.usuarios.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-12 text-gray-600">Nenhum usuário encontrado.</td></tr>';
                            return;
                        }
                        
                        const roleColors = {
                            'ADM': 'bg-red-100 text-red-800',
                            'GESTAO': 'bg-blue-100 text-blue-800',
                            'PROFESSOR': 'bg-green-100 text-green-800',
                            'ALUNO': 'bg-yellow-100 text-yellow-800',
                            'ADM_MERENDA': 'bg-purple-100 text-purple-800',
                            'NUTRICIONISTA': 'bg-pink-100 text-pink-800',
                            'RESPONSAVEL': 'bg-indigo-100 text-indigo-800'
                        };
                        
                        const roleNames = {
                            'ADM': 'Administrador',
                            'GESTAO': 'Gestão',
                            'PROFESSOR': 'Professor',
                            'ALUNO': 'Aluno',
                            'ADM_MERENDA': 'Adm. Merenda',
                            'NUTRICIONISTA': 'Nutricionista',
                            'RESPONSAVEL': 'Responsável'
                        };
                        
                        data.usuarios.forEach(usuario => {
                            const roleColor = roleColors[usuario.role] || 'bg-gray-100 text-gray-800';
                            const roleName = roleNames[usuario.role] || usuario.role;
                            
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-gray-900">${usuario.nome}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="text-sm text-gray-600">${usuario.username || '-'}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium ${roleColor}">
                                            ${roleName}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <button onclick="editarPermissoes(${usuario.id})" 
                                                class="text-orange-600 hover:text-orange-700 font-medium text-sm hover:underline">
                                            Editar Permissões
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar usuários:', error);
                });
        }

        async function editarPermissoes(id) {
            usuarioIdAtual = id;
            const modal = document.getElementById('modal-permissoes');
            const container = document.getElementById('permissoes-container');
            const loading = document.getElementById('loading-permissoes');
            const alertaErro = document.getElementById('alerta-erro-permissoes');
            const usuarioInfo = document.getElementById('usuario-info');
            
            // Mostrar modal
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            
            // Mostrar loading e esconder conteúdo
            loading.classList.remove('hidden');
            container.classList.add('hidden');
            alertaErro.classList.add('hidden');
            usuarioInfo.textContent = 'Carregando informações do usuário...';
            
            try {
                const response = await fetch('?acao=buscar_permissoes&usuario_id=' + id);
                const data = await response.json();
                
                if (data.success) {
                    // Atualizar informações do usuário
                    if (data.usuario) {
                        usuarioInfo.textContent = `${data.usuario.nome} (${data.usuario.role})`;
                    }
                    
                    // Definir todas as permissões disponíveis organizadas por categoria
                    const permissoesPorCategoria = {
                        'Gestão de Pessoas': [
                            { id: 'cadastrar_pessoas', nome: 'Cadastrar Pessoas', descricao: 'Permite cadastrar novos alunos, professores, funcionários e gestores' },
                            { id: 'editar_pessoas', nome: 'Editar Pessoas', descricao: 'Permite editar informações de pessoas cadastradas' },
                            { id: 'excluir_pessoas', nome: 'Excluir Pessoas', descricao: 'Permite excluir pessoas do sistema' },
                            { id: 'visualizar_pessoas', nome: 'Visualizar Pessoas', descricao: 'Permite visualizar informações de pessoas' }
                        ],
                        'Gestão Acadêmica': [
                            { id: 'matricular_alunos', nome: 'Matricular Alunos', descricao: 'Permite matricular alunos em turmas' },
                            { id: 'gerenciar_turmas', nome: 'Gerenciar Turmas', descricao: 'Permite criar, editar e excluir turmas' },
                            { id: 'gerenciar_disciplinas', nome: 'Gerenciar Disciplinas', descricao: 'Permite gerenciar disciplinas do currículo' },
                            { id: 'lancar_notas', nome: 'Lançar Notas', descricao: 'Permite lançar e editar notas dos alunos' },
                            { id: 'lancar_frequencia', nome: 'Lançar Frequência', descricao: 'Permite registrar frequência dos alunos' },
                            { id: 'acessar_registros', nome: 'Acessar Registros', descricao: 'Permite acessar registros acadêmicos' }
                        ],
                        'Relatórios': [
                            { id: 'gerar_relatorios_pedagogicos', nome: 'Relatórios Pedagógicos', descricao: 'Permite gerar relatórios pedagógicos' },
                            { id: 'relatorio_geral', nome: 'Relatório Geral', descricao: 'Permite gerar relatórios gerais do sistema' },
                            { id: 'relatorio_financeiro', nome: 'Relatório Financeiro', descricao: 'Permite gerar relatórios financeiros' }
                        ],
                        'Gestão de Merenda': [
                            { id: 'gerenciar_estoque', nome: 'Gerenciar Estoque', descricao: 'Permite gerenciar o estoque de alimentos' },
                            { id: 'gerenciar_cardapios', nome: 'Gerenciar Cardápios', descricao: 'Permite criar e editar cardápios escolares' },
                            { id: 'gerenciar_fornecedores', nome: 'Gerenciar Fornecedores', descricao: 'Permite gerenciar fornecedores' },
                            { id: 'gerenciar_pedidos', nome: 'Gerenciar Pedidos', descricao: 'Permite gerenciar pedidos de merenda' }
                        ],
                        'Configurações': [
                            { id: 'gerenciar_escolas', nome: 'Gerenciar Escolas', descricao: 'Permite criar, editar e excluir escolas' },
                            { id: 'gerenciar_usuarios', nome: 'Gerenciar Usuários', descricao: 'Permite gerenciar usuários do sistema' },
                            { id: 'gerenciar_permissoes', nome: 'Gerenciar Permissões', descricao: 'Permite gerenciar permissões de usuários' },
                            { id: 'configuracoes_sistema', nome: 'Configurações do Sistema', descricao: 'Permite alterar configurações gerais do sistema' }
                        ]
                    };
                    
                    // Limpar container
                    container.innerHTML = '';
                    
                    // Criar checkboxes organizados por categoria
                    Object.keys(permissoesPorCategoria).forEach(categoria => {
                        const categoriaDiv = document.createElement('div');
                        categoriaDiv.className = 'mb-6';
                        
                        const categoriaTitle = document.createElement('h4');
                        categoriaTitle.className = 'text-lg font-semibold text-gray-800 mb-3 pb-2 border-b border-gray-200';
                        categoriaTitle.textContent = categoria;
                        categoriaDiv.appendChild(categoriaTitle);
                        
                        const permissoesDiv = document.createElement('div');
                        permissoesDiv.className = 'grid grid-cols-1 md:grid-cols-2 gap-3';
                        
                        permissoesPorCategoria[categoria].forEach(perm => {
                            const checked = data.permissoes.includes(perm.id) ? 'checked' : '';
                            const permDiv = document.createElement('div');
                            permDiv.className = 'flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors';
                            
                            permDiv.innerHTML = `
                                <input type="checkbox" value="${perm.id}" ${checked} 
                                       class="permissao-checkbox mt-1 w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                                <div class="flex-1">
                                    <label class="text-sm font-medium text-gray-900 cursor-pointer">${perm.nome}</label>
                                    <p class="text-xs text-gray-500 mt-1">${perm.descricao}</p>
                                </div>
                            `;
                            
                            permissoesDiv.appendChild(permDiv);
                        });
                        
                        categoriaDiv.appendChild(permissoesDiv);
                        container.appendChild(categoriaDiv);
                    });
                    
                    // Adicionar botão "Selecionar Tudo" e "Desselecionar Tudo"
                    const botoesDiv = document.createElement('div');
                    botoesDiv.className = 'flex space-x-2 mb-4 pb-4 border-b border-gray-200';
                    botoesDiv.innerHTML = `
                        <button onclick="selecionarTodasPermissoes()" class="px-4 py-2 text-sm text-orange-600 hover:text-orange-700 hover:bg-orange-50 rounded-lg font-medium transition-colors">
                            Selecionar Todas
                        </button>
                        <button onclick="desselecionarTodasPermissoes()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-700 hover:bg-gray-50 rounded-lg font-medium transition-colors">
                            Desselecionar Todas
                        </button>
                    `;
                    container.insertBefore(botoesDiv, container.firstChild);
                    
                    // Esconder loading e mostrar conteúdo
                    loading.classList.add('hidden');
                    container.classList.remove('hidden');
                } else {
                    loading.classList.add('hidden');
                    alertaErro.textContent = data.message || 'Erro ao carregar permissões';
                    alertaErro.classList.remove('hidden');
                }
            } catch (error) {
                    console.error('Erro:', error);
                loading.classList.add('hidden');
                alertaErro.textContent = 'Erro ao carregar permissões. Por favor, tente novamente.';
                alertaErro.classList.remove('hidden');
            }
        }
        
        function selecionarTodasPermissoes() {
            document.querySelectorAll('.permissao-checkbox').forEach(cb => {
                cb.checked = true;
            });
        }
        
        function desselecionarTodasPermissoes() {
            document.querySelectorAll('.permissao-checkbox').forEach(cb => {
                cb.checked = false;
                });
        }

        function fecharModalPermissoes() {
            const modal = document.getElementById('modal-permissoes');
            modal.style.display = 'none';
            modal.classList.add('hidden');
            usuarioIdAtual = null;
            
            // Limpar conteúdo
            document.getElementById('permissoes-container').innerHTML = '';
            document.getElementById('permissoes-container').classList.add('hidden');
            document.getElementById('loading-permissoes').classList.remove('hidden');
            document.getElementById('alerta-erro-permissoes').classList.add('hidden');
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('modal-permissoes')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalPermissoes();
            }
        });

        async function salvarPermissoes() {
            if (!usuarioIdAtual) {
                alert('Nenhum usuário selecionado.');
                return;
            }
            
            const btnSalvar = document.getElementById('btn-salvar-permissoes');
            const spinner = document.getElementById('spinner-salvar-permissoes');
            const alertaErro = document.getElementById('alerta-erro-permissoes');
            
            // Coletar permissões selecionadas
            const permissoes = [];
            document.querySelectorAll('.permissao-checkbox:checked').forEach(cb => {
                permissoes.push(cb.value);
            });
            
            // Mostrar loading
            btnSalvar.disabled = true;
            spinner.classList.remove('hidden');
            alertaErro.classList.add('hidden');
            
            try {
            const formData = new FormData();
            formData.append('acao', 'atualizar_permissoes');
            formData.append('usuario_id', usuarioIdAtual);
            formData.append('permissoes', JSON.stringify(permissoes));
            
                const response = await fetch('', {
                method: 'POST',
                body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Mostrar mensagem de sucesso
                    const alertaSucesso = document.createElement('div');
                    alertaSucesso.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4';
                    alertaSucesso.textContent = 'Permissões atualizadas com sucesso!';
                    const container = document.getElementById('permissoes-container');
                    container.insertBefore(alertaSucesso, container.firstChild);
                    
                    // Fechar modal após 1.5 segundos
                    setTimeout(() => {
                    fecharModalPermissoes();
                    }, 1500);
                } else {
                    alertaErro.textContent = 'Erro ao atualizar permissões: ' + (data.message || 'Erro desconhecido');
                    alertaErro.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro:', error);
                alertaErro.textContent = 'Erro ao processar requisição. Por favor, tente novamente.';
                alertaErro.classList.remove('hidden');
            } finally {
                btnSalvar.disabled = false;
                spinner.classList.add('hidden');
            }
        }
    </script>
</body>
</html>

