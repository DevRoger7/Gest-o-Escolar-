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

// ==================== FUNÇÕES PARA PERMISSÕES ====================

// Criar tabela de permissões se não existir
function criarTabelaPermissoes() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `role_permissao` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA') NOT NULL,
        `permissao` varchar(100) NOT NULL,
        `ativo` tinyint(1) DEFAULT 1,
        `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
        `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `role_permissao_unique` (`role`, `permissao`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    try {
        $conn->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela de permissões: " . $e->getMessage());
        return false;
    }
}

// Inicializar permissões padrão
function inicializarPermissoesPadrao() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $permissoesPadrao = [
        'ADM' => [
            'cadastrar_pessoas',
            'gerenciar_escolas',
            'gerenciar_professores',
            'relatorio_geral',
            'gerenciar_estoque_produtos',
            'pedidos_nutricionista',
            'definir_permissoes'
        ],
        'GESTAO' => [
            'criar_turma',
            'matricular_alunos',
            'gerenciar_professores',
            'acessar_registros',
            'gerar_relatorios_pedagogicos'
        ],
        'PROFESSOR' => [
            'resgistrar_plano_aula',
            'cadastrar_avaliacao',
            'lancar_frequencia',
            'lancar_nota',
            'justificar_faltas'
        ],
        'NUTRICIONISTA' => [
            'adc_cardapio',
            'lista_insulmos',
            'env_pedidos'
        ],
        'ADM_MERENDA' => [
            'gerenciar_estoque_produtos',
            'criar_pacotes/cestas',
            'pedidos_nutricionista',
            'movimentacoes_estoque'
        ],
        'ALUNO' => [
            'notas',
            'frequencia',
            'comunicados'
        ]
    ];
    
    try {
        $conn->beginTransaction();
        
        foreach ($permissoesPadrao as $role => $permissoes) {
            foreach ($permissoes as $permissao) {
                // Verificar se já existe
                $stmt = $conn->prepare("SELECT id FROM role_permissao WHERE role = :role AND permissao = :permissao");
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':permissao', $permissao);
                $stmt->execute();
                
                if ($stmt->rowCount() == 0) {
                    // Inserir permissão padrão
                    $stmt = $conn->prepare("INSERT INTO role_permissao (role, permissao, ativo) VALUES (:role, :permissao, 1)");
                    $stmt->bindParam(':role', $role);
                    $stmt->bindParam(':permissao', $permissao);
                    $stmt->execute();
                }
            }
        }
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Erro ao inicializar permissões padrão: " . $e->getMessage());
        return false;
    }
}

// Listar permissões por role
function listarPermissoesPorRole($role) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT id, permissao, ativo 
            FROM role_permissao 
            WHERE role = :role 
            ORDER BY permissao ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':role', $role);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar todas as permissões disponíveis organizadas por categoria
function listarTodasPermissoes() {
    return [
        'Administração' => [
            'cadastrar_pessoas' => 'Cadastrar/Editar/Excluir Usuários',
            'gerenciar_escolas' => 'Gerenciar Escolas',
            'gerenciar_professores' => 'Gerenciar Professores',
            'relatorio_geral' => 'Acesso a Relatórios Gerais',
            'definir_permissoes' => 'Definir Permissões do Sistema'
        ],
        'Gestão Escolar' => [
            'criar_turma' => 'Criar Turmas',
            'matricular_alunos' => 'Matricular Alunos',
            'acessar_registros' => 'Acessar Registros dos Professores',
            'gerar_relatorios_pedagogicos' => 'Gerar Relatórios Pedagógicos'
        ],
        'Atividades Pedagógicas' => [
            'resgistrar_plano_aula' => 'Registrar Plano de Aula',
            'cadastrar_avaliacao' => 'Cadastrar Avaliações',
            'lancar_frequencia' => 'Lançar Frequência',
            'lancar_nota' => 'Lançar Notas',
            'justificar_faltas' => 'Justificar Faltas'
        ],
        'Alimentação Escolar' => [
            'gerenciar_estoque_produtos' => 'Gerenciar Estoque de Produtos',
            'pedidos_nutricionista' => 'Gerenciar Pedidos de Nutricionistas',
            'adc_cardapio' => 'Adicionar Cardápios',
            'lista_insulmos' => 'Gerar Lista de Insumos',
            'env_pedidos' => 'Enviar Pedidos',
            'criar_pacotes/cestas' => 'Criar Pacotes/Cestas',
            'movimentacoes_estoque' => 'Registrar Movimentações de Estoque'
        ],
        'Consulta' => [
            'notas' => 'Visualizar Notas',
            'frequencia' => 'Visualizar Frequência',
            'comunicados' => 'Visualizar Comunicados'
        ]
    ];
}

