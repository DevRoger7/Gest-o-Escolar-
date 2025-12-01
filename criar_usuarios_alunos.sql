-- ============================================================
-- Script para criar usuários para alunos de teste
-- ============================================================
-- Execute este script para criar usuários para os alunos
-- que foram criados no ambiente de teste
-- ============================================================

-- Criar usuários para alunos que não têm
INSERT IGNORE INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
SELECT 
    p.id,
    LOWER(REPLACE(REPLACE(REPLACE(REPLACE(p.nome, ' ', '.'), '(', ''), ')', ''), ' ', '')),
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Senha: 123456
    'ALUNO',
    1
FROM pessoa p
INNER JOIN aluno a ON a.pessoa_id = p.id
LEFT JOIN usuario u ON u.pessoa_id = p.id
WHERE p.cpf IN (
    '90000000001', '90000000002', '90000000003', '90000000004', '90000000005',
    '90000000006', '90000000007', '90000000008', '90000000009', '90000000010',
    '90000000011', '90000000012', '90000000013', '90000000014', '90000000015'
)
AND u.id IS NULL;

-- Verificar se foi criado
SELECT 
    p.nome,
    p.cpf,
    p.email,
    u.username,
    u.role,
    'Usuário criado! Senha: 123456' as mensagem
FROM pessoa p
INNER JOIN usuario u ON u.pessoa_id = p.id
WHERE p.cpf IN (
    '90000000001', '90000000002', '90000000003', '90000000004', '90000000005',
    '90000000006', '90000000007', '90000000008', '90000000009', '90000000010',
    '90000000011', '90000000012', '90000000013', '90000000014', '90000000015'
)
ORDER BY p.nome;

-- ============================================================
-- DADOS DE TESTE:
-- CPF: 90000000002
-- Email: bruno.oliveira.teste@sigae.com
-- Senha: 123456
-- ============================================================

