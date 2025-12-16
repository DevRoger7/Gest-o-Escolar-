-- =====================================================
-- SCRIPT PARA POPULAR BANCO DE DADOS COM DADOS DE TESTE
-- Sistema de Gestão Escolar
-- Baseado na estrutura real do banco escola_merenda
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
-- 1. INSERIR ESCOLAS
-- =====================================================
INSERT IGNORE INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `nivel_ensino`, `obs`, `ativo`, `criado_em`) VALUES
(100, 'ESC001', 'Escola Municipal João Silva', 'Rua das Flores', '100', 'Próximo ao mercado', 'Centro', 'Maranguape', 'CE', '61940000', '(85) 3341-1234', NULL, 'joaosilva@edu.maranguape.ce.gov.br', NULL, '12.345.678/0001-90', NULL, 12, 'ENSINO_FUNDAMENTAL', 'Escola de Ensino Fundamental', 1, NOW()),
(101, 'ESC002', 'Escola Municipal Maria José', 'Av. Principal', '250', NULL, 'São João', 'Maranguape', 'CE', '61940001', '(85) 3341-2345', NULL, 'mariajose@edu.maranguape.ce.gov.br', NULL, '12.345.679/0001-91', NULL, 15, 'ENSINO_FUNDAMENTAL', 'Escola de Ensino Fundamental', 1, NOW()),
(102, 'ESC003', 'Escola Municipal Pedro Alves', 'Rua do Comércio', '500', 'Bloco A', 'Centro', 'Maranguape', 'CE', '61940002', '(85) 3341-3456', NULL, 'pedroalves@edu.maranguape.ce.gov.br', NULL, '12.345.680/0001-92', NULL, 20, 'ENSINO_FUNDAMENTAL,ENSINO_MEDIO', 'Escola de Ensino Fundamental e Médio', 1, NOW());

-- =====================================================
-- 2. INSERIR SÉRIES
-- =====================================================
INSERT IGNORE INTO `serie` (`id`, `nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`) VALUES
(100, '1º Ano', '1ANO', 'ENSINO_FUNDAMENTAL', 1, 6, 7, 'Primeiro ano do Ensino Fundamental', 1, NOW()),
(101, '2º Ano', '2ANO', 'ENSINO_FUNDAMENTAL', 2, 7, 8, 'Segundo ano do Ensino Fundamental', 1, NOW()),
(102, '3º Ano', '3ANO', 'ENSINO_FUNDAMENTAL', 3, 8, 9, 'Terceiro ano do Ensino Fundamental', 1, NOW()),
(103, '4º Ano', '4ANO', 'ENSINO_FUNDAMENTAL', 4, 9, 10, 'Quarto ano do Ensino Fundamental', 1, NOW()),
(104, '5º Ano', '5ANO', 'ENSINO_FUNDAMENTAL', 5, 10, 11, 'Quinto ano do Ensino Fundamental', 1, NOW()),
(105, '6º Ano', '6ANO', 'ENSINO_FUNDAMENTAL', 6, 11, 12, 'Sexto ano do Ensino Fundamental', 1, NOW()),
(106, '7º Ano', '7ANO', 'ENSINO_FUNDAMENTAL', 7, 12, 13, 'Sétimo ano do Ensino Fundamental', 1, NOW()),
(107, '8º Ano', '8ANO', 'ENSINO_FUNDAMENTAL', 8, 13, 14, 'Oitavo ano do Ensino Fundamental', 1, NOW()),
(108, '9º Ano', '9ANO', 'ENSINO_FUNDAMENTAL', 9, 14, 15, 'Nono ano do Ensino Fundamental', 1, NOW()),
(109, '1º Ano', '1MEDIO', 'ENSINO_MEDIO', 10, 15, 16, 'Primeiro ano do Ensino Médio', 1, NOW()),
(110, '2º Ano', '2MEDIO', 'ENSINO_MEDIO', 11, 16, 17, 'Segundo ano do Ensino Médio', 1, NOW()),
(111, '3º Ano', '3MEDIO', 'ENSINO_MEDIO', 12, 17, 18, 'Terceiro ano do Ensino Médio', 1, NOW());

