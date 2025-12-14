-- Atualização dos comentários da tabela escola_backup
-- Aplicar para refletir a lógica atual de HARD DELETE
-- 
-- IMPORTANTE: Agora quando uma escola é excluída:
-- 1. TODOS os dados relacionados são salvos no backup (JSON)
-- 2. TODOS os dados são EXCLUÍDOS das tabelas normais (hard delete)
-- 3. Quando revertido, os dados são RESTAURADOS das tabelas normais
-- 4. O backup é marcado como revertido para não interferir no login

-- Atualizar comentários dos campos
ALTER TABLE `escola_backup` 
  MODIFY `dados_escola` longtext NOT NULL COMMENT 'JSON com todos os dados da escola (backup completo antes da exclusão - dados são EXCLUÍDOS das tabelas normais)',
  MODIFY `dados_turmas` longtext DEFAULT NULL COMMENT 'JSON com TODOS os dados relacionados (turmas, alunos, aluno_responsavel, notas, frequências, avaliações, boletins, entregas, cardápios, consumo, desperdicio, historico_escolar, indicador_nutricional, parecer_tecnico, pedido_cesta, pedido_cesta_item, relatorio, etc.) - backup completo antes da exclusão - dados são EXCLUÍDOS das tabelas normais',
  MODIFY `dados_lotacoes` longtext DEFAULT NULL COMMENT 'JSON com dados das lotações (professores, gestores, nutricionistas, funcionários) - backup completo antes da exclusão - dados são EXCLUÍDOS das tabelas normais',
  MODIFY `excluido_em` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da exclusão (HARD DELETE - todos os dados foram movidos para backup e EXCLUÍDOS das tabelas principais)';

-- Os índices já estão corretos e otimizados para as consultas:
-- - idx_revertido: usado para filtrar backups não revertidos no login
-- - idx_excluido_permanentemente: usado para filtrar backups não excluídos permanentemente
-- - idx_excluido_em: usado para limpar backups antigos (30 dias)


