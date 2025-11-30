<?php
/**
 * TurmaModel - Model para gerenciamento de turmas
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class TurmaModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todas as turmas
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT t.*, e.nome as escola_nome, s.nome as serie_nome,
                       COUNT(DISTINCT at.aluno_id) as total_alunos,
                       COUNT(DISTINCT tp.professor_id) as total_professores
                FROM turma t
                INNER JOIN escola e ON t.escola_id = e.id
                LEFT JOIN serie s ON t.serie_id = s.id
                LEFT JOIN aluno_turma at ON t.id = at.turma_id AND at.fim IS NULL
                LEFT JOIN turma_professor tp ON t.id = tp.turma_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND t.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['ano_letivo'])) {
            $sql .= " AND t.ano_letivo = :ano_letivo";
            $params[':ano_letivo'] = $filtros['ano_letivo'];
        }
        
        if (!empty($filtros['serie_id'])) {
            $sql .= " AND t.serie_id = :serie_id";
            $params[':serie_id'] = $filtros['serie_id'];
        }
        
        if (!empty($filtros['turno'])) {
            $sql .= " AND t.turno = :turno";
            $params[':turno'] = $filtros['turno'];
        }
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND t.ativo = :ativo";
            $params[':ativo'] = $filtros['ativo'];
        }
        
        $sql .= " GROUP BY t.id ORDER BY t.ano_letivo DESC, s.ordem ASC, t.letra ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca turma por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT t.*, e.nome as escola_nome, s.nome as serie_nome
                FROM turma t
                INNER JOIN escola e ON t.escola_id = e.id
                LEFT JOIN serie s ON t.serie_id = s.id
                WHERE t.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria nova turma
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO turma (escola_id, serie_id, ano_letivo, serie, letra, turno, capacidade, sala, ativo, criado_em)
                VALUES (:escola_id, :serie_id, :ano_letivo, :serie, :letra, :turno, :capacidade, :sala, 1, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':escola_id', $dados['escola_id']);
        $stmt->bindParam(':serie_id', $dados['serie_id'] ?? null);
        $stmt->bindParam(':ano_letivo', $dados['ano_letivo'] ?? date('Y'));
        $stmt->bindParam(':serie', $dados['serie'] ?? null);
        $stmt->bindParam(':letra', $dados['letra']);
        $stmt->bindParam(':turno', $dados['turno']);
        $stmt->bindParam(':capacidade', $dados['capacidade'] ?? null);
        $stmt->bindParam(':sala', $dados['sala'] ?? null);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar turma'];
    }
    
    /**
     * Atualiza turma
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE turma SET escola_id = :escola_id, serie_id = :serie_id, ano_letivo = :ano_letivo,
                serie = :serie, letra = :letra, turno = :turno, capacidade = :capacidade,
                sala = :sala, coordenador_id = :coordenador_id, ativo = :ativo
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':escola_id', $dados['escola_id']);
        $stmt->bindParam(':serie_id', $dados['serie_id'] ?? null);
        $stmt->bindParam(':ano_letivo', $dados['ano_letivo']);
        $stmt->bindParam(':serie', $dados['serie'] ?? null);
        $stmt->bindParam(':letra', $dados['letra']);
        $stmt->bindParam(':turno', $dados['turno']);
        $stmt->bindParam(':capacidade', $dados['capacidade'] ?? null);
        $stmt->bindParam(':sala', $dados['sala'] ?? null);
        $stmt->bindParam(':coordenador_id', $dados['coordenador_id'] ?? null);
        $stmt->bindParam(':ativo', $dados['ativo'] ?? 1);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Exclui turma
     */
    public function excluir($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE turma SET ativo = 0 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Atribui professor à turma
     */
    public function atribuirProfessor($turmaId, $professorId, $disciplinaId, $regime = 'REGULAR') {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO turma_professor (turma_id, professor_id, disciplina_id, inicio, regime, criado_em)
                VALUES (:turma_id, :professor_id, :disciplina_id, CURDATE(), :regime, NOW())
                ON DUPLICATE KEY UPDATE regime = :regime";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':professor_id', $professorId);
        $stmt->bindParam(':disciplina_id', $disciplinaId);
        $stmt->bindParam(':regime', $regime);
        
        return $stmt->execute();
    }
    
    /**
     * Remove professor da turma
     */
    public function removerProfessor($turmaId, $professorId, $disciplinaId) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE turma_professor SET fim = CURDATE() 
                WHERE turma_id = :turma_id AND professor_id = :professor_id 
                AND disciplina_id = :disciplina_id AND fim IS NULL";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':professor_id', $professorId);
        $stmt->bindParam(':disciplina_id', $disciplinaId);
        
        return $stmt->execute();
    }
}

?>

