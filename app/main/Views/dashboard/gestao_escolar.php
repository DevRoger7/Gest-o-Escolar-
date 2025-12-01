<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/academico/TurmaModel.php');
require_once('../../Models/academico/AlunoModel.php');
require_once('../../Models/dashboard/DashboardStats.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é GESTÃO
if ($_SESSION['tipo'] !== 'GESTAO' && !eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

require_once('../../config/Database.php');

$turmaModel = new TurmaModel();
$alunoModel = new AlunoModel();
$stats = new DashboardStats();

// Processar ações
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'criar_turma':
            $resultado = $turmaModel->criar([
                'escola_id' => $_POST['escola_id'] ?? null,
                'serie_id' => $_POST['serie_id'] ?? null,
                'ano_letivo' => $_POST['ano_letivo'] ?? date('Y'),
                'serie' => $_POST['serie'] ?? '',
                'letra' => $_POST['letra'] ?? '',
                'turno' => $_POST['turno'] ?? 'MANHA',
                'capacidade' => $_POST['capacidade'] ?? null,
                'sala' => $_POST['sala'] ?? null
            ]);
            
            if ($resultado['success']) {
                $mensagem = 'Turma criada com sucesso!';
                $tipoMensagem = 'success';
            } else {
                $mensagem = $resultado['message'] ?? 'Erro ao criar turma.';
                $tipoMensagem = 'error';
            }
            break;
            
        case 'editar_turma':
            $turmaId = $_POST['turma_id'] ?? null;
            if ($turmaId) {
                $resultado = $turmaModel->atualizar($turmaId, [
                    'escola_id' => $_POST['escola_id'] ?? null,
                    'serie_id' => $_POST['serie_id'] ?? null,
                    'ano_letivo' => $_POST['ano_letivo'] ?? date('Y'),
                    'serie' => $_POST['serie'] ?? '',
                    'letra' => $_POST['letra'] ?? '',
                    'turno' => $_POST['turno'] ?? 'MANHA',
                    'capacidade' => $_POST['capacidade'] ?? null,
                    'sala' => $_POST['sala'] ?? null,
                    'ativo' => $_POST['ativo'] ?? 1
                ]);
                
                if ($resultado) {
                    $mensagem = 'Turma atualizada com sucesso!';
                    $tipoMensagem = 'success';
                } else {
                    $mensagem = 'Erro ao atualizar turma.';
                    $tipoMensagem = 'error';
                }
            }
            break;
            
        case 'matricular_aluno':
            $resultado = $alunoModel->matricularEmTurma(
                $_POST['aluno_id'],
                $_POST['turma_id'],
                $_POST['data_inicio'] ?? date('Y-m-d')
            );
            
            if ($resultado) {
                $mensagem = 'Aluno matriculado com sucesso!';
                $tipoMensagem = 'success';
            } else {
                $mensagem = 'Erro ao matricular aluno.';
                $tipoMensagem = 'error';
            }
            break;
            
        case 'transferir_aluno':
            $resultado = $alunoModel->transferirTurma(
                $_POST['aluno_id'],
                $_POST['turma_antiga_id'],
                $_POST['turma_nova_id']
            );
            
            if ($resultado['success']) {
                $mensagem = 'Aluno transferido com sucesso!';
                $tipoMensagem = 'success';
            } else {
                $mensagem = $resultado['message'] ?? 'Erro ao transferir aluno.';
                $tipoMensagem = 'error';
            }
            break;
    }
}

// Buscar dados
$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar escolas (filtrar pela escola do gestor se necessário)
$sqlEscolas = "SELECT id, nome FROM escola WHERE ativo = 1 ORDER BY nome ASC";
$stmtEscolas = $conn->prepare($sqlEscolas);
$stmtEscolas->execute();
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Buscar turmas
$filtrosTurma = ['ativo' => 1];
if (!empty($_GET['escola_id'])) {
    $filtrosTurma['escola_id'] = $_GET['escola_id'];
}
if (!empty($_GET['ano_letivo'])) {
    $filtrosTurma['ano_letivo'] = $_GET['ano_letivo'];
}
$turmas = $turmaModel->listar($filtrosTurma);

