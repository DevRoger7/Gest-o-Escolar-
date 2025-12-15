-- Criação da tabela escola_programa
-- Tabela de relacionamento entre escolas e programas educacionais

CREATE TABLE IF NOT EXISTS `escola_programa` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `escola_id` bigint(20) NOT NULL,
  `programa_id` bigint(20) NOT NULL,
  `data_inscricao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_escola_programa` (`escola_id`, `programa_id`),
  KEY `idx_escola_id` (`escola_id`),
  KEY `idx_programa_id` (`programa_id`),
  KEY `idx_ativo` (`ativo`),
  CONSTRAINT `fk_escola_programa_escola` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_escola_programa_programa` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;





