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
        $escolaId = $dados['escola_id'];
        $serieId = (isset($dados['serie_id']) && $dados['serie_id'] !== '') ? $dados['serie_id'] : null;
        $anoLetivo = $dados['ano_letivo'] ?? date('Y');
        $serie = (isset($dados['serie']) && $dados['serie'] !== '') ? $dados['serie'] : null;
        $letra = $dados['letra'];
        $turno = $dados['turno'];
        $capacidade = (isset($dados['capacidade']) && $dados['capacidade'] !== '') ? $dados['capacidade'] : null;
        $sala = (isset($dados['sala']) && $dados['sala'] !== '') ? $dados['sala'] : null;

        $stmt->bindParam(':escola_id', $escolaId);
        $stmt->bindParam(':serie_id', $serieId);
        $stmt->bindParam(':ano_letivo', $anoLetivo);
        $stmt->bindParam(':serie', $serie);
        $stmt->bindParam(':letra', $letra);
        $stmt->bindParam(':turno', $turno);
        $stmt->bindParam(':capacidade', $capacidade);
        $stmt->bindParam(':sala', $sala);
        
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
        $escolaId = $dados['escola_id'];
        $serieId = (isset($dados['serie_id']) && $dados['serie_id'] !== '') ? $dados['serie_id'] : null;
        $anoLetivo = $dados['ano_letivo'];
        $serie = (isset($dados['serie']) && $dados['serie'] !== '') ? $dados['serie'] : null;
        $letra = $dados['letra'];
        $turno = $dados['turno'];
        $capacidade = (isset($dados['capacidade']) && $dados['capacidade'] !== '') ? $dados['capacidade'] : null;
        $sala = (isset($dados['sala']) && $dados['sala'] !== '') ? $dados['sala'] : null;
        $coordenadorId = (isset($dados['coordenador_id']) && $dados['coordenador_id'] !== '') ? $dados['coordenador_id'] : null;
        $ativo = $dados['ativo'] ?? 1;

        $stmt->bindParam(':escola_id', $escolaId);
        $stmt->bindParam(':serie_id', $serieId);
        $stmt->bindParam(':ano_letivo', $anoLetivo);
        $stmt->bindParam(':serie', $serie);
        $stmt->bindParam(':letra', $letra);
        $stmt->bindParam(':turno', $turno);
        $stmt->bindParam(':capacidade', $capacidade);
        $stmt->bindParam(':sala', $sala);
        $stmt->bindParam(':coordenador_id', $coordenadorId);
        $stmt->bindParam(':ativo', $ativo);
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
    
    /**
     * Busca alunos da turma
     */
    public function buscarAlunos($turmaId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT a.id, p.nome, p.cpf, a.matricula, at.inicio, at.status
                FROM aluno_turma at
                INNER JOIN aluno a ON at.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                WHERE at.turma_id = :turma_id AND at.fim IS NULL
                ORDER BY p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca professores da turma
     */
    public function buscarProfessores($turmaId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT tp.professor_id, tp.disciplina_id, p.nome, d.nome as disciplina_nome, tp.regime, tp.inicio
                FROM turma_professor tp
                INNER JOIN professor pr ON tp.professor_id = pr.id
                INNER JOIN pessoa p ON pr.pessoa_id = p.id
                LEFT JOIN disciplina d ON tp.disciplina_id = d.id
                WHERE tp.turma_id = :turma_id AND tp.fim IS NULL
                ORDER BY d.nome ASC, p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

