<?php
/**
 * ValidacaoModel - Model para validação de informações
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class ValidacaoModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Cria nova validação
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO validacao (tipo_registro, registro_id, status, observacoes, criado_em)
                VALUES (:tipo_registro, :registro_id, 'PENDENTE', :observacoes, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':tipo_registro', $dados['tipo_registro']);
        $stmt->bindParam(':registro_id', $dados['registro_id']);
        $stmt->bindParam(':observacoes', $dados['observacoes'] ?? null);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar validação'];
    }
    
    /**
     * Aprova validação
     */
    public function aprovar($id, $observacoes = null) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Buscar validação
            $sql = "SELECT * FROM validacao WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $validacao = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$validacao) {
                throw new Exception('Validação não encontrada');
            }
            
            // Atualizar validação
            $sql = "UPDATE validacao SET status = 'APROVADO', validado_por = :validado_por,
                    data_validacao = NOW(), observacoes = :observacoes WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':validado_por', $_SESSION['usuario_id']);
            $stmt->bindParam(':observacoes', $observacoes);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Atualizar registro validado
            $this->atualizarRegistroValidado($validacao['tipo_registro'], $validacao['registro_id'], true);
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Rejeita validação
     */
    public function rejeitar($id, $observacoes) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE validacao SET status = 'REJEITADO', validado_por = :validado_por,
                data_validacao = NOW(), observacoes = :observacoes WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':validado_por', $_SESSION['usuario_id']);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Atualiza registro validado
     */
    private function atualizarRegistroValidado($tipo, $registroId, $validado) {
        $conn = $this->db->getConnection();
        
        $tabelas = [
            'NOTA' => 'nota',
            'FREQUENCIA' => 'frequencia',
            'PLANO_AULA' => 'plano_aula',
            'OBSERVACAO' => 'observacao_desempenho',
            'COMUNICADO' => 'comunicado',
            'CARDAPIO' => 'cardapio',
            'PEDIDO' => 'pedido_cesta'
        ];
        
        if (isset($tabelas[$tipo])) {
            $tabela = $tabelas[$tipo];
            $sql = "UPDATE $tabela SET validado = :validado, validado_por = :validado_por,
                    data_validacao = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':validado', $validado, PDO::PARAM_BOOL);
            $stmt->bindParam(':validado_por', $_SESSION['usuario_id']);
            $stmt->bindParam(':id', $registroId);
            $stmt->execute();
        }
    }
    
    /**
     * Lista validações pendentes
     */
    public function listarPendentes($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT v.*, u.username as validado_por_nome
                FROM validacao v
                LEFT JOIN usuario u ON v.validado_por = u.id
                WHERE v.status = 'PENDENTE'";
        
        if (!empty($filtros['tipo_registro'])) {
            $sql .= " AND v.tipo_registro = :tipo_registro";
        }
        
        $sql .= " ORDER BY v.criado_em ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($filtros['tipo_registro'])) {
            $stmt->bindParam(':tipo_registro', $filtros['tipo_registro']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

