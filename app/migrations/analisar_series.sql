-- Script para analisar séries cadastradas no banco de dados
-- Execute este script para ver todas as séries e seus detalhes

-- Ver todas as séries cadastradas
SELECT 
    id,
    nome,
    codigo,
    nivel_ensino,
    ordem,
    idade_minima,
    idade_maxima,
    descricao,
    ativo,
    criado_em,
    atualizado_em
FROM serie
ORDER BY nivel_ensino ASC, ordem ASC;

-- Contar séries por nível de ensino
SELECT 
    nivel_ensino,
    COUNT(*) as total_series,
    GROUP_CONCAT(nome ORDER BY ordem SEPARATOR ', ') as series
FROM serie
WHERE ativo = 1
GROUP BY nivel_ensino
ORDER BY nivel_ensino;

-- Verificar se existem séries do Ensino Médio
SELECT 
    COUNT(*) as total_medio,
    GROUP_CONCAT(CONCAT(nome, ' (', codigo, ')') ORDER BY ordem SEPARATOR ', ') as series_medio
FROM serie
WHERE nivel_ensino = 'ENSINO_MEDIO' AND ativo = 1;

-- Verificar se existem séries do Ensino Fundamental
SELECT 
    COUNT(*) as total_fundamental,
    GROUP_CONCAT(CONCAT(nome, ' (', codigo, ')') ORDER BY ordem SEPARATOR ', ') as series_fundamental
FROM serie
WHERE nivel_ensino = 'ENSINO_FUNDAMENTAL' AND ativo = 1;

-- Verificar séries inativas
SELECT 
    id,
    nome,
    codigo,
    nivel_ensino,
    ativo
FROM serie
WHERE ativo = 0
ORDER BY nivel_ensino, ordem;


