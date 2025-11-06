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

// ==================== FUNÇÕES PARA DISCIPLINAS ====================

function listarDisciplinas($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT id, codigo, nome, carga_horaria 
            FROM disciplina 
            WHERE 1=1";
    
    if (!empty($busca)) {
        $sql .= " AND (codigo LIKE :busca OR nome LIKE :busca)";
    }
    
    $sql .= " ORDER BY nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cadastrarDisciplina($dados) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Verificar se código já existe
        if (!empty($dados['codigo'])) {
            $stmt = $conn->prepare("SELECT id FROM disciplina WHERE codigo = :codigo");
            $stmt->bindParam(':codigo', $dados['codigo']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['status' => false, 'mensagem' => 'Código da disciplina já cadastrado no sistema.'];
            }
        }
        
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("INSERT INTO disciplina (codigo, nome, carga_horaria) 
                                VALUES (:codigo, :nome, :carga_horaria)");
        
        $codigo = !empty($dados['codigo']) ? $dados['codigo'] : null;
        $cargaHoraria = !empty($dados['carga_horaria']) ? intval($dados['carga_horaria']) : null;
        
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':carga_horaria', $cargaHoraria);
        
        $stmt->execute();
        
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Disciplina cadastrada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao cadastrar disciplina: ' . $e->getMessage()];
    }
}

function atualizarDisciplina($dados) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Verificar se código já existe (exceto para a própria disciplina)
        if (!empty($dados['codigo'])) {
            $stmt = $conn->prepare("SELECT id FROM disciplina WHERE codigo = :codigo AND id != :id");
            $stmt->bindParam(':codigo', $dados['codigo']);
            $stmt->bindParam(':id', $dados['id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['status' => false, 'mensagem' => 'Código da disciplina já cadastrado para outra disciplina.'];
            }
        }
        
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("UPDATE disciplina SET 
                                codigo = :codigo, 
                                nome = :nome, 
                                carga_horaria = :carga_horaria 
                                WHERE id = :id");
        
        $codigo = !empty($dados['codigo']) ? $dados['codigo'] : null;
        $cargaHoraria = !empty($dados['carga_horaria']) ? intval($dados['carga_horaria']) : null;
        
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':carga_horaria', $cargaHoraria);
        $stmt->bindParam(':id', $dados['id']);
        
        $stmt->execute();
        
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Disciplina atualizada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao atualizar disciplina: ' . $e->getMessage()];
    }
}

function excluirDisciplina($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Verificar se a disciplina está sendo usada em turma_professor
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM turma_professor WHERE disciplina_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            return ['status' => false, 'mensagem' => 'Não é possível excluir a disciplina pois ela está sendo usada em turmas.'];
        }
        
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("DELETE FROM disciplina WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Disciplina excluída com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao excluir disciplina: ' . $e->getMessage()];
    }
}

// ==================== FUNÇÕES PARA TURMAS ====================

