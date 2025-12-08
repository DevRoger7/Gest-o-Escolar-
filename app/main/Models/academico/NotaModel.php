<?php
/**
 * NotaModel - Model para gerenciamento de notas
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class NotaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lança nota de aluno
     */
    public function lancar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO nota (avaliacao_id, disciplina_id, turma_id, aluno_id, nota, bimestre, recuperacao, comentario, lancado_por, lancado_em)
                VALUES (:avaliacao_id, :disciplina_id, :turma_id, :aluno_id, :nota, :bimestre, :recuperacao, :comentario, :lancado_por, NOW())";
        
        $stmt = $conn->prepare($sql);
        $avaliacaoId = (isset($dados['avaliacao_id']) && $dados['avaliacao_id'] !== '') ? $dados['avaliacao_id'] : null;
        $disciplinaId = $dados['disciplina_id'];
        $turmaId = $dados['turma_id'];
        $alunoId = $dados['aluno_id'];
        $notaValor = $dados['nota'];
        $bimestre = (isset($dados['bimestre']) && $dados['bimestre'] !== '') ? $dados['bimestre'] : null;
        $recuperacao = $dados['recuperacao'] ?? 0;
        $comentario = $dados['comentario'] ?? null;
        $lancadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;

        $stmt->bindParam(':avaliacao_id', $avaliacaoId);
        $stmt->bindParam(':disciplina_id', $disciplinaId);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':aluno_id', $alunoId);
        $stmt->bindParam(':nota', $notaValor);
        $stmt->bindParam(':bimestre', $bimestre);
        $stmt->bindParam(':recuperacao', $recuperacao, PDO::PARAM_BOOL);
        $stmt->bindParam(':comentario', $comentario);
        $stmt->bindParam(':lancado_por', $lancadoPor);
        
        return $stmt->execute();
    }
    
    /**
     * Lança notas em lote
     */
    public function lancarLote($notas) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            foreach ($notas as $nota) {
                $this->lancar($nota);
            }
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Busca notas de aluno
     */
    public function buscarPorAluno($alunoId, $turmaId = null, $disciplinaId = null, $bimestre = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT n.*, d.nome as disciplina_nome, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome, 
                a.titulo as avaliacao_titulo
                FROM nota n
                LEFT JOIN disciplina d ON n.disciplina_id = d.id
                LEFT JOIN turma t ON n.turma_id = t.id
                LEFT JOIN avaliacao a ON n.avaliacao_id = a.id
                WHERE n.aluno_id = :aluno_id";
        
        $params = [':aluno_id' => $alunoId];
        
        if ($turmaId) {
            $sql .= " AND n.turma_id = :turma_id";
            $params[':turma_id'] = $turmaId;
        }
        
        if ($disciplinaId) {
            $sql .= " AND n.disciplina_id = :disciplina_id";
            $params[':disciplina_id'] = $disciplinaId;
        }
        
        if ($bimestre) {
            $sql .= " AND n.bimestre = :bimestre";
            $params[':bimestre'] = $bimestre;
        }
        
        $sql .= " ORDER BY n.bimestre ASC, d.nome ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calcula média do aluno
     */
    public function calcularMedia($alunoId, $disciplinaId, $turmaId, $bimestre = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT AVG(nota) as media, COUNT(*) as total_notas
                FROM nota
                WHERE aluno_id = :aluno_id AND disciplina_id = :disciplina_id AND turma_id = :turma_id
                AND recuperacao = 0";
        
        $params = [
            ':aluno_id' => $alunoId,
            ':disciplina_id' => $disciplinaId,
            ':turma_id' => $turmaId
        ];
        
        if ($bimestre) {
            $sql .= " AND bimestre = :bimestre";
            $params[':bimestre'] = $bimestre;
        }
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'media' => round($result['media'] ?? 0, 2),
            'total_notas' => $result['total_notas'] ?? 0
        ];
    }
    
    /**
     * Atualiza nota
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE nota SET nota = :nota, bimestre = :bimestre, recuperacao = :recuperacao,
                comentario = :comentario, atualizado_em = NOW(), atualizado_por = :atualizado_por
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $notaValor = $dados['nota'];
        $bimestre = isset($dados['bimestre']) ? $dados['bimestre'] : null;
        $recuperacao = $dados['recuperacao'] ?? 0;
        $comentario = $dados['comentario'] ?? null;
        $atualizadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        $idParam = $id;
        $stmt->bindParam(':nota', $notaValor);
        $stmt->bindParam(':bimestre', $bimestre);
        $stmt->bindParam(':recuperacao', $recuperacao, PDO::PARAM_BOOL);
        $stmt->bindParam(':comentario', $comentario);
        $stmt->bindParam(':atualizado_por', $atualizadoPor);
        $stmt->bindParam(':id', $idParam);
        
        return $stmt->execute();
    }
    
    /**
     * Valida nota (GESTAO)
     */
    public function validar($notaId, $validado = true) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE nota SET validado = :validado, validado_por = :validado_por,
                data_validacao = NOW() WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $validadoParam = $validado ? 1 : 0;
        $validadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        $idParam = $notaId;
        $stmt->bindParam(':validado', $validadoParam, PDO::PARAM_BOOL);
        $stmt->bindParam(':validado_por', $validadoPor);
        $stmt->bindParam(':id', $idParam);
        
        return $stmt->execute();
    }
}

?>

