<?php
/**
 * Script para verificar e criar a tabela usuario se não existir
 * Execute este arquivo uma vez para garantir que a tabela existe
 */

require_once(__DIR__ . '/../config/Database.php');

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar se a tabela existe
    $sql = "SHOW TABLES LIKE 'usuario'";
    $stmt = $conn->query($sql);
    $tabelaExiste = $stmt->rowCount() > 0;
    
    if (!$tabelaExiste) {
        echo "Tabela 'usuario' não encontrada. Criando tabela...\n";
        
        // Ler o script SQL de criação
        $sqlFile = __DIR__ . '/create_table_usuario.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            // Remover comentários e executar
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            // Executar cada comando separadamente
            $commands = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($commands as $command) {
                if (!empty($command)) {
                    try {
                        $conn->exec($command);
                    } catch (PDOException $e) {
                        // Ignorar erros de "já existe" ou "duplicado"
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            echo "Tabela 'usuario' criada com sucesso!\n";
        } else {
            // Criar tabela diretamente se o arquivo SQL não existir
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
            echo "Tabela 'usuario' criada com sucesso!\n";
        }
    } else {
        echo "Tabela 'usuario' já existe no banco de dados.\n";
    }
    
    // Verificar também a tabela pessoa
    $sql = "SHOW TABLES LIKE 'pessoa'";
    $stmt = $conn->query($sql);
    $pessoaExiste = $stmt->rowCount() > 0;
    
    if (!$pessoaExiste) {
        echo "AVISO: Tabela 'pessoa' também não foi encontrada. Certifique-se de importar o banco de dados completo.\n";
    }
    
    echo "Verificação concluída!\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

?>