-- =====================================================
-- 3. INSERIR DISCIPLINAS
-- =====================================================
INSERT IGNORE INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`) VALUES
(100, 'PORT', 'Língua Portuguesa', 'Disciplina de Língua Portuguesa', 160, 'Linguagens', 1, NOW()),
(101, 'MAT', 'Matemática', 'Disciplina de Matemática', 160, 'Matemática', 1, NOW()),
(102, 'HIST', 'História', 'Disciplina de História', 80, 'Ciências Humanas', 1, NOW()),
(103, 'GEO', 'Geografia', 'Disciplina de Geografia', 80, 'Ciências Humanas', 1, NOW()),
(104, 'CIEN', 'Ciências', 'Disciplina de Ciências', 80, 'Ciências da Natureza', 1, NOW()),
(105, 'EDF', 'Educação Física', 'Disciplina de Educação Física', 80, 'Educação Física', 1, NOW()),
(106, 'ART', 'Artes', 'Disciplina de Artes', 40, 'Linguagens', 1, NOW()),
(107, 'ING', 'Língua Inglesa', 'Disciplina de Língua Inglesa', 40, 'Linguagens', 1, NOW());

-- =====================================================
-- 4. INSERIR TURMAS
-- =====================================================
INSERT IGNORE INTO `turma` (`id`, `escola_id`, `serie_id`, `ano_letivo`, `serie`, `letra`, `turno`, `capacidade`, `sala`, `coordenador_id`, `observacoes`, `ativo`, `criado_em`) VALUES
(100, 100, 100, 2025, '1º Ano', 'A', 'MANHA', 25, '01', NULL, NULL, 1, NOW()),
(101, 100, 100, 2025, '1º Ano', 'B', 'TARDE', 25, '02', NULL, NULL, 1, NOW()),
(102, 100, 101, 2025, '2º Ano', 'A', 'MANHA', 25, '03', NULL, NULL, 1, NOW()),
(103, 100, 102, 2025, '3º Ano', 'A', 'MANHA', 25, '04', NULL, NULL, 1, NOW()),
(104, 101, 100, 2025, '1º Ano', 'A', 'MANHA', 25, '01', NULL, NULL, 1, NOW()),
(105, 101, 101, 2025, '2º Ano', 'A', 'MANHA', 25, '02', NULL, NULL, 1, NOW()),
(106, 102, 105, 2025, '6º Ano', 'A', 'MANHA', 30, '05', NULL, NULL, 1, NOW()),
(107, 102, 106, 2025, '7º Ano', 'A', 'MANHA', 30, '06', NULL, NULL, 1, NOW()),
(108, 102, 109, 2025, '1º Ano', 'A', 'MANHA', 35, '10', NULL, NULL, 1, NOW());

-- =====================================================
-- 5. INSERIR PESSOAS (usando IDs altos para evitar conflitos)
-- =====================================================

-- Pessoas - Alunos
INSERT IGNORE INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `ativo`, `nome_social`, `raca`) VALUES
(1000, '11111111111', 'Ana Silva Santos', '2018-03-15', 'F', NULL, '(85) 98888-1111', NULL, NULL, 'Rua A', '10', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1001, '22222222222', 'Bruno Oliveira Costa', '2018-05-20', 'M', NULL, '(85) 98888-2222', NULL, NULL, 'Rua B', '20', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1002, '33333333333', 'Carlos Pereira Lima', '2018-07-10', 'M', NULL, '(85) 98888-3333', NULL, NULL, 'Rua C', '30', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1003, '44444444444', 'Daniela Souza Alves', '2017-09-25', 'F', NULL, '(85) 98888-4444', NULL, NULL, 'Rua D', '40', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1004, '55555555555', 'Eduardo Martins Ferreira', '2017-11-30', 'M', NULL, '(85) 98888-5555', NULL, NULL, 'Rua E', '50', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1005, '66666666666', 'Fernanda Rodrigues Gomes', '2016-01-15', 'F', NULL, '(85) 98888-6666', NULL, NULL, 'Rua F', '60', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1006, '77777777777', 'Gabriel Nunes Barbosa', '2016-03-20', 'M', NULL, '(85) 98888-7777', NULL, NULL, 'Rua G', '70', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1007, '88888888888', 'Helena Castro Rocha', '2015-05-10', 'F', NULL, '(85) 98888-8888', NULL, NULL, 'Rua H', '80', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1008, '99999999999', 'Igor Mendes Dias', '2012-07-05', 'M', NULL, '(85) 98888-9999', NULL, NULL, 'Rua I', '90', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1009, '10101010101', 'Julia Araújo Teixeira', '2012-09-12', 'F', NULL, '(85) 98888-1010', NULL, NULL, 'Rua J', '100', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1010, '20202020202', 'Lucas Santos Oliveira', '2011-04-18', 'M', NULL, '(85) 98888-2020', NULL, NULL, 'Rua K', '110', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1011, '30303030303', 'Mariana Costa Silva', '2011-06-22', 'F', NULL, '(85) 98888-3030', NULL, NULL, 'Rua L', '120', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1012, '40404040404', 'Pedro Henrique Lima', '2010-08-30', 'M', NULL, '(85) 98888-4040', NULL, NULL, 'Rua M', '130', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL),
(1013, '50505050505', 'Rafaela Alves Pereira', '2010-10-14', 'F', NULL, '(85) 98888-5050', NULL, NULL, 'Rua N', '140', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'ALUNO', NULL, NULL, NOW(), 1, NULL, NULL);

-- Pessoas - Professores
INSERT IGNORE INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `ativo`, `nome_social`, `raca`) VALUES
(2000, '11111111112', 'Maria da Silva', '1985-01-10', 'F', 'maria.silva@edu.maranguape.ce.gov.br', '(85) 98765-4321', NULL, NULL, 'Av. Professores', '100', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', NULL, NULL, NOW(), 1, NULL, NULL),
(2001, '22222222223', 'João Santos', '1980-03-15', 'M', 'joao.santos@edu.maranguape.ce.gov.br', '(85) 98765-4322', NULL, NULL, 'Av. Professores', '200', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', NULL, NULL, NOW(), 1, NULL, NULL),
(2002, '33333333334', 'Ana Costa', '1988-05-20', 'F', 'ana.costa@edu.maranguape.ce.gov.br', '(85) 98765-4323', NULL, NULL, 'Av. Professores', '300', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'PROFESSOR', NULL, NULL, NOW(), 1, NULL, NULL),
(2003, '44444444445', 'Pedro Oliveira', '1982-07-25', 'M', 'pedro.oliveira@edu.maranguape.ce.gov.br', '(85) 98765-4324', NULL, NULL, 'Av. Professores', '400', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', NULL, NULL, NOW(), 1, NULL, NULL),
(2004, '55555555556', 'Carla Mendes', '1990-09-30', 'F', 'carla.mendes@edu.maranguape.ce.gov.br', '(85) 98765-4325', NULL, NULL, 'Av. Professores', '500', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'PROFESSOR', NULL, NULL, NOW(), 1, NULL, NULL),
(2005, '66666666667', 'Roberto Almeida', '1983-11-12', 'M', 'roberto.almeida@edu.maranguape.ce.gov.br', '(85) 98765-4326', NULL, NULL, 'Av. Professores', '600', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', NULL, NULL, NOW(), 1, NULL, NULL),
(2006, '77777777778', 'Patricia Lima', '1987-02-18', 'F', 'patricia.lima@edu.maranguape.ce.gov.br', '(85) 98765-4327', NULL, NULL, 'Av. Professores', '700', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'PROFESSOR', NULL, NULL, NOW(), 1, NULL, NULL);

-- Pessoas - Gestores
INSERT IGNORE INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `ativo`, `nome_social`, `raca`) VALUES
(3000, '88888888889', 'Roberto Alves', '1975-11-10', 'M', 'roberto.alves@edu.maranguape.ce.gov.br', '(85) 98765-4328', NULL, NULL, 'Av. Gestores', '100', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'GESTOR', NULL, NULL, NOW(), 1, NULL, NULL),
(3001, '99999999990', 'Patricia Lima', '1978-12-15', 'F', 'patricia.lima@edu.maranguape.ce.gov.br', '(85) 98765-4329', NULL, NULL, 'Av. Gestores', '200', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'GESTOR', NULL, NULL, NOW(), 1, NULL, NULL),
(3002, '10101010102', 'Carlos Eduardo', '1976-08-20', 'M', 'carlos.eduardo@edu.maranguape.ce.gov.br', '(85) 98765-4330', NULL, NULL, 'Av. Gestores', '300', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'GESTOR', NULL, NULL, NOW(), 1, NULL, NULL);

-- Pessoas - Funcionários
INSERT IGNORE INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `ativo`, `nome_social`, `raca`) VALUES
(4000, '20202020203', 'Francisco Souza', '1985-01-20', 'M', 'francisco.souza@edu.maranguape.ce.gov.br', '(85) 98765-4331', NULL, NULL, 'Av. Funcionários', '100', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'FUNCIONARIO', NULL, NULL, NOW(), 1, NULL, NULL),
(4001, '30303030304', 'Lucia Ferreira', '1990-03-25', 'F', 'lucia.ferreira@edu.maranguape.ce.gov.br', '(85) 98765-4332', NULL, NULL, 'Av. Funcionários', '200', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'FUNCIONARIO', NULL, NULL, NOW(), 1, NULL, NULL),
(4002, '40404040405', 'Antonio Silva', '1988-06-10', 'M', 'antonio.silva@edu.maranguape.ce.gov.br', '(85) 98765-4333', NULL, NULL, 'Av. Funcionários', '300', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'FUNCIONARIO', NULL, NULL, NOW(), 1, NULL, NULL);

-- Pessoas - Responsáveis
INSERT IGNORE INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `ativo`, `nome_social`, `raca`) VALUES
(5000, '50505050506', 'Paulo Silva', '1980-05-10', 'M', 'paulo.silva@email.com', '(85) 98888-2001', NULL, NULL, 'Rua A', '10', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL),
(5001, '60606060607', 'Marcia Oliveira', '1982-07-15', 'F', 'marcia.oliveira@email.com', '(85) 98888-2002', NULL, NULL, 'Rua B', '20', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL),
(5002, '70707070708', 'Jose Santos', '1978-09-20', 'M', 'jose.santos@email.com', '(85) 98888-2003', NULL, NULL, 'Rua C', '30', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL),
(5003, '80808080809', 'Maria Costa', '1985-11-25', 'F', 'maria.costa@email.com', '(85) 98888-2004', NULL, NULL, 'Rua D', '40', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL),
(5004, '90909090910', 'Carlos Pereira', '1979-01-30', 'M', 'carlos.pereira@email.com', '(85) 98888-2005', NULL, NULL, 'Rua E', '50', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL),
(5005, '10101010111', 'Ana Lima', '1983-03-05', 'F', 'ana.lima@email.com', '(85) 98888-2006', NULL, NULL, 'Rua F', '60', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL),
(5006, '20202020212', 'Pedro Alves', '1981-05-10', 'M', 'pedro.alves@email.com', '(85) 98888-2007', NULL, NULL, 'Rua G', '70', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL),
(5007, '30303030313', 'Fernanda Rocha', '1984-07-15', 'F', 'fernanda.rocha@email.com', '(85) 98888-2008', NULL, NULL, 'Rua H', '80', NULL, 'São João', 'Maranguape', 'CE', '61940001', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL),
(5008, '40404040414', 'Ricardo Gomes', '1980-09-20', 'M', 'ricardo.gomes@email.com', '(85) 98888-2009', NULL, NULL, 'Rua I', '90', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL),
(5009, '50505050515', 'Juliana Barbosa', '1986-11-25', 'F', 'juliana.barbosa@email.com', '(85) 98888-2010', NULL, NULL, 'Rua J', '100', NULL, 'Centro', 'Maranguape', 'CE', '61940000', 'RESPONSAVEL', NULL, NULL, NOW(), 1, NULL, NULL);

-- =====================================================
-- 6. INSERIR USUÁRIOS (senha padrão: 123456)
-- Hash para senha "123456": $2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC
-- =====================================================
INSERT IGNORE INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `created_at`) VALUES
(100, 3000, 'admin', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'ADM', 1, 1, NOW()),
(101, 3000, 'roberto.alves', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'GESTAO', 1, 1, NOW()),
(102, 3001, 'patricia.lima', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'GESTAO', 1, 1, NOW()),
(103, 3002, 'carlos.eduardo', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'GESTAO', 1, 1, NOW()),
(104, 2000, 'maria.silva', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'PROFESSOR', 1, 1, NOW()),
(105, 2001, 'joao.santos', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'PROFESSOR', 1, 1, NOW()),
(106, 2002, 'ana.costa', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'PROFESSOR', 1, 1, NOW()),
(107, 2003, 'pedro.oliveira', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'PROFESSOR', 1, 1, NOW()),
(108, 2004, 'carla.mendes', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'PROFESSOR', 1, 1, NOW()),
(109, 2005, 'roberto.almeida', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'PROFESSOR', 1, 1, NOW()),
(110, 2006, 'patricia.lima.prof', '$2y$10$MQGJXWYS4ytZ1p2qVxgOdeIWfHsgCgI7Rlf0f.ROK7tBz.ar7NzOC', 'PROFESSOR', 1, 1, NOW());

-- =====================================================
-- 7. INSERIR ALUNOS
-- =====================================================
INSERT IGNORE INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `ativo`, `criado_em`) VALUES
(100, 1000, 'MAT-000100', NULL, 5000, 100, '2025-01-15', 'MATRICULADO', '2018-03-15', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(101, 1001, 'MAT-000101', NULL, 5001, 100, '2025-01-15', 'MATRICULADO', '2018-05-20', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(102, 1002, 'MAT-000102', NULL, 5002, 100, '2025-01-15', 'MATRICULADO', '2018-07-10', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(103, 1003, 'MAT-000103', NULL, 5003, 100, '2025-01-15', 'MATRICULADO', '2017-09-25', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(104, 1004, 'MAT-000104', NULL, 5004, 100, '2025-01-15', 'MATRICULADO', '2017-11-30', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(105, 1005, 'MAT-000105', NULL, 5005, 101, '2025-01-15', 'MATRICULADO', '2016-01-15', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(106, 1006, 'MAT-000106', NULL, 5006, 101, '2025-01-15', 'MATRICULADO', '2016-03-20', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(107, 1007, 'MAT-000107', NULL, 5007, 101, '2025-01-15', 'MATRICULADO', '2015-05-10', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(108, 1008, 'MAT-000108', NULL, 5008, 102, '2025-01-15', 'MATRICULADO', '2012-07-05', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(109, 1009, 'MAT-000109', NULL, 5009, 102, '2025-01-15', 'MATRICULADO', '2012-09-12', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(110, 1010, 'MAT-000110', NULL, 5000, 102, '2025-01-15', 'MATRICULADO', '2011-04-18', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(111, 1011, 'MAT-000111', NULL, 5001, 102, '2025-01-15', 'MATRICULADO', '2011-06-22', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(112, 1012, 'MAT-000112', NULL, 5002, 102, '2025-01-15', 'MATRICULADO', '2010-08-30', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW()),
(113, 1013, 'MAT-000113', NULL, 5003, 102, '2025-01-15', 'MATRICULADO', '2010-10-14', 'Brasileira', 'Maranguape', NULL, NULL, 1, NOW());

-- =====================================================
-- 8. INSERIR PROFESSORES
-- =====================================================
INSERT IGNORE INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `ativo`, `criado_em`, `data_admissao`, `pos`) VALUES
(100, 2000, 'PROF-000100', 'Licenciatura em Letras', NULL, NULL, NULL, 1, NOW(), '2020-01-15', NULL),
(101, 2001, 'PROF-000101', 'Licenciatura em Matemática', NULL, NULL, NULL, 1, NOW(), '2019-03-10', NULL),
(102, 2002, 'PROF-000102', 'Licenciatura em Pedagogia', NULL, NULL, NULL, 1, NOW(), '2021-02-20', NULL),
(103, 2003, 'PROF-000103', 'Licenciatura em Ciências', NULL, NULL, NULL, 1, NOW(), '2018-05-12', NULL),
(104, 2004, 'PROF-000104', 'Licenciatura em História', NULL, NULL, NULL, 1, NOW(), '2020-08-25', NULL),
(105, 2005, 'PROF-000105', 'Licenciatura em Geografia', NULL, NULL, NULL, 1, NOW(), '2019-11-30', NULL),
(106, 2006, 'PROF-000106', 'Licenciatura em Educação Física', NULL, NULL, NULL, 1, NOW(), '2021-01-10', NULL);

-- =====================================================
-- 9. INSERIR GESTORES
-- =====================================================
INSERT IGNORE INTO `gestor` (`id`, `pessoa_id`, `cargo`, `formacao`, `registro_profissional`, `observacoes`, `ativo`, `criado_em`) VALUES
(100, 3000, 'Diretor', 'Licenciatura em Pedagogia', NULL, NULL, 1, NOW()),
(101, 3001, 'Coordenador Pedagógico', 'Licenciatura em Letras', NULL, NULL, 1, NOW()),
(102, 3002, 'Vice-Diretor', 'Licenciatura em História', NULL, NULL, 1, NOW());

-- =====================================================
-- 10. INSERIR FUNCIONÁRIOS
-- =====================================================
INSERT IGNORE INTO `funcionario` (`id`, `pessoa_id`, `matricula`, `cargo`, `setor`, `data_admissao`, `data_demissao`, `salario`, `observacoes`, `ativo`, `criado_em`) VALUES
(100, 4000, 'FUNC-000100', 'Secretário', 'Secretaria', '2018-01-10', NULL, NULL, NULL, 1, NOW()),
(101, 4001, 'FUNC-000101', 'Auxiliar Administrativo', 'Secretaria', '2019-03-15', NULL, NULL, NULL, 1, NOW()),
(102, 4002, 'FUNC-000102', 'Auxiliar de Serviços Gerais', 'Limpeza', '2020-05-20', NULL, NULL, NULL, 1, NOW());

-- =====================================================
-- 11. INSERIR LOTAÇÕES (PROFESSORES E ESCOLAS)
-- =====================================================
INSERT IGNORE INTO `professor_lotacao` (`id`, `professor_id`, `escola_id`, `inicio`, `fim`, `carga_horaria`, `observacao`, `criado_em`) VALUES
(100, 100, 100, '2025-01-01', NULL, 20, 'Professora de Língua Portuguesa', NOW()),
(101, 101, 100, '2025-01-01', NULL, 20, 'Professor de Matemática', NOW()),
(102, 102, 100, '2025-01-01', NULL, 20, 'Professora de Pedagogia', NOW()),
(103, 103, 100, '2025-01-01', NULL, 10, 'Professor de Ciências', NOW()),
(104, 104, 101, '2025-01-01', NULL, 20, 'Professora de História', NOW()),
(105, 105, 101, '2025-01-01', NULL, 20, 'Professor de Geografia', NOW()),
(106, 106, 101, '2025-01-01', NULL, 10, 'Professora de Educação Física', NOW()),
(107, 100, 101, '2025-01-01', NULL, 20, 'Professora de Língua Portuguesa', NOW()),
(108, 101, 102, '2025-01-01', NULL, 30, 'Professor de Matemática', NOW()),
(109, 102, 102, '2025-01-01', NULL, 20, 'Professora de Pedagogia', NOW());

-- =====================================================
-- 12. INSERIR LOTAÇÕES (GESTORES E ESCOLAS)
-- =====================================================
INSERT IGNORE INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`) VALUES
(100, 100, 100, '2025-01-01', NULL, 1, 'Diretor', NULL, NOW()),
(101, 101, 100, '2025-01-01', NULL, 0, 'Coordenador Pedagógico', NULL, NOW()),
(102, 102, 101, '2025-01-01', NULL, 1, 'Vice-Diretor', NULL, NOW()),
(103, 100, 102, '2025-01-01', NULL, 1, 'Diretor', NULL, NOW());

