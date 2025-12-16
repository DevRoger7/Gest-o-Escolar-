-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/12/2025 às 17:31
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
  `ativo` tinyint(1) DEFAULT 1,
  `precisa_transporte` tinyint(1) DEFAULT 0 COMMENT '1 = aluno precisa de transporte escolar, 0 = não precisa',
  `distrito_transporte` varchar(100) DEFAULT NULL COMMENT 'Distrito de Maranguape onde o aluno precisa de transporte (ex: Amanari, Itapebussu, Lagoa)',
  `localidade_transporte` varchar(255) DEFAULT NULL COMMENT 'Localidade específica dentro do distrito onde o aluno precisa de transporte (ex: Massape, Alto das Vassouras, Centro)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aluno`
--

INSERT INTO `aluno` (`id`, `pessoa_id`, `matricula`, `nis`, `responsavel_id`, `escola_id`, `data_matricula`, `situacao`, `data_nascimento`, `nacionalidade`, `naturalidade`, `necessidades_especiais`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`, `precisa_transporte`, `distrito_transporte`, `localidade_transporte`) VALUES
(1, 10, '2024001', NULL, NULL, NULL, '2025-11-29', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-29 23:05:38', '2025-12-03 18:48:18', NULL, 0, 0, NULL, NULL),
(2, 28, 'MAT-000028', NULL, NULL, 25, '2025-12-11', 'TRANSFERIDO', NULL, 'Brasileira', NULL, NULL, 'TRANSFERENCIA_ORIGEM:22', '2025-12-01 02:40:04', '2025-12-11 16:06:58', NULL, 1, 0, NULL, NULL),
(3, 29, 'MAT-000029', NULL, NULL, 25, '2025-12-15', 'TRANSFERIDO', NULL, 'Brasileira', NULL, NULL, 'TRANSFERENCIA_ORIGEM:22', '2025-12-01 02:40:04', '2025-12-15 00:17:01', NULL, 1, 0, NULL, NULL),
(4, 30, 'MAT-000030', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(5, 31, 'MAT-000031', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(6, 32, 'MAT-000032', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(7, 33, 'MAT-000033', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(8, 34, 'MAT-000034', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(9, 35, 'MAT-000035', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(10, 36, 'MAT-000036', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(11, 37, 'MAT-000037', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(12, 38, 'MAT-000038', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(13, 39, 'MAT-000039', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(14, 40, 'MAT-000040', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(15, 41, 'MAT-000041', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(16, 42, 'MAT-000042', NULL, NULL, NULL, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-11 15:02:04', NULL, 1, 0, NULL, NULL),
(17, 51, '20250001', '5542556672', NULL, NULL, '2025-12-10', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-10 12:32:01', '2025-12-16 04:28:25', 3, 0, 0, NULL, NULL),
(18, 58, '20250002', '21132343434', NULL, 3, '2025-12-15', 'TRANSFERIDO', NULL, 'Brasileira', NULL, NULL, 'TRANSFERENCIA_ORIGEM:25', '2025-12-15 16:16:29', '2025-12-15 16:52:00', 3, 1, 0, NULL, NULL),
(19, 60, '20250003', NULL, NULL, 25, '2025-12-16', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-16 04:24:44', '2025-12-16 04:28:27', 3, 0, 0, NULL, NULL),
(20, 62, '20250004', '12321222121', NULL, 25, '2025-12-16', 'MATRICULADO', NULL, 'Brasileira', NULL, 'F32 - pcd', NULL, '2025-12-16 04:29:30', '2025-12-16 04:35:48', 3, 0, 0, NULL, NULL),
(21, 64, '20250005', '12312313132', 65, 25, '2025-12-16', 'MATRICULADO', '2000-12-12', 'Brasileira', NULL, 'F4 - pcd, F33 - autismo', NULL, '2025-12-16 04:37:39', '2025-12-16 04:46:09', 3, 0, 0, NULL, NULL),
(22, 66, '20250006', '12311111111', NULL, 25, '2025-12-16', 'MATRICULADO', '2025-11-30', 'Brasileira', NULL, 'F32.0', NULL, '2025-12-16 04:50:40', '2025-12-16 04:51:32', 3, 0, 0, NULL, NULL),
(23, 67, '20250007', '12311111111', 68, 25, '2025-12-16', 'MATRICULADO', '2025-11-30', 'Brasileira', NULL, 'F32.0', NULL, '2025-12-16 04:51:19', '2025-12-16 04:51:19', 3, 1, 0, NULL, NULL),
(24, 69, '20250008', NULL, NULL, 25, '2025-12-16', 'MATRICULADO', '1902-09-12', 'Brasileira', NULL, NULL, NULL, '2025-12-16 13:16:03', '2025-12-16 13:16:03', 11, 1, 0, NULL, NULL),
(25, 70, '20250009', '54657687896754', NULL, 25, '2025-12-16', 'MATRICULADO', '1915-12-12', 'Brasileira', NULL, NULL, NULL, '2025-12-16 14:23:40', '2025-12-16 14:23:40', 11, 1, 1, 'Amanari', 'Massape');

-- --------------------------------------------------------

--
-- Estrutura para tabela `aluno_responsavel`
--

CREATE TABLE `aluno_responsavel` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `responsavel_id` bigint(20) NOT NULL COMMENT 'ID da pessoa responsável',
  `parentesco` enum('PAI','MAE','AVO','TIO','OUTRO') DEFAULT 'OUTRO',
  `principal` tinyint(1) DEFAULT 0 COMMENT '1 = responsável principal',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aluno_responsavel`
--

INSERT INTO `aluno_responsavel` (`id`, `aluno_id`, `responsavel_id`, `parentesco`, `principal`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(2, 2, 50, 'PAI', 0, NULL, '2025-12-09 13:45:00', '2025-12-09 13:45:00', 11, 1),
(3, 6, 50, 'PAI', 0, NULL, '2025-12-09 14:13:27', '2025-12-09 14:13:27', 11, 1),
(4, 18, 59, 'PAI', 0, NULL, '2025-12-15 16:16:29', '2025-12-15 16:16:29', 3, 1),
(5, 19, 61, 'PAI', 0, NULL, '2025-12-16 04:24:44', '2025-12-16 04:24:44', 3, 1),
(6, 20, 63, 'PAI', 0, NULL, '2025-12-16 04:29:30', '2025-12-16 04:29:30', 3, 1),
(7, 21, 65, 'PAI', 0, NULL, '2025-12-16 04:37:39', '2025-12-16 04:37:39', 3, 1),
(8, 23, 68, 'PAI', 0, NULL, '2025-12-16 04:51:19', '2025-12-16 04:51:19', 3, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `aluno_rota`
--

CREATE TABLE `aluno_rota` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `rota_id` bigint(20) NOT NULL,
  `ponto_embarque_id` bigint(20) DEFAULT NULL COMMENT 'ID do ponto_rota onde o aluno embarca',
  `ponto_desembarque_id` bigint(20) DEFAULT NULL COMMENT 'ID do ponto_rota onde o aluno desembarca',
  `geolocalizacao_id` bigint(20) DEFAULT NULL COMMENT 'ID da geolocalização do aluno usada para criar/atribuir a rota',
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `status` enum('ATIVO','INATIVO','SUSPENSO') DEFAULT 'ATIVO',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `aluno_turma`
--

CREATE TABLE `aluno_turma` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `status` enum('MATRICULADO','TRANSFERIDO','CONCLUIDO','DESISTENTE') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aluno_turma`
--

INSERT INTO `aluno_turma` (`id`, `aluno_id`, `turma_id`, `inicio`, `fim`, `status`, `observacoes`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES
(20, 3, 7, '2025-12-15', NULL, 'MATRICULADO', NULL, '2025-12-15 03:56:02', '2025-12-15 03:56:02', NULL),
(22, 24, 7, '2025-12-16', NULL, 'MATRICULADO', NULL, '2025-12-16 13:16:03', '2025-12-16 13:16:03', NULL),
(23, 25, 7, '2025-12-16', NULL, 'MATRICULADO', NULL, '2025-12-16 14:23:40', '2025-12-16 14:23:40', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacao`
--

CREATE TABLE `avaliacao` (
  `id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `data` date DEFAULT NULL,
  `tipo` enum('TRABALHO','PROVA','ATIVIDADE') DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `criado_por` bigint(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `avaliacao`
--

INSERT INTO `avaliacao` (`id`, `turma_id`, `disciplina_id`, `titulo`, `descricao`, `data`, `tipo`, `peso`, `criado_por`, `criado_em`, `atualizado_em`, `ativo`) VALUES
(28, 7, 5, 'Avaliação Parcial - 1º Bimestre', NULL, '2025-12-15', 'ATIVIDADE', NULL, NULL, '2025-12-16 02:35:05', '2025-12-16 02:35:05', 1),
(29, 7, 5, 'Avaliação Bimestral - 1º Bimestre', NULL, '2025-12-15', 'PROVA', NULL, NULL, '2025-12-16 02:35:05', '2025-12-16 02:35:05', 1),
(30, 7, 5, 'Avaliação Participativa - 1º Bimestre', NULL, '2025-12-15', 'TRABALHO', NULL, NULL, '2025-12-16 02:35:05', '2025-12-16 02:35:05', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `boletim`
--

CREATE TABLE `boletim` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `ano_letivo` int(11) NOT NULL,
  `bimestre` int(11) NOT NULL,
  `media_geral` decimal(5,2) DEFAULT NULL,
  `frequencia_percentual` decimal(5,2) DEFAULT NULL,
  `total_faltas` int(11) DEFAULT 0,
  `situacao` enum('APROVADO','REPROVADO','RECUPERACAO','PENDENTE') DEFAULT 'PENDENTE',
  `observacoes` text DEFAULT NULL,
  `gerado_por` bigint(20) DEFAULT NULL,
  `gerado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `boletim_item`
--

CREATE TABLE `boletim_item` (
  `id` bigint(20) NOT NULL,
  `boletim_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) NOT NULL,
  `media` decimal(5,2) DEFAULT NULL,
  `faltas` int(11) DEFAULT 0,
  `situacao` enum('APROVADO','REPROVADO','RECUPERACAO') DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `calendar_categories`
--

CREATE TABLE `calendar_categories` (
  `id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(7) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `calendar_categories`
--

INSERT INTO `calendar_categories` (`id`, `name`, `color`, `icon`, `description`, `created_by`, `created_at`, `ativo`) VALUES
(1, 'ReuniÃµes', '#3B82F6', 'users', 'ReuniÃµes gerais e administrativas', 1, '2025-10-24 16:32:08', 1),
(2, 'AvaliaÃ§Ãµes', '#EF4444', 'book-open', 'Provas e avaliaÃ§Ãµes dos alunos', 1, '2025-10-24 16:32:08', 1),
(3, 'Feriados', '#10B981', 'calendar', 'Feriados nacionais e regionais', 1, '2025-10-24 16:32:08', 1),
(4, 'Eventos', '#F59E0B', 'star', 'Eventos especiais da escola', 1, '2025-10-24 16:32:08', 1),
(5, 'Aulas', '#8B5CF6', 'graduation-cap', 'Aulas e atividades pedagÃ³gicas', 1, '2025-10-24 16:32:08', 1),
(6, 'Treinamentos', '#EC4899', 'book', 'Treinamentos e capacitaÃ§Ãµes', 1, '2025-10-24 16:32:08', 1),
(7, 'ReuniÃ£o de Pais', '#14B8A6', 'users', 'ReuniÃµes com pais e responsÃ¡veis', 1, '2025-10-24 16:32:08', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `all_day` tinyint(1) DEFAULT 0,
  `color` varchar(7) DEFAULT '#3B82F6',
  `event_type` enum('meeting','exam','holiday','event','deadline','class','meeting_parents','training') DEFAULT 'event',
  `school_id` bigint(20) DEFAULT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `title`, `description`, `start_date`, `end_date`, `all_day`, `color`, `event_type`, `school_id`, `created_by`, `created_at`, `updated_at`, `ativo`) VALUES
(1, 'ReuniÃ£o PedagÃ³gica', 'ReuniÃ£o mensal com professores para planejamento pedagÃ³gico', '2025-01-15 14:00:00', '2025-01-15 16:00:00', 0, '#3B82F6', 'meeting', 14, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
(2, 'Prova de MatemÃ¡tica - 6Âº Ano', 'AvaliaÃ§Ã£o bimestral de matemÃ¡tica para o 6Âº ano', '2025-01-20 08:00:00', '2025-01-20 10:00:00', 0, '#EF4444', 'exam', 14, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
(3, 'Feriado - SÃ£o SebastiÃ£o', 'Feriado municipal em homenagem ao padroeiro', '2025-01-20 00:00:00', '2025-01-20 23:59:59', 1, '#10B981', 'holiday', NULL, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
(4, 'ReuniÃ£o de Pais - 1Âº Bimestre', 'ReuniÃ£o com pais para entrega de boletins do 1Âº bimestre', '2025-01-25 19:00:00', '2025-01-25 21:00:00', 0, '#14B8A6', 'meeting_parents', 14, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
(5, 'CapacitaÃ§Ã£o de Professores', 'Treinamento sobre novas metodologias de ensino', '2025-01-30 09:00:00', '2025-01-30 17:00:00', 0, '#EC4899', 'training', 14, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
(6, 'Salaberga fantasy', '', '2025-10-31 13:00:00', '2025-10-31 17:00:00', 0, '#10B981', 'meeting', NULL, 1, '2025-10-24 18:03:53', '2025-10-24 18:09:14', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `calendar_event_participants`
--

CREATE TABLE `calendar_event_participants` (
  `id` bigint(20) NOT NULL,
  `event_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `role` enum('organizer','attendee','optional') DEFAULT 'attendee',
  `status` enum('pending','accepted','declined','tentative') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `calendar_event_recurrence`
--

CREATE TABLE `calendar_event_recurrence` (
  `id` bigint(20) NOT NULL,
  `event_id` bigint(20) NOT NULL,
  `recurrence_type` enum('daily','weekly','monthly','yearly') NOT NULL,
  `interval_value` int(11) DEFAULT 1,
  `days_of_week` varchar(20) DEFAULT NULL,
  `day_of_month` int(11) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `occurrences` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `calendar_notifications`
--

CREATE TABLE `calendar_notifications` (
  `id` bigint(20) NOT NULL,
  `event_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `notification_type` enum('email','sms','push','system') DEFAULT 'system',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `calendar_settings`
--

CREATE TABLE `calendar_settings` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `calendar_settings`
--

INSERT INTO `calendar_settings` (`id`, `user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'default_view', 'month', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(2, 2, 'default_view', 'month', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(3, 3, 'default_view', 'month', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(4, 4, 'default_view', 'month', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(5, 5, 'default_view', 'month', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(6, 6, 'default_view', 'month', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(8, 1, 'week_start', 'monday', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(9, 2, 'week_start', 'monday', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(10, 3, 'week_start', 'monday', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(11, 4, 'week_start', 'monday', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(12, 5, 'week_start', 'monday', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(13, 6, 'week_start', 'monday', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(15, 1, 'timezone', 'America/Fortaleza', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(16, 2, 'timezone', 'America/Fortaleza', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(17, 3, 'timezone', 'America/Fortaleza', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(18, 4, 'timezone', 'America/Fortaleza', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(19, 5, 'timezone', 'America/Fortaleza', '2025-10-24 16:32:09', '2025-10-24 16:32:09'),
(20, 6, 'timezone', 'America/Fortaleza', '2025-10-24 16:32:09', '2025-10-24 16:32:09');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cardapio`
--

CREATE TABLE `cardapio` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `mes` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `status` enum('RASCUNHO','APROVADO','REJEITADO','PUBLICADO') DEFAULT 'RASCUNHO',
  `aprovado_por` bigint(20) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_por` bigint(20) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cardapio`
--

INSERT INTO `cardapio` (`id`, `escola_id`, `mes`, `ano`, `status`, `aprovado_por`, `data_aprovacao`, `observacoes`, `criado_por`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES
(1003, 3, 12, 2025, 'APROVADO', NULL, '2025-12-16 03:27:54', NULL, 29, '2025-12-16 03:15:31', '2025-12-16 03:27:54', NULL),
(1004, 3, 12, 2025, 'APROVADO', NULL, '2025-12-16 03:36:50', NULL, 29, '2025-12-16 03:35:52', '2025-12-16 03:36:50', NULL),
(1008, 3, 12, 2025, 'RASCUNHO', NULL, NULL, NULL, 29, '2025-12-16 04:01:15', '2025-12-16 04:01:42', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cardapio_item`
--

CREATE TABLE `cardapio_item` (
  `id` bigint(20) NOT NULL,
  `cardapio_id` bigint(20) NOT NULL,
  `semana_id` bigint(20) DEFAULT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cardapio_item`
--

INSERT INTO `cardapio_item` (`id`, `cardapio_id`, `semana_id`, `produto_id`, `quantidade`) VALUES
(20, 1003, 3, 1001, 2.000),
(21, 1003, 3, 1020, 3.000),
(22, 1004, NULL, 1001, 200.000),
(34, 1008, NULL, 1020, 6.000);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cardapio_semana`
--

CREATE TABLE `cardapio_semana` (
  `id` bigint(20) NOT NULL,
  `cardapio_id` bigint(20) NOT NULL,
  `numero_semana` tinyint(4) NOT NULL COMMENT 'Número da semana no mês (1-4 ou 1-5)',
  `observacao` text DEFAULT NULL COMMENT 'Observações específicas desta semana',
  `data_inicio` date DEFAULT NULL COMMENT 'Data de início da semana',
  `data_fim` date DEFAULT NULL COMMENT 'Data de fim da semana',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cardapio_semana`
--

INSERT INTO `cardapio_semana` (`id`, `cardapio_id`, `numero_semana`, `observacao`, `data_inicio`, `data_fim`, `criado_em`, `atualizado_em`) VALUES
(3, 1003, 1, '', '2025-12-15', '2025-12-21', '2025-12-16 03:19:08', '2025-12-16 03:19:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comunicado`
--

CREATE TABLE `comunicado` (
  `id` bigint(20) NOT NULL,
  `turma_id` bigint(20) DEFAULT NULL,
  `aluno_id` bigint(20) DEFAULT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `enviado_por` bigint(20) NOT NULL,
  `tipo` enum('GERAL','TURMA','ALUNO','URGENTE') DEFAULT 'GERAL',
  `prioridade` enum('BAIXA','NORMAL','ALTA','URGENTE') DEFAULT 'NORMAL',
  `canal` enum('SISTEMA','EMAIL','SMS','WHATSAPP','TODOS') DEFAULT 'SISTEMA',
  `titulo` varchar(255) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `lido` tinyint(1) DEFAULT 0,
  `enviado` tinyint(1) DEFAULT 0,
  `data_envio` timestamp NULL DEFAULT NULL,
  `respostas_recebidas` int(11) DEFAULT 0,
  `visualizacoes` int(11) DEFAULT 0,
  `data_leitura` timestamp NULL DEFAULT NULL,
  `lido_por` bigint(20) DEFAULT NULL,
  `anexo_url` varchar(500) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comunicado`
--

INSERT INTO `comunicado` (`id`, `turma_id`, `aluno_id`, `escola_id`, `enviado_por`, `tipo`, `prioridade`, `canal`, `titulo`, `mensagem`, `lido`, `enviado`, `data_envio`, `respostas_recebidas`, `visualizacoes`, `data_leitura`, `lido_por`, `anexo_url`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(4, NULL, NULL, 22, 11, 'GERAL', 'NORMAL', 'SISTEMA', 'Comunicado teste', '1', 0, 0, NULL, 0, 0, NULL, NULL, NULL, 1, '2025-12-16 05:57:43', '2025-12-16 05:57:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comunicado_resposta`
--

CREATE TABLE `comunicado_resposta` (
  `id` bigint(20) NOT NULL,
  `comunicado_id` bigint(20) NOT NULL,
  `responsavel_id` bigint(20) NOT NULL,
  `resposta` text DEFAULT NULL,
  `lido` tinyint(1) DEFAULT 0,
  `data_leitura` timestamp NULL DEFAULT NULL,
  `data_resposta` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracao`
--

CREATE TABLE `configuracao` (
  `id` bigint(20) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('STRING','INTEGER','BOOLEAN','JSON') DEFAULT 'STRING',
  `categoria` varchar(50) DEFAULT 'GERAL',
  `descricao` text DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `configuracao`
--

INSERT INTO `configuracao` (`id`, `chave`, `valor`, `tipo`, `categoria`, `descricao`, `atualizado_em`, `atualizado_por`) VALUES
(1, 'nome_sistema', 'SIGAE - Sistema de Gestão Escolar e Merenda', 'STRING', 'GERAL', NULL, '2025-12-08 12:41:40', 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `consumo_diario`
--

CREATE TABLE `consumo_diario` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) DEFAULT NULL,
  `data` date NOT NULL,
  `turno` enum('MANHA','TARDE','NOITE','INTEGRAL') DEFAULT NULL,
  `total_alunos` int(11) DEFAULT 0,
  `alunos_atendidos` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `consumo_diario`
--

INSERT INTO `consumo_diario` (`id`, `escola_id`, `turma_id`, `data`, `turno`, `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES
(9, 3, NULL, '2025-12-16', 'MANHA', 12, 1, '', NULL, '2025-12-16 00:50:20', '2025-12-16 00:50:20', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `consumo_item`
--

CREATE TABLE `consumo_item` (
  `id` bigint(20) NOT NULL,
  `consumo_diario_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL,
  `unidade_medida` varchar(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `custo_merenda`
--

CREATE TABLE `custo_merenda` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `tipo` enum('COMPRA_PRODUTOS','DISTRIBUICAO','PREPARO','DESPERDICIO','OUTROS') DEFAULT 'OUTROS',
  `descricao` varchar(255) DEFAULT NULL,
  `produto_id` bigint(20) DEFAULT NULL,
  `fornecedor_id` bigint(20) DEFAULT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL,
  `valor_unitario` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(12,2) NOT NULL,
  `data` date NOT NULL,
  `mes` int(11) DEFAULT NULL,
  `ano` int(11) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `custo_merenda`
--

INSERT INTO `custo_merenda` (`id`, `escola_id`, `tipo`, `descricao`, `produto_id`, `fornecedor_id`, `quantidade`, `valor_unitario`, `valor_total`, `data`, `mes`, `ano`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`) VALUES
(14, NULL, 'COMPRA_PRODUTOS', 'Entrada de estoque: Açúcar Cristal - Lote: 1212212121212 - NF: 12121212121212', 1027, 1005, 12.000, 12.00, 144.00, '2025-12-10', 12, 2025, 'Entrada de produto no estoque central - Validade: 16/01/2000 - Nota Fiscal: 12121212121212', NULL, '2025-12-10 14:13:57', '2025-12-10 14:13:57'),
(15, NULL, 'COMPRA_PRODUTOS', 'Entrada de estoque: Açúcar Cristal - Lote: 1212121212 - NF: 1212121212', 1027, 1005, 12.000, 15000.00, 180000.00, '2025-12-16', 12, 2025, 'Entrada de produto no estoque central - Validade: 16/01/2027 - Nota Fiscal: 1212121212', NULL, '2025-12-16 04:05:05', '2025-12-16 04:05:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `desperdicio`
--

CREATE TABLE `desperdicio` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `data` date NOT NULL,
  `turno` enum('MANHA','TARDE','NOITE','INTEGRAL') DEFAULT NULL,
  `produto_id` bigint(20) DEFAULT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL,
  `unidade_medida` varchar(20) DEFAULT NULL,
  `peso_kg` decimal(10,3) DEFAULT NULL,
  `motivo` enum('EXCESSO_PREPARO','REJEICAO_ALUNOS','VALIDADE_VENCIDA','PREPARO_INCORRETO','OUTROS') DEFAULT 'OUTROS',
  `motivo_detalhado` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `desperdicio`
--

INSERT INTO `desperdicio` (`id`, `escola_id`, `data`, `turno`, `produto_id`, `quantidade`, `unidade_medida`, `peso_kg`, `motivo`, `motivo_detalhado`, `observacoes`, `registrado_por`, `registrado_em`, `atualizado_em`) VALUES
(5, 25, '2025-12-16', 'MANHA', 1022, 12.000, NULL, 1.000, 'REJEICAO_ALUNOS', NULL, NULL, NULL, '2025-12-16 00:50:48', '2025-12-16 00:50:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `disciplina`
--

CREATE TABLE `disciplina` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `carga_horaria` int(11) DEFAULT NULL,
  `area_conhecimento` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `disciplina`
--

INSERT INTO `disciplina` (`id`, `codigo`, `nome`, `descricao`, `carga_horaria`, `area_conhecimento`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'PORT', 'Língua Portuguesa', NULL, 160, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57'),
(2, 'MAT', 'Matemática', NULL, 160, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57'),
(3, 'HIST', 'História', NULL, 80, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57'),
(4, 'GEO', 'Geografia', NULL, 80, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57'),
(5, 'CIEN', 'Ciências', NULL, 80, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57'),
(6, 'EDF', 'Educação Física', NULL, 80, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57'),
(7, 'ART', 'Artes', NULL, 40, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57'),
(8, 'ING', 'Língua Inglesa', NULL, 40, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57');

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
(1, 'Amanari', 'Massape', NULL, NULL, '', '', 'Maranguape', 'CE', '', '', NULL, 1, '2025-12-16 12:52:35', '2025-12-16 12:52:35', NULL),
(2, 'Amanari', 'Vassouras', NULL, NULL, '', '', 'Maranguape', 'CE', '', '', NULL, 1, '2025-12-16 14:53:15', '2025-12-16 14:53:15', NULL),
(3, 'Amanari', 'Pedra D´água', NULL, NULL, NULL, NULL, 'Maranguape', 'CE', NULL, NULL, NULL, 1, '2025-12-16 16:28:14', '2025-12-16 16:28:14', 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `distrito_ponto_central`
--

CREATE TABLE `distrito_ponto_central` (
  `id` bigint(20) NOT NULL,
  `distrito` varchar(100) NOT NULL COMMENT 'Nome do distrito',
  `nome` varchar(255) DEFAULT NULL COMMENT 'Nome do ponto central (ex: Sede Distrital, Escola de Referência)',
  `latitude` decimal(10,8) NOT NULL COMMENT 'Latitude do ponto central',
  `longitude` decimal(11,8) NOT NULL COMMENT 'Longitude do ponto central',
  `endereco` text DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT 'Maranguape',
  `estado` char(2) DEFAULT 'CE',
  `cep` varchar(10) DEFAULT NULL,
  `tipo` enum('SEDE_DISTRITAL','ESCOLA_REFERENCIA','OUTRO') DEFAULT 'SEDE_DISTRITAL',
  `escola_id` bigint(20) DEFAULT NULL COMMENT 'ID da escola se for escola de referência',
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `entrega`
--

CREATE TABLE `entrega` (
  `id` bigint(20) NOT NULL,
  `pedido_cesta_id` bigint(20) DEFAULT NULL,
  `escola_id` bigint(20) NOT NULL,
  `fornecedor_id` bigint(20) DEFAULT NULL,
  `data_prevista` date NOT NULL,
  `data_entrega` date DEFAULT NULL,
  `status` enum('AGENDADA','EM_TRANSITO','ENTREGUE','CANCELADA','ATRASADA') DEFAULT 'AGENDADA',
  `transportadora` varchar(255) DEFAULT NULL,
  `nota_fiscal` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `recebido_por` bigint(20) DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `entrega_item`
--

CREATE TABLE `entrega_item` (
  `id` bigint(20) NOT NULL,
  `entrega_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade_solicitada` decimal(10,3) DEFAULT NULL,
  `quantidade_entregue` decimal(10,3) DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `escola`
--

CREATE TABLE `escola` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `distrito` varchar(100) DEFAULT NULL COMMENT 'Distrito de Maranguape onde a escola está localizada (ex: Amanari, Sede, Itapebussu). Campo obrigatório para facilitar o processo de criação de rotas.',
  `escola_localidade` tinyint(1) DEFAULT 0 COMMENT 'Indica se a escola é uma escola de localidade (1) ou não (0). Escolas de localidade atendem alunos de outras localidades dentro do mesmo distrito.',
  `municipio` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT 'CE',
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `telefone_secundario` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `diretor_id` bigint(20) DEFAULT NULL,
  `qtd_salas` int(11) DEFAULT NULL,
  `nivel_ensino` set('ENSINO_FUNDAMENTAL','ENSINO_MEDIO') DEFAULT 'ENSINO_FUNDAMENTAL',
  `obs` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `escola`
--

INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `distrito`, `escola_localidade`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `nivel_ensino`, `obs`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES
(3, NULL, 'Salaberga', 'Rua rua rua', NULL, NULL, NULL, NULL, 0, 'maranguape', 'CE', '22222-222', '(85) 9999-9922', NULL, 'escola@gmail.com', NULL, NULL, NULL, 6, 'ENSINO_FUNDAMENTAL', 'aaa', 1, '2025-09-23 17:46:30', '2025-12-11 16:50:06', NULL),
(4, NULL, 'Sebastiao De Abreu', 'qr3e', NULL, NULL, NULL, NULL, 0, 'itapebusi', 'CE', '12323-123', '(85) 9999-9277', NULL, 'weeescola@gmail.com', NULL, NULL, NULL, 44, 'ENSINO_FUNDAMENTAL', 'aaa', 1, '2025-09-23 17:57:45', '2025-12-11 16:50:13', NULL),
(14, '3434343', 'Manoel Rodrigues', 'Rua Joaninha Vieira', NULL, NULL, NULL, NULL, 0, 'Maranguape', 'CE', '61943-290', '(85) 9999-9922', NULL, 'yudipro859@gmail.com', NULL, NULL, NULL, 12, 'ENSINO_FUNDAMENTAL', 'adasdwaddwdad', 1, '2025-09-24 19:05:44', '2025-12-11 16:50:18', NULL),
(16, '243434', 'Sao Jose', 'Rua Joaninha Vieira', NULL, NULL, NULL, NULL, 0, 'Maranguape', 'CE', '61943-290', '(85) 9999-9933', NULL, 'yudipro859@gmail.com', NULL, NULL, NULL, 8, 'ENSINO_FUNDAMENTAL', '', 1, '2025-09-25 17:23:57', '2025-12-11 16:50:29', NULL),
(17, '12345678', 'Espaco Livre', 'Rua das Flores, 123 - Centro', NULL, NULL, NULL, NULL, 0, 'Maranguape', 'CE', '61940-000', '(85) 3333-4444', NULL, 'escola.teste@sigae.com', NULL, NULL, NULL, 20, 'ENSINO_FUNDAMENTAL', 'Escola criada para testes do sistema SIGAE', 0, '2025-12-01 02:34:56', '2025-12-11 16:50:37', NULL),
(22, 'sdadsasd', 'Direitos Humanos', 'Rua José Batista, 422, campo do ronerio, Aldeoma', NULL, NULL, NULL, NULL, 0, 'MARANGUAPE', 'CE', '61948-050', '(85) 98183-5778', NULL, 'cavalcanterogeer@gmail.com', NULL, '12.132.313/1231-32', NULL, 12, 'ENSINO_FUNDAMENTAL', 'Gestor: João Silva (Gestor Teste) | CPF: 12345678900 | Cargo: Diretor | Email: gestor.teste@sigae.com | INEP Escola: dsadadas | Tipo: NORMAL | CNPJ: 12.132.313/1231-32', 1, '2025-12-10 18:54:53', '2025-12-11 16:50:44', NULL),
(25, 'dsfsdfsdf', 'Ari De Sa', 'Rua José Batista, 12, campo do ronerio, Aldeoma', NULL, NULL, NULL, NULL, 0, 'MARANGUAPE', 'CE', '61948-050', '(85) 98183-5778', NULL, 'cavalcanterogeer@gmail.com', NULL, '12.222.222/2222-22', NULL, 21, 'ENSINO_FUNDAMENTAL,ENSINO_MEDIO', 'Gestor: João Silva (Gestor Teste) | CPF: 12345678900 | Cargo: Diretor | Email: gestor.teste@sigae.com | INEP Escola: sdfsdfsd | Tipo: NORMAL | CNPJ: 12.222.222/2222-22', 1, '2025-12-10 19:09:26', '2025-12-15 03:20:19', NULL),
(26, 'ESC20251215030757', 'Escola de Ensino Médio E.C.M. Pedro Pessoa Câmara', 'Avenida Doutor Argeu Gurgel Braga Herbster, 42, Outra Banda', NULL, NULL, NULL, NULL, 0, 'MARANGUAPE', 'CE', '61942-005', '(85) 34545-45345', NULL, '', NULL, '76.754.565/6565-65', NULL, 32, 'ENSINO_FUNDAMENTAL', '', 1, '2025-12-15 02:07:57', '2025-12-15 04:11:08', NULL),
(27, 'ESC20251216132814', 'Cristovão Colombo EMEIEF', 'Pedra D´água, 00, Amanari', NULL, NULL, NULL, 'Amanari', 0, 'MARANGUAPE', 'CE', '61979-000', '(85) 98177-7131', NULL, 'cristovaocolombo@gmail.com', NULL, NULL, NULL, 1, 'ENSINO_FUNDAMENTAL', 'Gestor: João Silva (Gestor Teste) | CPF: 12345678900 | Cargo: Diretor | Email: gestor.teste@sigae.com | INEP Escola: 23082704 | Tipo: NORMAL', 1, '2025-12-16 16:28:14', '2025-12-16 16:28:14', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `escola_backup`
--

CREATE TABLE `escola_backup` (
  `id` bigint(20) NOT NULL,
  `escola_id_original` bigint(20) NOT NULL COMMENT 'ID original da escola antes da exclusão',
  `dados_escola` longtext NOT NULL COMMENT 'JSON com todos os dados da escola (backup completo antes da exclusão - dados são EXCLUÍDOS das tabelas normais)',
  `dados_turmas` longtext DEFAULT NULL COMMENT 'JSON com TODOS os dados relacionados (turmas, alunos, aluno_responsavel, notas, frequências, avaliações, boletins, entregas, cardápios, consumo, desperdicio, historico_escolar, indicador_nutricional, parecer_tecnico, pedido_cesta, pedido_cesta_item, relatorio, etc.) - backup completo antes da exclusão - dados são EXCLUÍDOS das tabelas normais',
  `dados_lotacoes` longtext DEFAULT NULL COMMENT 'JSON com dados das lotações (professores, gestores, nutricionistas, funcionários) - backup completo antes da exclusão - dados são EXCLUÍDOS das tabelas normais',
  `excluido_por` bigint(20) DEFAULT NULL COMMENT 'ID do usuário que excluiu',
  `excluido_em` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da exclusão (HARD DELETE - todos os dados foram movidos para backup e EXCLUÍDOS das tabelas principais)',
  `excluido_permanentemente` tinyint(1) DEFAULT 0 COMMENT 'Se foi excluído permanentemente antes dos 30 dias',
  `revertido` tinyint(1) DEFAULT 0 COMMENT 'Se a exclusão foi revertida',
  `revertido_em` timestamp NULL DEFAULT NULL COMMENT 'Data da reversão',
  `revertido_por` bigint(20) DEFAULT NULL COMMENT 'ID do usuário que reverteu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `escola_backup`
--

INSERT INTO `escola_backup` (`id`, `escola_id_original`, `dados_escola`, `dados_turmas`, `dados_lotacoes`, `excluido_por`, `excluido_em`, `excluido_permanentemente`, `revertido`, `revertido_em`, `revertido_por`) VALUES
(8, 16, '', NULL, NULL, 3, '2025-12-14 05:18:58', 0, 1, '2025-12-14 05:19:43', 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `escola_programa`
--

CREATE TABLE `escola_programa` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `programa_id` bigint(20) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `escola_programa`
--

INSERT INTO `escola_programa` (`id`, `escola_id`, `programa_id`, `criado_em`) VALUES
(1, 25, 1, '2025-12-10 19:09:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque_central`
--

CREATE TABLE `estoque_central` (
  `id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(12,3) DEFAULT NULL,
  `lote` varchar(100) DEFAULT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `fornecedor_id` bigint(20) DEFAULT NULL,
  `nota_fiscal` varchar(50) DEFAULT NULL,
  `valor_unitario` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(12,2) DEFAULT NULL,
  `criado_por` bigint(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `validade` date DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `estoque_central`
--

INSERT INTO `estoque_central` (`id`, `produto_id`, `quantidade`, `lote`, `fornecedor`, `fornecedor_id`, `nota_fiscal`, `valor_unitario`, `valor_total`, `criado_por`, `criado_em`, `validade`, `atualizado_em`, `atualizado_por`) VALUES
(1001, 1001, 200.000, 'LOTE-2024-001', NULL, 1001, 'NF-001234', 4.50, 2250.00, NULL, '2025-12-01 23:45:56', '2025-12-31', '2025-12-11 17:03:19', NULL),
(1002, 1002, 400.000, 'LOTE-2024-002', NULL, 1001, 'NF-001235', 8.00, 3200.00, NULL, '2025-12-01 23:45:56', '2025-12-31', '2025-12-01 23:45:56', NULL),
(1003, 1003, 300.000, 'LOTE-2024-003', NULL, 1001, 'NF-001236', 5.50, 1650.00, NULL, '2025-12-01 23:45:56', '2025-12-31', '2025-12-01 23:45:56', NULL),
(1004, 1006, 150.000, 'LOTE-2024-004', NULL, 1002, 'NF-002345', 28.00, 4200.00, NULL, '2025-12-01 23:45:56', '2025-01-15', '2025-12-01 23:45:56', NULL),
(1005, 1007, 200.000, 'LOTE-2024-005', NULL, 1002, 'NF-002346', 12.00, 2400.00, NULL, '2025-12-01 23:45:56', '2025-01-20', '2025-12-01 23:45:56', NULL),
(1006, 1009, 1000.000, 'LOTE-2024-006', NULL, 1004, 'NF-003456', 4.20, 4200.00, NULL, '2025-12-01 23:45:56', '2025-01-10', '2025-12-01 23:45:56', NULL),
(1007, 1012, 300.000, 'LOTE-2024-007', NULL, 1003, 'NF-004567', 3.50, 1050.00, NULL, '2025-12-01 23:45:56', '2025-01-25', '2025-12-01 23:45:56', NULL),
(1008, 1001, 500.000, 'LOTE-2024-008', NULL, 1001, 'NF-001237', 8.50, 1700.00, NULL, '2025-12-01 23:45:56', '2026-06-30', '2025-12-11 17:12:02', NULL),
(1009, 1005, 15.000, 'LOTE-2024-009', NULL, 1001, 'NF-001238', 3.80, 57.00, NULL, '2025-12-01 23:45:56', '2025-12-31', '2025-12-01 23:45:56', NULL),
(1010, 1011, 5.000, 'LOTE-2024-010', NULL, 1004, 'NF-003457', 18.00, 90.00, NULL, '2025-12-01 23:45:56', '2025-02-28', '2025-12-01 23:45:56', NULL),
(1011, 1013, 8.000, 'LOTE-2024-011', NULL, 1003, 'NF-004568', 4.00, 32.00, NULL, '2025-12-01 23:45:56', '2025-01-20', '2025-12-01 23:45:56', NULL),
(1023, 1020, 0.000, '23', NULL, 1003, '2334343434', 3.00, 9.00, NULL, '2025-12-09 17:03:12', '2026-03-03', '2025-12-16 03:43:18', NULL),
(1025, 1027, 12.000, '1212212121212', NULL, 1005, '12121212121212', NULL, NULL, NULL, '2025-12-10 14:13:57', '2000-01-16', '2025-12-10 14:13:57', NULL),
(1026, 1027, 12.000, '1212121212', NULL, 1005, '1212121212', NULL, NULL, NULL, '2025-12-16 04:05:05', '2027-01-16', '2025-12-16 04:05:05', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedor`
--

CREATE TABLE `fornecedor` (
  `id` bigint(20) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `inscricao_estadual` varchar(50) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `telefone_secundario` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contato` varchar(255) DEFAULT NULL,
  `tipo_fornecedor` enum('ALIMENTOS','BEBIDAS','MATERIAIS','SERVICOS','OUTROS') DEFAULT 'ALIMENTOS',
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `fornecedor`
--

INSERT INTO `fornecedor` (`id`, `nome`, `razao_social`, `cnpj`, `inscricao_estadual`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `contato`, `tipo_fornecedor`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1001, 'Distribuidora Alimentos Maranguape', 'Distribuidora Alimentos Maranguape LTDA', '12.345.678/0001-90', '123456789', 'Av. Principal', '500', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', '(85) 3333-1111', NULL, 'contato@alimentosmaranguape.com.br', 'João Silva', 'ALIMENTOS', NULL, 1, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL),
(1002, 'Carnes Premium', 'Carnes Premium EIRELI', '23.456.789/0001-01', '234567890', 'Rua dos Açougues', '200', NULL, 'Industrial', 'Fortaleza', 'CE', '60000-000', '(85) 3333-2222', NULL, 'vendas@carnespremium.com.br', 'Maria Santos', 'ALIMENTOS', NULL, 1, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL),
(1003, 'Hortifruti Verde Vida', 'Hortifruti Verde Vida ME', '34.567.890/0001-12', '345678901', 'Rodovia BR-116', 'KM 15', NULL, 'Zona Rural', 'Maranguape', 'CE', '61940-000', '(85) 3333-3333', NULL, 'compras@verdevida.com.br', 'Pedro Oliveira', 'ALIMENTOS', NULL, 1, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL),
(1004, 'Laticínios do Nordeste', 'Laticínios do Nordeste S.A.', '45.678.901/0001-23', '456789012', 'Av. Industrial', '1000', NULL, 'Distrito Industrial', 'Fortaleza', 'CE', '60000-000', '(85) 3333-4444', NULL, 'comercial@laticiniosne.com.br', 'Ana Costa', 'ALIMENTOS', NULL, 1, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL),
(1005, 'Bebidas e Refrigerantes CE', 'Bebidas e Refrigerantes CE LTDA', '56.789.012/0001-34', '567890123', 'Rua Comercial', '300', NULL, 'Centro', 'Maranguape', 'CE', '61940-000', '(85) 3333-5555', NULL, 'pedidos@bebidasce.com.br', 'Carlos Mendes', 'BEBIDAS', NULL, 1, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL),
(1014, 'bolacha itapebussu', 'null', '87438437434334', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '85343433434', NULL, NULL, NULL, 'ALIMENTOS', 'null', 1, '2025-12-09 17:43:11', '2025-12-09 17:43:11', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `frequencia`
--

CREATE TABLE `frequencia` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `data` date NOT NULL,
  `presenca` tinyint(1) NOT NULL,
  `observacao` text DEFAULT NULL,
  `validado` tinyint(1) DEFAULT 0,
  `validado_por` bigint(20) DEFAULT NULL,
  `data_validacao` timestamp NULL DEFAULT NULL,
  `justificativa_id` bigint(20) DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `frequencia`
--

INSERT INTO `frequencia` (`id`, `aluno_id`, `turma_id`, `data`, `presenca`, `observacao`, `validado`, `validado_por`, `data_validacao`, `justificativa_id`, `registrado_por`, `registrado_em`, `atualizado_em`, `atualizado_por`) VALUES
(21, 3, 7, '2025-12-15', 1, NULL, 0, NULL, NULL, NULL, NULL, '2025-12-16 02:25:58', '2025-12-16 02:25:58', NULL),
(22, 3, 7, '2025-12-16', 0, NULL, 0, NULL, NULL, NULL, NULL, '2025-12-16 02:27:57', '2025-12-16 02:27:57', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionario`
--

CREATE TABLE `funcionario` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `setor` varchar(100) DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `data_demissao` date DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionario_lotacao`
--

CREATE TABLE `funcionario_lotacao` (
  `id` bigint(20) NOT NULL,
  `funcionario_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `setor` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `geolocalizacao_aluno`
--

CREATE TABLE `geolocalizacao_aluno` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `tipo` enum('RESIDENCIA','PONTO_REFERENCIA','OUTRO') DEFAULT 'RESIDENCIA',
  `nome` varchar(255) DEFAULT NULL COMMENT 'Nome do local (ex: Casa, Ponto de referência)',
  `localidade` varchar(100) DEFAULT NULL COMMENT 'Nome da localidade/região (ex: Lagoa, Itapebussu, Amanari)',
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 0 COMMENT '1 = localização principal do aluno',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `gestor`
--

CREATE TABLE `gestor` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `formacao` text DEFAULT NULL,
  `registro_profissional` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `gestor`
--

INSERT INTO `gestor` (`id`, `pessoa_id`, `cargo`, `formacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(1, 4, 'gestor', NULL, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL, 1),
(2, 5, 'gestor', NULL, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL, 1),
(3, 11, 'Diretor', NULL, NULL, NULL, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL, 1),
(4, 11, 'Diretor', NULL, NULL, NULL, '2025-12-01 02:40:03', '2025-12-03 19:02:39', NULL, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `gestor_lotacao`
--

CREATE TABLE `gestor_lotacao` (
  `id` bigint(20) NOT NULL,
  `gestor_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `responsavel` tinyint(1) DEFAULT NULL,
  `tipo` enum('Diretor','Vice-Diretor','Coordenador PedagÃ³gico','SecretÃ¡rio Escolar') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `gestor_lotacao`
--

INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(9, 1, 14, '2025-09-24', NULL, 1, 'Diretor', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(13, 2, 14, '2025-10-01', NULL, 1, 'SecretÃ¡rio Escolar', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(14, 2, 14, '2025-10-01', NULL, 1, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(15, 2, 14, '2025-10-01', NULL, 1, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(16, 1, 14, '2025-10-01', NULL, 1, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(17, 2, 14, '2025-10-01', NULL, 1, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(19, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(20, 1, 4, '2025-10-13', NULL, 1, 'Vice-Diretor', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(21, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(22, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(23, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(24, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(27, 3, 22, '2025-12-10', NULL, 1, NULL, NULL, '2025-12-10 18:54:53', '2025-12-10 18:54:53', NULL),
(29, 3, 25, '2025-12-10', NULL, 1, NULL, NULL, '2025-12-10 19:09:26', '2025-12-10 19:09:26', NULL),
(31, 3, 17, '2025-11-30', NULL, 1, 'Diretor', NULL, '2025-12-11 15:03:58', '2025-12-11 15:03:58', NULL),
(33, 1, 3, '2025-10-03', NULL, 1, NULL, NULL, '2025-12-14 05:16:36', '2025-12-14 05:16:36', NULL),
(34, 2, 16, '2025-10-01', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-12-14 05:19:43', '2025-12-14 05:19:43', NULL),
(36, 3, 27, '2025-12-16', NULL, 1, NULL, NULL, '2025-12-16 16:28:14', '2025-12-16 16:28:14', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `habilidades_bncc`
--

CREATE TABLE `habilidades_bncc` (
  `id` int(11) NOT NULL,
  `codigo_bncc` varchar(20) NOT NULL,
  `etapa` varchar(50) NOT NULL,
  `componente` varchar(50) NOT NULL,
  `ano_inicio` tinyint(4) NOT NULL,
  `ano_fim` tinyint(4) NOT NULL,
  `descricao` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `habilidades_bncc`
--

INSERT INTO `habilidades_bncc` (`id`, `codigo_bncc`, `etapa`, `componente`, `ano_inicio`, `ano_fim`, `descricao`, `created_at`, `updated_at`) VALUES
(1, 'EI01TS01', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Explorar sons produzidos com o próprio corpo e com objetos do ambiente.', '2025-12-15 21:38:45', '2025-12-15 21:38:45'),
(2, 'EI02TS01', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Criar sons com materiais, objetos e instrumentos musicais, para acompanhar diversos ritmos de música.', '2025-12-15 21:38:45', '2025-12-15 21:38:45'),
(3, 'EI03TS01', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Utilizar sons produzidos por materiais, objetos e instrumentos musicais durante brincadeiras de faz de conta, encenações, criações musicais, festas.', '2025-12-15 21:38:45', '2025-12-15 21:38:45'),
(4, 'EI01TS02', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Traçar marcas gráficas, em diferentes suportes, usando instrumentos riscantes e tintas.', '2025-12-15 21:38:45', '2025-12-15 21:38:45'),
(5, 'EI02TS02', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Utilizar materiais variados com possibilidades de manipulação (argila, massa de modelar), explorando cores, texturas, superfícies, planos, formas e volumes ao criar objetos tridimensionais.', '2025-12-15 21:38:45', '2025-12-15 21:38:45'),
(6, 'EI03TS02', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Expressar-se livremente por meio de desenho, pintura, colagem, dobradura e escultura, criando produções bidimensionais e tridimensionais.', '2025-12-15 21:38:45', '2025-12-15 21:38:45'),
(7, 'EI01TS03', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Explorar diferentes fontes sonoras e materiais para acompanhar brincadeiras cantadas, canções, músicas e melodias.', '2025-12-15 21:38:45', '2025-12-15 21:38:45'),
(8, 'EI02TS03', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Utilizar diferentes fontes sonoras disponíveis no ambiente em brincadeiras cantadas, canções, músicas e melodias.', '2025-12-15 21:38:45', '2025-12-15 21:38:45'),
(9, 'EI03TS03', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Reconhecer as qualidades do som (intensidade, duração, altura e timbre), utilizando-as em suas produções sonoras e ao ouvir músicas e sons.', '2025-12-15 21:38:45', '2025-12-15 21:38:45'),
(10, 'EI01EO01', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Perceber que suas ações têm efeitos nas outras crianças e nos adultos.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(11, 'EI02EO01', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Demonstrar atitudes de cuidado e solidariedade na interação com crianças e adultos.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(12, 'EI03EO01', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Demonstrar empatia pelos outros, percebendo que as pessoas têm diferentes sentimentos, necessidades e maneiras de pensar e agir.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(13, 'EI01EO02', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Perceber as possibilidades e os limites de seu corpo nas brincadeiras e interações das quais participa.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(14, 'EI02EO02', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Demonstrar imagem positiva de si e confiança em sua capacidade para enfrentar dificuldades e desafios.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(15, 'EI03EO02', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Agir de maneira independente, com confiança em suas capacidades, reconhecendo suas conquistas e limitações.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(16, 'EI01EO03', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Interagir com crianças da mesma faixa etária e adultos ao explorar espaços, materiais, objetos, brinquedos.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(17, 'EI02EO03', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Compartilhar os objetos e os espaços com crianças da mesma faixa etária e adultos.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(18, 'EI03EO03', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Ampliar as relações interpessoais, desenvolvendo atitudes de participação e cooperação.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(19, 'EI01EO04', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Comunicar necessidades, desejos e emoções, utilizando gestos, balbucios, palavras.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(20, 'EI02EO04', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Comunicar-se com os colegas e os adultos, buscando compreendê-los e fazendo-se compreender.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(21, 'EI03EO04', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Comunicar suas ideias e sentimentos a pessoas e grupos diversos.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(22, 'EI01EO05', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Reconhecer seu corpo e expressar suas sensações em momentos de alimentação, higiene, brincadeira e descanso.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(23, 'EI02EO05', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Perceber que as pessoas têm características físicas diferentes, respeitando essas diferenças.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(24, 'EI03EO05', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Demonstrar valorização das características de seu corpo e respeitar as características dos outros (crianças e adultos) com os quais convive.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(25, 'EI01EO06', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Interagir com outras crianças da mesma faixa etária e adultos, adaptando-se ao convívio social.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(26, 'EI02EO06', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Respeitar regras básicas de convívio social nas interações e brincadeiras.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(27, 'EI03EO06', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Manifestar interesse e respeito por diferentes culturas e modos de vida.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(28, 'EI02EO07', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Resolver conflitos nas interações e brincadeiras, com a orientação de um adulto.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(29, 'EI03EO07', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Usar estratégias pautadas no respeito mútuo para lidar com conflitos nas interações com crianças e adultos.', '2025-12-15 21:38:51', '2025-12-15 21:38:51'),
(30, 'EI01CG01', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Movimentar as partes do corpo para exprimir corporalmente emoções, necessidades e desejos.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(31, 'EI02CG01', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Apropriar-se de gestos e movimentos de sua cultura no cuidado de si e nos jogos e brincadeiras.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(32, 'EI03CG01', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Criar com o corpo formas diversificadas de expressão de sentimentos, sensações e emoções, tanto nas situações do cotidiano quanto em brincadeiras, dança, teatro, música.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(33, 'EI01CG02', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Experimentar as possibilidades corporais nas brincadeiras e interações em ambientes acolhedores e desafiantes.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(34, 'EI02CG02', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Deslocar seu corpo no espaço, orientando-se por noções como em frente, atrás, no alto, embaixo, dentro, fora etc., ao se envolver em brincadeiras e atividades de diferentes naturezas.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(35, 'EI03CG02', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Demonstrar controle e adequação do uso de seu corpo em brincadeiras e jogos, escuta e reconto de histórias, atividades artísticas, entre outras possibilidades.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(36, 'EI01CG03', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Imitar gestos e movimentos de outras crianças, adultos e animais.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(37, 'EI02CG03', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Explorar formas de deslocamento no espaço (pular, saltar, dançar), combinando movimentos e seguindo orientações.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(38, 'EI03CG03', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Criar movimentos, gestos, olhares e mímicas em brincadeiras, jogos e atividades artísticas como dança, teatro e música.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(39, 'EI01CG04', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Participar do cuidado do seu corpo e da promoção do seu bem-estar.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(40, 'EI02CG04', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Demonstrar progressiva independência no cuidado do seu corpo.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(41, 'EI03CG04', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Adotar hábitos de autocuidado relacionados a higiene, alimentação, conforto e aparência.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(42, 'EI01CG05', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Utilizar os movimentos de preensão, encaixe e lançamento, ampliando suas possibilidades de manuseio de diferentes materiais e objetos.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(43, 'EI02CG05', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Desenvolver progressivamente as habilidades manuais, adquirindo controle para desenhar, pintar, rasgar, folhear, entre outros.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(44, 'EI03CG05', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Coordenar suas habilidades manuais no atendimento adequado a seus interesses e necessidades em situações diversas.', '2025-12-15 21:41:35', '2025-12-15 21:41:35'),
(45, 'EI01EF01', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Reconhecer quando é chamado por seu nome e reconhecer os nomes de pessoas com quem convive.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(46, 'EI02EF01', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Dialogar com crianças e adultos, expressando seus desejos, necessidades, sentimentos e opiniões.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(47, 'EI03EF01', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Expressar ideias, desejos e sentimentos sobre suas vivências, por meio da linguagem oral e escrita (escrita espontânea), de fotos, desenhos e outras formas de expressão.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(48, 'EI01EF02', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Demonstrar interesse ao ouvir a leitura de poemas e a apresentação de músicas.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(49, 'EI02EF02', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Identificar e criar diferentes sons e reconhecer rimas e aliterações em cantigas de roda e textos poéticos.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(50, 'EI03EF02', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Inventar brincadeiras cantadas, poemas e canções, criando rimas, aliterações e ritmos.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(51, 'EI01EF03', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Demonstrar interesse ao ouvir histórias lidas ou contadas, observando ilustrações e os movimentos de leitura do adulto-leitor (modo de segurar o portador e de virar as páginas).', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(52, 'EI02EF03', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Demonstrar interesse e atenção ao ouvir a leitura de histórias e outros textos, diferenciando escrita de ilustrações, e acompanhando, com orientação do adulto-leitor, a direção da leitura (de cima para baixo, da esquerda para a direita).', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(53, 'EI03EF03', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Escolher e folhear livros, procurando orientar-se por temas e ilustrações e tentando identificar palavras conhecidas.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(54, 'EI01EF04', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Reconhecer elementos das ilustrações de histórias, apontando-os, a pedido do adulto-leitor.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(55, 'EI02EF04', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Formular e responder perguntas sobre fatos da história narrada, identificando cenários, personagens e principais acontecimentos.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(56, 'EI03EF04', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Recontar histórias ouvidas e planejar coletivamente roteiros de vídeos e de encenações, definindo os contextos, os personagens, a estrutura da história.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(57, 'EI01EF05', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Imitar as variações de entonação e gestos realizados pelos adultos, ao ler histórias e ao cantar.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(58, 'EI02EF05', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Relatar experiências e fatos acontecidos, histórias ouvidas, filmes ou peças teatrais assistidos etc.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(59, 'EI03EF05', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Recontar histórias ouvidas para produção de reconto escrito, tendo o professor como escriba.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(60, 'EI01EF06', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Comunicar-se com outras pessoas usando movimentos, gestos, balbucios, fala e outras formas de expressão.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(61, 'EI02EF06', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Criar e contar histórias oralmente, com base em imagens ou temas sugeridos.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(62, 'EI03EF06', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Produzir suas próprias histórias orais e escritas (escrita espontânea), em situações com função social significativa.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(63, 'EI01EF07', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Conhecer e manipular materiais impressos e audiovisuais em diferentes portadores (livro, revista, gibi, jornal, cartaz, CD, tablet etc.).', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(64, 'EI02EF07', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Manusear diferentes portadores textuais, demonstrando reconhecer seus usos sociais.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(65, 'EI03EF07', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Levantar hipóteses sobre gêneros textuais veiculados em portadores conhecidos, recorrendo a estratégias de observação gráfica e/ou de leitura.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(66, 'EI01EF08', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Participar de situações de escuta de textos em diferentes gêneros textuais (poemas, fábulas, contos, receitas, quadrinhos, anúncios etc.).', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(67, 'EI02EF08', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Manipular textos e participar de situações de escuta para ampliar seu contato com diferentes gêneros textuais (parlendas, histórias de aventura, tirinhas, cartazes de sala, cardápios, notícias etc.).', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(68, 'EI03EF08', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Selecionar livros e textos de gêneros conhecidos para a leitura de um adulto e/ou para sua própria leitura (partindo de seu repertório sobre esses textos, como a recuperação pela memória, pela leitura das ilustrações etc.).', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(69, 'EI01EF09', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Conhecer e manipular diferentes instrumentos e suportes de escrita.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(70, 'EI02EF09', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Manusear diferentes instrumentos e suportes de escrita para desenhar, traçar letras e outros sinais gráficos.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(71, 'EI03EF09', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Levantar hipóteses em relação à linguagem escrita, realizando registros de palavras e textos, por meio de escrita espontânea.', '2025-12-15 21:42:10', '2025-12-15 21:42:10'),
(72, 'EI01ET01', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Explorar e descobrir as propriedades de objetos e materiais (odor, cor, sabor, temperatura).', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(73, 'EI02ET01', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Explorar e descrever semelhanças e diferenças entre as características e propriedades dos objetos (textura, massa, tamanho).', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(74, 'EI03ET01', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Estabelecer relações de comparação entre objetos, observando suas propriedades.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(75, 'EI01ET02', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Explorar relações de causa e efeito (transbordar, tingir, misturar, mover e remover etc.) na interação com o mundo físico.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(76, 'EI02ET02', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Observar, relatar e descrever incidentes do cotidiano e fenômenos naturais (luz solar, vento, chuva etc.).', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(77, 'EI03ET02', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Observar e descrever mudanças em diferentes materiais, resultantes de ações sobre eles, em experimentos envolvendo fenômenos naturais e artificiais.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(78, 'EI01ET03', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Explorar o ambiente pela ação e observação, manipulando, experimentando e fazendo descobertas.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(79, 'EI02ET03', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Compartilhar, com outras crianças, situações de cuidado de plantas e animais nos espaços da instituição e fora dela.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(80, 'EI03ET03', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Identificar e selecionar fontes de informações, para responder a questões sobre a natureza, seus fenômenos, sua conservação.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(81, 'EI01ET04', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Manipular, experimentar, arrumar e explorar o espaço por meio de experiências de deslocamentos de si e dos objetos.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(82, 'EI02ET04', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Identificar relações espaciais (dentro e fora, em cima, embaixo, acima, abaixo, entre e do lado) e temporais (antes, durante e depois).', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(83, 'EI03ET04', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Registrar observações, manipulações e medidas, usando múltiplas linguagens (desenho, registro por números ou escrita espontânea), em diferentes suportes.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(84, 'EI01ET05', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Manipular materiais diversos e variados para comparar as diferenças e semelhanças entre eles.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(85, 'EI02ET05', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Classificar objetos, considerando determinado atributo (tamanho, peso, cor, forma etc.).', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(86, 'EI03ET05', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Classificar objetos e figuras de acordo com suas semelhanças e diferenças.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(87, 'EI01ET06', 'Educação Infantil', 'Educação Infantil', 1, 1, 'Vivenciar diferentes ritmos, velocidades e fluxos nas interações e brincadeiras (em danças, balanços, escorregadores etc.).', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(88, 'EI02ET06', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Utilizar conceitos básicos de tempo (agora, antes, durante, depois, ontem, hoje, amanhã, lento, rápido, depressa, devagar).', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(89, 'EI03ET06', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Relatar fatos importantes sobre seu nascimento e desenvolvimento, a história dos seus familiares e da sua comunidade.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(90, 'EI02ET07', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Contar oralmente objetos, pessoas, livros etc., em contextos diversos.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(91, 'EI03ET07', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Relacionar números às suas respectivas quantidades e identificar o antes, o depois e o entre em uma sequência.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(92, 'EI02ET08', 'Educação Infantil', 'Educação Infantil', 2, 2, 'Registrar com números a quantidade de crianças (meninas e meninos, presentes e ausentes) e a quantidade de objetos da mesma natureza (bonecas, bolas, livros etc.).', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(93, 'EI03ET08', 'Educação Infantil', 'Educação Infantil', 3, 3, 'Expressar medidas (peso, altura etc.), construindo gráficos básicos.', '2025-12-15 21:42:17', '2025-12-15 21:42:17'),
(94, 'EF15LP01', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Identificar a função social de textos que circulam em campos da vida social dos quais participa cotidianamente (a casa, a rua, a comunidade, a escola) e nas mídias impressa, de massa e digital, reconhecendo para que foram produzidos, onde circulam, quem os produziu e a quem se destinam.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(95, 'EF15LP02', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Estabelecer expectativas em relação ao texto que vai ler (pressuposições antecipadoras dos sentidos, da forma e da função social do texto), apoiando-se em seus conhecimentos prévios sobre as condições de produção e recepção desse texto, o gênero, o suporte e o universo temático, bem como sobre saliências textuais, recursos gráficos, imagens, dados da própria obra (índice, prefácio etc.), confirmando antecipações e inferências realizadas antes e durante a leitura de textos, checando a adequação das hipóteses realizadas.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(96, 'EF15LP03', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Localizar informações explícitas em textos.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(97, 'EF15LP04', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Identificar o efeito de sentido produzido pelo uso de recursos expressivos gráfico-visuais em textos multissemióticos.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(98, 'EF15LP05', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Planejar, com a ajuda do professor, o texto que será produzido, considerando a situação comunicativa, os interlocutores (quem escreve/para quem escreve); a finalidade ou o propósito (escrever para quê); a circulação (onde o texto vai circular); o suporte (qual é o portador do texto); a linguagem, organização e forma do texto e seu tema, pesquisando em meios impressos ou digitais, sempre que for preciso, informações necessárias à produção do texto, organizando em tópicos os dados e as fontes pesquisadas.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(99, 'EF15LP06', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Reler e revisar o texto produzido com a ajuda do professor e a colaboração dos colegas, para corrigi-lo e aprimorá-lo, fazendo cortes, acréscimos, reformulações, correções de ortografia e pontuação.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(100, 'EF15LP07', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Editar a versão final do texto, em colaboração com os colegas e com a ajuda do professor, ilustrando, quando for o caso, em suporte adequado, manual ou digital.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(101, 'EF15LP08', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Utilizar software, inclusive programas de edição de texto, para editar e publicar os textos produzidos, explorando os recursos multissemióticos disponíveis.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(102, 'EF15LP09', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Expressar-se em situações de intercâmbio oral com clareza, preocupando-se em ser compreendido pelo interlocutor e usando a palavra com tom de voz audível, boa articulação e ritmo adequado.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(103, 'EF15LP10', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Escutar, com atenção, falas de professores e colegas, formulando perguntas pertinentes ao tema e solicitando esclarecimentos sempre que necessário.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(104, 'EF15LP11', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Reconhecer características da conversação espontânea presencial, respeitando os turnos de fala, selecionando e utilizando, durante a conversação, formas de tratamento adequadas, de acordo com a situação e a posição do interlocutor.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(105, 'EF15LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Atribuir significado a aspectos não linguísticos (paralinguísticos) observados na fala, como direção do olhar, riso, gestos, movimentos da cabeça (de concordância ou discordância), expressão corporal, tom de voz.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(106, 'EF15LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Identificar finalidades da interação oral em diferentes contextos comunicativos (solicitar informações, apresentar opiniões, informar, relatar experiências etc.).', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(107, 'EF15LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Construir o sentido de histórias em quadrinhos e tirinhas, relacionando imagens e palavras e interpretando recursos gráficos (tipos de balões, de letras, onomatopeias).', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(108, 'EF15LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Reconhecer que os textos literários fazem parte do mundo do imaginário e apresentam uma dimensão lúdica, de encantamento, valorizando-os, em sua diversidade cultural, como patrimônio artístico da humanidade.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(109, 'EF15LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Ler e compreender, em colaboração com os colegas e com a ajuda do professor e, mais tarde, de maneira autônoma, textos narrativos de maior porte como contos (populares, de fadas, acumulativos, de assombração etc.) e crônicas.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(110, 'EF15LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Apreciar poemas visuais e concretos, observando efeitos de sentido criados pelo formato do texto na página, distribuição e diagramação das letras, pelas ilustrações e por outros efeitos visuais.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(111, 'EF15LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Relacionar texto com ilustrações e outros recursos gráficos.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(112, 'EF15LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Recontar oralmente, com e sem apoio de imagem, textos literários lidos pelo professor.', '2025-12-15 21:55:23', '2025-12-15 21:55:23'),
(113, 'EF15LP20', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Apreciar textos literários, observando rimas, sonoridades, jogos de palavras e efeitos de sentido, em poemas, parlendas, cantigas, quadrinhas, trava-línguas, adivinhas, entre outros.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(114, 'EF15LP21', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Ler e compreender, em colaboração com os colegas e com a ajuda do professor e, gradativamente, de maneira autônoma, textos do campo da vida cotidiana, do campo artístico-literário e do campo das práticas de estudo e pesquisa.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(115, 'EF15LP22', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Localizar informações explícitas em textos de diferentes gêneros.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(116, 'EF15LP23', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Inferir informações implícitas em textos de diferentes gêneros.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(117, 'EF15LP24', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Identificar o tema de textos.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(118, 'EF15LP25', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Reconhecer o sentido de palavras ou expressões em textos, considerando o contexto de uso.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(119, 'EF15LP26', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Identificar efeitos de sentido decorrentes do uso de recursos linguísticos e gráficos em textos.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(120, 'EF15LP27', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Planejar e produzir, com a ajuda do professor, textos do campo da vida cotidiana, considerando a situação comunicativa, os interlocutores, a finalidade, a circulação e o suporte.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(121, 'EF15LP28', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Produzir textos do campo artístico-literário, considerando a situação comunicativa e os efeitos de sentido pretendidos.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(122, 'EF15LP29', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Revisar e editar textos produzidos, com a ajuda do professor e dos colegas, considerando aspectos discursivos, linguísticos e gráficos.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(123, 'EF15LP30', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Utilizar recursos tecnológicos para produzir, revisar e editar textos.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(124, 'EF15LP31', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Participar de situações de intercâmbio oral, respeitando turnos de fala e adequando a linguagem à situação comunicativa.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(125, 'EF15LP32', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Ouvir com atenção textos orais, identificando informações principais e detalhes relevantes.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(126, 'EF15LP33', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Relatar experiências pessoais e acontecimentos, organizando ideias de forma coerente.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(127, 'EF15LP34', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Apresentar oralmente temas estudados, com apoio de recursos visuais.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(128, 'EF15LP35', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Reconhecer características de gêneros textuais do campo jornalístico-midiático.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(129, 'EF15LP36', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Ler e compreender textos informativos e instrucionais.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(130, 'EF15LP37', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Identificar a finalidade de textos instrucionais e normativos.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(131, 'EF15LP38', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 5, 'Produzir textos instrucionais, considerando sequência lógica e clareza das informações.', '2025-12-15 21:55:44', '2025-12-15 21:55:44'),
(132, 'EF01LP01', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Reconhecer que textos são lidos e escritos da esquerda para a direita e de cima para baixo da página.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(133, 'EF01LP02', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Escrever, espontaneamente ou por ditado, palavras e frases de forma alfabética, usando letras ou grafemas que representem fonemas.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(134, 'EF01LP03', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Observar escritas convencionais, comparando-as às suas produções escritas.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(135, 'EF01LP04', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Distinguir as letras do alfabeto de outros sinais gráficos.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(136, 'EF01LP05', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Reconhecer o sistema de escrita alfabética como representação dos sons da fala.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(137, 'EF01LP06', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Segmentar oralmente palavras em sílabas.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(138, 'EF01LP07', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Identificar fonemas e sua representação por letras.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(139, 'EF01LP08', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Relacionar elementos sonoros (sílabas e fonemas) com sua representação escrita.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(140, 'EF01LP09', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Comparar palavras, identificando semelhanças e diferenças entre sons iniciais.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(141, 'EF01LP10', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Nomear as letras do alfabeto e recitá-lo na ordem correta.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(142, 'EF01LP11', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Conhecer e diferenciar letras em formato imprensa e cursiva, maiúsculas e minúsculas.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(143, 'EF01LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Reconhecer a separação das palavras por espaços em branco na escrita.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(144, 'EF01LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Comparar palavras, identificando semelhanças e diferenças entre sons mediais e finais.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(145, 'EF01LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Identificar sinais de pontuação como ponto final, interrogação e exclamação.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(146, 'EF01LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Agrupar palavras por semelhança de significado (sinônimos) e oposição de significado (antônimos).', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(147, 'EF01LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Ler e compreender quadras, parlendas, trava-línguas e outros textos do campo da vida cotidiana.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(148, 'EF01LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Planejar e produzir listas, bilhetes, convites e outros textos do campo da vida cotidiana.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(149, 'EF01LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Registrar cantigas, parlendas e textos curtos com a ajuda do professor.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(150, 'EF01LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Recitar textos orais com entonação adequada.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(151, 'EF01LP20', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Identificar e reproduzir formatação específica de gêneros do cotidiano.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(152, 'EF02LP01', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Utilizar grafia correta de palavras conhecidas e estruturas silábicas já dominadas.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(153, 'EF02LP02', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Segmentar palavras em sílabas e criar novas palavras por substituição ou remoção de sílabas.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(154, 'EF02LP03', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Ler e escrever palavras com correspondências regulares diretas e contextuais.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(155, 'EF02LP04', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Ler e escrever corretamente palavras com diferentes estruturas silábicas.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(156, 'EF02LP05', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Ler e escrever palavras com marcas de nasalidade.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(157, 'EF02LP06', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Reconhecer o princípio acrofônico nos nomes das letras.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(158, 'EF02LP07', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Escrever palavras, frases e textos curtos em letra cursiva e de imprensa.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(159, 'EF02LP08', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Segmentar corretamente palavras ao escrever frases e textos.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(160, 'EF02LP09', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Usar corretamente sinais de pontuação básicos.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(161, 'EF02LP10', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Identificar sinônimos e antônimos em textos.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(162, 'EF02LP11', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Formar aumentativos e diminutivos com sufixos.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(163, 'EF12LP01', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Ler palavras novas com precisão e palavras frequentes por memorização.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(164, 'EF12LP02', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Buscar, selecionar e ler textos com mediação do professor.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(165, 'EF12LP03', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Copiar textos breves respeitando suas características gráficas.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(166, 'EF12LP04', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Ler e compreender textos do campo da vida cotidiana.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(167, 'EF12LP05', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Planejar e produzir textos do campo artístico-literário.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(168, 'EF12LP06', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Produzir textos orais para circulação digital.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(169, 'EF12LP07', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Identificar rimas, aliterações e ritmo em textos orais.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(170, 'EF12LP08', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Ler e compreender textos do campo jornalístico infantil.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(171, 'EF12LP09', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Ler e compreender anúncios, slogans e campanhas de conscientização.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(172, 'EF12LP10', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Ler e compreender textos normativos e de atuação cidadã.', '2025-12-15 21:57:48', '2025-12-15 21:57:48'),
(173, 'EF35LP01', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Ler e compreender textos narrativos, identificando personagens, espaço, tempo e enredo.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(174, 'EF35LP02', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Inferir informações implícitas em textos de diferentes gêneros.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(175, 'EF35LP03', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Identificar o tema e a finalidade de textos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(176, 'EF35LP04', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Localizar informações explícitas em textos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(177, 'EF35LP05', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Identificar efeitos de sentido decorrentes do uso de recursos linguísticos e gráficos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(178, 'EF35LP06', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Reconhecer características de gêneros do campo jornalístico-midiático.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(179, 'EF35LP07', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Ler e compreender textos do campo das práticas de estudo e pesquisa.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(180, 'EF35LP08', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Utilizar estratégias de leitura para compreender textos longos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(181, 'EF35LP09', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Planejar e produzir textos considerando a situação comunicativa, os interlocutores e a finalidade.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(182, 'EF35LP10', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Produzir textos narrativos com coerência e coesão.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(183, 'EF35LP11', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Revisar e editar textos com apoio do professor e dos colegas.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(184, 'EF35LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Utilizar recursos tecnológicos na produção e revisão de textos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(185, 'EF35LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Participar de situações de intercâmbio oral, respeitando turnos de fala.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(186, 'EF35LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Apresentar oralmente temas estudados com apoio de recursos visuais.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(187, 'EF35LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Ouvir com atenção textos orais, identificando informações principais.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(188, 'EF35LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Reconhecer o uso de tempos verbais em textos narrativos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(189, 'EF35LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Identificar relações de causa e consequência em textos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(190, 'EF35LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Utilizar pontuação adequada na produção textual.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(191, 'EF35LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Reconhecer e empregar sinônimos e antônimos em textos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(192, 'EF35LP20', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Produzir textos do campo artístico-literário.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(193, 'EF35LP21', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Ler e apreciar poemas, identificando rimas e efeitos sonoros.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(194, 'EF35LP22', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Produzir textos instrucionais e normativos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(195, 'EF35LP23', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Ler e compreender textos instrucionais e normativos.', '2025-12-15 21:58:16', '2025-12-15 21:58:16'),
(196, 'EF01MA01', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 5, 'Contar, ordenar, comparar e representar números de até 100, utilizando diferentes estratégias de cálculo.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(197, 'EF01MA02', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 5, 'Utilizar as unidades de medida de comprimento e massa.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(198, 'EF01MA03', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 5, 'Reconhecer e escrever números em diferentes formas: algarismos, extenso, ordinais e romanos.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(199, 'EF02MA01', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 2, 5, 'Resolver problemas envolvendo adição e subtração de números de até 100.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(200, 'EF02MA02', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 2, 5, 'Utilizar a multiplicação e a divisão em situações do cotidiano.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(201, 'EF02MA03', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 2, 5, 'Compreender o conceito de fração e reconhecer frações em situações cotidianas.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(202, 'EF03MA01', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 3, 5, 'Resolver problemas de adição e subtração com até três parcelas, utilizando diferentes estratégias de cálculo.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(203, 'EF03MA02', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 3, 5, 'Realizar multiplicações e divisões de números com 1 algarismo.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(204, 'EF03MA03', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 3, 5, 'Compreender e resolver problemas envolvendo frações.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(205, 'EF04MA01', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 4, 5, 'Resolver problemas envolvendo operações de adição, subtração, multiplicação e divisão.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(206, 'EF04MA02', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 4, 5, 'Compreender a noção de percentagem em situações cotidianas.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(207, 'EF04MA03', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 4, 5, 'Utilizar diferentes estratégias para resolver problemas envolvendo a multiplicação de números de até 2 algarismos.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(208, 'EF05MA01', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Resolver problemas envolvendo as quatro operações fundamentais (adição, subtração, multiplicação e divisão).', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(209, 'EF05MA02', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Compreender a noção de divisão de números naturais por 10, 100 e 1000.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(210, 'EF05MA03', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Desenvolver o cálculo mental para a resolução de problemas envolvendo as operações básicas.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(211, 'EF15MA01', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Interpretar e resolver problemas de situações cotidianas que envolvam medidas de tempo.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(212, 'EF15MA02', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Estudar a geometria de figuras planas, reconhecendo suas propriedades.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(213, 'EF15MA03', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Compreender e resolver problemas envolvendo a moeda e a utilização de troco.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(214, 'EF15MA04', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Organizar dados e interpretar gráficos de barras, pictogramas e tabelas.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(215, 'EF15MA05', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Compreender e usar a adição e subtração de frações equivalentes e ordinais.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(216, 'EF15MA06', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Compreender os conceitos de área e perímetro em figuras planas.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(217, 'EF15MA07', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Resolver problemas de multiplicação e divisão de números de até 3 algarismos.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(218, 'EF15MA08', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Resolver problemas envolvendo porcentagens em situações do cotidiano.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(219, 'EF15MA09', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Estudar e representar ângulos e figuras geométricas no plano cartesiano.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(220, 'EF15MA10', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Interpretar e resolver problemas envolvendo as operações de adição e subtração de frações e decimais.', '2025-12-15 21:59:43', '2025-12-15 21:59:43'),
(221, 'EF01CI01', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Comparar características de diferentes materiais presentes no cotidiano, como dureza, transparência, flexibilidade e resistência.', '2025-12-15 22:01:07', '2025-12-15 22:01:07');
INSERT INTO `habilidades_bncc` (`id`, `codigo_bncc`, `etapa`, `componente`, `ano_inicio`, `ano_fim`, `descricao`, `created_at`, `updated_at`) VALUES
(222, 'EF01CI02', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Identificar e comparar características de plantas e animais em diferentes ambientes.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(223, 'EF01CI03', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Reconhecer a importância da água para os seres vivos e para o ambiente.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(224, 'EF02CI01', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 2, 5, 'Identificar necessidades básicas dos seres vivos e relações de dependência entre eles e o ambiente.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(225, 'EF02CI02', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 2, 5, 'Reconhecer mudanças ocorridas em plantas e animais ao longo do tempo.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(226, 'EF02CI03', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 2, 5, 'Investigar hábitos de higiene e cuidados com o corpo para a promoção da saúde.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(227, 'EF03CI01', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 3, 5, 'Identificar características da Terra, do Sol e da Lua, reconhecendo sua importância para a vida.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(228, 'EF03CI02', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 3, 5, 'Identificar diferentes formas de utilização dos recursos naturais e a necessidade de seu uso consciente.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(229, 'EF03CI03', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 3, 5, 'Reconhecer características dos estados físicos da água e suas transformações.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(230, 'EF04CI01', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 4, 5, 'Analisar cadeias alimentares simples e reconhecer a importância de cada ser vivo.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(231, 'EF04CI02', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 4, 5, 'Identificar mudanças provocadas pela ação humana no ambiente.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(232, 'EF04CI03', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 4, 5, 'Reconhecer formas de energia presentes no cotidiano e suas transformações.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(233, 'EF05CI01', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 5, 5, 'Compreender o funcionamento do corpo humano e a importância de hábitos saudáveis.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(234, 'EF05CI02', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 5, 5, 'Analisar o sistema digestório e sua relação com a alimentação.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(235, 'EF05CI03', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 5, 5, 'Reconhecer a importância das vacinas e da prevenção de doenças.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(236, 'EF15CI01', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Investigar propriedades dos materiais e suas aplicações no cotidiano.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(237, 'EF15CI02', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Analisar transformações da matéria em situações do dia a dia.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(238, 'EF15CI03', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Identificar fontes de energia renováveis e não renováveis.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(239, 'EF15CI04', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Compreender relações entre os seres vivos e o ambiente.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(240, 'EF15CI05', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Analisar a importância da preservação ambiental.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(241, 'EF15CI06', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Reconhecer ciclos naturais, como o ciclo da água.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(242, 'EF15CI07', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Identificar mudanças de estado físico da matéria.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(243, 'EF15CI08', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Reconhecer a importância da tecnologia no desenvolvimento da sociedade.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(244, 'EF15CI09', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Analisar hábitos de consumo e seus impactos no meio ambiente.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(245, 'EF15CI10', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 5, 'Desenvolver atitudes responsáveis em relação ao cuidado com o meio ambiente.', '2025-12-15 22:01:07', '2025-12-15 22:01:07'),
(246, 'EF01GE01', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Reconhecer e descrever características do lugar onde vive, considerando elementos naturais e construídos.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(247, 'EF01GE02', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Identificar diferentes formas de uso e ocupação dos espaços próximos.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(248, 'EF01GE03', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Reconhecer mudanças ocorridas nos lugares ao longo do tempo.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(249, 'EF02GE01', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 5, 'Identificar relações de convivência e trabalho no lugar onde vive.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(250, 'EF02GE02', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 5, 'Reconhecer diferentes formas de representação dos lugares (desenhos, mapas, croquis).', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(251, 'EF02GE03', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 5, 'Identificar elementos da paisagem natural e cultural.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(252, 'EF03GE01', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 5, 'Analisar modos de vida de diferentes grupos sociais em distintos lugares.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(253, 'EF03GE02', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 5, 'Identificar impactos das ações humanas na paisagem.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(254, 'EF03GE03', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 5, 'Utilizar noções básicas de orientação espacial (frente, trás, direita, esquerda).', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(255, 'EF04GE01', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 5, 'Analisar a organização do espaço urbano e rural.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(256, 'EF04GE02', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 5, 'Reconhecer a importância do trabalho humano na transformação do espaço.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(257, 'EF04GE03', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 5, 'Identificar problemas ambientais locais e possíveis soluções.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(258, 'EF05GE01', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 5, 5, 'Analisar a formação do território brasileiro e suas regiões.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(259, 'EF05GE02', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 5, 5, 'Reconhecer a diversidade cultural brasileira.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(260, 'EF05GE03', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 5, 5, 'Identificar atividades econômicas presentes no espaço brasileiro.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(261, 'EF15GE01', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Compreender a relação entre sociedade e natureza.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(262, 'EF15GE02', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Analisar transformações dos espaços ao longo do tempo.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(263, 'EF15GE03', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Reconhecer a importância da preservação ambiental.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(264, 'EF15GE04', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Utilizar mapas e outras representações para localizar lugares.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(265, 'EF15GE05', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Identificar diferentes paisagens e suas características.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(266, 'EF15GE06', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Analisar problemas socioambientais e suas consequências.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(267, 'EF15GE07', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Reconhecer a importância do consumo consciente.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(268, 'EF15GE08', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Identificar formas de organização social nos lugares.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(269, 'EF15GE09', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Analisar fluxos de pessoas, mercadorias e informações.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(270, 'EF15GE10', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 5, 'Desenvolver atitudes responsáveis em relação ao espaço em que vive.', '2025-12-15 22:03:08', '2025-12-15 22:03:08'),
(271, 'EF01HI01', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Reconhecer aspectos da própria história e da história de sua família, identificando mudanças e permanências.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(272, 'EF01HI02', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Identificar diferentes formas de registrar o tempo e a memória (fotos, relatos orais, objetos).', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(273, 'EF01HI03', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Reconhecer acontecimentos do cotidiano como parte da história pessoal e coletiva.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(274, 'EF02HI01', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 5, 'Identificar diferentes grupos sociais presentes na comunidade e suas formas de organização.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(275, 'EF02HI02', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 5, 'Reconhecer semelhanças e diferenças entre modos de vida do passado e do presente.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(276, 'EF02HI03', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 5, 'Identificar tradições e costumes presentes na comunidade.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(277, 'EF03HI01', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 5, 'Analisar a história da cidade ou região, considerando diferentes tempos e sujeitos históricos.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(278, 'EF03HI02', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 5, 'Reconhecer a importância das fontes históricas para o estudo do passado.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(279, 'EF03HI03', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 5, 'Identificar povos e culturas que contribuíram para a formação da sociedade brasileira.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(280, 'EF04HI01', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 5, 'Analisar a organização da sociedade brasileira ao longo do tempo.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(281, 'EF04HI02', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 5, 'Reconhecer conflitos e formas de resistência presentes na história.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(282, 'EF04HI03', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 5, 'Identificar processos de mudança e permanência na história do Brasil.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(283, 'EF05HI01', 'Ensino Fundamental – Anos Iniciais', 'História', 5, 5, 'Analisar a formação do povo brasileiro, considerando diferentes grupos étnicos e culturais.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(284, 'EF05HI02', 'Ensino Fundamental – Anos Iniciais', 'História', 5, 5, 'Reconhecer direitos e deveres das crianças como sujeitos históricos.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(285, 'EF05HI03', 'Ensino Fundamental – Anos Iniciais', 'História', 5, 5, 'Identificar acontecimentos importantes da história do Brasil.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(286, 'EF15HI01', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Compreender o conceito de tempo histórico e suas diferentes formas de registro.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(287, 'EF15HI02', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Analisar relações de causa e consequência em acontecimentos históricos.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(288, 'EF15HI03', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Reconhecer diferentes sujeitos históricos e suas experiências.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(289, 'EF15HI04', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Identificar transformações ocorridas nas sociedades ao longo do tempo.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(290, 'EF15HI05', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Valorizar a diversidade cultural presente na sociedade brasileira.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(291, 'EF15HI06', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Reconhecer a importância da memória e do patrimônio histórico-cultural.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(292, 'EF15HI07', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Analisar diferentes versões sobre um mesmo fato histórico.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(293, 'EF15HI08', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Reconhecer formas de organização política e social ao longo da história.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(294, 'EF15HI09', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Identificar lutas e conquistas de direitos ao longo da história.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(295, 'EF15HI10', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 5, 'Desenvolver atitudes de respeito à diversidade e à democracia.', '2025-12-15 22:03:40', '2025-12-15 22:03:40'),
(296, 'EF01AR01', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Explorar e reconhecer elementos das artes visuais (linha, forma, cor, textura) em produções próprias e de outros.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(297, 'EF01AR02', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Experimentar diferentes materiais, instrumentos e técnicas nas produções artísticas.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(298, 'EF01AR03', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Reconhecer e valorizar manifestações artísticas presentes no cotidiano.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(299, 'EF02AR01', 'Ensino Fundamental – Anos Iniciais', 'Arte', 2, 5, 'Criar produções artísticas bidimensionais e tridimensionais a partir de diferentes estímulos.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(300, 'EF02AR02', 'Ensino Fundamental – Anos Iniciais', 'Arte', 2, 5, 'Explorar sons, ritmos e movimentos em brincadeiras, jogos e criações artísticas.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(301, 'EF02AR03', 'Ensino Fundamental – Anos Iniciais', 'Arte', 2, 5, 'Reconhecer diferentes linguagens artísticas: artes visuais, dança, música e teatro.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(302, 'EF03AR01', 'Ensino Fundamental – Anos Iniciais', 'Arte', 3, 5, 'Produzir trabalhos artísticos utilizando diferentes linguagens e materiais.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(303, 'EF03AR02', 'Ensino Fundamental – Anos Iniciais', 'Arte', 3, 5, 'Analisar e apreciar produções artísticas próprias e de colegas.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(304, 'EF03AR03', 'Ensino Fundamental – Anos Iniciais', 'Arte', 3, 5, 'Reconhecer manifestações artísticas de diferentes culturas.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(305, 'EF04AR01', 'Ensino Fundamental – Anos Iniciais', 'Arte', 4, 5, 'Criar produções artísticas inspiradas em diferentes contextos culturais.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(306, 'EF04AR02', 'Ensino Fundamental – Anos Iniciais', 'Arte', 4, 5, 'Experimentar movimentos corporais em danças e jogos expressivos.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(307, 'EF04AR03', 'Ensino Fundamental – Anos Iniciais', 'Arte', 4, 5, 'Explorar a música por meio da escuta, criação e apreciação.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(308, 'EF05AR01', 'Ensino Fundamental – Anos Iniciais', 'Arte', 5, 5, 'Analisar e contextualizar produções artísticas de diferentes tempos e lugares.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(309, 'EF05AR02', 'Ensino Fundamental – Anos Iniciais', 'Arte', 5, 5, 'Produzir trabalhos artísticos integrando diferentes linguagens.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(310, 'EF05AR03', 'Ensino Fundamental – Anos Iniciais', 'Arte', 5, 5, 'Utilizar recursos tecnológicos na criação e apreciação artística.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(311, 'EF15AR01', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Explorar processos de criação artística de forma individual e coletiva.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(312, 'EF15AR02', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Valorizar a diversidade cultural por meio das manifestações artísticas.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(313, 'EF15AR03', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Analisar elementos constitutivos das diferentes linguagens artísticas.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(314, 'EF15AR04', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Expressar sentimentos, ideias e emoções por meio das artes.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(315, 'EF15AR05', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Participar de experiências artísticas coletivas.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(316, 'EF15AR06', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Reconhecer a arte como forma de comunicação e expressão.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(317, 'EF15AR07', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Experimentar práticas artísticas relacionadas ao cotidiano.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(318, 'EF15AR08', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Apreciar produções artísticas respeitando diferentes interpretações.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(319, 'EF15AR09', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Relacionar produções artísticas a contextos históricos e sociais.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(320, 'EF15AR10', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 5, 'Desenvolver atitudes de respeito e valorização da arte e da cultura.', '2025-12-15 22:04:10', '2025-12-15 22:04:10'),
(321, 'EF01EF01', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Vivenciar jogos e brincadeiras do contexto familiar e comunitário, respeitando regras simples.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(322, 'EF01EF02', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Explorar movimentos corporais básicos como correr, saltar, rolar e arremessar.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(323, 'EF01EF03', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Reconhecer o próprio corpo e suas possibilidades de movimento.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(324, 'EF02EF01', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 2, 5, 'Participar de jogos e brincadeiras respeitando regras e colegas.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(325, 'EF02EF02', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 2, 5, 'Experimentar diferentes formas de movimento em atividades rítmicas e expressivas.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(326, 'EF02EF03', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 2, 5, 'Identificar práticas corporais presentes na cultura local.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(327, 'EF03EF01', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Vivenciar jogos populares e tradicionais, reconhecendo sua importância cultural.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(328, 'EF03EF02', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Praticar atividades físicas respeitando limites corporais.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(329, 'EF03EF03', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Compreender a importância da atividade física para a saúde.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(330, 'EF04EF01', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 4, 5, 'Participar de esportes adaptados às possibilidades dos alunos.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(331, 'EF04EF02', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 4, 5, 'Experimentar diferentes modalidades esportivas de forma lúdica.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(332, 'EF04EF03', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 4, 5, 'Reconhecer atitudes de cooperação e respeito nas práticas corporais.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(333, 'EF05EF01', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 5, 5, 'Praticar esportes e jogos coletivos respeitando regras e estratégias.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(334, 'EF05EF02', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 5, 5, 'Analisar a importância do trabalho em equipe nas práticas corporais.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(335, 'EF05EF03', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 5, 5, 'Adotar atitudes de cuidado com o corpo e a saúde.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(336, 'EF15EF01', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Vivenciar práticas corporais como forma de lazer e socialização.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(337, 'EF15EF02', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Reconhecer a diversidade de práticas corporais presentes na sociedade.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(338, 'EF15EF03', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Experimentar atividades físicas respeitando diferenças individuais.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(339, 'EF15EF04', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Analisar atitudes de cooperação e solidariedade nas práticas corporais.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(340, 'EF15EF05', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Compreender a relação entre atividade física, saúde e qualidade de vida.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(341, 'EF15EF06', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Vivenciar práticas corporais de aventura de forma segura.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(342, 'EF15EF07', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Reconhecer regras e combinados em jogos e brincadeiras.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(343, 'EF15EF08', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Utilizar estratégias simples para resolver desafios motores.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(344, 'EF15EF09', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Valorizar o respeito mútuo nas práticas corporais.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(345, 'EF15EF10', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 5, 'Desenvolver atitudes éticas e responsáveis nas atividades físicas.', '2025-12-15 22:04:59', '2025-12-15 22:04:59'),
(346, 'EF67LP01', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Ler, compreender e interpretar textos de diferentes gêneros, identificando tema, tese e argumentos.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(347, 'EF67LP02', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Analisar efeitos de sentido decorrentes do uso de recursos linguísticos e discursivos.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(348, 'EF67LP03', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Identificar informações explícitas e implícitas em textos.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(349, 'EF67LP04', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Relacionar textos entre si, reconhecendo intertextualidade.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(350, 'EF67LP05', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Planejar e produzir textos adequados à situação comunicativa.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(351, 'EF67LP06', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Revisar e editar textos considerando aspectos gramaticais e discursivos.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(352, 'EF67LP07', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Participar de debates e discussões orais, respeitando turnos de fala.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(353, 'EF67LP08', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Utilizar recursos digitais na leitura e produção textual.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(354, 'EF67LP09', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Analisar variações linguísticas e seus contextos de uso.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(355, 'EF67LP10', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 7, 'Reconhecer relações de coesão e coerência nos textos.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(356, 'EF68LP01', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Analisar textos argumentativos, identificando estratégias persuasivas.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(357, 'EF68LP02', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Produzir textos argumentativos com clareza e consistência.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(358, 'EF68LP03', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Analisar textos do campo jornalístico-midiático.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(359, 'EF68LP04', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Reconhecer o papel das mídias na construção da informação.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(360, 'EF68LP05', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Utilizar normas da língua padrão na produção textual.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(361, 'EF69LP01', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Analisar textos literários de diferentes épocas e estilos.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(362, 'EF69LP02', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Produzir textos literários explorando recursos expressivos.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(363, 'EF69LP03', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Analisar discursos presentes em diferentes mídias.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(364, 'EF69LP04', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Planejar e realizar apresentações orais formais.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(365, 'EF69LP05', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Refletir criticamente sobre o uso da linguagem na sociedade.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(366, 'EF69LP06', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Reconhecer relações entre linguagem, poder e identidade.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(367, 'EF69LP07', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Utilizar estratégias de leitura para textos complexos.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(368, 'EF69LP08', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Revisar textos considerando estilo, adequação e norma-padrão.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(369, 'EF69LP09', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Analisar elementos de coesão e progressão temática.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(370, 'EF69LP10', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Produzir textos multissemióticos integrando diferentes linguagens.', '2025-12-15 22:05:32', '2025-12-15 22:05:32'),
(371, 'EF06MA01', 'Ensino Fundamental – Anos Finais', 'Matemática', 6, 6, 'Resolver e elaborar problemas envolvendo as quatro operações com números naturais.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(372, 'EF06MA02', 'Ensino Fundamental – Anos Finais', 'Matemática', 6, 6, 'Compreender e utilizar frações e números decimais em situações do cotidiano.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(373, 'EF06MA03', 'Ensino Fundamental – Anos Finais', 'Matemática', 6, 6, 'Reconhecer e representar números inteiros em diferentes contextos.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(374, 'EF06MA04', 'Ensino Fundamental – Anos Finais', 'Matemática', 6, 6, 'Resolver problemas envolvendo razões e proporções.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(375, 'EF06MA05', 'Ensino Fundamental – Anos Finais', 'Matemática', 6, 6, 'Identificar e classificar figuras geométricas planas e espaciais.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(376, 'EF07MA01', 'Ensino Fundamental – Anos Finais', 'Matemática', 7, 7, 'Resolver problemas envolvendo números racionais, em diferentes representações.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(377, 'EF07MA02', 'Ensino Fundamental – Anos Finais', 'Matemática', 7, 7, 'Utilizar expressões algébricas para representar situações-problema.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(378, 'EF07MA03', 'Ensino Fundamental – Anos Finais', 'Matemática', 7, 7, 'Resolver e elaborar problemas envolvendo porcentagem.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(379, 'EF07MA04', 'Ensino Fundamental – Anos Finais', 'Matemática', 7, 7, 'Analisar e interpretar dados apresentados em tabelas e gráficos.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(380, 'EF07MA05', 'Ensino Fundamental – Anos Finais', 'Matemática', 7, 7, 'Reconhecer e aplicar propriedades das figuras geométricas.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(381, 'EF08MA01', 'Ensino Fundamental – Anos Finais', 'Matemática', 8, 8, 'Resolver problemas envolvendo equações do 1º grau.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(382, 'EF08MA02', 'Ensino Fundamental – Anos Finais', 'Matemática', 8, 8, 'Compreender e aplicar o conceito de função em situações simples.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(383, 'EF08MA03', 'Ensino Fundamental – Anos Finais', 'Matemática', 8, 8, 'Resolver problemas envolvendo relações de proporcionalidade direta e inversa.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(384, 'EF08MA04', 'Ensino Fundamental – Anos Finais', 'Matemática', 8, 8, 'Identificar e calcular áreas e volumes de sólidos geométricos.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(385, 'EF08MA05', 'Ensino Fundamental – Anos Finais', 'Matemática', 8, 8, 'Analisar padrões numéricos e sequências.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(386, 'EF09MA01', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Resolver problemas envolvendo equações do 2º grau.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(387, 'EF09MA02', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Compreender e aplicar o conceito de função afim e quadrática.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(388, 'EF09MA03', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Analisar gráficos de funções em diferentes contextos.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(389, 'EF09MA04', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Resolver problemas envolvendo relações métricas no triângulo retângulo.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(390, 'EF09MA05', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Utilizar conceitos de estatística e probabilidade para interpretar dados.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(391, 'EF09MA06', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Resolver problemas envolvendo porcentagem, juros simples e compostos.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(392, 'EF09MA07', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Analisar situações envolvendo grandezas proporcionais.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(393, 'EF09MA08', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Resolver problemas utilizando sistemas de equações do 1º grau.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(394, 'EF09MA09', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Compreender conceitos de probabilidade em experimentos aleatórios.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(395, 'EF09MA10', 'Ensino Fundamental – Anos Finais', 'Matemática', 9, 9, 'Aplicar conhecimentos matemáticos na resolução de problemas do cotidiano.', '2025-12-15 22:05:57', '2025-12-15 22:05:57'),
(396, 'EF06CI01', 'Ensino Fundamental – Anos Finais', 'Ciências', 6, 6, 'Reconhecer a ciência como uma atividade humana, histórica e cultural.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(397, 'EF06CI02', 'Ensino Fundamental – Anos Finais', 'Ciências', 6, 6, 'Identificar características dos seres vivos e suas interações com o ambiente.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(398, 'EF06CI03', 'Ensino Fundamental – Anos Finais', 'Ciências', 6, 6, 'Compreender os níveis de organização dos seres vivos.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(399, 'EF06CI04', 'Ensino Fundamental – Anos Finais', 'Ciências', 6, 6, 'Analisar relações entre os componentes bióticos e abióticos dos ecossistemas.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(400, 'EF06CI05', 'Ensino Fundamental – Anos Finais', 'Ciências', 6, 6, 'Investigar propriedades e transformações dos materiais no cotidiano.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(401, 'EF07CI01', 'Ensino Fundamental – Anos Finais', 'Ciências', 7, 7, 'Compreender a organização do corpo humano e o funcionamento de seus sistemas.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(402, 'EF07CI02', 'Ensino Fundamental – Anos Finais', 'Ciências', 7, 7, 'Analisar hábitos relacionados à saúde e à prevenção de doenças.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(403, 'EF07CI03', 'Ensino Fundamental – Anos Finais', 'Ciências', 7, 7, 'Investigar fenômenos relacionados à energia e suas transformações.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(404, 'EF07CI04', 'Ensino Fundamental – Anos Finais', 'Ciências', 7, 7, 'Analisar interações entre os seres vivos em cadeias e teias alimentares.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(405, 'EF07CI05', 'Ensino Fundamental – Anos Finais', 'Ciências', 7, 7, 'Avaliar impactos das ações humanas sobre o meio ambiente.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(406, 'EF08CI01', 'Ensino Fundamental – Anos Finais', 'Ciências', 8, 8, 'Compreender os processos de reprodução e desenvolvimento dos seres vivos.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(407, 'EF08CI02', 'Ensino Fundamental – Anos Finais', 'Ciências', 8, 8, 'Analisar transformações químicas presentes no cotidiano.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(408, 'EF08CI03', 'Ensino Fundamental – Anos Finais', 'Ciências', 8, 8, 'Investigar fenômenos relacionados à eletricidade e ao magnetismo.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(409, 'EF08CI04', 'Ensino Fundamental – Anos Finais', 'Ciências', 8, 8, 'Compreender a dinâmica da Terra e do Sistema Solar.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(410, 'EF08CI05', 'Ensino Fundamental – Anos Finais', 'Ciências', 8, 8, 'Analisar relações entre ciência, tecnologia e sociedade.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(411, 'EF09CI01', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Compreender conceitos fundamentais de física relacionados ao movimento.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(412, 'EF09CI02', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Analisar transformações de energia em diferentes sistemas.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(413, 'EF09CI03', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Investigar reações químicas e suas aplicações tecnológicas.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(414, 'EF09CI04', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Analisar fenômenos relacionados à genética e à hereditariedade.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(415, 'EF09CI05', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Avaliar implicações éticas e ambientais do desenvolvimento científico.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(416, 'EF09CI06', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Compreender a evolução dos seres vivos e os mecanismos envolvidos.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(417, 'EF09CI07', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Analisar mudanças ambientais e seus impactos nos ecossistemas.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(418, 'EF09CI08', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Investigar fenômenos físicos relacionados à luz e ao som.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(419, 'EF09CI09', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Relacionar avanços científicos com transformações sociais.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(420, 'EF09CI10', 'Ensino Fundamental – Anos Finais', 'Ciências', 9, 9, 'Utilizar procedimentos científicos na investigação de problemas.', '2025-12-15 22:07:03', '2025-12-15 22:07:03'),
(421, 'EF06GE01', 'Ensino Fundamental – Anos Finais', 'Geografia', 6, 6, 'Reconhecer o espaço geográfico como resultado das relações entre sociedade e natureza.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(422, 'EF06GE02', 'Ensino Fundamental – Anos Finais', 'Geografia', 6, 6, 'Utilizar mapas, plantas, croquis e outras representações cartográficas.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(423, 'EF06GE03', 'Ensino Fundamental – Anos Finais', 'Geografia', 6, 6, 'Analisar elementos naturais da paisagem e suas transformações.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(424, 'EF06GE04', 'Ensino Fundamental – Anos Finais', 'Geografia', 6, 6, 'Identificar diferentes formas de ocupação do espaço geográfico.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(425, 'EF06GE05', 'Ensino Fundamental – Anos Finais', 'Geografia', 6, 6, 'Compreender a dinâmica dos climas e biomas.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(426, 'EF07GE01', 'Ensino Fundamental – Anos Finais', 'Geografia', 7, 7, 'Analisar a formação do território brasileiro.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(427, 'EF07GE02', 'Ensino Fundamental – Anos Finais', 'Geografia', 7, 7, 'Compreender a distribuição da população brasileira.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(428, 'EF07GE03', 'Ensino Fundamental – Anos Finais', 'Geografia', 7, 7, 'Analisar os fluxos migratórios internos e externos.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(429, 'EF07GE04', 'Ensino Fundamental – Anos Finais', 'Geografia', 7, 7, 'Relacionar atividades econômicas e uso do território.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(430, 'EF07GE05', 'Ensino Fundamental – Anos Finais', 'Geografia', 7, 7, 'Analisar impactos socioambientais no espaço geográfico.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(431, 'EF08GE01', 'Ensino Fundamental – Anos Finais', 'Geografia', 8, 8, 'Compreender a organização do espaço mundial.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(432, 'EF08GE02', 'Ensino Fundamental – Anos Finais', 'Geografia', 8, 8, 'Analisar processos de urbanização e industrialização.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(433, 'EF08GE03', 'Ensino Fundamental – Anos Finais', 'Geografia', 8, 8, 'Analisar as relações entre campo e cidade.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(434, 'EF08GE04', 'Ensino Fundamental – Anos Finais', 'Geografia', 8, 8, 'Compreender a dinâmica econômica global.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(435, 'EF08GE05', 'Ensino Fundamental – Anos Finais', 'Geografia', 8, 8, 'Analisar problemas socioambientais em escala global.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(436, 'EF09GE01', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Analisar a globalização e seus impactos.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(437, 'EF09GE02', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Compreender as redes de transporte e comunicação.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(438, 'EF09GE03', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Analisar conflitos geopolíticos contemporâneos.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(439, 'EF09GE04', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Compreender a organização política do mundo atual.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(440, 'EF09GE05', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Analisar desigualdades socioeconômicas globais.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(441, 'EF09GE06', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Relacionar desenvolvimento econômico e sustentabilidade.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(442, 'EF09GE07', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Analisar o papel das organizações internacionais.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(443, 'EF09GE08', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Interpretar indicadores socioeconômicos.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(444, 'EF09GE09', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Analisar a questão ambiental em escala global.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(445, 'EF09GE10', 'Ensino Fundamental – Anos Finais', 'Geografia', 9, 9, 'Utilizar conceitos geográficos para compreender o mundo contemporâneo.', '2025-12-15 22:07:46', '2025-12-15 22:07:46'),
(446, 'EF06HI01', 'Ensino Fundamental – Anos Finais', 'História', 6, 6, 'Identificar diferentes formas de organização do tempo histórico.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(447, 'EF06HI02', 'Ensino Fundamental – Anos Finais', 'História', 6, 6, 'Reconhecer vestígios do passado como fontes históricas.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(448, 'EF06HI03', 'Ensino Fundamental – Anos Finais', 'História', 6, 6, 'Analisar modos de vida das sociedades antigas.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(449, 'EF06HI04', 'Ensino Fundamental – Anos Finais', 'História', 6, 6, 'Comparar formas de organização social, política e cultural das sociedades antigas.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(450, 'EF06HI05', 'Ensino Fundamental – Anos Finais', 'História', 6, 6, 'Compreender a formação das primeiras cidades e Estados.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(451, 'EF07HI01', 'Ensino Fundamental – Anos Finais', 'História', 7, 7, 'Analisar a formação da sociedade medieval.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(452, 'EF07HI02', 'Ensino Fundamental – Anos Finais', 'História', 7, 7, 'Compreender o papel da Igreja na sociedade medieval.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(453, 'EF07HI03', 'Ensino Fundamental – Anos Finais', 'História', 7, 7, 'Analisar transformações sociais, econômicas e culturais da Idade Moderna.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(454, 'EF07HI04', 'Ensino Fundamental – Anos Finais', 'História', 7, 7, 'Compreender a expansão marítima europeia.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(455, 'EF07HI05', 'Ensino Fundamental – Anos Finais', 'História', 7, 7, 'Analisar os impactos da colonização nas Américas.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(456, 'EF08HI01', 'Ensino Fundamental – Anos Finais', 'História', 8, 8, 'Compreender o processo de formação do Brasil colonial.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(457, 'EF08HI02', 'Ensino Fundamental – Anos Finais', 'História', 8, 8, 'Analisar a escravidão e suas consequências históricas.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(458, 'EF08HI03', 'Ensino Fundamental – Anos Finais', 'História', 8, 8, 'Compreender movimentos de resistência e revoltas no Brasil.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(459, 'EF08HI04', 'Ensino Fundamental – Anos Finais', 'História', 8, 8, 'Analisar o processo de independência do Brasil.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(460, 'EF08HI05', 'Ensino Fundamental – Anos Finais', 'História', 8, 8, 'Compreender a formação do Estado nacional brasileiro.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(461, 'EF09HI01', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Analisar o mundo no contexto das revoluções industriais.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(462, 'EF09HI02', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Compreender os impactos do imperialismo e do colonialismo.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(463, 'EF09HI03', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Analisar conflitos mundiais do século XX.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(464, 'EF09HI04', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Compreender a formação dos regimes totalitários.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(465, 'EF09HI05', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Analisar a Guerra Fria e suas consequências.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(466, 'EF09HI06', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Compreender o processo de redemocratização no Brasil.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(467, 'EF09HI07', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Analisar movimentos sociais e políticos contemporâneos.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(468, 'EF09HI08', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Compreender a história recente do Brasil.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(469, 'EF09HI09', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Analisar transformações culturais no mundo contemporâneo.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(470, 'EF09HI10', 'Ensino Fundamental – Anos Finais', 'História', 9, 9, 'Utilizar conhecimentos históricos para compreender a realidade atual.', '2025-12-15 22:08:16', '2025-12-15 22:08:16'),
(521, 'EF06LP01', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 6, 'Ler, compreender e interpretar textos de diferentes gêneros, considerando tema, contexto e finalidade.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(522, 'EF06LP02', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 6, 'Identificar informações explícitas e implícitas em textos.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(523, 'EF06LP03', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 6, 'Reconhecer características de diferentes gêneros textuais.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(524, 'EF06LP04', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 6, 'Planejar e produzir textos escritos adequados ao contexto de circulação.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(525, 'EF06LP05', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 6, 6, 'Utilizar recursos linguísticos para garantir coesão e coerência textual.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(526, 'EF07LP01', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 7, 7, 'Analisar efeitos de sentido produzidos por recursos expressivos da linguagem.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(527, 'EF07LP02', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 7, 7, 'Comparar textos que tratam do mesmo tema em diferentes gêneros.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(528, 'EF07LP03', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 7, 7, 'Produzir textos narrativos, descritivos e argumentativos.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(529, 'EF07LP04', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 7, 7, 'Revisar textos produzidos, considerando aspectos gramaticais e discursivos.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(530, 'EF07LP05', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 7, 7, 'Reconhecer variações linguísticas e respeitar diferentes usos da língua.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(531, 'EF08LP01', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Analisar textos argumentativos, identificando tese e argumentos.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(532, 'EF08LP02', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Avaliar a confiabilidade de informações em diferentes fontes.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(533, 'EF08LP03', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Produzir textos argumentativos adequados à situação comunicativa.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(534, 'EF08LP04', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Utilizar estratégias de leitura para compreensão global e detalhada.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(535, 'EF08LP05', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 8, 8, 'Analisar recursos linguísticos e semióticos em textos multimodais.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(536, 'EF09LP01', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Analisar criticamente textos de diferentes esferas sociais.', '2025-12-15 22:10:04', '2025-12-15 22:10:04');
INSERT INTO `habilidades_bncc` (`id`, `codigo_bncc`, `etapa`, `componente`, `ano_inicio`, `ano_fim`, `descricao`, `created_at`, `updated_at`) VALUES
(537, 'EF09LP02', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Reconhecer posicionamentos e ideologias presentes em textos.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(538, 'EF09LP03', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Produzir textos dissertativo-argumentativos com clareza e consistência.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(539, 'EF09LP04', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Revisar e reescrever textos, aprimorando aspectos discursivos e linguísticos.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(540, 'EF09LP05', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Utilizar normas da língua padrão em situações formais de comunicação.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(541, 'EF09LP06', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Analisar relações intertextuais entre diferentes textos.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(542, 'EF09LP07', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Interpretar textos literários considerando contexto histórico e cultural.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(543, 'EF09LP08', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Analisar recursos estilísticos em textos literários.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(544, 'EF09LP09', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Participar de práticas de linguagem oral em diferentes contextos.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(545, 'EF09LP10', 'Ensino Fundamental – Anos Finais', 'Língua Portuguesa', 9, 9, 'Utilizar a leitura e a escrita como práticas sociais de cidadania.', '2025-12-15 22:10:04', '2025-12-15 22:10:04'),
(556, 'EF03LP01', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Ler e compreender textos narrativos e informativos.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(557, 'EF03LP02', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Identificar ideias principais em textos.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(558, 'EF03LP03', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Produzir textos com começo, meio e fim.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(559, 'EF03LP04', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Utilizar recursos de coesão textual simples.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(560, 'EF03LP05', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Revisar textos considerando clareza e organização.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(561, 'EF04LP01', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Ler e interpretar textos de diferentes gêneros.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(562, 'EF04LP02', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Identificar informações implícitas em textos.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(563, 'EF04LP03', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Produzir textos narrativos e descritivos.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(564, 'EF04LP04', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Utilizar normas ortográficas e gramaticais.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(565, 'EF04LP05', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Revisar textos com autonomia crescente.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(566, 'EF05LP01', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Ler e compreender textos literários e informativos.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(567, 'EF05LP02', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Analisar características de gêneros textuais.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(568, 'EF05LP03', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Produzir textos com organização e clareza.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(569, 'EF05LP04', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Utilizar recursos linguísticos para argumentar.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(570, 'EF05LP05', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Revisar e editar textos considerando adequação ao leitor.', '2025-12-15 22:14:53', '2025-12-15 22:14:53'),
(574, 'EF01MA04', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Resolver e elaborar problemas simples de adição e subtração.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(575, 'EF01MA05', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Reconhecer e nomear formas geométricas planas presentes no cotidiano.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(579, 'EF02MA04', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 2, 2, 'Identificar e descrever figuras geométricas planas.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(580, 'EF02MA05', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 2, 2, 'Utilizar unidades de medida de tempo e comprimento em situações cotidianas.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(584, 'EF03MA04', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 3, 3, 'Identificar e representar frações simples.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(585, 'EF03MA05', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 3, 3, 'Interpretar e produzir tabelas e gráficos simples.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(589, 'EF04MA04', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 4, 4, 'Reconhecer e classificar figuras geométricas planas e espaciais.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(590, 'EF04MA05', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 4, 4, 'Analisar e interpretar informações apresentadas em gráficos e tabelas.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(594, 'EF05MA04', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Calcular perímetro e área de figuras planas.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(595, 'EF05MA05', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 5, 5, 'Interpretar e produzir gráficos e tabelas mais complexos.', '2025-12-15 22:16:02', '2025-12-15 22:16:02'),
(599, 'EF01CI04', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 1, 'Reconhecer a importância da água e do ar para a vida.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(600, 'EF01CI05', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 1, 1, 'Adotar hábitos de cuidado com o ambiente em situações do cotidiano.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(604, 'EF02CI04', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 2, 2, 'Reconhecer a importância do Sol como fonte de luz e calor.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(605, 'EF02CI05', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 2, 2, 'Identificar ações humanas que impactam o meio ambiente.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(609, 'EF03CI04', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 3, 3, 'Analisar mudanças de estado físico da água.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(610, 'EF03CI05', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 3, 3, 'Adotar atitudes de preservação ambiental.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(614, 'EF04CI04', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 4, 4, 'Identificar formas de obtenção e uso da energia.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(615, 'EF04CI05', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 4, 4, 'Analisar impactos ambientais decorrentes das atividades humanas.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(619, 'EF05CI04', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 5, 5, 'Compreender o uso responsável dos recursos naturais.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(620, 'EF05CI05', 'Ensino Fundamental – Anos Iniciais', 'Ciências', 5, 5, 'Analisar a importância da ciência e da tecnologia para a sociedade.', '2025-12-15 22:16:34', '2025-12-15 22:16:34'),
(624, 'EF01HI04', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 1, 'Identificar as mudanças no cotidiano ao longo do tempo.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(625, 'EF01HI05', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 1, 'Reconhecer a importância das relações entre as gerações para a preservação da memória e dos saberes.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(629, 'EF02HI04', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 2, 'Compreender a organização e o uso do tempo para a construção da memória coletiva.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(630, 'EF02HI05', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 2, 'Identificar e valorizar as culturas e tradições de diferentes povos.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(634, 'EF03HI04', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 3, 'Relatar a formação de diversas sociedades e suas influências no mundo atual.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(635, 'EF03HI05', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 3, 'Comparar aspectos históricos, culturais e sociais entre diferentes povos.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(639, 'EF04HI04', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 4, 'Compreender o processo de colonização e os impactos históricos e sociais.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(640, 'EF04HI05', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 4, 'Analisar o papel da escravidão no Brasil e suas consequências históricas.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(644, 'EF05HI04', 'Ensino Fundamental – Anos Iniciais', 'História', 5, 5, 'Analisar a República Brasileira e os períodos históricos que a marcaram.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(645, 'EF05HI05', 'Ensino Fundamental – Anos Iniciais', 'História', 5, 5, 'Compreender a história dos direitos civis no Brasil e suas conquistas.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(646, 'EF05HI06', 'Ensino Fundamental – Anos Iniciais', 'História', 5, 5, 'Estudar o impacto das tecnologias e inovações no mundo moderno.', '2025-12-15 22:16:58', '2025-12-15 22:16:58'),
(650, 'EF01GE04', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 1, 'Localizar as regiões de seu país e as características físicas de cada uma.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(651, 'EF01GE05', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 1, 'Compreender o conceito de paisagem natural e transformada pelo ser humano.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(655, 'EF02GE04', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 2, 'Compreender os diferentes tipos de habitat e as transformações no uso do solo.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(656, 'EF02GE05', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 2, 'Localizar no mapa os estados e as capitais do Brasil e suas principais características.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(657, 'EF02GE06', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 2, 'Comparar as características de diferentes regiões do Brasil.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(658, 'EF02GE07', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 2, 'Analisar as atividades humanas e seu impacto no meio ambiente.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(659, 'EF02GE08', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 2, 'Estudar os sistemas de transporte e comunicação no Brasil e no mundo.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(663, 'EF03GE04', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 3, 'Comparar diferentes paisagens geográficas e seus recursos naturais.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(664, 'EF03GE05', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 3, 'Entender o conceito de território e suas divisões.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(665, 'EF03GE06', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 3, 'Identificar as transformações que os processos de urbanização causam no meio ambiente.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(669, 'EF04GE04', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 4, 'Compreender as causas e consequências da migração e do deslocamento populacional.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(670, 'EF04GE05', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 4, 'Identificar as diferentes formas de ocupação do solo e seu impacto ambiental.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(671, 'EF04GE06', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 4, 'Estudar as relações de comércio e consumo no espaço geográfico.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(675, 'EF05GE04', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 5, 5, 'Estudar o impacto da globalização nos espaços geográficos e nas relações sociais e econômicas.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(676, 'EF05GE05', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 5, 5, 'Reconhecer as questões ambientais como desafios globais e locais.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(677, 'EF05GE06', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 5, 5, 'Analisar os processos de urbanização e seus impactos no meio ambiente e na sociedade.', '2025-12-15 22:17:43', '2025-12-15 22:17:43'),
(806, 'EF01AR04', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 1, 'Participar de atividades artísticas individuais e coletivas.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(807, 'EF01AR05', 'Ensino Fundamental – Anos Iniciais', 'Arte', 1, 1, 'Valorizar produções artísticas próprias e de colegas.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(811, 'EF02AR04', 'Ensino Fundamental – Anos Iniciais', 'Arte', 2, 2, 'Participar de apresentações e apreciações artísticas.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(812, 'EF02AR05', 'Ensino Fundamental – Anos Iniciais', 'Arte', 2, 2, 'Respeitar e valorizar manifestações artísticas de diferentes culturas.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(816, 'EF03AR04', 'Ensino Fundamental – Anos Iniciais', 'Arte', 3, 3, 'Participar de processos de criação coletiva em arte.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(817, 'EF03AR05', 'Ensino Fundamental – Anos Iniciais', 'Arte', 3, 3, 'Analisar e apreciar produções artísticas próprias e de outros.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(821, 'EF04AR04', 'Ensino Fundamental – Anos Iniciais', 'Arte', 4, 4, 'Planejar e desenvolver projetos artísticos individuais e coletivos.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(822, 'EF04AR05', 'Ensino Fundamental – Anos Iniciais', 'Arte', 4, 4, 'Valorizar o patrimônio artístico e cultural.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(826, 'EF05AR04', 'Ensino Fundamental – Anos Iniciais', 'Arte', 5, 5, 'Participar de projetos artísticos que envolvam diferentes linguagens.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(827, 'EF05AR05', 'Ensino Fundamental – Anos Iniciais', 'Arte', 5, 5, 'Valorizar a arte como forma de expressão e comunicação.', '2025-12-15 22:22:10', '2025-12-15 22:22:10'),
(831, 'EF01EF04', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 1, 'Reconhecer o próprio corpo e expressar sensações e emoções por meio do movimento.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(832, 'EF01EF05', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 1, 'Valorizar a convivência e o respeito aos colegas nas atividades corporais.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(836, 'EF02EF04', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 2, 2, 'Explorar movimentos rítmicos e expressivos em danças e brincadeiras cantadas.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(837, 'EF02EF05', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 2, 2, 'Reconhecer a importância da atividade física para a saúde e o bem-estar.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(841, 'EF03EF04', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 3, 'Experimentar práticas corporais de diferentes culturas.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(842, 'EF03EF05', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 3, 'Refletir sobre atitudes de cooperação, respeito e solidariedade nas práticas corporais.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(846, 'EF04EF04', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 4, 4, 'Experimentar danças e atividades rítmicas de diferentes contextos culturais.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(847, 'EF04EF05', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 4, 4, 'Adotar atitudes de cuidado com o corpo durante as práticas corporais.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(851, 'EF05EF04', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 5, 5, 'Planejar e realizar atividades físicas de forma autônoma e segura.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(852, 'EF05EF05', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 5, 5, 'Reconhecer a importância da atividade física para a qualidade de vida.', '2025-12-15 22:22:45', '2025-12-15 22:22:45'),
(853, 'EF06EF01', 'Ensino Fundamental – Anos Finais', 'Educação Física', 6, 6, 'Participar de jogos, esportes e brincadeiras respeitando regras, adversários e colegas.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(854, 'EF06EF02', 'Ensino Fundamental – Anos Finais', 'Educação Física', 6, 6, 'Aprimorar habilidades motoras em diferentes modalidades esportivas.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(855, 'EF06EF03', 'Ensino Fundamental – Anos Finais', 'Educação Física', 6, 6, 'Planejar estratégias simples em jogos coletivos e individuais.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(856, 'EF06EF04', 'Ensino Fundamental – Anos Finais', 'Educação Física', 6, 6, 'Explorar práticas corporais de diferentes culturas e contextos históricos.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(857, 'EF06EF05', 'Ensino Fundamental – Anos Finais', 'Educação Física', 6, 6, 'Refletir sobre atitudes de cooperação, ética e respeito nas atividades físicas.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(858, 'EF07EF01', 'Ensino Fundamental – Anos Finais', 'Educação Física', 7, 7, 'Participar de jogos e esportes com estratégias mais elaboradas.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(859, 'EF07EF02', 'Ensino Fundamental – Anos Finais', 'Educação Física', 7, 7, 'Aprimorar desempenho motor e coordenação em atividades físicas variadas.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(860, 'EF07EF03', 'Ensino Fundamental – Anos Finais', 'Educação Física', 7, 7, 'Compreender regras e normas de diferentes modalidades esportivas.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(861, 'EF07EF04', 'Ensino Fundamental – Anos Finais', 'Educação Física', 7, 7, 'Explorar movimentos rítmicos, expressivos e artísticos em dança e ginástica.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(862, 'EF07EF05', 'Ensino Fundamental – Anos Finais', 'Educação Física', 7, 7, 'Valorizar a saúde, o cuidado corporal e hábitos saudáveis.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(863, 'EF08EF01', 'Ensino Fundamental – Anos Finais', 'Educação Física', 8, 8, 'Participar de jogos, esportes e atividades físicas com responsabilidade e fair play.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(864, 'EF08EF02', 'Ensino Fundamental – Anos Finais', 'Educação Física', 8, 8, 'Desenvolver habilidades motoras e estratégias em diferentes contextos de prática.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(865, 'EF08EF03', 'Ensino Fundamental – Anos Finais', 'Educação Física', 8, 8, 'Analisar práticas corporais como manifestações culturais e sociais.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(866, 'EF08EF04', 'Ensino Fundamental – Anos Finais', 'Educação Física', 8, 8, 'Planejar e executar atividades físicas de forma autônoma e segura.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(867, 'EF08EF05', 'Ensino Fundamental – Anos Finais', 'Educação Física', 8, 8, 'Refletir sobre a importância da atividade física para o bem-estar e qualidade de vida.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(868, 'EF09EF01', 'Ensino Fundamental – Anos Finais', 'Educação Física', 9, 9, 'Participar de jogos e esportes com regras complexas e estratégias avançadas.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(869, 'EF09EF02', 'Ensino Fundamental – Anos Finais', 'Educação Física', 9, 9, 'Aprimorar habilidades motoras, coordenação e resistência física.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(870, 'EF09EF03', 'Ensino Fundamental – Anos Finais', 'Educação Física', 9, 9, 'Analisar criticamente diferentes modalidades esportivas e suas regras.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(871, 'EF09EF04', 'Ensino Fundamental – Anos Finais', 'Educação Física', 9, 9, 'Explorar práticas corporais de diferentes culturas, contextos e tradições.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(872, 'EF09EF05', 'Ensino Fundamental – Anos Finais', 'Educação Física', 9, 9, 'Valorizar hábitos saudáveis, cuidado com o corpo e atitudes de cooperação.', '2025-12-15 22:23:16', '2025-12-15 22:23:16'),
(873, 'EF06AR01', 'Ensino Fundamental – Anos Finais', 'Artes', 6, 6, 'Experimentar diferentes linguagens artísticas, como música, dança, teatro e artes visuais.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(874, 'EF06AR02', 'Ensino Fundamental – Anos Finais', 'Artes', 6, 6, 'Produzir trabalhos artísticos individuais e coletivos com criatividade.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(875, 'EF06AR03', 'Ensino Fundamental – Anos Finais', 'Artes', 6, 6, 'Analisar obras de arte considerando elementos formais e contextos culturais.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(876, 'EF06AR04', 'Ensino Fundamental – Anos Finais', 'Artes', 6, 6, 'Expressar ideias, sentimentos e conceitos por meio da arte.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(877, 'EF06AR05', 'Ensino Fundamental – Anos Finais', 'Artes', 6, 6, 'Refletir sobre o papel da arte na sociedade e na cultura.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(878, 'EF07AR01', 'Ensino Fundamental – Anos Finais', 'Artes', 7, 7, 'Aprofundar a experimentação em diferentes linguagens artísticas.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(879, 'EF07AR02', 'Ensino Fundamental – Anos Finais', 'Artes', 7, 7, 'Produzir projetos artísticos integrando técnicas, materiais e linguagens variadas.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(880, 'EF07AR03', 'Ensino Fundamental – Anos Finais', 'Artes', 7, 7, 'Interpretar obras artísticas considerando contexto histórico e cultural.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(881, 'EF07AR04', 'Ensino Fundamental – Anos Finais', 'Artes', 7, 7, 'Expressar ideias e emoções de forma criativa e crítica nas produções artísticas.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(882, 'EF07AR05', 'Ensino Fundamental – Anos Finais', 'Artes', 7, 7, 'Valorizar a diversidade cultural e as manifestações artísticas locais e globais.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(883, 'EF08AR01', 'Ensino Fundamental – Anos Finais', 'Artes', 8, 8, 'Produzir e recriar obras artísticas utilizando diferentes técnicas e recursos.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(884, 'EF08AR02', 'Ensino Fundamental – Anos Finais', 'Artes', 8, 8, 'Analisar e criticar obras de arte, considerando intenções e contextos.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(885, 'EF08AR03', 'Ensino Fundamental – Anos Finais', 'Artes', 8, 8, 'Planejar projetos artísticos individuais e coletivos com autonomia.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(886, 'EF08AR04', 'Ensino Fundamental – Anos Finais', 'Artes', 8, 8, 'Expressar opiniões e sentimentos por meio de linguagens artísticas diversas.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(887, 'EF08AR05', 'Ensino Fundamental – Anos Finais', 'Artes', 8, 8, 'Compreender o papel social e cultural da arte na vida cotidiana.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(888, 'EF09AR01', 'Ensino Fundamental – Anos Finais', 'Artes', 9, 9, 'Criar obras artísticas complexas integrando diferentes linguagens.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(889, 'EF09AR02', 'Ensino Fundamental – Anos Finais', 'Artes', 9, 9, 'Analisar criticamente obras de arte considerando múltiplos contextos.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(890, 'EF09AR03', 'Ensino Fundamental – Anos Finais', 'Artes', 9, 9, 'Planejar e executar projetos artísticos com maior autonomia.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(891, 'EF09AR04', 'Ensino Fundamental – Anos Finais', 'Artes', 9, 9, 'Expressar ideias, sentimentos e conceitos de forma criativa e crítica.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(892, 'EF09AR05', 'Ensino Fundamental – Anos Finais', 'Artes', 9, 9, 'Valorizar a diversidade de manifestações artísticas e culturais.', '2025-12-15 22:23:41', '2025-12-15 22:23:41'),
(893, 'EF01MA22', 'Ensino Fundamental', 'Matemática', 1, 1, 'Realizar pesquisa, envolvendo até duas variáveis categóricas de seu interesse e universo de até 30 elementos, e organizar dados por meio de representações pessoais.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(899, 'EF02MA06', 'Ensino Fundamental', 'Matemática', 2, 2, 'Resolver e elaborar problemas de adição e de subtração, envolvendo números de até três ordens, com os significados de juntar, acrescentar, separar, retirar, utilizando estratégias pessoais.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(900, 'EF02MA07', 'Ensino Fundamental', 'Matemática', 2, 2, 'Resolver e elaborar problemas de multiplicação (por 2, 3, 4 e 5) com a ideia de adição de parcelas iguais por meio de estratégias e formas de registro pessoais, utilizando ou não suporte de imagens e/ou material manipulável.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(901, 'EF02MA08', 'Ensino Fundamental', 'Matemática', 2, 2, 'Resolver e elaborar problemas envolvendo dobro, metade, triplo e terça parte, com o suporte de imagens ou material manipulável, utilizando estratégias pessoais.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(902, 'EF02MA09', 'Ensino Fundamental', 'Matemática', 2, 2, 'Construir sequências de números naturais em ordem crescente ou decrescente a partir de um número qualquer, utilizando uma regularidade estabelecida.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(903, 'EF02MA10', 'Ensino Fundamental', 'Matemática', 2, 2, 'Descrever um padrão (ou regularidade) de sequências repetitivas e de sequências recursivas, por meio de palavras, símbolos ou desenhos.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(904, 'EF02MA11', 'Ensino Fundamental', 'Matemática', 2, 2, 'Descrever os elementos ausentes em sequências repetitivas e em sequências recursivas de números naturais, objetos ou figuras.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(905, 'EF02MA12', 'Ensino Fundamental', 'Matemática', 2, 2, 'Identificar e registrar, em linguagem verbal ou não verbal, a localização e os deslocamentos de pessoas e de objetos no espaço, considerando mais de um ponto de referência, e indicar as mudanças de direção e de sentido.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(906, 'EF02MA13', 'Ensino Fundamental', 'Matemática', 2, 2, 'Esboçar roteiros a ser seguidos ou plantas de ambientes familiares, assinalando entradas, saídas e alguns pontos de referência.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(907, 'EF02MA14', 'Ensino Fundamental', 'Matemática', 2, 2, 'Reconhecer, nomear e comparar figuras geométricas espaciais (cubo, bloco retangular, pirâmide, cone, cilindro e esfera), relacionando-as com objetos do mundo físico.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(908, 'EF02MA15', 'Ensino Fundamental', 'Matemática', 2, 2, 'Reconhecer, comparar e nomear figuras planas (círculo, quadrado, retângulo e triângulo), por meio de características comuns, em desenhos apresentados em diferentes disposições ou em sólidos geométricos.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(909, 'EF02MA16', 'Ensino Fundamental', 'Matemática', 2, 2, 'Estimar, medir e comparar comprimentos de lados de salas (incluindo contorno) e de polígonos, utilizando unidades de medida não padronizadas e padronizadas (metro, centímetro e milímetro) e instrumentos adequados.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(910, 'EF02MA17', 'Ensino Fundamental', 'Matemática', 2, 2, 'Estimar, medir e comparar capacidade e massa, utilizando estratégias pessoais e unidades de medida não padronizadas ou padronizadas (litro, mililitro, grama e quilograma).', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(911, 'EF02MA18', 'Ensino Fundamental', 'Matemática', 2, 2, 'Indicar a duração de intervalos de tempo entre duas datas, como dias da semana e meses do ano, utilizando calendário, para planejamentos e organização de agenda.', '2025-12-15 22:28:36', '2025-12-15 22:28:36'),
(941, 'EF02MA19', 'Ensino Fundamental', 'Matemática', 2, 2, 'Medir a duração de um intervalo de tempo por meio de relógio digital e registrar o horário do início e do fim do intervalo.', '2025-12-15 22:30:29', '2025-12-15 22:30:29'),
(942, 'EF02MA20', 'Ensino Fundamental', 'Matemática', 2, 2, 'Estabelecer a equivalência de valores entre moedas e cédulas do sistema monetário brasileiro para resolver situações cotidianas.', '2025-12-15 22:30:53', '2025-12-15 22:30:53'),
(943, 'EF02MA21', 'Ensino Fundamental', 'Matemática', 2, 2, 'Classificar resultados de eventos cotidianos aleatórios como “pouco prováveis”, “muito prováveis”, “improváveis” e “impossíveis”.', '2025-12-15 22:30:53', '2025-12-15 22:30:53'),
(944, 'EF02MA22', 'Ensino Fundamental', 'Matemática', 2, 2, 'Comparar informações de pesquisas apresentadas por meio de tabelas de dupla entrada e em gráficos de colunas simples ou barras, para melhor compreender aspectos da realidade próxima.', '2025-12-15 22:30:53', '2025-12-15 22:30:53'),
(945, 'EF02MA23', 'Ensino Fundamental', 'Matemática', 2, 2, 'Realizar pesquisa em universo de até 30 elementos, escolhendo até três variáveis categóricas de seu interesse, organizando os dados coletados em listas, tabelas e gráficos de colunas simples.', '2025-12-15 22:30:53', '2025-12-15 22:30:53'),
(951, 'EF03MA06', 'Ensino Fundamental', 'Matemática', 3, 3, 'Resolver e elaborar problemas de adição e subtração com os significados de juntar, acrescentar, separar, retirar, comparar e completar quantidades, utilizando diferentes estratégias de cálculo exato ou aproximado, incluindo cálculo mental.', '2025-12-15 22:30:53', '2025-12-15 22:30:53'),
(952, 'EF03MA07', 'Ensino Fundamental', 'Matemática', 3, 3, 'Resolver e elaborar problemas de multiplicação (por 2, 3, 4, 5 e 10) com os significados de adição de parcelas iguais e elementos apresentados em disposição retangular, utilizando diferentes estratégias de cálculo e registros.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(953, 'EF03MA08', 'Ensino Fundamental', 'Matemática', 3, 3, 'Resolver e elaborar problemas de divisão de um número natural por outro (até 10), com resto zero e com resto diferente de zero, com os significados de repartição equitativa e de medida, por meio de estratégias e registros pessoais.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(954, 'EF03MA09', 'Ensino Fundamental', 'Matemática', 3, 3, 'Associar o quociente de uma divisão com resto zero de um número natural por 2, 3, 4, 5 e 10 às ideias de metade, terça, quarta, quinta e décima partes.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(955, 'EF03MA10', 'Ensino Fundamental', 'Matemática', 3, 3, 'Identificar regularidades em sequências ordenadas de números naturais, resultantes da realização de adições ou subtrações sucessivas, por um mesmo número, descrever uma regra de formação da sequência e determinar elementos faltantes ou seguintes.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(956, 'EF03MA11', 'Ensino Fundamental', 'Matemática', 3, 3, 'Compreender a ideia de igualdade para escrever diferentes sentenças de adições ou de subtrações de dois números naturais que resultem na mesma soma ou diferença.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(957, 'EF03MA12', 'Ensino Fundamental', 'Matemática', 3, 3, 'Descrever e representar, por meio de esboços de trajetos ou utilizando croquis e maquetes, a movimentação de pessoas ou de objetos no espaço, incluindo mudanças de direção e sentido, com base em diferentes pontos de referência.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(958, 'EF03MA13', 'Ensino Fundamental', 'Matemática', 3, 3, 'Associar figuras geométricas espaciais (cubo, bloco retangular, pirâmide, cone, cilindro e esfera) a objetos do mundo físico e nomear essas figuras.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(959, 'EF03MA14', 'Ensino Fundamental', 'Matemática', 3, 3, 'Descrever características de algumas figuras geométricas espaciais (prismas retos, pirâmides, cilindros, cones), relacionando-as com suas planificações.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(960, 'EF03MA15', 'Ensino Fundamental', 'Matemática', 3, 3, 'Classificar e comparar figuras planas (triângulo, quadrado, retângulo, trapézio e paralelogramo) em relação a seus lados (quantidade, posições relativas e comprimento) e vértices.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(961, 'EF03MA16', 'Ensino Fundamental', 'Matemática', 3, 3, 'Reconhecer figuras congruentes, usando sobreposição e desenhos em malhas quadriculadas ou triangulares, incluindo o uso de tecnologias digitais.', '2025-12-15 22:31:50', '2025-12-15 22:31:50'),
(962, 'EF03MA17', 'Ensino Fundamental', 'Matemática', 3, 3, 'Reconhecer que o resultado de uma medida depende da unidade de medida utilizada.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(963, 'EF03MA18', 'Ensino Fundamental', 'Matemática', 3, 3, 'Escolher a unidade de medida e o instrumento mais apropriado para medições de comprimento, tempo e capacidade.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(964, 'EF03MA19', 'Ensino Fundamental', 'Matemática', 3, 3, 'Estimar, medir e comparar comprimentos, utilizando unidades de medida não padronizadas e padronizadas mais usuais (metro, centímetro e milímetro) e diversos instrumentos de medida.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(965, 'EF03MA20', 'Ensino Fundamental', 'Matemática', 3, 3, 'Estimar e medir capacidade e massa, utilizando unidades de medida não padronizadas e padronizadas mais usuais (litro, mililitro, quilograma, grama e miligrama), reconhecendo-as em leitura de rótulos e embalagens, entre outros.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(966, 'EF03MA21', 'Ensino Fundamental', 'Matemática', 3, 3, 'Comparar, visualmente ou por superposição, áreas de faces de objetos, de figuras planas ou de desenhos.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(967, 'EF03MA22', 'Ensino Fundamental', 'Matemática', 3, 3, 'Ler e registrar medidas e intervalos de tempo, utilizando relógios (analógico e digital) para informar os horários de início e término de realização de uma atividade e sua duração.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(968, 'EF03MA23', 'Ensino Fundamental', 'Matemática', 3, 3, 'Ler horas em relógios digitais e em relógios analógicos e reconhecer a relação entre hora e minutos e entre minuto e segundos.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(969, 'EF03MA24', 'Ensino Fundamental', 'Matemática', 3, 3, 'Resolver e elaborar problemas que envolvam a comparação e a equivalência de valores monetários do sistema brasileiro em situações de compra, venda e troca.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(970, 'EF03MA25', 'Ensino Fundamental', 'Matemática', 3, 3, 'Identificar, em eventos familiares aleatórios, todos os resultados possíveis, estimando os que têm maiores ou menores chances de ocorrência.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(971, 'EF03MA26', 'Ensino Fundamental', 'Matemática', 3, 3, 'Resolver problemas cujos dados estão apresentados em tabelas de dupla entrada, gráficos de barras ou de colunas.', '2025-12-15 22:32:20', '2025-12-15 22:32:20'),
(972, 'EF03MA27', 'Ensino Fundamental', 'Matemática', 3, 3, 'Ler, interpretar e comparar dados apresentados em tabelas de dupla entrada, gráficos de barras ou de colunas, envolvendo resultados de pesquisas significativas, utilizando termos como maior e menor frequência, apropriando-se desse tipo de linguagem para compreender aspectos da realidade sociocultural significativos.', '2025-12-15 22:32:42', '2025-12-15 22:32:42'),
(973, 'EF03MA28', 'Ensino Fundamental', 'Matemática', 3, 3, 'Realizar pesquisa envolvendo variáveis categóricas em um universo de até 50 elementos, organizar os dados coletados utilizando listas, tabelas simples ou de dupla entrada e representá-los em gráficos de colunas simples, com e sem uso de tecnologias digitais.', '2025-12-15 22:32:42', '2025-12-15 22:32:42'),
(979, 'EF04MA06', 'Ensino Fundamental', 'Matemática', 4, 4, 'Resolver e elaborar problemas envolvendo diferentes significados da multiplicação (adição de parcelas iguais, organização retangular e proporcionalidade), utilizando estratégias diversas, como cálculo por estimativa, cálculo mental e algoritmos.', '2025-12-15 22:32:42', '2025-12-15 22:32:42'),
(980, 'EF04MA07', 'Ensino Fundamental', 'Matemática', 4, 4, 'Resolver e elaborar problemas de divisão cujo divisor tenha no máximo dois algarismos, envolvendo os significados de repartição equitativa e de medida, utilizando estratégias diversas, como cálculo por estimativa, cálculo mental e algoritmos.', '2025-12-15 22:32:42', '2025-12-15 22:32:42'),
(981, 'EF04MA08', 'Ensino Fundamental', 'Matemática', 4, 4, 'Resolver, com o suporte de imagem e/ou material manipulável, problemas simples de contagem, como a determinação do número de agrupamentos possíveis ao se combinar cada elemento de uma coleção com todos os elementos de outra, utilizando estratégias e formas de registro pessoais.', '2025-12-15 22:32:42', '2025-12-15 22:32:42'),
(982, 'EF04MA09', 'Ensino Fundamental', 'Matemática', 4, 4, 'Reconhecer as frações unitárias mais usuais (1/2, 1/3, 1/4, 1/5, 1/10 e 1/100) como unidades de medida menores do que uma unidade, utilizando a reta numérica como recurso.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(983, 'EF04MA10', 'Ensino Fundamental', 'Matemática', 4, 4, 'Reconhecer que as regras do sistema de numeração decimal podem ser estendidas para a representação decimal de um número racional e relacionar décimos e centésimos com a representação do sistema monetário brasileiro.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(984, 'EF04MA11', 'Ensino Fundamental', 'Matemática', 4, 4, 'Identificar regularidades em sequências numéricas compostas por múltiplos de um número natural.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(985, 'EF04MA12', 'Ensino Fundamental', 'Matemática', 4, 4, 'Reconhecer, por meio de investigações, que há grupos de números naturais para os quais as divisões por um determinado número resultam em restos iguais, identificando regularidades.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(986, 'EF04MA13', 'Ensino Fundamental', 'Matemática', 4, 4, 'Reconhecer, por meio de investigações, utilizando a calculadora quando necessário, as relações inversas entre as operações de adição e de subtração e de multiplicação e de divisão, para aplicá-las na resolução de problemas.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(987, 'EF04MA14', 'Ensino Fundamental', 'Matemática', 4, 4, 'Reconhecer e mostrar, por meio de exemplos, que a relação de igualdade existente entre dois termos permanece quando se adiciona ou se subtrai um mesmo número a cada um desses termos.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(988, 'EF04MA15', 'Ensino Fundamental', 'Matemática', 4, 4, 'Determinar o número desconhecido que torna verdadeira uma igualdade que envolve as operações fundamentais com números naturais.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(989, 'EF04MA16', 'Ensino Fundamental', 'Matemática', 4, 4, 'Descrever deslocamentos e localização de pessoas e de objetos no espaço, por meio de malhas quadriculadas e representações como desenhos, mapas, planta baixa e croquis, empregando termos como direita e esquerda, mudanças de direção e sentido, intersecção, transversais, paralelas e perpendiculares.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(990, 'EF04MA17', 'Ensino Fundamental', 'Matemática', 4, 4, 'Associar prismas e pirâmides a suas planificações e analisar, nomear e comparar seus atributos, estabelecendo relações entre as representações planas e espaciais.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(991, 'EF04MA18', 'Ensino Fundamental', 'Matemática', 4, 4, 'Reconhecer ângulos retos e não retos em figuras poligonais com o uso de dobraduras, esquadros ou softwares de geometria.', '2025-12-15 22:33:13', '2025-12-15 22:33:13'),
(992, 'EF04MA19', 'Ensino Fundamental', 'Matemática', 4, 4, 'Reconhecer simetria de reflexão em figuras e em pares de figuras geométricas planas e utilizá-la na construção de figuras congruentes, com o uso de malhas quadriculadas e de softwares de geometria.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(993, 'EF04MA20', 'Ensino Fundamental', 'Matemática', 4, 4, 'Medir e estimar comprimentos (incluindo perímetros), massas e capacidades, utilizando unidades de medida padronizadas mais usuais, valorizando e respeitando a cultura local.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(994, 'EF04MA21', 'Ensino Fundamental', 'Matemática', 4, 4, 'Medir, comparar e estimar área de figuras planas desenhadas em malha quadriculada, pela contagem dos quadradinhos ou de metades de quadradinho, reconhecendo que duas figuras com formatos diferentes podem ter a mesma medida de área.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(995, 'EF04MA22', 'Ensino Fundamental', 'Matemática', 4, 4, 'Ler e registrar medidas e intervalos de tempo em horas, minutos e segundos em situações relacionadas ao seu cotidiano, como informar os horários de início e término de realização de uma tarefa e sua duração.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(996, 'EF04MA23', 'Ensino Fundamental', 'Matemática', 4, 4, 'Reconhecer temperatura como grandeza e o grau Celsius como unidade de medida a ela associada e utilizá-lo em comparações de temperaturas em diferentes regiões do Brasil ou no exterior ou, ainda, em discussões que envolvam problemas relacionados ao aquecimento global.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(997, 'EF04MA24', 'Ensino Fundamental', 'Matemática', 4, 4, 'Registrar as temperaturas máxima e mínima diárias, em locais do seu cotidiano, e elaborar gráficos de colunas com as variações diárias da temperatura, utilizando, inclusive, planilhas eletrônicas.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(998, 'EF04MA25', 'Ensino Fundamental', 'Matemática', 4, 4, 'Resolver e elaborar problemas que envolvam situações de compra e venda e formas de pagamento, utilizando termos como troco e desconto, enfatizando o consumo ético, consciente e responsável.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(999, 'EF04MA26', 'Ensino Fundamental', 'Matemática', 4, 4, 'Identificar, entre eventos aleatórios cotidianos, aqueles que têm maior chance de ocorrência, reconhecendo características de resultados mais prováveis, sem utilizar frações.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(1000, 'EF04MA27', 'Ensino Fundamental', 'Matemática', 4, 4, 'Analisar dados apresentados em tabelas simples ou de dupla entrada e em gráficos de colunas ou pictóricos, com base em informações das diferentes áreas do conhecimento, e produzir texto com a síntese de sua análise.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(1001, 'EF04MA28', 'Ensino Fundamental', 'Matemática', 4, 4, 'Realizar pesquisa envolvendo variáveis categóricas e numéricas e organizar dados coletados por meio de tabelas e gráficos de colunas simples ou agrupadas, com e sem uso de tecnologias digitais.', '2025-12-15 22:33:34', '2025-12-15 22:33:34'),
(1007, 'EF05MA06', 'Ensino Fundamental', 'Matemática', 5, 5, 'Medir e estimar comprimentos, áreas, volumes, massas e capacidades, utilizando unidades de medidas padronizadas, e resolver problemas práticos do dia a dia.', '2025-12-15 22:33:56', '2025-12-15 22:33:56'),
(1008, 'EF05MA07', 'Ensino Fundamental', 'Matemática', 5, 5, 'Identificar e utilizar propriedades de figuras geométricas planas e espaciais, explorando simetrias e construindo figuras com materiais concretos ou softwares.', '2025-12-15 22:33:56', '2025-12-15 22:33:56'),
(1009, 'EF05MA08', 'Ensino Fundamental', 'Matemática', 5, 5, 'Resolver problemas envolvendo ângulos e medir ângulos utilizando transferidor, relacionando-os a situações do cotidiano.', '2025-12-15 22:33:56', '2025-12-15 22:33:56'),
(1010, 'EF05MA09', 'Ensino Fundamental', 'Matemática', 5, 5, 'Analisar e resolver problemas de proporcionalidade direta e inversa, utilizando tabelas, gráficos e equações, aplicando em situações concretas do cotidiano.', '2025-12-15 22:33:56', '2025-12-15 22:33:56'),
(1011, 'EF05MA10', 'Ensino Fundamental', 'Matemática', 5, 5, 'Resolver problemas envolvendo porcentagem, interpretando situações de aumento, desconto e divisão proporcional, com ou sem o uso de tecnologia.', '2025-12-15 22:33:56', '2025-12-15 22:33:56'),
(1012, 'EF05MA11', 'Ensino Fundamental', 'Matemática', 5, 5, 'Resolver e elaborar problemas cuja conversão em sentença matemática envolva igualdade com operação desconhecida, utilizando diferentes estratégias de cálculo.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1013, 'EF05MA12', 'Ensino Fundamental', 'Matemática', 5, 5, 'Resolver problemas que envolvam variação de proporcionalidade direta entre duas grandezas, aplicando a relação para cálculo de valores e medidas em situações do cotidiano.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1014, 'EF05MA13', 'Ensino Fundamental', 'Matemática', 5, 5, 'Resolver problemas que envolvam a partilha de quantidades em partes desiguais, compreendendo a razão entre as partes e a relação com o todo.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1015, 'EF05MA14', 'Ensino Fundamental', 'Matemática', 5, 5, 'Utilizar e interpretar diferentes representações para localização de objetos no plano, incluindo mapas, planilhas e coordenadas cartesianas.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1016, 'EF05MA15', 'Ensino Fundamental', 'Matemática', 5, 5, 'Representar e interpretar a localização ou movimentação de objetos no plano cartesiano (1º quadrante), indicando mudanças de direção, sentido e giros.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1017, 'EF05MA16', 'Ensino Fundamental', 'Matemática', 5, 5, 'Associar figuras espaciais a suas planificações (prismas, pirâmides, cilindros e cones) e analisar, nomear e comparar seus atributos.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1018, 'EF05MA17', 'Ensino Fundamental', 'Matemática', 5, 5, 'Reconhecer, nomear e comparar polígonos, considerando lados, vértices e ângulos, e desenhá-los utilizando materiais de desenho ou tecnologia digital.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1019, 'EF05MA18', 'Ensino Fundamental', 'Matemática', 5, 5, 'Reconhecer congruência de ângulos e proporcionalidade entre lados correspondentes de figuras poligonais em situações de ampliação ou redução.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1020, 'EF05MA19', 'Ensino Fundamental', 'Matemática', 5, 5, 'Resolver problemas envolvendo medidas de comprimento, área, massa, tempo, temperatura e capacidade, utilizando conversões entre unidades usuais.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1021, 'EF05MA20', 'Ensino Fundamental', 'Matemática', 5, 5, 'Concluir, por meio de investigações, que figuras com mesmo perímetro podem ter áreas diferentes e que figuras com mesma área podem ter perímetros diferentes.', '2025-12-15 22:34:20', '2025-12-15 22:34:20'),
(1022, 'EF05MA21', 'Ensino Fundamental', 'Matemática', 5, 5, 'Reconhecer volume como grandeza associada a sólidos geométricos e medir volumes por empilhamento de cubos ou objetos concretos.', '2025-12-15 22:35:01', '2025-12-15 22:35:01'),
(1023, 'EF05MA22', 'Ensino Fundamental', 'Matemática', 5, 5, 'Apresentar todos os possíveis resultados de um experimento aleatório, estimando se são equiprováveis ou não.', '2025-12-15 22:35:01', '2025-12-15 22:35:01'),
(1024, 'EF05MA23', 'Ensino Fundamental', 'Matemática', 5, 5, 'Determinar a probabilidade de ocorrência de um resultado em eventos aleatórios equiprováveis.', '2025-12-15 22:35:01', '2025-12-15 22:35:01'),
(1025, 'EF05MA24', 'Ensino Fundamental', 'Matemática', 5, 5, 'Interpretar dados estatísticos apresentados em textos, tabelas e gráficos (colunas ou linhas), sintetizando conclusões.', '2025-12-15 22:35:01', '2025-12-15 22:35:01'),
(1026, 'EF05MA25', 'Ensino Fundamental', 'Matemática', 5, 5, 'Realizar pesquisa envolvendo variáveis categóricas e numéricas, organizar dados em tabelas e gráficos (colunas, pictóricos e de linhas) e apresentar texto sobre finalidade e síntese dos resultados.', '2025-12-15 22:35:01', '2025-12-15 22:35:01'),
(1032, 'EF01CI06', 'Ensino Fundamental', 'Ciências', 1, 1, 'Selecionar exemplos de como a sucessão de dias e noites orienta o ritmo de atividades diárias de seres humanos e de outros seres vivos.', '2025-12-15 22:35:25', '2025-12-15 22:35:25'),
(1038, 'EF02CI06', 'Ensino Fundamental', 'Ciências', 2, 2, 'Identificar as principais partes de uma planta (raiz, caule, folhas, flores e frutos) e a função desempenhada por cada uma delas, analisando as relações com o ambiente e outros seres vivos.', '2025-12-15 22:35:25', '2025-12-15 22:35:25'),
(1039, 'EF02CI07', 'Ensino Fundamental', 'Ciências', 2, 2, 'Descrever as posições do Sol em diferentes horários do dia e associá-las ao tamanho da sombra projetada.', '2025-12-15 22:35:25', '2025-12-15 22:35:25');
INSERT INTO `habilidades_bncc` (`id`, `codigo_bncc`, `etapa`, `componente`, `ano_inicio`, `ano_fim`, `descricao`, `created_at`, `updated_at`) VALUES
(1040, 'EF02CI08', 'Ensino Fundamental', 'Ciências', 2, 2, 'Comparar o efeito da radiação solar (aquecimento e reflexão) em diferentes tipos de superfície (água, areia, solo, superfícies escura, clara e metálica).', '2025-12-15 22:35:25', '2025-12-15 22:35:25'),
(1046, 'EF03CI06', 'Ensino Fundamental', 'Ciências', 3, 3, 'Descrever mudanças de posição do Sol ao longo do dia e relacionar com a duração e posição das sombras.', '2025-12-15 22:35:46', '2025-12-15 22:35:46'),
(1047, 'EF03CI07', 'Ensino Fundamental', 'Ciências', 3, 3, 'Comparar temperaturas de diferentes superfícies expostas à luz do Sol e relacionar com absorção ou reflexão de calor.', '2025-12-15 22:35:46', '2025-12-15 22:35:46'),
(1048, 'EF03CI08', 'Ensino Fundamental', 'Ciências', 3, 3, 'Observar e registrar mudanças nos fenômenos naturais do dia a dia, como chuvas, ventos e crescimento de plantas.', '2025-12-15 22:35:46', '2025-12-15 22:35:46'),
(1049, 'EF03CI09', 'Ensino Fundamental', 'Ciências', 3, 3, 'Identificar práticas de preservação ambiental no entorno, como reciclagem, economia de água e cuidado com os animais.', '2025-12-15 22:35:46', '2025-12-15 22:35:46'),
(1050, 'EF03CI10', 'Ensino Fundamental', 'Ciências', 3, 3, 'Reconhecer diferentes estados físicos da água (líquido, sólido e gasoso) e as mudanças entre eles (fusão, solidificação, evaporação e condensação).', '2025-12-15 22:35:46', '2025-12-15 22:35:46'),
(1056, 'EF04CI06', 'Ensino Fundamental', 'Ciências', 4, 4, 'Relacionar a participação de fungos e bactérias no processo de decomposição e reconhecer sua importância ambiental.', '2025-12-15 22:36:08', '2025-12-15 22:36:08'),
(1057, 'EF04CI07', 'Ensino Fundamental', 'Ciências', 4, 4, 'Verificar a participação de microrganismos na produção de alimentos, combustíveis e medicamentos.', '2025-12-15 22:36:08', '2025-12-15 22:36:08'),
(1058, 'EF04CI08', 'Ensino Fundamental', 'Ciências', 4, 4, 'Propor atitudes e medidas de prevenção de doenças com base no conhecimento das formas de transmissão de microrganismos (vírus, bactérias e protozoários).', '2025-12-15 22:36:08', '2025-12-15 22:36:08'),
(1059, 'EF04CI09', 'Ensino Fundamental', 'Ciências', 4, 4, 'Identificar os pontos cardeais usando a sombra de uma vara (gnômon) e outras referências.', '2025-12-15 22:36:08', '2025-12-15 22:36:08'),
(1060, 'EF04CI10', 'Ensino Fundamental', 'Ciências', 4, 4, 'Comparar as indicações dos pontos cardeais obtidas por observação de sombras e por bússola.', '2025-12-15 22:36:08', '2025-12-15 22:36:08'),
(1061, 'EF04CI11', 'Ensino Fundamental', 'Ciências', 4, 4, 'Associar os movimentos cíclicos da Lua e da Terra a períodos de tempo regulares, aplicando esse conhecimento na construção de calendários.', '2025-12-15 22:36:08', '2025-12-15 22:36:08'),
(1067, 'EF05CI06', 'Ensino Fundamental', 'Ciências', 5, 5, 'Selecionar argumentos que justifiquem por que os sistemas digestório e respiratório são corresponsáveis pelo processo de nutrição, com base nas funções desses sistemas.', '2025-12-15 22:36:52', '2025-12-15 22:36:52'),
(1068, 'EF05CI07', 'Ensino Fundamental', 'Ciências', 5, 5, 'Justificar a relação entre o funcionamento do sistema circulatório, distribuição de nutrientes e eliminação de resíduos.', '2025-12-15 22:36:52', '2025-12-15 22:36:52'),
(1069, 'EF05CI08', 'Ensino Fundamental', 'Ciências', 5, 5, 'Organizar um cardápio equilibrado considerando grupos alimentares, necessidades individuais e atividades realizadas para manutenção da saúde.', '2025-12-15 22:36:52', '2025-12-15 22:36:52'),
(1070, 'EF05CI09', 'Ensino Fundamental', 'Ciências', 5, 5, 'Discutir a ocorrência de distúrbios nutricionais entre crianças e jovens a partir da análise de hábitos alimentares e prática de atividade física.', '2025-12-15 22:36:52', '2025-12-15 22:36:52'),
(1071, 'EF05CI10', 'Ensino Fundamental', 'Ciências', 5, 5, 'Identificar algumas constelações no céu com apoio de mapas celestes, aplicativos digitais e períodos do ano em que são visíveis no início da noite.', '2025-12-15 22:36:52', '2025-12-15 22:36:52'),
(1072, 'EF05CI11', 'Ensino Fundamental', 'Ciências', 5, 5, 'Associar o movimento diário do Sol e das estrelas ao movimento de rotação da Terra.', '2025-12-15 22:36:52', '2025-12-15 22:36:52'),
(1073, 'EF05CI12', 'Ensino Fundamental', 'Ciências', 5, 5, 'Concluir sobre a periodicidade das fases da Lua com base na observação e registro ao longo de, pelo menos, dois meses.', '2025-12-15 22:36:52', '2025-12-15 22:36:52'),
(1074, 'EF05CI13', 'Ensino Fundamental', 'Ciências', 5, 5, 'Projetar e construir dispositivos para observação à distância, ampliação ou registro de imagens, e discutir seus usos sociais.', '2025-12-15 22:36:52', '2025-12-15 22:36:52'),
(1081, 'EF05GE07', 'Ensino Fundamental', 'Geografia', 5, 5, 'Discutir questões socioambientais locais e globais e propor atitudes de preservação ambiental e cidadania.', '2025-12-15 22:37:12', '2025-12-15 22:37:12'),
(1082, 'EF05GE08', 'Ensino Fundamental', 'Geografia', 5, 5, 'Relacionar fatos históricos e geográficos com mudanças sociais, econômicas e culturais nas regiões estudadas.', '2025-12-15 22:37:12', '2025-12-15 22:37:12'),
(1083, 'EF05GE09', 'Ensino Fundamental', 'Geografia', 5, 5, 'Interpretar dados estatísticos e informações geográficas para compreender fenômenos naturais e sociais.', '2025-12-15 22:37:12', '2025-12-15 22:37:12'),
(1084, 'EF05GE10', 'Ensino Fundamental', 'Geografia', 5, 5, 'Elaborar mapas, croquis e representações gráficas do espaço geográfico, indicando elementos naturais e humanos.', '2025-12-15 22:37:12', '2025-12-15 22:37:12'),
(1091, 'EF05HI07', 'Ensino Fundamental', 'História', 5, 5, 'Identificar os diferentes tipos de energia utilizados na produção industrial, agrícola e extrativa e no cotidiano das populações.', '2025-12-15 22:37:33', '2025-12-15 22:37:33'),
(1092, 'EF05HI08', 'Ensino Fundamental', 'História', 5, 5, 'Analisar transformações de paisagens nas cidades, comparando sequência de fotografias, fotografias aéreas e imagens de satélite de épocas diferentes.', '2025-12-15 22:37:33', '2025-12-15 22:37:33'),
(1093, 'EF05HI09', 'Ensino Fundamental', 'História', 5, 5, 'Estabelecer conexões e hierarquias entre diferentes cidades, utilizando mapas temáticos e representações gráficas.', '2025-12-15 22:37:33', '2025-12-15 22:37:33'),
(1094, 'EF05HI10', 'Ensino Fundamental', 'História', 5, 5, 'Reconhecer e comparar atributos da qualidade ambiental e algumas formas de poluição dos cursos de água e dos oceanos (esgotos, efluentes industriais, marés negras etc.).', '2025-12-15 22:37:33', '2025-12-15 22:37:33'),
(1095, 'EF05HI11', 'Ensino Fundamental', 'História', 5, 5, 'Identificar e descrever problemas ambientais que ocorrem no entorno da escola e da residência (lixões, indústrias poluentes, destruição do patrimônio histórico etc.), propondo soluções (inclusive tecnológicas) para esses problemas.', '2025-12-15 22:37:33', '2025-12-15 22:37:33'),
(1096, 'EF05HI12', 'Ensino Fundamental', 'História', 5, 5, 'Identificar órgãos do poder público e canais de participação social responsáveis por buscar soluções para a melhoria da qualidade de vida (em áreas como meio ambiente, mobilidade, moradia e direito à cidade) e discutir as propostas implementadas por esses órgãos que afetam a comunidade em que vive.', '2025-12-15 22:37:33', '2025-12-15 22:37:33'),
(1142, 'EF05HI13', 'Ensino Fundamental', 'História', 5, 5, 'Analisar a participação de diferentes grupos sociais na construção da sociedade brasileira.', '2025-12-15 22:38:51', '2025-12-15 22:38:51'),
(1143, 'EF05HI14', 'Ensino Fundamental', 'História', 5, 5, 'Identificar causas e consequências da abolição da escravatura e transformações sociais do período pós-abolição.', '2025-12-15 22:38:51', '2025-12-15 22:38:51'),
(1144, 'EF05HI15', 'Ensino Fundamental', 'História', 5, 5, 'Compreender o papel das crianças e adolescentes na história do Brasil, considerando direitos e educação ao longo do tempo.', '2025-12-15 22:38:51', '2025-12-15 22:38:51'),
(1145, 'EF05HI16', 'Ensino Fundamental', 'História', 5, 5, 'Relacionar acontecimentos históricos com datas, períodos e marcos importantes na história do Brasil.', '2025-12-15 22:38:51', '2025-12-15 22:38:51'),
(1146, 'EF05HI17', 'Ensino Fundamental', 'História', 5, 5, 'Produzir relatos e registros de fatos históricos a partir de diferentes fontes e interpretações.', '2025-12-15 22:38:51', '2025-12-15 22:38:51'),
(1147, 'EF05HI18', 'Ensino Fundamental', 'História', 5, 5, 'Compreender que a História é construída a partir de múltiplas perspectivas e interpretações.', '2025-12-15 22:38:51', '2025-12-15 22:38:51'),
(1148, 'EF05HI19', 'Ensino Fundamental', 'História', 5, 5, 'Identificar elementos culturais, sociais e econômicos que influenciam transformações históricas.', '2025-12-15 22:38:51', '2025-12-15 22:38:51'),
(1149, 'EF05HI20', 'Ensino Fundamental', 'História', 5, 5, 'Reconhecer a importância da memória coletiva e da preservação de patrimônios históricos.', '2025-12-15 22:38:51', '2025-12-15 22:38:51'),
(1160, 'EF05GE11', 'Ensino Fundamental', 'Geografia', 5, 5, 'Compreender os processos de produção e distribuição de bens e serviços no território.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1161, 'EF05GE12', 'Ensino Fundamental', 'Geografia', 5, 5, 'Interpretar informações sobre população, migrações e densidade demográfica.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1162, 'EF05GE13', 'Ensino Fundamental', 'Geografia', 5, 5, 'Relacionar diferentes formas de ocupação do solo com impactos ambientais.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1163, 'EF05GE14', 'Ensino Fundamental', 'Geografia', 5, 5, 'Compreender os conceitos de território, fronteira e região.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1164, 'EF05GE15', 'Ensino Fundamental', 'Geografia', 5, 5, 'Analisar problemas socioambientais locais e globais e propor soluções.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1165, 'EF05GE16', 'Ensino Fundamental', 'Geografia', 5, 5, 'Reconhecer a importância de políticas públicas e iniciativas comunitárias na gestão do espaço.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1166, 'EF05GE17', 'Ensino Fundamental', 'Geografia', 5, 5, 'Identificar e analisar a relação entre atividades econômicas e características naturais do espaço.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1167, 'EF05GE18', 'Ensino Fundamental', 'Geografia', 5, 5, 'Compreender os impactos da urbanização, industrialização e modernização no território.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1168, 'EF05GE19', 'Ensino Fundamental', 'Geografia', 5, 5, 'Analisar a relação entre transportes, comunicação e circulação de pessoas e mercadorias.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1169, 'EF05GE20', 'Ensino Fundamental', 'Geografia', 5, 5, 'Produzir registros, mapas e relatos sobre o espaço vivido, utilizando diferentes fontes e tecnologias.', '2025-12-15 22:39:23', '2025-12-15 22:39:23'),
(1195, 'EF06GE06', 'Ensino Fundamental', 'Geografia', 6, 6, 'Compreender conceitos de latitude, longitude, meridianos e paralelos para localização geográfica.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1196, 'EF06GE07', 'Ensino Fundamental', 'Geografia', 6, 6, 'Reconhecer a importância dos recursos naturais e sua exploração sustentável.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1197, 'EF06GE08', 'Ensino Fundamental', 'Geografia', 6, 6, 'Analisar fenômenos naturais e sua relação com a vida humana.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1198, 'EF06GE09', 'Ensino Fundamental', 'Geografia', 6, 6, 'Identificar diferentes formas de representação cartográfica e interpretar mapas, gráficos e imagens.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1199, 'EF06GE10', 'Ensino Fundamental', 'Geografia', 6, 6, 'Compreender a dinâmica populacional, migrações e distribuição da população no espaço geográfico.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1200, 'EF06GE11', 'Ensino Fundamental', 'Geografia', 6, 6, 'Analisar a relação entre clima, relevo, vegetação e ocupação humana.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1201, 'EF06GE12', 'Ensino Fundamental', 'Geografia', 6, 6, 'Identificar problemas ambientais locais, regionais e globais e suas possíveis soluções.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1202, 'EF06GE13', 'Ensino Fundamental', 'Geografia', 6, 6, 'Compreender processos de globalização e suas consequências para diferentes regiões.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1203, 'EF06GE14', 'Ensino Fundamental', 'Geografia', 6, 6, 'Reconhecer a diversidade cultural e econômica entre diferentes regiões do Brasil e do mundo.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1204, 'EF06GE15', 'Ensino Fundamental', 'Geografia', 6, 6, 'Analisar os efeitos da urbanização e industrialização no ambiente e na sociedade.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1205, 'EF06GE16', 'Ensino Fundamental', 'Geografia', 6, 6, 'Compreender a importância da conservação ambiental e do desenvolvimento sustentável.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1206, 'EF06GE17', 'Ensino Fundamental', 'Geografia', 6, 6, 'Relacionar eventos históricos e sociais com mudanças no espaço geográfico.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1207, 'EF06GE18', 'Ensino Fundamental', 'Geografia', 6, 6, 'Interpretar diferentes formas de transporte, comunicação e infraestrutura no espaço geográfico.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1208, 'EF06GE19', 'Ensino Fundamental', 'Geografia', 6, 6, 'Reconhecer a interdependência econômica e ambiental entre países e regiões.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1209, 'EF06GE20', 'Ensino Fundamental', 'Geografia', 6, 6, 'Produzir registros, mapas e projetos que representem e analisem o espaço geográfico.', '2025-12-15 22:40:15', '2025-12-15 22:40:15'),
(1215, 'EF06HI06', 'Ensino Fundamental', 'História', 6, 6, 'Compreender a relação entre povos antigos e o ambiente natural.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1216, 'EF06HI07', 'Ensino Fundamental', 'História', 6, 6, 'Analisar a organização social e política de diferentes sociedades ao longo da história.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1217, 'EF06HI08', 'Ensino Fundamental', 'História', 6, 6, 'Identificar conflitos, guerras e processos de conquista e colonização.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1218, 'EF06HI09', 'Ensino Fundamental', 'História', 6, 6, 'Compreender a evolução de sistemas de escrita e registro histórico.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1219, 'EF06HI10', 'Ensino Fundamental', 'História', 6, 6, 'Analisar mudanças culturais, tecnológicas e econômicas ao longo do tempo.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1220, 'EF06HI11', 'Ensino Fundamental', 'História', 6, 6, 'Compreender a história da América e do Brasil, incluindo sociedades indígenas e colonização.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1221, 'EF06HI12', 'Ensino Fundamental', 'História', 6, 6, 'Relacionar acontecimentos históricos com transformações sociais e culturais.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1222, 'EF06HI13', 'Ensino Fundamental', 'História', 6, 6, 'Analisar o papel de líderes e instituições no desenvolvimento histórico.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1223, 'EF06HI14', 'Ensino Fundamental', 'História', 6, 6, 'Identificar direitos e deveres de diferentes grupos sociais ao longo da história.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1224, 'EF06HI15', 'Ensino Fundamental', 'História', 6, 6, 'Compreender processos de resistência e lutas por liberdade em diferentes períodos.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1225, 'EF06HI16', 'Ensino Fundamental', 'História', 6, 6, 'Analisar a importância da memória e preservação do patrimônio histórico.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1226, 'EF06HI17', 'Ensino Fundamental', 'História', 6, 6, 'Compreender a influência de fatores econômicos, religiosos e culturais na história.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1227, 'EF06HI18', 'Ensino Fundamental', 'História', 6, 6, 'Relacionar acontecimentos históricos à vida cotidiana das pessoas.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1228, 'EF06HI19', 'Ensino Fundamental', 'História', 6, 6, 'Analisar processos de escravidão, exploração e direitos humanos ao longo do tempo.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1229, 'EF06HI20', 'Ensino Fundamental', 'História', 6, 6, 'Desenvolver a capacidade de argumentação e interpretação histórica com base em fontes diversas.', '2025-12-15 22:40:47', '2025-12-15 22:40:47'),
(1235, 'EF07HI06', 'Ensino Fundamental', 'História', 7, 7, 'Compreender processos de exploração, colonização e escravização no contexto mundial.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1236, 'EF07HI07', 'Ensino Fundamental', 'História', 7, 7, 'Identificar características culturais e científicas do Renascimento e da Reforma.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1237, 'EF07HI08', 'Ensino Fundamental', 'História', 7, 7, 'Analisar o impacto das grandes navegações e descobertas geográficas.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1238, 'EF07HI09', 'Ensino Fundamental', 'História', 7, 7, 'Compreender a formação dos Estados modernos e os conflitos pelo poder.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1239, 'EF07HI10', 'Ensino Fundamental', 'História', 7, 7, 'Analisar a economia colonial e os sistemas de trabalho forçado.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1240, 'EF07HI11', 'Ensino Fundamental', 'História', 7, 7, 'Compreender o papel das revoltas e conflitos sociais na história.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1241, 'EF07HI12', 'Ensino Fundamental', 'História', 7, 7, 'Identificar mudanças demográficas e urbanas em diferentes períodos.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1242, 'EF07HI13', 'Ensino Fundamental', 'História', 7, 7, 'Analisar a importância das artes e da cultura como expressão histórica.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1243, 'EF07HI14', 'Ensino Fundamental', 'História', 7, 7, 'Compreender a diversidade cultural de povos indígenas, africanos e europeus.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1244, 'EF07HI15', 'Ensino Fundamental', 'História', 7, 7, 'Relacionar acontecimentos históricos ao contexto atual, identificando continuidades e rupturas.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1245, 'EF07HI16', 'Ensino Fundamental', 'História', 7, 7, 'Desenvolver habilidades de análise crítica de fontes históricas diversas.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1246, 'EF07HI17', 'Ensino Fundamental', 'História', 7, 7, 'Compreender os processos de industrialização e suas consequências sociais e econômicas.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1247, 'EF07HI18', 'Ensino Fundamental', 'História', 7, 7, 'Analisar movimentos de resistência e luta por direitos em diferentes períodos.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1248, 'EF07HI19', 'Ensino Fundamental', 'História', 7, 7, 'Identificar a importância da memória e preservação de patrimônios históricos e culturais.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1249, 'EF07HI20', 'Ensino Fundamental', 'História', 7, 7, 'Desenvolver a capacidade de argumentação e interpretação histórica com base em múltiplas fontes.', '2025-12-15 22:41:09', '2025-12-15 22:41:09'),
(1255, 'EF08HI06', 'Ensino Fundamental', 'História', 8, 8, 'Compreender a expansão colonial e imperialista europeia e suas consequências globais.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1256, 'EF08HI07', 'Ensino Fundamental', 'História', 8, 8, 'Identificar movimentos sociais e políticos de resistência e emancipação.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1257, 'EF08HI08', 'Ensino Fundamental', 'História', 8, 8, 'Analisar as relações econômicas e comerciais entre diferentes regiões no período moderno.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1258, 'EF08HI09', 'Ensino Fundamental', 'História', 8, 8, 'Compreender o surgimento e desenvolvimento do capitalismo industrial.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1259, 'EF08HI10', 'Ensino Fundamental', 'História', 8, 8, 'Identificar o papel da ciência e da cultura no processo de modernização.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1260, 'EF08HI11', 'Ensino Fundamental', 'História', 8, 8, 'Analisar a formação de Estados nacionais e as disputas de poder na Europa e no mundo.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1261, 'EF08HI12', 'Ensino Fundamental', 'História', 8, 8, 'Compreender o impacto da escravidão, imigrações e migrações nos contextos sociais.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1262, 'EF08HI13', 'Ensino Fundamental', 'História', 8, 8, 'Relacionar eventos históricos aos direitos humanos e às mudanças sociais.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1263, 'EF08HI14', 'Ensino Fundamental', 'História', 8, 8, 'Desenvolver habilidades de leitura crítica e interpretação de diferentes fontes históricas.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1264, 'EF08HI15', 'Ensino Fundamental', 'História', 8, 8, 'Compreender a importância da cidadania e participação política no contexto histórico.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1265, 'EF08HI16', 'Ensino Fundamental', 'História', 8, 8, 'Analisar conflitos, guerras e transformações territoriais no período moderno.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1266, 'EF08HI17', 'Ensino Fundamental', 'História', 8, 8, 'Identificar processos de urbanização e industrialização e suas consequências sociais.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1267, 'EF08HI18', 'Ensino Fundamental', 'História', 8, 8, 'Compreender movimentos culturais e artísticos como reflexo das mudanças históricas.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1268, 'EF08HI19', 'Ensino Fundamental', 'História', 8, 8, 'Analisar a diversidade cultural e social em diferentes regiões e períodos.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1269, 'EF08HI20', 'Ensino Fundamental', 'História', 8, 8, 'Desenvolver capacidade de argumentação e síntese ao interpretar eventos históricos.', '2025-12-15 22:41:43', '2025-12-15 22:41:43'),
(1280, 'EF09HI11', 'Ensino Fundamental', 'História', 9, 9, 'Analisar os processos de globalização e suas implicações econômicas, sociais e culturais.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1281, 'EF09HI12', 'Ensino Fundamental', 'História', 9, 9, 'Compreender as transformações políticas e sociais na América Latina e Brasil no século XX.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1282, 'EF09HI13', 'Ensino Fundamental', 'História', 9, 9, 'Identificar direitos humanos e cidadania como conquistas históricas e sociais.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1283, 'EF09HI14', 'Ensino Fundamental', 'História', 9, 9, 'Analisar conflitos contemporâneos e suas raízes históricas.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1284, 'EF09HI15', 'Ensino Fundamental', 'História', 9, 9, 'Compreender o impacto das tecnologias e ciência no mundo moderno.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1285, 'EF09HI16', 'Ensino Fundamental', 'História', 9, 9, 'Desenvolver habilidades de leitura crítica e interpretação de fontes históricas variadas.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1286, 'EF09HI17', 'Ensino Fundamental', 'História', 9, 9, 'Analisar a diversidade cultural e social em contextos históricos distintos.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1287, 'EF09HI18', 'Ensino Fundamental', 'História', 9, 9, 'Compreender o papel dos movimentos sociais e políticos na construção das sociedades modernas.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1288, 'EF09HI19', 'Ensino Fundamental', 'História', 9, 9, 'Identificar processos de urbanização, industrialização e migrações internas e externas.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1289, 'EF09HI20', 'Ensino Fundamental', 'História', 9, 9, 'Desenvolver capacidade de argumentação, síntese e análise crítica ao interpretar eventos históricos.', '2025-12-15 22:42:06', '2025-12-15 22:42:06'),
(1290, 'EMH01', 'Ensino Médio', 'História', 1, 1, 'Compreender a formação do mundo moderno, considerando o Renascimento, a Reforma e as monarquias nacionais.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1291, 'EMH02', 'Ensino Médio', 'História', 1, 1, 'Analisar a expansão marítima europeia e o início do colonialismo.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1292, 'EMH03', 'Ensino Médio', 'História', 1, 1, 'Identificar as transformações sociais e econômicas na Europa do século XVIII, incluindo a Revolução Industrial.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1293, 'EMH04', 'Ensino Médio', 'História', 1, 1, 'Compreender as revoluções políticas da Idade Moderna e Contemporânea, incluindo a Revolução Francesa.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1294, 'EMH05', 'Ensino Médio', 'História', 1, 1, 'Analisar o processo de independência das colônias americanas e africanas.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1295, 'EMH06', 'Ensino Médio', 'História', 1, 1, 'Compreender o impacto da escravidão e das sociedades coloniais no desenvolvimento histórico global.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1296, 'EMH07', 'Ensino Médio', 'História', 1, 1, 'Identificar a organização social, econômica e política nas sociedades pré-industriais e industriais.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1297, 'EMH08', 'Ensino Médio', 'História', 1, 1, 'Analisar o surgimento do capitalismo e suas implicações sociais e econômicas.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1298, 'EMH09', 'Ensino Médio', 'História', 1, 1, 'Compreender os movimentos de imigração e urbanização e seus efeitos na sociedade contemporânea.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1299, 'EMH10', 'Ensino Médio', 'História', 1, 1, 'Desenvolver habilidades de análise crítica de fontes primárias e secundárias para interpretar eventos históricos.', '2025-12-15 22:45:26', '2025-12-15 22:45:26'),
(1300, 'EMH11', 'Ensino Médio', 'História', 1, 1, 'Compreender os processos de formação das sociedades brasileiras e suas especificidades regionais.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1301, 'EMH12', 'Ensino Médio', 'História', 1, 1, 'Analisar a participação do Brasil nas grandes transformações políticas, econômicas e sociais do século XX.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1302, 'EMH13', 'Ensino Médio', 'História', 1, 1, 'Refletir sobre os movimentos sociais e culturais que influenciaram a construção da cidadania.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1303, 'EMH14', 'Ensino Médio', 'História', 1, 1, 'Identificar as continuidades e rupturas nas estruturas de poder ao longo da história brasileira.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1304, 'EMH15', 'Ensino Médio', 'História', 1, 1, 'Analisar conflitos e negociações entre grupos sociais em diferentes períodos históricos.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1305, 'EMH16', 'Ensino Médio', 'História', 1, 1, 'Compreender a diversidade cultural e étnica como elemento constitutivo da sociedade brasileira.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1306, 'EMH17', 'Ensino Médio', 'História', 1, 1, 'Interpretar fontes históricas diversas para construir argumentações fundamentadas.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1307, 'EMH18', 'Ensino Médio', 'História', 1, 1, 'Analisar os impactos da globalização nas sociedades contemporâneas.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1308, 'EMH19', 'Ensino Médio', 'História', 1, 1, 'Identificar transformações no mundo do trabalho e nas relações econômicas ao longo do tempo.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1309, 'EMH20', 'Ensino Médio', 'História', 1, 1, 'Desenvolver a capacidade de reflexão crítica sobre o passado e suas consequências para o presente.', '2025-12-15 22:46:44', '2025-12-15 22:46:44'),
(1310, 'EMH21', 'Ensino Médio', 'História', 1, 1, 'Compreender o papel das instituições políticas e sociais na formação das sociedades modernas.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1311, 'EMH22', 'Ensino Médio', 'História', 1, 1, 'Analisar os processos de colonização e suas consequências para as populações nativas e africanas.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1312, 'EMH23', 'Ensino Médio', 'História', 1, 1, 'Identificar e interpretar diferentes formas de resistência e mobilização social ao longo da história.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1313, 'EMH24', 'Ensino Médio', 'História', 1, 1, 'Refletir sobre a construção da memória histórica e suas diferentes interpretações.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1314, 'EMH25', 'Ensino Médio', 'História', 1, 1, 'Analisar o desenvolvimento científico e tecnológico e suas implicações sociais e culturais.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1315, 'EMH26', 'Ensino Médio', 'História', 1, 1, 'Compreender a evolução das relações internacionais e dos conflitos globais.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1316, 'EMH27', 'Ensino Médio', 'História', 1, 1, 'Interpretar transformações culturais e artísticas como reflexos de contextos históricos.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1317, 'EMH28', 'Ensino Médio', 'História', 1, 1, 'Analisar as mudanças nas estruturas familiares e sociais ao longo do tempo.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1318, 'EMH29', 'Ensino Médio', 'História', 1, 1, 'Compreender a importância da legislação e dos direitos civis na história contemporânea.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1319, 'EMH30', 'Ensino Médio', 'História', 1, 1, 'Desenvolver habilidades de síntese e comparação entre diferentes períodos históricos.', '2025-12-15 22:47:10', '2025-12-15 22:47:10'),
(1320, 'EMH31', 'Ensino Médio', 'História', 1, 1, 'Analisar o impacto das ideologias políticas na história contemporânea.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1321, 'EMH32', 'Ensino Médio', 'História', 1, 1, 'Compreender os processos de globalização e suas repercussões sociais, econômicas e culturais.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1322, 'EMH33', 'Ensino Médio', 'História', 1, 1, 'Investigar as relações entre cultura, religião e poder ao longo da história.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1323, 'EMH34', 'Ensino Médio', 'História', 1, 1, 'Interpretar documentos históricos e utilizar diferentes fontes para construção do conhecimento.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1324, 'EMH35', 'Ensino Médio', 'História', 1, 1, 'Compreender as transformações na economia mundial e seus impactos locais.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1325, 'EMH36', 'Ensino Médio', 'História', 1, 1, 'Analisar conflitos sociais e políticos e suas consequências para a sociedade.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1326, 'EMH37', 'Ensino Médio', 'História', 1, 1, 'Refletir sobre movimentos sociais e culturais que influenciaram mudanças históricas.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1327, 'EMH38', 'Ensino Médio', 'História', 1, 1, 'Desenvolver capacidade crítica para relacionar passado e presente.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1328, 'EMH39', 'Ensino Médio', 'História', 1, 1, 'Compreender a evolução dos direitos humanos e sua aplicação na sociedade.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1329, 'EMH40', 'Ensino Médio', 'História', 1, 1, 'Analisar a diversidade cultural e étnica e sua influência na formação das sociedades.', '2025-12-15 22:47:31', '2025-12-15 22:47:31'),
(1668, 'EF02LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Ler e compreender com certa autonomia cantigas, letras de canção, dentre outros gêneros do campo da vida cotidiana, considerando a situação comunicativa e o tema/assunto do texto e relacionando sua forma de organização à sua finalidade.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1669, 'EF02LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir bilhetes e cartas, em meio impresso e/ou digital, dentre outros gêneros do campo da vida cotidiana, considerando a situação comunicativa e o tema/assunto/finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1670, 'EF02LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir pequenos relatos de observação de processos, de fatos, de experiências pessoais, mantendo as características do gênero, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1671, 'EF02LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Cantar cantigas e canções, obedecendo ao ritmo e à melodia.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1672, 'EF02LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Identificar e reproduzir, em  bilhetes, recados, avisos, cartas, e-mails, receitas (modo de fazer), relatos (digitais ou impressos), a formatação e diagramação específica de cada um desses gêneros.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1673, 'EF02LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Identificar e reproduzir, em relatos de experiências pessoais, a sequência dos fatos, utilizando expressões que marquem a passagem do tempo (“antes”, “depois”, “ontem”, “hoje”, “amanhã”, “outro dia”, “antigamente”, “há muito tempo” etc.), e o nível de informatividade necessário.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1674, 'EF12LP11', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Escrever, em colaboração com os colegas e com a ajuda do professor, fotolegendas em notícias, manchetes e lides em notícias, álbum de fotos digital noticioso e notícias curtas para público infantil, digitais ou impressos, dentre outros gêneros do campo jornalístico, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1675, 'EF12LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Escrever, em colaboração com os colegas e com a ajuda do professor, slogans,* anúncios publicitários e textos de campanhas de conscientização destinados ao público* infantil, dentre outros gêneros do campo publicitário, considerando a situação comunicativa e o tema/ assunto/finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1676, 'EF01LP21', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Escrever, em colaboração com os colegas e com a ajuda do professor, listas de regras e regulamentos que organizam a vida na comunidade escolar, dentre outros gêneros do campo da atuação cidadã, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1677, 'EF02LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir cartazes e folhetos para divulgar eventos da escola ou da comunidade, utilizando linguagem persuasiva e elementos textuais e visuais (tamanho da letra, leiaute, imagens) adequados ao gênero, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1678, 'EF02LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, notícias curtas para público infantil,  para compor jornal falado que possa ser repassado oralmente ou em meio digital, em áudio ou vídeo, dentre outros gêneros do campo jornalístico, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1679, 'EF12LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Planejar, em colaboração com os colegas e com a ajuda do professor, slogans e* peça de campanha de conscientização destinada ao público infantil que possam ser repassados* oralmente por meio de ferramentas digitais, em áudio ou vídeo, considerando a situação comunicativa e o tema/assunto/finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1680, 'EF12LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Identificar e reproduzir, em fotolegendas de notícias, álbum de fotos digital noticioso, cartas de leitor (revista infantil), digitais ou impressos, a formatação e diagramação específica de cada um desses gêneros, inclusive em suas versões orais.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1681, 'EF12LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Identificar a forma de composição de slogans publicitários.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1682, 'EF12LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Identificar e reproduzir, em anúncios publicitários e textos de campanhas de conscientização destinados ao público infantil (orais e escritos, digitais ou impressos), a formatação e diagramação específica de cada um desses gêneros, inclusive o uso de imagens.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1683, 'EF12LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Ler e compreender, em colaboração com os colegas e com a ajuda do professor, enunciados de tarefas escolares, diagramas, curiosidades, pequenos relatos de experimentos, entrevistas, verbetes de enciclopédia infantil, entre outros gêneros do campo investigativo, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1684, 'EF02LP20', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Reconhecer a função de textos utilizados para apresentar informações coletadas em atividades de pesquisa (enquetes, pequenas entrevistas, registros de experimentações).,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1685, 'EF02LP21', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Explorar, com a mediação do professor, textos informativos de diferentes ambientes digitais de pesquisa, conhecendo suas possibilidades.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1686, 'EF01LP22', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, diagramas, entrevistas, curiosidades, dentre outros gêneros do campo investigativo, digitais ou impressos, considerando a situação comunicativa e o tema/assunto/finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1687, 'EF02LP22', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, pequenos relatos de experimentos, entrevistas, verbetes de enciclopédia infantil, dentre outros gêneros do campo investigativo, digitais ou impressos, considerando a situação comunicativa e o tema/assunto/finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1688, 'EF02LP23', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir, com certa autonomia, pequenos registros de observação de resultados de pesquisa, coerentes com um tema investigado.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1689, 'EF01LP23', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, entrevistas, curiosidades, dentre outros gêneros do campo investigativo, que possam ser repassados oralmente por meio de ferramentas digitais, em áudio ou vídeo, considerando a situação comunicativa e o tema/assunto/finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1690, 'EF02LP24', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, relatos de experimentos, registros de observação, entrevistas, dentre outros gêneros do campo investigativo, que possam ser repassados oralmente por meio de ferramentas digitais, em áudio ou vídeo, considerando a situação comunicativa e o tema/assunto/ finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1691, 'EF01LP24', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Identificar e reproduzir, em enunciados de tarefas escolares, diagramas, entrevistas, curiosidades, digitais ou impressos, a formatação e diagramação específica de cada um desses gêneros, inclusive em suas versões orais.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1692, 'EF02LP25', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Identificar e reproduzir, em relatos de experimentos, entrevistas, verbetes de enciclopédia infantil, digitais ou impressos, a formatação e diagramação específica de cada um desses gêneros, inclusive em suas versões orais.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1693, 'EF02LP26', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Ler e compreender, com certa autonomia, textos literários, de gêneros variados, desenvolvendo o gosto pela leitura.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1694, 'EF12LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Apreciar poemas e outros textos versificados, observando rimas, sonoridades, jogos de palavras, reconhecendo seu pertencimento ao mundo imaginário e sua dimensão de encantamento, jogo e fruição.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1695, 'EF01LP25', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Produzir, tendo o professor como escriba, recontagens de histórias lidas pelo professor, histórias imaginadas ou baseadas em livros de imagens, observando a forma de composição de textos narrativos (personagens, enredo, tempo e espaço).,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1696, 'EF02LP27', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Reescrever textos narrativos literários lidos pelo professor.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1697, 'EF01LP26', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Identificar elementos de uma narrativa lida ou escutada, incluindo personagens, enredo, tempo e espaço.', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1698, 'EF02LP28', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Reconhecer o conflito gerador de uma narrativa ficcional e sua resolução, além de palavras, expressões e frases que caracterizam personagens e ambientes.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1699, 'EF12LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Reconhecer, em textos versificados, rimas, sonoridades, jogos de palavras, palavras, expressões, comparações, relacionando-as com sensações e associações.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1700, 'EF02LP29', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Observar, em poemas visuais, o formato do texto na página, as ilustrações e outros efeitos visuais.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1701, 'EF03LP06', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Identificar a sílaba tônica em palavras, classificando-as em oxítonas, paroxítonas e proparoxítonas.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1702, 'EF03LP07', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Identificar a função na leitura e usar na escrita ponto final, ponto de interrogação, ponto de exclamação e, em diálogos (discurso direto), dois-pontos e travessão.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1703, 'EF03LP08', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Identificar e diferenciar, em textos, substantivos e verbos e suas funções na oração: agente, ação, objeto da ação.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1704, 'EF04LP06', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Identificar em textos e usar na produção textual a concordância entre substantivo ou pronome pessoal e verbo (concordância verbal).,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1705, 'EF05LP06', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Flexionar, adequadamente, na escrita e na oralidade, os verbos em concordância com pronomes pessoais/nomes sujeitos da oração.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1706, 'EF03LP09', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Identificar, em textos, adjetivos e sua função de atribuição de propriedades aos substantivos.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1707, 'EF04LP07', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Identificar em textos e usar na produção textual a concordância entre artigo, substantivo e adjetivo (concordância no grupo nominal).,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1708, 'EF05LP07', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Identificar, em textos, o uso de conjunções e a relação que estabelecem entre partes do texto: adição, oposição, tempo, causa, condição, finalidade.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1709, 'EF03LP10', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Reconhecer prefixos e sufixos produtivos na formação de palavras derivadas de substantivos, de adjetivos e de verbos, utilizando-os para compreender palavras e para formar novas palavras.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1710, 'EF04LP08', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Reconhecer e grafar, corretamente, palavras derivadas com os sufixos -agem, -oso, -eza, -izar/-isar (regulares morfológicas).,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1711, 'EF05LP08', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Diferenciar palavras primitivas, derivadas e compostas, e derivadas por adição de prefixo e de sufixo.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1712, 'EF03LP11', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Ler e compreender, com autonomia, textos injuntivos instrucionais (receitas, instruções de montagem etc.), com a estrutura própria desses textos (verbos imperativos, indicação de passos a ser seguidos) e mesclando palavras, imagens e recursos gráfico-visuais, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1713, 'EF04LP09', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Ler e compreender, com autonomia, boletos, faturas e carnês, dentre outros gêneros do campo da vida cotidiana, de acordo com as convenções do gênero (campos, itens elencados, medidas de consumo, código de barras) e considerando a situação comunicativa e a finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1714, 'EF05LP09', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Ler e compreender, com autonomia, textos instrucional de regras de jogo, dentre outros gêneros do campo da vida cotidiana, de acordo com as convenções do gênero e considerando a situação comunicativa e a finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1715, 'EF03LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Ler e compreender, com autonomia, cartas pessoais e diários, com expressão de sentimentos e opiniões, dentre outros gêneros do campo da vida cotidiana, de acordo com as convenções do gênero carta e considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1716, 'EF04LP10', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Ler e compreender, com autonomia, cartas pessoais de reclamação, dentre outros gêneros do campo da vida cotidiana, de acordo com as convenções do gênero carta e considerando a situação comunicativa e o tema/assunto/finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1717, 'EF05LP10', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Ler e compreender, com autonomia, anedotas, piadas e cartuns, dentre outros gêneros do campo da vida cotidiana, de acordo com as convenções do gênero e considerando a situação comunicativa e a finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1718, 'EF03LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Planejar e produzir cartas pessoais e diários, com expressão de sentimentos e opiniões, dentre outros gêneros do campo da vida cotidiana, de acordo com as convenções dos gêneros carta e diário e considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1719, 'EF04LP11', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Planejar e produzir, com autonomia, cartas pessoais de reclamação, dentre outros gêneros do campo da vida cotidiana, de acordo com as convenções do gênero carta e com a estrutura própria desses textos (problema, opinião, argumentos), considerando a situação comunicativa e o tema/assunto/finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1720, 'EF05LP11', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Registrar, com autonomia, anedotas, piadas e cartuns, dentre outros gêneros do campo da vida cotidiana, de acordo com as convenções do gênero e considerando a situação comunicativa e a finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1721, 'EF03LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Planejar e produzir textos injuntivos instrucionais, com a estrutura própria desses textos (verbos imperativos, indicação de passos a ser seguidos) e mesclando palavras, imagens e recursos gráfico-visuais, considerando a situação comunicativa e o tema/ assunto do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36');
INSERT INTO `habilidades_bncc` (`id`, `codigo_bncc`, `etapa`, `componente`, `ano_inicio`, `ano_fim`, `descricao`, `created_at`, `updated_at`) VALUES
(1722, 'EF05LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Planejar e produzir, com autonomia, textos instrucionais de regras de jogo, dentre outros gêneros do campo da vida cotidiana, de acordo com as convenções do gênero e considerando a situação comunicativa e a finalidade do texto.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1723, 'EF03LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Assistir, em vídeo digital, a programa de culinária infantil e, a partir dele, planejar e produzir receitas em áudio ou vídeo.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1724, 'EF04LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Assistir, em vídeo digital, a programa infantil com instruções de montagem, de jogos e brincadeiras e, a partir dele, planejar e produzir tutoriais em áudio ou vídeo.,', '2025-12-15 23:54:36', '2025-12-15 23:54:36'),
(1725, 'EF05LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Assistir, em vídeo  digital, a postagem de vlog infantil de críticas de brinquedos e livros de literatura infantil e, a partir dele, planejar e produzir resenhas digitais em áudio ou vídeo.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1726, 'EF03LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Identificar e reproduzir, em textos injuntivos instrucionais (receitas, instruções de montagem, digitais ou impressos), a formatação própria desses textos (verbos imperativos, indicação de passos a ser seguidos) e a diagramação específica dos textos desses gêneros (lista de ingredientes ou materiais e instruções de execução – \\\"modo de fazer\\\").,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1727, 'EF04LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Identificar e reproduzir, em textos injuntivos instrucionais (instruções de jogos digitais ou impressos), a formatação própria desses textos (verbos imperativos, indicação de passos a ser seguidos) e formato específico dos textos orais ou escritos desses gêneros (lista/ apresentação de materiais e instruções/passos de jogo).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1728, 'EF05LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Identificar e reproduzir, em textos de resenha crítica de brinquedos ou livros de literatura infantil, a formatação própria desses textos (apresentação e avaliação do produto).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1729, 'EF03LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Identificar e reproduzir, em gêneros epistolares e diários, a formatação própria desses textos (relatos de acontecimentos, expressão de vivências, emoções, opiniões ou críticas) e a diagramação específica dos textos desses gêneros (data, saudação, corpo do texto, despedida, assinatura).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1730, 'EF03LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Ler e compreender, com autonomia, cartas dirigidas a veículos da mídia impressa ou digital (cartas de leitor e de reclamação a jornais, revistas) e notícias, dentre outros gêneros do campo jornalístico, de acordo com as convenções do gênero carta e considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1731, 'EF04LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Identificar, em notícias, fatos, participantes, local e momento/tempo da ocorrência do fato noticiado.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1732, 'EF05LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Ler/assistir e compreender, com autonomia, notícias, reportagens, vídeos  em vlogs argumentativos, dentre outros gêneros do campo político-cidadão, de acordo com as convenções dos gêneros e considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1733, 'EF03LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Identificar e discutir o propósito do uso de recursos de persuasão (cores, imagens, escolha de palavras, jogo de palavras, tamanho de letras) em textos publicitários e de propaganda, como elementos de convencimento.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1734, 'EF04LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Distinguir fatos de opiniões/sugestões em textos (informativos, jornalísticos, publicitários etc.).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1735, 'EF05LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Comparar informações sobre um mesmo fato veiculadas em diferentes mídias e concluir sobre qual é mais confiável e por quê.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1736, 'EF03LP20', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Produzir cartas dirigidas a veículos da mídia impressa ou digital (cartas do leitor ou de reclamação a jornais ou revistas), dentre outros gêneros do campo político-cidadão, com opiniões e críticas, de acordo com as convenções do gênero carta e considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1737, 'EF04LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Produzir notícias sobre fatos ocorridos no universo escolar, digitais ou impressas, para o jornal da escola, noticiando os fatos e seus atores e comentando decorrências, de acordo com as convenções do gênero notícia e considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1738, 'EF05LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Produzir roteiro para edição de uma reportagem digital sobre temas de interesse da turma, a partir de buscas de informações, imagens, áudios e vídeos na internet, de acordo com as convenções do gênero e considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1739, 'EF03LP21', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Produzir anúncios publicitários, textos de campanhas de conscientização destinados ao público infantil, observando os recursos de persuasão utilizados nos textos publicitários e de propaganda  (cores, imagens, slogan, escolha de palavras, jogo de palavras, tamanho e tipo de letras, diagramação).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1740, 'EF03LP22', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Planejar e produzir, em colaboração com os colegas, telejornal para público infantil com algumas notícias e textos de campanhas que possam ser repassados oralmente ou em meio digital, em áudio ou vídeo, considerando a situação comunicativa, a organização específica da fala nesses gêneros e o tema/assunto/ finalidade dos textos.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1741, 'EF04LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Produzir jornais radiofônicos ou televisivos e entrevistas veiculadas em rádio, TV e na internet, orientando-se por roteiro ou texto e demonstrando conhecimento dos gêneros jornal falado/televisivo e entrevista.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1742, 'EF05LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Roteirizar, produzir e editar vídeo para  vlogs argumentativos sobre produtos de mídia para público infantil (filmes, desenhos  animados, HQs, games etc.), com base em conhecimentos sobre os mesmos, de acordo com as convenções do gênero e considerando a situação comunicativa e o tema/ assunto/finalidade do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1743, 'EF05LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Argumentar oralmente sobre acontecimentos de interesse social, com base em conhecimentos sobre fatos divulgados em TV, rádio, mídia impressa e digital, respeitando pontos de vista diferentes.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1744, 'EF03LP23', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Analisar o uso de adjetivos em cartas dirigidas a veículos da mídia impressa ou digital (cartas do leitor ou de reclamação a jornais ou revistas), digitais ou impressas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1745, 'EF05LP20', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Analisar a validade e força de argumentos em argumentações sobre produtos de mídia para público infantil (filmes, desenhos  animados, HQs, games etc.), com base em conhecimentos sobre os mesmos.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1746, 'EF04LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Analisar o padrão entonacional e a expressão facial e corporal de âncoras de jornais radiofônicos ou televisivos e de entrevistadores/entrevistados.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1747, 'EF05LP21', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Analisar o padrão entonacional, a expressão facial e corporal e as escolhas de variedade e registro linguísticos  de vloggers de vlogs opinativos ou argumentativos.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1748, 'EF03LP24', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Ler/ouvir e compreender, com autonomia, relatos de observações e de pesquisas em fontes de informações, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1749, 'EF04LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Ler e compreender textos expositivos de divulgação científica para crianças, considerando a situação comunicativa e o tema/ assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1750, 'EF05LP22', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Ler e compreender verbetes de dicionário, identificando a estrutura, as informações gramaticais (significado de abreviaturas) e as informações semânticas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1751, 'EF04LP20', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Reconhecer a função de gráficos, diagramas e tabelas em textos, como forma de apresentação de dados e informações.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1752, 'EF05LP23', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Comparar informações apresentadas em gráficos ou tabelas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1753, 'EF03LP25', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Planejar e produzir textos para apresentar resultados de observações e de pesquisas em fontes de informações, incluindo, quando pertinente, imagens, diagramas e gráficos ou tabelas simples, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1754, 'EF04LP21', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Planejar e produzir textos sobre temas de interesse, com base em resultados de observações e pesquisas em fontes de informações impressas ou eletrônicas, incluindo, quando pertinente, imagens e gráficos ou tabelas simples, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1755, 'EF05LP24', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Planejar e produzir texto sobre tema de interesse, organizando resultados de pesquisa em fontes de informação impressas ou digitais, incluindo imagens e gráficos ou tabelas, considerando a situação comunicativa e o tema/assunto do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1756, 'EF04LP22', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Planejar e produzir, com certa autonomia, verbetes de enciclopédia infantil, digitais ou impressos, considerando a situação comunicativa e o tema/ assunto/finalidade do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1757, 'EF05LP25', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Planejar e produzir, com certa autonomia, verbetes de dicionário, digitais ou impressos, considerando a situação comunicativa e o tema/assunto/finalidade do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1758, 'EF03LP26', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Identificar e reproduzir, em relatórios de observação e pesquisa, a formatação e diagramação específica desses gêneros (passos ou listas de itens, tabelas, ilustrações, gráficos, resumo dos resultados), inclusive em suas versões orais.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1759, 'EF05LP26', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Utilizar, ao produzir o texto, conhecimentos linguísticos e gramaticais: regras sintáticas de concordância nominal e verbal, convenções de escrita de citações, pontuação (ponto final, dois-pontos, vírgulas em enumerações) e regras ortográficas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1760, 'EF04LP23', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Identificar e reproduzir, em verbetes de enciclopédia infantil, digitais ou impressos, a formatação e diagramação específica desse gênero (título do verbete, definição, detalhamento, curiosidades), considerando a situação comunicativa e o tema/ assunto/finalidade do texto.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1761, 'EF05LP27', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Utilizar, ao produzir o texto, recursos de coesão pronominal (pronomes anafóricos) e articuladores de relações de sentido (tempo, causa, oposição, conclusão, comparação), com nível adequado de informatividade.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1762, 'EF04LP24', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Identificar e reproduzir, em seu formato, tabelas, diagramas e gráficos em relatórios de observação e pesquisa, como forma de apresentação de dados e informações.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1763, 'EF35LP24', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Identificar funções do texto dramático (escrito para ser encenado) e sua organização por meio de diálogos entre personagens e marcadores das falas das personagens e de cena.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1764, 'EF35LP25', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Criar narrativas ficcionais, com certa autonomia, utilizando detalhes descritivos, sequências de eventos e imagens apropriadas para sustentar o sentido do texto, e marcadores de tempo, espaço e de fala de personagens.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1765, 'EF35LP26', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Ler e compreender, com certa autonomia, narrativas ficcionais que apresentem cenários e personagens, observando os elementos da estrutura narrativa: enredo, tempo, espaço, personagens, narrador e a construção do discurso indireto e discurso direto.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1766, 'EF35LP27', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Ler e compreender, com certa autonomia, textos em versos, explorando rimas, sons e jogos de palavras, imagens poéticas (sentidos figurados) e recursos visuais e sonoros.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1767, 'EF35LP28', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Declamar poemas, com entonação, postura e interpretação adequadas.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1768, 'EF03LP27', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 3, 'Recitar cordel e cantar repentes e emboladas, observando as rimas e obedecendo ao ritmo e à melodia.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1769, 'EF04LP25', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Representar cenas de textos dramáticos, reproduzindo as falas das personagens, de acordo com as rubricas de interpretação e movimento indicadas pelo autor.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1770, 'EF35LP29', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Identificar, em narrativas, cenário, personagem central, conflito gerador, resolução e o ponto de vista com base no qual histórias são narradas, diferenciando narrativas em primeira e terceira pessoas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1771, 'EF35LP30', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Diferenciar discurso indireto e discurso direto, determinando o efeito de sentido de verbos de enunciação e explicando o uso de variedades linguísticas no discurso direto, quando for o caso.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1772, 'EF35LP31', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 3, 5, 'Identificar, em textos versificados, efeitos de sentido decorrentes do uso de recursos rítmicos e sonoros e de metáforas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1773, 'EF04LP26', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Observar, em poemas concretos, o formato, a distribuição e a diagramação das letras do texto na página.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1774, 'EF05LP28', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 5, 5, 'Observar, em ciberpoemas e minicontos infantis em mídia digital, os recursos multissemióticos presentes nesses textos digitais.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1775, 'EF04LP27', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 4, 4, 'Identificar, em textos dramáticos, marcadores das falas das personagens e de cena.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1776, 'EF15AR11', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Criar e improvisar movimentos dançados de modo individual, coletivo e colaborativo, considerando os aspectos estruturais, dinâmicos e expressivos dos elementos constitutivos do movimento, com base nos códigos de dança.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1777, 'EF15AR12', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Discutir, com respeito e sem preconceito, as experiências pessoais e coletivas em dança vivenciadas na escola, como fonte para a construção de vocabulários e repertórios próprios.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1778, 'EF15AR13', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Identificar e apreciar criticamente diversas formas e gêneros de expressão musical, reconhecendo e analisando os usos e as funções da música em diversos contextos de circulação, em especial, aqueles da vida cotidiana.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1779, 'EF15AR14', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Perceber e explorar os elementos constitutivos da música (altura, intensidade, timbre, melodia, ritmo etc.), por meio de jogos, brincadeiras, canções e práticas diversas de composição/criação, execução e apreciação musical.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1780, 'EF15AR15', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Explorar fontes sonoras diversas, como as existentes no próprio corpo (palmas, voz, percussão corporal), na natureza e em objetos cotidianos, reconhecendo os elementos constitutivos da música e as características de instrumentos musicais variados.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1781, 'EF15AR16', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Explorar diferentes formas de registro musical não convencional (representação gráfica de sons, partituras criativas etc.), bem como procedimentos e técnicas de registro em áudio e audiovisual, e reconhecer a notação musical convencional.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1782, 'EF15AR17', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Experimentar improvisações, composições e sonorização de histórias, entre outros, utilizando vozes, sons corporais e/ou instrumentos musicais convencionais ou não convencionais, de modo individual, coletivo e colaborativo.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1783, 'EF15AR18', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Reconhecer e apreciar formas distintas de manifestações do teatro presentes em diferentes contextos, aprendendo a ver e a ouvir histórias dramatizadas e cultivando a percepção, o imaginário, a capacidade de simbolizar e o repertório ficcional.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1784, 'EF15AR19', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Descobrir teatralidades na vida cotidiana, identificando elementos teatrais (variadas entonações de voz, diferentes fisicalidades, diversidade de personagens e narrativas etc.).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1785, 'EF15AR20', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Experimentar o trabalho colaborativo, coletivo e autoral em improvisações teatrais e processos narrativos criativos em teatro, explorando desde a teatralidade dos gestos e das ações do cotidiano até elementos de diferentes matrizes estéticas e culturais.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1786, 'EF15AR21', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Exercitar a imitação e o faz de conta, ressignificando objetos e fatos e experimentando-se no lugar do outro, ao compor e encenar acontecimentos cênicos, por meio de músicas, imagens, textos ou outros pontos de partida, de forma intencional e reflexiva.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1787, 'EF15AR22', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Experimentar possibilidades criativas de movimento e de voz na criação de um personagem teatral, discutindo estereótipos.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1788, 'EF15AR23', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Reconhecer e experimentar, em projetos temáticos, as relações processuais entre diversas linguagens artísticas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1789, 'EF15AR24', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Caracterizar e experimentar brinquedos, brincadeiras, jogos, danças, canções e histórias de diferentes matrizes estéticas e culturais.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1790, 'EF15AR25', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Conhecer e valorizar o patrimônio cultural, material e imaterial, de culturas diversas, em especial a brasileira, incluindo-se suas matrizes indígenas, africanas e europeias, de diferentes épocas, favorecendo a construção de vocabulário e repertório relativos às diferentes linguagens artísticas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1791, 'EF15AR26', 'Ensino Fundamental – Anos Iniciais', 'Artes', 1, 5, 'Explorar diferentes tecnologias e recursos digitais (multimeios, animações, jogos  eletrônicos, gravações em áudio e vídeo, fotografia, softwares etc.) nos processos de criação artística.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1792, 'EF12EF01', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Experimentar, fruir e recriar diferentes brincadeiras e jogos da cultura popular presentes no contexto comunitário e regional, reconhecendo e respeitando as diferenças individuais de desempenho dos colegas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1793, 'EF12EF02', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Explicar, por meio de múltiplas linguagens (corporal, visual, oral e escrita), as brincadeiras e os jogos populares do contexto comunitário e regional, reconhecendo e valorizando a importância desses jogos e brincadeiras para suas culturas de origem.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1794, 'EF12EF03', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Planejar e utilizar estratégias para resolver desafios de brincadeiras e jogos populares do contexto comunitário e regional, com base no reconhecimento das características dessas práticas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1795, 'EF12EF04', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Colaborar na proposição e na produção de alternativas para a prática, em outros momentos e espaços, de brincadeiras e jogos e demais práticas corporais tematizadas na escola, produzindo textos (orais, escritos, audiovisuais) para divulgá-las na escola e na comunidade.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1796, 'EF12EF05', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Experimentar e fruir, prezando pelo trabalho coletivo e pelo protagonismo, a prática de esportes de marca e de precisão, identificando os elementos comuns a esses esportes.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1797, 'EF12EF06', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Discutir a importância da observação das normas e das regras dos esportes de marca e de precisão para assegurar a integridade própria e as dos demais participantes.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1798, 'EF12EF07', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Experimentar, fruir e identificar diferentes elementos básicos da ginástica (equilíbrios, saltos, giros, rotações, acrobacias, com e sem materiais) e da ginástica geral, de forma individual e em pequenos grupos, adotando procedimentos de segurança.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1799, 'EF12EF08', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Planejar e utilizar estratégias para a execução de diferentes elementos básicos da ginástica e da ginástica geral.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1800, 'EF12EF09', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Participar da ginástica geral, identificando as potencialidades e os limites do corpo, e respeitando as diferenças individuais e de desempenho corporal.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1801, 'EF12EF10', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Descrever, por meio de múltiplas linguagens (corporal, oral, escrita e audiovisual), as características dos elementos básicos da ginástica e da ginástica geral, identificando a presença desses elementos em distintas práticas corporais.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1802, 'EF12EF11', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Experimentar e fruir diferentes danças do contexto comunitário e regional (rodas cantadas, brincadeiras rítmicas e expressivas), e recriá-las, respeitando as diferenças individuais e de desempenho corporal.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1803, 'EF12EF12', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 1, 2, 'Identificar os elementos constitutivos (ritmo, espaço, gestos) das danças do contexto comunitário e regional, valorizando e respeitando as manifestações de diferentes culturas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1804, 'EF35EF01', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Experimentar e fruir brincadeiras e jogos populares do Brasil e do mundo, incluindo aqueles de matriz indígena e africana, e recriá-los, valorizando a importância desse patrimônio histórico cultural.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1805, 'EF35EF02', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Planejar e utilizar estratégias para possibilitar a participação segura de todos os alunos em brincadeiras e jogos populares do Brasil e de matriz indígena e africana.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1806, 'EF35EF03', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Descrever, por meio de múltiplas linguagens (corporal, oral, escrita, audiovisual), as brincadeiras e os jogos populares do Brasil e de matriz indígena e africana, explicando suas características e a importância desse patrimônio histórico cultural na preservação das diferentes culturas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1807, 'EF35EF04', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Recriar, individual e coletivamente, e experimentar, na escola e fora dela, brincadeiras e jogos populares do Brasil e do mundo, incluindo aqueles de matriz indígena e africana, e demais práticas corporais tematizadas na escola, adequando-as aos espaços públicos disponíveis.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1808, 'EF35EF05', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Experimentar e fruir diversos tipos de esportes de campo e taco, rede/parede e invasão, identificando seus elementos comuns e criando estratégias individuais e coletivas básicas para sua execução, prezando pelo trabalho coletivo e pelo protagonismo.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1809, 'EF35EF06', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Diferenciar os conceitos de jogo e esporte, identificando as características que os constituem na contemporaneidade e suas manifestações (profissional e comunitária/lazer).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1810, 'EF35EF07', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Experimentar e fruir, de forma coletiva, combinações de diferentes elementos da ginástica geral (equilíbrios, saltos, giros, rotações, acrobacias, com e sem materiais), propondo coreografias com diferentes temas do cotidiano.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1811, 'EF35EF08', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Planejar e utilizar estratégias para resolver desafios na execução de elementos básicos de apresentações coletivas de ginástica geral, reconhecendo as potencialidades e os limites do corpo e adotando procedimentos de segurança.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1812, 'EF35EF09', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Experimentar, recriar e fruir danças populares do Brasil e do mundo e danças de matriz indígena e africana, valorizando e respeitando os diferentes sentidos e significados dessas danças em suas culturas de origem.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1813, 'EF35EF10', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Comparar e identificar os elementos constitutivos comuns e diferentes (ritmo, espaço, gestos) em danças populares do Brasil e do mundo e danças de matriz indígena e africana.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1814, 'EF35EF11', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Formular e utilizar estratégias para a execução de elementos constitutivos das danças populares do Brasil e do mundo, e das danças de matriz indígena e africana.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1815, 'EF35EF12', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Identificar situações de injustiça e preconceito geradas e/ou presentes no contexto das danças e demais práticas corporais e discutir alternativas para superá-las.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1816, 'EF35EF13', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Experimentar, fruir e recriar diferentes lutas presentes no contexto comunitário e regional e lutas de matriz indígena e africana.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1817, 'EF35EF14', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Planejar e utilizar estratégias básicas das lutas do contexto comunitário e regional e lutas de matriz indígena e africana experimentadas, respeitando o colega como oponente e as normas de segurança.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1818, 'EF35EF15', 'Ensino Fundamental – Anos Iniciais', 'Educação Física', 3, 5, 'Identificar as características das lutas do contexto comunitário e regional e lutas de matriz indígena e africana, reconhecendo as diferenças entre lutas e brigas e entre lutas e as demais práticas corporais.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1819, 'EF01MA06', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Construir fatos básicos da adição e utilizá-los em procedimentos de cálculo para resolver problemas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1820, 'EF01MA07', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Compor e decompor número de até duas ordens, por meio de diferentes adições, com o suporte de material manipulável, contribuindo para a compreensão de características do sistema de numeração decimal e o desenvolvimento de estratégias de cálculo.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1821, 'EF01MA08', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Resolver e elaborar problemas de adição e de subtração, envolvendo números de até dois algarismos, com os significados de juntar, acrescentar, separar e retirar, com o suporte de imagens e/ou material manipulável, utilizando estratégias e formas de registro pessoais.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1822, 'EF01MA09', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Organizar e ordenar objetos familiares ou representações por figuras, por meio de atributos, tais como cor, forma e medida.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1823, 'EF01MA10', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Descrever, após o reconhecimento e a explicitação de um padrão (ou regularidade), os elementos ausentes em sequências recursivas de números naturais, objetos ou figuras.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1824, 'EF01MA11', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Descrever a localização de pessoas e de objetos no espaço em relação à sua própria posição, utilizando termos como à direita, à esquerda, em frente, atrás.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1825, 'EF01MA12', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Descrever a localização de pessoas e de objetos no espaço segundo um dado ponto de referência, compreendendo que, para a utilização de termos que se referem à posição, como direita, esquerda, em cima, em baixo, é necessário explicitar-se o referencial.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1826, 'EF01MA13', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Relacionar figuras geométricas espaciais (cones, cilindros, esferas e blocos retangulares) a objetos familiares do mundo físico.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1827, 'EF01MA14', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Identificar e nomear figuras planas (círculo, quadrado, retângulo e triângulo) em desenhos apresentados em diferentes disposições ou em contornos de faces de sólidos geométricos.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1828, 'EF01MA15', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Comparar comprimentos, capacidades ou massas, utilizando termos como mais alto, mais baixo, mais comprido, mais curto, mais grosso, mais fino, mais largo, mais pesado, mais leve, cabe mais, cabe menos, entre outros, para ordenar objetos de uso cotidiano.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1829, 'EF01MA16', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Relatar em linguagem verbal ou não verbal sequência de acontecimentos relativos a um dia, utilizando, quando possível, os horários dos eventos.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1830, 'EF01MA17', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Reconhecer e relacionar períodos do dia, dias da semana e meses do ano, utilizando calendário, quando necessário.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1831, 'EF01MA18', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Produzir a escrita de uma data, apresentando o dia, o mês e o ano, e indicar o dia da semana de uma data, consultando calendários.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1832, 'EF01MA19', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Reconhecer e relacionar valores de moedas e cédulas do sistema monetário brasileiro para resolver situações simples do cotidiano do estudante.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1833, 'EF01MA20', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Classificar eventos envolvendo o acaso, tais como “acontecerá com certeza”, “talvez aconteça” e “é impossível acontecer”, em situações do cotidiano.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1834, 'EF01MA21', 'Ensino Fundamental – Anos Iniciais', 'Matemática', 1, 1, 'Ler dados expressos em tabelas e em gráficos de colunas simples.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1835, 'EF01GE06', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 1, 'Descrever e comparar diferentes tipos de moradia ou objetos de uso cotidiano (brinquedos, roupas, mobiliários), considerando técnicas e materiais utilizados em sua produção.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1836, 'EF01GE07', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 1, 'Descrever atividades de trabalho relacionadas com o dia a dia da sua comunidade.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1837, 'EF01GE08', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 1, 'Criar mapas mentais e desenhos com base em itinerários, contos literários, histórias inventadas e brincadeiras.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1838, 'EF01GE09', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 1, 'Elaborar e utilizar mapas simples para localizar elementos do local de vivência, considerando referenciais espaciais (frente e atrás, esquerda e direita, em cima e embaixo, dentro e fora) e tendo o corpo como referência.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1839, 'EF01GE10', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 1, 'Descrever características de seus lugares de vivência relacionadas aos ritmos da natureza (chuva, vento, calor etc.).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1840, 'EF01GE11', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 1, 1, 'Associar mudanças de vestuário e hábitos alimentares em sua comunidade ao longo do ano, decorrentes da variação de temperatura e umidade no ambiente.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1841, 'EF02GE09', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 2, 'Identificar objetos e lugares de vivência (escola e moradia) em imagens aéreas e mapas (visão vertical) e fotografias (visão oblíqua).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1842, 'EF02GE10', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 2, 'Aplicar princípios de localização e posição de objetos (referenciais espaciais, como frente e atrás, esquerda e direita, em cima e embaixo, dentro e fora) por meio de representações espaciais da sala de aula e da escola.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1843, 'EF02GE11', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 2, 2, 'Reconhecer a importância do solo e da água para a vida, identificando seus diferentes usos (plantação e extração de materiais, entre outras possibilidades) e os impactos desses usos no cotidiano da cidade e do campo.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1844, 'EF03GE07', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 3, 'Reconhecer e elaborar legendas com símbolos de diversos tipos de representações em diferentes escalas cartográficas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1845, 'EF03GE08', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 3, 'Relacionar a produção de lixo doméstico ou da escola aos problemas causados pelo consumo excessivo e construir propostas para o consumo consciente, considerando a ampliação de hábitos de redução, reúso e reciclagem/descarte de materiais consumidos em casa, na escola e/ou no entorno.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1846, 'EF03GE09', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 3, 'Investigar os usos dos recursos naturais, com destaque para os usos da água em atividades cotidianas (alimentação, higiene, cultivo de plantas etc.), e discutir os problemas ambientais provocados por esses usos.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1847, 'EF03GE10', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 3, 'Identificar os cuidados necessários para utilização da água na agricultura e na geração de energia de modo a garantir a manutenção do provimento de água potável.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1848, 'EF03GE11', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 3, 3, 'Comparar impactos das atividades econômicas urbanas e rurais sobre o ambiente físico natural, assim como os riscos provenientes do uso de ferramentas e máquinas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1849, 'EF04GE07', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 4, 'Comparar as características do trabalho no campo e na cidade.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1850, 'EF04GE08', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 4, 'Descrever e discutir o processo de produção (transformação de matérias-primas), circulação e consumo de diferentes produtos.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1851, 'EF04GE09', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 4, 'Utilizar as direções cardeais na localização de componentes físicos e humanos nas paisagens rurais e urbanas.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1852, 'EF04GE10', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 4, 'Comparar tipos variados de mapas, identificando suas características, elaboradores, finalidades, diferenças e semelhanças.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1853, 'EF04GE11', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 4, 'Identificar as características das paisagens naturais e antrópicas (relevo, cobertura vegetal, rios etc.) no ambiente em que vive, bem como a ação humana na conservação ou degradação dessas áreas.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1854, 'EF04GE12', 'Ensino Fundamental – Anos Iniciais', 'Geografia', 4, 4, 'DESCRIÇÃO NÃO ENCONTRADA - Preencher manualmente', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1855, 'EF01HI06', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 1, 'Conhecer as histórias da família e da escola e identificar o papel desempenhado por diferentes sujeitos em diferentes espaços.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1856, 'EF01HI07', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 1, 'Identificar mudanças e permanências nas formas de organização familiar.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1857, 'EF01HI08', 'Ensino Fundamental – Anos Iniciais', 'História', 1, 1, 'Reconhecer o significado das comemorações e festas escolares, diferenciando-as das datas festivas comemoradas no âmbito familiar ou da comunidade.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1858, 'EF02HI06', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 2, 'Identificar e organizar, temporalmente, fatos da vida cotidiana, usando noções relacionadas ao tempo (antes, durante, ao mesmo tempo e depois).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1859, 'EF02HI07', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 2, 'Identificar e utilizar diferentes marcadores do tempo presentes na comunidade, como relógio e calendário.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1860, 'EF02HI08', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 2, 'Compilar histórias da família e/ou da comunidade registradas em diferentes fontes.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1861, 'EF02HI09', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 2, 'Identificar objetos e documentos pessoais que remetam à própria experiência no âmbito da família  e/ou da comunidade, discutindo as razões pelas quais alguns objetos são preservados e outros são descartados.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1862, 'EF02HI10', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 2, 'Identificar diferentes formas de trabalho existentes na comunidade em que vive, seus significados, suas especificidades e importância.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1863, 'EF02HI11', 'Ensino Fundamental – Anos Iniciais', 'História', 2, 2, 'Identificar impactos no ambiente causados pelas diferentes formas de trabalho existentes na comunidade em que vive.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1864, 'EF03HI06', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 3, 'Identificar os registros de memória na cidade (nomes de ruas, monumentos, edifícios etc.), discutindo os critérios que explicam a escolha desses nomes.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1865, 'EF03HI07', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 3, 'Identificar semelhanças e diferenças existentes entre comunidades de sua cidade ou região, e descrever o papel dos diferentes grupos sociais que as formam.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1866, 'EF03HI08', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 3, 'Identificar modos de vida na cidade e no campo no presente, comparando-os com os do passado.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1867, 'EF03HI09', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 3, 'Mapear os espaços públicos no lugar em que vive (ruas, praças, escolas, hospitais, prédios da Prefeitura e da Câmara de Vereadores etc.) e identificar suas funções.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1868, 'EF03HI10', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 3, 'Identificar as diferenças entre o espaço doméstico, os espaços públicos e as áreas de conservação ambiental, compreendendo a importância dessa distinção.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1869, 'EF03HI11', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 3, 'Identificar diferenças entre formas de trabalho realizadas na cidade e no campo, considerando também o uso da tecnologia nesses diferentes contextos.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1870, 'EF03HI12', 'Ensino Fundamental – Anos Iniciais', 'História', 3, 3, 'Comparar as relações de trabalho e lazer do presente com as de outros tempos e espaços, analisando mudanças e permanências.', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1871, 'EF04HI06', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 4, 'Identificar as transformações ocorridas nos processos de deslocamento das pessoas e mercadorias, analisando as formas de adaptação ou marginalização.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1872, 'EF04HI07', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 4, 'Identificar e descrever a importância dos caminhos terrestres, fluviais e marítimos para a dinâmica da vida comercial.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1873, 'EF04HI08', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 4, 'Identificar as transformações ocorridas nos meios de comunicação (cultura oral, imprensa, rádio, televisão, cinema, internet e demais tecnologias digitais de informação e comunicação) e discutir seus significados para os diferentes grupos ou estratos sociais.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1874, 'EF04HI09', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 4, 'Identificar as motivações dos processos migratórios em diferentes tempos e espaços e avaliar o papel desempenhado pela migração nas regiões de destino.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1875, 'EF04HI10', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 4, 'Analisar diferentes fluxos populacionais e suas contribuições para a formação da sociedade brasileira.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1876, 'EF04HI11', 'Ensino Fundamental – Anos Iniciais', 'História', 4, 4, 'Analisar, na sociedade em que vive,  a existência ou não de mudanças associadas à migração (interna e internacional).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1877, 'EF01ER01', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 1, 1, 'Identificar e acolher as semelhanças e diferenças entre o eu, o outro e o nós.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1878, 'EF01ER02', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 1, 1, 'Reconhecer que o seu nome e o das demais pessoas os identificam e os diferenciam.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1879, 'EF01ER03', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 1, 1, 'Reconhecer e respeitar as características físicas e subjetivas de cada um.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1880, 'EF01ER04', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 1, 1, 'Valorizar a diversidade de formas de vida.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1881, 'EF01ER05', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 1, 1, 'Identificar e acolher sentimentos, lembranças, memórias e saberes de cada um.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1882, 'EF01ER06', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 1, 1, 'Identificar as diferentes formas pelas quais as pessoas manifestam sentimentos, ideias, memórias, gostos e crenças em diferentes espaços.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1883, 'EF02ER01', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 2, 2, 'Reconhecer os diferentes espaços de convivência.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1884, 'EF02ER02', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 2, 2, 'Identificar costumes, crenças e formas diversas de viver em variados ambientes de convivência.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37');
INSERT INTO `habilidades_bncc` (`id`, `codigo_bncc`, `etapa`, `componente`, `ano_inicio`, `ano_fim`, `descricao`, `created_at`, `updated_at`) VALUES
(1885, 'EF02ER03', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 2, 2, 'Identificar as diferentes formas de registro das memórias pessoais, familiares e escolares (fotos, músicas, narrativas, álbuns...).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1886, 'EF02ER04', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 2, 2, 'Identificar os símbolos presentes nos variados espaços de convivência.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1887, 'EF02ER05', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 2, 2, 'Identificar, distinguir e respeitar símbolos religiosos de distintas manifestações, tradições e instituições religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1888, 'EF02ER06', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 2, 2, 'Exemplificar alimentos considerados sagrados por diferentes culturas, tradições e expressões religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1889, 'EF02ER07', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 2, 2, 'Identificar significados atribuídos a alimentos em diferentes manifestações e tradições religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1890, 'EF03ER01', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 3, 3, 'Identificar e respeitar os diferentes espaços e territórios religiosos de diferentes tradições e movimentos religiosos.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1891, 'EF03ER02', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 3, 3, 'Caracterizar os espaços e territórios religiosos como locais de realização das práticas celebrativas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1892, 'EF03ER03', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 3, 3, 'Identificar e respeitar práticas celebrativas (cerimônias, orações, festividades, peregrinações, entre outras) de diferentes tradições religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1893, 'EF03ER04', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 3, 3, 'Caracterizar as práticas celebrativas como parte integrante do conjunto das manifestações religiosas de diferentes culturas e sociedades.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1894, 'EF03ER05', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 3, 3, 'Reconhecer as indumentárias (roupas, acessórios, símbolos, pinturas corporais) utilizadas em diferentes manifestações e tradições religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1895, 'EF03ER06', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 3, 3, 'Caracterizar as indumentárias como elementos integrantes das identidades religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1896, 'EF04ER01', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 4, 4, 'Identificar ritos presentes no cotidiano pessoal, familiar, escolar e comunitário.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1897, 'EF04ER02', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 4, 4, 'Identificar ritos e suas funções em diferentes manifestações e tradições religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1898, 'EF04ER03', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 4, 4, 'Caracterizar ritos de iniciação e de passagem em diversos grupos religiosos (nascimento, casamento e morte).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1899, 'EF04ER04', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 4, 4, 'Identificar as diversas formas de expressão da espiritualidade (orações, cultos, gestos, cantos, dança, meditação) nas diferentes tradições religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1900, 'EF04ER05', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 4, 4, 'Identificar representações religiosas em diferentes expressões artísticas (pinturas, arquitetura, esculturas, ícones, símbolos, imagens), reconhecendo-as como parte da identidade de diferentes culturas e tradições religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1901, 'EF04ER06', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 4, 4, 'Identificar nomes, significados e representações de divindades nos contextos familiar e comunitário.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1902, 'EF04ER07', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 4, 4, 'Reconhecer e respeitar as ideias de divindades de diferentes manifestações e tradições religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1903, 'EF05ER01', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 5, 5, 'Identificar e respeitar acontecimentos sagrados de diferentes culturas e tradições religiosas como recurso para preservar a memória.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1904, 'EF05ER02', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 5, 5, 'Identificar mitos de criação em diferentes culturas e tradições religiosas.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1905, 'EF05ER03', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 5, 5, 'Reconhecer funções e mensagens religiosas contidas nos mitos de criação (concepções de mundo, natureza, ser humano, divindades, vida e morte).,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1906, 'EF05ER04', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 5, 5, 'Reconhecer a importância da tradição oral para preservar memórias e acontecimentos religiosos.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1907, 'EF05ER05', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 5, 5, 'Identificar elementos da tradição oral nas culturas e religiosidades indígenas, afro-brasileiras, ciganas, entre outras.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1908, 'EF05ER06', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 5, 5, 'Identificar o papel dos sábios e anciãos na comunicação e preservação da tradição oral.,', '2025-12-15 23:54:37', '2025-12-15 23:54:37'),
(1909, 'EF05ER07', 'Ensino Fundamental – Anos Iniciais', 'Ensino Religioso', 5, 5, 'Reconhecer, em textos orais, ensinamentos relacionados a modos de ser e viver.', '2025-12-15 23:54:37', '2025-12-15 23:54:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_escolar`
--

CREATE TABLE `historico_escolar` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `ano_letivo` int(11) NOT NULL,
  `serie` varchar(50) DEFAULT NULL,
  `turma` varchar(50) DEFAULT NULL,
  `situacao` enum('APROVADO','REPROVADO','TRANSFERIDO','ABANDONO','CONCLUIDO') DEFAULT NULL,
  `media_geral` decimal(5,2) DEFAULT NULL,
  `frequencia_percentual` decimal(5,2) DEFAULT NULL,
  `total_dias_letivos` int(11) DEFAULT NULL,
  `total_faltas` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `gerado_por` bigint(20) DEFAULT NULL,
  `gerado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `indicador_nutricional`
--

CREATE TABLE `indicador_nutricional` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `cardapio_id` bigint(20) DEFAULT NULL,
  `periodo_inicio` date NOT NULL,
  `periodo_fim` date NOT NULL,
  `tipo_indicador` enum('VARIEDADE','VALOR_NUTRICIONAL','PORCOES','DESPERDICIO','SAZONALIDADE','OUTROS') NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `unidade` varchar(20) DEFAULT NULL,
  `meta` decimal(10,2) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Indicadores nutricionais acompanhados pelo nutricionista';

-- --------------------------------------------------------

--
-- Estrutura para tabela `justificativa`
--

CREATE TABLE `justificativa` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `enviado_por` bigint(20) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `status` enum('PENDENTE','APROVADA','REJEITADA') DEFAULT 'PENDENTE',
  `arquivo_url` varchar(500) DEFAULT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `analisado_por` bigint(20) DEFAULT NULL,
  `analise` text DEFAULT NULL,
  `data_analise` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_sistema`
--

CREATE TABLE `log_sistema` (
  `id` bigint(20) NOT NULL,
  `usuario_id` bigint(20) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `tipo` enum('INFO','WARNING','ERROR','SECURITY') DEFAULT 'INFO',
  `descricao` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `log_sistema`
--

INSERT INTO `log_sistema` (`id`, `usuario_id`, `acao`, `tipo`, `descricao`, `ip`, `user_agent`, `criado_em`) VALUES
(1, 3, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 15:06:10'),
(2, NULL, 'LOGIN_FALHA', 'SECURITY', 'Tentativa de login falhou para: adm.transporte@sigae.com - Motivo: Senha incorreta - Tentativa 1/5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 15:07:18'),
(3, 56, 'LOGIN', 'SECURITY', 'Login realizado por: adm.transporte', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 15:07:51'),
(4, 56, 'LOGIN', 'SECURITY', 'Login realizado por: adm.transporte', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 15:08:21'),
(5, 11, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 15:08:38'),
(6, 57, 'LOGIN', 'SECURITY', 'Login realizado por: transporte.aluno', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 15:10:19'),
(7, 56, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 15:54:11'),
(8, 3, 'LOGIN', 'SECURITY', 'Login realizado por: francisco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 15:54:21'),
(9, 3, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 16:55:55'),
(10, 56, 'LOGIN', 'SECURITY', 'Login realizado por: adm.transporte', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 16:56:08'),
(11, 57, 'LOGIN', 'SECURITY', 'Login realizado por: transporte.aluno', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 16:56:35'),
(12, 38, 'LOGIN', 'SECURITY', 'Login realizado por: larissa.araújo.freitas', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:11:48'),
(13, 38, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:11:50'),
(14, 10, 'LOGIN', 'SECURITY', 'Login realizado por: joao.silva', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:12:14'),
(15, 10, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:12:16'),
(16, 28, 'LOGIN', 'SECURITY', 'Login realizado por: ana.silva.santos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:12:24'),
(17, 28, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:12:28'),
(18, 29, 'LOGIN', 'SECURITY', 'Login realizado por: bruno.oliveira.costa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:12:38'),
(19, 29, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:12:41'),
(20, 3, 'LOGIN', 'SECURITY', 'Login realizado por: francisco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:13:30'),
(21, 3, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:47:03'),
(22, 47, 'LOGIN', 'SECURITY', 'Login realizado por: nutricionista.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:47:23'),
(23, 47, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:48:29'),
(24, 46, 'LOGIN', 'SECURITY', 'Login realizado por: merenda.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:48:39'),
(25, 46, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:57:12'),
(26, 47, 'LOGIN', 'SECURITY', 'Login realizado por: nutricionista.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 00:57:18'),
(27, 47, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:03:24'),
(28, 11, 'LOGIN', 'SECURITY', 'Login realizado por: gestor.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:04:31'),
(29, 11, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:13:34'),
(30, 3, 'LOGIN', 'SECURITY', 'Login realizado por: francisco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:13:37'),
(31, 3, 'EDITAR_PROFESSOR', 'INFO', 'Professor editado: Jose Carlos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:13:46'),
(32, 3, 'EDITAR_PROFESSOR', 'INFO', 'Professor editado: Pedro Alvares', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:13:56'),
(33, 3, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:20:19'),
(34, 11, 'LOGIN', 'SECURITY', 'Login realizado por: gestor.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:22:06'),
(35, 11, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:24:18'),
(36, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:24:32'),
(37, 44, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:24:52'),
(38, 3, 'LOGIN', 'SECURITY', 'Login realizado por: francisco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:24:55'),
(39, 3, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:25:25'),
(40, 11, 'LOGIN', 'SECURITY', 'Login realizado por: gestor.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:25:29'),
(41, 11, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:26:06'),
(42, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:26:11'),
(43, 44, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:27:00'),
(44, NULL, 'LOGIN_FALHA', 'SECURITY', 'Tentativa de login falhou para: 123.456.789-00 - Motivo: Senha incorreta - Tentativa 1/5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:27:02'),
(45, 11, 'LOGIN', 'SECURITY', 'Login realizado por: gestor.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:27:07'),
(46, 11, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:29:38'),
(47, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:30:03'),
(48, 44, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:31:46'),
(49, 3, 'LOGIN', 'SECURITY', 'Login realizado por: francisco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:31:49'),
(50, 3, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:33:12'),
(51, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:39:48'),
(52, 44, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:54:01'),
(53, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 01:54:25'),
(54, 44, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 02:26:26'),
(55, 29, 'LOGIN', 'SECURITY', 'Login realizado por: bruno.oliveira.costa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 02:26:28'),
(56, 29, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 02:26:59'),
(57, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 02:27:12'),
(58, 44, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 02:35:12'),
(59, NULL, 'LOGIN_FALHA', 'SECURITY', 'Tentativa de login falhou para: 800.000.000-02 - Motivo: Senha incorreta - Tentativa 1/5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 02:35:18'),
(60, 29, 'LOGIN', 'SECURITY', 'Login realizado por: bruno.oliveira.costa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 02:35:26'),
(61, 29, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 02:36:03'),
(62, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 02:36:17'),
(63, 44, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:03:15'),
(64, 47, 'LOGIN', 'SECURITY', 'Login realizado por: nutricionista.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:03:40'),
(65, 47, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:25:04'),
(66, 46, 'LOGIN', 'SECURITY', 'Login realizado por: merenda.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:25:19'),
(67, 46, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:31:44'),
(68, 47, 'LOGIN', 'SECURITY', 'Login realizado por: nutricionista.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:31:49'),
(69, 47, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:36:37'),
(70, 46, 'LOGIN', 'SECURITY', 'Login realizado por: merenda.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:36:43'),
(71, 46, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:36:58'),
(72, 47, 'LOGIN', 'SECURITY', 'Login realizado por: nutricionista.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 03:37:05'),
(73, 47, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:04:29'),
(74, 46, 'LOGIN', 'SECURITY', 'Login realizado por: merenda.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:04:41'),
(75, 46, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:05:15'),
(76, 47, 'LOGIN', 'SECURITY', 'Login realizado por: nutricionista.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:05:21'),
(77, 47, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:10:29'),
(78, NULL, 'LOGIN_FALHA', 'SECURITY', 'Tentativa de login falhou para: 123.456.789-01 - Motivo: Senha incorreta - Tentativa 1/5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:10:34'),
(79, 3, 'LOGIN', 'SECURITY', 'Login realizado por: francisco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:10:37'),
(80, 3, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:55:10'),
(81, NULL, 'LOGIN_FALHA', 'SECURITY', 'Tentativa de login falhou para: 800.000.000-02 - Motivo: Senha incorreta - Tentativa 1/5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:55:25'),
(82, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:55:29'),
(83, 3, 'LOGIN', 'SECURITY', 'Login realizado por: francisco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:55:54'),
(84, 3, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:56:09'),
(85, 11, 'LOGIN', 'SECURITY', 'Login realizado por: gestor.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 04:56:15'),
(86, 44, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 05:03:32'),
(87, NULL, 'LOGIN_FALHA', 'SECURITY', 'Tentativa de login falhou para: 123.456.789-00 - Motivo: Senha incorreta - Tentativa 1/5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 05:03:35'),
(88, 11, 'LOGIN', 'SECURITY', 'Login realizado por: gestor.teste', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 05:03:39'),
(89, 11, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 05:04:28'),
(90, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 05:04:39'),
(91, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 05:53:05'),
(92, 44, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 05:56:17'),
(93, 44, 'LOGIN', 'SECURITY', 'Login realizado por: josé', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 05:56:22'),
(94, 3, 'LOGIN', 'SECURITY', 'Login realizado por: francisco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 11:59:08'),
(95, 3, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 13:17:52'),
(96, 57, 'LOGIN', 'SECURITY', 'Login realizado por: transporte.aluno', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 13:18:03'),
(97, 2, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', '2025-12-16 14:43:22'),
(98, 56, 'LOGIN', 'SECURITY', 'Login realizado por: adm.transporte', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.1.36 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', '2025-12-16 14:45:43'),
(99, 56, 'LOGOUT', 'SECURITY', 'Usuário realizou logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 15:39:15'),
(100, 3, 'LOGIN', 'SECURITY', 'Login realizado por: francisco', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 15:39:30'),
(101, 3, 'CRIAR_ESCOLA', 'INFO', 'Escola criada: Cristovão Colombo EMEIEF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 16:28:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `motorista`
--

CREATE TABLE `motorista` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `cnh` varchar(20) NOT NULL,
  `categoria_cnh` varchar(5) DEFAULT NULL COMMENT 'B, C, D, E',
  `validade_cnh` date DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `data_demissao` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `motorista`
--

INSERT INTO `motorista` (`id`, `pessoa_id`, `cnh`, `categoria_cnh`, `validade_cnh`, `data_admissao`, `data_demissao`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 71, '212343547643', 'D', '2027-12-09', '2022-05-02', NULL, '', 1, '2025-12-16 15:03:25', '2025-12-16 15:03:25', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `motorista_veiculo`
--

CREATE TABLE `motorista_veiculo` (
  `id` bigint(20) NOT NULL,
  `motorista_id` bigint(20) NOT NULL,
  `veiculo_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 0 COMMENT '1 = veículo principal do motorista',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacao_estoque`
--

CREATE TABLE `movimentacao_estoque` (
  `id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,0) DEFAULT NULL,
  `tipo` enum('ENTRADA','SAIDA','RESERVA','AJUSTE') DEFAULT NULL,
  `referencia_id` bigint(20) DEFAULT NULL,
  `referencia_tipo` varchar(50) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `valor_unitario` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(12,2) DEFAULT NULL,
  `realizado_por` bigint(20) DEFAULT NULL,
  `realizado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `nota`
--

CREATE TABLE `nota` (
  `id` bigint(20) NOT NULL,
  `avaliacao_id` bigint(20) DEFAULT NULL,
  `disciplina_id` bigint(20) DEFAULT NULL,
  `turma_id` bigint(20) DEFAULT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `bimestre` int(11) DEFAULT NULL,
  `recuperacao` tinyint(1) DEFAULT 0,
  `validado` tinyint(1) DEFAULT 0,
  `validado_por` bigint(20) DEFAULT NULL,
  `data_validacao` timestamp NULL DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `lancado_por` bigint(20) DEFAULT NULL,
  `lancado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `nota`
--

INSERT INTO `nota` (`id`, `avaliacao_id`, `disciplina_id`, `turma_id`, `aluno_id`, `nota`, `bimestre`, `recuperacao`, `validado`, `validado_por`, `data_validacao`, `comentario`, `lancado_por`, `lancado_em`, `atualizado_em`, `atualizado_por`) VALUES
(46, 28, 5, 7, 3, 3.00, 1, 0, 0, NULL, NULL, '', NULL, '2025-12-16 02:35:05', '2025-12-16 02:46:13', NULL),
(47, 29, 5, 7, 3, 2.00, 1, 0, 0, NULL, NULL, '', NULL, '2025-12-16 02:35:05', '2025-12-16 02:46:13', NULL),
(48, 30, 5, 7, 3, 2.00, 1, 0, 0, NULL, NULL, '', NULL, '2025-12-16 02:35:05', '2025-12-16 02:46:13', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `nutricionista`
--

CREATE TABLE `nutricionista` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `crn` varchar(20) DEFAULT NULL COMMENT 'Conselho Regional de Nutricionistas',
  `formacao` text DEFAULT NULL,
  `especializacao` text DEFAULT NULL,
  `registro_profissional` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabela de nutricionistas do sistema';

--
-- Despejando dados para a tabela `nutricionista`
--

INSERT INTO `nutricionista` (`id`, `pessoa_id`, `crn`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 47, 'CRN-12345', 'Bacharelado em Nutrição - UFC', 'Especialização em Nutrição Escolar', '12345', 'Nutricionista com experiência em alimentação escolar e PNAE', 1, '2025-12-08 13:12:49', '2025-12-08 13:12:49', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `nutricionista_lotacao`
--

CREATE TABLE `nutricionista_lotacao` (
  `id` bigint(20) NOT NULL,
  `nutricionista_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `responsavel` tinyint(1) DEFAULT 0 COMMENT '1 = nutricionista responsável pela escola',
  `carga_horaria` int(11) DEFAULT NULL COMMENT 'Carga horária semanal',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Lotação de nutricionistas em escolas';

--
-- Despejando dados para a tabela `nutricionista_lotacao`
--

INSERT INTO `nutricionista_lotacao` (`id`, `nutricionista_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `carga_horaria`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(3, 1, 17, '2025-12-08', NULL, 1, 40, 'Nutricionista responsável pela escola', '2025-12-11 15:03:58', '2025-12-11 15:03:58', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `observacao_desempenho`
--

CREATE TABLE `observacao_desempenho` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) DEFAULT NULL,
  `professor_id` bigint(20) NOT NULL,
  `tipo` enum('COMPORTAMENTO','APRENDIZAGEM','PARTICIPACAO','DIFICULDADE','MELHORIA','OUTROS') DEFAULT 'OUTROS',
  `titulo` varchar(255) DEFAULT NULL,
  `observacao` text NOT NULL,
  `data` date NOT NULL,
  `bimestre` int(11) DEFAULT NULL,
  `visivel_responsavel` tinyint(1) DEFAULT 1,
  `criado_por` bigint(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `observacao_desempenho`
--

INSERT INTO `observacao_desempenho` (`id`, `aluno_id`, `turma_id`, `disciplina_id`, `professor_id`, `tipo`, `titulo`, `observacao`, `data`, `bimestre`, `visivel_responsavel`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(5, 3, 7, 5, 5, 'COMPORTAMENTO', '', 'o', '2025-12-16', NULL, 1, NULL, '2025-12-16 02:49:55', '2025-12-16 02:49:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacote`
--

CREATE TABLE `pacote` (
  `id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `criado_por` bigint(20) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacote_escola`
--

CREATE TABLE `pacote_escola` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `enviado_por` bigint(20) DEFAULT NULL,
  `data_envio` date NOT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pacote_escola`
--

INSERT INTO `pacote_escola` (`id`, `escola_id`, `descricao`, `enviado_por`, `data_envio`, `observacoes`, `criado_em`) VALUES
(2, 3, NULL, 46, '2025-12-11', 'Produtos enviados conforme solicitação mensal. Atentar para os itens com validade mais próxima: leite (15/01) e batata (10/01).', '2025-12-11 16:51:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacote_escola_item`
--

CREATE TABLE `pacote_escola_item` (
  `id` bigint(20) NOT NULL,
  `pacote_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `estoque_central_id` bigint(20) DEFAULT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `unidade_medida` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pacote_escola_item`
--

INSERT INTO `pacote_escola_item` (`id`, `pacote_id`, `produto_id`, `estoque_central_id`, `quantidade`, `unidade_medida`) VALUES
(11, 2, 1001, NULL, 2.000, 'KG'),
(12, 2, 1012, NULL, 5.000, 'KG'),
(13, 2, 1013, NULL, 4.000, 'KG'),
(14, 2, 1020, NULL, 6.000, 'UN');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacote_item`
--

CREATE TABLE `pacote_item` (
  `id` bigint(20) NOT NULL,
  `pacote_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `parecer_tecnico`
--

CREATE TABLE `parecer_tecnico` (
  `id` bigint(20) NOT NULL,
  `nutricionista_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `cardapio_id` bigint(20) DEFAULT NULL,
  `tipo` enum('CARDAPIO','CONSUMO','DESPERDICIO','ADEQUACAO_NUTRICIONAL','OUTROS') DEFAULT 'OUTROS',
  `titulo` varchar(255) NOT NULL,
  `conteudo` text NOT NULL,
  `recomendacoes` text DEFAULT NULL,
  `status` enum('RASCUNHO','PUBLICADO','ARQUIVADO') DEFAULT 'RASCUNHO',
  `data_referencia` date DEFAULT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fim` date DEFAULT NULL,
  `anexo_url` varchar(500) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pareceres técnicos emitidos pelos nutricionistas';

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) NOT NULL,
  `usuario_id` bigint(20) NOT NULL,
  `token` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `usuario_id`, `token`, `email`, `expira_em`, `usado`, `criado_em`) VALUES
(1, 8, 'c45a5968df80ab6a1f82a9538178eb1389fe8cb791e5c875a32a29f8103f5d54', 'gestor.teste@sigae.com', '2025-12-02 04:01:42', 1, '2025-12-01 03:01:42'),
(2, 8, '0d30ccbc3111273eac40e711804eaadcb994a2cd4d006a3da15b31c209269a8c', 'gestor.teste@sigae.com', '2025-12-02 04:04:08', 1, '2025-12-01 03:04:08'),
(3, 8, 'dd5b3610eae2ab0e20d79d9ca65e304c0def53a8aa6e0af5bef1d69964df6f8a', 'gestor.teste@sigae.com', '2025-12-02 04:30:21', 1, '2025-12-01 03:30:21'),
(4, 8, '6c6c8de7bfb84ef7135960521ffe712c4ab8ac051e674bcb6b0a60507d2a21f2', 'gestor.teste@sigae.com', '2025-12-02 04:30:25', 0, '2025-12-01 03:30:25'),
(5, 29, '2f57a54e0c95186bedc377bfa01b4b2e449e0f7e0ce17a436cb7dd770df2e8a2', 'nutricionista.teste@sigae.com', '2025-12-13 03:04:27', 1, '2025-12-12 02:04:27'),
(6, 14, 'ca8815e3a2627201ee01380618937d39dc6b804ed01549805f7712d01e3aab91', 'bruno.oliveira.teste@sigae.com', '2025-12-16 01:11:13', 0, '2025-12-15 00:11:13'),
(7, 14, '52816ad3fd1170957aa1da4698a8293e99e8f36ffe04cafc6ece50a018b1b3c3', 'bruno.oliveira.teste@sigae.com', '2025-12-16 01:11:13', 1, '2025-12-15 00:11:13'),
(8, 35, '6de6fa6983111613b5a60ed06e162af316f3e2d73702bde28cf23f780283d084', 'adm.transporte@sigae.com', '2025-12-16 16:07:30', 1, '2025-12-15 15:07:30'),
(9, 36, 'bfa4f68f698c26d703b8dfc6ab8748959017c1edb215e9682eb18d35b15a3288', 'transporte.aluno@sigae.com', '2025-12-16 16:09:58', 1, '2025-12-15 15:09:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_cesta`
--

CREATE TABLE `pedido_cesta` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `mes` int(11) DEFAULT NULL,
  `nutricionista_id` bigint(20) DEFAULT NULL,
  `status` enum('RASCUHO','ENVIADO','APROVADO','REJEITADO','ENVIADO_A_ESCOLA') DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  `data_envio` timestamp NULL DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `data_entrega` date DEFAULT NULL,
  `entregue` tinyint(1) DEFAULT 0,
  `aprovado_por` bigint(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `motivo_rejeicao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_item`
--

CREATE TABLE `pedido_item` (
  `id` bigint(20) NOT NULL,
  `pedido_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade_solicitada` decimal(10,0) DEFAULT NULL,
  `quantidade_atendida` decimal(10,0) DEFAULT NULL,
  `obs` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(69, '12190222197', 'Juscelino Kubitschek', '1902-09-12', 'M', 'juscelinojk@gmail.com', NULL, NULL, NULL, 'praça Adelaide coelho', '21', NULL, 'Amanari', 'Maranguape', 'CE', '61979000', 'ALUNO', NULL, NULL, '2025-12-16 13:16:03', '2025-12-16 13:16:03', 11, 1, 'jk', 'BRANCA'),
(70, '62764236743', 'Francis Albert Sinatra', '1915-12-12', 'M', 'frankdojazz@gmail.com', NULL, NULL, NULL, NULL, '21', NULL, 'Amanari', 'Maranguape', 'CE', '61979005', 'ALUNO', NULL, NULL, '2025-12-16 14:23:40', '2025-12-16 14:23:40', 11, 1, 'Sinatra', 'BRANCA'),
(71, '76647474773', 'carlos pinto', '1992-08-07', NULL, 'carlin177@gmail.com', '85672727277', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-12-16 15:03:25', '2025-12-16 15:03:25', NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `plano_aula`
--

CREATE TABLE `plano_aula` (
  `id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) NOT NULL,
  `professor_id` bigint(20) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `conteudo` text DEFAULT NULL,
  `objetivos` text DEFAULT NULL,
  `metodologia` text DEFAULT NULL,
  `recursos` text DEFAULT NULL,
  `avaliacao` text DEFAULT NULL,
  `atividades_flexibilizadas` text DEFAULT NULL,
  `data_aula` date NOT NULL,
  `bimestre` int(11) DEFAULT NULL,
  `status` enum('RASCUNHO','APROVADO','APLICADO','CANCELADO') DEFAULT 'RASCUNHO',
  `aprovado_por` bigint(20) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `observacoes_complementares` text DEFAULT NULL,
  `secoes_temas` text DEFAULT NULL,
  `atividade_permanente` text DEFAULT NULL,
  `habilidades` text DEFAULT NULL,
  `competencias_socioemocionais` text DEFAULT NULL,
  `competencias_especificas` text DEFAULT NULL,
  `competencias_gerais` text DEFAULT NULL,
  `disciplinas_componentes` text DEFAULT NULL,
  `criado_por` bigint(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `plano_aula`
--

INSERT INTO `plano_aula` (`id`, `turma_id`, `disciplina_id`, `professor_id`, `titulo`, `conteudo`, `objetivos`, `metodologia`, `recursos`, `avaliacao`, `atividades_flexibilizadas`, `data_aula`, `bimestre`, `status`, `aprovado_por`, `data_aprovacao`, `observacoes`, `observacoes_complementares`, `secoes_temas`, `atividade_permanente`, `habilidades`, `competencias_socioemocionais`, `competencias_especificas`, `competencias_gerais`, `disciplinas_componentes`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(2, 5, 2, 5, 'Título do Plano', 'Conteúdo', 'Objetivo(s) da Aula', 'Metodologia', 'Recursos', 'Avaliação', NULL, '2025-12-16', 1, 'RASCUNHO', NULL, NULL, 'Observações', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-16 05:19:17', '2025-12-16 05:19:17'),
(3, 5, 2, 5, 'Título do Plano', 'Conteúdo', 'Objetivo(s) da Aula', 'Metodologia', 'Recursos', 'Avaliação', 'Atividades Flexibilizadas', '2025-12-16', 1, 'RASCUNHO', NULL, NULL, 'Observações', 'Observações Complementares', 'Seções e Temas', '[{\"id\":\"10\",\"nome\":\"INTERPRETAÇÃO TEXTUAL\"}]', '[]', '[{\"id\":\"1-2\",\"socioemocional_id\":\"1\",\"socioemocional_nome\":\"ABERTURA AO NOVO\",\"nome\":\"IMAGINAÇÃO CRIATIVA\"}]', '[{\"id\":\"1\",\"nome\":\"ANALISAR INFORMAÇÕES, ARGUMENTOS E OPINIÕES MANIFESTADAS EM INTERAÇÕES SOCIAIS E NOS MEIOS DE COMUNICAÇÃO, POSICIONANDO-SE ÉTICA E CRITICAMENTE EM RELAÇÃO A CONTEÚDOS DISCRIMINATÓRIOS QUE FEREM DIREITOS HUMANOS E AMBIENTAIS.\"}]', '[]', '[{\"id\":\"2\",\"nome\":\"Matemática\"}]', NULL, '2025-12-16 05:31:37', '2025-12-16 05:31:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ponto_rota`
--

CREATE TABLE `ponto_rota` (
  `id` bigint(20) NOT NULL,
  `rota_id` bigint(20) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `localidade` varchar(100) DEFAULT NULL COMMENT 'Localidade/região do ponto (ex: Lagoa, Itapebussu, Amanari)',
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `ordem` int(11) DEFAULT NULL COMMENT 'Ordem do ponto na rota',
  `tipo` enum('ORIGEM','PARADA','DESTINO') DEFAULT 'PARADA',
  `horario_previsto` time DEFAULT NULL,
  `total_alunos_embarque` int(11) DEFAULT 0 COMMENT 'Quantidade de alunos que embarcam neste ponto',
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto`
--

CREATE TABLE `produto` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(100) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `unidade_medida` varchar(20) DEFAULT NULL,
  `estoque_minimo` decimal(10,0) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL,
  `obs` varchar(255) DEFAULT NULL,
  `localizacao` varchar(255) DEFAULT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `fornecedor_id` bigint(20) DEFAULT NULL,
  `quantidade` int(10) DEFAULT NULL,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produto`
--

INSERT INTO `produto` (`id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, `estoque_minimo`, `criado_em`, `atualizado_em`, `atualizado_por`, `obs`, `localizacao`, `fornecedor`, `fornecedor_id`, `quantidade`, `preco_unitario`, `foto_url`, `ativo`) VALUES
(1001, 'PROD001', 'Arroz Branco Tipo 1', 'CEREAIS', 'Tio João', 'KG', 100, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1002, 'PROD002', 'Feijão Carioca', 'CEREAIS', 'Camil', 'KG', 80, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1003, 'PROD003', 'Macarrão Espaguete', 'CEREAIS', 'Galão', 'KG', 50, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1004, 'PROD004', 'Farinha de Trigo', 'CEREAIS', 'Dona Benta', 'KG', 60, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1005, 'PROD005', 'Açúcar Cristal', 'CEREAIS', 'União', 'KG', 40, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1006, 'PROD006', 'Carne Bovina Moída', 'CARNES', 'Friboi', 'KG', 30, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1007, 'PROD007', 'Frango Inteiro', 'CARNES', 'Sadia', 'KG', 25, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1008, 'PROD008', 'Salsicha', 'CARNES', 'Perdigão', 'KG', 20, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1009, 'PROD009', 'Leite Integral', 'LATICINIOS', 'Itambé', 'L', 200, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1010, 'PROD010', 'Queijo Mussarela', 'LATICINIOS', 'Tirolez', 'KG', 15, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1011, 'PROD011', 'Manteiga', 'LATICINIOS', 'Aviação', 'KG', 10, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1012, 'PROD012', 'Batata', 'HORTIFRUTI', 'Frescor', 'KG', 50, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1013, 'PROD013', 'Cebola', 'HORTIFRUTI', 'Frescor', 'KG', 20, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1014, 'PROD014', 'Tomate', 'HORTIFRUTI', 'Frescor', 'KG', 30, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1015, 'PROD015', 'Banana', 'HORTIFRUTI', 'Frescor', 'KG', 40, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1016, 'PROD016', 'Laranja', 'HORTIFRUTI', 'Frescor', 'KG', 35, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1017, 'PROD017', 'Óleo de Soja', 'OLEOS', 'Liza', 'L', 50, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1018, 'PROD018', 'Margarina', 'OLEOS', 'Qualy', 'KG', 20, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1019, 'PROD019', 'Milho Verde em Conserva', 'ENLATADOS', 'Quero', 'UN', 100, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1020, 'PROD020', 'Ervilha em Conserva', 'ENLATADOS', 'Quero', 'UN', 80, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1021, 'PROD021', 'Suco de Laranja', 'BEBIDAS', 'Maguary', 'L', 150, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1022, 'PROD022', 'Achocolatado em Pó', 'BEBIDAS', 'Nescau', 'KG', 25, '2025-12-01 23:45:56', '2025-12-01 23:45:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1023, 'PROD001', 'Arroz Branco Tipo 1', 'CEREAIS', 'Tio João', 'KG', 100, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1024, 'PROD002', 'Feijão Carioca', 'CEREAIS', 'Camil', 'KG', 80, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1025, 'PROD003', 'Macarrão Espaguete', 'CEREAIS', 'Galão', 'KG', 50, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1026, 'PROD004', 'Farinha de Trigo', 'CEREAIS', 'Dona Benta', 'KG', 60, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1027, 'PROD005', 'Açúcar Cristal', 'CEREAIS', 'União', 'KG', 40, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1028, 'PROD006', 'Carne Bovina Moída', 'CARNES', 'Friboi', 'KG', 30, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1029, 'PROD007', 'Frango Inteiro', 'CARNES', 'Sadia', 'KG', 25, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1030, 'PROD008', 'Salsicha', 'CARNES', 'Perdigão', 'KG', 20, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1031, 'PROD009', 'Leite Integral', 'LATICINIOS', 'Itambé', 'L', 200, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1032, 'PROD010', 'Queijo Mussarela', 'LATICINIOS', 'Tirolez', 'KG', 15, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1033, 'PROD011', 'Manteiga', 'LATICINIOS', 'Aviação', 'KG', 10, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1034, 'PROD012', 'Batata', 'HORTIFRUTI', 'Frescor', 'KG', 50, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1035, 'PROD013', 'Cebola', 'HORTIFRUTI', 'Frescor', 'KG', 20, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1036, 'PROD014', 'Tomate', 'HORTIFRUTI', 'Frescor', 'KG', 30, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1037, 'PROD015', 'Banana', 'HORTIFRUTI', 'Frescor', 'KG', 40, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1038, 'PROD016', 'Laranja', 'HORTIFRUTI', 'Frescor', 'KG', 35, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1039, 'PROD017', 'Óleo de Soja', 'OLEOS', 'Liza', 'L', 50, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1040, 'PROD018', 'Margarina', 'OLEOS', 'Qualy', 'KG', 20, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1041, 'PROD019', 'Milho Verde em Conserva', 'ENLATADOS', 'Quero', 'UN', 100, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1042, 'PROD020', 'Ervilha em Conserva', 'ENLATADOS', 'Quero', 'UN', 80, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1043, 'PROD021', 'Suco de Laranja', 'BEBIDAS', 'Maguary', 'L', 150, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(1044, 'PROD022', 'Achocolatado em Pó', 'BEBIDAS', 'Nescau', 'KG', 25, '2025-12-02 00:00:11', '2025-12-02 00:00:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `professor`
--

CREATE TABLE `professor` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `formacao` text DEFAULT NULL,
  `especializacao` text DEFAULT NULL,
  `registro_profissional` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `pos` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professor`
--

INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `data_admissao`, `ativo`, `pos`) VALUES
(2, 2, '7777777', 'MATEMATICA', NULL, NULL, NULL, '2025-11-29 22:14:01', '2025-12-03 18:53:14', NULL, NULL, 0, NULL),
(3, 8, '3344567', 'HISTORIA', NULL, NULL, NULL, '2025-11-29 22:14:01', '2025-12-04 18:42:57', NULL, NULL, 0, NULL),
(4, 43, 'PROF-000043', 'Licenciatura em Letras', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, '2025-11-30', 1, NULL),
(5, 44, 'PROF-000044', 'Licenciatura em Matemática', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, '2025-11-30', 1, NULL),
(6, 45, 'PROF-000045', 'Licenciatura em História', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, '2025-11-30', 1, NULL),
(7, 2, 'MAT-7582935', 'COMPUTARIA', 'COMPUTARIA', 'na', 'na', '2025-12-08 18:17:31', '2025-12-08 18:17:31', NULL, '2025-12-08', 1, NULL),
(8, 52, '123532523', '[\"MESTRADO\"]', '[\"duas\"]', 'CREA', NULL, '2025-12-11 12:31:49', '2025-12-16 01:13:46', 11, '2025-12-19', 1, NULL),
(9, 53, '34111KEK', 'MATEMATICA', 'AUTISTAS', 'cera', NULL, '2025-12-11 12:40:33', '2025-12-15 12:27:41', 11, '2025-12-11', 0, NULL),
(10, 55, 'MAT-034968', '[\"MATEMATICA\"]', '[\"todas\"]', 'creaf', NULL, '2025-12-11 12:56:40', '2025-12-16 01:13:56', 11, '2025-12-11', 1, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `professor_lotacao`
--

CREATE TABLE `professor_lotacao` (
  `id` bigint(20) NOT NULL,
  `professor_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `carga_horaria` int(11) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professor_lotacao`
--

INSERT INTO `professor_lotacao` (`id`, `professor_id`, `escola_id`, `inicio`, `fim`, `carga_horaria`, `observacao`, `criado_em`) VALUES
(11, 4, 17, '2025-11-30', NULL, 20, 'Professora de Língua Portuguesa', '2025-12-11 15:03:58'),
(12, 5, 17, '2025-11-30', '2025-12-12', 20, 'Professor de Matemática', '2025-12-11 15:03:58'),
(13, 6, 17, '2025-11-30', NULL, 10, 'Professora de História', '2025-12-11 15:03:58'),
(14, 8, 17, '2025-12-11', '2025-12-15', 20, 'na', '2025-12-11 15:03:58'),
(16, 7, 22, '2025-12-12', NULL, 12, NULL, '2025-12-12 13:49:38'),
(17, 5, 22, '2025-12-12', '2025-12-12', NULL, NULL, '2025-12-12 13:50:58'),
(18, 5, 22, '2025-12-12', NULL, 20, NULL, '2025-12-12 13:52:58'),
(19, 5, 25, '2025-12-12', NULL, 30, NULL, '2025-12-12 14:15:12'),
(20, 9, 16, '2025-12-11', NULL, 30, 'aasaa', '2025-12-14 05:19:43'),
(21, 10, 16, '2025-12-11', NULL, 20, 'na', '2025-12-14 05:19:43'),
(22, 8, 25, '2025-12-15', '2025-12-15', 16, NULL, '2025-12-15 13:01:52'),
(23, 8, 22, '2025-12-15', '2025-12-15', 16, NULL, '2025-12-15 13:02:23'),
(24, 8, 26, '2025-12-15', NULL, 14, NULL, '2025-12-15 13:11:47'),
(25, 8, 25, '2025-12-15', NULL, 14, NULL, '2025-12-15 13:11:59');

-- --------------------------------------------------------

--
-- Estrutura para tabela `programa`
--

CREATE TABLE `programa` (
  `id` bigint(20) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `programa`
--

INSERT INTO `programa` (`id`, `nome`, `descricao`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'Gov+ enem mix', 'Programa criado para instruir os alunos da rede publica para o enem.', 1, '2025-12-10 17:50:53', '2025-12-10 17:52:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorio`
--

CREATE TABLE `relatorio` (
  `id` bigint(20) NOT NULL,
  `tipo` enum('FINANCEIRO','PEDAGOGICO','MERENDA','FREQUENCIA','DESEMPENHO','ESTOQUE','NUTRICIONAL','OUTROS') NOT NULL,
  `subtipo` varchar(100) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fim` date DEFAULT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `turma_id` bigint(20) DEFAULT NULL,
  `parametros` text DEFAULT NULL COMMENT 'JSON com parÃ¢metros do relatÃ³rio',
  `arquivo_url` varchar(500) DEFAULT NULL,
  `status` enum('GERANDO','CONCLUIDO','ERRO','CANCELADO') DEFAULT 'GERANDO',
  `gerado_por` bigint(20) NOT NULL,
  `gerado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `concluido_em` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `role_permissao`
--

CREATE TABLE `role_permissao` (
  `id` bigint(20) NOT NULL,
  `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL','ADM_TRANSPORTE','TRANSPORTE_ALUNO') NOT NULL,
  `permissao` varchar(100) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `role_permissao`
--

INSERT INTO `role_permissao` (`id`, `role`, `permissao`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, 'ADM_TRANSPORTE', 'gerenciar_transporte', 1, '2025-12-15 15:03:41', '2025-12-15 15:03:41'),
(2, 'ADM_TRANSPORTE', 'gerenciar_veiculos', 1, '2025-12-15 15:03:42', '2025-12-15 15:03:42'),
(3, 'ADM_TRANSPORTE', 'gerenciar_motoristas', 1, '2025-12-15 15:03:42', '2025-12-15 15:03:42'),
(4, 'ADM_TRANSPORTE', 'gerenciar_rotas', 1, '2025-12-15 15:03:42', '2025-12-15 15:03:42'),
(5, 'ADM_TRANSPORTE', 'visualizar_relatorios_transporte', 1, '2025-12-15 15:03:42', '2025-12-15 15:03:42'),
(6, 'TRANSPORTE_ALUNO', 'criar_rotas', 1, '2025-12-15 15:03:42', '2025-12-15 15:03:42'),
(7, 'TRANSPORTE_ALUNO', 'visualizar_rotas', 1, '2025-12-15 15:03:42', '2025-12-15 15:03:42'),
(8, 'TRANSPORTE_ALUNO', 'gerenciar_geolocalizacao', 1, '2025-12-15 15:03:42', '2025-12-15 15:03:42'),
(9, 'TRANSPORTE_ALUNO', 'atribuir_alunos_rotas', 1, '2025-12-15 15:03:42', '2025-12-15 15:03:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `rota`
--

CREATE TABLE `rota` (
  `id` bigint(20) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `escola_id` bigint(20) DEFAULT NULL,
  `veiculo_id` bigint(20) DEFAULT NULL,
  `motorista_id` bigint(20) DEFAULT NULL,
  `turno` enum('MANHA','TARDE','NOITE','INTEGRAL') DEFAULT NULL,
  `localidades` text DEFAULT NULL COMMENT 'JSON array com as localidades que a rota atende (ex: ["Lagoa","Itapebussu","Amanari"])',
  `distrito` varchar(100) DEFAULT NULL COMMENT 'Distrito principal da rota',
  `total_alunos` int(11) DEFAULT 0 COMMENT 'Total de alunos na rota',
  `distancia_km` decimal(10,2) DEFAULT NULL,
  `tempo_estimado_minutos` int(11) DEFAULT NULL,
  `horario_saida` time DEFAULT NULL,
  `horario_chegada` time DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `serie`
--

CREATE TABLE `serie` (
  `id` bigint(20) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `nivel_ensino` enum('EDUCACAO_INFANTIL','ENSINO_FUNDAMENTAL','ENSINO_MEDIO','EJA') DEFAULT 'ENSINO_FUNDAMENTAL',
  `ordem` int(11) DEFAULT NULL,
  `idade_minima` int(11) DEFAULT NULL,
  `idade_maxima` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `serie`
--

INSERT INTO `serie` (`id`, `nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, '1º Ano', '1ANO', 'ENSINO_FUNDAMENTAL', 1, 8, 7, 'Primeiro ano do Ensino Fundamental', 1, '2025-12-01 02:34:57', '2025-12-15 12:34:59', NULL),
(2, '2º Ano', '2ANO', 'ENSINO_FUNDAMENTAL', 2, 7, 8, 'Segundo ano do Ensino Fundamental', 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL),
(3, '3º Ano', '3ANO', 'ENSINO_FUNDAMENTAL', 3, 8, 9, 'Terceiro ano do Ensino Fundamental', 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL),
(5, '4º Ano', '4ANO', 'ENSINO_FUNDAMENTAL', 4, 9, 10, 'Quarto ano do Ensino Fundamental', 1, '2025-12-15 02:20:47', '2025-12-15 02:20:47', NULL),
(6, '5º Ano', '5ANO', 'ENSINO_FUNDAMENTAL', 5, 10, 11, 'Quinto ano do Ensino Fundamental', 1, '2025-12-15 02:20:47', '2025-12-15 02:20:47', NULL),
(7, '6º Ano', '6ANO', 'ENSINO_FUNDAMENTAL', 6, 11, 12, 'Sexto ano do Ensino Fundamental', 1, '2025-12-15 02:20:47', '2025-12-15 02:20:47', NULL),
(8, '7º Ano', '7ANO', 'ENSINO_FUNDAMENTAL', 7, 12, 13, 'Sétimo ano do Ensino Fundamental', 1, '2025-12-15 02:20:47', '2025-12-15 02:20:47', NULL),
(9, '8º Ano', '8ANO', 'ENSINO_FUNDAMENTAL', 8, 13, 14, 'Oitavo ano do Ensino Fundamental', 1, '2025-12-15 02:20:47', '2025-12-15 02:20:47', NULL),
(10, '9º Ano', '9ANO', 'ENSINO_FUNDAMENTAL', 9, 14, 15, 'Nono ano do Ensino Fundamental', 1, '2025-12-15 02:20:47', '2025-12-15 02:20:47', NULL),
(11, '1º Ano', '1MEDIO', 'ENSINO_MEDIO', 10, 15, 16, 'Primeiro ano do Ensino Médio', 1, '2025-12-15 02:20:47', '2025-12-15 02:20:47', NULL),
(12, '2º Ano', '2MEDIO', 'ENSINO_MEDIO', 11, 16, 17, 'Segundo ano do Ensino Médio', 1, '2025-12-15 02:20:47', '2025-12-15 02:20:47', NULL),
(13, '3º Ano', '3MEDIO', 'ENSINO_MEDIO', 12, 17, 18, 'Terceiro ano do Ensino Médio', 1, '2025-12-15 02:20:47', '2025-12-15 02:20:47', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `substituicao_alimento`
--

CREATE TABLE `substituicao_alimento` (
  `id` bigint(20) NOT NULL,
  `nutricionista_id` bigint(20) NOT NULL,
  `produto_original_id` bigint(20) NOT NULL,
  `produto_substituto_id` bigint(20) NOT NULL,
  `motivo` enum('SAZONALIDADE','DISPONIBILIDADE','NECESSIDADE_ESPECIAL','VALOR_NUTRICIONAL','OUTRO') DEFAULT 'OUTRO',
  `proporcao` decimal(5,2) DEFAULT 1.00 COMMENT 'Proporção de substituição (ex: 1.5 = 1.5kg do substituto para 1kg do original)',
  `equivalencia_nutricional` text DEFAULT NULL COMMENT 'Descrição da equivalência nutricional',
  `observacoes` text DEFAULT NULL,
  `aprovado` tinyint(1) DEFAULT 0 COMMENT 'Se foi aprovado para uso geral',
  `aprovado_por` bigint(20) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sugestões de substituição de alimentos';

-- --------------------------------------------------------

--
-- Estrutura para tabela `turma`
--

CREATE TABLE `turma` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `serie_id` bigint(20) DEFAULT NULL,
  `ano_letivo` int(11) DEFAULT NULL,
  `serie` varchar(20) DEFAULT NULL,
  `letra` varchar(3) DEFAULT NULL,
  `turno` enum('MANHA','TARDE','NOITE','INTEGRAL') DEFAULT NULL,
  `capacidade` int(11) DEFAULT NULL,
  `sala` varchar(50) DEFAULT NULL,
  `coordenador_id` bigint(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turma`
--

INSERT INTO `turma` (`id`, `escola_id`, `serie_id`, `ano_letivo`, `serie`, `letra`, `turno`, `capacidade`, `sala`, `coordenador_id`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES
(1, 17, 1, 2025, '1º Ano', 'A', 'MANHA', 30, NULL, NULL, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL),
(2, 17, 2, 2025, '2º Ano', 'A', 'MANHA', 30, NULL, NULL, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL),
(3, 17, 3, 2025, '3º Ano', 'A', 'MANHA', 30, NULL, NULL, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL),
(4, 22, NULL, 2025, '7º ANO', 'B', 'MANHA', 25, '04', NULL, NULL, 1, '2025-12-12 00:09:56', '2025-12-12 00:26:05', NULL),
(5, 22, NULL, 2025, '7º Ano', 'A', 'MANHA', 25, '04', NULL, NULL, 1, '2025-12-12 00:20:45', '2025-12-12 00:20:45', NULL),
(6, 22, NULL, 2025, '7º ANO', 'C', 'MANHA', 25, '04', NULL, NULL, 1, '2025-12-12 00:20:50', '2025-12-12 00:26:13', NULL),
(7, 25, 11, 2025, '1º ANO', 'A', 'MANHA', 23, '04', NULL, NULL, 1, '2025-12-15 03:53:26', '2025-12-15 03:53:26', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `turma_professor`
--

CREATE TABLE `turma_professor` (
  `id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `professor_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `regime` enum('REGULAR','SUBSTITUTO') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turma_professor`
--

INSERT INTO `turma_professor` (`id`, `turma_id`, `professor_id`, `disciplina_id`, `inicio`, `fim`, `regime`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(11, 7, 5, 7, '2025-12-15', '2025-12-15', 'SUBSTITUTO', NULL, '2025-12-16 01:08:32', '2025-12-16 01:28:26', NULL),
(12, 7, 5, 5, '2025-12-15', NULL, 'REGULAR', NULL, '2025-12-16 01:08:43', '2025-12-16 01:08:43', NULL),
(13, 5, 5, 2, '2025-12-16', NULL, 'REGULAR', NULL, '2025-12-16 05:04:23', '2025-12-16 05:04:23', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL','ADM_TRANSPORTE','TRANSPORTE_ALUNO') DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `email_verificado` tinyint(1) DEFAULT 0,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expiracao` timestamp NULL DEFAULT NULL,
  `tentativas_login` int(11) DEFAULT 0,
  `bloqueado_ate` timestamp NULL DEFAULT NULL,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `email_verificado`, `token_recuperacao`, `token_expiracao`, `tentativas_login`, `bloqueado_ate`, `ultimo_login`, `ultimo_acesso`, `created_at`, `atualizado_em`, `atualizado_por`) VALUES
(1, 1, 'Roger', '1', 'ADM', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-09-19 16:35:40', '2025-11-29 22:14:02', NULL),
(2, 2, 'lavosier', '$2y$10$cJ4zJP1As7NtakAsDLmRfu2X.2z53ZEDo1SRT1131di5djhclf6Zi', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, '2025-12-10 12:48:08', NULL, '2025-09-22 19:17:23', '2025-12-10 12:48:08', NULL),
(3, 3, 'francisco', '$2y$10$RqVIvLDU2B3aMH8D5DCUeubFZ0dVMgvfNgzbhCqWr6REia5O/69gy', 'ADM', 1, 0, NULL, NULL, 0, NULL, '2025-12-16 15:39:29', '2025-12-16 16:28:14', '2025-09-22 19:42:40', '2025-12-16 16:28:14', NULL),
(4, 4, 'yudi', '$2y$10$3WUQGohoZf8tiE0UvSC43uxF4kQCrjERBG8NmfyMQZ8FgMHN0vKnS', 'GESTAO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-09-23 17:56:04', '2025-11-29 22:14:02', NULL),
(5, 5, 'raimundo', '$2y$10$yAoiZi1i3HOosehIwKCg5OMua7tXjlpVIlm5SJAuIIfZ/tcoNbup.', 'GESTAO', 1, 0, NULL, NULL, 0, NULL, '2025-12-08 13:04:56', NULL, '2025-09-23 17:58:50', '2025-12-08 13:04:56', NULL),
(6, 6, 'cabra', '$2y$10$KjDXdWEqd.98YRW6bHErve.JEjPU6hx0Nb1QjJd4DvjcSRZJMlyoG', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, '2025-09-29 17:00:01', NULL, '2025-09-29 16:56:48', '2025-11-29 22:14:02', NULL),
(7, 10, 'joao.silva', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-11-29 23:05:38', '2025-11-29 23:05:38', NULL),
(8, 11, 'gestor.teste', '$2y$10$97/wPF7UQfMIuhy17lkgpOzvzcOLawjW.wB6Y8ctM2JnxYt5NIAGm', 'GESTAO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 02:34:57', '2025-12-09 12:55:35', NULL),
(10, 43, 'maria', '$2y$10$FgA0jUH/2TgUfko7QmwmB.qWLPO9kHy9CZ4eX/CbnVDTfSbaqW9.C', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, '2025-12-16 00:12:14', '2025-12-16 00:12:14', '2025-12-01 02:40:04', '2025-12-16 00:12:14', NULL),
(11, 44, 'josé', '$2y$10$4gl3Gwp/0u7z6EMmR1/IZOPg30t4pYLt2ps.mEMNrZIpLo3Jna4aq', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, '2025-12-16 05:03:39', '2025-12-16 14:23:36', '2025-12-01 02:40:04', '2025-12-16 14:23:36', NULL),
(12, 45, 'patrícia', '1', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-08 18:12:21', NULL),
(13, 28, 'ana.silva.santos', '$2y$10$5Tcc269FHgJLZYq4PeqUZe.QC9L0xuQS5FZ2d/.Ph6WGi3.8zKtu6', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:14:38', NULL),
(14, 29, 'bruno.oliveira.costa', '$2y$10$KEX8cchujO5TOR46UuGr3uWAKj4VNdXnNVFuiCjGOt6g0awnz0ZLi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-15 00:11:20', NULL),
(15, 30, 'carla.mendes.lima', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(16, 31, 'daniel.souza.alves', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(17, 32, 'eduarda.ferreira.rocha', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(18, 33, 'felipe.gomes.pereira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(19, 34, 'gabriela.martins.dias', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(20, 35, 'henrique.barbosa.ramos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(21, 36, 'isabela.nunes.cardoso', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(22, 37, 'joão.pedro.teixeira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(23, 38, 'larissa.araújo.freitas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(24, 39, 'marcos.vinicius.lopes', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(25, 40, 'natália.correia.monteiro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(26, 41, 'otávio.ribeiro.campos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(27, 42, 'paula.cristina.moreira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
(28, 46, 'merenda.teste', '$2y$10$t/nUJBD4VyWkx6YK5fcRAu3kaOFsMX0nZ9zWBdHvlqZQstiVAZCji', 'ADM_MERENDA', 1, 1, NULL, NULL, 0, NULL, '2025-12-16 00:12:24', '2025-12-16 00:12:24', '2025-12-02 00:08:28', '2025-12-16 00:12:24', NULL),
(29, 47, 'nutricionista.teste', '$2y$10$MDKU1U5HUkVOcWh1yDOB1emNwjYG8PEYlOY0I4MDkDZ90q0y/fG1G', 'NUTRICIONISTA', 1, 1, NULL, NULL, 0, NULL, '2025-12-16 02:35:26', '2025-12-16 02:35:26', '2025-12-08 13:12:49', '2025-12-16 02:35:26', NULL),
(30, 48, 'antoniosilva6723', '$2y$10$cL858W9Dyst4Jua46DJxLOxjqFB03IzY3eDZPTYInM21eAPsR6Kpy', 'RESPONSAVEL', 0, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-09 13:28:36', '2025-12-09 13:38:19', NULL),
(31, 50, 'antoniosilva6728', '$2y$10$J/v3PtK5otOtvD13Yh7JgOlUusPhkl4nHhdbAHnONzuCetXfz1Fkq', 'RESPONSAVEL', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-09 13:45:00', '2025-12-10 17:18:35', NULL),
(32, 52, 'testeeeeeeeeee', '$2y$10$0gGssazv/8s8U0S94wizGuTeOOTL2539Ok9j8i8H5XkRcEPS9T2ie', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-11 12:31:49', '2025-12-11 12:31:49', NULL),
(33, 53, 'abaf', '$2y$10$t5hLEyD1CJN1cmN587kI0uVfarDG.t4A75jeF98X6wK9g0VC80VPi', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-11 12:40:33', '2025-12-11 12:40:33', NULL),
(34, 55, 'ssssdssssss', '$2y$10$OztU7NreOo5fC1Fz0aJHoub5f6VqiyUajvRSh5Z.yeZNRUKzI6NV6', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-11 12:56:40', '2025-12-11 12:56:40', NULL),
(35, 56, 'adm.transporte', '$2y$10$oArwLQxx/krHzIu0ji1CbuCzvPsirf6cmL5UdTrf2sXUZme2PN7xe', 'ADM_TRANSPORTE', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-15 15:06:01', '2025-12-15 15:07:40', NULL),
(36, 57, 'transporte.aluno', '$2y$10$mY7.eMKOFCKVGWw.E9LU2.rJWAVXeOrsRIh71CuUN2TFDAq0pL2by', 'TRANSPORTE_ALUNO', 1, 1, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-15 15:06:01', '2025-12-15 15:10:06', NULL),
(37, 59, 'franciscobestafera1212', '$2y$10$RWOoKRczqVzgrcBA/F/Jz.JVOU0v9d7NDU32JrOO0EiZCEJY0Dr7a', 'RESPONSAVEL', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-15 16:16:29', '2025-12-15 16:16:29', NULL),
(38, 61, 'ronerio2121', '$2y$10$f.voS8U9ijo/f5hWv9j17.YFPIfdARScAy7nmUM5i91aLe32b/s7y', 'RESPONSAVEL', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-16 04:24:44', '2025-12-16 04:24:44', NULL),
(39, 63, 'joseronerio1231', '$2y$10$mHQRdH3z5F7TkD9MpAxsxO.7Tgp10pzlIcLYKNfpKW3e7UE2RVrU.', 'RESPONSAVEL', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-16 04:29:30', '2025-12-16 04:29:30', NULL),
(40, 65, 'joseronerio3131', '$2y$10$nUn1UFL4ySDcrD8TMpIh0eU9PIPNHCddgGHwSwLJI40bFNv9kTjsa', 'RESPONSAVEL', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-16 04:37:39', '2025-12-16 04:37:39', NULL),
(41, 68, 'jose0011', '$2y$10$uD7eBJXN2FGzF4ySaqNAbu.7yPr1hwYbZuR6MRqqvT/7AKML0PB6C', 'RESPONSAVEL', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-16 04:51:19', '2025-12-16 04:51:19', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `validacao`
--

CREATE TABLE `validacao` (
  `id` bigint(20) NOT NULL,
  `tipo_registro` enum('NOTA','FREQUENCIA','PLANO_AULA','OBSERVACAO','COMUNICADO','CARDAPIO','PEDIDO','OUTROS') NOT NULL,
  `registro_id` bigint(20) NOT NULL,
  `status` enum('PENDENTE','APROVADO','REJEITADO') DEFAULT 'PENDENTE',
  `observacoes` text DEFAULT NULL,
  `validado_por` bigint(20) DEFAULT NULL,
  `data_validacao` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `veiculo`
--

CREATE TABLE `veiculo` (
  `id` bigint(20) NOT NULL,
  `placa` varchar(10) NOT NULL,
  `renavam` varchar(20) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `ano` int(11) DEFAULT NULL,
  `cor` varchar(50) DEFAULT NULL,
  `capacidade_maxima` int(11) NOT NULL COMMENT 'Lotação máxima do veículo',
  `capacidade_minima` int(11) DEFAULT NULL COMMENT 'Lotação mínima recomendada para viabilidade (ex: van pequena = 8, ônibus = 30)',
  `tipo` enum('ONIBUS','VAN','MICROONIBUS','OUTRO') DEFAULT 'ONIBUS',
  `numero_frota` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `veiculo`
--

INSERT INTO `veiculo` (`id`, `placa`, `renavam`, `marca`, `modelo`, `ano`, `cor`, `capacidade_maxima`, `capacidade_minima`, `tipo`, `numero_frota`, `observacoes`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) VALUES
(1, 'ABC1D23', '12345678901', 'Mercedes-Benz', 'OF-1712 Escolar', 2018, 'Amarelo', 52, 17, 'ONIBUS', '06', NULL, 1, '2025-12-16 14:52:31', '2025-12-16 14:52:31', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `viagem`
--

CREATE TABLE `viagem` (
  `id` bigint(20) NOT NULL,
  `rota_id` bigint(20) NOT NULL,
  `veiculo_id` bigint(20) DEFAULT NULL,
  `motorista_id` bigint(20) DEFAULT NULL,
  `data` date NOT NULL,
  `tipo` enum('IDA','VOLTA','IDA_VOLTA') DEFAULT 'IDA',
  `horario_saida_previsto` time DEFAULT NULL,
  `horario_saida_real` time DEFAULT NULL,
  `horario_chegada_previsto` time DEFAULT NULL,
  `horario_chegada_real` time DEFAULT NULL,
  `total_alunos` int(11) DEFAULT 0,
  `total_alunos_embarcados` int(11) DEFAULT 0,
  `status` enum('AGENDADA','EM_ANDAMENTO','CONCLUIDA','CANCELADA','ATRASADA') DEFAULT 'AGENDADA',
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `viagem_aluno`
--

CREATE TABLE `viagem_aluno` (
  `id` bigint(20) NOT NULL,
  `viagem_id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `ponto_embarque_id` bigint(20) DEFAULT NULL,
  `ponto_desembarque_id` bigint(20) DEFAULT NULL,
  `horario_embarque` time DEFAULT NULL,
  `horario_desembarque` time DEFAULT NULL,
  `presente` tinyint(1) DEFAULT 1,
  `observacoes` text DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD KEY `idx_aluno_escola` (`escola_id`),
  ADD KEY `idx_aluno_transporte` (`precisa_transporte`,`distrito_transporte`,`localidade_transporte`,`ativo`);

--
-- Índices de tabela `aluno_responsavel`
--
ALTER TABLE `aluno_responsavel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_aluno_responsavel` (`aluno_id`,`responsavel_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `responsavel_id` (`responsavel_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_aluno_responsavel_ativo` (`ativo`),
  ADD KEY `idx_responsavel_ativo` (`responsavel_id`,`ativo`),
  ADD KEY `idx_aluno_ativo` (`aluno_id`,`ativo`);

--
-- Índices de tabela `aluno_rota`
--
ALTER TABLE `aluno_rota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `rota_id` (`rota_id`),
  ADD KEY `ponto_embarque_id` (`ponto_embarque_id`),
  ADD KEY `ponto_desembarque_id` (`ponto_desembarque_id`),
  ADD KEY `geolocalizacao_id` (`geolocalizacao_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_aluno_rota_status` (`status`),
  ADD KEY `idx_aluno_rota_ativo` (`aluno_id`,`status`);

--
-- Índices de tabela `aluno_turma`
--
ALTER TABLE `aluno_turma`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_aluno_turma_ativo` (`aluno_id`,`turma_id`,`status`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_aluno_turma_status` (`status`),
  ADD KEY `idx_aluno_turma_inicio` (`inicio`);

--
-- Índices de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_avaliacao_data` (`data`),
  ADD KEY `idx_avaliacao_tipo` (`tipo`),
  ADD KEY `idx_avaliacao_ativo` (`ativo`);

--
-- Índices de tabela `boletim`
--
ALTER TABLE `boletim`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_boletim_aluno_turma_bimestre` (`aluno_id`,`turma_id`,`ano_letivo`,`bimestre`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `gerado_por` (`gerado_por`),
  ADD KEY `idx_boletim_ano_bimestre` (`ano_letivo`,`bimestre`),
  ADD KEY `idx_boletim_situacao` (`situacao`);

--
-- Índices de tabela `boletim_item`
--
ALTER TABLE `boletim_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boletim_id` (`boletim_id`),
  ADD KEY `disciplina_id` (`disciplina_id`);

--
-- Índices de tabela `calendar_categories`
--
ALTER TABLE `calendar_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Índices de tabela `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `start_date` (`start_date`),
  ADD KEY `event_type` (`event_type`),
  ADD KEY `idx_calendar_events_date` (`start_date`,`end_date`),
  ADD KEY `idx_calendar_events_school` (`school_id`,`ativo`),
  ADD KEY `idx_calendar_events_type` (`event_type`,`ativo`),
  ADD KEY `idx_calendar_events_creator` (`created_by`,`ativo`);

--
-- Índices de tabela `calendar_event_participants`
--
ALTER TABLE `calendar_event_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participant` (`event_id`,`user_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `calendar_event_recurrence`
--
ALTER TABLE `calendar_event_recurrence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Índices de tabela `calendar_notifications`
--
ALTER TABLE `calendar_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `calendar_settings`
--
ALTER TABLE `calendar_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `cardapio`
--
ALTER TABLE `cardapio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `aprovado_por` (`aprovado_por`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_cardapio_status` (`status`),
  ADD KEY `idx_cardapio_mes_ano` (`mes`,`ano`),
  ADD KEY `idx_cardapio_escola_mes_ano` (`escola_id`,`mes`,`ano`),
  ADD KEY `idx_cardapio_criado_por` (`criado_por`);

--
-- Índices de tabela `cardapio_item`
--
ALTER TABLE `cardapio_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cardapio_id` (`cardapio_id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `idx_cardapio_item_semana` (`semana_id`);

--
-- Índices de tabela `cardapio_semana`
--
ALTER TABLE `cardapio_semana`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cardapio_semana_cardapio` (`cardapio_id`),
  ADD KEY `idx_cardapio_semana_numero` (`cardapio_id`,`numero_semana`);

--
-- Índices de tabela `comunicado`
--
ALTER TABLE `comunicado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `enviado_por` (`enviado_por`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `lido_por` (`lido_por`),
  ADD KEY `idx_comunicado_tipo` (`tipo`),
  ADD KEY `idx_comunicado_prioridade` (`prioridade`),
  ADD KEY `idx_comunicado_lido` (`lido`),
  ADD KEY `idx_comunicado_ativo` (`ativo`),
  ADD KEY `idx_comunicado_data` (`criado_em`),
  ADD KEY `idx_comunicado_canal` (`canal`),
  ADD KEY `idx_comunicado_enviado` (`enviado`);

--
-- Índices de tabela `comunicado_resposta`
--
ALTER TABLE `comunicado_resposta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_comunicado_responsavel` (`comunicado_id`,`responsavel_id`),
  ADD KEY `comunicado_id` (`comunicado_id`),
  ADD KEY `responsavel_id` (`responsavel_id`);

--
-- Índices de tabela `configuracao`
--
ALTER TABLE `configuracao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave_unique` (`chave`);

--
-- Índices de tabela `consumo_diario`
--
ALTER TABLE `consumo_diario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_consumo_escola_turma_data` (`escola_id`,`turma_id`,`data`,`turno`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_consumo_data` (`data`),
  ADD KEY `idx_consumo_escola_data` (`escola_id`,`data`);

--
-- Índices de tabela `consumo_item`
--
ALTER TABLE `consumo_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consumo_diario_id` (`consumo_diario_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `custo_merenda`
--
ALTER TABLE `custo_merenda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_custo_data` (`data`),
  ADD KEY `idx_custo_tipo` (`tipo`),
  ADD KEY `idx_custo_mes_ano` (`mes`,`ano`),
  ADD KEY `idx_custo_escola_mes_ano` (`escola_id`,`mes`,`ano`);

--
-- Índices de tabela `desperdicio`
--
ALTER TABLE `desperdicio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_desperdicio_data` (`data`),
  ADD KEY `idx_desperdicio_motivo` (`motivo`),
  ADD KEY `idx_desperdicio_escola_data` (`escola_id`,`data`);

--
-- Índices de tabela `disciplina`
--
ALTER TABLE `disciplina`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_disciplina_ativo` (`ativo`),
  ADD KEY `idx_disciplina_area` (`area_conhecimento`);

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
-- Índices de tabela `distrito_ponto_central`
--
ALTER TABLE `distrito_ponto_central`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `distrito` (`distrito`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `idx_ativo` (`ativo`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `entrega`
--
ALTER TABLE `entrega`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_cesta_id` (`pedido_cesta_id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `recebido_por` (`recebido_por`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_entrega_status` (`status`),
  ADD KEY `idx_entrega_data_prevista` (`data_prevista`),
  ADD KEY `idx_entrega_data_entrega` (`data_entrega`);

--
-- Índices de tabela `entrega_item`
--
ALTER TABLE `entrega_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entrega_id` (`entrega_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `escola`
--
ALTER TABLE `escola`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD KEY `diretor_id` (`diretor_id`),
  ADD KEY `idx_escola_ativo` (`ativo`),
  ADD KEY `idx_escola_municipio` (`municipio`),
  ADD KEY `idx_escola_estado` (`estado`),
  ADD KEY `idx_escola_distrito` (`distrito`,`ativo`);

--
-- Índices de tabela `escola_backup`
--
ALTER TABLE `escola_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_escola_id_original` (`escola_id_original`),
  ADD KEY `idx_excluido_em` (`excluido_em`),
  ADD KEY `idx_revertido` (`revertido`),
  ADD KEY `idx_excluido_permanentemente` (`excluido_permanentemente`),
  ADD KEY `excluido_por` (`excluido_por`),
  ADD KEY `revertido_por` (`revertido_por`);

--
-- Índices de tabela `escola_programa`
--
ALTER TABLE `escola_programa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_escola_programa_escola` (`escola_id`),
  ADD KEY `fk_escola_programa_programa` (`programa_id`);

--
-- Índices de tabela `estoque_central`
--
ALTER TABLE `estoque_central`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_estoque_validade` (`validade`),
  ADD KEY `idx_estoque_lote` (`lote`),
  ADD KEY `idx_estoque_fornecedor` (`fornecedor`),
  ADD KEY `fornecedor_id` (`fornecedor_id`);

--
-- Índices de tabela `fornecedor`
--
ALTER TABLE `fornecedor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_fornecedor_ativo` (`ativo`),
  ADD KEY `idx_fornecedor_tipo` (`tipo_fornecedor`),
  ADD KEY `idx_fornecedor_cidade` (`cidade`);

--
-- Índices de tabela `frequencia`
--
ALTER TABLE `frequencia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_frequencia_aluno_data` (`aluno_id`,`turma_id`,`data`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `justificativa_id` (`justificativa_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_frequencia_data` (`data`),
  ADD KEY `idx_frequencia_presenca` (`presenca`),
  ADD KEY `validado_por` (`validado_por`);

--
-- Índices de tabela `funcionario`
--
ALTER TABLE `funcionario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_funcionario_ativo` (`ativo`),
  ADD KEY `idx_funcionario_cargo` (`cargo`),
  ADD KEY `idx_funcionario_setor` (`setor`);

--
-- Índices de tabela `funcionario_lotacao`
--
ALTER TABLE `funcionario_lotacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `funcionario_id` (`funcionario_id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `geolocalizacao_aluno`
--
ALTER TABLE `geolocalizacao_aluno`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_geoloc_aluno_principal` (`aluno_id`,`principal`),
  ADD KEY `idx_geoloc_localidade` (`localidade`);

--
-- Índices de tabela `gestor`
--
ALTER TABLE `gestor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_gestor_ativo` (`ativo`);

--
-- Índices de tabela `gestor_lotacao`
--
ALTER TABLE `gestor_lotacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gestor_id` (`gestor_id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_gestor_lotacao_responsavel` (`responsavel`),
  ADD KEY `idx_gestor_lotacao_tipo` (`tipo`),
  ADD KEY `idx_gestor_lotacao_escola` (`escola_id`);

--
-- Índices de tabela `habilidades_bncc`
--
ALTER TABLE `habilidades_bncc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_codigo_bncc` (`codigo_bncc`),
  ADD KEY `idx_ano` (`ano_inicio`,`ano_fim`),
  ADD KEY `idx_codigo` (`codigo_bncc`),
  ADD KEY `idx_componente` (`componente`);

--
-- Índices de tabela `historico_escolar`
--
ALTER TABLE `historico_escolar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `gerado_por` (`gerado_por`),
  ADD KEY `idx_historico_ano` (`ano_letivo`),
  ADD KEY `idx_historico_situacao` (`situacao`);

--
-- Índices de tabela `indicador_nutricional`
--
ALTER TABLE `indicador_nutricional`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `cardapio_id` (`cardapio_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_indicador_tipo` (`tipo_indicador`),
  ADD KEY `idx_indicador_periodo` (`periodo_inicio`,`periodo_fim`);

--
-- Índices de tabela `justificativa`
--
ALTER TABLE `justificativa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `enviado_por` (`enviado_por`),
  ADD KEY `analisado_por` (`analisado_por`),
  ADD KEY `idx_justificativa_status` (`status`),
  ADD KEY `idx_justificativa_data_envio` (`data_envio`);

--
-- Índices de tabela `log_sistema`
--
ALTER TABLE `log_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `tipo` (`tipo`),
  ADD KEY `criado_em` (`criado_em`),
  ADD KEY `idx_log_acao` (`acao`),
  ADD KEY `idx_log_tipo_acao` (`tipo`,`acao`),
  ADD KEY `idx_log_usuario_tipo` (`usuario_id`,`tipo`);

--
-- Índices de tabela `motorista`
--
ALTER TABLE `motorista`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnh` (`cnh`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_motorista_ativo` (`ativo`);

--
-- Índices de tabela `motorista_veiculo`
--
ALTER TABLE `motorista_veiculo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `motorista_id` (`motorista_id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `idx_motorista_veiculo_principal` (`motorista_id`,`principal`);

--
-- Índices de tabela `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `realizado_por` (`realizado_por`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `idx_movimentacao_tipo` (`tipo`),
  ADD KEY `idx_movimentacao_data` (`realizado_em`),
  ADD KEY `idx_movimentacao_produto_tipo` (`produto_id`,`tipo`);

--
-- Índices de tabela `nota`
--
ALTER TABLE `nota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avaliacao_id` (`avaliacao_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `lancado_por` (`lancado_por`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_nota_bimestre` (`bimestre`),
  ADD KEY `idx_nota_recuperacao` (`recuperacao`),
  ADD KEY `idx_nota_aluno_disciplina` (`aluno_id`,`disciplina_id`),
  ADD KEY `validado_por` (`validado_por`);

--
-- Índices de tabela `nutricionista`
--
ALTER TABLE `nutricionista`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `crn` (`crn`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_nutricionista_ativo` (`ativo`);

--
-- Índices de tabela `nutricionista_lotacao`
--
ALTER TABLE `nutricionista_lotacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nutricionista_id` (`nutricionista_id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_nutricionista_lotacao_responsavel` (`responsavel`),
  ADD KEY `idx_nutricionista_lotacao_escola` (`escola_id`),
  ADD KEY `idx_nutricionista_lotacao_inicio` (`inicio`);

--
-- Índices de tabela `observacao_desempenho`
--
ALTER TABLE `observacao_desempenho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_observacao_data` (`data`),
  ADD KEY `idx_observacao_tipo` (`tipo`),
  ADD KEY `idx_observacao_bimestre` (`bimestre`);

--
-- Índices de tabela `pacote`
--
ALTER TABLE `pacote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `pacote_escola`
--
ALTER TABLE `pacote_escola`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`);

--
-- Índices de tabela `pacote_escola_item`
--
ALTER TABLE `pacote_escola_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pacote_id` (`pacote_id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `idx_estoque_central_id` (`estoque_central_id`);

--
-- Índices de tabela `pacote_item`
--
ALTER TABLE `pacote_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pacote_id` (`pacote_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `parecer_tecnico`
--
ALTER TABLE `parecer_tecnico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nutricionista_id` (`nutricionista_id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `cardapio_id` (`cardapio_id`),
  ADD KEY `idx_parecer_tipo` (`tipo`),
  ADD KEY `idx_parecer_status` (`status`),
  ADD KEY `idx_parecer_data_referencia` (`data_referencia`);

--
-- Índices de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `expira_em` (`expira_em`),
  ADD KEY `usado` (`usado`);

--
-- Índices de tabela `pedido_cesta`
--
ALTER TABLE `pedido_cesta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aprovado_por` (`aprovado_por`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `nutricionista_id` (`nutricionista_id`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_pedido_status` (`status`),
  ADD KEY `idx_pedido_mes` (`mes`),
  ADD KEY `idx_pedido_data_criacao` (`data_criacao`),
  ADD KEY `idx_pedido_nutricionista_status` (`nutricionista_id`,`status`);

--
-- Índices de tabela `pedido_item`
--
ALTER TABLE `pedido_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `produto_id` (`produto_id`);

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
-- Índices de tabela `plano_aula`
--
ALTER TABLE `plano_aula`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `aprovado_por` (`aprovado_por`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_plano_data_aula` (`data_aula`),
  ADD KEY `idx_plano_status` (`status`),
  ADD KEY `idx_plano_bimestre` (`bimestre`);

--
-- Índices de tabela `ponto_rota`
--
ALTER TABLE `ponto_rota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rota_id` (`rota_id`),
  ADD KEY `idx_ponto_rota_ordem` (`rota_id`,`ordem`),
  ADD KEY `idx_ponto_rota_ativo` (`ativo`),
  ADD KEY `idx_ponto_rota_localidade` (`localidade`);

--
-- Índices de tabela `produto`
--
ALTER TABLE `produto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_produto_categoria` (`categoria`),
  ADD KEY `idx_produto_ativo` (`ativo`),
  ADD KEY `idx_produto_nome` (`nome`),
  ADD KEY `fornecedor_id` (`fornecedor_id`);

--
-- Índices de tabela `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_professor_ativo` (`ativo`);

--
-- Índices de tabela `professor_lotacao`
--
ALTER TABLE `professor_lotacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `escola_id` (`escola_id`);

--
-- Índices de tabela `programa`
--
ALTER TABLE `programa`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `relatorio`
--
ALTER TABLE `relatorio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `gerado_por` (`gerado_por`),
  ADD KEY `idx_relatorio_tipo` (`tipo`),
  ADD KEY `idx_relatorio_status` (`status`),
  ADD KEY `idx_relatorio_periodo` (`periodo_inicio`,`periodo_fim`);

--
-- Índices de tabela `role_permissao`
--
ALTER TABLE `role_permissao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permissao_unique` (`role`,`permissao`);

--
-- Índices de tabela `rota`
--
ALTER TABLE `rota`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `motorista_id` (`motorista_id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_rota_ativo` (`ativo`),
  ADD KEY `idx_rota_distrito` (`distrito`);

--
-- Índices de tabela `serie`
--
ALTER TABLE `serie`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_serie_nivel` (`nivel_ensino`),
  ADD KEY `idx_serie_ativo` (`ativo`);

--
-- Índices de tabela `substituicao_alimento`
--
ALTER TABLE `substituicao_alimento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nutricionista_id` (`nutricionista_id`),
  ADD KEY `produto_original_id` (`produto_original_id`),
  ADD KEY `produto_substituto_id` (`produto_substituto_id`),
  ADD KEY `aprovado_por` (`aprovado_por`),
  ADD KEY `idx_substituicao_motivo` (`motivo`),
  ADD KEY `idx_substituicao_aprovado` (`aprovado`),
  ADD KEY `idx_substituicao_ativo` (`ativo`);

--
-- Índices de tabela `turma`
--
ALTER TABLE `turma`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `coordenador_id` (`coordenador_id`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_turma_ano_letivo` (`ano_letivo`),
  ADD KEY `idx_turma_turno` (`turno`),
  ADD KEY `idx_turma_ativo` (`ativo`),
  ADD KEY `idx_turma_escola_ano` (`escola_id`,`ano_letivo`),
  ADD KEY `serie_id` (`serie_id`);

--
-- Índices de tabela `turma_professor`
--
ALTER TABLE `turma_professor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_turma_professor_disciplina` (`turma_id`,`professor_id`,`disciplina_id`,`inicio`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pessoa_id` (`pessoa_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `atualizado_por` (`atualizado_por`),
  ADD KEY `idx_usuario_ativo` (`ativo`),
  ADD KEY `idx_usuario_role` (`role`),
  ADD KEY `idx_usuario_email_verificado` (`email_verificado`);

--
-- Índices de tabela `validacao`
--
ALTER TABLE `validacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `validado_por` (`validado_por`),
  ADD KEY `idx_validacao_tipo` (`tipo_registro`),
  ADD KEY `idx_validacao_status` (`status`),
  ADD KEY `idx_validacao_registro` (`tipo_registro`,`registro_id`);

--
-- Índices de tabela `veiculo`
--
ALTER TABLE `veiculo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `placa` (`placa`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_veiculo_ativo` (`ativo`),
  ADD KEY `idx_veiculo_capacidade` (`capacidade_maxima`,`capacidade_minima`);

--
-- Índices de tabela `viagem`
--
ALTER TABLE `viagem`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rota_id` (`rota_id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `motorista_id` (`motorista_id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_viagem_data` (`data`),
  ADD KEY `idx_viagem_status` (`status`),
  ADD KEY `idx_viagem_rota_data` (`rota_id`,`data`);

--
-- Índices de tabela `viagem_aluno`
--
ALTER TABLE `viagem_aluno`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_viagem_aluno` (`viagem_id`,`aluno_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `ponto_embarque_id` (`ponto_embarque_id`),
  ADD KEY `ponto_desembarque_id` (`ponto_desembarque_id`),
  ADD KEY `idx_viagem_aluno_presente` (`presente`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aluno`
--
ALTER TABLE `aluno`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `aluno_responsavel`
--
ALTER TABLE `aluno_responsavel`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `aluno_rota`
--
ALTER TABLE `aluno_rota`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `aluno_turma`
--
ALTER TABLE `aluno_turma`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `boletim`
--
ALTER TABLE `boletim`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `boletim_item`
--
ALTER TABLE `boletim_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `calendar_categories`
--
ALTER TABLE `calendar_categories`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `calendar_event_participants`
--
ALTER TABLE `calendar_event_participants`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `calendar_event_recurrence`
--
ALTER TABLE `calendar_event_recurrence`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `calendar_notifications`
--
ALTER TABLE `calendar_notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `calendar_settings`
--
ALTER TABLE `calendar_settings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `cardapio`
--
ALTER TABLE `cardapio`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1009;

--
-- AUTO_INCREMENT de tabela `cardapio_item`
--
ALTER TABLE `cardapio_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de tabela `cardapio_semana`
--
ALTER TABLE `cardapio_semana`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `comunicado`
--
ALTER TABLE `comunicado`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `comunicado_resposta`
--
ALTER TABLE `comunicado_resposta`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracao`
--
ALTER TABLE `configuracao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `consumo_diario`
--
ALTER TABLE `consumo_diario`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `consumo_item`
--
ALTER TABLE `consumo_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `custo_merenda`
--
ALTER TABLE `custo_merenda`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `desperdicio`
--
ALTER TABLE `desperdicio`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `disciplina`
--
ALTER TABLE `disciplina`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `distrito_localidade`
--
ALTER TABLE `distrito_localidade`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `distrito_ponto_central`
--
ALTER TABLE `distrito_ponto_central`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `entrega`
--
ALTER TABLE `entrega`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `entrega_item`
--
ALTER TABLE `entrega_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `escola`
--
ALTER TABLE `escola`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `escola_backup`
--
ALTER TABLE `escola_backup`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `escola_programa`
--
ALTER TABLE `escola_programa`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `estoque_central`
--
ALTER TABLE `estoque_central`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1027;

--
-- AUTO_INCREMENT de tabela `fornecedor`
--
ALTER TABLE `fornecedor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1015;

--
-- AUTO_INCREMENT de tabela `frequencia`
--
ALTER TABLE `frequencia`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `funcionario`
--
ALTER TABLE `funcionario`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `funcionario_lotacao`
--
ALTER TABLE `funcionario_lotacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `geolocalizacao_aluno`
--
ALTER TABLE `geolocalizacao_aluno`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `gestor`
--
ALTER TABLE `gestor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `gestor_lotacao`
--
ALTER TABLE `gestor_lotacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `habilidades_bncc`
--
ALTER TABLE `habilidades_bncc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1910;

--
-- AUTO_INCREMENT de tabela `historico_escolar`
--
ALTER TABLE `historico_escolar`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `indicador_nutricional`
--
ALTER TABLE `indicador_nutricional`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `justificativa`
--
ALTER TABLE `justificativa`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `log_sistema`
--
ALTER TABLE `log_sistema`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT de tabela `motorista`
--
ALTER TABLE `motorista`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `motorista_veiculo`
--
ALTER TABLE `motorista_veiculo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `nota`
--
ALTER TABLE `nota`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `nutricionista`
--
ALTER TABLE `nutricionista`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `nutricionista_lotacao`
--
ALTER TABLE `nutricionista_lotacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `observacao_desempenho`
--
ALTER TABLE `observacao_desempenho`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `pacote`
--
ALTER TABLE `pacote`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pacote_escola`
--
ALTER TABLE `pacote_escola`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pacote_escola_item`
--
ALTER TABLE `pacote_escola_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `pacote_item`
--
ALTER TABLE `pacote_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `parecer_tecnico`
--
ALTER TABLE `parecer_tecnico`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `pedido_cesta`
--
ALTER TABLE `pedido_cesta`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `pedido_item`
--
ALTER TABLE `pedido_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `pessoa`
--
ALTER TABLE `pessoa`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de tabela `plano_aula`
--
ALTER TABLE `plano_aula`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `ponto_rota`
--
ALTER TABLE `ponto_rota`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produto`
--
ALTER TABLE `produto`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1045;

--
-- AUTO_INCREMENT de tabela `professor`
--
ALTER TABLE `professor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `professor_lotacao`
--
ALTER TABLE `professor_lotacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `programa`
--
ALTER TABLE `programa`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `relatorio`
--
ALTER TABLE `relatorio`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `role_permissao`
--
ALTER TABLE `role_permissao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `rota`
--
ALTER TABLE `rota`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `serie`
--
ALTER TABLE `serie`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `substituicao_alimento`
--
ALTER TABLE `substituicao_alimento`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `turma`
--
ALTER TABLE `turma`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `turma_professor`
--
ALTER TABLE `turma_professor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de tabela `validacao`
--
ALTER TABLE `validacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `veiculo`
--
ALTER TABLE `veiculo`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `viagem`
--
ALTER TABLE `viagem`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `viagem_aluno`
--
ALTER TABLE `viagem_aluno`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

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

--
-- Restrições para tabelas `aluno_responsavel`
--
ALTER TABLE `aluno_responsavel`
  ADD CONSTRAINT `aluno_responsavel_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aluno_responsavel_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aluno_responsavel_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `aluno_rota`
--
ALTER TABLE `aluno_rota`
  ADD CONSTRAINT `aluno_rota_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aluno_rota_ibfk_2` FOREIGN KEY (`rota_id`) REFERENCES `rota` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aluno_rota_ibfk_3` FOREIGN KEY (`ponto_embarque_id`) REFERENCES `ponto_rota` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `aluno_rota_ibfk_4` FOREIGN KEY (`ponto_desembarque_id`) REFERENCES `ponto_rota` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `aluno_rota_ibfk_5` FOREIGN KEY (`geolocalizacao_id`) REFERENCES `geolocalizacao_aluno` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `aluno_rota_ibfk_6` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `aluno_turma`
--
ALTER TABLE `aluno_turma`
  ADD CONSTRAINT `aluno_turma_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `aluno_turma_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `aluno_turma_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD CONSTRAINT `avaliacao_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `avaliacao_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `avaliacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `boletim`
--
ALTER TABLE `boletim`
  ADD CONSTRAINT `boletim_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `boletim_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `boletim_ibfk_3` FOREIGN KEY (`gerado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `boletim_item`
--
ALTER TABLE `boletim_item`
  ADD CONSTRAINT `boletim_item_ibfk_1` FOREIGN KEY (`boletim_id`) REFERENCES `boletim` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `boletim_item_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`);

--
-- Restrições para tabelas `calendar_categories`
--
ALTER TABLE `calendar_categories`
  ADD CONSTRAINT `calendar_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `calendar_events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `calendar_event_participants`
--
ALTER TABLE `calendar_event_participants`
  ADD CONSTRAINT `calendar_event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendar_event_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `calendar_event_recurrence`
--
ALTER TABLE `calendar_event_recurrence`
  ADD CONSTRAINT `calendar_event_recurrence_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `calendar_notifications`
--
ALTER TABLE `calendar_notifications`
  ADD CONSTRAINT `calendar_notifications_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendar_notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `calendar_settings`
--
ALTER TABLE `calendar_settings`
  ADD CONSTRAINT `calendar_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cardapio`
--
ALTER TABLE `cardapio`
  ADD CONSTRAINT `cardapio_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `cardapio_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `cardapio_ibfk_3` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cardapio_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `cardapio_item`
--
ALTER TABLE `cardapio_item`
  ADD CONSTRAINT `cardapio_item_ibfk_1` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapio` (`id`),
  ADD CONSTRAINT `cardapio_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`),
  ADD CONSTRAINT `cardapio_item_ibfk_3` FOREIGN KEY (`semana_id`) REFERENCES `cardapio_semana` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `cardapio_semana`
--
ALTER TABLE `cardapio_semana`
  ADD CONSTRAINT `cardapio_semana_ibfk_1` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapio` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comunicado`
--
ALTER TABLE `comunicado`
  ADD CONSTRAINT `comunicado_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `comunicado_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `comunicado_ibfk_3` FOREIGN KEY (`enviado_por`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `comunicado_ibfk_4` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `comunicado_ibfk_5` FOREIGN KEY (`lido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `comunicado_resposta`
--
ALTER TABLE `comunicado_resposta`
  ADD CONSTRAINT `comunicado_resposta_ibfk_1` FOREIGN KEY (`comunicado_id`) REFERENCES `comunicado` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comunicado_resposta_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`);

--
-- Restrições para tabelas `consumo_diario`
--
ALTER TABLE `consumo_diario`
  ADD CONSTRAINT `consumo_diario_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `consumo_diario_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consumo_diario_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consumo_diario_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `consumo_item`
--
ALTER TABLE `consumo_item`
  ADD CONSTRAINT `consumo_item_ibfk_1` FOREIGN KEY (`consumo_diario_id`) REFERENCES `consumo_diario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consumo_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `custo_merenda`
--
ALTER TABLE `custo_merenda`
  ADD CONSTRAINT `custo_merenda_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `custo_merenda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `custo_merenda_ibfk_3` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `custo_merenda_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `desperdicio`
--
ALTER TABLE `desperdicio`
  ADD CONSTRAINT `desperdicio_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `desperdicio_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `desperdicio_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `distrito_localidade`
--
ALTER TABLE `distrito_localidade`
  ADD CONSTRAINT `distrito_localidade_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `distrito_ponto_central`
--
ALTER TABLE `distrito_ponto_central`
  ADD CONSTRAINT `distrito_ponto_central_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `distrito_ponto_central_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `entrega`
--
ALTER TABLE `entrega`
  ADD CONSTRAINT `entrega_ibfk_1` FOREIGN KEY (`pedido_cesta_id`) REFERENCES `pedido_cesta` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `entrega_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `entrega_ibfk_3` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `entrega_ibfk_4` FOREIGN KEY (`recebido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `entrega_ibfk_5` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `entrega_item`
--
ALTER TABLE `entrega_item`
  ADD CONSTRAINT `entrega_item_ibfk_1` FOREIGN KEY (`entrega_id`) REFERENCES `entrega` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entrega_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `escola`
--
ALTER TABLE `escola`
  ADD CONSTRAINT `escola_ibfk_2` FOREIGN KEY (`diretor_id`) REFERENCES `gestor` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `escola_backup`
--
ALTER TABLE `escola_backup`
  ADD CONSTRAINT `escola_backup_ibfk_1` FOREIGN KEY (`excluido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `escola_backup_ibfk_2` FOREIGN KEY (`revertido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `escola_programa`
--
ALTER TABLE `escola_programa`
  ADD CONSTRAINT `fk_escola_programa_escola` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_escola_programa_programa` FOREIGN KEY (`programa_id`) REFERENCES `programa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `estoque_central`
--
ALTER TABLE `estoque_central`
  ADD CONSTRAINT `estoque_central_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`),
  ADD CONSTRAINT `estoque_central_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `estoque_central_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `estoque_central_ibfk_4` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `fornecedor`
--
ALTER TABLE `fornecedor`
  ADD CONSTRAINT `fornecedor_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `frequencia`
--
ALTER TABLE `frequencia`
  ADD CONSTRAINT `frequencia_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `frequencia_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `frequencia_ibfk_3` FOREIGN KEY (`justificativa_id`) REFERENCES `justificativa` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `frequencia_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `frequencia_ibfk_5` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `frequencia_ibfk_6` FOREIGN KEY (`validado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `funcionario`
--
ALTER TABLE `funcionario`
  ADD CONSTRAINT `funcionario_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `funcionario_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `funcionario_lotacao`
--
ALTER TABLE `funcionario_lotacao`
  ADD CONSTRAINT `funcionario_lotacao_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`),
  ADD CONSTRAINT `funcionario_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `funcionario_lotacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `geolocalizacao_aluno`
--
ALTER TABLE `geolocalizacao_aluno`
  ADD CONSTRAINT `geolocalizacao_aluno_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `geolocalizacao_aluno_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `gestor`
--
ALTER TABLE `gestor`
  ADD CONSTRAINT `gestor_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `gestor_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `gestor_lotacao`
--
ALTER TABLE `gestor_lotacao`
  ADD CONSTRAINT `gestor_lotacao_ibfk_1` FOREIGN KEY (`gestor_id`) REFERENCES `gestor` (`id`),
  ADD CONSTRAINT `gestor_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `gestor_lotacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `historico_escolar`
--
ALTER TABLE `historico_escolar`
  ADD CONSTRAINT `historico_escolar_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `historico_escolar_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `historico_escolar_ibfk_3` FOREIGN KEY (`gerado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `indicador_nutricional`
--
ALTER TABLE `indicador_nutricional`
  ADD CONSTRAINT `indicador_nutricional_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicador_nutricional_ibfk_2` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapio` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `indicador_nutricional_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `justificativa`
--
ALTER TABLE `justificativa`
  ADD CONSTRAINT `justificativa_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `justificativa_ibfk_2` FOREIGN KEY (`enviado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `justificativa_ibfk_3` FOREIGN KEY (`analisado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `motorista`
--
ALTER TABLE `motorista`
  ADD CONSTRAINT `motorista_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `motorista_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `motorista_veiculo`
--
ALTER TABLE `motorista_veiculo`
  ADD CONSTRAINT `motorista_veiculo_ibfk_1` FOREIGN KEY (`motorista_id`) REFERENCES `motorista` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `motorista_veiculo_ibfk_2` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  ADD CONSTRAINT `movimentacao_estoque_ibfk_1` FOREIGN KEY (`realizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimentacao_estoque_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `nota`
--
ALTER TABLE `nota`
  ADD CONSTRAINT `nota_ibfk_1` FOREIGN KEY (`avaliacao_id`) REFERENCES `avaliacao` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `nota_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `nota_ibfk_3` FOREIGN KEY (`lancado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `nota_ibfk_4` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `nota_ibfk_5` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `nota_ibfk_6` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `nota_ibfk_7` FOREIGN KEY (`validado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `nutricionista`
--
ALTER TABLE `nutricionista`
  ADD CONSTRAINT `nutricionista_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `nutricionista_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `nutricionista_lotacao`
--
ALTER TABLE `nutricionista_lotacao`
  ADD CONSTRAINT `nutricionista_lotacao_ibfk_1` FOREIGN KEY (`nutricionista_id`) REFERENCES `nutricionista` (`id`),
  ADD CONSTRAINT `nutricionista_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `nutricionista_lotacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `observacao_desempenho`
--
ALTER TABLE `observacao_desempenho`
  ADD CONSTRAINT `observacao_desempenho_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `observacao_desempenho_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `observacao_desempenho_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `observacao_desempenho_ibfk_4` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  ADD CONSTRAINT `observacao_desempenho_ibfk_5` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pacote`
--
ALTER TABLE `pacote`
  ADD CONSTRAINT `pacote_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `pacote_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `pacote_escola`
--
ALTER TABLE `pacote_escola`
  ADD CONSTRAINT `pacote_escola_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`);

--
-- Restrições para tabelas `pacote_escola_item`
--
ALTER TABLE `pacote_escola_item`
  ADD CONSTRAINT `fk_pacote_escola_item_estoque` FOREIGN KEY (`estoque_central_id`) REFERENCES `estoque_central` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pacote_escola_item_ibfk_1` FOREIGN KEY (`pacote_id`) REFERENCES `pacote_escola` (`id`),
  ADD CONSTRAINT `pacote_escola_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `pacote_item`
--
ALTER TABLE `pacote_item`
  ADD CONSTRAINT `pacote_item_ibfk_1` FOREIGN KEY (`pacote_id`) REFERENCES `pacote` (`id`),
  ADD CONSTRAINT `pacote_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `parecer_tecnico`
--
ALTER TABLE `parecer_tecnico`
  ADD CONSTRAINT `parecer_tecnico_ibfk_1` FOREIGN KEY (`nutricionista_id`) REFERENCES `nutricionista` (`id`),
  ADD CONSTRAINT `parecer_tecnico_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `parecer_tecnico_ibfk_3` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapio` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pedido_cesta`
--
ALTER TABLE `pedido_cesta`
  ADD CONSTRAINT `pedido_cesta_ibfk_1` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `pedido_cesta_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `pedido_cesta_ibfk_3` FOREIGN KEY (`nutricionista_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedido_cesta_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pedido_item`
--
ALTER TABLE `pedido_item`
  ADD CONSTRAINT `pedido_item_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedido_cesta` (`id`),
  ADD CONSTRAINT `pedido_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `plano_aula`
--
ALTER TABLE `plano_aula`
  ADD CONSTRAINT `plano_aula_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `plano_aula_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`),
  ADD CONSTRAINT `plano_aula_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  ADD CONSTRAINT `plano_aula_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `plano_aula_ibfk_5` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `ponto_rota`
--
ALTER TABLE `ponto_rota`
  ADD CONSTRAINT `ponto_rota_ibfk_1` FOREIGN KEY (`rota_id`) REFERENCES `rota` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produto`
--
ALTER TABLE `produto`
  ADD CONSTRAINT `produto_ibfk_1` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `produto_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `professor`
--
ALTER TABLE `professor`
  ADD CONSTRAINT `professor_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `professor_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `professor_lotacao`
--
ALTER TABLE `professor_lotacao`
  ADD CONSTRAINT `professor_lotacao_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  ADD CONSTRAINT `professor_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`);

--
-- Restrições para tabelas `relatorio`
--
ALTER TABLE `relatorio`
  ADD CONSTRAINT `relatorio_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `relatorio_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `relatorio_ibfk_3` FOREIGN KEY (`gerado_por`) REFERENCES `usuario` (`id`);

--
-- Restrições para tabelas `rota`
--
ALTER TABLE `rota`
  ADD CONSTRAINT `rota_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `rota_ibfk_2` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `rota_ibfk_3` FOREIGN KEY (`motorista_id`) REFERENCES `motorista` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `rota_ibfk_4` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `serie`
--
ALTER TABLE `serie`
  ADD CONSTRAINT `serie_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `substituicao_alimento`
--
ALTER TABLE `substituicao_alimento`
  ADD CONSTRAINT `substituicao_alimento_ibfk_1` FOREIGN KEY (`nutricionista_id`) REFERENCES `nutricionista` (`id`),
  ADD CONSTRAINT `substituicao_alimento_ibfk_2` FOREIGN KEY (`produto_original_id`) REFERENCES `produto` (`id`),
  ADD CONSTRAINT `substituicao_alimento_ibfk_3` FOREIGN KEY (`produto_substituto_id`) REFERENCES `produto` (`id`),
  ADD CONSTRAINT `substituicao_alimento_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `turma`
--
ALTER TABLE `turma`
  ADD CONSTRAINT `turma_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `turma_ibfk_2` FOREIGN KEY (`coordenador_id`) REFERENCES `professor` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `turma_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `turma_ibfk_4` FOREIGN KEY (`serie_id`) REFERENCES `serie` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `turma_professor`
--
ALTER TABLE `turma_professor`
  ADD CONSTRAINT `turma_professor_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `turma_professor_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  ADD CONSTRAINT `turma_professor_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`),
  ADD CONSTRAINT `turma_professor_ibfk_4` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `validacao`
--
ALTER TABLE `validacao`
  ADD CONSTRAINT `validacao_ibfk_1` FOREIGN KEY (`validado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `veiculo`
--
ALTER TABLE `veiculo`
  ADD CONSTRAINT `veiculo_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `viagem`
--
ALTER TABLE `viagem`
  ADD CONSTRAINT `viagem_ibfk_1` FOREIGN KEY (`rota_id`) REFERENCES `rota` (`id`),
  ADD CONSTRAINT `viagem_ibfk_2` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `viagem_ibfk_3` FOREIGN KEY (`motorista_id`) REFERENCES `motorista` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `viagem_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `viagem_aluno`
--
ALTER TABLE `viagem_aluno`
  ADD CONSTRAINT `viagem_aluno_ibfk_1` FOREIGN KEY (`viagem_id`) REFERENCES `viagem` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `viagem_aluno_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `viagem_aluno_ibfk_3` FOREIGN KEY (`ponto_embarque_id`) REFERENCES `ponto_rota` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `viagem_aluno_ibfk_4` FOREIGN KEY (`ponto_desembarque_id`) REFERENCES `ponto_rota` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