// Buscar séries para os formulários
$sqlSeries = "SELECT id, nome, codigo FROM serie WHERE ativo = 1 ORDER BY ordem ASC";
$stmtSeries = $conn->prepare($sqlSeries);
$stmtSeries->execute();
$series = $stmtSeries->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições AJAX
if (!empty($_GET['acao']) && $_GET['acao'] === 'buscar_turma' && !empty($_GET['id'])) {
    header('Content-Type: application/json');
    
    $turmaId = $_GET['id'];
    $turma = $turmaModel->buscarPorId($turmaId);
    
    if ($turma) {
        $alunos = $turmaModel->buscarAlunos($turmaId);
        $professores = $turmaModel->buscarProfessores($turmaId);
        
        echo json_encode([
            'success' => true,
            'turma' => $turma,
            'alunos' => $alunos,
            'professores' => $professores
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Turma não encontrada'
        ]);
    }
    exit;
}

// Buscar alunos
$filtrosAluno = ['ativo' => 1];
if (!empty($_GET['escola_id'])) {
    $filtrosAluno['escola_id'] = $_GET['escola_id'];
}
$alunos = $alunoModel->listar($filtrosAluno);

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/GitHub/Gest-o-Escolar-');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Escolar - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'secondary-green': '#4A7C59',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h1 class="text-xl font-semibold text-gray-800">Gestão Escolar</h1>
                </div>
                <div class="text-sm text-gray-600">
                    <?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?>
                </div>
            </div>
        </div>
    </header>

    <div class="p-4 sm:p-6 lg:p-8">
        <!-- Mensagens -->
        <?php if ($mensagem): ?>
            <div class="mb-6 p-4 rounded-lg <?= $tipoMensagem === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <!-- Tabs de Navegação -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-8 overflow-x-auto">
                <button onclick="mostrarAba('turmas')" id="tab-turmas" class="tab-button py-4 px-1 border-b-2 border-primary-green font-medium text-sm text-primary-green">
                    Turmas
                </button>
                <button onclick="mostrarAba('matriculas')" id="tab-matriculas" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Matrículas
                </button>
                <button onclick="mostrarAba('professores')" id="tab-professores" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Professores
                </button>
                <button onclick="mostrarAba('acompanhamento')" id="tab-acompanhamento" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Acompanhamento
                </button>
                <button onclick="mostrarAba('validacao')" id="tab-validacao" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Validação
                </button>
            </nav>
        </div>

        <!-- Conteúdo das Abas -->
        
        <!-- ABA: TURMAS -->
        <div id="conteudo-turmas" class="aba-conteudo">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Gerenciamento de Turmas</h2>
                    <button onclick="abrirModalCriarTurma()" class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-secondary-green transition-colors">
                        + Nova Turma
                    </button>
                </div>

                <!-- Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                        <select id="filtro-escola" onchange="filtrarTurmas()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            <option value="">Todas as escolas</option>
                            <?php foreach ($escolas as $escola): ?>
                                <option value="<?= $escola['id'] ?>" <?= (!empty($_GET['escola_id']) && $_GET['escola_id'] == $escola['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($escola['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo</label>
                        <select id="filtro-ano" onchange="filtrarTurmas()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            <option value="">Todos os anos</option>
                            <?php for ($ano = date('Y'); $ano >= date('Y') - 5; $ano--): ?>
                                <option value="<?= $ano ?>" <?= (!empty($_GET['ano_letivo']) && $_GET['ano_letivo'] == $ano) ? 'selected' : '' ?>>
                                    <?= $ano ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <input type="text" id="busca-turma" placeholder="Buscar turma..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                    </div>
                </div>

                <!-- Lista de Turmas -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano Letivo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professores</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($turmas)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhuma turma encontrada</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($turmas as $turma): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars(($turma['serie'] ?? '') . ' ' . ($turma['letra'] ?? '') . ' - ' . ($turma['turno'] ?? '')) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($turma['escola_nome'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($turma['ano_letivo'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $turma['total_alunos'] ?? 0 ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $turma['total_professores'] ?? 0 ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="verDetalhesTurma(<?= $turma['id'] ?>)" class="text-primary-green hover:text-secondary-green mr-3">Ver</button>
                                            <button onclick="editarTurma(<?= $turma['id'] ?>)" class="text-blue-600 hover:text-blue-800 mr-3">Editar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA: MATRÍCULAS -->
        <div id="conteudo-matriculas" class="aba-conteudo hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Matrícula e Alocação de Alunos</h2>
                    <button onclick="abrirModalMatricularAluno()" class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-secondary-green transition-colors">
                        + Matricular Aluno
                    </button>
                </div>

                <!-- Lista de Alunos -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma Atual</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($alunos)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhum aluno encontrado</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($alunos as $aluno): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($aluno['nome'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($aluno['cpf'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                            // Buscar turma atual do aluno
                                            $sqlTurmaAluno = "SELECT CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                                                             FROM aluno_turma at
                                                             INNER JOIN turma t ON at.turma_id = t.id
                                                             WHERE at.aluno_id = :aluno_id AND at.fim IS NULL
                                                             LIMIT 1";
                                            $stmtTurmaAluno = $conn->prepare($sqlTurmaAluno);
                                            $stmtTurmaAluno->bindParam(':aluno_id', $aluno['id']);
                                            $stmtTurmaAluno->execute();
                                            $turmaAluno = $stmtTurmaAluno->fetch(PDO::FETCH_ASSOC);
                                            echo htmlspecialchars($turmaAluno['turma_nome'] ?? 'Sem turma');
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="matricularAluno(<?= $aluno['id'] ?>)" class="text-primary-green hover:text-secondary-green mr-3">Matricular</button>
                                            <button onclick="transferirAluno(<?= $aluno['id'] ?>)" class="text-blue-600 hover:text-blue-800">Transferir</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA: PROFESSORES -->
        <div id="conteudo-professores" class="aba-conteudo hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Atribuição de Professores</h2>
                <p class="text-gray-600">Funcionalidade em desenvolvimento...</p>
            </div>
        </div>

        <!-- ABA: ACOMPANHAMENTO -->
        <div id="conteudo-acompanhamento" class="aba-conteudo hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Acompanhamento Acadêmico</h2>
                <p class="text-gray-600">Funcionalidade em desenvolvimento...</p>
            </div>
        </div>

        <!-- ABA: VALIDAÇÃO -->
        <div id="conteudo-validacao" class="aba-conteudo hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Validação de Lançamentos</h2>
                <p class="text-gray-600">Funcionalidade em desenvolvimento...</p>
            </div>
        </div>
    </div>

    <!-- Modal Criar Turma -->
    <div id="modal-criar-turma" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900">Nova Turma</h3>
                <button onclick="fecharModalCriarTurma()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="criar_turma">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                    <select name="escola_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                        <option value="">Selecione...</option>
                        <?php foreach ($escolas as $escola): ?>
                            <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Série</label>
                    <input type="text" name="serie" placeholder="Ex: 1º Ano" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Letra *</label>
                    <input type="text" name="letra" required placeholder="Ex: A" maxlength="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Turno *</label>
                    <select name="turno" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                        <option value="MANHA">Manhã</option>
                        <option value="TARDE">Tarde</option>
                        <option value="NOITE">Noite</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo</label>
                    <input type="number" name="ano_letivo" value="<?= date('Y') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacidade</label>
                    <input type="number" name="capacidade" placeholder="Ex: 30" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sala</label>
                    <input type="text" name="sala" placeholder="Ex: 101" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="fecharModalCriarTurma()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-secondary-green">Criar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Detalhes da Turma -->
    <div id="modal-ver-turma" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h3 class="text-2xl font-bold text-gray-900">Detalhes da Turma</h3>
                <button onclick="fecharModalVerTurma()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto">
                <div id="conteudo-ver-turma">
                    <div class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green"></div>
                        <p class="mt-2 text-gray-600">Carregando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Turma -->
    <div id="modal-editar-turma" class="hidden fixed inset-0 bg-white overflow-y-auto h-full w-full z-50">
        <div class="w-full h-full flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white sticky top-0 z-10">
                <h3 class="text-2xl font-bold text-gray-900">Editar Turma</h3>
                <button onclick="fecharModalEditarTurma()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Content -->
            <div class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-4xl mx-auto">
                    <form method="POST" id="form-editar-turma" class="space-y-6">
                        <input type="hidden" name="acao" value="editar_turma">
                        <input type="hidden" name="turma_id" id="editar-turma-id">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                                <select name="escola_id" id="editar-escola-id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Série</label>
                                <select name="serie_id" id="editar-serie-id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($series as $serie): ?>
                                        <option value="<?= $serie['id'] ?>"><?= htmlspecialchars($serie['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Série (Texto)</label>
                                <input type="text" name="serie" id="editar-serie" placeholder="Ex: 1º Ano" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Letra *</label>
                                <input type="text" name="letra" id="editar-letra" required placeholder="Ex: A" maxlength="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Turno *</label>
                                <select name="turno" id="editar-turno" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                    <option value="MANHA">Manhã</option>
                                    <option value="TARDE">Tarde</option>
                                    <option value="NOITE">Noite</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo</label>
                                <input type="number" name="ano_letivo" id="editar-ano-letivo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Capacidade</label>
                                <input type="number" name="capacidade" id="editar-capacidade" placeholder="Ex: 30" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sala</label>
                                <input type="text" name="sala" id="editar-sala" placeholder="Ex: 101" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                            </div>
                        </div>
                        
                        <div class="pt-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="ativo" id="editar-ativo" value="1" checked class="mr-2 w-5 h-5">
                                <span class="text-sm font-medium text-gray-700">Turma Ativa</span>
                            </label>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <button type="button" onclick="fecharModalEditarTurma()" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">Cancelar</button>
                            <button type="submit" class="px-6 py-3 bg-primary-green text-white rounded-lg hover:bg-secondary-green font-medium transition-colors">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Controle de abas
        function mostrarAba(aba) {
            // Esconder todos os conteúdos
            document.querySelectorAll('.aba-conteudo').forEach(el => el.classList.add('hidden'));
            
            // Remover estilo ativo de todas as tabs
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-primary-green', 'text-primary-green');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar conteúdo da aba selecionada
            document.getElementById('conteudo-' + aba).classList.remove('hidden');
            
            // Ativar tab
            const tab = document.getElementById('tab-' + aba);
            tab.classList.remove('border-transparent', 'text-gray-500');
            tab.classList.add('border-primary-green', 'text-primary-green');
        }

        // Modal Criar Turma
        function abrirModalCriarTurma() {
            document.getElementById('modal-criar-turma').classList.remove('hidden');
        }

        function fecharModalCriarTurma() {
            document.getElementById('modal-criar-turma').classList.add('hidden');
        }

        // Filtros
        function filtrarTurmas() {
            const escolaId = document.getElementById('filtro-escola').value;
            const anoLetivo = document.getElementById('filtro-ano').value;
            
            let url = 'gestao_escolar.php?';
            if (escolaId) url += 'escola_id=' + escolaId + '&';
            if (anoLetivo) url += 'ano_letivo=' + anoLetivo;
            
            window.location.href = url;
        }

        // Função para ver detalhes da turma
        function verDetalhesTurma(id) {
            document.getElementById('modal-ver-turma').classList.remove('hidden');
            document.getElementById('conteudo-ver-turma').innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green"></div>
                    <p class="mt-2 text-gray-600">Carregando...</p>
                </div>
            `;
            
            // Buscar dados da turma via AJAX
            fetch('gestao_escolar.php?acao=buscar_turma&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const turma = data.turma;
                        const alunos = data.alunos || [];
                        const professores = data.professores || [];
                        
                        let html = `
                            <div class="space-y-8 max-w-6xl mx-auto">
                                <!-- Informações Básicas -->
                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Informações Básicas</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600">Turma:</span>
                                            <p class="font-medium">${turma.serie || ''} ${turma.letra || ''} - ${turma.turno || ''}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Escola:</span>
                                            <p class="font-medium">${turma.escola_nome || ''}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Ano Letivo:</span>
                                            <p class="font-medium">${turma.ano_letivo || ''}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Capacidade:</span>
                                            <p class="font-medium">${turma.capacidade || 'Não informado'}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Sala:</span>
                                            <p class="font-medium">${turma.sala || 'Não informado'}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Status:</span>
                                            <p class="font-medium"><span class="px-2 py-1 rounded ${turma.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${turma.ativo ? 'Ativa' : 'Inativa'}</span></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Alunos -->
                                <div class="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Alunos (${alunos.length})</h4>
                                    ${alunos.length > 0 ? `
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    ${alunos.map(aluno => `
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${aluno.nome || ''}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${aluno.cpf || ''}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${aluno.matricula || ''}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${aluno.status || ''}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    ` : '<p class="text-gray-500 text-sm py-4">Nenhum aluno matriculado nesta turma.</p>'}
                                </div>
                                
                                <!-- Professores -->
                                <div class="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Professores (${professores.length})</h4>
                                    ${professores.length > 0 ? `
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regime</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    ${professores.map(prof => `
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${prof.nome || ''}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${prof.disciplina_nome || 'Não informado'}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${prof.regime || ''}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    ` : '<p class="text-gray-500 text-sm py-4">Nenhum professor atribuído a esta turma.</p>'}
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('conteudo-ver-turma').innerHTML = html;
                    } else {
                        document.getElementById('conteudo-ver-turma').innerHTML = `
                            <div class="text-center py-8">
                                <p class="text-red-600">Erro ao carregar dados da turma.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('conteudo-ver-turma').innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-red-600">Erro ao carregar dados: ${error.message}</p>
                        </div>
                    `;
                });
        }

        function fecharModalVerTurma() {
            document.getElementById('modal-ver-turma').classList.add('hidden');
        }

        // Função para editar turma
        function editarTurma(id) {
            // Buscar dados da turma
            fetch('gestao_escolar.php?acao=buscar_turma&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.turma) {
                        const turma = data.turma;
                        
                        // Preencher formulário
                        document.getElementById('editar-turma-id').value = turma.id;
                        document.getElementById('editar-escola-id').value = turma.escola_id || '';
                        document.getElementById('editar-serie-id').value = turma.serie_id || '';
                        document.getElementById('editar-serie').value = turma.serie || '';
                        document.getElementById('editar-letra').value = turma.letra || '';
                        document.getElementById('editar-turno').value = turma.turno || 'MANHA';
                        document.getElementById('editar-ano-letivo').value = turma.ano_letivo || '';
                        document.getElementById('editar-capacidade').value = turma.capacidade || '';
                        document.getElementById('editar-sala').value = turma.sala || '';
                        document.getElementById('editar-ativo').checked = turma.ativo == 1;
                        
                        // Abrir modal
                        document.getElementById('modal-editar-turma').classList.remove('hidden');
                    } else {
                        alert('Erro ao carregar dados da turma.');
                    }
                })
                .catch(error => {
                    alert('Erro ao carregar dados: ' + error.message);
                });
        }

        function fecharModalEditarTurma() {
            document.getElementById('modal-editar-turma').classList.add('hidden');
        }

        function abrirModalMatricularAluno() {
            alert('Modal de matrícula - Em desenvolvimento');
        }

        function matricularAluno(id) {
            alert('Matricular aluno ' + id + ' - Em desenvolvimento');
        }

        function transferirAluno(id) {
            alert('Transferir aluno ' + id + ' - Em desenvolvimento');
        }
    </script>
</body>
</html>

