-- ============================================================
-- EXEMPLO SIMPLES - Criar Usuário Aluno
-- ============================================================
-- Substitua os valores e execute

-- 1. Criar Pessoa
INSERT INTO `pessoa` (`cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) 
VALUES ('12345678901', 'João Silva', '2010-05-15', 'M', 'joao@email.com', '(85) 99999-9999', 'ALUNO');

-- 2. Criar Aluno (use o ID da pessoa criada acima)
INSERT INTO `aluno` (`pessoa_id`, `matricula`, `data_matricula`, `situacao`, `ativo`) 
VALUES (LAST_INSERT_ID(), '2024001', CURDATE(), 'MATRICULADO', 1);

-- 3. Criar Usuário para Login (use o ID da pessoa criada)
-- Senha padrão: "123456" (hash gerado)
INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
VALUES (
    (SELECT id FROM pessoa WHERE cpf = '12345678901'),
    'joao.silva',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'ALUNO',
    1
);

-- ============================================================
-- IMPORTANTE: 
-- - CPF deve ser único
-- - Matrícula deve ser única  
-- - Username deve ser único
-- - Para gerar hash de senha no PHP: password_hash('senha', PASSWORD_DEFAULT)
-- ============================================================

