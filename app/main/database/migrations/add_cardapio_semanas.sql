-- Migration: Adicionar suporte a semanas no cardápio
-- Data: 2025-01-XX
-- Descrição: Adiciona estrutura para separar cardápios por semana com observações

-- Criar tabela cardapio_semana
CREATE TABLE IF NOT EXISTS `cardapio_semana` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `cardapio_id` bigint(20) NOT NULL,
  `numero_semana` tinyint(4) NOT NULL COMMENT 'Número da semana no mês (1-4 ou 1-5)',
  `observacao` text DEFAULT NULL COMMENT 'Observações específicas desta semana',
  `data_inicio` date DEFAULT NULL COMMENT 'Data de início da semana',
  `data_fim` date DEFAULT NULL COMMENT 'Data de fim da semana',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cardapio_semana_cardapio` (`cardapio_id`),
  KEY `idx_cardapio_semana_numero` (`cardapio_id`, `numero_semana`),
  CONSTRAINT `cardapio_semana_ibfk_1` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adicionar campo semana_id em cardapio_item (opcional, para permitir itens sem semana específica)
ALTER TABLE `cardapio_item` 
ADD COLUMN `semana_id` bigint(20) DEFAULT NULL AFTER `cardapio_id`,
ADD KEY `idx_cardapio_item_semana` (`semana_id`),
ADD CONSTRAINT `cardapio_item_ibfk_3` FOREIGN KEY (`semana_id`) REFERENCES `cardapio_semana` (`id`) ON DELETE SET NULL;

