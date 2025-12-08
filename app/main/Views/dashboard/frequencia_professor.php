<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/FrequenciaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'professor') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$frequenciaModel = new FrequenciaModel();

// Buscar professor_id
$professorId = null;
$pessoaId = $_SESSION['pessoa_id'] ?? null;
if ($pessoaId) {
    $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
    $stmtProfessor = $conn->prepare($sqlProfessor);
    $pessoaIdParam = $pessoaId;
    $stmtProfessor->bindParam(':pessoa_id', $pessoaIdParam);
    $stmtProfessor->execute();
    $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
    $professorId = $professor['id'] ?? null;
}

// Fallback: tentar obter pessoa_id via usuario_id e CPF se necessário
if (!$professorId) {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    if (!$pessoaId && $usuarioId) {
        $sqlPessoa = "SELECT pessoa_id FROM usuario WHERE id = :usuario_id LIMIT 1";
        $stmtPessoa = $conn->prepare($sqlPessoa);
        $usuarioIdParam = $usuarioId;
        $stmtPessoa->bindParam(':usuario_id', $usuarioIdParam);
        $stmtPessoa->execute();
        $usuario = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
        $pessoaId = $usuario['pessoa_id'] ?? null;
    }
    if (!$pessoaId) {
        $cpf = $_SESSION['cpf'] ?? null;
        if ($cpf) {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
            $sqlPessoaCpf = "SELECT id FROM pessoa WHERE cpf = :cpf LIMIT 1";
            $stmtPessoaCpf = $conn->prepare($sqlPessoaCpf);
            $stmtPessoaCpf->bindParam(':cpf', $cpfLimpo);
            $stmtPessoaCpf->execute();
            $pessoa = $stmtPessoaCpf->fetch(PDO::FETCH_ASSOC);
            $pessoaId = $pessoa['id'] ?? null;
        }
    }
    if ($pessoaId) {
        $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
        $stmtProfessor = $conn->prepare($sqlProfessor);
        $pessoaIdParam = $pessoaId;
        $stmtProfessor->bindParam(':pessoa_id', $pessoaIdParam);
        $stmtProfessor->execute();
        $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
        $professorId = $professor['id'] ?? null;
    }
}

