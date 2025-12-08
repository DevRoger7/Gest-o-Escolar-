<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/dashboard/DashboardStats.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAluno() && !eAdm()) {
    header('Location: ../../../auth/login.php?erro=sem_permissao');
    exit;
}

require_once('../../config/Database.php');
require_once('../../Models/academico/NotaModel.php');

$notaModel = new NotaModel();
$stats = new DashboardStats();
$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar aluno_id da sessão
$usuarioId = $_SESSION['usuario_id'] ?? null;
$pessoaId = $_SESSION['pessoa_id'] ?? null;
$cpf = $_SESSION['cpf'] ?? null;

// Se não tiver pessoa_id na sessão, buscar pelo usuario_id
if (!$pessoaId && $usuarioId) {
    $sqlPessoa = "SELECT pessoa_id FROM usuario WHERE id = :usuario_id LIMIT 1";
    $stmtPessoa = $conn->prepare($sqlPessoa);
    $stmtPessoa->bindParam(':usuario_id', $usuarioId);
    $stmtPessoa->execute();
    $usuario = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
    $pessoaId = $usuario['pessoa_id'] ?? null;
}

// Buscar aluno_id real - tentar primeiro pelo pessoa_id (mais direto)
$alunoIdReal = null;
if ($pessoaId) {
    $sqlAluno = "SELECT a.id FROM aluno a WHERE a.pessoa_id = :pessoa_id LIMIT 1";
    $stmtAluno = $conn->prepare($sqlAluno);
    $stmtAluno->bindParam(':pessoa_id', $pessoaId);
    $stmtAluno->execute();
    $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
    $alunoIdReal = $aluno['id'] ?? null;
}

// Se ainda não encontrou, tentar pelo usuario_id diretamente
if (!$alunoIdReal && $usuarioId) {
    $sqlAluno = "SELECT a.id FROM aluno a 
                 INNER JOIN usuario u ON a.pessoa_id = u.pessoa_id 
                 WHERE u.id = :usuario_id 
                 LIMIT 1";
    $stmtAluno = $conn->prepare($sqlAluno);
    $stmtAluno->bindParam(':usuario_id', $usuarioId);
    $stmtAluno->execute();
    $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
    $alunoIdReal = $aluno['id'] ?? null;
}

// Último recurso: buscar pelo CPF da sessão
if (!$alunoIdReal && $cpf) {
    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
    $sqlAluno = "SELECT a.id FROM aluno a 
                 INNER JOIN pessoa p ON a.pessoa_id = p.id 
                 WHERE p.cpf = :cpf 
                 LIMIT 1";
    $stmtAluno = $conn->prepare($sqlAluno);
    $stmtAluno->bindParam(':cpf', $cpfLimpo);
    $stmtAluno->execute();
    $aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
    $alunoIdReal = $aluno['id'] ?? null;
}

// Buscar turma atual do aluno
$turmaId = null;
$anoLetivo = date('Y'); // Sempre ter um ano letivo (padrão: ano atual)
$turmaAtual = null;

if ($alunoIdReal) {
    // Buscar turma do aluno (verificar tanto por fim IS NULL quanto por status MATRICULADO)
    // Removendo a verificação de t.ativo para não filtrar turmas inativas
    $sqlTurma = "SELECT at.turma_id, t.ano_letivo, t.serie, t.letra, t.turno,
                 CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                 FROM aluno_turma at 
                 INNER JOIN turma t ON at.turma_id = t.id 
                 WHERE at.aluno_id = :aluno_id 
                 AND (at.fim IS NULL OR at.status = 'MATRICULADO' OR at.status IS NULL)
                 ORDER BY at.inicio DESC
                 LIMIT 1";
    $stmtTurma = $conn->prepare($sqlTurma);
    $stmtTurma->bindParam(':aluno_id', $alunoIdReal);
    $stmtTurma->execute();
    $turmaAtual = $stmtTurma->fetch(PDO::FETCH_ASSOC);
    
    if ($turmaAtual) {
        $turmaId = $turmaAtual['turma_id'] ?? null;
        $anoLetivo = $turmaAtual['ano_letivo'] ?? date('Y');
    }
}

