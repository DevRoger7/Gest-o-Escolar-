<?php
/**
 * RelatorioModel - Model para gerenciamento de relatórios
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class RelatorioModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Cria novo relatório
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO relatorio (tipo, subtipo, titulo, descricao, periodo_inicio, periodo_fim,
                escola_id, turma_id, parametros, status, gerado_por, gerado_em)
                VALUES (:tipo, :subtipo, :titulo, :descricao, :periodo_inicio, :periodo_fim,
                :escola_id, :turma_id, :parametros, 'GERANDO', :gerado_por, NOW())";
        
        $stmt = $conn->prepare($sql);
        $geradoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        $tipo = isset($dados['tipo']) ? $dados['tipo'] : null;
        $subtipo = isset($dados['subtipo']) && $dados['subtipo'] !== '' ? $dados['subtipo'] : null;
        $titulo = isset($dados['titulo']) ? $dados['titulo'] : null;
        $descricao = isset($dados['descricao']) && $dados['descricao'] !== '' ? $dados['descricao'] : null;
        $periodoInicio = isset($dados['periodo_inicio']) && $dados['periodo_inicio'] !== '' ? $dados['periodo_inicio'] : null;
        $periodoFim = isset($dados['periodo_fim']) && $dados['periodo_fim'] !== '' ? $dados['periodo_fim'] : null;
        $escolaId = isset($dados['escola_id']) && $dados['escola_id'] !== '' ? $dados['escola_id'] : null;
        $turmaId = isset($dados['turma_id']) && $dados['turma_id'] !== '' ? $dados['turma_id'] : null;
        $parametros = isset($dados['parametros']) && $dados['parametros'] !== '' ? $dados['parametros'] : null;
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':subtipo', $subtipo);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':periodo_inicio', $periodoInicio);
        $stmt->bindParam(':periodo_fim', $periodoFim);
        $stmt->bindParam(':escola_id', $escolaId);
        $stmt->bindParam(':turma_id', $turmaId);
        $stmt->bindParam(':parametros', $parametros);
        $stmt->bindParam(':gerado_por', $geradoPor);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar relatório'];
    }
    
    /**
     * Atualiza status do relatório
     */
    public function atualizarStatus($id, $status, $arquivoUrl = null) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE relatorio SET status = :status, arquivo_url = :arquivo_url,
                concluido_em = NOW() WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':arquivo_url', $arquivoUrl);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Lista relatórios
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT r.*, e.nome as escola_nome, 
                CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome, 
                u.username as gerado_por_nome
                FROM relatorio r
                LEFT JOIN escola e ON r.escola_id = e.id
                LEFT JOIN turma t ON r.turma_id = t.id
                LEFT JOIN usuario u ON r.gerado_por = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['tipo'])) {
            $sql .= " AND r.tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }
        
        if (!empty($filtros['status'])) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $filtros['status'];
        }
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND r.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['gerado_por'])) {
            $sql .= " AND r.gerado_por = :gerado_por";
            $params[':gerado_por'] = $filtros['gerado_por'];
        }
        
        $sql .= " ORDER BY r.gerado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

