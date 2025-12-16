-- =====================================================
-- Script de Alimentação Completa do Banco de Dados
-- Sistema de Gestão Escolar - Maranguape/CE
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. DISTRITO_LOCALIDADE (Base para transporte)
-- =====================================================
INSERT INTO `distrito_localidade` (`id`, `distrito`, `localidade`, `latitude`, `longitude`, `endereco`, `bairro`, `cidade`, `estado`, `cep`, `descricao`, `distancia_centro_km`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 'Amanari', 'Massape', -3.891234, -38.685678, 'Rua Principal', 'Centro', 'Maranguape', 'CE', '61979-000', 'Localidade principal do distrito de Amanari', 15.5, 1, NOW(), NOW(), NULL),
(2, 'Amanari', 'Vassouras', -3.892345, -38.686789, 'Rua das Flores', 'Vassouras', 'Maranguape', 'CE', '61979-100', 'Localidade rural de Amanari', 18.2, 1, NOW(), NOW(), NULL),
(3, 'Amanari', 'Pedra D''água', -3.893456, -38.687890, 'Estrada da Pedra', 'Pedra D''água', 'Maranguape', 'CE', '61979-200', 'Localidade com fonte natural', 20.0, 1, NOW(), NOW(), NULL),
(4, 'Sede', 'Centro', -3.890123, -38.684567, 'Rua da Matriz', 'Centro', 'Maranguape', 'CE', '61940-000', 'Centro da cidade de Maranguape', 0.0, 1, NOW(), NOW(), NULL),
(5, 'Sede', 'Aldeoma', -3.891234, -38.685678, 'Avenida Principal', 'Aldeoma', 'Maranguape', 'CE', '61948-050', 'Bairro residencial', 2.5, 1, NOW(), NOW(), NULL),
(6, 'Itapebussu', 'Itapebussu', -3.894567, -38.688901, 'Rua Central', 'Centro', 'Maranguape', 'CE', '61950-000', 'Distrito de Itapebussu', 12.0, 1, NOW(), NOW(), NULL),
(7, 'Itapebussu', 'Lagoa', -3.895678, -38.689012, 'Estrada da Lagoa', 'Lagoa', 'Maranguape', 'CE', '61950-100', 'Localidade próxima à lagoa', 14.5, 1, NOW(), NOW(), NULL);

