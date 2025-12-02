<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/AlunoModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é ADM
if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$alunoModel = new AlunoModel();

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'cadastrar_aluno') {
        // Implementar cadastro de aluno
        echo json_encode(['success' => false, 'message' => 'Funcionalidade em desenvolvimento']);
        exit;
    }
    
    if ($_POST['acao'] === 'editar_aluno') {
        // Implementar edição de aluno
        echo json_encode(['success' => false, 'message' => 'Funcionalidade em desenvolvimento']);
        exit;
    }
    
    if ($_POST['acao'] === 'excluir_aluno') {
        // Implementar exclusão de aluno
        echo json_encode(['success' => false, 'message' => 'Funcionalidade em desenvolvimento']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_alunos') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
        
        $sql = "SELECT a.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, e.nome as escola_nome
                FROM aluno a
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN escola e ON a.escola_id = e.id
                WHERE 1=1";
        
        $params = [];
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND a.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        if (!empty($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR a.matricula LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        $sql .= " ORDER BY p.nome ASC LIMIT 100";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'alunos' => $alunos]);
        exit;
    }
}

// Buscar alunos iniciais
$sqlAlunos = "SELECT a.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, e.nome as escola_nome
              FROM aluno a
              INNER JOIN pessoa p ON a.pessoa_id = p.id
              LEFT JOIN escola e ON a.escola_id = e.id
              ORDER BY p.nome ASC
              LIMIT 50";
$stmtAlunos = $conn->prepare($sqlAlunos);
$stmtAlunos->execute();
$alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Alunos - SIGEA</title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Alunos</h1>
                    </div>
                    <div class="w-10"></div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Alunos</h2>
                        <p class="text-gray-600 mt-1">Cadastre, edite e exclua alunos do sistema</p>
                    </div>
                    <button onclick="abrirModalNovoAluno()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Aluno</span>
                    </button>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="filtro-busca" placeholder="Nome, CPF ou Matrícula..." class="w-full px-4 py-2 border border-gray-300 rounded-lg" onkeyup="filtrarAlunos()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarAlunos()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarAlunos()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Alunos -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nome</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Matrícula</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">CPF</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-alunos">
                                <?php if (empty($alunos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-gray-600">
                                            Nenhum aluno encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($alunos as $aluno): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['matricula'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['cpf'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['escola_nome'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($aluno['email'] ?? '-') ?></td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarAluno(<?= $aluno['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                        Editar
                                                    </button>
                                                    <button onclick="excluirAluno(<?= $aluno['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
                                                        Excluir
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
        </div>
    </main>
    
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

        function abrirModalNovoAluno() {
            alert('Funcionalidade de cadastro de aluno em desenvolvimento.');
        }

        function editarAluno(id) {
            alert('Funcionalidade de edição de aluno em desenvolvimento. ID: ' + id);
        }

        function excluirAluno(id) {
            if (confirm('Tem certeza que deseja excluir este aluno?')) {
                alert('Funcionalidade de exclusão de aluno em desenvolvimento. ID: ' + id);
            }
        }

        function filtrarAlunos() {
            const busca = document.getElementById('filtro-busca').value;
            const escolaId = document.getElementById('filtro-escola').value;
            
            let url = '?acao=listar_alunos';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            if (escolaId) url += '&escola_id=' + escolaId;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-alunos');
                        tbody.innerHTML = '';
                        
                        if (data.alunos.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-gray-600">Nenhum aluno encontrado.</td></tr>';
                            return;
                        }
                        
                        data.alunos.forEach(aluno => {
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4">${aluno.nome}</td>
                                    <td class="py-3 px-4">${aluno.matricula || '-'}</td>
                                    <td class="py-3 px-4">${aluno.cpf || '-'}</td>
                                    <td class="py-3 px-4">${aluno.escola_nome || '-'}</td>
                                    <td class="py-3 px-4">${aluno.email || '-'}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarAluno(${aluno.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                            <button onclick="excluirAluno(${aluno.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
                                                Excluir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao filtrar alunos:', error);
                });
        }
    </script>
</body>
</html>