// Salvar permissões de um role
function salvarPermissoes($role, $permissoes) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Desativar todas as permissões do role
        $stmt = $conn->prepare("UPDATE role_permissao SET ativo = 0 WHERE role = :role");
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        // Ativar as permissões selecionadas
        foreach ($permissoes as $permissao) {
            // Verificar se já existe
            $stmt = $conn->prepare("SELECT id FROM role_permissao WHERE role = :role AND permissao = :permissao");
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':permissao', $permissao);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Atualizar
                $stmt = $conn->prepare("UPDATE role_permissao SET ativo = 1 WHERE role = :role AND permissao = :permissao");
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':permissao', $permissao);
                $stmt->execute();
            } else {
                // Inserir nova
                $stmt = $conn->prepare("INSERT INTO role_permissao (role, permissao, ativo) VALUES (:role, :permissao, 1)");
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':permissao', $permissao);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        return ['status' => true, 'mensagem' => 'Permissões salvas com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao salvar permissões: ' . $e->getMessage()];
    }
}

// ==================== PROCESSAMENTO ====================

// Criar tabela se não existir
criarTabelaPermissoes();

// Inicializar permissões padrão se necessário
if (isset($_GET['inicializar']) && $_GET['inicializar'] == '1') {
    inicializarPermissoesPadrao();
}

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'salvar_permissoes') {
        $role = $_POST['role'] ?? '';
        $permissoes = $_POST['permissoes'] ?? [];
        
        $resultado = salvarPermissoes($role, $permissoes);
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = $resultado['status'] ? 'success' : 'error';
    }
}

// Listar roles disponíveis
$roles = [
    'ADM' => 'Administrador Geral',
    'GESTAO' => 'Gestão',
    'PROFESSOR' => 'Professor',
    'NUTRICIONISTA' => 'Nutricionista',
    'ADM_MERENDA' => 'Administrador de Merenda',
    'ALUNO' => 'Aluno'
];

$roleSelecionado = $_GET['role'] ?? 'ADM';
$permissoesRole = listarPermissoesPorRole($roleSelecionado);
$todasPermissoes = listarTodasPermissoes();

// Criar array de permissões ativas para facilitar verificação
$permissoesAtivas = [];
foreach ($permissoesRole as $perm) {
    if ($perm['ativo']) {
        $permissoesAtivas[] = $perm['permissao'];
    }
}

// Criar array plano de todas as permissões para facilitar iteração
$todasPermissoesPlano = [];
foreach ($todasPermissoes as $categoria => $permissoes) {
    foreach ($permissoes as $key => $nome) {
        $todasPermissoesPlano[$key] = $nome;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Permissões - SIGAE</title>
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
                    <h1 class="text-2xl font-bold text-gray-900">Gestão de Permissões</h1>
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
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Selecione o Tipo de Usuário</h2>
                        <a href="?inicializar=1&role=<?= $roleSelecionado ?>" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                           onclick="return confirm('Deseja inicializar as permissões padrão? Isso irá adicionar as permissões padrão para todos os tipos de usuário.');">
                            Inicializar Permissões Padrão
                        </a>
                    </div>
                    
                    <!-- Seletor de Role -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <?php foreach ($roles as $roleKey => $roleNome): ?>
                        <a href="?role=<?= $roleKey ?>" 
                           class="p-4 border-2 rounded-lg text-center transition-all duration-200 <?= $roleSelecionado === $roleKey ? 'border-primary-green bg-green-50 text-primary-green font-semibold' : 'border-gray-200 hover:border-gray-300 text-gray-700' ?>">
                            <?= htmlspecialchars($roleNome) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Formulário de Permissões -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Permissões para: <span class="text-primary-green"><?= htmlspecialchars($roles[$roleSelecionado]) ?></span>
                    </h3>
                    
                    <form method="POST" id="formPermissoes">
                        <input type="hidden" name="acao" value="salvar_permissoes">
                        <input type="hidden" name="role" value="<?= htmlspecialchars($roleSelecionado) ?>">
                        
                        <?php foreach ($todasPermissoes as $categoria => $permissoes): ?>
                        <div class="mb-8">
                            <h4 class="text-md font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                                <?= htmlspecialchars($categoria) ?>
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($permissoes as $permissaoKey => $permissaoNome): ?>
                                <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <input type="checkbox" 
                                           name="permissoes[]" 
                                           value="<?= htmlspecialchars($permissaoKey) ?>" 
                                           id="perm_<?= htmlspecialchars($permissaoKey) ?>"
                                           <?= in_array($permissaoKey, $permissoesAtivas) ? 'checked' : '' ?>
                                           class="w-5 h-5 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                    <label for="perm_<?= htmlspecialchars($permissaoKey) ?>" class="ml-3 text-sm text-gray-700 cursor-pointer flex-1">
                                        <?= htmlspecialchars($permissaoNome) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200">
                            <a href="dashboard.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                Cancelar
                            </a>
                            <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                                Salvar Permissões
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

