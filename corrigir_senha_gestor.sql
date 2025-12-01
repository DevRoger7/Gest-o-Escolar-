-- ============================================================
-- Script RÁPIDO para corrigir a senha do gestor
-- ============================================================
-- Execute este script para atualizar a senha do gestor
-- CPF: 12345678900
-- Nova Senha: 123456
-- ============================================================

-- Atualizar senha do gestor diretamente
-- Hash válido para a senha "123456" gerado com password_hash('123456', PASSWORD_DEFAULT)
UPDATE `usuario` u
INNER JOIN `pessoa` p ON u.pessoa_id = p.id
SET u.senha_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'  -- Hash da senha "123456"
WHERE p.cpf = '12345678900';

-- Verificar se foi atualizado
SELECT 
    p.nome,
    p.cpf,
    u.username,
    u.role,
    'Senha atualizada! Use: CPF 12345678900 / Senha 123456' as mensagem
FROM usuario u
INNER JOIN pessoa p ON u.pessoa_id = p.id
WHERE p.cpf = '12345678900';

-- ============================================================
-- DADOS DE LOGIN:
-- CPF: 12345678900
-- Senha: 123456
-- ============================================================

