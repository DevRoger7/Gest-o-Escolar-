-- ============================================================
-- SIGAE - Sistema de Gestão e Alimentação Escolar
-- BANCO DE DADOS COMPLETO E MELHORADO
-- Versão: 2.0 - Dezembro 2024
-- ============================================================
-- 
-- Este arquivo contém:
-- 1. Banco base original (escola_merenda (8).sql)
-- 2. Todas as melhorias (escola_merenda_melhorado.sql)
-- 3. Todas as novas tabelas SIGAE (escola_merenda_completo_sigae.sql)
--
-- INSTRUÇÕES:
-- Execute este arquivo em um banco NOVO ou após fazer BACKUP
-- ============================================================

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
  `tipo` enum('Diretor','Vice-Diretor','Coordenador PedagÃ³gico','SecretÃ¡rio Escolar') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `gestor_lotacao`
--

INSERT INTO `gestor_lotacao` (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`) VALUES
(9, 1, 14, '2025-09-24', NULL, 1, 'Diretor'),
(10, 1, 15, '2025-09-24', NULL, 1, 'Vice-Diretor'),
(12, 2, 16, '2025-10-01', NULL, 1, 'Coordenador PedagÃ³gico'),
(13, 2, 14, '2025-10-01', NULL, 1, 'SecretÃ¡rio Escolar'),
(14, 2, 14, '2025-10-01', NULL, 1, NULL),
(15, 2, 14, '2025-10-01', NULL, 1, NULL),
(16, 1, 14, '2025-10-01', NULL, 1, NULL),
(17, 2, 14, '2025-10-01', NULL, 1, NULL),
(18, 1, 3, '2025-10-03', NULL, 1, NULL),
(19, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico'),
(20, 1, 4, '2025-10-13', NULL, 1, 'Vice-Diretor'),
(21, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico'),
(22, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico'),
(23, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico'),
(24, 2, 4, '2025-10-13', NULL, 1, 'Coordenador PedagÃ³gico');

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
-- Estrutura para tabela `role_permissao`
--

CREATE TABLE `role_permissao` (
  `id` bigint(20) NOT NULL,
  `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA') NOT NULL,
  `permissao` varchar(100) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(3, 3, 'francisco', '$2y$10$RqVIvLDU2B3aMH8D5DCUeubFZ0dVMgvfNgzbhCqWr6REia5O/69gy', 'ADM', 1, '2025-11-11 19:39:25', '2025-09-22 19:42:40'),
(4, 4, 'yudi', '$2y$10$3WUQGohoZf8tiE0UvSC43uxF4kQCrjERBG8NmfyMQZ8FgMHN0vKnS', 'GESTAO', 1, NULL, '2025-09-23 17:56:04'),
(5, 5, 'raimundo', '$2y$10$yAoiZi1i3HOosehIwKCg5OMua7tXjlpVIlm5SJAuIIfZ/tcoNbup.', 'GESTAO', 1, '2025-09-25 17:16:41', '2025-09-23 17:58:50'),
(6, 6, 'cabra', '$2y$10$KjDXdWEqd.98YRW6bHErve.JEjPU6hx0Nb1QjJd4DvjcSRZJMlyoG', 'PROFESSOR', 1, '2025-09-29 17:00:01', '2025-09-29 16:56:48');

--
-- Ãndices para tabelas despejadas
--

--
-- Ãndices de tabela `aluno`
--
ALTER TABLE `aluno`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD KEY `pessoa_id` (`pessoa_id`),
  ADD KEY `responsavel_id` (`responsavel_id`);

--
-- Ãndices de tabela `aluno_turma`
--
ALTER TABLE `aluno_turma`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Ãndices de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`);

--
-- Ãndices de tabela `calendar_categories`
--
ALTER TABLE `calendar_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Ãndices de tabela `calendar_events`
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
-- Ãndices de tabela `calendar_event_participants`
--
ALTER TABLE `calendar_event_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participant` (`event_id`,`user_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Ãndices de tabela `calendar_event_recurrence`
--
ALTER TABLE `calendar_event_recurrence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Ãndices de tabela `calendar_notifications`
--
ALTER TABLE `calendar_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Ãndices de tabela `calendar_settings`
--
ALTER TABLE `calendar_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`),
  ADD KEY `user_id` (`user_id`);

--
-- Ãndices de tabela `cardapio`
--
ALTER TABLE `cardapio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Ãndices de tabela `cardapio_item`
--
ALTER TABLE `cardapio_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cardapio_id` (`cardapio_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Ãndices de tabela `comunicado`
--
ALTER TABLE `comunicado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `enviado_por` (`enviado_por`);

--
-- Ãndices de tabela `configuracao`
--
ALTER TABLE `configuracao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave_unique` (`chave`);

--
-- Ãndices de tabela `disciplina`
--
ALTER TABLE `disciplina`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Ãndices de tabela `escola`
--
ALTER TABLE `escola`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Ãndices de tabela `estoque_central`
--
ALTER TABLE `estoque_central`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Ãndices de tabela `frequencia`
--
ALTER TABLE `frequencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `justificativa_id` (`justificativa_id`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- Ãndices de tabela `gestor`
--
ALTER TABLE `gestor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pessoa_id` (`pessoa_id`);

--
-- Ãndices de tabela `gestor_lotacao`
--
ALTER TABLE `gestor_lotacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gestor_id` (`gestor_id`),
  ADD KEY `escola_id` (`escola_id`);

--
-- Ãndices de tabela `justificativa`
--
ALTER TABLE `justificativa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `enviado_por` (`enviado_por`),
  ADD KEY `analisado_por` (`analisado_por`);

--
-- Ãndices de tabela `log_sistema`
--
ALTER TABLE `log_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `tipo` (`tipo`),
  ADD KEY `criado_em` (`criado_em`);

--
-- Ãndices de tabela `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `realizado_por` (`realizado_por`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Ãndices de tabela `nota`
--
ALTER TABLE `nota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avaliacao_id` (`avaliacao_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `lancado_por` (`lancado_por`);

--
-- Ãndices de tabela `pacote`
--
ALTER TABLE `pacote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criado_por` (`criado_por`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Ãndices de tabela `pacote_item`
--
ALTER TABLE `pacote_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pacote_id` (`pacote_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Ãndices de tabela `pedido_cesta`
--
ALTER TABLE `pedido_cesta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aprovado_por` (`aprovado_por`),
  ADD KEY `escola_id` (`escola_id`),
  ADD KEY `nutricionista_id` (`nutricionista_id`);

--
-- Ãndices de tabela `pedido_item`
--
ALTER TABLE `pedido_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Ãndices de tabela `pessoa`
--
ALTER TABLE `pessoa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Ãndices de tabela `produto`
--
ALTER TABLE `produto`
  ADD PRIMARY KEY (`id`);

--
-- Ãndices de tabela `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD KEY `pessoa_id` (`pessoa_id`);

--
-- Ãndices de tabela `professor_lotacao`
--
ALTER TABLE `professor_lotacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `escola_id` (`escola_id`);

--
-- Ãndices de tabela `role_permissao`
--
ALTER TABLE `role_permissao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permissao_unique` (`role`,`permissao`);

--
-- Ãndices de tabela `turma`
--
ALTER TABLE `turma`
  ADD PRIMARY KEY (`id`),
  ADD KEY `escola_id` (`escola_id`);

--
-- Ãndices de tabela `turma_professor`
--
ALTER TABLE `turma_professor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `professor_id` (`professor_id`),
  ADD KEY `disciplina_id` (`disciplina_id`);

--
-- Ãndices de tabela `usuario`
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
-- AUTO_INCREMENT de tabela `configuracao`
--
ALTER TABLE `configuracao`
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
-- AUTO_INCREMENT de tabela `role_permissao`
--
ALTER TABLE `role_permissao`
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
-- RestriÃ§Ãµes para tabelas despejadas
--

--
-- RestriÃ§Ãµes para tabelas `aluno`
--
ALTER TABLE `aluno`
  ADD CONSTRAINT `aluno_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `aluno_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`);

--
-- RestriÃ§Ãµes para tabelas `aluno_turma`
--
ALTER TABLE `aluno_turma`
  ADD CONSTRAINT `aluno_turma_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `aluno_turma_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`);

--
-- RestriÃ§Ãµes para tabelas `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD CONSTRAINT `avaliacao_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`);

--
-- RestriÃ§Ãµes para tabelas `calendar_categories`
--
ALTER TABLE `calendar_categories`
  ADD CONSTRAINT `calendar_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `calendar_events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `calendar_event_participants`
--
ALTER TABLE `calendar_event_participants`
  ADD CONSTRAINT `calendar_event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendar_event_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `calendar_event_recurrence`
--
ALTER TABLE `calendar_event_recurrence`
  ADD CONSTRAINT `calendar_event_recurrence_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `calendar_notifications`
--
ALTER TABLE `calendar_notifications`
  ADD CONSTRAINT `calendar_notifications_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendar_notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `calendar_settings`
--
ALTER TABLE `calendar_settings`
  ADD CONSTRAINT `calendar_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `cardapio`
--
ALTER TABLE `cardapio`
  ADD CONSTRAINT `cardapio_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `cardapio_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`);

--
-- RestriÃ§Ãµes para tabelas `cardapio_item`
--
ALTER TABLE `cardapio_item`
  ADD CONSTRAINT `cardapio_item_ibfk_1` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapio` (`id`),
  ADD CONSTRAINT `cardapio_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- RestriÃ§Ãµes para tabelas `comunicado`
--
ALTER TABLE `comunicado`
  ADD CONSTRAINT `comunicado_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `comunicado_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `comunicado_ibfk_3` FOREIGN KEY (`enviado_por`) REFERENCES `usuario` (`id`);

--
-- RestriÃ§Ãµes para tabelas `estoque_central`
--
ALTER TABLE `estoque_central`
  ADD CONSTRAINT `estoque_central_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- RestriÃ§Ãµes para tabelas `frequencia`
--
ALTER TABLE `frequencia`
  ADD CONSTRAINT `frequencia_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `frequencia_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `frequencia_ibfk_3` FOREIGN KEY (`justificativa_id`) REFERENCES `justificativa` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `frequencia_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- RestriÃ§Ãµes para tabelas `gestor`
--
ALTER TABLE `gestor`
  ADD CONSTRAINT `gestor_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`);

--
-- RestriÃ§Ãµes para tabelas `gestor_lotacao`
--
ALTER TABLE `gestor_lotacao`
  ADD CONSTRAINT `gestor_lotacao_ibfk_1` FOREIGN KEY (`gestor_id`) REFERENCES `gestor` (`id`),
  ADD CONSTRAINT `gestor_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`);

--
-- RestriÃ§Ãµes para tabelas `justificativa`
--
ALTER TABLE `justificativa`
  ADD CONSTRAINT `justificativa_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `justificativa_ibfk_2` FOREIGN KEY (`enviado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `justificativa_ibfk_3` FOREIGN KEY (`analisado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- RestriÃ§Ãµes para tabelas `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  ADD CONSTRAINT `movimentacao_estoque_ibfk_1` FOREIGN KEY (`realizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimentacao_estoque_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- RestriÃ§Ãµes para tabelas `nota`
--
ALTER TABLE `nota`
  ADD CONSTRAINT `nota_ibfk_1` FOREIGN KEY (`avaliacao_id`) REFERENCES `avaliacao` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `nota_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  ADD CONSTRAINT `nota_ibfk_3` FOREIGN KEY (`lancado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- RestriÃ§Ãµes para tabelas `pacote`
--
ALTER TABLE `pacote`
  ADD CONSTRAINT `pacote_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `pessoa` (`id`),
  ADD CONSTRAINT `pacote_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- RestriÃ§Ãµes para tabelas `pacote_item`
--
ALTER TABLE `pacote_item`
  ADD CONSTRAINT `pacote_item_ibfk_1` FOREIGN KEY (`pacote_id`) REFERENCES `pacote` (`id`),
  ADD CONSTRAINT `pacote_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- RestriÃ§Ãµes para tabelas `pedido_cesta`
--
ALTER TABLE `pedido_cesta`
  ADD CONSTRAINT `pedido_cesta_ibfk_1` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `pedido_cesta_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  ADD CONSTRAINT `pedido_cesta_ibfk_3` FOREIGN KEY (`nutricionista_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

--
-- RestriÃ§Ãµes para tabelas `pedido_item`
--
ALTER TABLE `pedido_item`
  ADD CONSTRAINT `pedido_item_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedido_cesta` (`id`),
  ADD CONSTRAINT `pedido_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`);

--
-- RestriÃ§Ãµes para tabelas `professor`
--
ALTER TABLE `professor`
  ADD CONSTRAINT `professor_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`);

--
-- RestriÃ§Ãµes para tabelas `professor_lotacao`
--
ALTER TABLE `professor_lotacao`
  ADD CONSTRAINT `professor_lotacao_ibfk_1` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  ADD CONSTRAINT `professor_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`);

--
-- RestriÃ§Ãµes para tabelas `turma`
--
ALTER TABLE `turma`
  ADD CONSTRAINT `turma_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`);

--
-- RestriÃ§Ãµes para tabelas `turma_professor`
--
ALTER TABLE `turma_professor`
  ADD CONSTRAINT `turma_professor_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `turma_professor_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  ADD CONSTRAINT `turma_professor_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`);

--
-- RestriÃ§Ãµes para tabelas `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`);
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================
-- 1. ADICIONAR RESPONSAVEL NO ENUM DE USUARIO E ROLE_PERMISSAO
-- ============================================================

ALTER TABLE `usuario` 
MODIFY COLUMN `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL') DEFAULT NULL;

ALTER TABLE `role_permissao` 
MODIFY COLUMN `role` enum('ADM','GESTAO','PROFESSOR','ALUNO','NUTRICIONISTA','ADM_MERENDA','RESPONSAVEL') NOT NULL;

-- ============================================================
-- 2. MELHORIAS NA TABELA PESSOA
-- ============================================================

ALTER TABLE `pessoa`
ADD COLUMN `endereco` text DEFAULT NULL AFTER `telefone`,
ADD COLUMN `numero` varchar(20) DEFAULT NULL AFTER `endereco`,
ADD COLUMN `complemento` varchar(100) DEFAULT NULL AFTER `numero`,
ADD COLUMN `bairro` varchar(100) DEFAULT NULL AFTER `complemento`,
ADD COLUMN `cidade` varchar(100) DEFAULT NULL AFTER `bairro`,
ADD COLUMN `estado` char(2) DEFAULT NULL AFTER `cidade`,
ADD COLUMN `cep` varchar(10) DEFAULT NULL AFTER `estado`,
ADD COLUMN `whatsapp` varchar(30) DEFAULT NULL AFTER `telefone`,
ADD COLUMN `telefone_secundario` varchar(30) DEFAULT NULL AFTER `whatsapp`,
ADD COLUMN `foto_url` varchar(500) DEFAULT NULL AFTER `tipo`,
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `foto_url`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `observacoes`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `criado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`,
ADD COLUMN `ativo` tinyint(1) DEFAULT 1 AFTER `criado_por`;

-- Ãndices para pessoa
ALTER TABLE `pessoa`
ADD KEY `idx_pessoa_tipo` (`tipo`),
ADD KEY `idx_pessoa_ativo` (`ativo`),
ADD KEY `idx_pessoa_cidade` (`cidade`),
ADD KEY `idx_pessoa_estado` (`estado`);

-- ============================================================
-- 3. MELHORIAS NA TABELA ALUNO
-- ============================================================

ALTER TABLE `aluno`
ADD COLUMN `escola_id` bigint(20) DEFAULT NULL AFTER `responsavel_id`,
ADD COLUMN `situacao` enum('MATRICULADO','TRANSFERIDO','EVADIDO','CONCLUIDO','CANCELADO') DEFAULT 'MATRICULADO' AFTER `data_matricula`,
ADD COLUMN `data_nascimento` date DEFAULT NULL AFTER `situacao`,
ADD COLUMN `nacionalidade` varchar(50) DEFAULT 'Brasileira' AFTER `data_nascimento`,
ADD COLUMN `naturalidade` varchar(100) DEFAULT NULL AFTER `nacionalidade`,
ADD COLUMN `necessidades_especiais` text DEFAULT NULL AFTER `naturalidade`,
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `necessidades_especiais`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `observacoes`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `criado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key para escola
ALTER TABLE `aluno`
ADD KEY `escola_id` (`escola_id`),
ADD CONSTRAINT `aluno_ibfk_3` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL;

-- Ãndices para aluno
ALTER TABLE `aluno`
ADD KEY `idx_aluno_situacao` (`situacao`),
ADD KEY `idx_aluno_ativo` (`ativo`),
ADD KEY `idx_aluno_escola` (`escola_id`);

-- ============================================================
-- 4. MELHORIAS NA TABELA ESCOLA
-- ============================================================

ALTER TABLE `escola`
ADD COLUMN `numero` varchar(20) DEFAULT NULL AFTER `endereco`,
ADD COLUMN `complemento` varchar(100) DEFAULT NULL AFTER `numero`,
ADD COLUMN `bairro` varchar(100) DEFAULT NULL AFTER `complemento`,
ADD COLUMN `estado` char(2) DEFAULT 'CE' AFTER `municipio`,
ADD COLUMN `cnpj` varchar(18) DEFAULT NULL AFTER `email`,
ADD COLUMN `diretor_id` bigint(20) DEFAULT NULL AFTER `cnpj`,
ADD COLUMN `telefone_secundario` varchar(30) DEFAULT NULL AFTER `telefone`,
ADD COLUMN `site` varchar(255) DEFAULT NULL AFTER `email`,
ADD COLUMN `ativo` tinyint(1) DEFAULT 1 AFTER `obs`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key para diretor
ALTER TABLE `escola`
ADD KEY `diretor_id` (`diretor_id`),
ADD CONSTRAINT `escola_ibfk_2` FOREIGN KEY (`diretor_id`) REFERENCES `gestor` (`id`) ON DELETE SET NULL;

-- Ãndices para escola
ALTER TABLE `escola`
ADD KEY `idx_escola_ativo` (`ativo`),
ADD KEY `idx_escola_municipio` (`municipio`),
ADD KEY `idx_escola_estado` (`estado`),
ADD UNIQUE KEY `cnpj` (`cnpj`);

-- ============================================================
-- 5. MELHORIAS NA TABELA AVALIACAO
-- ============================================================

ALTER TABLE `avaliacao`
ADD COLUMN `disciplina_id` bigint(20) DEFAULT NULL AFTER `turma_id`,
ADD COLUMN `descricao` text DEFAULT NULL AFTER `titulo`,
ADD COLUMN `criado_por` bigint(20) DEFAULT NULL AFTER `peso`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `criado_por`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `ativo` tinyint(1) DEFAULT 1 AFTER `atualizado_em`;

-- Foreign keys
ALTER TABLE `avaliacao`
ADD KEY `disciplina_id` (`disciplina_id`),
ADD KEY `criado_por` (`criado_por`),
ADD CONSTRAINT `avaliacao_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `avaliacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `avaliacao`
ADD KEY `idx_avaliacao_data` (`data`),
ADD KEY `idx_avaliacao_tipo` (`tipo`),
ADD KEY `idx_avaliacao_ativo` (`ativo`);

-- ============================================================
-- 6. MELHORIAS NA TABELA NOTA
-- ============================================================

ALTER TABLE `nota`
ADD COLUMN `disciplina_id` bigint(20) DEFAULT NULL AFTER `avaliacao_id`,
ADD COLUMN `turma_id` bigint(20) DEFAULT NULL AFTER `disciplina_id`,
ADD COLUMN `bimestre` int(11) DEFAULT NULL AFTER `nota`,
ADD COLUMN `recuperacao` tinyint(1) DEFAULT 0 AFTER `bimestre`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `lancado_em`,
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign keys
ALTER TABLE `nota`
ADD KEY `disciplina_id` (`disciplina_id`),
ADD KEY `turma_id` (`turma_id`),
ADD KEY `atualizado_por` (`atualizado_por`),
ADD CONSTRAINT `nota_ibfk_4` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `nota_ibfk_5` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `nota_ibfk_6` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `nota`
ADD KEY `idx_nota_bimestre` (`bimestre`),
ADD KEY `idx_nota_recuperacao` (`recuperacao`),
ADD KEY `idx_nota_aluno_disciplina` (`aluno_id`, `disciplina_id`);

-- ============================================================
-- 7. MELHORIAS NA TABELA FREQUENCIA
-- ============================================================

ALTER TABLE `frequencia`
ADD COLUMN `observacao` text DEFAULT NULL AFTER `presenca`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `registrado_em`,
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `frequencia`
ADD KEY `atualizado_por` (`atualizado_por`),
ADD CONSTRAINT `frequencia_ibfk_5` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndice composto para melhor performance
ALTER TABLE `frequencia`
ADD UNIQUE KEY `unique_frequencia_aluno_data` (`aluno_id`, `turma_id`, `data`),
ADD KEY `idx_frequencia_data` (`data`),
ADD KEY `idx_frequencia_presenca` (`presenca`);

-- ============================================================
-- 8. MELHORIAS NA TABELA COMUNICADO
-- ============================================================

ALTER TABLE `comunicado`
ADD COLUMN `escola_id` bigint(20) DEFAULT NULL AFTER `aluno_id`,
ADD COLUMN `tipo` enum('GERAL','TURMA','ALUNO','URGENTE') DEFAULT 'GERAL' AFTER `enviado_por`,
ADD COLUMN `prioridade` enum('BAIXA','NORMAL','ALTA','URGENTE') DEFAULT 'NORMAL' AFTER `tipo`,
ADD COLUMN `lido` tinyint(1) DEFAULT 0 AFTER `mensagem`,
ADD COLUMN `data_leitura` timestamp NULL DEFAULT NULL AFTER `lido`,
ADD COLUMN `lido_por` bigint(20) DEFAULT NULL AFTER `data_leitura`,
ADD COLUMN `anexo_url` varchar(500) DEFAULT NULL AFTER `lido_por`,
ADD COLUMN `ativo` tinyint(1) DEFAULT 1 AFTER `anexo_url`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`;

-- Foreign keys
ALTER TABLE `comunicado`
ADD KEY `escola_id` (`escola_id`),
ADD KEY `lido_por` (`lido_por`),
ADD CONSTRAINT `comunicado_ibfk_4` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `comunicado_ibfk_5` FOREIGN KEY (`lido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `comunicado`
ADD KEY `idx_comunicado_tipo` (`tipo`),
ADD KEY `idx_comunicado_prioridade` (`prioridade`),
ADD KEY `idx_comunicado_lido` (`lido`),
ADD KEY `idx_comunicado_ativo` (`ativo`),
ADD KEY `idx_comunicado_data` (`criado_em`);

-- ============================================================
-- 9. MELHORIAS NA TABELA CARDAPIO
-- ============================================================

ALTER TABLE `cardapio`
ADD COLUMN `status` enum('RASCUNHO','APROVADO','REJEITADO','PUBLICADO') DEFAULT 'RASCUNHO' AFTER `ano`,
ADD COLUMN `aprovado_por` bigint(20) DEFAULT NULL AFTER `status`,
ADD COLUMN `data_aprovacao` timestamp NULL DEFAULT NULL AFTER `aprovado_por`,
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `data_aprovacao`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign keys
ALTER TABLE `cardapio`
ADD KEY `aprovado_por` (`aprovado_por`),
ADD KEY `atualizado_por` (`atualizado_por`),
ADD CONSTRAINT `cardapio_ibfk_3` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `cardapio_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `cardapio`
ADD KEY `idx_cardapio_status` (`status`),
ADD KEY `idx_cardapio_mes_ano` (`mes`, `ano`),
ADD KEY `idx_cardapio_escola_mes_ano` (`escola_id`, `mes`, `ano`);

-- ============================================================
-- 10. MELHORIAS NA TABELA PEDIDO_CESTA
-- ============================================================

-- Corrigir timestamps invÃ¡lidos
ALTER TABLE `pedido_cesta`
MODIFY COLUMN `data_envio` timestamp NULL DEFAULT NULL,
MODIFY COLUMN `data_aprovacao` timestamp NULL DEFAULT NULL;

-- Adicionar campos
ALTER TABLE `pedido_cesta`
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `aprovado_por`,
ADD COLUMN `motivo_rejeicao` text DEFAULT NULL AFTER `observacoes`,
ADD COLUMN `data_entrega` date DEFAULT NULL AFTER `data_aprovacao`,
ADD COLUMN `entregue` tinyint(1) DEFAULT 0 AFTER `data_entrega`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `data_criacao`,
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `pedido_cesta`
ADD KEY `atualizado_por` (`atualizado_por`),
ADD CONSTRAINT `pedido_cesta_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `pedido_cesta`
ADD KEY `idx_pedido_status` (`status`),
ADD KEY `idx_pedido_mes` (`mes`),
ADD KEY `idx_pedido_data_criacao` (`data_criacao`);

-- ============================================================
-- 11. MELHORIAS NA TABELA PRODUTO
-- ============================================================

ALTER TABLE `produto`
ADD COLUMN `categoria` varchar(100) DEFAULT NULL AFTER `nome`,
ADD COLUMN `marca` varchar(100) DEFAULT NULL AFTER `categoria`,
ADD COLUMN `preco_unitario` decimal(10,2) DEFAULT NULL AFTER `quantidade`,
ADD COLUMN `foto_url` varchar(500) DEFAULT NULL AFTER `preco_unitario`,
ADD COLUMN `ativo` tinyint(1) DEFAULT 1 AFTER `foto_url`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `produto`
ADD KEY `atualizado_por` (`atualizado_por`),
ADD CONSTRAINT `produto_ibfk_1` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `produto`
ADD KEY `idx_produto_categoria` (`categoria`),
ADD KEY `idx_produto_ativo` (`ativo`),
ADD KEY `idx_produto_nome` (`nome`);

-- ============================================================
-- 12. MELHORIAS NA TABELA ESTOQUE_CENTRAL
-- ============================================================

ALTER TABLE `estoque_central`
ADD COLUMN `fornecedor` varchar(255) DEFAULT NULL AFTER `lote`,
ADD COLUMN `nota_fiscal` varchar(50) DEFAULT NULL AFTER `fornecedor`,
ADD COLUMN `valor_unitario` decimal(10,2) DEFAULT NULL AFTER `nota_fiscal`,
ADD COLUMN `valor_total` decimal(12,2) DEFAULT NULL AFTER `valor_unitario`,
ADD COLUMN `criado_por` bigint(20) DEFAULT NULL AFTER `valor_total`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `criado_por`,
MODIFY COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign keys
ALTER TABLE `estoque_central`
ADD KEY `criado_por` (`criado_por`),
ADD KEY `atualizado_por` (`atualizado_por`),
ADD CONSTRAINT `estoque_central_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `estoque_central_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `estoque_central`
ADD KEY `idx_estoque_validade` (`validade`),
ADD KEY `idx_estoque_lote` (`lote`),
ADD KEY `idx_estoque_fornecedor` (`fornecedor`);

-- ============================================================
-- 13. MELHORIAS NA TABELA MOVIMENTACAO_ESTOQUE
-- ============================================================

ALTER TABLE `movimentacao_estoque`
ADD COLUMN `observacao` text DEFAULT NULL AFTER `referencia_tipo`,
ADD COLUMN `valor_unitario` decimal(10,2) DEFAULT NULL AFTER `observacao`,
ADD COLUMN `valor_total` decimal(12,2) DEFAULT NULL AFTER `valor_unitario`;

-- Ãndices
ALTER TABLE `movimentacao_estoque`
ADD KEY `idx_movimentacao_tipo` (`tipo`),
ADD KEY `idx_movimentacao_data` (`realizado_em`),
ADD KEY `idx_movimentacao_produto_tipo` (`produto_id`, `tipo`);

-- ============================================================
-- 14. MELHORIAS NA TABELA TURMA
-- ============================================================

ALTER TABLE `turma`
ADD COLUMN `sala` varchar(50) DEFAULT NULL AFTER `capacidade`,
ADD COLUMN `coordenador_id` bigint(20) DEFAULT NULL AFTER `sala`,
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `coordenador_id`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `turma`
ADD KEY `coordenador_id` (`coordenador_id`),
ADD KEY `atualizado_por` (`atualizado_por`),
ADD CONSTRAINT `turma_ibfk_2` FOREIGN KEY (`coordenador_id`) REFERENCES `professor` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `turma_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `turma`
ADD KEY `idx_turma_ano_letivo` (`ano_letivo`),
ADD KEY `idx_turma_turno` (`turno`),
ADD KEY `idx_turma_ativo` (`ativo`),
ADD KEY `idx_turma_escola_ano` (`escola_id`, `ano_letivo`);

-- ============================================================
-- 15. MELHORIAS NA TABELA PROFESSOR
-- ============================================================

ALTER TABLE `professor`
ADD COLUMN `especializacao` text DEFAULT NULL AFTER `formacao`,
ADD COLUMN `registro_profissional` varchar(50) DEFAULT NULL AFTER `especializacao`,
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `registro_profissional`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `observacoes`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `criado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `professor`
ADD KEY `criado_por` (`criado_por`),
ADD CONSTRAINT `professor_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `professor`
ADD KEY `idx_professor_ativo` (`ativo`);

-- ============================================================
-- 16. MELHORIAS NA TABELA GESTOR
-- ============================================================

ALTER TABLE `gestor`
ADD COLUMN `formacao` text DEFAULT NULL AFTER `cargo`,
ADD COLUMN `registro_profissional` varchar(50) DEFAULT NULL AFTER `formacao`,
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `registro_profissional`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `observacoes`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `criado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `gestor`
ADD KEY `criado_por` (`criado_por`),
ADD CONSTRAINT `gestor_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `gestor`
ADD KEY `idx_gestor_ativo` (`ativo`);

-- ============================================================
-- 17. MELHORIAS NA TABELA GESTOR_LOTACAO
-- ============================================================

ALTER TABLE `gestor_lotacao`
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `tipo`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `observacoes`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `criado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `gestor_lotacao`
ADD KEY `criado_por` (`criado_por`),
ADD CONSTRAINT `gestor_lotacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `gestor_lotacao`
ADD KEY `idx_gestor_lotacao_responsavel` (`responsavel`),
ADD KEY `idx_gestor_lotacao_tipo` (`tipo`),
ADD KEY `idx_gestor_lotacao_escola` (`escola_id`);

-- ============================================================
-- 18. MELHORIAS NA TABELA JUSTIFICATIVA
-- ============================================================

ALTER TABLE `justificativa`
ADD COLUMN `status` enum('PENDENTE','APROVADA','REJEITADA') DEFAULT 'PENDENTE' AFTER `motivo`,
ADD COLUMN `data_analise` timestamp NULL DEFAULT NULL AFTER `analise`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `data_analise`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`;

-- Ãndices
ALTER TABLE `justificativa`
ADD KEY `idx_justificativa_status` (`status`),
ADD KEY `idx_justificativa_data_envio` (`data_envio`);

-- ============================================================
-- 19. MELHORIAS NA TABELA USUARIO
-- ============================================================

ALTER TABLE `usuario`
ADD COLUMN `email_verificado` tinyint(1) DEFAULT 0 AFTER `ativo`,
ADD COLUMN `token_recuperacao` varchar(255) DEFAULT NULL AFTER `email_verificado`,
ADD COLUMN `token_expiracao` timestamp NULL DEFAULT NULL AFTER `token_recuperacao`,
ADD COLUMN `tentativas_login` int(11) DEFAULT 0 AFTER `token_expiracao`,
ADD COLUMN `bloqueado_ate` timestamp NULL DEFAULT NULL AFTER `tentativas_login`,
ADD COLUMN `ultimo_acesso` timestamp NULL DEFAULT NULL AFTER `ultimo_login`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`,
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `usuario`
ADD KEY `atualizado_por` (`atualizado_por`),
ADD CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndices
ALTER TABLE `usuario`
ADD KEY `idx_usuario_ativo` (`ativo`),
ADD KEY `idx_usuario_role` (`role`),
ADD KEY `idx_usuario_email_verificado` (`email_verificado`);

-- ============================================================
-- 20. MELHORIAS NA TABELA DISCIPLINA
-- ============================================================

ALTER TABLE `disciplina`
ADD COLUMN `descricao` text DEFAULT NULL AFTER `nome`,
ADD COLUMN `area_conhecimento` varchar(100) DEFAULT NULL AFTER `carga_horaria`,
ADD COLUMN `ativo` tinyint(1) DEFAULT 1 AFTER `area_conhecimento`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `ativo`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`;

-- Ãndices
ALTER TABLE `disciplina`
ADD KEY `idx_disciplina_ativo` (`ativo`),
ADD KEY `idx_disciplina_area` (`area_conhecimento`);

-- ============================================================
-- 21. MELHORIAS NA TABELA ALUNO_TURMA
-- ============================================================

ALTER TABLE `aluno_turma`
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `status`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `atualizado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `aluno_turma`
ADD KEY `atualizado_por` (`atualizado_por`),
ADD CONSTRAINT `aluno_turma_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndice Ãºnico para evitar duplicatas
ALTER TABLE `aluno_turma`
ADD UNIQUE KEY `unique_aluno_turma_ativo` (`aluno_id`, `turma_id`, `status`);

-- Ãndices
ALTER TABLE `aluno_turma`
ADD KEY `idx_aluno_turma_status` (`status`),
ADD KEY `idx_aluno_turma_inicio` (`inicio`);

-- ============================================================
-- 22. MELHORIAS NA TABELA TURMA_PROFESSOR
-- ============================================================

ALTER TABLE `turma_professor`
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `regime`,
ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `observacoes`,
ADD COLUMN `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `criado_em`,
ADD COLUMN `criado_por` bigint(20) DEFAULT NULL AFTER `atualizado_em`;

-- Foreign key
ALTER TABLE `turma_professor`
ADD KEY `criado_por` (`criado_por`),
ADD CONSTRAINT `turma_professor_ibfk_4` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Ãndice Ãºnico
ALTER TABLE `turma_professor`
ADD UNIQUE KEY `unique_turma_professor_disciplina` (`turma_id`, `professor_id`, `disciplina_id`, `inicio`);

-- ============================================================
-- 23. MELHORIAS NA TABELA LOG_SISTEMA
-- ============================================================

-- Ãndices adicionais
ALTER TABLE `log_sistema`
ADD KEY `idx_log_acao` (`acao`),
ADD KEY `idx_log_tipo_acao` (`tipo`, `acao`),
ADD KEY `idx_log_usuario_tipo` (`usuario_id`, `tipo`);

-- ============================================================
-- 24. CORRIGIR DADOS INVÃLIDOS
-- ============================================================

-- Corrigir datas invÃ¡lidas em pessoa
UPDATE `pessoa` SET `data_nascimento` = NULL WHERE `data_nascimento` = '0000-00-00' OR `data_nascimento` = '0303-00-03';

-- Corrigir datas invÃ¡lidas em professor
UPDATE `professor` SET `data_admissao` = NULL WHERE `data_admissao` = '0000-00-00' OR `data_admissao` = '0000-00-01';

-- Corrigir timestamps invÃ¡lidos em pedido_cesta
UPDATE `pedido_cesta` SET `data_envio` = NULL WHERE `data_envio` = '0000-00-00 00:00:00';
UPDATE `pedido_cesta` SET `data_aprovacao` = NULL WHERE `data_aprovacao` = '0000-00-00 00:00:00';

-- ============================================================
-- 25. ADICIONAR VALORES PADRÃƒO MELHORES
-- ============================================================

-- Definir valores padrÃ£o para campos booleanos
ALTER TABLE `aluno` MODIFY COLUMN `ativo` tinyint(1) DEFAULT 1;
ALTER TABLE `professor` MODIFY COLUMN `ativo` tinyint(1) DEFAULT 1;
ALTER TABLE `gestor` MODIFY COLUMN `ativo` tinyint(1) DEFAULT 1;
ALTER TABLE `usuario` MODIFY COLUMN `ativo` tinyint(1) DEFAULT 1;

-- ============================================================
-- FIM DAS MELHORIAS
-- ============================================================

-- ============================================================
-- RESUMO DAS MELHORIAS APLICADAS
-- ============================================================
-- 1. âœ… Adicionado RESPONSAVEL no enum de usuario e role_permissao
-- 2. âœ… Melhorias em PESSOA (endereÃ§o completo, contatos, foto, auditoria)
-- 3. âœ… Melhorias em ALUNO (escola_id, situacao, dados pessoais, auditoria)
-- 4. âœ… Melhorias em ESCOLA (endereÃ§o completo, CNPJ, diretor, auditoria)
-- 5. âœ… Melhorias em AVALIACAO (disciplina_id, criado_por, auditoria)
-- 6. âœ… Melhorias em NOTA (disciplina_id, turma_id, bimestre, recuperacao)
-- 7. âœ… Melhorias em FREQUENCIA (observacao, unique constraint, Ã­ndices)
-- 8. âœ… Melhorias em COMUNICADO (escola_id, tipo, prioridade, lido, anexo)
-- 9. âœ… Melhorias em CARDAPIO (status, aprovado_por, auditoria)
-- 10. âœ… Melhorias em PEDIDO_CESTA (timestamps corrigidos, campos adicionais)
-- 11. âœ… Melhorias em PRODUTO (categoria, marca, preÃ§o, foto, auditoria)
-- 12. âœ… Melhorias em ESTOQUE_CENTRAL (fornecedor, nota fiscal, valores, auditoria)
-- 13. âœ… Melhorias em MOVIMENTACAO_ESTOQUE (observacao, valores, Ã­ndices)
-- 14. âœ… Melhorias em TURMA (sala, coordenador, auditoria, Ã­ndices)
-- 15. âœ… Melhorias em PROFESSOR (especializacao, registro, auditoria)
-- 16. âœ… Melhorias em GESTOR (formacao, registro, auditoria)
-- 17. âœ… Melhorias em GESTOR_LOTACAO (observacoes, auditoria, Ã­ndices)
-- 18. âœ… Melhorias em JUSTIFICATIVA (status, data_analise, Ã­ndices)
-- 19. âœ… Melhorias em USUARIO (seguranÃ§a, tokens, auditoria)
-- 20. âœ… Melhorias em DISCIPLINA (descricao, area_conhecimento, auditoria)
-- 21. âœ… Melhorias em ALUNO_TURMA (unique constraint, auditoria)
-- 22. âœ… Melhorias em TURMA_PROFESSOR (unique constraint, auditoria)
-- 23. âœ… Melhorias em LOG_SISTEMA (Ã­ndices adicionais)
-- 24. âœ… CorreÃ§Ã£o de dados invÃ¡lidos (datas e timestamps)
-- 25. âœ… Valores padrÃ£o melhorados
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================
-- 1. TABELA SERIE (ADM pode criar sÃ©ries)
-- ============================================================

CREATE TABLE IF NOT EXISTS `serie` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_serie_nivel` (`nivel_ensino`),
  KEY `idx_serie_ativo` (`ativo`),
  CONSTRAINT `serie_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adicionar serie_id na tabela turma
ALTER TABLE `turma`
ADD COLUMN `serie_id` bigint(20) DEFAULT NULL AFTER `escola_id`,
ADD KEY `serie_id` (`serie_id`),
ADD CONSTRAINT `turma_ibfk_4` FOREIGN KEY (`serie_id`) REFERENCES `serie` (`id`) ON DELETE SET NULL;

-- ============================================================
-- 2. TABELA FUNCIONARIO (ADM pode cadastrar funcionÃ¡rios)
-- ============================================================

CREATE TABLE IF NOT EXISTS `funcionario` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `pessoa_id` (`pessoa_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_funcionario_ativo` (`ativo`),
  KEY `idx_funcionario_cargo` (`cargo`),
  KEY `idx_funcionario_setor` (`setor`),
  CONSTRAINT `funcionario_ibfk_1` FOREIGN KEY (`pessoa_id`) REFERENCES `pessoa` (`id`),
  CONSTRAINT `funcionario_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. TABELA FORNECEDOR (ADM_MERENDA monitora fornecedores)
-- ============================================================

CREATE TABLE IF NOT EXISTS `fornecedor` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_fornecedor_ativo` (`ativo`),
  KEY `idx_fornecedor_tipo` (`tipo_fornecedor`),
  KEY `idx_fornecedor_cidade` (`cidade`),
  CONSTRAINT `fornecedor_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Atualizar produto para usar fornecedor_id
ALTER TABLE `produto`
ADD COLUMN `fornecedor_id` bigint(20) DEFAULT NULL AFTER `fornecedor`,
ADD KEY `fornecedor_id` (`fornecedor_id`),
ADD CONSTRAINT `produto_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL;

-- Atualizar estoque_central para usar fornecedor_id
ALTER TABLE `estoque_central`
ADD COLUMN `fornecedor_id` bigint(20) DEFAULT NULL AFTER `fornecedor`,
ADD KEY `fornecedor_id` (`fornecedor_id`),
ADD CONSTRAINT `estoque_central_ibfk_4` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL;

-- ============================================================
-- 4. TABELA CONSUMO_DIARIO (ADM_MERENDA registra consumo diÃ¡rio)
-- ============================================================

CREATE TABLE IF NOT EXISTS `consumo_diario` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `atualizado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `turma_id` (`turma_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_consumo_data` (`data`),
  KEY `idx_consumo_escola_data` (`escola_id`, `data`),
  UNIQUE KEY `unique_consumo_escola_turma_data` (`escola_id`, `turma_id`, `data`, `turno`),
  CONSTRAINT `consumo_diario_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `consumo_diario_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumo_diario_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumo_diario_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 5. TABELA CONSUMO_ITEM (Itens consumidos no dia)
-- ============================================================

CREATE TABLE IF NOT EXISTS `consumo_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `consumo_diario_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade` decimal(10,3) DEFAULT NULL,
  `unidade_medida` varchar(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consumo_diario_id` (`consumo_diario_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `consumo_item_ibfk_1` FOREIGN KEY (`consumo_diario_id`) REFERENCES `consumo_diario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consumo_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 6. TABELA DESPERDICIO (ADM_MERENDA monitora desperdÃ­cio)
-- ============================================================

CREATE TABLE IF NOT EXISTS `desperdicio` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `produto_id` (`produto_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_desperdicio_data` (`data`),
  KEY `idx_desperdicio_motivo` (`motivo`),
  KEY `idx_desperdicio_escola_data` (`escola_id`, `data`),
  CONSTRAINT `desperdicio_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `desperdicio_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`) ON DELETE SET NULL,
  CONSTRAINT `desperdicio_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 7. TABELA CUSTO_MERENDA (ADM_MERENDA monitora custos)
-- ============================================================

CREATE TABLE IF NOT EXISTS `custo_merenda` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `produto_id` (`produto_id`),
  KEY `fornecedor_id` (`fornecedor_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_custo_data` (`data`),
  KEY `idx_custo_tipo` (`tipo`),
  KEY `idx_custo_mes_ano` (`mes`, `ano`),
  KEY `idx_custo_escola_mes_ano` (`escola_id`, `mes`, `ano`),
  CONSTRAINT `custo_merenda_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `custo_merenda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`) ON DELETE SET NULL,
  CONSTRAINT `custo_merenda_ibfk_3` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `custo_merenda_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 8. TABELA ENTREGA (ADM_MERENDA acompanha entregas)
-- ============================================================

CREATE TABLE IF NOT EXISTS `entrega` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pedido_cesta_id` (`pedido_cesta_id`),
  KEY `escola_id` (`escola_id`),
  KEY `fornecedor_id` (`fornecedor_id`),
  KEY `recebido_por` (`recebido_por`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_entrega_status` (`status`),
  KEY `idx_entrega_data_prevista` (`data_prevista`),
  KEY `idx_entrega_data_entrega` (`data_entrega`),
  CONSTRAINT `entrega_ibfk_1` FOREIGN KEY (`pedido_cesta_id`) REFERENCES `pedido_cesta` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entrega_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `entrega_ibfk_3` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entrega_ibfk_4` FOREIGN KEY (`recebido_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `entrega_ibfk_5` FOREIGN KEY (`registrado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 9. TABELA ENTREGA_ITEM (Itens da entrega)
-- ============================================================

CREATE TABLE IF NOT EXISTS `entrega_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `entrega_id` bigint(20) NOT NULL,
  `produto_id` bigint(20) NOT NULL,
  `quantidade_solicitada` decimal(10,3) DEFAULT NULL,
  `quantidade_entregue` decimal(10,3) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entrega_id` (`entrega_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `entrega_item_ibfk_1` FOREIGN KEY (`entrega_id`) REFERENCES `entrega` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entrega_item_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 10. TABELA PLANO_AULA (PROFESSOR registra planos de aula)
-- ============================================================

CREATE TABLE IF NOT EXISTS `plano_aula` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `turma_id` (`turma_id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `professor_id` (`professor_id`),
  KEY `aprovado_por` (`aprovado_por`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_plano_data_aula` (`data_aula`),
  KEY `idx_plano_status` (`status`),
  KEY `idx_plano_bimestre` (`bimestre`),
  CONSTRAINT `plano_aula_ibfk_1` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `plano_aula_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`),
  CONSTRAINT `plano_aula_ibfk_3` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  CONSTRAINT `plano_aula_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `plano_aula_ibfk_5` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 11. TABELA OBSERVACAO_DESEMPENHO (PROFESSOR adiciona observaÃ§Ãµes)
-- ============================================================

CREATE TABLE IF NOT EXISTS `observacao_desempenho` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `turma_id` (`turma_id`),
  KEY `disciplina_id` (`disciplina_id`),
  KEY `professor_id` (`professor_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_observacao_data` (`data`),
  KEY `idx_observacao_tipo` (`tipo`),
  KEY `idx_observacao_bimestre` (`bimestre`),
  CONSTRAINT `observacao_desempenho_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `observacao_desempenho_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `observacao_desempenho_ibfk_3` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`) ON DELETE SET NULL,
  CONSTRAINT `observacao_desempenho_ibfk_4` FOREIGN KEY (`professor_id`) REFERENCES `professor` (`id`),
  CONSTRAINT `observacao_desempenho_ibfk_5` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 12. TABELA BOLETIM (ALUNO visualiza boletins)
-- ============================================================

CREATE TABLE IF NOT EXISTS `boletim` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `turma_id` (`turma_id`),
  KEY `gerado_por` (`gerado_por`),
  KEY `idx_boletim_ano_bimestre` (`ano_letivo`, `bimestre`),
  KEY `idx_boletim_situacao` (`situacao`),
  UNIQUE KEY `unique_boletim_aluno_turma_bimestre` (`aluno_id`, `turma_id`, `ano_letivo`, `bimestre`),
  CONSTRAINT `boletim_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `boletim_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`),
  CONSTRAINT `boletim_ibfk_3` FOREIGN KEY (`gerado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 13. TABELA BOLETIM_ITEM (Notas por disciplina no boletim)
-- ============================================================

CREATE TABLE IF NOT EXISTS `boletim_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `boletim_id` bigint(20) NOT NULL,
  `disciplina_id` bigint(20) NOT NULL,
  `media` decimal(5,2) DEFAULT NULL,
  `faltas` int(11) DEFAULT 0,
  `situacao` enum('APROVADO','REPROVADO','RECUPERACAO') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `boletim_id` (`boletim_id`),
  KEY `disciplina_id` (`disciplina_id`),
  CONSTRAINT `boletim_item_ibfk_1` FOREIGN KEY (`boletim_id`) REFERENCES `boletim` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boletim_item_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplina` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 14. TABELA HISTORICO_ESCOLAR (ALUNO visualiza histÃ³rico)
-- ============================================================

CREATE TABLE IF NOT EXISTS `historico_escolar` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `gerado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `aluno_id` (`aluno_id`),
  KEY `escola_id` (`escola_id`),
  KEY `gerado_por` (`gerado_por`),
  KEY `idx_historico_ano` (`ano_letivo`),
  KEY `idx_historico_situacao` (`situacao`),
  CONSTRAINT `historico_escolar_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `aluno` (`id`),
  CONSTRAINT `historico_escolar_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `historico_escolar_ibfk_3` FOREIGN KEY (`gerado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 15. TABELA RELATORIO (ADM e GESTAO geram relatÃ³rios)
-- ============================================================

CREATE TABLE IF NOT EXISTS `relatorio` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
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
  `concluido_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escola_id` (`escola_id`),
  KEY `turma_id` (`turma_id`),
  KEY `gerado_por` (`gerado_por`),
  KEY `idx_relatorio_tipo` (`tipo`),
  KEY `idx_relatorio_status` (`status`),
  KEY `idx_relatorio_periodo` (`periodo_inicio`, `periodo_fim`),
  CONSTRAINT `relatorio_ibfk_1` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
  CONSTRAINT `relatorio_ibfk_2` FOREIGN KEY (`turma_id`) REFERENCES `turma` (`id`) ON DELETE SET NULL,
  CONSTRAINT `relatorio_ibfk_3` FOREIGN KEY (`gerado_por`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 16. TABELA VALIDACAO (ADM e GESTAO validam informaÃ§Ãµes)
-- ============================================================

CREATE TABLE IF NOT EXISTS `validacao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tipo_registro` enum('NOTA','FREQUENCIA','PLANO_AULA','OBSERVACAO','COMUNICADO','CARDAPIO','PEDIDO','OUTROS') NOT NULL,
  `registro_id` bigint(20) NOT NULL,
  `status` enum('PENDENTE','APROVADO','REJEITADO') DEFAULT 'PENDENTE',
  `observacoes` text DEFAULT NULL,
  `validado_por` bigint(20) DEFAULT NULL,
  `data_validacao` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `validado_por` (`validado_por`),
  KEY `idx_validacao_tipo` (`tipo_registro`),
  KEY `idx_validacao_status` (`status`),
  KEY `idx_validacao_registro` (`tipo_registro`, `registro_id`),
  CONSTRAINT `validacao_ibfk_1` FOREIGN KEY (`validado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 17. MELHORIAS NA TABELA COMUNICADO (GESTAO supervisiona comunicaÃ§Ã£o)
-- ============================================================

-- Adicionar campos para comunicaÃ§Ã£o escola-responsÃ¡veis
ALTER TABLE `comunicado`
ADD COLUMN `canal` enum('SISTEMA','EMAIL','SMS','WHATSAPP','TODOS') DEFAULT 'SISTEMA' AFTER `prioridade`,
ADD COLUMN `enviado` tinyint(1) DEFAULT 0 AFTER `lido`,
ADD COLUMN `data_envio` timestamp NULL DEFAULT NULL AFTER `enviado`,
ADD COLUMN `respostas_recebidas` int(11) DEFAULT 0 AFTER `data_envio`,
ADD COLUMN `visualizacoes` int(11) DEFAULT 0 AFTER `respostas_recebidas`;

-- Ãndices adicionais
ALTER TABLE `comunicado`
ADD KEY `idx_comunicado_canal` (`canal`),
ADD KEY `idx_comunicado_enviado` (`enviado`);

-- ============================================================
-- 18. TABELA COMUNICADO_RESPOSTA (Respostas dos responsÃ¡veis)
-- ============================================================

CREATE TABLE IF NOT EXISTS `comunicado_resposta` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `comunicado_id` bigint(20) NOT NULL,
  `responsavel_id` bigint(20) NOT NULL,
  `resposta` text DEFAULT NULL,
  `lido` tinyint(1) DEFAULT 0,
  `data_leitura` timestamp NULL DEFAULT NULL,
  `data_resposta` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `comunicado_id` (`comunicado_id`),
  KEY `responsavel_id` (`responsavel_id`),
  UNIQUE KEY `unique_comunicado_responsavel` (`comunicado_id`, `responsavel_id`),
  CONSTRAINT `comunicado_resposta_ibfk_1` FOREIGN KEY (`comunicado_id`) REFERENCES `comunicado` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comunicado_resposta_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `pessoa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 19. TABELA FUNCIONARIO_LOTACAO (FuncionÃ¡rios lotados em escolas)
-- ============================================================

CREATE TABLE IF NOT EXISTS `funcionario_lotacao` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `funcionario_id` bigint(20) NOT NULL,
  `escola_id` bigint(20) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fim` date DEFAULT NULL,
  `setor` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criado_por` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `funcionario_id` (`funcionario_id`),
  KEY `escola_id` (`escola_id`),
  KEY `criado_por` (`criado_por`),
  CONSTRAINT `funcionario_lotacao_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`),
  CONSTRAINT `funcionario_lotacao_ibfk_2` FOREIGN KEY (`escola_id`) REFERENCES `escola` (`id`),
  CONSTRAINT `funcionario_lotacao_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 20. ADICIONAR CAMPOS DE VALIDAÃ‡ÃƒO NAS TABELAS EXISTENTES
-- ============================================================

-- Adicionar campo validado em NOTA
ALTER TABLE `nota`
ADD COLUMN `validado` tinyint(1) DEFAULT 0 AFTER `recuperacao`,
ADD COLUMN `validado_por` bigint(20) DEFAULT NULL AFTER `validado`,
ADD COLUMN `data_validacao` timestamp NULL DEFAULT NULL AFTER `validado_por`,
ADD KEY `validado_por` (`validado_por`),
ADD CONSTRAINT `nota_ibfk_7` FOREIGN KEY (`validado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Adicionar campo validado em FREQUENCIA
ALTER TABLE `frequencia`
ADD COLUMN `validado` tinyint(1) DEFAULT 0 AFTER `observacao`,
ADD COLUMN `validado_por` bigint(20) DEFAULT NULL AFTER `validado`,
ADD COLUMN `data_validacao` timestamp NULL DEFAULT NULL AFTER `validado_por`,
ADD KEY `validado_por` (`validado_por`),
ADD CONSTRAINT `frequencia_ibfk_6` FOREIGN KEY (`validado_por`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

-- Adicionar campo validado em PLANO_AULA (se jÃ¡ existe)
-- Se nÃ£o existe, jÃ¡ foi criado acima com status

-- ============================================================
-- FIM DAS MELHORIAS SIGAE
-- ============================================================

COMMIT;

-- ============================================================
-- RESUMO DAS TABELAS CRIADAS
-- ============================================================
-- 1. âœ… serie - SÃ©ries escolares (ADM cria)
-- 2. âœ… funcionario - FuncionÃ¡rios (ADM cadastra)
-- 3. âœ… funcionario_lotacao - LotaÃ§Ã£o de funcionÃ¡rios
-- 4. âœ… fornecedor - Fornecedores (ADM_MERENDA monitora)
-- 5. âœ… consumo_diario - Consumo diÃ¡rio (ADM_MERENDA registra)
-- 6. âœ… consumo_item - Itens consumidos
-- 7. âœ… desperdicio - DesperdÃ­cio (ADM_MERENDA monitora)
-- 8. âœ… custo_merenda - Custos (ADM_MERENDA monitora)
-- 9. âœ… entrega - Entregas (ADM_MERENDA acompanha)
-- 10. âœ… entrega_item - Itens das entregas
-- 11. âœ… plano_aula - Planos de aula (PROFESSOR registra)
-- 12. âœ… observacao_desempenho - ObservaÃ§Ãµes (PROFESSOR adiciona)
-- 13. âœ… boletim - Boletins (ALUNO visualiza)
-- 14. âœ… boletim_item - Itens do boletim
-- 15. âœ… historico_escolar - HistÃ³rico escolar (ALUNO visualiza)
-- 16. âœ… relatorio - RelatÃ³rios (ADM e GESTAO geram)
-- 17. âœ… validacao - ValidaÃ§Ãµes (ADM e GESTAO validam)
-- 18. âœ… comunicado_resposta - Respostas de comunicados
-- ============================================================

