-- Migration: Adicionar campos distrito e escola_localidade na tabela escola
-- Descrição: Adiciona campos para identificar o distrito da escola e se é uma escola de localidade
-- Compatível com a estrutura do banco escola_merenda (33).sql

-- Verificar se a coluna distrito existe
SET @col_exists_distrito = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'escola' 
    AND COLUMN_NAME = 'distrito'
);

-- Verificar se a coluna escola_localidade existe
SET @col_exists_escola_localidade = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'escola' 
    AND COLUMN_NAME = 'escola_localidade'
);

-- Adicionar coluna distrito se não existir
-- Posicionada após bairro conforme estrutura atual: bairro, municipio, estado, cep...
SET @sql_distrito = IF(@col_exists_distrito = 0,
    'ALTER TABLE `escola` ADD COLUMN `distrito` varchar(100) DEFAULT NULL COMMENT \'Distrito de Maranguape onde a escola está localizada (ex: Amanari, Sede, Itapebussu). Campo obrigatório para facilitar o processo de criação de rotas.\' AFTER `bairro`',
    'SELECT \'Coluna distrito já existe\' AS mensagem'
);
PREPARE stmt FROM @sql_distrito;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna escola_localidade se não existir
-- Posicionada após distrito
SET @sql_escola_localidade = IF(@col_exists_escola_localidade = 0,
    'ALTER TABLE `escola` ADD COLUMN `escola_localidade` tinyint(1) DEFAULT 0 COMMENT \'Indica se a escola é uma escola de localidade (1) ou não (0). Escolas de localidade atendem alunos de outras localidades dentro do mesmo distrito.\' AFTER `distrito`',
    'SELECT \'Coluna escola_localidade já existe\' AS mensagem'
);
PREPARE stmt FROM @sql_escola_localidade;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice para facilitar buscas por distrito
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'escola' 
    AND INDEX_NAME = 'idx_escola_distrito'
);

SET @sql_idx = IF(@idx_exists = 0,
    'ALTER TABLE `escola` ADD INDEX `idx_escola_distrito` (`distrito`, `ativo`)',
    'SELECT \'Índice idx_escola_distrito já existe\' AS mensagem'
);
PREPARE stmt FROM @sql_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

