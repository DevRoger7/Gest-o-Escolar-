-- ============================================================
-- DADOS DE TESTE PARA MERENDA ESCOLAR
-- ============================================================
-- Este script cria dados completos para testar todas as funcionalidades
-- do Administrador de Merenda
-- ============================================================

-- ============================================================
-- 0. CONFIGURAÇÃO INICIAL - Buscar IDs dinamicamente
-- ============================================================
-- Obtém o ID da primeira escola ativa disponível
SET @escola_id = (SELECT id FROM escola WHERE ativo = 1 LIMIT 1);

-- Se não houver escola, use ID 1 (ajuste se necessário)
-- SET @escola_id = 1;

-- Obtém o ID do usuário ADM_MERENDA (ou use 1 como fallback)
SET @usuario_id = (SELECT id FROM usuario WHERE role = 'ADM_MERENDA' LIMIT 1);
SET @usuario_id = IFNULL(@usuario_id, 1);

-- Calcular próximo ID disponível para cada tabela
SET @produto_id_inicio = IFNULL((SELECT MAX(id) FROM produto), 0) + 1;
SET @fornecedor_id_inicio = IFNULL((SELECT MAX(id) FROM fornecedor), 0) + 1;
SET @estoque_id_inicio = IFNULL((SELECT MAX(id) FROM estoque_central), 0) + 1;
SET @cardapio_id_inicio = IFNULL((SELECT MAX(id) FROM cardapio), 0) + 1;
SET @cardapio_item_id_inicio = IFNULL((SELECT MAX(id) FROM cardapio_item), 0) + 1;
SET @consumo_id_inicio = IFNULL((SELECT MAX(id) FROM consumo_diario), 0) + 1;
SET @desperdicio_id_inicio = IFNULL((SELECT MAX(id) FROM desperdicio), 0) + 1;
SET @pedido_id_inicio = IFNULL((SELECT MAX(id) FROM pedido_cesta), 0) + 1;
SET @pedido_item_id_inicio = IFNULL((SELECT MAX(id) FROM pedido_item), 0) + 1;
SET @entrega_id_inicio = IFNULL((SELECT MAX(id) FROM entrega), 0) + 1;
SET @entrega_item_id_inicio = IFNULL((SELECT MAX(id) FROM entrega_item), 0) + 1;

-- Verificar se as variáveis foram definidas (descomente para debug)
-- SELECT @escola_id as escola_id, @usuario_id as usuario_id, @produto_id_inicio as produto_id_inicio;

-- ============================================================
-- 1. PRODUTOS PARA CARDÁPIOS E ESTOQUE
-- ============================================================

INSERT IGNORE INTO `produto` (
    `id`, `codigo`, `nome`, `categoria`, `marca`, `unidade_medida`, 
    `estoque_minimo`, `ativo`, `criado_em`, `atualizado_em`
) VALUES
-- Cereais e Grãos
(@produto_id_inicio + 0, 'PROD001', 'Arroz Branco Tipo 1', 'CEREAIS', 'Tio João', 'KG', 100, 1, NOW(), NOW()),
(@produto_id_inicio + 1, 'PROD002', 'Feijão Carioca', 'CEREAIS', 'Camil', 'KG', 80, 1, NOW(), NOW()),
(@produto_id_inicio + 2, 'PROD003', 'Macarrão Espaguete', 'CEREAIS', 'Galão', 'KG', 50, 1, NOW(), NOW()),
(@produto_id_inicio + 3, 'PROD004', 'Farinha de Trigo', 'CEREAIS', 'Dona Benta', 'KG', 60, 1, NOW(), NOW()),
(@produto_id_inicio + 4, 'PROD005', 'Açúcar Cristal', 'CEREAIS', 'União', 'KG', 40, 1, NOW(), NOW()),

-- Carnes
(@produto_id_inicio + 5, 'PROD006', 'Carne Bovina Moída', 'CARNES', 'Friboi', 'KG', 30, 1, NOW(), NOW()),
(@produto_id_inicio + 6, 'PROD007', 'Frango Inteiro', 'CARNES', 'Sadia', 'KG', 25, 1, NOW(), NOW()),
(@produto_id_inicio + 7, 'PROD008', 'Salsicha', 'CARNES', 'Perdigão', 'KG', 20, 1, NOW(), NOW()),

