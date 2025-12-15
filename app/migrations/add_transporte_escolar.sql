-- =====================================================
-- Migração: Sistema de Transporte Escolar
-- Data: 2025-12-15
-- Descrição: Adiciona novos tipos de usuário e tabelas
--            para o sistema de transporte escolar
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. Adicionar novos roles na tabela usuario
-- --------------------------------------------------------

ALTER TABLE `usuario` 
MODIFY COLUMN `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL','ADM_TRANSPORTE','TRANSPORTE_ALUNO') DEFAULT NULL;

-- Adicionar também na tabela role_permissao
ALTER TABLE `role_permissao` 
MODIFY COLUMN `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL','ADM_TRANSPORTE','TRANSPORTE_ALUNO') NOT NULL;

-- --------------------------------------------------------
-- 2. Tabela: veiculo (Veículos do transporte escolar)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `veiculo` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `placa` varchar(10) NOT NULL,
  `renavam` varchar(20) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `ano` int(11) DEFAULT NULL,
  `cor` varchar(50) DEFAULT NULL,
  `capacidade_maxima` int(11) NOT NULL COMMENT 'Lotação máxima do veículo',
  `capacidade_minima` int(11) DEFAULT NULL COMMENT 'Lotação mínima recomendada para viabilidade (ex: van pequena = 8, ônibus = 30)',
  `tipo` enum('ONIBUS','VAN','MICROONIBUS','OUTRO') DEFAULT 'ONIBUS',
  `numero_frota` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_veiculo_ativo` (`ativo`),
  KEY `idx_veiculo_capacidade` (`capacidade_maxima`,`capacidade_minima`),
  CONSTRAINT `veiculo_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 3. Tabela: motorista (Motoristas do transporte)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `motorista` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pessoa_id` bigint(20) NOT NULL,
  `cnh` varchar(20) NOT NULL,
  `categoria_cnh` varchar(5) DEFAULT NULL COMMENT 'B, C, D, E',
  `validade_cnh` date DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `data_demissao` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnh` (`cnh`),
  KEY `pessoa_id` (`pessoa_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_motorista_ativo` (`ativo`),
  CONSTRAINT `motorista_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `motorista_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 4. Tabela: rota (Rotas de transporte escolar)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `rota` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `veiculo_id` bigint(20) DEFAULT NULL,
  `motorista_id` bigint(20) DEFAULT NULL,
  `turno` enum('MANHA','TARDE','NOITE','INTEGRAL') DEFAULT NULL,
  `localidades` text DEFAULT NULL COMMENT 'JSON array com as localidades que a rota atende (ex: ["Lagoa","Itapebussu","Amanari"])',
  `total_alunos` int(11) DEFAULT 0 COMMENT 'Total de alunos na rota',
  `distancia_km` decimal(10,2) DEFAULT NULL,
  `tempo_estimado_minutos` int(11) DEFAULT NULL,
  `horario_saida` time DEFAULT NULL,
  `horario_chegada` time DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `escola_id` (`escola_id`),
  KEY `veiculo_id` (`veiculo_id`),
  KEY `motorista_id` (`motorista_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_rota_ativo` (`ativo`),
  CONSTRAINT `rota_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rota_ibfk_2` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rota_ibfk_3` FOREIGN KEY (`motorista_id`) REFERENCES `motorista` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rota_ibfk_4` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 5. Tabela: ponto_rota (Pontos de parada das rotas com geolocalização)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ponto_rota` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `rota_id` bigint(20) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `localidade` varchar(100) DEFAULT NULL COMMENT 'Localidade/região do ponto (ex: Lagoa, Itapebussu, Amanari)',
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `ordem` int(11) DEFAULT NULL COMMENT 'Ordem do ponto na rota',
  `tipo` enum('ORIGEM','PARADA','DESTINO') DEFAULT 'PARADA',
  `horario_previsto` time DEFAULT NULL,
  `total_alunos_embarque` int(11) DEFAULT 0 COMMENT 'Quantidade de alunos que embarcam neste ponto',
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rota_id` (`rota_id`),
  KEY `idx_ponto_rota_ordem` (`rota_id`,`ordem`),
  KEY `idx_ponto_rota_ativo` (`ativo`),
  KEY `idx_ponto_rota_localidade` (`localidade`),
  CONSTRAINT `ponto_rota_ibfk_1` FOREIGN KEY (`rota_id`) REFERENCES `rota` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 6. Tabela: geolocalizacao_aluno (Geolocalização dos alunos)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `geolocalizacao_aluno` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` bigint(20) NOT NULL,
  `tipo` enum('RESIDENCIA','PONTO_REFERENCIA','OUTRO') DEFAULT 'RESIDENCIA',
  `nome` varchar(255) DEFAULT NULL COMMENT 'Nome do local (ex: Casa, Ponto de referência)',
  `localidade` varchar(100) DEFAULT NULL COMMENT 'Nome da localidade/região (ex: Lagoa, Itapebussu, Amanari)',
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 0 COMMENT '1 = localização principal do aluno',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_geoloc_aluno_principal` (`aluno_id`,`principal`),
  KEY `idx_geoloc_localidade` (`localidade`),
  CONSTRAINT `geolocalizacao_aluno_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  CONSTRAINT `geolocalizacao_aluno_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 7. Tabela: aluno_rota (Relacionamento aluno com rota)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `aluno_rota` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aluno_id` bigint(20) NOT NULL,
  `rota_id` bigint(20) NOT NULL,
  `ponto_embarque_id` bigint(20) DEFAULT NULL COMMENT 'ID do ponto_rota onde o aluno embarca',
  `ponto_desembarque_id` bigint(20) DEFAULT NULL COMMENT 'ID do ponto_rota onde o aluno desembarca',
  `geolocalizacao_id` bigint(20) DEFAULT NULL COMMENT 'ID da geolocalização do aluno usada para criar/atribuir a rota',
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `status` enum('ATIVO','INATIVO','SUSPENSO') DEFAULT 'ATIVO',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `rota_id` (`rota_id`),
  KEY `ponto_embarque_id` (`ponto_embarque_id`),
  KEY `ponto_desembarque_id` (`ponto_desembarque_id`),
  KEY `geolocalizacao_id` (`geolocalizacao_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_aluno_rota_status` (`status`),
  KEY `idx_aluno_rota_ativo` (`aluno_id`,`status`),
  CONSTRAINT `aluno_rota_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aluno_rota_ibfk_2` FOREIGN KEY (`rota_id`) REFERENCES `rota` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aluno_rota_ibfk_3` FOREIGN KEY (`ponto_embarque_id`) REFERENCES `ponto_rota` (`id`) ON DELETE SET NULL,
  CONSTRAINT `aluno_rota_ibfk_4` FOREIGN KEY (`ponto_desembarque_id`) REFERENCES `ponto_rota` (`id`) ON DELETE SET NULL,
  CONSTRAINT `aluno_rota_ibfk_5` FOREIGN KEY (`geolocalizacao_id`) REFERENCES `geolocalizacao_aluno` (`id`) ON DELETE SET NULL,
  CONSTRAINT `aluno_rota_ibfk_6` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 8. Tabela: viagem (Registro de viagens realizadas)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `viagem` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `rota_id` bigint(20) NOT NULL,
  `veiculo_id` bigint(20) DEFAULT NULL,
  `motorista_id` bigint(20) DEFAULT NULL,
  `data` date NOT NULL,
  `tipo` enum('IDA','VOLTA','IDA_VOLTA') DEFAULT 'IDA',
  `horario_saida_previsto` time DEFAULT NULL,
  `horario_saida_real` time DEFAULT NULL,
  `horario_chegada_previsto` time DEFAULT NULL,
  `horario_chegada_real` time DEFAULT NULL,
  `total_alunos` int(11) DEFAULT 0,
  `total_alunos_embarcados` int(11) DEFAULT 0,
  `status` enum('AGENDADA','EM_ANDAMENTO','CONCLUIDA','CANCELADA','ATRASADA') DEFAULT 'AGENDADA',
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rota_id` (`rota_id`),
  KEY `veiculo_id` (`veiculo_id`),
  KEY `motorista_id` (`motorista_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_viagem_data` (`data`),
  KEY `idx_viagem_status` (`status`),
  KEY `idx_viagem_rota_data` (`rota_id`,`data`),
  CONSTRAINT `viagem_ibfk_1` FOREIGN KEY (`rota_id`) REFERENCES `rota` (`id`),
  CONSTRAINT `viagem_ibfk_2` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `viagem_ibfk_3` FOREIGN KEY (`motorista_id`) REFERENCES `motorista` (`id`) ON DELETE SET NULL,
  CONSTRAINT `viagem_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 9. Tabela: viagem_aluno (Alunos que participaram de uma viagem)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `viagem_aluno` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `viagem_id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `ponto_embarque_id` bigint(20) DEFAULT NULL,
  `ponto_desembarque_id` bigint(20) DEFAULT NULL,
  `horario_embarque` time DEFAULT NULL,
  `horario_desembarque` time DEFAULT NULL,
  `presente` tinyint(1) DEFAULT 1,
  `observacoes` text DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_viagem_aluno` (`viagem_id`,`aluno_id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `ponto_embarque_id` (`ponto_embarque_id`),
  KEY `ponto_desembarque_id` (`ponto_desembarque_id`),
  KEY `idx_viagem_aluno_presente` (`presente`),
  CONSTRAINT `viagem_aluno_ibfk_1` FOREIGN KEY (`viagem_id`) REFERENCES `viagem` (`id`) ON DELETE CASCADE,
  CONSTRAINT `viagem_aluno_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  CONSTRAINT `viagem_aluno_ibfk_3` FOREIGN KEY (`ponto_embarque_id`) REFERENCES `ponto_rota` (`id`) ON DELETE SET NULL,
  CONSTRAINT `viagem_aluno_ibfk_4` FOREIGN KEY (`ponto_desembarque_id`) REFERENCES `ponto_rota` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 10. Tabela: motorista_veiculo (Relacionamento motorista com veículo)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `motorista_veiculo` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `motorista_id` bigint(20) NOT NULL,
  `veiculo_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 0 COMMENT '1 = veículo principal do motorista',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `motorista_id` (`motorista_id`),
  KEY `veiculo_id` (`veiculo_id`),
  KEY `idx_motorista_veiculo_principal` (`motorista_id`,`principal`),
  CONSTRAINT `motorista_veiculo_ibfk_1` FOREIGN KEY (`motorista_id`) REFERENCES `motorista` (`id`) ON DELETE CASCADE,
  CONSTRAINT `motorista_veiculo_ibfk_2` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 11. Inserir permissões básicas para os novos roles
-- --------------------------------------------------------

-- Inserir permissões apenas se não existirem
INSERT INTO `role_permissao` (`role`, `permissao`, `ativo`) 
SELECT 'ADM_TRANSPORTE', 'gerenciar_transporte', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissao` 
    WHERE `role` = 'ADM_TRANSPORTE' AND `permissao` = 'gerenciar_transporte'
);

INSERT INTO `role_permissao` (`role`, `permissao`, `ativo`) 
SELECT 'ADM_TRANSPORTE', 'gerenciar_veiculos', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissao` 
    WHERE `role` = 'ADM_TRANSPORTE' AND `permissao` = 'gerenciar_veiculos'
);

INSERT INTO `role_permissao` (`role`, `permissao`, `ativo`) 
SELECT 'ADM_TRANSPORTE', 'gerenciar_motoristas', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissao` 
    WHERE `role` = 'ADM_TRANSPORTE' AND `permissao` = 'gerenciar_motoristas'
);

INSERT INTO `role_permissao` (`role`, `permissao`, `ativo`) 
SELECT 'ADM_TRANSPORTE', 'gerenciar_rotas', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissao` 
    WHERE `role` = 'ADM_TRANSPORTE' AND `permissao` = 'gerenciar_rotas'
);

INSERT INTO `role_permissao` (`role`, `permissao`, `ativo`) 
SELECT 'ADM_TRANSPORTE', 'visualizar_relatorios_transporte', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissao` 
    WHERE `role` = 'ADM_TRANSPORTE' AND `permissao` = 'visualizar_relatorios_transporte'
);

