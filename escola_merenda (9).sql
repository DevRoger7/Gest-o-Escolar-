-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 01/12/2025 às 15:23
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
(1, 10, '2024001', NULL, NULL, NULL, '2025-11-29', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-11-29 23:05:38', '2025-11-29 23:05:38', NULL, 1),
(2, 28, 'MAT-000028', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(3, 29, 'MAT-000029', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(4, 30, 'MAT-000030', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(5, 31, 'MAT-000031', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(6, 32, 'MAT-000032', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(7, 33, 'MAT-000033', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(8, 34, 'MAT-000034', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(9, 35, 'MAT-000035', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(10, 36, 'MAT-000036', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(11, 37, 'MAT-000037', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(12, 38, 'MAT-000038', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(13, 39, 'MAT-000039', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(14, 40, 'MAT-000040', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(15, 41, 'MAT-000041', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(16, 42, 'MAT-000042', NULL, NULL, 17, '2025-11-30', 'MATRICULADO', NULL, 'Brasileira', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1);

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
(1, 2, 1, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(2, 3, 1, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(3, 4, 1, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(4, 5, 1, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(5, 6, 1, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(8, 7, 2, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(9, 8, 2, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(10, 9, 2, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(11, 10, 2, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(12, 11, 2, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(15, 12, 3, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(16, 13, 3, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(17, 14, 3, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(18, 15, 3, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(19, 16, 3, '2025-11-30', NULL, 'MATRICULADO', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL);

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `cardapio_item`
--

CREATE TABLE `cardapio_item` (
  `id` bigint(20) NOT NULL,
  `cardapio_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `consumo_diario`
--

CREATE TABLE `consumo_diario` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `turma_id` bigint(20) DEFAULT NULL,
  `data` date NOT NULL,
  `turno` enum('MANHA','TARDE','NOITE') DEFAULT NULL,
  `total_alunos` int(11) DEFAULT 0,
  `alunos_atendidos` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `desperdicio`
--

CREATE TABLE `desperdicio` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `data` date NOT NULL,
  `turno` enum('MANHA','TARDE','NOITE') DEFAULT NULL,
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
  `obs` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atualizado_por` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `escola`
--

INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `numero`, `complemento`, `bairro`, `municipio`, `estado`, `cep`, `telefone`, `telefone_secundario`, `email`, `site`, `cnpj`, `diretor_id`, `qtd_salas`, `obs`, `ativo`, `criado_em`, `atualizado_em`, `atualizado_por`) VALUES
(3, NULL, 'escolatal', 'Rua rua rua', NULL, NULL, NULL, 'maranguape', 'CE', '22222-222', '(85) 9999-9922', NULL, 'escola@gmail.com', NULL, NULL, NULL, 6, 'aaa', 1, '2025-09-23 17:46:30', '2025-11-29 22:13:59', NULL),
(4, NULL, 'escolatalas', 'qr3e', NULL, NULL, NULL, 'itapebusi', 'CE', '12323-123', '(85) 9999-9277', NULL, 'weeescola@gmail.com', NULL, NULL, NULL, 44, 'aaa', 1, '2025-09-23 17:57:45', '2025-11-29 22:13:59', NULL),
(14, '3434343', 'yudi', 'Rua Joaninha Vieira', NULL, NULL, NULL, 'Maranguape', 'CE', '61943-290', '(85) 9999-9922', NULL, 'yudipro859@gmail.com', NULL, NULL, NULL, 12, 'adasdwaddwdad', 1, '2025-09-24 19:05:44', '2025-11-29 22:13:59', NULL),
(15, '', 'teste do erro com o gestor consertado', 'Rua Joaninha Vieira', NULL, NULL, NULL, 'Maranguape', 'CE', '61943-290', '(85) 9999-9277', NULL, 'assa@gmail.com', NULL, NULL, NULL, 55, 'teste pra ver se o gestor ta funcionando', 1, '2025-09-24 19:06:24', '2025-11-29 22:13:59', NULL),
(16, '243434', 'escola do Raimundo ', 'Rua Joaninha Vieira', NULL, NULL, NULL, 'Maranguape', 'CE', '61943-290', '(85) 9999-9933', NULL, 'yudipro859@gmail.com', NULL, NULL, NULL, 8, '', 1, '2025-09-25 17:23:57', '2025-11-29 22:13:59', NULL),
(17, '12345678', 'Escola Municipal de Teste - SIGAE', 'Rua das Flores, 123 - Centro', NULL, NULL, NULL, 'Maranguape', 'CE', '61940-000', '(85) 3333-4444', NULL, 'escola.teste@sigae.com', NULL, NULL, NULL, 20, 'Escola criada para testes do sistema SIGAE', 1, '2025-12-01 02:34:56', '2025-12-01 02:34:56', NULL);

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
(4, 11, 'Diretor', NULL, NULL, NULL, '2025-12-01 02:40:03', '2025-12-01 02:40:03', NULL, 1);

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
(10, 1, 15, '2025-09-24', NULL, 1, 'Vice-Diretor', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(12, 2, 16, '2025-10-01', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(13, 2, 14, '2025-10-01', NULL, 1, 'SecretÃ¡rio Escolar', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(14, 2, 14, '2025-10-01', NULL, 1, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(15, 2, 14, '2025-10-01', NULL, 1, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(16, 1, 14, '2025-10-01', NULL, 1, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(17, 2, 14, '2025-10-01', NULL, 1, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(18, 1, 3, '2025-10-03', NULL, 1, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(19, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(20, 1, 4, '2025-10-13', NULL, 1, 'Vice-Diretor', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(21, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(22, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(23, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(24, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico', NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:01', NULL),
(25, 3, 17, '2025-11-30', NULL, 1, 'Diretor', NULL, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL),
(26, 3, 17, '2025-11-30', NULL, 1, 'Diretor', NULL, '2025-12-01 02:40:03', '2025-12-01 02:40:03', NULL);

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
(4, 8, '6c6c8de7bfb84ef7135960521ffe712c4ab8ac051e674bcb6b0a60507d2a21f2', 'gestor.teste@sigae.com', '2025-12-02 04:30:25', 0, '2025-12-01 03:30:25');

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
  `tipo` enum('ALUNO','PROFESSOR','GESTOR','FUNCIONARIO','RESPONSAVEL','OUTRO') DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pessoa`
--

INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `whatsapp`, `telefone_secundario`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, `tipo`, `foto_url`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `ativo`) VALUES
(1, '11111111111', 'Roger', NULL, 'M', 'cavalcanterogeer@gmail.com', '85981835778', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1),
(2, '11970867302', 'Francisco lavosier Silva Nascimento', '2001-04-20', NULL, 'slavosier298@gmail.com', '(85) 98948-2053', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1),
(3, '12345678901', 'Francisco', '1999-04-21', NULL, 'tambaqui123@gmail.com', '(85) 98948-2053', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1),
(4, '12321321333', 'yudi', '2000-03-13', NULL, 'assa@gmail.com', '(85) 9999-9922', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GESTOR', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1),
(5, '13232332322', 'raimundo nonato', '1997-03-13', NULL, 'raimundo@gmail.com', '(85) 9999-9233', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1),
(6, '12312312300', 'cabra mac', '2001-09-10', NULL, 'cabramacho@gmail.com', '(85) 3333-3333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'FUNCIONARIO', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:13:59', NULL, 1),
(7, '12112112112', 'vascaino', NULL, 'M', 'vascainoprofessor@gmail.com', '85985858585', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:14:02', NULL, 1),
(8, '33333333333', 'raparigueiro', NULL, 'M', 'raparigueiro@gmail.com', '85933445566', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-11-29 22:13:59', '2025-11-29 22:14:02', NULL, 1),
(10, '98765432100', 'João Silva', '2010-05-15', 'M', 'joao@email.com', '(85) 99999-9999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-11-29 23:05:38', '2025-11-29 23:05:38', NULL, 1),
(11, '12345678900', 'João Silva (Gestor Teste)', '1980-01-15', 'M', 'gestor.teste@sigae.com', '85999999999', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GESTOR', NULL, NULL, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL, 1),
(28, '90000000001', 'Ana Silva Santos', '2017-03-15', 'F', 'ana.silva.teste@sigae.com', '85990000001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(29, '90000000002', 'Bruno Oliveira Costa', '2017-05-20', 'M', 'bruno.oliveira.teste@sigae.com', '85990000002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(30, '90000000003', 'Carla Mendes Lima', '2017-07-10', 'F', 'carla.mendes.teste@sigae.com', '85990000003', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(31, '90000000004', 'Daniel Souza Alves', '2017-09-25', 'M', 'daniel.souza.teste@sigae.com', '85990000004', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(32, '90000000005', 'Eduarda Ferreira Rocha', '2017-11-30', 'F', 'eduarda.ferreira.teste@sigae.com', '85990000005', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(33, '90000000006', 'Felipe Gomes Pereira', '2016-02-14', 'M', 'felipe.gomes.teste@sigae.com', '85990000006', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(34, '90000000007', 'Gabriela Martins Dias', '2016-04-18', 'F', 'gabriela.martins.teste@sigae.com', '85990000007', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(35, '90000000008', 'Henrique Barbosa Ramos', '2016-06-22', 'M', 'henrique.barbosa.teste@sigae.com', '85990000008', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(36, '90000000009', 'Isabela Nunes Cardoso', '2016-08-28', 'F', 'isabela.nunes.teste@sigae.com', '85990000009', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(37, '90000000010', 'João Pedro Teixeira', '2016-10-12', 'M', 'joao.pedro.teste@sigae.com', '85990000010', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(38, '90000000011', 'Larissa Araújo Freitas', '2015-01-08', 'F', 'larissa.araujo.teste@sigae.com', '85990000011', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(39, '90000000012', 'Marcos Vinicius Lopes', '2015-03-16', 'M', 'marcos.vinicius.teste@sigae.com', '85990000012', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(40, '90000000013', 'Natália Correia Monteiro', '2015-05-24', 'F', 'natalia.correia.teste@sigae.com', '85990000013', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(41, '90000000014', 'Otávio Ribeiro Campos', '2015-07-30', 'M', 'otavio.ribeiro.teste@sigae.com', '85990000014', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(42, '90000000015', 'Paula Cristina Moreira', '2015-09-05', 'F', 'paula.cristina.teste@sigae.com', '85990000015', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ALUNO', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(43, '80000000001', 'Maria Santos (Professora Português)', '1985-05-10', 'F', 'maria.santos.teste@sigae.com', '85980000001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(44, '80000000002', 'José Carlos (Professor Matemática)', '1982-08-20', 'M', 'jose.carlos.teste@sigae.com', '85980000002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1),
(45, '80000000003', 'Patrícia Lima (Professora História)', '1987-12-05', 'F', 'patricia.lima.teste@sigae.com', '85980000003', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PROFESSOR', NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, 1);

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
  `data_aula` date NOT NULL,
  `bimestre` int(11) DEFAULT NULL,
  `status` enum('RASCUNHO','APROVADO','APLICADO','CANCELADO') DEFAULT 'RASCUNHO',
  `aprovado_por` bigint(20) DEFAULT NULL,
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_por` bigint(20) DEFAULT NULL,
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
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professor`
--

INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `especializacao`, `registro_profissional`, `observacoes`, `criado_em`, `atualizado_em`, `criado_por`, `data_admissao`, `ativo`) VALUES
(2, 2, '7777777', 'MATEMATICA', NULL, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:02', NULL, NULL, 1),
(3, 8, '3344567', 'HISTORIA', NULL, NULL, NULL, '2025-11-29 22:14:01', '2025-11-29 22:14:02', NULL, NULL, 1),
(4, 43, 'PROF-000043', 'Licenciatura em Letras', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, '2025-11-30', 1),
(5, 44, 'PROF-000044', 'Licenciatura em Matemática', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, '2025-11-30', 1),
(6, 45, 'PROF-000045', 'Licenciatura em História', NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL, '2025-11-30', 1);

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
(1, 4, 17, '2025-11-30', NULL, 20, 'Professora de Língua Portuguesa', '2025-12-01 02:40:04'),
(2, 5, 17, '2025-11-30', NULL, 20, 'Professor de Matemática', '2025-12-01 02:40:04'),
(3, 6, 17, '2025-11-30', NULL, 10, 'Professora de História', '2025-12-01 02:40:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorio`
--

CREATE TABLE `relatorio` (
  `id` bigint(20) NOT NULL,
  `tipo` enum('FINANCEIRO','PEDAGOGICO','MERENDA','FREQUENCIA','DESEMPENHO','ESTOQUE','OUTROS') NOT NULL,
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
  `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL') NOT NULL,
  `permissao` varchar(100) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(1, '1º Ano', '1ANO', 'ENSINO_FUNDAMENTAL', 1, 6, 7, 'Primeiro ano do Ensino Fundamental', 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL),
(2, '2º Ano', '2ANO', 'ENSINO_FUNDAMENTAL', 2, 7, 8, 'Segundo ano do Ensino Fundamental', 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL),
(3, '3º Ano', '3ANO', 'ENSINO_FUNDAMENTAL', 3, 8, 9, 'Terceiro ano do Ensino Fundamental', 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL);

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
  `turno` enum('MANHA','TARDE','NOITE') DEFAULT NULL,
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
(3, 17, 3, 2025, '3º Ano', 'A', 'MANHA', 30, NULL, NULL, NULL, 1, '2025-12-01 02:34:57', '2025-12-01 02:34:57', NULL);

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
(1, 1, 4, 1, '2025-11-30', NULL, 'REGULAR', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(2, 2, 4, 1, '2025-11-30', NULL, 'REGULAR', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(3, 3, 4, 1, '2025-11-30', NULL, 'REGULAR', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(4, 1, 5, 2, '2025-11-30', NULL, 'REGULAR', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(5, 2, 5, 2, '2025-11-30', NULL, 'REGULAR', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(6, 3, 5, 2, '2025-11-30', NULL, 'REGULAR', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(7, 1, 6, 3, '2025-11-30', NULL, 'REGULAR', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(8, 2, 6, 3, '2025-11-30', NULL, 'REGULAR', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(9, 3, 6, 3, '2025-11-30', NULL, 'REGULAR', NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL') DEFAULT NULL,
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
(2, 2, 'lavosier', '$2y$10$UL3XYcPWQMPqkouQRbzSMu/bJw5kItNqZmiY6MkjADEYDdApAoLnW', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-09-22 19:17:23', '2025-11-29 22:14:02', NULL),
(3, 3, 'francisco', '$2y$10$RqVIvLDU2B3aMH8D5DCUeubFZ0dVMgvfNgzbhCqWr6REia5O/69gy', 'ADM', 1, 0, NULL, NULL, 0, NULL, '2025-11-29 22:21:14', NULL, '2025-09-22 19:42:40', '2025-11-29 22:21:14', NULL),
(4, 4, 'yudi', '$2y$10$3WUQGohoZf8tiE0UvSC43uxF4kQCrjERBG8NmfyMQZ8FgMHN0vKnS', 'GESTAO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-09-23 17:56:04', '2025-11-29 22:14:02', NULL),
(5, 5, 'raimundo', '$2y$10$yAoiZi1i3HOosehIwKCg5OMua7tXjlpVIlm5SJAuIIfZ/tcoNbup.', 'GESTAO', 1, 0, NULL, NULL, 0, NULL, '2025-09-25 17:16:41', NULL, '2025-09-23 17:58:50', '2025-11-29 22:14:02', NULL),
(6, 6, 'cabra', '$2y$10$KjDXdWEqd.98YRW6bHErve.JEjPU6hx0Nb1QjJd4DvjcSRZJMlyoG', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, '2025-09-29 17:00:01', NULL, '2025-09-29 16:56:48', '2025-11-29 22:14:02', NULL),
(7, 10, 'joao.silva', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-11-29 23:05:38', '2025-11-29 23:05:38', NULL),
(8, 11, 'gestor.teste', '$2y$10$8IVuV10pnI3aXOxgyHZ.se6usDjY.g7yPegrJTz7tsUplUs0X2as.', 'GESTAO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 02:34:57', '2025-12-01 03:04:40', NULL),
(10, 43, 'maria', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(11, 44, 'josé', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, '2025-12-01 04:37:17', NULL, '2025-12-01 02:40:04', '2025-12-01 04:37:17', NULL),
(12, 45, 'patrícia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PROFESSOR', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 02:40:04', '2025-12-01 02:40:04', NULL),
(13, 28, 'ana.silva.santos', '$2y$10$5Tcc269FHgJLZYq4PeqUZe.QC9L0xuQS5FZ2d/.Ph6WGi3.8zKtu6', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:14:38', NULL),
(14, 29, 'bruno.oliveira.costa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL),
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
(27, 42, 'paula.cristina.moreira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ALUNO', 1, 0, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-01 04:11:43', '2025-12-01 04:11:43', NULL);

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
  ADD KEY `idx_cardapio_escola_mes_ano` (`escola_id`,`mes`,`ano`);

--
-- Índices de tabela `cardapio_item`
--
ALTER TABLE `cardapio_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cardapio_id` (`cardapio_id`),
  ADD KEY `produto_id` (`produto_id`);

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
  ADD KEY `idx_escola_estado` (`estado`);

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
-- Índices de tabela `pacote_item`
--
ALTER TABLE `pacote_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pacote_id` (`pacote_id`),
  ADD KEY `produto_id` (`produto_id`);

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
  ADD KEY `idx_pedido_data_criacao` (`data_criacao`);

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
-- Índices de tabela `serie`
--
ALTER TABLE `serie`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `idx_serie_nivel` (`nivel_ensino`),
  ADD KEY `idx_serie_ativo` (`ativo`);

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
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aluno`
--
ALTER TABLE `aluno`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `aluno_turma`
--
ALTER TABLE `aluno_turma`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `boletim`
--
ALTER TABLE `boletim`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `boletim_item`
--
ALTER TABLE `boletim_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cardapio_item`
--
ALTER TABLE `cardapio_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comunicado`
--
ALTER TABLE `comunicado`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comunicado_resposta`
--
ALTER TABLE `comunicado_resposta`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracao`
--
ALTER TABLE `configuracao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `consumo_diario`
--
ALTER TABLE `consumo_diario`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `consumo_item`
--
ALTER TABLE `consumo_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `custo_merenda`
--
ALTER TABLE `custo_merenda`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `desperdicio`
--
ALTER TABLE `desperdicio`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `disciplina`
--
ALTER TABLE `disciplina`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `entrega`
--
ALTER TABLE `entrega`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `entrega_item`
--
ALTER TABLE `entrega_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `escola`
--
ALTER TABLE `escola`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `estoque_central`
--
ALTER TABLE `estoque_central`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fornecedor`
--
ALTER TABLE `fornecedor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `frequencia`
--
ALTER TABLE `frequencia`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT de tabela `gestor`
--
ALTER TABLE `gestor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `gestor_lotacao`
--
ALTER TABLE `gestor_lotacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `historico_escolar`
--
ALTER TABLE `historico_escolar`
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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `observacao_desempenho`
--
ALTER TABLE `observacao_desempenho`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pacote`
--
ALTER TABLE `pacote`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pacote_item`
--
ALTER TABLE `pacote_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `pedido_cesta`
--
ALTER TABLE `pedido_cesta`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedido_item`
--
ALTER TABLE `pedido_item`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pessoa`
--
ALTER TABLE `pessoa`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de tabela `plano_aula`
--
ALTER TABLE `plano_aula`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produto`
--
ALTER TABLE `produto`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `professor`
--
ALTER TABLE `professor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `professor_lotacao`
--
ALTER TABLE `professor_lotacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `relatorio`
--
ALTER TABLE `relatorio`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `role_permissao`
--
ALTER TABLE `role_permissao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `serie`
--
ALTER TABLE `serie`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `turma`
--
ALTER TABLE `turma`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `turma_professor`
--
ALTER TABLE `turma_professor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `validacao`
--
ALTER TABLE `validacao`
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
  ADD CONSTRAINT `cardapio_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

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
-- Restrições para tabelas `justificativa`
--
ALTER TABLE `justificativa`
  ADD CONSTRAINT `justificativa_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `justificativa_ibfk_2` FOREIGN KEY (`enviado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `justificativa_ibfk_3` FOREIGN KEY (`analisado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

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
-- Restrições para tabelas `pacote_item`
--
ALTER TABLE `pacote_item`
  ADD CONSTRAINT `pacote_item_ibfk_1` FOREIGN KEY (`pacote_id`) REFERENCES `pacote` (`id`),
  ADD CONSTRAINT `pacote_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

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
-- Restrições para tabelas `serie`
--
ALTER TABLE `serie`
  ADD CONSTRAINT `serie_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
