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
require_once('../../Models/academico/BoletimModel.php');

$boletimModel = new BoletimModel();
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
$anoLetivo = date('Y');
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

// Buscar boletins
$boletins = [];
if ($alunoIdReal) {
    $boletins = $boletimModel->listarPorAluno($alunoIdReal, $anoLetivo);
}

// Buscar boletim detalhado se solicitado
$boletimDetalhado = null;
if (isset($_GET['bimestre']) && $turmaId) {
    $bimestre = $_GET['bimestre'];
    $boletimDetalhado = $boletimModel->buscar($alunoIdReal, $turmaId, $anoLetivo, $bimestre);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Boletins - SIGAE</title>
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
        .hover-scale { transition: transform 0.2s; }
        .hover-scale:hover { transform: scale(1.02); }
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <span class="text-lg font-semibold text-gray-800">Boletins</span>
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
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Boletins Escolares</h1>
            <p class="text-gray-600 mt-1">Consulte seus boletins por bimestre</p>
        </div>

        <?php if ($boletimDetalhado): ?>
            <!-- Visualização de Boletim Detalhado -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Boletim do <?= $boletimDetalhado['bimestre'] ?>º Bimestre</h2>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($turmaAtual['turma_nome'] ?? '') ?> • <?= $anoLetivo ?></p>
                    </div>
                    <a href="aluno_boletins.php" class="text-sm text-primary-green font-medium hover:underline">Voltar para lista</a>
                </div>
                
                <div class="p-6">
                    <!-- Resumo do Bimestre -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-100 text-center">
                            <p class="text-xs text-blue-600 font-semibold uppercase">Média Geral</p>
                            <p class="text-2xl font-bold text-blue-700"><?= number_format($boletimDetalhado['media_geral'], 1, ',', '.') ?></p>
                        </div>
                        <div class="p-4 bg-green-50 rounded-lg border border-green-100 text-center">
                            <p class="text-xs text-green-600 font-semibold uppercase">Frequência</p>
                            <p class="text-2xl font-bold text-green-700"><?= number_format($boletimDetalhado['frequencia_percentual'], 0) ?>%</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 text-center">
                            <p class="text-xs text-gray-500 font-semibold uppercase">Situação</p>
                            <p class="text-xl font-bold text-gray-800"><?= $boletimDetalhado['situacao'] ?></p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Média</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Faltas</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($boletimDetalhado['itens'] as $item): ?>
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($item['disciplina_nome']) ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-bold <?= $item['media'] >= 7 ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= number_format($item['media'], 1, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        <?= $item['faltas'] ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $item['situacao'] == 'APROVADO' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $item['situacao'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Lista de Bimestres -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php for ($b = 1; $b <= 4; $b++): 
                    $dadosBimestre = null;
                    foreach ($boletins as $bol) {
                        if ($bol['bimestre'] == $b) {
                            $dadosBimestre = $bol;
                            break;
                        }
                    }
                    $disponivel = !empty($dadosBimestre);
                ?>
                <a href="<?= $disponivel ? '?bimestre=' . $b : '#' ?>" class="block bg-white rounded-xl border border-gray-200 p-6 hover-scale shadow-sm relative overflow-hidden group <?= $disponivel ? 'cursor-pointer' : 'cursor-not-allowed opacity-75' ?>">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <span class="text-6xl font-bold text-gray-800"><?= $b ?></span>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 mb-4 relative z-10"><?= $b ?>º Bimestre</h3>
                    
                    <?php if ($disponivel): ?>
                        <div class="space-y-3 relative z-10">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Média Geral</span>
                                <span class="font-bold text-gray-900"><?= number_format($dadosBimestre['media_geral'], 1, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Frequência</span>
                                <span class="font-bold text-green-600"><?= number_format($dadosBimestre['frequencia_percentual'], 0) ?>%</span>
                            </div>
                            <div class="pt-3 border-t border-gray-100 mt-3">
                                <span class="block w-full text-center text-xs font-medium text-primary-green">Ver Detalhes</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="h-24 flex items-center justify-center relative z-10">
                            <span class="text-sm text-gray-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Não disponível
                            </span>
                        </div>
                    <?php endif; ?>
                </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="theme-manager.js"></script>
</body>
</html>
