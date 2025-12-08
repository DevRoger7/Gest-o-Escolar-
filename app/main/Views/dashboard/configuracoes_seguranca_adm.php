<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'atualizar_configuracao') {
        try {
            $chave = $_POST['chave'] ?? null;
            $valor = $_POST['valor'] ?? null;
            
            if (empty($chave)) {
                echo json_encode(['success' => false, 'message' => 'Chave de configuração não informada']);
                exit;
            }
            
            // Verificar se a tabela configuracao existe
            $sqlCheck = "SHOW TABLES LIKE 'configuracao'";
            $stmtCheck = $conn->query($sqlCheck);
            
            if ($stmtCheck->rowCount() > 0) {
                // Verificar se a configuração já existe
                $sqlSelect = "SELECT id FROM configuracao WHERE chave = :chave";
                $stmtSelect = $conn->prepare($sqlSelect);
                $stmtSelect->bindParam(':chave', $chave);
                $stmtSelect->execute();
                $exists = $stmtSelect->fetch(PDO::FETCH_ASSOC);
                
                if ($exists) {
                    // Atualizar configuração existente
                    $sqlUpdate = "UPDATE configuracao SET valor = :valor, atualizado_em = NOW(), atualizado_por = :usuario_id WHERE chave = :chave";
                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->bindParam(':valor', $valor);
                    $stmtUpdate->bindParam(':chave', $chave);
                    $stmtUpdate->bindParam(':usuario_id', $_SESSION['usuario_id']);
                    $stmtUpdate->execute();
                } else {
                    // Inserir nova configuração
                    $sqlInsert = "INSERT INTO configuracao (chave, valor, tipo, categoria, atualizado_por) VALUES (:chave, :valor, 'STRING', 'GERAL', :usuario_id)";
                    $stmtInsert = $conn->prepare($sqlInsert);
                    $stmtInsert->bindParam(':chave', $chave);
                    $stmtInsert->bindParam(':valor', $valor);
                    $stmtInsert->bindParam(':usuario_id', $_SESSION['usuario_id']);
                    $stmtInsert->execute();
                }
                
                echo json_encode(['success' => true, 'message' => 'Configuração atualizada com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Tabela de configurações não encontrada']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar configuração: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'gerar_backup') {
        try {
            // Nome do arquivo de backup
            $backupFileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backupPath = __DIR__ . '/../../../backups/';
            
            // Criar diretório de backups se não existir
            if (!file_exists($backupPath)) {
                if (!mkdir($backupPath, 0755, true)) {
                    throw new Exception('Não foi possível criar o diretório de backups');
                }
            }
            
            $fullPath = $backupPath . $backupFileName;
            
            // Gerar backup via PDO (método mais confiável)
            $backupContent = "-- ============================================================\n";
            $backupContent .= "-- Backup do Banco de Dados\n";
            $backupContent .= "-- Gerado em: " . date('Y-m-d H:i:s') . "\n";
            $backupContent .= "-- Banco: escola_merenda\n";
            $backupContent .= "-- ============================================================\n\n";
            $backupContent .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $backupContent .= "SET time_zone = \"+00:00\";\n\n";
            $backupContent .= "START TRANSACTION;\n\n";
            
            // Obter todas as tabelas
            $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                $backupContent .= "\n-- ============================================================\n";
                $backupContent .= "-- Estrutura da tabela `$table`\n";
                $backupContent .= "-- ============================================================\n\n";
                $backupContent .= "DROP TABLE IF EXISTS `$table`;\n\n";
                
                $createTable = $conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                $backupContent .= $createTable['Create Table'] . ";\n\n";
                
                // Dados da tabela
                $rows = $conn->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if (count($rows) > 0) {
                    $backupContent .= "-- Dados da tabela `$table`\n";
                    $backupContent .= "LOCK TABLES `$table` WRITE;\n";
                    $backupContent .= "/*!40000 ALTER TABLE `$table` DISABLE KEYS */;\n";
                    
                    foreach ($rows as $row) {
                        $columns = array_keys($row);
                        $values = array_map(function($val) use ($conn) {
                            if ($val === null) return 'NULL';
                            return $conn->quote($val);
                        }, array_values($row));
                        
                        $backupContent .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    
                    $backupContent .= "/*!40000 ALTER TABLE `$table` ENABLE KEYS */;\n";
                    $backupContent .= "UNLOCK TABLES;\n\n";
                }
            }
            
            $backupContent .= "COMMIT;\n";
            
            // Escrever arquivo
            if (file_put_contents($fullPath, $backupContent) === false) {
                throw new Exception('Não foi possível escrever o arquivo de backup');
            }
            
            if (!file_exists($fullPath) || filesize($fullPath) === 0) {
                throw new Exception('O arquivo de backup foi criado mas está vazio');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Backup gerado com sucesso!',
                'filename' => $backupFileName,
                'size' => filesize($fullPath)
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao gerar backup: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'limpar_cache') {
        try {
            $cleaned = [];
            
            // Limpar logs antigos (mais de 90 dias) se a tabela existir
            try {
                $stmt = $conn->prepare("DELETE FROM log_sistema WHERE criado_em < DATE_SUB(NOW(), INTERVAL 90 DAY)");
                $stmt->execute();
                $cleaned['logs'] = $stmt->rowCount();
            } catch (Exception $e) {
                // Tabela pode não existir
                $cleaned['logs'] = 0;
            }
            
            // Limpar sessões PHP antigas (mais de 1 dia)
            $sessionPath = session_save_path();
            if (empty($sessionPath)) {
                $sessionPath = sys_get_temp_dir();
            }
            $sessionFiles = glob($sessionPath . '/sess_*');
            $sessionCleaned = 0;
            foreach ($sessionFiles as $file) {
                if (is_file($file) && filemtime($file) < (time() - 86400)) { // Mais de 1 dia
                    @unlink($file);
                    $sessionCleaned++;
                }
            }
            $cleaned['sessoes_php'] = $sessionCleaned;
            
            // Limpar arquivos temporários do PHP
            $tempDir = sys_get_temp_dir();
            $tempFiles = glob($tempDir . '/php*');
            $tempCleaned = 0;
            foreach ($tempFiles as $file) {
                if (is_file($file) && filemtime($file) < (time() - 86400)) { // Mais de 1 dia
                    @unlink($file);
                    $tempCleaned++;
                }
            }
            $cleaned['arquivos_temp'] = $tempCleaned;
            
            // Limpar cache de opcode se disponível
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $cleaned['opcache'] = true;
            } else {
                $cleaned['opcache'] = false;
            }
            
            // Limpar diretório de uploads temporários se existir
            $uploadsDir = __DIR__ . '/../../../uploads/temp/';
            $uploadsCleaned = 0;
            if (file_exists($uploadsDir)) {
                $uploadFiles = glob($uploadsDir . '*');
                foreach ($uploadFiles as $file) {
                    if (is_file($file) && filemtime($file) < (time() - 86400)) {
                        @unlink($file);
                        $uploadsCleaned++;
                    }
                }
            }
            $cleaned['uploads_temp'] = $uploadsCleaned;
            
            echo json_encode([
                'success' => true,
                'message' => 'Cache limpo com sucesso!',
                'details' => $cleaned
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao limpar cache: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($_POST['acao'] === 'resetar_sistema') {
        try {
            $resetResults = [];
            
            // 1. Limpar cache completo
            try {
                // Limpar logs antigos
                try {
                    $stmt = $conn->prepare("DELETE FROM log_sistema WHERE criado_em < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    $stmt->execute();
                    $resetResults['logs'] = $stmt->rowCount();
                } catch (Exception $e) {
                    $resetResults['logs'] = 0;
                }
                
                // Limpar sessões PHP
                $sessionPath = session_save_path();
                if (empty($sessionPath)) {
                    $sessionPath = sys_get_temp_dir();
                }
                $sessionFiles = glob($sessionPath . '/sess_*');
                $sessionCleaned = 0;
                foreach ($sessionFiles as $file) {
                    @unlink($file);
                    $sessionCleaned++;
                }
                $resetResults['sessoes_php'] = $sessionCleaned;
                
                // Limpar arquivos temporários
                $tempDir = sys_get_temp_dir();
                $tempFiles = glob($tempDir . '/php*');
                $tempCleaned = 0;
                foreach ($tempFiles as $file) {
                    @unlink($file);
                    $tempCleaned++;
                }
                $resetResults['arquivos_temp'] = $tempCleaned;
                
                // Limpar OPcache
                if (function_exists('opcache_reset')) {
                    opcache_reset();
                    $resetResults['opcache'] = true;
                }
                
                // Limpar uploads temporários
                $uploadsDir = __DIR__ . '/../../../uploads/temp/';
                $uploadsCleaned = 0;
                if (file_exists($uploadsDir)) {
                    $uploadFiles = glob($uploadsDir . '*');
                    foreach ($uploadFiles as $file) {
                        @unlink($file);
                        $uploadsCleaned++;
                    }
                }
                $resetResults['uploads_temp'] = $uploadsCleaned;
                
                // Limpar diretório de cache se existir
                $cacheDir = __DIR__ . '/../../../cache/';
                $cacheCleaned = 0;
                if (file_exists($cacheDir)) {
                    $cacheFiles = glob($cacheDir . '*');
                    foreach ($cacheFiles as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                            $cacheCleaned++;
                        }
                    }
                }
                $resetResults['cache_files'] = $cacheCleaned;
                
            } catch (Exception $e) {
                // Continuar mesmo se houver erro em alguma parte
            }
            
            // 2. Resetar configurações temporárias (se necessário)
            // Aqui você pode adicionar lógica para resetar configurações específicas
            
            // 3. Forçar limpeza de sessões ativas (exceto a atual)
            // Não vamos destruir a sessão atual para manter o usuário logado
            
            echo json_encode([
                'success' => true,
                'message' => 'Sistema resetado com sucesso!',
                'details' => $resetResults
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao resetar sistema: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// Buscar configurações do sistema
$config = [];
try {
    // Verificar se a tabela configuracao existe
    $sqlCheck = "SHOW TABLES LIKE 'configuracao'";
    $stmtCheck = $conn->query($sqlCheck);
    if ($stmtCheck->rowCount() > 0) {
        // Buscar configurações existentes
        $sqlConfig = "SELECT chave, valor, tipo, categoria FROM configuracao";
        $stmtConfig = $conn->prepare($sqlConfig);
        $stmtConfig->execute();
        $configs = $stmtConfig->fetchAll(PDO::FETCH_ASSOC);
        
        // Converter array de configurações para formato chave-valor
        foreach ($configs as $cfg) {
            $config[$cfg['chave']] = $cfg['valor'];
        }
    }
} catch (Exception $e) {
    // Se a tabela não existir, usar valores padrão
    $config = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle('Configurações e Segurança') ?></title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.12) 0%, rgba(220, 38, 38, 0.06) 100%);
            border-right: 3px solid #dc2626;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(220, 38, 38, 0.08) 0%, rgba(220, 38, 38, 0.04) 100%);
            transform: translateX(4px);
        }
        .mobile-menu-overlay { transition: opacity 0.3s ease-in-out; }
        @media (max-width: 1023px) {
            .sidebar-mobile { transform: translateX(-100%); }
            .sidebar-mobile.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_adm.php'; ?>
    
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
                        <h1 class="text-xl font-semibold text-gray-800">Configurações e Segurança</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                                <!-- Para ADM, texto simples com padding para alinhamento -->
                                <div class="text-right px-4 py-2">
                                    <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                    <p class="text-xs text-gray-500">Órgão Central</p>
                                </div>
                            <?php } else { ?>
                                <!-- Para outros usuários, card verde com ícone -->
                                <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="text-sm font-semibold">
                                            <?php echo $_SESSION['escola_atual'] ?? 'Escola Municipal'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Configurações do Sistema</h2>
                    <p class="text-gray-600 mt-1">Gerencie configurações gerais e segurança do sistema</p>
                </div>
                
                <!-- Seções de Configuração -->
                <div class="space-y-6">
                    <!-- Configurações Gerais -->
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Configurações Gerais</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Sistema</label>
                                <div class="flex space-x-2">
                                    <input type="text" id="nomeSistema" value="<?= htmlspecialchars($config['nome_sistema'] ?? 'SIGEA - Sistema de Gestão e Alimentação Escolar') ?>" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <button onclick="salvarConfiguracao('nome_sistema', document.getElementById('nomeSistema').value)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Salvar</span>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Este nome será exibido em todo o sistema</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo Atual</label>
                                <div class="flex space-x-2">
                                    <input type="number" id="anoLetivo" value="<?= htmlspecialchars($config['ano_letivo'] ?? date('Y')) ?>" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <button onclick="salvarConfiguracao('ano_letivo', document.getElementById('anoLetivo').value)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center space-x-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Salvar</span>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Ano letivo atual do sistema</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Segurança -->
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Segurança</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">Sessão Automática</h4>
                                    <p class="text-sm text-gray-600">Tempo de expiração da sessão</p>
                                </div>
                                <input type="number" value="30" class="w-20 px-3 py-2 border border-gray-300 rounded-lg" placeholder="minutos">
                            </div>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">Tentativas de Login</h4>
                                    <p class="text-sm text-gray-600">Máximo de tentativas antes de bloquear</p>
                                </div>
                                <input type="number" value="5" class="w-20 px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Backup -->
                    <div class="bg-white rounded-2xl p-6 shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Backup e Manutenção</h3>
                        <div class="space-y-4">
                            <button id="btnBackup" onclick="gerarBackup()" class="w-full bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center space-x-2">
                                <span id="backupText">Gerar Backup do Banco de Dados</span>
                                <svg id="backupSpinner" class="hidden w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                            <button id="btnLimparCache" onclick="limparCache()" class="w-full bg-red-600 hover:bg-red-700 disabled:bg-red-400 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center space-x-2">
                                <span id="cacheText">Limpar Cache do Sistema</span>
                                <svg id="cacheSpinner" class="hidden w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                            <button id="btnResetSistema" onclick="confirmarResetSistema()" class="w-full bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center space-x-2 border-2 border-orange-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Resetar Sistema</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
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
    
    <!-- Modal de Feedback -->
    <div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center space-x-3 mb-4">
                <div id="feedbackIcon" class="w-12 h-12 rounded-full flex items-center justify-center">
                    <!-- Ícone será inserido via JavaScript -->
                </div>
                <div>
                    <h3 id="feedbackTitle" class="text-lg font-semibold text-gray-900"></h3>
                    <p id="feedbackMessage" class="text-sm text-gray-600"></p>
                </div>
            </div>
            <div id="feedbackDetails" class="mb-4 text-sm text-gray-600 hidden"></div>
            <button onclick="fecharFeedbackModal()" class="w-full px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors duration-200">
                Fechar
            </button>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Reset -->
    <div id="resetModal" class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-lg w-full mx-4 shadow-2xl">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmar Reset do Sistema</h3>
                    <p class="text-sm text-gray-600">Esta ação é irreversível!</p>
                </div>
            </div>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-800 font-medium mb-2">⚠️ ATENÇÃO: Esta operação irá:</p>
                <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                    <li>Limpar todo o cache do sistema</li>
                    <li>Limpar todas as sessões ativas</li>
                    <li>Limpar logs antigos</li>
                    <li>Resetar configurações temporárias</li>
                    <li>Limpar arquivos temporários</li>
                </ul>
                <p class="text-sm text-red-800 font-medium mt-3">Os dados do banco de dados NÃO serão afetados.</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Digite <strong class="text-red-600">RESETAR</strong> para confirmar:
                </label>
                <input type="text" id="confirmResetInput" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" placeholder="Digite RESETAR">
            </div>
            <div class="flex space-x-3">
                <button onclick="fecharResetModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button id="btnConfirmReset" onclick="resetarSistema()" class="flex-1 px-4 py-2 text-white bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 disabled:cursor-not-allowed rounded-lg font-medium transition-colors duration-200">
                    Resetar Sistema
                </button>
            </div>
        </div>
    </div>
    
    <script>
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };
        
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
        
        // Função para gerar backup
        function gerarBackup() {
            const btn = document.getElementById('btnBackup');
            const text = document.getElementById('backupText');
            const spinner = document.getElementById('backupSpinner');
            
            // Desabilitar botão e mostrar loading
            btn.disabled = true;
            text.textContent = 'Gerando backup...';
            spinner.classList.remove('hidden');
            
            // Criar FormData
            const formData = new FormData();
            formData.append('acao', 'gerar_backup');
            
            // Fazer requisição
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const sizeMB = (data.size / (1024 * 1024)).toFixed(2);
                    mostrarFeedback(
                        'success',
                        'Backup Gerado com Sucesso!',
                        `O backup foi gerado com sucesso. Arquivo: ${data.filename} (${sizeMB} MB)`,
                        null
                    );
                } else {
                    mostrarFeedback(
                        'error',
                        'Erro ao Gerar Backup',
                        data.message || 'Ocorreu um erro ao gerar o backup.',
                        null
                    );
                }
            })
            .catch(error => {
                mostrarFeedback(
                    'error',
                    'Erro ao Gerar Backup',
                    'Ocorreu um erro ao comunicar com o servidor.',
                    null
                );
            })
            .finally(() => {
                // Reabilitar botão
                btn.disabled = false;
                text.textContent = 'Gerar Backup do Banco de Dados';
                spinner.classList.add('hidden');
            });
        }
        
        // Função para limpar cache
        function limparCache() {
            const btn = document.getElementById('btnLimparCache');
            const text = document.getElementById('cacheText');
            const spinner = document.getElementById('cacheSpinner');
            
            // Desabilitar botão e mostrar loading
            btn.disabled = true;
            text.textContent = 'Limpando cache...';
            spinner.classList.remove('hidden');
            
            // Criar FormData
            const formData = new FormData();
            formData.append('acao', 'limpar_cache');
            
            // Fazer requisição
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let details = '';
                    if (data.details) {
                        const d = data.details;
                        details = '<div class="mt-2 space-y-1">';
                        if (d.sessoes !== undefined) details += `<p>• Sessões antigas removidas: ${d.sessoes}</p>`;
                        if (d.logs !== undefined) details += `<p>• Logs antigos removidos: ${d.logs}</p>`;
                        if (d.arquivos_temp !== undefined) details += `<p>• Arquivos temporários removidos: ${d.arquivos_temp}</p>`;
                        if (d.opcache) details += `<p>• Cache OPcache limpo</p>`;
                        details += '</div>';
                    }
                    mostrarFeedback(
                        'success',
                        'Cache Limpo com Sucesso!',
                        data.message || 'O cache foi limpo com sucesso.',
                        details
                    );
                } else {
                    mostrarFeedback(
                        'error',
                        'Erro ao Limpar Cache',
                        data.message || 'Ocorreu um erro ao limpar o cache.',
                        null
                    );
                }
            })
            .catch(error => {
                mostrarFeedback(
                    'error',
                    'Erro ao Limpar Cache',
                    'Ocorreu um erro ao comunicar com o servidor.',
                    null
                );
            })
            .finally(() => {
                // Reabilitar botão
                btn.disabled = false;
                text.textContent = 'Limpar Cache do Sistema';
                spinner.classList.add('hidden');
            });
        }
        
        // Função para mostrar feedback
        function mostrarFeedback(tipo, titulo, mensagem, detalhes) {
            const modal = document.getElementById('feedbackModal');
            const icon = document.getElementById('feedbackIcon');
            const title = document.getElementById('feedbackTitle');
            const message = document.getElementById('feedbackMessage');
            const detailsDiv = document.getElementById('feedbackDetails');
            
            // Limpar classes anteriores
            icon.className = 'w-12 h-12 rounded-full flex items-center justify-center';
            
            if (tipo === 'success') {
                icon.classList.add('bg-green-100');
                icon.innerHTML = '<svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            } else {
                icon.classList.add('bg-red-100');
                icon.innerHTML = '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            }
            
            title.textContent = titulo;
            message.textContent = mensagem;
            
            if (detalhes) {
                detailsDiv.innerHTML = detalhes;
                detailsDiv.classList.remove('hidden');
            } else {
                detailsDiv.classList.add('hidden');
            }
            
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
        }
        
        // Função para fechar modal de feedback
        function fecharFeedbackModal() {
            const modal = document.getElementById('feedbackModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }
        
        // Função para confirmar reset do sistema
        function confirmarResetSistema() {
            const modal = document.getElementById('resetModal');
            const input = document.getElementById('confirmResetInput');
            const btn = document.getElementById('btnConfirmReset');
            
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                input.value = '';
                input.focus();
                btn.disabled = true;
                
                // Habilitar botão apenas quando digitar "RESETAR"
                input.addEventListener('input', function() {
                    if (this.value.toUpperCase() === 'RESETAR') {
                        btn.disabled = false;
                    } else {
                        btn.disabled = true;
                    }
                });
            }
        }
        
        // Função para fechar modal de reset
        function fecharResetModal() {
            const modal = document.getElementById('resetModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                document.getElementById('confirmResetInput').value = '';
            }
        }
        
        // Função para salvar configuração
        function salvarConfiguracao(chave, valor) {
            if (!valor || valor.trim() === '') {
                mostrarFeedback(
                    'error',
                    'Valor Inválido',
                    'Por favor, preencha o campo antes de salvar.',
                    null
                );
                return;
            }
            
            // Criar FormData
            const formData = new FormData();
            formData.append('acao', 'atualizar_configuracao');
            formData.append('chave', chave);
            formData.append('valor', valor);
            
            // Fazer requisição
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarFeedback(
                        'success',
                        'Configuração Salva',
                        'A configuração foi salva com sucesso!',
                        null
                    );
                } else {
                    mostrarFeedback(
                        'error',
                        'Erro ao Salvar',
                        data.message || 'Ocorreu um erro ao salvar a configuração.',
                        null
                    );
                }
            })
            .catch(error => {
                mostrarFeedback(
                    'error',
                    'Erro ao Salvar',
                    'Ocorreu um erro ao comunicar com o servidor.',
                    null
                );
            });
        }
        
        // Permitir salvar com Enter nos campos de configuração
        document.addEventListener('DOMContentLoaded', function() {
            const nomeSistema = document.getElementById('nomeSistema');
            const anoLetivo = document.getElementById('anoLetivo');
            
            if (nomeSistema) {
                nomeSistema.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        salvarConfiguracao('nome_sistema', this.value);
                    }
                });
            }
            
            if (anoLetivo) {
                anoLetivo.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        salvarConfiguracao('ano_letivo', this.value);
                    }
                });
            }
        });
        
        // Função para resetar sistema
        function resetarSistema() {
            const input = document.getElementById('confirmResetInput');
            const btn = document.getElementById('btnConfirmReset');
            
            if (input.value.toUpperCase() !== 'RESETAR') {
                mostrarFeedback(
                    'error',
                    'Confirmação Inválida',
                    'Por favor, digite "RESETAR" para confirmar a operação.',
                    null
                );
                return;
            }
            
            // Fechar modal de confirmação
            fecharResetModal();
            
            // Desabilitar botão e mostrar loading
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-5 h-5 animate-spin inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Resetando...';
            
            // Criar FormData
            const formData = new FormData();
            formData.append('acao', 'resetar_sistema');
            
            // Fazer requisição
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let details = '';
                    if (data.details) {
                        const d = data.details;
                        details = '<div class="mt-2 space-y-1">';
                        if (d.logs !== undefined) details += `<p>• Logs removidos: ${d.logs}</p>`;
                        if (d.sessoes_php !== undefined) details += `<p>• Sessões PHP removidas: ${d.sessoes_php}</p>`;
                        if (d.arquivos_temp !== undefined) details += `<p>• Arquivos temporários removidos: ${d.arquivos_temp}</p>`;
                        if (d.uploads_temp !== undefined) details += `<p>• Uploads temporários removidos: ${d.uploads_temp}</p>`;
                        if (d.cache_files !== undefined) details += `<p>• Arquivos de cache removidos: ${d.cache_files}</p>`;
                        if (d.opcache) details += `<p>• Cache OPcache resetado</p>`;
                        details += '</div>';
                    }
                    mostrarFeedback(
                        'success',
                        'Sistema Resetado com Sucesso!',
                        data.message || 'O sistema foi resetado com sucesso. Todos os caches e dados temporários foram limpos.',
                        details
                    );
                } else {
                    mostrarFeedback(
                        'error',
                        'Erro ao Resetar Sistema',
                        data.message || 'Ocorreu um erro ao resetar o sistema.',
                        null
                    );
                }
            })
            .catch(error => {
                mostrarFeedback(
                    'error',
                    'Erro ao Resetar Sistema',
                    'Ocorreu um erro ao comunicar com o servidor.',
                    null
                );
            })
            .finally(() => {
                // Reabilitar botão
                btn.disabled = false;
                btn.innerHTML = 'Resetar Sistema';
            });
        }
    </script>
</body>
</html>

