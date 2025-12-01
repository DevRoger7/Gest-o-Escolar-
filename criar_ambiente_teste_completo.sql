-- ============================================================
-- SCRIPT COMPLETO - Ambiente de Teste SIGAE
-- Sistema de Gestão e Alimentação Escolar
-- ============================================================
-- 
-- Este script cria um ambiente completo de teste com:
-- 1. Escola de teste
-- 2. Gestor lotado na escola
-- 3. Séries escolares
-- 4. Disciplinas
-- 5. Turmas (3 turmas: 1º Ano A, 2º Ano A, 3º Ano A)
-- 6. Alunos (15 alunos distribuídos nas turmas)
-- 7. Professores (3 professores)
-- 8. Lotação de professores na escola
-- 9. Atribuição de professores às turmas com disciplinas
-- 10. Matrícula de alunos nas turmas
--
-- DADOS DE LOGIN:
-- Gestor: gestor.teste / 123456
-- ============================================================

-- ============================================================
-- PARTE 1: CRIAR ESCOLA
-- ============================================================
-- Verificar se a escola já existe, se não existir, criar
INSERT INTO `escola` (`codigo`, `nome`, `endereco`, `municipio`, `cep`, `telefone`, `email`, `qtd_salas`, `obs`, `criado_em`) 
SELECT * FROM (
    SELECT 
        '12345678' as codigo,
        'Escola Municipal de Teste - SIGAE' as nome,
        'Rua das Flores, 123 - Centro' as endereco,
        'Maranguape' as municipio,
        '61940-000' as cep,
        '(85) 3333-4444' as telefone,
        'escola.teste@sigae.com' as email,
        20 as qtd_salas,
        'Escola criada para testes do sistema SIGAE' as obs,
        NOW() as criado_em
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM escola WHERE codigo = '12345678'
);

-- Variável para armazenar o ID da escola (será usado nas próximas queries)
-- Se a escola já existir, buscar pelo código; caso contrário, usar LAST_INSERT_ID()
SET @escola_id = COALESCE(
    (SELECT id FROM escola WHERE codigo = '12345678' LIMIT 1),
    LAST_INSERT_ID()
);

-- ============================================================
-- PARTE 2: CRIAR GESTOR E LOTAR NA ESCOLA
-- ============================================================