-- Laticínios
(@produto_id_inicio + 8, 'PROD009', 'Leite Integral', 'LATICINIOS', 'Itambé', 'L', 200, 1, NOW(), NOW()),
(@produto_id_inicio + 9, 'PROD010', 'Queijo Mussarela', 'LATICINIOS', 'Tirolez', 'KG', 15, 1, NOW(), NOW()),
(@produto_id_inicio + 10, 'PROD011', 'Manteiga', 'LATICINIOS', 'Aviação', 'KG', 10, 1, NOW(), NOW()),

-- Hortifruti
(@produto_id_inicio + 11, 'PROD012', 'Batata', 'HORTIFRUTI', 'Frescor', 'KG', 50, 1, NOW(), NOW()),
(@produto_id_inicio + 12, 'PROD013', 'Cebola', 'HORTIFRUTI', 'Frescor', 'KG', 20, 1, NOW(), NOW()),
(@produto_id_inicio + 13, 'PROD014', 'Tomate', 'HORTIFRUTI', 'Frescor', 'KG', 30, 1, NOW(), NOW()),
(@produto_id_inicio + 14, 'PROD015', 'Banana', 'HORTIFRUTI', 'Frescor', 'KG', 40, 1, NOW(), NOW()),
(@produto_id_inicio + 15, 'PROD016', 'Laranja', 'HORTIFRUTI', 'Frescor', 'KG', 35, 1, NOW(), NOW()),

-- Óleos e Gorduras
(@produto_id_inicio + 16, 'PROD017', 'Óleo de Soja', 'OLEOS', 'Liza', 'L', 50, 1, NOW(), NOW()),
(@produto_id_inicio + 17, 'PROD018', 'Margarina', 'OLEOS', 'Qualy', 'KG', 20, 1, NOW(), NOW()),

-- Enlatados
(@produto_id_inicio + 18, 'PROD019', 'Milho Verde em Conserva', 'ENLATADOS', 'Quero', 'UN', 100, 1, NOW(), NOW()),
(@produto_id_inicio + 19, 'PROD020', 'Ervilha em Conserva', 'ENLATADOS', 'Quero', 'UN', 80, 1, NOW(), NOW()),

-- Bebidas
(@produto_id_inicio + 20, 'PROD021', 'Suco de Laranja', 'BEBIDAS', 'Maguary', 'L', 150, 1, NOW(), NOW()),
(@produto_id_inicio + 21, 'PROD022', 'Achocolatado em Pó', 'BEBIDAS', 'Nescau', 'KG', 25, 1, NOW(), NOW());

-- ============================================================
-- 2. FORNECEDORES
-- ============================================================

INSERT IGNORE INTO `fornecedor` (
    `id`, `nome`, `razao_social`, `cnpj`, `inscricao_estadual`,
    `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`,
    `telefone`, `email`, `contato`, `tipo_fornecedor`, `ativo`, `criado_em`
) VALUES
(@fornecedor_id_inicio + 0, 'Distribuidora Alimentos Maranguape', 'Distribuidora Alimentos Maranguape LTDA', '12.345.678/0001-90', '123456789',
 'Av. Principal', '500', 'Centro', 'Maranguape', 'CE', '61940-000',
 '(85) 3333-1111', 'contato@alimentosmaranguape.com.br', 'João Silva', 'ALIMENTOS', 1, NOW()),

(@fornecedor_id_inicio + 1, 'Carnes Premium', 'Carnes Premium EIRELI', '23.456.789/0001-01', '234567890',
 'Rua dos Açougues', '200', 'Industrial', 'Fortaleza', 'CE', '60000-000',
 '(85) 3333-2222', 'vendas@carnespremium.com.br', 'Maria Santos', 'ALIMENTOS', 1, NOW()),

