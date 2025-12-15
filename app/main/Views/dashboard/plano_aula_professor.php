<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../Models/academico/PlanoAulaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'professor') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$planoAulaModel = new PlanoAulaModel();

// Buscar professor_id
$professorId = null;
$pessoaId = $_SESSION['pessoa_id'] ?? null;
if ($pessoaId) {
    $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
    $stmtProfessor = $conn->prepare($sqlProfessor);
    $stmtProfessor->bindParam(':pessoa_id', $pessoaId);
    $stmtProfessor->execute();
    $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
    $professorId = $professor['id'] ?? null;
}

if (!$professorId) {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    if ($usuarioId) {
        $sqlPessoa = "SELECT pessoa_id FROM usuario WHERE id = :usuario_id LIMIT 1";
        $stmtPessoa = $conn->prepare($sqlPessoa);
        $stmtPessoa->bindParam(':usuario_id', $usuarioId);
        $stmtPessoa->execute();
        $usuario = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
        $pessoaId = $usuario['pessoa_id'] ?? null;
        
        if ($pessoaId) {
            $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
            $stmtProfessor = $conn->prepare($sqlProfessor);
            $stmtProfessor->bindParam(':pessoa_id', $pessoaId);
            $stmtProfessor->execute();
            $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
            $professorId = $professor['id'] ?? null;
        }
    }
}

if (!$professorId) {
    header('Location: dashboard.php?erro=professor_nao_encontrado');
    exit;
}

// Buscar turmas e disciplinas do professor
$turmasProfessor = [];
$escolas = [];
if ($professorId) {
    // Primeiro, buscar escolas através da lotação do professor
    $sqlLotacao = "SELECT DISTINCT 
                    e.id as escola_id,
                    e.nome as escola_nome
                  FROM professor_lotacao pl
                  INNER JOIN escola e ON pl.escola_id = e.id
                  WHERE pl.professor_id = :professor_id 
                  AND (pl.fim IS NULL OR pl.fim = '' OR pl.fim = '0000-00-00')
                  AND e.ativo = 1";
    
    // Filtrar por escola selecionada se houver
    $escolaIdSelecionada = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
    if ($escolaIdSelecionada) {
        $sqlLotacao .= " AND e.id = :escola_id";
    }
    
    $stmtLotacao = $conn->prepare($sqlLotacao);
    $stmtLotacao->bindParam(':professor_id', $professorId);
    if ($escolaIdSelecionada) {
        $stmtLotacao->bindParam(':escola_id', $escolaIdSelecionada, PDO::PARAM_INT);
    }
    $stmtLotacao->execute();
    $escolasLotacao = $stmtLotacao->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar escolas da lotação ao array
    foreach ($escolasLotacao as $escolaLot) {
        $escolaId = $escolaLot['escola_id'];
        if (!isset($escolas[$escolaId])) {
            $escolas[$escolaId] = [
                'id' => $escolaId,
                'nome' => $escolaLot['escola_nome'],
                'turmas' => []
            ];
        }
    }
    
    // Agora buscar turmas e disciplinas do professor
    $sqlTurmas = "SELECT DISTINCT 
                    t.id as turma_id,
                    CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                    d.id as disciplina_id,
                    d.nome as disciplina_nome,
                    e.id as escola_id,
                    e.nome as escola_nome
                  FROM turma_professor tp
                  INNER JOIN turma t ON tp.turma_id = t.id
                  INNER JOIN disciplina d ON tp.disciplina_id = d.id
                  INNER JOIN escola e ON t.escola_id = e.id
                  WHERE tp.professor_id = :professor_id AND tp.fim IS NULL AND t.ativo = 1";
    
    if ($escolaIdSelecionada) {
        $sqlTurmas .= " AND t.escola_id = :escola_id";
    }
    
    $sqlTurmas .= " ORDER BY e.nome, t.serie, t.letra, d.nome";
    $stmtTurmas = $conn->prepare($sqlTurmas);
    $stmtTurmas->bindParam(':professor_id', $professorId);
    if ($escolaIdSelecionada) {
        $stmtTurmas->bindParam(':escola_id', $escolaIdSelecionada, PDO::PARAM_INT);
    }
    $stmtTurmas->execute();
    $turmasProfessor = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar por escola
    foreach ($turmasProfessor as $turma) {
        $escolaId = $turma['escola_id'];
        if (!isset($escolas[$escolaId])) {
            $escolas[$escolaId] = [
                'id' => $escolaId,
                'nome' => $turma['escola_nome'],
                'turmas' => []
            ];
        }
        if (!isset($escolas[$escolaId]['turmas'][$turma['turma_id']])) {
            $escolas[$escolaId]['turmas'][$turma['turma_id']] = [
                'id' => $turma['turma_id'],
                'nome' => $turma['turma_nome'],
                'disciplinas' => []
            ];
        }
        $escolas[$escolaId]['turmas'][$turma['turma_id']]['disciplinas'][] = [
            'id' => $turma['disciplina_id'],
            'nome' => $turma['disciplina_nome']
        ];
    }
}

