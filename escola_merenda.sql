-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24/10/2025 às 21:22
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
  `data_matricula` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT NULL
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
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacao`
--

CREATE TABLE `avaliacao` (
  `id` bigint(20) NOT NULL,
  `turma_id` bigint(20) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `tipo` enum('TRABALHO','PROVA','ATIVIDADE') DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL
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
(1, 'Reuniões', '#3B82F6', 'users', 'Reuniões gerais e administrativas', 1, '2025-10-24 16:32:08', 1),
(2, 'Avaliações', '#EF4444', 'book-open', 'Provas e avaliações dos alunos', 1, '2025-10-24 16:32:08', 1),
(3, 'Feriados', '#10B981', 'calendar', 'Feriados nacionais e regionais', 1, '2025-10-24 16:32:08', 1),
(4, 'Eventos', '#F59E0B', 'star', 'Eventos especiais da escola', 1, '2025-10-24 16:32:08', 1),
(5, 'Aulas', '#8B5CF6', 'graduation-cap', 'Aulas e atividades pedagógicas', 1, '2025-10-24 16:32:08', 1),
(6, 'Treinamentos', '#EC4899', 'book', 'Treinamentos e capacitações', 1, '2025-10-24 16:32:08', 1),
(7, 'Reunião de Pais', '#14B8A6', 'users', 'Reuniões com pais e responsáveis', 1, '2025-10-24 16:32:08', 1);

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
(1, 'Reunião Pedagógica', 'Reunião mensal com professores para planejamento pedagógico', '2025-01-15 14:00:00', '2025-01-15 16:00:00', 0, '#3B82F6', 'meeting', 14, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
(2, 'Prova de Matemática - 6º Ano', 'Avaliação bimestral de matemática para o 6º ano', '2025-01-20 08:00:00', '2025-01-20 10:00:00', 0, '#EF4444', 'exam', 14, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
(3, 'Feriado - São Sebastião', 'Feriado municipal em homenagem ao padroeiro', '2025-01-20 00:00:00', '2025-01-20 23:59:59', 1, '#10B981', 'holiday', NULL, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
(4, 'Reunião de Pais - 1º Bimestre', 'Reunião com pais para entrega de boletins do 1º bimestre', '2025-01-25 19:00:00', '2025-01-25 21:00:00', 0, '#14B8A6', 'meeting_parents', 14, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
(5, 'Capacitação de Professores', 'Treinamento sobre novas metodologias de ensino', '2025-01-30 09:00:00', '2025-01-30 17:00:00', 0, '#EC4899', 'training', 14, 1, '2025-10-24 16:32:09', '2025-10-24 16:32:09', 1),
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
  `criado_por` bigint(20) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
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
  `enviado_por` bigint(20) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `disciplina`
--

CREATE TABLE `disciplina` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `carga_horaria` int(11) DEFAULT NULL
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
  `municipio` varchar(100) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `qtd_salas` int(11) DEFAULT NULL,
  `obs` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `escola`
--

INSERT INTO `escola` (`id`, `codigo`, `nome`, `endereco`, `municipio`, `cep`, `telefone`, `email`, `qtd_salas`, `obs`, `criado_em`) VALUES
(3, NULL, 'escolatal', 'Rua rua rua', 'maranguape', '22222-222', '(85) 9999-9922', 'escola@gmail.com', 6, 'aaa', '2025-09-23 17:46:30'),
(4, NULL, 'escolatalas', 'qr3e', 'itapebusi', '12323-123', '(85) 9999-9277', 'weeescola@gmail.com', 44, 'aaa', '2025-09-23 17:57:45'),
(14, '3434343', 'yudi', 'Rua Joaninha Vieira', 'Maranguape', '61943-290', '(85) 9999-9922', 'yudipro859@gmail.com', 12, 'adasdwaddwdad', '2025-09-24 19:05:44'),
(15, '', 'teste do erro com o gestor consertado', 'Rua Joaninha Vieira', 'Maranguape', '61943-290', '(85) 9999-9277', 'assa@gmail.com', 55, 'teste pra ver se o gestor ta funcionando', '2025-09-24 19:06:24'),
(16, '243434', 'escola do Raimundo ', 'Rua Joaninha Vieira', 'Maranguape', '61943-290', '(85) 9999-9933', 'yudipro859@gmail.com', 8, '', '2025-09-25 17:23:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque_central`
--