(@fornecedor_id_inicio + 2, 'Hortifruti Verde Vida', 'Hortifruti Verde Vida ME', '34.567.890/0001-12', '345678901',
 'Rodovia BR-116', 'KM 15', 'Zona Rural', 'Maranguape', 'CE', '61940-000',
 '(85) 3333-3333', 'compras@verdevida.com.br', 'Pedro Oliveira', 'ALIMENTOS', 1, NOW()),

(@fornecedor_id_inicio + 3, 'Laticínios do Nordeste', 'Laticínios do Nordeste S.A.', '45.678.901/0001-23', '456789012',
 'Av. Industrial', '1000', 'Distrito Industrial', 'Fortaleza', 'CE', '60000-000',
 '(85) 3333-4444', 'comercial@laticiniosne.com.br', 'Ana Costa', 'ALIMENTOS', 1, NOW()),

(@fornecedor_id_inicio + 4, 'Bebidas e Refrigerantes CE', 'Bebidas e Refrigerantes CE LTDA', '56.789.012/0001-34', '567890123',
 'Rua Comercial', '300', 'Centro', 'Maranguape', 'CE', '61940-000',
 '(85) 3333-5555', 'pedidos@bebidasce.com.br', 'Carlos Mendes', 'BEBIDAS', 1, NOW());

-- ============================================================
-- 3. ESTOQUE CENTRAL
-- ============================================================

INSERT IGNORE INTO `estoque_central` (
    `id`, `produto_id`, `quantidade`, `lote`, `fornecedor_id`,
    `nota_fiscal`, `valor_unitario`, `valor_total`, `validade`, `criado_em`
) VALUES
-- Produtos com estoque bom
(@estoque_id_inicio + 0, @produto_id_inicio + 0, 500.000, 'LOTE-2024-001', @fornecedor_id_inicio + 0, 'NF-001234', 4.50, 2250.00, '2025-12-31', NOW()),
(@estoque_id_inicio + 1, @produto_id_inicio + 1, 400.000, 'LOTE-2024-002', @fornecedor_id_inicio + 0, 'NF-001235', 8.00, 3200.00, '2025-12-31', NOW()),
(@estoque_id_inicio + 2, @produto_id_inicio + 2, 300.000, 'LOTE-2024-003', @fornecedor_id_inicio + 0, 'NF-001236', 5.50, 1650.00, '2025-12-31', NOW()),
(@estoque_id_inicio + 3, @produto_id_inicio + 5, 150.000, 'LOTE-2024-004', @fornecedor_id_inicio + 1, 'NF-002345', 28.00, 4200.00, '2025-01-15', NOW()),
(@estoque_id_inicio + 4, @produto_id_inicio + 6, 200.000, 'LOTE-2024-005', @fornecedor_id_inicio + 1, 'NF-002346', 12.00, 2400.00, '2025-01-20', NOW()),
(@estoque_id_inicio + 5, @produto_id_inicio + 8, 1000.000, 'LOTE-2024-006', @fornecedor_id_inicio + 3, 'NF-003456', 4.20, 4200.00, '2025-01-10', NOW()),
(@estoque_id_inicio + 6, @produto_id_inicio + 11, 300.000, 'LOTE-2024-007', @fornecedor_id_inicio + 2, 'NF-004567', 3.50, 1050.00, '2025-01-25', NOW()),
(@estoque_id_inicio + 7, @produto_id_inicio + 16, 200.000, 'LOTE-2024-008', @fornecedor_id_inicio + 0, 'NF-001237', 8.50, 1700.00, '2026-06-30', NOW()),

-- Produtos com estoque baixo (abaixo do mínimo)
(@estoque_id_inicio + 8, @produto_id_inicio + 4, 15.000, 'LOTE-2024-009', @fornecedor_id_inicio + 0, 'NF-001238', 3.80, 57.00, '2025-12-31', NOW()),
(@estoque_id_inicio + 9, @produto_id_inicio + 10, 5.000, 'LOTE-2024-010', @fornecedor_id_inicio + 3, 'NF-003457', 18.00, 90.00, '2025-02-28', NOW()),
(@estoque_id_inicio + 10, @produto_id_inicio + 12, 8.000, 'LOTE-2024-011', @fornecedor_id_inicio + 2, 'NF-004568', 4.00, 32.00, '2025-01-20', NOW());

