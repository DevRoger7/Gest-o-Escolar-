-- =====================================================
-- Adicionar campos de transporte na tabela aluno
-- Data: 2025-12-15
-- Descrição: Adiciona campos para indicar se o aluno precisa de transporte e qual distrito
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Verificar se as colunas já existem antes de adicionar
SET @col_exists_precisa = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'aluno' 
    AND COLUMN_NAME = 'precisa_transporte'
);

SET @col_exists_distrito = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'aluno' 
    AND COLUMN_NAME = 'distrito_transporte'
);

-- Adicionar coluna precisa_transporte se não existir
SET @sql_precisa = IF(@col_exists_precisa = 0,
    'ALTER TABLE `aluno` ADD COLUMN `precisa_transporte` tinyint(1) DEFAULT 0 COMMENT \'1 = aluno precisa de transporte escolar, 0 = não precisa\' AFTER `ativo`',
    'SELECT \'Coluna precisa_transporte já existe\' AS mensagem'
);
PREPARE stmt FROM @sql_precisa;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna distrito_transporte se não existir
SET @sql_distrito = IF(@col_exists_distrito = 0,
    'ALTER TABLE `aluno` ADD COLUMN `distrito_transporte` varchar(100) DEFAULT NULL COMMENT \'Distrito/localidade de Maranguape onde o aluno precisa de transporte (ex: Amanari, Itapebussu, Lagoa)\' AFTER `precisa_transporte`',
    'SELECT \'Coluna distrito_transporte já existe\' AS mensagem'
);
PREPARE stmt FROM @sql_distrito;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice para melhorar performance nas consultas
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'aluno' 
    AND INDEX_NAME = 'idx_aluno_transporte'
);

SET @sql_idx = IF(@idx_exists = 0,
    'ALTER TABLE `aluno` ADD INDEX `idx_aluno_transporte` (`precisa_transporte`, `distrito_transporte`, `ativo`)',
    'SELECT \'Índice idx_aluno_transporte já existe\' AS mensagem'
);
PREPARE stmt FROM @sql_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;

-- =====================================================
-- NOTA: Os distritos válidos de Maranguape são:
-- Amanari, Antônio Marques, Cachoeira, Itapebussu,
-- Jubaia, Ladeira Grande, Lages, Lagoa do Juvenal,
-- Manoel Guedes, Maranguape, Papara, Penedo, Sapupara,
-- São João do Amanari, Tanques, Umarizeiras,
-- Vertentes do Lagedo
-- =====================================================

