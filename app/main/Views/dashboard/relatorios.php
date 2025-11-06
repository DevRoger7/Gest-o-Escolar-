<?php
// Iniciar sessão
session_start();

// Verificar se o usuário está logado e tem permissão para acessar esta página
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADM') {
    header('Location: ../auth/login.php');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// ==================== FUNÇÕES DE RELATÓRIOS ====================

// Relatório de Notas por Turma
function relatorioNotasPorTurma($turmaId = null, $disciplinaId = null, $dataInicio = null, $dataFim = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT n.id, n.nota, n.lancado_em, n.comentario,
                   a.titulo as avaliacao_titulo, a.data as avaliacao_data, a.tipo as avaliacao_tipo,
                   CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome,
                   d.nome as disciplina_nome,
                   p.nome as aluno_nome,
                   al.matricula
            FROM nota n
            INNER JOIN avaliacao a ON n.avaliacao_id = a.id
            INNER JOIN aluno al ON n.aluno_id = al.id
            INNER JOIN pessoa p ON al.pessoa_id = p.id
            INNER JOIN turma t ON a.turma_id = t.id
            INNER JOIN turma_professor tp ON t.id = tp.turma_id
            INNER JOIN disciplina d ON tp.disciplina_id = d.id
            WHERE 1=1";
    
    if ($turmaId) {
        $sql .= " AND t.id = :turma_id";
    }
    
    if ($disciplinaId) {
        $sql .= " AND d.id = :disciplina_id";
    }
    
    if ($dataInicio) {
        $sql .= " AND a.data >= :data_inicio";
    }
    
    if ($dataFim) {
        $sql .= " AND a.data <= :data_fim";
    }
    
    $sql .= " ORDER BY a.data DESC, p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if ($turmaId) {
        $stmt->bindParam(':turma_id', $turmaId);
    }
    
    if ($disciplinaId) {
        $stmt->bindParam(':disciplina_id', $disciplinaId);
    }
    
    if ($dataInicio) {
        $stmt->bindParam(':data_inicio', $dataInicio);
    }
    
    if ($dataFim) {
        $stmt->bindParam(':data_fim', $dataFim);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Relatório de Frequência
function relatorioFrequencia($turmaId = null, $dataInicio = null, $dataFim = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT al.id as aluno_id,
                   CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome,
                   p.nome as aluno_nome,
                   al.matricula,
                   COUNT(CASE WHEN f.presenca = 1 THEN 1 END) as total_presencas,
                   COUNT(CASE WHEN f.presenca = 0 THEN 1 END) as total_faltas,
                   COUNT(f.id) as total_registros
            FROM aluno al
            INNER JOIN pessoa p ON al.pessoa_id = p.id
            INNER JOIN aluno_turma at ON al.id = at.aluno_id
            INNER JOIN turma t ON at.turma_id = t.id
            LEFT JOIN frequencia f ON f.aluno_id = al.id AND f.turma_id = t.id
            WHERE 1=1";
    
    if ($turmaId) {
        $sql .= " AND t.id = :turma_id";
    }
    
    if ($dataInicio) {
        $sql .= " AND (f.data >= :data_inicio OR f.data IS NULL)";
    }
    
    if ($dataFim) {
        $sql .= " AND (f.data <= :data_fim OR f.data IS NULL)";
    }
    
    $sql .= " GROUP BY al.id, t.id
              ORDER BY p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if ($turmaId) {
        $stmt->bindParam(':turma_id', $turmaId);
    }
    
    if ($dataInicio) {
        $stmt->bindParam(':data_inicio', $dataInicio);
    }
    
    if ($dataFim) {
        $stmt->bindParam(':data_fim', $dataFim);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Relatório de Desempenho dos Alunos
function relatorioDesempenhoAlunos($turmaId = null, $disciplinaId = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT al.id, al.matricula, p.nome as aluno_nome,
                   CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome,
                   d.nome as disciplina_nome,
                   AVG(n.nota) as media_geral,
                   COUNT(n.id) as total_avaliacoes,
                   MIN(n.nota) as nota_minima,
                   MAX(n.nota) as nota_maxima
            FROM aluno al
            INNER JOIN pessoa p ON al.pessoa_id = p.id
            INNER JOIN aluno_turma at ON al.id = at.aluno_id
            INNER JOIN turma t ON at.turma_id = t.id
            INNER JOIN turma_professor tp ON t.id = tp.turma_id
            INNER JOIN disciplina d ON tp.disciplina_id = d.id
            LEFT JOIN avaliacao a ON a.turma_id = t.id
            LEFT JOIN nota n ON n.aluno_id = al.id AND n.avaliacao_id = a.id
            WHERE 1=1";
    
    if ($turmaId) {
        $sql .= " AND t.id = :turma_id";
    }
    
    if ($disciplinaId) {
        $sql .= " AND d.id = :disciplina_id";
    }
    
    $sql .= " GROUP BY al.id, d.id, t.id
              HAVING COUNT(n.id) > 0
              ORDER BY media_geral DESC, p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if ($turmaId) {
        $stmt->bindParam(':turma_id', $turmaId);
    }
    
    if ($disciplinaId) {
        $stmt->bindParam(':disciplina_id', $disciplinaId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Relatório de Matrículas
function relatorioMatriculas($escolaId = null, $anoLetivo = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT al.id, al.matricula, al.data_matricula, al.ativo,
                   p.nome as aluno_nome, p.data_nascimento,
                   CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome,
                   e.nome as escola_nome
            FROM aluno al
            INNER JOIN pessoa p ON al.pessoa_id = p.id
            LEFT JOIN aluno_turma at ON al.id = at.aluno_id
            LEFT JOIN turma t ON at.turma_id = t.id
            LEFT JOIN escola e ON t.escola_id = e.id
            WHERE 1=1";
    
    if ($escolaId) {
        $sql .= " AND e.id = :escola_id";
    }
    
    if ($anoLetivo) {
        $sql .= " AND t.ano_letivo = :ano_letivo";
    }
    
    $sql .= " ORDER BY al.data_matricula DESC, p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if ($escolaId) {
        $stmt->bindParam(':escola_id', $escolaId);
    }
    
    if ($anoLetivo) {
        $stmt->bindParam(':ano_letivo', $anoLetivo);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Relatório de Custos de Alimentação
function relatorioCustosAlimentacao($dataInicio = null, $dataFim = null, $escolaId = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT pc.id, pc.mes, pc.status, pc.data_criacao, pc.data_envio, pc.data_aprovacao,
                   e.nome as escola_nome,
                   SUM(pi.quantidade_solicitada * pr.preco_unitario) as valor_total,
                   COUNT(pi.id) as total_itens
            FROM pedido_cesta pc
            INNER JOIN escola e ON pc.escola_id = e.id
            LEFT JOIN pedido_item pi ON pc.id = pi.pedido_id
            LEFT JOIN produto pr ON pi.produto_id = pr.id
            WHERE 1=1";
    
    if ($dataInicio) {
        $sql .= " AND pc.data_criacao >= :data_inicio";
    }
    
    if ($dataFim) {
        $sql .= " AND pc.data_criacao <= :data_fim";
    }
    
    if ($escolaId) {
        $sql .= " AND e.id = :escola_id";
    }
    
    $sql .= " GROUP BY pc.id
              ORDER BY pc.data_criacao DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($dataInicio) {
        $stmt->bindParam(':data_inicio', $dataInicio);
    }
    
    if ($dataFim) {
        $stmt->bindParam(':data_fim', $dataFim);
    }
    
    if ($escolaId) {
        $stmt->bindParam(':escola_id', $escolaId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar turmas
function listarTurmas() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT t.id, CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as nome, t.ano_letivo
            FROM turma t
            ORDER BY t.serie ASC, t.letra ASC";
    
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar disciplinas
function listarDisciplinas() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT id, nome FROM disciplina ORDER BY nome ASC";
    
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar escolas
function listarEscolas() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT id, nome FROM escola ORDER BY nome ASC";
    
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== PROCESSAMENTO ====================

$tipoRelatorio = $_GET['tipo'] ?? 'pedagogico';
$relatorioSelecionado = $_GET['relatorio'] ?? 'notas';
$dados = [];

// Filtros
$turmaId = $_GET['turma_id'] ?? null;
$disciplinaId = $_GET['disciplina_id'] ?? null;
$escolaId = $_GET['escola_id'] ?? null;
$dataInicio = $_GET['data_inicio'] ?? null;
$dataFim = $_GET['data_fim'] ?? null;
$anoLetivo = $_GET['ano_letivo'] ?? null;

// Buscar dados do relatório
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['gerar'])) {
    switch ($relatorioSelecionado) {
        case 'notas':
            $dados = relatorioNotasPorTurma($turmaId, $disciplinaId, $dataInicio, $dataFim);
            break;
        case 'frequencia':
            $dados = relatorioFrequencia($turmaId, $dataInicio, $dataFim);
            break;
        case 'desempenho':
            $dados = relatorioDesempenhoAlunos($turmaId, $disciplinaId);
            break;
        case 'matriculas':
            $dados = relatorioMatriculas($escolaId, $anoLetivo);
            break;
        case 'custos':
            $dados = relatorioCustosAlimentacao($dataInicio, $dataFim, $escolaId);
            break;
    }
}

// Listar opções para filtros
$turmas = listarTurmas();
$disciplinas = listarDisciplinas();
$escolas = listarEscolas();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#22c55e',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
                        <p class="text-sm text-gray-500 mt-1">Relatórios Financeiros e Pedagógicos</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Abas -->
            <div class="bg-white rounded-lg shadow-sm mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <a href="?tipo=pedagogico&relatorio=<?= $relatorioSelecionado ?>" 
                           class="px-6 py-4 text-sm font-medium <?= $tipoRelatorio === 'pedagogico' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Relatórios Pedagógicos
                        </a>
                        <a href="?tipo=financeiro&relatorio=<?= $relatorioSelecionado ?>" 
                           class="px-6 py-4 text-sm font-medium <?= $tipoRelatorio === 'financeiro' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Relatórios Financeiros
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Seleção de Relatório -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Selecione o Relatório</h2>
                
                <?php if ($tipoRelatorio === 'pedagogico'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="?tipo=pedagogico&relatorio=notas" 
                       class="p-4 border-2 rounded-lg <?= $relatorioSelecionado === 'notas' ? 'border-primary-green bg-green-50' : 'border-gray-200 hover:border-gray-300' ?>">
                        <h3 class="font-semibold text-gray-900">Notas por Turma</h3>
                        <p class="text-sm text-gray-500 mt-1">Relatório de notas lançadas</p>
                    </a>
                    
                    <a href="?tipo=pedagogico&relatorio=frequencia" 
                       class="p-4 border-2 rounded-lg <?= $relatorioSelecionado === 'frequencia' ? 'border-primary-green bg-green-50' : 'border-gray-200 hover:border-gray-300' ?>">
                        <h3 class="font-semibold text-gray-900">Frequência</h3>
                        <p class="text-sm text-gray-500 mt-1">Relatório de presenças e faltas</p>
                    </a>
                    
                    <a href="?tipo=pedagogico&relatorio=desempenho" 
                       class="p-4 border-2 rounded-lg <?= $relatorioSelecionado === 'desempenho' ? 'border-primary-green bg-green-50' : 'border-gray-200 hover:border-gray-300' ?>">
                        <h3 class="font-semibold text-gray-900">Desempenho</h3>
                        <p class="text-sm text-gray-500 mt-1">Desempenho dos alunos</p>
                    </a>
                    
                    <a href="?tipo=pedagogico&relatorio=matriculas" 
                       class="p-4 border-2 rounded-lg <?= $relatorioSelecionado === 'matriculas' ? 'border-primary-green bg-green-50' : 'border-gray-200 hover:border-gray-300' ?>">
                        <h3 class="font-semibold text-gray-900">Matrículas</h3>
                        <p class="text-sm text-gray-500 mt-1">Relatório de matrículas</p>
                    </a>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="?tipo=financeiro&relatorio=custos" 
                       class="p-4 border-2 rounded-lg <?= $relatorioSelecionado === 'custos' ? 'border-primary-green bg-green-50' : 'border-gray-200 hover:border-gray-300' ?>">
                        <h3 class="font-semibold text-gray-900">Custos de Alimentação</h3>
                        <p class="text-sm text-gray-500 mt-1">Relatório de custos e pedidos</p>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h2>
                
                <form method="GET" action="" class="space-y-4">
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoRelatorio) ?>">
                    <input type="hidden" name="relatorio" value="<?= htmlspecialchars($relatorioSelecionado) ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <?php if ($tipoRelatorio === 'pedagogico'): ?>
                        <div>
                            <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                            <select id="turma_id" name="turma_id" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                                <option value="">Todas</option>
                                <?php foreach ($turmas as $turma): ?>
                                <option value="<?= $turma['id'] ?>" <?= $turmaId == $turma['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($turma['nome']) ?> - <?= htmlspecialchars($turma['ano_letivo'] ?? '') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="disciplina_id" class="block text-sm font-medium text-gray-700 mb-2">Disciplina</label>
                            <select id="disciplina_id" name="disciplina_id" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                                <option value="">Todas</option>
                                <?php foreach ($disciplinas as $disciplina): ?>
                                <option value="<?= $disciplina['id'] ?>" <?= $disciplinaId == $disciplina['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($disciplina['nome']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($tipoRelatorio === 'financeiro' || $relatorioSelecionado === 'matriculas'): ?>
                        <div>
                            <label for="escola_id" class="block text-sm font-medium text-gray-700 mb-2">Escola</label>
                            <select id="escola_id" name="escola_id" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                                <option value="">Todas</option>
                                <?php foreach ($escolas as $escola): ?>
                                <option value="<?= $escola['id'] ?>" <?= $escolaId == $escola['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($escola['nome']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                            <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                        </div>
                        
                        <div>
                            <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                            <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                        </div>
                        
                        <?php if ($relatorioSelecionado === 'matriculas'): ?>
                        <div>
                            <label for="ano_letivo" class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo</label>
                            <input type="text" id="ano_letivo" name="ano_letivo" value="<?= htmlspecialchars($anoLetivo) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green"
                                   placeholder="Ex: 2024">
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <a href="?tipo=<?= htmlspecialchars($tipoRelatorio) ?>&relatorio=<?= htmlspecialchars($relatorioSelecionado) ?>" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Limpar
                        </a>
                        <button type="submit" name="gerar" value="1" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                            Gerar Relatório
                        </button>
                    </div>
                </form>
            </div>

            <!-- Resultados do Relatório -->
            <?php if (!empty($dados)): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Resultados</h2>
                    <button onclick="window.print()" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                        Imprimir
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php if ($relatorioSelecionado === 'notas'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avaliação</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nota</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <?php elseif ($relatorioSelecionado === 'frequencia'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Presenças</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faltas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% Frequência</th>
                                <?php elseif ($relatorioSelecionado === 'desempenho'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Média</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avaliações</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min/Max</th>
                                <?php elseif ($relatorioSelecionado === 'matriculas'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Matrícula</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <?php elseif ($relatorioSelecionado === 'custos'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mês</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Itens</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($dados as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <?php if ($relatorioSelecionado === 'notas'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['aluno_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['turma_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['disciplina_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['avaliacao_titulo']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?= number_format($item['nota'], 1) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y', strtotime($item['avaliacao_data'])) ?></td>
                                <?php elseif ($relatorioSelecionado === 'frequencia'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['aluno_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['turma_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600"><?= $item['total_presencas'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600"><?= $item['total_faltas'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $item['total_registros'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    <?= $item['total_registros'] > 0 ? number_format(($item['total_presencas'] / $item['total_registros']) * 100, 1) : 0 ?>%
                                </td>
                                <?php elseif ($relatorioSelecionado === 'desempenho'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['aluno_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['turma_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['disciplina_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?= number_format($item['media_geral'] ?? 0, 1) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $item['total_avaliacoes'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= number_format($item['nota_minima'] ?? 0, 1) ?> / <?= number_format($item['nota_maxima'] ?? 0, 1) ?>
                                </td>
                                <?php elseif ($relatorioSelecionado === 'matriculas'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['aluno_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['matricula'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['turma_nome'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['escola_nome'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $item['data_matricula'] ? date('d/m/Y', strtotime($item['data_matricula'])) : '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $item['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $item['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <?php elseif ($relatorioSelecionado === 'custos'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['escola_nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $item['mes'] ? date('m/Y', mktime(0, 0, 0, $item['mes'], 1, date('Y'))) : '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= 
                                        $item['status'] === 'APROVADO' ? 'bg-green-100 text-green-800' : 
                                        ($item['status'] === 'REJEITADO' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')
                                    ?>">
                                        <?= htmlspecialchars($item['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $item['total_itens'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    R$ <?= number_format($item['valor_total'] ?? 0, 2, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y', strtotime($item['data_criacao'])) ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>

