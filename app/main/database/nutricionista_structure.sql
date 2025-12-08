-- =====================================================
-- ESTRUTURA DO MÓDULO NUTRICIONISTA
-- Sistema de Gestão e Alimentação Escolar (SIGEA)
-- =====================================================

-- 1. Adicionar 'NUTRICIONISTA' ao enum tipo da tabela pessoa
ALTER TABLE `pessoa` 
MODIFY COLUMN `tipo` enum('ALUNO','PROFESSOR','GESTOR','FUNCIONARIO','RESPONSAVEL','NUTRICIONISTA','OUTRO') DEFAULT NULL;

-- 2. Criar tabela nutricionista
CREATE TABLE IF NOT EXISTS `nutricionista` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Criar tabela nutricionista_lotacao (para lotação em escolas)
CREATE TABLE IF NOT EXISTS `nutricionista_lotacao` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Criar tabela substituicao_alimento (para sugestões de substituição)
CREATE TABLE IF NOT EXISTS `substituicao_alimento` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Criar tabela indicador_nutricional (para acompanhar indicadores)
CREATE TABLE IF NOT EXISTS `indicador_nutricional` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Criar tabela parecer_tecnico (para pareceres técnicos)
CREATE TABLE IF NOT EXISTS `parecer_tecnico` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Adicionar tipo NUTRICIONAL ao enum tipo da tabela relatorio
ALTER TABLE `relatorio` 
MODIFY COLUMN `tipo` enum('FINANCEIRO','PEDAGOGICO','MERENDA','FREQUENCIA','DESEMPENHO','ESTOQUE','NUTRICIONAL','OUTROS') NOT NULL;

-- 8. Adicionar subtipos nutricionais à tabela relatorio (via campo subtipo que já existe)
-- Os subtipos podem ser: 'CARDAPIOS', 'CONSUMO', 'DESPERDICIO', 'VARIEDADE', 'SAZONALIDADE', 'PARECER_TECNICO'

-- =====================================================
-- ÍNDICES ADICIONAIS PARA OTIMIZAÇÃO
-- =====================================================

-- Índices para busca por nutricionista em pedidos
CREATE INDEX IF NOT EXISTS `idx_pedido_nutricionista_status` ON `pedido_cesta` (`nutricionista_id`, `status`);

-- Índices para busca de cardápios por nutricionista
CREATE INDEX IF NOT EXISTS `idx_cardapio_criado_por` ON `cardapio` (`criado_por`);

-- =====================================================
-- COMENTÁRIOS DAS TABELAS
-- =====================================================

ALTER TABLE `nutricionista` COMMENT = 'Tabela de nutricionistas do sistema';
ALTER TABLE `nutricionista_lotacao` COMMENT = 'Lotação de nutricionistas em escolas';
ALTER TABLE `substituicao_alimento` COMMENT = 'Sugestões de substituição de alimentos';
ALTER TABLE `indicador_nutricional` COMMENT = 'Indicadores nutricionais acompanhados pelo nutricionista';
ALTER TABLE `parecer_tecnico` COMMENT = 'Pareceres técnicos emitidos pelos nutricionistas';

-- =====================================================
-- DADOS DE TESTE - USUÁRIO NUTRICIONISTA
-- =====================================================

-- Inserir pessoa nutricionista
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(47, '77777777777', 'Ana Paula Costa - Nutricionista', '1990-03-20', 'F', 'nutricionista.teste@sigae.com', '(85) 97777-7777', '(85) 97777-7777', NULL, 'Rua das Nutrições', '456', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'NUTRICIONISTA', NULL, 'Nutricionista responsável pelo planejamento nutricional', NOW(), NOW(), NULL, 1);

-- Inserir nutricionista
INSERT INTO `nutricionista` (`id`, `pessoa_id`, `crn`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 47, 'CRN-12345', 'Bacharelado em Nutrição - UFC', 'Especialização em Nutrição Escolar', '12345', 'Nutricionista com experiência em alimentação escolar e PNAE', 1, NOW(), NOW(), NULL);

-- Inserir usuário nutricionista
-- Senha: 1
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES
(29, 47, 'nutricionista.teste', '1', 'NUTRICIONISTA', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL);

-- Inserir lotação do nutricionista na escola de teste (ID 17)
INSERT INTO `nutricionista_lotacao` (`id`, `nutricionista_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `carga_horaria`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 1, 17, CURDATE(), NULL, 1, 40, 'Nutricionista responsável pela escola', NOW(), NOW(), NULL);

-- =====================================================
-- CREDENCIAIS DE TESTE
-- =====================================================
-- Username: nutricionista.teste
-- Senha: 1
-- Email: nutricionista.teste@sigae.com
-- Role: NUTRICIONISTA
-- CRN: CRN-12345

