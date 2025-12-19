-- =====================================================
-- Adicionar coluna localidade_transporte na tabela aluno
-- Data: 2025-12-16
-- Descrição: Adiciona coluna para armazenar a localidade específica
--            dentro do distrito onde o aluno precisa de transporte
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Verificar se a coluna já existe antes de adicionar
SET @col_exists_localidade = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'aluno' 
    AND COLUMN_NAME = 'localidade_transporte'
);

-- Adicionar coluna localidade_transporte se não existir
SET @sql_localidade = IF(@col_exists_localidade = 0,
    'ALTER TABLE `aluno` ADD COLUMN `localidade_transporte` varchar(255) DEFAULT NULL COMMENT \'Localidade específica dentro do distrito onde o aluno precisa de transporte (ex: Massape, Alto das Vassouras, Centro). Deve corresponder a uma localidade cadastrada na tabela distrito_localidade.\' AFTER `distrito_transporte`',
    'SELECT \'Coluna localidade_transporte já existe\' AS mensagem'
);
PREPARE stmt FROM @sql_localidade;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualizar índice se necessário
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'aluno' 
    AND INDEX_NAME = 'idx_aluno_transporte'
);

-- Se o índice existe mas não tem localidade_transporte, recriar
SET @idx_has_localidade = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'aluno' 
    AND INDEX_NAME = 'idx_aluno_transporte'
    AND COLUMN_NAME = 'localidade_transporte'
);

-- Se índice existe mas não tem localidade, recriar
SET @sql_recreate_idx = IF(@idx_exists > 0 AND @idx_has_localidade = 0,
    'ALTER TABLE `aluno` DROP INDEX `idx_aluno_transporte`; ALTER TABLE `aluno` ADD INDEX `idx_aluno_transporte` (`precisa_transporte`, `distrito_transporte`, `localidade_transporte`, `ativo`)',
    'SELECT \'Índice já está atualizado ou não existe\' AS mensagem'
);

-- Executar apenas se necessário
IF @idx_exists > 0 AND @idx_has_localidade = 0 THEN
    SET @sql_drop = 'ALTER TABLE `aluno` DROP INDEX `idx_aluno_transporte`';
    PREPARE stmt FROM @sql_drop;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    SET @sql_add = 'ALTER TABLE `aluno` ADD INDEX `idx_aluno_transporte` (`precisa_transporte`, `distrito_transporte`, `localidade_transporte`, `ativo`)';
    PREPARE stmt FROM @sql_add;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END IF;

COMMIT;

-- =====================================================
-- NOTA: 
-- A localidade_transporte deve ser uma localidade específica
-- dentro do distrito, conforme cadastrado na tabela
-- distrito_localidade (ex: Massape dentro de Amanari)
-- 
-- Exemplo de uso:
-- distrito_transporte = 'Amanari'
-- localidade_transporte = 'Massape'
-- =====================================================

