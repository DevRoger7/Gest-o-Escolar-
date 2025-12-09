<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/pessoas/ResponsavelModel.php');
require_once('../../Models/academico/FrequenciaModel.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eResponsavel()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$responsavelModel = new ResponsavelModel();
$frequenciaModel = new FrequenciaModel();
$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar responsável_id
$usuarioId = $_SESSION['usuario_id'] ?? null;
$pessoaId = $_SESSION['pessoa_id'] ?? null;

if (!$pessoaId && $usuarioId) {
    $sqlPessoa = "SELECT pessoa_id FROM usuario WHERE id = :usuario_id LIMIT 1";
    $stmtPessoa = $conn->prepare($sqlPessoa);
    $stmtPessoa->bindParam(':usuario_id', $usuarioId);
    $stmtPessoa->execute();
    $usuario = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
    $pessoaId = $usuario['pessoa_id'] ?? null;
}

// Buscar alunos do responsável
$alunos = [];
if ($pessoaId) {
    $alunos = $responsavelModel->listarAlunos($pessoaId);
}

// Aluno selecionado
$alunoSelecionadoId = $_GET['aluno_id'] ?? ($alunos[0]['id'] ?? null);
$alunoSelecionado = null;
$turmaId = null;
$turmaAtual = null;
$frequencias = [];
$percentual = ['total_dias' => 0, 'dias_presentes' => 0, 'dias_faltas' => 0, 'percentual' => 0];

// Período (mês atual ou selecionado)
$mesSelecionado = $_GET['mes'] ?? date('Y-m');
$periodoInicio = date('Y-m-01', strtotime($mesSelecionado));
$periodoFim = date('Y-m-t', strtotime($mesSelecionado));

if ($alunoSelecionadoId) {
    foreach ($alunos as $aluno) {
        if ($aluno['id'] == $alunoSelecionadoId) {
            $alunoSelecionado = $aluno;
            break;
        }
    }
    
    if ($alunoSelecionado) {
        // Buscar turma atual
        $sqlTurma = "SELECT at.turma_id, t.ano_letivo, t.serie, t.letra, t.turno,
                     CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                     FROM aluno_turma at 
                     INNER JOIN turma t ON at.turma_id = t.id 
                     WHERE at.aluno_id = :aluno_id AND (at.fim IS NULL OR at.status = 'MATRICULADO')
                     ORDER BY at.inicio DESC
                     LIMIT 1";
        $stmtTurma = $conn->prepare($sqlTurma);
        $stmtTurma->bindParam(':aluno_id', $alunoSelecionadoId);
        $stmtTurma->execute();
        $turmaAtual = $stmtTurma->fetch(PDO::FETCH_ASSOC);
        $turmaId = $turmaAtual['turma_id'] ?? null;
        
        if ($turmaId) {
            $frequencias = $frequenciaModel->buscarPorAluno($alunoSelecionadoId, $turmaId, $periodoInicio, $periodoFim);
            $percentual = $frequenciaModel->calcularPercentual($alunoSelecionadoId, $turmaId, $periodoInicio, $periodoFim);
        }
    }
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
    <title>Frequência dos Alunos - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include('components/sidebar_responsavel.php'); ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
                <div class="flex items-center gap-4">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-xl font-bold text-gray-900">Frequência dos Alunos</h1>
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Seletor de Aluno -->
            <?php if (count($alunos) > 1): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o aluno:</label>
                <select id="seletor-aluno" onchange="window.location.href='?aluno_id=' + this.value + '&mes=<?= $mesSelecionado ?>'" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                    <?php foreach ($alunos as $aluno): ?>
                        <option value="<?= $aluno['id'] ?>" <?= $aluno['id'] == $alunoSelecionadoId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($aluno['aluno_nome']) ?> 
                            <?= $aluno['matricula'] ? ' (' . htmlspecialchars($aluno['matricula']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php elseif (count($alunos) == 1): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-800">
                        <strong>Aluno:</strong> <?= htmlspecialchars($alunos[0]['aluno_nome']) ?>
                        <?= !empty($alunos[0]['turma_nome']) ? ' - ' . htmlspecialchars($alunos[0]['turma_nome']) : '' ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (empty($alunos)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p class="text-gray-500">Nenhum aluno associado ao seu cadastro.</p>
                </div>
            <?php elseif (!$alunoSelecionado || !$turmaAtual): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p class="text-gray-500">O aluno selecionado não está matriculado em uma turma.</p>
                </div>
            <?php else: ?>
                <!-- Seletor de Mês -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o mês:</label>
                    <input type="month" value="<?= $mesSelecionado ?>" onchange="window.location.href='?aluno_id=<?= $alunoSelecionadoId ?>&mes=' + this.value" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                </div>

                <!-- Resumo -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <p class="text-sm text-gray-600 mb-1">Total de Dias</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $percentual['total_dias'] ?></p>
                    </div>
                    <div class="bg-green-50 rounded-lg shadow-sm border border-green-200 p-4">
                        <p class="text-sm text-green-600 mb-1">Dias Presentes</p>
                        <p class="text-2xl font-bold text-green-700"><?= $percentual['dias_presentes'] ?></p>
                    </div>
                    <div class="bg-red-50 rounded-lg shadow-sm border border-red-200 p-4">
                        <p class="text-sm text-red-600 mb-1">Dias Faltas</p>
                        <p class="text-2xl font-bold text-red-700"><?= $percentual['dias_faltas'] ?></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg shadow-sm border border-blue-200 p-4">
                        <p class="text-sm text-blue-600 mb-1">Percentual</p>
                        <p class="text-2xl font-bold text-blue-700"><?= number_format($percentual['percentual'], 1) ?>%</p>
                    </div>
                </div>

                <!-- Calendário de Frequência -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Frequência do Mês</h3>
                    <div class="grid grid-cols-7 gap-2">
                        <?php
                        $diasNoMes = date('t', strtotime($mesSelecionado));
                        $primeiroDia = date('w', strtotime($mesSelecionado . '-01'));
                        $primeiroDia = $primeiroDia == 0 ? 6 : $primeiroDia - 1; // Ajustar para segunda = 0
                        
                        // Criar mapa de frequências por data
                        $frequenciasPorData = [];
                        foreach ($frequencias as $freq) {
                            $data = date('Y-m-d', strtotime($freq['data']));
                            $frequenciasPorData[$data] = $freq;
                        }
                        
                        // Dias da semana
                        $diasSemana = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
                        foreach ($diasSemana as $dia): ?>
                            <div class="text-center text-xs font-medium text-gray-500 py-2"><?= $dia ?></div>
                        <?php endforeach; ?>
                        
                        <?php for ($i = 0; $i < $primeiroDia; $i++): ?>
                            <div></div>
                        <?php endfor; ?>
                        
                        <?php for ($dia = 1; $dia <= $diasNoMes; $dia++): ?>
                            <?php
                            $dataAtual = sprintf('%s-%02d', $mesSelecionado, $dia);
                            $freq = $frequenciasPorData[$dataAtual] ?? null;
                            $presenca = $freq ? (int)$freq['presenca'] : null;
                            
                            $cor = 'bg-gray-100 text-gray-400'; // Sem registro
                            if ($presenca === 1) {
                                $cor = 'bg-green-100 text-green-700 border-green-300';
                            } elseif ($presenca === 0) {
                                $cor = 'bg-red-100 text-red-700 border-red-300';
                            }
                            ?>
                            <div class="aspect-square flex items-center justify-center border rounded-lg <?= $cor ?> text-sm font-medium">
                                <?= $dia ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="mt-6 flex items-center gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-green-100 border border-green-300 rounded"></div>
                            <span class="text-gray-600">Presente</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-red-100 border border-red-300 rounded"></div>
                            <span class="text-gray-600">Falta</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-gray-100 border border-gray-300 rounded"></div>
                            <span class="text-gray-600">Sem registro</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };
    </script>
</body>
</html>

