-- ============================================================
-- SQL para criar usuário de teste do Administrador de Merenda
-- ============================================================
-- 
-- Credenciais de acesso:
-- CPF: 88888888888 (use este CPF para fazer login)
-- Senha: 123456
-- Role: ADM_MERENDA
--
-- IMPORTANTE: O sistema usa CPF para login, não username!
-- No campo de login, digite: 88888888888 (ou 888.888.888-88)
--
-- NOTA: O hash usado é para a senha "123456"
-- Para gerar um novo hash, use: password_hash('sua_senha', PASSWORD_DEFAULT)
-- ============================================================

-- Verificar se já existe pessoa com este CPF
SET @pessoa_existe = (SELECT COUNT(*) FROM pessoa WHERE cpf = '88888888888');

-- Se não existir, criar pessoa
INSERT INTO `pessoa` (
    `cpf`, 
    `nome`, 
    `data_nascimento`, 
    `sexo`, 
    `email`, 
    `telefone`, 
    `whatsapp`, 
    `endereco`, 
    `numero`, 
    `bairro`, 
    `cidade`, 
    `estado`, 
    `cep`, 
    `tipo`, 
    `ativo`
) 
SELECT 
    '88888888888',  -- CPF de teste
    'Maria da Silva - Administradora de Merenda',  -- Nome completo
    '1985-05-15',  -- Data de nascimento
    'F',  -- Sexo: Feminino
    'merenda.teste@sigae.com',  -- Email
    '(85) 98888-8888',  -- Telefone
    '(85) 98888-8888',  -- WhatsApp
    'Rua das Flores',  -- Endereço
    '123',  -- Número
    'Centro',  -- Bairro
    'Maranguape',  -- Cidade
    'CE',  -- Estado
    '61940-000',  -- CEP
    'FUNCIONARIO',  -- Tipo
    1  -- Ativo
WHERE NOT EXISTS (
    SELECT 1 FROM pessoa WHERE cpf = '88888888888'
);

-- Obter o ID da pessoa (criada agora ou já existente)
SET @pessoa_id = (SELECT id FROM pessoa WHERE cpf = '88888888888' LIMIT 1);

-- Verificar se já existe usuário para esta pessoa
SET @usuario_existe = (SELECT COUNT(*) FROM usuario WHERE pessoa_id = @pessoa_id);

-- Se não existir, criar usuário
INSERT INTO `usuario` (
    `pessoa_id`, 
    `username`, 
    `senha_hash`, 
    `role`, 
    `ativo`, 
    `email_verificado`
) 
SELECT 
    @pessoa_id,  -- ID da pessoa
    'merenda.teste',  -- Username
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',  -- Hash da senha "123456"
    'ADM_MERENDA',  -- Role: Administrador de Merenda
    1,  -- Ativo
    1  -- Email verificado
WHERE NOT EXISTS (
    SELECT 1 FROM usuario WHERE pessoa_id = @pessoa_id
);

-- ============================================================
-- ALTERNATIVA: Se você quiser usar IDs fixos
-- ============================================================
-- 
-- Descomente e ajuste os IDs abaixo se preferir usar IDs fixos:
--
-- INSERT INTO `pessoa` (
--     `id`,
--     `cpf`, 
--     `nome`, 
--     `data_nascimento`, 
--     `sexo`, 
--     `email`, 
--     `telefone`, 
--     `whatsapp`, 
--     `endereco`, 
--     `numero`, 
--     `bairro`, 
--     `cidade`, 
--     `estado`, 
--     `cep`, 
--     `tipo`, 
--     `ativo`
-- ) VALUES (
--     100,  -- ID fixo (ajuste conforme necessário)
--     '88888888888',
--     'Maria da Silva - Administradora de Merenda',
--     '1985-05-15',
--     'F',
--     'merenda.teste@sigae.com',
--     '(85) 98888-8888',
--     '(85) 98888-8888',
--     'Rua das Flores',
--     '123',
--     'Centro',
--     'Maranguape',
--     'CE',
--     '61940-000',
--     'FUNCIONARIO',
--     1
-- );
--
-- INSERT INTO `usuario` (
--     `id`,
--     `pessoa_id`, 
--     `username`, 
--     `senha_hash`, 
--     `role`, 
--     `ativo`, 
--     `email_verificado`
-- ) VALUES (
--     100,  -- ID fixo (ajuste conforme necessário)
--     100,  -- ID da pessoa criada acima
--     'merenda.teste',
--     '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',  -- Hash da senha "123456"
--     'ADM_MERENDA',
--     1,
--     1
-- );

-- ============================================================
-- Verificar se o usuário foi criado corretamente
-- ============================================================
-- 
-- Execute estas queries para verificar:
--
-- SELECT u.id, u.username, u.role, p.nome, p.email 
-- FROM usuario u 
-- INNER JOIN pessoa p ON u.pessoa_id = p.id 
-- WHERE u.username = 'merenda.teste';
--
-- ============================================================

