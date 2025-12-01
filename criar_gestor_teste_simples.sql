-- ============================================================
-- SQL SIMPLIFICADO - Criar Gestor de Teste
-- Execute este script no seu banco de dados
-- ============================================================

-- 1. Criar pessoa
INSERT INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
VALUES ('12345678900', 'João Silva (Gestor Teste)', '1980-01-15', 'M', 'gestor.teste@sigae.com', '85999999999', 'GESTOR');

-- 2. Criar gestor (usa o ID da pessoa criada acima)
INSERT INTO `gestor` (`pessoa_id`, `cargo`, `ativo`) 
SELECT id, 'Diretor', 1 FROM pessoa WHERE cpf = '12345678900' LIMIT 1;

-- 3. Criar usuário para login
-- Username: gestor.teste
-- Senha: 123456
INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
SELECT id, 'gestor.teste', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GESTAO', 1 
FROM pessoa WHERE cpf = '12345678900' LIMIT 1;

-- 4. (OPCIONAL) Lotar gestor em uma escola
-- Descomente e ajuste o ID da escola (substitua 1 pelo ID da escola desejada)
/*
INSERT INTO `gestor_lotacao` (`gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`) 
SELECT g.id, 1, CURDATE(), NULL, 1, 'Diretor'
FROM gestor g
INNER JOIN pessoa p ON g.pessoa_id = p.id
WHERE p.cpf = '12345678900' LIMIT 1;
*/

-- ============================================================
-- DADOS DE LOGIN:
-- Username: gestor.teste
-- Senha: 123456
-- ============================================================

