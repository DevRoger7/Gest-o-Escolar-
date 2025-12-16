<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'professor') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

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
    if ($usuarioId) {
        $sqlUsuario = "SELECT p.id as pessoa_id FROM usuario u 
                       INNER JOIN pessoa p ON u.pessoa_id = p.id 
                       WHERE u.id = :usuario_id AND u.ativo = 1 LIMIT 1";
        $stmtUsuario = $conn->prepare($sqlUsuario);
        $stmtUsuario->bindParam(':usuario_id', $usuarioId);
        $stmtUsuario->execute();
        $usuarioData = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
        if ($usuarioData && isset($usuarioData['pessoa_id'])) {
            $pessoaId = $usuarioData['pessoa_id'];
            $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
            $stmtProfessor = $conn->prepare($sqlProfessor);
            $stmtProfessor->bindParam(':pessoa_id', $pessoaId);
            $stmtProfessor->execute();
            $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
            $professorId = $professor['id'] ?? null;
        }
    }
}

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    
    $acao = $_POST['acao'];
    $resposta = ['status' => false, 'mensagem' => 'Ação não reconhecida'];
    
    try {
        // Listar turmas do professor
        if ($acao === 'listar_turmas') {
            if (!$professorId) {
                throw new Exception('Professor não encontrado');
            }
            
            // Buscar escola selecionada da sessão
            $escolaSelecionadaId = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
            $escolaFiltro = $_POST['escola_id'] ?? $escolaSelecionadaId;
            
            $sql = "SELECT DISTINCT 
                        t.id as turma_id,
                        CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                        t.serie,
                        t.letra,
                        t.turno,
                        d.id as disciplina_id,
                        d.nome as disciplina_nome,
                        e.id as escola_id,
                        e.nome as escola_nome,
                        tp.inicio as data_inicio,
                        tp.fim as data_fim,
                        COUNT(DISTINCT at.aluno_id) as total_alunos
                    FROM turma_professor tp
                    INNER JOIN turma t ON tp.turma_id = t.id
                    INNER JOIN disciplina d ON tp.disciplina_id = d.id
                    INNER JOIN escola e ON t.escola_id = e.id
                    LEFT JOIN aluno_turma at ON t.id = at.turma_id 
                        AND (at.fim IS NULL OR at.fim = '' OR at.fim = '0000-00-00')
                    WHERE tp.professor_id = :professor_id 
                        AND (tp.fim IS NULL OR tp.fim = '' OR tp.fim = '0000-00-00')
                        AND t.ativo = 1
                        AND e.ativo = 1";
            
            // Filtrar por escola se especificada
            if ($escolaFiltro) {
                $sql .= " AND e.id = :escola_id";
            }
            
            $sql .= " GROUP BY t.id, d.id, e.id, tp.inicio, tp.fim
                      ORDER BY e.nome, t.serie, t.letra, d.nome";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            if ($escolaFiltro) {
                $stmt->bindParam(':escola_id', $escolaFiltro, PDO::PARAM_INT);
            }
            $stmt->execute();
            $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $resposta = ['status' => true, 'dados' => $turmas];
        }
        
    } catch (PDOException $e) {
        error_log("Erro: " . $e->getMessage());
        $resposta = ['status' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("Erro: " . $e->getMessage());
        $resposta = ['status' => false, 'mensagem' => $e->getMessage()];
    }
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}

// Buscar escola selecionada da sessão
$escolaSelecionadaId = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
$escolaSelecionadaNome = $_SESSION['escola_selecionada_nome'] ?? $_SESSION['escola_atual'] ?? null;

