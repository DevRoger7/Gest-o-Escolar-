<?php
/**
 * FrequenciaModel - Model para gerenciamento de frequência
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class FrequenciaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registra frequência de aluno
     */
    public function registrar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO frequencia (aluno_id, turma_id, data, presenca, observacao, registrado_por, registrado_em)
                VALUES (:aluno_id, :turma_id, :data, :presenca, :observacao, :registrado_por, NOW())
                ON DUPLICATE KEY UPDATE presenca = :presenca, observacao = :observacao, atualizado_em = NOW()";
        
        $stmt = $conn->prepare($sql);
        $alunoId = $dados['aluno_id'];
        $turmaId = $dados['turma_id'];
        $data = $dados['data'];
        $presenca = isset($dados['presenca']) ? (int)$dados['presenca'] : 0;
        $observacao = $dados['observacao'] ?? null;
        $registradoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;

        $stmt->bindParam(':aluno_id', $alunoId);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':presenca', $presenca);
        $stmt->bindParam(':observacao', $observacao);
        $stmt->bindParam(':registrado_por', $registradoPor);
        
        return $stmt->execute();
    }
    
    /**
     * Registra frequência em lote (toda a turma)
     */
    public function registrarLote($turmaId, $data, $frequencias) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            foreach ($frequencias as $freq) {
                $this->registrar([
                    'aluno_id' => $freq['aluno_id'],
                    'turma_id' => $turmaId,
                    'data' => $data,
                    'presenca' => $freq['presenca'],
                    'observacao' => $freq['observacao'] ?? null
                ]);
            }
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Busca frequência de aluno
     */
    public function buscarPorAluno($alunoId, $turmaId = null, $periodoInicio = null, $periodoFim = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT f.*, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome
                FROM frequencia f
                LEFT JOIN turma t ON f.turma_id = t.id
                WHERE f.aluno_id = :aluno_id";
        
        $params = [':aluno_id' => $alunoId];
        
        if ($turmaId) {
            $sql .= " AND f.turma_id = :turma_id";
            $params[':turma_id'] = $turmaId;
        }
        
        if ($periodoInicio) {
            $sql .= " AND f.data >= :periodo_inicio";
            $params[':periodo_inicio'] = $periodoInicio;
        }
        
        if ($periodoFim) {
            $sql .= " AND f.data <= :periodo_fim";
            $params[':periodo_fim'] = $periodoFim;
        }
        
        $sql .= " ORDER BY f.data DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcula percentual de frequência
     */
    public function calcularPercentual($alunoId, $turmaId, $periodoInicio = null, $periodoFim = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT 
                    COUNT(*) as total_dias,
                    SUM(CASE WHEN presenca = 1 THEN 1 ELSE 0 END) as dias_presentes,
                    SUM(CASE WHEN presenca = 0 THEN 1 ELSE 0 END) as dias_faltas
                FROM frequencia
                WHERE aluno_id = :aluno_id AND turma_id = :turma_id";
        
        $params = [':aluno_id' => $alunoId, ':turma_id' => $turmaId];
        
        if ($periodoInicio) {
            $sql .= " AND data >= :periodo_inicio";
            $params[':periodo_inicio'] = $periodoInicio;
        }
        
        if ($periodoFim) {
            $sql .= " AND data <= :periodo_fim";
            $params[':periodo_fim'] = $periodoFim;
        }
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total_dias'] > 0) {
            $percentual = ($result['dias_presentes'] / $result['total_dias']) * 100;
        } else {
            $percentual = 0;
        }
        
        return [
            'total_dias' => $result['total_dias'],
            'dias_presentes' => $result['dias_presentes'],
            'dias_faltas' => $result['dias_faltas'],
            'percentual' => round($percentual, 2)
        ];
    }
    
    /**
     * Valida frequência (GESTAO)
     */
    public function validar($frequenciaId, $validado = true) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE frequencia SET validado = :validado, validado_por = :validado_por,
                data_validacao = NOW() WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $validadoParam = $validado ? 1 : 0;
        $validadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        $idParam = $frequenciaId;
        $stmt->bindParam(':validado', $validadoParam, PDO::PARAM_BOOL);
        $stmt->bindParam(':validado_por', $validadoPor);
        $stmt->bindParam(':id', $idParam);
        
        return $stmt->execute();
    }
}

?>

