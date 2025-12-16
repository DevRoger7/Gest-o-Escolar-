-- =====================================================
-- SQL SIMPLES: Adicionar colunas de transporte na tabela aluno
-- Data: 2025-12-16
-- 
-- IMPORTANTE: Execute este SQL apenas se as colunas ainda não existirem!
-- Caso contrário, use o arquivo add_transporte_aluno.sql que verifica automaticamente
-- 
-- Se alguma coluna já existir, você receberá um erro. Nesse caso, comente
-- a linha que está dando erro e execute novamente.
-- =====================================================

-- Adicionar coluna precisa_transporte
ALTER TABLE `aluno` 
ADD COLUMN `precisa_transporte` tinyint(1) DEFAULT 0 
COMMENT '1 = aluno precisa de transporte escolar, 0 = não precisa' 
AFTER `ativo`;

-- Adicionar coluna distrito_transporte
ALTER TABLE `aluno` 
ADD COLUMN `distrito_transporte` varchar(100) DEFAULT NULL 
COMMENT 'Distrito de Maranguape onde o aluno precisa de transporte (ex: Amanari, Itapebussu, Lagoa)' 
AFTER `precisa_transporte`;

-- Adicionar coluna localidade_transporte
ALTER TABLE `aluno` 
ADD COLUMN `localidade_transporte` varchar(255) DEFAULT NULL 
COMMENT 'Localidade específica dentro do distrito onde o aluno precisa de transporte (ex: Massape, Alto das Vassouras, Centro). Deve corresponder a uma localidade cadastrada na tabela distrito_localidade.' 
AFTER `distrito_transporte`;

-- Adicionar índice para melhorar performance
-- (Se o índice já existir, comente esta linha)
ALTER TABLE `aluno` 
ADD INDEX `idx_aluno_transporte` (`precisa_transporte`, `distrito_transporte`, `localidade_transporte`, `ativo`);

-- =====================================================
-- NOTA: 
-- - precisa_transporte: 1 = precisa, 0 = não precisa
-- - distrito_transporte: Nome do distrito (ex: Amanari, Itapebussu)
-- - localidade_transporte: Nome da localidade dentro do distrito (ex: Massape)
-- 
-- A localidade_transporte deve corresponder a uma entrada na tabela
-- distrito_localidade onde distrito = distrito_transporte
-- =====================================================

