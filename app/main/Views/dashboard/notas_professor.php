<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/NotaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'professor') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$notaModel = new NotaModel();

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
    
    if ($_POST['acao'] === 'lancar_notas' && $professorId) {
        $turmaId = $_POST['turma_id'] ?? null;
        $disciplinaId = $_POST['disciplina_id'] ?? null;
        $notas = json_decode($_POST['notas'] ?? '[]', true);
        
        if ($turmaId && $disciplinaId && !empty($notas)) {
            try {
                $conn->beginTransaction();
                
                // Criar cache de avaliações para evitar múltiplas consultas
                $avaliacoesCache = [];
                $bimestre = $notas[0]['bimestre'] ?? 1;
                
                // Buscar ou criar avaliações para PARCIAL e BIMESTRAL
                foreach (['PARCIAL', 'BIMESTRAL'] as $tipo) {
                    $tipoAvaliacao = ($tipo === 'PARCIAL') ? 'ATIVIDADE' : 'PROVA';
                    $sqlAvaliacao = "SELECT id FROM avaliacao 
                                    WHERE turma_id = :turma_id 
                                    AND disciplina_id = :disciplina_id 
                                    AND tipo = :tipo 
                                    AND DATE_FORMAT(data, '%Y') = YEAR(CURDATE())
                                    AND ativo = 1
                                    LIMIT 1";
                    $stmtAvaliacao = $conn->prepare($sqlAvaliacao);
                    $stmtAvaliacao->bindParam(':turma_id', $turmaId);
                    $stmtAvaliacao->bindParam(':disciplina_id', $disciplinaId);
                    $stmtAvaliacao->bindValue(':tipo', $tipoAvaliacao);
                    $stmtAvaliacao->execute();
                    $avaliacao = $stmtAvaliacao->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$avaliacao) {
                        // Criar nova avaliação
                        $titulo = ($tipo === 'PARCIAL') ? "Avaliação Parcial - {$bimestre}º Bimestre" : "Avaliação Bimestral - {$bimestre}º Bimestre";
                        $sqlInsertAvaliacao = "INSERT INTO avaliacao (turma_id, disciplina_id, titulo, tipo, data, criado_por, criado_em)
                                               VALUES (:turma_id, :disciplina_id, :titulo, :tipo, CURDATE(), :criado_por, NOW())";
                        $stmtInsertAvaliacao = $conn->prepare($sqlInsertAvaliacao);
                        $stmtInsertAvaliacao->bindParam(':turma_id', $turmaId);
                        $stmtInsertAvaliacao->bindParam(':disciplina_id', $disciplinaId);
                        $stmtInsertAvaliacao->bindParam(':titulo', $titulo);
                        $stmtInsertAvaliacao->bindValue(':tipo', $tipoAvaliacao);
                        $stmtInsertAvaliacao->bindParam(':criado_por', $_SESSION['usuario_id']);
                        $stmtInsertAvaliacao->execute();
                        $avaliacoesCache[$tipo] = $conn->lastInsertId();
                    } else {
                        $avaliacoesCache[$tipo] = $avaliacao['id'];
                    }
                }
                
                $notasFormatadas = [];
                foreach ($notas as $nota) {
                    if (!isset($nota['aluno_id']) || !isset($nota['nota'])) {
                        continue; // Pular notas inválidas
                    }
                    $tipo = $nota['tipo'] ?? 'PARCIAL';
                    $avaliacaoId = $avaliacoesCache[$tipo] ?? null;
                    
                    if (!$avaliacaoId) {
                        continue; // Pular se não encontrou avaliação
                    }
                    
                    // Preparar nota para inserção
                    $notasFormatadas[] = [
                        'avaliacao_id' => $avaliacaoId,
                        'disciplina_id' => $disciplinaId,
                        'turma_id' => $turmaId,
                        'aluno_id' => $nota['aluno_id'],
                        'nota' => $nota['nota'],
                        'bimestre' => $bimestre,
                        'recuperacao' => 0,
                        'comentario' => $nota['comentario'] ?? null
                    ];
                }
                
                // Inserir notas
                foreach ($notasFormatadas as $nota) {
                    $notaModel->lancar($nota);
                }
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Notas registradas com sucesso']);
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'Erro ao registrar notas: ' . $e->getMessage()]);
            }
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
    
    if ($_GET['acao'] === 'buscar_info_turma' && !empty($_GET['turma_id']) && !empty($_GET['disciplina_id'])) {
        $turmaId = $_GET['turma_id'];
        $disciplinaId = $_GET['disciplina_id'];
        $sql = "SELECT CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome, d.nome as disciplina_nome
                FROM turma t
                INNER JOIN disciplina d ON d.id = :disciplina_id
                WHERE t.id = :turma_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':disciplina_id', $disciplinaId);
        $stmt->execute();
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'turma_nome' => $info['turma_nome'] ?? '', 'disciplina_nome' => $info['disciplina_nome'] ?? '']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lançar Notas - SIGEA</title>
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
        /* Estilos para o modal fullscreen de notas */
        .nota-input {
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .nota-input:focus {
            outline: none;
            border-color: #ea580c;
            box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.1);
        }
        .media-badge {
            min-width: 48px;
            text-align: center;
        }
        .aluno-row {
            transition: background-color 0.15s ease;
        }
        .aluno-row:hover {
            background-color: #f9fafb;
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
                        <h1 class="text-xl font-semibold text-gray-800">Lançar Notas</h1>
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
                    <p class="text-gray-600">Registre as notas dos alunos nas suas disciplinas</p>
                </div>
                
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Minhas Turmas</h2>
                        <button onclick="abrirModalLancarNotas()" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Lançar Notas</span>
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
                                    <button onclick="abrirModalLancarNotas(<?= $turma['turma_id'] ?>, <?= $turma['disciplina_id'] ?>, '<?= htmlspecialchars($turma['turma_nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['disciplina_nome'], ENT_QUOTES) ?>')" class="w-full text-orange-600 hover:text-orange-700 font-medium text-sm py-2 border border-orange-200 rounded-lg hover:bg-orange-50 transition-colors">
                                        Lançar Notas
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Lançar Notas - Fullscreen Minimalista -->
    <div id="modal-lancar-notas" class="fixed inset-0 bg-gray-50 z-[60] hidden flex flex-col">
        <!-- Header Compacto -->
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button onclick="fecharModalLancarNotas()" class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Lançar Notas</h3>
                    <p id="notas-info-turma" class="text-xs text-gray-500">Selecione uma turma</p>
                </div>
            </div>
            <button onclick="salvarNotas()" class="px-4 py-1.5 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors">
                Salvar
            </button>
        </div>
        
        <!-- Barra de Controles -->
        <div class="bg-white border-b border-gray-100 px-4 py-2">
            <div class="max-w-5xl mx-auto flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-medium text-gray-600">Bimestre:</label>
                    <select id="notas-bimestre" class="text-sm px-3 py-1.5 border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-orange-500">
                        <option value="1">1º Bimestre</option>
                        <option value="2">2º Bimestre</option>
                        <option value="3">3º Bimestre</option>
                        <option value="4">4º Bimestre</option>
                    </select>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <span class="text-gray-500">
                        <span id="total-alunos" class="font-medium text-gray-700">0</span> alunos
                    </span>
                    <span class="text-gray-300">|</span>
                    <span class="text-gray-500">
                        <span id="notas-preenchidas" class="font-medium text-orange-600">0</span> notas
                    </span>
                </div>
            </div>
            <input type="hidden" id="notas-turma-id">
            <input type="hidden" id="notas-disciplina-id">
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="max-w-5xl mx-auto py-4 px-4">
                <!-- Header da Tabela -->
                <div class="grid grid-cols-12 gap-3 text-xs font-medium text-gray-500 uppercase tracking-wide px-3 py-2 border-b border-gray-200 mb-2">
                    <div class="col-span-4">Aluno</div>
                    <div class="col-span-2 text-center">Parcial</div>
                    <div class="col-span-2 text-center">Bimestral</div>
                    <div class="col-span-1 text-center">Média</div>
                    <div class="col-span-3">Observação</div>
                </div>
                
                <div id="notas-alunos-container" class="space-y-1">
                    <!-- Alunos serão carregados aqui -->
                    <div class="text-center py-16 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm">Selecione uma turma para carregar os alunos</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Compacto -->
        <div class="bg-white border-t border-gray-200 px-4 py-3">
            <div class="max-w-5xl mx-auto flex items-center justify-between">
                <p class="text-xs text-gray-400">Preencha as notas de 0 a 10</p>
                <div class="flex gap-2">
                    <button onclick="fecharModalLancarNotas()" class="px-4 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button onclick="salvarNotas()" class="px-4 py-1.5 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors">
                        Salvar Notas
                    </button>
                </div>
            </div>
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
        
        function abrirModalLancarNotas(turmaId = null, disciplinaId = null, turmaNome = '', disciplinaNome = '') {
            const modal = document.getElementById('modal-lancar-notas');
            if (modal) {
                modal.classList.remove('hidden');
                if (turmaId && disciplinaId) {
                    document.getElementById('notas-turma-id').value = turmaId;
                    document.getElementById('notas-disciplina-id').value = disciplinaId;
                    
                    // Exibir informações da turma
                    const infoElement = document.getElementById('notas-info-turma');
                    if (infoElement && turmaNome && disciplinaNome) {
                        infoElement.textContent = turmaNome + ' - ' + disciplinaNome;
                    } else if (infoElement) {
                        buscarInfoTurmaNotas(turmaId, disciplinaId);
                    }
                    
                    carregarAlunosParaNotas(turmaId);
                }
            }
        }
        
        function buscarInfoTurmaNotas(turmaId, disciplinaId) {
            fetch('?acao=buscar_info_turma&turma_id=' + turmaId + '&disciplina_id=' + disciplinaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const infoElement = document.getElementById('notas-info-turma');
                        if (infoElement && data.turma_nome && data.disciplina_nome) {
                            infoElement.textContent = data.turma_nome + ' - ' + data.disciplina_nome;
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar info da turma:', error);
                });
        }
        
        function fecharModalLancarNotas() {
            const modal = document.getElementById('modal-lancar-notas');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        function atualizarContadores() {
            const inputs = document.querySelectorAll('#notas-alunos-container .nota-input');
            let preenchidas = 0;
            inputs.forEach(input => {
                if (input.value && parseFloat(input.value) >= 0) {
                    preenchidas++;
                }
            });
            document.getElementById('notas-preenchidas').textContent = preenchidas;
        }
        
        function carregarAlunosParaNotas(turmaId) {
            fetch('?acao=buscar_alunos_turma&turma_id=' + turmaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.alunos) {
                        const container = document.getElementById('notas-alunos-container');
                        container.innerHTML = '';
                        
                        // Atualizar contador de alunos
                        document.getElementById('total-alunos').textContent = data.alunos.length;
                        document.getElementById('notas-preenchidas').textContent = '0';
                        
                        data.alunos.forEach((aluno, index) => {
                            const div = document.createElement('div');
                            div.className = 'aluno-row grid grid-cols-12 gap-3 items-center px-3 py-2.5 bg-white rounded-lg border border-gray-100';
                            div.innerHTML = `
                                <div class="col-span-4 flex items-center gap-3">
                                    <span class="text-xs text-gray-400 w-5">${index + 1}</span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">${aluno.nome}</div>
                                        ${aluno.matricula ? `<div class="text-xs text-gray-400">${aluno.matricula}</div>` : ''}
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" step="0.1" min="0" max="10" 
                                        class="nota-input nota-parcial w-full px-2 py-1.5 text-sm text-center border border-gray-200 rounded-lg" 
                                        data-aluno-id="${aluno.id}" 
                                        placeholder="0.0" 
                                        oninput="calcularMediaAluno(this); atualizarContadores();">
                                </div>
                                <div class="col-span-2">
                                    <input type="number" step="0.1" min="0" max="10" 
                                        class="nota-input nota-bimestral w-full px-2 py-1.5 text-sm text-center border border-gray-200 rounded-lg" 
                                        data-aluno-id="${aluno.id}" 
                                        placeholder="0.0" 
                                        oninput="calcularMediaAluno(this); atualizarContadores();">
                                </div>
                                <div class="col-span-1">
                                    <div class="media-badge media-aluno text-sm font-medium text-gray-400 py-1 rounded" data-aluno-id="${aluno.id}">
                                        -
                                    </div>
                                </div>
                                <div class="col-span-3">
                                    <input type="text" 
                                        class="w-full px-2 py-1.5 text-sm border border-gray-200 rounded-lg" 
                                        data-aluno-id="${aluno.id}" 
                                        placeholder="Opcional">
                                </div>
                            `;
                            container.appendChild(div);
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar alunos:', error);
                    alert('Erro ao carregar alunos da turma');
                });
        }
        
        function calcularMediaAluno(input) {
            const alunoId = input.dataset.alunoId;
            const row = input.closest('.aluno-row');
            const notaParcialInput = row.querySelector('.nota-parcial[data-aluno-id="' + alunoId + '"]');
            const notaBimestralInput = row.querySelector('.nota-bimestral[data-aluno-id="' + alunoId + '"]');
            const mediaDiv = row.querySelector('.media-aluno[data-aluno-id="' + alunoId + '"]');
            
            const notaParcial = parseFloat(notaParcialInput.value) || 0;
            const notaBimestral = parseFloat(notaBimestralInput.value) || 0;
            
            let media = 0;
            if (notaParcial > 0 && notaBimestral > 0) {
                media = (notaParcial + notaBimestral) / 2;
            } else if (notaParcial > 0) {
                media = notaParcial;
            } else if (notaBimestral > 0) {
                media = notaBimestral;
            }
            
            // Resetar classes
            mediaDiv.className = 'media-badge media-aluno text-sm font-medium py-1 rounded';
            mediaDiv.setAttribute('data-aluno-id', alunoId);
            
            if (media > 0) {
                mediaDiv.textContent = media.toFixed(1);
                if (media >= 7) {
                    mediaDiv.classList.add('text-green-600', 'bg-green-50');
                } else if (media >= 5) {
                    mediaDiv.classList.add('text-amber-600', 'bg-amber-50');
                } else {
                    mediaDiv.classList.add('text-red-600', 'bg-red-50');
                }
            } else {
                mediaDiv.textContent = '-';
                mediaDiv.classList.add('text-gray-400');
            }
        }
        
        function salvarNotas() {
            const turmaId = document.getElementById('notas-turma-id').value;
            const disciplinaId = document.getElementById('notas-disciplina-id').value;
            const bimestre = document.getElementById('notas-bimestre').value;
            const notas = [];
            
            const alunosProcessados = new Set();
            document.querySelectorAll('#notas-alunos-container .nota-parcial').forEach(input => {
                const alunoId = input.dataset.alunoId;
                if (!alunosProcessados.has(alunoId)) {
                    const notaParcialInput = document.querySelector(`.nota-parcial[data-aluno-id="${alunoId}"]`);
                    const notaBimestralInput = document.querySelector(`.nota-bimestral[data-aluno-id="${alunoId}"]`);
                    const comentarioInput = document.querySelector(`input[type="text"][data-aluno-id="${alunoId}"]`);
                    
                    const notaParcial = notaParcialInput ? parseFloat(notaParcialInput.value) : null;
                    const notaBimestral = notaBimestralInput ? parseFloat(notaBimestralInput.value) : null;
                    const comentario = comentarioInput ? comentarioInput.value : '';
                    
                    if (notaParcial !== null && notaParcial > 0) {
                        notas.push({
                            aluno_id: alunoId,
                            nota: notaParcial,
                            tipo: 'PARCIAL',
                            bimestre: bimestre,
                            comentario: comentario
                        });
                    }
                    
                    if (notaBimestral !== null && notaBimestral > 0) {
                        notas.push({
                            aluno_id: alunoId,
                            nota: notaBimestral,
                            tipo: 'BIMESTRAL',
                            bimestre: bimestre,
                            comentario: comentario
                        });
                    }
                    
                    alunosProcessados.add(alunoId);
                }
            });
            
            if (notas.length === 0) {
                alert('Nenhuma nota foi preenchida');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'lancar_notas');
            formData.append('turma_id', turmaId);
            formData.append('disciplina_id', disciplinaId);
            formData.append('notas', JSON.stringify(notas));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notas registradas com sucesso!');
                    fecharModalLancarNotas();
                    location.reload();
                } else {
                    alert('Erro ao registrar notas: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar notas');
            });
        }
    </script>
</body>
</html>
