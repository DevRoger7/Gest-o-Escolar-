-- Script para adicionar campos do TrendEducar à tabela escola existente
-- Baseado na estrutura atual do banco escola_merenda

USE escola_merenda;

-- Adicionar campos do TrendEducar à tabela escola existente
ALTER TABLE `escola` 
ADD COLUMN IF NOT EXISTS `inep` VARCHAR(20) DEFAULT NULL AFTER `codigo`,
ADD COLUMN IF NOT EXISTS `nome_curto` VARCHAR(100) DEFAULT NULL AFTER `inep`,
ADD COLUMN IF NOT EXISTS `tipo_escola` ENUM('NORMAL','ESPECIAL','INDIGENA','QUILOMBOLA') DEFAULT 'NORMAL' AFTER `nome_curto`,
ADD COLUMN IF NOT EXISTS `escola_administradora` VARCHAR(150) DEFAULT NULL AFTER `tipo_escola`,
ADD COLUMN IF NOT EXISTS `logradouro` VARCHAR(200) DEFAULT NULL AFTER `endereco`,
ADD COLUMN IF NOT EXISTS `numero` VARCHAR(10) DEFAULT NULL AFTER `logradouro`,
ADD COLUMN IF NOT EXISTS `complemento` VARCHAR(100) DEFAULT NULL AFTER `numero`,
ADD COLUMN IF NOT EXISTS `bairro` VARCHAR(100) DEFAULT NULL AFTER `complemento`,
ADD COLUMN IF NOT EXISTS `uf` VARCHAR(2) DEFAULT 'CE' AFTER `municipio`,
ADD COLUMN IF NOT EXISTS `zona` ENUM('URBANA','RURAL') DEFAULT 'URBANA' AFTER `uf`,
ADD COLUMN IF NOT EXISTS `distrito` VARCHAR(100) DEFAULT NULL AFTER `zona`,
ADD COLUMN IF NOT EXISTS `orgao_ensino` VARCHAR(100) DEFAULT NULL AFTER `distrito`,
ADD COLUMN IF NOT EXISTS `telefone_fixo` VARCHAR(20) DEFAULT NULL AFTER `telefone`,
ADD COLUMN IF NOT EXISTS `telefone_movel` VARCHAR(20) DEFAULT NULL AFTER `telefone_fixo`,
ADD COLUMN IF NOT EXISTS `site` VARCHAR(200) DEFAULT NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `gestor_cpf` VARCHAR(14) DEFAULT NULL AFTER `site`,
ADD COLUMN IF NOT EXISTS `gestor_nome` VARCHAR(100) DEFAULT NULL AFTER `gestor_cpf`,
ADD COLUMN IF NOT EXISTS `gestor_email` VARCHAR(100) DEFAULT NULL AFTER `gestor_nome`,
ADD COLUMN IF NOT EXISTS `gestor_inep` VARCHAR(20) DEFAULT NULL AFTER `gestor_email`,
ADD COLUMN IF NOT EXISTS `gestor_cargo` ENUM('DIRETOR','VICE_DIRETOR','COORDENADOR','OUTRO_CARGO') DEFAULT 'OUTRO_CARGO' AFTER `gestor_inep`,
ADD COLUMN IF NOT EXISTS `gestor_tipo_acesso` ENUM('CONCURSO','PROVIMENTO','NOMEACAO','OUTROS') DEFAULT 'OUTROS' AFTER `gestor_cargo`,
ADD COLUMN IF NOT EXISTS `gestor_criterio_acesso` VARCHAR(100) DEFAULT NULL AFTER `gestor_tipo_acesso`;

-- Adicionar índices para melhor performance
CREATE INDEX IF NOT EXISTS `idx_escola_inep` ON `escola`(`inep`);
CREATE INDEX IF NOT EXISTS `idx_escola_tipo` ON `escola`(`tipo_escola`);
CREATE INDEX IF NOT EXISTS `idx_escola_gestor_cpf` ON `escola`(`gestor_cpf`);

-- Adicionar comentários para documentar os novos campos
ALTER TABLE `escola` 
MODIFY COLUMN `inep` VARCHAR(20) DEFAULT NULL COMMENT 'Código INEP da escola',
MODIFY COLUMN `nome_curto` VARCHAR(100) DEFAULT NULL COMMENT 'Nome abreviado da escola',
MODIFY COLUMN `tipo_escola` ENUM('NORMAL','ESPECIAL','INDIGENA','QUILOMBOLA') DEFAULT 'NORMAL' COMMENT 'Tipo de escola conforme INEP',
MODIFY COLUMN `escola_administradora` VARCHAR(150) DEFAULT NULL COMMENT 'Escola que administra esta unidade',
MODIFY COLUMN `logradouro` VARCHAR(200) DEFAULT NULL COMMENT 'Logradouro do endereço da escola',
MODIFY COLUMN `numero` VARCHAR(10) DEFAULT NULL COMMENT 'Número do endereço da escola',
MODIFY COLUMN `complemento` VARCHAR(100) DEFAULT NULL COMMENT 'Complemento do endereço',
MODIFY COLUMN `bairro` VARCHAR(100) DEFAULT NULL COMMENT 'Bairro da escola',
MODIFY COLUMN `uf` VARCHAR(2) DEFAULT 'CE' COMMENT 'Unidade Federativa (padrão CE)',
MODIFY COLUMN `zona` ENUM('URBANA','RURAL') DEFAULT 'URBANA' COMMENT 'Zona urbana ou rural',
MODIFY COLUMN `distrito` VARCHAR(100) DEFAULT NULL COMMENT 'Distrito da escola',
MODIFY COLUMN `orgao_ensino` VARCHAR(100) DEFAULT NULL COMMENT 'Órgão responsável pelo ensino',
MODIFY COLUMN `telefone_fixo` VARCHAR(20) DEFAULT NULL COMMENT 'Telefone fixo da escola',
MODIFY COLUMN `telefone_movel` VARCHAR(20) DEFAULT NULL COMMENT 'Telefone móvel da escola',
MODIFY COLUMN `site` VARCHAR(200) DEFAULT NULL COMMENT 'Site da escola',
MODIFY COLUMN `gestor_cpf` VARCHAR(14) DEFAULT NULL COMMENT 'CPF do gestor responsável',
MODIFY COLUMN `gestor_nome` VARCHAR(100) DEFAULT NULL COMMENT 'Nome do gestor responsável',
MODIFY COLUMN `gestor_email` VARCHAR(100) DEFAULT NULL COMMENT 'E-mail do gestor',
MODIFY COLUMN `gestor_inep` VARCHAR(20) DEFAULT NULL COMMENT 'Código INEP do gestor',
MODIFY COLUMN `gestor_cargo` ENUM('DIRETOR','VICE_DIRETOR','COORDENADOR','OUTRO_CARGO') DEFAULT 'OUTRO_CARGO' COMMENT 'Cargo do gestor',
MODIFY COLUMN `gestor_tipo_acesso` ENUM('CONCURSO','PROVIMENTO','NOMEACAO','OUTROS') DEFAULT 'OUTROS' COMMENT 'Tipo de acesso ao cargo',
MODIFY COLUMN `gestor_criterio_acesso` VARCHAR(100) DEFAULT NULL COMMENT 'Critério de acesso ao cargo';
