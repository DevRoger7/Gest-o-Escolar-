-- Migração: Adicionar novos campos na tabela plano_aula
-- Data: 2025-01-XX
-- Descrição: Adiciona campos para Avaliação, Atividades Flexibilizadas, Observações Complementares,
--            Seções e Temas, Atividade Permanente, Habilidade, Competências (Socioemocional, Específica, Geral)

DELIMITER //

CREATE PROCEDURE AddCamposPlanoAula()
BEGIN
    -- Adicionar campo "atividades_flexibilizadas" se não existir
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'plano_aula' 
        AND COLUMN_NAME = 'atividades_flexibilizadas'
    ) THEN
        ALTER TABLE `plano_aula`
        ADD COLUMN `atividades_flexibilizadas` TEXT DEFAULT NULL
        AFTER `avaliacao`;
    END IF;
    
    -- Adicionar campo "observacoes_complementares" se não existir
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'plano_aula' 
        AND COLUMN_NAME = 'observacoes_complementares'
    ) THEN
        ALTER TABLE `plano_aula`
        ADD COLUMN `observacoes_complementares` TEXT DEFAULT NULL
        AFTER `observacoes`;
    END IF;
    
    -- Adicionar campo "secoes_temas" se não existir
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'plano_aula' 
        AND COLUMN_NAME = 'secoes_temas'
    ) THEN
        ALTER TABLE `plano_aula`
        ADD COLUMN `secoes_temas` TEXT DEFAULT NULL
        AFTER `observacoes_complementares`;
    END IF;
    
    -- Adicionar campo "atividade_permanente" se não existir
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'plano_aula' 
        AND COLUMN_NAME = 'atividade_permanente'
    ) THEN
        ALTER TABLE `plano_aula`
        ADD COLUMN `atividade_permanente` TEXT DEFAULT NULL
        AFTER `secoes_temas`;
    END IF;
    
    -- Adicionar campo "habilidades" se não existir
    -- Armazenará JSON ou texto com lista de habilidades
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'plano_aula' 
        AND COLUMN_NAME = 'habilidades'
    ) THEN
        ALTER TABLE `plano_aula`
        ADD COLUMN `habilidades` TEXT DEFAULT NULL
        AFTER `atividade_permanente`;
    END IF;
    
    -- Adicionar campo "competencias_socioemocionais" se não existir
    -- Armazenará JSON ou texto com lista de competências socioemocionais
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'plano_aula' 
        AND COLUMN_NAME = 'competencias_socioemocionais'
    ) THEN
        ALTER TABLE `plano_aula`
        ADD COLUMN `competencias_socioemocionais` TEXT DEFAULT NULL
        AFTER `habilidades`;
    END IF;
    
    -- Adicionar campo "competencias_especificas" se não existir
    -- Armazenará JSON ou texto com lista de competências específicas
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'plano_aula' 
        AND COLUMN_NAME = 'competencias_especificas'
    ) THEN
        ALTER TABLE `plano_aula`
        ADD COLUMN `competencias_especificas` TEXT DEFAULT NULL
        AFTER `competencias_socioemocionais`;
    END IF;
    
    -- Adicionar campo "competencias_gerais" se não existir
    -- Armazenará JSON ou texto com lista de competências gerais
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'plano_aula' 
        AND COLUMN_NAME = 'competencias_gerais'
    ) THEN
        ALTER TABLE `plano_aula`
        ADD COLUMN `competencias_gerais` TEXT DEFAULT NULL
        AFTER `competencias_especificas`;
    END IF;
    
    -- Adicionar campo "disciplinas_componentes" se não existir
    -- Para armazenar os componentes curriculares selecionados
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'plano_aula' 
        AND COLUMN_NAME = 'disciplinas_componentes'
    ) THEN
        ALTER TABLE `plano_aula`
        ADD COLUMN `disciplinas_componentes` TEXT DEFAULT NULL
        AFTER `competencias_gerais`;
    END IF;
END //

DELIMITER ;

-- Executar a procedure
CALL AddCamposPlanoAula();

-- Remover a procedure após execução
DROP PROCEDURE IF EXISTS AddCamposPlanoAula;

