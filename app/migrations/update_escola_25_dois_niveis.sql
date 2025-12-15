-- Atualizar escola ID 25 para ter os dois níveis de ensino
-- Execute este script no banco de dados

-- Atualizar escola 25 para ter Ensino Fundamental E Ensino Médio
UPDATE `escola` 
SET `nivel_ensino` = 'ENSINO_FUNDAMENTAL,ENSINO_MEDIO' 
WHERE `id` = 25;

-- Verificar se foi atualizado corretamente
SELECT 
    id, 
    nome, 
    nivel_ensino,
    FIND_IN_SET('ENSINO_FUNDAMENTAL', nivel_ensino) as tem_fundamental,
    FIND_IN_SET('ENSINO_MEDIO', nivel_ensino) as tem_medio
FROM escola 
WHERE id = 25;

