-- ============================================================
-- SQL para verificar se o usuário ADM_MERENDA foi criado
-- ============================================================

-- Verificar se a pessoa existe
SELECT 
    p.id as pessoa_id,
    p.cpf,
    p.nome,
    p.email,
    p.tipo,
    p.ativo
FROM pessoa p
WHERE p.cpf = '88888888888' OR p.email = 'merenda.teste@sigae.com';

-- Verificar se o usuário existe
SELECT 
    u.id as usuario_id,
    u.username,
    u.role,
    u.ativo,
    p.cpf,
    p.nome,
    p.email
FROM usuario u
INNER JOIN pessoa p ON u.pessoa_id = p.id
WHERE u.username = 'merenda.teste' 
   OR p.cpf = '88888888888' 
   OR p.email = 'merenda.teste@sigae.com'
   OR u.role = 'ADM_MERENDA';

-- ============================================================
-- Se não encontrar, execute o criar_usuario_merenda_teste.sql
-- ============================================================

