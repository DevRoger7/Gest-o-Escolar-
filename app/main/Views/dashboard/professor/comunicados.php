<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/comunicacao/ComunicadoModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'professor') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$comunicadoModel = new ComunicadoModel();

// Buscar professor_id e escola_id
$professorId = null;
$escolaId = null;
$pessoaId = $_SESSION['pessoa_id'] ?? null;
if ($pessoaId) {
    $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
    $stmtProfessor = $conn->prepare($sqlProfessor);
    $stmtProfessor->bindParam(':pessoa_id', $pessoaId);
    $stmtProfessor->execute();
    $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
    $professorId = $professor['id'] ?? null;
    
    // Buscar escola do professor através da lotação ou das turmas
    if ($professorId) {
        // Primeiro tenta pela lotação
        $sqlEscola = "SELECT DISTINCT e.id, e.nome
                      FROM professor_lotacao pl
                      INNER JOIN escola e ON pl.escola_id = e.id
                      WHERE pl.professor_id = :professor_id AND pl.fim IS NULL
                      LIMIT 1";
        $stmtEscola = $conn->prepare($sqlEscola);
        $stmtEscola->bindParam(':professor_id', $professorId);
        $stmtEscola->execute();
        $escola = $stmtEscola->fetch(PDO::FETCH_ASSOC);
        $escolaId = $escola['id'] ?? null;
        
        // Se não encontrou pela lotação, busca pela primeira turma atribuída
        if (!$escolaId) {
            $sqlEscolaTurma = "SELECT DISTINCT e.id, e.nome
                              FROM turma_professor tp
                              INNER JOIN turma t ON tp.turma_id = t.id
                              INNER JOIN escola e ON t.escola_id = e.id
                              WHERE tp.professor_id = :professor_id AND tp.fim IS NULL AND t.ativo = 1
                              LIMIT 1";
            $stmtEscolaTurma = $conn->prepare($sqlEscolaTurma);
            $stmtEscolaTurma->bindParam(':professor_id', $professorId);
            $stmtEscolaTurma->execute();
            $escola = $stmtEscolaTurma->fetch(PDO::FETCH_ASSOC);
            $escolaId = $escola['id'] ?? null;
        }
    }
}

// Buscar turmas do professor (para comunicados específicos de turma)
$turmasProfessor = [];
if ($professorId) {
    $sqlTurmas = "SELECT DISTINCT 
                    t.id as turma_id,
                    CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                    e.nome as escola_nome
                  FROM turma_professor tp
                  INNER JOIN turma t ON tp.turma_id = t.id
                  INNER JOIN escola e ON t.escola_id = e.id
                  WHERE tp.professor_id = :professor_id AND tp.fim IS NULL AND t.ativo = 1
                  ORDER BY t.serie, t.letra";
    $stmtTurmas = $conn->prepare($sqlTurmas);
    $stmtTurmas->bindParam(':professor_id', $professorId);
    $stmtTurmas->execute();
    $turmasProfessor = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'criar_comunicado' && $escolaId) {
        $dados = [
            'turma_id' => !empty($_POST['turma_id']) ? $_POST['turma_id'] : null,
            'aluno_id' => !empty($_POST['aluno_id']) ? $_POST['aluno_id'] : null,
            'escola_id' => $escolaId,
            'titulo' => $_POST['titulo'] ?? '',
            'mensagem' => $_POST['mensagem'] ?? '',
            'tipo' => $_POST['tipo'] ?? 'GERAL',
            'prioridade' => $_POST['prioridade'] ?? 'NORMAL',
            'canal' => $_POST['canal'] ?? 'SISTEMA'
        ];
        
        if (!empty($dados['titulo']) && !empty($dados['mensagem'])) {
            $resultado = $comunicadoModel->criar($dados);
            echo json_encode($resultado);
        } else {
            echo json_encode(['success' => false, 'message' => 'Título e mensagem são obrigatórios']);
        }
        exit;
    }
}

