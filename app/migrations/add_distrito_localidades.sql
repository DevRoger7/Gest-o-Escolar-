-- =====================================================
-- Migração: Sistema de Localidades por Distrito
-- Data: 2025-12-15
-- Descrição: Adiciona tabelas para gerenciar localidades
--            por distrito e pontos centrais
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. Tabela: distrito_localidade (Localidades de cada distrito)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `distrito_localidade` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `distrito` varchar(100) NOT NULL COMMENT 'Nome do distrito (ex: Amanari, Sede, Itapebussu)',
  `localidade` varchar(255) NOT NULL COMMENT 'Nome da localidade dentro do distrito (ex: Alto das Vassouras, Centro)',
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Latitude da localidade',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'Longitude da localidade',
  `endereco` text DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT 'Maranguape',
  `estado` char(2) DEFAULT 'CE',
  `cep` varchar(10) DEFAULT NULL,
  `descricao` text DEFAULT NULL COMMENT 'Descrição da localidade',
  `distancia_centro_km` decimal(10,2) DEFAULT NULL COMMENT 'Distância em km do ponto central do distrito',
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_distrito` (`distrito`),
  KEY `idx_localidade` (`localidade`),
  KEY `idx_distrito_localidade` (`distrito`, `localidade`),
  KEY `idx_ativo` (`ativo`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `distrito_localidade_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 2. Tabela: distrito_ponto_central (Ponto central de cada distrito)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `distrito_ponto_central` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `distrito` varchar(100) NOT NULL COMMENT 'Nome do distrito',
  `nome` varchar(255) DEFAULT NULL COMMENT 'Nome do ponto central (ex: Sede Distrital, Escola de Referência)',
  `latitude` decimal(10,8) NOT NULL COMMENT 'Latitude do ponto central',
  `longitude` decimal(11,8) NOT NULL COMMENT 'Longitude do ponto central',
  `endereco` text DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT 'Maranguape',
  `estado` char(2) DEFAULT 'CE',
  `cep` varchar(10) DEFAULT NULL,
  `tipo` enum('SEDE_DISTRITAL','ESCOLA_REFERENCIA','OUTRO') DEFAULT 'SEDE_DISTRITAL',
  `escola_id` bigint(20) DEFAULT NULL COMMENT 'ID da escola se for escola de referência',
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `distrito` (`distrito`),
  KEY `escola_id` (`escola_id`),
  KEY `idx_ativo` (`ativo`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `distrito_ponto_central_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `distrito_ponto_central_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 3. Atualizar tabela rota para incluir distrito
-- --------------------------------------------------------
-- Nota: A tabela rota já existe no banco e possui o campo 'distancia_km'
-- Vamos apenas adicionar o campo 'distrito' que é necessário para a nova lógica

-- Verificar e adicionar coluna distrito se não existir
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'rota' 
                   AND COLUMN_NAME = 'distrito');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `rota` ADD COLUMN `distrito` varchar(100) DEFAULT NULL COMMENT \'Distrito principal da rota\' AFTER `localidades`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice se não existir
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'rota' 
                   AND INDEX_NAME = 'idx_rota_distrito');

SET @sql2 = IF(@idx_exists = 0, 
    'ALTER TABLE `rota` ADD INDEX `idx_rota_distrito` (`distrito`)',
    'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

COMMIT;

