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

// ==================== FUNÇÕES DE GERENCIAMENTO ====================

// Listar alunos
function listarAlunos($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT al.id, al.matricula, al.nis, al.data_matricula, al.ativo,
                   p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, p.sexo,
                   CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) as turma_nome
            FROM aluno al
            INNER JOIN pessoa p ON al.pessoa_id = p.id
            LEFT JOIN aluno_turma at ON al.id = at.aluno_id
            LEFT JOIN turma t ON at.turma_id = t.id
            WHERE 1=1";
    
    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR al.matricula LIKE :busca OR p.email LIKE :busca)";
    }
    
    $sql .= " ORDER BY p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $buscaParam = "%{$busca}%";
        $stmt->bindParam(':busca', $buscaParam);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar professores
function listarProfessores($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT pr.id, pr.matricula, pr.formacao, pr.data_admissao, pr.ativo,
                   p.nome, p.cpf, p.email, p.telefone, p.data_nascimento, p.sexo
            FROM professor pr
            INNER JOIN pessoa p ON pr.pessoa_id = p.id
            WHERE 1=1";
    
    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR pr.matricula LIKE :busca OR p.email LIKE :busca)";
    }
    
    $sql .= " ORDER BY p.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $buscaParam = "%{$busca}%";
        $stmt->bindParam(':busca', $buscaParam);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar turmas
function listarTurmas($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT t.id, t.serie, t.letra, t.ano_letivo, t.turno, t.capacidade,
                   e.nome as escola_nome, e.id as escola_id,
                   COUNT(DISTINCT at.aluno_id) as total_alunos
            FROM turma t
            LEFT JOIN escola e ON t.escola_id = e.id
            LEFT JOIN aluno_turma at ON t.id = at.turma_id
            WHERE 1=1";
    
    if (!empty($busca)) {
        $sql .= " AND (CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, '')) LIKE :busca 
                   OR e.nome LIKE :busca 
                   OR t.ano_letivo LIKE :busca)";
    }
    
    $sql .= " GROUP BY t.id, t.serie, t.letra, t.ano_letivo, t.turno, t.capacidade, e.nome, e.id
              ORDER BY t.serie ASC, t.letra ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $buscaParam = "%{$busca}%";
        $stmt->bindParam(':busca', $buscaParam);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar disciplinas