INSERT INTO `role_permissao` (`role`, `permissao`, `ativo`) 
SELECT 'TRANSPORTE_ALUNO', 'criar_rotas', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissao` 
    WHERE `role` = 'TRANSPORTE_ALUNO' AND `permissao` = 'criar_rotas'
);

INSERT INTO `role_permissao` (`role`, `permissao`, `ativo`) 
SELECT 'TRANSPORTE_ALUNO', 'visualizar_rotas', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissao` 
    WHERE `role` = 'TRANSPORTE_ALUNO' AND `permissao` = 'visualizar_rotas'
);

INSERT INTO `role_permissao` (`role`, `permissao`, `ativo`) 
SELECT 'TRANSPORTE_ALUNO', 'gerenciar_geolocalizacao', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissao` 
    WHERE `role` = 'TRANSPORTE_ALUNO' AND `permissao` = 'gerenciar_geolocalizacao'
);

INSERT INTO `role_permissao` (`role`, `permissao`, `ativo`) 
SELECT 'TRANSPORTE_ALUNO', 'atribuir_alunos_rotas', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissao` 
    WHERE `role` = 'TRANSPORTE_ALUNO' AND `permissao` = 'atribuir_alunos_rotas'
);

-- =====================================================
-- DOCUMENTAÇÃO: Lógica de Criação de Rotas por Localidade
-- =====================================================
-- 
-- COMO O SISTEMA MONTA AS ROTAS:
-- 
-- 1. AGRUPAMENTO POR LOCALIDADE:
--    - Alunos são agrupados pela localidade (ex: Lagoa, Itapebussu, Amanari)
--    - Cada aluno tem sua geolocalização com campo 'localidade' preenchido
-- 
-- 2. ANÁLISE DE QUANTIDADE DE ALUNOS POR LOCALIDADE:
--    - Sistema conta quantos alunos tem em cada localidade
--    - Compara com a capacidade dos veículos disponíveis
-- 
-- 3. DECISÃO: ROTA ÚNICA OU MÚLTIPLAS ROTAS:
--    
--    CASO A: Poucos alunos (cabe em 1 veículo)
--    - Se total de alunos de TODAS as localidades <= capacidade_maxima de um veículo
--    - Cria UMA rota que passa por todas as localidades em sequência
--    - Exemplo: Rota "Interior" passa por Lagoa → Itapebussu → Amanari → Escola
--    - Usa veículo adequado (se 15 alunos, usa van de 20 lugares, não ônibus de 50)
-- 
--    CASO B: Muitos alunos (precisa de múltiplos veículos)
--    - Se total de alunos > capacidade_maxima de um veículo
--    - Cria UMA rota SEPARADA para cada localidade
--    - Exemplo: 
--      * Rota "Lagoa" - 1 ônibus só para Lagoa
--      * Rota "Itapebussu" - 1 ônibus só para Itapebussu  
--      * Rota "Amanari" - 1 ônibus só para Amanari
-- 
-- 4. SELEÇÃO DE VEÍCULO ADEQUADO:
--    - Sistema verifica capacidade_minima e capacidade_maxima
--    - Escolhe o menor veículo que atenda a necessidade
--    - Exemplo: Se localidade tem 8 alunos, usa VAN (min:8, max:20)
--              Não usa ÔNIBUS (min:30, max:50) - não é viável
-- 
-- 5. ORDEM DOS PONTOS NA ROTA:
--    - Se rota única com múltiplas localidades:
--      * Ordena localidades pela proximidade geográfica
--      * Cria pontos de parada em cada localidade
--      * Último ponto sempre é a escola (DESTINO)
-- 
-- 6. EXEMPLO PRÁTICO:
--    Escola: E.E.M. Exemplo
--    Localidades: Lagoa (12 alunos), Itapebussu (18 alunos), Amanari (15 alunos)
--    Total: 45 alunos
--    
--    Veículos disponíveis:
--    - VAN: capacidade_minima=8, capacidade_maxima=20
--    - ÔNIBUS: capacidade_minima=30, capacidade_maxima=50
--    
--    Resultado:
--    - Como 45 > 50 (capacidade máxima do ônibus), NÃO pode ser 1 rota única
--    - Cria 3 rotas separadas:
--      * Rota "Lagoa" - VAN (12 alunos, cabe em van de 20)
--      * Rota "Itapebussu" - VAN (18 alunos, cabe em van de 20)
--      * Rota "Amanari" - VAN (15 alunos, cabe em van de 20)
--    
--    Se tivesse apenas 30 alunos no total:
--    - Cria 1 rota única "Interior" - ÔNIBUS (30 alunos, usa ônibus de 50)
--    - Passa por: Lagoa → Itapebussu → Amanari → Escola
-- 
-- =====================================================

COMMIT;

