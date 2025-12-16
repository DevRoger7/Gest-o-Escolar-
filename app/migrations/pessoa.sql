-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/12/2025 às 14:39
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `escola_merenda`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `pessoa`
--

CREATE TABLE `pessoa` (
  `id` bigint(20) NOT NULL,
  `cpf` char(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `sexo` char(1) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `telefone_secundario` varchar(30) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `tipo` enum('ALUNO','PROFESSOR','GESTOR','FUNCIONARIO','RESPONSAVEL','NUTRICIONISTA','OUTRO') DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `nome_social` varchar(255) DEFAULT NULL,
  `raca` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pessoa`
--

INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`, `nome_social`, `raca`) VALUES
(1, '11111111111', 'Roger', NULL, 'M', 'cavalcanterogeer@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1, NULL, NULL),
(2, '11970867302', 'Francisco lavosier Silva Nascimento', '2001-04-20', NULL, 'slavosier298@gmail.com', '(85) 98948-2053', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1, NULL, NULL),
(3, '12345678901', 'Francisco', '1999-04-21', NULL, 'tambaqui123@gmail.com', '(85) 98948-2053', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1, NULL, NULL),
(4, '12321321333', 'yudi', '2000-03-13', NULL, 'assa@gmail.com', '(85) 9999-9922', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GESTOR', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1, NULL, NULL),
(5, '13232332322', 'raimundo nonato', '1997-03-13', NULL, 'raimundo@gmail.com', '(85) 9999-9233', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1, NULL, NULL),
(6, '12312312300', 'cabra mac', '2001-09-10', NULL, 'cabramacho@gmail.com', '(85) 3333-3333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1, NULL, NULL),
(7, '12112112112', 'vascaino', NULL, 'M', 'vascainoprofessor@gmail.com', '85985858585', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:14:02', NULL, 1, NULL, NULL),
(8, '33333333333', 'raparigueiro', NULL, 'M', 'raparigueiro@gmail.com', '85933445566', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:14:02', NULL, 1, NULL, NULL),
(10, '98765432100', 'João Silva', '2010-05-15', 'M', 'joao@email.com', '(85) 99999-9999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-29 23:05:38', '2025-11-29 23:05:38', NULL, 1, NULL, NULL),
(11, '12345678900', 'João Silva (Gestor Teste)', '1980-01-15', 'M', 'gestor.teste@sigae.com', '85999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GESTOR', NULL, NULL, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL, 1, NULL, NULL),
(28, '90000000001', 'Ana Silva Santos', '2017-03-15', 'F', 'ana.silva.teste@sigae.com', '85990000001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(29, '90000000002', 'Bruno Oliveira Costa', '2017-05-20', 'M', 'bruno.oliveira.teste@sigae.com', '85990000002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CE', NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-15 00:05:11', NULL, 1, NULL, NULL),
(30, '90000000003', 'Carla Mendes Lima', '2017-07-10', 'F', 'carla.mendes.teste@sigae.com', '85990000003', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(31, '90000000004', 'Daniel Souza Alves', '2017-09-25', 'M', 'daniel.souza.teste@sigae.com', '85990000004', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(32, '90000000005', 'Eduarda Ferreira Rocha', '2017-11-30', 'F', 'eduarda.ferreira.teste@sigae.com', '85990000005', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(33, '90000000006', 'Felipe Gomes Pereira', '2016-02-14', 'M', 'felipe.gomes.teste@sigae.com', '85990000006', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(34, '90000000007', 'Gabriela Martins Dias', '2016-04-18', 'F', 'gabriela.martins.teste@sigae.com', '85990000007', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(35, '90000000008', 'Henrique Barbosa Ramos', '2016-06-22', 'M', 'henrique.barbosa.teste@sigae.com', '85990000008', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(36, '90000000009', 'Isabela Nunes Cardoso', '2016-08-28', 'F', 'isabela.nunes.teste@sigae.com', '85990000009', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(37, '90000000010', 'João Pedro Teixeira', '2016-10-12', 'M', 'joao.pedro.teste@sigae.com', '85990000010', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(38, '90000000011', 'Larissa Araújo Freitas', '2015-01-08', 'F', 'larissa.araujo.teste@sigae.com', '85990000011', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(39, '90000000012', 'Marcos Vinicius Lopes', '2015-03-16', 'M', 'marcos.vinicius.teste@sigae.com', '85990000012', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(40, '90000000013', 'Natália Correia Monteiro', '2015-05-24', 'F', 'natalia.correia.teste@sigae.com', '85990000013', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(41, '90000000014', 'Otávio Ribeiro Campos', '2015-07-30', 'M', 'otavio.ribeiro.teste@sigae.com', '85990000014', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(42, '90000000015', 'Paula Cristina Moreira', '2015-09-05', 'F', 'paula.cristina.teste@sigae.com', '85990000015', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(43, '80000000001', 'Maria Santos (Professora Português)', '1985-05-10', 'F', 'maria.santos.teste@sigae.com', '85980000001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(44, '80000000002', 'José Carlos (Professor Matemática)', '1982-08-20', 'M', 'jose.carlos.teste@sigae.com', '85980000002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(45, '80000000003', 'Patrícia Lima (Professora História)', '1987-12-05', 'F', 'patricia.lima.teste@sigae.com', '85980000003', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1, NULL, NULL),
(46, '88888888888', 'Maria da Silva - Administradora de Merenda', '1985-05-15', 'F', 'merenda.teste@sigae.com', '(85) 98888-8888', '(85) 98888-8888', NULL, 'Rua das Flores', '123', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'FUNCIONARIO', NULL, NULL, '2025-12-02 00:08:28', '2025-12-02 00:08:28', NULL, 1, NULL, NULL),
(47, '77777777777', 'Ana Paula Costa - Nutricionista', '1990-03-20', 'F', 'nutricionista.teste@sigae.com', '(85) 97777-7777', '(85) 97777-7777', NULL, 'Rua das Nutrições', '456', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', 'NUTRICIONISTA', NULL, 'Nutricionista responsável pelo planejamento nutricional', '2025-12-08 13:12:49', '2025-12-08 13:12:49', NULL, 1, NULL, NULL),
(48, '01491156723', 'Antonio silva', '1998-06-04', 'M', 'null', '85440289222', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RESPONSAVEL', NULL, NULL, '2025-12-09 13:28:36', '2025-12-09 13:38:19', 11, 0, NULL, NULL),
(50, '01491156728', 'Antonio silva', '1998-04-03', 'M', 'resp.teste@sigae.com', '85440289222', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RESPONSAVEL', NULL, NULL, '2025-12-09 13:45:00', '2025-12-10 17:17:57', 11, 1, NULL, NULL),
(51, '34543523322', 'Roger Silva', '2009-05-06', 'M', 'rogersilva@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-10 12:32:01', '2025-12-10 12:32:01', 3, 1, NULL, NULL),
(52, '43243324432', 'Jose Carlos', '2025-12-01', 'M', 'ladalddaw@sigae.com', '85923436453', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CE', NULL, 'PROFESSOR', NULL, NULL, '2025-12-11 12:31:49', '2025-12-16 01:13:46', 11, 1, NULL, NULL),
(53, '12421531131', 'abaf', '2025-11-19', 'M', '234ier298@gmail.com', '(85) 99031-4322', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-12-11 12:40:33', '2025-12-11 12:40:33', 11, 1, NULL, NULL),
(54, '124.412.455', 'ssssdssssss', '2025-06-11', 'M', 'dsds3345r298@gmail.com', '(85) 99343-4356', NULL, NULL, 'aguaadddd', '6556', 'em frente ao fumo4', 'emilio conde', 'fortaleza', 'CE', '', NULL, NULL, NULL, '2025-12-11 12:56:40', '2025-12-11 12:56:40', NULL, 1, 'eeeerrrrreeeeee', 'PRETO'),
(55, '12441245567', 'Pedro Alvares', '2025-06-11', 'M', 'dsds3345r298@gmail.com', '85993434356', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CE', NULL, 'PROFESSOR', NULL, NULL, '2025-12-11 12:56:40', '2025-12-16 01:13:56', 11, 1, NULL, NULL),
(56, '11111111112', 'Administrador de Transporte', '1985-01-15', 'M', 'adm.transporte@sigae.com', '(85) 99999-9999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-12-15 15:06:01', '2025-12-15 15:06:01', NULL, 1, NULL, NULL),
(57, '11111111113', 'Operador de Rotas Escolares', '1990-05-20', 'M', 'transporte.aluno@sigae.com', '(85) 98888-8888', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-12-15 15:06:01', '2025-12-15 15:06:01', NULL, 1, NULL, NULL),
(58, '12111111111', 'chico neto', '2008-04-21', 'M', 'chicobestafera@gmail.com', '85989482053', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CE', NULL, 'ALUNO', NULL, NULL, '2025-12-15 16:16:29', '2025-12-15 16:16:29', 3, 1, 'chico besta fera', 'PARDA'),
(59, '12122121212', 'francisco besta fera', '1998-04-21', 'M', 'franciscobesta@gmail.com', '85984797128', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RESPONSAVEL', NULL, NULL, '2025-12-15 16:16:29', '2025-12-15 16:16:29', 3, 1, NULL, NULL),
(60, '21312312312', 'Roger Silva', '2001-03-12', 'M', 'cavalcanterogeer22@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CE', NULL, 'ALUNO', NULL, NULL, '2025-12-16 04:24:44', '2025-12-16 04:24:44', 3, 1, NULL, 'PARDA'),
(61, '12121212121', 'Ronerio', '1974-03-07', 'M', 'cavalcanterogeer23@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RESPONSAVEL', NULL, NULL, '2025-12-16 04:24:44', '2025-12-16 04:24:44', 3, 1, NULL, NULL),
(62, '13213123123', 'Roger Silva', '2000-12-12', 'M', 'cavalcanterogee222r@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CE', NULL, 'ALUNO', NULL, NULL, '2025-12-16 04:29:30', '2025-12-16 04:29:30', 3, 1, 'Jose', 'PARDA'),
(63, '11231231231', 'Jose Ronerio', '1974-03-07', 'M', 'cavalcanterogee212r@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RESPONSAVEL', NULL, NULL, '2025-12-16 04:29:30', '2025-12-16 04:29:30', 3, 1, NULL, NULL),
(64, '12312312312', 'Roger Silva', '2000-12-12', 'M', 'cavalcanteroge222er@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'CE', NULL, 'ALUNO', NULL, NULL, '2025-12-16 04:37:39', '2025-12-16 04:37:39', 3, 1, 'adwae', 'BRANCA'),
(65, '12312313131', 'Jose Ronerio', '1974-03-07', 'M', 'cavalcanterogeer2313@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RESPONSAVEL', NULL, NULL, '2025-12-16 04:37:39', '2025-12-16 04:37:39', 3, 1, NULL, NULL),
(66, '12312312313', 'Roger Silva', '2025-11-30', 'M', 'cavalcanterogeer321@gmail.com', '85981835778', NULL, NULL, 'Rua José Batista', '422', NULL, 'Aldeoma', 'Maranguape', 'CE', '61948050', 'ALUNO', NULL, NULL, '2025-12-16 04:50:40', '2025-12-16 04:50:40', 3, 1, 'klaudio', 'BRANCA'),
(67, '11111111222', 'Roger Silva', '2025-11-30', 'M', 'awdawd1@gmail.com', '85981835778', NULL, NULL, 'Rua José Batista', '422', NULL, 'Aldeoma', 'Maranguape', 'CE', '61948050', 'ALUNO', NULL, NULL, '2025-12-16 04:51:19', '2025-12-16 04:51:19', 3, 1, 'klaudio', 'BRANCA'),
(68, '00000000011', 'jose', '2025-12-03', 'M', 'dawdawdwad@gmail.com', '85981835778', NULL, NULL, 'rua monique paula', '12', NULL, 'parque sao joao', 'Maranguape', 'CE', NULL, 'RESPONSAVEL', NULL, NULL, '2025-12-16 04:51:19', '2025-12-16 04:51:19', 3, 1, NULL, NULL),
(69, '12190222197', 'Juscelino Kubitschek', '1902-09-12', 'M', 'juscelinojk@gmail.com', NULL, NULL, NULL, 'praça Adelaide coelho', '21', NULL, 'Amanari', 'Maranguape', 'CE', '61979000', 'ALUNO', NULL, NULL, '2025-12-16 13:16:03', '2025-12-16 13:16:03', 11, 1, 'jk', 'BRANCA');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `pessoa`
--
ALTER TABLE `pessoa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `idx_pessoa_tipo` (`tipo`),
  ADD KEY `idx_pessoa_ativo` (`ativo`),
  ADD KEY `idx_pessoa_cidade` (`cidade`),
  ADD KEY `idx_pessoa_estado` (`estado`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `pessoa`
--
ALTER TABLE `pessoa`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
