-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS escola_merenda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE escola_merenda;

-- Tabela de pessoas (dados pessoais)
CREATE TABLE IF NOT EXISTS pessoas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(11) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    endereco TEXT,
    data_nascimento DATE,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de usuários (dados de acesso)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pessoa_id INT NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'funcionario', 'nutricionista') NOT NULL DEFAULT 'funcionario',
    ultimo_login TIMESTAMP NULL,
    bloqueado BOOLEAN DEFAULT FALSE,
    tentativas_login INT DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id) ON DELETE CASCADE
);

-- Tabela de escolas
CREATE TABLE IF NOT EXISTS escolas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    endereco TEXT,
    telefone VARCHAR(20),
    email VARCHAR(255),
    diretor VARCHAR(255),
    total_alunos INT DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de alimentos
CREATE TABLE IF NOT EXISTS alimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    unidade_medida ENUM('kg', 'g', 'l', 'ml', 'unidade', 'pacote', 'caixa') NOT NULL,
    valor_nutricional TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de estoque
CREATE TABLE IF NOT EXISTS estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    escola_id INT NOT NULL,
    alimento_id INT NOT NULL,
    quantidade DECIMAL(10,3) NOT NULL DEFAULT 0,
    quantidade_minima DECIMAL(10,3) DEFAULT 0,
    data_validade DATE,
    lote VARCHAR(100),
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE CASCADE,
    FOREIGN KEY (alimento_id) REFERENCES alimentos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_estoque (escola_id, alimento_id, lote)
);

-- Inserir usuário administrador padrão
INSERT INTO pessoas (nome, cpf, email, telefone) VALUES 
('Administrador do Sistema', '00000000000', 'admin@escola.com', '(85) 99999-9999')
ON DUPLICATE KEY UPDATE nome = nome;

INSERT INTO usuarios (pessoa_id, senha, tipo) VALUES 
((SELECT id FROM pessoas WHERE cpf = '00000000000'), '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE senha = senha;

-- Inserir algumas escolas de exemplo
INSERT INTO escolas (nome, codigo, endereco, telefone, diretor, total_alunos) VALUES 
('Escola Municipal João Silva', 'EM001', 'Rua das Flores, 123 - Centro', '(85) 3333-1111', 'Maria Santos', 250),
('Escola Municipal Ana Costa', 'EM002', 'Av. Principal, 456 - Bairro Novo', '(85) 3333-2222', 'João Oliveira', 180),
('Escola Municipal Pedro Lima', 'EM003', 'Rua da Escola, 789 - Vila Verde', '(85) 3333-3333', 'Ana Silva', 320)
ON DUPLICATE KEY UPDATE nome = nome;

-- Inserir alguns alimentos de exemplo
INSERT INTO alimentos (nome, categoria, unidade_medida, valor_nutricional) VALUES 
('Arroz Branco', 'Cereais', 'kg', 'Carboidratos: 78g, Proteínas: 7g, Gorduras: 0.7g por 100g'),
('Feijão Carioca', 'Leguminosas', 'kg', 'Proteínas: 21g, Carboidratos: 62g, Fibras: 24g por 100g'),
('Óleo de Soja', 'Óleos', 'l', 'Gorduras: 100g, Calorias: 884 por 100ml'),
('Sal Refinado', 'Temperos', 'kg', 'Sódio: 39g por 100g'),
('Açúcar Cristal', 'Açúcares', 'kg', 'Carboidratos: 99.8g, Calorias: 387 por 100g'),
('Macarrão Espaguete', 'Massas', 'pacote', 'Carboidratos: 75g, Proteínas: 13g por 100g'),
('Carne Bovina', 'Carnes', 'kg', 'Proteínas: 26g, Gorduras: 17g por 100g'),
('Frango', 'Carnes', 'kg', 'Proteínas: 31g, Gorduras: 3.6g por 100g'),
('Leite Integral', 'Laticínios', 'l', 'Proteínas: 3.2g, Carboidratos: 4.8g, Cálcio: 113mg por 100ml'),
('Ovos', 'Proteínas', 'unidade', 'Proteínas: 13g, Gorduras: 11g, Vitaminas A, D, E por unidade')
ON DUPLICATE KEY UPDATE nome = nome;