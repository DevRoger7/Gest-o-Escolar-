-- Script de teste para verificar o valor de nivel_ensino
-- Execute este script para verificar se a escola tem os dois níveis configurados

-- Verificar escola ID 25
SELECT 
    id, 
    nome, 
    nivel_ensino,
    LENGTH(nivel_ensino) as tamanho,
    FIND_IN_SET('ENSINO_FUNDAMENTAL', nivel_ensino) as tem_fundamental,
    FIND_IN_SET('ENSINO_MEDIO', nivel_ensino) as tem_medio
FROM escola 
WHERE id = 25;

-- Para ver todas as escolas e seus níveis:
-- SELECT id, nome, nivel_ensino FROM escola WHERE ativo = 1;

