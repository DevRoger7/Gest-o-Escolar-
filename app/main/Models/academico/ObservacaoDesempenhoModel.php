<?php
/**
 * ObservacaoDesempenhoModel - Model para observações de desempenho
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class ObservacaoDesempenhoModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Adiciona observação de desempenho
     */
    public function adicionar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO observacao_desempenho (aluno_id, turma_id, disciplina_id, professor_id, tipo,
                titulo, observacao, data, bimestre, visivel_responsavel, criado_por, criado_em)
                VALUES (:aluno_id, :turma_id, :disciplina_id, :professor_id, :tipo,
                :titulo, :observacao, :data, :bimestre, :visivel_responsavel, :criado_por, NOW())";
        
        $stmt = $conn->prepare($sql);
        $alunoId = $dados['aluno_id'];
        $turmaId = $dados['turma_id'];
        $disciplinaId = $dados['disciplina_id'] ?? null;
        $professorId = $dados['professor_id'];
        $tipo = $dados['tipo'] ?? 'OUTROS';
        $titulo = $dados['titulo'] ?? null;
        $observacao = $dados['observacao'];
        $data = $dados['data'] ?? date('Y-m-d');
        $bimestre = $dados['bimestre'] ?? null;
        $visivelResponsavel = $dados['visivel_responsavel'] ?? 1;
        $criadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;

        $stmt->bindParam(':aluno_id', $alunoId);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':disciplina_id', $disciplinaId);
        $stmt->bindParam(':professor_id', $professorId);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':observacao', $observacao);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':bimestre', $bimestre);
        $stmt->bindParam(':visivel_responsavel', $visivelResponsavel, PDO::PARAM_BOOL);
        $stmt->bindParam(':criado_por', $criadoPor);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao adicionar observação'];
    }
    
    /**
     * Lista observações de aluno
     */
    public function listarPorAluno($alunoId, $visivelResponsavel = null) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT od.*, d.nome as disciplina_nome, p.nome as professor_nome
                FROM observacao_desempenho od
                LEFT JOIN disciplina d ON od.disciplina_id = d.id
                INNER JOIN professor prof ON od.professor_id = prof.id
                INNER JOIN pessoa p ON prof.pessoa_id = p.id
                WHERE od.aluno_id = :aluno_id";
        
        $params = [':aluno_id' => $alunoId];
        
        if ($visivelResponsavel !== null) {
            $sql .= " AND od.visivel_responsavel = :visivel_responsavel";
            $params[':visivel_responsavel'] = $visivelResponsavel;
        }
        
        $sql .= " ORDER BY od.data DESC, od.criado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