-- 2.1. Criar pessoa do gestor (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
VALUES (
    '12345678900',
    'João Silva (Gestor Teste)',
    '1980-01-15',
    'M',
    'gestor.teste@sigae.com',
    '85999999999',
    'GESTOR'
);

-- Se a pessoa já existir, buscar pelo CPF; caso contrário, usar LAST_INSERT_ID()
SET @gestor_pessoa_id = COALESCE(
    (SELECT id FROM pessoa WHERE cpf = '12345678900' LIMIT 1),
    LAST_INSERT_ID()
);

-- 2.2. Criar gestor (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `gestor` (`pessoa_id`, `cargo`, `ativo`) 
VALUES (@gestor_pessoa_id, 'Diretor', 1);

-- Se o gestor já existir, buscar pelo pessoa_id; caso contrário, usar LAST_INSERT_ID()
SET @gestor_id = COALESCE(
    (SELECT id FROM gestor WHERE pessoa_id = @gestor_pessoa_id LIMIT 1),
    LAST_INSERT_ID()
);

-- 2.3. Criar usuário para login (usando INSERT IGNORE para evitar duplicatas)
-- Se já existir, atualizar a senha
INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
VALUES (
    @gestor_pessoa_id,
    'gestor.teste',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Senha: 123456
    'GESTAO',
    1
)
ON DUPLICATE KEY UPDATE 
    `senha_hash` = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
    `role` = 'GESTAO',
    `ativo` = 1;

-- Garantir que a senha está correta (UPDATE direto como backup)
UPDATE `usuario` 
SET `senha_hash` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    `role` = 'GESTAO',
    `ativo` = 1
WHERE `pessoa_id` = @gestor_pessoa_id;

-- 2.4. Lotar gestor na escola (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `gestor_lotacao` (`gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`) 
VALUES (@gestor_id, @escola_id, CURDATE(), NULL, 1, 'Diretor');

-- ============================================================
-- PARTE 3: CRIAR SÉRIES
-- ============================================================
INSERT IGNORE INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`) 
VALUES 
('1º Ano', '1ANO', 'ENSINO_FUNDAMENTAL', 1, 6, 7, 'Primeiro ano do Ensino Fundamental', 1),
('2º Ano', '2ANO', 'ENSINO_FUNDAMENTAL', 2, 7, 8, 'Segundo ano do Ensino Fundamental', 1),
('3º Ano', '3ANO', 'ENSINO_FUNDAMENTAL', 3, 8, 9, 'Terceiro ano do Ensino Fundamental', 1);

SET @serie_1ano_id = (SELECT id FROM serie WHERE codigo = '1ANO' LIMIT 1);
SET @serie_2ano_id = (SELECT id FROM serie WHERE codigo = '2ANO' LIMIT 1);
SET @serie_3ano_id = (SELECT id FROM serie WHERE codigo = '3ANO' LIMIT 1);

-- ============================================================
-- PARTE 4: CRIAR DISCIPLINAS
-- ============================================================
INSERT IGNORE INTO `disciplina` (`codigo`, `nome`, `carga_horaria`) 
VALUES 
('PORT', 'Língua Portuguesa', 160),
('MAT', 'Matemática', 160),
('HIST', 'História', 80),
('GEO', 'Geografia', 80),
('CIEN', 'Ciências', 80),
('EDF', 'Educação Física', 80),
('ART', 'Artes', 40),
('ING', 'Língua Inglesa', 40);

SET @disciplina_port_id = (SELECT id FROM disciplina WHERE codigo = 'PORT' LIMIT 1);
SET @disciplina_mat_id = (SELECT id FROM disciplina WHERE codigo = 'MAT' LIMIT 1);
SET @disciplina_hist_id = (SELECT id FROM disciplina WHERE codigo = 'HIST' LIMIT 1);
SET @disciplina_geo_id = (SELECT id FROM disciplina WHERE codigo = 'GEO' LIMIT 1);
SET @disciplina_cien_id = (SELECT id FROM disciplina WHERE codigo = 'CIEN' LIMIT 1);
SET @disciplina_edf_id = (SELECT id FROM disciplina WHERE codigo = 'EDF' LIMIT 1);

-- ============================================================
-- PARTE 5: CRIAR TURMAS
-- ============================================================
-- Verificar se as turmas já existem antes de criar
INSERT INTO `turma` (`escola_id`, `serie_id`, `ano_letivo`, `serie`, `letra`, `turno`, `capacidade`, `ativo`, `criado_em`) 
SELECT * FROM (
    SELECT @escola_id as escola_id, @serie_1ano_id as serie_id, YEAR(CURDATE()) as ano_letivo, '1º Ano' as serie, 'A' as letra, 'MANHA' as turno, 30 as capacidade, 1 as ativo, NOW() as criado_em
    UNION ALL
    SELECT @escola_id, @serie_2ano_id, YEAR(CURDATE()), '2º Ano', 'A', 'MANHA', 30, 1, NOW()
    UNION ALL
    SELECT @escola_id, @serie_3ano_id, YEAR(CURDATE()), '3º Ano', 'A', 'MANHA', 30, 1, NOW()
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM turma t 
    WHERE t.escola_id = @escola_id 
    AND t.serie = tmp.serie 
    AND t.letra = tmp.letra 
    AND t.ano_letivo = tmp.ano_letivo
);

SET @turma_1ano_id = (SELECT id FROM turma WHERE escola_id = @escola_id AND serie = '1º Ano' AND letra = 'A' LIMIT 1);
SET @turma_2ano_id = (SELECT id FROM turma WHERE escola_id = @escola_id AND serie = '2º Ano' AND letra = 'A' LIMIT 1);
SET @turma_3ano_id = (SELECT id FROM turma WHERE escola_id = @escola_id AND serie = '3º Ano' AND letra = 'A' LIMIT 1);

-- ============================================================
-- PARTE 6: CRIAR ALUNOS
-- ============================================================

-- 6.1. Criar pessoas dos alunos (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
VALUES 
-- Turma 1º Ano (5 alunos) - CPFs únicos para teste
('90000000001', 'Ana Silva Santos', '2017-03-15', 'F', 'ana.silva.teste@sigae.com', '85990000001', 'ALUNO'),
('90000000002', 'Bruno Oliveira Costa', '2017-05-20', 'M', 'bruno.oliveira.teste@sigae.com', '85990000002', 'ALUNO'),
('90000000003', 'Carla Mendes Lima', '2017-07-10', 'F', 'carla.mendes.teste@sigae.com', '85990000003', 'ALUNO'),
('90000000004', 'Daniel Souza Alves', '2017-09-25', 'M', 'daniel.souza.teste@sigae.com', '85990000004', 'ALUNO'),
('90000000005', 'Eduarda Ferreira Rocha', '2017-11-30', 'F', 'eduarda.ferreira.teste@sigae.com', '85990000005', 'ALUNO'),

-- Turma 2º Ano (5 alunos)
('90000000006', 'Felipe Gomes Pereira', '2016-02-14', 'M', 'felipe.gomes.teste@sigae.com', '85990000006', 'ALUNO'),
('90000000007', 'Gabriela Martins Dias', '2016-04-18', 'F', 'gabriela.martins.teste@sigae.com', '85990000007', 'ALUNO'),
('90000000008', 'Henrique Barbosa Ramos', '2016-06-22', 'M', 'henrique.barbosa.teste@sigae.com', '85990000008', 'ALUNO'),
('90000000009', 'Isabela Nunes Cardoso', '2016-08-28', 'F', 'isabela.nunes.teste@sigae.com', '85990000009', 'ALUNO'),
('90000000010', 'João Pedro Teixeira', '2016-10-12', 'M', 'joao.pedro.teste@sigae.com', '85990000010', 'ALUNO'),

-- Turma 3º Ano (5 alunos)
('90000000011', 'Larissa Araújo Freitas', '2015-01-08', 'F', 'larissa.araujo.teste@sigae.com', '85990000011', 'ALUNO'),
('90000000012', 'Marcos Vinicius Lopes', '2015-03-16', 'M', 'marcos.vinicius.teste@sigae.com', '85990000012', 'ALUNO'),
('90000000013', 'Natália Correia Monteiro', '2015-05-24', 'F', 'natalia.correia.teste@sigae.com', '85990000013', 'ALUNO'),
('90000000014', 'Otávio Ribeiro Campos', '2015-07-30', 'M', 'otavio.ribeiro.teste@sigae.com', '85990000014', 'ALUNO'),
('90000000015', 'Paula Cristina Moreira', '2015-09-05', 'F', 'paula.cristina.teste@sigae.com', '85990000015', 'ALUNO');

-- 6.2. Criar registros de alunos (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `aluno` (`pessoa_id`, `matricula`, `escola_id`, `data_matricula`, `situacao`, `ativo`) 
SELECT 
    p.id,
    CONCAT('MAT-', LPAD(p.id, 6, '0')),
    @escola_id,
    CURDATE(),
    'MATRICULADO',
    1
FROM pessoa p
WHERE p.cpf IN (
    '90000000001', '90000000002', '90000000003', '90000000004', '90000000005',
    '90000000006', '90000000007', '90000000008', '90000000009', '90000000010',
    '90000000011', '90000000012', '90000000013', '90000000014', '90000000015'
);

-- 6.3. Criar usuários para os alunos (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
SELECT 
    p.id,
    LOWER(REPLACE(REPLACE(REPLACE(REPLACE(p.nome, ' ', '.'), '(', ''), ')', ''), ' ', '')),
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Senha: 123456
    'ALUNO',
    1
FROM pessoa p
INNER JOIN aluno a ON a.pessoa_id = p.id
WHERE p.cpf IN (
    '90000000001', '90000000002', '90000000003', '90000000004', '90000000005',
    '90000000006', '90000000007', '90000000008', '90000000009', '90000000010',
    '90000000011', '90000000012', '90000000013', '90000000014', '90000000015'
);

-- 6.4. Matricular alunos nas turmas (usando INSERT IGNORE para evitar duplicatas)
-- Turma 1º Ano
INSERT IGNORE INTO `aluno_turma` (`aluno_id`, `turma_id`, `inicio`, `status`, `criado_em`)
SELECT a.id, @turma_1ano_id, CURDATE(), 'MATRICULADO', NOW()
FROM aluno a
INNER JOIN pessoa p ON a.pessoa_id = p.id
WHERE p.cpf IN ('90000000001', '90000000002', '90000000003', '90000000004', '90000000005');

-- Turma 2º Ano
INSERT IGNORE INTO `aluno_turma` (`aluno_id`, `turma_id`, `inicio`, `status`, `criado_em`)
SELECT a.id, @turma_2ano_id, CURDATE(), 'MATRICULADO', NOW()
FROM aluno a
INNER JOIN pessoa p ON a.pessoa_id = p.id
WHERE p.cpf IN ('90000000006', '90000000007', '90000000008', '90000000009', '90000000010');

-- Turma 3º Ano
INSERT IGNORE INTO `aluno_turma` (`aluno_id`, `turma_id`, `inicio`, `status`, `criado_em`)
SELECT a.id, @turma_3ano_id, CURDATE(), 'MATRICULADO', NOW()
FROM aluno a
INNER JOIN pessoa p ON a.pessoa_id = p.id
WHERE p.cpf IN ('90000000011', '90000000012', '90000000013', '90000000014', '90000000015');

-- ============================================================
-- PARTE 7: CRIAR PROFESSORES
-- ============================================================

-- 7.1. Criar pessoas dos professores (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
VALUES 
('80000000001', 'Maria Santos (Professora Português)', '1985-05-10', 'F', 'maria.santos.teste@sigae.com', '85980000001', 'PROFESSOR'),
('80000000002', 'José Carlos (Professor Matemática)', '1982-08-20', 'M', 'jose.carlos.teste@sigae.com', '85980000002', 'PROFESSOR'),
('80000000003', 'Patrícia Lima (Professora História)', '1987-12-05', 'F', 'patricia.lima.teste@sigae.com', '85980000003', 'PROFESSOR');

-- 7.2. Criar registros de professores (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `professor` (`pessoa_id`, `matricula`, `formacao`, `data_admissao`, `ativo`) 
SELECT 
    p.id,
    CONCAT('PROF-', LPAD(p.id, 6, '0')),
    CASE 
        WHEN p.cpf = '80000000001' THEN 'Licenciatura em Letras'
        WHEN p.cpf = '80000000002' THEN 'Licenciatura em Matemática'
        WHEN p.cpf = '80000000003' THEN 'Licenciatura em História'
    END,
    CURDATE(),
    1
FROM pessoa p
WHERE p.cpf IN ('80000000001', '80000000002', '80000000003');

SET @prof_port_id = (SELECT id FROM professor WHERE pessoa_id = (SELECT id FROM pessoa WHERE cpf = '80000000001' LIMIT 1) LIMIT 1);
SET @prof_mat_id = (SELECT id FROM professor WHERE pessoa_id = (SELECT id FROM pessoa WHERE cpf = '80000000002' LIMIT 1) LIMIT 1);
SET @prof_hist_id = (SELECT id FROM professor WHERE pessoa_id = (SELECT id FROM pessoa WHERE cpf = '80000000003' LIMIT 1) LIMIT 1);

-- 7.3. Criar usuários para os professores (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
SELECT 
    p.id,
    LOWER(REPLACE(SUBSTRING_INDEX(p.nome, ' ', 1), '(', '')),
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Senha: 123456
    'PROFESSOR',
    1
FROM pessoa p
WHERE p.cpf IN ('80000000001', '80000000002', '80000000003');

-- 7.4. Lotar professores na escola (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `professor_lotacao` (`professor_id`, `escola_id`, `inicio`, `carga_horaria`, `observacao`, `criado_em`)
VALUES 
(@prof_port_id, @escola_id, CURDATE(), 20, 'Professora de Língua Portuguesa', NOW()),
(@prof_mat_id, @escola_id, CURDATE(), 20, 'Professor de Matemática', NOW()),
(@prof_hist_id, @escola_id, CURDATE(), 10, 'Professora de História', NOW());

-- ============================================================
-- PARTE 8: ATRIBUIR PROFESSORES ÀS TURMAS COM DISCIPLINAS
-- ============================================================

-- Professora de Português em todas as turmas (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO `turma_professor` (`turma_id`, `professor_id`, `disciplina_id`, `inicio`, `regime`) 
VALUES 
(@turma_1ano_id, @prof_port_id, @disciplina_port_id, CURDATE(), 'REGULAR'),
(@turma_2ano_id, @prof_port_id, @disciplina_port_id, CURDATE(), 'REGULAR'),
(@turma_3ano_id, @prof_port_id, @disciplina_port_id, CURDATE(), 'REGULAR');

-- Professor de Matemática em todas as turmas
INSERT IGNORE INTO `turma_professor` (`turma_id`, `professor_id`, `disciplina_id`, `inicio`, `regime`) 
VALUES 
(@turma_1ano_id, @prof_mat_id, @disciplina_mat_id, CURDATE(), 'REGULAR'),
(@turma_2ano_id, @prof_mat_id, @disciplina_mat_id, CURDATE(), 'REGULAR'),
(@turma_3ano_id, @prof_mat_id, @disciplina_mat_id, CURDATE(), 'REGULAR');

-- Professora de História em todas as turmas
INSERT IGNORE INTO `turma_professor` (`turma_id`, `professor_id`, `disciplina_id`, `inicio`, `regime`) 
VALUES 
(@turma_1ano_id, @prof_hist_id, @disciplina_hist_id, CURDATE(), 'REGULAR'),
(@turma_2ano_id, @prof_hist_id, @disciplina_hist_id, CURDATE(), 'REGULAR'),
(@turma_3ano_id, @prof_hist_id, @disciplina_hist_id, CURDATE(), 'REGULAR');

-- ============================================================
-- RESUMO DO AMBIENTE CRIADO
-- ============================================================
-- 
-- ESCOLA:
-- - Nome: Escola Municipal de Teste - SIGAE
-- - Código: 12345678
-- - ID: @escola_id
--
-- GESTOR:
-- - Nome: João Silva (Gestor Teste)
-- - Username: gestor.teste
-- - Senha: 123456
-- - Lotado como Diretor na escola
--
-- TURMAS (3 turmas):
-- - 1º Ano A (Manhã) - 5 alunos
-- - 2º Ano A (Manhã) - 5 alunos
-- - 3º Ano A (Manhã) - 5 alunos
--
-- DISCIPLINAS (8 disciplinas):
-- - Língua Portuguesa
-- - Matemática
-- - História
-- - Geografia
-- - Ciências
-- - Educação Física
-- - Artes
-- - Língua Inglesa
--
-- PROFESSORES (3 professores):
-- - Maria Santos (Português) - Username: maria / Senha: 123456
-- - José Carlos (Matemática) - Username: jose / Senha: 123456
-- - Patrícia Lima (História) - Username: patricia / Senha: 123456
--
-- ALUNOS (15 alunos):
-- - 5 alunos no 1º Ano A
-- - 5 alunos no 2º Ano A
-- - 5 alunos no 3º Ano A
--
-- ============================================================
-- QUERIES DE VERIFICAÇÃO
-- ============================================================

-- Verificar escola criada
-- SELECT * FROM escola WHERE codigo = '12345678';

-- Verificar gestor e lotação
-- SELECT g.*, p.nome, p.email, gl.escola_id, e.nome as escola_nome
-- FROM gestor g
-- INNER JOIN pessoa p ON g.pessoa_id = p.id
-- LEFT JOIN gestor_lotacao gl ON g.id = gl.gestor_id AND gl.fim IS NULL
-- LEFT JOIN escola e ON gl.escola_id = e.id
-- WHERE p.cpf = '12345678900';

-- Verificar turmas criadas
-- SELECT t.*, e.nome as escola_nome, s.nome as serie_nome,
--        COUNT(DISTINCT at.aluno_id) as total_alunos
-- FROM turma t
-- INNER JOIN escola e ON t.escola_id = e.id
-- LEFT JOIN serie s ON t.serie_id = s.id
-- LEFT JOIN aluno_turma at ON t.id = at.turma_id AND at.fim IS NULL
-- WHERE e.codigo = '12345678'
-- GROUP BY t.id;

-- Verificar alunos matriculados
-- SELECT a.id, p.nome, p.cpf, t.serie, t.letra, t.turno
-- FROM aluno a
-- INNER JOIN pessoa p ON a.pessoa_id = p.id
-- INNER JOIN aluno_turma at ON a.id = at.aluno_id AND at.fim IS NULL
-- INNER JOIN turma t ON at.turma_id = t.id
-- INNER JOIN escola e ON t.escola_id = e.id
-- WHERE e.codigo = '12345678'
-- ORDER BY t.serie, t.letra, p.nome;

-- Verificar professores e atribuições
-- SELECT p.nome as professor_nome, d.nome as disciplina_nome, 
--        CONCAT(t.serie, ' ', t.letra) as turma, t.turno
-- FROM turma_professor tp
-- INNER JOIN professor prof ON tp.professor_id = prof.id
-- INNER JOIN pessoa p ON prof.pessoa_id = p.id
-- INNER JOIN disciplina d ON tp.disciplina_id = d.id
-- INNER JOIN turma t ON tp.turma_id = t.id
-- INNER JOIN escola e ON t.escola_id = e.id
-- WHERE e.codigo = '12345678' AND tp.fim IS NULL
-- ORDER BY t.serie, d.nome;

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================

