<?php
/**
 * Script para reparar/criar a tabela usuario
 * Resolve problemas de tabela corrompida ou inexistente
 */

require_once(__DIR__ . '/../config/Database.php');

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Reparação da Tabela Usuario</h2>";
echo "<pre>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Conectado ao banco: escola_merenda\n\n";
    
    // Verificar se a tabela existe de diferentes formas
    echo "1. Verificando existência da tabela...\n";
    
    // Método 1: SHOW TABLES
    $sql = "SHOW TABLES LIKE 'usuario'";
    $stmt = $conn->query($sql);
    $existeShow = $stmt->rowCount() > 0;
    echo "   SHOW TABLES: " . ($existeShow ? "✓ Existe" : "✗ Não existe") . "\n";
    
    // Método 2: INFORMATION_SCHEMA
    $sql = "SELECT COUNT(*) as total 
            FROM information_schema.tables 
            WHERE table_schema = 'escola_merenda' 
            AND table_name = 'usuario'";
    $stmt = $conn->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $existeInfo = $result['total'] > 0;
    echo "   INFORMATION_SCHEMA: " . ($existeInfo ? "✓ Existe" : "✗ Não existe") . "\n";
    
    // Método 3: Tentar DESCRIBE
    $existeDescribe = false;
    try {
        $sql = "DESCRIBE usuario";
        $stmt = $conn->query($sql);
        $existeDescribe = true;
        echo "   DESCRIBE: ✓ Funciona\n";
    } catch (PDOException $e) {
        echo "   DESCRIBE: ✗ Erro - " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Se SHOW TABLES diz que existe mas DESCRIBE não funciona, pode estar corrompida
    if ($existeShow && !$existeDescribe) {
        echo "2. PROBLEMA DETECTADO: Tabela aparece mas não pode ser acessada!\n";
        echo "   Isso indica que a tabela pode estar corrompida.\n\n";
        
        echo "3. Tentando reparar...\n";
        
        // Tentar reparar a tabela
        try {
            $sql = "REPAIR TABLE usuario";
            $stmt = $conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "   Resultado do REPAIR: " . ($result['Msg_text'] ?? 'OK') . "\n";
        } catch (PDOException $e) {
            echo "   REPAIR falhou: " . $e->getMessage() . "\n";
        }
        
        // Verificar novamente
        try {
            $sql = "DESCRIBE usuario";
            $stmt = $conn->query($sql);
            echo "   ✓ Tabela reparada com sucesso!\n";
        } catch (PDOException $e) {
            echo "   ✗ Tabela ainda não acessível. Vamos recriar...\n\n";
            
            // Fazer backup dos dados se existirem
            echo "4. Fazendo backup dos dados...\n";
            try {
                $sql = "SELECT * FROM usuario";
                $stmt = $conn->query($sql);
                $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "   " . count($dados) . " registros encontrados\n";
                
                if (count($dados) > 0) {
                    // Salvar backup em arquivo
                    $backupFile = __DIR__ . '/backup_usuario_' . date('Y-m-d_H-i-s') . '.json';
                    file_put_contents($backupFile, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    echo "   Backup salvo em: " . basename($backupFile) . "\n";
                }
            } catch (PDOException $e) {
                echo "   ⚠ Não foi possível fazer backup: " . $e->getMessage() . "\n";
            }
            
            // Dropar a tabela corrompida
            echo "\n5. Removendo tabela corrompida...\n";
            try {
                $sql = "DROP TABLE IF EXISTS usuario";
                $conn->exec($sql);
                echo "   ✓ Tabela removida\n";
            } catch (PDOException $e) {
                echo "   ✗ Erro ao remover: " . $e->getMessage() . "\n";
                echo "   Tentando FORCE DROP...\n";
                try {
                    $sql = "DROP TABLE IF EXISTS usuario FORCE";
                    $conn->exec($sql);
                    echo "   ✓ Tabela removida com FORCE\n";
                } catch (PDOException $e2) {
                    echo "   ✗ Erro crítico: " . $e2->getMessage() . "\n";
                    throw $e2;
                }
            }
        }
    }
    
    // Verificar se a tabela existe e funciona agora
    echo "\n6. Verificando se a tabela está acessível...\n";
    try {
        $sql = "DESCRIBE usuario";
        $stmt = $conn->query($sql);
        $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   ✓ Tabela está acessível!\n";
        echo "   Campos encontrados: " . count($campos) . "\n";
    } catch (PDOException $e) {
        echo "   ✗ Tabela ainda não acessível. Criando nova tabela...\n\n";
        
        // Criar a tabela
        echo "7. Criando nova tabela 'usuario'...\n";
        
        $createTable = "CREATE TABLE IF NOT EXISTS `usuario` (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `pessoa_id` bigint(20) NOT NULL,
          `username` varchar(50) NOT NULL,
          `senha_hash` varchar(255) NOT NULL,
          `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL') DEFAULT NULL,
          `ativo` tinyint(1) DEFAULT 1,
          `email_verificado` tinyint(1) DEFAULT 0,
          `token_recuperacao` varchar(255) DEFAULT NULL,
          `token_expiracao` timestamp NULL DEFAULT NULL,
          `tentativas_login` int(11) DEFAULT 0,
          `bloqueado_ate` timestamp NULL DEFAULT NULL,
          `ultimo_login` timestamp NULL DEFAULT NULL,
          `ultimo_acesso` timestamp NULL DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          `atualizado_por` bigint(20) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `pessoa_id` (`pessoa_id`),
          UNIQUE KEY `username` (`username`),
          KEY `atualizado_por` (`atualizado_por`),
          KEY `idx_usuario_ativo` (`ativo`),
          KEY `idx_usuario_role` (`role`),
          KEY `idx_usuario_email_verificado` (`email_verificado`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($createTable);
        echo "   ✓ Tabela criada com sucesso!\n";
        
        // Restaurar dados do backup se existir
        echo "\n8. Verificando backups para restaurar dados...\n";
        $backupFiles = glob(__DIR__ . '/backup_usuario_*.json');
        if (!empty($backupFiles)) {
            // Pegar o backup mais recente
            rsort($backupFiles);
            $backupFile = $backupFiles[0];
            echo "   Backup encontrado: " . basename($backupFile) . "\n";
            
            $dados = json_decode(file_get_contents($backupFile), true);
            if ($dados && count($dados) > 0) {
                echo "   Restaurando " . count($dados) . " registros...\n";
                
                $sql = "INSERT INTO usuario (id, pessoa_id, username, senha_hash, role, ativo, email_verificado, token_recuperacao, token_expiracao, tentativas_login, bloqueado_ate, ultimo_login, ultimo_acesso, created_at, atualizado_em, atualizado_por) 
                        VALUES (:id, :pessoa_id, :username, :senha_hash, :role, :ativo, :email_verificado, :token_recuperacao, :token_expiracao, :tentativas_login, :bloqueado_ate, :ultimo_login, :ultimo_acesso, :created_at, :atualizado_em, :atualizado_por)";
                $stmt = $conn->prepare($sql);
                
                $restaurados = 0;
                foreach ($dados as $registro) {
                    try {
                        $stmt->execute($registro);
                        $restaurados++;
                    } catch (PDOException $e) {
                        // Ignorar erros de duplicação
                        if (strpos($e->getMessage(), 'Duplicate') === false) {
                            echo "     ⚠ Erro ao restaurar registro ID {$registro['id']}: " . $e->getMessage() . "\n";
                        }
                    }
                }
                echo "   ✓ $restaurados registros restaurados\n";
            }
        } else {
            echo "   ℹ Nenhum backup encontrado\n";
        }
    }
    
    // Teste final
    echo "\n9. Teste final...\n";
    try {
        $sql = "SELECT COUNT(*) as total FROM usuario";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✓ Tabela funcionando corretamente!\n";
        echo "   Total de registros: {$result['total']}\n";
        
        // Testar query de login
        $sql = "SELECT u.*, p.* FROM usuario u 
                INNER JOIN pessoa p ON u.pessoa_id = p.id 
                LIMIT 1";
        $stmt = $conn->query($sql);
        $teste = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($teste) {
            echo "   ✓ Query de login funciona!\n";
        } else {
            echo "   ⚠ Query funciona mas não há dados (ou tabela pessoa não existe)\n";
        }
        
    } catch (PDOException $e) {
        echo "   ✗ Erro no teste final: " . $e->getMessage() . "\n";
    }
    
    echo "\n✓ Reparação concluída!\n";
    echo "\nPor favor, tente fazer login novamente.\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO CRÍTICO:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  Arquivo: " . $e->getFile() . "\n";
    echo "  Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

