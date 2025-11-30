-- ============================================================
-- SQL para Criar Usuário Aluno Completo
-- SIGAE - Sistema de Gestão e Alimentação Escolar
-- ============================================================

-- Exemplo de uso: Substitua os valores entre < > pelos dados reais

-- 1. INSERIR PESSOA
INSERT INTO `pessoa` (
    `cpf`, 
    `nome`, 
    `data_nascimento`, 
    `sexo`, 
    `email`, 
    `telefone`, 
    `tipo`
) VALUES (
    '12345678901',                    -- CPF (11 dígitos, sem pontos ou traços)
    'João Silva Santos',              -- Nome completo
    '2010-05-15',                     -- Data de nascimento (YYYY-MM-DD)
    'M',                              -- Sexo: 'M' ou 'F'
    'joao.silva@email.com',           -- Email
    '(85) 99999-9999',                -- Telefone
    'ALUNO'                           -- Tipo: 'ALUNO'
);

-- Obter o ID da pessoa criada (use o último ID inserido)
SET @pessoa_id = LAST_INSERT_ID();

-- 2. INSERIR ALUNO
INSERT INTO `aluno` (
    `pessoa_id`,
    `matricula`,
    `nis`,
    `responsavel_id`,
    `escola_id`,
    `data_matricula`,
    `situacao`,
    `ativo`
) VALUES (
    @pessoa_id,                       -- ID da pessoa criada acima
    '2024001',                        -- Matrícula (única)
    '12345678901',                    -- NIS (opcional)
    NULL,                             -- ID do responsável (opcional, pode ser NULL)
    NULL,                             -- ID da escola (opcional, pode ser NULL)
    CURDATE(),                        -- Data de matrícula (hoje)
    'MATRICULADO',                    -- Situação: 'MATRICULADO', 'TRANSFERIDO', 'EVADIDO', 'CONCLUIDO', 'CANCELADO'
    1                                 -- Ativo: 1 = sim, 0 = não
);

-- Obter o ID do aluno criado
SET @aluno_id = LAST_INSERT_ID();

-- 3. INSERIR USUÁRIO (para login no sistema)
INSERT INTO `usuario` (
    `pessoa_id`,
    `username`,
    `senha_hash`,
    `role`,
    `ativo`,
    `created_at`
) VALUES (
    @pessoa_id,                       -- ID da pessoa criada
    'joao.silva',                     -- Username para login (único)
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Senha: "password" (use password_hash() no PHP)
    'ALUNO',                          -- Role: 'ALUNO'
    1,                                -- Ativo: 1 = sim, 0 = não
    NOW()                             -- Data de criação
);

-- ============================================================
-- OBSERVAÇÕES:
-- ============================================================
-- 1. CPF deve ser único na tabela pessoa
-- 2. Matrícula deve ser única na tabela aluno
-- 3. Username deve ser único na tabela usuario
-- 4. Para gerar hash de senha no PHP, use:
--    password_hash('senha123', PASSWORD_DEFAULT)
-- 5. Para verificar senha no PHP, use:
--    password_verify('senha123', $hash)
-- ============================================================

-- ============================================================
-- EXEMPLO COMPLETO COM VALORES REAIS:
-- ============================================================

/*
-- Exemplo 1: Aluno completo
INSERT INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
VALUES ('11122233344', 'Maria Oliveira', '2011-08-20', 'F', 'maria.oliveira@email.com', '(85) 98888-8888', 'ALUNO');

SET @pessoa_id = LAST_INSERT_ID();

INSERT INTO `aluno` (`pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `ativo`) 
VALUES (@pessoa_id, '2024002', '98765432100', NULL, NULL, CURDATE(), 'MATRICULADO', 1);

INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `created_at`) 
VALUES (@pessoa_id, 'maria.oliveira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, NOW());
*/

-- ============================================================
-- VERIFICAR SE FOI CRIADO CORRETAMENTE:
-- ============================================================

/*
SELECT 
    p.id as pessoa_id,
    p.nome,
    p.cpf,
    p.email,
    a.id as aluno_id,
    a.matricula,
    a.situacao,
    u.id as usuario_id,
    u.username,
    u.role
FROM pessoa p
INNER JOIN aluno a ON p.id = a.pessoa_id
INNER JOIN usuario u ON p.id = u.pessoa_id
WHERE p.cpf = '12345678901';
*/