CREATE TABLE `estoque_central` (
  `id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(12,3) DEFAULT NULL,
  `lote` varchar(100) DEFAULT NULL,
  `validade` date DEFAULT NULL,
  `atualizado_em` date DEFAULT NULL
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
  `justificativa_id` bigint(20) DEFAULT NULL,
  `registrado_por` bigint(20) DEFAULT NULL,
  `registrado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `gestor`
--

CREATE TABLE `gestor` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `gestor`
--

INSERT INTO `gestor` (`id`, `pessoa_id`, `cargo`, `ativo`) VALUES
(1, 4, 'gestor', 1),
(2, 5, 'gestor', 1);

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
  `tipo` enum('Diretor','Vice-Diretor','Coordenador Pedagógico','Secretário Escolar') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `gestor_lotacao`
--

INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`) VALUES
(9, 1, 14, '2025-09-24', NULL, 1, 'Diretor'),
(10, 1, 15, '2025-09-24', NULL, 1, 'Vice-Diretor'),
(12, 2, 16, '2025-10-01', NULL, 1, 'Coordenador Pedagógico'),
(13, 2, 14, '2025-10-01', NULL, 1, 'Secretário Escolar'),
(14, 2, 14, '2025-10-01', NULL, 1, NULL),
(15, 2, 14, '2025-10-01', NULL, 1, NULL),
(16, 1, 14, '2025-10-01', NULL, 1, NULL),
(17, 2, 14, '2025-10-01', NULL, 1, NULL),
(18, 1, 3, '2025-10-03', NULL, 1, NULL),
(19, 2, 4, '2025-10-13', NULL, 1, 'Coordenador Pedagógico'),
(20, 1, 4, '2025-10-13', NULL, 1, 'Vice-Diretor'),
(21, 2, 4, '2025-10-13', NULL, 1, 'Coordenador Pedagógico'),
(22, 2, 4, '2025-10-13', NULL, 1, 'Coordenador Pedagógico'),
(23, 2, 4, '2025-10-13', NULL, 1, 'Coordenador Pedagógico'),
(24, 2, 4, '2025-10-13', NULL, 1, 'Coordenador Pedagógico');

-- --------------------------------------------------------

--
-- Estrutura para tabela `justificativa`
--

CREATE TABLE `justificativa` (
  `id` bigint(20) NOT NULL,
  `aluno_id` bigint(20) NOT NULL,
  `enviado_por` bigint(20) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `arquivo_url` varchar(500) DEFAULT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `analisado_por` bigint(20) DEFAULT NULL,
  `analise` text DEFAULT NULL
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
  `aluno_id` bigint(20) NOT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `lancado_por` bigint(20) DEFAULT NULL,
  `lancado_em` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Estrutura para tabela `pedido_cesta`
--

CREATE TABLE `pedido_cesta` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `mes` int(11) DEFAULT NULL,
  `nutricionista_id` bigint(20) DEFAULT NULL,
  `status` enum('RASCUHO','ENVIADO','APROVADO','REJEITADO','ENVIADO_A_ESCOLA') DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_envio` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `data_aprovacao` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `aprovado_por` bigint(20) DEFAULT NULL
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
  `tipo` enum('ALUNO','PROFESSOR','GESTOR','FUNCIONARIO','RESPONSAVEL','OUTRO') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pessoa`
--

INSERT INTO `pessoa` (`id`, `cpf`, `nome`, `data_nascimento`, `sexo`, `email`, `telefone`, `tipo`) VALUES
(1, '11111111111', 'Roger', NULL, 'M', 'cavalcanterogeer@gmail.com', '85981835778', 'ALUNO'),
(2, '11970867302', 'Francisco lavosier Silva Nascimento', '2001-04-20', NULL, 'slavosier298@gmail.com', '(85) 98948-2053', 'FUNCIONARIO'),
(3, '12345678901', 'Francisco', '1999-04-21', NULL, 'tambaqui123@gmail.com', '(85) 98948-2053', 'FUNCIONARIO'),
(4, '12321321333', 'yudi', '2000-03-13', NULL, 'assa@gmail.com', '(85) 9999-9922', 'GESTOR'),
(5, '13232332322', 'raimundo nonato', '1997-03-13', NULL, 'raimundo@gmail.com', '(85) 9999-9233', 'FUNCIONARIO'),
(6, '12312312300', 'cabra mac', '2001-09-10', NULL, 'cabramacho@gmail.com', '(85) 3333-3333', 'FUNCIONARIO'),
(7, '12112112112', 'vascaino', '0000-00-00', 'M', 'vascainoprofessor@gmail.com', '85985858585', 'PROFESSOR'),
(8, '33333333333', 'raparigueiro', '0303-00-03', 'M', 'raparigueiro@gmail.com', '85933445566', 'PROFESSOR');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto`
--

CREATE TABLE `produto` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(100) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `unidade_medida` varchar(20) DEFAULT NULL,
  `estoque_minimo` decimal(10,0) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `obs` varchar(255) DEFAULT NULL,
  `localizacao` varchar(255) DEFAULT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `quantidade` int(10) DEFAULT NULL
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
  `data_admissao` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professor`
--

INSERT INTO `professor` (`id`, `pessoa_id`, `matricula`, `formacao`, `data_admissao`, `ativo`) VALUES
(2, 2, '7777777', 'MATEMATICA', '0000-00-01', 1),
(3, 8, '3344567', 'HISTORIA', '0000-00-00', 1);

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `turma`
--

CREATE TABLE `turma` (
  `id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `ano_letivo` int(11) DEFAULT NULL,
  `serie` varchar(20) DEFAULT NULL,
  `letra` varchar(3) DEFAULT NULL,
  `turno` enum('MANHA','TARDE','NOITE') DEFAULT NULL,
  `capacidade` int(11) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `regime` enum('REGULAR','SUBSTITUTO') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id` bigint(20) NOT NULL,
  `pessoa_id` bigint(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA') DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id`, `pessoa_id`, `username`, `senha_hash`, `role`, `ativo`, `ultimo_login`, `created_at`) VALUES
