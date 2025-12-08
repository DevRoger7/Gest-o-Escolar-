<?php
/**
 * FornecedorModel - Model para gerenciamento de fornecedores
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class FornecedorModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Busca fornecedor por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        $sql = "SELECT * FROM fornecedor WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lista fornecedores
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM fornecedor WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['busca'])) {
            // Remover formatação da busca para comparar com CNPJ/CPF sem formatação
            $buscaLimpa = preg_replace('/[^0-9]/', '', $filtros['busca']);
            $buscaTexto = $filtros['busca'];
            
            // Buscar por nome, razão social ou CNPJ (com ou sem formatação)
            if (strlen($buscaLimpa) >= 11) {
                // Se a busca tiver muitos números, buscar também no CNPJ sem formatação
                $sql .= " AND (nome LIKE :busca OR razao_social LIKE :busca OR cnpj LIKE :busca OR REPLACE(REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', ''), ' ', '') LIKE :busca_limpa)";
                $params[':busca'] = "%{$buscaTexto}%";
                $params[':busca_limpa'] = "%{$buscaLimpa}%";
            } else {
                // Se for texto ou poucos números, buscar normalmente
                $sql .= " AND (nome LIKE :busca OR razao_social LIKE :busca OR cnpj LIKE :busca)";
                $params[':busca'] = "%{$buscaTexto}%";
            }
        }
        
        if (!empty($filtros['tipo_fornecedor'])) {
            $sql .= " AND tipo_fornecedor = :tipo_fornecedor";
            $params[':tipo_fornecedor'] = $filtros['tipo_fornecedor'];
        }
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND ativo = :ativo";
            $params[':ativo'] = $filtros['ativo'];
        }
        
        $sql .= " ORDER BY nome ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo fornecedor
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO fornecedor (nome, razao_social, cnpj, inscricao_estadual, endereco, numero,
                complemento, bairro, cidade, estado, cep, telefone, telefone_secundario, email, contato,
                tipo_fornecedor, observacoes, ativo, criado_por, criado_em)
                VALUES (:nome, :razao_social, :cnpj, :inscricao_estadual, :endereco, :numero,
                :complemento, :bairro, :cidade, :estado, :cep, :telefone, :telefone_secundario, :email, :contato,
                :tipo_fornecedor, :observacoes, 1, :criado_por, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':razao_social', $dados['razao_social'] ?? null);
        $stmt->bindParam(':cnpj', $dados['cnpj'] ?? null);
        $stmt->bindParam(':inscricao_estadual', $dados['inscricao_estadual'] ?? null);
        $stmt->bindParam(':endereco', $dados['endereco'] ?? null);
        $stmt->bindParam(':numero', $dados['numero'] ?? null);
        $stmt->bindParam(':complemento', $dados['complemento'] ?? null);
        $stmt->bindParam(':bairro', $dados['bairro'] ?? null);
        $stmt->bindParam(':cidade', $dados['cidade'] ?? null);
        $stmt->bindParam(':estado', $dados['estado'] ?? null);
        $stmt->bindParam(':cep', $dados['cep'] ?? null);
        $stmt->bindParam(':telefone', $dados['telefone'] ?? null);
        $stmt->bindParam(':telefone_secundario', $dados['telefone_secundario'] ?? null);
        $stmt->bindParam(':email', $dados['email'] ?? null);
        $stmt->bindParam(':contato', $dados['contato'] ?? null);
        $stmt->bindParam(':tipo_fornecedor', $dados['tipo_fornecedor'] ?? 'ALIMENTOS');
        $stmt->bindParam(':observacoes', $dados['observacoes'] ?? null);
        $stmt->bindParam(':criado_por', $_SESSION['usuario_id']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Erro ao criar fornecedor'];
    }
    
    /**
     * Atualiza fornecedor
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE fornecedor SET nome = :nome, razao_social = :razao_social, cnpj = :cnpj,
                inscricao_estadual = :inscricao_estadual, endereco = :endereco, numero = :numero,
                complemento = :complemento, bairro = :bairro, cidade = :cidade, estado = :estado,
                cep = :cep, telefone = :telefone, telefone_secundario = :telefone_secundario,
                email = :email, contato = :contato, tipo_fornecedor = :tipo_fornecedor,
                observacoes = :observacoes, ativo = :ativo
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':razao_social', $dados['razao_social'] ?? null);
        $stmt->bindParam(':cnpj', $dados['cnpj'] ?? null);
        $stmt->bindParam(':inscricao_estadual', $dados['inscricao_estadual'] ?? null);
        $stmt->bindParam(':endereco', $dados['endereco'] ?? null);
        $stmt->bindParam(':numero', $dados['numero'] ?? null);
        $stmt->bindParam(':complemento', $dados['complemento'] ?? null);
        $stmt->bindParam(':bairro', $dados['bairro'] ?? null);
        $stmt->bindParam(':cidade', $dados['cidade'] ?? null);
        $stmt->bindParam(':estado', $dados['estado'] ?? null);
        $stmt->bindParam(':cep', $dados['cep'] ?? null);
        $stmt->bindParam(':telefone', $dados['telefone'] ?? null);
        $stmt->bindParam(':telefone_secundario', $dados['telefone_secundario'] ?? null);
        $stmt->bindParam(':email', $dados['email'] ?? null);
        $stmt->bindParam(':contato', $dados['contato'] ?? null);
        $stmt->bindParam(':tipo_fornecedor', $dados['tipo_fornecedor']);
        $stmt->bindParam(':observacoes', $dados['observacoes'] ?? null);
        $stmt->bindParam(':ativo', $dados['ativo'] ?? 1);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}

?>

