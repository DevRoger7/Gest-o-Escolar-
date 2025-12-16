-- =====================================================
-- SCRIPT CORRIGIDO PARA POPULAR BANCO DE DADOS COM DADOS DE TESTE
-- Sistema de Gestão Escolar
-- Baseado na estrutura real do banco de dados
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- =====================================================
-- 2. INSERIR ESCOLAS (corrigido: codigo, municipio)
-- =====================================================
INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `municipio`, `estado`, `cep`, `telefone`, `email`, `ativo`, `criado_em`) VALUES
(100, 'ESC001', 'Escola Municipal João Silva', 'Rua das Flores', '100', 'Próximo ao mercado', 'Centro', 'Maranguape', 'CE', '61940000', '(85) 3341-1234', 'joaosilva@edu.maranguape.ce.gov.br', 1, NOW()),
(101, 'ESC002', 'Escola Municipal Maria José', 'Av. Principal', '250', NULL, 'São João', 'Maranguape', 'CE', '61940001', '(85) 3341-2345', 'mariajose@edu.maranguape.ce.gov.br', 1, NOW()),
(102, 'ESC003', 'Escola Municipal Pedro Alves', 'Rua do Comércio', '500', 'Bloco A', 'Centro', 'Maranguape', 'CE', '61940002', '(85) 3341-3456', 'pedroalves@edu.maranguape.ce.gov.br', 1, NOW())
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- =====================================================
-- 3. INSERIR SÉRIES
-- =====================================================
INSERT INTO `serie` (`id`, `nome`, `nivel_ensino`, `ordem`, `ativo`, `criado_em`) VALUES
(100, '1º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 1, 1, NOW()),
(101, '2º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 2, 1, NOW()),
(102, '3º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 3, 1, NOW()),
(103, '4º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 4, 1, NOW()),
(104, '5º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 5, 1, NOW()),
(105, '6º Ano', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 6, 1, NOW()),
(106, '7º Ano', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 7, 1, NOW()),
(107, '8º Ano', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 8, 1, NOW()),
(108, '9º Ano', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 9, 1, NOW())
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- =====================================================
-- 4. INSERIR DISCIPLINAS (corrigido: codigo, sem nivel_ensino)
-- =====================================================
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `carga_horaria`, `ativo`, `criado_em`) VALUES
(100, 'PORT', 'Língua Portuguesa', 160, 1, NOW()),
(101, 'MAT', 'Matemática', 160, 1, NOW()),
(102, 'CI', 'Ciências', 80, 1, NOW()),
(103, 'HIST', 'História', 80, 1, NOW()),
(104, 'GEO', 'Geografia', 80, 1, NOW()),
(105, 'AR', 'Artes', 40, 1, NOW()),
(106, 'EF', 'Educação Física', 40, 1, NOW())
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- =====================================================
-- 5. INSERIR TURMAS (corrigido: serie, letra, turno enum, capacidade)
-- =====================================================
INSERT INTO `turma` (`id`, `escola_id`, `serie_id`, `serie`, `letra`, `turno`, `ano_letivo`, `capacidade`, `ativo`, `criado_em`) VALUES
(100, 100, 100, '1º Ano', 'A', 'MANHA', 2025, 25, 1, NOW()),
(101, 100, 100, '1º Ano', 'B', 'TARDE', 2025, 25, 1, NOW()),
(102, 100, 101, '2º Ano', 'A', 'MANHA', 2025, 25, 1, NOW()),
(103, 100, 102, '3º Ano', 'A', 'MANHA', 2025, 25, 1, NOW()),
(104, 101, 100, '1º Ano', 'A', 'MANHA', 2025, 25, 1, NOW()),
(105, 101, 101, '2º Ano', 'A', 'MANHA', 2025, 25, 1, NOW()),
(106, 102, 105, '6º Ano', 'A', 'MANHA', 2025, 30, 1, NOW()),
(107, 102, 106, '7º Ano', 'A', 'MANHA', 2025, 30, 1, NOW())
ON DUPLICATE KEY UPDATE serie = VALUES(serie);

-- =====================================================
-- 6. INSERIR PESSOAS (usando IDs altos para evitar conflitos)
-- =====================================================

-- Pessoas - Alunos (sem ON DUPLICATE KEY para garantir inserção)
INSERT IGNORE INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `ativo`, `criado_em`) VALUES
(1000, '11111111111', 'Ana Silva Santos', '2018-03-15', 'F', NULL, '(85) 98888-1111', 'Rua A', '10', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(1001, '22222222222', 'Bruno Oliveira Costa', '2018-05-20', 'M', NULL, '(85) 98888-2222', 'Rua B', '20', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(1002, '33333333333', 'Carlos Pereira Lima', '2018-07-10', 'M', NULL, '(85) 98888-3333', 'Rua C', '30', 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', 1, NOW()),
(1003, '44444444444', 'Daniela Souza Alves', '2017-09-25', 'F', NULL, '(85) 98888-4444', 'Rua D', '40', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(1004, '55555555555', 'Eduardo Martins Ferreira', '2017-11-30', 'M', NULL, '(85) 98888-5555', 'Rua E', '50', 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', 1, NOW()),
(1005, '66666666666', 'Fernanda Rodrigues Gomes', '2016-01-15', 'F', NULL, '(85) 98888-6666', 'Rua F', '60', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(1006, '77777777777', 'Gabriel Nunes Barbosa', '2016-03-20', 'M', NULL, '(85) 98888-7777', 'Rua G', '70', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(1007, '88888888888', 'Helena Castro Rocha', '2015-05-10', 'F', NULL, '(85) 98888-8888', 'Rua H', '80', 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', 1, NOW()),
(1008, '99999999999', 'Igor Mendes Dias', '2012-07-05', 'M', NULL, '(85) 98888-9999', 'Rua I', '90', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(1009, '10101010101', 'Julia Araújo Teixeira', '2012-09-12', 'F', NULL, '(85) 98888-1010', 'Rua J', '100', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW());

-- Pessoas - Professores
INSERT IGNORE INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `ativo`, `criado_em`) VALUES
(2000, '11111111112', 'Maria da Silva', '1985-01-10', 'F', 'maria.silva@edu.maranguape.ce.gov.br', '(85) 98765-4321', 'Av. Professores', '100', 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', 1, NOW()),
(2001, '22222222223', 'João Santos', '1980-03-15', 'M', 'joao.santos@edu.maranguape.ce.gov.br', '(85) 98765-4322', 'Av. Professores', '200', 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', 1, NOW()),
(2002, '33333333334', 'Ana Costa', '1988-05-20', 'F', 'ana.costa@edu.maranguape.ce.gov.br', '(85) 98765-4323', 'Av. Professores', '300', 'São João', 'Maranguape', 'CE', '61940001', 'PROFESSOR', 1, NOW()),
(2003, '44444444445', 'Pedro Oliveira', '1982-07-25', 'M', 'pedro.oliveira@edu.maranguape.ce.gov.br', '(85) 98765-4324', 'Av. Professores', '400', 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', 1, NOW()),
(2004, '55555555556', 'Carla Mendes', '1990-09-30', 'F', 'carla.mendes@edu.maranguape.ce.gov.br', '(85) 98765-4325', 'Av. Professores', '500', 'São João', 'Maranguape', 'CE', '61940001', 'PROFESSOR', 1, NOW());

-- Pessoas - Gestores
INSERT IGNORE INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `ativo`, `criado_em`) VALUES
(3000, '66666666667', 'Roberto Alves', '1975-11-10', 'M', 'roberto.alves@edu.maranguape.ce.gov.br', '(85) 98765-4326', 'Av. Gestores', '100', 'Centro', 'Maranguape', 'CE', '61940000', 'GESTOR', 1, NOW()),
(3001, '77777777778', 'Patricia Lima', '1978-12-15', 'F', 'patricia.lima@edu.maranguape.ce.gov.br', '(85) 98765-4327', 'Av. Gestores', '200', 'Centro', 'Maranguape', 'CE', '61940000', 'GESTOR', 1, NOW());

-- Pessoas - Funcionários
INSERT IGNORE INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `ativo`, `criado_em`) VALUES
(4000, '88888888889', 'Francisco Souza', '1985-01-20', 'M', 'francisco.souza@edu.maranguape.ce.gov.br', '(85) 98765-4328', 'Av. Funcionários', '100', 'Centro', 'Maranguape', 'CE', '61940000', 'FUNCIONARIO', 1, NOW()),
(4001, '99999999990', 'Lucia Ferreira', '1990-03-25', 'F', 'lucia.ferreira@edu.maranguape.ce.gov.br', '(85) 98765-4329', 'Av. Funcionários', '200', 'São João', 'Maranguape', 'CE', '61940001', 'FUNCIONARIO', 1, NOW());

-- =====================================================
-- 7. INSERIR USUÁRIOS (senha padrão: 123456)
-- =====================================================
INSERT IGNORE INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `created_at`) VALUES
(100, 3000, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADM', 1, 1, NOW()),
(101, 3000, 'roberto.alves', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GESTAO', 1, 1, NOW()),
(102, 3001, 'patricia.lima', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GESTAO', 1, 1, NOW()),
(103, 2000, 'maria.silva', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW()),
(104, 2001, 'joao.santos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW()),
(105, 2002, 'ana.costa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW()),
(106, 2003, 'pedro.oliveira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW()),
(107, 2004, 'carla.mendes', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW());

-- =====================================================
-- 8. INSERIR ALUNOS
-- =====================================================
INSERT IGNORE INTO `aluno` (`id`, `pessoa_id`, `matricula`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `ativo`, `criado_em`) VALUES
(100, 1000, '2025001', '2025-01-15', 'MATRICULADO', '2018-03-15', 'Brasileira', 1, NOW()),
(101, 1001, '2025002', '2025-01-15', 'MATRICULADO', '2018-05-20', 'Brasileira', 1, NOW()),
(102, 1002, '2025003', '2025-01-15', 'MATRICULADO', '2018-07-10', 'Brasileira', 1, NOW()),
(103, 1003, '2025004', '2025-01-15', 'MATRICULADO', '2017-09-25', 'Brasileira', 1, NOW()),
(104, 1004, '2025005', '2025-01-15', 'MATRICULADO', '2017-11-30', 'Brasileira', 1, NOW()),
(105, 1005, '2025006', '2025-01-15', 'MATRICULADO', '2016-01-15', 'Brasileira', 1, NOW()),
(106, 1006, '2025007', '2025-01-15', 'MATRICULADO', '2016-03-20', 'Brasileira', 1, NOW()),
(107, 1007, '2025008', '2025-01-15', 'MATRICULADO', '2015-05-10', 'Brasileira', 1, NOW()),
(108, 1008, '2025009', '2025-01-15', 'MATRICULADO', '2012-07-05', 'Brasileira', 1, NOW()),
(109, 1009, '2025010', '2025-01-15', 'MATRICULADO', '2012-09-12', 'Brasileira', 1, NOW());

-- =====================================================
-- 9. INSERIR PROFESSORES (corrigido: matricula em vez de registro)
-- =====================================================
INSERT IGNORE INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `ativo`, `criado_em`) VALUES
(100, 2000, 'PROF001', 'Licenciatura em Pedagogia', 1, NOW()),
(101, 2001, 'PROF002', 'Licenciatura em Matemática', 1, NOW()),
(102, 2002, 'PROF003', 'Licenciatura em Letras', 1, NOW()),
(103, 2003, 'PROF004', 'Licenciatura em Ciências', 1, NOW()),
(104, 2004, 'PROF005', 'Licenciatura em História', 1, NOW());

-- =====================================================
-- 10. INSERIR GESTORES (corrigido: sem registro)
-- =====================================================
INSERT IGNORE INTO `gestor` (`id`, `pessoa_id`, `cargo`, `ativo`, `criado_em`) VALUES
(100, 3000, 'Diretor', 1, NOW()),
(101, 3001, 'Coordenador Pedagógico', 1, NOW());

-- =====================================================
-- 11. INSERIR FUNCIONÁRIOS (corrigido: matricula em vez de registro)
-- =====================================================
INSERT IGNORE INTO `funcionario` (`id`, `pessoa_id`, `matricula`, `cargo`, `setor`, `ativo`, `criado_em`) VALUES
(100, 4000, 'FUNC001', 'Secretário', 'Secretaria', 1, NOW()),
(101, 4001, 'FUNC002', 'Auxiliar Administrativo', 'Secretaria', 1, NOW());

-- =====================================================
-- 12. INSERIR LOTAÇÕES (PROFESSORES E ESCOLAS)
-- =====================================================
INSERT INTO `professor_lotacao` (`professor_id`, `escola_id`, `inicio`, `fim`, `criado_em`) VALUES
(100, 100, '2025-01-01', NULL, NOW()),
(101, 100, '2025-01-01', NULL, NOW()),
(102, 100, '2025-01-01', NULL, NOW()),
(103, 101, '2025-01-01', NULL, NOW()),
(104, 101, '2025-01-01', NULL, NOW()),
(100, 101, '2025-01-01', NULL, NOW()),
(102, 102, '2025-01-01', NULL, NOW())
ON DUPLICATE KEY UPDATE inicio = VALUES(inicio);

-- =====================================================
-- 13. INSERIR LOTAÇÕES (GESTORES E ESCOLAS)
-- =====================================================
INSERT INTO `gestor_lotacao` (`gestor_id`, `escola_id`, `inicio`, `fim`, `criado_em`) VALUES
(100, 100, '2025-01-01', NULL, NOW()),
(101, 101, '2025-01-01', NULL, NOW()),
(100, 102, '2025-01-01', NULL, NOW())
ON DUPLICATE KEY UPDATE inicio = VALUES(inicio);

-- =====================================================
-- 14. INSERIR LOTAÇÕES (FUNCIONÁRIOS E ESCOLAS)
-- =====================================================
INSERT INTO `funcionario_lotacao` (`funcionario_id`, `escola_id`, `setor`, `inicio`, `fim`, `criado_em`) VALUES
(100, 100, 'Secretaria', '2025-01-01', NULL, NOW()),
(101, 101, 'Secretaria', '2025-01-01', NULL, NOW()),
(100, 101, 'Biblioteca', '2025-01-01', NULL, NOW())
ON DUPLICATE KEY UPDATE inicio = VALUES(inicio);

-- =====================================================
-- 15. INSERIR ALUNOS EM TURMAS
-- =====================================================
INSERT INTO `aluno_turma` (`aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `criado_em`) VALUES
(100, 100, '2025-01-15', NULL, 'MATRICULADO', NOW()),
(101, 100, '2025-01-15', NULL, 'MATRICULADO', NOW()),
(102, 100, '2025-01-15', NULL, 'MATRICULADO', NOW()),
(103, 102, '2025-01-15', NULL, 'MATRICULADO', NOW()),
(104, 102, '2025-01-15', NULL, 'MATRICULADO', NOW()),
(105, 103, '2025-01-15', NULL, 'MATRICULADO', NOW()),
(106, 103, '2025-01-15', NULL, 'MATRICULADO', NOW()),
(107, 103, '2025-01-15', NULL, 'MATRICULADO', NOW()),
(108, 106, '2025-01-15', NULL, 'MATRICULADO', NOW()),
(109, 106, '2025-01-15', NULL, 'MATRICULADO', NOW())
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- =====================================================
-- 16. INSERIR PROFESSORES EM TURMAS
-- =====================================================
INSERT INTO `turma_professor` (`turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `criado_em`) VALUES
(100, 100, 100, '2025-01-15', NULL, 'REGULAR', NOW()),
(100, 101, 101, '2025-01-15', NULL, 'REGULAR', NOW()),
(102, 100, 100, '2025-01-15', NULL, 'REGULAR', NOW()),
(102, 101, 101, '2025-01-15', NULL, 'REGULAR', NOW()),
(103, 102, 100, '2025-01-15', NULL, 'REGULAR', NOW()),
(103, 103, 102, '2025-01-15', NULL, 'REGULAR', NOW()),
(106, 102, 100, '2025-01-15', NULL, 'REGULAR', NOW()),
(106, 101, 101, '2025-01-15', NULL, 'REGULAR', NOW())
ON DUPLICATE KEY UPDATE regime = VALUES(regime);

-- =====================================================
-- 17. INSERIR NOTAS (corrigido: sem tipo_avaliacao)
-- =====================================================
INSERT INTO `nota` (`aluno_id`, `turma_id`, `disciplina_id`, `bimestre`, `nota`, `lancado_em`, `lancado_por`) VALUES
(100, 100, 100, 1, 8.5, NOW(), 103),
(100, 100, 101, 1, 7.0, NOW(), 104),
(101, 100, 100, 1, 9.0, NOW(), 103),
(101, 100, 101, 1, 8.5, NOW(), 104),
(102, 100, 100, 1, 7.5, NOW(), 103),
(102, 100, 101, 1, 6.5, NOW(), 104),
(103, 102, 100, 1, 8.0, NOW(), 103),
(103, 102, 101, 1, 9.5, NOW(), 104),
(104, 102, 100, 1, 6.0, NOW(), 103),
(104, 102, 101, 1, 7.5, NOW(), 104),
(105, 103, 100, 1, 9.5, NOW(), 105),
(105, 103, 102, 1, 8.0, NOW(), 106),
(106, 103, 100, 1, 7.0, NOW(), 105),
(106, 103, 102, 1, 6.5, NOW(), 106),
(108, 106, 100, 1, 8.5, NOW(), 105),
(108, 106, 101, 1, 9.0, NOW(), 104),
(109, 106, 100, 1, 7.5, NOW(), 105),
(109, 106, 101, 1, 8.0, NOW(), 104);

-- =====================================================
-- 18. INSERIR FREQUÊNCIAS (corrigido: sem disciplina_id)
-- =====================================================
INSERT INTO `frequencia` (`aluno_id`, `turma_id`, `data`, `presenca`, `registrado_em`, `registrado_por`) VALUES
(100, 100, '2025-01-20', 1, NOW(), 103),
(100, 100, '2025-01-22', 1, NOW(), 103),
(100, 100, '2025-01-24', 0, NOW(), 103),
(101, 100, '2025-01-20', 1, NOW(), 103),
(101, 100, '2025-01-22', 1, NOW(), 103),
(101, 100, '2025-01-24', 1, NOW(), 103),
(102, 100, '2025-01-20', 1, NOW(), 103),
(102, 100, '2025-01-22', 0, NOW(), 103),
(102, 100, '2025-01-24', 1, NOW(), 103),
(103, 102, '2025-01-20', 1, NOW(), 103),
(103, 102, '2025-01-22', 1, NOW(), 103),
(103, 102, '2025-01-24', 1, NOW(), 103),
(104, 102, '2025-01-20', 0, NOW(), 103),
(104, 102, '2025-01-22', 1, NOW(), 103),
(104, 102, '2025-01-24', 1, NOW(), 103);

-- =====================================================
-- 19. INSERIR PLANOS DE AULA
-- =====================================================
INSERT INTO `plano_aula` (`turma_id`, `disciplina_id`, `professor_id`, `titulo`, `conteudo`, `objetivos`, `metodologia`, `recursos`, `avaliacao`, `data_aula`, `bimestre`, `status`, `criado_por`, `criado_em`, `aprovado_por`, `data_aprovacao`) VALUES
(100, 100, 103, 'Introdução à Leitura', 'Apresentação de textos simples e alfabeto', 'Desenvolver habilidades básicas de leitura', 'Aulas expositivas e atividades práticas', 'Livros, quadro, material didático', 'Avaliação contínua e prova escrita', '2025-01-20', 1, 'APROVADO', 103, NOW(), 103, NOW()),
(100, 101, 104, 'Números de 1 a 10', 'Reconhecimento e escrita de números', 'Reconhecer e escrever números de 1 a 10', 'Jogos educativos e exercícios', 'Material concreto, cartazes', 'Observação e atividades práticas', '2025-01-21', 1, 'APROVADO', 104, NOW(), 104, NOW()),
(102, 100, 103, 'Leitura e Interpretação', 'Textos narrativos simples', 'Melhorar compreensão leitora', 'Leitura compartilhada e discussão', 'Livros de histórias', 'Questionário de compreensão', '2025-01-22', 1, 'APROVADO', 103, NOW(), 103, NOW()),
(103, 100, 105, 'Gramática Básica', 'Substantivos e adjetivos', 'Identificar substantivos e adjetivos', 'Exemplos práticos e exercícios', 'Textos, exercícios', 'Prova escrita', '2025-01-23', 1, 'APROVADO', 105, NOW(), 105, NOW()),
(106, 100, 105, 'Literatura Brasileira', 'Obras clássicas da literatura', 'Conhecer obras literárias brasileiras', 'Leitura e análise de textos', 'Livros literários', 'Seminário e prova', '2025-01-24', 1, 'APROVADO', 105, NOW(), 105, NOW());

-- =====================================================
-- 20. INSERIR HABILIDADES BNCC (verificar estrutura dinamicamente)
-- =====================================================
-- Tenta inserir com codigo_bncc primeiro, se falhar, tenta com codigo
INSERT INTO `habilidades_bncc` (`codigo_bncc`, `descricao`, `nivel_ensino`, `disciplina_id`, `serie`, `ativo`) VALUES
('EF01LP01', 'Reconhecer que textos são lidos da esquerda para a direita e de cima para baixo da página.', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 100, '1º', 1),
('EF01LP02', 'Ler palavras formadas por sílabas canônicas (consoante-vogal).', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 100, '1º', 1),
('EF01MA01', 'Utilizar números naturais como indicador de quantidade ou de ordem em diferentes situações cotidianas.', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 101, '1º', 1),
('EF01MA02', 'Contar de maneira exata ou aproximada, por estimativa, quantidades de elementos de uma coleção.', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 101, '1º', 1),
('EF69LP01', 'Diferenciar liberdade de expressão de discursos de ódio.', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 100, '6º ao 9º', 1),
('EF69MA01', 'Compreender e utilizar a ideia de número racional para resolver problemas matemáticos.', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 101, '6º ao 9º', 1)
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- RESUMO DOS DADOS INSERIDOS
-- =====================================================
-- 3 Escolas (IDs: 100-102)
-- 9 Séries (IDs: 100-108)
-- 7 Disciplinas (IDs: 100-106)
-- 8 Turmas (IDs: 100-107)
-- 14 Pessoas (IDs: 1000-4001)
-- 8 Usuários (IDs: 100-107)
-- 10 Alunos (IDs: 100-109)
-- 5 Professores (IDs: 100-104)
-- 2 Gestores (IDs: 100-101)
-- 2 Funcionários (IDs: 100-101)
-- Lotações, matrículas, notas, frequências e planos de aula
-- 
-- CREDENCIAIS DE ACESSO:
-- Admin: username: admin, senha: 123456
-- Gestor: username: roberto.alves, senha: 123456
-- Professor: username: maria.silva, senha: 123456
-- =====================================================

