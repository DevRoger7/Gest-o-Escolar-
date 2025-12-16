<?php
/**
 * ResponsavelModel - Model para gerenciamento de responsáveis
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class ResponsavelModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todos os responsáveis com seus alunos associados
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT DISTINCT p.*, u.id as usuario_id, u.username, u.ativo as usuario_ativo,
                       COUNT(DISTINCT ar.aluno_id) as total_alunos
                FROM pessoa p
                INNER JOIN aluno_responsavel ar ON p.id = ar.responsavel_id
                LEFT JOIN usuario u ON u.pessoa_id = p.id AND u.role = 'RESPONSAVEL'
                WHERE p.tipo = 'RESPONSAVEL' AND ar.ativo = 1";
        
        $params = [];
        
        if (!empty($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR p.email LIKE :busca)";
            $params[':busca'] = "%{$filtros['busca']}%";
        }
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND ar.ativo = :ativo";
            $params[':ativo'] = $filtros['ativo'];
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca responsável por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT p.*, u.id as usuario_id, u.username, u.ativo as usuario_ativo
                FROM pessoa p
                LEFT JOIN usuario u ON u.pessoa_id = p.id AND u.role = 'RESPONSAVEL'
                WHERE p.id = :id AND p.tipo = 'RESPONSAVEL'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria novo responsável e usuário
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Verificar se CPF já existe (apenas registros ativos)
            $cpf = preg_replace('/[^0-9]/', '', $dados['cpf'] ?? '');
            $stmtChkCpf = $conn->prepare("SELECT id FROM pessoa WHERE cpf = :cpf AND (ativo = 1 OR ativo IS NULL) LIMIT 1");
            $stmtChkCpf->bindParam(':cpf', $cpf);
            $stmtChkCpf->execute();
            if ($stmtChkCpf->fetch()) {
                throw new Exception('CPF já cadastrado no sistema');
            }
            
            // Verificar se email já existe (apenas registros ativos)
            if (!empty($dados['email'])) {
                $stmtChkEmail = $conn->prepare("SELECT id FROM pessoa WHERE email = :email AND (ativo = 1 OR ativo IS NULL) LIMIT 1");
                $stmtChkEmail->bindParam(':email', $dados['email']);
                $stmtChkEmail->execute();
                if ($stmtChkEmail->fetch()) {
                    throw new Exception('Email já cadastrado no sistema');
                }
            }
            
            // 1. Criar pessoa
            $criadoPor = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
            
            $sqlPessoa = "INSERT INTO pessoa (cpf, nome, data_nascimento, sexo, email, telefone, 
                         endereco, numero, complemento, bairro, cidade, estado, cep, tipo, criado_por)
                         VALUES (:cpf, :nome, :data_nascimento, :sexo, :email, :telefone,
                         :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep, 'RESPONSAVEL', :criado_por)";
            
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':cpf', $cpf);
            $stmtPessoa->bindParam(':nome', $dados['nome']);
            $dataNasc = !empty($dados['data_nascimento']) ? $dados['data_nascimento'] : null;
            $stmtPessoa->bindParam(':data_nascimento', $dataNasc);
            $sexo = !empty($dados['sexo']) ? $dados['sexo'] : null;
            $stmtPessoa->bindParam(':sexo', $sexo);
            $email = !empty($dados['email']) ? $dados['email'] : null;
            $stmtPessoa->bindParam(':email', $email);
            $telefone = !empty($dados['telefone']) ? $dados['telefone'] : null;
            $stmtPessoa->bindParam(':telefone', $telefone);
            $endereco = isset($dados['endereco']) && !empty($dados['endereco']) ? $dados['endereco'] : null;
            $numero = isset($dados['numero']) && !empty($dados['numero']) ? $dados['numero'] : null;
            $complemento = isset($dados['complemento']) && !empty($dados['complemento']) ? $dados['complemento'] : null;
            $bairro = isset($dados['bairro']) && !empty($dados['bairro']) ? $dados['bairro'] : null;
            $cidade = isset($dados['cidade']) && !empty($dados['cidade']) ? $dados['cidade'] : null;
            $estado = isset($dados['estado']) && !empty($dados['estado']) ? $dados['estado'] : 'CE';
            $cep = isset($dados['cep']) && !empty($dados['cep']) ? $dados['cep'] : null;
            $stmtPessoa->bindParam(':endereco', $endereco);
            $stmtPessoa->bindParam(':numero', $numero);
            $stmtPessoa->bindParam(':complemento', $complemento);
            $stmtPessoa->bindParam(':bairro', $bairro);
            $stmtPessoa->bindParam(':cidade', $cidade);
            $stmtPessoa->bindParam(':estado', $estado);
            $stmtPessoa->bindParam(':cep', $cep);
            $stmtPessoa->bindParam(':criado_por', $criadoPor);
            $stmtPessoa->execute();
            
            $pessoaId = $conn->lastInsertId();
            
            // 2. Criar usuário
            $username = $dados['username'] ?? $this->gerarUsername($dados['nome'], $cpf);
            $senha = $dados['senha'] ?? $this->gerarSenhaPadrao();
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            
            $sqlUsuario = "INSERT INTO usuario (pessoa_id, username, senha_hash, role, ativo, email_verificado)
                          VALUES (:pessoa_id, :username, :senha_hash, 'RESPONSAVEL', 1, 0)";
            
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':pessoa_id', $pessoaId);
            $stmtUsuario->bindParam(':username', $username);
            $stmtUsuario->bindParam(':senha_hash', $senhaHash);
            $stmtUsuario->execute();
            
            $conn->commit();
            
            return [
                'success' => true,
                'pessoa_id' => $pessoaId,
                'usuario_id' => $conn->lastInsertId(),
                'username' => $username,
                'senha' => $senha
            ];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Erro ao criar responsável: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Associa responsável a um ou mais alunos
     */
    public function associarAlunos($responsavelId, $alunos, $parentesco = 'OUTRO', $principal = 0) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            $criadoPor = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
            
            foreach ($alunos as $alunoId) {
                $alunoId = (int)$alunoId;
                $responsavelId = (int)$responsavelId;
                
                // Verificar se já existe associação
                $stmtCheck = $conn->prepare("SELECT id FROM aluno_responsavel 
                                            WHERE aluno_id = :aluno_id AND responsavel_id = :responsavel_id");
                $stmtCheck->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
                $stmtCheck->bindParam(':responsavel_id', $responsavelId, PDO::PARAM_INT);
                $stmtCheck->execute();
                
                if (!$stmtCheck->fetch()) {
                    $sql = "INSERT INTO aluno_responsavel (aluno_id, responsavel_id, parentesco, principal, criado_por)
                           VALUES (:aluno_id, :responsavel_id, :parentesco, :principal, :criado_por)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':aluno_id', $alunoId, PDO::PARAM_INT);
                    $stmt->bindParam(':responsavel_id', $responsavelId, PDO::PARAM_INT);
                    $stmt->bindParam(':parentesco', $parentesco);
                    $stmt->bindParam(':principal', $principal, PDO::PARAM_INT);
                    $stmt->bindParam(':criado_por', $criadoPor);
                    $stmt->execute();
                }
            }
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Remove associação entre responsável e aluno
     */
    public function removerAssociacao($responsavelId, $alunoId) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE aluno_responsavel SET ativo = 0 
               WHERE responsavel_id = :responsavel_id AND aluno_id = :aluno_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':responsavel_id', $responsavelId);
        $stmt->bindParam(':aluno_id', $alunoId);
        
        return $stmt->execute();
    }
    
    /**
     * Exclui um responsável (remove do banco de dados)
     */
    public function excluir($responsavelId) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // 1. Deletar todas as associações com alunos
            $sqlAssoc = "DELETE FROM aluno_responsavel WHERE responsavel_id = :responsavel_id";
            $stmtAssoc = $conn->prepare($sqlAssoc);
            $stmtAssoc->bindParam(':responsavel_id', $responsavelId, PDO::PARAM_INT);
            $stmtAssoc->execute();
            
            // 2. Deletar usuário
            $sqlUsuario = "DELETE FROM usuario WHERE pessoa_id = :pessoa_id AND role = 'RESPONSAVEL'";
            $stmtUsuario = $conn->prepare($sqlUsuario);
            $stmtUsuario->bindParam(':pessoa_id', $responsavelId, PDO::PARAM_INT);
            $stmtUsuario->execute();
            
            // 3. Deletar pessoa
            $sqlPessoa = "DELETE FROM pessoa WHERE id = :id AND tipo = 'RESPONSAVEL'";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bindParam(':id', $responsavelId, PDO::PARAM_INT);
            $stmtPessoa->execute();
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Erro ao excluir responsável: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Lista alunos associados a um responsável
     */
    public function listarAlunos($responsavelId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT a.*, p.nome as aluno_nome, p.cpf as aluno_cpf, a.matricula,
                       e.nome as escola_nome, t.serie, t.letra, t.turno,
                       CONCAT(COALESCE(t.serie, ''), ' ', COALESCE(t.letra, ''), ' - ', COALESCE(t.turno, '')) as turma_nome,
                       ar.parentesco, ar.principal
                FROM aluno_responsavel ar
                INNER JOIN aluno a ON ar.aluno_id = a.id
                INNER JOIN pessoa p ON a.pessoa_id = p.id
                LEFT JOIN escola e ON a.escola_id = e.id
                LEFT JOIN aluno_turma at ON a.id = at.aluno_id AND (at.fim IS NULL OR at.status = 'MATRICULADO')
                LEFT JOIN turma t ON at.turma_id = t.id
                WHERE ar.responsavel_id = :responsavel_id AND ar.ativo = 1 AND a.ativo = 1
                ORDER BY p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':responsavel_id', $responsavelId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca responsáveis de um aluno
     */
    public function buscarResponsaveisAluno($alunoId) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT p.*, ar.parentesco, ar.principal, u.id as usuario_id, u.username
                FROM aluno_responsavel ar
                INNER JOIN pessoa p ON ar.responsavel_id = p.id
                LEFT JOIN usuario u ON u.pessoa_id = p.id AND u.role = 'RESPONSAVEL'
                WHERE ar.aluno_id = :aluno_id AND ar.ativo = 1
                ORDER BY ar.principal DESC, p.nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':aluno_id', $alunoId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Gera username único
     */
    private function gerarUsername($nome, $cpf) {
        $conn = $this->db->getConnection();
        
        $nomeBase = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nome));
        $cpfBase = substr($cpf, -4);
        $username = $nomeBase . $cpfBase;
        
        // Verificar se já existe
        $stmt = $conn->prepare("SELECT id FROM usuario WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $contador = 1;
        $usernameOriginal = $username;
        while ($stmt->fetch()) {
            $username = $usernameOriginal . $contador;
            // Rebind do parâmetro com novo valor
            $stmt = $conn->prepare("SELECT id FROM usuario WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $contador++;
            
            // Proteção contra loop infinito
            if ($contador > 1000) {
                $username = $usernameOriginal . time();
                break;
            }
        }
        
        return $username;
    }
    
    /**
     * Gera senha padrão
     */
    private function gerarSenhaPadrao() {
        return 'responsavel123'; // Senha padrão - deve ser alterada no primeiro login
    }
}

