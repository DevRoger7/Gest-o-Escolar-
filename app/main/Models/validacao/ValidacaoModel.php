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
        $tipoRegistro = isset($dados['tipo_registro']) ? $dados['tipo_registro'] : null;
        $registroId = isset($dados['registro_id']) ? $dados['registro_id'] : null;
        $observacoes = isset($dados['observacoes']) && $dados['observacoes'] !== '' ? $dados['observacoes'] : null;
        $stmt->bindParam(':tipo_registro', $tipoRegistro);
        $stmt->bindParam(':registro_id', $registroId);
        $stmt->bindParam(':observacoes', $observacoes);
        
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
            $validadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
            $obs = $observacoes ?? null;
            $stmt->bindParam(':validado_por', $validadoPor);
            $stmt->bindParam(':observacoes', $obs);
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
        $validadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
        $obs = $observacoes ?? null;
        $stmt->bindParam(':validado_por', $validadoPor);
        $stmt->bindParam(':observacoes', $obs);
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
            $validadoParam = $validado ? 1 : 0;
            $validadoPor = (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) ? (int)$_SESSION['usuario_id'] : null;
            $idParam = $registroId;
            $stmt->bindParam(':validado', $validadoParam, PDO::PARAM_BOOL);
            $stmt->bindParam(':validado_por', $validadoPor);
            $stmt->bindParam(':id', $idParam);
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
            $tipoRegistroFiltro = $filtros['tipo_registro'];
            $stmt->bindParam(':tipo_registro', $tipoRegistroFiltro);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

