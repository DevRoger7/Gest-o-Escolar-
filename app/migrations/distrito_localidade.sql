-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/12/2025 às 14:05
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
-- Estrutura para tabela `distrito_localidade`
--

CREATE TABLE `distrito_localidade` (
  `id` bigint(20) NOT NULL,
  `distrito` varchar(100) NOT NULL COMMENT 'Nome do distrito (ex: Amanari, Sede, Itapebussu)',
  `localidade` varchar(255) NOT NULL COMMENT 'Nome da localidade dentro do distrito (ex: Alto das Vassouras, Centro)',
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Latitude da localidade',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'Longitude da localidade',
  `endereco` text DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT 'Maranguape',
  `estado` char(2) DEFAULT 'CE',
  `cep` varchar(10) DEFAULT NULL,
  `descricao` text DEFAULT NULL COMMENT 'Descrição da localidade',
  `distancia_centro_km` decimal(10,2) DEFAULT NULL COMMENT 'Distância em km do ponto central do distrito',
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `distrito_localidade`
--

INSERT INTO `distrito_localidade` (`id`, `distrito`, `localidade`, `latitude`, `longitude`, `endereco`, `bairro`, `cidade`, `estado`, `cep`, `descricao`, `distancia_centro_km`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 'Amanari', 'Massape', NULL, NULL, '', '', 'Maranguape', 'CE', '', '', NULL, 1, '2025-12-16 12:52:35', '2025-12-16 12:52:35', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `distrito_localidade`
--
ALTER TABLE `distrito_localidade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_distrito` (`distrito`),
  ADD KEY `idx_localidade` (`localidade`),
  ADD KEY `idx_distrito_localidade` (`distrito`,`localidade`),
  ADD KEY `idx_ativo` (`ativo`),
  ADD KEY `criado_por` (`criado_por`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `distrito_localidade`
--
ALTER TABLE `distrito_localidade`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `distrito_localidade`
--
ALTER TABLE `distrito_localidade`
  ADD CONSTRAINT `distrito_localidade_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
