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
require_once('../../Models/academico/FrequenciaModel.php');

$frequenciaModel = new FrequenciaModel();
$stats = new DashboardStats();
$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar aluno_id
$usuarioId = $_SESSION['usuario_id'] ?? null;
$sqlAluno = "SELECT a.id FROM aluno a INNER JOIN usuario u ON a.pessoa_id = u.pessoa_id WHERE u.id = :usuario_id";
$stmtAluno = $conn->prepare($sqlAluno);
$stmtAluno->bindParam(':usuario_id', $usuarioId);
$stmtAluno->execute();
$aluno = $stmtAluno->fetch(PDO::FETCH_ASSOC);
$alunoIdReal = $aluno['id'] ?? null;

// Buscar turma atual
$turmaId = null;
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
    $turmaId = $turmaAtual['turma_id'] ?? null;
}

// Período (mês atual)
$periodoInicio = date('Y-m-01');
$periodoFim = date('Y-m-t');

// Buscar frequência
$frequencias = [];
$percentual = ['total_dias' => 0, 'dias_presentes' => 0, 'dias_faltas' => 0, 'percentual' => 0];

if ($alunoIdReal && $turmaId) {
    $frequencias = $frequenciaModel->buscarPorAluno($alunoIdReal, $turmaId, $periodoInicio, $periodoFim);
    $percentual = $frequenciaModel->calcularPercentual($alunoIdReal, $turmaId, $periodoInicio, $periodoFim);
}

// Agrupar por mês
$frequenciasPorMes = [];
foreach ($frequencias as $freq) {
    $mes = date('Y-m', strtotime($freq['data']));
    if (!isset($frequenciasPorMes[$mes])) {
        $frequenciasPorMes[$mes] = [];
    }
    $frequenciasPorMes[$mes][] = $freq;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Frequência - SIGAE</title>
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
        .stat-card { transition: all 0.2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">
    
    <!-- Header Fixo -->
    <header class="bg-white shadow-sm sticky top-0 z-40 border-b border-gray-200 h-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="../dashboard.php" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div class="h-6 w-px bg-gray-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-primary-green/10 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="text-lg font-semibold text-gray-800">Minha Frequência</span>
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
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Controle de Presença</h1>
            <p class="text-gray-600 mt-1">
                Resumo de faltas e presenças no mês atual
            </p>
        </div>

        <!-- Cards de Estatísticas (Minimalista) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm stat-card flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Frequência Total</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($percentual['percentual'], 1) ?>%</h3>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 mt-3 w-32">
                        <div class="bg-primary-green h-1.5 rounded-full" style="width: <?= min($percentual['percentual'], 100) ?>%"></div>
                    </div>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm stat-card flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Presenças</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?= $percentual['dias_presentes'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1">Dias letivos</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm stat-card flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Faltas</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?= $percentual['dias_faltas'] ?></h3>
                    <p class="text-xs text-gray-400 mt-1">Necessitam justificativa</p>
                </div>
                <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Calendário -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Histórico Mensal</h3>
            </div>
            <div class="p-6">
                <?php if (empty($frequenciasPorMes)): ?>
                    <div class="text-center py-8 text-gray-500">
                        Nenhum registro de frequência encontrado para este período.
                    </div>
                <?php else: ?>
                    <?php foreach ($frequenciasPorMes as $mes => $freqs): ?>
                        <div class="mb-8 last:mb-0">
                            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4"><?= ucfirst(strftime('%B %Y', strtotime($mes . '-01'))) ?></h4>
                            
                            <div class="grid grid-cols-7 gap-2 sm:gap-4 max-w-3xl">
                                <?php
                                $diasSemana = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
                                foreach ($diasSemana as $d) echo "<div class='text-center text-xs font-medium text-gray-400'>$d</div>";
                                
                                $primeiroDia = date('w', strtotime($mes . '-01'));
                                $ultimoDia = date('t', strtotime($mes . '-01'));
                                
                                for ($i = 0; $i < $primeiroDia; $i++) echo "<div></div>";
                                
                                for ($dia = 1; $dia <= $ultimoDia; $dia++):
                                    $dataCompleta = $mes . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);
                                    $status = null;
                                    foreach ($freqs as $freq) {
                                        if ($freq['data'] == $dataCompleta) {
                                            $status = $freq['presenca'] ? 'presente' : 'falta';
                                            break;
                                        }
                                    }
                                    
                                    $bgClass = 'bg-gray-50 text-gray-400';
                                    if ($status === 'presente') $bgClass = 'bg-green-100 text-green-700 border border-green-200';
                                    if ($status === 'falta') $bgClass = 'bg-red-100 text-red-700 border border-red-200';
                                    
                                    $isToday = $dataCompleta == date('Y-m-d') ? 'ring-2 ring-primary-green ring-offset-2' : '';
                                ?>
                                    <div class="aspect-square rounded-lg flex items-center justify-center text-sm font-medium <?= $bgClass ?> <?= $isToday ?>">
                                        <?= $dia ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="flex gap-6 mt-6 border-t border-gray-100 pt-4">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <div class="w-3 h-3 rounded-full bg-green-100 border border-green-200"></div>
                            Presente
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <div class="w-3 h-3 rounded-full bg-red-100 border border-red-200"></div>
                            Falta
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <div class="w-3 h-3 rounded-full bg-gray-50 border border-gray-100"></div>
                            Sem registro
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="theme-manager.js"></script>
</body>
</html>
