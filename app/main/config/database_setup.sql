-- Script de Criação do Banco de Dados
-- Sistema de Gestão Escolar - Merenda

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS escola_merenda 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Usar o banco de dados
USE escola_merenda;

-- Tabela de pessoas (dados pessoais básicos)
CREATE TABLE IF NOT EXISTS pessoas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    endereco TEXT,
    data_nascimento DATE,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de usuários (credenciais e permissões)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pessoa_id INT NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'funcionario', 'nutricionista') DEFAULT 'funcionario',
    ultimo_login TIMESTAMP NULL,
    tentativas_login INT DEFAULT 0,
    bloqueado BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id) ON DELETE CASCADE
);

-- Tabela de escolas
CREATE TABLE IF NOT EXISTS escolas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    endereco TEXT,
    telefone VARCHAR(20),
    email VARCHAR(100),
    diretor VARCHAR(100),
    total_alunos INT DEFAULT 0,
    ativa BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de alimentos
CREATE TABLE IF NOT EXISTS alimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria VARCHAR(50),
    unidade_medida VARCHAR(20) NOT NULL,
    valor_nutricional TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de estoque
CREATE TABLE IF NOT EXISTS estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alimento_id INT NOT NULL,
    escola_id INT NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantidade_minima DECIMAL(10,2) DEFAULT 0,
    data_validade DATE,
    lote VARCHAR(50),
    data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (alimento_id) REFERENCES alimentos(id) ON DELETE CASCADE,
    FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE CASCADE
);

-- Tabela de cardápios
CREATE TABLE IF NOT EXISTS cardapios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    escola_id INT NOT NULL,
    data_cardapio DATE NOT NULL,
    refeicao ENUM('cafe_manha', 'almoco', 'lanche_tarde', 'jantar') NOT NULL,
    descricao TEXT,
    observacoes TEXT,
    aprovado BOOLEAN DEFAULT FALSE,
    aprovado_por INT,
    data_aprovacao TIMESTAMP NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE CASCADE,
    FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabela de itens do cardápio
CREATE TABLE IF NOT EXISTS cardapio_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cardapio_id INT NOT NULL,
    alimento_id INT NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    observacoes TEXT,
    FOREIGN KEY (cardapio_id) REFERENCES cardapios(id) ON DELETE CASCADE,
    FOREIGN KEY (alimento_id) REFERENCES alimentos(id) ON DELETE CASCADE
);

-- Tabela de movimentações de estoque
CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estoque_id INT NOT NULL,
    tipo ENUM('entrada', 'saida', 'ajuste') NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    motivo VARCHAR(100),
    observacoes TEXT,
    usuario_id INT NOT NULL,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estoque_id) REFERENCES estoque(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de relatórios de consumo
CREATE TABLE IF NOT EXISTS relatorios_consumo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    escola_id INT NOT NULL,
    data_relatorio DATE NOT NULL,
    total_refeicoes INT DEFAULT 0,
    observacoes TEXT,
    criado_por INT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Inserir pessoa e usuário administrador padrão
INSERT INTO pessoas (nome, cpf, email) VALUES 
('Administrador do Sistema', '000.000.000-00', 'admin@escola.com');

INSERT INTO usuarios (pessoa_id, senha, tipo) VALUES 
(1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Inserir mais exemplos de pessoas e usuários
INSERT INTO pessoas (nome, cpf, email, telefone) VALUES 
('Maria Silva Santos', '111.222.333-44', 'maria@escola.com', '(11) 98765-4321'),
('João Carlos Oliveira', '555.666.777-88', 'joao@escola.com', '(11) 91234-5678');

INSERT INTO usuarios (pessoa_id, senha, tipo) VALUES 
(2, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'funcionario'),
(3, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nutricionista');

-- Inserir algumas categorias de alimentos básicas
INSERT INTO alimentos (nome, categoria, unidade_medida) VALUES 
('Arroz Branco', 'Cereais', 'kg'),
('Feijão Carioca', 'Leguminosas', 'kg'),
('Frango', 'Proteínas', 'kg'),
('Batata', 'Tubérculos', 'kg'),
('Cenoura', 'Vegetais', 'kg'),
('Banana', 'Frutas', 'kg'),
('Leite Integral', 'Laticínios', 'litro'),
('Pão Francês', 'Panificados', 'unidade');

-- Inserir escola exemplo
INSERT INTO escolas (nome, endereco, telefone, total_alunos) VALUES 
('Escola Municipal Exemplo', 'Rua das Flores, 123 - Centro', '(11) 1234-5678', 300);