-- ============================================================
-- 4. CARDÁPIOS
-- ============================================================
-- Cardápio de Janeiro 2025 - Aprovado
INSERT IGNORE INTO `cardapio` (
    `id`, `escola_id`, `mes`, `ano`, `status`, `criado_por`, `criado_em`, `aprovado_por`, `data_aprovacao`
) VALUES
(@cardapio_id_inicio + 0, @escola_id, 1, 2025, 'APROVADO', @usuario_id, '2024-12-15 10:00:00', @usuario_id, '2024-12-20 14:30:00');

-- Itens do Cardápio de Janeiro
INSERT IGNORE INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES
(@cardapio_item_id_inicio + 0, @cardapio_id_inicio + 0, @produto_id_inicio + 0, 50.000),  -- Arroz
(@cardapio_item_id_inicio + 1, @cardapio_id_inicio + 0, @produto_id_inicio + 1, 30.000),  -- Feijão
(@cardapio_item_id_inicio + 2, @cardapio_id_inicio + 0, @produto_id_inicio + 5, 20.000),  -- Carne moída
(@cardapio_item_id_inicio + 3, @cardapio_id_inicio + 0, @produto_id_inicio + 11, 25.000),  -- Batata
(@cardapio_item_id_inicio + 4, @cardapio_id_inicio + 0, @produto_id_inicio + 8, 100.000), -- Leite
(@cardapio_item_id_inicio + 5, @cardapio_id_inicio + 0, @produto_id_inicio + 14, 30.000);  -- Banana

-- Cardápio de Fevereiro 2025 - Rascunho
INSERT IGNORE INTO `cardapio` (
    `id`, `escola_id`, `mes`, `ano`, `status`, `criado_por`, `criado_em`
) VALUES
(@cardapio_id_inicio + 1, @escola_id, 2, 2025, 'RASCUNHO', @usuario_id, NOW());

-- Itens do Cardápio de Fevereiro
INSERT IGNORE INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES
(@cardapio_item_id_inicio + 6, @cardapio_id_inicio + 1, @produto_id_inicio + 0, 55.000),
(@cardapio_item_id_inicio + 7, @cardapio_id_inicio + 1, @produto_id_inicio + 1, 32.000),
(@cardapio_item_id_inicio + 8, @cardapio_id_inicio + 1, @produto_id_inicio + 6, 18.000),  -- Frango
(@cardapio_item_id_inicio + 9, @cardapio_id_inicio + 1, @produto_id_inicio + 2, 20.000),  -- Macarrão
(@cardapio_item_id_inicio + 10, @cardapio_id_inicio + 1, @produto_id_inicio + 13, 20.000),  -- Tomate
(@cardapio_item_id_inicio + 11, @cardapio_id_inicio + 1, @produto_id_inicio + 8, 110.000);

-- Cardápio de Março 2025 - Aprovado
INSERT IGNORE INTO `cardapio` (
    `id`, `escola_id`, `mes`, `ano`, `status`, `criado_por`, `criado_em`, `aprovado_por`, `data_aprovacao`
) VALUES
(@cardapio_id_inicio + 2, @escola_id, 3, 2025, 'APROVADO', @usuario_id, '2025-02-15 10:00:00', @usuario_id, '2025-02-20 14:30:00');

-- Itens do Cardápio de Março
INSERT IGNORE INTO `cardapio_item` (`id`, `cardapio_id`, `produto_id`, `quantidade`) VALUES
(@cardapio_item_id_inicio + 12, @cardapio_id_inicio + 2, @produto_id_inicio + 0, 50.000),
(@cardapio_item_id_inicio + 13, @cardapio_id_inicio + 2, @produto_id_inicio + 1, 30.000),
(@cardapio_item_id_inicio + 14, @cardapio_id_inicio + 2, @produto_id_inicio + 7, 15.000),  -- Salsicha
(@cardapio_item_id_inicio + 15, @cardapio_id_inicio + 2, @produto_id_inicio + 2, 25.000),
(@cardapio_item_id_inicio + 16, @cardapio_id_inicio + 2, @produto_id_inicio + 15, 35.000),  -- Laranja
(@cardapio_item_id_inicio + 17, @cardapio_id_inicio + 2, @produto_id_inicio + 8, 100.000);