function listarTurmas($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT t.id, t.escola_id, t.ano_letivo, t.serie, t.letra, t.turno, t.capacidade, t.ativo, t.criado_em,
                   e.nome as escola_nome, e.codigo as escola_codigo,
                   COUNT(DISTINCT at.aluno_id) as total_alunos
            FROM turma t
            INNER JOIN escola e ON t.escola_id = e.id
            LEFT JOIN aluno_turma at ON t.id = at.turma_id AND at.status = 'MATRICULADO'
            WHERE 1=1";
    
    if (!empty($busca)) {
        $sql .= " AND (t.serie LIKE :busca OR t.letra LIKE :busca OR e.nome LIKE :busca)";
    }
    
    $sql .= " GROUP BY t.id, t.escola_id, t.ano_letivo, t.serie, t.letra, t.turno, t.capacidade, t.ativo, t.criado_em, e.nome, e.codigo
              ORDER BY t.ano_letivo DESC, t.serie ASC, t.letra ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarEscolas() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT id, codigo, nome FROM escola ORDER BY nome ASC";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cadastrarTurma($dados) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Verificar se já existe turma com mesma série, letra, turno e escola no mesmo ano letivo
        $stmt = $conn->prepare("SELECT id FROM turma 
                                WHERE escola_id = :escola_id 
                                AND ano_letivo = :ano_letivo 
                                AND serie = :serie 
                                AND letra = :letra 
                                AND turno = :turno");
        $stmt->bindParam(':escola_id', $dados['escola_id']);
        $stmt->bindParam(':ano_letivo', $dados['ano_letivo']);
        $stmt->bindParam(':serie', $dados['serie']);
        $stmt->bindParam(':letra', $dados['letra']);
        $stmt->bindParam(':turno', $dados['turno']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['status' => false, 'mensagem' => 'Já existe uma turma com essas características na escola selecionada.'];
        }
        
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("INSERT INTO turma (escola_id, ano_letivo, serie, letra, turno, capacidade, ativo) 
                                VALUES (:escola_id, :ano_letivo, :serie, :letra, :turno, :capacidade, 1)");
        
        $capacidade = !empty($dados['capacidade']) ? intval($dados['capacidade']) : null;
        
        $stmt->bindParam(':escola_id', $dados['escola_id']);
        $stmt->bindParam(':ano_letivo', $dados['ano_letivo']);
        $stmt->bindParam(':serie', $dados['serie']);
        $stmt->bindParam(':letra', $dados['letra']);
        $stmt->bindParam(':turno', $dados['turno']);
        $stmt->bindParam(':capacidade', $capacidade);
        
        $stmt->execute();
        
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Turma cadastrada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao cadastrar turma: ' . $e->getMessage()];
    }
}

function atualizarTurma($dados) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Verificar se já existe outra turma com mesma série, letra, turno e escola no mesmo ano letivo
        $stmt = $conn->prepare("SELECT id FROM turma 
                                WHERE escola_id = :escola_id 
                                AND ano_letivo = :ano_letivo 
                                AND serie = :serie 
                                AND letra = :letra 
                                AND turno = :turno
                                AND id != :id");
        $stmt->bindParam(':escola_id', $dados['escola_id']);
        $stmt->bindParam(':ano_letivo', $dados['ano_letivo']);
        $stmt->bindParam(':serie', $dados['serie']);
        $stmt->bindParam(':letra', $dados['letra']);
        $stmt->bindParam(':turno', $dados['turno']);
        $stmt->bindParam(':id', $dados['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['status' => false, 'mensagem' => 'Já existe outra turma com essas características na escola selecionada.'];
        }
        
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("UPDATE turma SET 
                                escola_id = :escola_id, 
                                ano_letivo = :ano_letivo, 
                                serie = :serie, 
                                letra = :letra, 
                                turno = :turno, 
                                capacidade = :capacidade, 
                                ativo = :ativo 
                                WHERE id = :id");
        
        $capacidade = !empty($dados['capacidade']) ? intval($dados['capacidade']) : null;
        $ativo = isset($dados['ativo']) ? intval($dados['ativo']) : 1;
        
        $stmt->bindParam(':escola_id', $dados['escola_id']);
        $stmt->bindParam(':ano_letivo', $dados['ano_letivo']);
        $stmt->bindParam(':serie', $dados['serie']);
        $stmt->bindParam(':letra', $dados['letra']);
        $stmt->bindParam(':turno', $dados['turno']);
        $stmt->bindParam(':capacidade', $capacidade);
        $stmt->bindParam(':ativo', $ativo);
        $stmt->bindParam(':id', $dados['id']);
        
        $stmt->execute();
        
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Turma atualizada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao atualizar turma: ' . $e->getMessage()];
    }
}

function excluirTurma($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Verificar se a turma tem alunos matriculados
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM aluno_turma WHERE turma_id = :id AND status = 'MATRICULADO'");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            return ['status' => false, 'mensagem' => 'Não é possível excluir a turma pois ela possui alunos matriculados.'];
        }
        
        $conn->beginTransaction();
        
        // Excluir relacionamentos primeiro
        $stmt = $conn->prepare("DELETE FROM turma_professor WHERE turma_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM aluno_turma WHERE turma_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Excluir a turma
        $stmt = $conn->prepare("DELETE FROM turma WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $conn->commit();
        
        return ['status' => true, 'mensagem' => 'Turma excluída com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao excluir turma: ' . $e->getMessage()];
    }
}

// ==================== FUNÇÕES PARA SÉRIES ====================

function listarSeries() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT DISTINCT serie, COUNT(*) as total_turmas
            FROM turma
            WHERE serie IS NOT NULL AND serie != ''
            GROUP BY serie
            ORDER BY serie ASC";
    
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== PROCESSAMENTO DE FORMULÁRIOS ====================

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        // Processar Disciplinas
        if ($_POST['acao'] === 'cadastrar_disciplina') {
            $dados = [
                'codigo' => $_POST['codigo'] ?? '',
                'nome' => $_POST['nome'] ?? '',
                'carga_horaria' => $_POST['carga_horaria'] ?? null
            ];
            
            $resultado = cadastrarDisciplina($dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
        
        if ($_POST['acao'] === 'editar_disciplina' && isset($_POST['id'])) {
            $dados = [
                'id' => $_POST['id'],
                'codigo' => $_POST['codigo'] ?? '',
                'nome' => $_POST['nome'] ?? '',
                'carga_horaria' => $_POST['carga_horaria'] ?? null
            ];
            
            $resultado = atualizarDisciplina($dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
        
        if ($_POST['acao'] === 'excluir_disciplina' && isset($_POST['id'])) {
            $resultado = excluirDisciplina($_POST['id']);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
        
        // Processar Turmas
        if ($_POST['acao'] === 'cadastrar_turma') {
            $dados = [
                'escola_id' => $_POST['escola_id'] ?? '',
                'ano_letivo' => $_POST['ano_letivo'] ?? date('Y'),
                'serie' => $_POST['serie'] ?? '',
                'letra' => $_POST['letra'] ?? '',
                'turno' => $_POST['turno'] ?? '',
                'capacidade' => $_POST['capacidade'] ?? null
            ];
            
            $resultado = cadastrarTurma($dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
        
        if ($_POST['acao'] === 'editar_turma' && isset($_POST['id'])) {
            $dados = [
                'id' => $_POST['id'],
                'escola_id' => $_POST['escola_id'] ?? '',
                'ano_letivo' => $_POST['ano_letivo'] ?? date('Y'),
                'serie' => $_POST['serie'] ?? '',
                'letra' => $_POST['letra'] ?? '',
                'turno' => $_POST['turno'] ?? '',
                'capacidade' => $_POST['capacidade'] ?? null,
                'ativo' => $_POST['ativo'] ?? '1'
            ];
            
            $resultado = atualizarTurma($dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
        
        if ($_POST['acao'] === 'excluir_turma' && isset($_POST['id'])) {
            $resultado = excluirTurma($_POST['id']);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
    }
}

// Buscar dados
$disciplinas = listarDisciplinas($_GET['busca_disciplina'] ?? '');
$turmas = listarTurmas($_GET['busca_turma'] ?? '');
$escolas = listarEscolas();
$series = listarSeries();
$anoAtual = date('Y');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Turmas, Séries e Disciplinas - SIGAE</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <style>
        /* Estilos adicionais serão adicionados aqui */
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header e Sidebar serão adicionados aqui -->
    <!-- Por enquanto, vou criar uma versão simplificada -->
    
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">Gestão de Turmas, Séries e Disciplinas</h1>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="gestao_usuarios.php" class="text-gray-600 hover:text-gray-900">Usuários</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-md p-4 <?= $tipoMensagem === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Conteúdo Principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Tabs -->
            <div class="bg-white rounded-lg shadow-sm mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button onclick="showTab('tab-disciplinas')" class="tab-btn tab-active py-4 px-6 text-sm font-medium text-primary-green border-b-2 border-primary-green">
                            Disciplinas
                        </button>
                        <button onclick="showTab('tab-turmas')" class="tab-btn py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Turmas
                        </button>
                        <button onclick="showTab('tab-series')" class="tab-btn py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Séries
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Tab Disciplinas -->
            <div id="tab-disciplinas" class="tab-content">
                <!-- Listagem de Disciplinas -->
                <div class="bg-white rounded-lg shadow-sm mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-gray-900">Disciplinas Cadastradas</h2>
                            <button onclick="abrirModalCadastroDisciplina()" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                                + Nova Disciplina
                            </button>
                        </div>
                        
                        <!-- Busca -->
                        <form method="GET" class="mb-4">
                            <input type="hidden" name="busca_turma" value="<?= htmlspecialchars($_GET['busca_turma'] ?? '') ?>">
                            <div class="flex space-x-2">
                                <input type="text" name="busca_disciplina" value="<?= htmlspecialchars($_GET['busca_disciplina'] ?? '') ?>" 
                                       placeholder="Buscar por código ou nome..." 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                    Buscar
                                </button>
                            </div>
                        </form>
                        
                        <!-- Tabela de Disciplinas -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carga Horária</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($disciplinas)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Nenhuma disciplina cadastrada.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($disciplinas as $disciplina): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($disciplina['codigo'] ?? '-') ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($disciplina['nome']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($disciplina['carga_horaria'] ?? '-') ?> horas</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="editarDisciplina(<?= $disciplina['id'] ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                                            <button onclick="abrirModalExclusaoDisciplina(<?= $disciplina['id'] ?>, '<?= htmlspecialchars($disciplina['nome']) ?>')" class="text-red-600 hover:text-red-900">Excluir</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Turmas -->
            <div id="tab-turmas" class="tab-content hidden">
                <!-- Listagem de Turmas -->
                <div class="bg-white rounded-lg shadow-sm mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-gray-900">Turmas Cadastradas</h2>
                            <button onclick="abrirModalCadastroTurma()" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                                + Nova Turma
                            </button>
                        </div>
                        
                        <!-- Busca -->
                        <form method="GET" class="mb-4">
                            <input type="hidden" name="busca_disciplina" value="<?= htmlspecialchars($_GET['busca_disciplina'] ?? '') ?>">
                            <div class="flex space-x-2">
                                <input type="text" name="busca_turma" value="<?= htmlspecialchars($_GET['busca_turma'] ?? '') ?>" 
                                       placeholder="Buscar por série, letra ou escola..." 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                    Buscar
                                </button>
                            </div>
                        </form>
                        
                        <!-- Tabela de Turmas -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano Letivo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Série</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Letra</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turno</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacidade</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($turmas)): ?>
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">Nenhuma turma cadastrada.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($turmas as $turma): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($turma['escola_nome']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($turma['ano_letivo']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($turma['serie']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($turma['letra']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($turma['turno']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($turma['capacidade'] ?? '-') ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($turma['total_alunos']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $turma['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= $turma['ativo'] ? 'Ativa' : 'Inativa' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="editarTurma(<?= $turma['id'] ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                                            <button onclick="abrirModalExclusaoTurma(<?= $turma['id'] ?>, '<?= htmlspecialchars($turma['serie'] . ' ' . $turma['letra']) ?>')" class="text-red-600 hover:text-red-900">Excluir</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Séries -->
            <div id="tab-series" class="tab-content hidden">
                <!-- Listagem de Séries -->
                <div class="bg-white rounded-lg shadow-sm mb-6">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Séries Cadastradas</h2>
                        
                        <!-- Tabela de Séries -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Série</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total de Turmas</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($series)): ?>
                                    <tr>
                                        <td colspan="2" class="px-6 py-4 text-center text-gray-500">Nenhuma série cadastrada.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($series as $serie): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($serie['serie']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($serie['total_turmas']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Cadastro/Edição de Disciplina -->
    <div id="modalDisciplina" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="bg-primary-green text-white p-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold" id="modalDisciplinaTitulo">Nova Disciplina</h3>
                    <button onclick="fecharModalDisciplina()" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form id="formDisciplina" method="POST" class="p-6">
                <input type="hidden" name="acao" id="acaoDisciplina" value="cadastrar_disciplina">
                <input type="hidden" name="id" id="disciplinaId">
                
                <div class="space-y-4">
                    <div>
                        <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">Código</label>
                        <input type="text" id="codigo" name="codigo" 
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                    </div>
                    
                    <div>
                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">Nome *</label>
                        <input type="text" id="nome" name="nome" required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                    </div>
                    
                    <div>
                        <label for="carga_horaria" class="block text-sm font-medium text-gray-700 mb-2">Carga Horária (horas)</label>
                        <input type="number" id="carga_horaria" name="carga_horaria" min="0"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="fecharModalDisciplina()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Cadastro/Edição de Turma -->
    <div id="modalTurma" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="bg-primary-green text-white p-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold" id="modalTurmaTitulo">Nova Turma</h3>
                    <button onclick="fecharModalTurma()" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form id="formTurma" method="POST" class="p-6">
                <input type="hidden" name="acao" id="acaoTurma" value="cadastrar_turma">
                <input type="hidden" name="id" id="turmaId">
                
                <div class="space-y-4">
                    <div>
                        <label for="escola_id" class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                        <select id="escola_id" name="escola_id" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                            <option value="">Selecione uma escola</option>
                            <?php foreach ($escolas as $escola): ?>
                            <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="ano_letivo" class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo *</label>
                            <input type="number" id="ano_letivo" name="ano_letivo" value="<?= $anoAtual ?>" required min="2000" max="2100"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                        </div>
                        
                        <div>
                            <label for="serie" class="block text-sm font-medium text-gray-700 mb-2">Série *</label>
                            <input type="text" id="serie" name="serie" required placeholder="Ex: 1º Ano, 2º Ano, etc."
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="letra" class="block text-sm font-medium text-gray-700 mb-2">Letra *</label>
                            <input type="text" id="letra" name="letra" required maxlength="3" placeholder="Ex: A, B, C"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                        </div>
                        
                        <div>
                            <label for="turno" class="block text-sm font-medium text-gray-700 mb-2">Turno *</label>
                            <select id="turno" name="turno" required
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                                <option value="">Selecione o turno</option>
                                <option value="MANHA">Manhã</option>
                                <option value="TARDE">Tarde</option>
                                <option value="NOITE">Noite</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="capacidade" class="block text-sm font-medium text-gray-700 mb-2">Capacidade (alunos)</label>
                        <input type="number" id="capacidade" name="capacidade" min="1"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                    </div>
                    
                    <div id="campoAtivoTurma" class="hidden">
                        <label for="ativo" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="ativo" name="ativo"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                            <option value="1">Ativa</option>
                            <option value="0">Inativa</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="fecharModalTurma()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Função para alternar entre tabs
        function showTab(tabId) {
            // Esconder todas as tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remover classe ativa de todos os botões
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active', 'text-primary-green', 'border-primary-green');
                btn.classList.add('text-gray-500');
            });
            
            // Mostrar a tab selecionada
            document.getElementById(tabId).classList.remove('hidden');
            
            // Adicionar classe ativa ao botão clicado
            event.currentTarget.classList.add('tab-active', 'text-primary-green', 'border-primary-green');
            event.currentTarget.classList.remove('text-gray-500');
        }
        
        // Funções para modais de Disciplinas
        function abrirModalCadastroDisciplina() {
            document.getElementById('modalDisciplinaTitulo').textContent = 'Nova Disciplina';
            document.getElementById('acaoDisciplina').value = 'cadastrar_disciplina';
            document.getElementById('disciplinaId').value = '';
            document.getElementById('formDisciplina').reset();
            document.getElementById('modalDisciplina').classList.remove('hidden');
        }
        
        function fecharModalDisciplina() {
            document.getElementById('modalDisciplina').classList.add('hidden');
        }
        
        function editarDisciplina(id) {
            fetch(`../../Controllers/gestao/TurmaDisciplinaController.php?id=${id}&tipo=disciplina`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    document.getElementById('modalDisciplinaTitulo').textContent = 'Editar Disciplina';
                    document.getElementById('acaoDisciplina').value = 'editar_disciplina';
                    document.getElementById('disciplinaId').value = data.disciplina.id;
                    document.getElementById('codigo').value = data.disciplina.codigo || '';
                    document.getElementById('nome').value = data.disciplina.nome || '';
                    document.getElementById('carga_horaria').value = data.disciplina.carga_horaria || '';
                    document.getElementById('modalDisciplina').classList.remove('hidden');
                } else {
                    alert('Erro ao obter dados da disciplina: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('Erro ao obter dados da disciplina.');
            });
        }
        
        function abrirModalExclusaoDisciplina(id, nome) {
            if (confirm('Tem certeza que deseja excluir a disciplina "' + nome + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="acao" value="excluir_disciplina"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Funções para modais de Turmas
        function abrirModalCadastroTurma() {
            document.getElementById('modalTurmaTitulo').textContent = 'Nova Turma';
            document.getElementById('acaoTurma').value = 'cadastrar_turma';
            document.getElementById('turmaId').value = '';
            document.getElementById('campoAtivoTurma').classList.add('hidden');
            document.getElementById('formTurma').reset();
            document.getElementById('ano_letivo').value = <?= $anoAtual ?>;
            document.getElementById('modalTurma').classList.remove('hidden');
        }
        
        function fecharModalTurma() {
            document.getElementById('modalTurma').classList.add('hidden');
        }
        
        function editarTurma(id) {
            fetch(`../../Controllers/gestao/TurmaDisciplinaController.php?id=${id}&tipo=turma`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    document.getElementById('modalTurmaTitulo').textContent = 'Editar Turma';
                    document.getElementById('acaoTurma').value = 'editar_turma';
                    document.getElementById('turmaId').value = data.turma.id;
                    document.getElementById('escola_id').value = data.turma.escola_id;
                    document.getElementById('ano_letivo').value = data.turma.ano_letivo;
                    document.getElementById('serie').value = data.turma.serie || '';
                    document.getElementById('letra').value = data.turma.letra || '';
                    document.getElementById('turno').value = data.turma.turno || '';
                    document.getElementById('capacidade').value = data.turma.capacidade || '';
                    document.getElementById('ativo').value = data.turma.ativo;
                    document.getElementById('campoAtivoTurma').classList.remove('hidden');
                    document.getElementById('modalTurma').classList.remove('hidden');
                } else {
                    alert('Erro ao obter dados da turma: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('Erro ao obter dados da turma.');
            });
        }
        
        function abrirModalExclusaoTurma(id, nome) {
            if (confirm('Tem certeza que deseja excluir a turma "' + nome + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="acao" value="excluir_turma"><input type="hidden" name="id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

