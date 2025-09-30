-- Script para criar a tabela professor_lotacao
-- Execute este script se a tabela não existir

CREATE TABLE IF NOT EXISTS professor_lotacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    escola_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
    ativo BOOLEAN DEFAULT TRUE,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (escola_id) REFERENCES escola(id) ON DELETE CASCADE,
    INDEX idx_professor_escola (professor_id, escola_id),
    INDEX idx_ativo (ativo),
    INDEX idx_data_inicio (data_inicio)
);

-- Comentários sobre a estrutura:
-- professor_id: referencia usuario.id onde role = 'PROFESSOR'
-- escola_id: referencia escola.id
-- data_inicio: data de início da lotação
-- data_fim: data de fim da lotação (NULL se ainda ativo)
-- ativo: indica se a lotação está ativa
-- observacoes: campo para observações sobre a lotação