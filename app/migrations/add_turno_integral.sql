-- Adicionar opção INTEGRAL ao campo turno
-- Execute este script no banco de dados
-- Compatível com MariaDB 10.4.32
-- Baseado na estrutura do banco escola_merenda (27)

-- Atualizar ENUM na tabela turma
ALTER TABLE `turma` 
MODIFY COLUMN `turno` ENUM('MANHA','TARDE','NOITE','INTEGRAL') DEFAULT NULL;

-- Atualizar ENUM na tabela consumo_diario
ALTER TABLE `consumo_diario` 
MODIFY COLUMN `turno` ENUM('MANHA','TARDE','NOITE','INTEGRAL') DEFAULT NULL;

-- Atualizar ENUM na tabela desperdicio
ALTER TABLE `desperdicio` 
MODIFY COLUMN `turno` ENUM('MANHA','TARDE','NOITE','INTEGRAL') DEFAULT NULL;


