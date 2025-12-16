-- Migration: Adicionar suporte a PCD e CID para alunos
-- Execute este script no banco de dados

-- Adicionar campo is_pcd na tabela aluno
ALTER TABLE `aluno` 
ADD COLUMN `is_pcd` TINYINT(1) DEFAULT 0 COMMENT '1 = Pessoa com Deficiência' AFTER `necessidades_especiais`;

-- Criar tabela para armazenar múltiplos CIDs por aluno
CREATE TABLE IF NOT EXISTS `aluno_cid` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` BIGINT(20) NOT NULL,
  `cid` VARCHAR(10) NOT NULL COMMENT 'Código CID (ex: F84.0)',
  `descricao` VARCHAR(255) DEFAULT NULL COMMENT 'Descrição opcional do CID',
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  `criado_por` BIGINT(20) DEFAULT NULL,
  `ativo` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_aluno_id` (`aluno_id`),
  KEY `idx_cid` (`cid`),
  KEY `idx_ativo` (`ativo`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `fk_aluno_cid_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aluno_cid_usuario` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Armazena os CIDs (Códigos de Classificação Internacional de Doenças) dos alunos PCD';

