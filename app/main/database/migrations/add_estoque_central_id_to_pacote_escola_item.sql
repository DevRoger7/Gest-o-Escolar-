-- Adicionar coluna estoque_central_id na tabela pacote_escola_item
-- Execute este script no banco de dados para adicionar suporte a lotes específicos

ALTER TABLE pacote_escola_item 
ADD COLUMN estoque_central_id BIGINT(20) NULL AFTER produto_id;

ALTER TABLE pacote_escola_item 
ADD INDEX idx_estoque_central_id (estoque_central_id);

ALTER TABLE pacote_escola_item 
ADD CONSTRAINT fk_pacote_escola_item_estoque 
FOREIGN KEY (estoque_central_id) 
REFERENCES estoque_central(id) 
ON DELETE SET NULL;

