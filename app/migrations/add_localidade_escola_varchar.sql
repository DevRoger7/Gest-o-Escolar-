-- Migration: Adicionar coluna localidade_escola (varchar) na tabela escola
-- Descrição: Adiciona campo para armazenar o nome da localidade da escola (ex: Massape, Pedra d'água)
-- Este campo é diferente de escola_localidade (tinyint) que indica se é escola de localidade

-- Verificar se a coluna localidade_escola existe
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'escola' 
    AND COLUMN_NAME = 'localidade_escola'
);

-- Adicionar coluna localidade_escola se não existir
-- Posicionada após distrito
SET @sql_add = IF(@col_exists = 0,
    'ALTER TABLE `escola` ADD COLUMN `localidade_escola` varchar(255) DEFAULT NULL AFTER `distrito`',
    'SELECT ''Coluna localidade_escola ja existe'' AS mensagem'
);
PREPARE stmt FROM @sql_add;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

