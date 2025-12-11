-- Atualização da tabela escola_backup
-- Aplicar apenas se a tabela já existir
-- 
-- NOTA: Agora a exclusão de escola é um soft delete (apenas desativa a escola)
-- Todos os dados (turmas, notas, frequências, etc.) são preservados
-- O backup ainda é útil para histórico e para casos de exclusão permanente

-- Verificar se a tabela existe antes de aplicar alterações
-- Se não existir, use o arquivo create_escola_backup_table.sql

-- Atualizar comentários dos campos para refletir a nova lógica de soft delete
ALTER TABLE `escola_backup` 
  MODIFY `dados_escola` longtext NOT NULL COMMENT 'JSON com todos os dados da escola (backup para histórico)',
  MODIFY `dados_turmas` longtext DEFAULT NULL COMMENT 'JSON com dados das turmas (backup para histórico - dados são preservados no banco)',
  MODIFY `dados_lotacoes` longtext DEFAULT NULL COMMENT 'JSON com dados das lotações (backup para histórico - dados são preservados no banco)',
  MODIFY `excluido_em` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da desativação (soft delete)';

-- Não há necessidade de alterar estrutura, apenas comentários foram atualizados
-- A tabela já está adequada para o novo sistema de soft delete

