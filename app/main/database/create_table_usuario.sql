-- Script para criar a tabela usuario caso não exista
-- Execute este script no banco de dados escola_merenda

CREATE TABLE IF NOT EXISTS `usuario` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pessoa_id` bigint(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL') DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `email_verificado` tinyint(1) DEFAULT 0,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expiracao` timestamp NULL DEFAULT NULL,
  `tentativas_login` int(11) DEFAULT 0,
  `bloqueado_ate` timestamp NULL DEFAULT NULL,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pessoa_id` (`pessoa_id`),
  UNIQUE KEY `username` (`username`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_usuario_ativo` (`ativo`),
  KEY `idx_usuario_role` (`role`),
  KEY `idx_usuario_email_verificado` (`email_verificado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