// Buscar TODAS as turmas do professor (sem filtrar por escola)
$todasTurmasProfessor = [];
if ($professorId) {
    $sql = "SELECT DISTINCT 
                t.id as turma_id,
                CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                t.serie,
                t.letra,
                t.turno,
                d.id as disciplina_id,
                d.nome as disciplina_nome,
                e.id as escola_id,
                e.nome as escola_nome,
                tp.inicio as data_inicio,
                tp.fim as data_fim,
                COUNT(DISTINCT at.aluno_id) as total_alunos
            FROM turma_professor tp
            INNER JOIN turma t ON tp.turma_id = t.id
            INNER JOIN disciplina d ON tp.disciplina_id = d.id
            INNER JOIN escola e ON t.escola_id = e.id
            LEFT JOIN aluno_turma at ON t.id = at.turma_id 
                AND (at.fim IS NULL OR at.fim = '' OR at.fim = '0000-00-00')
            WHERE tp.professor_id = :professor_id 
                AND (tp.fim IS NULL OR tp.fim = '' OR tp.fim = '0000-00-00')
                AND t.ativo = 1
                AND e.ativo = 1
            GROUP BY t.id, d.id, e.id, tp.inicio, tp.fim
            ORDER BY e.nome, t.serie, t.letra, d.nome";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
    $stmt->execute();
    $todasTurmasProfessor = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Filtrar turmas pela escola selecionada por padrão (para exibição inicial)
// Se não houver escola selecionada, mostrar todas as turmas
$turmasProfessor = $todasTurmasProfessor;
if ($escolaSelecionadaId) {
    $turmasFiltradas = array_filter($todasTurmasProfessor, function($turma) use ($escolaSelecionadaId) {
        return $turma['escola_id'] == $escolaSelecionadaId;
    });
    $turmasProfessor = array_values($turmasFiltradas); // Reindexar array
}

// Buscar todas as escolas do professor para o filtro
$todasEscolasProfessor = [];
if ($professorId) {
    $sqlEscolas = "SELECT DISTINCT e.id, e.nome
                   FROM turma_professor tp
                   INNER JOIN turma t ON tp.turma_id = t.id
                   INNER JOIN escola e ON t.escola_id = e.id
                   WHERE tp.professor_id = :professor_id 
                     AND (tp.fim IS NULL OR tp.fim = '' OR tp.fim = '0000-00-00')
                     AND t.ativo = 1
                     AND e.ativo = 1
                   ORDER BY e.nome";
    $stmtEscolas = $conn->prepare($sqlEscolas);
    $stmtEscolas->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
    $stmtEscolas->execute();
    $todasEscolasProfessor = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Turmas - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }
        .menu-item {
            transition: all 0.2s ease;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        .menu-item.active svg {
            color: #2D5A27;
        }
        .mobile-menu-overlay {
            transition: opacity 0.3s ease-in-out;
        }
        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 999 !important;
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                height: 100vh !important;
                width: 16rem !important;
            }
            .sidebar-mobile.open {
                transform: translateX(0) !important;
                z-index: 999 !important;
            }
        }
        .card-turma {
            transition: all 0.3s ease;
        }
        .card-turma:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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
                        <h1 class="text-xl font-semibold text-gray-800">Minhas Turmas</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($escolaSelecionadaNome): ?>
                                <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="text-sm font-semibold">
                                            <?= htmlspecialchars($escolaSelecionadaNome) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <p class="text-gray-600">Visualize todas as turmas e disciplinas que você leciona</p>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Filtros</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="filtro-busca" placeholder="Turma, disciplina ou escola..." 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                   onkeyup="filtrarTurmas()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <?php if ($escolaSelecionadaNome): ?>
                                <input type="text" 
                                       value="<?= htmlspecialchars($escolaSelecionadaNome) ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" 
                                       readonly
                                       title="Para mudar a escola, use a opção no Dashboard">
                            <?php else: ?>
                                <input type="text" 
                                       value="Nenhuma escola selecionada" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" 
                                       readonly>
                            <?php endif; ?>
                            <input type="hidden" id="filtro-escola" value="<?= $escolaSelecionadaId ?? '' ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Turno</label>
                            <select id="filtro-turno" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                    onchange="filtrarTurmas()">
                                <option value="">Todos os turnos</option>
                                <option value="Manhã">Manhã</option>
                                <option value="Tarde">Tarde</option>
                                <option value="Noite">Noite</option>
                                <option value="Integral">Integral</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Turmas -->
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Turmas e Disciplinas</h2>
                        <?php if ($escolaSelecionadaNome): ?>
                            <p class="text-sm text-gray-600 mt-1">Mostrando turmas da escola: <strong><?= htmlspecialchars($escolaSelecionadaNome) ?></strong></p>
                        <?php endif; ?>
                    </div>
                    <div id="lista-turmas" class="p-6">
                    <?php if (empty($todasTurmasProfessor)): ?>
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma turma encontrada</h3>
                            <p class="mt-1 text-sm text-gray-500">Você não está lotado em nenhuma turma no momento.</p>
                        </div>
                    <?php elseif (empty($turmasProfessor) && $escolaSelecionadaId): ?>
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma turma encontrada</h3>
                            <p class="mt-1 text-sm text-gray-500">Você não está lotado em nenhuma turma na escola <strong><?= htmlspecialchars($escolaSelecionadaNome) ?></strong>.</p>
                            <p class="mt-2 text-sm text-blue-600">Total de turmas em outras escolas: <?= count($todasTurmasProfessor) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($turmasProfessor as $turma): ?>
                                <div class="card-turma bg-white border border-gray-200 rounded-xl p-6 shadow-md hover:shadow-lg">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                <?= htmlspecialchars($turma['turma_nome']) ?>
                                            </h3>
                                            <p class="text-sm text-gray-600 mb-2 flex items-center">
                                                <i class="fas fa-book text-blue-500 mr-2"></i>
                                                <?= htmlspecialchars($turma['disciplina_nome']) ?>
                                            </p>
                                            <p class="text-sm text-gray-500 flex items-center">
                                                <i class="fas fa-school text-primary-green mr-2"></i>
                                                <?= htmlspecialchars($turma['escola_nome']) ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <div class="flex items-center justify-between text-sm mb-2">
                                            <span class="text-gray-600 flex items-center">
                                                <i class="fas fa-users text-purple-500 mr-2"></i>
                                                Alunos:
                                            </span>
                                            <span class="font-semibold text-gray-900"><?= $turma['total_alunos'] ?? 0 ?></span>
                                        </div>
                                        <?php if ($turma['data_inicio']): ?>
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600 flex items-center">
                                                    <i class="fas fa-calendar text-orange-500 mr-2"></i>
                                                    Desde:
                                                </span>
                                                <span class="text-gray-900">
                                                    <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-6 flex gap-2">
                                        <a href="frequencia_professor.php?turma_id=<?= $turma['turma_id'] ?>&disciplina_id=<?= $turma['disciplina_id'] ?>" 
                                           class="flex-1 text-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                            <i class="fas fa-check-circle mr-1"></i> Frequência
                                        </a>
                                        <a href="notas_professor.php?turma_id=<?= $turma['turma_id'] ?>&disciplina_id=<?= $turma['disciplina_id'] ?>" 
                                           class="flex-1 text-center px-4 py-2.5 bg-primary-green text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors duration-200">
                                            <i class="fas fa-star mr-1"></i> Notas
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Todas as turmas do professor (sem filtro)
        const todasTurmas = <?= json_encode($todasTurmasProfessor, JSON_UNESCAPED_UNICODE) ?>;
        // Turmas filtradas pela escola selecionada (para exibição inicial)
        const turmasIniciais = <?= json_encode($turmasProfessor, JSON_UNESCAPED_UNICODE) ?>;
        const escolaSelecionadaId = <?= $escolaSelecionadaId ? json_encode($escolaSelecionadaId) : 'null' ?>;
        
        // Inicializar com turmas da escola selecionada
        let turmasAtuais = turmasIniciais;
        
        function filtrarTurmas() {
            const busca = document.getElementById('filtro-busca').value.toLowerCase();
            const escolaIdInput = document.getElementById('filtro-escola');
            const escolaId = escolaIdInput ? escolaIdInput.value : (escolaSelecionadaId || null);
            const turno = document.getElementById('filtro-turno').value;
            
            // Sempre filtrar pela escola atual (se houver)
            const escolaFiltro = escolaId || escolaSelecionadaId;
            
            // Usar todas as turmas para filtrar
            const turmasFiltradas = todasTurmas.filter(turma => {
                const matchBusca = !busca || 
                    turma.turma_nome.toLowerCase().includes(busca) ||
                    turma.disciplina_nome.toLowerCase().includes(busca) ||
                    turma.escola_nome.toLowerCase().includes(busca);
                
                // Sempre filtrar pela escola atual (se houver)
                const matchEscola = !escolaFiltro || turma.escola_id == escolaFiltro;
                const matchTurno = !turno || turma.turno === turno;
                
                return matchBusca && matchEscola && matchTurno;
            });
            
            turmasAtuais = turmasFiltradas;
            renderizarTurmas(turmasFiltradas);
        }
        
        // Inicializar ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            if (todasTurmas.length > 0) {
                renderizarTurmas(turmasIniciais);
            }
        });
        
        function renderizarTurmas(turmas) {
            const container = document.getElementById('lista-turmas');
            
            if (turmas.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma turma encontrada</h3>
                        <p class="mt-1 text-sm text-gray-500">Tente ajustar os filtros de busca.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    ${turmas.map(turma => `
                        <div class="card-turma bg-white border border-gray-200 rounded-xl p-6 shadow-md hover:shadow-lg">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                        ${turma.turma_nome}
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-2 flex items-center">
                                        <i class="fas fa-book text-blue-500 mr-2"></i>
                                        ${turma.disciplina_nome}
                                    </p>
                                    <p class="text-sm text-gray-500 flex items-center">
                                        <i class="fas fa-school mr-2" style="color: #2D5A27;"></i>
                                        ${turma.escola_nome}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex items-center justify-between text-sm mb-2">
                                    <span class="text-gray-600 flex items-center">
                                        <i class="fas fa-users text-purple-500 mr-2"></i>
                                        Alunos:
                                    </span>
                                    <span class="font-semibold text-gray-900">${turma.total_alunos || 0}</span>
                                </div>
                                ${turma.data_inicio ? `
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 flex items-center">
                                            <i class="fas fa-calendar text-orange-500 mr-2"></i>
                                            Desde:
                                        </span>
                                        <span class="text-gray-900">
                                            ${new Date(turma.data_inicio).toLocaleDateString('pt-BR')}
                                        </span>
                                    </div>
                                ` : ''}
                            </div>
                            
                            <div class="mt-6 flex gap-2">
                                <a href="frequencia_professor.php?turma_id=${turma.turma_id}&disciplina_id=${turma.disciplina_id}" 
                                   class="flex-1 text-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                    <i class="fas fa-check-circle mr-1"></i> Frequência
                                </a>
                                <a href="notas_professor.php?turma_id=${turma.turma_id}&disciplina_id=${turma.disciplina_id}" 
                                   class="flex-1 text-center px-4 py-2.5 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors duration-200" style="background-color: #2D5A27;">
                                    <i class="fas fa-star mr-1"></i> Notas
                                </a>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        // Função de toggle sidebar (mobile)
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };

        // Fechar sidebar ao clicar no overlay
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('mobileOverlay');
            if (overlay) {
                overlay.addEventListener('click', function() {
                    window.toggleSidebar();
                });
            }
        });

        // Função de logout
        window.confirmLogout = function() {
            if (confirm('Tem certeza que deseja sair?')) {
                window.location.href = '../auth/logout.php';
            }
        };
    </script>
</body>
</html>

