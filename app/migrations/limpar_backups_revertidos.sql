-- Script para limpar dados de backups já revertidos
-- Execute este script para limpar os dados JSON de backups que foram revertidos antes da atualização

-- Limpar dados de backups revertidos (onde revertido = 1)
UPDATE `escola_backup` 
SET 
    `dados_escola` = '',
    `dados_turmas` = NULL,
    `dados_lotacoes` = NULL
WHERE 
    `revertido` = 1 
    AND (`dados_escola` IS NOT NULL AND `dados_escola` != '' 
         OR `dados_turmas` IS NOT NULL 
         OR `dados_lotacoes` IS NOT NULL);

-- Verificar quantos registros foram atualizados
SELECT 
    COUNT(*) as total_limpos,
    'Backups revertidos com dados limpos' as status
FROM `escola_backup` 
WHERE `revertido` = 1 
  AND (`dados_escola` = '' OR `dados_escola` IS NULL)
  AND `dados_turmas` IS NULL 
  AND `dados_lotacoes` IS NULL;


