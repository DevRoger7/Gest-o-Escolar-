-- ============================================================
-- Verificar Senha do Usuário com CPF 98765432100
-- ============================================================

-- Ver dados do usuário
SELECT 
    p.cpf,
    p.nome,
    p.email,
    u.username,
    u.senha_hash,
    u.role,
    u.ativo
FROM pessoa p
INNER JOIN usuario u ON p.id = u.pessoa_id
WHERE p.cpf = '98765432100';

-- ============================================================
-- Se você criou usando o SQL que forneci, a senha é: password
-- ============================================================

-- Hash usado no exemplo: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- Senha correspondente: password

-- ============================================================
-- Para alterar a senha deste usuário:
-- ============================================================

-- 1. Gerar novo hash (use gerar_hash_senha.php ou bcrypt-generator.com)
-- 2. Atualizar no banco:

/*
UPDATE usuario 
SET senha_hash = '$2y$10$NOVO_HASH_AQUI' 
WHERE pessoa_id = (
    SELECT id FROM pessoa WHERE cpf = '98765432100'
);
*/

-- ============================================================
-- Exemplo: Alterar senha para "123456"
-- ============================================================

-- Primeiro gere o hash de "123456" usando:
-- php gerar_hash_senha.php
-- ou https://bcrypt-generator.com/

-- Depois execute:
/*
UPDATE usuario 
SET senha_hash = '$2y$10$HASH_GERADO_AQUI' 
WHERE pessoa_id = (
    SELECT id FROM pessoa WHERE cpf = '98765432100'
);
*/