(1, 1, 'Roger', '1', 'ADM', 1, NULL, '2025-09-19 16:35:40'),
(2, 2, 'lavosier', '$2y$10$UL3XYcPWQMPqkouQRbzSMu/bJw5kItNqZmiY6MkjADEYDdApAoLnW', 'PROFESSOR', 1, NULL, '2025-09-22 19:17:23'),
(3, 3, 'francisco', '$2y$10$RqVIvLDU2B3aMH8D5DCUeubFZ0dVMgvfNgzbhCqWr6REia5O/69gy', 'ADM', 1, '2025-10-24 13:20:33', '2025-09-22 19:42:40'),
(4, 4, 'yudi', '$2y$10$3WUQGohoZf8tiE0UvSC43uxF4kQCrjERBG8NmfyMQZ8FgMHN0vKnS', 'GESTAO', 1, NULL, '2025-09-23 17:56:04'),
(5, 5, 'raimundo', '$2y$10$yAoiZi1i3HOosehIwKCg5OMua7tXjlpVIlm5SJAuIIfZ/tcoNbup.', 'GESTAO', 1, '2025-09-25 17:16:41', '2025-09-23 17:58:50'),
(6, 6, 'cabra', '$2y$10$KjDXdWEqd.98YRW6bHErve.JEjPU6hx0Nb1QjJd4DvjcSRZJMlyoG', 'PROFESSOR', 1, '2025-09-29 17:00:01', '2025-09-29 16:56:48');

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
  ADD KEY `responsavel_id` (`responsavel_id`);

