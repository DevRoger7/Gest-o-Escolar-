<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/pessoas/ResponsavelModel.php');
require_once('../../Models/academico/ObservacaoDesempenhoModel.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eResponsavel()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$responsavelModel = new ResponsavelModel();
$observacaoModel = new ObservacaoDesempenhoModel();
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
$observacoes = [];

if ($alunoSelecionadoId) {
    foreach ($alunos as $aluno) {
        if ($aluno['id'] == $alunoSelecionadoId) {
            $alunoSelecionado = $aluno;
            break;
        }
    }
    
    if ($alunoSelecionado) {
        // Buscar observações do aluno (apenas as visíveis para responsável)
        $observacoes = $observacaoModel->listarPorAluno($alunoSelecionadoId, 1);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Observações dos Alunos - SIGAE</title>
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
                    <h1 class="text-xl font-bold text-gray-900">Observações dos Alunos</h1>
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
                    <p class="text-gray-500">Selecione um aluno para visualizar as observações.</p>
                </div>
            <?php elseif (empty($observacoes)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p class="text-gray-500">Nenhuma observação registrada para este aluno.</p>
                </div>
            <?php else: ?>
                <!-- Lista de Observações -->
                <div class="space-y-4">
                    <?php foreach ($observacoes as $obs): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?= htmlspecialchars($obs['titulo'] ?? 'Observação') ?>
                                        </h3>
                                        <?php
                                        $tipoCores = [
                                            'POSITIVA' => 'bg-green-100 text-green-800',
                                            'NEGATIVA' => 'bg-red-100 text-red-800',
                                            'NEUTRA' => 'bg-gray-100 text-gray-800',
                                            'OUTROS' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $tipo = $obs['tipo'] ?? 'OUTROS';
                                        $cor = $tipoCores[$tipo] ?? $tipoCores['OUTROS'];
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $cor ?>">
                                            <?= htmlspecialchars($tipo) ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <strong>Disciplina:</strong> <?= htmlspecialchars($obs['disciplina_nome'] ?? 'N/A') ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <strong>Professor:</strong> <?= htmlspecialchars($obs['professor_nome'] ?? 'N/A') ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">
                                        <?= date('d/m/Y', strtotime($obs['data'] ?? $obs['criado_em'] ?? 'now')) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">
                                    <?= htmlspecialchars($obs['observacao'] ?? 'Sem descrição') ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
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

