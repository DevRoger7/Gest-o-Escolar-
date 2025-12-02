<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

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
            
            // Remover permissões existentes
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
                WHERE u.ativo = 1
                ORDER BY p.nome ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'usuarios' => $usuarios]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_permissoes' && !empty($_GET['usuario_id'])) {
        $sql = "SELECT permissao FROM role_permissao WHERE usuario_id = :usuario_id AND ativo = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $_GET['usuario_id']);
        $stmt->execute();
        $permissoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['success' => true, 'permissoes' => $permissoes]);
        exit;
    }
}

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
    <title>Permissões - SIGEA</title>
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
                    <div class="w-10"></div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Permissões de Usuários</h2>
                    <p class="text-gray-600 mt-1">Defina permissões específicas para cada usuário do sistema</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Usuário</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Role</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-usuarios">
                                <?php if (empty($usuarios)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-12 text-gray-600">
                                            Nenhum usuário encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= htmlspecialchars($usuario['nome']) ?></td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800">
                                                    <?= htmlspecialchars($usuario['role']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <button onclick="editarPermissoes(<?= $usuario['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
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
    <div id="modal-permissoes" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Editar Permissões</h3>
            <div id="permissoes-container" class="space-y-3 mb-4">
                <!-- Permissões serão carregadas aqui -->
            </div>
            <div class="flex space-x-3">
                <button onclick="fecharModalPermissoes()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarPermissoes()" class="flex-1 px-4 py-2 text-white bg-orange-600 hover:bg-orange-700 rounded-lg font-medium transition-colors">
                    Salvar
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

        function editarPermissoes(id) {
            usuarioIdAtual = id;
            document.getElementById('modal-permissoes').classList.remove('hidden');
            
            fetch('?acao=buscar_permissoes&usuario_id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('permissoes-container');
                        const todasPermissoes = [
                            'cadastrar_pessoas', 'matricular_alunos', 'acessar_registros',
                            'lancar_notas', 'lancar_frequencia', 'gerar_relatorios_pedagogicos',
                            'relatorio_geral', 'gerenciar_estoque', 'gerenciar_cardapios'
                        ];
                        
                        container.innerHTML = '';
                        todasPermissoes.forEach(perm => {
                            const checked = data.permissoes.includes(perm) ? 'checked' : '';
                            container.innerHTML += `
                                <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded">
                                    <input type="checkbox" value="${perm}" ${checked} class="permissao-checkbox">
                                    <span class="text-sm text-gray-700">${perm.replace(/_/g, ' ').toUpperCase()}</span>
                                </label>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                });
        }

        function fecharModalPermissoes() {
            document.getElementById('modal-permissoes').classList.add('hidden');
            usuarioIdAtual = null;
        }

        function salvarPermissoes() {
            if (!usuarioIdAtual) return;
            
            const permissoes = [];
            document.querySelectorAll('.permissao-checkbox:checked').forEach(cb => {
                permissoes.push(cb.value);
            });
            
            const formData = new FormData();
            formData.append('acao', 'atualizar_permissoes');
            formData.append('usuario_id', usuarioIdAtual);
            formData.append('permissoes', JSON.stringify(permissoes));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Permissões atualizadas com sucesso!');
                    fecharModalPermissoes();
                } else {
                    alert('Erro ao atualizar permissões: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar permissões.');
            });
        }
    </script>
</body>
</html>

