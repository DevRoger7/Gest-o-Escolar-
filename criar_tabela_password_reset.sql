-- ============================================================
-- Script para criar tabela de tokens de recuperação de senha
-- ============================================================
-- Execute este script se a tabela não for criada automaticamente
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `usuario_id` bigint(20) NOT NULL,
    `token` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `expira_em` datetime NOT NULL,
    `usado` tinyint(1) DEFAULT 0,
    `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `usuario_id` (`usuario_id`),
    KEY `expira_em` (`expira_em`),
    KEY `usado` (`usado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- Tabela criada com sucesso!
-- ============================================================

