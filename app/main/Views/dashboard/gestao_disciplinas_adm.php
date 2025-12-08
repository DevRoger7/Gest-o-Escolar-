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
    
    if ($_POST['acao'] === 'criar_disciplina') {
        try {
            // Validar nome obrigatório
            if (empty(trim($_POST['nome'] ?? ''))) {
                throw new Exception('Nome da disciplina é obrigatório.');
            }
            
            $nome = trim($_POST['nome']);
            $codigo = !empty($_POST['codigo']) ? trim($_POST['codigo']) : null;
            $cargaHoraria = !empty($_POST['carga_horaria']) ? (int)$_POST['carga_horaria'] : null;
            
            $sql = "INSERT INTO disciplina (nome, codigo, carga_horaria, ativo, criado_em) 
                    VALUES (:nome, :codigo, :carga_horaria, 1, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->bindParam(':carga_horaria', $cargaHoraria);
            $resultado = $stmt->execute();
            echo json_encode(['success' => $resultado, 'id' => $conn->lastInsertId()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'editar_disciplina') {
        try {
            // Validar nome obrigatório
            if (empty(trim($_POST['nome'] ?? ''))) {
                throw new Exception('Nome da disciplina é obrigatório.');
            }
            
            $id = (int)$_POST['id'];
            $nome = trim($_POST['nome']);
            $codigo = !empty($_POST['codigo']) ? trim($_POST['codigo']) : null;
            $cargaHoraria = !empty($_POST['carga_horaria']) ? (int)$_POST['carga_horaria'] : null;
            $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
            
            $sql = "UPDATE disciplina SET nome = :nome, codigo = :codigo, carga_horaria = :carga_horaria, ativo = :ativo WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->bindParam(':carga_horaria', $cargaHoraria);
            $stmt->bindParam(':ativo', $ativo);
            $resultado = $stmt->execute();
            echo json_encode(['success' => $resultado]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'excluir_disciplina') {
        $sql = "UPDATE disciplina SET ativo = 0 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $_POST['id']);
        $resultado = $stmt->execute();
        echo json_encode(['success' => $resultado]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_disciplinas') {
        $sql = "SELECT * FROM disciplina WHERE 1=1";
        $params = [];
        
        if (!empty($_GET['busca'])) {
            $sql .= " AND (nome LIKE :busca OR codigo LIKE :busca)";
            $params[':busca'] = "%{$_GET['busca']}%";
        }
        
        $sql .= " ORDER BY nome ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'disciplinas' => $disciplinas]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_disciplina' && isset($_GET['id'])) {
        $sql = "SELECT * FROM disciplina WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
        $disciplina = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($disciplina) {
            echo json_encode(['success' => true, 'disciplina' => $disciplina]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Disciplina não encontrada.']);
        }
        exit;
    }
}

$sqlDisciplinas = "SELECT * FROM disciplina ORDER BY nome ASC";
$stmtDisciplinas = $conn->prepare($sqlDisciplinas);
$stmtDisciplinas->execute();
$disciplinas = $stmtDisciplinas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Disciplinas - SIGEA</title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Disciplinas</h1>
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
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Disciplinas</h2>
                        <p class="text-gray-600 mt-1">Crie, edite e exclua disciplinas do sistema</p>
                    </div>
                    <button onclick="abrirModalNovaDisciplina()" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Disciplina</span>
                    </button>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="filtro-busca" placeholder="Nome ou Código..." class="w-full px-4 py-2 border border-gray-300 rounded-lg" onkeyup="filtrarDisciplinas()">
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarDisciplinas()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
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
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nome</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Código</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Carga Horária</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-disciplinas">
                                <?php if (empty($disciplinas)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-gray-600">
                                            Nenhuma disciplina encontrada.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($disciplinas as $disciplina): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4 font-medium"><?= htmlspecialchars($disciplina['nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($disciplina['codigo'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($disciplina['carga_horaria'] ?? '-') ?>h</td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 rounded text-xs <?= $disciplina['ativo'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                    <?= $disciplina['ativo'] ? 'Ativa' : 'Inativa' ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarDisciplina(<?= $disciplina['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                        Editar
                                                    </button>
                                                    <button onclick="excluirDisciplina(<?= $disciplina['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
    
    <!-- Modal de Nova Disciplina -->
    <div id="modalNovaDisciplina" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-900">Nova Disciplina</h3>
                <button onclick="fecharModalNovaDisciplina()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="formNovaDisciplina" class="p-6 space-y-6">
                <div>
                    <label for="nova_nome" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome da Disciplina <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nova_nome" name="nome" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           placeholder="Ex: Matemática">
                </div>
                
                <div>
                    <label for="nova_codigo" class="block text-sm font-medium text-gray-700 mb-2">
                        Código
                    </label>
                    <input type="text" id="nova_codigo" name="codigo"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           placeholder="Ex: MAT001">
                </div>
                
                <div>
                    <label for="nova_carga_horaria" class="block text-sm font-medium text-gray-700 mb-2">
                        Carga Horária (horas)
                    </label>
                    <input type="number" id="nova_carga_horaria" name="carga_horaria" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           placeholder="Ex: 40">
                </div>
                
                <div id="mensagem-nova" class="hidden"></div>
                
                <div class="flex space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="fecharModalNovaDisciplina()"
                            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 text-white bg-pink-600 hover:bg-pink-700 rounded-lg font-medium transition-colors duration-200">
                        Criar Disciplina
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Edição de Disciplina -->
    <div id="modalEditarDisciplina" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-900">Editar Disciplina</h3>
                <button onclick="fecharModalEditarDisciplina()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="formEditarDisciplina" class="p-6 space-y-6">
                <input type="hidden" id="edit_disciplina_id" name="id">
                
                <div>
                    <label for="edit_nome" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome da Disciplina <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_nome" name="nome" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           placeholder="Ex: Matemática">
                </div>
                
                <div>
                    <label for="edit_codigo" class="block text-sm font-medium text-gray-700 mb-2">
                        Código
                    </label>
                    <input type="text" id="edit_codigo" name="codigo"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           placeholder="Ex: MAT001">
                </div>
                
                <div>
                    <label for="edit_carga_horaria" class="block text-sm font-medium text-gray-700 mb-2">
                        Carga Horária (horas)
                    </label>
                    <input type="number" id="edit_carga_horaria" name="carga_horaria" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                           placeholder="Ex: 40">
                </div>
                
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" id="edit_ativo" name="ativo" value="1" checked
                               class="w-4 h-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                        <span class="text-sm font-medium text-gray-700">Disciplina Ativa</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Desmarque para desativar a disciplina</p>
                </div>
                
                <div id="mensagem-edicao" class="hidden"></div>
                
                <div class="flex space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="fecharModalEditarDisciplina()"
                            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 text-white bg-pink-600 hover:bg-pink-700 rounded-lg font-medium transition-colors duration-200">
                        Salvar Alterações
                    </button>
                </div>
            </form>
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

        function abrirModalNovaDisciplina() {
            // Limpar formulário
            document.getElementById('formNovaDisciplina').reset();
            const mensagemDiv = document.getElementById('mensagem-nova');
            mensagemDiv.classList.add('hidden');
            mensagemDiv.innerHTML = '';
            
            // Abrir modal
            const modal = document.getElementById('modalNovaDisciplina');
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            
            // Focar no primeiro campo
            setTimeout(() => {
                document.getElementById('nova_nome').focus();
            }, 100);
        }
        
        function fecharModalNovaDisciplina() {
            const modal = document.getElementById('modalNovaDisciplina');
            modal.style.display = 'none';
            modal.classList.add('hidden');
            
            // Limpar formulário
            document.getElementById('formNovaDisciplina').reset();
            document.getElementById('mensagem-nova').classList.add('hidden');
        }
        
        // Submeter formulário de nova disciplina
        document.getElementById('formNovaDisciplina').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'criar_disciplina');
            
            const mensagemDiv = document.getElementById('mensagem-nova');
            mensagemDiv.classList.remove('hidden');
            mensagemDiv.innerHTML = '<div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">Criando disciplina...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mensagemDiv.innerHTML = '<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">Disciplina criada com sucesso!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    mensagemDiv.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">Erro ao criar disciplina. Tente novamente.</div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mensagemDiv.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">Erro ao criar disciplina. Tente novamente.</div>';
            });
        });

        function editarDisciplina(id) {
            // Buscar dados da disciplina
            fetch(`?acao=buscar_disciplina&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.disciplina) {
                        const disciplina = data.disciplina;
                        
                        // Preencher formulário
                        document.getElementById('edit_disciplina_id').value = disciplina.id;
                        document.getElementById('edit_nome').value = disciplina.nome || '';
                        document.getElementById('edit_codigo').value = disciplina.codigo || '';
                        document.getElementById('edit_carga_horaria').value = disciplina.carga_horaria || '';
                        document.getElementById('edit_ativo').checked = disciplina.ativo == 1;
                        
                        // Limpar mensagem anterior
                        const mensagemDiv = document.getElementById('mensagem-edicao');
                        mensagemDiv.classList.add('hidden');
                        mensagemDiv.innerHTML = '';
                        
                        // Abrir modal
                        const modal = document.getElementById('modalEditarDisciplina');
                        modal.style.display = 'flex';
                        modal.classList.remove('hidden');
                    } else {
                        alert('Erro ao carregar dados da disciplina: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar disciplina:', error);
                    alert('Erro ao carregar dados da disciplina. Tente novamente.');
                });
        }
        
        function fecharModalEditarDisciplina() {
            const modal = document.getElementById('modalEditarDisciplina');
            modal.style.display = 'none';
            modal.classList.add('hidden');
            
            // Limpar formulário
            document.getElementById('formEditarDisciplina').reset();
            document.getElementById('mensagem-edicao').classList.add('hidden');
        }
        
        // Submeter formulário de edição
        document.getElementById('formEditarDisciplina').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'editar_disciplina');
            
            // Adicionar campo ativo (checkbox)
            const ativo = document.getElementById('edit_ativo').checked ? 1 : 0;
            formData.set('ativo', ativo);
            
            const mensagemDiv = document.getElementById('mensagem-edicao');
            mensagemDiv.classList.remove('hidden');
            mensagemDiv.innerHTML = '<div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">Salvando alterações...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mensagemDiv.innerHTML = '<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">Disciplina atualizada com sucesso!</div>';
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    mensagemDiv.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">Erro ao atualizar disciplina. Tente novamente.</div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mensagemDiv.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">Erro ao atualizar disciplina. Tente novamente.</div>';
            });
        });

        function excluirDisciplina(id) {
            if (confirm('Tem certeza que deseja excluir esta disciplina?')) {
                const formData = new FormData();
                formData.append('acao', 'excluir_disciplina');
                formData.append('id', id);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Disciplina excluída com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir disciplina.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao excluir disciplina.');
                });
            }
        }

        function filtrarDisciplinas() {
            const busca = document.getElementById('filtro-busca').value;
            
            let url = '?acao=listar_disciplinas';
            if (busca) url += '&busca=' + encodeURIComponent(busca);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-disciplinas');
                        tbody.innerHTML = '';
                        
                        if (data.disciplinas.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-gray-600">Nenhuma disciplina encontrada.</td></tr>';
                            return;
                        }
                        
                        data.disciplinas.forEach(disciplina => {
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium">${disciplina.nome}</td>
                                    <td class="py-3 px-4">${disciplina.codigo || '-'}</td>
                                    <td class="py-3 px-4">${disciplina.carga_horaria || '-'}h</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs ${disciplina.ativo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                            ${disciplina.ativo ? 'Ativa' : 'Inativa'}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarDisciplina(${disciplina.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                            <button onclick="excluirDisciplina(${disciplina.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
                    console.error('Erro ao filtrar disciplinas:', error);
                });
        }
    </script>
</body>
</html>

