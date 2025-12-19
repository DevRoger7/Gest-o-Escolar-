-- =====================================================
-- SCRIPT PARA POPULAR BANCO DE DADOS COM DADOS DE TESTE
-- Sistema de Gestão Escolar
-- =====================================================
-- 
-- ATENÇÃO: Este script irá inserir dados de teste no banco.
-- Execute apenas em ambiente de desenvolvimento/teste.
-- 
-- Para executar:
-- 1. Faça backup do banco de dados atual
-- 2. Execute este script no phpMyAdmin ou via linha de comando
-- 3. Senha padrão para todos os usuários: 123456
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- =====================================================
-- 1. LIMPAR DADOS EXISTENTES (OPCIONAL - DESCOMENTE SE NECESSÁRIO)
-- =====================================================
-- DELETE FROM nota;
-- DELETE FROM frequencia;
-- DELETE FROM plano_aula;
-- DELETE FROM aluno_turma;
-- DELETE FROM turma_professor;
-- DELETE FROM aluno;
-- DELETE FROM professor;
-- DELETE FROM gestor;
-- DELETE FROM funcionario;
-- DELETE FROM usuario;
-- DELETE FROM pessoa;
-- DELETE FROM turma;
-- DELETE FROM serie;
-- DELETE FROM disciplina;
-- DELETE FROM escola;

-- =====================================================
-- 2. INSERIR ESCOLAS
-- =====================================================
INSERT INTO `escola` (`id`, `nome`, `codigo_inep`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `telefone`, `email`, `diretor`, `tipo`, `ativo`, `criado_em`) VALUES
(1, 'Escola Municipal João Silva', '12345678', 'Rua das Flores', '100', 'Próximo ao mercado', 'Centro', 'Maranguape', 'CE', '61940000', '(85) 3341-1234', 'joaosilva@edu.maranguape.ce.gov.br', 'Maria Santos', 'MUNICIPAL', 1, NOW()),
(2, 'Escola Municipal Maria José', '12345679', 'Av. Principal', '250', NULL, 'São João', 'Maranguape', 'CE', '61940001', '(85) 3341-2345', 'mariajose@edu.maranguape.ce.gov.br', 'João Oliveira', 'MUNICIPAL', 1, NOW()),
(3, 'Escola Municipal Pedro Alves', '12345680', 'Rua do Comércio', '500', 'Bloco A', 'Centro', 'Maranguape', 'CE', '61940002', '(85) 3341-3456', 'pedroalves@edu.maranguape.ce.gov.br', 'Ana Costa', 'MUNICIPAL', 1, NOW());

