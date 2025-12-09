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

// Buscar turma atual ou última turma (fallback)
$turmaId = null;
$turmaAtual = null;
if ($alunoIdReal) {
    // Tenta turma vigente; se não houver, pega a mais recente
    $sqlTurma = "SELECT at.turma_id, t.ano_letivo, t.serie, t.letra, t.turno,
                 CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                 FROM aluno_turma at 
                 INNER JOIN turma t ON at.turma_id = t.id 
                 WHERE at.aluno_id = :aluno_id AND (at.fim IS NULL OR at.fim = '0000-00-00')
                 ORDER BY at.fim IS NULL DESC, at.fim DESC
                 LIMIT 1";
    $stmtTurma = $conn->prepare($sqlTurma);
    $stmtTurma->bindParam(':aluno_id', $alunoIdReal);
    $stmtTurma->execute();
    $turmaAtual = $stmtTurma->fetch(PDO::FETCH_ASSOC);
    $turmaId = $turmaAtual['turma_id'] ?? null;
}

// --- NEW: fallback to the most recent turma when no active turma is found ---
if (!$turmaId && $alunoIdReal) {
    $sqlTurmaFallback = "SELECT at.turma_id, t.ano_letivo, t.serie, t.letra, t.turno,
                 CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                 FROM aluno_turma at
                 INNER JOIN turma t ON at.turma_id = t.id
                 WHERE at.aluno_id = :aluno_id
                 ORDER BY at.inicio DESC
                 LIMIT 1";
    $stmtTurmaFallback = $conn->prepare($sqlTurmaFallback);
    $stmtTurmaFallback->bindParam(':aluno_id', $alunoIdReal);
    $stmtTurmaFallback->execute();
    $turmaAtual = $stmtTurmaFallback->fetch(PDO::FETCH_ASSOC);
    $turmaId = $turmaAtual['turma_id'] ?? null;
}
// --- end NEW ---

// Período: ajustar para mês atual (coerente com o cabeçalho "mês atual")
// --- NEW: use selected month from query (?mes=YYYY-MM) ---
$mesSelecionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', (string)$mesSelecionado)) {
    $mesSelecionado = date('Y-m');
}
$periodoInicio = date('Y-m-01', strtotime($mesSelecionado . '-01'));
$periodoFim    = date('Y-m-t', strtotime($mesSelecionado . '-01'));
// --- end NEW ---

// Buscar frequência
$frequencias = [];
$percentual = ['total_dias' => 0, 'dias_presentes' => 0, 'dias_faltas' => 0, 'percentual' => 0];

if ($alunoIdReal && $turmaId) {
    $frequencias = $frequenciaModel->buscarPorAluno($alunoIdReal, $turmaId, $periodoInicio, $periodoFim);
    $percentual  = $frequenciaModel->calcularPercentual($alunoIdReal, $turmaId, $periodoInicio, $periodoFim);
}


function dias_frequentados_data($mes, $ano, $alunoId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT 
                f.data,
                CASE 
                    WHEN f.presenca = 1 THEN 1
                    WHEN f.presenca = 0 THEN 0
                    ELSE f.presenca
                END as presenca_valor
            FROM frequencia f
            INNER JOIN aluno a ON f.aluno_id = a.id
            INNER JOIN pessoa p ON a.pessoa_id = p.id
            WHERE p.id = :pessoa_id 
                AND p.tipo = 'aluno'
                AND YEAR(f.data) = :ano
                AND MONTH(f.data) = :mes
            ORDER BY f.data";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':pessoa_id', $alunoId, PDO::PARAM_INT);
        $stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        // Log do erro ou tratamento apropriado
        error_log("Erro na função dias_frequentados_data: " . $e->getMessage());
        return []; // Retorna array vazio em caso de erro
    }
}

$dias_frequentados_data = dias_frequentados_data(date('m'),date('Y'),$usuarioId);

/**
 * Conta quantos registros de frequência existem para o aluno.
 */