// Verificar se o professor atua em apenas uma escola
$escolasArray = array_values($escolas);
$totalEscolas = count($escolasArray);
$escolaUnica = null;
if ($totalEscolas === 1) {
    $escolaUnica = $escolasArray[0];
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'criar_plano') {
        $dados = json_decode($_POST['dados'], true);
        
        if (empty($dados['turmas']) || empty($dados['data_aula'])) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
            exit;
        }
        
        try {
            $conn->beginTransaction();
            $planosCriados = [];
            
            foreach ($dados['turmas'] as $turmaData) {
                $turmaId = $turmaData['turma_id'];
                
                // Buscar todas as disciplinas do professor nesta turma
                $sqlDisciplinas = "SELECT DISTINCT d.id as disciplina_id, d.nome as disciplina_nome
                                  FROM turma_professor tp
                                  INNER JOIN disciplina d ON tp.disciplina_id = d.id
                                  WHERE tp.turma_id = :turma_id
                                  AND tp.professor_id = :professor_id
                                  AND (tp.fim IS NULL OR tp.fim = '' OR tp.fim = '0000-00-00')
                                  AND d.ativo = 1";
                
                $stmtDisciplinas = $conn->prepare($sqlDisciplinas);
                $stmtDisciplinas->bindParam(':turma_id', $turmaId, PDO::PARAM_INT);
                $stmtDisciplinas->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
                $stmtDisciplinas->execute();
                $disciplinas = $stmtDisciplinas->fetchAll(PDO::FETCH_ASSOC);
                
                // Se não encontrar disciplinas, criar plano sem disciplina
                if (empty($disciplinas)) {
                    $dadosPlano = [
                        'turma_id' => $turmaId,
                        'disciplina_id' => null,
                        'professor_id' => $professorId,
                        'titulo' => $dados['titulo'] ?? 'Plano de Aula',
                        'conteudo' => $dados['conteudo'] ?? null,
                        'objetivos' => $dados['objetivos'] ?? null,
                        'metodologia' => $dados['metodologia'] ?? null,
                        'recursos' => $dados['recursos'] ?? null,
                        'avaliacao' => $dados['avaliacao'] ?? null,
                        'data_aula' => $dados['data_aula'],
                        'bimestre' => $dados['bimestre'] ?? null,
                        'observacoes' => $dados['observacoes'] ?? null
                    ];
                    
                    $resultado = $planoAulaModel->criar($dadosPlano);
                    if ($resultado['success']) {
                        $planosCriados[] = $resultado['id'];
                    }
                } else {
                    // Criar um plano para cada disciplina do professor nesta turma
                    foreach ($disciplinas as $disciplina) {
                        $dadosPlano = [
                            'turma_id' => $turmaId,
                            'disciplina_id' => $disciplina['disciplina_id'],
                            'professor_id' => $professorId,
                            'titulo' => $dados['titulo'] ?? 'Plano de Aula',
                            'conteudo' => $dados['conteudo'] ?? null,
                            'objetivos' => $dados['objetivos'] ?? null,
                            'metodologia' => $dados['metodologia'] ?? null,
                            'recursos' => $dados['recursos'] ?? null,
                            'avaliacao' => $dados['avaliacao'] ?? null,
                            'data_aula' => $dados['data_aula'],
                            'bimestre' => $dados['bimestre'] ?? null,
                            'observacoes' => $dados['observacoes'] ?? null
                        ];
                        
                        $resultado = $planoAulaModel->criar($dadosPlano);
                        if ($resultado['success']) {
                            $planosCriados[] = $resultado['id'];
                        }
                    }
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Plano(s) de aula criado(s) com sucesso!', 'ids' => $planosCriados]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erro ao criar plano: ' . $e->getMessage()]);
        }
        exit;
    }
    
}

