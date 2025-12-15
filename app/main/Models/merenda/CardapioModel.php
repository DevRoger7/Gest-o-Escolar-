<?php
/**
 * CardapioModel - Model para gerenciamento de cardápios
 * SIGAE - Sistema de Gestão e Alimentação Escolar
 */

require_once(__DIR__ . '/../../config/Database.php');

class CardapioModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista cardápios
     */
    public function listar($filtros = []) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT c.*, e.nome as escola_nome, u.username as criado_por_nome
                FROM cardapio c
                INNER JOIN escola e ON c.escola_id = e.id
                LEFT JOIN usuario u ON c.criado_por = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['escola_id'])) {
            $sql .= " AND c.escola_id = :escola_id";
            $params[':escola_id'] = $filtros['escola_id'];
        }
        
        if (!empty($filtros['mes'])) {
            $sql .= " AND c.mes = :mes";
            $params[':mes'] = $filtros['mes'];
        }
        
        if (!empty($filtros['ano'])) {
            $sql .= " AND c.ano = :ano";
            $params[':ano'] = $filtros['ano'];
        }
        
        if (!empty($filtros['status'])) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filtros['status'];
        }
        
        if (!empty($filtros['criado_por'])) {
            $sql .= " AND c.criado_por = :criado_por";
            $params[':criado_por'] = $filtros['criado_por'];
        }
        
        $sql .= " ORDER BY c.ano DESC, c.mes DESC, c.criado_em DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $cardapios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar contagem de itens para cada cardápio
        foreach ($cardapios as &$cardapio) {
            $sqlItens = "SELECT COUNT(*) as total FROM cardapio_item WHERE cardapio_id = :cardapio_id";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':cardapio_id', $cardapio['id'], PDO::PARAM_INT);
            $stmtItens->execute();
            $result = $stmtItens->fetch(PDO::FETCH_ASSOC);
            $cardapio['total_itens'] = $result['total'] ?? 0;
        }
        
        return $cardapios;
    }
    
    /**
     * Busca cardápio por ID
     */
    public function buscarPorId($id) {
        $conn = $this->db->getConnection();
        
        $sql = "SELECT c.*, e.nome as escola_nome
                FROM cardapio c
                INNER JOIN escola e ON c.escola_id = e.id
                WHERE c.id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $cardapio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cardapio) {
            // Buscar itens do cardápio
            $sqlItens = "SELECT ci.*, p.nome as produto_nome, p.unidade_medida
                        FROM cardapio_item ci
                        INNER JOIN produto p ON ci.produto_id = p.id
                        WHERE ci.cardapio_id = :cardapio_id
                        ORDER BY p.nome ASC";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':cardapio_id', $id);
            $stmtItens->execute();
            $cardapio['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $cardapio;
    }
    
    /**
     * Cria novo cardápio
     */
    public function criar($dados) {
        $conn = $this->db->getConnection();
        
        try {
            // Iniciar sessão se não estiver iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Obter usuario_id da sessão ou dos dados
            $usuarioId = $dados['criado_por'] ?? null;
            $pessoaId = $_SESSION['pessoa_id'] ?? null;
            
            // Log para debug
            error_log("CardapioModel::criar - usuario_id recebido: " . var_export($usuarioId, true));
            error_log("CardapioModel::criar - SESSION usuario_id: " . var_export($_SESSION['usuario_id'] ?? 'não definido', true));
            error_log("CardapioModel::criar - SESSION pessoa_id: " . var_export($pessoaId, true));
            error_log("CardapioModel::criar - dados['criado_por']: " . var_export($dados['criado_por'] ?? 'não definido', true));
            
            if (!$usuarioId) {
                error_log("CardapioModel::criar - ERRO: usuario_id não identificado");
                return ['success' => false, 'message' => 'Usuário não identificado. Faça login novamente.'];
            }
            
            // Garantir que seja um inteiro
            $usuarioId = (int)$usuarioId;
            
            if ($usuarioId <= 0) {
                error_log("CardapioModel::criar - ERRO: usuario_id inválido (<= 0): " . $usuarioId);
                return ['success' => false, 'message' => 'ID do usuário inválido.'];
            }
            
            // Validar se o usuário existe
            $sqlCheckUsuario = "SELECT id, username, ativo, pessoa_id FROM usuario WHERE id = :usuario_id";
            $stmtCheckUsuario = $conn->prepare($sqlCheckUsuario);
            $stmtCheckUsuario->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmtCheckUsuario->execute();
            $usuarioExiste = $stmtCheckUsuario->fetch(PDO::FETCH_ASSOC);
            
            // Se não encontrou, tentar buscar pelo pessoa_id
            if (!$usuarioExiste && $pessoaId) {
                error_log("CardapioModel::criar - Usuário ID $usuarioId não encontrado, tentando buscar por pessoa_id: $pessoaId");
                $sqlCheckPessoa = "SELECT id, username, ativo, pessoa_id FROM usuario WHERE pessoa_id = :pessoa_id";
                $stmtCheckPessoa = $conn->prepare($sqlCheckPessoa);
                $stmtCheckPessoa->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                $stmtCheckPessoa->execute();
                $usuarioExiste = $stmtCheckPessoa->fetch(PDO::FETCH_ASSOC);
                
                if ($usuarioExiste) {
                    $usuarioId = (int)$usuarioExiste['id'];
                    error_log("CardapioModel::criar - Usuário encontrado por pessoa_id: usuario_id={$usuarioId}");
                }
            }
            
            if (!$usuarioExiste) {
                error_log("CardapioModel::criar - ERRO: Usuário ID $usuarioId não encontrado no banco de dados (tentou também pessoa_id: $pessoaId)");
                // Tentar verificar se há algum usuário no banco
                $sqlCount = "SELECT COUNT(*) as total FROM usuario";
                $stmtCount = $conn->query($sqlCount);
                $totalUsuarios = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                error_log("CardapioModel::criar - Total de usuários no banco: $totalUsuarios");
                return ['success' => false, 'message' => "Usuário inválido. ID do usuário não encontrado no banco de dados."];
            }
            
            error_log("CardapioModel::criar - Usuário validado: ID={$usuarioExiste['id']}, Username={$usuarioExiste['username']}, Ativo={$usuarioExiste['ativo']}, Pessoa_ID={$usuarioExiste['pessoa_id']}");
            
            $conn->beginTransaction();
            
            // Status padrão: PUBLICADO (ou RASCUNHO se especificado)
            $status = $dados['status'] ?? 'PUBLICADO';
            
            $sql = "INSERT INTO cardapio (escola_id, mes, ano, status, criado_por, criado_em)
                    VALUES (:escola_id, :mes, :ano, :status, :criado_por, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':escola_id', $dados['escola_id'], PDO::PARAM_INT);
            $stmt->bindParam(':mes', $dados['mes'], PDO::PARAM_INT);
            $stmt->bindParam(':ano', $dados['ano'], PDO::PARAM_INT);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':criado_por', $usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            
            $cardapioId = $conn->lastInsertId();
            
            // Adicionar itens
            if (!empty($dados['itens'])) {
                foreach ($dados['itens'] as $item) {
                    $sqlItem = "INSERT INTO cardapio_item (cardapio_id, produto_id, quantidade)
                               VALUES (:cardapio_id, :produto_id, :quantidade)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindParam(':cardapio_id', $cardapioId);
                    $stmtItem->bindParam(':produto_id', $item['produto_id']);
                    $stmtItem->bindParam(':quantidade', $item['quantidade']);
                    $stmtItem->execute();
                }
            }
            
            $conn->commit();
            return ['success' => true, 'id' => $cardapioId];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Aprova cardápio (ADM_MERENDA) - muda de PUBLICADO para APROVADO
     */
    public function aprovar($id) {
        $conn = $this->db->getConnection();
        
        try {
            // Verificar se o cardápio existe e está como PUBLICADO
            $sqlCheck = "SELECT id, status FROM cardapio WHERE id = :id";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $cardapio = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$cardapio) {
                return ['success' => false, 'message' => 'Cardápio não encontrado'];
            }
            
            if ($cardapio['status'] !== 'PUBLICADO') {
                return ['success' => false, 'message' => 'Apenas cardápios publicados podem ser aprovados'];
            }
            
            $sql = "UPDATE cardapio SET status = 'APROVADO', aprovado_por = :aprovado_por,
                    data_aprovacao = NOW() WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':aprovado_por', $_SESSION['usuario_id'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Rejeita cardápio (muda status de PUBLICADO para REJEITADO)
     */
    public function rejeitar($id, $observacoes = '') {
        $conn = $this->db->getConnection();
        
        try {
            // Iniciar sessão se não estiver iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Verificar se o cardápio existe e está como PUBLICADO
            $sqlCheck = "SELECT id, status FROM cardapio WHERE id = :id";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $cardapio = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$cardapio) {
                return ['success' => false, 'message' => 'Cardápio não encontrado'];
            }
            
            if ($cardapio['status'] !== 'PUBLICADO') {
                return ['success' => false, 'message' => 'Apenas cardápios publicados podem ser recusados'];
            }
            
            // Atualizar status para REJEITADO e adicionar observações
            $sql = "UPDATE cardapio SET status = 'REJEITADO', observacoes = CONCAT(COALESCE(observacoes, ''), '\n[MOTIVO DA RECUSA] ', :observacoes), atualizado_em = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':observacoes', $observacoes);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Publica cardápio
     */
    public function publicar($id) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE cardapio SET status = 'PUBLICADO' WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Atualiza cardápio existente
     */
    public function atualizar($id, $dados) {
        $conn = $this->db->getConnection();
        
        try {
            // Iniciar sessão se não estiver iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            
            if (!$usuarioId) {
                return ['success' => false, 'message' => 'Usuário não identificado'];
            }
            
            // Verificar se o cardápio existe, está como RASCUNHO e foi criado pelo usuário logado
            $sqlCheck = "SELECT id, status, criado_por FROM cardapio WHERE id = :id";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $cardapio = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$cardapio) {
                return ['success' => false, 'message' => 'Cardápio não encontrado'];
            }
            
            if ($cardapio['status'] !== 'RASCUNHO') {
                return ['success' => false, 'message' => 'Apenas cardápios em rascunho podem ser editados'];
            }
            
            // Removido: validação de criado_por - qualquer nutricionista pode editar rascunhos
            
            $conn->beginTransaction();
            
            // Atualizar dados do cardápio
            $sql = "UPDATE cardapio SET escola_id = :escola_id, mes = :mes, ano = :ano, atualizado_em = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':escola_id', $dados['escola_id'], PDO::PARAM_INT);
            $stmt->bindParam(':mes', $dados['mes'], PDO::PARAM_INT);
            $stmt->bindParam(':ano', $dados['ano'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Remover itens antigos
            $sqlDelete = "DELETE FROM cardapio_item WHERE cardapio_id = :cardapio_id";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
            $stmtDelete->execute();
            
            // Adicionar novos itens
            if (!empty($dados['itens'])) {
                foreach ($dados['itens'] as $item) {
                    $sqlItem = "INSERT INTO cardapio_item (cardapio_id, produto_id, quantidade)
                               VALUES (:cardapio_id, :produto_id, :quantidade)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
                    $stmtItem->bindParam(':produto_id', $item['produto_id'], PDO::PARAM_INT);
                    $stmtItem->bindParam(':quantidade', $item['quantidade']);
                    $stmtItem->execute();
                }
            }
            
            $conn->commit();
            return ['success' => true, 'id' => $id];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Publica cardápio rascunho (muda status de RASCUNHO para PUBLICADO)
     * O ADM_MERENDA pode então aprovar (PUBLICADO -> APROVADO) ou rejeitar
     */
    public function enviar($id) {
        $conn = $this->db->getConnection();
        
        try {
            // Iniciar sessão se não estiver iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            
            if (!$usuarioId) {
                return ['success' => false, 'message' => 'Usuário não identificado'];
            }
            
            // Verificar se o cardápio existe, está como RASCUNHO e foi criado pelo usuário logado
            $sqlCheck = "SELECT id, status, criado_por FROM cardapio WHERE id = :id";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $cardapio = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$cardapio) {
                return ['success' => false, 'message' => 'Cardápio não encontrado'];
            }
            
            if ($cardapio['status'] !== 'RASCUNHO') {
                return ['success' => false, 'message' => 'Apenas cardápios em rascunho podem ser publicados'];
            }
            
            // Removido: validação de criado_por - qualquer nutricionista pode publicar rascunhos
            
            // Verificar se tem itens
            $sqlItens = "SELECT COUNT(*) as total FROM cardapio_item WHERE cardapio_id = :cardapio_id";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
            $stmtItens->execute();
            $resultItens = $stmtItens->fetch(PDO::FETCH_ASSOC);
            
            if (($resultItens['total'] ?? 0) === 0) {
                return ['success' => false, 'message' => 'O cardápio precisa ter pelo menos um item para ser publicado'];
            }
            
            // Mudar status para PUBLICADO (será revisado pelo ADM_MERENDA)
            $sql = "UPDATE cardapio SET status = 'PUBLICADO', atualizado_em = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Exclui cardápio (apenas rascunhos criados pelo usuário logado)
     */
    public function excluir($id) {
        $conn = $this->db->getConnection();
        
        try {
            // Iniciar sessão se não estiver iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            
            if (!$usuarioId) {
                return ['success' => false, 'message' => 'Usuário não identificado'];
            }
            
            // Verificar se o cardápio existe, está como RASCUNHO e foi criado pelo usuário logado
            $sqlCheck = "SELECT id, status, criado_por FROM cardapio WHERE id = :id";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $cardapio = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$cardapio) {
                return ['success' => false, 'message' => 'Cardápio não encontrado'];
            }
            
            if ($cardapio['status'] !== 'RASCUNHO') {
                return ['success' => false, 'message' => 'Apenas cardápios em rascunho podem ser excluídos'];
            }
            
            if ($cardapio['criado_por'] != $usuarioId) {
                return ['success' => false, 'message' => 'Você só pode excluir cardápios criados por você'];
            }
            
            $conn->beginTransaction();
            
            // Excluir itens
            $sqlDeleteItens = "DELETE FROM cardapio_item WHERE cardapio_id = :cardapio_id";
            $stmtDeleteItens = $conn->prepare($sqlDeleteItens);
            $stmtDeleteItens->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
            $stmtDeleteItens->execute();
            
            // Excluir cardápio
            $sqlDelete = "DELETE FROM cardapio WHERE id = :id";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtDelete->execute();
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Cancela envio do cardápio (volta de APROVADO para RASCUNHO)
     * Apenas se o cardápio foi criado pelo usuário logado
     */
    public function cancelarEnvio($id) {
        $conn = $this->db->getConnection();
        
        try {
            // Iniciar sessão se não estiver iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            $tipoUsuario = strtolower($_SESSION['tipo'] ?? '');
            
            if (!$usuarioId) {
                return ['success' => false, 'message' => 'Usuário não identificado'];
            }
            
            // Verificar se o cardápio existe e seu status
            $sqlCheck = "SELECT id, status, criado_por FROM cardapio WHERE id = :id";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $cardapio = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$cardapio) {
                return ['success' => false, 'message' => 'Cardápio não encontrado'];
            }
            
            // Verificar se está como APROVADO ou PUBLICADO
            if ($cardapio['status'] !== 'APROVADO' && $cardapio['status'] !== 'PUBLICADO') {
                return ['success' => false, 'message' => 'Apenas cardápios aprovados ou publicados podem ter o envio cancelado'];
            }
            
            // NUTRICIONISTA pode cancelar qualquer cardápio publicado
            // ADM_MERENDA NÃO pode cancelar publicação (apenas aprovar ou recusar)
            // Outros usuários só podem cancelar cardápios que criaram
            $podeCancelar = false;
            if ($tipoUsuario === 'nutricionista') {
                $podeCancelar = true;
            } else if ($tipoUsuario === 'adm_merenda') {
                // ADM_MERENDA não pode cancelar publicação
                $podeCancelar = false;
            } else {
                // Verificar se foi criado pelo usuário logado
                if ($cardapio['criado_por'] == $usuarioId) {
                    $podeCancelar = true;
                }
            }
            
            if (!$podeCancelar) {
                return ['success' => false, 'message' => 'Você só pode cancelar o envio de cardápios criados por você'];
            }
            
            // Voltar para RASCUNHO
            $sql = "UPDATE cardapio SET status = 'RASCUNHO', atualizado_em = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

?>

