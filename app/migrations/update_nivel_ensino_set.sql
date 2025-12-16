-- Migration: Atualizar campo nivel_ensino para incluir EDUCACAO_INFANTIL
-- Descrição: Adiciona EDUCACAO_INFANTIL ao SET do campo nivel_ensino na tabela escola

-- Verificar se a coluna nivel_ensino existe
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'escola' 
    AND COLUMN_NAME = 'nivel_ensino'
);

-- Atualizar o SET para incluir EDUCACAO_INFANTIL
SET @sql_update = IF(@col_exists > 0,
    'ALTER TABLE `escola` MODIFY COLUMN `nivel_ensino` SET(\'EDUCACAO_INFANTIL\',\'ENSINO_FUNDAMENTAL\',\'ENSINO_MEDIO\') DEFAULT \'ENSINO_FUNDAMENTAL\'',
    'SELECT \'Coluna nivel_ensino não existe\' AS mensagem'
);
PREPARE stmt FROM @sql_update;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

