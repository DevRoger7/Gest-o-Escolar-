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
            $notasFormatadas = [];
            foreach ($notas as $nota) {
                $notasFormatadas[] = [
                    'avaliacao_id' => null,
                    'disciplina_id' => $disciplinaId,
                    'turma_id' => $turmaId,
                    'aluno_id' => $nota['aluno_id'],
                    'nota' => $nota['nota'],
                    'bimestre' => $nota['bimestre'] ?? 1,
                    'recuperacao' => 0,
                    'comentario' => $nota['comentario'] ?? null
                ];
            }
            $resultado = $notaModel->lancarLote($notasFormatadas);
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
                    <div class="w-10"></div>
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
                                    <button onclick="abrirModalLancarNotas(<?= $turma['turma_id'] ?>, <?= $turma['disciplina_id'] ?>)" class="w-full text-orange-600 hover:text-orange-700 font-medium text-sm py-2 border border-orange-200 rounded-lg hover:bg-orange-50 transition-colors">
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
    
    <!-- Modal Lançar Notas -->
    <div id="modal-lancar-notas" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-6xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Lançar Notas</h3>
                <button onclick="fecharModalLancarNotas()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bimestre</label>
                        <select id="notas-bimestre" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="1">1º Bimestre</option>
                            <option value="2">2º Bimestre</option>
                            <option value="3">3º Bimestre</option>
                            <option value="4">4º Bimestre</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" id="notas-turma-id">
                <input type="hidden" id="notas-disciplina-id">
            </div>
            
            <div class="mb-4">
                <div class="grid grid-cols-12 gap-4 font-semibold text-gray-700 pb-2 border-b">
                    <div class="col-span-4">Aluno</div>
                    <div class="col-span-2">Nota</div>
                    <div class="col-span-6">Comentário</div>
                </div>
            </div>
            
            <div id="notas-alunos-container" class="space-y-2 mb-6">
                <!-- Alunos serão carregados aqui -->
            </div>
            
            <div class="flex space-x-3">
                <button onclick="fecharModalLancarNotas()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">
                    Cancelar
                </button>
                <button onclick="salvarNotas()" class="flex-1 px-4 py-2 text-white bg-orange-600 hover:bg-orange-700 rounded-lg font-medium">
                    Salvar Notas
                </button>
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
        
        function abrirModalLancarNotas(turmaId = null, disciplinaId = null) {
            const modal = document.getElementById('modal-lancar-notas');
            if (modal) {
                modal.classList.remove('hidden');
                if (turmaId && disciplinaId) {
                    document.getElementById('notas-turma-id').value = turmaId;
                    document.getElementById('notas-disciplina-id').value = disciplinaId;
                    carregarAlunosParaNotas(turmaId);
                }
            }
        }
        
        function fecharModalLancarNotas() {
            const modal = document.getElementById('modal-lancar-notas');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        function carregarAlunosParaNotas(turmaId) {
            fetch('?acao=buscar_alunos_turma&turma_id=' + turmaId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.alunos) {
                        const container = document.getElementById('notas-alunos-container');
                        container.innerHTML = '';
                        data.alunos.forEach(aluno => {
                            const div = document.createElement('div');
                            div.className = 'grid grid-cols-12 gap-4 items-center p-3 border border-gray-200 rounded-lg';
                            div.innerHTML = `
                                <div class="col-span-4 font-medium">${aluno.nome}</div>
                                <div class="col-span-2">
                                    <input type="number" step="0.1" min="0" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-lg" data-aluno-id="${aluno.id}" placeholder="0.0">
                                </div>
                                <div class="col-span-6">
                                    <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg" data-aluno-id="${aluno.id}" placeholder="Comentário (opcional)">
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
        
        function salvarNotas() {
            const turmaId = document.getElementById('notas-turma-id').value;
            const disciplinaId = document.getElementById('notas-disciplina-id').value;
            const bimestre = document.getElementById('notas-bimestre').value;
            const notas = [];
            
            const alunosProcessados = new Set();
            document.querySelectorAll('#notas-alunos-container input[type="number"]').forEach(input => {
                const alunoId = input.dataset.alunoId;
                if (!alunosProcessados.has(alunoId) && input.value) {
                    const comentarioInput = document.querySelector(`input[type="text"][data-aluno-id="${alunoId}"]`);
                    
                    notas.push({
                        aluno_id: alunoId,
                        nota: parseFloat(input.value),
                        bimestre: bimestre,
                        comentario: comentarioInput ? comentarioInput.value : ''
                    });
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