-- =====================================================
-- 2. PESSOA (Base para todos os usuários)
-- =====================================================
-- Administradores
INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`, `nome_social`, `raca`) VALUES
(1, '12345678901', 'João Silva', '1980-05-15', 'M', 'joao.silva@sigae.com', '(85) 99999-1111', '(85) 99999-1111', NULL, 'Rua da Matriz', '100', 'Apto 101', 'Centro', 'Maranguape', 'CE', '61940-000', 'GESTOR', NULL, 'Administrador do sistema', NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(2, '12345678902', 'Maria Santos', '1985-08-20', 'F', 'maria.santos@sigae.com', '(85) 99999-2222', '(85) 99999-2222', NULL, 'Avenida Principal', '200', NULL, 'Aldeoma', 'Maranguape', 'CE', '61948-050', 'GESTOR', NULL, 'Gestora de escola', NOW(), NOW(), NULL, 1, NULL, 'PARDA'),

-- Gestores
(3, '12345678903', 'Carlos Oliveira', '1975-03-10', 'M', 'carlos.oliveira@escola.com', '(85) 99999-3333', '(85) 99999-3333', NULL, 'Rua das Flores', '50', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'GESTOR', NULL, 'Diretor da EMEIEF', NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(4, '12345678904', 'Ana Costa', '1982-11-25', 'F', 'ana.costa@escola.com', '(85) 99999-4444', '(85) 99999-4444', NULL, 'Rua Principal', '75', NULL, 'Massape', 'Maranguape', 'CE', '61979-000', 'GESTOR', NULL, 'Diretora da escola de Amanari', NOW(), NOW(), NULL, 1, NULL, 'PRETA'),

-- Professores
(5, '12345678905', 'Pedro Almeida', '1990-01-15', 'M', 'pedro.almeida@escola.com', '(85) 99999-5555', '(85) 99999-5555', NULL, 'Rua da Escola', '10', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'PROFESSOR', NULL, 'Professor de Matemática', NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(6, '12345678906', 'Juliana Ferreira', '1988-06-20', 'F', 'juliana.ferreira@escola.com', '(85) 99999-6666', '(85) 99999-6666', NULL, 'Avenida Central', '25', NULL, 'Aldeoma', 'Maranguape', 'CE', '61948-050', 'PROFESSOR', NULL, 'Professora de Português', NOW(), NOW(), NULL, 1, NULL, 'PARDA'),
(7, '12345678907', 'Roberto Lima', '1987-09-12', 'M', 'roberto.lima@escola.com', '(85) 99999-7777', '(85) 99999-7777', NULL, 'Rua das Palmeiras', '30', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'PROFESSOR', NULL, 'Professor de História', NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(8, '12345678908', 'Fernanda Souza', '1991-04-05', 'F', 'fernanda.souza@escola.com', '(85) 99999-8888', '(85) 99999-8888', NULL, 'Rua do Comércio', '40', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'PROFESSOR', NULL, 'Professora de Ciências', NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(9, '12345678909', 'Marcos Rocha', '1989-07-18', 'M', 'marcos.rocha@escola.com', '(85) 99999-9999', '(85) 99999-9999', NULL, 'Avenida da Paz', '60', NULL, 'Aldeoma', 'Maranguape', 'CE', '61948-050', 'PROFESSOR', NULL, 'Professor de Educação Física', NOW(), NOW(), NULL, 1, NULL, 'PARDA'),

-- Responsáveis
(10, '12345678910', 'Paulo Mendes', '1970-12-30', 'M', 'paulo.mendes@email.com', '(85) 98888-1111', '(85) 98888-1111', NULL, 'Rua da Pedra', '100', NULL, 'Pedra D''água', 'Maranguape', 'CE', '61979-200', 'RESPONSAVEL', NULL, 'Pai de aluno', NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(11, '12345678911', 'Lucia Barbosa', '1975-02-14', 'F', 'lucia.barbosa@email.com', '(85) 98888-2222', '(85) 98888-2222', NULL, 'Rua das Vassouras', '200', NULL, 'Vassouras', 'Maranguape', 'CE', '61979-100', 'RESPONSAVEL', NULL, 'Mãe de aluno', NOW(), NOW(), NULL, 1, NULL, 'PARDA'),
(12, '12345678912', 'Jose Santos', '1972-08-22', 'M', 'jose.santos@email.com', '(85) 98888-3333', '(85) 98888-3333', NULL, 'Rua Principal', '300', NULL, 'Massape', 'Maranguape', 'CE', '61979-000', 'RESPONSAVEL', NULL, 'Pai de aluno', NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(13, '12345678913', 'Rita Oliveira', '1978-05-10', 'F', 'rita.oliveira@email.com', '(85) 98888-4444', '(85) 98888-4444', NULL, 'Estrada da Lagoa', '400', NULL, 'Lagoa', 'Maranguape', 'CE', '61950-100', 'RESPONSAVEL', NULL, 'Mãe de aluno', NOW(), NOW(), NULL, 1, NULL, 'PRETA'),

-- Alunos
(14, '11111111111', 'Lucas Mendes', '2010-03-15', 'M', NULL, NULL, NULL, NULL, 'Rua da Pedra', '100', NULL, 'Pedra D''água', 'Maranguape', 'CE', '61979-200', 'ALUNO', NULL, NULL, NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(15, '11111111112', 'Sofia Barbosa', '2011-07-20', 'F', NULL, NULL, NULL, NULL, 'Rua das Vassouras', '200', NULL, 'Vassouras', 'Maranguape', 'CE', '61979-100', 'ALUNO', NULL, NULL, NOW(), NOW(), NULL, 1, NULL, 'PARDA'),
(16, '11111111113', 'Gabriel Santos', '2010-11-05', 'M', NULL, NULL, NULL, NULL, 'Rua Principal', '300', NULL, 'Massape', 'Maranguape', 'CE', '61979-000', 'ALUNO', NULL, NULL, NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(17, '11111111114', 'Isabella Oliveira', '2012-01-18', 'F', NULL, NULL, NULL, NULL, 'Estrada da Lagoa', '400', NULL, 'Lagoa', 'Maranguape', 'CE', '61950-100', 'ALUNO', NULL, NULL, NOW(), NOW(), NULL, 1, NULL, 'PRETA'),
(18, '11111111115', 'Enzo Silva', '2011-09-25', 'M', NULL, NULL, NULL, NULL, 'Rua Principal', '500', NULL, 'Massape', 'Maranguape', 'CE', '61979-000', 'ALUNO', NULL, NULL, NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(19, '11111111116', 'Maria Eduarda', '2010-12-10', 'F', NULL, NULL, NULL, NULL, 'Rua das Flores', '600', NULL, 'Vassouras', 'Maranguape', 'CE', '61979-100', 'ALUNO', NULL, NULL, NOW(), NOW(), NULL, 1, NULL, 'PARDA'),
(20, '11111111117', 'Rafael Costa', '2012-04-30', 'M', NULL, NULL, NULL, NULL, 'Rua da Pedra', '700', NULL, 'Pedra D''água', 'Maranguape', 'CE', '61979-200', 'ALUNO', NULL, NULL, NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(21, '11111111118', 'Julia Ferreira', '2011-08-15', 'F', NULL, NULL, NULL, NULL, 'Estrada da Lagoa', '800', NULL, 'Lagoa', 'Maranguape', 'CE', '61950-100', 'ALUNO', NULL, NULL, NOW(), NOW(), NULL, 1, NULL, 'PRETA'),

-- Motoristas
(22, '12345678922', 'Antonio Motorista', '1980-06-15', 'M', 'antonio.motorista@transporte.com', '(85) 97777-1111', '(85) 97777-1111', NULL, 'Rua do Transporte', '100', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'OUTRO', NULL, 'Motorista de transporte escolar', NOW(), NOW(), NULL, 1, NULL, 'BRANCA'),
(23, '12345678923', 'Francisco Condutor', '1978-09-20', 'M', 'francisco.condutor@transporte.com', '(85) 97777-2222', '(85) 97777-2222', NULL, 'Avenida dos Motoristas', '200', NULL, 'Aldeoma', 'Maranguape', 'CE', '61948-050', 'OUTRO', NULL, 'Motorista de transporte escolar', NOW(), NOW(), NULL, 1, NULL, 'PARDA'),

-- Nutricionista
(24, '12345678924', 'Patricia Nutricionista', '1985-03-12', 'F', 'patricia.nutricionista@merenda.com', '(85) 96666-1111', '(85) 96666-1111', NULL, 'Rua da Nutrição', '50', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'NUTRICIONISTA', NULL, 'Nutricionista responsável', NOW(), NOW(), NULL, 1, NULL, 'BRANCA');

-- =====================================================
-- 3. USUARIO (Acesso ao sistema)
-- =====================================================
-- Senha padrão para todos: "123456" (hash bcrypt)
INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES
(1, 1, 'joao.silva', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADM', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(2, 2, 'maria.santos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GESTAO', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(3, 3, 'carlos.oliveira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GESTAO', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(4, 4, 'ana.costa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'GESTAO', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(5, 5, 'pedro.almeida', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(6, 6, 'juliana.ferreira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(7, 7, 'roberto.lima', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(8, 8, 'fernanda.souza', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(9, 9, 'marcos.rocha', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(10, 22, 'antonio.motorista', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'TRANSPORTE_ALUNO', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL),
(11, 24, 'patricia.nutricionista', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'NUTRICIONISTA', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, NOW(), NOW(), NULL);

-- =====================================================
-- 4. ESCOLA
-- =====================================================
INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `distrito`, `localidade_escola`, `escola_localidade`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `nivel_ensino`, `obs`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES
(1, 'ESC001', 'EMEIEF Cristovão Colombo', 'Rua Principal', '100', NULL, 'Massape', 'Amanari', 'Massape', 1, 'Maranguape', 'CE', '61979-000', '(85) 3333-1111', '(85) 3333-1112', 'cristovao.colombo@escola.com', 'www.cristovaocolombo.edu.br', '12.345.678/0001-01', NULL, 8, 'ENSINO_FUNDAMENTAL', 'Escola de localidade que atende alunos de outras localidades do distrito de Amanari', 1, NOW(), NOW(), NULL),
(2, 'ESC002', 'Escola Municipal João Silva', 'Rua da Matriz', '200', NULL, 'Centro', 'Sede', 'Centro', 0, 'Maranguape', 'CE', '61940-000', '(85) 3333-2222', NULL, 'joao.silva@escola.com', NULL, '12.345.678/0001-02', NULL, 12, 'ENSINO_FUNDAMENTAL,ENSINO_MEDIO', 'Escola central da cidade', 1, NOW(), NOW(), NULL),
(3, 'ESC003', 'EMEIEF Maria Santos', 'Avenida Principal', '300', NULL, 'Aldeoma', 'Sede', 'Aldeoma', 0, 'Maranguape', 'CE', '61948-050', '(85) 3333-3333', NULL, 'maria.santos@escola.com', NULL, '12.345.678/0001-03', NULL, 10, 'ENSINO_FUNDAMENTAL', 'Escola do bairro Aldeoma', 1, NOW(), NOW(), NULL),
(4, 'ESC004', 'Escola Itapebussu', 'Rua Central', '400', NULL, 'Centro', 'Itapebussu', 'Itapebussu', 0, 'Maranguape', 'CE', '61950-000', '(85) 3333-4444', NULL, 'itapebussu@escola.com', NULL, '12.345.678/0001-04', NULL, 6, 'ENSINO_FUNDAMENTAL', 'Escola do distrito de Itapebussu', 1, NOW(), NOW(), NULL);

-- =====================================================
-- 5. SERIE
-- =====================================================
INSERT INTO `serie` (`id`, `nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, '1º Ano', '1ANO', 'ENSINO_FUNDAMENTAL', 1, 6, 7, 'Primeiro ano do Ensino Fundamental', 1, NOW(), NOW(), NULL),
(2, '2º Ano', '2ANO', 'ENSINO_FUNDAMENTAL', 2, 7, 8, 'Segundo ano do Ensino Fundamental', 1, NOW(), NOW(), NULL),
(3, '3º Ano', '3ANO', 'ENSINO_FUNDAMENTAL', 3, 8, 9, 'Terceiro ano do Ensino Fundamental', 1, NOW(), NOW(), NULL),
(4, '4º Ano', '4ANO', 'ENSINO_FUNDAMENTAL', 4, 9, 10, 'Quarto ano do Ensino Fundamental', 1, NOW(), NOW(), NULL),
(5, '5º Ano', '5ANO', 'ENSINO_FUNDAMENTAL', 5, 10, 11, 'Quinto ano do Ensino Fundamental', 1, NOW(), NOW(), NULL),
(6, '6º Ano', '6ANO', 'ENSINO_FUNDAMENTAL', 6, 11, 12, 'Sexto ano do Ensino Fundamental', 1, NOW(), NOW(), NULL),
(7, '7º Ano', '7ANO', 'ENSINO_FUNDAMENTAL', 7, 12, 13, 'Sétimo ano do Ensino Fundamental', 1, NOW(), NOW(), NULL),
(8, '8º Ano', '8ANO', 'ENSINO_FUNDAMENTAL', 8, 13, 14, 'Oitavo ano do Ensino Fundamental', 1, NOW(), NOW(), NULL),
(9, '9º Ano', '9ANO', 'ENSINO_FUNDAMENTAL', 9, 14, 15, 'Nono ano do Ensino Fundamental', 1, NOW(), NOW(), NULL),
(10, '1º Ano', '1MEDIO', 'ENSINO_MEDIO', 10, 15, 16, 'Primeiro ano do Ensino Médio', 1, NOW(), NOW(), NULL),
(11, '2º Ano', '2MEDIO', 'ENSINO_MEDIO', 11, 16, 17, 'Segundo ano do Ensino Médio', 1, NOW(), NOW(), NULL),
(12, '3º Ano', '3MEDIO', 'ENSINO_MEDIO', 12, 17, 18, 'Terceiro ano do Ensino Médio', 1, NOW(), NOW(), NULL);

