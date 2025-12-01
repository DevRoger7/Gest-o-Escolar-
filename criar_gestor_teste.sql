-- ============================================================
-- SQL para criar um Gestor de Teste completo
-- SIGAE - Sistema de Gestão e Alimentação Escolar
-- ============================================================
-- 
-- Este script cria:
-- 1. Uma pessoa do tipo GESTOR
-- 2. Um registro na tabela gestor
-- 3. Um usuário com role GESTAO para fazer login
-- 4. Opcionalmente, uma lotação em uma escola
--
-- Dados do Gestor de Teste:
-- Nome: João Silva (Gestor Teste)
-- CPF: 12345678900
-- Email: gestor.teste@sigae.com
-- Telefone: (85) 99999-9999
-- Username: gestor.teste
-- Senha: 123456 (hash será gerado)
-- Cargo: Diretor
-- ============================================================

-- Passo 1: Criar a pessoa
INSERT INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
VALUES (
    '12345678900',                           -- CPF (sem pontos e traço)
    'João Silva (Gestor Teste)',            -- Nome
    '1980-01-15',                            -- Data de nascimento
    'M',                                     -- Sexo (M/F)
    'gestor.teste@sigae.com',               -- Email
    '85999999999',                          -- Telefone (sem formatação)
    'GESTOR'                                -- Tipo
);

-- Passo 2: Obter o ID da pessoa criada (ajuste o ID se necessário)
-- Se você já sabe o próximo ID, pode usar diretamente
-- Caso contrário, execute: SELECT LAST_INSERT_ID() as pessoa_id;

-- Passo 3: Criar o registro na tabela gestor
-- Substitua @pessoa_id pelo ID retornado no passo anterior ou use o próximo ID disponível
INSERT INTO `gestor` (`pessoa_id`, `cargo`, `ativo`) 
VALUES (
    (SELECT id FROM pessoa WHERE cpf = '12345678900' LIMIT 1),  -- pessoa_id (busca automática)
    'Diretor',                                                 -- Cargo
    1                                                          -- Ativo (1 = sim, 0 = não)
);

-- Passo 4: Criar o usuário para login
-- Senha padrão: 123456
-- Hash gerado com password_hash('123456', PASSWORD_DEFAULT)
INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
VALUES (
    (SELECT id FROM pessoa WHERE cpf = '12345678900' LIMIT 1),  -- pessoa_id
    'gestor.teste',                                              -- username
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Hash da senha "123456"
    'GESTAO',                                                    -- Role (tipo de usuário)
    1                                                            -- Ativo
);

-- Passo 5 (OPCIONAL): Criar lotação do gestor em uma escola
-- Descomente e ajuste o escola_id conforme necessário
/*
INSERT INTO `gestor_lotacao` (`gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`) 
VALUES (
    (SELECT id FROM gestor WHERE pessoa_id = (SELECT id FROM pessoa WHERE cpf = '12345678900' LIMIT 1) LIMIT 1),  -- gestor_id
    1,                    -- escola_id (ajuste conforme sua necessidade)
    CURDATE(),            -- Data de início (hoje)
    NULL,                 -- Data de fim (NULL = lotação ativa)
    1,                    -- Responsável (1 = sim, 0 = não)
    'Diretor'             -- Tipo de cargo na escola
);
*/

-- ============================================================
-- VERIFICAÇÃO
-- ============================================================
-- Execute estas queries para verificar se tudo foi criado corretamente:

-- Verificar pessoa criada
-- SELECT * FROM pessoa WHERE cpf = '12345678900';

-- Verificar gestor criado
-- SELECT g.*, p.nome, p.email 
-- FROM gestor g 
-- INNER JOIN pessoa p ON g.pessoa_id = p.id 
-- WHERE p.cpf = '12345678900';

-- Verificar usuário criado
-- SELECT u.*, p.nome, p.email 
-- FROM usuario u 
-- INNER JOIN pessoa p ON u.pessoa_id = p.id 
-- WHERE p.cpf = '12345678900';

-- Verificar lotação (se criada)
-- SELECT gl.*, e.nome as escola_nome, p.nome as gestor_nome
-- FROM gestor_lotacao gl
-- INNER JOIN gestor g ON gl.gestor_id = g.id
-- INNER JOIN pessoa p ON g.pessoa_id = p.id
-- INNER JOIN escola e ON gl.escola_id = e.id
-- WHERE p.cpf = '12345678900' AND gl.fim IS NULL;

-- ============================================================
-- DADOS DE LOGIN
-- ============================================================
-- Username: gestor.teste
-- Senha: 123456
-- Tipo: GESTAO
-- ============================================================

