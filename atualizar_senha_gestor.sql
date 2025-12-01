-- ============================================================
-- Script para atualizar a senha do gestor
-- ============================================================
-- 
-- Este script atualiza a senha do gestor com CPF 12345678900
-- Nova senha: 123456
-- 
-- Execute este script se a senha não estiver funcionando
-- ============================================================

-- Atualizar senha do gestor
UPDATE `usuario` 
SET `senha_hash` = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy'  -- Hash da senha "123456"
WHERE `pessoa_id` = (
    SELECT id FROM pessoa WHERE cpf = '12345678900' LIMIT 1
);

-- Verificar se foi atualizado
SELECT 
    u.id,
    p.nome,
    p.cpf,
    u.username,
    u.role,
    CASE 
        WHEN u.senha_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy' 
        THEN 'Senha atualizada com sucesso!' 
        ELSE 'Senha NÃO foi atualizada' 
    END as status
FROM usuario u
INNER JOIN pessoa p ON u.pessoa_id = p.id
WHERE p.cpf = '12345678900';

-- ============================================================
-- DADOS DE LOGIN:
-- CPF: 12345678900
-- Senha: 123456
-- ============================================================