-- =====================================================
-- 13. INSERIR LOTAÇÕES (FUNCIONÁRIOS E ESCOLAS)
-- =====================================================
INSERT IGNORE INTO `funcionario_lotacao` (`id`, `funcionario_id`, `escola_id`, `inicio`, `fim`, `setor`, `observacoes`, `criado_em`) VALUES
(100, 100, 100, '2025-01-01', NULL, 'Secretaria', NULL, NOW()),
(101, 101, 101, '2025-01-01', NULL, 'Secretaria', NULL, NOW()),
(102, 102, 100, '2025-01-01', NULL, 'Limpeza', NULL, NOW()),
(103, 100, 101, '2025-01-01', NULL, 'Secretaria', NULL, NOW());

-- =====================================================
-- 14. INSERIR ALUNOS EM TURMAS
-- =====================================================
INSERT IGNORE INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`) VALUES
(100, 100, 100, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(101, 101, 100, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(102, 102, 100, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(103, 103, 102, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(104, 104, 102, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(105, 105, 104, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(106, 106, 104, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(107, 107, 105, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(108, 108, 106, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(109, 109, 106, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(110, 110, 107, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(111, 111, 107, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(112, 112, 108, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW()),
(113, 113, 108, '2025-01-15', NULL, 'MATRICULADO', NULL, NOW());

-- =====================================================
-- 15. INSERIR RESPONSÁVEIS DOS ALUNOS
-- =====================================================
INSERT IGNORE INTO `aluno_responsavel` (`id`, `aluno_id`, `responsavel_id`, `parentesco`, `principal`, `observacoes`, `ativo`, `criado_em`) VALUES
(100, 100, 5000, 'PAI', 1, NULL, 1, NOW()),
(101, 101, 5001, 'MAE', 1, NULL, 1, NOW()),
(102, 102, 5002, 'PAI', 1, NULL, 1, NOW()),
(103, 103, 5003, 'MAE', 1, NULL, 1, NOW()),
(104, 104, 5004, 'PAI', 1, NULL, 1, NOW()),
(105, 105, 5005, 'MAE', 1, NULL, 1, NOW()),
(106, 106, 5006, 'PAI', 1, NULL, 1, NOW()),
(107, 107, 5007, 'MAE', 1, NULL, 1, NOW()),
(108, 108, 5008, 'PAI', 1, NULL, 1, NOW()),
(109, 109, 5009, 'MAE', 1, NULL, 1, NOW()),
(110, 110, 5000, 'PAI', 1, NULL, 1, NOW()),
(111, 111, 5001, 'MAE', 1, NULL, 1, NOW()),
(112, 112, 5002, 'PAI', 1, NULL, 1, NOW()),
(113, 113, 5003, 'MAE', 1, NULL, 1, NOW());

-- =====================================================
-- 16. INSERIR PROFESSORES EM TURMAS
-- =====================================================
INSERT IGNORE INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`) VALUES
(100, 100, 100, 100, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(101, 100, 101, 101, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(102, 100, 102, 104, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(103, 102, 100, 100, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(104, 102, 101, 101, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(105, 103, 103, 104, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(106, 104, 100, 100, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(107, 104, 101, 101, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(108, 105, 104, 102, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(109, 106, 100, 100, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(110, 106, 101, 101, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(111, 107, 105, 103, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(112, 108, 101, 101, '2025-01-15', NULL, 'REGULAR', NULL, NOW()),
(113, 108, 100, 100, '2025-01-15', NULL, 'REGULAR', NULL, NOW());

-- =====================================================
-- 17. INSERIR NOTAS
-- =====================================================
INSERT IGNORE INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `comentario`, `lancado_por`, `lancado_em`) VALUES
(100, NULL, 100, 100, 100, 8.5, 1, 0, 0, NULL, 104, NOW()),
(101, NULL, 101, 100, 100, 7.0, 1, 0, 0, NULL, 105, NOW()),
(102, NULL, 100, 100, 101, 9.0, 1, 0, 0, NULL, 104, NOW()),
(103, NULL, 101, 100, 101, 8.5, 1, 0, 0, NULL, 105, NOW()),
(104, NULL, 100, 100, 102, 7.5, 1, 0, 0, NULL, 104, NOW()),
(105, NULL, 101, 100, 102, 6.5, 1, 0, 0, NULL, 105, NOW()),
(106, NULL, 100, 102, 103, 8.0, 1, 0, 0, NULL, 104, NOW()),
(107, NULL, 101, 102, 103, 9.5, 1, 0, 0, NULL, 105, NOW()),
(108, NULL, 100, 102, 104, 6.0, 1, 0, 0, NULL, 104, NOW()),
(109, NULL, 101, 102, 104, 7.5, 1, 0, 0, NULL, 105, NOW()),
(110, NULL, 100, 104, 105, 9.5, 1, 0, 0, NULL, 104, NOW()),
(111, NULL, 101, 104, 105, 8.0, 1, 0, 0, NULL, 105, NOW()),
(112, NULL, 100, 104, 106, 7.0, 1, 0, 0, NULL, 104, NOW()),
(113, NULL, 101, 104, 106, 6.5, 1, 0, 0, NULL, 105, NOW()),
(114, NULL, 100, 106, 108, 8.5, 1, 0, 0, NULL, 104, NOW()),
(115, NULL, 101, 106, 108, 9.0, 1, 0, 0, NULL, 105, NOW()),
(116, NULL, 100, 106, 109, 7.5, 1, 0, 0, NULL, 104, NOW()),
(117, NULL, 101, 106, 109, 8.0, 1, 0, 0, NULL, 105, NOW());

-- =====================================================
-- 18. INSERIR FREQUÊNCIAS
-- =====================================================
INSERT IGNORE INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `justificativa_id`, `registrado_por`, `registrado_em`) VALUES
(100, 100, 100, '2025-01-20', 1, NULL, 0, NULL, 104, NOW()),
(101, 100, 100, '2025-01-22', 1, NULL, 0, NULL, 104, NOW()),
(102, 100, 100, '2025-01-24', 0, NULL, 0, NULL, 104, NOW()),
(103, 101, 100, '2025-01-20', 1, NULL, 0, NULL, 104, NOW()),
(104, 101, 100, '2025-01-22', 1, NULL, 0, NULL, 104, NOW()),
(105, 101, 100, '2025-01-24', 1, NULL, 0, NULL, 104, NOW()),
(106, 102, 100, '2025-01-20', 1, NULL, 0, NULL, 104, NOW()),
(107, 102, 100, '2025-01-22', 0, NULL, 0, NULL, 104, NOW()),
(108, 102, 100, '2025-01-24', 1, NULL, 0, NULL, 104, NOW()),
(109, 103, 102, '2025-01-20', 1, NULL, 0, NULL, 104, NOW()),
(110, 103, 102, '2025-01-22', 1, NULL, 0, NULL, 104, NOW()),
(111, 103, 102, '2025-01-24', 1, NULL, 0, NULL, 104, NOW()),
(112, 104, 102, '2025-01-20', 0, NULL, 0, NULL, 104, NOW()),
(113, 104, 102, '2025-01-22', 1, NULL, 0, NULL, 104, NOW()),
(114, 104, 102, '2025-01-24', 1, NULL, 0, NULL, 104, NOW()),
(115, 108, 106, '2025-01-20', 1, NULL, 0, NULL, 104, NOW()),
(116, 108, 106, '2025-01-22', 1, NULL, 0, NULL, 104, NOW()),
(117, 109, 106, '2025-01-20', 1, NULL, 0, NULL, 104, NOW()),
(118, 109, 106, '2025-01-22', 0, NULL, 0, NULL, 104, NOW());

-- =====================================================
-- 19. INSERIR PLANOS DE AULA
-- =====================================================
INSERT IGNORE INTO `plano_aula` (`id`, `turma_id`, `disciplina_id`, `professor_id`, `titulo`, `conteudo`, `objetivos`, `metodologia`, `recursos`, `avaliacao`, `atividades_flexibilizadas`, `data_aula`, `bimestre`, `status`, `criado_por`, `criado_em`, `aprovado_por`, `data_aprovacao`) VALUES
(100, 100, 100, 104, 'Introdução à Leitura', 'Apresentação de textos simples e alfabeto', 'Desenvolver habilidades básicas de leitura', 'Aulas expositivas e atividades práticas', 'Livros, quadro, material didático', 'Avaliação contínua e prova escrita', NULL, '2025-01-20', 1, 'APROVADO', 104, NOW(), 104, NOW()),
(101, 100, 101, 105, 'Números de 1 a 10', 'Reconhecimento e escrita de números', 'Reconhecer e escrever números de 1 a 10', 'Jogos educativos e exercícios', 'Material concreto, cartazes', 'Observação e atividades práticas', NULL, '2025-01-21', 1, 'APROVADO', 105, NOW(), 105, NOW()),
(102, 102, 100, 104, 'Leitura e Interpretação', 'Textos narrativos simples', 'Melhorar compreensão leitora', 'Leitura compartilhada e discussão', 'Livros de histórias', 'Questionário de compreensão', NULL, '2025-01-22', 1, 'APROVADO', 104, NOW(), 104, NOW()),
(103, 103, 104, 107, 'Ciências Naturais', 'Introdução às ciências', 'Despertar interesse pelas ciências', 'Experimentos práticos', 'Material de laboratório', 'Relatório de observação', NULL, '2025-01-23', 1, 'APROVADO', 107, NOW(), 107, NOW()),
(104, 106, 100, 104, 'Literatura Brasileira', 'Obras clássicas da literatura', 'Conhecer obras literárias brasileiras', 'Leitura e análise de textos', 'Livros literários', 'Seminário e prova', NULL, '2025-01-24', 1, 'APROVADO', 104, NOW(), 104, NOW());

-- =====================================================
-- FINALIZAR TRANSAÇÃO
-- =====================================================
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- RESUMO DOS DADOS INSERIDOS
-- =====================================================
-- 3 Escolas (IDs: 100-102)
-- 11 Séries (IDs: 100-111)
-- 8 Disciplinas (IDs: 100-107)
-- 9 Turmas (IDs: 100-108)
-- 32 Pessoas (14 alunos, 7 professores, 3 gestores, 3 funcionários, 10 responsáveis)
-- 11 Usuários (IDs: 100-110)
-- 14 Alunos (IDs: 100-113)
-- 7 Professores (IDs: 100-106)
-- 3 Gestores (IDs: 100-102)
-- 3 Funcionários (IDs: 100-102)
-- 10 Lotações de Professores
-- 4 Lotações de Gestores
-- 4 Lotações de Funcionários
-- 14 Matrículas de Alunos em Turmas
-- 14 Vínculos de Responsáveis com Alunos
-- 14 Vínculos de Professores com Turmas
-- 18 Notas
-- 19 Frequências
-- 5 Planos de Aula
-- 
-- CREDENCIAIS DE ACESSO (LOGIN COM CPF E SENHA):
-- Admin: CPF: 88888888889, senha: 123456
-- Gestor: CPF: 88888888889 (Roberto Alves), senha: 123456
-- Gestor: CPF: 99999999990 (Patricia Lima), senha: 123456
-- Gestor: CPF: 10101010102 (Carlos Eduardo), senha: 123456
-- Professor: CPF: 11111111112 (Maria da Silva), senha: 123456
-- Professor: CPF: 22222222223 (João Santos), senha: 123456
-- Professor: CPF: 33333333334 (Ana Costa), senha: 123456
-- Professor: CPF: 44444444445 (Pedro Oliveira), senha: 123456
-- Professor: CPF: 55555555556 (Carla Mendes), senha: 123456
-- Professor: CPF: 66666666667 (Roberto Almeida), senha: 123456
-- Professor: CPF: 77777777778 (Patricia Lima), senha: 123456
-- 
-- NOTA: O sistema permite login com CPF ou email. Para alunos, apenas CPF é permitido.
-- =====================================================
