<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/TurmaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$turmaModel = new TurmaModel();

// Buscar escolas
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar séries
$sqlSeries = "SELECT id, nome FROM serie WHERE ativo = 1 ORDER BY ordem ASC";
$stmtSeries = $conn->prepare($sqlSeries);
$stmtSeries->execute();
$series = $stmtSeries->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'excluir_turma') {
        $id = $_POST['turma_id'] ?? null;
        if ($id) {
            $sql = "UPDATE turma SET ativo = 0 WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $resultado = $stmt->execute();
            echo json_encode(['success' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'atualizar_turma') {
        $id = $_POST['id'] ?? null;
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID da turma não informado.']);
            exit;
        }
        
        // Validar campos obrigatórios
        $escolaId = $_POST['escola_id'] ?? null;
        $letra = trim($_POST['letra'] ?? '');
        $turno = trim($_POST['turno'] ?? '');
        $anoLetivo = $_POST['ano_letivo'] ?? date('Y');
        
        if (empty($escolaId)) {
            echo json_encode(['success' => false, 'message' => 'O campo Escola é obrigatório.']);
            exit;
        }
        if (empty($letra)) {
            echo json_encode(['success' => false, 'message' => 'O campo Letra é obrigatório.']);
            exit;
        }
        if (empty($turno)) {
            echo json_encode(['success' => false, 'message' => 'O campo Turno é obrigatório.']);
            exit;
        }        
        $dados = [
            'escola_id' => $escolaId,
            'serie_id' => !empty($_POST['serie_id']) ? $_POST['serie_id'] : null,
            'ano_letivo' => $anoLetivo,
            'serie' => trim($_POST['serie'] ?? '') ?: null,
            'letra' => $letra,
            'turno' => $turno,
            'capacidade' => !empty($_POST['capacidade']) ? (int)$_POST['capacidade'] : null,
            'sala' => trim($_POST['sala'] ?? '') ?: null,
            'coordenador_id' => !empty($_POST['coordenador_id']) ? $_POST['coordenador_id'] : null,
            'ativo' => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1
        ];
        
        $resultado = $turmaModel->atualizar($id, $dados);
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Turma atualizada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar turma.']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'listar_turmas') {
        $filtros = [];
        if (!empty($_GET['escola_id'])) $filtros['escola_id'] = $_GET['escola_id'];
        if (!empty($_GET['ano_letivo'])) $filtros['ano_letivo'] = $_GET['ano_letivo'];
        
        $turmas = $turmaModel->listar($filtros);
        echo json_encode(['success' => true, 'turmas' => $turmas]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_turma') {
        $id = $_GET['id'] ?? null;
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID da turma não informado']);
            exit;
        }
        
        $turma = $turmaModel->buscarPorId($id);
        if ($turma) {
            echo json_encode(['success' => true, 'turma' => $turma]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Turma não encontrada']);
        }
        exit;
    }
}

$turmas = $turmaModel->listar(['ano_letivo' => date('Y'), 'ativo' => 1]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Turmas - SIGEA</title>
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
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Turmas</h1>
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
                        <h2 class="text-2xl font-bold text-gray-900">Turmas</h2>
                        <p class="text-gray-600 mt-1">Crie, edite e exclua turmas do sistema</p>
                    </div>
                    <button onclick="abrirModalNovaTurma()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Turma</span>
                    </button>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="filtro-escola" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarTurmas()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo</label>
                            <select id="filtro-ano" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarTurmas()">
                                <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                                    <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="filtrarTurmas()" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
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
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Turma</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Série</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Turno</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Alunos</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-turmas">
                                <?php if (empty($turmas)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-gray-600">
                                            Nenhuma turma encontrada.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($turmas as $turma): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4 font-medium"><?= htmlspecialchars($turma['serie'] . ' ' . $turma['letra']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($turma['escola_nome']) ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($turma['serie_nome'] ?? '-') ?></td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($turma['turno']) ?></td>
                                            <td class="py-3 px-4"><?= $turma['total_alunos'] ?? 0 ?></td>
                                            <td class="py-3 px-4">
                                                <div class="flex space-x-2">
                                                    <button onclick="editarTurma(<?= $turma['id'] ?>)" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                        Editar
                                                    </button>
                                                    <button onclick="excluirTurma(<?= $turma['id'] ?>)" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
    
    <!-- Modal Editar Turma -->
    <div id="modal-editar-turma" class="fixed inset-0 bg-gray-100 z-[60] hidden flex flex-col">
        <div class="bg-white border-b border-gray-200 p-6 flex items-center justify-between shadow-sm">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">Editar Turma</h3>
                <p class="text-sm text-gray-500 mt-1">Atualize os dados da turma</p>
            </div>
            <button onclick="fecharModalEditarTurma()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <form id="form-editar-turma" class="space-y-6">
                    <input type="hidden" id="editar-turma-id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola <span class="text-red-500">*</span></label>
                            <select id="editar-turma-escola" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors">
                                <option value="">Selecione a escola...</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Série</label>
                            <select id="editar-turma-serie" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors">
                                <option value="">Selecione a série...</option>
                                <?php foreach ($series as $serie): ?>
                                    <option value="<?= $serie['id'] ?>"><?= htmlspecialchars($serie['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo <span class="text-red-500">*</span></label>
                            <input type="number" id="editar-turma-ano" required min="2000" max="2100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors" placeholder="Ex: 2024">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Série (texto) <span class="text-gray-400 text-xs">(opcional)</span></label>
                            <input type="text" id="editar-turma-serie-texto" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors" placeholder="Ex: 1º Ano">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Letra <span class="text-red-500">*</span></label>
                            <input type="text" id="editar-turma-letra" required maxlength="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors uppercase" placeholder="Ex: A">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Turno <span class="text-red-500">*</span></label>
                            <select id="editar-turma-turno" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors">
                                <option value="">Selecione o turno...</option>
                                <option value="MANHÃ">Manhã</option>
                                <option value="TARDE">Tarde</option>
                                <option value="NOITE">Noite</option>
                                <option value="INTEGRAL">Integral</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Capacidade <span class="text-gray-400 text-xs">(opcional)</span></label>
                            <input type="number" id="editar-turma-capacidade" min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors" placeholder="Ex: 30">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sala <span class="text-gray-400 text-xs">(opcional)</span></label>
                            <input type="text" id="editar-turma-sala" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors" placeholder="Ex: Sala 101">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="editar-turma-ativo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-colors">
                                <option value="1">Ativa</option>
                                <option value="0">Inativa</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="bg-white border-t border-gray-200 p-6 shadow-sm">
            <div class="max-w-4xl mx-auto flex space-x-3">
                <button onclick="fecharModalEditarTurma()" class="flex-1 px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarEdicaoTurma()" class="flex-1 px-6 py-3 text-white bg-purple-600 hover:bg-purple-700 rounded-lg font-medium transition-colors">
                    Salvar Alterações
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

        function excluirTurma(id) {
            if (confirm('Tem certeza que deseja excluir esta turma?')) {
                const formData = new FormData();
                formData.append('acao', 'excluir_turma');
                formData.append('turma_id', id);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Turma excluída com sucesso!');
                        filtrarTurmas();
                    } else {
                        alert('Erro ao excluir turma.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao excluir turma.');
                });
            }
        }

        function filtrarTurmas() {
            const escolaId = document.getElementById('filtro-escola').value;
            const ano = document.getElementById('filtro-ano').value;
            
            let url = '?acao=listar_turmas';
            if (escolaId) url += '&escola_id=' + escolaId;
            if (ano) url += '&ano_letivo=' + ano;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('lista-turmas');
                        tbody.innerHTML = '';
                        
                        if (data.turmas.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-gray-600">Nenhuma turma encontrada.</td></tr>';
                            return;
                        }
                        
                        data.turmas.forEach(turma => {
                            tbody.innerHTML += `
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium">${turma.serie || ''} ${turma.letra || ''}</td>
                                    <td class="py-3 px-4">${turma.escola_nome}</td>
                                    <td class="py-3 px-4">${turma.serie_nome || '-'}</td>
                                    <td class="py-3 px-4">${turma.turno}</td>
                                    <td class="py-3 px-4">${turma.total_alunos || 0}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editarTurma(${turma.id})" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Editar
                                            </button>
                                            <button onclick="excluirTurma(${turma.id})" class="text-red-600 hover:text-red-700 font-medium text-sm">
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
                    console.error('Erro ao filtrar turmas:', error);
                });
        }
        
        function editarTurma(id) {
            // Buscar dados da turma
            fetch('?acao=buscar_turma&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.turma) {
                        const t = data.turma;
                        
                        // Preencher campos do modal
                        document.getElementById('editar-turma-id').value = t.id;
                        document.getElementById('editar-turma-escola').value = t.escola_id || '';
                        document.getElementById('editar-turma-serie').value = t.serie_id || '';
                        document.getElementById('editar-turma-ano').value = t.ano_letivo || new Date().getFullYear();
                        document.getElementById('editar-turma-serie-texto').value = t.serie || '';
                        document.getElementById('editar-turma-letra').value = t.letra || '';
                        document.getElementById('editar-turma-turno').value = t.turno || '';
                        document.getElementById('editar-turma-capacidade').value = t.capacidade || '';
                        document.getElementById('editar-turma-sala').value = t.sala || '';
                        document.getElementById('editar-turma-ativo').value = t.ativo || '1';
                        
                        // Abrir modal
                        document.getElementById('modal-editar-turma').classList.remove('hidden');
                    } else {
                        alert('Erro ao carregar dados da turma: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados da turma.');
                });
        }
        
        function fecharModalEditarTurma() {
            document.getElementById('modal-editar-turma').classList.add('hidden');
            document.getElementById('form-editar-turma').reset();
        }
        
        function salvarEdicaoTurma() {
            const id = document.getElementById('editar-turma-id').value;
            if (!id) {
                alert('ID da turma não encontrado.');
                return;
            }
            
            // Validar campos obrigatórios
            const escolaId = document.getElementById('editar-turma-escola').value;
            const letra = document.getElementById('editar-turma-letra').value.trim();
            const turno = document.getElementById('editar-turma-turno').value;
            
            if (!escolaId) {
                alert('Por favor, selecione a Escola.');
                document.getElementById('editar-turma-escola').focus();
                return;
            }
            if (!letra) {
                alert('Por favor, preencha o campo Letra.');
                document.getElementById('editar-turma-letra').focus();
                return;
            }
            if (!turno) {
                alert('Por favor, selecione o Turno.');
                document.getElementById('editar-turma-turno').focus();
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'atualizar_turma');
            formData.append('id', id);
            formData.append('escola_id', escolaId);
            formData.append('serie_id', document.getElementById('editar-turma-serie').value || '');
            formData.append('ano_letivo', document.getElementById('editar-turma-ano').value);
            formData.append('serie', document.getElementById('editar-turma-serie-texto').value.trim() || '');
            formData.append('letra', letra.toUpperCase());
            formData.append('turno', turno);
            formData.append('capacidade', document.getElementById('editar-turma-capacidade').value || '');
            formData.append('sala', document.getElementById('editar-turma-sala').value.trim() || '');
            formData.append('ativo', document.getElementById('editar-turma-ativo').value);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Turma atualizada com sucesso!');
                    fecharModalEditarTurma();
                    filtrarTurmas();
                } else {
                    alert('Erro ao atualizar turma: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar turma.');
            });
        }
        
        function abrirModalNovaTurma() {
            // Por enquanto, redireciona para gestao_escolar.php
            // Pode ser implementado um modal de criação depois
            window.location.href = 'gestao_escolar.php';
        }
    </script>
</body>
</html>

