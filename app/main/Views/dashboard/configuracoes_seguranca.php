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

// ==================== FUNÇÕES DE CONFIGURAÇÃO ====================

// Criar tabela de configurações se não existir
function criarTabelaConfiguracoes() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `configuracao` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `chave` varchar(100) NOT NULL,
        `valor` text DEFAULT NULL,
        `tipo` enum('STRING','INTEGER','BOOLEAN','JSON') DEFAULT 'STRING',
        `categoria` varchar(50) DEFAULT 'GERAL',
        `descricao` text DEFAULT NULL,
        `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `atualizado_por` bigint(20) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `chave_unique` (`chave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    try {
        $conn->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela de configurações: " . $e->getMessage());
        return false;
    }
}

// Criar tabela de logs se não existir
function criarTabelaLogs() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `log_sistema` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `usuario_id` bigint(20) DEFAULT NULL,
        `acao` varchar(100) NOT NULL,
        `tipo` enum('INFO','WARNING','ERROR','SECURITY') DEFAULT 'INFO',
        `descricao` text DEFAULT NULL,
        `ip` varchar(45) DEFAULT NULL,
        `user_agent` text DEFAULT NULL,
        `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `usuario_id` (`usuario_id`),
        KEY `tipo` (`tipo`),
        KEY `criado_em` (`criado_em`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    try {
        $conn->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela de logs: " . $e->getMessage());
        return false;
    }
}

// Obter configuração
function obterConfiguracao($chave, $valorPadrao = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT valor, tipo FROM configuracao WHERE chave = :chave";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':chave', $chave);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        $valor = $resultado['valor'];
        $tipo = $resultado['tipo'];
        
        switch ($tipo) {
            case 'BOOLEAN':
                return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
            case 'INTEGER':
                return intval($valor);
            case 'JSON':
                return json_decode($valor, true);
            default:
                return $valor;
        }
    }
    
    return $valorPadrao;
}

// Salvar configuração
function salvarConfiguracao($chave, $valor, $tipo = 'STRING', $categoria = 'GERAL', $descricao = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Converter valor conforme o tipo
        $valorFinal = $valor;
        if ($tipo === 'BOOLEAN') {
            $valorFinal = $valor ? '1' : '0';
        } elseif ($tipo === 'INTEGER') {
            $valorFinal = strval(intval($valor));
        } elseif ($tipo === 'JSON') {
            $valorFinal = json_encode($valor);
        }
        
        // Verificar se já existe
        $stmt = $conn->prepare("SELECT id FROM configuracao WHERE chave = :chave");
        $stmt->bindParam(':chave', $chave);
        $stmt->execute();
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existe) {
            // Atualizar
            $stmt = $conn->prepare("UPDATE configuracao SET 
                                    valor = :valor, 
                                    tipo = :tipo, 
                                    categoria = :categoria, 
                                    descricao = :descricao,
                                    atualizado_por = :atualizado_por
                                    WHERE chave = :chave");
        } else {
            // Inserir
            $stmt = $conn->prepare("INSERT INTO configuracao (chave, valor, tipo, categoria, descricao, atualizado_por) 
                                    VALUES (:chave, :valor, :tipo, :categoria, :descricao, :atualizado_por)");
        }
        
        $stmt->bindParam(':chave', $chave);
        $stmt->bindParam(':valor', $valorFinal);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':atualizado_por', $_SESSION['usuario_id']);
        $stmt->execute();
        
        return ['status' => true, 'mensagem' => 'Configuração salva com sucesso!'];
    } catch (PDOException $e) {
        return ['status' => false, 'mensagem' => 'Erro ao salvar configuração: ' . $e->getMessage()];
    }
}

// Listar logs
function listarLogs($tipo = null, $limit = 100) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT l.*, p.nome as usuario_nome
            FROM log_sistema l
            LEFT JOIN usuario u ON l.usuario_id = u.id
            LEFT JOIN pessoa p ON u.pessoa_id = p.id
            WHERE 1=1";
    
    if ($tipo) {
        $sql .= " AND l.tipo = :tipo";
    }
    
    $sql .= " ORDER BY l.criado_em DESC LIMIT " . intval($limit);
    
    $stmt = $conn->prepare($sql);
    
    if ($tipo) {
        $stmt->bindParam(':tipo', $tipo);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Registrar log
function registrarLog($acao, $tipo = 'INFO', $descricao = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $sql = "INSERT INTO log_sistema (usuario_id, acao, tipo, descricao, ip, user_agent) 
                VALUES (:usuario_id, :acao, :tipo, :descricao, :ip, :user_agent)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
        $stmt->bindParam(':acao', $acao);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR'] ?? null);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
        $stmt->execute();
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
        return false;
    }
}

// Obter estatísticas de segurança
function obterEstatisticasSeguranca() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $estatisticas = [];
    
    try {
        // Total de usuários ativos
        $stmt = $conn->query("SELECT COUNT(*) as total FROM usuario WHERE ativo = 1");
        $estatisticas['usuarios_ativos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de tentativas de login falhadas (últimas 24h)
        $stmt = $conn->query("SELECT COUNT(*) as total FROM log_sistema 
                              WHERE acao = 'LOGIN_FALHADO' 
                              AND criado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $estatisticas['tentativas_falhadas_24h'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de logs de segurança (últimas 24h)
        $stmt = $conn->query("SELECT COUNT(*) as total FROM log_sistema 
                              WHERE tipo = 'SECURITY' 
                              AND criado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $estatisticas['eventos_seguranca_24h'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de erros (últimas 24h)
        $stmt = $conn->query("SELECT COUNT(*) as total FROM log_sistema 
                              WHERE tipo = 'ERROR' 
                              AND criado_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $estatisticas['erros_24h'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Último backup
        $ultimoBackup = obterConfiguracao('ultimo_backup', null);
        $estatisticas['ultimo_backup'] = $ultimoBackup;
        
    } catch (PDOException $e) {
        error_log("Erro ao obter estatísticas: " . $e->getMessage());
    }
    
    return $estatisticas;
}

// ==================== PROCESSAMENTO ====================

// Criar tabelas se não existirem
criarTabelaConfiguracoes();
criarTabelaLogs();

$mensagem = '';
$tipoMensagem = '';
$abaSelecionada = $_GET['aba'] ?? 'geral';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'salvar_configuracao') {
        $chave = $_POST['chave'] ?? '';
        $valor = $_POST['valor'] ?? '';
        $tipo = $_POST['tipo'] ?? 'STRING';
        $categoria = $_POST['categoria'] ?? 'GERAL';
        $descricao = $_POST['descricao'] ?? null;
        
        $resultado = salvarConfiguracao($chave, $valor, $tipo, $categoria, $descricao);
        $mensagem = $resultado['mensagem'];
        $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        
        if ($resultado['status']) {
            registrarLog('CONFIGURACAO_ALTERADA', 'INFO', "Configuração '{$chave}' alterada");
        }
    }
}

// Obter configurações atuais
$configuracoes = [
    'nome_sistema' => obterConfiguracao('nome_sistema', 'SIGAE - Sistema de Gestão e Alimentação Escolar'),
    'email_sistema' => obterConfiguracao('email_sistema', 'contato@sigae.com.br'),
    'telefone_sistema' => obterConfiguracao('telefone_sistema', ''),
    'timeout_sessao' => obterConfiguracao('timeout_sessao', 3600),
    'tentativas_login' => obterConfiguracao('tentativas_login', 5),
    'bloqueio_temporario' => obterConfiguracao('bloqueio_temporario', 1800),
    'senha_minima' => obterConfiguracao('senha_minima', 8),
    'senha_maiuscula' => obterConfiguracao('senha_maiuscula', true),
    'senha_minuscula' => obterConfiguracao('senha_minuscula', true),
    'senha_numero' => obterConfiguracao('senha_numero', true),
    'senha_especial' => obterConfiguracao('senha_especial', false),
    'backup_automatico' => obterConfiguracao('backup_automatico', false),
    'backup_frequencia' => obterConfiguracao('backup_frequencia', 'DIARIO'),
    'log_ativado' => obterConfiguracao('log_ativado', true),
    'log_nivel' => obterConfiguracao('log_nivel', 'INFO'),
];

// Obter estatísticas
$estatisticas = obterEstatisticasSeguranca();

// Obter logs
$tipoLog = $_GET['tipo_log'] ?? null;
$logs = listarLogs($tipoLog, 100);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações e Segurança - SIGAE</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Configurações e Segurança</h1>
                        <p class="text-sm text-gray-500 mt-1">Gerenciar configurações do sistema e segurança</p>
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
                        <a href="?aba=geral" 
                           class="px-6 py-4 text-sm font-medium <?= $abaSelecionada === 'geral' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Geral
                        </a>
                        <a href="?aba=seguranca" 
                           class="px-6 py-4 text-sm font-medium <?= $abaSelecionada === 'seguranca' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Segurança
                        </a>
                        <a href="?aba=senhas" 
                           class="px-6 py-4 text-sm font-medium <?= $abaSelecionada === 'senhas' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Políticas de Senha
                        </a>
                        <a href="?aba=backup" 
                           class="px-6 py-4 text-sm font-medium <?= $abaSelecionada === 'backup' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Backup
                        </a>
                        <a href="?aba=logs" 
                           class="px-6 py-4 text-sm font-medium <?= $abaSelecionada === 'logs' ? 'border-b-2 border-primary-green text-primary-green' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                            Logs
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Conteúdo das Abas -->
            <?php if ($abaSelecionada === 'geral'): ?>
            <!-- Configurações Gerais -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Configurações Gerais do Sistema</h2>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="acao" value="salvar_configuracao">
                    <input type="hidden" name="categoria" value="GERAL">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nome_sistema" class="block text-sm font-medium text-gray-700 mb-2">Nome do Sistema</label>
                            <input type="text" id="nome_sistema" name="valor" value="<?= htmlspecialchars($configuracoes['nome_sistema']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                            <input type="hidden" name="chave" value="nome_sistema">
                            <input type="hidden" name="tipo" value="STRING">
                            <p class="mt-1 text-sm text-gray-500">Nome exibido no sistema</p>
                        </div>
                        
                        <div>
                            <label for="email_sistema" class="block text-sm font-medium text-gray-700 mb-2">E-mail do Sistema</label>
                            <input type="email" id="email_sistema" name="valor" value="<?= htmlspecialchars($configuracoes['email_sistema']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                            <input type="hidden" name="chave" value="email_sistema">
                            <input type="hidden" name="tipo" value="STRING">
                            <p class="mt-1 text-sm text-gray-500">E-mail de contato do sistema</p>
                        </div>
                        
                        <div>
                            <label for="telefone_sistema" class="block text-sm font-medium text-gray-700 mb-2">Telefone do Sistema</label>
                            <input type="text" id="telefone_sistema" name="valor" value="<?= htmlspecialchars($configuracoes['telefone_sistema']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                            <input type="hidden" name="chave" value="telefone_sistema">
                            <input type="hidden" name="tipo" value="STRING">
                            <p class="mt-1 text-sm text-gray-500">Telefone de contato do sistema</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($abaSelecionada === 'seguranca'): ?>
            <!-- Configurações de Segurança -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Configurações de Segurança</h2>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="acao" value="salvar_configuracao">
                    <input type="hidden" name="categoria" value="SEGURANCA">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="timeout_sessao" class="block text-sm font-medium text-gray-700 mb-2">Timeout de Sessão (segundos)</label>
                            <input type="number" id="timeout_sessao" name="valor" value="<?= htmlspecialchars($configuracoes['timeout_sessao']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                            <input type="hidden" name="chave" value="timeout_sessao">
                            <input type="hidden" name="tipo" value="INTEGER">
                            <p class="mt-1 text-sm text-gray-500">Tempo de inatividade antes de expirar a sessão</p>
                        </div>
                        
                        <div>
                            <label for="tentativas_login" class="block text-sm font-medium text-gray-700 mb-2">Tentativas de Login Permitidas</label>
                            <input type="number" id="tentativas_login" name="valor" value="<?= htmlspecialchars($configuracoes['tentativas_login']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                            <input type="hidden" name="chave" value="tentativas_login">
                            <input type="hidden" name="tipo" value="INTEGER">
                            <p class="mt-1 text-sm text-gray-500">Número máximo de tentativas de login falhadas</p>
                        </div>
                        
                        <div>
                            <label for="bloqueio_temporario" class="block text-sm font-medium text-gray-700 mb-2">Bloqueio Temporário (segundos)</label>
                            <input type="number" id="bloqueio_temporario" name="valor" value="<?= htmlspecialchars($configuracoes['bloqueio_temporario']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                            <input type="hidden" name="chave" value="bloqueio_temporario">
                            <input type="hidden" name="tipo" value="INTEGER">
                            <p class="mt-1 text-sm text-gray-500">Tempo de bloqueio após tentativas falhadas</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Estatísticas de Segurança -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Estatísticas de Segurança</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-sm text-blue-600 font-medium">Usuários Ativos</div>
                        <div class="text-2xl font-bold text-blue-900"><?= $estatisticas['usuarios_ativos'] ?? 0 ?></div>
                    </div>
                    
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="text-sm text-yellow-600 font-medium">Tentativas Falhadas (24h)</div>
                        <div class="text-2xl font-bold text-yellow-900"><?= $estatisticas['tentativas_falhadas_24h'] ?? 0 ?></div>
                    </div>
                    
                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="text-sm text-red-600 font-medium">Eventos de Segurança (24h)</div>
                        <div class="text-2xl font-bold text-red-900"><?= $estatisticas['eventos_seguranca_24h'] ?? 0 ?></div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-600 font-medium">Erros (24h)</div>
                        <div class="text-2xl font-bold text-gray-900"><?= $estatisticas['erros_24h'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($abaSelecionada === 'senhas'): ?>
            <!-- Políticas de Senha -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Políticas de Senha</h2>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="acao" value="salvar_configuracao">
                    <input type="hidden" name="categoria" value="SENHA">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="senha_minima" class="block text-sm font-medium text-gray-700 mb-2">Tamanho Mínimo da Senha</label>
                            <input type="number" id="senha_minima" name="valor" value="<?= htmlspecialchars($configuracoes['senha_minima']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                            <input type="hidden" name="chave" value="senha_minima">
                            <input type="hidden" name="tipo" value="INTEGER">
                            <p class="mt-1 text-sm text-gray-500">Número mínimo de caracteres</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="senha_maiuscula" <?= $configuracoes['senha_maiuscula'] ? 'checked' : '' ?> 
                                       class="h-4 w-4 text-primary-green focus:ring-primary-green border-gray-300 rounded"
                                       onchange="salvarCheckbox('senha_maiuscula', this.checked)">
                                <label for="senha_maiuscula" class="ml-2 block text-sm text-gray-700">Requer letra maiúscula</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="senha_minuscula" <?= $configuracoes['senha_minuscula'] ? 'checked' : '' ?> 
                                       class="h-4 w-4 text-primary-green focus:ring-primary-green border-gray-300 rounded"
                                       onchange="salvarCheckbox('senha_minuscula', this.checked)">
                                <label for="senha_minuscula" class="ml-2 block text-sm text-gray-700">Requer letra minúscula</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="senha_numero" <?= $configuracoes['senha_numero'] ? 'checked' : '' ?> 
                                       class="h-4 w-4 text-primary-green focus:ring-primary-green border-gray-300 rounded"
                                       onchange="salvarCheckbox('senha_numero', this.checked)">
                                <label for="senha_numero" class="ml-2 block text-sm text-gray-700">Requer número</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="senha_especial" <?= $configuracoes['senha_especial'] ? 'checked' : '' ?> 
                                       class="h-4 w-4 text-primary-green focus:ring-primary-green border-gray-300 rounded"
                                       onchange="salvarCheckbox('senha_especial', this.checked)">
                                <label for="senha_especial" class="ml-2 block text-sm text-gray-700">Requer caractere especial</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($abaSelecionada === 'backup'): ?>
            <!-- Configurações de Backup -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Configurações de Backup</h2>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="acao" value="salvar_configuracao">
                    <input type="hidden" name="categoria" value="BACKUP">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <input type="checkbox" id="backup_automatico" <?= $configuracoes['backup_automatico'] ? 'checked' : '' ?> 
                                   class="h-4 w-4 text-primary-green focus:ring-primary-green border-gray-300 rounded"
                                   onchange="salvarCheckbox('backup_automatico', this.checked)">
                            <label for="backup_automatico" class="ml-2 block text-sm text-gray-700">Backup Automático</label>
                        </div>
                        
                        <div>
                            <label for="backup_frequencia" class="block text-sm font-medium text-gray-700 mb-2">Frequência do Backup</label>
                            <select id="backup_frequencia" name="valor" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-green">
                                <option value="DIARIO" <?= $configuracoes['backup_frequencia'] === 'DIARIO' ? 'selected' : '' ?>>Diário</option>
                                <option value="SEMANAL" <?= $configuracoes['backup_frequencia'] === 'SEMANAL' ? 'selected' : '' ?>>Semanal</option>
                                <option value="MENSAL" <?= $configuracoes['backup_frequencia'] === 'MENSAL' ? 'selected' : '' ?>>Mensal</option>
                            </select>
                            <input type="hidden" name="chave" value="backup_frequencia">
                            <input type="hidden" name="tipo" value="STRING">
                        </div>
                    </div>
                    
                    <?php if ($estatisticas['ultimo_backup']): ?>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Último Backup: <span class="font-medium"><?= date('d/m/Y H:i', strtotime($estatisticas['ultimo_backup'])) ?></span></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="alert('Funcionalidade de backup manual será implementada')" 
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Fazer Backup Agora
                        </button>
                        <button type="submit" class="px-6 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700">
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($abaSelecionada === 'logs'): ?>
            <!-- Logs do Sistema -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Logs do Sistema</h2>
                    <div class="flex space-x-2">
                        <a href="?aba=logs" class="px-3 py-1 rounded-lg <?= !$tipoLog ? 'bg-primary-green text-white' : 'bg-gray-100 text-gray-700' ?>">Todos</a>
                        <a href="?aba=logs&tipo_log=INFO" class="px-3 py-1 rounded-lg <?= $tipoLog === 'INFO' ? 'bg-primary-green text-white' : 'bg-gray-100 text-gray-700' ?>">Info</a>
                        <a href="?aba=logs&tipo_log=WARNING" class="px-3 py-1 rounded-lg <?= $tipoLog === 'WARNING' ? 'bg-primary-green text-white' : 'bg-gray-100 text-gray-700' ?>">Aviso</a>
                        <a href="?aba=logs&tipo_log=ERROR" class="px-3 py-1 rounded-lg <?= $tipoLog === 'ERROR' ? 'bg-primary-green text-white' : 'bg-gray-100 text-gray-700' ?>">Erro</a>
                        <a href="?aba=logs&tipo_log=SECURITY" class="px-3 py-1 rounded-lg <?= $tipoLog === 'SECURITY' ? 'bg-primary-green text-white' : 'bg-gray-100 text-gray-700' ?>">Segurança</a>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Hora</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum log encontrado.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('d/m/Y H:i:s', strtotime($log['criado_em'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($log['usuario_nome'] ?? 'Sistema') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= 
                                        $log['tipo'] === 'ERROR' ? 'bg-red-100 text-red-800' : 
                                        ($log['tipo'] === 'WARNING' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($log['tipo'] === 'SECURITY' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'))
                                    ?>">
                                        <?= htmlspecialchars($log['tipo']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($log['acao']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= htmlspecialchars($log['descricao'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($log['ip'] ?? '-') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function salvarCheckbox(chave, valor) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const acao = document.createElement('input');
            acao.type = 'hidden';
            acao.name = 'acao';
            acao.value = 'salvar_configuracao';
            form.appendChild(acao);
            
            const chaveInput = document.createElement('input');
            chaveInput.type = 'hidden';
            chaveInput.name = 'chave';
            chaveInput.value = chave;
            form.appendChild(chaveInput);
            
            const valorInput = document.createElement('input');
            valorInput.type = 'hidden';
            valorInput.name = 'valor';
            valorInput.value = valor ? '1' : '0';
            form.appendChild(valorInput);
            
            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo';
            tipoInput.value = 'BOOLEAN';
            form.appendChild(tipoInput);
            
            const categoriaInput = document.createElement('input');
            categoriaInput.type = 'hidden';
            categoriaInput.name = 'categoria';
            categoriaInput.value = chave.startsWith('senha_') ? 'SENHA' : 'BACKUP';
            form.appendChild(categoriaInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>

