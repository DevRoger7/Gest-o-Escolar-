-- ============================================================
-- Script para verificar e criar usuários para alunos
-- ============================================================
-- Este script verifica se os alunos têm usuários e cria se necessário
-- ============================================================

-- Verificar alunos sem usuário
SELECT 
    a.id as aluno_id,
    p.id as pessoa_id,
    p.nome,
    p.cpf,
    p.email,
    CASE 
        WHEN u.id IS NULL THEN 'SEM USUÁRIO' 
        ELSE 'TEM USUÁRIO' 
    END as status_usuario
FROM aluno a
INNER JOIN pessoa p ON a.pessoa_id = p.id
LEFT JOIN usuario u ON u.pessoa_id = p.id
WHERE p.cpf IN ('90000000001', '90000000002', '90000000003', '90000000004', '90000000005',
                 '90000000006', '90000000007', '90000000008', '90000000009', '90000000010',
                 '90000000011', '90000000012', '90000000013', '90000000014', '90000000015')
ORDER BY p.nome;

-- Criar usuários para alunos que não têm
INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
SELECT 
    p.id,
    LOWER(REPLACE(REPLACE(REPLACE(p.nome, ' ', '.'), '(', ''), ')', '')) as username,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Senha: 123456
    'ALUNO',
    1
FROM pessoa p
INNER JOIN aluno a ON a.pessoa_id = p.id
LEFT JOIN usuario u ON u.pessoa_id = p.id
WHERE p.cpf IN ('90000000001', '90000000002', '90000000003', '90000000004', '90000000005',
                 '90000000006', '90000000007', '90000000008', '90000000009', '90000000010',
                 '90000000011', '90000000012', '90000000013', '90000000014', '90000000015')
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
WHERE p.cpf IN ('90000000001', '90000000002', '90000000003', '90000000004', '90000000005',
                 '90000000006', '90000000007', '90000000008', '90000000009', '90000000010',
                 '90000000011', '90000000012', '90000000013', '90000000014', '90000000015')
ORDER BY p.nome;

