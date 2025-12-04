<?php
/**
 * FuncionarioModel - Model para gerenciamento de funcionários
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class FuncionarioModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todos os funcionários
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT f.*, p.nome, p.cpf, p.email, p.telefone, p.data_nascimento
                FROM funcionario f
                INNER JOIN pessoa p ON f.pessoa_id = p.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR f.matricula LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        if (!empty($filtros['cargo'])) {
            $sql .= " AND f.cargo = :cargo";
            $params[':cargo'] = $filtros['cargo'];
        }
        
        if (!empty($filtros['setor'])) {
            $sql .= " AND f.setor = :setor";
            $params[':setor'] = $filtros['setor'];
        }
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND f.ativo = :ativo";
            $params[':ativo'] = $filtros['ativo'];
        }
        
        $sql .= " ORDER BY p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca funcionário por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT f.*, p.*
                FROM funcionario f
                INNER JOIN pessoa p ON f.pessoa_id = p.id
                WHERE f.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo funcionário
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // 1. Criar pessoa
            $sqlPessoa = "INSERT INTO pessoa (cpf, nome, data_nascimento, sexo, email, telefone, tipo, criado_por)
                         VALUES (:cpf, :nome, :data_nascimento, :sexo, :email, :telefone, 'FUNCIONARIO', :criado_por)";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':cpf', $dados['cpf']);
            $stmtPessoa->bindParam(':nome', $dados['nome']);
            $stmtPessoa->bindParam(':data_nascimento', $dados['data_nascimento']);
            $stmtPessoa->bindParam(':sexo', $dados['sexo']);
            $stmtPessoa->bindParam(':email', $dados['email']);
            $stmtPessoa->bindParam(':telefone', $dados['telefone']);
            $stmtPessoa->bindParam(':criado_por', $_SESSION['usuario_id']);
            $stmtPessoa->execute();
            
            $pessoaId = $conn->lastInsertId();
            
            // 2. Criar funcionário
            $matricula = $dados['matricula'] ?? null;
            $cargo = $dados['cargo'] ?? '';
            $setor = $dados['setor'] ?? null;
            $dataAdmissao = $dados['data_admissao'] ?? date('Y-m-d');
            $criadoPor = $_SESSION['usuario_id'];
            
            $sqlFunc = "INSERT INTO funcionario (pessoa_id, matricula, cargo, setor, data_admissao, ativo, criado_por)
                       VALUES (:pessoa_id, :matricula, :cargo, :setor, :data_admissao, 1, :criado_por)";
            $stmtFunc = $conn->prepare($sqlFunc);
            $stmtFunc->bindParam(':pessoa_id', $pessoaId);
            $stmtFunc->bindParam(':matricula', $matricula);
            $stmtFunc->bindParam(':cargo', $cargo);
            $stmtFunc->bindParam(':setor', $setor);
            $stmtFunc->bindParam(':data_admissao', $dataAdmissao);
            $stmtFunc->bindParam(':criado_por', $criadoPor);
            $stmtFunc->execute();
            
            $funcionarioId = $conn->lastInsertId();
            
            $conn->commit();
            return ['success' => true, 'id' => $funcionarioId];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Atualiza funcionário
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $funcionario = $this->buscarPorId($id);
            if (!$funcionario) {
                throw new Exception('Funcionário não encontrado');
            }
            
            // 1. Atualizar pessoa
            $sqlPessoa = "UPDATE pessoa SET nome = :nome, data_nascimento = :data_nascimento,
                          sexo = :sexo, email = :email, telefone = :telefone
                          WHERE id = :pessoa_id";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':nome', $dados['nome']);
            $stmtPessoa->bindParam(':data_nascimento', $dados['data_nascimento']);
            $stmtPessoa->bindParam(':sexo', $dados['sexo']);
            $stmtPessoa->bindParam(':email', $dados['email']);
            $stmtPessoa->bindParam(':telefone', $dados['telefone']);
            $stmtPessoa->bindParam(':pessoa_id', $funcionario['pessoa_id']);
            $stmtPessoa->execute();
            
            // 2. Atualizar funcionário
            $sqlFunc = "UPDATE funcionario SET matricula = :matricula, cargo = :cargo,
                       setor = :setor, data_admissao = :data_admissao, ativo = :ativo
                       WHERE id = :id";
            $stmtFunc = $conn->prepare($sqlFunc);
            $stmtFunc->bindParam(':matricula', $dados['matricula'] ?? null);
            $stmtFunc->bindParam(':cargo', $dados['cargo']);
            $stmtFunc->bindParam(':setor', $dados['setor'] ?? null);
            $stmtFunc->bindParam(':data_admissao', $dados['data_admissao']);
            $stmtFunc->bindParam(':ativo', $dados['ativo'] ?? 1);
            $stmtFunc->bindParam(':id', $id);
            $stmtFunc->execute();
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Exclui funcionário
     */
    public function excluir($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE funcionario SET ativo = 0 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Lotar funcionário em escola
     */
    public function lotarEmEscola($funcionarioId, $escolaId, $setor = null) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO funcionario_lotacao (funcionario_id, escola_id, inicio, setor, criado_por)
                VALUES (:funcionario_id, :escola_id, CURDATE(), :setor, :criado_por)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':funcionario_id', $funcionarioId);
        $stmt->bindParam(':escola_id', $escolaId);
        $stmt->bindParam(':setor', $setor);
        $stmt->bindParam(':criado_por', $_SESSION['usuario_id']);
        
        return $stmt->execute();
    }
}

?>

