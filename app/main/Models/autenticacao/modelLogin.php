<?php



Class ModelLogin {
    public function login($cpfOuEmail, $senha) {
        // Inicia a sessão se ainda não foi iniciada
        if (session_status() == PHP_SESSION_NONE) {
            // Configura o tempo de vida da sessão para 24 horas
            $lifetime = 24 * 60 * 60; // 24 horas em segundos
            session_set_cookie_params($lifetime);
            ini_set('session.gc_maxlifetime', $lifetime);
            session_start();
        }
        
        require_once("../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar se é email ou CPF
        $isEmail = filter_var($cpfOuEmail, FILTER_VALIDATE_EMAIL);
        
        if ($isEmail) {
            // Buscar por email
            $sql = "SELECT u.*, p.* FROM usuario u 
                    INNER JOIN pessoa p ON u.pessoa_id = p.id 
                    WHERE p.email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$cpfOuEmail]);
        } else {
            // Remove pontos e hífens do CPF, mantendo apenas números
            $cpf = preg_replace('/[^0-9]/', '', $cpfOuEmail);
            
            // Buscar por CPF
            $sql = "SELECT u.*, p.* FROM usuario u 
                    INNER JOIN pessoa p ON u.pessoa_id = p.id 
                    WHERE p.cpf = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$cpf]);
        }
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar se o usuário existe e se a senha está correta usando password_verify
        if ($resultado && password_verify($senha, $resultado['senha_hash'])) {
            // Senha correta, continuar com o login
            
            // Definir o fuso horário para América/Sao_Paulo (GMT-3)
            date_default_timezone_set('America/Sao_Paulo');
            
            // Atualizar o campo ultimo_login com a data e hora atual no fuso horário correto
            $dataHoraAtual = date('Y-m-d H:i:s');
            $sqlAtualizarLogin = "UPDATE usuario SET ultimo_login = :ultimo_login WHERE id = :id";
            $stmtAtualizarLogin = $conn->prepare($sqlAtualizarLogin);
            $stmtAtualizarLogin->bindParam(':ultimo_login', $dataHoraAtual);
            $stmtAtualizarLogin->bindParam(':id', $resultado['id']);
            $stmtAtualizarLogin->execute();
            
            // Criar as sessões com os dados do usuário
            $_SESSION['logado'] = true;
            $_SESSION['usuario_id'] = $resultado['id'];
            $_SESSION['pessoa_id'] = $resultado['pessoa_id'];
            $_SESSION['nome'] = $resultado['nome'];
            $_SESSION['email'] = $resultado['email'];
            $_SESSION['cpf'] = $resultado['cpf'];
            $_SESSION['telefone'] = $resultado['telefone'] ?? '';
            $_SESSION['tipo'] = $resultado['role'] ?? 'Professor';
            
            // Buscar escola do gestor se for tipo GESTAO
            $escolaSelecionadaId = null;
            $escolaSelecionadaNome = null;
            
            if (strtoupper($resultado['role'] ?? '') === 'GESTAO') {
                try {
                    $pessoaId = $resultado['pessoa_id'] ?? null;
                    error_log("DEBUG LOGIN - Buscando gestor para pessoa_id: " . $pessoaId);
                    
                    if (!$pessoaId) {
                        error_log("DEBUG LOGIN - pessoa_id não encontrado no resultado do login");
                    } else {
                        // Buscar o ID do gestor usando pessoa_id (não usuario_id)
                        $sqlGestor = "SELECT g.id as gestor_id
                                      FROM gestor g
                                      WHERE g.pessoa_id = :pessoa_id AND g.ativo = 1
                                      LIMIT 1";
                        $stmtGestor = $conn->prepare($sqlGestor);
                        $stmtGestor->bindParam(':pessoa_id', $pessoaId);
                        $stmtGestor->execute();
                        $gestorData = $stmtGestor->fetch(PDO::FETCH_ASSOC);
                        
                        error_log("DEBUG LOGIN - SQL gestor: " . $sqlGestor);
                        error_log("DEBUG LOGIN - pessoa_id usado: " . $pessoaId);
                        error_log("DEBUG LOGIN - Resultado busca gestor: " . json_encode($gestorData));
                        
                        if ($gestorData && isset($gestorData['gestor_id'])) {
                            $gestorId = (int)$gestorData['gestor_id'];
                            error_log("DEBUG LOGIN - Gestor encontrado, ID: " . $gestorId);
                            
                            // Buscar TODAS as escolas ativas do gestor (sem LIMIT para ver todas)
                            $sqlEscolas = "SELECT DISTINCT 
                                             gl.escola_id, 
                                             e.nome as escola_nome, 
                                             MAX(gl.responsavel) as responsavel,
                                             MAX(gl.inicio) as inicio
                                           FROM gestor_lotacao gl
                                           INNER JOIN escola e ON gl.escola_id = e.id
                                           WHERE gl.gestor_id = :gestor_id
                                           AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' OR gl.fim >= CURDATE())
                                           AND e.ativo = 1
                                           GROUP BY gl.escola_id, e.nome
                                           ORDER BY 
                                             MAX(gl.responsavel) DESC,
                                             MAX(gl.inicio) DESC,
                                             e.nome ASC";
                            $stmtEscolas = $conn->prepare($sqlEscolas);
                            $stmtEscolas->bindParam(':gestor_id', $gestorId);
                            $stmtEscolas->execute();
                            $todasEscolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
                            
                            error_log("DEBUG LOGIN - SQL escolas: " . $sqlEscolas);
                            error_log("DEBUG LOGIN - Gestor ID usado: " . $gestorId);
                            error_log("DEBUG LOGIN - Total de escolas encontradas: " . count($todasEscolas));
                            error_log("DEBUG LOGIN - Todas as escolas do gestor: " . json_encode($todasEscolas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                            
                            // Pegar a primeira escola (já está ordenada por responsável)
                            if (!empty($todasEscolas)) {
                                $escolaData = $todasEscolas[0];
                                $escolaSelecionadaId = (int)$escolaData['escola_id'];
                                $escolaSelecionadaNome = $escolaData['escola_nome'];
                                error_log("DEBUG LOGIN - Escola selecionada: ID=" . $escolaSelecionadaId . ", Nome=" . $escolaSelecionadaNome);
                            } else {
                                error_log("DEBUG LOGIN - Nenhuma escola ativa encontrada para o gestor ID=" . $gestorId);
                            }
                        } else {
                            error_log("DEBUG LOGIN - Gestor não encontrado para pessoa_id=" . $pessoaId);
                        }
                    }
                } catch (Exception $e) {
                    error_log("ERRO ao buscar escola do gestor no login: " . $e->getMessage());
                    error_log("ERRO - Stack trace: " . $e->getTraceAsString());
                }
            } else {
                error_log("DEBUG LOGIN - Usuário não é GESTAO, role: " . ($resultado['role'] ?? 'NULL'));
            }
            
            // Definir escola na sessão
            if ($escolaSelecionadaId && $escolaSelecionadaNome) {
                $_SESSION['escola_atual'] = $escolaSelecionadaNome;
                $_SESSION['escola_selecionada_id'] = $escolaSelecionadaId;
                $_SESSION['escola_selecionada_nome'] = $escolaSelecionadaNome;
            } else {
                $_SESSION['escola_atual'] = 'Escola Municipal';
                $_SESSION['escola_selecionada_id'] = null;
                $_SESSION['escola_selecionada_nome'] = null;
            }
            
            // Definir permissões baseadas no tipo de usuário usando PermissionManager
            require_once("../../Models/permissions/PermissionManager.php");
            PermissionManager::definirPermissoes($resultado['role'] ?? '');
            
            return $resultado;
        } else {
            // Usuário não encontrado ou senha incorreta
            return false;
        }
    }
    
    // Método removido - agora usa PermissionManager
    // As permissões são definidas em Models/permissions/PermissionManager.php
}

?>
