-- Adicionar todas as séries do Ensino Fundamental e Ensino Médio
-- Execute este script no banco de dados
-- Compatível com MariaDB 10.4.32
-- Baseado na estrutura do banco escola_merenda (27)
-- A tabela serie já possui: 1º, 2º e 3º Ano do Ensino Fundamental
-- Este script adiciona: 4º ao 9º Ano do Fundamental + 1º ao 3º Ano do Médio

-- Ensino Fundamental: 4º ao 9º ano (1º, 2º e 3º já existem)
INSERT INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) 
SELECT '4º Ano', '4ANO', 'ENSINO_FUNDAMENTAL', 4, 9, 10, 'Quarto ano do Ensino Fundamental', 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `serie` WHERE `codigo` = '4ANO');

INSERT INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) 
SELECT '5º Ano', '5ANO', 'ENSINO_FUNDAMENTAL', 5, 10, 11, 'Quinto ano do Ensino Fundamental', 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `serie` WHERE `codigo` = '5ANO');

INSERT INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) 
SELECT '6º Ano', '6ANO', 'ENSINO_FUNDAMENTAL', 6, 11, 12, 'Sexto ano do Ensino Fundamental', 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `serie` WHERE `codigo` = '6ANO');

INSERT INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) 
SELECT '7º Ano', '7ANO', 'ENSINO_FUNDAMENTAL', 7, 12, 13, 'Sétimo ano do Ensino Fundamental', 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `serie` WHERE `codigo` = '7ANO');

INSERT INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) 
SELECT '8º Ano', '8ANO', 'ENSINO_FUNDAMENTAL', 8, 13, 14, 'Oitavo ano do Ensino Fundamental', 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `serie` WHERE `codigo` = '8ANO');

INSERT INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) 
SELECT '9º Ano', '9ANO', 'ENSINO_FUNDAMENTAL', 9, 14, 15, 'Nono ano do Ensino Fundamental', 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `serie` WHERE `codigo` = '9ANO');

-- Ensino Médio: 1º ao 3º ano
INSERT INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) 
SELECT '1º Ano', '1MEDIO', 'ENSINO_MEDIO', 10, 15, 16, 'Primeiro ano do Ensino Médio', 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `serie` WHERE `codigo` = '1MEDIO');

INSERT INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) 
SELECT '2º Ano', '2MEDIO', 'ENSINO_MEDIO', 11, 16, 17, 'Segundo ano do Ensino Médio', 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `serie` WHERE `codigo` = '2MEDIO');

INSERT INTO `serie` (`nome`, `codigo`, `nivel_ensino`, `ordem`, `idade_minima`, `idade_maxima`, `descricao`, `ativo`, `criado_em`, `atualizado_em`, `criado_por`) 
SELECT '3º Ano', '3MEDIO', 'ENSINO_MEDIO', 12, 17, 18, 'Terceiro ano do Ensino Médio', 1, NOW(), NOW(), NULL
WHERE NOT EXISTS (SELECT 1 FROM `serie` WHERE `codigo` = '3MEDIO');

