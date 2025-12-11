<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/pessoas/ResponsavelModel.php');
require_once('../../Models/academico/NotaModel.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eResponsavel()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$responsavelModel = new ResponsavelModel();
$notaModel = new NotaModel();
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

// Aluno selecionado (via GET ou primeiro da lista)
$alunoSelecionadoId = $_GET['aluno_id'] ?? ($alunos[0]['id'] ?? null);
$alunoSelecionado = null;
$turmaId = null;
$turmaAtual = null;
$notas = [];
$todasDisciplinas = [];

if ($alunoSelecionadoId) {
    // Buscar dados do aluno selecionado
    foreach ($alunos as $aluno) {
        if ($aluno['id'] == $alunoSelecionadoId) {
            $alunoSelecionado = $aluno;
            break;
        }
    }
    
    if ($alunoSelecionado) {
        // Buscar turma atual do aluno
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
        
        // Buscar disciplinas
        if ($turmaId) {
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
            
            // Buscar todas as notas
            $notas = $notaModel->buscarPorAluno($alunoSelecionadoId, $turmaId);
        }
    }
}

// Tipo de visualização
$tipoVisualizacao = $_GET['tipo'] ?? '1';
$bimestreSelecionado = null;
if (is_numeric($tipoVisualizacao) && $tipoVisualizacao >= 1 && $tipoVisualizacao <= 4) {
    $bimestreSelecionado = (int)$tipoVisualizacao;
}

// Agrupar notas por disciplina e bimestre
$notasAgrupadas = [];
foreach ($todasDisciplinas as $disciplina) {
    $notasAgrupadas[$disciplina['id']] = [
        'disciplina_nome' => $disciplina['disciplina_nome'],
        'bimestres' => [1 => [], 2 => [], 3 => [], 4 => []]
    ];
}

foreach ($notas as $nota) {
    $disciplinaId = $nota['disciplina_id'];
    $bimestre = $nota['bimestre'] ?? null;
    
    if (!isset($notasAgrupadas[$disciplinaId])) {
        $notasAgrupadas[$disciplinaId] = [
            'disciplina_nome' => $nota['disciplina_nome'] ?? 'Disciplina',
            'bimestres' => [1 => [], 2 => [], 3 => [], 4 => []]
        ];
    }
    
    if ($bimestre && $bimestre >= 1 && $bimestre <= 4) {
        $notasAgrupadas[$disciplinaId]['bimestres'][$bimestre][] = $nota;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas dos Alunos - SIGAE</title>
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
                    <h1 class="text-xl font-bold text-gray-900">Notas dos Alunos</h1>
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Seletor de Aluno -->
            <?php if (count($alunos) > 1): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Selecione o aluno:</label>
                <select id="seletor-aluno" onchange="window.location.href='?aluno_id=' + this.value" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-primary-green">
                    <?php foreach ($alunos as $aluno): ?>
                        <option value="<?= $aluno['id'] ?>" <?= $aluno['id'] == $alunoSelecionadoId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($aluno['aluno_nome']) ?> 
                            <?= $aluno['matricula'] ? ' (' . htmlspecialchars($aluno['matricula']) . ')' : '' ?>
                            <?= !empty($aluno['turma_nome']) ? ' - ' . htmlspecialchars($aluno['turma_nome']) : '' ?>
                            <?= !empty($aluno['escola_nome']) ? ' - ' . htmlspecialchars($aluno['escola_nome']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php elseif (count($alunos) == 1): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-800">
                        <strong>Aluno:</strong> <?= htmlspecialchars($alunos[0]['aluno_nome']) ?>
                        <?= !empty($alunos[0]['turma_nome']) ? ' - ' . htmlspecialchars($alunos[0]['turma_nome']) : '' ?>
                        <?= !empty($alunos[0]['escola_nome']) ? ' - ' . htmlspecialchars($alunos[0]['escola_nome']) : '' ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (empty($alunos)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p class="text-gray-500">Nenhum aluno associado ao seu cadastro.</p>
                </div>
            <?php elseif (!$alunoSelecionado): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p class="text-gray-500">Selecione um aluno para visualizar as notas.</p>
                </div>
            <?php elseif (!$turmaAtual): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p class="text-gray-500">O aluno selecionado não está matriculado em uma turma.</p>
                </div>
            <?php else: ?>
                <!-- Navegação de Bimestres -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex items-center gap-1 border-b-2 border-gray-200">
                        <?php for ($b = 1; $b <= 4; $b++): ?>
                            <a href="?aluno_id=<?= $alunoSelecionadoId ?>&tipo=<?= $b ?>" 
                               class="px-6 py-3 text-sm font-semibold transition-all relative <?= $bimestreSelecionado == $b ? 'text-primary-green' : 'text-gray-600 hover:text-gray-900' ?>">
                                <?= $b ?>º Bimestre
                                <?php if ($bimestreSelecionado == $b): ?>
                                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-green"></span>
                                <?php endif; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Tabela de Notas -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">1º Bim</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">2º Bim</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">3º Bim</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">4º Bim</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Média</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($notasAgrupadas as $disciplinaId => $dados): ?>
                                    <?php
                                    $mediasBimestre = [];
                                    $somaTotal = 0;
                                    $countTotal = 0;
                                    
                                    for ($b = 1; $b <= 4; $b++) {
                                        $notasBimestre = $dados['bimestres'][$b];
                                        $soma = 0;
                                        $count = 0;
                                        
                                        foreach ($notasBimestre as $nota) {
                                            $valor = (float)($nota['nota'] ?? 0);
                                            $soma += $valor;
                                            $count++;
                                        }
                                        
                                        $media = $count > 0 ? $soma / $count : 0;
                                        $mediasBimestre[$b] = $media;
                                        
                                        if ($media > 0) {
                                            $somaTotal += $media;
                                            $countTotal++;
                                        }
                                    }
                                    
                                    $mediaGeral = $countTotal > 0 ? $somaTotal / $countTotal : 0;
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($dados['disciplina_nome']) ?>
                                        </td>
                                        <?php for ($b = 1; $b <= 4; $b++): ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                                <?= $mediasBimestre[$b] > 0 ? number_format($mediasBimestre[$b], 1, ',', '.') : '-' ?>
                                            </td>
                                        <?php endfor; ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-semibold <?= $mediaGeral >= 6 ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $mediaGeral > 0 ? number_format($mediaGeral, 1, ',', '.') : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

