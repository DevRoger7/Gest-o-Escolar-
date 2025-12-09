-- Estrutura para sistema de Responsáveis
-- Permite que um responsável tenha múltiplos alunos e vice-versa

-- Tabela de relacionamento aluno_responsavel (many-to-many)
CREATE TABLE IF NOT EXISTS `aluno_responsavel` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` bigint(20) NOT NULL,
  `responsavel_id` bigint(20) NOT NULL COMMENT 'ID da pessoa responsável',
  `parentesco` enum('PAI','MAE','AVO','TIO','OUTRO') DEFAULT 'OUTRO',
  `principal` tinyint(1) DEFAULT 0 COMMENT '1 = responsável principal',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_aluno_responsavel` (`aluno_id`, `responsavel_id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `responsavel_id` (`responsavel_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_aluno_responsavel_ativo` (`ativo`),
  KEY `idx_responsavel_ativo` (`responsavel_id`, `ativo`),
  KEY `idx_aluno_ativo` (`aluno_id`, `ativo`),
  CONSTRAINT `aluno_responsavel_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aluno_responsavel_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aluno_responsavel_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Nota: O campo responsavel_id na tabela aluno pode ser mantido para compatibilidade,
-- mas o relacionamento principal será através de aluno_responsavel


