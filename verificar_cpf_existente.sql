-- ============================================================
-- Verificar CPFs existentes no banco
-- ============================================================

-- Ver todos os CPFs de alunos
SELECT cpf, nome, tipo FROM pessoa WHERE tipo = 'ALUNO' ORDER BY cpf;

-- Ver todos os CPFs (todos os tipos)
SELECT cpf, nome, tipo FROM pessoa ORDER BY cpf;

-- Verificar se um CPF específico existe
SELECT cpf, nome, tipo FROM pessoa WHERE cpf = '12345678901';

-- Ver CPFs disponíveis (últimos 10 dígitos que não estão em uso)
-- Use este para escolher um CPF único
SELECT 
    CONCAT('00000000', LPAD(ROW_NUMBER() OVER (ORDER BY cpf), 2, '0')) as cpf_sugerido
FROM (
    SELECT 1 as cpf UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) as numeros
WHERE NOT EXISTS (
    SELECT 1 FROM pessoa 
    WHERE cpf = CONCAT('00000000', LPAD(ROW_NUMBER() OVER (ORDER BY cpf), 2, '0'))
)
LIMIT 10;

