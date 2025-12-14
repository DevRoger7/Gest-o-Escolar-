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
                  WHERE tp.professor_id = :professor_id AND tp.fim IS NULL AND t.ativo = 1";
    
    // Filtrar por escola selecionada se houver
    $escolaIdSelecionada = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
    if ($escolaIdSelecionada) {
        $sqlTurmas .= " AND t.escola_id = :escola_id";
    }
    
    $sqlTurmas .= " ORDER BY t.serie, t.letra, d.nome";
    $stmtTurmas = $conn->prepare($sqlTurmas);
    $stmtTurmas->bindParam(':professor_id', $professorId);
    if ($escolaIdSelecionada) {
        $stmtTurmas->bindParam(':escola_id', $escolaIdSelecionada, PDO::PARAM_INT);
    }
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
        $sql = "SELECT a.id, p.nome, COALESCE(a.matricula, '') as matricula
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
    
    if ($_GET['acao'] === 'buscar_frequencia_data' && !empty($_GET['turma_id']) && !empty($_GET['data'])) {
        $turmaId = $_GET['turma_id'];
        $data = $_GET['data'];
        
        try {
            $frequencias = $frequenciaModel->buscarPorTurmaData($turmaId, $data);
            echo json_encode(['success' => true, 'frequencias' => $frequencias]);
        } catch (Exception $e) {
            error_log("Erro ao buscar frequência: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar frequência: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_frequencia' && !empty($_GET['frequencia_id'])) {
        $frequenciaId = $_GET['frequencia_id'];
        $frequencia = $frequenciaModel->buscarPorId($frequenciaId);
        if ($frequencia) {
            echo json_encode(['success' => true, 'frequencia' => $frequencia]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Frequência não encontrada']);
        }
        exit;
    }
}

// Processar atualização de frequência
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_frequencia' && $professorId) {
    header('Content-Type: application/json');
    
    $frequenciaId = $_POST['frequencia_id'] ?? null;
    $presenca = $_POST['presenca'] ?? null;
    $observacao = $_POST['observacao'] ?? null;
    
    if (!$frequenciaId || $presenca === null) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        exit;
    }
    
    try {
        $result = $frequenciaModel->atualizar($frequenciaId, [
            'presenca' => $presenca,
            'observacao' => $observacao
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Frequência atualizada com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar frequência']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
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
        
        /* Animação para modal de sucesso */
        @keyframes bounce-in {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }
            50% {
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-sucesso-icon {
            animation: bounce-in 0.6s ease-out;
        }
        
        .modal-sucesso-content {
            transition: transform 0.2s ease-out;
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
                                            <?php echo !empty($_SESSION['escola_atual']) ? htmlspecialchars($_SESSION['escola_atual']) : 'N/A'; ?>
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
                                    <div class="flex gap-2">
                                        <button onclick="abrirModalHistoricoFrequencia(<?= $turma['turma_id'] ?>, '<?= htmlspecialchars($turma['turma_nome'], ENT_QUOTES) ?>')" class="flex-1 text-blue-600 hover:text-blue-700 font-medium text-sm py-2 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors">
                                            Ver Histórico
                                        </button>
                                        <button onclick="abrirModalLancarFrequencia(<?= $turma['turma_id'] ?>, <?= $turma['disciplina_id'] ?>, '<?= htmlspecialchars($turma['turma_nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['disciplina_nome'], ENT_QUOTES) ?>')" class="flex-1 text-green-600 hover:text-green-700 font-medium text-sm py-2 border border-green-200 rounded-lg hover:bg-green-50 transition-colors">
                                            Registrar
                                        </button>
                                    </div>
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
                    <input type="date" id="frequencia-data" value="<?= date('Y-m-d') ?>" class="text-sm px-3 py-1.5 border border-gray-300 rounded focus:border-primary-green focus:ring-1 focus:ring-primary-green focus:outline-none" onchange="recarregarFrequenciaComData()">
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
    
    <!-- Modal Histórico de Frequência -->
    <div id="modal-historico-frequencia" class="fixed inset-0 bg-gray-50 z-[60] hidden flex flex-col">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="fecharModalHistoricoFrequencia()" class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Histórico de Frequência</h3>
                    <p id="historico-turma-info" class="text-xs text-gray-500"></p>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="bg-white border-b border-gray-100 px-4 py-3">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Selecione a Data</label>
                    <input type="date" id="historico-data" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-green-500" onchange="carregarFrequenciaPorData()">
                </div>
            </div>
            <input type="hidden" id="historico-turma-id">
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="max-w-5xl mx-auto py-4 px-4">
                <div id="historico-frequencia-container" class="space-y-2">
                    <div class="text-center py-16 text-gray-400">
                        <p class="text-sm">Selecione uma data para ver o histórico de frequência</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Frequência -->
    <div id="modal-editar-frequencia" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Editar Frequência</h3>
                <button onclick="fecharModalEditarFrequencia()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aluno</label>
                    <p id="editar-frequencia-aluno" class="text-sm text-gray-600"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <div class="flex gap-3">
                        <button onclick="selecionarStatusFrequencia('presente')" id="btn-status-presente" class="flex-1 px-4 py-2 border-2 border-gray-200 rounded-lg font-medium transition-colors hover:bg-green-50">
                            Presente
                        </button>
                        <button onclick="selecionarStatusFrequencia('ausente')" id="btn-status-ausente" class="flex-1 px-4 py-2 border-2 border-gray-200 rounded-lg font-medium transition-colors hover:bg-red-50">
                            Ausente
                        </button>
                        <button onclick="selecionarStatusFrequencia('justificada')" id="btn-status-justificada" class="flex-1 px-4 py-2 border-2 border-gray-200 rounded-lg font-medium transition-colors hover:bg-amber-50">
                            Justificada
                        </button>
                    </div>
                </div>
                
                <div id="editar-frequencia-justificativa-container" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Justificativa</label>
                    <textarea id="editar-frequencia-justificativa" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-lg" placeholder="Digite a justificativa..."></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button onclick="fecharModalEditarFrequencia()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                    Cancelar
                </button>
                <button onclick="salvarEdicaoFrequencia()" class="flex-1 px-4 py-2 text-white bg-green-600 hover:bg-green-700 rounded-lg font-medium transition-colors">
                    Salvar
                </button>
            </div>
            
            <input type="hidden" id="editar-frequencia-id">
            <input type="hidden" id="editar-frequencia-status">
        </div>
    </div>
    
    <!-- Modal de Sucesso -->
    <div id="modal-sucesso" class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 shadow-2xl modal-sucesso-content scale-95">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4 modal-sucesso-icon">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Sucesso!</h3>
                <p id="modal-sucesso-mensagem" class="text-sm text-gray-600 mb-6"></p>
                <button onclick="fecharModalSucesso()" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                    OK
                </button>
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
        
        function recarregarFrequenciaComData() {
            const turmaId = document.getElementById('frequencia-turma-id').value;
            if (turmaId) {
                carregarAlunosParaFrequencia(turmaId);
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
            const data = document.getElementById('frequencia-data').value;
            
            // Buscar alunos e frequências em paralelo
            Promise.all([
                fetch('?acao=buscar_alunos_turma&turma_id=' + turmaId).then(r => r.json()),
                fetch('?acao=buscar_frequencia_data&turma_id=' + turmaId + '&data=' + data).then(r => r.json())
            ])
            .then(([alunosData, frequenciasData]) => {
                if (alunosData.success && alunosData.alunos) {
                    const container = document.getElementById('frequencia-alunos-container');
                    container.innerHTML = '';
                    
                    const totalAlunos = alunosData.alunos.length;
                    document.getElementById('alunos-count').textContent = totalAlunos + ' alunos';
                    
                    // Criar mapa de frequências por aluno_id
                    const frequenciasMap = {};
                    if (frequenciasData.success && frequenciasData.frequencias) {
                        frequenciasData.frequencias.forEach(freq => {
                            frequenciasMap[freq.aluno_id] = freq;
                        });
                    }
                    
                    alunosData.alunos.forEach((aluno, index) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'aluno-wrapper';
                        
                        // Verificar se há frequência registrada para este aluno
                        const frequencia = frequenciasMap[aluno.id];
                        let status = 'presente';
                        let justificativa = '';
                        let statusClass = 'presente';
                        
                        if (frequencia) {
                            if (frequencia.presenca == 1) {
                                status = 'presente';
                                statusClass = 'presente';
                            } else if (frequencia.observacao) {
                                status = 'justificada';
                                statusClass = 'justificada';
                                justificativa = frequencia.observacao;
                            } else {
                                status = 'ausente';
                                statusClass = 'ausente';
                            }
                        }
                        
                        const div = document.createElement('div');
                        div.className = `aluno-row ${statusClass}`;
                        div.setAttribute('data-aluno-id', aluno.id);
                        div.setAttribute('data-status', status);
                        div.setAttribute('data-justificativa', justificativa);
                        
                        const iniciais = aluno.nome.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
                        
                        // Determinar quais ícones mostrar
                        const iconCheckClass = status === 'presente' ? '' : 'hidden';
                        const iconXClass = status === 'ausente' ? '' : 'hidden';
                        const iconJustifiedClass = status === 'justificada' ? '' : 'hidden';
                        
                        div.innerHTML = `
                            <div class="aluno-avatar-sm">${iniciais}</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${aluno.nome}</p>
                                ${aluno.matricula ? `<p class="text-xs text-gray-400">${aluno.matricula}</p>` : ''}
                            </div>
                            <button type="button" class="presenca-toggle ${statusClass}" data-aluno-id="${aluno.id}">
                                <svg class="icon-check ${iconCheckClass}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <svg class="icon-x ${iconXClass}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <svg class="icon-justified ${iconJustifiedClass}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </button>
                        `;
                        
                        const justificativaField = document.createElement('div');
                        justificativaField.className = status === 'justificada' ? 'justificativa-field show' : 'justificativa-field';
                        justificativaField.innerHTML = `
                            <input type="text" placeholder="Motivo da falta (opcional)" data-aluno-id="${aluno.id}" value="${justificativa}" onchange="atualizarJustificativa(this, ${aluno.id})">
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
            .catch(error => {
                console.error('Erro ao carregar alunos:', error);
                alert('Erro ao carregar alunos e frequências');
            });
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
                    mostrarModalSucesso('Frequência registrada com sucesso!');
                    // Recarregar os alunos para mostrar o que foi salvo
                    setTimeout(() => {
                        carregarAlunosParaFrequencia(turmaId);
                    }, 500);
                } else {
                    alert('Erro ao registrar frequência: ' + (data.message || 'Tente novamente.'));
                }
            })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao salvar frequência. Tente novamente.');
                });
        }
        
        // Funções para modal de histórico
        function abrirModalHistoricoFrequencia(turmaId, turmaNome) {
            const modal = document.getElementById('modal-historico-frequencia');
            if (modal) {
                modal.classList.remove('hidden');
                document.getElementById('historico-turma-id').value = turmaId;
                document.getElementById('historico-turma-info').textContent = turmaNome;
                
                // Definir data padrão como hoje
                const hoje = new Date().toISOString().split('T')[0];
                document.getElementById('historico-data').value = hoje;
                
                // Carregar frequência do dia
                setTimeout(() => {
                    carregarFrequenciaPorData();
                }, 100);
            }
        }
        
        function fecharModalHistoricoFrequencia() {
            const modal = document.getElementById('modal-historico-frequencia');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        function carregarFrequenciaPorData() {
            const turmaId = document.getElementById('historico-turma-id').value;
            const data = document.getElementById('historico-data').value;
            
            if (!turmaId || !data) {
                return;
            }
            
            fetch(`?acao=buscar_frequencia_data&turma_id=${turmaId}&data=${data}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.frequencias) {
                        const container = document.getElementById('historico-frequencia-container');
                        container.innerHTML = '';
                        
                        if (data.frequencias.length === 0) {
                            container.innerHTML = '<div class="text-center py-16 text-gray-400"><p class="text-sm">Nenhuma frequência registrada para esta data</p></div>';
                            return;
                        }
                        
                        // Header da tabela
                        const header = document.createElement('div');
                        header.className = 'grid grid-cols-12 gap-3 text-xs font-medium text-gray-500 uppercase tracking-wide px-3 py-2 border-b border-gray-200 mb-2';
                        header.innerHTML = `
                            <div class="col-span-5">Aluno</div>
                            <div class="col-span-2 text-center">Status</div>
                            <div class="col-span-4">Observação</div>
                            <div class="col-span-1 text-center">Ação</div>
                        `;
                        container.appendChild(header);
                        
                        // Renderizar frequências
                        data.frequencias.forEach(freq => {
                            const div = document.createElement('div');
                            div.className = 'grid grid-cols-12 gap-3 items-center px-3 py-3 bg-white rounded-lg border border-gray-100 hover:bg-gray-50';
                            
                            const status = freq.presenca == 1 ? 'Presente' : (freq.observacao ? 'Justificada' : 'Ausente');
                            const statusClass = freq.presenca == 1 ? 'text-green-600 bg-green-50' : (freq.observacao ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50');
                            
                            div.innerHTML = `
                                <div class="col-span-5">
                                    <div class="text-sm font-medium text-gray-900">${freq.aluno_nome}</div>
                                    ${freq.aluno_matricula ? `<div class="text-xs text-gray-400">${freq.aluno_matricula}</div>` : ''}
                                </div>
                                <div class="col-span-2 text-center">
                                    <span class="px-2 py-1 text-xs font-medium rounded ${statusClass}">${status}</span>
                                </div>
                                <div class="col-span-4 text-sm text-gray-600 truncate" title="${freq.observacao || ''}">${freq.observacao || '-'}</div>
                                <div class="col-span-1 text-center">
                                    <button onclick="editarFrequencia(${freq.id})" class="px-2 py-1 text-xs font-medium text-green-600 hover:text-green-700 hover:bg-green-50 rounded transition-colors">
                                        Editar
                                    </button>
                                </div>
                            `;
                            container.appendChild(div);
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar frequência:', error);
                    alert('Erro ao carregar frequência');
                });
        }
        
        // Funções para editar frequência
        function editarFrequencia(frequenciaId) {
            fetch(`?acao=buscar_frequencia&frequencia_id=${frequenciaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.frequencia) {
                        const freq = data.frequencia;
                        document.getElementById('editar-frequencia-id').value = frequenciaId;
                        document.getElementById('editar-frequencia-aluno').textContent = freq.aluno_nome + (freq.aluno_matricula ? ' - ' + freq.aluno_matricula : '');
                        
                        // Determinar status atual
                        let statusAtual = 'ausente';
                        if (freq.presenca == 1) {
                            statusAtual = 'presente';
                        } else if (freq.observacao) {
                            statusAtual = 'justificada';
                        }
                        
                        document.getElementById('editar-frequencia-status').value = statusAtual;
                        selecionarStatusFrequencia(statusAtual);
                        
                        if (freq.observacao) {
                            document.getElementById('editar-frequencia-justificativa').value = freq.observacao;
                        }
                        
                        const modal = document.getElementById('modal-editar-frequencia');
                        if (modal) {
                            modal.classList.remove('hidden');
                            modal.style.display = 'flex';
                        }
                    } else {
                        alert('Erro ao carregar dados da frequência');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar dados da frequência');
                });
        }
        
        function selecionarStatusFrequencia(status) {
            document.getElementById('editar-frequencia-status').value = status;
            
            // Resetar todos os botões
            document.getElementById('btn-status-presente').classList.remove('border-green-500', 'bg-green-50', 'text-green-700');
            document.getElementById('btn-status-presente').classList.add('border-gray-200');
            document.getElementById('btn-status-ausente').classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
            document.getElementById('btn-status-ausente').classList.add('border-gray-200');
            document.getElementById('btn-status-justificada').classList.remove('border-amber-500', 'bg-amber-50', 'text-amber-700');
            document.getElementById('btn-status-justificada').classList.add('border-gray-200');
            
            // Ativar botão selecionado
            if (status === 'presente') {
                document.getElementById('btn-status-presente').classList.add('border-green-500', 'bg-green-50', 'text-green-700');
                document.getElementById('editar-frequencia-justificativa-container').classList.add('hidden');
            } else if (status === 'ausente') {
                document.getElementById('btn-status-ausente').classList.add('border-red-500', 'bg-red-50', 'text-red-700');
                document.getElementById('editar-frequencia-justificativa-container').classList.add('hidden');
            } else if (status === 'justificada') {
                document.getElementById('btn-status-justificada').classList.add('border-amber-500', 'bg-amber-50', 'text-amber-700');
                document.getElementById('editar-frequencia-justificativa-container').classList.remove('hidden');
            }
        }
        
        function fecharModalEditarFrequencia() {
            const modal = document.getElementById('modal-editar-frequencia');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }
        
        // Funções para modal de sucesso
        function mostrarModalSucesso(mensagem) {
            const modal = document.getElementById('modal-sucesso');
            const mensagemElement = document.getElementById('modal-sucesso-mensagem');
            if (modal && mensagemElement) {
                mensagemElement.textContent = mensagem;
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                
                setTimeout(() => {
                    const modalContent = modal.querySelector('.modal-sucesso-content');
                    if (modalContent) {
                        modalContent.classList.remove('scale-95');
                        modalContent.classList.add('scale-100');
                    }
                }, 10);
            }
        }
        
        function fecharModalSucesso() {
            const modal = document.getElementById('modal-sucesso');
            if (modal) {
                const modalContent = modal.querySelector('.modal-sucesso-content');
                if (modalContent) {
                    modalContent.classList.remove('scale-100');
                    modalContent.classList.add('scale-95');
                }
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                }, 200);
            }
        }
        
        function salvarEdicaoFrequencia() {
            const frequenciaId = document.getElementById('editar-frequencia-id').value;
            const status = document.getElementById('editar-frequencia-status').value;
            const justificativa = document.getElementById('editar-frequencia-justificativa').value;
            
            if (!frequenciaId || !status) {
                alert('Por favor, selecione um status');
                return;
            }
            
            const presenca = status === 'presente' ? 1 : 0;
            const observacao = (status === 'justificada' && justificativa) ? justificativa : (status === 'justificada' ? 'Falta justificada' : null);
            
            const formData = new FormData();
            formData.append('acao', 'editar_frequencia');
            formData.append('frequencia_id', frequenciaId);
            formData.append('presenca', presenca);
            formData.append('observacao', observacao || '');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarModalSucesso('Frequência atualizada com sucesso!');
                    fecharModalEditarFrequencia();
                    setTimeout(() => {
                        carregarFrequenciaPorData();
                    }, 1500);
                } else {
                    alert('Erro ao atualizar frequência: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar frequência');
            });
        }
    </script>
</body>
</html>