-- =====================================================
-- 6. DISCIPLINA
-- =====================================================
INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'MAT', 'Matemática', 'Disciplina de Matemática', 160, 'Ciências Exatas', 1, NOW(), NOW()),
(2, 'POR', 'Língua Portuguesa', 'Disciplina de Língua Portuguesa', 160, 'Linguagens', 1, NOW(), NOW()),
(3, 'HIS', 'História', 'Disciplina de História', 80, 'Ciências Humanas', 1, NOW(), NOW()),
(4, 'GEO', 'Geografia', 'Disciplina de Geografia', 80, 'Ciências Humanas', 1, NOW(), NOW()),
(5, 'CIE', 'Ciências', 'Disciplina de Ciências', 80, 'Ciências da Natureza', 1, NOW(), NOW()),
(6, 'EDF', 'Educação Física', 'Disciplina de Educação Física', 80, 'Linguagens', 1, NOW(), NOW()),
(7, 'ART', 'Artes', 'Disciplina de Artes', 40, 'Linguagens', 1, NOW(), NOW()),
(8, 'ING', 'Inglês', 'Disciplina de Inglês', 40, 'Linguagens', 1, NOW(), NOW());

-- =====================================================
-- 7. GESTOR
-- =====================================================
INSERT INTO `gestor` (`id`, `pessoa_id`, `cargo`, `formacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(1, 3, 'Diretor', 'Pedagogia - Licenciatura', 'REG123456', 'Diretor da EMEIEF Cristovão Colombo', NOW(), NOW(), NULL, 1),
(2, 4, 'Diretora', 'Pedagogia - Licenciatura', 'REG123457', 'Diretora da Escola Municipal João Silva', NOW(), NOW(), NULL, 1);

-- =====================================================
-- 8. GESTOR_LOTACAO
-- =====================================================
INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(1, 1, 1, '2024-01-01', NULL, 1, 'Diretor responsável pela escola', NOW(), NOW(), NULL, 1),
(2, 2, 2, '2024-01-01', NULL, 1, 'Diretora responsável pela escola', NOW(), NOW(), NULL, 1);

-- Atualizar diretor_id nas escolas
UPDATE `escola` SET `diretor_id` = 1 WHERE `id` = 1;
UPDATE `escola` SET `diretor_id` = 2 WHERE `id` = 2;

-- =====================================================
-- 9. PROFESSOR
-- =====================================================
INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `data_admissao`, `ativo`, `pos`) VALUES
(1, 5, 'PROF001', 'Licenciatura em Matemática', 'Especialização em Educação Matemática', 'REG001', 'Professor de Matemática', NOW(), NOW(), NULL, '2020-01-15', 1, NULL),
(2, 6, 'PROF002', 'Licenciatura em Letras', 'Especialização em Literatura', 'REG002', 'Professora de Português', NOW(), NOW(), NULL, '2020-02-01', 1, NULL),
(3, 7, 'PROF003', 'Licenciatura em História', NULL, 'REG003', 'Professor de História', NOW(), NOW(), NULL, '2020-03-10', 1, NULL),
(4, 8, 'PROF004', 'Licenciatura em Ciências Biológicas', NULL, 'REG004', 'Professora de Ciências', NOW(), NOW(), NULL, '2020-04-05', 1, NULL),
(5, 9, 'PROF005', 'Licenciatura em Educação Física', NULL, 'REG005', 'Professor de Educação Física', NOW(), NOW(), NULL, '2020-05-20', 1, NULL);

-- =====================================================
-- 10. PROFESSOR_LOTACAO
-- =====================================================
INSERT INTO `professor_lotacao` (`id`, `professor_id`, `escola_id`, `inicio`, `fim`, `carga_horaria`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(1, 1, 1, '2024-01-01', NULL, 40, 'Lotação na EMEIEF Cristovão Colombo', NOW(), NOW(), NULL, 1),
(2, 2, 1, '2024-01-01', NULL, 40, 'Lotação na EMEIEF Cristovão Colombo', NOW(), NOW(), NULL, 1),
(3, 3, 2, '2024-01-01', NULL, 40, 'Lotação na Escola Municipal João Silva', NOW(), NOW(), NULL, 1),
(4, 4, 2, '2024-01-01', NULL, 40, 'Lotação na Escola Municipal João Silva', NOW(), NOW(), NULL, 1),
(5, 5, 1, '2024-01-01', NULL, 20, 'Lotação parcial na EMEIEF Cristovão Colombo', NOW(), NOW(), NULL, 1);

-- =====================================================
-- 11. TURMA
-- =====================================================
INSERT INTO `turma` (`id`, `escola_id`, `serie_id`, `ano_letivo`, `serie`, `letra`, `turno`, `capacidade`, `sala`, `coordenador_id`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES
(1, 1, 1, 2025, '1', 'A', 'MANHA', 25, 'Sala 01', 1, 'Turma do 1º Ano - Manhã', 1, NOW(), NOW(), NULL),
(2, 1, 2, 2025, '2', 'A', 'MANHA', 25, 'Sala 02', 1, 'Turma do 2º Ano - Manhã', 1, NOW(), NOW(), NULL),
(3, 1, 3, 2025, '3', 'A', 'TARDE', 25, 'Sala 03', 2, 'Turma do 3º Ano - Tarde', 1, NOW(), NOW(), NULL),
(4, 2, 4, 2025, '4', 'A', 'MANHA', 30, 'Sala 01', 3, 'Turma do 4º Ano - Manhã', 1, NOW(), NOW(), NULL),
(5, 2, 5, 2025, '5', 'A', 'TARDE', 30, 'Sala 02', 4, 'Turma do 5º Ano - Tarde', 1, NOW(), NOW(), NULL);

-- =====================================================
-- 12. TURMA_PROFESSOR
-- =====================================================
INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `carga_horaria`, `inicio`, `fim`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(1, 1, 1, 1, 5, '2025-01-01', NULL, 'Professor de Matemática - 1º Ano A', NOW(), NOW(), NULL, 1),
(2, 1, 2, 2, 5, '2025-01-01', NULL, 'Professora de Português - 1º Ano A', NOW(), NOW(), NULL, 1),
(3, 2, 1, 1, 5, '2025-01-01', NULL, 'Professor de Matemática - 2º Ano A', NOW(), NOW(), NULL, 1),
(4, 2, 2, 2, 5, '2025-01-01', NULL, 'Professora de Português - 2º Ano A', NOW(), NOW(), NULL, 1),
(5, 3, 3, 3, 3, '2025-01-01', NULL, 'Professor de História - 3º Ano A', NOW(), NOW(), NULL, 1),
(6, 4, 3, 3, 3, '2025-01-01', NULL, 'Professor de História - 4º Ano A', NOW(), NOW(), NULL, 1),
(7, 5, 4, 5, 3, '2025-01-01', NULL, 'Professora de Ciências - 5º Ano A', NOW(), NOW(), NULL, 1);

-- =====================================================
-- 13. ALUNO
-- =====================================================
INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`, `precisa_transporte`, `distrito_transporte`, `localidade_transporte`) VALUES
(1, 14, 'MAT2025001', '12345678901', 10, 1, '2025-01-15', 'MATRICULADO', '2010-03-15', 'Brasileira', 'Maranguape', NULL, NULL, NOW(), NOW(), NULL, 1, 1, 'Amanari', 'Pedra D''água'),
(2, 15, 'MAT2025002', '12345678902', 11, 1, '2025-01-15', 'MATRICULADO', '2011-07-20', 'Brasileira', 'Maranguape', NULL, NULL, NOW(), NOW(), NULL, 1, 1, 'Amanari', 'Vassouras'),
(3, 16, 'MAT2025003', '12345678903', 12, 1, '2025-01-15', 'MATRICULADO', '2010-11-05', 'Brasileira', 'Maranguape', NULL, NULL, NOW(), NOW(), NULL, 1, 0, NULL, NULL),
(4, 17, 'MAT2025004', '12345678904', 13, 2, '2025-01-15', 'MATRICULADO', '2012-01-18', 'Brasileira', 'Maranguape', NULL, NULL, NOW(), NOW(), NULL, 1, 1, 'Itapebussu', 'Lagoa'),
(5, 18, 'MAT2025005', '12345678905', 12, 1, '2025-01-15', 'MATRICULADO', '2011-09-25', 'Brasileira', 'Maranguape', NULL, NULL, NOW(), NOW(), NULL, 1, 0, NULL, NULL),
(6, 19, 'MAT2025006', '12345678906', 11, 1, '2025-01-15', 'MATRICULADO', '2010-12-10', 'Brasileira', 'Maranguape', NULL, NULL, NOW(), NOW(), NULL, 1, 1, 'Amanari', 'Vassouras'),
(7, 20, 'MAT2025007', '12345678907', 10, 1, '2025-01-15', 'MATRICULADO', '2012-04-30', 'Brasileira', 'Maranguape', NULL, NULL, NOW(), NOW(), NULL, 1, 1, 'Amanari', 'Pedra D''água'),
(8, 21, 'MAT2025008', '12345678908', 13, 2, '2025-01-15', 'MATRICULADO', '2011-08-15', 'Brasileira', 'Maranguape', NULL, NULL, NOW(), NOW(), NULL, 1, 1, 'Itapebussu', 'Lagoa');