function listarDisciplinas($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT d.id, d.codigo, d.nome, d.carga_horaria,
                   COUNT(DISTINCT tp.turma_id) as total_turmas
            FROM disciplina d
            LEFT JOIN turma_professor tp ON d.id = tp.disciplina_id
            WHERE 1=1";
    
    if (!empty($busca)) {
        $sql .= " AND (d.nome LIKE :busca OR d.codigo LIKE :busca)";
    }
    
    $sql .= " GROUP BY d.id, d.codigo, d.nome, d.carga_horaria
              ORDER BY d.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $buscaParam = "%{$busca}%";
        $stmt->bindParam(':busca', $buscaParam);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar escolas
function listarEscolas($busca = '') {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT e.id, e.codigo, e.nome, e.endereco, e.municipio, e.cep, 
                   e.telefone, e.email, e.qtd_salas, e.obs,
                   COUNT(DISTINCT t.id) as total_turmas
            FROM escola e
            LEFT JOIN turma t ON e.id = t.escola_id
            WHERE 1=1";
    
    if (!empty($busca)) {
        $sql .= " AND (e.nome LIKE :busca OR e.codigo LIKE :busca OR e.municipio LIKE :busca)";
    }
    
    $sql .= " GROUP BY e.id, e.codigo, e.nome, e.endereco, e.municipio, e.cep, e.telefone, e.email, e.qtd_salas, e.obs
              ORDER BY e.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($busca)) {
        $buscaParam = "%{$busca}%";
        $stmt->bindParam(':busca', $buscaParam);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obter aluno por ID
function obterAluno($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT al.*, p.*
            FROM aluno al
            INNER JOIN pessoa p ON al.pessoa_id = p.id
            WHERE al.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obter professor por ID
function obterProfessor($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT pr.*, p.*
            FROM professor pr
            INNER JOIN pessoa p ON pr.pessoa_id = p.id
            WHERE pr.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obter turma por ID
function obterTurma($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT t.*, e.nome as escola_nome
            FROM turma t
            LEFT JOIN escola e ON t.escola_id = e.id
            WHERE t.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obter disciplina por ID
function obterDisciplina($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT * FROM disciplina WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obter escola por ID
function obterEscola($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT * FROM escola WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Excluir aluno
function excluirAluno($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Buscar pessoa_id
        $stmt = $conn->prepare("SELECT pessoa_id FROM aluno WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$aluno) {
            return ['status' => false, 'mensagem' => 'Aluno não encontrado.'];
        }
        
        $pessoaId = $aluno['pessoa_id'];
        
        // Excluir relacionamentos
        $stmt = $conn->prepare("DELETE FROM aluno_turma WHERE aluno_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Excluir aluno
        $stmt = $conn->prepare("DELETE FROM aluno WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Excluir pessoa (se não estiver sendo usada por outro registro)
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuario WHERE pessoa_id = :pessoa_id");
        $stmt->bindParam(':pessoa_id', $pessoaId);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado['total'] == 0) {
            $stmt = $conn->prepare("DELETE FROM pessoa WHERE id = :pessoa_id");
            $stmt->bindParam(':pessoa_id', $pessoaId);
            $stmt->execute();
        }
        
        $conn->commit();
        return ['status' => true, 'mensagem' => 'Aluno excluído com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao excluir aluno: ' . $e->getMessage()];
    }
}

// Excluir professor
function excluirProfessor($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Buscar pessoa_id
        $stmt = $conn->prepare("SELECT pessoa_id FROM professor WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $professor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$professor) {
            return ['status' => false, 'mensagem' => 'Professor não encontrado.'];
        }
        
        $pessoaId = $professor['pessoa_id'];
        
        // Excluir relacionamentos
        $stmt = $conn->prepare("DELETE FROM turma_professor WHERE professor_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Excluir professor
        $stmt = $conn->prepare("DELETE FROM professor WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Excluir pessoa (se não estiver sendo usada por outro registro)
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuario WHERE pessoa_id = :pessoa_id");
        $stmt->bindParam(':pessoa_id', $pessoaId);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado['total'] == 0) {
            $stmt = $conn->prepare("DELETE FROM pessoa WHERE id = :pessoa_id");
            $stmt->bindParam(':pessoa_id', $pessoaId);
            $stmt->execute();
        }
        
        $conn->commit();
        return ['status' => true, 'mensagem' => 'Professor excluído com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao excluir professor: ' . $e->getMessage()];
    }
}

// Excluir turma
function excluirTurma($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Excluir relacionamentos
        $stmt = $conn->prepare("DELETE FROM aluno_turma WHERE turma_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM turma_professor WHERE turma_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Excluir turma
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

// Excluir disciplina
function excluirDisciplina($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Excluir relacionamentos
        $stmt = $conn->prepare("DELETE FROM turma_professor WHERE disciplina_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Excluir disciplina
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

// Excluir escola
function excluirEscola($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Verificar se há turmas vinculadas
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM turma WHERE escola_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado['total'] > 0) {
            return ['status' => false, 'mensagem' => 'Não é possível excluir a escola pois existem turmas vinculadas a ela.'];
        }
        
        // Excluir escola
        $stmt = $conn->prepare("DELETE FROM escola WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $conn->commit();
        return ['status' => true, 'mensagem' => 'Escola excluída com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao excluir escola: ' . $e->getMessage()];
    }
}

// ==================== PROCESSAMENTO ====================

$mensagem = '';
$tipoMensagem = '';
$tipoSelecionado = $_GET['tipo'] ?? 'alunos';
$busca = $_GET['busca'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
        $tipo = $_POST['tipo'] ?? '';
        $id = $_POST['id'] ?? '';
        
        switch ($tipo) {
            case 'aluno':
                $resultado = excluirAluno($id);
                break;
            case 'professor':
                $resultado = excluirProfessor($id);
                break;
            case 'turma':
                $resultado = excluirTurma($id);
                break;
            case 'disciplina':
                $resultado = excluirDisciplina($id);
                break;
            case 'escola':
                $resultado = excluirEscola($id);
                break;
            default:
                $resultado = ['status' => false, 'mensagem' => 'Tipo inválido.'];
        }
        
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = $resultado['status'] ? 'success' : 'error';
    }
}

// Buscar dados
$dados = [];
switch ($tipoSelecionado) {
    case 'alunos':
        $dados = listarAlunos($busca);
        break;
    case 'professores':
        $dados = listarProfessores($busca);
        break;
    case 'turmas':
        $dados = listarTurmas($busca);
        break;
    case 'disciplinas':
        $dados = listarDisciplinas($busca);
        break;
    case 'escolas':
        $dados = listarEscolas($busca);
        break;
}

// Função para formatar CPF
function formatarCPF($cpf) {
    if (empty($cpf)) return '-';
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return $cpf;
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// Função para formatar telefone
function formatarTelefone($telefone) {
    if (empty($telefone)) return '-';
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7, 4);
    } elseif (strlen($telefone) == 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6, 4);
    }
    return $telefone;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Dados - SIGAE</title>
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
</head>
<body class="bg-gray-50">
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gerenciar Dados</h1>
                        <p class="text-sm text-gray-500 mt-1">Visualizar, editar e excluir dados do sistema</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
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
            <!-- Abas -->
            <div class="bg-white rounded-lg shadow-sm mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <a href="?tipo=alunos&busca=<?= urlencode($busca) ?>" 
                           class="px-6 py-4 text-sm font-medium <?= $tipoSelecionado === 'alunos' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Alunos
                        </a>
                        <a href="?tipo=professores&busca=<?= urlencode($busca) ?>" 
                           class="px-6 py-4 text-sm font-medium <?= $tipoSelecionado === 'professores' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Professores
                        </a>
                        <a href="?tipo=turmas&busca=<?= urlencode($busca) ?>" 
                           class="px-6 py-4 text-sm font-medium <?= $tipoSelecionado === 'turmas' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Turmas
                        </a>
                        <a href="?tipo=disciplinas&busca=<?= urlencode($busca) ?>" 
                           class="px-6 py-4 text-sm font-medium <?= $tipoSelecionado === 'disciplinas' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Disciplinas
                        </a>
                        <a href="?tipo=escolas&busca=<?= urlencode($busca) ?>" 
                           class="px-6 py-4 text-sm font-medium <?= $tipoSelecionado === 'escolas' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Escolas
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Busca -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <form method="GET" action="" class="flex items-center space-x-4">
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoSelecionado) ?>">
                    <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" 
                           placeholder="Buscar..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                    <button type="submit" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                        Buscar
                    </button>
                    <?php if (!empty($busca)): ?>
                    <a href="?tipo=<?= htmlspecialchars($tipoSelecionado) ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Limpar
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabela de Dados -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php if ($tipoSelecionado === 'alunos'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                <?php elseif ($tipoSelecionado === 'professores'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Formação</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                <?php elseif ($tipoSelecionado === 'turmas'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Escola</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano Letivo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turno</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alunos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                <?php elseif ($tipoSelecionado === 'disciplinas'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carga Horária</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turmas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                <?php elseif ($tipoSelecionado === 'escolas'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Município</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turmas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($dados)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Nenhum registro encontrado.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($dados as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <?php if ($tipoSelecionado === 'alunos'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= formatarCPF($item['cpf']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['matricula'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['turma_nome'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $item['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $item['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="gestao_usuarios.php?editar=<?= $item['id'] ?>" class="text-primary-green hover:text-green-700 mr-3">Editar</a>
                                    <button onclick="abrirModalExclusao('aluno', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nome']) ?>')" class="text-red-600 hover:text-red-900">Excluir</button>
                                </td>
                                <?php elseif ($tipoSelecionado === 'professores'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= formatarCPF($item['cpf']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['matricula'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['formacao'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $item['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $item['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="gestao_usuarios.php?editar=<?= $item['id'] ?>" class="text-primary-green hover:text-green-700 mr-3">Editar</a>
                                    <button onclick="abrirModalExclusao('professor', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nome']) ?>')" class="text-red-600 hover:text-red-900">Excluir</button>
                                </td>
                                <?php elseif ($tipoSelecionado === 'turmas'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars(($item['serie'] ?? '') . ' ' . ($item['letra'] ?? '')) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['escola_nome'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['ano_letivo'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['turno'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $item['total_alunos'] ?? 0 ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="gestao_turmas_disciplinas.php?editar_turma=<?= $item['id'] ?>" class="text-primary-green hover:text-green-700 mr-3">Editar</a>
                                    <button onclick="abrirModalExclusao('turma', <?= $item['id'] ?>, '<?= htmlspecialchars(($item['serie'] ?? '') . ' ' . ($item['letra'] ?? '')) ?>')" class="text-red-600 hover:text-red-900">Excluir</button>
                                </td>
                                <?php elseif ($tipoSelecionado === 'disciplinas'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['codigo'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($item['nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['carga_horaria'] ?? '-') ?>h</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $item['total_turmas'] ?? 0 ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="gestao_turmas_disciplinas.php?editar_disciplina=<?= $item['id'] ?>" class="text-primary-green hover:text-green-700 mr-3">Editar</a>
                                    <button onclick="abrirModalExclusao('disciplina', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nome']) ?>')" class="text-red-600 hover:text-red-900">Excluir</button>
                                </td>
                                <?php elseif ($tipoSelecionado === 'escolas'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($item['nome']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['codigo'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['municipio'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $item['total_turmas'] ?? 0 ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="gestao_escolas.php?editar=<?= $item['id'] ?>" class="text-primary-green hover:text-green-700 mr-3">Editar</a>
                                    <button onclick="abrirModalExclusao('escola', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nome']) ?>')" class="text-red-600 hover:text-red-900">Excluir</button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Exclusão -->
    <div id="modalExclusao" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="bg-red-600 text-white p-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold">Confirmar Exclusão</h3>
                    <button onclick="fecharModalExclusao()" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <form id="formExclusao" method="POST" class="p-6">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="tipo" id="exclusaoTipo">
                <input type="hidden" name="id" id="exclusaoId">
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">Tem certeza que deseja excluir o seguinte registro?</p>
                    <p class="text-sm font-medium text-gray-900" id="exclusaoNome"></p>
                    <p class="text-xs text-red-600 mt-2">Esta ação não pode ser desfeita!</p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="fecharModalExclusao()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Excluir
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalExclusao(tipo, id, nome) {
            document.getElementById('exclusaoTipo').value = tipo;
            document.getElementById('exclusaoId').value = id;
            document.getElementById('exclusaoNome').textContent = nome;
            document.getElementById('modalExclusao').classList.remove('hidden');
        }
        
        function fecharModalExclusao() {
            document.getElementById('modalExclusao').classList.add('hidden');
        }
    </script>
</body>
</html>

