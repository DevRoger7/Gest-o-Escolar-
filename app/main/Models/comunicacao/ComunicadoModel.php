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
        
        if (!empty($filtros['enviado_por'])) {
            $sql .= " AND c.enviado_por = :enviado_por";
            $params[':enviado_por'] = $filtros['enviado_por'];
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
        try {
            $conn = $this->db->getConnection();
            
            $sql = "INSERT INTO comunicado (turma_id, aluno_id, escola_id, enviado_por, titulo, mensagem,
                    tipo, prioridade, canal, criado_em)
                    VALUES (:turma_id, :aluno_id, :escola_id, :enviado_por, :titulo, :mensagem,
                    :tipo, :prioridade, :canal, NOW())";
            
            $stmt = $conn->prepare($sql);
            $turmaId = !empty($dados['turma_id']) ? (int)$dados['turma_id'] : null;
            $alunoId = !empty($dados['aluno_id']) ? (int)$dados['aluno_id'] : null;
            $escolaId = !empty($dados['escola_id']) ? (int)$dados['escola_id'] : null;
            
            // Validar e obter usuario_id válido
            // Primeiro tenta usar usuario_id da sessão, depois tenta buscar através de pessoa_id
            $enviadoPor = null;
            
            // Tentar 1: usar usuario_id diretamente da sessão
            if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
                $usuarioIdParam = (int)$_SESSION['usuario_id'];
                $sqlVerificarUsuario = "SELECT id FROM usuario WHERE id = :usuario_id AND ativo = 1 LIMIT 1";
                $stmtVerificarUsuario = $conn->prepare($sqlVerificarUsuario);
                $stmtVerificarUsuario->bindParam(':usuario_id', $usuarioIdParam, PDO::PARAM_INT);
                $stmtVerificarUsuario->execute();
                $usuarioExiste = $stmtVerificarUsuario->fetch(PDO::FETCH_ASSOC);
                if ($usuarioExiste) {
                    $enviadoPor = $usuarioIdParam;
                }
            }
            
            // Tentar 2: buscar usuario_id através de pessoa_id
            if (!$enviadoPor && isset($_SESSION['pessoa_id']) && !empty($_SESSION['pessoa_id'])) {
                $pessoaIdParam = (int)$_SESSION['pessoa_id'];
                $sqlBuscarUsuario = "SELECT u.id FROM usuario u 
                                    WHERE u.pessoa_id = :pessoa_id AND u.ativo = 1 
                                    LIMIT 1";
                $stmtBuscarUsuario = $conn->prepare($sqlBuscarUsuario);
                $stmtBuscarUsuario->bindParam(':pessoa_id', $pessoaIdParam, PDO::PARAM_INT);
                $stmtBuscarUsuario->execute();
                $usuarioData = $stmtBuscarUsuario->fetch(PDO::FETCH_ASSOC);
                if ($usuarioData && isset($usuarioData['id'])) {
                    $enviadoPor = (int)$usuarioData['id'];
                }
            }
            
            // Tentar 3: buscar através de CPF se disponível
            if (!$enviadoPor && isset($_SESSION['cpf']) && !empty($_SESSION['cpf'])) {
                $cpfLimpo = preg_replace('/[^0-9]/', '', $_SESSION['cpf']);
                $sqlBuscarUsuarioCpf = "SELECT u.id FROM usuario u 
                                       INNER JOIN pessoa p ON u.pessoa_id = p.id 
                                       WHERE p.cpf = :cpf AND u.ativo = 1 
                                       LIMIT 1";
                $stmtBuscarUsuarioCpf = $conn->prepare($sqlBuscarUsuarioCpf);
                $stmtBuscarUsuarioCpf->bindParam(':cpf', $cpfLimpo);
                $stmtBuscarUsuarioCpf->execute();
                $usuarioDataCpf = $stmtBuscarUsuarioCpf->fetch(PDO::FETCH_ASSOC);
                if ($usuarioDataCpf && isset($usuarioDataCpf['id'])) {
                    $enviadoPor = (int)$usuarioDataCpf['id'];
                }
            }
            
            // Se não encontrou usuário válido, retornar erro
            if (!$enviadoPor) {
                error_log("Não foi possível identificar usuario_id para criar comunicado. Sessão: usuario_id=" . ($_SESSION['usuario_id'] ?? 'null') . ", pessoa_id=" . ($_SESSION['pessoa_id'] ?? 'null'));
                return ['success' => false, 'message' => 'Usuário não identificado. Faça login novamente.'];
            }
            $titulo = trim($dados['titulo'] ?? '');
            $mensagem = trim($dados['mensagem'] ?? '');
            $tipo = $dados['tipo'] ?? 'GERAL';
            $prioridade = $dados['prioridade'] ?? 'NORMAL';
            $canal = $dados['canal'] ?? 'SISTEMA';

            // Validar dados obrigatórios
            if (empty($titulo) || empty($mensagem)) {
                return ['success' => false, 'message' => 'Título e mensagem são obrigatórios'];
            }
            
            if (!$escolaId) {
                return ['success' => false, 'message' => 'Escola não identificada'];
            }

            // Usar bindValue para permitir null nos campos opcionais
            $stmt->bindValue(':turma_id', $turmaId, $turmaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':aluno_id', $alunoId, $alunoId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->bindValue(':enviado_por', $enviadoPor, PDO::PARAM_INT);
            $stmt->bindValue(':titulo', $titulo);
            $stmt->bindValue(':mensagem', $mensagem);
            $stmt->bindValue(':tipo', $tipo);
            $stmt->bindValue(':prioridade', $prioridade);
            $stmt->bindValue(':canal', $canal);
            
            if ($stmt->execute()) {
                return ['success' => true, 'id' => $conn->lastInsertId()];
            }
            
            $errorInfo = $stmt->errorInfo();
            $errorMessage = $errorInfo[2] ?? 'Erro desconhecido ao criar comunicado';
            error_log("Erro ao executar INSERT em comunicado: " . $errorMessage);
            
            return ['success' => false, 'message' => 'Erro ao salvar comunicado: ' . $errorMessage];
        } catch (PDOException $e) {
            error_log("Erro PDO ao criar comunicado: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Erro ao criar comunicado: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro inesperado: ' . $e->getMessage()];
        }
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