// Buscar turmas e disciplinas do professor
$turmasProfessor = [];
if ($professorId) {
    $sqlTurmas = "SELECT DISTINCT 
                    t.id as turma_id,
                    CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                    t.serie,
                    t.letra,
                    t.turno,
                    d.id as disciplina_id,
                    d.nome as disciplina_nome,
                    e.id as escola_id,
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
    
    if ($_POST['acao'] === 'lancar_frequencia') {
        if (!$professorId) {
            echo json_encode(['success' => false, 'message' => 'Professor não encontrado']);
            exit;
        }
        $turmaId = $_POST['turma_id'] ?? null;
        $disciplinaId = $_POST['disciplina_id'] ?? null;
        $data = $_POST['data'] ?? date('Y-m-d');
        $frequencias = json_decode($_POST['frequencias'] ?? '[]', true);
        
        if ($turmaId && $disciplinaId && !empty($frequencias)) {
            $resultado = $frequenciaModel->registrarLote($turmaId, $data, $frequencias);
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
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Frequência - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'primary-light': '#4CAF50',
                    }
                }
            }
        }
    </script>
    <!-- Estilos minimalistas e profissionais -->
    <style>
        .sidebar-transition { transition: all 0.2s ease; }
        .content-transition { transition: margin-left 0.2s ease; }
        .menu-item.active {
            background: rgba(45, 90, 39, 0.08);
            border-right: 2px solid #2D5A27;
        }
        .menu-item:hover {
            background: rgba(45, 90, 39, 0.05);
        }
        .mobile-menu-overlay {
            transition: opacity 0.2s ease;
        }
        
        /* Cards minimalistas */
        /* Modal fullscreen minimalista */
        .modal-fullscreen {
            animation: fadeIn 0.2s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Botão de presença redondo minimalista */
        .presenca-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #d1d5db;
            background: #fff;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .presenca-toggle:hover {
            border-color: #9ca3af;
        }
        .presenca-toggle.presente {
            border-color: #2D5A27;
            background: #2D5A27;
        }
        .presenca-toggle.ausente {
            border-color: #dc2626;
            background: #dc2626;
        }
        /* Adicionando estilo para falta justificada (amarelo) */
        .presenca-toggle.justificada {
            border-color: #d97706;
            background: #d97706;
        }
        .presenca-toggle svg {
            width: 20px;
            height: 20px;
            color: #9ca3af;
        }
        .presenca-toggle.presente svg,
        .presenca-toggle.ausente svg,
        .presenca-toggle.justificada svg {
            color: #fff;
        }
        
        /* Card do aluno minimalista */
        .aluno-row {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: border-color 0.15s ease;
        }
        .aluno-row:hover {
            border-color: #d1d5db;
        }
        .aluno-row.ausente {
            background: #fef2f2;
            border-color: #fecaca;
        }
        .aluno-row.presente {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        /* Estilo do card quando falta justificada */
        .aluno-row.justificada {
            background: #fffbeb;
            border-color: #fde68a;
        }
        
        /* Campo de justificativa */
        .justificativa-field {
            display: none;
            width: 100%;
            margin-top: 8px;
            padding-left: 48px;
        }
        .justificativa-field.show {
            display: block;
        }
        .justificativa-field input {
            width: 100%;
            padding: 6px 10px;
            font-size: 12px;
            border: 1px solid #fde68a;
            border-radius: 6px;
            background: #fffbeb;
            outline: none;
        }
        .justificativa-field input:focus {
            border-color: #d97706;
        }
        .justificativa-field input::placeholder {
            color: #a3a3a3;
        }
        
        /* Aluno row com wrapper para justificativa */
        .aluno-wrapper {
            display: flex;
            flex-direction: column;
        }
        
        /* Grid responsivo */
        .alunos-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 8px;
        }
        @media (max-width: 640px) {
            .alunos-list {
                grid-template-columns: 1fr;
            }
        }
        
        /* Scrollbar discreta */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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
        <!-- Header simplificado -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-14">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">Frequência</h1>
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
                    <p class="text-gray-600">Registre a presença dos alunos nas suas turmas</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Minhas Turmas</h2>
                        <button onclick="abrirModalLancarFrequencia()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Registrar Frequência</span>
                        </button>
                    </div>
                    
                    <?php if (empty($turmasProfessor)): ?>
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-600">Você não possui turmas atribuídas no momento.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($turmasProfessor as $turma): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                                    <div class="mb-3">
                                        <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($turma['turma_nome']) ?></h3>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($turma['disciplina_nome']) ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($turma['escola_nome']) ?></p>
                                    </div>
                                    <button onclick="abrirModalLancarFrequencia(<?= $turma['turma_id'] ?>, <?= $turma['disciplina_id'] ?>, '<?= htmlspecialchars($turma['turma_nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['disciplina_nome'], ENT_QUOTES) ?>')" class="w-full text-green-600 hover:text-green-700 font-medium text-sm py-2 border border-green-200 rounded-lg hover:bg-green-50 transition-colors">
                                        Registrar Frequência
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal fullscreen minimalista e profissional -->
    <div id="modal-lancar-frequencia" class="fixed inset-0 bg-gray-900/50 z-[60] hidden" style="display: none;">
        <div class="h-full w-full bg-gray-50 flex flex-col modal-fullscreen">
            <!-- Header do modal - compacto -->
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <button onclick="fecharModalLancarFrequencia()" class="p-1.5 rounded hover:bg-gray-100 text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div>
                        <h3 class="font-semibold text-gray-900">Registrar Frequência</h3>
                        <p id="frequencia-info-turma" class="text-xs text-gray-500">Selecione uma turma</p>
                    </div>
                </div>
                <button onclick="salvarFrequencia()" class="bg-primary-green hover:bg-green-700 text-white text-sm font-medium py-2 px-4 rounded transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Salvar
                </button>
            </div>
            
            <!-- Barra de controles - compacta -->
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 flex flex-wrap items-center gap-3 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-medium text-gray-600">Data:</label>
                    <input type="date" id="frequencia-data" value="<?= date('Y-m-d') ?>" class="text-sm px-3 py-1.5 border border-gray-300 rounded focus:border-primary-green focus:ring-1 focus:ring-primary-green focus:outline-none">
                </div>
                <div class="h-4 w-px bg-gray-300 hidden sm:block"></div>
                <div class="flex items-center gap-2 text-xs">
                    <span id="alunos-count" class="font-medium text-gray-700 bg-gray-100 px-2 py-1 rounded">0 alunos</span>
                    <span id="presentes-count" class="text-green-700 bg-green-50 px-2 py-1 rounded">0 P</span>
                    <span id="ausentes-count" class="text-red-700 bg-red-50 px-2 py-1 rounded">0 F</span>
                    <!-- Contador de faltas justificadas -->
                    <span id="justificadas-count" class="text-amber-700 bg-amber-50 px-2 py-1 rounded">0 FJ</span>
                </div>
                <div class="h-4 w-px bg-gray-300 hidden sm:block"></div>
                <div class="flex items-center gap-2">
                    <button onclick="marcarTodosPresentes()" class="text-xs font-medium text-green-700 hover:bg-green-50 px-2 py-1 rounded transition-colors">
                        Todos P
                    </button>
                    <button onclick="marcarTodosAusentes()" class="text-xs font-medium text-red-700 hover:bg-red-50 px-2 py-1 rounded transition-colors">
                        Todos F
                    </button>
                </div>
                <!-- Dica de uso para falta justificada -->
                <div class="hidden sm:flex items-center gap-1 text-xs text-gray-400 ml-auto">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Segure o botao para falta justificada</span>
                </div>
                <input type="hidden" id="frequencia-turma-id">
                <input type="hidden" id="frequencia-disciplina-id">
            </div>
            
            <!-- Lista de alunos -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-4 sm:p-6">
                <div id="frequencia-alunos-container" class="alunos-list max-w-4xl mx-auto">
                    <!-- Estado vazio -->
                    <div class="col-span-full text-center py-12">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-2a6 6 0 0112 0v2zm0 0h6v-2a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">Selecione uma turma para carregar os alunos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full shadow-lg">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Confirmar Saída</h3>
                    <p class="text-sm text-gray-500 mt-1">Tem certeza que deseja sair do sistema?</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="window.closeLogoutModal()" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded transition-colors">
                    Cancelar
                </button>
                <button onclick="window.logout()" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded transition-colors">
                    Sair
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
        
        let longPressTimer = null;
        const LONG_PRESS_DURATION = 500; // 500ms para ativar falta justificada
        
        function abrirModalLancarFrequencia(turmaId = null, disciplinaId = null, turmaNome = '', disciplinaNome = '') {
            const modal = document.getElementById('modal-lancar-frequencia');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                if (turmaId && disciplinaId) {
                    document.getElementById('frequencia-turma-id').value = turmaId;
                    document.getElementById('frequencia-disciplina-id').value = disciplinaId;
                    
                    const infoElement = document.getElementById('frequencia-info-turma');
                    if (infoElement && turmaNome && disciplinaNome) {
                        infoElement.textContent = turmaNome + ' - ' + disciplinaNome;
                    } else if (infoElement) {
                        buscarInfoTurma(turmaId, disciplinaId);
                    }
                    
                    carregarAlunosParaFrequencia(turmaId);
                }
            }
        }
        
        function buscarInfoTurma(turmaId, disciplinaId) {
            fetch('?acao=buscar_info_turma&turma_id=' + turmaId + '&disciplina_id=' + disciplinaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const infoElement = document.getElementById('frequencia-info-turma');
                        if (infoElement && data.turma_nome && data.disciplina_nome) {
                            infoElement.textContent = data.turma_nome + ' - ' + data.disciplina_nome;
                        }
                    }
                })
                .catch(error => console.error('Erro ao buscar info da turma:', error));
        }
        
        function fecharModalLancarFrequencia() {
            const modal = document.getElementById('modal-lancar-frequencia');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }
        
        function carregarAlunosParaFrequencia(turmaId) {
            fetch('?acao=buscar_alunos_turma&turma_id=' + turmaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.alunos) {
                        const container = document.getElementById('frequencia-alunos-container');
                        container.innerHTML = '';
                        
                        const totalAlunos = data.alunos.length;
                        document.getElementById('alunos-count').textContent = totalAlunos + ' alunos';
                        
                        data.alunos.forEach((aluno, index) => {
                            const wrapper = document.createElement('div');
                            wrapper.className = 'aluno-wrapper';
                            
                            const div = document.createElement('div');
                            div.className = 'aluno-row presente';
                            div.setAttribute('data-aluno-id', aluno.id);
                            div.setAttribute('data-status', 'presente'); // presente, ausente, justificada
                            div.setAttribute('data-justificativa', '');
                            
                            const iniciais = aluno.nome.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
                            
                            div.innerHTML = `
                                <div class="aluno-avatar-sm">${iniciais}</div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">${aluno.nome}</p>
                                    ${aluno.matricula ? `<p class="text-xs text-gray-400">${aluno.matricula}</p>` : ''}
                                </div>
                                <button type="button" class="presenca-toggle presente" data-aluno-id="${aluno.id}">
                                    <svg class="icon-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <svg class="icon-x hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <svg class="icon-justified hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </button>
                            `;
                            
                            const justificativaField = document.createElement('div');
                            justificativaField.className = 'justificativa-field';
                            justificativaField.innerHTML = `
                                <input type="text" placeholder="Motivo da falta (opcional)" data-aluno-id="${aluno.id}" onchange="atualizarJustificativa(this, ${aluno.id})">
                            `;
                            
                            wrapper.appendChild(div);
                            wrapper.appendChild(justificativaField);
                            container.appendChild(wrapper);
                            
                            const button = div.querySelector('.presenca-toggle');
                            setupLongPress(button, aluno.id);
                        });
                        
                        atualizarContadores();
                    }
                })
                .catch(error => console.error('Erro ao carregar alunos:', error));
        }
        
        function setupLongPress(button, alunoId) {
            let pressTimer = null;
            let isLongPress = false;
            
            const startPress = (e) => {
                isLongPress = false;
                pressTimer = setTimeout(() => {
                    isLongPress = true;
                    marcarFaltaJustificada(button, alunoId);
                }, LONG_PRESS_DURATION);
            };
            
            const endPress = (e) => {
                clearTimeout(pressTimer);
                if (!isLongPress) {
                    togglePresenca(button, alunoId);
                }
            };
            
            const cancelPress = () => {
                clearTimeout(pressTimer);
            };
            
            // Mouse events
            button.addEventListener('mousedown', startPress);
            button.addEventListener('mouseup', endPress);
            button.addEventListener('mouseleave', cancelPress);
            
            // Touch events
            button.addEventListener('touchstart', (e) => {
                e.preventDefault();
                startPress(e);
            });
            button.addEventListener('touchend', (e) => {
                e.preventDefault();
                endPress(e);
            });
            button.addEventListener('touchcancel', cancelPress);
            
            // Prevenir click padrao
            button.addEventListener('click', (e) => {
                e.preventDefault();
            });
        }
        
        function marcarFaltaJustificada(button, alunoId) {
            const row = button.closest('.aluno-row');
            const wrapper = row.closest('.aluno-wrapper');
            const justificativaField = wrapper.querySelector('.justificativa-field');
            const iconCheck = button.querySelector('.icon-check');
            const iconX = button.querySelector('.icon-x');
            const iconJustified = button.querySelector('.icon-justified');
            
            row.setAttribute('data-status', 'justificada');
            row.className = 'aluno-row justificada';
            button.className = 'presenca-toggle justificada';
            
            iconCheck.classList.add('hidden');
            iconX.classList.add('hidden');
            iconJustified.classList.remove('hidden');
            
            // Mostrar campo de justificativa
            justificativaField.classList.add('show');
            justificativaField.querySelector('input').focus();
            
            atualizarContadores();
        }
        
        function atualizarJustificativa(input, alunoId) {
            const wrapper = input.closest('.aluno-wrapper');
            const row = wrapper.querySelector('.aluno-row');
            row.setAttribute('data-justificativa', input.value);
        }
        
        function togglePresenca(button, alunoId) {
            const row = button.closest('.aluno-row');
            const wrapper = row.closest('.aluno-wrapper');
            const justificativaField = wrapper.querySelector('.justificativa-field');
            const status = row.getAttribute('data-status');
            const iconCheck = button.querySelector('.icon-check');
            const iconX = button.querySelector('.icon-x');
            const iconJustified = button.querySelector('.icon-justified');
            
            if (status === 'presente') {
                row.setAttribute('data-status', 'ausente');
                row.className = 'aluno-row ausente';
                button.className = 'presenca-toggle ausente';
                iconCheck.classList.add('hidden');
                iconX.classList.remove('hidden');
                iconJustified.classList.add('hidden');
                justificativaField.classList.remove('show');
            } else {
                row.setAttribute('data-status', 'presente');
                row.setAttribute('data-justificativa', '');
                row.className = 'aluno-row presente';
                button.className = 'presenca-toggle presente';
                iconCheck.classList.remove('hidden');
                iconX.classList.add('hidden');
                iconJustified.classList.add('hidden');
                justificativaField.classList.remove('show');
                justificativaField.querySelector('input').value = '';
            }
            
            atualizarContadores();
        }
        
        function marcarTodosPresentes() {
            document.querySelectorAll('.aluno-wrapper').forEach(wrapper => {
                const row = wrapper.querySelector('.aluno-row');
                const button = row.querySelector('.presenca-toggle');
                const justificativaField = wrapper.querySelector('.justificativa-field');
                const iconCheck = button.querySelector('.icon-check');
                const iconX = button.querySelector('.icon-x');
                const iconJustified = button.querySelector('.icon-justified');
                
                row.setAttribute('data-status', 'presente');
                row.setAttribute('data-justificativa', '');
                row.className = 'aluno-row presente';
                button.className = 'presenca-toggle presente';
                iconCheck.classList.remove('hidden');
                iconX.classList.add('hidden');
                iconJustified.classList.add('hidden');
                justificativaField.classList.remove('show');
                justificativaField.querySelector('input').value = '';
            });
            atualizarContadores();
        }
        
        function marcarTodosAusentes() {
            document.querySelectorAll('.aluno-wrapper').forEach(wrapper => {
                const row = wrapper.querySelector('.aluno-row');
                const button = row.querySelector('.presenca-toggle');
                const justificativaField = wrapper.querySelector('.justificativa-field');
                const iconCheck = button.querySelector('.icon-check');
                const iconX = button.querySelector('.icon-x');
                const iconJustified = button.querySelector('.icon-justified');
                
                row.setAttribute('data-status', 'ausente');
                row.setAttribute('data-justificativa', '');
                row.className = 'aluno-row ausente';
                button.className = 'presenca-toggle ausente';
                iconCheck.classList.add('hidden');
                iconX.classList.remove('hidden');
                iconJustified.classList.add('hidden');
                justificativaField.classList.remove('show');
                justificativaField.querySelector('input').value = '';
            });
            atualizarContadores();
        }
        
        function atualizarContadores() {
            const rows = document.querySelectorAll('.aluno-row');
            let presentes = 0;
            let ausentes = 0;
            let justificadas = 0;
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (status === 'presente') {
                    presentes++;
                } else if (status === 'ausente') {
                    ausentes++;
                } else if (status === 'justificada') {
                    justificadas++;
                }
            });
            
            document.getElementById('presentes-count').textContent = presentes + ' P';
            document.getElementById('ausentes-count').textContent = ausentes + ' F';
            document.getElementById('justificadas-count').textContent = justificadas + ' FJ';
        }
        
        function salvarFrequencia() {
            const turmaId = document.getElementById('frequencia-turma-id').value;
            const disciplinaId = document.getElementById('frequencia-disciplina-id').value;
            const data = document.getElementById('frequencia-data').value;
            
            if (!turmaId || !disciplinaId || !data) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            const frequencias = [];
            document.querySelectorAll('.aluno-row').forEach(row => {
                const status = row.getAttribute('data-status');
                const justificativa = row.getAttribute('data-justificativa') || '';
                
                frequencias.push({
                    aluno_id: row.getAttribute('data-aluno-id'),
                    presente: status === 'presente' ? 1 : 0,
                    justificada: status === 'justificada' ? 1 : 0,
                    justificativa: status === 'justificada' ? justificativa : ''
                });
            });
            
            if (frequencias.length === 0) {
                alert('Nenhum aluno disponível para registrar frequência.');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'lancar_frequencia');
            formData.append('turma_id', turmaId);
            formData.append('disciplina_id', disciplinaId);
            formData.append('data', data);
            formData.append('frequencias', JSON.stringify(frequencias));
            
            fetch('', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Frequência registrada com sucesso!');
                        fecharModalLancarFrequencia();
                    } else {
                        alert('Erro ao registrar frequência: ' + (data.message || 'Tente novamente.'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao salvar frequência. Tente novamente.');
                });
        }
    </script>
</body>
</html>
