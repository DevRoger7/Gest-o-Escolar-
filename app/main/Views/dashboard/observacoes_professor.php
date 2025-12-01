<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/ObservacaoDesempenhoModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'professor') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$observacaoModel = new ObservacaoDesempenhoModel();

// Buscar professor_id
$professorId = null;
$pessoaId = $_SESSION['pessoa_id'] ?? null;
if ($pessoaId) {
    $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
    $stmtProfessor = $conn->prepare($sqlProfessor);
    $stmtProfessor->bindParam(':pessoa_id', $pessoaId);
    $stmtProfessor->execute();
    $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
    $professorId = $professor['id'] ?? null;
}

// Buscar turmas e disciplinas do professor
$turmasProfessor = [];
if ($professorId) {
    $sqlTurmas = "SELECT DISTINCT 
                    t.id as turma_id,
                    CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                    d.id as disciplina_id,
                    d.nome as disciplina_nome,
                    e.nome as escola_nome
                  FROM turma_professor tp
                  INNER JOIN turma t ON tp.turma_id = t.id
                  INNER JOIN disciplina d ON tp.disciplina_id = d.id
                  INNER JOIN escola e ON t.escola_id = e.id
                  WHERE tp.professor_id = :professor_id AND tp.fim IS NULL AND t.ativo = 1
                  ORDER BY t.serie, t.letra, d.nome";
    $stmtTurmas = $conn->prepare($sqlTurmas);
    $stmtTurmas->bindParam(':professor_id', $professorId);
    $stmtTurmas->execute();
    $turmasProfessor = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'adicionar_observacao' && $professorId) {
        $dados = [
            'aluno_id' => $_POST['aluno_id'] ?? null,
            'turma_id' => $_POST['turma_id'] ?? null,
            'disciplina_id' => $_POST['disciplina_id'] ?? null,
            'professor_id' => $professorId,
            'tipo' => $_POST['tipo'] ?? 'OUTROS',
            'titulo' => $_POST['titulo'] ?? null,
            'observacao' => $_POST['observacao'] ?? '',
            'data' => $_POST['data'] ?? date('Y-m-d'),
            'bimestre' => !empty($_POST['bimestre']) ? $_POST['bimestre'] : null,
            'visivel_responsavel' => isset($_POST['visivel_responsavel']) ? 1 : 0
        ];
        
        if ($dados['aluno_id'] && $dados['turma_id'] && !empty($dados['observacao'])) {
            $resultado = $observacaoModel->adicionar($dados);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'buscar_alunos_turma' && !empty($_GET['turma_id'])) {
        $turmaId = $_GET['turma_id'];
        $sql = "SELECT a.id, p.nome, a.matricula
                FROM aluno_turma at
                INNER JOIN aluno a ON at.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                WHERE at.turma_id = :turma_id AND at.fim IS NULL
                ORDER BY p.nome ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'alunos' => $alunos]);
        exit;
    }
    
    if ($_GET['acao'] === 'listar_observacoes' && !empty($_GET['turma_id']) && !empty($_GET['disciplina_id'])) {
        $turmaId = $_GET['turma_id'];
        $disciplinaId = $_GET['disciplina_id'];
        
        $sql = "SELECT od.*, p.nome as aluno_nome, a.matricula, d.nome as disciplina_nome
                FROM observacao_desempenho od
                INNER JOIN aluno a ON od.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN disciplina d ON od.disciplina_id = d.id
                WHERE od.turma_id = :turma_id 
                AND od.disciplina_id = :disciplina_id
                AND od.professor_id = :professor_id
                ORDER BY od.data DESC, od.criado_em DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':disciplina_id', $disciplinaId);
        $stmt->bindParam(':professor_id', $professorId);
        $stmt->execute();
        $observacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'observacoes' => $observacoes]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Observações de Desempenho - SIGEA</title>
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
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        .mobile-menu-overlay {
            transition: opacity 0.3s ease-in-out;
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
<body class="bg-gray-50">
    <?php include 'components/sidebar_professor.php'; ?>
    
    <!-- Main Content -->
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Observações de Desempenho</h1>
                    </div>
                    <div class="w-10"></div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <p class="text-gray-600">Adicione observações sobre o desempenho dos alunos</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Minhas Turmas</h2>
                        <button onclick="abrirModalAdicionarObservacao()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Adicionar Observação</span>
                        </button>
                    </div>
                    
                    <?php if (empty($turmasProfessor)): ?>
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <p class="text-gray-600">Você não possui turmas atribuídas no momento.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                            <?php foreach ($turmasProfessor as $turma): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                                    <div class="mb-3">
                                        <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($turma['turma_nome']) ?></h3>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($turma['disciplina_nome']) ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($turma['escola_nome']) ?></p>
                                    </div>
                                    <button onclick="verObservacoes(<?= $turma['turma_id'] ?>, <?= $turma['disciplina_id'] ?>, '<?= htmlspecialchars($turma['turma_nome']) ?>', '<?= htmlspecialchars($turma['disciplina_nome']) ?>')" class="w-full text-blue-600 hover:text-blue-700 font-medium text-sm py-2 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors">
                                        Ver Observações
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Lista de Observações -->
                        <div id="observacoes-container" class="hidden">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900" id="observacoes-titulo"></h3>
                                    <p class="text-sm text-gray-600" id="observacoes-subtitulo"></p>
                                </div>
                                <button onclick="fecharObservacoes()" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="observacoes-lista" class="space-y-4">
                                <!-- Observações serão carregadas aqui -->
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Adicionar Observação -->
    <div id="modal-adicionar-observacao" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Adicionar Observação</h3>
                <button onclick="fecharModalAdicionarObservacao()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="form-observacao" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                        <select id="observacao-turma-id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="carregarAlunosParaObservacao()">
                            <option value="">Selecione uma turma</option>
                            <?php foreach ($turmasProfessor as $turma): ?>
                                <option value="<?= $turma['turma_id'] ?>" data-disciplina-id="<?= $turma['disciplina_id'] ?>">
                                    <?= htmlspecialchars($turma['turma_nome']) ?> - <?= htmlspecialchars($turma['disciplina_nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Aluno</label>
                        <select id="observacao-aluno-id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Selecione primeiro a turma</option>
                        </select>
                    </div>
                </div>
                
                <input type="hidden" id="observacao-disciplina-id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select id="observacao-tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="COMPORTAMENTO">Comportamento</option>
                            <option value="APRENDIZAGEM">Aprendizagem</option>
                            <option value="PARTICIPACAO">Participação</option>
                            <option value="DIFICULDADE">Dificuldade</option>
                            <option value="MELHORIA">Melhoria</option>
                            <option value="OUTROS">Outros</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bimestre</label>
                        <select id="observacao-bimestre" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Não especificado</option>
                            <option value="1">1º Bimestre</option>
                            <option value="2">2º Bimestre</option>
                            <option value="3">3º Bimestre</option>
                            <option value="4">4º Bimestre</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                    <input type="date" id="observacao-data" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título (opcional)</label>
                    <input type="text" id="observacao-titulo" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Melhoria na participação">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observação *</label>
                    <textarea id="observacao-texto" required rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Descreva a observação sobre o desempenho do aluno..."></textarea>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="observacao-visivel" checked class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                    <label for="observacao-visivel" class="ml-2 text-sm text-gray-700">Visível para o responsável</label>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="fecharModalAdicionarObservacao()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-medium">
                        Salvar Observação
                    </button>
                </div>
            </form>
        </div>
    </div>
    
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
        
        function abrirModalAdicionarObservacao() {
            const modal = document.getElementById('modal-adicionar-observacao');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }
        
        function fecharModalAdicionarObservacao() {
            const modal = document.getElementById('modal-adicionar-observacao');
            if (modal) {
                modal.classList.add('hidden');
                document.getElementById('form-observacao').reset();
                document.getElementById('observacao-aluno-id').innerHTML = '<option value="">Selecione primeiro a turma</option>';
            }
        }
        
        function carregarAlunosParaObservacao() {
            const turmaSelect = document.getElementById('observacao-turma-id');
            const alunoSelect = document.getElementById('observacao-aluno-id');
            const disciplinaInput = document.getElementById('observacao-disciplina-id');
            
            const turmaId = turmaSelect.value;
            const selectedOption = turmaSelect.options[turmaSelect.selectedIndex];
            const disciplinaId = selectedOption ? selectedOption.dataset.disciplinaId : null;
            
            if (disciplinaId) {
                disciplinaInput.value = disciplinaId;
            }
            
            if (!turmaId) {
                alunoSelect.innerHTML = '<option value="">Selecione primeiro a turma</option>';
                return;
            }
            
            alunoSelect.innerHTML = '<option value="">Carregando...</option>';
            
            fetch('?acao=buscar_alunos_turma&turma_id=' + turmaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.alunos) {
                        alunoSelect.innerHTML = '<option value="">Selecione um aluno</option>';
                        data.alunos.forEach(aluno => {
                            const option = document.createElement('option');
                            option.value = aluno.id;
                            option.textContent = aluno.nome + (aluno.matricula ? ' (' + aluno.matricula + ')' : '');
                            alunoSelect.appendChild(option);
                        });
                    } else {
                        alunoSelect.innerHTML = '<option value="">Nenhum aluno encontrado</option>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar alunos:', error);
                    alunoSelect.innerHTML = '<option value="">Erro ao carregar alunos</option>';
                });
        }
        
        document.getElementById('form-observacao').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('acao', 'adicionar_observacao');
            formData.append('aluno_id', document.getElementById('observacao-aluno-id').value);
            formData.append('turma_id', document.getElementById('observacao-turma-id').value);
            formData.append('disciplina_id', document.getElementById('observacao-disciplina-id').value);
            formData.append('tipo', document.getElementById('observacao-tipo').value);
            formData.append('titulo', document.getElementById('observacao-titulo').value);
            formData.append('observacao', document.getElementById('observacao-texto').value);
            formData.append('data', document.getElementById('observacao-data').value);
            formData.append('bimestre', document.getElementById('observacao-bimestre').value);
            formData.append('visivel_responsavel', document.getElementById('observacao-visivel').checked ? '1' : '0');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Observação adicionada com sucesso!');
                    fecharModalAdicionarObservacao();
                    // Recarregar observações se estiverem sendo exibidas
                    const container = document.getElementById('observacoes-container');
                    if (!container.classList.contains('hidden')) {
                        const turmaId = document.getElementById('observacao-turma-id').value;
                        const disciplinaId = document.getElementById('observacao-disciplina-id').value;
                        const turmaSelect = document.getElementById('observacao-turma-id');
                        const selectedOption = turmaSelect.options[turmaSelect.selectedIndex];
                        const turmaNome = selectedOption ? selectedOption.textContent.split(' - ')[0] : '';
                        const disciplinaNome = selectedOption ? selectedOption.textContent.split(' - ')[1] || '' : '';
                        verObservacoes(turmaId, disciplinaId, turmaNome, disciplinaNome);
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Erro ao adicionar observação: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao adicionar observação');
            });
        });
        
        function verObservacoes(turmaId, disciplinaId, turmaNome = '', disciplinaNome = '') {
            const container = document.getElementById('observacoes-container');
            const titulo = document.getElementById('observacoes-titulo');
            const subtitulo = document.getElementById('observacoes-subtitulo');
            const lista = document.getElementById('observacoes-lista');
            
            container.classList.remove('hidden');
            titulo.textContent = turmaNome || 'Observações';
            subtitulo.textContent = disciplinaNome || '';
            
            lista.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-2 text-gray-600">Carregando...</p></div>';
            
            fetch('?acao=listar_observacoes&turma_id=' + turmaId + '&disciplina_id=' + disciplinaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.observacoes && data.observacoes.length > 0) {
                        lista.innerHTML = '';
                        data.observacoes.forEach(obs => {
                            const tipoColors = {
                                'COMPORTAMENTO': 'bg-yellow-100 text-yellow-800',
                                'APRENDIZAGEM': 'bg-green-100 text-green-800',
                                'PARTICIPACAO': 'bg-blue-100 text-blue-800',
                                'DIFICULDADE': 'bg-red-100 text-red-800',
                                'MELHORIA': 'bg-purple-100 text-purple-800',
                                'OUTROS': 'bg-gray-100 text-gray-800'
                            };
                            
                            const tipoLabels = {
                                'COMPORTAMENTO': 'Comportamento',
                                'APRENDIZAGEM': 'Aprendizagem',
                                'PARTICIPACAO': 'Participação',
                                'DIFICULDADE': 'Dificuldade',
                                'MELHORIA': 'Melhoria',
                                'OUTROS': 'Outros'
                            };
                            
                            const div = document.createElement('div');
                            div.className = 'border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow';
                            div.innerHTML = `
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <span class="font-semibold text-gray-900">${obs.aluno_nome}</span>
                                            ${obs.matricula ? '<span class="text-sm text-gray-500">(${obs.matricula})</span>' : ''}
                                            <span class="text-xs px-2 py-1 rounded-full ${tipoColors[obs.tipo] || tipoColors['OUTROS']}">
                                                ${tipoLabels[obs.tipo] || 'Outros'}
                                            </span>
                                        </div>
                                        ${obs.titulo ? `<h4 class="font-medium text-gray-800 mb-1">${obs.titulo}</h4>` : ''}
                                        <p class="text-sm text-gray-600 mb-2">${obs.observacao}</p>
                                        <div class="flex items-center space-x-4 text-xs text-gray-500">
                                            <span>${new Date(obs.data).toLocaleDateString('pt-BR')}</span>
                                            ${obs.bimestre ? `<span>${obs.bimestre}º Bimestre</span>` : ''}
                                            ${obs.visivel_responsavel ? '<span class="text-green-600">Visível para responsável</span>' : '<span class="text-gray-400">Não visível</span>'}
                                        </div>
                                    </div>
                                </div>
                            `;
                            lista.appendChild(div);
                        });
                    } else {
                        lista.innerHTML = '<div class="text-center py-12"><p class="text-gray-600">Nenhuma observação cadastrada para esta turma/disciplina.</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar observações:', error);
                    lista.innerHTML = '<div class="text-center py-12"><p class="text-red-600">Erro ao carregar observações.</p></div>';
                });
            
            // Scroll para o container
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function fecharObservacoes() {
            const container = document.getElementById('observacoes-container');
            container.classList.add('hidden');
        }
    </script>
</body>
</html>