-- ============================================================
-- 5. CONSUMO DIÁRIO
-- ============================================================

INSERT IGNORE INTO `consumo_diario` (
    `id`, `escola_id`, `turma_id`, `data`, `turno`, 
    `total_alunos`, `alunos_atendidos`, `observacoes`, `registrado_por`, `registrado_em`
) VALUES
-- Consumos da última semana
(@consumo_id_inicio + 0, @escola_id, NULL, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'MANHA', 150, 145, 'Consumo normal', @usuario_id, DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(@consumo_id_inicio + 1, @escola_id, NULL, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'TARDE', 120, 118, NULL, @usuario_id, DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(@consumo_id_inicio + 2, @escola_id, NULL, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'MANHA', 150, 148, 'Alguns alunos faltaram', @usuario_id, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(@consumo_id_inicio + 3, @escola_id, NULL, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'TARDE', 120, 120, 'Todos os alunos atendidos', @usuario_id, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(@consumo_id_inicio + 4, @escola_id, NULL, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'MANHA', 150, 142, NULL, @usuario_id, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(@consumo_id_inicio + 5, @escola_id, NULL, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'TARDE', 120, 115, NULL, @usuario_id, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(@consumo_id_inicio + 6, @escola_id, NULL, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'MANHA', 150, 150, 'Consumo completo', @usuario_id, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(@consumo_id_inicio + 7, @escola_id, NULL, CURDATE(), 'TARDE', 120, 118, NULL, @usuario_id, NOW());

-- Itens de Consumo (exemplo para alguns consumos)
INSERT IGNORE INTO `consumo_item` (`consumo_diario_id`, `produto_id`, `quantidade`, `unidade_medida`) VALUES
(@consumo_id_inicio + 0, @produto_id_inicio + 0, 15.500, 'KG'),
(@consumo_id_inicio + 0, @produto_id_inicio + 1, 9.200, 'KG'),
(@consumo_id_inicio + 0, @produto_id_inicio + 5, 6.000, 'KG'),
(@consumo_id_inicio + 0, @produto_id_inicio + 8, 30.000, 'L'),
(@consumo_id_inicio + 1, @produto_id_inicio + 0, 12.000, 'KG'),
(@consumo_id_inicio + 1, @produto_id_inicio + 1, 7.500, 'KG'),
(@consumo_id_inicio + 1, @produto_id_inicio + 6, 5.500, 'KG'),
(@consumo_id_inicio + 1, @produto_id_inicio + 8, 25.000, 'L'),
(@consumo_id_inicio + 6, @produto_id_inicio + 0, 15.800, 'KG'),
(@consumo_id_inicio + 6, @produto_id_inicio + 1, 9.500, 'KG'),
(@consumo_id_inicio + 6, @produto_id_inicio + 5, 6.200, 'KG'),
(@consumo_id_inicio + 6, @produto_id_inicio + 8, 30.000, 'L');

-- ============================================================
-- 6. DESPERDÍCIO
-- ============================================================

INSERT IGNORE INTO `desperdicio` (
    `id`, `escola_id`, `data`, `turno`, `produto_id`, 
    `quantidade`, `unidade_medida`, `peso_kg`, 
    `motivo`, `motivo_detalhado`, `observacoes`, `registrado_por`, `registrado_em`
    
) VALUES
(@desperdicio_id_inicio + 0, @escola_id, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'MANHA', @produto_id_inicio + 0, 2.500, 'KG', 2.500, 
 'EXCESSO_PREPARO', 'Foi preparado mais arroz do que necessário', 'Reduzir quantidade na próxima vez', @usuario_id, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),

(@desperdicio_id_inicio + 1, @escola_id, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'TARDE', @produto_id_inicio + 1, 1.200, 'KG', 1.200,
 'REJEICAO_ALUNOS', 'Alunos não gostaram do feijão', 'Verificar tempero e qualidade', @usuario_id, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),

(@desperdicio_id_inicio + 2, @escola_id, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'MANHA', @produto_id_inicio + 11, 3.000, 'KG', 3.000,
 'VALIDADE_VENCIDA', 'Batatas começaram a estragar', 'Verificar estoque com mais frequência', @usuario_id, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),

(@desperdicio_id_inicio + 3, @escola_id, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'TARDE', @produto_id_inicio + 8, 5.000, 'L', 5.000,
 'PREPARO_INCORRETO', 'Leite foi preparado em excesso', NULL, @usuario_id, DATE_SUB(CURDATE(), INTERVAL 1 DAY));

-- ============================================================
-- 7. PEDIDOS DE COMPRA
-- ============================================================

INSERT IGNORE INTO `pedido_cesta` (
    `id`, `escola_id`, `mes`, `nutricionista_id`, `status`, 
    `data_criacao`, `data_envio`, `data_aprovacao`, `aprovado_por`, `observacoes`
) VALUES
-- Pedido pendente de aprovação
(@pedido_id_inicio + 0, @escola_id, 4, NULL, 'ENVIADO', 
 NOW(), NOW(), NULL, NULL, 
 'Pedido para o mês de abril - aguardando aprovação do ADM_MERENDA'),

-- Pedido aprovado
(@pedido_id_inicio + 1, @escola_id, 5, NULL, 'APROVADO',
 DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), @usuario_id,
 'Pedido aprovado e enviado para fornecedor'),

-- Pedido rejeitado
(@pedido_id_inicio + 2, @escola_id, 6, NULL, 'REJEITADO',
 DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY), NULL, NULL,
 'Pedido rejeitado - valores acima do orçamento');

-- Itens dos Pedidos
INSERT IGNORE INTO `pedido_item` (`id`, `pedido_id`, `produto_id`, `quantidade_solicitada`, `quantidade_atendida`) VALUES
-- Itens do pedido (pendente)
(@pedido_item_id_inicio + 0, @pedido_id_inicio + 0, @produto_id_inicio + 0, 60, NULL),
(@pedido_item_id_inicio + 1, @pedido_id_inicio + 0, @produto_id_inicio + 1, 35, NULL),
(@pedido_item_id_inicio + 2, @pedido_id_inicio + 0, @produto_id_inicio + 5, 25, NULL),
(@pedido_item_id_inicio + 3, @pedido_id_inicio + 0, @produto_id_inicio + 8, 120, NULL),

-- Itens do pedido (aprovado)
(@pedido_item_id_inicio + 4, @pedido_id_inicio + 1, @produto_id_inicio + 0, 55, 55),
(@pedido_item_id_inicio + 5, @pedido_id_inicio + 1, @produto_id_inicio + 1, 33, 33),
(@pedido_item_id_inicio + 6, @pedido_id_inicio + 1, @produto_id_inicio + 6, 20, 20),
(@pedido_item_id_inicio + 7, @pedido_id_inicio + 1, @produto_id_inicio + 8, 115, 115),

-- Itens do pedido (rejeitado)
(@pedido_item_id_inicio + 8, @pedido_id_inicio + 2, @produto_id_inicio + 0, 100, NULL),
(@pedido_item_id_inicio + 9, @pedido_id_inicio + 2, @produto_id_inicio + 1, 60, NULL),
(@pedido_item_id_inicio + 10, @pedido_id_inicio + 2, @produto_id_inicio + 5, 50, NULL);

-- ============================================================
-- 8. ENTREGAS
-- ============================================================

INSERT IGNORE INTO `entrega` (
    `id`, `pedido_cesta_id`, `escola_id`, `fornecedor_id`,
    `data_prevista`, `data_entrega`, `status`,
    `transportadora`, `nota_fiscal`, `observacoes`, `recebido_por`, `registrado_por`, `registrado_em`
) VALUES
-- Entrega agendada
(@entrega_id_inicio + 0, @pedido_id_inicio + 1, @escola_id, @fornecedor_id_inicio + 0,
 DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL, 'AGENDADA',
 'Transportadora Rápida', NULL, 'Entrega agendada para próxima semana', NULL, @usuario_id, NOW()),

-- Entrega em trânsito
(@entrega_id_inicio + 1, @pedido_id_inicio + 1, @escola_id, @fornecedor_id_inicio + 1,
 DATE_SUB(CURDATE(), INTERVAL 2 DAY), NULL, 'EM_TRANSITO',
 'Logística Express', 'NF-005678', 'Saiu do fornecedor hoje', NULL, @usuario_id, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),

-- Entrega concluída
(@entrega_id_inicio + 2, @pedido_id_inicio + 1, @escola_id, @fornecedor_id_inicio + 3,
 DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'ENTREGUE',
 'Transportes Maranguape', 'NF-004789', 'Entrega realizada com sucesso', @usuario_id, @usuario_id, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),

-- Entrega atrasada
(@entrega_id_inicio + 3, @pedido_id_inicio + 1, @escola_id, @fornecedor_id_inicio + 2,
 DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL, 'ATRASADA',
 'Transportadora Lenta', 'NF-006789', 'Entrega atrasada - contatar fornecedor', NULL, @usuario_id, DATE_SUB(CURDATE(), INTERVAL 5 DAY));

-- Itens das Entregas
INSERT IGNORE INTO `entrega_item` (`id`, `entrega_id`, `produto_id`, `quantidade_solicitada`, `quantidade_entregue`, `observacoes`) VALUES
-- Itens da entrega (agendada)
(@entrega_item_id_inicio + 0, @entrega_id_inicio + 0, @produto_id_inicio + 0, 55, NULL, NULL),
(@entrega_item_id_inicio + 1, @entrega_id_inicio + 0, @produto_id_inicio + 1, 33, NULL, NULL),

-- Itens da entrega (em trânsito)
(@entrega_item_id_inicio + 2, @entrega_id_inicio + 1, @produto_id_inicio + 6, 20, NULL, NULL),
(@entrega_item_id_inicio + 3, @entrega_id_inicio + 1, @produto_id_inicio + 8, 115, NULL, NULL),

-- Itens da entrega (entregue)
(@entrega_item_id_inicio + 4, @entrega_id_inicio + 2, @produto_id_inicio + 8, 100, 100, 'Quantidade correta'),
(@entrega_item_id_inicio + 5, @entrega_id_inicio + 2, @produto_id_inicio + 9, 15, 15, 'Entrega completa'),

-- Itens da entrega (atrasada)
(@entrega_item_id_inicio + 6, @entrega_id_inicio + 3, @produto_id_inicio + 11, 30, NULL, 'Aguardando entrega');

-- ============================================================
-- VERIFICAÇÃO DOS DADOS INSERIDOS
-- ============================================================

-- Verificar produtos criados
-- SELECT COUNT(*) as total_produtos FROM produto WHERE id >= 1001;

-- Verificar fornecedores criados
-- SELECT COUNT(*) as total_fornecedores FROM fornecedor WHERE id >= 1001;

-- Verificar estoque criado
-- SELECT COUNT(*) as total_estoque FROM estoque_central WHERE id >= 1001;

-- Verificar cardápios criados
-- SELECT COUNT(*) as total_cardapios FROM cardapio WHERE id >= 1001;

-- Verificar consumos criados
-- SELECT COUNT(*) as total_consumos FROM consumo_diario WHERE id >= 1001;

-- Verificar desperdícios criados
-- SELECT COUNT(*) as total_desperdicios FROM desperdicio WHERE id >= 1001;

-- Verificar pedidos criados
-- SELECT COUNT(*) as total_pedidos FROM pedido_cesta WHERE id >= 1001;

-- Verificar entregas criadas
-- SELECT COUNT(*) as total_entregas FROM entrega WHERE id >= 1001;

-- ============================================================
-- FIM DOS DADOS DE TESTE
-- ============================================================