// Buscar comunicados enviados pelo professor
$comunicadosEnviados = [];
if ($_SESSION['usuario_id']) {
    $filtros = [
        'escola_id' => $escolaId,
        'ativo' => 1
    ];
    $todosComunicados = $comunicadoModel->listar($filtros);
    // Filtrar apenas os enviados pelo professor logado
    $comunicadosEnviados = array_filter($todosComunicados, function($c) {
        return $c['enviado_por'] == $_SESSION['usuario_id'];
    });
    $comunicadosEnviados = array_values($comunicadosEnviados); // Reindexar array
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
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunicados - SIGEA</title>
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
    <?php include '../../components/sidebar_professor.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Comunicados para Coordenação</h1>
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
                    <p class="text-gray-600">Envie comunicados à coordenação</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Enviar Comunicado</h2>
                            <p class="text-sm text-gray-600 mt-1">Envie comunicados para a coordenação da escola</p>
                        </div>
                        <button onclick="abrirModalNovoComunicado()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Novo Comunicado</span>
                        </button>
                    </div>
                    
                    <?php if (!$escolaId): ?>
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                            </svg>
                            <p class="text-gray-600">Você não está lotado em nenhuma escola no momento.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Lista de Comunicados Enviados -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Meus Comunicados Enviados</h2>
                    
                    <?php if (empty($comunicadosEnviados)): ?>
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                            </svg>
                            <p class="text-gray-600">Nenhum comunicado enviado ainda.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($comunicadosEnviados as $comunicado): ?>
                                <?php
                                $prioridadeColors = [
                                    'BAIXA' => 'bg-gray-100 text-gray-800',
                                    'NORMAL' => 'bg-blue-100 text-blue-800',
                                    'ALTA' => 'bg-orange-100 text-orange-800',
                                    'URGENTE' => 'bg-red-100 text-red-800'
                                ];
                                
                                $tipoLabels = [
                                    'GERAL' => 'Geral',
                                    'TURMA' => 'Turma',
                                    'ALUNO' => 'Aluno',
                                    'URGENTE' => 'Urgente'
                                ];
                                ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($comunicado['titulo'] ?? 'Sem título') ?></h3>
                                                <span class="text-xs px-2 py-1 rounded-full <?= $prioridadeColors[$comunicado['prioridade']] ?? $prioridadeColors['NORMAL'] ?>">
                                                    <?= $comunicado['prioridade'] ?>
                                                </span>
                                                <span class="text-xs px-2 py-1 rounded-full bg-indigo-100 text-indigo-800">
                                                    <?= $tipoLabels[$comunicado['tipo']] ?? 'Geral' ?>
                                                </span>
                                                <?php if ($comunicado['lido']): ?>
                                                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800">
                                                        Lido
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">
                                                        Não lido
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($comunicado['mensagem']) ?></p>
                                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                <span><?= date('d/m/Y H:i', strtotime($comunicado['criado_em'])) ?></span>
                                                <?php if ($comunicado['turma_nome']): ?>
                                                    <span>Turma: <?= htmlspecialchars($comunicado['turma_nome']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($comunicado['aluno_nome']): ?>
                                                    <span>Aluno: <?= htmlspecialchars($comunicado['aluno_nome']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($comunicado['data_leitura']): ?>
                                                    <span class="text-green-600">Lido em: <?= date('d/m/Y H:i', strtotime($comunicado['data_leitura'])) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Novo Comunicado -->
    <div id="modal-novo-comunicado" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Novo Comunicado para Coordenação</h3>
                <button onclick="fecharModalNovoComunicado()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="form-comunicado" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select id="comunicado-tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="GERAL">Geral</option>
                            <option value="TURMA">Turma Específica</option>
                            <option value="ALUNO">Aluno Específico</option>
                            <option value="URGENTE">Urgente</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioridade</label>
                        <select id="comunicado-prioridade" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="NORMAL">Normal</option>
                            <option value="BAIXA">Baixa</option>
                            <option value="ALTA">Alta</option>
                            <option value="URGENTE">Urgente</option>
                        </select>
                    </div>
                </div>
                
                <div id="comunicado-turma-container" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                    <select id="comunicado-turma-id" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="carregarAlunosParaComunicado()">
                        <option value="">Selecione uma turma</option>
                        <?php foreach ($turmasProfessor as $turma): ?>
                            <option value="<?= $turma['turma_id'] ?>"><?= htmlspecialchars($turma['turma_nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="comunicado-aluno-container" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Aluno</label>
                    <select id="comunicado-aluno-id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecione primeiro a turma</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                    <input type="text" id="comunicado-titulo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Necessidade de reunião">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mensagem *</label>
                    <textarea id="comunicado-mensagem" required rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Descreva o comunicado..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Canal de Envio</label>
                    <select id="comunicado-canal" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="SISTEMA">Sistema</option>
                        <option value="EMAIL">E-mail</option>
                        <option value="SMS">SMS</option>
                        <option value="WHATSAPP">WhatsApp</option>
                        <option value="TODOS">Todos os Canais</option>
                    </select>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="fecharModalNovoComunicado()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg font-medium">
                        Enviar Comunicado
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
        
        function abrirModalNovoComunicado() {
            const modal = document.getElementById('modal-novo-comunicado');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }
        
        function fecharModalNovoComunicado() {
            const modal = document.getElementById('modal-novo-comunicado');
            if (modal) {
                modal.classList.add('hidden');
                document.getElementById('form-comunicado').reset();
                document.getElementById('comunicado-turma-container').classList.add('hidden');
                document.getElementById('comunicado-aluno-container').classList.add('hidden');
            }
        }
        
        document.getElementById('comunicado-tipo').addEventListener('change', function() {
            const tipo = this.value;
            const turmaContainer = document.getElementById('comunicado-turma-container');
            const alunoContainer = document.getElementById('comunicado-aluno-container');
            
            if (tipo === 'TURMA' || tipo === 'ALUNO') {
                turmaContainer.classList.remove('hidden');
                if (tipo === 'ALUNO') {
                    alunoContainer.classList.remove('hidden');
                } else {
                    alunoContainer.classList.add('hidden');
                }
            } else {
                turmaContainer.classList.add('hidden');
                alunoContainer.classList.add('hidden');
            }
        });
        
        function carregarAlunosParaComunicado() {
            const turmaSelect = document.getElementById('comunicado-turma-id');
            const alunoSelect = document.getElementById('comunicado-aluno-id');
            const turmaId = turmaSelect.value;
            
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
        
        document.getElementById('form-comunicado').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('acao', 'criar_comunicado');
            formData.append('tipo', document.getElementById('comunicado-tipo').value);
            formData.append('prioridade', document.getElementById('comunicado-prioridade').value);
            formData.append('titulo', document.getElementById('comunicado-titulo').value);
            formData.append('mensagem', document.getElementById('comunicado-mensagem').value);
            formData.append('canal', document.getElementById('comunicado-canal').value);
            
            const tipo = document.getElementById('comunicado-tipo').value;
            if (tipo === 'TURMA' || tipo === 'ALUNO') {
                const turmaId = document.getElementById('comunicado-turma-id').value;
                if (turmaId) {
                    formData.append('turma_id', turmaId);
                }
            }
            
            if (tipo === 'ALUNO') {
                const alunoId = document.getElementById('comunicado-aluno-id').value;
                if (alunoId) {
                    formData.append('aluno_id', alunoId);
                }
            }
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Comunicado enviado com sucesso!');
                    fecharModalNovoComunicado();
                    location.reload();
                } else {
                    alert('Erro ao enviar comunicado: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao enviar comunicado');
            });
        });
    </script>
</body>
</html>

