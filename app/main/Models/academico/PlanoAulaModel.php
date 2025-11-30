<?php
/**
 * PlanoAulaModel - Model para gerenciamento de planos de aula
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class PlanoAulaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista planos de aula
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT pa.*, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome, 
                d.nome as disciplina_nome, p.nome as professor_nome
                FROM plano_aula pa
                INNER JOIN turma t ON pa.turma_id = t.id
                INNER JOIN disciplina d ON pa.disciplina_id = d.id
                INNER JOIN professor prof ON pa.professor_id = prof.id
                INNER JOIN pessoa p ON prof.pessoa_id = p.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['professor_id'])) {
            $sql .= " AND pa.professor_id = :professor_id";
            $params[':professor_id'] = $filtros['professor_id'];
        }
        
        if (!empty($filtros['turma_id'])) {
            $sql .= " AND pa.turma_id = :turma_id";
            $params[':turma_id'] = $filtros['turma_id'];
        }
        
        if (!empty($filtros['disciplina_id'])) {
            $sql .= " AND pa.disciplina_id = :disciplina_id";
            $params[':disciplina_id'] = $filtros['disciplina_id'];
        }
        
        if (!empty($filtros['status'])) {
            $sql .= " AND pa.status = :status";
            $params[':status'] = $filtros['status'];
        }
        
        if (!empty($filtros['data_aula'])) {
            $sql .= " AND pa.data_aula = :data_aula";
            $params[':data_aula'] = $filtros['data_aula'];
        }
        
        $sql .= " ORDER BY pa.data_aula DESC, pa.criado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo plano de aula
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO plano_aula (turma_id, disciplina_id, professor_id, titulo, conteudo, objetivos,
                metodologia, recursos, avaliacao, data_aula, bimestre, status, criado_por, criado_em)
                VALUES (:turma_id, :disciplina_id, :professor_id, :titulo, :conteudo, :objetivos,
                :metodologia, :recursos, :avaliacao, :data_aula, :bimestre, 'RASCUNHO', :criado_por, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $dados['turma_id']);
        $stmt->bindParam(':disciplina_id', $dados['disciplina_id']);
        $stmt->bindParam(':professor_id', $dados['professor_id']);
        $stmt->bindParam(':titulo', $dados['titulo']);
        $stmt->bindParam(':conteudo', $dados['conteudo'] ?? null);
        $stmt->bindParam(':objetivos', $dados['objetivos'] ?? null);
        $stmt->bindParam(':metodologia', $dados['metodologia'] ?? null);
        $stmt->bindParam(':recursos', $dados['recursos'] ?? null);
        $stmt->bindParam(':avaliacao', $dados['avaliacao'] ?? null);
        $stmt->bindParam(':data_aula', $dados['data_aula']);
        $stmt->bindParam(':bimestre', $dados['bimestre'] ?? null);
        $stmt->bindParam(':criado_por', $_SESSION['usuario_id']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar plano de aula'];
    }
    
    /**
     * Aprova plano de aula (GESTAO)
     */
    public function aprovar($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE plano_aula SET status = 'APROVADO', aprovado_por = :aprovado_por,
                data_aprovacao = NOW() WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':aprovado_por', $_SESSION['usuario_id']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}

?>

