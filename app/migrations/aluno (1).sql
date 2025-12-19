-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/12/2025 às 14:36
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
-- Estrutura para tabela `aluno`
--

CREATE TABLE `aluno` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `responsavel_id` bigint(20) DEFAULT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `data_matricula` date DEFAULT NULL,
  `situacao` enum('MATRICULADO','TRANSFERIDO','EVADIDO','CONCLUIDO','CANCELADO') DEFAULT 'MATRICULADO',
  `data_nascimento` date DEFAULT NULL,
  `nacionalidade` varchar(50) DEFAULT 'Brasileira',
  `naturalidade` varchar(100) DEFAULT NULL,
  `necessidades_especiais` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aluno`
--

INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(1, 10, '2024001', NULL, NULL, NULL, '2025-11-29', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-29 23:05:38', '2025-12-03 18:48:18', NULL, 0),
(2, 28, 'MAT-000028', NULL, NULL, 25, '2025-12-11', 'TRANSFERIDO', NULL, 'Brasileira', NULL, NULL, 'TRANSFERENCIA_ORIGEM:22', '2025-12-01 02:40:04', '2025-12-11 16:06:58', NULL, 1),
(3, 29, 'MAT-000029', NULL, NULL, 25, '2025-12-15', 'TRANSFERIDO', NULL, 'Brasileira', NULL, NULL, 'TRANSFERENCIA_ORIGEM:22', '2025-12-01 02:40:04', '2025-12-15 00:17:01', NULL, 1),
(4, 30, 'MAT-000030', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(5, 31, 'MAT-000031', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(6, 32, 'MAT-000032', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(7, 33, 'MAT-000033', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(8, 34, 'MAT-000034', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(9, 35, 'MAT-000035', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(10, 36, 'MAT-000036', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(11, 37, 'MAT-000037', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(12, 38, 'MAT-000038', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(13, 39, 'MAT-000039', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(14, 40, 'MAT-000040', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(15, 41, 'MAT-000041', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(16, 42, 'MAT-000042', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1),
(17, 51, '20250001', '5542556672', NULL, NULL, '2025-12-10', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-10 12:32:01', '2025-12-16 04:28:25', 3, 0),
(18, 58, '20250002', '21132343434', NULL, 3, '2025-12-15', 'TRANSFERIDO', NULL, 'Brasileira', NULL, NULL, 'TRANSFERENCIA_ORIGEM:25', '2025-12-15 16:16:29', '2025-12-15 16:52:00', 3, 1),
(19, 60, '20250003', NULL, NULL, 25, '2025-12-16', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-16 04:24:44', '2025-12-16 04:28:27', 3, 0),
(20, 62, '20250004', '12321222121', NULL, 25, '2025-12-16', 'MATRICULADO', NULL, 'Brasileira', NULL, 'F32 - pcd', NULL, '2025-12-16 04:29:30', '2025-12-16 04:35:48', 3, 0),
(21, 64, '20250005', '12312313132', 65, 25, '2025-12-16', 'MATRICULADO', '2000-12-12', 'Brasileira', NULL, 'F4 - pcd, F33 - autismo', NULL, '2025-12-16 04:37:39', '2025-12-16 04:46:09', 3, 0),
(22, 66, '20250006', '12311111111', NULL, 25, '2025-12-16', 'MATRICULADO', '2025-11-30', 'Brasileira', NULL, 'F32.0', NULL, '2025-12-16 04:50:40', '2025-12-16 04:51:32', 3, 0),
(23, 67, '20250007', '12311111111', 68, 25, '2025-12-16', 'MATRICULADO', '2025-11-30', 'Brasileira', NULL, 'F32.0', NULL, '2025-12-16 04:51:19', '2025-12-16 04:51:19', 3, 1),
(24, 69, '20250008', NULL, NULL, 25, '2025-12-16', 'MATRICULADO', '1902-09-12', 'Brasileira', NULL, NULL, NULL, '2025-12-16 13:16:03', '2025-12-16 13:16:03', 11, 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `aluno`
--
ALTER TABLE `aluno`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `responsavel_id` (`responsavel_id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `idx_aluno_situacao` (`situacao`),
  ADD KEY `idx_aluno_ativo` (`ativo`),
  ADD KEY `idx_aluno_escola` (`escola_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aluno`
--
ALTER TABLE `aluno`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aluno`
--
ALTER TABLE `aluno`
  ADD CONSTRAINT `aluno_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `aluno_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `aluno_ibfk_3` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