function outputalunoid($alunoId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = " SELECT id FROM aluno WHERE pessoa_id = :pessoa_id ;";
            
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':pessoa_id', $alunoId);
        $stmt->execute(); 
    }
    catch (PDOException $e) {
        echo "Erro ao retirar o id do aluno com base na tabela pessoa: " . $e->getMessage();
        return 0; 
    }
}
$outputalunoid = outputalunoid($usuarioId);
// 
function count_frequencia_true($usuarioId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT COUNT(*) 
    FROM frequencia f
    INNER JOIN aluno a ON f.aluno_id = a.id
    WHERE a.pessoa_id = :pessoa_id
    AND f.presenca = 1";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':pessoa_id', $usuarioId);
        $stmt->execute();
        
        return (int) $stmt->fetchColumn(); // retorna direto o número!
        
    } catch (PDOException $e) {
        error_log("Erro ao contar frequência: " . $e->getMessage());
        return 0;
    }
}
$count_frequencia_true = count_frequencia_true($usuarioId);
function count_frequencia_false($usuarioId) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT COUNT(*) 
    FROM frequencia f
    INNER JOIN aluno a ON f.aluno_id = a.id
    WHERE a.pessoa_id = :pessoa_id
    AND f.presenca = 0";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':pessoa_id', $usuarioId);
        $stmt->execute();
        
        return (int) $stmt->fetchColumn(); // retorna direto o número!
        
    } catch (PDOException $e) {
        error_log("Erro ao contar frequência: " . $e->getMessage());
        return 0;
    }
}
$count_frequencia_false = count_frequencia_false($usuarioId);
function frequencia_por_mes($usuarioId, $mes) {
    
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
                <a href="dashboard.php" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors">
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
                    <?php $total_frequencia = $count_frequencia_true + $count_frequencia_false?>
                    <p class="text-sm text-gray-500 font-medium">Frequência Total</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($count_frequencia_true/$total_frequencia, 1)*100 ?>%</h3>
                    <p class="text-xs text-gray-500 mt-1">Registros: <?= $total_frequencia ?></p>
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
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php  echo ($count_frequencia_true)?></h3>
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
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php echo ($count_frequencia_false) ?></h3>
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
            <!-- --- UPDATED: header sem debug print e mantendo seletor de mês --- -->
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-gray-900">Histórico Mensal</h3>
                    <!-- removido print_r($dias_frequentados_data) -->
                </div>
            
                <form method="get" action="" class="flex items-center gap-2">
                    <input 
                        type="month"
                        name="mes"
                        value="<?= htmlspecialchars($mesSelecionado) ?>"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-green"
                        aria-label="Selecione o mês"
                        required
                    />
                    <button 
                        type="submit"
                        class="bg-primary-green text-white text-sm font-medium px-3 py-2 rounded-lg hover:bg-secondary-green transition-colors"
                    >
                        Atualizar
                    </button>
                </form>
            </div>
            <!-- --- end UPDATED --- -->

            <div class="p-6">
                <?php if (empty($dias_frequentados_data)): ?>
                    <!-- Placeholder calendar when there is no data -->
                    <?php
                        $mesPlaceholder = $mesSelecionado;
                        $primeiroDiaPlaceholder = date('w', strtotime($mesPlaceholder . '-01'));
                        $ultimoDiaPlaceholder   = date('t', strtotime($mesPlaceholder . '-01'));
                        $percentualMesPlaceholder = 0;
                    ?>
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h4 class="text-base font-semibold text-gray-800">
                                <?= ucfirst(strftime('%B %Y', strtotime($mesPlaceholder . '-01'))) ?>
                            </h4>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-2 rounded-full bg-green-50 text-green-700 border border-green-200 px-3 py-1 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Faltas: 0
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-gray-50 text-gray-700 border border-gray-200 px-3 py-1 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                    <?= $percentualMesPlaceholder ?>%
                                </span>
                            </div>
                        </div>

                        <div class="rounded-lg border border-blue-200 bg-blue-50 text-blue-700 px-3 py-2 text-sm">
                            Sem registros de frequência no período. Exibindo o esboço do calendário do mês.
                        </div>

                        <div class="grid grid-cols-7 gap-2 sm:gap-3 max-w-3xl">
                            <?php
                                $diasSemanaPlaceholder = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
                                foreach ($diasSemanaPlaceholder as $d) {
                                    echo "<div class='text-center text-[11px] sm:text-xs font-medium text-gray-400 uppercase tracking-wide py-1'>$d</div>";
                                }

                                for ($i = 0; $i < $primeiroDiaPlaceholder; $i++) echo "<div></div>";

                                for ($dia = 1; $dia <= $ultimoDiaPlaceholder; $dia++):
                                    $dataCompletaPlaceholder = $mesPlaceholder . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);
                                    $isTodayPlaceholder = $dataCompletaPlaceholder == date('Y-m-d') ? 'ring-2 ring-primary-green ring-offset-2' : '';
                            ?>
                                <div class="relative group aspect-square rounded-lg flex items-center justify-center text-sm font-semibold bg-gray-50 text-gray-500 border border-gray-200 <?= $isTodayPlaceholder ?>" title="Sem registro">
                                    <?= $dia ?>
                                    <div class="pointer-events-none absolute left-1/2 -translate-x-1/2 bottom-0 translate-y-full mt-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-900 text-white text-[11px] px-2 py-1 rounded shadow-lg">
                                        Sem registro
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="flex flex-wrap gap-4 mt-6 border-t border-gray-100 pt-4">
                            <div class="flex items-center gap-2 text-sm text-gray-700">
                                <span class="w-3 h-3 rounded-full bg-green-50 border border-green-200"></span>
                                Presente
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700">
                                <span class="w-3 h-3 rounded-full bg-red-50 border border-red-200"></span>
                                Falta
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-700">
                                <span class="w-3 h-3 rounded-full bg-gray-50 border border-gray-200"></span>
                                Sem registro
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php
                        // --- UPDATED: usar dados do mês selecionado vindos de dias_frequentados_data ---
                        $mesAtual = $mesSelecionado;
                        $primeiroDia = date('w', strtotime($mesAtual . '-01'));
                        $ultimoDia   = date('t', strtotime($mesAtual . '-01'));

                        // Indexar por data (Y-m-d => 1/0)
                        $freqsPorData = [];
                        $presencasMes = 0;
                        $faltasMes    = 0;

                        foreach ($dias_frequentados_data as $r) {
                            $dataNorm = date('Y-m-d', strtotime($r['data']));
                            $valor = (int)$r['presenca_valor'];
                            $freqsPorData[$dataNorm] = $valor;

                            if ($valor === 1) $presencasMes++;
                            elseif ($valor === 0) $faltasMes++;
                        }

                        $totalRegMes   = $presencasMes + $faltasMes;
                        $percentualMes = $totalRegMes > 0 ? round(($presencasMes / $totalRegMes) * 100) : 0;
                    ?>

                    <div class="mb-10">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <h4 class="text-base font-semibold text-gray-800">
                                <?= ucfirst(strftime('%B %Y', strtotime($mesAtual . '-01'))) ?>
                            </h4>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-2 rounded-full bg-green-50 text-green-700 border border-green-200 px-3 py-1 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    Presenças: <?= $presencasMes ?>
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Faltas: <?= $faltasMes ?>
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-gray-50 text-gray-700 border border-gray-200 px-3 py-1 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                    <?= $percentualMes ?>%
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-7 gap-2 sm:gap-3 max-w-3xl">
                            <?php
                                $diasSemana = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
                                foreach ($diasSemana as $d) {
                                    echo "<div class='text-center text-[11px] sm:text-xs font-medium text-gray-400 uppercase tracking-wide py-1'>$d</div>";
                                }

                                for ($i = 0; $i < $primeiroDia; $i++) echo "<div></div>";

                                for ($dia = 1; $dia <= $ultimoDia; $dia++):
                                    $dataCompleta = $mesAtual . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);

                                    $status = null;
                                    if (isset($freqsPorData[$dataCompleta])) {
                                        $status = $freqsPorData[$dataCompleta] === 1 ? 'presente' : 'falta';
                                    }

                                    $bgClass = 'bg-gray-50 text-gray-500 border border-gray-200';
                                    if ($status === 'presente') $bgClass = 'bg-green-50 text-green-700 border border-green-200';
                                    if ($status === 'falta')   $bgClass = 'bg-red-50 text-red-700 border border-red-200';

                                    $tooltipTexto = $status === 'presente' ? 'Presente' : ($status === 'falta' ? 'Falta' : 'Sem registro');
                                    $isToday = $dataCompleta == date('Y-m-d') ? 'ring-2 ring-primary-green ring-offset-2' : '';
                            ?>
                                <div class="relative group aspect-square rounded-lg flex items-center justify-center text-sm font-semibold <?= $bgClass ?> <?= $isToday ?>" title="<?= $tooltipTexto ?>">
                                    <?= $dia ?>
                                    <?php if ($status): ?>
                                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full <?= $status === 'presente' ? 'bg-green-500' : 'bg-red-500' ?>"></span>
                                    <?php endif; ?>
                                    <div class="pointer-events-none absolute left-1/2 -translate-x-1/2 bottom-0 translate-y-full mt-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-900 text-white text-[11px] px-2 py-1 rounded shadow-lg">
                                        <?= $tooltipTexto ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4 mt-6 border-t border-gray-100 pt-4">
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <span class="w-3 h-3 rounded-full bg-green-50 border border-green-200"></span>
                            Presente
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <span class="w-3 h-3 rounded-full bg-red-50 border border-red-200"></span>
                            Falta
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-700">
                            <span class="w-3 h-3 rounded-full bg-gray-50 border border-gray-200"></span>
                            Sem registro
                        </div>
                    </div>
                    <!-- ... existing code ... -->
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="theme-manager.js"></script>
</body>
</html>
