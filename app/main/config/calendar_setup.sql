-- =====================================================
-- SCRIPT DE CONFIGURAÇÃO DO CALENDÁRIO
-- Sistema de Gestão Escolar - SIGEA
-- =====================================================

-- Tabela de Eventos do Calendário
CREATE TABLE IF NOT EXISTS `calendar_events` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `start_date` datetime NOT NULL,
    `end_date` datetime DEFAULT NULL,
    `all_day` tinyint(1) DEFAULT 0,
    `color` varchar(7) DEFAULT '#3B82F6',
    `event_type` enum('meeting', 'exam', 'holiday', 'event', 'deadline', 'class', 'meeting_parents', 'training') DEFAULT 'event',
    `school_id` bigint(20) DEFAULT NULL,
    `created_by` bigint(20) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `ativo` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `school_id` (`school_id`),
    KEY `created_by` (`created_by`),
    KEY `start_date` (`start_date`),
    KEY `event_type` (`event_type`),
    CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `escola` (`id`) ON DELETE SET NULL,
    CONSTRAINT `calendar_events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Notificações do Calendário
CREATE TABLE IF NOT EXISTS `calendar_notifications` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `event_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `notification_type` enum('email', 'sms', 'push', 'system') DEFAULT 'system',
    `sent_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `ativo` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `event_id` (`event_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `calendar_notifications_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
    CONSTRAINT `calendar_notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Participantes do Evento
CREATE TABLE IF NOT EXISTS `calendar_event_participants` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `event_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `role` enum('organizer', 'attendee', 'optional') DEFAULT 'attendee',
    `status` enum('pending', 'accepted', 'declined', 'tentative') DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `event_id` (`event_id`),
    KEY `user_id` (`user_id`),
    UNIQUE KEY `unique_participant` (`event_id`, `user_id`),
    CONSTRAINT `calendar_event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
    CONSTRAINT `calendar_event_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Recorrência de Eventos
CREATE TABLE IF NOT EXISTS `calendar_event_recurrence` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `event_id` bigint(20) NOT NULL,
    `recurrence_type` enum('daily', 'weekly', 'monthly', 'yearly') NOT NULL,
    `interval_value` int(11) DEFAULT 1,
    `days_of_week` varchar(20) DEFAULT NULL,
    `day_of_month` int(11) DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `occurrences` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `event_id` (`event_id`),
    CONSTRAINT `calendar_event_recurrence_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de Categorias de Eventos
CREATE TABLE IF NOT EXISTS `calendar_categories` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `color` varchar(7) NOT NULL,
    `icon` varchar(50) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `created_by` bigint(20) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `ativo` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `created_by` (`created_by`),
    CONSTRAINT `calendar_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserir categorias padrão
INSERT INTO `calendar_categories` (`name`, `color`, `icon`, `description`, `created_by`) VALUES
('Reuniões', '#3B82F6', 'users', 'Reuniões gerais e administrativas', 1),
('Avaliações', '#EF4444', 'book-open', 'Provas e avaliações dos alunos', 1),
('Feriados', '#10B981', 'calendar', 'Feriados nacionais e regionais', 1),
('Eventos', '#F59E0B', 'star', 'Eventos especiais da escola', 1),
('Aulas', '#8B5CF6', 'graduation-cap', 'Aulas e atividades pedagógicas', 1),
('Treinamentos', '#EC4899', 'book', 'Treinamentos e capacitações', 1),
('Reunião de Pais', '#14B8A6', 'users', 'Reuniões com pais e responsáveis', 1);

-- Tabela de Configurações do Calendário
CREATE TABLE IF NOT EXISTS `calendar_settings` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) NOT NULL,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    UNIQUE KEY `unique_user_setting` (`user_id`, `setting_key`),
    CONSTRAINT `calendar_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserir configurações padrão para todos os usuários
INSERT INTO `calendar_settings` (`user_id`, `setting_key`, `setting_value`) 
SELECT `id`, 'default_view', 'month' FROM `usuario` WHERE `ativo` = 1;

INSERT INTO `calendar_settings` (`user_id`, `setting_key`, `setting_value`) 
SELECT `id`, 'week_start', 'monday' FROM `usuario` WHERE `ativo` = 1;

INSERT INTO `calendar_settings` (`user_id`, `setting_key`, `setting_value`) 
SELECT `id`, 'timezone', 'America/Fortaleza' FROM `usuario` WHERE `ativo` = 1;

-- Inserir alguns eventos de exemplo
INSERT INTO `calendar_events` (`title`, `description`, `start_date`, `end_date`, `all_day`, `color`, `event_type`, `school_id`, `created_by`) VALUES
('Reunião Pedagógica', 'Reunião mensal com professores para planejamento pedagógico', '2025-01-15 14:00:00', '2025-01-15 16:00:00', 0, '#3B82F6', 'meeting', 14, 1),
('Prova de Matemática - 6º Ano', 'Avaliação bimestral de matemática para o 6º ano', '2025-01-20 08:00:00', '2025-01-20 10:00:00', 0, '#EF4444', 'exam', 14, 1),
('Feriado - São Sebastião', 'Feriado municipal em homenagem ao padroeiro', '2025-01-20 00:00:00', '2025-01-20 23:59:59', 1, '#10B981', 'holiday', NULL, 1),
('Reunião de Pais - 1º Bimestre', 'Reunião com pais para entrega de boletins do 1º bimestre', '2025-01-25 19:00:00', '2025-01-25 21:00:00', 0, '#14B8A6', 'meeting_parents', 14, 1),
('Capacitação de Professores', 'Treinamento sobre novas metodologias de ensino', '2025-01-30 09:00:00', '2025-01-30 17:00:00', 0, '#EC4899', 'training', 14, 1);

-- Criar índices para melhor performance
CREATE INDEX `idx_calendar_events_date` ON `calendar_events` (`start_date`, `end_date`);
CREATE INDEX `idx_calendar_events_school` ON `calendar_events` (`school_id`, `ativo`);
CREATE INDEX `idx_calendar_events_type` ON `calendar_events` (`event_type`, `ativo`);
CREATE INDEX `idx_calendar_events_creator` ON `calendar_events` (`created_by`, `ativo`);

-- =====================================================
-- SCRIPT CONCLUÍDO
-- =====================================================
