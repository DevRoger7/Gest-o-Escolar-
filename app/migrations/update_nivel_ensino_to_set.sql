-- Atualizar campo nivel_ensino de ENUM para SET
-- Execute este script se você já rodou o script anterior que criou como ENUM
-- Compatível com MariaDB 10.4.32
-- Baseado na estrutura do banco escola_merenda

-- Verificar se a coluna existe e é do tipo ENUM
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'escola' 
    AND COLUMN_NAME = 'nivel_ensino'
    AND DATA_TYPE = 'enum'
);

-- Alterar coluna de ENUM para SET se existir
SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `escola` MODIFY COLUMN `nivel_ensino` SET(''ENSINO_FUNDAMENTAL'', ''ENSINO_MEDIO'') DEFAULT ''ENSINO_FUNDAMENTAL''',
    'SELECT ''Coluna nivel_ensino não existe ou já é do tipo SET'' AS mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

