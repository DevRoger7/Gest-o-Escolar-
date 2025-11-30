-- ============================================================
-- SQL para Criar Usuário Aluno (com verificação de duplicatas)
-- SIGAE - Sistema de Gestão e Alimentação Escolar
-- ============================================================

-- OPÇÃO 1: Verificar CPFs existentes antes de criar
-- Execute este SELECT para ver quais CPFs já existem:
SELECT cpf, nome, tipo FROM pessoa WHERE tipo = 'ALUNO' ORDER BY cpf;

-- ============================================================
-- OPÇÃO 2: Criar aluno com CPF diferente (use um CPF único)
-- ============================================================

-- 1. Criar Pessoa (USE UM CPF DIFERENTE!)
INSERT INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
VALUES ('98765432100', 'João Silva', '2010-05-15', 'M', 'joao@email.com', '(85) 99999-9999', 'ALUNO');

-- 2. Criar Aluno
INSERT INTO `aluno` (`pessoa_id`, `matricula`, `data_matricula`, `situacao`, `ativo`) 
VALUES (LAST_INSERT_ID(), '2024001', CURDATE(), 'MATRICULADO', 1);

-- 3. Criar Usuário para Login
INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
VALUES (
    (SELECT id FROM pessoa WHERE cpf = '98765432100'),
    'joao.silva',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Senha: "password"
    'ALUNO',
    1
);

-- ============================================================
-- OPÇÃO 3: Criar aluno apenas se o CPF não existir
-- ============================================================

-- Verificar e criar apenas se não existir
INSERT INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
SELECT 
    '11122233344' as cpf,
    'Maria Oliveira' as nome,
    '2011-08-20' as data_nascimento,
    'F' as sexo,
    'maria@email.com' as email,
    '(85) 98888-8888' as telefone,
    'ALUNO' as tipo
WHERE NOT EXISTS (
    SELECT 1 FROM pessoa WHERE cpf = '11122233344'
);

-- Se a pessoa foi criada, criar aluno e usuário
SET @pessoa_id = (SELECT id FROM pessoa WHERE cpf = '11122233344');

-- Criar aluno apenas se a pessoa foi criada e não existe aluno
INSERT INTO `aluno` (`pessoa_id`, `matricula`, `data_matricula`, `situacao`, `ativo`) 
SELECT 
    @pessoa_id,
    '2024002',
    CURDATE(),
    'MATRICULADO',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM aluno WHERE pessoa_id = @pessoa_id
);

-- Criar usuário apenas se a pessoa foi criada e não existe usuário
INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
SELECT 
    @pessoa_id,
    'maria.oliveira',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'ALUNO',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM usuario WHERE pessoa_id = @pessoa_id
);

-- ============================================================
-- OPÇÃO 4: Gerar CPF aleatório único (útil para testes)
-- ============================================================

-- Esta função gera um CPF aleatório e verifica se já existe
-- Use valores aleatórios entre 10000000000 e 99999999999

SET @novo_cpf = LPAD(FLOOR(RAND() * 99999999999), 11, '0');

-- Verificar se já existe, se sim, gerar outro
WHILE EXISTS (SELECT 1 FROM pessoa WHERE cpf = @novo_cpf) DO
    SET @novo_cpf = LPAD(FLOOR(RAND() * 99999999999), 11, '0');
END WHILE;

-- Agora usar @novo_cpf para criar o aluno
INSERT INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
VALUES (@novo_cpf, 'Aluno Teste', '2010-01-01', 'M', 'teste@email.com', '(85) 99999-9999', 'ALUNO');

SET @pessoa_id = LAST_INSERT_ID();

INSERT INTO `aluno` (`pessoa_id`, `matricula`, `data_matricula`, `situacao`, `ativo`) 
VALUES (@pessoa_id, CONCAT('2024', LPAD(FLOOR(RAND() * 9999), 3, '0')), CURDATE(), 'MATRICULADO', 1);

INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
VALUES (@pessoa_id, CONCAT('aluno', @pessoa_id), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1);

-- ============================================================
-- RECOMENDAÇÃO: Use a OPÇÃO 2 com um CPF único
-- ============================================================

