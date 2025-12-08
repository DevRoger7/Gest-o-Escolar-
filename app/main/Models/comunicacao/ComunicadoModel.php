<?php
/**
 * ComunicadoModel - Model para gerenciamento de comunicados
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class ComunicadoModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista comunicados
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT c.*, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome, 
                p.nome as aluno_nome, e.nome as escola_nome,
                u.username as enviado_por_nome
                FROM comunicado c
                LEFT JOIN turma t ON c.turma_id = t.id
                LEFT JOIN aluno a ON c.aluno_id = a.id
                LEFT JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN escola e ON c.escola_id = e.id
                LEFT JOIN usuario u ON c.enviado_por = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['turma_id'])) {
            $sql .= " AND c.turma_id = :turma_id";
            $params[':turma_id'] = $filtros['turma_id'];
        }
        
        if (!empty($filtros['aluno_id'])) {
            $sql .= " AND c.aluno_id = :aluno_id";
            $params[':aluno_id'] = $filtros['aluno_id'];
        }
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND c.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['tipo'])) {
            $sql .= " AND c.tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }
        
        if (isset($filtros['lido'])) {
            $sql .= " AND c.lido = :lido";
            $params[':lido'] = $filtros['lido'];
        }
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND c.ativo = :ativo";
            $params[':ativo'] = $filtros['ativo'];
        }
        
        $sql .= " ORDER BY c.criado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo comunicado
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO comunicado (turma_id, aluno_id, escola_id, enviado_por, titulo, mensagem,
                tipo, prioridade, canal, criado_em)
                VALUES (:turma_id, :aluno_id, :escola_id, :enviado_por, :titulo, :mensagem,
                :tipo, :prioridade, :canal, NOW())";
        
        $stmt = $conn->prepare($sql);
        $turmaId = $dados['turma_id'] ?? null;
        $alunoId = $dados['aluno_id'] ?? null;
        $escolaId = $dados['escola_id'] ?? null;
        $enviadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        $titulo = $dados['titulo'];
        $mensagem = $dados['mensagem'];
        $tipo = $dados['tipo'] ?? 'GERAL';
        $prioridade = $dados['prioridade'] ?? 'NORMAL';
        $canal = $dados['canal'] ?? 'SISTEMA';

        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':aluno_id', $alunoId);
        $stmt->bindParam(':escola_id', $escolaId);
        $stmt->bindParam(':enviado_por', $enviadoPor);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':mensagem', $mensagem);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':prioridade', $prioridade);
        $stmt->bindParam(':canal', $canal);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar comunicado'];
    }
    
    /**
     * Marca comunicado como lido
     */
    public function marcarComoLido($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE comunicado SET lido = 1, lido_por = :lido_por, data_leitura = NOW()
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $lidoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        $idParam = $id;
        $stmt->bindParam(':lido_por', $lidoPor);
        $stmt->bindParam(':id', $idParam);
        
        return $stmt->execute();
    }
    
    /**
     * Adiciona resposta ao comunicado (RESPONSAVEL)
     */
    public function adicionarResposta($comunicadoId, $responsavelId, $resposta) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO comunicado_resposta (comunicado_id, responsavel_id, resposta, data_resposta, criado_em)
                VALUES (:comunicado_id, :responsavel_id, :resposta, NOW(), NOW())
                ON DUPLICATE KEY UPDATE resposta = :resposta, data_resposta = NOW()";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':comunicado_id', $comunicadoId);
        $stmt->bindParam(':responsavel_id', $responsavelId);
        $stmt->bindParam(':resposta', $resposta);
        
        return $stmt->execute();
    }
}

?>

