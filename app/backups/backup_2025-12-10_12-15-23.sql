-- ============================================================
-- Backup do Banco de Dados
-- Gerado em: 2025-12-10 12:15:23
-- Banco: escola_merenda
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

START TRANSACTION;


-- ============================================================
-- Estrutura da tabela `aluno`
-- ============================================================

DROP TABLE IF EXISTS `aluno`;

CREATE TABLE `aluno` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pessoa_id` bigint(20) NOT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `responsavel_id` bigint(20) DEFAULT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `data_matricula` date DEFAULT NULL,
  `situacao` enum('MATRICULADO','TRANSFERIDO','EVADIDO','CONCLUIDO','CANCELADO') DEFAULT 'MATRICULADO',
  `data_nascimento` date DEFAULT NULL,
  `nacionalidade` varchar(50) DEFAULT 'Brasileira',
  `naturalidade` varchar(100) DEFAULT NULL,
  `necessidades_especiais` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `pessoa_id` (`pessoa_id`),
  KEY `responsavel_id` (`responsavel_id`),
  KEY `escola_id` (`escola_id`),
  KEY `idx_aluno_situacao` (`situacao`),
  KEY `idx_aluno_ativo` (`ativo`),
  KEY `idx_aluno_escola` (`escola_id`),
  CONSTRAINT `aluno_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `aluno_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `aluno_ibfk_3` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `aluno`
LOCK TABLES `aluno` WRITE;
/*!40000 ALTER TABLE `aluno` DISABLE KEYS */;
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('1', '10', '2024001', NULL, NULL, NULL, '2025-11-29', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-29 20:05:38', '2025-12-03 15:48:18', NULL, '0');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('2', '28', 'MAT-000028', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('3', '29', 'MAT-000029', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('4', '30', 'MAT-000030', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('5', '31', 'MAT-000031', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('6', '32', 'MAT-000032', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('7', '33', 'MAT-000033', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('8', '34', 'MAT-000034', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('9', '35', 'MAT-000035', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('10', '36', 'MAT-000036', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('11', '37', 'MAT-000037', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('12', '38', 'MAT-000038', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('13', '39', 'MAT-000039', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('14', '40', 'MAT-000040', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('15', '41', 'MAT-000041', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('16', '42', 'MAT-000042', NULL, NULL, '17', '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
/*!40000 ALTER TABLE `aluno` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `aluno_responsavel`
-- ============================================================

DROP TABLE IF EXISTS `aluno_responsavel`;

CREATE TABLE `aluno_responsavel` (
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
  UNIQUE KEY `unique_aluno_responsavel` (`aluno_id`,`responsavel_id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `responsavel_id` (`responsavel_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_aluno_responsavel_ativo` (`ativo`),
  KEY `idx_responsavel_ativo` (`responsavel_id`,`ativo`),
  KEY `idx_aluno_ativo` (`aluno_id`,`ativo`),
  CONSTRAINT `aluno_responsavel_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aluno_responsavel_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aluno_responsavel_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `aluno_responsavel`
LOCK TABLES `aluno_responsavel` WRITE;
/*!40000 ALTER TABLE `aluno_responsavel` DISABLE KEYS */;
INSERT INTO `aluno_responsavel` (`id`, `aluno_id`, `responsavel_id`, `parentesco`, `principal`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('2', '2', '50', 'PAI', '0', NULL, '2025-12-09 10:45:00', '2025-12-09 10:45:00', '11', '1');
INSERT INTO `aluno_responsavel` (`id`, `aluno_id`, `responsavel_id`, `parentesco`, `principal`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('3', '6', '50', 'PAI', '0', NULL, '2025-12-09 11:13:27', '2025-12-09 11:13:27', '11', '1');
/*!40000 ALTER TABLE `aluno_responsavel` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `aluno_turma`
-- ============================================================

DROP TABLE IF EXISTS `aluno_turma`;

CREATE TABLE `aluno_turma` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `status` enum('MATRICULADO','TRANSFERIDO','CONCLUIDO','DESISTENTE') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_aluno_turma_ativo` (`aluno_id`,`turma_id`,`status`),
  KEY `aluno_id` (`aluno_id`),
  KEY `turma_id` (`turma_id`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_aluno_turma_status` (`status`),
  KEY `idx_aluno_turma_inicio` (`inicio`),
  CONSTRAINT `aluno_turma_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `aluno_turma_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `aluno_turma_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `aluno_turma`
LOCK TABLES `aluno_turma` WRITE;
/*!40000 ALTER TABLE `aluno_turma` DISABLE KEYS */;
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('1', '2', '1', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('2', '3', '1', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('3', '4', '1', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('4', '5', '1', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('5', '6', '1', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('8', '7', '2', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('9', '8', '2', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('10', '9', '2', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('11', '10', '2', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('12', '11', '2', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('15', '12', '3', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('16', '13', '3', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('17', '14', '3', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('18', '15', '3', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('19', '16', '3', '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
/*!40000 ALTER TABLE `aluno_turma` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `avaliacao`
-- ============================================================

DROP TABLE IF EXISTS `avaliacao`;

CREATE TABLE `avaliacao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `turma_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `data` date DEFAULT NULL,
  `tipo` enum('TRABALHO','PROVA','ATIVIDADE') DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `criado_por` bigint(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `turma_id` (`turma_id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_avaliacao_data` (`data`),
  KEY `idx_avaliacao_tipo` (`tipo`),
  KEY `idx_avaliacao_ativo` (`ativo`),
  CONSTRAINT `avaliacao_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `avaliacao_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`) ON DELETE SET NULL,
  CONSTRAINT `avaliacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `avaliacao`
LOCK TABLES `avaliacao` WRITE;
/*!40000 ALTER TABLE `avaliacao` DISABLE KEYS */;
INSERT INTO `avaliacao` (`id`, `turma_id`, `disciplina_id`, `titulo`, `descricao`, `data`, `tipo`, `peso`, `criado_por`, `criado_em`, `atualizado_em`, `ativo`) VALUES ('17', '1', '2', 'Avaliação Parcial - 1º Bimestre', NULL, '2025-12-09', 'ATIVIDADE', NULL, '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', '1');
INSERT INTO `avaliacao` (`id`, `turma_id`, `disciplina_id`, `titulo`, `descricao`, `data`, `tipo`, `peso`, `criado_por`, `criado_em`, `atualizado_em`, `ativo`) VALUES ('18', '1', '2', 'Avaliação Bimestral - 1º Bimestre', NULL, '2025-12-09', 'PROVA', NULL, '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', '1');
/*!40000 ALTER TABLE `avaliacao` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `boletim`
-- ============================================================

DROP TABLE IF EXISTS `boletim`;

CREATE TABLE `boletim` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `ano_letivo` int(11) NOT NULL,
  `bimestre` int(11) NOT NULL,
  `media_geral` decimal(5,2) DEFAULT NULL,
  `frequencia_percentual` decimal(5,2) DEFAULT NULL,
  `total_faltas` int(11) DEFAULT 0,
  `situacao` enum('APROVADO','REPROVADO','RECUPERACAO','PENDENTE') DEFAULT 'PENDENTE',
  `observacoes` text DEFAULT NULL,
  `gerado_por` bigint(20) DEFAULT NULL,
  `gerado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_boletim_aluno_turma_bimestre` (`aluno_id`,`turma_id`,`ano_letivo`,`bimestre`),
  KEY `aluno_id` (`aluno_id`),
  KEY `turma_id` (`turma_id`),
  KEY `gerado_por` (`gerado_por`),
  KEY `idx_boletim_ano_bimestre` (`ano_letivo`,`bimestre`),
  KEY `idx_boletim_situacao` (`situacao`),
  CONSTRAINT `boletim_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `boletim_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `boletim_ibfk_3` FOREIGN KEY (`gerado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `boletim`
LOCK TABLES `boletim` WRITE;
/*!40000 ALTER TABLE `boletim` DISABLE KEYS */;
INSERT INTO `boletim` (`id`, `aluno_id`, `turma_id`, `ano_letivo`, `bimestre`, `media_geral`, `frequencia_percentual`, `total_faltas`, `situacao`, `observacoes`, `gerado_por`, `gerado_em`, `atualizado_em`) VALUES ('1', '2', '1', '2025', '1', '2.58', '0.00', '0', 'REPROVADO', NULL, '11', '2025-12-09 12:53:41', '2025-12-09 12:53:41');
INSERT INTO `boletim` (`id`, `aluno_id`, `turma_id`, `ano_letivo`, `bimestre`, `media_geral`, `frequencia_percentual`, `total_faltas`, `situacao`, `observacoes`, `gerado_por`, `gerado_em`, `atualizado_em`) VALUES ('2', '3', '1', '2025', '1', '2.33', '0.00', '0', 'REPROVADO', NULL, '11', '2025-12-09 12:53:42', '2025-12-09 12:53:42');
INSERT INTO `boletim` (`id`, `aluno_id`, `turma_id`, `ano_letivo`, `bimestre`, `media_geral`, `frequencia_percentual`, `total_faltas`, `situacao`, `observacoes`, `gerado_por`, `gerado_em`, `atualizado_em`) VALUES ('3', '4', '1', '2025', '1', '2.17', '0.00', '0', 'REPROVADO', NULL, '11', '2025-12-09 12:53:42', '2025-12-09 12:53:42');
INSERT INTO `boletim` (`id`, `aluno_id`, `turma_id`, `ano_letivo`, `bimestre`, `media_geral`, `frequencia_percentual`, `total_faltas`, `situacao`, `observacoes`, `gerado_por`, `gerado_em`, `atualizado_em`) VALUES ('4', '5', '1', '2025', '1', '2.17', '0.00', '0', 'REPROVADO', NULL, '11', '2025-12-09 12:53:42', '2025-12-09 12:53:42');
INSERT INTO `boletim` (`id`, `aluno_id`, `turma_id`, `ano_letivo`, `bimestre`, `media_geral`, `frequencia_percentual`, `total_faltas`, `situacao`, `observacoes`, `gerado_por`, `gerado_em`, `atualizado_em`) VALUES ('5', '6', '1', '2025', '1', '2.00', '0.00', '0', 'REPROVADO', NULL, '11', '2025-12-09 12:53:42', '2025-12-09 12:53:42');
/*!40000 ALTER TABLE `boletim` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `boletim_item`
-- ============================================================

DROP TABLE IF EXISTS `boletim_item`;

CREATE TABLE `boletim_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `boletim_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) NOT NULL,
  `media` decimal(5,2) DEFAULT NULL,
  `faltas` int(11) DEFAULT 0,
  `situacao` enum('APROVADO','REPROVADO','RECUPERACAO') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `boletim_id` (`boletim_id`),
  KEY `disciplina_id` (`disciplina_id`),
  CONSTRAINT `boletim_item_ibfk_1` FOREIGN KEY (`boletim_id`) REFERENCES `boletim` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boletim_item_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `boletim_item`
LOCK TABLES `boletim_item` WRITE;
/*!40000 ALTER TABLE `boletim_item` DISABLE KEYS */;
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('1', '1', '1', '0.00', NULL, 'REPROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('2', '1', '2', '7.75', NULL, 'APROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('3', '1', '3', '0.00', NULL, 'REPROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('4', '2', '1', '0.00', NULL, 'REPROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('5', '2', '2', '7.00', NULL, 'APROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('6', '2', '3', '0.00', NULL, 'REPROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('7', '3', '1', '0.00', NULL, 'REPROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('8', '3', '2', '6.50', NULL, 'RECUPERACAO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('9', '3', '3', '0.00', NULL, 'REPROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('10', '4', '1', '0.00', NULL, 'REPROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('11', '4', '2', '6.50', NULL, 'RECUPERACAO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('12', '4', '3', '0.00', NULL, 'REPROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('13', '5', '1', '0.00', NULL, 'REPROVADO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('14', '5', '2', '6.00', NULL, 'RECUPERACAO', NULL);
INSERT INTO `boletim_item` (`id`, `boletim_id`, `disciplina_id`, `media`, `faltas`, `situacao`, `observacoes`) VALUES ('15', '5', '3', '0.00', NULL, 'REPROVADO', NULL);
/*!40000 ALTER TABLE `boletim_item` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `calendar_categories`
-- ============================================================

DROP TABLE IF EXISTS `calendar_categories`;

CREATE TABLE `calendar_categories` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color` varchar(7) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `calendar_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `calendar_categories`
LOCK TABLES `calendar_categories` WRITE;
/*!40000 ALTER TABLE `calendar_categories` DISABLE KEYS */;
INSERT INTO `calendar_categories` (`id`, `name`, `color`, `icon`, `description`, `created_by`, `created_at`, `ativo`) VALUES ('1', 'ReuniÃµes', '#3B82F6', 'users', 'ReuniÃµes gerais e administrativas', '1', '2025-10-24 13:32:08', '1');
INSERT INTO `calendar_categories` (`id`, `name`, `color`, `icon`, `description`, `created_by`, `created_at`, `ativo`) VALUES ('2', 'AvaliaÃ§Ãµes', '#EF4444', 'book-open', 'Provas e avaliaÃ§Ãµes dos alunos', '1', '2025-10-24 13:32:08', '1');
INSERT INTO `calendar_categories` (`id`, `name`, `color`, `icon`, `description`, `created_by`, `created_at`, `ativo`) VALUES ('3', 'Feriados', '#10B981', 'calendar', 'Feriados nacionais e regionais', '1', '2025-10-24 13:32:08', '1');
INSERT INTO `calendar_categories` (`id`, `name`, `color`, `icon`, `description`, `created_by`, `created_at`, `ativo`) VALUES ('4', 'Eventos', '#F59E0B', 'star', 'Eventos especiais da escola', '1', '2025-10-24 13:32:08', '1');
INSERT INTO `calendar_categories` (`id`, `name`, `color`, `icon`, `description`, `created_by`, `created_at`, `ativo`) VALUES ('5', 'Aulas', '#8B5CF6', 'graduation-cap', 'Aulas e atividades pedagÃ³gicas', '1', '2025-10-24 13:32:08', '1');
INSERT INTO `calendar_categories` (`id`, `name`, `color`, `icon`, `description`, `created_by`, `created_at`, `ativo`) VALUES ('6', 'Treinamentos', '#EC4899', 'book', 'Treinamentos e capacitaÃ§Ãµes', '1', '2025-10-24 13:32:08', '1');
INSERT INTO `calendar_categories` (`id`, `name`, `color`, `icon`, `description`, `created_by`, `created_at`, `ativo`) VALUES ('7', 'ReuniÃ£o de Pais', '#14B8A6', 'users', 'ReuniÃµes com pais e responsÃ¡veis', '1', '2025-10-24 13:32:08', '1');
/*!40000 ALTER TABLE `calendar_categories` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `calendar_event_participants`
-- ============================================================

DROP TABLE IF EXISTS `calendar_event_participants`;

CREATE TABLE `calendar_event_participants` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `role` enum('organizer','attendee','optional') DEFAULT 'attendee',
  `status` enum('pending','accepted','declined','tentative') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participant` (`event_id`,`user_id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `calendar_event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calendar_event_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `calendar_event_recurrence`
-- ============================================================

DROP TABLE IF EXISTS `calendar_event_recurrence`;

CREATE TABLE `calendar_event_recurrence` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) NOT NULL,
  `recurrence_type` enum('daily','weekly','monthly','yearly') NOT NULL,
  `interval_value` int(11) DEFAULT 1,
  `days_of_week` varchar(20) DEFAULT NULL,
  `day_of_month` int(11) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `occurrences` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `calendar_event_recurrence_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `calendar_events`
-- ============================================================

DROP TABLE IF EXISTS `calendar_events`;

CREATE TABLE `calendar_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `all_day` tinyint(1) DEFAULT 0,
  `color` varchar(7) DEFAULT '#3B82F6',
  `event_type` enum('meeting','exam','holiday','event','deadline','class','meeting_parents','training') DEFAULT 'event',
  `school_id` bigint(20) DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `created_by` (`created_by`),
  KEY `start_date` (`start_date`),
  KEY `event_type` (`event_type`),
  KEY `idx_calendar_events_date` (`start_date`,`end_date`),
  KEY `idx_calendar_events_school` (`school_id`,`ativo`),
  KEY `idx_calendar_events_type` (`event_type`,`ativo`),
  KEY `idx_calendar_events_creator` (`created_by`,`ativo`),
  CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `calendar_events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `calendar_events`
LOCK TABLES `calendar_events` WRITE;
/*!40000 ALTER TABLE `calendar_events` DISABLE KEYS */;
INSERT INTO `calendar_events` (`id`, `title`, `description`, `start_date`, `end_date`, `all_day`, `color`, `event_type`, `school_id`, `created_by`, `created_at`, `updated_at`, `ativo`) VALUES ('1', 'ReuniÃ£o PedagÃ³gica', 'ReuniÃ£o mensal com professores para planejamento pedagÃ³gico', '2025-01-15 14:00:00', '2025-01-15 16:00:00', '0', '#3B82F6', 'meeting', '14', '1', '2025-10-24 13:32:09', '2025-10-24 13:32:09', '1');
INSERT INTO `calendar_events` (`id`, `title`, `description`, `start_date`, `end_date`, `all_day`, `color`, `event_type`, `school_id`, `created_by`, `created_at`, `updated_at`, `ativo`) VALUES ('2', 'Prova de MatemÃ¡tica - 6Âº Ano', 'AvaliaÃ§Ã£o bimestral de matemÃ¡tica para o 6Âº ano', '2025-01-20 08:00:00', '2025-01-20 10:00:00', '0', '#EF4444', 'exam', '14', '1', '2025-10-24 13:32:09', '2025-10-24 13:32:09', '1');
INSERT INTO `calendar_events` (`id`, `title`, `description`, `start_date`, `end_date`, `all_day`, `color`, `event_type`, `school_id`, `created_by`, `created_at`, `updated_at`, `ativo`) VALUES ('3', 'Feriado - SÃ£o SebastiÃ£o', 'Feriado municipal em homenagem ao padroeiro', '2025-01-20 00:00:00', '2025-01-20 23:59:59', '1', '#10B981', 'holiday', NULL, '1', '2025-10-24 13:32:09', '2025-10-24 13:32:09', '1');
INSERT INTO `calendar_events` (`id`, `title`, `description`, `start_date`, `end_date`, `all_day`, `color`, `event_type`, `school_id`, `created_by`, `created_at`, `updated_at`, `ativo`) VALUES ('4', 'ReuniÃ£o de Pais - 1Âº Bimestre', 'ReuniÃ£o com pais para entrega de boletins do 1Âº bimestre', '2025-01-25 19:00:00', '2025-01-25 21:00:00', '0', '#14B8A6', 'meeting_parents', '14', '1', '2025-10-24 13:32:09', '2025-10-24 13:32:09', '1');
INSERT INTO `calendar_events` (`id`, `title`, `description`, `start_date`, `end_date`, `all_day`, `color`, `event_type`, `school_id`, `created_by`, `created_at`, `updated_at`, `ativo`) VALUES ('5', 'CapacitaÃ§Ã£o de Professores', 'Treinamento sobre novas metodologias de ensino', '2025-01-30 09:00:00', '2025-01-30 17:00:00', '0', '#EC4899', 'training', '14', '1', '2025-10-24 13:32:09', '2025-10-24 13:32:09', '1');
INSERT INTO `calendar_events` (`id`, `title`, `description`, `start_date`, `end_date`, `all_day`, `color`, `event_type`, `school_id`, `created_by`, `created_at`, `updated_at`, `ativo`) VALUES ('6', 'Salaberga fantasy', '', '2025-10-31 13:00:00', '2025-10-31 17:00:00', '0', '#10B981', 'meeting', NULL, '1', '2025-10-24 15:03:53', '2025-10-24 15:09:14', '1');
/*!40000 ALTER TABLE `calendar_events` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `calendar_notifications`
-- ============================================================

DROP TABLE IF EXISTS `calendar_notifications`;

CREATE TABLE `calendar_notifications` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `notification_type` enum('email','sms','push','system') DEFAULT 'system',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `calendar_notifications_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calendar_notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `calendar_settings`
-- ============================================================

DROP TABLE IF EXISTS `calendar_settings`;

CREATE TABLE `calendar_settings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `calendar_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `calendar_settings`
LOCK TABLES `calendar_settings` WRITE;
/*!40000 ALTER TABLE `calendar_settings` DISABLE KEYS */;
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('1', '1', 'default_view', 'month', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('2', '2', 'default_view', 'month', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('3', '3', 'default_view', 'month', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('4', '4', 'default_view', 'month', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('5', '5', 'default_view', 'month', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('6', '6', 'default_view', 'month', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('8', '1', 'week_start', 'monday', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('9', '2', 'week_start', 'monday', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('10', '3', 'week_start', 'monday', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('11', '4', 'week_start', 'monday', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('12', '5', 'week_start', 'monday', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('13', '6', 'week_start', 'monday', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('15', '1', 'timezone', 'America/Fortaleza', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('16', '2', 'timezone', 'America/Fortaleza', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('17', '3', 'timezone', 'America/Fortaleza', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('18', '4', 'timezone', 'America/Fortaleza', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('19', '5', 'timezone', 'America/Fortaleza', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES ('20', '6', 'timezone', 'America/Fortaleza', '2025-10-24 13:32:09', '2025-10-24 13:32:09');
/*!40000 ALTER TABLE `calendar_settings` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `cardapio`
-- ============================================================

DROP TABLE IF EXISTS `cardapio`;

CREATE TABLE `cardapio` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `escola_id` bigint(20) NOT NULL,
  `mes` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `status` enum('RASCUNHO','APROVADO','REJEITADO','PUBLICADO') DEFAULT 'RASCUNHO',
  `aprovado_por` bigint(20) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_por` bigint(20) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `criado_por` (`criado_por`),
  KEY `aprovado_por` (`aprovado_por`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_cardapio_status` (`status`),
  KEY `idx_cardapio_mes_ano` (`mes`,`ano`),
  KEY `idx_cardapio_escola_mes_ano` (`escola_id`,`mes`,`ano`),
  KEY `idx_cardapio_criado_por` (`criado_por`),
  CONSTRAINT `cardapio_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `cardapio_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`),
  CONSTRAINT `cardapio_ibfk_3` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cardapio_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1003 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `cardapio`
LOCK TABLES `cardapio` WRITE;
/*!40000 ALTER TABLE `cardapio` DISABLE KEYS */;
INSERT INTO `cardapio` (`id`, `escola_id`, `mes`, `ano`, `status`, `aprovado_por`, `data_aprovacao`, `observacoes`, `criado_por`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('1', '3', '1', '2025', 'APROVADO', '1', '2024-12-20 14:30:00', NULL, '1', '2024-12-15 10:00:00', '2025-12-01 21:00:11', NULL);
INSERT INTO `cardapio` (`id`, `escola_id`, `mes`, `ano`, `status`, `aprovado_por`, `data_aprovacao`, `observacoes`, `criado_por`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('2', '3', '2', '2025', 'RASCUNHO', NULL, NULL, NULL, '1', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL);
INSERT INTO `cardapio` (`id`, `escola_id`, `mes`, `ano`, `status`, `aprovado_por`, `data_aprovacao`, `observacoes`, `criado_por`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('3', '3', '3', '2025', 'APROVADO', '1', '2025-02-20 14:30:00', NULL, '1', '2025-02-15 10:00:00', '2025-12-01 21:00:11', NULL);
/*!40000 ALTER TABLE `cardapio` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `cardapio_item`
-- ============================================================

DROP TABLE IF EXISTS `cardapio_item`;

CREATE TABLE `cardapio_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `cardapio_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cardapio_id` (`cardapio_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `cardapio_item_ibfk_1` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapio` (`id`),
  CONSTRAINT `cardapio_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `cardapio_item`
LOCK TABLES `cardapio_item` WRITE;
/*!40000 ALTER TABLE `cardapio_item` DISABLE KEYS */;
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('1', '1', '1023', '50.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('2', '1', '1024', '30.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('3', '1', '1028', '20.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('4', '1', '1034', '25.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('5', '1', '1031', '100.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('6', '1', '1037', '30.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('7', '2', '1023', '55.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('8', '2', '1024', '32.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('9', '2', '1029', '18.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('10', '2', '1025', '20.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('11', '2', '1036', '20.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('12', '2', '1031', '110.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('13', '3', '1023', '50.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('14', '3', '1024', '30.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('15', '3', '1030', '15.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('16', '3', '1025', '25.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('17', '3', '1038', '35.000');
INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES ('18', '3', '1031', '100.000');
/*!40000 ALTER TABLE `cardapio_item` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `comunicado`
-- ============================================================

DROP TABLE IF EXISTS `comunicado`;

CREATE TABLE `comunicado` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `turma_id` bigint(20) DEFAULT NULL,
  `aluno_id` bigint(20) DEFAULT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `enviado_por` bigint(20) NOT NULL,
  `tipo` enum('GERAL','TURMA','ALUNO','URGENTE') DEFAULT 'GERAL',
  `prioridade` enum('BAIXA','NORMAL','ALTA','URGENTE') DEFAULT 'NORMAL',
  `canal` enum('SISTEMA','EMAIL','SMS','WHATSAPP','TODOS') DEFAULT 'SISTEMA',
  `titulo` varchar(255) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `lido` tinyint(1) DEFAULT 0,
  `enviado` tinyint(1) DEFAULT 0,
  `data_envio` timestamp NULL DEFAULT NULL,
  `respostas_recebidas` int(11) DEFAULT 0,
  `visualizacoes` int(11) DEFAULT 0,
  `data_leitura` timestamp NULL DEFAULT NULL,
  `lido_por` bigint(20) DEFAULT NULL,
  `anexo_url` varchar(500) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `turma_id` (`turma_id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `enviado_por` (`enviado_por`),
  KEY `escola_id` (`escola_id`),
  KEY `lido_por` (`lido_por`),
  KEY `idx_comunicado_tipo` (`tipo`),
  KEY `idx_comunicado_prioridade` (`prioridade`),
  KEY `idx_comunicado_lido` (`lido`),
  KEY `idx_comunicado_ativo` (`ativo`),
  KEY `idx_comunicado_data` (`criado_em`),
  KEY `idx_comunicado_canal` (`canal`),
  KEY `idx_comunicado_enviado` (`enviado`),
  CONSTRAINT `comunicado_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `comunicado_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `comunicado_ibfk_3` FOREIGN KEY (`enviado_por`) REFERENCES `usuario` (`id`),
  CONSTRAINT `comunicado_ibfk_4` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `comunicado_ibfk_5` FOREIGN KEY (`lido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `comunicado_resposta`
-- ============================================================

DROP TABLE IF EXISTS `comunicado_resposta`;

CREATE TABLE `comunicado_resposta` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `comunicado_id` bigint(20) NOT NULL,
  `responsavel_id` bigint(20) NOT NULL,
  `resposta` text DEFAULT NULL,
  `lido` tinyint(1) DEFAULT 0,
  `data_leitura` timestamp NULL DEFAULT NULL,
  `data_resposta` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_comunicado_responsavel` (`comunicado_id`,`responsavel_id`),
  KEY `comunicado_id` (`comunicado_id`),
  KEY `responsavel_id` (`responsavel_id`),
  CONSTRAINT `comunicado_resposta_ibfk_1` FOREIGN KEY (`comunicado_id`) REFERENCES `comunicado` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comunicado_resposta_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `configuracao`
-- ============================================================

DROP TABLE IF EXISTS `configuracao`;

CREATE TABLE `configuracao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('STRING','INTEGER','BOOLEAN','JSON') DEFAULT 'STRING',
  `categoria` varchar(50) DEFAULT 'GERAL',
  `descricao` text DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave_unique` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `configuracao`
LOCK TABLES `configuracao` WRITE;
/*!40000 ALTER TABLE `configuracao` DISABLE KEYS */;
INSERT INTO `configuracao` (`id`, `chave`, `valor`, `tipo`, `categoria`, `descricao`, `atualizado_em`, `atualizado_por`) VALUES ('1', 'nome_sistema', 'SIGAE - Sistema de Gestão Escolar e Merenda', 'STRING', 'GERAL', NULL, '2025-12-08 09:41:40', '3');
/*!40000 ALTER TABLE `configuracao` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `consumo_diario`
-- ============================================================

DROP TABLE IF EXISTS `consumo_diario`;

CREATE TABLE `consumo_diario` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `escola_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) DEFAULT NULL,
  `data` date NOT NULL,
  `turno` enum('MANHA','TARDE','NOITE') DEFAULT NULL,
  `total_alunos` int(11) DEFAULT 0,
  `alunos_atendidos` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_consumo_escola_turma_data` (`escola_id`,`turma_id`,`data`,`turno`),
  KEY `escola_id` (`escola_id`),
  KEY `turma_id` (`turma_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_consumo_data` (`data`),
  KEY `idx_consumo_escola_data` (`escola_id`,`data`),
  CONSTRAINT `consumo_diario_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `consumo_diario_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumo_diario_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumo_diario_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `consumo_diario`
LOCK TABLES `consumo_diario` WRITE;
/*!40000 ALTER TABLE `consumo_diario` DISABLE KEYS */;
INSERT INTO `consumo_diario` (`id`, `escola_id`, `turma_id`, `data`, `turno`, `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('1', '3', NULL, '2025-11-24', 'MANHA', '150', '145', 'Consumo normal', '1', '2025-11-24 00:00:00', '2025-12-01 21:00:11', NULL);
INSERT INTO `consumo_diario` (`id`, `escola_id`, `turma_id`, `data`, `turno`, `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('2', '3', NULL, '2025-11-25', 'TARDE', '120', '118', NULL, '1', '2025-11-25 00:00:00', '2025-12-01 21:00:11', NULL);
INSERT INTO `consumo_diario` (`id`, `escola_id`, `turma_id`, `data`, `turno`, `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('3', '3', NULL, '2025-11-26', 'MANHA', '150', '148', 'Alguns alunos faltaram', '1', '2025-11-26 00:00:00', '2025-12-01 21:00:11', NULL);
INSERT INTO `consumo_diario` (`id`, `escola_id`, `turma_id`, `data`, `turno`, `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('4', '3', NULL, '2025-11-27', 'TARDE', '120', '120', 'Todos os alunos atendidos', '1', '2025-11-27 00:00:00', '2025-12-01 21:00:11', NULL);
INSERT INTO `consumo_diario` (`id`, `escola_id`, `turma_id`, `data`, `turno`, `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('5', '3', NULL, '2025-11-28', 'MANHA', '150', '142', NULL, '1', '2025-11-28 00:00:00', '2025-12-01 21:00:11', NULL);
INSERT INTO `consumo_diario` (`id`, `escola_id`, `turma_id`, `data`, `turno`, `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('6', '3', NULL, '2025-11-29', 'TARDE', '120', '115', NULL, '1', '2025-11-29 00:00:00', '2025-12-01 21:00:11', NULL);
INSERT INTO `consumo_diario` (`id`, `escola_id`, `turma_id`, `data`, `turno`, `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('7', '3', NULL, '2025-11-30', 'MANHA', '150', '150', 'Consumo completo', '1', '2025-11-30 00:00:00', '2025-12-01 21:00:11', NULL);
INSERT INTO `consumo_diario` (`id`, `escola_id`, `turma_id`, `data`, `turno`, `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('8', '3', NULL, '2025-12-01', 'TARDE', '120', '118', NULL, '1', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL);
/*!40000 ALTER TABLE `consumo_diario` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `consumo_item`
-- ============================================================

DROP TABLE IF EXISTS `consumo_item`;

CREATE TABLE `consumo_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `consumo_diario_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL,
  `unidade_medida` varchar(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consumo_diario_id` (`consumo_diario_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `consumo_item_ibfk_1` FOREIGN KEY (`consumo_diario_id`) REFERENCES `consumo_diario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consumo_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `consumo_item`
LOCK TABLES `consumo_item` WRITE;
/*!40000 ALTER TABLE `consumo_item` DISABLE KEYS */;
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('1', '1', '1023', '15.500', 'KG', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('2', '1', '1024', '9.200', 'KG', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('3', '1', '1028', '6.000', 'KG', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('4', '1', '1031', '30.000', 'L', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('5', '2', '1023', '12.000', 'KG', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('6', '2', '1024', '7.500', 'KG', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('7', '2', '1029', '5.500', 'KG', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('8', '2', '1031', '25.000', 'L', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('9', '7', '1023', '15.800', 'KG', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('10', '7', '1024', '9.500', 'KG', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('11', '7', '1028', '6.200', 'KG', NULL);
INSERT INTO `consumo_item` (`id`, `consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`, `observacoes`) VALUES ('12', '7', '1031', '30.000', 'L', NULL);
/*!40000 ALTER TABLE `consumo_item` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `custo_merenda`
-- ============================================================

DROP TABLE IF EXISTS `custo_merenda`;

CREATE TABLE `custo_merenda` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `escola_id` bigint(20) DEFAULT NULL,
  `tipo` enum('COMPRA_PRODUTOS','DISTRIBUICAO','PREPARO','DESPERDICIO','OUTROS') DEFAULT 'OUTROS',
  `descricao` varchar(255) DEFAULT NULL,
  `produto_id` bigint(20) DEFAULT NULL,
  `fornecedor_id` bigint(20) DEFAULT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL,
  `valor_unitario` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(12,2) NOT NULL,
  `data` date NOT NULL,
  `mes` int(11) DEFAULT NULL,
  `ano` int(11) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `produto_id` (`produto_id`),
  KEY `fornecedor_id` (`fornecedor_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_custo_data` (`data`),
  KEY `idx_custo_tipo` (`tipo`),
  KEY `idx_custo_mes_ano` (`mes`,`ano`),
  KEY `idx_custo_escola_mes_ano` (`escola_id`,`mes`,`ano`),
  CONSTRAINT `custo_merenda_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `custo_merenda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`) ON DELETE SET NULL,
  CONSTRAINT `custo_merenda_ibfk_3` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `custo_merenda_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `desperdicio`
-- ============================================================

DROP TABLE IF EXISTS `desperdicio`;

CREATE TABLE `desperdicio` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `escola_id` bigint(20) NOT NULL,
  `data` date NOT NULL,
  `turno` enum('MANHA','TARDE','NOITE') DEFAULT NULL,
  `produto_id` bigint(20) DEFAULT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL,
  `unidade_medida` varchar(20) DEFAULT NULL,
  `peso_kg` decimal(10,3) DEFAULT NULL,
  `motivo` enum('EXCESSO_PREPARO','REJEICAO_ALUNOS','VALIDADE_VENCIDA','PREPARO_INCORRETO','OUTROS') DEFAULT 'OUTROS',
  `motivo_detalhado` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `produto_id` (`produto_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_desperdicio_data` (`data`),
  KEY `idx_desperdicio_motivo` (`motivo`),
  KEY `idx_desperdicio_escola_data` (`escola_id`,`data`),
  CONSTRAINT `desperdicio_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `desperdicio_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`) ON DELETE SET NULL,
  CONSTRAINT `desperdicio_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `desperdicio`
LOCK TABLES `desperdicio` WRITE;
/*!40000 ALTER TABLE `desperdicio` DISABLE KEYS */;
INSERT INTO `desperdicio` (`id`, `escola_id`, `data`, `turno`, `produto_id`, `quantidade`, `unidade_medida`, `peso_kg`, `motivo`, `motivo_detalhado`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`) VALUES ('1', '3', '2025-11-26', 'MANHA', '1023', '2.500', 'KG', '2.500', 'EXCESSO_PREPARO', 'Foi preparado mais arroz do que necessário', 'Reduzir quantidade na próxima vez', '1', '2025-11-26 00:00:00', '2025-12-01 21:00:11');
INSERT INTO `desperdicio` (`id`, `escola_id`, `data`, `turno`, `produto_id`, `quantidade`, `unidade_medida`, `peso_kg`, `motivo`, `motivo_detalhado`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`) VALUES ('2', '3', '2025-11-28', 'TARDE', '1024', '1.200', 'KG', '1.200', 'REJEICAO_ALUNOS', 'Alunos não gostaram do feijão', 'Verificar tempero e qualidade', '1', '2025-11-28 00:00:00', '2025-12-01 21:00:11');
INSERT INTO `desperdicio` (`id`, `escola_id`, `data`, `turno`, `produto_id`, `quantidade`, `unidade_medida`, `peso_kg`, `motivo`, `motivo_detalhado`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`) VALUES ('3', '3', '2025-11-29', 'MANHA', '1034', '3.000', 'KG', '3.000', 'VALIDADE_VENCIDA', 'Batatas começaram a estragar', 'Verificar estoque com mais frequência', '1', '2025-11-29 00:00:00', '2025-12-01 21:00:11');
INSERT INTO `desperdicio` (`id`, `escola_id`, `data`, `turno`, `produto_id`, `quantidade`, `unidade_medida`, `peso_kg`, `motivo`, `motivo_detalhado`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`) VALUES ('4', '3', '2025-11-30', 'TARDE', '1031', '5.000', 'L', '5.000', 'PREPARO_INCORRETO', 'Leite foi preparado em excesso', NULL, '1', '2025-11-30 00:00:00', '2025-12-01 21:00:11');
/*!40000 ALTER TABLE `desperdicio` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `disciplina`
-- ============================================================

DROP TABLE IF EXISTS `disciplina`;

CREATE TABLE `disciplina` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `carga_horaria` int(11) DEFAULT NULL,
  `area_conhecimento` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_disciplina_ativo` (`ativo`),
  KEY `idx_disciplina_area` (`area_conhecimento`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `disciplina`
LOCK TABLES `disciplina` WRITE;
/*!40000 ALTER TABLE `disciplina` DISABLE KEYS */;
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('1', 'PORT', 'Língua Portuguesa', NULL, '160', NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57');
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('2', 'MAT', 'Matemática', NULL, '160', NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57');
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('3', 'HIST', 'História', NULL, '80', NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57');
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('4', 'GEO', 'Geografia', NULL, '80', NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57');
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('5', 'CIEN', 'Ciências', NULL, '80', NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57');
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('6', 'EDF', 'Educação Física', NULL, '80', NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57');
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('7', 'ART', 'Artes', NULL, '40', NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57');
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES ('8', 'ING', 'Língua Inglesa', NULL, '40', NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57');
/*!40000 ALTER TABLE `disciplina` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `entrega`
-- ============================================================

DROP TABLE IF EXISTS `entrega`;

CREATE TABLE `entrega` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pedido_cesta_id` bigint(20) DEFAULT NULL,
  `escola_id` bigint(20) NOT NULL,
  `fornecedor_id` bigint(20) DEFAULT NULL,
  `data_prevista` date NOT NULL,
  `data_entrega` date DEFAULT NULL,
  `status` enum('AGENDADA','EM_TRANSITO','ENTREGUE','CANCELADA','ATRASADA') DEFAULT 'AGENDADA',
  `transportadora` varchar(255) DEFAULT NULL,
  `nota_fiscal` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `recebido_por` bigint(20) DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pedido_cesta_id` (`pedido_cesta_id`),
  KEY `escola_id` (`escola_id`),
  KEY `fornecedor_id` (`fornecedor_id`),
  KEY `recebido_por` (`recebido_por`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_entrega_status` (`status`),
  KEY `idx_entrega_data_prevista` (`data_prevista`),
  KEY `idx_entrega_data_entrega` (`data_entrega`),
  CONSTRAINT `entrega_ibfk_1` FOREIGN KEY (`pedido_cesta_id`) REFERENCES `pedido_cesta` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entrega_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `entrega_ibfk_3` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entrega_ibfk_4` FOREIGN KEY (`recebido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entrega_ibfk_5` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `entrega`
LOCK TABLES `entrega` WRITE;
/*!40000 ALTER TABLE `entrega` DISABLE KEYS */;
INSERT INTO `entrega` (`id`, `pedido_cesta_id`, `escola_id`, `fornecedor_id`, `data_prevista`, `data_entrega`, `status`, `transportadora`, `nota_fiscal`, `observacoes`, `recebido_por`, `registrado_por`, `registrado_em`, `atualizado_em`) VALUES ('8', NULL, '17', '1002', '3322-02-23', NULL, 'AGENDADA', 'marapa', '23232232323', NULL, NULL, NULL, '2025-12-09 15:00:16', '2025-12-09 15:00:16');
/*!40000 ALTER TABLE `entrega` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `entrega_item`
-- ============================================================

DROP TABLE IF EXISTS `entrega_item`;

CREATE TABLE `entrega_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `entrega_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade_solicitada` decimal(10,3) DEFAULT NULL,
  `quantidade_entregue` decimal(10,3) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entrega_id` (`entrega_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `entrega_item_ibfk_1` FOREIGN KEY (`entrega_id`) REFERENCES `entrega` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entrega_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `entrega_item`
LOCK TABLES `entrega_item` WRITE;
/*!40000 ALTER TABLE `entrega_item` DISABLE KEYS */;
INSERT INTO `entrega_item` (`id`, `entrega_id`, `produto_id`, `quantidade_solicitada`, `quantidade_entregue`, `observacoes`) VALUES ('8', '8', '1013', '23.000', '0.000', NULL);
/*!40000 ALTER TABLE `entrega_item` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `escola`
-- ============================================================

DROP TABLE IF EXISTS `escola`;

CREATE TABLE `escola` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT 'CE',
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `telefone_secundario` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `diretor_id` bigint(20) DEFAULT NULL,
  `qtd_salas` int(11) DEFAULT NULL,
  `obs` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  UNIQUE KEY `cnpj` (`cnpj`),
  KEY `diretor_id` (`diretor_id`),
  KEY `idx_escola_ativo` (`ativo`),
  KEY `idx_escola_municipio` (`municipio`),
  KEY `idx_escola_estado` (`estado`),
  CONSTRAINT `escola_ibfk_2` FOREIGN KEY (`diretor_id`) REFERENCES `gestor` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `escola`
LOCK TABLES `escola` WRITE;
/*!40000 ALTER TABLE `escola` DISABLE KEYS */;
INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `obs`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('3', NULL, 'escolatal', 'Rua rua rua', NULL, NULL, NULL, 'maranguape', 'CE', '22222-222', '(85) 9999-9922', NULL, 'escola@gmail.com', NULL, NULL, NULL, '6', 'aaa', '1', '2025-09-23 14:46:30', '2025-11-29 19:13:59', NULL);
INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `obs`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('4', NULL, 'escolatalas', 'qr3e', NULL, NULL, NULL, 'itapebusi', 'CE', '12323-123', '(85) 9999-9277', NULL, 'weeescola@gmail.com', NULL, NULL, NULL, '44', 'aaa', '1', '2025-09-23 14:57:45', '2025-11-29 19:13:59', NULL);
INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `obs`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('14', '3434343', 'yudi', 'Rua Joaninha Vieira', NULL, NULL, NULL, 'Maranguape', 'CE', '61943-290', '(85) 9999-9922', NULL, 'yudipro859@gmail.com', NULL, NULL, NULL, '12', 'adasdwaddwdad', '1', '2025-09-24 16:05:44', '2025-11-29 19:13:59', NULL);
INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `obs`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('15', '', 'teste do erro com o gestor consertado', 'Rua Joaninha Vieira', NULL, NULL, NULL, 'Maranguape', 'CE', '61943-290', '(85) 9999-9277', NULL, 'assa@gmail.com', NULL, NULL, NULL, '55', 'teste pra ver se o gestor ta funcionando', '1', '2025-09-24 16:06:24', '2025-11-29 19:13:59', NULL);
INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `obs`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('16', '243434', 'escola do Raimundo ', 'Rua Joaninha Vieira', NULL, NULL, NULL, 'Maranguape', 'CE', '61943-290', '(85) 9999-9933', NULL, 'yudipro859@gmail.com', NULL, NULL, NULL, '8', '', '1', '2025-09-25 14:23:57', '2025-11-29 19:13:59', NULL);
INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `obs`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('17', '12345678', 'Escola Municipal de Teste - SIGAE', 'Rua das Flores, 123 - Centro', NULL, NULL, NULL, 'Maranguape', 'CE', '61940-000', '(85) 3333-4444', NULL, 'escola.teste@sigae.com', NULL, NULL, NULL, '20', 'Escola criada para testes do sistema SIGAE', '1', '2025-11-30 23:34:56', '2025-11-30 23:34:56', NULL);
/*!40000 ALTER TABLE `escola` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `estoque_central`
-- ============================================================

DROP TABLE IF EXISTS `estoque_central`;

CREATE TABLE `estoque_central` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(12,3) DEFAULT NULL,
  `lote` varchar(100) DEFAULT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `fornecedor_id` bigint(20) DEFAULT NULL,
  `nota_fiscal` varchar(50) DEFAULT NULL,
  `valor_unitario` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(12,2) DEFAULT NULL,
  `criado_por` bigint(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `validade` date DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  KEY `criado_por` (`criado_por`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_estoque_validade` (`validade`),
  KEY `idx_estoque_lote` (`lote`),
  KEY `idx_estoque_fornecedor` (`fornecedor`),
  KEY `fornecedor_id` (`fornecedor_id`),
  CONSTRAINT `estoque_central_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`),
  CONSTRAINT `estoque_central_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `estoque_central_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `estoque_central_ibfk_4` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1024 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `estoque_central`
LOCK TABLES `estoque_central` WRITE;
/*!40000 ALTER TABLE `estoque_central` DISABLE KEYS */;
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1001', '1001', '500.000', 'LOTE-2024-001', NULL, '1001', 'NF-001234', '4.50', '2250.00', NULL, '2025-12-01 20:45:56', '2025-12-31', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1002', '1002', '400.000', 'LOTE-2024-002', NULL, '1001', 'NF-001235', '8.00', '3200.00', NULL, '2025-12-01 20:45:56', '2025-12-31', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1003', '1003', '300.000', 'LOTE-2024-003', NULL, '1001', 'NF-001236', '5.50', '1650.00', NULL, '2025-12-01 20:45:56', '2025-12-31', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1004', '1006', '150.000', 'LOTE-2024-004', NULL, '1002', 'NF-002345', '28.00', '4200.00', NULL, '2025-12-01 20:45:56', '2025-01-15', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1005', '1007', '200.000', 'LOTE-2024-005', NULL, '1002', 'NF-002346', '12.00', '2400.00', NULL, '2025-12-01 20:45:56', '2025-01-20', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1006', '1009', '1000.000', 'LOTE-2024-006', NULL, '1004', 'NF-003456', '4.20', '4200.00', NULL, '2025-12-01 20:45:56', '2025-01-10', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1007', '1012', '300.000', 'LOTE-2024-007', NULL, '1003', 'NF-004567', '3.50', '1050.00', NULL, '2025-12-01 20:45:56', '2025-01-25', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1008', '1017', '200.000', 'LOTE-2024-008', NULL, '1001', 'NF-001237', '8.50', '1700.00', NULL, '2025-12-01 20:45:56', '2026-06-30', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1009', '1005', '15.000', 'LOTE-2024-009', NULL, '1001', 'NF-001238', '3.80', '57.00', NULL, '2025-12-01 20:45:56', '2025-12-31', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1010', '1011', '5.000', 'LOTE-2024-010', NULL, '1004', 'NF-003457', '18.00', '90.00', NULL, '2025-12-01 20:45:56', '2025-02-28', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1011', '1013', '8.000', 'LOTE-2024-011', NULL, '1003', 'NF-004568', '4.00', '32.00', NULL, '2025-12-01 20:45:56', '2025-01-20', '2025-12-01 20:45:56', NULL);
INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES ('1023', '1020', '3.000', '23', NULL, '1003', '2334343434', '3.00', '9.00', NULL, '2025-12-09 14:03:12', '2026-03-03', '2025-12-09 14:03:12', NULL);
/*!40000 ALTER TABLE `estoque_central` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `fornecedor`
-- ============================================================

DROP TABLE IF EXISTS `fornecedor`;

CREATE TABLE `fornecedor` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `inscricao_estadual` varchar(50) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `telefone_secundario` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contato` varchar(255) DEFAULT NULL,
  `tipo_fornecedor` enum('ALIMENTOS','BEBIDAS','MATERIAIS','SERVICOS','OUTROS') DEFAULT 'ALIMENTOS',
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_fornecedor_ativo` (`ativo`),
  KEY `idx_fornecedor_tipo` (`tipo_fornecedor`),
  KEY `idx_fornecedor_cidade` (`cidade`),
  CONSTRAINT `fornecedor_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1015 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `fornecedor`
LOCK TABLES `fornecedor` WRITE;
/*!40000 ALTER TABLE `fornecedor` DISABLE KEYS */;
INSERT INTO `fornecedor` (`id`, `nome`, `razao_social`, `cnpj`, `inscricao_estadual`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `contato`, `tipo_fornecedor`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1001', 'Distribuidora Alimentos Maranguape', 'Distribuidora Alimentos Maranguape LTDA', '12.345.678/0001-90', '123456789', 'Av. Principal', '500', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', '(85) 3333-1111', NULL, 'contato@alimentosmaranguape.com.br', 'João Silva', 'ALIMENTOS', NULL, '1', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL);
INSERT INTO `fornecedor` (`id`, `nome`, `razao_social`, `cnpj`, `inscricao_estadual`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `contato`, `tipo_fornecedor`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1002', 'Carnes Premium', 'Carnes Premium EIRELI', '23.456.789/0001-01', '234567890', 'Rua dos Açougues', '200', NULL, 'Industrial', 'Fortaleza', 'CE', '60000-000', '(85) 3333-2222', NULL, 'vendas@carnespremium.com.br', 'Maria Santos', 'ALIMENTOS', NULL, '1', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL);
INSERT INTO `fornecedor` (`id`, `nome`, `razao_social`, `cnpj`, `inscricao_estadual`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `contato`, `tipo_fornecedor`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1003', 'Hortifruti Verde Vida', 'Hortifruti Verde Vida ME', '34.567.890/0001-12', '345678901', 'Rodovia BR-116', 'KM 15', NULL, 'Zona Rural', 'Maranguape', 'CE', '61940-000', '(85) 3333-3333', NULL, 'compras@verdevida.com.br', 'Pedro Oliveira', 'ALIMENTOS', NULL, '1', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL);
INSERT INTO `fornecedor` (`id`, `nome`, `razao_social`, `cnpj`, `inscricao_estadual`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `contato`, `tipo_fornecedor`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1004', 'Laticínios do Nordeste', 'Laticínios do Nordeste S.A.', '45.678.901/0001-23', '456789012', 'Av. Industrial', '1000', NULL, 'Distrito Industrial', 'Fortaleza', 'CE', '60000-000', '(85) 3333-4444', NULL, 'comercial@laticiniosne.com.br', 'Ana Costa', 'ALIMENTOS', NULL, '1', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL);
INSERT INTO `fornecedor` (`id`, `nome`, `razao_social`, `cnpj`, `inscricao_estadual`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `contato`, `tipo_fornecedor`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1005', 'Bebidas e Refrigerantes CE', 'Bebidas e Refrigerantes CE LTDA', '56.789.012/0001-34', '567890123', 'Rua Comercial', '300', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', '(85) 3333-5555', NULL, 'pedidos@bebidasce.com.br', 'Carlos Mendes', 'BEBIDAS', NULL, '1', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL);
INSERT INTO `fornecedor` (`id`, `nome`, `razao_social`, `cnpj`, `inscricao_estadual`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `contato`, `tipo_fornecedor`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1014', 'bolacha itapebussu', 'null', '87438437434334', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '85343433434', NULL, NULL, NULL, 'ALIMENTOS', 'null', '1', '2025-12-09 14:43:11', '2025-12-09 14:43:11', NULL);
/*!40000 ALTER TABLE `fornecedor` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `frequencia`
-- ============================================================

DROP TABLE IF EXISTS `frequencia`;

CREATE TABLE `frequencia` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `data` date NOT NULL,
  `presenca` tinyint(1) NOT NULL,
  `observacao` text DEFAULT NULL,
  `validado` tinyint(1) DEFAULT 0,
  `validado_por` bigint(20) DEFAULT NULL,
  `data_validacao` timestamp NULL DEFAULT NULL,
  `justificativa_id` bigint(20) DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_frequencia_aluno_data` (`aluno_id`,`turma_id`,`data`),
  KEY `aluno_id` (`aluno_id`),
  KEY `turma_id` (`turma_id`),
  KEY `justificativa_id` (`justificativa_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_frequencia_data` (`data`),
  KEY `idx_frequencia_presenca` (`presenca`),
  KEY `validado_por` (`validado_por`),
  CONSTRAINT `frequencia_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `frequencia_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `frequencia_ibfk_3` FOREIGN KEY (`justificativa_id`) REFERENCES `justificativa` (`id`) ON DELETE SET NULL,
  CONSTRAINT `frequencia_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `frequencia_ibfk_5` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `frequencia_ibfk_6` FOREIGN KEY (`validado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `frequencia`
LOCK TABLES `frequencia` WRITE;
/*!40000 ALTER TABLE `frequencia` DISABLE KEYS */;
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('1', '2', '1', '2025-12-08', '0', NULL, '0', NULL, NULL, NULL, '2', '2025-12-08 15:48:14', '2025-12-08 15:54:10', '2');
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('2', '3', '1', '2025-12-08', '0', NULL, '0', NULL, NULL, NULL, '2', '2025-12-08 15:48:14', '2025-12-08 15:54:10', '2');
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('3', '4', '1', '2025-12-08', '1', NULL, '0', NULL, NULL, NULL, '2', '2025-12-08 15:48:14', '2025-12-08 15:54:11', '2');
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('4', '5', '1', '2025-12-08', '1', NULL, '0', NULL, NULL, NULL, '2', '2025-12-08 15:48:14', '2025-12-08 15:54:11', '2');
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('5', '6', '1', '2025-12-08', '1', NULL, '0', NULL, NULL, NULL, '2', '2025-12-08 15:48:14', '2025-12-08 15:54:11', '2');
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('11', '2', '1', '2025-12-09', '1', NULL, '0', NULL, NULL, NULL, '2', '2025-12-09 09:11:30', '2025-12-09 09:11:58', NULL);
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('12', '3', '1', '2025-12-09', '0', NULL, '0', NULL, NULL, NULL, '2', '2025-12-09 09:11:30', '2025-12-09 09:11:58', NULL);
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('13', '4', '1', '2025-12-09', '0', NULL, '0', NULL, NULL, NULL, '2', '2025-12-09 09:11:30', '2025-12-09 09:11:58', NULL);
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('14', '5', '1', '2025-12-09', '0', NULL, '0', NULL, NULL, NULL, '2', '2025-12-09 09:11:30', '2025-12-09 09:11:58', NULL);
INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES ('15', '6', '1', '2025-12-09', '0', 'Falta justificada', '0', NULL, NULL, NULL, '2', '2025-12-09 09:11:30', '2025-12-09 09:11:58', NULL);
/*!40000 ALTER TABLE `frequencia` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `funcionario`
-- ============================================================

DROP TABLE IF EXISTS `funcionario`;

CREATE TABLE `funcionario` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pessoa_id` bigint(20) NOT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `setor` varchar(100) DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `data_demissao` date DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `pessoa_id` (`pessoa_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_funcionario_ativo` (`ativo`),
  KEY `idx_funcionario_cargo` (`cargo`),
  KEY `idx_funcionario_setor` (`setor`),
  CONSTRAINT `funcionario_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `funcionario_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `funcionario_lotacao`
-- ============================================================

DROP TABLE IF EXISTS `funcionario_lotacao`;

CREATE TABLE `funcionario_lotacao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `funcionario_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `setor` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `funcionario_id` (`funcionario_id`),
  KEY `escola_id` (`escola_id`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `funcionario_lotacao_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`),
  CONSTRAINT `funcionario_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `funcionario_lotacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `gestor`
-- ============================================================

DROP TABLE IF EXISTS `gestor`;

CREATE TABLE `gestor` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pessoa_id` bigint(20) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `formacao` text DEFAULT NULL,
  `registro_profissional` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `pessoa_id` (`pessoa_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_gestor_ativo` (`ativo`),
  CONSTRAINT `gestor_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `gestor_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `gestor`
LOCK TABLES `gestor` WRITE;
/*!40000 ALTER TABLE `gestor` DISABLE KEYS */;
INSERT INTO `gestor` (`id`, `pessoa_id`, `cargo`, `formacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('1', '4', 'gestor', NULL, NULL, NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL, '1');
INSERT INTO `gestor` (`id`, `pessoa_id`, `cargo`, `formacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('2', '5', 'gestor', NULL, NULL, NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL, '1');
INSERT INTO `gestor` (`id`, `pessoa_id`, `cargo`, `formacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('3', '11', 'Diretor', NULL, NULL, NULL, '2025-11-30 23:34:57', '2025-11-30 23:34:57', NULL, '1');
INSERT INTO `gestor` (`id`, `pessoa_id`, `cargo`, `formacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('4', '11', 'Diretor', NULL, NULL, NULL, '2025-11-30 23:40:03', '2025-12-03 16:02:39', NULL, '0');
/*!40000 ALTER TABLE `gestor` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `gestor_lotacao`
-- ============================================================

DROP TABLE IF EXISTS `gestor_lotacao`;

CREATE TABLE `gestor_lotacao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `gestor_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `responsavel` tinyint(1) DEFAULT NULL,
  `tipo` enum('Diretor','Vice-Diretor','Coordenador PedagÃ³gico','SecretÃ¡rio Escolar') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gestor_id` (`gestor_id`),
  KEY `escola_id` (`escola_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_gestor_lotacao_responsavel` (`responsavel`),
  KEY `idx_gestor_lotacao_tipo` (`tipo`),
  KEY `idx_gestor_lotacao_escola` (`escola_id`),
  CONSTRAINT `gestor_lotacao_ibfk_1` FOREIGN KEY (`gestor_id`) REFERENCES `gestor` (`id`),
  CONSTRAINT `gestor_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `gestor_lotacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `gestor_lotacao`
LOCK TABLES `gestor_lotacao` WRITE;
/*!40000 ALTER TABLE `gestor_lotacao` DISABLE KEYS */;
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('9', '1', '14', '2025-09-24', NULL, '1', 'Diretor', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('10', '1', '15', '2025-09-24', NULL, '1', 'Vice-Diretor', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('12', '2', '16', '2025-10-01', NULL, '1', 'Coordenador PedagÃ³gico', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('13', '2', '14', '2025-10-01', NULL, '1', 'SecretÃ¡rio Escolar', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('14', '2', '14', '2025-10-01', NULL, '1', NULL, NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('15', '2', '14', '2025-10-01', NULL, '1', NULL, NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('16', '1', '14', '2025-10-01', NULL, '1', NULL, NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('17', '2', '14', '2025-10-01', NULL, '1', NULL, NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('18', '1', '3', '2025-10-03', NULL, '1', NULL, NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('19', '2', '4', '2025-10-13', NULL, '1', 'Coordenador PedagÃ³gico', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('20', '1', '4', '2025-10-13', NULL, '1', 'Vice-Diretor', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('21', '2', '4', '2025-10-13', NULL, '1', 'Coordenador PedagÃ³gico', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('22', '2', '4', '2025-10-13', NULL, '1', 'Coordenador PedagÃ³gico', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('23', '2', '4', '2025-10-13', NULL, '1', 'Coordenador PedagÃ³gico', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('24', '2', '4', '2025-10-13', NULL, '1', 'Coordenador PedagÃ³gico', NULL, '2025-11-29 19:14:01', '2025-11-29 19:14:01', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('25', '3', '17', '2025-11-30', NULL, '1', 'Diretor', NULL, '2025-11-30 23:34:57', '2025-11-30 23:34:57', NULL);
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('26', '3', '17', '2025-11-30', NULL, '1', 'Diretor', NULL, '2025-11-30 23:40:03', '2025-11-30 23:40:03', NULL);
/*!40000 ALTER TABLE `gestor_lotacao` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `historico_escolar`
-- ============================================================

DROP TABLE IF EXISTS `historico_escolar`;

CREATE TABLE `historico_escolar` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `ano_letivo` int(11) NOT NULL,
  `serie` varchar(50) DEFAULT NULL,
  `turma` varchar(50) DEFAULT NULL,
  `situacao` enum('APROVADO','REPROVADO','TRANSFERIDO','ABANDONO','CONCLUIDO') DEFAULT NULL,
  `media_geral` decimal(5,2) DEFAULT NULL,
  `frequencia_percentual` decimal(5,2) DEFAULT NULL,
  `total_dias_letivos` int(11) DEFAULT NULL,
  `total_faltas` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `gerado_por` bigint(20) DEFAULT NULL,
  `gerado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `escola_id` (`escola_id`),
  KEY `gerado_por` (`gerado_por`),
  KEY `idx_historico_ano` (`ano_letivo`),
  KEY `idx_historico_situacao` (`situacao`),
  CONSTRAINT `historico_escolar_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `historico_escolar_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `historico_escolar_ibfk_3` FOREIGN KEY (`gerado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `indicador_nutricional`
-- ============================================================

DROP TABLE IF EXISTS `indicador_nutricional`;

CREATE TABLE `indicador_nutricional` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `escola_id` bigint(20) DEFAULT NULL,
  `cardapio_id` bigint(20) DEFAULT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fim` date NOT NULL,
  `tipo_indicador` enum('VARIEDADE','VALOR_NUTRICIONAL','PORCOES','DESPERDICIO','SAZONALIDADE','OUTROS') NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `unidade` varchar(20) DEFAULT NULL,
  `meta` decimal(10,2) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `cardapio_id` (`cardapio_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_indicador_tipo` (`tipo_indicador`),
  KEY `idx_indicador_periodo` (`periodo_inicio`,`periodo_fim`),
  CONSTRAINT `indicador_nutricional_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `indicador_nutricional_ibfk_2` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapio` (`id`) ON DELETE SET NULL,
  CONSTRAINT `indicador_nutricional_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Indicadores nutricionais acompanhados pelo nutricionista';


-- ============================================================
-- Estrutura da tabela `justificativa`
-- ============================================================

DROP TABLE IF EXISTS `justificativa`;

CREATE TABLE `justificativa` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` bigint(20) NOT NULL,
  `enviado_por` bigint(20) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `status` enum('PENDENTE','APROVADA','REJEITADA') DEFAULT 'PENDENTE',
  `arquivo_url` varchar(500) DEFAULT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `analisado_por` bigint(20) DEFAULT NULL,
  `analise` text DEFAULT NULL,
  `data_analise` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `enviado_por` (`enviado_por`),
  KEY `analisado_por` (`analisado_por`),
  KEY `idx_justificativa_status` (`status`),
  KEY `idx_justificativa_data_envio` (`data_envio`),
  CONSTRAINT `justificativa_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `justificativa_ibfk_2` FOREIGN KEY (`enviado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `justificativa_ibfk_3` FOREIGN KEY (`analisado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `log_sistema`
-- ============================================================

DROP TABLE IF EXISTS `log_sistema`;

CREATE TABLE `log_sistema` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `tipo` enum('INFO','WARNING','ERROR','SECURITY') DEFAULT 'INFO',
  `descricao` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `tipo` (`tipo`),
  KEY `criado_em` (`criado_em`),
  KEY `idx_log_acao` (`acao`),
  KEY `idx_log_tipo_acao` (`tipo`,`acao`),
  KEY `idx_log_usuario_tipo` (`usuario_id`,`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `movimentacao_estoque`
-- ============================================================

DROP TABLE IF EXISTS `movimentacao_estoque`;

CREATE TABLE `movimentacao_estoque` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,0) DEFAULT NULL,
  `tipo` enum('ENTRADA','SAIDA','RESERVA','AJUSTE') DEFAULT NULL,
  `referencia_id` bigint(20) DEFAULT NULL,
  `referencia_tipo` varchar(50) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `valor_unitario` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(12,2) DEFAULT NULL,
  `realizado_por` bigint(20) DEFAULT NULL,
  `realizado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `realizado_por` (`realizado_por`),
  KEY `produto_id` (`produto_id`),
  KEY `idx_movimentacao_tipo` (`tipo`),
  KEY `idx_movimentacao_data` (`realizado_em`),
  KEY `idx_movimentacao_produto_tipo` (`produto_id`,`tipo`),
  CONSTRAINT `movimentacao_estoque_ibfk_1` FOREIGN KEY (`realizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `movimentacao_estoque_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `nota`
-- ============================================================

DROP TABLE IF EXISTS `nota`;

CREATE TABLE `nota` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `avaliacao_id` bigint(20) DEFAULT NULL,
  `disciplina_id` bigint(20) DEFAULT NULL,
  `turma_id` bigint(20) DEFAULT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `bimestre` int(11) DEFAULT NULL,
  `recuperacao` tinyint(1) DEFAULT 0,
  `validado` tinyint(1) DEFAULT 0,
  `validado_por` bigint(20) DEFAULT NULL,
  `data_validacao` timestamp NULL DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `lancado_por` bigint(20) DEFAULT NULL,
  `lancado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `avaliacao_id` (`avaliacao_id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `lancado_por` (`lancado_por`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `turma_id` (`turma_id`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_nota_bimestre` (`bimestre`),
  KEY `idx_nota_recuperacao` (`recuperacao`),
  KEY `idx_nota_aluno_disciplina` (`aluno_id`,`disciplina_id`),
  KEY `validado_por` (`validado_por`),
  CONSTRAINT `nota_ibfk_1` FOREIGN KEY (`avaliacao_id`) REFERENCES `avaliacao` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nota_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `nota_ibfk_3` FOREIGN KEY (`lancado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nota_ibfk_4` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nota_ibfk_5` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nota_ibfk_6` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `nota_ibfk_7` FOREIGN KEY (`validado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `nota`
LOCK TABLES `nota` WRITE;
/*!40000 ALTER TABLE `nota` DISABLE KEYS */;
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('1', '17', '2', '1', '2', '5.50', '1', '0', '0', NULL, NULL, '', '2', '2025-12-09 08:28:21', '2025-12-09 08:53:31', '2');
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('2', '18', '2', '1', '2', '10.00', '1', '0', '0', NULL, NULL, '', '2', '2025-12-09 08:28:21', '2025-12-09 08:53:31', '2');
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('3', '17', '2', '1', '3', '6.00', '1', '0', '0', NULL, NULL, '6', '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', NULL);
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('4', '18', '2', '1', '3', '8.00', '1', '0', '0', NULL, NULL, '6', '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', NULL);
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('5', '17', '2', '1', '4', '8.00', '1', '0', '0', NULL, NULL, '8', '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', NULL);
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('6', '18', '2', '1', '4', '5.00', '1', '0', '0', NULL, NULL, '8', '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', NULL);
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('7', '17', '2', '1', '5', '6.00', '1', '0', '0', NULL, NULL, '6', '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', NULL);
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('8', '18', '2', '1', '5', '7.00', '1', '0', '0', NULL, NULL, '6', '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', NULL);
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('9', '17', '2', '1', '6', '8.00', '1', '0', '0', NULL, NULL, '8', '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', NULL);
INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES ('10', '18', '2', '1', '6', '4.00', '1', '0', '0', NULL, NULL, '8', '2', '2025-12-09 08:28:21', '2025-12-09 08:28:21', NULL);
/*!40000 ALTER TABLE `nota` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `nutricionista`
-- ============================================================

DROP TABLE IF EXISTS `nutricionista`;

CREATE TABLE `nutricionista` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pessoa_id` bigint(20) NOT NULL,
  `crn` varchar(20) DEFAULT NULL COMMENT 'Conselho Regional de Nutricionistas',
  `formacao` text DEFAULT NULL,
  `especializacao` text DEFAULT NULL,
  `registro_profissional` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crn` (`crn`),
  KEY `pessoa_id` (`pessoa_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_nutricionista_ativo` (`ativo`),
  CONSTRAINT `nutricionista_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `nutricionista_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabela de nutricionistas do sistema';

-- Dados da tabela `nutricionista`
LOCK TABLES `nutricionista` WRITE;
/*!40000 ALTER TABLE `nutricionista` DISABLE KEYS */;
INSERT INTO `nutricionista` (`id`, `pessoa_id`, `crn`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1', '47', 'CRN-12345', 'Bacharelado em Nutrição - UFC', 'Especialização em Nutrição Escolar', '12345', 'Nutricionista com experiência em alimentação escolar e PNAE', '1', '2025-12-08 10:12:49', '2025-12-08 10:12:49', NULL);
/*!40000 ALTER TABLE `nutricionista` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `nutricionista_lotacao`
-- ============================================================

DROP TABLE IF EXISTS `nutricionista_lotacao`;

CREATE TABLE `nutricionista_lotacao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `nutricionista_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `responsavel` tinyint(1) DEFAULT 0 COMMENT '1 = nutricionista responsável pela escola',
  `carga_horaria` int(11) DEFAULT NULL COMMENT 'Carga horária semanal',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nutricionista_id` (`nutricionista_id`),
  KEY `escola_id` (`escola_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_nutricionista_lotacao_responsavel` (`responsavel`),
  KEY `idx_nutricionista_lotacao_escola` (`escola_id`),
  KEY `idx_nutricionista_lotacao_inicio` (`inicio`),
  CONSTRAINT `nutricionista_lotacao_ibfk_1` FOREIGN KEY (`nutricionista_id`) REFERENCES `nutricionista` (`id`),
  CONSTRAINT `nutricionista_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `nutricionista_lotacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lotação de nutricionistas em escolas';

-- Dados da tabela `nutricionista_lotacao`
LOCK TABLES `nutricionista_lotacao` WRITE;
/*!40000 ALTER TABLE `nutricionista_lotacao` DISABLE KEYS */;
INSERT INTO `nutricionista_lotacao` (`id`, `nutricionista_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `carga_horaria`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1', '1', '17', '2025-12-08', NULL, '1', '40', 'Nutricionista responsável pela escola', '2025-12-08 10:12:49', '2025-12-08 10:12:49', NULL);
/*!40000 ALTER TABLE `nutricionista_lotacao` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `observacao_desempenho`
-- ============================================================

DROP TABLE IF EXISTS `observacao_desempenho`;

CREATE TABLE `observacao_desempenho` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) DEFAULT NULL,
  `professor_id` bigint(20) NOT NULL,
  `tipo` enum('COMPORTAMENTO','APRENDIZAGEM','PARTICIPACAO','DIFICULDADE','MELHORIA','OUTROS') DEFAULT 'OUTROS',
  `titulo` varchar(255) DEFAULT NULL,
  `observacao` text NOT NULL,
  `data` date NOT NULL,
  `bimestre` int(11) DEFAULT NULL,
  `visivel_responsavel` tinyint(1) DEFAULT 1,
  `criado_por` bigint(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `turma_id` (`turma_id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `professor_id` (`professor_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_observacao_data` (`data`),
  KEY `idx_observacao_tipo` (`tipo`),
  KEY `idx_observacao_bimestre` (`bimestre`),
  CONSTRAINT `observacao_desempenho_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `observacao_desempenho_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `observacao_desempenho_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`) ON DELETE SET NULL,
  CONSTRAINT `observacao_desempenho_ibfk_4` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  CONSTRAINT `observacao_desempenho_ibfk_5` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `observacao_desempenho`
LOCK TABLES `observacao_desempenho` WRITE;
/*!40000 ALTER TABLE `observacao_desempenho` DISABLE KEYS */;
INSERT INTO `observacao_desempenho` (`id`, `aluno_id`, `turma_id`, `disciplina_id`, `professor_id`, `tipo`, `titulo`, `observacao`, `data`, `bimestre`, `visivel_responsavel`, `criado_por`, `criado_em`, `atualizado_em`) VALUES ('1', '2', '1', '2', '7', 'APRENDIZAGEM', '', 'muito burra', '2025-12-09', '1', '1', '2', '2025-12-09 09:40:11', '2025-12-09 09:40:11');
/*!40000 ALTER TABLE `observacao_desempenho` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `pacote`
-- ============================================================

DROP TABLE IF EXISTS `pacote`;

CREATE TABLE `pacote` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `produto_id` bigint(20) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `criado_por` bigint(20) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `criado_por` (`criado_por`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `pacote_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `pacote_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `pacote_item`
-- ============================================================

DROP TABLE IF EXISTS `pacote_item`;

CREATE TABLE `pacote_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pacote_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pacote_id` (`pacote_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `pacote_item_ibfk_1` FOREIGN KEY (`pacote_id`) REFERENCES `pacote` (`id`),
  CONSTRAINT `pacote_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `parecer_tecnico`
-- ============================================================

DROP TABLE IF EXISTS `parecer_tecnico`;

CREATE TABLE `parecer_tecnico` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `nutricionista_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `cardapio_id` bigint(20) DEFAULT NULL,
  `tipo` enum('CARDAPIO','CONSUMO','DESPERDICIO','ADEQUACAO_NUTRICIONAL','OUTROS') DEFAULT 'OUTROS',
  `titulo` varchar(255) NOT NULL,
  `conteudo` text NOT NULL,
  `recomendacoes` text DEFAULT NULL,
  `status` enum('RASCUNHO','PUBLICADO','ARQUIVADO') DEFAULT 'RASCUNHO',
  `data_referencia` date DEFAULT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fim` date DEFAULT NULL,
  `anexo_url` varchar(500) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `nutricionista_id` (`nutricionista_id`),
  KEY `escola_id` (`escola_id`),
  KEY `cardapio_id` (`cardapio_id`),
  KEY `idx_parecer_tipo` (`tipo`),
  KEY `idx_parecer_status` (`status`),
  KEY `idx_parecer_data_referencia` (`data_referencia`),
  CONSTRAINT `parecer_tecnico_ibfk_1` FOREIGN KEY (`nutricionista_id`) REFERENCES `nutricionista` (`id`),
  CONSTRAINT `parecer_tecnico_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `parecer_tecnico_ibfk_3` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapio` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pareceres técnicos emitidos pelos nutricionistas';


-- ============================================================
-- Estrutura da tabela `password_reset_tokens`
-- ============================================================

DROP TABLE IF EXISTS `password_reset_tokens`;

CREATE TABLE `password_reset_tokens` (
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `password_reset_tokens`
LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` (`id`, `usuario_id`, `token`, `email`, `expira_em`, `usado`, `criado_em`) VALUES ('1', '8', 'c45a5968df80ab6a1f82a9538178eb1389fe8cb791e5c875a32a29f8103f5d54', 'gestor.teste@sigae.com', '2025-12-02 04:01:42', '1', '2025-12-01 00:01:42');
INSERT INTO `password_reset_tokens` (`id`, `usuario_id`, `token`, `email`, `expira_em`, `usado`, `criado_em`) VALUES ('2', '8', '0d30ccbc3111273eac40e711804eaadcb994a2cd4d006a3da15b31c209269a8c', 'gestor.teste@sigae.com', '2025-12-02 04:04:08', '1', '2025-12-01 00:04:08');
INSERT INTO `password_reset_tokens` (`id`, `usuario_id`, `token`, `email`, `expira_em`, `usado`, `criado_em`) VALUES ('3', '8', 'dd5b3610eae2ab0e20d79d9ca65e304c0def53a8aa6e0af5bef1d69964df6f8a', 'gestor.teste@sigae.com', '2025-12-02 04:30:21', '1', '2025-12-01 00:30:21');
INSERT INTO `password_reset_tokens` (`id`, `usuario_id`, `token`, `email`, `expira_em`, `usado`, `criado_em`) VALUES ('4', '8', '6c6c8de7bfb84ef7135960521ffe712c4ab8ac051e674bcb6b0a60507d2a21f2', 'gestor.teste@sigae.com', '2025-12-02 04:30:25', '0', '2025-12-01 00:30:25');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `pedido_cesta`
-- ============================================================

DROP TABLE IF EXISTS `pedido_cesta`;

CREATE TABLE `pedido_cesta` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `escola_id` bigint(20) NOT NULL,
  `mes` int(11) DEFAULT NULL,
  `nutricionista_id` bigint(20) DEFAULT NULL,
  `status` enum('RASCUHO','ENVIADO','APROVADO','REJEITADO','ENVIADO_A_ESCOLA') DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  `data_envio` timestamp NULL DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `data_entrega` date DEFAULT NULL,
  `entregue` tinyint(1) DEFAULT 0,
  `aprovado_por` bigint(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `motivo_rejeicao` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aprovado_por` (`aprovado_por`),
  KEY `escola_id` (`escola_id`),
  KEY `nutricionista_id` (`nutricionista_id`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_pedido_status` (`status`),
  KEY `idx_pedido_mes` (`mes`),
  KEY `idx_pedido_data_criacao` (`data_criacao`),
  KEY `idx_pedido_nutricionista_status` (`nutricionista_id`,`status`),
  CONSTRAINT `pedido_cesta_ibfk_1` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`),
  CONSTRAINT `pedido_cesta_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `pedido_cesta_ibfk_3` FOREIGN KEY (`nutricionista_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pedido_cesta_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `pedido_cesta`
LOCK TABLES `pedido_cesta` WRITE;
/*!40000 ALTER TABLE `pedido_cesta` DISABLE KEYS */;
INSERT INTO `pedido_cesta` (`id`, `escola_id`, `mes`, `nutricionista_id`, `status`, `data_criacao`, `atualizado_em`, `atualizado_por`, `data_envio`, `data_aprovacao`, `data_entrega`, `entregue`, `aprovado_por`, `observacoes`, `motivo_rejeicao`) VALUES ('1', '3', '4', NULL, 'ENVIADO', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, '2025-12-01 21:00:11', NULL, NULL, '0', NULL, 'Pedido para o mês de abril - aguardando aprovação do ADM_MERENDA', NULL);
INSERT INTO `pedido_cesta` (`id`, `escola_id`, `mes`, `nutricionista_id`, `status`, `data_criacao`, `atualizado_em`, `atualizado_por`, `data_envio`, `data_aprovacao`, `data_entrega`, `entregue`, `aprovado_por`, `observacoes`, `motivo_rejeicao`) VALUES ('2', '3', '5', NULL, 'APROVADO', '2025-11-21 00:00:00', '2025-12-01 21:00:11', NULL, '2025-11-23 00:00:00', '2025-11-26 00:00:00', NULL, '0', '1', 'Pedido aprovado e enviado para fornecedor', NULL);
INSERT INTO `pedido_cesta` (`id`, `escola_id`, `mes`, `nutricionista_id`, `status`, `data_criacao`, `atualizado_em`, `atualizado_por`, `data_envio`, `data_aprovacao`, `data_entrega`, `entregue`, `aprovado_por`, `observacoes`, `motivo_rejeicao`) VALUES ('3', '3', '6', NULL, 'REJEITADO', '2025-11-16 00:00:00', '2025-12-01 21:00:11', NULL, '2025-11-19 00:00:00', NULL, NULL, '0', NULL, 'Pedido rejeitado - valores acima do orçamento', NULL);
/*!40000 ALTER TABLE `pedido_cesta` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `pedido_item`
-- ============================================================

DROP TABLE IF EXISTS `pedido_item`;

CREATE TABLE `pedido_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pedido_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade_solicitada` decimal(10,0) DEFAULT NULL,
  `quantidade_atendida` decimal(10,0) DEFAULT NULL,
  `obs` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `pedido_item_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedido_cesta` (`id`),
  CONSTRAINT `pedido_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `pedido_item`
LOCK TABLES `pedido_item` WRITE;
/*!40000 ALTER TABLE `pedido_item` DISABLE KEYS */;
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('1', '1', '1023', '60', NULL, NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('2', '1', '1024', '35', NULL, NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('3', '1', '1028', '25', NULL, NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('4', '1', '1031', '120', NULL, NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('5', '2', '1023', '55', '55', NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('6', '2', '1024', '33', '33', NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('7', '2', '1029', '20', '20', NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('8', '2', '1031', '115', '115', NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('9', '3', '1023', '100', NULL, NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('10', '3', '1024', '60', NULL, NULL);
INSERT INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`, `obs`) VALUES ('11', '3', '1028', '50', NULL, NULL);
/*!40000 ALTER TABLE `pedido_item` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `pessoa`
-- ============================================================

DROP TABLE IF EXISTS `pessoa`;

CREATE TABLE `pessoa` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `cpf` char(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `sexo` char(1) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `telefone_secundario` varchar(30) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `tipo` enum('ALUNO','PROFESSOR','GESTOR','FUNCIONARIO','RESPONSAVEL','NUTRICIONISTA','OUTRO') DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`),
  KEY `idx_pessoa_tipo` (`tipo`),
  KEY `idx_pessoa_ativo` (`ativo`),
  KEY `idx_pessoa_cidade` (`cidade`),
  KEY `idx_pessoa_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `pessoa`
LOCK TABLES `pessoa` WRITE;
/*!40000 ALTER TABLE `pessoa` DISABLE KEYS */;
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('1', '11111111111', 'Roger', NULL, 'M', 'cavalcanterogeer@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-29 19:13:59', '2025-11-29 19:13:59', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('2', '11970867302', 'Francisco lavosier Silva Nascimento', '2001-04-20', NULL, 'slavosier298@gmail.com', '(85) 98948-2053', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 19:13:59', '2025-11-29 19:13:59', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('3', '12345678901', 'Francisco', '1999-04-21', NULL, 'tambaqui123@gmail.com', '(85) 98948-2053', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 19:13:59', '2025-11-29 19:13:59', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('4', '12321321333', 'yudi', '2000-03-13', NULL, 'assa@gmail.com', '(85) 9999-9922', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GESTOR', NULL, NULL, '2025-11-29 19:13:59', '2025-11-29 19:13:59', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('5', '13232332322', 'raimundo nonato', '1997-03-13', NULL, 'raimundo@gmail.com', '(85) 9999-9233', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 19:13:59', '2025-11-29 19:13:59', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('6', '12312312300', 'cabra mac', '2001-09-10', NULL, 'cabramacho@gmail.com', '(85) 3333-3333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 19:13:59', '2025-11-29 19:13:59', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('7', '12112112112', 'vascaino', NULL, 'M', 'vascainoprofessor@gmail.com', '85985858585', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RESPONSAVEL', NULL, NULL, '2025-11-29 19:13:59', '2025-12-09 16:04:25', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('8', '33333333333', 'raparigueiro', NULL, 'M', 'raparigueiro@gmail.com', '85933445566', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-11-29 19:13:59', '2025-11-29 19:14:02', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('10', '98765432100', 'João Silva', '2010-05-15', 'M', 'joao@email.com', '(85) 99999-9999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-29 20:05:38', '2025-11-29 20:05:38', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('11', '12345678900', 'João Silva (Gestor Teste)', '1980-01-15', 'M', 'gestor.teste@sigae.com', '85999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GESTOR', NULL, NULL, '2025-11-30 23:34:57', '2025-11-30 23:34:57', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('28', '90000000001', 'Ana Silva Santos', '2017-03-15', 'F', 'ana.silva.teste@sigae.com', '85990000001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('29', '90000000002', 'Bruno Oliveira Costa', '2017-05-20', 'M', 'bruno.oliveira.teste@sigae.com', '85990000002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('30', '90000000003', 'Carla Mendes Lima', '2017-07-10', 'F', 'carla.mendes.teste@sigae.com', '85990000003', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('31', '90000000004', 'Daniel Souza Alves', '2017-09-25', 'M', 'daniel.souza.teste@sigae.com', '85990000004', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('32', '90000000005', 'Eduarda Ferreira Rocha', '2017-11-30', 'F', 'eduarda.ferreira.teste@sigae.com', '85990000005', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('33', '90000000006', 'Felipe Gomes Pereira', '2016-02-14', 'M', 'felipe.gomes.teste@sigae.com', '85990000006', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('34', '90000000007', 'Gabriela Martins Dias', '2016-04-18', 'F', 'gabriela.martins.teste@sigae.com', '85990000007', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('35', '90000000008', 'Henrique Barbosa Ramos', '2016-06-22', 'M', 'henrique.barbosa.teste@sigae.com', '85990000008', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('36', '90000000009', 'Isabela Nunes Cardoso', '2016-08-28', 'F', 'isabela.nunes.teste@sigae.com', '85990000009', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('37', '90000000010', 'João Pedro Teixeira', '2016-10-12', 'M', 'joao.pedro.teste@sigae.com', '85990000010', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('38', '90000000011', 'Larissa Araújo Freitas', '2015-01-08', 'F', 'larissa.araujo.teste@sigae.com', '85990000011', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('39', '90000000012', 'Marcos Vinicius Lopes', '2015-03-16', 'M', 'marcos.vinicius.teste@sigae.com', '85990000012', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('40', '90000000013', 'Natália Correia Monteiro', '2015-05-24', 'F', 'natalia.correia.teste@sigae.com', '85990000013', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('41', '90000000014', 'Otávio Ribeiro Campos', '2015-07-30', 'M', 'otavio.ribeiro.teste@sigae.com', '85990000014', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('42', '90000000015', 'Paula Cristina Moreira', '2015-09-05', 'F', 'paula.cristina.teste@sigae.com', '85990000015', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('43', '80000000001', 'Maria Santos (Professora Português)', '1985-05-10', 'F', 'maria.santos.teste@sigae.com', '85980000001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('44', '80000000002', 'José Carlos (Professor Matemática)', '1982-08-20', 'M', 'jose.carlos.teste@sigae.com', '85980000002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('45', '80000000003', 'Patrícia Lima (Professora História)', '1987-12-05', 'F', 'patricia.lima.teste@sigae.com', '85980000003', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('46', '88888888888', 'Maria da Silva - Administradora de Merenda', '1985-05-15', 'F', 'merenda.teste@sigae.com', '(85) 98888-8888', '(85) 98888-8888', NULL, 'Rua das Flores', '123', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'FUNCIONARIO', NULL, NULL, '2025-12-01 21:08:28', '2025-12-01 21:08:28', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('47', '77777777777', 'Ana Paula Costa - Nutricionista', '1990-03-20', 'F', 'nutricionista.teste@sigae.com', '(85) 97777-7777', '(85) 97777-7777', NULL, 'Rua das Nutrições', '456', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'NUTRICIONISTA', NULL, 'Nutricionista responsável pelo planejamento nutricional', '2025-12-08 10:12:49', '2025-12-08 10:12:49', NULL, '1');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('48', '01491156723', 'Antonio silva', '1998-06-04', 'M', 'antonio@gmail.com\r\n', '85440289222', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RESPONSAVEL', NULL, NULL, '2025-12-09 10:28:36', '2025-12-09 16:01:09', '11', '0');
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES ('50', '01491156728', 'Antonio silva', '1998-04-03', 'M', 'null', '85440289222', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RESPONSAVEL', NULL, NULL, '2025-12-09 10:45:00', '2025-12-09 10:45:00', '11', '1');
/*!40000 ALTER TABLE `pessoa` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `plano_aula`
-- ============================================================

DROP TABLE IF EXISTS `plano_aula`;

CREATE TABLE `plano_aula` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `turma_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) NOT NULL,
  `professor_id` bigint(20) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `conteudo` text DEFAULT NULL,
  `objetivos` text DEFAULT NULL,
  `metodologia` text DEFAULT NULL,
  `recursos` text DEFAULT NULL,
  `avaliacao` text DEFAULT NULL,
  `data_aula` date NOT NULL,
  `bimestre` int(11) DEFAULT NULL,
  `status` enum('RASCUNHO','APROVADO','APLICADO','CANCELADO') DEFAULT 'RASCUNHO',
  `aprovado_por` bigint(20) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_por` bigint(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `turma_id` (`turma_id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `professor_id` (`professor_id`),
  KEY `aprovado_por` (`aprovado_por`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_plano_data_aula` (`data_aula`),
  KEY `idx_plano_status` (`status`),
  KEY `idx_plano_bimestre` (`bimestre`),
  CONSTRAINT `plano_aula_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `plano_aula_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`),
  CONSTRAINT `plano_aula_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  CONSTRAINT `plano_aula_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `plano_aula_ibfk_5` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `produto`
-- ============================================================

DROP TABLE IF EXISTS `produto`;

CREATE TABLE `produto` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(100) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `unidade_medida` varchar(20) DEFAULT NULL,
  `estoque_minimo` decimal(10,0) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  `obs` varchar(255) DEFAULT NULL,
  `localizacao` varchar(255) DEFAULT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `fornecedor_id` bigint(20) DEFAULT NULL,
  `quantidade` int(10) DEFAULT NULL,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_produto_categoria` (`categoria`),
  KEY `idx_produto_ativo` (`ativo`),
  KEY `idx_produto_nome` (`nome`),
  KEY `fornecedor_id` (`fornecedor_id`),
  CONSTRAINT `produto_ibfk_1` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `produto_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1045 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `produto`
LOCK TABLES `produto` WRITE;
/*!40000 ALTER TABLE `produto` DISABLE KEYS */;
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1001', 'PROD001', 'Arroz Branco Tipo 1', 'CEREAIS', 'Tio João', 'KG', '100', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1002', 'PROD002', 'Feijão Carioca', 'CEREAIS', 'Camil', 'KG', '80', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1003', 'PROD003', 'Macarrão Espaguete', 'CEREAIS', 'Galão', 'KG', '50', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1004', 'PROD004', 'Farinha de Trigo', 'CEREAIS', 'Dona Benta', 'KG', '60', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1005', 'PROD005', 'Açúcar Cristal', 'CEREAIS', 'União', 'KG', '40', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1006', 'PROD006', 'Carne Bovina Moída', 'CARNES', 'Friboi', 'KG', '30', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1007', 'PROD007', 'Frango Inteiro', 'CARNES', 'Sadia', 'KG', '25', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1008', 'PROD008', 'Salsicha', 'CARNES', 'Perdigão', 'KG', '20', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1009', 'PROD009', 'Leite Integral', 'LATICINIOS', 'Itambé', 'L', '200', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1010', 'PROD010', 'Queijo Mussarela', 'LATICINIOS', 'Tirolez', 'KG', '15', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1011', 'PROD011', 'Manteiga', 'LATICINIOS', 'Aviação', 'KG', '10', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1012', 'PROD012', 'Batata', 'HORTIFRUTI', 'Frescor', 'KG', '50', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1013', 'PROD013', 'Cebola', 'HORTIFRUTI', 'Frescor', 'KG', '20', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1014', 'PROD014', 'Tomate', 'HORTIFRUTI', 'Frescor', 'KG', '30', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1015', 'PROD015', 'Banana', 'HORTIFRUTI', 'Frescor', 'KG', '40', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1016', 'PROD016', 'Laranja', 'HORTIFRUTI', 'Frescor', 'KG', '35', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1017', 'PROD017', 'Óleo de Soja', 'OLEOS', 'Liza', 'L', '50', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1018', 'PROD018', 'Margarina', 'OLEOS', 'Qualy', 'KG', '20', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1019', 'PROD019', 'Milho Verde em Conserva', 'ENLATADOS', 'Quero', 'UN', '100', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1020', 'PROD020', 'Ervilha em Conserva', 'ENLATADOS', 'Quero', 'UN', '80', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1021', 'PROD021', 'Suco de Laranja', 'BEBIDAS', 'Maguary', 'L', '150', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1022', 'PROD022', 'Achocolatado em Pó', 'BEBIDAS', 'Nescau', 'KG', '25', '2025-12-01 20:45:56', '2025-12-01 20:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1023', 'PROD001', 'Arroz Branco Tipo 1', 'CEREAIS', 'Tio João', 'KG', '100', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1024', 'PROD002', 'Feijão Carioca', 'CEREAIS', 'Camil', 'KG', '80', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1025', 'PROD003', 'Macarrão Espaguete', 'CEREAIS', 'Galão', 'KG', '50', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1026', 'PROD004', 'Farinha de Trigo', 'CEREAIS', 'Dona Benta', 'KG', '60', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1027', 'PROD005', 'Açúcar Cristal', 'CEREAIS', 'União', 'KG', '40', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1028', 'PROD006', 'Carne Bovina Moída', 'CARNES', 'Friboi', 'KG', '30', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1029', 'PROD007', 'Frango Inteiro', 'CARNES', 'Sadia', 'KG', '25', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1030', 'PROD008', 'Salsicha', 'CARNES', 'Perdigão', 'KG', '20', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1031', 'PROD009', 'Leite Integral', 'LATICINIOS', 'Itambé', 'L', '200', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1032', 'PROD010', 'Queijo Mussarela', 'LATICINIOS', 'Tirolez', 'KG', '15', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1033', 'PROD011', 'Manteiga', 'LATICINIOS', 'Aviação', 'KG', '10', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1034', 'PROD012', 'Batata', 'HORTIFRUTI', 'Frescor', 'KG', '50', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1035', 'PROD013', 'Cebola', 'HORTIFRUTI', 'Frescor', 'KG', '20', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1036', 'PROD014', 'Tomate', 'HORTIFRUTI', 'Frescor', 'KG', '30', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1037', 'PROD015', 'Banana', 'HORTIFRUTI', 'Frescor', 'KG', '40', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1038', 'PROD016', 'Laranja', 'HORTIFRUTI', 'Frescor', 'KG', '35', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1039', 'PROD017', 'Óleo de Soja', 'OLEOS', 'Liza', 'L', '50', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1040', 'PROD018', 'Margarina', 'OLEOS', 'Qualy', 'KG', '20', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1041', 'PROD019', 'Milho Verde em Conserva', 'ENLATADOS', 'Quero', 'UN', '100', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1042', 'PROD020', 'Ervilha em Conserva', 'ENLATADOS', 'Quero', 'UN', '80', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1043', 'PROD021', 'Suco de Laranja', 'BEBIDAS', 'Maguary', 'L', '150', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES ('1044', 'PROD022', 'Achocolatado em Pó', 'BEBIDAS', 'Nescau', 'KG', '25', '2025-12-01 21:00:11', '2025-12-01 21:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1');
/*!40000 ALTER TABLE `produto` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `professor`
-- ============================================================

DROP TABLE IF EXISTS `professor`;

CREATE TABLE `professor` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pessoa_id` bigint(20) NOT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `formacao` text DEFAULT NULL,
  `especializacao` text DEFAULT NULL,
  `registro_profissional` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `pessoa_id` (`pessoa_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_professor_ativo` (`ativo`),
  CONSTRAINT `professor_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `professor_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `professor`
LOCK TABLES `professor` WRITE;
/*!40000 ALTER TABLE `professor` DISABLE KEYS */;
INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `data_admissao`, `ativo`) VALUES ('2', '2', '7777777', 'MATEMATICA', NULL, NULL, NULL, '2025-11-29 19:14:01', '2025-12-03 15:53:14', NULL, NULL, '0');
INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `data_admissao`, `ativo`) VALUES ('3', '8', '3344567', 'HISTORIA', NULL, NULL, NULL, '2025-11-29 19:14:01', '2025-12-04 15:42:57', NULL, NULL, '0');
INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `data_admissao`, `ativo`) VALUES ('4', '43', 'PROF-000043', 'Licenciatura em Letras', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '2025-11-30', '1');
INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `data_admissao`, `ativo`) VALUES ('5', '44', 'PROF-000044', 'Licenciatura em Matemática', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '2025-11-30', '1');
INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `data_admissao`, `ativo`) VALUES ('6', '45', 'PROF-000045', 'Licenciatura em História', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL, '2025-11-30', '1');
INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `data_admissao`, `ativo`) VALUES ('7', '2', 'MAT-7582935', 'COMPUTARIA', 'COMPUTARIA', 'na', 'na', '2025-12-08 15:17:31', '2025-12-08 15:17:31', NULL, '2025-12-08', '1');
/*!40000 ALTER TABLE `professor` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `professor_lotacao`
-- ============================================================

DROP TABLE IF EXISTS `professor_lotacao`;

CREATE TABLE `professor_lotacao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `professor_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `carga_horaria` int(11) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `professor_id` (`professor_id`),
  KEY `escola_id` (`escola_id`),
  CONSTRAINT `professor_lotacao_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  CONSTRAINT `professor_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `professor_lotacao`
LOCK TABLES `professor_lotacao` WRITE;
/*!40000 ALTER TABLE `professor_lotacao` DISABLE KEYS */;
INSERT INTO `professor_lotacao` (`id`, `professor_id`, `escola_id`, `inicio`, `fim`, `carga_horaria`, `observacao`, `criado_em`) VALUES ('1', '4', '17', '2025-11-30', NULL, '20', 'Professora de Língua Portuguesa', '2025-11-30 23:40:04');
INSERT INTO `professor_lotacao` (`id`, `professor_id`, `escola_id`, `inicio`, `fim`, `carga_horaria`, `observacao`, `criado_em`) VALUES ('2', '5', '17', '2025-11-30', NULL, '20', 'Professor de Matemática', '2025-11-30 23:40:04');
INSERT INTO `professor_lotacao` (`id`, `professor_id`, `escola_id`, `inicio`, `fim`, `carga_horaria`, `observacao`, `criado_em`) VALUES ('3', '6', '17', '2025-11-30', NULL, '10', 'Professora de História', '2025-11-30 23:40:04');
/*!40000 ALTER TABLE `professor_lotacao` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `relatorio`
-- ============================================================

DROP TABLE IF EXISTS `relatorio`;

CREATE TABLE `relatorio` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tipo` enum('FINANCEIRO','PEDAGOGICO','MERENDA','FREQUENCIA','DESEMPENHO','ESTOQUE','NUTRICIONAL','OUTROS') NOT NULL,
  `subtipo` varchar(100) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fim` date DEFAULT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `turma_id` bigint(20) DEFAULT NULL,
  `parametros` text DEFAULT NULL COMMENT 'JSON com parÃ¢metros do relatÃ³rio',
  `arquivo_url` varchar(500) DEFAULT NULL,
  `status` enum('GERANDO','CONCLUIDO','ERRO','CANCELADO') DEFAULT 'GERANDO',
  `gerado_por` bigint(20) NOT NULL,
  `gerado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `concluido_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `turma_id` (`turma_id`),
  KEY `gerado_por` (`gerado_por`),
  KEY `idx_relatorio_tipo` (`tipo`),
  KEY `idx_relatorio_status` (`status`),
  KEY `idx_relatorio_periodo` (`periodo_inicio`,`periodo_fim`),
  CONSTRAINT `relatorio_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `relatorio_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`) ON DELETE SET NULL,
  CONSTRAINT `relatorio_ibfk_3` FOREIGN KEY (`gerado_por`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `role_permissao`
-- ============================================================

DROP TABLE IF EXISTS `role_permissao`;

CREATE TABLE `role_permissao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL') NOT NULL,
  `permissao` varchar(100) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissao_unique` (`role`,`permissao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- Estrutura da tabela `serie`
-- ============================================================

DROP TABLE IF EXISTS `serie`;

CREATE TABLE `serie` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `nivel_ensino` enum('EDUCACAO_INFANTIL','ENSINO_FUNDAMENTAL','ENSINO_MEDIO','EJA') DEFAULT 'ENSINO_FUNDAMENTAL',
  `ordem` int(11) DEFAULT NULL,
  `idade_minima` int(11) DEFAULT NULL,
  `idade_maxima` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_serie_nivel` (`nivel_ensino`),
  KEY `idx_serie_ativo` (`ativo`),
  CONSTRAINT `serie_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `serie`
LOCK TABLES `serie` WRITE;
/*!40000 ALTER TABLE `serie` DISABLE KEYS */;
INSERT INTO `serie` (`id`, `nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1', '1º Ano', '1ANO', 'ENSINO_FUNDAMENTAL', '1', '6', '7', 'Primeiro ano do Ensino Fundamental', '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57', NULL);
INSERT INTO `serie` (`id`, `nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('2', '2º Ano', '2ANO', 'ENSINO_FUNDAMENTAL', '2', '7', '8', 'Segundo ano do Ensino Fundamental', '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57', NULL);
INSERT INTO `serie` (`id`, `nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('3', '3º Ano', '3ANO', 'ENSINO_FUNDAMENTAL', '3', '8', '9', 'Terceiro ano do Ensino Fundamental', '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57', NULL);
/*!40000 ALTER TABLE `serie` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `substituicao_alimento`
-- ============================================================

DROP TABLE IF EXISTS `substituicao_alimento`;

CREATE TABLE `substituicao_alimento` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `nutricionista_id` bigint(20) NOT NULL,
  `produto_original_id` bigint(20) NOT NULL,
  `produto_substituto_id` bigint(20) NOT NULL,
  `motivo` enum('SAZONALIDADE','DISPONIBILIDADE','NECESSIDADE_ESPECIAL','VALOR_NUTRICIONAL','OUTRO') DEFAULT 'OUTRO',
  `proporcao` decimal(5,2) DEFAULT 1.00 COMMENT 'Proporção de substituição (ex: 1.5 = 1.5kg do substituto para 1kg do original)',
  `equivalencia_nutricional` text DEFAULT NULL COMMENT 'Descrição da equivalência nutricional',
  `observacoes` text DEFAULT NULL,
  `aprovado` tinyint(1) DEFAULT 0 COMMENT 'Se foi aprovado para uso geral',
  `aprovado_por` bigint(20) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `nutricionista_id` (`nutricionista_id`),
  KEY `produto_original_id` (`produto_original_id`),
  KEY `produto_substituto_id` (`produto_substituto_id`),
  KEY `aprovado_por` (`aprovado_por`),
  KEY `idx_substituicao_motivo` (`motivo`),
  KEY `idx_substituicao_aprovado` (`aprovado`),
  KEY `idx_substituicao_ativo` (`ativo`),
  CONSTRAINT `substituicao_alimento_ibfk_1` FOREIGN KEY (`nutricionista_id`) REFERENCES `nutricionista` (`id`),
  CONSTRAINT `substituicao_alimento_ibfk_2` FOREIGN KEY (`produto_original_id`) REFERENCES `produto` (`id`),
  CONSTRAINT `substituicao_alimento_ibfk_3` FOREIGN KEY (`produto_substituto_id`) REFERENCES `produto` (`id`),
  CONSTRAINT `substituicao_alimento_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sugestões de substituição de alimentos';


-- ============================================================
-- Estrutura da tabela `turma`
-- ============================================================

DROP TABLE IF EXISTS `turma`;

CREATE TABLE `turma` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `escola_id` bigint(20) NOT NULL,
  `serie_id` bigint(20) DEFAULT NULL,
  `ano_letivo` int(11) DEFAULT NULL,
  `serie` varchar(20) DEFAULT NULL,
  `letra` varchar(3) DEFAULT NULL,
  `turno` enum('MANHA','TARDE','NOITE') DEFAULT NULL,
  `capacidade` int(11) DEFAULT NULL,
  `sala` varchar(50) DEFAULT NULL,
  `coordenador_id` bigint(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `coordenador_id` (`coordenador_id`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_turma_ano_letivo` (`ano_letivo`),
  KEY `idx_turma_turno` (`turno`),
  KEY `idx_turma_ativo` (`ativo`),
  KEY `idx_turma_escola_ano` (`escola_id`,`ano_letivo`),
  KEY `serie_id` (`serie_id`),
  CONSTRAINT `turma_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `turma_ibfk_2` FOREIGN KEY (`coordenador_id`) REFERENCES `professor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `turma_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `turma_ibfk_4` FOREIGN KEY (`serie_id`) REFERENCES `serie` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `turma`
LOCK TABLES `turma` WRITE;
/*!40000 ALTER TABLE `turma` DISABLE KEYS */;
INSERT INTO `turma` (`id`, `escola_id`, `serie_id`, `ano_letivo`, `serie`, `letra`, `turno`, `capacidade`, `sala`, `coordenador_id`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('1', '17', '1', '2025', '1º Ano', 'A', 'MANHA', '30', NULL, NULL, NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57', NULL);
INSERT INTO `turma` (`id`, `escola_id`, `serie_id`, `ano_letivo`, `serie`, `letra`, `turno`, `capacidade`, `sala`, `coordenador_id`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('2', '17', '2', '2025', '2º Ano', 'A', 'MANHA', '30', NULL, NULL, NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57', NULL);
INSERT INTO `turma` (`id`, `escola_id`, `serie_id`, `ano_letivo`, `serie`, `letra`, `turno`, `capacidade`, `sala`, `coordenador_id`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES ('3', '17', '3', '2025', '3º Ano', 'A', 'MANHA', '30', NULL, NULL, NULL, '1', '2025-11-30 23:34:57', '2025-11-30 23:34:57', NULL);
/*!40000 ALTER TABLE `turma` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `turma_professor`
-- ============================================================

DROP TABLE IF EXISTS `turma_professor`;

CREATE TABLE `turma_professor` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `turma_id` bigint(20) NOT NULL,
  `professor_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `regime` enum('REGULAR','SUBSTITUTO') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_turma_professor_disciplina` (`turma_id`,`professor_id`,`disciplina_id`,`inicio`),
  KEY `turma_id` (`turma_id`),
  KEY `professor_id` (`professor_id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `turma_professor_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `turma_professor_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  CONSTRAINT `turma_professor_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`),
  CONSTRAINT `turma_professor_ibfk_4` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `turma_professor`
LOCK TABLES `turma_professor` WRITE;
/*!40000 ALTER TABLE `turma_professor` DISABLE KEYS */;
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('1', '1', '4', '1', '2025-11-30', NULL, 'REGULAR', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('2', '2', '4', '1', '2025-11-30', NULL, 'REGULAR', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('3', '3', '4', '1', '2025-11-30', NULL, 'REGULAR', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('4', '1', '5', '2', '2025-11-30', NULL, 'REGULAR', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('5', '2', '5', '2', '2025-11-30', NULL, 'REGULAR', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('6', '3', '5', '2', '2025-11-30', NULL, 'REGULAR', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('7', '1', '6', '3', '2025-11-30', NULL, 'REGULAR', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('8', '2', '6', '3', '2025-11-30', NULL, 'REGULAR', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('9', '3', '6', '3', '2025-11-30', NULL, 'REGULAR', NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES ('10', '1', '7', '2', NULL, NULL, NULL, NULL, '2025-12-08 15:23:12', '2025-12-08 15:23:12', NULL);
/*!40000 ALTER TABLE `turma_professor` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `usuario`
-- ============================================================

DROP TABLE IF EXISTS `usuario`;

CREATE TABLE `usuario` (
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
  KEY `idx_usuario_email_verificado` (`email_verificado`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados da tabela `usuario`
LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('1', '1', 'Roger', '1', 'ADM', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-09-19 13:35:40', '2025-11-29 19:14:02', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('2', '2', 'lavosier', '$2y$10$cJ4zJP1As7NtakAsDLmRfu2X.2z53ZEDo1SRT1131di5djhclf6Zi', 'PROFESSOR', '1', '0', NULL, NULL, '0', NULL, '2025-12-09 15:40:45', NULL, '2025-09-22 16:17:23', '2025-12-09 15:40:45', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('3', '3', 'francisco', '$2y$10$RqVIvLDU2B3aMH8D5DCUeubFZ0dVMgvfNgzbhCqWr6REia5O/69gy', 'ADM', '1', '0', NULL, NULL, '0', NULL, '2025-12-10 08:10:23', NULL, '2025-09-22 16:42:40', '2025-12-10 08:10:23', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('4', '4', 'yudi', '$2y$10$3WUQGohoZf8tiE0UvSC43uxF4kQCrjERBG8NmfyMQZ8FgMHN0vKnS', 'GESTAO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-09-23 14:56:04', '2025-11-29 19:14:02', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('5', '5', 'raimundo', '$2y$10$yAoiZi1i3HOosehIwKCg5OMua7tXjlpVIlm5SJAuIIfZ/tcoNbup.', 'GESTAO', '1', '0', NULL, NULL, '0', NULL, '2025-12-08 10:04:56', NULL, '2025-09-23 14:58:50', '2025-12-08 10:04:56', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('6', '6', 'cabra', '$2y$10$KjDXdWEqd.98YRW6bHErve.JEjPU6hx0Nb1QjJd4DvjcSRZJMlyoG', 'PROFESSOR', '1', '0', NULL, NULL, '0', NULL, '2025-09-29 14:00:01', NULL, '2025-09-29 13:56:48', '2025-11-29 19:14:02', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('7', '10', 'joao.silva', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-11-29 20:05:38', '2025-11-29 20:05:38', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('8', '11', 'gestor.teste', '$2y$10$97/wPF7UQfMIuhy17lkgpOzvzcOLawjW.wB6Y8ctM2JnxYt5NIAGm', 'GESTAO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-11-30 23:34:57', '2025-12-09 09:55:35', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('10', '43', 'maria', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-11-30 23:40:04', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('11', '44', 'josé', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', '1', '0', NULL, NULL, '0', NULL, '2025-12-09 13:22:51', NULL, '2025-11-30 23:40:04', '2025-12-09 13:22:51', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('12', '45', 'patrícia', '1', 'PROFESSOR', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-11-30 23:40:04', '2025-12-08 15:12:21', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('13', '28', 'ana.silva.santos', '$2y$10$5Tcc269FHgJLZYq4PeqUZe.QC9L0xuQS5FZ2d/.Ph6WGi3.8zKtu6', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:14:38', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('14', '29', 'bruno.oliveira.costa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('15', '30', 'carla.mendes.lima', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('16', '31', 'daniel.souza.alves', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('17', '32', 'eduarda.ferreira.rocha', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('18', '33', 'felipe.gomes.pereira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('19', '34', 'gabriela.martins.dias', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('20', '35', 'henrique.barbosa.ramos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('21', '36', 'isabela.nunes.cardoso', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('22', '37', 'joão.pedro.teixeira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('23', '38', 'larissa.araújo.freitas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('24', '39', 'marcos.vinicius.lopes', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('25', '40', 'natália.correia.monteiro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('26', '41', 'otávio.ribeiro.campos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('27', '42', 'paula.cristina.moreira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-01 01:11:43', '2025-12-01 01:11:43', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('28', '46', 'merenda.teste', '$2y$10$t/nUJBD4VyWkx6YK5fcRAu3kaOFsMX0nZ9zWBdHvlqZQstiVAZCji', 'ADM_MERENDA', '1', '1', NULL, NULL, '0', NULL, '2025-12-09 11:45:04', NULL, '2025-12-01 21:08:28', '2025-12-09 11:45:04', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('29', '47', 'nutricionista.teste', '$2y$10$oXrBGX7cOyVVVNono/n8beQF0SY4IOeXDqxOTP52FbVk5lL06rG4K', 'NUTRICIONISTA', '1', '1', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-08 10:12:49', '2025-12-09 15:49:29', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('30', '48', 'antoniosilva6723', '$2y$10$cL858W9Dyst4Jua46DJxLOxjqFB03IzY3eDZPTYInM21eAPsR6Kpy', 'RESPONSAVEL', '0', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-09 10:28:36', '2025-12-09 10:38:19', NULL);
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES ('31', '50', 'antoniosilva6728', '$2y$10$PJzQfziAZ/MCsRIcNA.89OM38ASGVBx8kD6IrfosCYqHd5vFlPXfm', 'RESPONSAVEL', '1', '0', NULL, NULL, '0', NULL, NULL, NULL, '2025-12-09 10:45:00', '2025-12-09 10:45:00', NULL);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;


-- ============================================================
-- Estrutura da tabela `validacao`
-- ============================================================

DROP TABLE IF EXISTS `validacao`;

CREATE TABLE `validacao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tipo_registro` enum('NOTA','FREQUENCIA','PLANO_AULA','OBSERVACAO','COMUNICADO','CARDAPIO','PEDIDO','OUTROS') NOT NULL,
  `registro_id` bigint(20) NOT NULL,
  `status` enum('PENDENTE','APROVADO','REJEITADO') DEFAULT 'PENDENTE',
  `observacoes` text DEFAULT NULL,
  `validado_por` bigint(20) DEFAULT NULL,
  `data_validacao` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `validado_por` (`validado_por`),
  KEY `idx_validacao_tipo` (`tipo_registro`),
  KEY `idx_validacao_status` (`status`),
  KEY `idx_validacao_registro` (`tipo_registro`,`registro_id`),
  CONSTRAINT `validacao_ibfk_1` FOREIGN KEY (`validado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
