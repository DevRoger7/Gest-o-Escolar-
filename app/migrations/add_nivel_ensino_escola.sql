-- Adicionar campo nivel_ensino na tabela escola
-- Execute este script no banco de dados
-- Compatível com MariaDB 10.4.32
-- Baseado na estrutura do banco escola_merenda
-- Permite múltiplos níveis de ensino (SET permite selecionar ambos)

-- Verificar se a coluna já existe antes de adicionar
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'escola' 
    AND COLUMN_NAME = 'nivel_ensino'
);

-- Adicionar coluna apenas se não existir
-- SET permite múltiplos valores separados por vírgula (ex: 'ENSINO_FUNDAMENTAL,ENSINO_MEDIO')
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `escola` ADD COLUMN `nivel_ensino` SET(''ENSINO_FUNDAMENTAL'', ''ENSINO_MEDIO'') DEFAULT ''ENSINO_FUNDAMENTAL'' AFTER `qtd_salas`',
    'SELECT ''Coluna nivel_ensino já existe na tabela escola'' AS mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

