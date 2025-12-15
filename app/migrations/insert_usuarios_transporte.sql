-- =====================================================
-- Inserir Usuários do Sistema de Transporte Escolar
-- Data: 2025-12-15
-- Descrição: Cria usuários ADM_TRANSPORTE e TRANSPORTE_ALUNO
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. Inserir Pessoa: Administrador de Transporte
-- --------------------------------------------------------

INSERT INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`, `ativo`) 
VALUES 
('11111111112', 'Administrador de Transporte', '1985-01-15', 'M', 'adm.transporte@sigae.com', '(85) 99999-9999', 'FUNCIONARIO', 1);

SET @pessoa_adm_transporte_id = LAST_INSERT_ID();

-- --------------------------------------------------------
-- 2. Inserir Usuário: ADM_TRANSPORTE
-- Senha padrão: 123456
-- Hash bcrypt: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- --------------------------------------------------------

INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`) 
VALUES 
(@pessoa_adm_transporte_id, 'adm.transporte', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADM_TRANSPORTE', 1, 1);

-- --------------------------------------------------------
-- 3. Inserir Pessoa: Transporte Escolar (Aluno)
-- --------------------------------------------------------

INSERT INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`, `ativo`) 
VALUES 
('11111111113', 'Operador de Rotas Escolares', '1990-05-20', 'M', 'transporte.aluno@sigae.com', '(85) 98888-8888', 'FUNCIONARIO', 1);

SET @pessoa_transporte_aluno_id = LAST_INSERT_ID();

-- --------------------------------------------------------
-- 4. Inserir Usuário: TRANSPORTE_ALUNO
-- Senha padrão: 123456
-- Hash bcrypt: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- --------------------------------------------------------

INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`) 
VALUES 
(@pessoa_transporte_aluno_id, 'transporte.aluno', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'TRANSPORTE_ALUNO', 1, 1);

COMMIT;

-- =====================================================
-- CREDENCIAIS DE ACESSO:
-- =====================================================
-- 
-- ADM_TRANSPORTE:
--   Username: adm.transporte
--   Senha: 123456
--   Email: adm.transporte@sigae.com
-- 
-- TRANSPORTE_ALUNO:
--   Username: transporte.aluno
--   Senha: 123456
--   Email: transporte.aluno@sigae.com
-- 
-- =====================================================
-- NOTA: Altere as senhas após o primeiro login!
-- =====================================================

