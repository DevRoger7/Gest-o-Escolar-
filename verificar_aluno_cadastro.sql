-- Script para verificar se um aluno está cadastrado corretamente
-- Substitua 'CPF_DO_ALUNO' pelo CPF do aluno que está tentando acessar

-- 1. Verificar se a pessoa existe
SELECT '=== VERIFICANDO PESSOA ===' as etapa;
SELECT id, cpf, nome, tipo FROM pessoa WHERE cpf = '90000000001'; -- Substitua pelo CPF do aluno

-- 2. Verificar se existe usuário para essa pessoa
SELECT '=== VERIFICANDO USUÁRIO ===' as etapa;
SELECT u.id as usuario_id, u.pessoa_id, u.username, u.role, p.cpf, p.nome 
FROM usuario u 
INNER JOIN pessoa p ON u.pessoa_id = p.id 
WHERE p.cpf = '90000000001'; -- Substitua pelo CPF do aluno

-- 3. Verificar se existe registro na tabela aluno
SELECT '=== VERIFICANDO ALUNO ===' as etapa;
SELECT a.id as aluno_id, a.pessoa_id, a.matricula, a.escola_id, a.situacao, a.ativo, p.cpf, p.nome
FROM aluno a
INNER JOIN pessoa p ON a.pessoa_id = p.id
WHERE p.cpf = '90000000001'; -- Substitua pelo CPF do aluno

-- 4. Verificar se o aluno está matriculado em alguma turma
SELECT '=== VERIFICANDO MATRÍCULA EM TURMA ===' as etapa;
SELECT at.id, at.aluno_id, at.turma_id, at.inicio, at.fim, at.status,
       t.serie, t.letra, t.turno, t.ano_letivo
FROM aluno_turma at
INNER JOIN turma t ON at.turma_id = t.id
INNER JOIN aluno a ON at.aluno_id = a.id
INNER JOIN pessoa p ON a.pessoa_id = p.id
WHERE p.cpf = '90000000001'; -- Substitua pelo CPF do aluno

-- 5. Se o aluno não existir, criar (ajuste os valores conforme necessário)
-- Descomente e ajuste se necessário:
/*
INSERT INTO aluno (pessoa_id, matricula, escola_id, data_matricula, situacao, ativo)
SELECT 
    p.id,
    CONCAT('MAT-', LPAD(p.id, 6, '0')),
    (SELECT id FROM escola LIMIT 1), -- Ajuste para a escola correta
    CURDATE(),
    'MATRICULADO',
    1
FROM pessoa p
WHERE p.cpf = '90000000001' -- Substitua pelo CPF do aluno
AND NOT EXISTS (
    SELECT 1 FROM aluno a WHERE a.pessoa_id = p.id
);
*/

