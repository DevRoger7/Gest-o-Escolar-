-- Tabela para backup de escolas excluídas
-- Permite reversão de exclusões por até 30 dias

--
-- Estrutura para tabela `escola_backup`
--

CREATE TABLE `escola_backup` (
  `id` bigint(20) NOT NULL,
  `escola_id_original` bigint(20) NOT NULL COMMENT 'ID original da escola antes da exclusão',
  `dados_escola` longtext NOT NULL COMMENT 'JSON com todos os dados da escola',
  `dados_turmas` longtext DEFAULT NULL COMMENT 'JSON com dados das turmas excluídas',
  `dados_lotacoes` longtext DEFAULT NULL COMMENT 'JSON com dados das lotações excluídas',
  `excluido_por` bigint(20) DEFAULT NULL COMMENT 'ID do usuário que excluiu',
  `excluido_em` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da exclusão',
  `excluido_permanentemente` tinyint(1) DEFAULT 0 COMMENT 'Se foi excluído permanentemente antes dos 30 dias',
  `revertido` tinyint(1) DEFAULT 0 COMMENT 'Se a exclusão foi revertida',
  `revertido_em` timestamp NULL DEFAULT NULL COMMENT 'Data da reversão',
  `revertido_por` bigint(20) DEFAULT NULL COMMENT 'ID do usuário que reverteu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabela `escola_backup`
--

ALTER TABLE `escola_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_escola_id_original` (`escola_id_original`),
  ADD KEY `idx_excluido_em` (`excluido_em`),
  ADD KEY `idx_revertido` (`revertido`),
  ADD KEY `idx_excluido_permanentemente` (`excluido_permanentemente`),
  ADD KEY `excluido_por` (`excluido_por`),
  ADD KEY `revertido_por` (`revertido_por`);

--
-- AUTO_INCREMENT para tabela `escola_backup`
--

ALTER TABLE `escola_backup`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabela `escola_backup`
--

ALTER TABLE `escola_backup`
  ADD CONSTRAINT `escola_backup_ibfk_1` FOREIGN KEY (`excluido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `escola_backup_ibfk_2` FOREIGN KEY (`revertido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

