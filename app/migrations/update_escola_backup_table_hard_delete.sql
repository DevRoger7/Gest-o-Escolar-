-- Atualização da tabela escola_backup para refletir a lógica de HARD DELETE
-- Aplicar apenas se a tabela já existir
-- 
-- NOTA: Agora a exclusão de escola é um HARD DELETE (exclusão completa)
-- Todos os dados relacionados são movidos para o backup e depois excluídos das tabelas principais
-- O backup permite reversão completa dos dados dentro de 30 dias

-- Verificar se a tabela existe antes de aplicar alterações
-- Se não existir, use o arquivo create_escola_backup_table.sql

-- Atualizar comentários dos campos para refletir a nova lógica de hard delete
-- Baseado na estrutura atual da tabela em escola_merenda (4) (1).sql (linhas 722-734)
ALTER TABLE `escola_backup` 
  MODIFY COLUMN `dados_escola` longtext NOT NULL COMMENT 'JSON com todos os dados da escola (backup completo antes da exclusão)',
  MODIFY COLUMN `dados_turmas` longtext DEFAULT NULL COMMENT 'JSON com TODOS os dados relacionados (turmas, alunos, notas, frequências, avaliações, boletins, entregas, cardápios, consumo, etc.) - backup completo antes da exclusão',
  MODIFY COLUMN `dados_lotacoes` longtext DEFAULT NULL COMMENT 'JSON com dados das lotações (professores, gestores, nutricionistas, funcionários) - backup completo antes da exclusão',
  MODIFY COLUMN `excluido_em` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da exclusão (hard delete - todos os dados foram movidos para backup e excluídos das tabelas principais)';

-- Nota: A estrutura da tabela não é alterada, apenas os comentários para documentação.
-- 
-- Mudanças de lógica:
-- ANTES (soft delete):
--   - Escola marcada como ativo = 0
--   - Dados preservados no banco
--   - Comentários mencionavam "soft delete" e "dados preservados"
-- 
-- AGORA (hard delete):
--   1. Ao excluir escola: TODOS os dados são salvos no backup (dados_turmas contém tudo relacionado)
--   2. Depois: TODOS os dados são excluídos das tabelas principais (hard delete)
--   3. Ao reverter: TODOS os dados são restaurados do backup para as tabelas principais
--   4. Login verifica tanto tabelas principais quanto backup para bloquear acesso
