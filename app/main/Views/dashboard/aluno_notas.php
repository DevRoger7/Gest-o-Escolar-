<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/dashboard/DashboardStats.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAluno() && !eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
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

// Buscar aluno_id real
$sqlAluno = "SELECT a.id FROM aluno a INNER JOIN usuario u ON a.pessoa_id = u.pessoa_id WHERE u.id = :usuario_id";
$stmtAluno = $conn->prepare($sqlAluno);
$stmtAluno->bindParam(':usuario_id', $usuarioId);
$stmtAluno->execute();
$aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
$alunoIdReal = $aluno['id'] ?? null;

// Buscar turma atual do aluno
$turmaId = null;
$anoLetivo = date('Y'); // Sempre ter um ano letivo (padrão: ano atual)
$turmaAtual = null;

if ($alunoIdReal) {
    $sqlTurma = "SELECT at.turma_id, t.ano_letivo, t.serie, t.letra, t.turno,
                 CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                 FROM aluno_turma at 
                 INNER JOIN turma t ON at.turma_id = t.id 
                 WHERE at.aluno_id = :aluno_id AND at.fim IS NULL 
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
    $sqlDisciplinas = "SELECT DISTINCT d.id, d.nome as disciplina_nome
                       FROM turma_professor tp
                       INNER JOIN disciplina d ON tp.disciplina_id = d.id
                       WHERE tp.turma_id = :turma_id AND (tp.fim IS NULL OR tp.fim >= CURDATE())
                       ORDER BY d.nome";
    $stmtDisciplinas = $conn->prepare($sqlDisciplinas);
    $stmtDisciplinas->bindParam(':turma_id', $turmaId);
    $stmtDisciplinas->execute();
    $todasDisciplinas = $stmtDisciplinas->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar notas
$notas = [];
if ($alunoIdReal && $turmaId) {
    $notas = $notaModel->buscarPorAluno($alunoIdReal, $turmaId);
}

// Inicializar estrutura com TODAS as disciplinas e TODOS os bimestres
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

// Preencher com as notas existentes
foreach ($notas as $nota) {
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

// Calcular médias e estatísticas
$medias = [];
$estatisticas = [
    'media_geral' => 0,
    'total_disciplinas' => count($notasAgrupadas),
    'disciplinas_aprovadas' => 0,
    'disciplinas_recuperacao' => 0,
    'disciplinas_reprovadas' => 0,
    'melhor_disciplina' => null,
    'pior_disciplina' => null,
    'evolucao_bimestres' => []
];

foreach ($notasAgrupadas as $disciplinaId => $dados) {
    $medias[$disciplinaId] = [];
    $mediasBimestres = [];
    
    // Processar apenas bimestres 1-4
    for ($b = 1; $b <= 4; $b++) {
        $notasBimestre = $dados['bimestres'][$b] ?? [];
        $soma = 0;
        $count = 0;
        foreach ($notasBimestre as $nota) {
            $soma += floatval($nota['nota']);
            $count++;
        }
        $mediaBimestre = $count > 0 ? round($soma / $count, 2) : 0;
        $medias[$disciplinaId][$b] = $mediaBimestre;
        $mediasBimestres[] = $mediaBimestre;
    }
    
    // Calcular média geral da disciplina
    $mediaGeralDisciplina = 0;
    $countBimestres = 0;
    foreach ($medias[$disciplinaId] as $media) {
        if ($media > 0) {
            $mediaGeralDisciplina += $media;
            $countBimestres++;
        }
    }
    $mediaGeralDisciplina = $countBimestres > 0 ? round($mediaGeralDisciplina / $countBimestres, 2) : 0;
    
    // Classificar disciplina
    if ($mediaGeralDisciplina >= 7) {
        $estatisticas['disciplinas_aprovadas']++;
    } elseif ($mediaGeralDisciplina >= 5) {
        $estatisticas['disciplinas_recuperacao']++;
    } else {
        $estatisticas['disciplinas_reprovadas']++;
    }
    
    // Melhor e pior disciplina
    if ($estatisticas['melhor_disciplina'] === null || $mediaGeralDisciplina > $estatisticas['melhor_disciplina']['media']) {
        $estatisticas['melhor_disciplina'] = [
            'nome' => $dados['disciplina_nome'],
            'media' => $mediaGeralDisciplina
        ];
    }
    if ($estatisticas['pior_disciplina'] === null || $mediaGeralDisciplina < $estatisticas['pior_disciplina']['media']) {
        $estatisticas['pior_disciplina'] = [
            'nome' => $dados['disciplina_nome'],
            'media' => $mediaGeralDisciplina
        ];
    }
    
    // Calcular média geral geral
    $estatisticas['media_geral'] += $mediaGeralDisciplina;
}

// Calcular média geral final
if ($estatisticas['total_disciplinas'] > 0) {
    $estatisticas['media_geral'] = round($estatisticas['media_geral'] / $estatisticas['total_disciplinas'], 2);
}

// Calcular evolução por bimestre
for ($b = 1; $b <= 4; $b++) {
    $somaBimestre = 0;
    $countBimestre = 0;
    foreach ($medias as $disciplinaId => $mediasDisciplina) {
        if (isset($mediasDisciplina[$b]) && $mediasDisciplina[$b] > 0) {
            $somaBimestre += $mediasDisciplina[$b];
            $countBimestre++;
        }
    }
    $estatisticas['evolucao_bimestres'][$b] = $countBimestre > 0 ? round($somaBimestre / $countBimestre, 2) : 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Notas - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        .collapse-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .collapse-content.expanded { max-height: 2000px; transition: max-height 0.5s ease-in; }
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
                <a href="dashboard.php" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors" title="Voltar ao Dashboard">
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
        <div class="mb-8 fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Desempenho Acadêmico</h1>
                    <p class="text-gray-600 mt-1">
                        <?php if ($turmaAtual): ?>
                            <span class="font-medium text-primary-green"><?= htmlspecialchars($turmaAtual['turma_nome']) ?></span> • 
                        <?php endif; ?>
                        Ano Letivo <span class="font-medium text-gray-900"><?= $anoLetivo ?></span>
                    </p>
                </div>
                
                <!-- Filtros -->
                <?php if (!empty($notasAgrupadas)): ?>
                <div class="flex gap-2">
                    <select id="filtroDisciplina" onchange="filtrarDisciplinas()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                        <option value="todas">Todas as Disciplinas</option>
                        <?php foreach ($notasAgrupadas as $disciplinaId => $dados): ?>
                            <option value="disciplina-<?= $disciplinaId ?>"><?= htmlspecialchars($dados['disciplina_nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($notasAgrupadas) && empty($todasDisciplinas)): ?>
            <!-- Estado Vazio (apenas se não houver turma/disciplinas) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center max-w-lg mx-auto mt-12 fade-in">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma disciplina encontrada</h3>
                <p class="text-gray-500 mb-6 text-sm">Você precisa estar matriculado em uma turma para visualizar suas notas.</p>
                <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-800 transition-colors text-sm font-medium shadow-sm">
                    Voltar ao Início
                </a>
            </div>
        <?php else: ?>
            
            <!-- Cards de Resumo Estatístico -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 fade-in">
                <!-- Média Geral -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-primary-green/10 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full <?= $estatisticas['media_geral'] >= 7 ? 'bg-green-100 text-green-700' : ($estatisticas['media_geral'] >= 5 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?> font-medium">
                            <?= $estatisticas['media_geral'] >= 7 ? 'Aprovado' : ($estatisticas['media_geral'] >= 5 ? 'Recuperação' : 'Reprovado') ?>
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?= number_format($estatisticas['media_geral'], 1, ',', '.') ?></h3>
                    <p class="text-sm text-gray-600">Média Geral</p>
                    <div class="mt-3 w-full bg-gray-100 rounded-full h-2">
                        <div class="progress-bar h-2 rounded-full <?= $estatisticas['media_geral'] >= 7 ? 'bg-green-500' : ($estatisticas['media_geral'] >= 5 ? 'bg-yellow-500' : 'bg-red-500') ?>" style="width: <?= min(($estatisticas['media_geral'] / 10) * 100, 100) ?>%"></div>
                    </div>
                </div>

                <!-- Melhor Disciplina -->
                <?php if ($estatisticas['melhor_disciplina']): ?>
                <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-1"><?= number_format($estatisticas['melhor_disciplina']['media'], 1, ',', '.') ?></h3>
                    <p class="text-sm text-gray-600 truncate" title="<?= htmlspecialchars($estatisticas['melhor_disciplina']['nome']) ?>">
                        <?= htmlspecialchars($estatisticas['melhor_disciplina']['nome']) ?>
                    </p>
                    <p class="text-xs text-green-600 font-medium mt-1">Melhor Desempenho</p>
                </div>
                <?php endif; ?>

                <!-- Disciplinas Aprovadas -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?= $estatisticas['disciplinas_aprovadas'] ?></h3>
                    <p class="text-sm text-gray-600">Aprovadas</p>
                    <p class="text-xs text-blue-600 font-medium mt-1">de <?= $estatisticas['total_disciplinas'] ?> disciplinas</p>
                </div>

                <!-- Total de Disciplinas -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm card-hover">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?= $estatisticas['total_disciplinas'] ?></h3>
                    <p class="text-sm text-gray-600">Disciplinas</p>
                    <p class="text-xs text-purple-600 font-medium mt-1">Total cursadas</p>
                </div>
            </div>

            <!-- Gráfico de Evolução -->
            <?php if (!empty($estatisticas['evolucao_bimestres'])): ?>
            <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm mb-8 fade-in">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-gray-900">Evolução por Bimestre</h2>
                    <span class="text-sm text-gray-500">Média geral por período</span>
                </div>
                <canvas id="evolucaoChart" height="80"></canvas>
            </div>
            <?php endif; ?>

            <!-- Grid de Disciplinas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="disciplinasContainer">
                <?php foreach ($notasAgrupadas as $disciplinaId => $dados): 
                    // Calcular média geral da disciplina (soma de todos os bimestres, mesmo zerados)
                    $mediaGeral = 0;
                    $countBimestres = 0;
                    for ($b = 1; $b <= 4; $b++) {
                        $mediaBimestre = $medias[$disciplinaId][$b] ?? 0;
                        $mediaGeral += $mediaBimestre;
                        $countBimestres++;
                    }
                    $mediaGeral = $countBimestres > 0 ? round($mediaGeral / $countBimestres, 2) : 0;
                    
                    // Definir cor baseada na média
                    $corTexto = $mediaGeral >= 7 ? 'text-primary-green' : ($mediaGeral >= 5 ? 'text-yellow-600' : 'text-red-600');
                    $corBg = $mediaGeral >= 7 ? 'bg-green-50' : ($mediaGeral >= 5 ? 'bg-yellow-50' : 'bg-red-50');
                    $corBorda = $mediaGeral >= 7 ? 'border-primary-green' : ($mediaGeral >= 5 ? 'border-yellow-500' : 'border-red-500');
                    $corProgresso = $mediaGeral >= 7 ? 'bg-green-500' : ($mediaGeral >= 5 ? 'bg-yellow-500' : 'bg-red-500');
                ?>
                <div class="disciplina-card bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover fade-in" data-disciplina-id="disciplina-<?= $disciplinaId ?>">
                    <!-- Header da Disciplina -->
                    <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-gray-900 mb-1"><?= htmlspecialchars($dados['disciplina_nome']) ?></h3>
                                <p class="text-xs text-gray-500">Disciplina Curricular</p>
                            </div>
                            <div class="text-right ml-4">
                                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium block mb-1">Média Geral</span>
                                <div class="px-4 py-2 rounded-lg font-bold text-xl <?= $corBg ?> <?= $corTexto ?> border-2 <?= $corBorda ?>">
                                    <?= number_format($mediaGeral, 1, ',', '.') ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Barra de Progresso -->
                        <div class="w-full bg-gray-100 rounded-full h-2 mt-3">
                            <div class="progress-bar h-2 rounded-full <?= $corProgresso ?>" style="width: <?= min(($mediaGeral / 10) * 100, 100) ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Conteúdo Colapsável -->
                    <div class="collapse-content" id="collapse-<?= $disciplinaId ?>">
                        <div class="p-5 space-y-6">
                            <?php for ($b = 1; $b <= 4; $b++): 
                                $notasBimestre = $dados['bimestres'][$b] ?? [];
                                $mediaBimestre = $medias[$disciplinaId][$b] ?? 0;
                            ?>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                                        <div class="flex items-center gap-2">
                                            <span class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-sm font-bold text-gray-700">
                                                <?= $b ?>º
                                            </span>
                                            <span class="text-sm font-semibold text-gray-700">
                                                <?= $b ?>º Bimestre
                                            </span>
                                        </div>
                                        <div class="text-sm">
                                            <span class="text-gray-500 mr-2">Média:</span>
                                            <span class="font-bold text-gray-900"><?= number_format($mediaBimestre, 1, ',', '.') ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                                        <?php if (empty($notasBimestre)): ?>
                                            <!-- Sem notas - mostrar campo zerado -->
                                            <div class="p-3 rounded-lg border-2 border-gray-200 bg-gray-50 flex justify-between items-center">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-semibold text-gray-400">Sem avaliações lançadas</p>
                                                </div>
                                                <span class="text-xl font-bold ml-3 flex-shrink-0 text-gray-400">0.0</span>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($notasBimestre as $nota): 
                                                $notaValor = floatval($nota['nota']);
                                                $notaClass = $notaValor >= 7 ? 'text-green-700 bg-green-50 border-green-200' : ($notaValor >= 5 ? 'text-yellow-700 bg-yellow-50 border-yellow-200' : 'text-red-700 bg-red-50 border-red-200');
                                            ?>
                                            <div class="p-3 rounded-lg border-2 <?= $notaClass ?> flex justify-between items-center tooltip">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-semibold opacity-90 truncate"><?= htmlspecialchars($nota['avaliacao_titulo'] ?? 'Avaliação') ?></p>
                                                    <?php if (!empty($nota['comentario'])): ?>
                                                        <p class="text-[10px] opacity-70 mt-1 line-clamp-2"><?= htmlspecialchars($nota['comentario']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="text-xl font-bold ml-3 flex-shrink-0"><?= number_format($notaValor, 1, ',', '.') ?></span>
                                                <?php if (!empty($nota['comentario'])): ?>
                                                    <span class="tooltip-text"><?= htmlspecialchars($nota['comentario']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Botão Expandir/Colapsar -->
                    <button onclick="toggleDisciplina(<?= $disciplinaId ?>)" class="w-full p-3 bg-gray-50 hover:bg-gray-100 text-sm font-medium text-gray-700 flex items-center justify-center gap-2 transition-colors border-t border-gray-200">
                        <span id="toggle-text-<?= $disciplinaId ?>">Ver Detalhes</span>
                        <svg id="toggle-icon-<?= $disciplinaId ?>" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="theme-manager.js"></script>

    <script>
        // Gráfico de Evolução
        <?php if (!empty($estatisticas['evolucao_bimestres'])): ?>
        const ctx = document.getElementById('evolucaoChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['1º Bimestre', '2º Bimestre', '3º Bimestre', '4º Bimestre'],
                    datasets: [{
                        label: 'Média Geral',
                        data: [
                            <?= $estatisticas['evolucao_bimestres'][1] ?>,
                            <?= $estatisticas['evolucao_bimestres'][2] ?>,
                            <?= $estatisticas['evolucao_bimestres'][3] ?>,
                            <?= $estatisticas['evolucao_bimestres'][4] ?>
                        ],
                        borderColor: '#2D5A27',
                        backgroundColor: 'rgba(45, 90, 39, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: '#2D5A27',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            callbacks: {
                                label: function(context) {
                                    return 'Média: ' + context.parsed.y.toFixed(1);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10,
                            ticks: {
                                stepSize: 1,
                                font: { size: 12 }
                            },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            ticks: { font: { size: 12 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }
        <?php endif; ?>

        // Toggle Disciplina
        function toggleDisciplina(disciplinaId) {
            const content = document.getElementById('collapse-' + disciplinaId);
            const icon = document.getElementById('toggle-icon-' + disciplinaId);
            const text = document.getElementById('toggle-text-' + disciplinaId);
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                icon.style.transform = 'rotate(0deg)';
                text.textContent = 'Ver Detalhes';
            } else {
                content.classList.add('expanded');
                icon.style.transform = 'rotate(180deg)';
                text.textContent = 'Ocultar Detalhes';
            }
        }

        // Filtro de Disciplinas
        function filtrarDisciplinas() {
            const filtro = document.getElementById('filtroDisciplina').value;
            const cards = document.querySelectorAll('.disciplina-card');
            
            cards.forEach(card => {
                if (filtro === 'todas' || card.dataset.disciplinaId === filtro) {
                    card.style.display = 'block';
                    setTimeout(() => card.style.opacity = '1', 10);
                } else {
                    card.style.opacity = '0';
                    setTimeout(() => card.style.display = 'none', 300);
                }
            });
        }

        // Animação de entrada para barras de progresso
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>

</body>
</html>