--
-- Índices de tabela `aluno_turma`
--
ALTER TABLE `aluno_turma`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Índices de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`);

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
  ADD KEY `criado_por` (`criado_por`);

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
  ADD KEY `enviado_por` (`enviado_por`);

--
-- Índices de tabela `disciplina`
--
ALTER TABLE `disciplina`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `escola`
--
ALTER TABLE `escola`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `estoque_central`
--
ALTER TABLE `estoque_central`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `frequencia`
--
ALTER TABLE `frequencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `justificativa_id` (`justificativa_id`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- Índices de tabela `gestor`
--
ALTER TABLE `gestor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pessoa_id` (`pessoa_id`);

--
-- Índices de tabela `gestor_lotacao`
--
ALTER TABLE `gestor_lotacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gestor_id` (`gestor_id`),
  ADD KEY `escola_id` (`escola_id`);

--
-- Índices de tabela `justificativa`
--
ALTER TABLE `justificativa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `enviado_por` (`enviado_por`),
  ADD KEY `analisado_por` (`analisado_por`);

--
-- Índices de tabela `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `realizado_por` (`realizado_por`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `nota`
--
ALTER TABLE `nota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avaliacao_id` (`avaliacao_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `lancado_por` (`lancado_por`);

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
-- Índices de tabela `pedido_cesta`
--
ALTER TABLE `pedido_cesta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aprovado_por` (`aprovado_por`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `nutricionista_id` (`nutricionista_id`);

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
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `produto`
--
ALTER TABLE `produto`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD KEY `pessoa_id` (`pessoa_id`);

--
-- Índices de tabela `professor_lotacao`
--
ALTER TABLE `professor_lotacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `escola_id` (`escola_id`);

--
-- Índices de tabela `turma`
--
ALTER TABLE `turma`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`);

--
-- Índices de tabela `turma_professor`
--
ALTER TABLE `turma_professor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `disciplina_id` (`disciplina_id`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pessoa_id` (`pessoa_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aluno`
--
ALTER TABLE `aluno`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `aluno_turma`
--
ALTER TABLE `aluno_turma`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
-- AUTO_INCREMENT de tabela `disciplina`
--
ALTER TABLE `disciplina`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `escola`
--
ALTER TABLE `escola`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `estoque_central`
--
ALTER TABLE `estoque_central`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `frequencia`
--
ALTER TABLE `frequencia`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `gestor`
--
ALTER TABLE `gestor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `gestor_lotacao`
--
ALTER TABLE `gestor_lotacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `justificativa`
--
ALTER TABLE `justificativa`
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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `produto`
--
ALTER TABLE `produto`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `professor`
--
ALTER TABLE `professor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `professor_lotacao`
--
ALTER TABLE `professor_lotacao`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `turma`
--
ALTER TABLE `turma`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `turma_professor`
--
ALTER TABLE `turma_professor`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aluno`
--
ALTER TABLE `aluno`
  ADD CONSTRAINT `aluno_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `aluno_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`);

--
-- Restrições para tabelas `aluno_turma`
--
ALTER TABLE `aluno_turma`
  ADD CONSTRAINT `aluno_turma_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `aluno_turma_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`);

--
-- Restrições para tabelas `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD CONSTRAINT `avaliacao_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`);

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
  ADD CONSTRAINT `cardapio_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`);

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
  ADD CONSTRAINT `comunicado_ibfk_3` FOREIGN KEY (`enviado_por`) REFERENCES `usuario` (`id`);

--
-- Restrições para tabelas `estoque_central`
--
ALTER TABLE `estoque_central`
  ADD CONSTRAINT `estoque_central_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `frequencia`
--
ALTER TABLE `frequencia`
  ADD CONSTRAINT `frequencia_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `frequencia_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `frequencia_ibfk_3` FOREIGN KEY (`justificativa_id`) REFERENCES `justificativa` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `frequencia_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `gestor`
--
ALTER TABLE `gestor`
  ADD CONSTRAINT `gestor_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`);

--
-- Restrições para tabelas `gestor_lotacao`
--
ALTER TABLE `gestor_lotacao`
  ADD CONSTRAINT `gestor_lotacao_ibfk_1` FOREIGN KEY (`gestor_id`) REFERENCES `gestor` (`id`),
  ADD CONSTRAINT `gestor_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`);

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
  ADD CONSTRAINT `nota_ibfk_3` FOREIGN KEY (`lancado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `pedido_cesta_ibfk_3` FOREIGN KEY (`nutricionista_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pedido_item`
--
ALTER TABLE `pedido_item`
  ADD CONSTRAINT `pedido_item_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedido_cesta` (`id`),
  ADD CONSTRAINT `pedido_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `professor`
--
ALTER TABLE `professor`
  ADD CONSTRAINT `professor_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`);

--
-- Restrições para tabelas `professor_lotacao`
--
ALTER TABLE `professor_lotacao`
  ADD CONSTRAINT `professor_lotacao_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  ADD CONSTRAINT `professor_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`);

--
-- Restrições para tabelas `turma`
--
ALTER TABLE `turma`
  ADD CONSTRAINT `turma_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`);

--
-- Restrições para tabelas `turma_professor`
--
ALTER TABLE `turma_professor`
  ADD CONSTRAINT `turma_professor_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `turma_professor_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  ADD CONSTRAINT `turma_professor_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`);

--
-- Restrições para tabelas `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