-- =====================================================
-- 3. INSERIR SÉRIES
-- =====================================================
INSERT INTO `serie` (`id`, `nome`, `nivel_ensino`, `ordem`, `ativo`, `criado_em`) VALUES
(1, '1º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 1, 1, NOW()),
(2, '2º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 2, 1, NOW()),
(3, '3º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 3, 1, NOW()),
(4, '4º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 4, 1, NOW()),
(5, '5º Ano', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 5, 1, NOW()),
(6, '6º Ano', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 6, 1, NOW()),
(7, '7º Ano', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 7, 1, NOW()),
(8, '8º Ano', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 8, 1, NOW()),
(9, '9º Ano', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 9, 1, NOW());

-- =====================================================
-- 4. INSERIR DISCIPLINAS
-- =====================================================
INSERT INTO `disciplina` (`id`, `nome`, `codigo_bncc`, `nivel_ensino`, `carga_horaria`, `ativo`, `criado_em`) VALUES
(1, 'Língua Portuguesa', 'LP', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 160, 1, NOW()),
(2, 'Matemática', 'MA', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 160, 1, NOW()),
(3, 'Ciências', 'CI', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 80, 1, NOW()),
(4, 'História', 'HI', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 80, 1, NOW()),
(5, 'Geografia', 'GE', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 80, 1, NOW()),
(6, 'Artes', 'AR', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 40, 1, NOW()),
(7, 'Educação Física', 'EF', 'ENSINO_FUNDAMENTAL_ANOS_INICIAIS', 40, 1, NOW()),
(8, 'Língua Portuguesa', 'LP', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 200, 1, NOW()),
(9, 'Matemática', 'MA', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 200, 1, NOW()),
(10, 'Ciências', 'CI', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 120, 1, NOW()),
(11, 'História', 'HI', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 120, 1, NOW()),
(12, 'Geografia', 'GE', 'ENSINO_FUNDAMENTAL_ANOS_FINAIS', 120, 1, NOW());

-- =====================================================
-- 5. INSERIR TURMAS
-- =====================================================
INSERT INTO `turma` (`id`, `escola_id`, `serie_id`, `nome`, `letra`, `turno`, `ano_letivo`, `capacidade_maxima`, `ativo`, `criado_em`) VALUES
(1, 1, 1, '1º Ano A', 'A', 'MATUTINO', 2025, 25, 1, NOW()),
(2, 1, 1, '1º Ano B', 'B', 'VESPERTINO', 2025, 25, 1, NOW()),
(3, 1, 2, '2º Ano A', 'A', 'MATUTINO', 2025, 25, 1, NOW()),
(4, 1, 3, '3º Ano A', 'A', 'MATUTINO', 2025, 25, 1, NOW()),
(5, 2, 1, '1º Ano A', 'A', 'MATUTINO', 2025, 25, 1, NOW()),
(6, 2, 2, '2º Ano A', 'A', 'MATUTINO', 2025, 25, 1, NOW()),
(7, 3, 6, '6º Ano A', 'A', 'MATUTINO', 2025, 30, 1, NOW()),
(8, 3, 7, '7º Ano A', 'A', 'MATUTINO', 2025, 30, 1, NOW());

-- =====================================================
-- 6. INSERIR PESSOAS (ALUNOS, PROFESSORES, GESTORES, FUNCIONÁRIOS)
-- =====================================================

-- Pessoas - Alunos
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `ativo`, `criado_em`) VALUES
(1, '11111111111', 'Ana Silva Santos', '2018-03-15', 'F', NULL, '(85) 98888-1111', 'Rua A', '10', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(2, '22222222222', 'Bruno Oliveira Costa', '2018-05-20', 'M', NULL, '(85) 98888-2222', 'Rua B', '20', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(3, '33333333333', 'Carlos Pereira Lima', '2018-07-10', 'M', NULL, '(85) 98888-3333', 'Rua C', '30', 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', 1, NOW()),
(4, '44444444444', 'Daniela Souza Alves', '2017-09-25', 'F', NULL, '(85) 98888-4444', 'Rua D', '40', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(5, '55555555555', 'Eduardo Martins Ferreira', '2017-11-30', 'M', NULL, '(85) 98888-5555', 'Rua E', '50', 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', 1, NOW()),
(6, '66666666666', 'Fernanda Rodrigues Gomes', '2016-01-15', 'F', NULL, '(85) 98888-6666', 'Rua F', '60', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(7, '77777777777', 'Gabriel Nunes Barbosa', '2016-03-20', 'M', NULL, '(85) 98888-7777', 'Rua G', '70', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(8, '88888888888', 'Helena Castro Rocha', '2015-05-10', 'F', NULL, '(85) 98888-8888', 'Rua H', '80', 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', 1, NOW()),
(9, '99999999999', 'Igor Mendes Dias', '2012-07-05', 'M', NULL, '(85) 98888-9999', 'Rua I', '90', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW()),
(10, '10101010101', 'Julia Araújo Teixeira', '2012-09-12', 'F', NULL, '(85) 98888-1010', 'Rua J', '100', 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', 1, NOW());

-- Pessoas - Professores
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `ativo`, `criado_em`) VALUES
(11, '11111111112', 'Maria da Silva', '1985-01-10', 'F', 'maria.silva@edu.maranguape.ce.gov.br', '(85) 98765-4321', 'Av. Professores', '100', 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', 1, NOW()),
(12, '22222222223', 'João Santos', '1980-03-15', 'M', 'joao.santos@edu.maranguape.ce.gov.br', '(85) 98765-4322', 'Av. Professores', '200', 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', 1, NOW()),
(13, '33333333334', 'Ana Costa', '1988-05-20', 'F', 'ana.costa@edu.maranguape.ce.gov.br', '(85) 98765-4323', 'Av. Professores', '300', 'São João', 'Maranguape', 'CE', '61940001', 'PROFESSOR', 1, NOW()),
(14, '44444444445', 'Pedro Oliveira', '1982-07-25', 'M', 'pedro.oliveira@edu.maranguape.ce.gov.br', '(85) 98765-4324', 'Av. Professores', '400', 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', 1, NOW()),
(15, '55555555556', 'Carla Mendes', '1990-09-30', 'F', 'carla.mendes@edu.maranguape.ce.gov.br', '(85) 98765-4325', 'Av. Professores', '500', 'São João', 'Maranguape', 'CE', '61940001', 'PROFESSOR', 1, NOW());

-- Pessoas - Gestores
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `ativo`, `criado_em`) VALUES
(16, '66666666667', 'Roberto Alves', '1975-11-10', 'M', 'roberto.alves@edu.maranguape.ce.gov.br', '(85) 98765-4326', 'Av. Gestores', '100', 'Centro', 'Maranguape', 'CE', '61940000', 'GESTOR', 1, NOW()),
(17, '77777777778', 'Patricia Lima', '1978-12-15', 'F', 'patricia.lima@edu.maranguape.ce.gov.br', '(85) 98765-4327', 'Av. Gestores', '200', 'Centro', 'Maranguape', 'CE', '61940000', 'GESTOR', 1, NOW());

-- Pessoas - Funcionários
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `ativo`, `criado_em`) VALUES
(18, '88888888889', 'Francisco Souza', '1985-01-20', 'M', 'francisco.souza@edu.maranguape.ce.gov.br', '(85) 98765-4328', 'Av. Funcionários', '100', 'Centro', 'Maranguape', 'CE', '61940000', 'FUNCIONARIO', 1, NOW()),
(19, '99999999990', 'Lucia Ferreira', '1990-03-25', 'F', 'lucia.ferreira@edu.maranguape.ce.gov.br', '(85) 98765-4329', 'Av. Funcionários', '200', 'São João', 'Maranguape', 'CE', '61940001', 'FUNCIONARIO', 1, NOW());

-- Pessoas - Responsáveis
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `ativo`, `criado_em`) VALUES
(20, '10101010102', 'Paulo Silva', '1980-05-10', 'M', 'paulo.silva@email.com', '(85) 98888-2001', 'Rua A', '10', 'Centro', 'Maranguape', 'CE', '61940000', 'RESPONSAVEL', 1, NOW()),
(21, '20202020202', 'Marcia Oliveira', '1982-07-15', 'F', 'marcia.oliveira@email.com', '(85) 98888-2002', 'Rua B', '20', 'Centro', 'Maranguape', 'CE', '61940000', 'RESPONSAVEL', 1, NOW());

-- =====================================================
-- 7. INSERIR USUÁRIOS (senha padrão: 123456 - hash bcrypt)
-- =====================================================
-- Hash para senha "123456": $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `created_at`) VALUES
-- Admin
(1, 16, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADM', 1, 1, NOW()),
-- Gestores
(2, 16, 'roberto.alves', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GESTAO', 1, 1, NOW()),
(3, 17, 'patricia.lima', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GESTAO', 1, 1, NOW()),
-- Professores
(4, 11, 'maria.silva', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW()),
(5, 12, 'joao.santos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW()),
(6, 13, 'ana.costa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW()),
(7, 14, 'pedro.oliveira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW()),
(8, 15, 'carla.mendes', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NOW());

-- =====================================================
-- 8. INSERIR ALUNOS
-- =====================================================
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `ativo`, `criado_em`) VALUES
(1, 1, '2025001', '2025-01-15', 'MATRICULADO', '2018-03-15', 'Brasileira', 1, NOW()),
(2, 2, '2025002', '2025-01-15', 'MATRICULADO', '2018-05-20', 'Brasileira', 1, NOW()),
(3, 3, '2025003', '2025-01-15', 'MATRICULADO', '2018-07-10', 'Brasileira', 1, NOW()),
(4, 4, '2025004', '2025-01-15', 'MATRICULADO', '2017-09-25', 'Brasileira', 1, NOW()),
(5, 5, '2025005', '2025-01-15', 'MATRICULADO', '2017-11-30', 'Brasileira', 1, NOW()),
(6, 6, '2025006', '2025-01-15', 'MATRICULADO', '2016-01-15', 'Brasileira', 1, NOW()),
(7, 7, '2025007', '2025-01-15', 'MATRICULADO', '2016-03-20', 'Brasileira', 1, NOW()),
(8, 8, '2025008', '2025-01-15', 'MATRICULADO', '2015-05-10', 'Brasileira', 1, NOW()),
(9, 9, '2025009', '2025-01-15', 'MATRICULADO', '2012-07-05', 'Brasileira', 1, NOW()),
(10, 10, '2025010', '2025-01-15', 'MATRICULADO', '2012-09-12', 'Brasileira', 1, NOW());

-- =====================================================
-- 9. INSERIR PROFESSORES
-- =====================================================
INSERT INTO `professor` (`id`, `pessoa_id`, `registro`, `formacao`, `ativo`, `criado_em`) VALUES
(1, 11, 'PROF001', 'Licenciatura em Pedagogia', 1, NOW()),
(2, 12, 'PROF002', 'Licenciatura em Matemática', 1, NOW()),
(3, 13, 'PROF003', 'Licenciatura em Letras', 1, NOW()),
(4, 14, 'PROF004', 'Licenciatura em Ciências', 1, NOW()),
(5, 15, 'PROF005', 'Licenciatura em História', 1, NOW());

-- =====================================================
-- 10. INSERIR GESTORES
-- =====================================================
INSERT INTO `gestor` (`id`, `pessoa_id`, `registro`, `cargo`, `ativo`, `criado_em`) VALUES
(1, 16, 'GEST001', 'Diretor', 1, NOW()),
(2, 17, 'GEST002', 'Coordenador Pedagógico', 1, NOW());

-- =====================================================
-- 11. INSERIR FUNCIONÁRIOS
-- =====================================================
INSERT INTO `funcionario` (`id`, `pessoa_id`, `registro`, `cargo`, `setor`, `ativo`, `criado_em`) VALUES
(1, 18, 'FUNC001', 'Secretário', 'Secretaria', 1, NOW()),
(2, 19, 'FUNC002', 'Auxiliar Administrativo', 'Secretaria', 1, NOW());

-- =====================================================
-- 12. INSERIR LOTAÇÕES (PROFESSORES E ESCOLAS)
-- =====================================================
INSERT INTO `professor_lotacao` (`professor_id`, `escola_id`, `inicio`, `fim`, `criado_em`) VALUES
(1, 1, '2025-01-01', NULL, NOW()),
(2, 1, '2025-01-01', NULL, NOW()),
(3, 1, '2025-01-01', NULL, NOW()),
(4, 2, '2025-01-01', NULL, NOW()),
(5, 2, '2025-01-01', NULL, NOW()),
(1, 2, '2025-01-01', NULL, NOW()), -- Professor em múltiplas escolas
(3, 3, '2025-01-01', NULL, NOW());

-- =====================================================
-- 13. INSERIR LOTAÇÕES (GESTORES E ESCOLAS)
-- =====================================================
INSERT INTO `gestor_lotacao` (`gestor_id`, `escola_id`, `inicio`, `fim`, `criado_em`) VALUES
(1, 1, '2025-01-01', NULL, NOW()),
(2, 2, '2025-01-01', NULL, NOW()),
(1, 3, '2025-01-01', NULL, NOW());

-- =====================================================
-- 14. INSERIR LOTAÇÕES (FUNCIONÁRIOS E ESCOLAS)
-- =====================================================
INSERT INTO `funcionario_lotacao` (`funcionario_id`, `escola_id`, `setor`, `inicio`, `fim`, `criado_em`) VALUES
(1, 1, 'Secretaria', '2025-01-01', NULL, NOW()),
(2, 2, 'Secretaria', '2025-01-01', NULL, NOW()),
(1, 2, 'Biblioteca', '2025-01-01', NULL, NOW());

-- =====================================================
-- 15. INSERIR ALUNOS EM TURMAS
-- =====================================================
INSERT INTO `aluno_turma` (`aluno_id`, `turma_id`, `inicio`, `fim`, `criado_em`) VALUES
(1, 1, '2025-01-15', NULL, NOW()),
(2, 1, '2025-01-15', NULL, NOW()),
(3, 1, '2025-01-15', NULL, NOW()),
(4, 3, '2025-01-15', NULL, NOW()),
(5, 3, '2025-01-15', NULL, NOW()),
(6, 4, '2025-01-15', NULL, NOW()),
(7, 4, '2025-01-15', NULL, NOW()),
(8, 4, '2025-01-15', NULL, NOW()),
(9, 7, '2025-01-15', NULL, NOW()),
(10, 7, '2025-01-15', NULL, NOW());

-- =====================================================
-- 16. INSERIR PROFESSORES EM TURMAS (TURMA_PROFESSOR)
-- =====================================================
INSERT INTO `turma_professor` (`turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `criado_em`) VALUES
(1, 1, 1, '2025-01-15', NULL, 'REGULAR', NOW()), -- Maria - Português - 1º Ano A
(1, 2, 2, '2025-01-15', NULL, 'REGULAR', NOW()), -- João - Matemática - 1º Ano A
(3, 1, 1, '2025-01-15', NULL, 'REGULAR', NOW()), -- Maria - Português - 2º Ano A
(3, 2, 2, '2025-01-15', NULL, 'REGULAR', NOW()), -- João - Matemática - 2º Ano A
(4, 3, 1, '2025-01-15', NULL, 'REGULAR', NOW()), -- Ana - Português - 3º Ano A
(4, 4, 3, '2025-01-15', NULL, 'REGULAR', NOW()), -- Pedro - Ciências - 3º Ano A
(7, 3, 8, '2025-01-15', NULL, 'REGULAR', NOW()), -- Ana - Português - 6º Ano A
(7, 2, 9, '2025-01-15', NULL, 'REGULAR', NOW()); -- João - Matemática - 6º Ano A

-- =====================================================
-- 17. INSERIR NOTAS
-- =====================================================
INSERT INTO `nota` (`aluno_id`, `turma_id`, `disciplina_id`, `bimestre`, `nota`, `tipo_avaliacao`, `lancado_em`, `lancado_por`) VALUES
(1, 1, 1, 1, 8.5, 'PROVA', NOW(), 4),
(1, 1, 2, 1, 7.0, 'PROVA', NOW(), 5),
(2, 1, 1, 1, 9.0, 'PROVA', NOW(), 4),
(2, 1, 2, 1, 8.5, 'PROVA', NOW(), 5),
(3, 1, 1, 1, 7.5, 'PROVA', NOW(), 4),
(3, 1, 2, 1, 6.5, 'PROVA', NOW(), 5),
(4, 3, 1, 1, 8.0, 'PROVA', NOW(), 4),
(4, 3, 2, 1, 9.5, 'PROVA', NOW(), 5),
(5, 3, 1, 1, 6.0, 'PROVA', NOW(), 4),
(5, 3, 2, 1, 7.5, 'PROVA', NOW(), 5),
(6, 4, 1, 1, 9.5, 'PROVA', NOW(), 6),
(6, 4, 3, 1, 8.0, 'PROVA', NOW(), 7),
(7, 4, 1, 1, 7.0, 'PROVA', NOW(), 6),
(7, 4, 3, 1, 6.5, 'PROVA', NOW(), 7),
(9, 7, 8, 1, 8.5, 'PROVA', NOW(), 6),
(9, 7, 9, 1, 9.0, 'PROVA', NOW(), 5),
(10, 7, 8, 1, 7.5, 'PROVA', NOW(), 6),
(10, 7, 9, 1, 8.0, 'PROVA', NOW(), 5);

-- =====================================================
-- 18. INSERIR FREQUÊNCIAS
-- =====================================================
INSERT INTO `frequencia` (`aluno_id`, `turma_id`, `disciplina_id`, `data`, `presenca`, `registrado_em`, `registrado_por`) VALUES
(1, 1, 1, '2025-01-20', 1, NOW(), 4),
(1, 1, 1, '2025-01-22', 1, NOW(), 4),
(1, 1, 1, '2025-01-24', 0, NOW(), 4),
(2, 1, 1, '2025-01-20', 1, NOW(), 4),
(2, 1, 1, '2025-01-22', 1, NOW(), 4),
(2, 1, 1, '2025-01-24', 1, NOW(), 4),
(3, 1, 1, '2025-01-20', 1, NOW(), 4),
(3, 1, 1, '2025-01-22', 0, NOW(), 4),
(3, 1, 1, '2025-01-24', 1, NOW(), 4),
(4, 3, 1, '2025-01-20', 1, NOW(), 4),
(4, 3, 1, '2025-01-22', 1, NOW(), 4),
(4, 3, 1, '2025-01-24', 1, NOW(), 4),
(5, 3, 1, '2025-01-20', 0, NOW(), 4),
(5, 3, 1, '2025-01-22', 1, NOW(), 4),
(5, 3, 1, '2025-01-24', 1, NOW(), 4);

-- =====================================================
-- 19. INSERIR PLANOS DE AULA
-- =====================================================
INSERT INTO `plano_aula` (`turma_id`, `disciplina_id`, `professor_id`, `titulo`, `conteudo`, `objetivos`, `metodologia`, `recursos`, `avaliacao`, `data_aula`, `bimestre`, `status`, `criado_por`, `criado_em`, `aprovado_por`, `data_aprovacao`) VALUES
(1, 1, 4, 'Introdução à Leitura', 'Apresentação de textos simples e alfabeto', 'Desenvolver habilidades básicas de leitura', 'Aulas expositivas e atividades práticas', 'Livros, quadro, material didático', 'Avaliação contínua e prova escrita', '2025-01-20', 1, 'APROVADO', 4, NOW(), 4, NOW()),
(1, 2, 5, 'Números de 1 a 10', 'Reconhecimento e escrita de números', 'Reconhecer e escrever números de 1 a 10', 'Jogos educativos e exercícios', 'Material concreto, cartazes', 'Observação e atividades práticas', '2025-01-21', 1, 'APROVADO', 5, NOW(), 5, NOW()),
(3, 1, 4, 'Leitura e Interpretação', 'Textos narrativos simples', 'Melhorar compreensão leitora', 'Leitura compartilhada e discussão', 'Livros de histórias', 'Questionário de compreensão', '2025-01-22', 1, 'APROVADO', 4, NOW(), 4, NOW()),
(4, 1, 6, 'Gramática Básica', 'Substantivos e adjetivos', 'Identificar substantivos e adjetivos', 'Exemplos práticos e exercícios', 'Textos, exercícios', 'Prova escrita', '2025-01-23', 1, 'APROVADO', 6, NOW(), 6, NOW()),
(7, 8, 6, 'Literatura Brasileira', 'Obras clássicas da literatura', 'Conhecer obras literárias brasileiras', 'Leitura e análise de textos', 'Livros literários', 'Seminário e prova', '2025-01-24', 1, 'APROVADO', 6, NOW(), 6, NOW());

-- =====================================================
-- 20. INSERIR HABILIDADES BNCC (EXEMPLOS)
-- =====================================================
-- Verificar se a tabela existe e tem a estrutura correta antes de inserir
INSERT INTO `habilidades_bncc` (`codigo_bncc`, `descricao`, `etapa`, `componente`, `ano_inicio`, `ano_fim`) VALUES
('EF01LP01', 'Reconhecer que textos são lidos da esquerda para a direita e de cima para baixo da página.', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1),
('EF01LP02', 'Ler palavras formadas por sílabas canônicas (consoante-vogal).', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1),
('EF01MA01', 'Utilizar números naturais como indicador de quantidade ou de ordem em diferentes situações cotidianas e reconhecer situações em que os números não indicam quantidade nem ordem.', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1),
('EF01MA02', 'Contar de maneira exata ou aproximada, por estimativa, quantidades de elementos de uma coleção e expressar oralmente ou por escrito a quantidade de elementos contados.', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1),
('EF69LP01', 'Diferenciar liberdade de expressão de discursos de ódio, posicionando-se contrariamente a esse tipo de discurso e vislumbrando possibilidades de denúncia quando for o caso.', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 9),
('EF69MA01', 'Compreender e utilizar a ideia de número racional para resolver problemas matemáticos e problemas de outras áreas do conhecimento.', 'Ensino Fundamental – Anos Finais', 'Matemática', 6, 9)
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

-- =====================================================
-- FINALIZAR TRANSAÇÃO
-- =====================================================
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- RESUMO DOS DADOS INSERIDOS
-- =====================================================
-- 3 Escolas
-- 9 Séries
-- 12 Disciplinas
-- 8 Turmas
-- 21 Pessoas (10 alunos, 5 professores, 2 gestores, 2 funcionários, 2 responsáveis)
-- 8 Usuários (1 admin, 2 gestores, 5 professores)
-- 10 Alunos
-- 5 Professores
-- 2 Gestores
-- 2 Funcionários
-- 7 Lotações de Professores
-- 3 Lotações de Gestores
-- 3 Lotações de Funcionários
-- 10 Matrículas de Alunos em Turmas
-- 8 Vínculos de Professores com Turmas
-- 18 Notas
-- 15 Frequências
-- 5 Planos de Aula
-- 6 Habilidades BNCC
-- 
-- CREDENCIAIS DE ACESSO:
-- Admin: username: admin, senha: 123456
-- Gestor: username: roberto.alves, senha: 123456
-- Professor: username: maria.silva, senha: 123456
-- =====================================================



