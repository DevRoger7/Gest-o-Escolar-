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
            // Buscar semanas do cardápio
            $sqlSemanas = "SELECT * FROM cardapio_semana 
                          WHERE cardapio_id = :cardapio_id 
                          ORDER BY numero_semana ASC";
            $stmtSemanas = $conn->prepare($sqlSemanas);
            $stmtSemanas->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
            $stmtSemanas->execute();
            $cardapio['semanas'] = $stmtSemanas->fetchAll(PDO::FETCH_ASSOC);
            
            // Buscar itens do cardápio (agora com informação da semana)
            $sqlItens = "SELECT ci.*, p.nome as produto_nome, p.unidade_medida,
                        cs.numero_semana, cs.observacao as semana_observacao
                        FROM cardapio_item ci
                        INNER JOIN produto p ON ci.produto_id = p.id
                        LEFT JOIN cardapio_semana cs ON ci.semana_id = cs.id
                        WHERE ci.cardapio_id = :cardapio_id
                        ORDER BY cs.numero_semana ASC, p.nome ASC";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
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
            
            // Criar semanas do cardápio
            $semanasMap = []; // Mapear numero_semana => semana_id
            if (!empty($dados['semanas']) && is_array($dados['semanas'])) {
                foreach ($dados['semanas'] as $semana) {
                    $numeroSemana = (int)($semana['numero_semana'] ?? 0);
                    $observacao = trim($semana['observacao'] ?? '');
                    $dataInicio = !empty($semana['data_inicio']) ? $semana['data_inicio'] : null;
                    $dataFim = !empty($semana['data_fim']) ? $semana['data_fim'] : null;
                    
                    if ($numeroSemana > 0) {
                        $sqlSemana = "INSERT INTO cardapio_semana (cardapio_id, numero_semana, observacao, data_inicio, data_fim, criado_em)
                                     VALUES (:cardapio_id, :numero_semana, :observacao, :data_inicio, :data_fim, NOW())";
                        $stmtSemana = $conn->prepare($sqlSemana);
                        $stmtSemana->bindParam(':cardapio_id', $cardapioId, PDO::PARAM_INT);
                        $stmtSemana->bindParam(':numero_semana', $numeroSemana, PDO::PARAM_INT);
                        $stmtSemana->bindParam(':observacao', $observacao);
                        $stmtSemana->bindParam(':data_inicio', $dataInicio);
                        $stmtSemana->bindParam(':data_fim', $dataFim);
                        $stmtSemana->execute();
                        
                        $semanaId = $conn->lastInsertId();
                        $semanasMap[$numeroSemana] = $semanaId;
                    }
                }
            }
            
            // Adicionar itens (associados às semanas se fornecido)
            if (!empty($dados['itens'])) {
                foreach ($dados['itens'] as $item) {
                    $semanaId = null;
                    if (!empty($item['numero_semana'])) {
                        $numeroSemana = (int)$item['numero_semana'];
                        $semanaId = $semanasMap[$numeroSemana] ?? null;
                    }
                    
                    $sqlItem = "INSERT INTO cardapio_item (cardapio_id, semana_id, produto_id, quantidade)
                               VALUES (:cardapio_id, :semana_id, :produto_id, :quantidade)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindParam(':cardapio_id', $cardapioId, PDO::PARAM_INT);
                    $stmtItem->bindParam(':semana_id', $semanaId, PDO::PARAM_INT);
                    $stmtItem->bindParam(':produto_id', $item['produto_id'], PDO::PARAM_INT);
                    $stmtItem->bindParam(':quantidade', $item['quantidade']);
                    $stmtItem->execute();
                }
            }
            
            // Se o cardápio foi criado com status PUBLICADO, descontar estoque
            if ($status === 'PUBLICADO') {
                try {
                    $this->descontarEstoque($cardapioId);
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("CardapioModel->criar: Erro ao descontar estoque - " . $e->getMessage());
                    return ['success' => false, 'message' => 'Erro ao descontar estoque: ' . $e->getMessage()];
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
            
            // Validar e obter usuario_id válido
            $aprovadoPor = null;
            if (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) {
                $usuarioIdParam = (int)$_SESSION['usuario_id'];
                // Verificar se o usuário existe na tabela
                $sqlVerificarUsuario = "SELECT id FROM usuario WHERE id = :usuario_id LIMIT 1";
                $stmtVerificarUsuario = $conn->prepare($sqlVerificarUsuario);
                $stmtVerificarUsuario->bindParam(':usuario_id', $usuarioIdParam, PDO::PARAM_INT);
                $stmtVerificarUsuario->execute();
                $usuarioExiste = $stmtVerificarUsuario->fetch(PDO::FETCH_ASSOC);
                if ($usuarioExiste) {
                    $aprovadoPor = $usuarioIdParam;
                } else {
                    error_log("CardapioModel->aprovar: usuario_id " . $usuarioIdParam . " não encontrado na tabela usuario");
                }
            } else {
                error_log("CardapioModel->aprovar: usuario_id não está definido na sessão ou não é numérico");
            }
            
            $sql = "UPDATE cardapio SET status = 'APROVADO', aprovado_por = :aprovado_por,
                    data_aprovacao = NOW() WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':aprovado_por', $aprovadoPor, PDO::PARAM_INT);
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
            
            $conn->beginTransaction();
            
            // Devolver produtos ao estoque
            try {
                $this->devolverEstoque($id);
            } catch (Exception $e) {
                $conn->rollBack();
                return ['success' => false, 'message' => 'Erro ao devolver estoque: ' . $e->getMessage()];
            }
            
            // Atualizar status para REJEITADO e adicionar observações
            $sql = "UPDATE cardapio SET status = 'REJEITADO', observacoes = CONCAT(COALESCE(observacoes, ''), '\n[MOTIVO DA RECUSA] ', :observacoes), atualizado_em = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':observacoes', $observacoes);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Publica cardápio
     * Nota: Este método é usado pelo ADM_MERENDA para publicar cardápios
     * Se o cardápio estava em RASCUNHO, desconta estoque ao publicar
     */
    public function publicar($id) {
        $conn = $this->db->getConnection();
        
        try {
            // Verificar status atual do cardápio
            $sqlCheck = "SELECT id, status FROM cardapio WHERE id = :id";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $cardapio = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$cardapio) {
                return false;
            }
            
            $conn->beginTransaction();
            
            // Se estava em RASCUNHO, descontar estoque ao publicar
            if ($cardapio['status'] === 'RASCUNHO') {
                try {
                    $this->descontarEstoque($id);
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("CardapioModel->publicar: Erro ao descontar estoque - " . $e->getMessage());
                    throw $e;
                }
            }
            
            $sql = "UPDATE cardapio SET status = 'PUBLICADO' WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("CardapioModel->publicar: Erro - " . $e->getMessage());
            return false;
        }
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
            
            // Remover semanas antigas e recriar
            $sqlDeleteSemanas = "DELETE FROM cardapio_semana WHERE cardapio_id = :cardapio_id";
            $stmtDeleteSemanas = $conn->prepare($sqlDeleteSemanas);
            $stmtDeleteSemanas->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
            $stmtDeleteSemanas->execute();
            
            // Criar novas semanas
            $semanasMap = [];
            if (!empty($dados['semanas']) && is_array($dados['semanas'])) {
                foreach ($dados['semanas'] as $semana) {
                    $numeroSemana = (int)($semana['numero_semana'] ?? 0);
                    $observacao = trim($semana['observacao'] ?? '');
                    $dataInicio = !empty($semana['data_inicio']) ? $semana['data_inicio'] : null;
                    $dataFim = !empty($semana['data_fim']) ? $semana['data_fim'] : null;
                    
                    if ($numeroSemana > 0) {
                        $sqlSemana = "INSERT INTO cardapio_semana (cardapio_id, numero_semana, observacao, data_inicio, data_fim, criado_em)
                                     VALUES (:cardapio_id, :numero_semana, :observacao, :data_inicio, :data_fim, NOW())";
                        $stmtSemana = $conn->prepare($sqlSemana);
                        $stmtSemana->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
                        $stmtSemana->bindParam(':numero_semana', $numeroSemana, PDO::PARAM_INT);
                        $stmtSemana->bindParam(':observacao', $observacao);
                        $stmtSemana->bindParam(':data_inicio', $dataInicio);
                        $stmtSemana->bindParam(':data_fim', $dataFim);
                        $stmtSemana->execute();
                        
                        $semanaId = $conn->lastInsertId();
                        $semanasMap[$numeroSemana] = $semanaId;
                    }
                }
            }
            
            // Remover itens antigos
            $sqlDelete = "DELETE FROM cardapio_item WHERE cardapio_id = :cardapio_id";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
            $stmtDelete->execute();
            
            // Adicionar novos itens (associados às semanas se fornecido)
            if (!empty($dados['itens'])) {
                foreach ($dados['itens'] as $item) {
                    $semanaId = null;
                    if (!empty($item['numero_semana'])) {
                        $numeroSemana = (int)$item['numero_semana'];
                        $semanaId = $semanasMap[$numeroSemana] ?? null;
                    }
                    
                    $sqlItem = "INSERT INTO cardapio_item (cardapio_id, semana_id, produto_id, quantidade)
                               VALUES (:cardapio_id, :semana_id, :produto_id, :quantidade)";
                    $stmtItem = $conn->prepare($sqlItem);
                    $stmtItem->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
                    $stmtItem->bindParam(':semana_id', $semanaId, PDO::PARAM_INT);
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
     * Desconta produtos do estoque da escola (pacote_escola_item) quando cardápio é publicado
     */
    private function descontarEstoque($cardapioId) {
        $conn = $this->db->getConnection();
        
        try {
            // Primeiro, buscar o escola_id do cardápio
            $sqlCardapio = "SELECT escola_id FROM cardapio WHERE id = :cardapio_id";
            $stmtCardapio = $conn->prepare($sqlCardapio);
            $stmtCardapio->bindParam(':cardapio_id', $cardapioId, PDO::PARAM_INT);
            $stmtCardapio->execute();
            $cardapio = $stmtCardapio->fetch(PDO::FETCH_ASSOC);
            
            if (!$cardapio || !$cardapio['escola_id']) {
                throw new Exception("Cardápio não encontrado ou sem escola associada");
            }
            
            $escolaId = (int)$cardapio['escola_id'];
            
            // Buscar itens do cardápio
            $sqlItens = "SELECT produto_id, quantidade FROM cardapio_item WHERE cardapio_id = :cardapio_id";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':cardapio_id', $cardapioId, PDO::PARAM_INT);
            $stmtItens->execute();
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($itens as $item) {
                $produtoId = (int)$item['produto_id'];
                $quantidadeNecessaria = floatval($item['quantidade']);
                
                if ($quantidadeNecessaria <= 0) continue;
                
                // Buscar itens do pacote_escola_item para este produto e escola
                // Ordenar por validade (se houver estoque_central_id relacionado) - mais próximo primeiro
                $sqlEstoque = "SELECT 
                                pei.id, 
                                pei.quantidade,
                                pei.estoque_central_id,
                                COALESCE(ec.validade, '9999-12-31') as validade
                              FROM pacote_escola_item pei
                              INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                              LEFT JOIN estoque_central ec ON pei.estoque_central_id = ec.id
                              WHERE pei.produto_id = :produto_id 
                              AND pe.escola_id = :escola_id
                              AND pei.quantidade > 0
                              ORDER BY 
                                CASE WHEN ec.validade IS NULL THEN 1 ELSE 0 END ASC,
                                ec.validade ASC,
                                pei.id ASC";
                
                $stmtEstoque = $conn->prepare($sqlEstoque);
                $stmtEstoque->bindParam(':produto_id', $produtoId, PDO::PARAM_INT);
                $stmtEstoque->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
                $stmtEstoque->execute();
                $itensEstoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);
                
                $quantidadeRestante = $quantidadeNecessaria;
                
                foreach ($itensEstoque as $itemEstoque) {
                    if ($quantidadeRestante <= 0) break;
                    
                    $itemEstoqueId = (int)$itemEstoque['id'];
                    $quantidadeDisponivel = floatval($itemEstoque['quantidade']);
                    
                    if ($quantidadeDisponivel >= $quantidadeRestante) {
                        // Item tem quantidade suficiente
                        $novaQuantidade = $quantidadeDisponivel - $quantidadeRestante;
                        $sqlUpdate = "UPDATE pacote_escola_item SET quantidade = :quantidade WHERE id = :id";
                        $stmtUpdate = $conn->prepare($sqlUpdate);
                        $stmtUpdate->bindParam(':quantidade', $novaQuantidade);
                        $stmtUpdate->bindParam(':id', $itemEstoqueId, PDO::PARAM_INT);
                        $stmtUpdate->execute();
                        $quantidadeRestante = 0;
                    } else {
                        // Item não tem quantidade suficiente, usar tudo e passar para o próximo
                        $sqlUpdate = "UPDATE pacote_escola_item SET quantidade = 0 WHERE id = :id";
                        $stmtUpdate = $conn->prepare($sqlUpdate);
                        $stmtUpdate->bindParam(':id', $itemEstoqueId, PDO::PARAM_INT);
                        $stmtUpdate->execute();
                        $quantidadeRestante -= $quantidadeDisponivel;
                    }
                }
                
                if ($quantidadeRestante > 0) {
                    error_log("CardapioModel->descontarEstoque: Aviso - Quantidade insuficiente no estoque da escola para produto ID $produtoId. Faltam $quantidadeRestante unidades.");
                    throw new Exception("Estoque insuficiente para o produto ID {$produtoId}. Faltam {$quantidadeRestante} unidades no estoque da escola.");
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("CardapioModel->descontarEstoque: Erro - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Devolve produtos ao estoque da escola (pacote_escola_item) quando cardápio é recusado ou apagado
     */
    private function devolverEstoque($cardapioId) {
        $conn = $this->db->getConnection();
        
        try {
            // Primeiro, buscar o escola_id do cardápio
            $sqlCardapio = "SELECT escola_id FROM cardapio WHERE id = :cardapio_id";
            $stmtCardapio = $conn->prepare($sqlCardapio);
            $stmtCardapio->bindParam(':cardapio_id', $cardapioId, PDO::PARAM_INT);
            $stmtCardapio->execute();
            $cardapio = $stmtCardapio->fetch(PDO::FETCH_ASSOC);
            
            if (!$cardapio || !$cardapio['escola_id']) {
                throw new Exception("Cardápio não encontrado ou sem escola associada");
            }
            
            $escolaId = (int)$cardapio['escola_id'];
            
            // Buscar itens do cardápio
            $sqlItens = "SELECT produto_id, quantidade FROM cardapio_item WHERE cardapio_id = :cardapio_id";
            $stmtItens = $conn->prepare($sqlItens);
            $stmtItens->bindParam(':cardapio_id', $cardapioId, PDO::PARAM_INT);
            $stmtItens->execute();
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($itens as $item) {
                $produtoId = (int)$item['produto_id'];
                $quantidadeADevolver = floatval($item['quantidade']);
                
                if ($quantidadeADevolver <= 0) continue;
                
                // Tentar encontrar um item existente em pacote_escola_item para este produto e escola
                // Priorizar itens que já têm estoque_central_id
                $sqlItemExistente = "SELECT pei.id, pei.quantidade, pei.estoque_central_id
                                     FROM pacote_escola_item pei
                                     INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                                     WHERE pei.produto_id = :produto_id 
                                     AND pe.escola_id = :escola_id
                                     ORDER BY 
                                       CASE WHEN pei.estoque_central_id IS NULL THEN 1 ELSE 0 END ASC,
                                       pei.id ASC
                                     LIMIT 1";
                
                $stmtItemExistente = $conn->prepare($sqlItemExistente);
                $stmtItemExistente->bindParam(':produto_id', $produtoId, PDO::PARAM_INT);
                $stmtItemExistente->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
                $stmtItemExistente->execute();
                $itemExistente = $stmtItemExistente->fetch(PDO::FETCH_ASSOC);
                
                if ($itemExistente) {
                    // Atualizar quantidade do item existente
                    $itemId = (int)$itemExistente['id'];
                    $quantidadeAtual = floatval($itemExistente['quantidade']);
                    $novaQuantidade = $quantidadeAtual + $quantidadeADevolver;
                    
                    $sqlUpdate = "UPDATE pacote_escola_item SET quantidade = :quantidade WHERE id = :id";
                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->bindParam(':quantidade', $novaQuantidade);
                    $stmtUpdate->bindParam(':id', $itemId, PDO::PARAM_INT);
                    $stmtUpdate->execute();
                } else {
                    // Se não houver item existente, buscar um pacote da escola ou criar um novo
                    // Primeiro, tentar encontrar um pacote existente da escola
                    $sqlPacote = "SELECT id FROM pacote_escola WHERE escola_id = :escola_id ORDER BY id DESC LIMIT 1";
                    $stmtPacote = $conn->prepare($sqlPacote);
                    $stmtPacote->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
                    $stmtPacote->execute();
                    $pacote = $stmtPacote->fetch(PDO::FETCH_ASSOC);
                    
                    $pacoteId = null;
                    if ($pacote) {
                        $pacoteId = (int)$pacote['id'];
                    } else {
                        // Criar um novo pacote se não existir nenhum
                        $sqlInsertPacote = "INSERT INTO pacote_escola (escola_id, data_envio, criado_em) 
                                           VALUES (:escola_id, CURDATE(), NOW())";
                        $stmtInsertPacote = $conn->prepare($sqlInsertPacote);
                        $stmtInsertPacote->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
                        $stmtInsertPacote->execute();
                        $pacoteId = $conn->lastInsertId();
                    }
                    
                    // Buscar unidade de medida do produto
                    $sqlProduto = "SELECT unidade_medida FROM produto WHERE id = :produto_id";
                    $stmtProduto = $conn->prepare($sqlProduto);
                    $stmtProduto->bindParam(':produto_id', $produtoId, PDO::PARAM_INT);
                    $stmtProduto->execute();
                    $produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);
                    $unidadeMedida = $produto ? $produto['unidade_medida'] : null;
                    
                    // Criar novo item no pacote_escola_item
                    $sqlInsert = "INSERT INTO pacote_escola_item (pacote_id, produto_id, quantidade, unidade_medida) 
                                 VALUES (:pacote_id, :produto_id, :quantidade, :unidade_medida)";
                    $stmtInsert = $conn->prepare($sqlInsert);
                    $stmtInsert->bindParam(':pacote_id', $pacoteId, PDO::PARAM_INT);
                    $stmtInsert->bindParam(':produto_id', $produtoId, PDO::PARAM_INT);
                    $stmtInsert->bindParam(':quantidade', $quantidadeADevolver);
                    $stmtInsert->bindParam(':unidade_medida', $unidadeMedida);
                    $stmtInsert->execute();
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("CardapioModel->devolverEstoque: Erro - " . $e->getMessage());
            throw $e;
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
            
            $conn->beginTransaction();
            
            // Descontar produtos do estoque
            try {
                $this->descontarEstoque($id);
            } catch (Exception $e) {
                $conn->rollBack();
                return ['success' => false, 'message' => 'Erro ao descontar estoque: ' . $e->getMessage()];
            }
            
            // Mudar status para PUBLICADO (será revisado pelo ADM_MERENDA)
            $sql = "UPDATE cardapio SET status = 'PUBLICADO', atualizado_em = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
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
            
            // Permitir exclusão de cardápios que não foram aprovados:
            // - RASCUNHO: qualquer nutricionista pode excluir (não descontou estoque)
            // - PUBLICADO: qualquer nutricionista pode excluir, mas precisa devolver estoque se foi descontado
            // - REJEITADO: qualquer nutricionista pode excluir (não foi aprovado)
            // - APROVADO: não pode excluir (foi aprovado)
            if ($cardapio['status'] === 'APROVADO') {
                return ['success' => false, 'message' => 'Cardápios aprovados não podem ser excluídos'];
            }
            
            // Para PUBLICADO, verificar se descontou estoque e devolver se necessário
            // Mas permitir que qualquer nutricionista exclua (não apenas o criador)
            
            $conn->beginTransaction();
            
            // Se estava PUBLICADO, devolver estoque (pois foi descontado quando foi publicado)
            if ($cardapio['status'] === 'PUBLICADO') {
                try {
                    $this->devolverEstoque($id);
                } catch (Exception $e) {
                    $conn->rollBack();
                    return ['success' => false, 'message' => 'Erro ao devolver estoque: ' . $e->getMessage()];
                }
            }
            // RASCUNHO e REJEITADO não descontam estoque, então não precisam devolver
            
            // Excluir itens
            $sqlDeleteItens = "DELETE FROM cardapio_item WHERE cardapio_id = :cardapio_id";
            $stmtDeleteItens = $conn->prepare($sqlDeleteItens);
            $stmtDeleteItens->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
            $stmtDeleteItens->execute();
            
            // Excluir semanas
            $sqlDeleteSemanas = "DELETE FROM cardapio_semana WHERE cardapio_id = :cardapio_id";
            $stmtDeleteSemanas = $conn->prepare($sqlDeleteSemanas);
            $stmtDeleteSemanas->bindParam(':cardapio_id', $id, PDO::PARAM_INT);
            $stmtDeleteSemanas->execute();
            
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
     * Cancela envio do cardápio (volta de PUBLICADO para RASCUNHO)
     * Apenas se o cardápio foi criado pelo usuário logado
     * Devolve produtos ao estoque
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
            
            $conn->beginTransaction();
            
            // Devolver produtos ao estoque (pois estava PUBLICADO ou APROVADO)
            try {
                $this->devolverEstoque($id);
            } catch (Exception $e) {
                $conn->rollBack();
                return ['success' => false, 'message' => 'Erro ao devolver estoque: ' . $e->getMessage()];
            }
            
            // Voltar para RASCUNHO
            $sql = "UPDATE cardapio SET status = 'RASCUNHO', atualizado_em = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

?>