-- =====================================================
-- 14. ALUNO_RESPONSAVEL
-- =====================================================
INSERT INTO `aluno_responsavel` (`id`, `aluno_id`, `responsavel_id`, `parentesco`, `principal`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(1, 1, 10, 'PAI', 1, 'Responsável principal', NOW(), NOW(), NULL, 1),
(2, 2, 11, 'MAE', 1, 'Responsável principal', NOW(), NOW(), NULL, 1),
(3, 3, 12, 'PAI', 1, 'Responsável principal', NOW(), NOW(), NULL, 1),
(4, 4, 13, 'MAE', 1, 'Responsável principal', NOW(), NOW(), NULL, 1),
(5, 5, 12, 'PAI', 1, 'Responsável principal', NOW(), NOW(), NULL, 1),
(6, 6, 11, 'MAE', 1, 'Responsável principal', NOW(), NOW(), NULL, 1),
(7, 7, 10, 'PAI', 1, 'Responsável principal', NOW(), NOW(), NULL, 1),
(8, 8, 13, 'MAE', 1, 'Responsável principal', NOW(), NOW(), NULL, 1);

-- =====================================================
-- 15. ALUNO_TURMA
-- =====================================================
INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES
(1, 1, 1, '2025-01-15', NULL, 'MATRICULADO', 'Aluno matriculado no 1º Ano A', NOW(), NOW(), NULL),
(2, 2, 1, '2025-01-15', NULL, 'MATRICULADO', 'Aluna matriculada no 1º Ano A', NOW(), NOW(), NULL),
(3, 3, 2, '2025-01-15', NULL, 'MATRICULADO', 'Aluno matriculado no 2º Ano A', NOW(), NOW(), NULL),
(4, 4, 4, '2025-01-15', NULL, 'MATRICULADO', 'Aluna matriculada no 4º Ano A', NOW(), NOW(), NULL),
(5, 5, 2, '2025-01-15', NULL, 'MATRICULADO', 'Aluno matriculado no 2º Ano A', NOW(), NOW(), NULL),
(6, 6, 3, '2025-01-15', NULL, 'MATRICULADO', 'Aluna matriculada no 3º Ano A', NOW(), NOW(), NULL),
(7, 7, 1, '2025-01-15', NULL, 'MATRICULADO', 'Aluno matriculado no 1º Ano A', NOW(), NOW(), NULL),
(8, 8, 5, '2025-01-15', NULL, 'MATRICULADO', 'Aluna matriculada no 5º Ano A', NOW(), NOW(), NULL);

-- =====================================================
-- 16. MOTORISTA
-- =====================================================
INSERT INTO `motorista` (`id`, `pessoa_id`, `cnh`, `categoria_cnh`, `validade_cnh`, `data_admissao`, `data_demissao`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 22, '12345678901', 'D', '2027-12-31', '2020-01-10', NULL, 'Motorista experiente', 1, NOW(), NOW(), NULL),
(2, 23, '12345678902', 'D', '2028-06-30', '2020-02-15', NULL, 'Motorista de transporte escolar', 1, NOW(), NOW(), NULL);

-- =====================================================
-- 17. VEICULO
-- =====================================================
INSERT INTO `veiculo` (`id`, `placa`, `renavam`, `marca`, `modelo`, `ano`, `cor`, `capacidade_maxima`, `capacidade_minima`, `tipo`, `numero_frota`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 'ABC1D23', '12345678901', 'Mercedes-Benz', 'OF-1712 Escolar', 2018, 'Amarelo', 52, 17, 'ONIBUS', '01', 'Ônibus escolar grande', 1, NOW(), NOW(), NULL),
(2, 'XYZ9W87', '12345678902', 'Volkswagen', 'Sprinter Escolar', 2020, 'Amarelo', 20, 8, 'VAN', '02', 'Van escolar média', 1, NOW(), NOW(), NULL),
(3, 'DEF4G56', '12345678903', 'Mercedes-Benz', 'OF-1722 Escolar', 2019, 'Amarelo', 44, 15, 'ONIBUS', '03', 'Ônibus escolar médio', 1, NOW(), NOW(), NULL);

-- =====================================================
-- 18. ROTA
-- =====================================================
INSERT INTO `rota` (`id`, `nome`, `codigo`, `descricao`, `escola_id`, `veiculo_id`, `motorista_id`, `turno`, `localidades`, `distrito`, `total_alunos`, `distancia_km`, `tempo_estimado_minutos`, `horario_saida`, `horario_chegada`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 'Rota Amanari - Massape', 'ROTA001', 'Rota que atende alunos de Massape para a EMEIEF Cristovão Colombo', 1, 1, 1, 'MANHA', '["Massape"]', 'Amanari', 2, 5.5, 15, '06:30:00', '07:00:00', 'Rota da manhã', 1, NOW(), NOW(), NULL),
(2, 'Rota Amanari - Vassouras e Pedra D''água', 'ROTA002', 'Rota que atende alunos de Vassouras e Pedra D''água', 1, 2, 2, 'MANHA', '["Vassouras","Pedra D''água"]', 'Amanari', 4, 12.0, 30, '06:00:00', '07:00:00', 'Rota da manhã - múltiplas localidades', 1, NOW(), NOW(), NULL),
(3, 'Rota Itapebussu - Lagoa', 'ROTA003', 'Rota que atende alunos de Lagoa para a Escola Municipal João Silva', 2, 3, 1, 'MANHA', '["Lagoa"]', 'Itapebussu', 2, 8.0, 20, '06:45:00', '07:15:00', 'Rota da manhã', 1, NOW(), NOW(), NULL);

-- =====================================================
-- 19. NUTRICIONISTA
-- =====================================================
INSERT INTO `nutricionista` (`id`, `pessoa_id`, `crn`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 24, 'CRN12345', 'Bacharelado em Nutrição', 'Especialização em Nutrição Escolar', 'REG001', 'Nutricionista responsável pela merenda escolar', 1, NOW(), NOW(), NULL);

-- =====================================================
-- 20. NUTRICIONISTA_LOTACAO
-- =====================================================
INSERT INTO `nutricionista_lotacao` (`id`, `nutricionista_id`, `escola_id`, `inicio`, `fim`, `carga_horaria`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(1, 1, 1, '2024-01-01', NULL, 40, 'Nutricionista responsável pela EMEIEF Cristovão Colombo', NOW(), NOW(), NULL, 1),
(2, 1, 2, '2024-01-01', NULL, 20, 'Nutricionista responsável pela Escola Municipal João Silva (parcial)', NOW(), NOW(), NULL, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- FIM DO SCRIPT DE ALIMENTAÇÃO
-- =====================================================
-- Resumo dos dados inseridos:
-- - 7 localidades em 3 distritos
-- - 24 pessoas (2 admins, 2 gestores, 5 professores, 4 responsáveis, 8 alunos, 2 motoristas, 1 nutricionista)
-- - 11 usuários do sistema
-- - 4 escolas
-- - 12 séries
-- - 8 disciplinas
-- - 2 gestores com lotação
-- - 5 professores com lotação
-- - 5 turmas
-- - 7 vínculos turma-professor
-- - 8 alunos matriculados
-- - 8 vínculos aluno-responsável
-- - 8 matrículas em turmas
-- - 2 motoristas
-- - 3 veículos
-- - 3 rotas de transporte
-- - 1 nutricionista com 2 lotações
-- =====================================================

