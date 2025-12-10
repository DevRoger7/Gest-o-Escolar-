-- Migração: Adicionar campo especializacao na tabela gestor
-- Data: 2025-12-10
-- Descrição: Adiciona campo para armazenar especializações do gestor (múltiplas, em JSON)

-- Verificar e adicionar campo especializacao se não existir
SET @dbname = DATABASE();
SET @tablename = "gestor";
SET @columnname = "especializacao";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_schema = @dbname)
      AND (table_name = @tablename)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Campo especializacao já existe' AS resultado;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " TEXT DEFAULT NULL AFTER formacao;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SELECT 'Migração concluída com sucesso!' AS resultado;