// Processar requisições GET AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    header('Content-Type: application/json');
    
    if ($_GET['acao'] === 'buscar_turmas_escola') {
        $escolaId = $_GET['escola_id'] ?? null;
        
        if (empty($escolaId)) {
            echo json_encode(['success' => false, 'message' => 'ID da escola não fornecido']);
            exit;
        }
        
        try {
            // Buscar apenas as turmas únicas que o professor está atribuído na escola
            // Usar a mesma lógica do código original (linha 110)
            $sqlTurmas = "SELECT DISTINCT 
                            t.id as turma_id,
                            CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome
                          FROM turma_professor tp
                          INNER JOIN turma t ON tp.turma_id = t.id 
                          WHERE t.escola_id = :escola_id 
                          AND tp.professor_id = :professor_id
                          AND tp.fim IS NULL
                          AND t.ativo = 1
                          ORDER BY t.serie, t.letra";
            
            $stmtTurmas = $conn->prepare($sqlTurmas);
            $stmtTurmas->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmtTurmas->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            $stmtTurmas->execute();
            $turmas = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
            
            // Log para debug
            error_log("Busca de turmas - Escola ID: $escolaId, Professor ID: $professorId, Turmas encontradas: " . count($turmas));
            if (count($turmas) > 0) {
                error_log("Primeira turma encontrada: " . json_encode($turmas[0]));
            }
            
            echo json_encode(['success' => true, 'turmas' => $turmas]);
        } catch (Exception $e) {
            error_log("Erro ao buscar turmas: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar turmas: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_disciplinas_escola') {
        $escolaId = $_GET['escola_id'] ?? null;
        
        if (empty($escolaId)) {
            echo json_encode(['success' => false, 'message' => 'ID da escola não fornecido']);
            exit;
        }
        
        try {
            // Buscar todas as disciplinas que o professor leciona na escola
            $sqlDisciplinas = "SELECT DISTINCT 
                                d.id as disciplina_id,
                                d.nome as disciplina_nome
                              FROM disciplina d
                              INNER JOIN turma_professor tp ON tp.disciplina_id = d.id
                              INNER JOIN turma t ON tp.turma_id = t.id
                              WHERE t.escola_id = :escola_id 
                              AND tp.professor_id = :professor_id
                              AND (tp.fim IS NULL OR tp.fim = '' OR tp.fim = '0000-00-00')
                              AND d.ativo = 1
                              AND t.ativo = 1
                              ORDER BY d.nome";
            
            $stmtDisciplinas = $conn->prepare($sqlDisciplinas);
            $stmtDisciplinas->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmtDisciplinas->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            $stmtDisciplinas->execute();
            $disciplinas = $stmtDisciplinas->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'disciplinas' => $disciplinas]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar disciplinas: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'listar_planos') {
        $filtros = ['professor_id' => $professorId];
        if (!empty($_GET['turma_id'])) $filtros['turma_id'] = $_GET['turma_id'];
        if (!empty($_GET['data_aula'])) $filtros['data_aula'] = $_GET['data_aula'];
        if (!empty($_GET['escola_id'])) {
            $filtros['escola_id'] = $_GET['escola_id'];
        } else {
            // Se não foi especificado, usar a escola selecionada na sessão
            $escolaIdSelecionada = $_SESSION['escola_selecionada_id'] ?? $_SESSION['escola_id'] ?? null;
            if ($escolaIdSelecionada) {
                $filtros['escola_id'] = $escolaIdSelecionada;
            }
        }
        if (!empty($_GET['mes'])) $filtros['mes'] = $_GET['mes'];
        
        // Paginação
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $itensPorPagina = isset($_GET['itens_por_pagina']) ? (int)$_GET['itens_por_pagina'] : 100;
        $offset = ($pagina - 1) * $itensPorPagina;
        
        // Contar total de registros
        $totalPlanos = count($planoAulaModel->listar($filtros));
        
        // Aplicar paginação
        $filtros['limit'] = $itensPorPagina;
        $filtros['offset'] = $offset;
        
        $planos = $planoAulaModel->listar($filtros);
        $totalPaginas = ceil($totalPlanos / $itensPorPagina);
        
        echo json_encode([
            'success' => true, 
            'planos' => $planos,
            'pagina_atual' => $pagina,
            'total_paginas' => $totalPaginas,
            'total_registros' => $totalPlanos
        ]);
        exit;
    }
}

// Buscar planos recentes
$planosRecentes = [];
if ($professorId) {
    $planosRecentes = $planoAulaModel->listar(['professor_id' => $professorId]);
    $planosRecentes = array_slice($planosRecentes, 0, 10);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plano de Aula - SIGAE</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
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
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        .tab-active {
            border-bottom: 3px solid #2D5A27;
            color: #2D5A27;
            font-weight: 600;
        }
        .tab-inactive {
            border-bottom: 2px solid transparent;
            color: #6B7280;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_professor.php'; ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Plano de Aula</h1>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Título e Botões Superiores -->
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900">Pesquisar :: Planejamento</h2>
                    <div class="flex gap-3">
                        <button onclick="abrirModalNovoPlano()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Adicionar</span>
                        </button>
                        <button onclick="toggleFiltros()" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            <span>Filtrar</span>
                        </button>
                    </div>
                </div>
                
                <!-- Seção de Filtros -->
                <div id="secao-filtros" class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                            <input type="date" id="filtro-data" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mês</label>
                            <select id="filtro-mes" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione</option>
                                <option value="01">Janeiro</option>
                                <option value="02">Fevereiro</option>
                                <option value="03">Março</option>
                                <option value="04">Abril</option>
                                <option value="05">Maio</option>
                                <option value="06">Junho</option>
                                <option value="07">Julho</option>
                                <option value="08">Agosto</option>
                                <option value="09">Setembro</option>
                                <option value="10">Outubro</option>
                                <option value="11">Novembro</option>
                                <option value="12">Dezembro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Unidade Escolar</label>
                            <select id="filtro-escola" onchange="carregarTurmasFiltro()" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg <?= $escolaUnica ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                    <?= $escolaUnica ? 'disabled' : '' ?>>
                                <option value="">Selecione</option>
                                <?php foreach (array_values($escolas) as $escola): ?>
                                    <option value="<?= $escola['id'] ?>" 
                                            <?= $escolaUnica && $escolaUnica['id'] == $escola['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($escola['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                            <select id="filtro-turma" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Selecione</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button onclick="buscarPlanos()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <span>Buscar</span>
                        </button>
                        <button onclick="limparFiltros()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Limpar</span>
                        </button>
                        <button onclick="exportarPlanos()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Exportar</span>
                        </button>
                    </div>
                </div>
                
                <!-- Tabela de Planos -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center space-x-1">
                                            <span>AÇÃO</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center space-x-1">
                                            <span>DATA</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center space-x-1">
                                            <span>NOME DA ESCOLA</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center space-x-1">
                                            <span>TURMA</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                        <div class="flex items-center space-x-1">
                                            <span>COMPONENTE CURRICULAR</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="tbody-planos" class="bg-white divide-y divide-gray-200">
                                <!-- Conteúdo será carregado via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button onclick="irParaPagina(1)" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100">K</button>
                            <button onclick="paginaAnterior()" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100">&lt;</button>
                            <div id="paginacao-numeros" class="flex items-center space-x-1">
                                <!-- Números de página serão gerados via JavaScript -->
                            </div>
                            <button onclick="proximaPagina()" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100">&gt;</button>
                            <button onclick="irParaUltimaPagina()" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100">H</button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-700">Itens por página:</span>
                            <select id="itens-por-pagina" onchange="alterarItensPorPagina()" class="px-3 py-1 border border-gray-300 rounded">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100" selected>100</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Novo Plano -->
    <div id="modal-novo-plano" class="fixed inset-0 bg-white z-50 hidden flex flex-col" style="display: none;">
        <!-- Header fixo -->
        <div class="bg-white border-b border-gray-200 px-6 py-4 flex-shrink-0 shadow-sm">
            <div class="flex justify-between items-center">
                <h3 class="text-2xl font-bold text-gray-900">Cadastro :: Planejamento</h3>
                <button onclick="fecharModalNovoPlano()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Conteúdo scrollável -->
        <div class="flex-1 overflow-y-auto bg-gray-50">
            <div class="p-6 max-w-7xl mx-auto w-full">
                
                <form id="form-novo-plano" onsubmit="event.preventDefault(); salvarPlano();">
                    <!-- Data -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                        <input type="date" id="data-aula" name="data_aula" value="<?= date('Y-m-d') ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    
                    <!-- Abas -->
                    <div class="mb-6 border-b border-gray-200">
                        <nav class="flex space-x-8 overflow-x-auto">
                            <button type="button" onclick="mostrarAba('turma')" id="tab-turma" 
                                    class="tab-active py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                                TURMA
                            </button>
                            <button type="button" onclick="mostrarAba('componentes')" id="tab-componentes" 
                                    class="tab-inactive py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                                COMPONENTES CURRICULARES
                            </button>
                            <button type="button" onclick="mostrarAba('competencias')" id="tab-competencias" 
                                    class="tab-inactive py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                                COMPETÊNCIAS
                            </button>
                            <button type="button" onclick="mostrarAba('habilidades')" id="tab-habilidades" 
                                    class="tab-inactive py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                                HABILIDADES
                            </button>
                            <button type="button" onclick="mostrarAba('desenvolvimento')" id="tab-desenvolvimento" 
                                    class="tab-inactive py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                                DESENVOLVIMENTO DO PLANO
                            </button>
                            <button type="button" onclick="mostrarAba('observacao')" id="tab-observacao" 
                                    class="tab-inactive py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                                OBSERVAÇÃO
                            </button>
                        </nav>
                    </div>
                    
                    <!-- Conteúdo das Abas -->
                    <div id="conteudo-abas">
                        <!-- Aba TURMA -->
                        <div id="aba-turma" class="aba-conteudo">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Unidade Escolar</label>
                                    <select id="escola-select" onchange="carregarTurmasEscola()" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg <?= $escolaUnica ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                            <?= $escolaUnica ? 'disabled' : '' ?>>
                                        <option value="">Selecione uma escola</option>
                                        <?php foreach (array_values($escolas) as $escola): ?>
                                            <option value="<?= $escola['id'] ?>" 
                                                    <?= $escolaUnica && $escolaUnica['id'] == $escola['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($escola['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                                    <select id="turma-select" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                        <option value="">Selecione uma turma</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="button" onclick="adicionarTurma()" 
                                            class="w-full bg-primary-green hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium flex items-center justify-center space-x-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span>Adicionar</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Turmas Selecionadas -->
                            <div class="mt-6">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Turmas Selecionadas</h4>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="border-b border-gray-200">
                                                <th class="text-left py-2 px-4 text-sm font-medium text-gray-700">TURMA</th>
                                                <th class="text-left py-2 px-4 text-sm font-medium text-gray-700">AÇÃO</th>
                                            </tr>
                                        </thead>
                                        <tbody id="turmas-selecionadas">
                                            <tr>
                                                <td colspan="2" class="text-center py-4 text-gray-500 text-sm">
                                                    Nenhuma turma selecionada ainda
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba COMPONENTES CURRICULARES -->
                        <div id="aba-componentes" class="aba-conteudo hidden">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Componentes Curriculares</label>
                                <div class="flex gap-2 mb-3">
                                    <select id="select-disciplina-componente" 
                                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">
                                        <option value="">Selecione uma disciplina</option>
                                    </select>
                                    <button type="button" onclick="adicionarDisciplinaComponente()" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center min-w-[48px]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <table class="w-full">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">COMPONENTE CURRICULAR</th>
                                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">AÇÃO</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabela-disciplinas-componente" class="bg-white">
                                            <tr>
                                                <td colspan="2" class="text-center py-4 text-gray-500 text-sm">
                                                    Nenhuma disciplina selecionada
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba COMPETÊNCIAS -->
                        <div id="aba-competencias" class="aba-conteudo hidden">
                            <div class="space-y-8">
                                <!-- Competência Socioemocional -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Competência Socioemocional</label>
                                    <div class="flex gap-2 mb-3">
                                        <select id="select-competencia-socioemocional" 
                                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">
                                            <option value="">Selecione uma competência socioemocional</option>
                                        </select>
                                        <button type="button" onclick="adicionarCompetenciaSocioemocional()" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center min-w-[48px]">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <table class="w-full">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">COMPETÊNCIA SOCIOEMOCIONAL</th>
                                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">AÇÃO</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tabela-competencia-socioemocional" class="bg-white">
                                                <!-- Itens serão adicionados aqui via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Competência Específica -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Competência Específica</label>
                                    <div class="flex gap-2 mb-3">
                                        <select id="select-competencia-especifica" 
                                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">
                                            <option value="">Selecione uma competência específica</option>
                                        </select>
                                        <button type="button" onclick="adicionarCompetenciaEspecifica()" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center min-w-[48px]">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <table class="w-full">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">COMPONENTE</th>
                                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase">COMPETÊNCIA ESPECÍFICA</th>
                                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">AÇÃO</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tabela-competencia-especifica" class="bg-white">
                                                <!-- Itens serão adicionados aqui via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Competência Geral -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Competência Geral</label>
                                    <div class="flex gap-2 mb-3">
                                        <select id="select-competencia-geral" 
                                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">
                                            <option value="">Selecione uma competência geral</option>
                                        </select>
                                        <button type="button" onclick="adicionarCompetenciaGeral()" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center min-w-[48px]">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <table class="w-full">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">COMPETÊNCIA GERAL</th>
                                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">AÇÃO</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tabela-competencia-geral" class="bg-white">
                                                <!-- Itens serão adicionados aqui via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba HABILIDADES -->
                        <div id="aba-habilidades" class="aba-conteudo hidden">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Habilidades</label>
                                <textarea id="habilidades" rows="6" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                          placeholder="Descreva as habilidades que serão trabalhadas..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Aba DESENVOLVIMENTO DO PLANO -->
                        <div id="aba-desenvolvimento" class="aba-conteudo hidden">
                            <div class="space-y-4 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Título do Plano</label>
                                    <input type="text" id="titulo-plano" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                           placeholder="Digite o título do plano de aula">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Conteúdo</label>
                                    <textarea id="conteudo-plano" rows="4" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                              placeholder="Descreva o conteúdo que será trabalhado..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Recursos</label>
                                    <textarea id="recursos-plano" rows="4" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                              placeholder="Liste os recursos necessários..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bimestre</label>
                                    <select id="bimestre-plano" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                        <option value="">Selecione</option>
                                        <option value="1">1º Bimestre</option>
                                        <option value="2">2º Bimestre</option>
                                        <option value="3">3º Bimestre</option>
                                        <option value="4">4º Bimestre</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Grid 2x2 para os campos principais -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Objetivo(s) da Aula -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Objetivo(s) da Aula</label>
                                    <textarea id="objetivos-plano" rows="6" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg resize-y"
                                              placeholder="Descreva os objetivos da aula..."></textarea>
                                    <button type="button" onclick="abrirModalVerMais('objetivos')" 
                                            class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Ver Mais
                                    </button>
                                </div>
                                
                                <!-- Metodologia -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Metodologia</label>
                                    <textarea id="metodologia-plano" rows="6" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg resize-y"
                                              placeholder="Descreva a metodologia que será utilizada..."></textarea>
                                    <button type="button" onclick="abrirModalVerMais('metodologia')" 
                                            class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Ver Mais
                                    </button>
                                </div>
                                
                                <!-- Atividades Flexibilizadas -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Atividades Flexibilizadas</label>
                                    <textarea id="atividades-flexibilizadas" rows="6" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg resize-y"
                                              placeholder="Descreva as atividades flexibilizadas..."></textarea>
                                    <button type="button" onclick="abrirModalVerMais('atividades-flexibilizadas')" 
                                            class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Ver Mais
                                    </button>
                                </div>
                                
                                <!-- Avaliação -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Avaliação</label>
                                    <textarea id="avaliacao-plano" rows="6" 
                                              class="w-full px-4 py-2 border border-gray-300 rounded-lg resize-y"
                                              placeholder="Descreva como será a avaliação..."></textarea>
                                    <button type="button" onclick="abrirModalVerDetalhe('avaliacao')" 
                                            class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Ver Detalhe
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba OBSERVAÇÃO -->
                        <div id="aba-observacao" class="aba-conteudo hidden">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                                <textarea id="observacoes-plano" rows="6" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                          placeholder="Adicione observações sobre o plano de aula..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                </form>
            </div>
        </div>
        
        <!-- Footer fixo com botões -->
        <div class="bg-white border-t border-gray-200 px-6 py-4 flex-shrink-0 shadow-lg">
            <div class="max-w-7xl mx-auto flex justify-end space-x-3">
                <button type="button" onclick="fecharModalNovoPlano()" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                    Cancelar
                </button>
                <button type="submit" form="form-novo-plano"
                        class="px-6 py-2 bg-primary-green hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                    Salvar Plano
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Ver Mais / Ver Detalhe (Full Screen) -->
    <div id="modal-ver-mais" class="fixed inset-0 bg-white z-50 hidden flex flex-col" style="display: none;">
        <!-- Header fixo -->
        <div class="bg-white border-b border-gray-200 px-6 py-4 flex-shrink-0 shadow-sm">
            <div class="flex justify-between items-center">
                <h3 id="modal-ver-mais-titulo" class="text-2xl font-bold text-gray-900"></h3>
                <button onclick="fecharModalVerMais()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Conteúdo scrollável -->
        <div class="flex-1 overflow-y-auto bg-gray-50">
            <div class="p-6 max-w-7xl mx-auto w-full">
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div id="modal-ver-mais-conteudo" 
                         class="w-full px-4 py-3 border border-gray-300 rounded-lg min-h-[500px] whitespace-pre-wrap text-gray-900">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer fixo -->
        <div class="bg-white border-t border-gray-200 px-6 py-4 flex-shrink-0 shadow-lg">
            <div class="max-w-7xl mx-auto flex justify-end">
                <button type="button" onclick="fecharModalVerMais()" 
                        class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    
    <script>
        const turmasProfessor = <?= json_encode($turmasProfessor) ?>;
        const escolas = <?= json_encode(array_values($escolas)) ?>;
        console.log('Turmas do professor:', turmasProfessor);
        console.log('Escolas disponíveis:', escolas);
        let turmasSelecionadas = [];
        let abaAtual = 'turma';
        
        // Arrays para armazenar competências selecionadas
        let competenciasSocioemocionais = [];
        let competenciasEspecificas = [];
        let competenciasGerais = [];
        
        // Array para armazenar disciplinas (componentes curriculares) selecionadas
        let disciplinasComponente = [];
        
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };
        
        function mostrarAba(aba) {
            abaAtual = aba;
            
            // Atualizar tabs
            document.querySelectorAll('[id^="tab-"]').forEach(tab => {
                tab.classList.remove('tab-active');
                tab.classList.add('tab-inactive');
            });
            document.getElementById('tab-' + aba).classList.remove('tab-inactive');
            document.getElementById('tab-' + aba).classList.add('tab-active');
            
            // Atualizar conteúdo
            document.querySelectorAll('.aba-conteudo').forEach(conteudo => {
                conteudo.classList.add('hidden');
            });
            document.getElementById('aba-' + aba).classList.remove('hidden');
            
            // Se a aba de componentes for aberta e houver escola selecionada, carregar disciplinas
            if (aba === 'componentes') {
                const escolaId = document.getElementById('escola-select').value;
                if (escolaId) {
                    carregarDisciplinasEscola(escolaId);
                }
            }
        }
        
        async function carregarTurmasEscola() {
            const escolaId = document.getElementById('escola-select').value;
            const turmaSelect = document.getElementById('turma-select');
            
            turmaSelect.innerHTML = '<option value="">Carregando turmas...</option>';
            turmaSelect.disabled = true;
            
            if (!escolaId) {
                turmaSelect.innerHTML = '<option value="">Selecione uma escola</option>';
                turmaSelect.disabled = false;
                return;
            }
            
            console.log('Carregando turmas para escola ID:', escolaId);
            
            try {
                const response = await fetch(`?acao=buscar_turmas_escola&escola_id=${escolaId}`);
                const data = await response.json();
                
                console.log('Resposta da busca de turmas:', data);
                
                turmaSelect.innerHTML = '<option value="">Selecione uma turma</option>';
                turmaSelect.disabled = false;
                
                if (data.success && data.turmas && data.turmas.length > 0) {
                    data.turmas.forEach(turma => {
                        const option = document.createElement('option');
                        // Usar apenas o ID da turma (sem disciplina)
                        option.value = turma.turma_id;
                        option.textContent = turma.turma_nome;
                        option.dataset.turmaId = turma.turma_id;
                        option.dataset.turmaNome = turma.turma_nome;
                        turmaSelect.appendChild(option);
                    });
                    console.log('Turmas carregadas:', data.turmas.length);
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Nenhuma turma disponível para esta escola';
                    option.disabled = true;
                    turmaSelect.appendChild(option);
                    console.warn('Nenhuma turma encontrada para a escola:', escolaId);
                    console.warn('Resposta completa:', data);
                }
                
                // Carregar disciplinas da escola também
                await carregarDisciplinasEscola(escolaId);
            } catch (error) {
                console.error('Erro ao carregar turmas:', error);
                turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                turmaSelect.disabled = false;
            }
        }
        
        async function carregarDisciplinasEscola(escolaId) {
            const disciplinaSelect = document.getElementById('select-disciplina-componente');
            
            if (!disciplinaSelect) return;
            
            disciplinaSelect.innerHTML = '<option value="">Carregando disciplinas...</option>';
            disciplinaSelect.disabled = true;
            
            if (!escolaId) {
                disciplinaSelect.innerHTML = '<option value="">Selecione uma escola primeiro</option>';
                disciplinaSelect.disabled = false;
                return;
            }
            
            try {
                const response = await fetch(`?acao=buscar_disciplinas_escola&escola_id=${escolaId}`);
                const data = await response.json();
                
                disciplinaSelect.innerHTML = '<option value="">Selecione uma disciplina</option>';
                disciplinaSelect.disabled = false;
                
                if (data.success && data.disciplinas && data.disciplinas.length > 0) {
                    data.disciplinas.forEach(disciplina => {
                        const option = document.createElement('option');
                        option.value = disciplina.disciplina_id;
                        option.textContent = disciplina.disciplina_nome;
                        option.dataset.disciplinaId = disciplina.disciplina_id;
                        option.dataset.disciplinaNome = disciplina.disciplina_nome;
                        disciplinaSelect.appendChild(option);
                    });
                    console.log('Disciplinas carregadas:', data.disciplinas.length);
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Nenhuma disciplina disponível para esta escola';
                    option.disabled = true;
                    disciplinaSelect.appendChild(option);
                    console.warn('Nenhuma disciplina encontrada para a escola:', escolaId);
                }
            } catch (error) {
                console.error('Erro ao carregar disciplinas:', error);
                disciplinaSelect.innerHTML = '<option value="">Erro ao carregar disciplinas</option>';
                disciplinaSelect.disabled = false;
            }
        }
        
        function adicionarDisciplinaComponente() {
            const select = document.getElementById('select-disciplina-componente');
            const selectedOption = select.options[select.selectedIndex];
            
            if (!selectedOption.value) {
                alert('Selecione uma disciplina');
                return;
            }
            
            const disciplina = {
                id: selectedOption.value,
                nome: selectedOption.textContent
            };
            
            // Verificar se já foi adicionada
            if (disciplinasComponente.find(d => d.id == disciplina.id)) {
                alert('Esta disciplina já foi adicionada');
                return;
            }
            
            disciplinasComponente.push(disciplina);
            atualizarTabelaDisciplinasComponente();
            select.value = '';
        }
        
        function removerDisciplinaComponente(index) {
            disciplinasComponente.splice(index, 1);
            atualizarTabelaDisciplinasComponente();
        }
        
        function atualizarTabelaDisciplinasComponente() {
            const tbody = document.getElementById('tabela-disciplinas-componente');
            
            if (disciplinasComponente.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="2" class="text-center py-4 text-gray-500 text-sm">
                            Nenhuma disciplina selecionada
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = disciplinasComponente.map((disciplina, index) => `
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 text-sm text-gray-900">${disciplina.nome}</td>
                    <td class="py-3 px-4 text-right">
                        <button type="button" onclick="removerDisciplinaComponente(${index})" 
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Remover
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        function adicionarTurma() {
            const turmaSelect = document.getElementById('turma-select');
            const selectedOption = turmaSelect.options[turmaSelect.selectedIndex];
            
            if (!selectedOption.value) {
                alert('Selecione uma turma');
                return;
            }
            
            const turmaId = selectedOption.dataset.turmaId;
            const turmaNome = selectedOption.dataset.turmaNome;
            
            // Verificar se já foi adicionada
            if (turmasSelecionadas.find(t => t.turma_id == turmaId)) {
                alert('Esta turma já foi adicionada');
                return;
            }
            
            turmasSelecionadas.push({
                turma_id: turmaId,
                turma_nome: turmaNome
            });
            
            atualizarTabelaTurmas();
            turmaSelect.value = '';
        }
        
        function removerTurma(index) {
            turmasSelecionadas.splice(index, 1);
            atualizarTabelaTurmas();
        }
        
        function atualizarTabelaTurmas() {
            const tbody = document.getElementById('turmas-selecionadas');
            
            if (turmasSelecionadas.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="2" class="text-center py-4 text-gray-500 text-sm">
                            Nenhuma turma selecionada ainda
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = turmasSelecionadas.map((turma, index) => `
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 text-sm text-gray-900">${turma.turma_nome}</td>
                    <td class="py-3 px-4">
                        <button type="button" onclick="removerTurma(${index})" 
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Remover
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Funções para gerenciar Competências Socioemocionais
        function adicionarCompetenciaSocioemocional() {
            const select = document.getElementById('select-competencia-socioemocional');
            const selectedOption = select.options[select.selectedIndex];
            
            if (!selectedOption.value) {
                alert('Selecione uma competência socioemocional');
                return;
            }
            
            const competencia = {
                id: selectedOption.value,
                nome: selectedOption.text
            };
            
            // Verificar se já foi adicionada
            if (competenciasSocioemocionais.find(c => c.id == competencia.id)) {
                alert('Esta competência socioemocional já foi adicionada');
                return;
            }
            
            competenciasSocioemocionais.push(competencia);
            atualizarTabelaCompetenciasSocioemocionais();
            select.value = '';
        }
        
        function removerCompetenciaSocioemocional(index) {
            competenciasSocioemocionais.splice(index, 1);
            atualizarTabelaCompetenciasSocioemocionais();
        }
        
        function atualizarTabelaCompetenciasSocioemocionais() {
            const tbody = document.getElementById('tabela-competencia-socioemocional');
            
            if (competenciasSocioemocionais.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="2" class="text-center py-4 text-gray-500 text-sm">
                            Nenhuma competência socioemocional selecionada
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = competenciasSocioemocionais.map((comp, index) => `
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 text-sm text-gray-900">${comp.nome}</td>
                    <td class="py-3 px-4 text-right">
                        <button type="button" onclick="removerCompetenciaSocioemocional(${index})" 
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Remover
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Funções para gerenciar Competências Específicas
        function adicionarCompetenciaEspecifica() {
            const select = document.getElementById('select-competencia-especifica');
            const selectedOption = select.options[select.selectedIndex];
            
            if (!selectedOption.value) {
                alert('Selecione uma competência específica');
                return;
            }
            
            const competencia = {
                id: selectedOption.value,
                componente: selectedOption.dataset.componente || '',
                nome: selectedOption.text
            };
            
            // Verificar se já foi adicionada
            if (competenciasEspecificas.find(c => c.id == competencia.id)) {
                alert('Esta competência específica já foi adicionada');
                return;
            }
            
            competenciasEspecificas.push(competencia);
            atualizarTabelaCompetenciasEspecificas();
            select.value = '';
        }
        
        function removerCompetenciaEspecifica(index) {
            competenciasEspecificas.splice(index, 1);
            atualizarTabelaCompetenciasEspecificas();
        }
        
        function atualizarTabelaCompetenciasEspecificas() {
            const tbody = document.getElementById('tabela-competencia-especifica');
            
            if (competenciasEspecificas.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center py-4 text-gray-500 text-sm">
                            Nenhuma competência específica selecionada
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = competenciasEspecificas.map((comp, index) => `
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 text-sm text-gray-900">${comp.componente || '-'}</td>
                    <td class="py-3 px-4 text-sm text-gray-700 text-center">${comp.nome}</td>
                    <td class="py-3 px-4 text-right">
                        <button type="button" onclick="removerCompetenciaEspecifica(${index})" 
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Remover
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Funções para gerenciar Competências Gerais
        function adicionarCompetenciaGeral() {
            const select = document.getElementById('select-competencia-geral');
            const selectedOption = select.options[select.selectedIndex];
            
            if (!selectedOption.value) {
                alert('Selecione uma competência geral');
                return;
            }
            
            const competencia = {
                id: selectedOption.value,
                nome: selectedOption.text
            };
            
            // Verificar se já foi adicionada
            if (competenciasGerais.find(c => c.id == competencia.id)) {
                alert('Esta competência geral já foi adicionada');
                return;
            }
            
            competenciasGerais.push(competencia);
            atualizarTabelaCompetenciasGerais();
            select.value = '';
        }
        
        function removerCompetenciaGeral(index) {
            competenciasGerais.splice(index, 1);
            atualizarTabelaCompetenciasGerais();
        }
        
        function atualizarTabelaCompetenciasGerais() {
            const tbody = document.getElementById('tabela-competencia-geral');
            
            if (competenciasGerais.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="2" class="text-center py-4 text-gray-500 text-sm">
                            Nenhuma competência geral selecionada
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = competenciasGerais.map((comp, index) => `
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 text-sm text-gray-900">${comp.nome}</td>
                    <td class="py-3 px-4 text-right">
                        <button type="button" onclick="removerCompetenciaGeral(${index})" 
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Remover
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        function abrirModalNovoPlano() {
            document.getElementById('modal-novo-plano').classList.remove('hidden');
            document.getElementById('modal-novo-plano').style.display = 'flex';
            
            // Salvar o valor da escola antes do reset (se estiver pré-selecionada)
            const escolaSelect = document.getElementById('escola-select');
            const escolaSelecionada = escolaSelect.value;
            
            // Resetar apenas os campos do formulário, não o select de escola
            document.getElementById('data-aula').value = new Date().toISOString().split('T')[0];
            document.getElementById('titulo-plano').value = '';
            document.getElementById('conteudo-plano').value = '';
            document.getElementById('objetivos-plano').value = '';
            document.getElementById('metodologia-plano').value = '';
            document.getElementById('recursos-plano').value = '';
            document.getElementById('avaliacao-plano').value = '';
            document.getElementById('atividades-flexibilizadas').value = '';
            document.getElementById('bimestre-plano').value = '';
            document.getElementById('observacoes-plano').value = '';
            document.getElementById('componentes-curriculares').value = '';
            document.getElementById('habilidades').value = '';
            
            // Garantir que o select de escola está populado corretamente
            console.log('Escolas disponíveis:', escolas);
            if (escolas && escolas.length > 0) {
                // Limpar e repovoar o select de escola
                escolaSelect.innerHTML = '<option value="">Selecione uma escola</option>';
                escolas.forEach(escola => {
                    const option = document.createElement('option');
                    option.value = escola.id;
                    option.textContent = escola.nome;
                    escolaSelect.appendChild(option);
                });
                
                // Restaurar a escola selecionada se estava pré-selecionada
                if (escolaSelecionada) {
                    escolaSelect.value = escolaSelecionada;
                } else if (escolas.length === 1) {
                    // Se há apenas uma escola, selecionar automaticamente
                    escolaSelect.value = escolas[0].id;
                }
            } else {
                console.error('Nenhuma escola encontrada para o professor. Verifique a lotação do professor.');
                // Tentar buscar escolas diretamente do HTML se não estiverem no JavaScript
                const optionsHTML = document.querySelectorAll('#escola-select option');
                if (optionsHTML.length <= 1) {
                    console.error('Select de escola não foi populado no HTML. Verifique o PHP.');
                }
            }
            
            // Limpar turma select
            const turmaSelect = document.getElementById('turma-select');
            turmaSelect.innerHTML = '<option value="">Selecione uma turma</option>';
            
            turmasSelecionadas = [];
            competenciasSocioemocionais = [];
            competenciasEspecificas = [];
            competenciasGerais = [];
            disciplinasComponente = [];
            atualizarTabelaTurmas();
            atualizarTabelaCompetenciasSocioemocionais();
            atualizarTabelaCompetenciasEspecificas();
            atualizarTabelaCompetenciasGerais();
            atualizarTabelaDisciplinasComponente();
            mostrarAba('turma');
            
            // Se a escola já estiver pré-selecionada (professor atua em apenas uma escola), carregar turmas automaticamente
            if (escolaSelect.value) {
                carregarTurmasEscola();
            } else {
                // Limpar disciplinas se não houver escola selecionada
                const disciplinaSelect = document.getElementById('select-disciplina-componente');
                if (disciplinaSelect) {
                    disciplinaSelect.innerHTML = '<option value="">Selecione uma escola primeiro</option>';
                }
            }
        }
        
        function fecharModalNovoPlano() {
            document.getElementById('modal-novo-plano').classList.add('hidden');
            document.getElementById('modal-novo-plano').style.display = 'none';
        }
        
        // Funções para modais Ver Mais / Ver Detalhe
        function abrirModalVerMais(tipo) {
            const titulos = {
                'objetivos': 'Objetivo(s) da Aula',
                'metodologia': 'Metodologia',
                'atividades-flexibilizadas': 'Atividades Flexibilizadas'
            };
            
            const campos = {
                'objetivos': 'objetivos-plano',
                'metodologia': 'metodologia-plano',
                'atividades-flexibilizadas': 'atividades-flexibilizadas'
            };
            
            const campoId = campos[tipo];
            const campo = document.getElementById(campoId);
            const conteudo = campo ? campo.value : '';
            
            document.getElementById('modal-ver-mais-titulo').textContent = titulos[tipo] || 'Visualizar';
            document.getElementById('modal-ver-mais-conteudo').textContent = conteudo || 'Nenhum conteúdo adicionado ainda.';
            
            document.getElementById('modal-ver-mais').classList.remove('hidden');
            document.getElementById('modal-ver-mais').style.display = 'flex';
        }
        
        function abrirModalVerDetalhe(tipo) {
            const titulos = {
                'avaliacao': 'Avaliação'
            };
            
            const campos = {
                'avaliacao': 'avaliacao-plano'
            };
            
            const campoId = campos[tipo];
            const campo = document.getElementById(campoId);
            const conteudo = campo ? campo.value : '';
            
            document.getElementById('modal-ver-mais-titulo').textContent = titulos[tipo] || 'Visualizar Detalhe';
            document.getElementById('modal-ver-mais-conteudo').textContent = conteudo || 'Nenhum conteúdo adicionado ainda.';
            
            document.getElementById('modal-ver-mais').classList.remove('hidden');
            document.getElementById('modal-ver-mais').style.display = 'flex';
        }
        
        function fecharModalVerMais() {
            document.getElementById('modal-ver-mais').classList.add('hidden');
            document.getElementById('modal-ver-mais').style.display = 'none';
        }
        
        async function salvarPlano() {
            if (turmasSelecionadas.length === 0) {
                alert('Adicione pelo menos uma turma');
                mostrarAba('turma');
                return;
            }
            
            const dados = {
                data_aula: document.getElementById('data-aula').value,
                turmas: turmasSelecionadas,
                titulo: document.getElementById('titulo-plano').value || 'Plano de Aula',
                conteudo: document.getElementById('conteudo-plano').value,
                objetivos: document.getElementById('objetivos-plano').value,
                metodologia: document.getElementById('metodologia-plano').value,
                recursos: document.getElementById('recursos-plano').value,
                avaliacao: document.getElementById('avaliacao-plano').value,
                atividades_flexibilizadas: document.getElementById('atividades-flexibilizadas').value,
                bimestre: document.getElementById('bimestre-plano').value,
                observacoes: document.getElementById('observacoes-plano').value
            };
            
            const formData = new FormData();
            formData.append('acao', 'criar_plano');
            formData.append('dados', JSON.stringify(dados));
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Resposta não é JSON:', text.substring(0, 200));
                    throw new Error('Resposta do servidor não é válida.');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Plano(s) de aula criado(s) com sucesso!');
                    fecharModalNovoPlano();
                    location.reload();
                } else {
                    alert('Erro ao criar plano: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao processar requisição. Por favor, tente novamente.');
            }
        }
        
        function visualizarPlano(id) {
            alert('Funcionalidade de visualização será implementada. ID: ' + id);
        }
        
        function editarPlano(id) {
            alert('Funcionalidade de edição será implementada. ID: ' + id);
        }
        
        // Variáveis de paginação
        let paginaAtual = 1;
        let totalPaginas = 1;
        let itensPorPagina = 100;
        
        // Funções para tela de pesquisa
        function toggleFiltros() {
            const secao = document.getElementById('secao-filtros');
            secao.classList.toggle('hidden');
        }
        
        async function buscarPlanos() {
            const filtros = {
                data_aula: document.getElementById('filtro-data').value,
                mes: document.getElementById('filtro-mes').value,
                escola_id: document.getElementById('filtro-escola').value,
                turma_id: document.getElementById('filtro-turma').value,
                pagina: paginaAtual,
                itens_por_pagina: itensPorPagina
            };
            
            const params = new URLSearchParams();
            params.append('acao', 'listar_planos');
            Object.keys(filtros).forEach(key => {
                if (filtros[key]) {
                    params.append(key, filtros[key]);
                }
            });
            
            try {
                const response = await fetch(`?${params.toString()}`);
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Resposta do servidor não é válida.');
                }
                const data = await response.json();
                
                if (data.success) {
                    renderizarPlanos(data.planos);
                    paginaAtual = data.pagina_atual || 1;
                    totalPaginas = data.total_paginas || 1;
                    atualizarPaginacao();
                } else {
                    alert('Erro ao buscar planos');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao buscar planos. Por favor, tente novamente.');
            }
        }
        
        function renderizarPlanos(planos) {
            const tbody = document.getElementById('tbody-planos');
            
            if (!planos || planos.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Nenhum plano de aula encontrado.
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = planos.map(plano => `
                <tr class="hover:bg-gray-50 ${planos.indexOf(plano) % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <button onclick="abrirMenuAcoes(${plano.id})" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                        </button>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${new Date(plano.data_aula).toLocaleDateString('pt-BR')}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${plano.escola_nome || '-'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${plano.turma_nome || '-'}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        ${plano.disciplina_nome || '-'}
                    </td>
                </tr>
            `).join('');
        }
        
        function limparFiltros() {
            document.getElementById('filtro-data').value = '';
            document.getElementById('filtro-mes').value = '';
            document.getElementById('filtro-escola').value = '';
            document.getElementById('filtro-turma').value = '';
            paginaAtual = 1;
            buscarPlanos();
        }
        
        function exportarPlanos() {
            alert('Funcionalidade de exportação será implementada.');
        }
        
        function atualizarPaginacao() {
            const container = document.getElementById('paginacao-numeros');
            let html = '';
            
            const maxBotoes = 5;
            let inicio = Math.max(1, paginaAtual - Math.floor(maxBotoes / 2));
            let fim = Math.min(totalPaginas, inicio + maxBotoes - 1);
            
            if (fim - inicio < maxBotoes - 1) {
                inicio = Math.max(1, fim - maxBotoes + 1);
            }
            
            for (let i = inicio; i <= fim; i++) {
                html += `
                    <button onclick="irParaPagina(${i})" 
                            class="px-3 py-1 border border-gray-300 rounded ${i === paginaAtual ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'}">
                        ${i}
                    </button>
                `;
            }
            
            container.innerHTML = html;
        }
        
        function irParaPagina(pagina) {
            if (pagina >= 1 && pagina <= totalPaginas) {
                paginaAtual = pagina;
                buscarPlanos();
            }
        }
        
        function paginaAnterior() {
            if (paginaAtual > 1) {
                paginaAtual--;
                buscarPlanos();
            }
        }
        
        function proximaPagina() {
            if (paginaAtual < totalPaginas) {
                paginaAtual++;
                buscarPlanos();
            }
        }
        
        function irParaUltimaPagina() {
            paginaAtual = totalPaginas;
            buscarPlanos();
        }
        
        function alterarItensPorPagina() {
            itensPorPagina = parseInt(document.getElementById('itens-por-pagina').value);
            paginaAtual = 1;
            buscarPlanos();
        }
        
        function abrirMenuAcoes(id) {
            // Implementar menu de ações (visualizar, editar, excluir)
            alert('Menu de ações para plano ID: ' + id);
        }
        
        function carregarTurmasFiltro() {
            const escolaId = document.getElementById('filtro-escola').value;
            const turmaSelect = document.getElementById('filtro-turma');
            
            turmaSelect.innerHTML = '<option value="">Selecione</option>';
            
            if (!escolaId) return;
            
            const escola = escolas.find(e => e.id == escolaId);
            if (escola) {
                const turmasUnicas = {};
                Object.values(escola.turmas).forEach(turma => {
                    if (!turmasUnicas[turma.id]) {
                        turmasUnicas[turma.id] = turma;
                    }
                });
                
                Object.values(turmasUnicas).forEach(turma => {
                    const option = document.createElement('option');
                    option.value = turma.id;
                    option.textContent = turma.nome;
                    turmaSelect.appendChild(option);
                });
            }
        }
        
        // Funções para modal de logout
        window.confirmLogout = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        };
        
        window.closeLogoutModal = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        };
        
        window.logout = function() {
            window.location.href = '../auth/logout.php';
        };
        
        // Carregar planos ao abrir a página
        document.addEventListener('DOMContentLoaded', function() {
            // Se a escola já estiver pré-selecionada (professor atua em apenas uma escola), carregar turmas automaticamente
            const filtroEscola = document.getElementById('filtro-escola');
            if (filtroEscola.value) {
                carregarTurmasFiltro();
            }
            buscarPlanos();
        });
    </script>
    
    <!-- Modal de Logout -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmar Saída</h3>
                    <p class="text-sm text-gray-600">Tem certeza que deseja sair do sistema?</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <button onclick="window.closeLogoutModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button onclick="window.logout()" class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors duration-200">
                    Sim, Sair
                </button>
            </div>
        </div>
    </div>
</body>
</html>