// Buscar TODAS as disciplinas da turma (mesmo sem notas)
$todasDisciplinas = [];
if ($alunoIdReal && $turmaId) {
    // Primeiro: tentar buscar disciplinas através de professores atribuídos
    $sqlDisciplinas = "SELECT DISTINCT d.id, d.nome as disciplina_nome
                       FROM turma_professor tp
                       INNER JOIN disciplina d ON tp.disciplina_id = d.id
                       WHERE tp.turma_id = :turma_id 
                       AND (tp.fim IS NULL OR tp.fim >= CURDATE())
                       ORDER BY d.nome";
    $stmtDisciplinas = $conn->prepare($sqlDisciplinas);
    $stmtDisciplinas->bindParam(':turma_id', $turmaId);
    $stmtDisciplinas->execute();
    $todasDisciplinas = $stmtDisciplinas->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não encontrou disciplinas através de professores, buscar todas as disciplinas cadastradas
    // (fallback para desenvolvimento/teste - em produção, isso deveria vir de uma grade curricular)
    if (empty($todasDisciplinas)) {
        $sqlTodasDisciplinas = "SELECT id, nome as disciplina_nome
                               FROM disciplina
                               WHERE id IS NOT NULL
                               ORDER BY nome";
        $stmtTodasDisciplinas = $conn->prepare($sqlTodasDisciplinas);
        $stmtTodasDisciplinas->execute();
        $todasDisciplinas = $stmtTodasDisciplinas->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Debug (remover em produção)
    if (empty($todasDisciplinas) && $turmaId) {
        // Verificar se há professores atribuídos
        $sqlDebug = "SELECT COUNT(*) as total FROM turma_professor WHERE turma_id = :turma_id";
        $stmtDebug = $conn->prepare($sqlDebug);
        $stmtDebug->bindParam(':turma_id', $turmaId);
        $stmtDebug->execute();
        $debug = $stmtDebug->fetch(PDO::FETCH_ASSOC);
        error_log("Debug - Turma ID: $turmaId | Professores atribuídos: " . ($debug['total'] ?? 0));
    }
}

// Tipo de visualização selecionado (padrão: 1º bimestre)
$tipoVisualizacao = isset($_GET['tipo']) ? $_GET['tipo'] : '1';
$bimestreSelecionado = null;
$mostrarRecuperacao = false;
$mostrarFinal = false;

if ($tipoVisualizacao === 'recuperacao') {
    $mostrarRecuperacao = true;
} elseif ($tipoVisualizacao === 'final') {
    $mostrarFinal = true;
} else {
    $bimestreSelecionado = intval($tipoVisualizacao);
    if ($bimestreSelecionado < 1 || $bimestreSelecionado > 4) {
        $bimestreSelecionado = 1;
        $tipoVisualizacao = '1';
    }
}

// Buscar notas conforme o tipo selecionado
$notas = [];
if ($alunoIdReal && $turmaId) {
    if ($mostrarRecuperacao) {
        // Buscar apenas notas de recuperação
        $conn = Database::getInstance()->getConnection();
        $sql = "SELECT n.*, d.nome as disciplina_nome, 
                a.titulo as avaliacao_titulo
                FROM nota n
                LEFT JOIN disciplina d ON n.disciplina_id = d.id
                LEFT JOIN avaliacao a ON n.avaliacao_id = a.id
                WHERE n.aluno_id = :aluno_id AND n.turma_id = :turma_id 
                AND n.recuperacao = 1
                ORDER BY d.nome ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':aluno_id', $alunoIdReal);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->execute();
        $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($mostrarFinal) {
        // Buscar todas as notas para calcular média final
        $notas = $notaModel->buscarPorAluno($alunoIdReal, $turmaId);
    } else {
        // Buscar notas do bimestre selecionado
        $notas = $notaModel->buscarPorAluno($alunoIdReal, $turmaId, null, $bimestreSelecionado);
    }
}

// Buscar TODAS as notas para calcular médias gerais por bimestre
$todasNotas = [];
if ($alunoIdReal && $turmaId) {
    $todasNotas = $notaModel->buscarPorAluno($alunoIdReal, $turmaId);
}

// Inicializar estrutura com TODAS as disciplinas
$notasAgrupadas = [];
foreach ($todasDisciplinas as $disciplina) {
    $disciplinaId = $disciplina['id'];
    $notasAgrupadas[$disciplinaId] = [
        'disciplina_nome' => $disciplina['disciplina_nome'],
        'bimestres' => [
            1 => [],
            2 => [],
            3 => [],
            4 => []
        ]
    ];
}

// Preencher com TODAS as notas (para cálculo de médias)
foreach ($todasNotas as $nota) {
    $disciplinaId = $nota['disciplina_id'];
    $bimestre = $nota['bimestre'] ?? null;
    
    // Se a disciplina não estiver na lista, adicionar
    if (!isset($notasAgrupadas[$disciplinaId])) {
        $notasAgrupadas[$disciplinaId] = [
            'disciplina_nome' => $nota['disciplina_nome'],
            'bimestres' => [
                1 => [],
                2 => [],
                3 => [],
                4 => []
            ]
        ];
    }
    
    // Adicionar nota ao bimestre correspondente (1-4)
    if ($bimestre && is_numeric($bimestre) && $bimestre >= 1 && $bimestre <= 4) {
        $notasAgrupadas[$disciplinaId]['bimestres'][$bimestre][] = $nota;
    }
}

// Calcular médias conforme o tipo selecionado
$mediasBimestre = [];
$mediaGeralBimestre = 0;
$totalDisciplinasComNota = 0;

foreach ($notasAgrupadas as $disciplinaId => $dados) {
    if ($mostrarRecuperacao) {
        // Buscar notas de recuperação desta disciplina
        $notasRecuperacao = [];
        foreach ($todasNotas as $nota) {
            if ($nota['disciplina_id'] == $disciplinaId && isset($nota['recuperacao']) && $nota['recuperacao']) {
                $notasRecuperacao[] = $nota;
            }
        }
        $soma = 0;
        $count = 0;
        foreach ($notasRecuperacao as $nota) {
            $soma += floatval($nota['nota']);
            $count++;
        }
        $mediaBimestre = $count > 0 ? round($soma / $count, 2) : 0;
        $mediasBimestre[$disciplinaId] = $mediaBimestre;
    } elseif ($mostrarFinal) {
        // Calcular média final (média dos 4 bimestres)
        $somaBimestres = 0;
        $countBimestres = 0;
        for ($b = 1; $b <= 4; $b++) {
            $notasBimestre = $dados['bimestres'][$b] ?? [];
            $soma = 0;
            $count = 0;
            foreach ($notasBimestre as $nota) {
                // Não contar notas de recuperação na média final dos bimestres
                if (!isset($nota['recuperacao']) || !$nota['recuperacao']) {
                    $soma += floatval($nota['nota']);
                    $count++;
                }
            }
            if ($count > 0) {
                $somaBimestres += round($soma / $count, 2);
                $countBimestres++;
            }
        }
        $mediaBimestre = $countBimestres > 0 ? round($somaBimestres / $countBimestres, 2) : 0;
        $mediasBimestre[$disciplinaId] = $mediaBimestre;
    } else {
        // Calcular média apenas do bimestre selecionado
        $notasBimestre = $dados['bimestres'][$bimestreSelecionado] ?? [];
        $soma = 0;
        $count = 0;
        foreach ($notasBimestre as $nota) {
            // Não contar notas de recuperação na média do bimestre
            if (!isset($nota['recuperacao']) || !$nota['recuperacao']) {
                $soma += floatval($nota['nota']);
                $count++;
            }
        }
        $mediaBimestre = $count > 0 ? round($soma / $count, 2) : 0;
        $mediasBimestre[$disciplinaId] = $mediaBimestre;
    }
    
    if ($mediasBimestre[$disciplinaId] > 0) {
        $mediaGeralBimestre += $mediasBimestre[$disciplinaId];
        $totalDisciplinasComNota++;
    }
}

// Calcular média geral
if ($totalDisciplinasComNota > 0) {
    $mediaGeralBimestre = round($mediaGeralBimestre / $totalDisciplinasComNota, 2);
}

// Calcular médias de todos os bimestres (para evolução)
$mediasTodosBimestres = [];
$evolucaoBimestres = [];
foreach ($notasAgrupadas as $disciplinaId => $dados) {
    $mediasTodosBimestres[$disciplinaId] = [];
    for ($b = 1; $b <= 4; $b++) {
        $notasBimestre = $dados['bimestres'][$b] ?? [];
        $soma = 0;
        $count = 0;
        foreach ($notasBimestre as $nota) {
            $soma += floatval($nota['nota']);
            $count++;
        }
        $mediaBimestre = $count > 0 ? round($soma / $count, 2) : 0;
        $mediasTodosBimestres[$disciplinaId][$b] = $mediaBimestre;
    }
}

// Calcular evolução por bimestre
for ($b = 1; $b <= 4; $b++) {
    $somaBimestre = 0;
    $countBimestre = 0;
    foreach ($mediasTodosBimestres as $disciplinaId => $mediasDisciplina) {
        if (isset($mediasDisciplina[$b]) && $mediasDisciplina[$b] > 0) {
            $somaBimestre += $mediasDisciplina[$b];
            $countBimestre++;
        }
    }
    $evolucaoBimestres[$b] = $countBimestre > 0 ? round($somaBimestre / $countBimestre, 2) : 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Notas - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'secondary-green': '#4A7C59',
                        'accent-orange': '#FF6B35',
                        'accent-red': '#D62828',
                        'light-green': '#A8D5BA',
                        'warm-orange': '#FF8C42'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="global-theme.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-hover { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .progress-bar { transition: width 0.6s ease-out; }
        .collapse-content { transition: all 0.3s ease-out; }
        .disciplina-item { transition: background-color 0.2s ease; }
        [x-cloak] { display: none !important; }
        .tooltip { position: relative; }
        .tooltip:hover .tooltip-text { visibility: visible; opacity: 1; }
        .tooltip-text {
            visibility: hidden; opacity: 0;
            position: absolute; z-index: 50;
            bottom: 125%; left: 50%; transform: translateX(-50%);
            background-color: #1f2937; color: white;
            padding: 8px 12px; border-radius: 6px;
            font-size: 12px; white-space: nowrap;
            transition: opacity 0.3s;
        }
        .tooltip-text::after {
            content: ""; position: absolute;
            top: 100%; left: 50%; transform: translateX(-50%);
            border: 5px solid transparent; border-top-color: #1f2937;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">
    
    <!-- Header Fixo -->
    <header class="bg-white shadow-sm sticky top-0 z-40 border-b border-gray-200 h-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="../dashboard.php" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors" title="Voltar ao Dashboard">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div class="h-6 w-px bg-gray-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-primary-green/10 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span class="text-lg font-semibold text-gray-800">Minhas Notas</span>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($_SESSION['nome'] ?? 'Aluno') ?></p>
                        <p class="text-xs text-gray-500">Aluno</p>
                    </div>
                    <div class="w-10 h-10 bg-primary-green rounded-xl flex items-center justify-center text-white font-bold shadow-sm">
                        <?= strtoupper(substr($_SESSION['nome'] ?? 'A', 0, 2)) ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Cabeçalho -->
        <div class="mb-6 fade-in">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Notas</h1>
                <div class="flex items-center gap-2">
                    <select class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                        <option value="<?= $anoLetivo ?>"><?= $anoLetivo ?></option>
                    </select>
                </div>
            </div>
            
            <!-- Navegação de Bimestres -->
            <div class="flex items-center gap-1 border-b-2 border-gray-200 mb-6">
                <?php for ($b = 1; $b <= 4; $b++): ?>
                    <a href="?tipo=<?= $b ?>" 
                       class="px-6 py-3 text-sm font-semibold transition-all relative <?= $tipoVisualizacao == (string)$b ? 'text-primary-green' : 'text-gray-600 hover:text-gray-900' ?>">
                        <?= $b ?>º Bimestre
                        <?php if ($tipoVisualizacao == (string)$b): ?>
                            <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-green"></span>
                        <?php endif; ?>
                    </a>
                <?php endfor; ?>
                <a href="?tipo=recuperacao" 
                   class="px-6 py-3 text-sm font-semibold transition-all relative <?= $tipoVisualizacao == 'recuperacao' ? 'text-primary-green' : 'text-gray-600 hover:text-gray-900' ?>">
                    Recuperação
                    <?php if ($tipoVisualizacao == 'recuperacao'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-green"></span>
                    <?php endif; ?>
                </a>
                <a href="?tipo=final" 
                   class="px-6 py-3 text-sm font-semibold transition-all relative <?= $tipoVisualizacao == 'final' ? 'text-primary-green' : 'text-gray-600 hover:text-gray-900' ?>">
                    Final
                    <?php if ($tipoVisualizacao == 'final'): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-green"></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <?php if (!$alunoIdReal): ?>
            <!-- Aluno não encontrado -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center max-w-lg mx-auto mt-12 fade-in">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aluno não encontrado</h3>
                <p class="text-gray-500 mb-6 text-sm">Não foi possível identificar seu cadastro de aluno no sistema.</p>
                <a href="../dashboard.php" class="inline-flex items-center px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-800 transition-colors text-sm font-medium shadow-sm">
                    Voltar ao Início
                </a>
            </div>
        <?php elseif (!$turmaAtual): ?>
            <!-- Aluno não matriculado em turma -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center max-w-lg mx-auto mt-12 fade-in">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Não matriculado em turma</h3>
                <p class="text-gray-500 mb-6 text-sm">Você precisa estar matriculado em uma turma para visualizar suas notas.</p>
                <a href="../dashboard.php" class="inline-flex items-center px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-800 transition-colors text-sm font-medium shadow-sm">
                    Voltar ao Início
                </a>
            </div>
        <?php elseif (empty($todasDisciplinas)): ?>
            <!-- Nenhuma disciplina cadastrada -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center max-w-lg mx-auto mt-12 fade-in">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma disciplina encontrada</h3>
                <p class="text-gray-500 mb-6 text-sm">Sua turma ainda não possui disciplinas cadastradas ou professores atribuídos.</p>
                <div class="text-left bg-gray-50 rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-600"><strong>Turma:</strong> <?= htmlspecialchars($turmaAtual['turma_nome'] ?? 'N/A') ?></p>
                    <p class="text-sm text-gray-600"><strong>Ano Letivo:</strong> <?= $anoLetivo ?></p>
                </div>
                <a href="../dashboard.php" class="inline-flex items-center px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-800 transition-colors text-sm font-medium shadow-sm">
                    Voltar ao Início
                </a>
            </div>
        <?php else: ?>
            
            <!-- Lista de Disciplinas -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="divide-y divide-gray-200">
                    <?php foreach ($notasAgrupadas as $disciplinaId => $dados): 
                        // Calcular média conforme o tipo selecionado
                        $mediaBimestre = $mediasBimestre[$disciplinaId] ?? 0;
                        
                        // Buscar notas conforme o tipo
                        if ($mostrarRecuperacao) {
                            $notasExibir = [];
                            foreach ($todasNotas as $nota) {
                                if ($nota['disciplina_id'] == $disciplinaId && isset($nota['recuperacao']) && $nota['recuperacao']) {
                                    $notasExibir[] = $nota;
                                }
                            }
                        } elseif ($mostrarFinal) {
                            // Para final, mostrar todas as notas dos 4 bimestres
                            $notasExibir = [];
                            for ($b = 1; $b <= 4; $b++) {
                                $notasBimestre = $dados['bimestres'][$b] ?? [];
                                foreach ($notasBimestre as $nota) {
                                    if (!isset($nota['recuperacao']) || !$nota['recuperacao']) {
                                        $nota['bimestre_exibicao'] = $b;
                                        $notasExibir[] = $nota;
                                    }
                                }
                            }
                        } else {
                            $notasExibir = $dados['bimestres'][$bimestreSelecionado] ?? [];
                            // Filtrar apenas notas não de recuperação
                            $notasExibir = array_filter($notasExibir, function($nota) {
                                return !isset($nota['recuperacao']) || !$nota['recuperacao'];
                            });
                        }
                        
                        $temNota = $mediaBimestre > 0;
                        
                        // Determinar método de cálculo (por padrão, aritmética - pode ser ajustado depois)
                        $metodoCalculo = 'Média Aritmética';
                    ?>
                    <div class="disciplina-item hover:bg-gray-50 transition-colors" data-disciplina-id="disciplina-<?= $disciplinaId ?>">
                        <button onclick="toggleDisciplina(<?= $disciplinaId ?>)" class="w-full px-6 py-4 flex items-center gap-4 group">
                            <!-- Círculo com Nota -->
                            <div class="flex-shrink-0">
                                <?php if ($temNota): ?>
                                    <div class="w-14 h-14 rounded-full <?= $mediaBimestre >= 7 ? 'bg-blue-100' : ($mediaBimestre >= 5 ? 'bg-yellow-100' : 'bg-red-100') ?> flex items-center justify-center">
                                        <span class="text-lg font-bold <?= $mediaBimestre >= 7 ? 'text-blue-700' : ($mediaBimestre >= 5 ? 'text-yellow-700' : 'text-red-700') ?>">
                                            <?= number_format($mediaBimestre, 1, ',', '.') ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <span class="text-gray-400 text-2xl font-light">—</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Nome e Método -->
                            <div class="flex-1 min-w-0 text-left">
                                <h3 class="font-bold text-gray-900 text-base mb-1 truncate">
                                    <?= htmlspecialchars($dados['disciplina_nome']) ?>
                                </h3>
                                <p class="text-xs text-gray-500">
                                    <?= $metodoCalculo ?>
                                </p>
                            </div>
                            
                            <!-- Seta -->
                            <div class="flex-shrink-0">
                                <svg id="arrow-<?= $disciplinaId ?>" class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </button>
                        
                        <!-- Conteúdo Colapsável - Avaliações -->
                        <div class="collapse-content hidden" id="collapse-<?= $disciplinaId ?>">
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">
                                        <?php if ($mostrarRecuperacao): ?>
                                            Avaliações de Recuperação
                                        <?php elseif ($mostrarFinal): ?>
                                            Avaliações - Média Final
                                        <?php else: ?>
                                            Avaliações do <?= $bimestreSelecionado ?>º Bimestre
                                        <?php endif; ?>
                                    </h4>
                                    <?php if (empty($notasExibir)): ?>
                                        <p class="text-sm text-gray-500">Nenhuma avaliação lançada ainda.</p>
                                    <?php else: ?>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <?php foreach ($notasExibir as $nota): 
                                                $notaValor = floatval($nota['nota']);
                                                $notaClass = $notaValor >= 7 ? 'bg-green-50 border-green-200' : ($notaValor >= 5 ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200');
                                                $notaTextClass = $notaValor >= 7 ? 'text-green-700' : ($notaValor >= 5 ? 'text-yellow-700' : 'text-red-700');
                                            ?>
                                            <div class="p-3 rounded-lg border-2 <?= $notaClass ?>">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-semibold <?= $notaTextClass ?> truncate">
                                                            <?= htmlspecialchars($nota['avaliacao_titulo'] ?? 'Avaliação') ?>
                                                            <?php if ($mostrarFinal && isset($nota['bimestre_exibicao'])): ?>
                                                                <span class="text-xs text-gray-500 ml-2">(<?= $nota['bimestre_exibicao'] ?>º Bim)</span>
                                                            <?php endif; ?>
                                                        </p>
                                                        <?php if (!empty($nota['comentario'])): ?>
                                                            <p class="text-xs text-gray-600 mt-1 line-clamp-2"><?= htmlspecialchars($nota['comentario']) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="text-lg font-bold <?= $notaTextClass ?> ml-3 flex-shrink-0">
                                                        <?= number_format($notaValor, 1, ',', '.') ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="theme-manager.js"></script>

    <script>
        // Toggle Disciplina
        function toggleDisciplina(disciplinaId) {
            const content = document.getElementById('collapse-' + disciplinaId);
            const arrow = document.getElementById('arrow-' + disciplinaId);
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                if (arrow) arrow.style.transform = 'rotate(90deg)';
            } else {
                content.classList.add('hidden');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        }
    </script>

</body>
</html>
