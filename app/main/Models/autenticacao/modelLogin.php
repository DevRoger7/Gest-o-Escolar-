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
        
        try {
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
        } catch (PDOException $e) {
            // Verificar se o erro é de tabela não encontrada
            if (strpos($e->getMessage(), "doesn't exist") !== false || 
                strpos($e->getMessage(), "Base table or view not found") !== false) {
                $erroMsg = "ERRO CRÍTICO: Tabela 'usuario' não existe no banco de dados 'escola_merenda'. ";
                $erroMsg .= "Erro PDO: " . $e->getMessage();
                error_log($erroMsg);
                
                // Tentar verificar qual tabela está faltando
                try {
                    $sqlCheck = "SHOW TABLES LIKE 'usuario'";
                    $stmtCheck = $conn->query($sqlCheck);
                    if ($stmtCheck->rowCount() == 0) {
                        throw new Exception("A tabela 'usuario' não foi encontrada no banco de dados 'escola_merenda'. Por favor, execute o script SQL de criação da tabela em: app/main/database/create_table_usuario.sql");
                    }
                } catch (Exception $checkEx) {
                    throw new Exception("Erro ao verificar tabela: " . $checkEx->getMessage());
                }
                
                // Se chegou aqui, a tabela existe mas há outro problema
                throw new Exception("Erro ao acessar tabela 'usuario': " . $e->getMessage());
            }
            
            // Log de outros erros PDO para debug
            error_log("ERRO PDO no login: " . $e->getMessage() . " | Código: " . $e->getCode());
            
            // Re-lançar outros erros PDO
            throw $e;
        }
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar se o usuário existe e se a senha está correta usando password_verify
        if ($resultado && password_verify($senha, $resultado['senha_hash'])) {
            // Senha correta, mas ANTES de criar a sessão, verificar se a escola está ativa
            
            $tipoUsuario = $resultado['role'] ?? '';
            $tiposComEscola = ['GESTAO', 'PROFESSOR', 'NUTRICIONISTA'];
            $usuarioId = $resultado['id'];
            
            // Verificar ANTES de criar sessão se o usuário tem escola ativa
            // IMPORTANTE: Verificar tanto nas tabelas principais quanto no backup
            if (in_array(strtoupper($tipoUsuario), $tiposComEscola)) {
                $escolaAtiva = false;
                $tinhaLotacao = false;
                $estaNoBackup = false;
                
                // Primeiro, verificar se tem lotação ATIVA com escola ATIVA nas tabelas principais
                if (strtoupper($tipoUsuario) === 'GESTAO') {
                    // Verificar se tem lotação ATIVA com escola ATIVA
                    $sqlAtiva = "SELECT COUNT(*) as total 
                                FROM gestor_lotacao gl 
                                INNER JOIN escola e ON gl.escola_id = e.id 
                                INNER JOIN gestor g ON gl.gestor_id = g.id 
                                INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id 
                                WHERE u.id = :usuario_id 
                                AND e.ativo = 1 
                                AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00')";
                    $stmtAtiva = $conn->prepare($sqlAtiva);
                    $stmtAtiva->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmtAtiva->execute();
                    $resultAtiva = $stmtAtiva->fetch(PDO::FETCH_ASSOC);
                    $escolaAtiva = ($resultAtiva && $resultAtiva['total'] > 0);
                    
                    // Verificar se já teve alguma lotação (nas tabelas principais)
                    $sqlLotacao = "SELECT COUNT(*) as total FROM gestor_lotacao gl 
                                   INNER JOIN gestor g ON gl.gestor_id = g.id 
                                   INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id 
                                   WHERE u.id = :usuario_id";
                    $stmtLotacao = $conn->prepare($sqlLotacao);
                    $stmtLotacao->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmtLotacao->execute();
                    $resultLotacao = $stmtLotacao->fetch(PDO::FETCH_ASSOC);
                    $tinhaLotacao = ($resultLotacao && $resultLotacao['total'] > 0);
                    
                    // Se não tem lotação ativa, verificar se está no backup
                    if (!$escolaAtiva) {
                        // Buscar pessoa_id do gestor
                        $sqlPessoa = "SELECT g.pessoa_id FROM gestor g 
                                     INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id 
                                     WHERE u.id = :usuario_id LIMIT 1";
                        $stmtPessoa = $conn->prepare($sqlPessoa);
                        $stmtPessoa->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                        $stmtPessoa->execute();
                        $pessoaData = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
                        
                        if ($pessoaData && isset($pessoaData['pessoa_id'])) {
                            // Verificar se há backup com lotação deste gestor
                            $sqlBackup = "SELECT COUNT(*) as total FROM escola_backup eb
                                         WHERE eb.revertido = 0 
                                         AND eb.excluido_permanentemente = 0
                                         AND JSON_EXTRACT(eb.dados_lotacoes, '$.gestores[*].gestor_id') IS NOT NULL
                                         AND EXISTS (
                                             SELECT 1 FROM gestor g 
                                             WHERE g.pessoa_id = :pessoa_id 
                                             AND JSON_CONTAINS(eb.dados_lotacoes, JSON_OBJECT('gestor_id', g.id), '$.gestores')
                                         )";
                            $stmtBackup = $conn->prepare($sqlBackup);
                            $stmtBackup->bindParam(':pessoa_id', $pessoaData['pessoa_id'], PDO::PARAM_INT);
                            $stmtBackup->execute();
                            $resultBackup = $stmtBackup->fetch(PDO::FETCH_ASSOC);
                            $estaNoBackup = ($resultBackup && $resultBackup['total'] > 0);
                        }
                    }
                    
                } elseif (strtoupper($tipoUsuario) === 'PROFESSOR') {
                    $sqlAtiva = "SELECT COUNT(*) as total 
                                FROM professor_lotacao pl 
                                INNER JOIN escola e ON pl.escola_id = e.id 
                                INNER JOIN professor p ON pl.professor_id = p.id 
                                INNER JOIN usuario u ON p.pessoa_id = u.pessoa_id 
                                WHERE u.id = :usuario_id 
                                AND e.ativo = 1 
                                AND (pl.fim IS NULL OR pl.fim = '' OR pl.fim = '0000-00-00')";
                    $stmtAtiva = $conn->prepare($sqlAtiva);
                    $stmtAtiva->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmtAtiva->execute();
                    $resultAtiva = $stmtAtiva->fetch(PDO::FETCH_ASSOC);
                    $escolaAtiva = ($resultAtiva && $resultAtiva['total'] > 0);
                    
                    $sqlLotacao = "SELECT COUNT(*) as total FROM professor_lotacao pl 
                                   INNER JOIN professor p ON pl.professor_id = p.id 
                                   INNER JOIN usuario u ON p.pessoa_id = u.pessoa_id 
                                   WHERE u.id = :usuario_id";
                    $stmtLotacao = $conn->prepare($sqlLotacao);
                    $stmtLotacao->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmtLotacao->execute();
                    $resultLotacao = $stmtLotacao->fetch(PDO::FETCH_ASSOC);
                    $tinhaLotacao = ($resultLotacao && $resultLotacao['total'] > 0);
                    
                    // Verificar backup
                    if (!$escolaAtiva) {
                        $sqlPessoa = "SELECT p.pessoa_id FROM professor p 
                                     INNER JOIN usuario u ON p.pessoa_id = u.pessoa_id 
                                     WHERE u.id = :usuario_id LIMIT 1";
                        $stmtPessoa = $conn->prepare($sqlPessoa);
                        $stmtPessoa->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                        $stmtPessoa->execute();
                        $pessoaData = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
                        
                        if ($pessoaData && isset($pessoaData['pessoa_id'])) {
                            $sqlBackup = "SELECT COUNT(*) as total FROM escola_backup eb
                                         WHERE eb.revertido = 0 
                                         AND eb.excluido_permanentemente = 0
                                         AND EXISTS (
                                             SELECT 1 FROM professor pr 
                                             WHERE pr.pessoa_id = :pessoa_id 
                                             AND JSON_CONTAINS(eb.dados_lotacoes, JSON_OBJECT('professor_id', pr.id), '$.professores')
                                         )";
                            $stmtBackup = $conn->prepare($sqlBackup);
                            $stmtBackup->bindParam(':pessoa_id', $pessoaData['pessoa_id'], PDO::PARAM_INT);
                            $stmtBackup->execute();
                            $resultBackup = $stmtBackup->fetch(PDO::FETCH_ASSOC);
                            $estaNoBackup = ($resultBackup && $resultBackup['total'] > 0);
                        }
                    }
                    
                } elseif (strtoupper($tipoUsuario) === 'NUTRICIONISTA') {
                    $sqlAtiva = "SELECT COUNT(*) as total 
                                FROM nutricionista_lotacao nl 
                                INNER JOIN escola e ON nl.escola_id = e.id 
                                INNER JOIN nutricionista n ON nl.nutricionista_id = n.id 
                                INNER JOIN usuario u ON n.pessoa_id = u.pessoa_id 
                                WHERE u.id = :usuario_id 
                                AND e.ativo = 1 
                                AND (nl.fim IS NULL OR nl.fim = '' OR nl.fim = '0000-00-00')";
                    $stmtAtiva = $conn->prepare($sqlAtiva);
                    $stmtAtiva->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmtAtiva->execute();
                    $resultAtiva = $stmtAtiva->fetch(PDO::FETCH_ASSOC);
                    $escolaAtiva = ($resultAtiva && $resultAtiva['total'] > 0);
                    
                    $sqlLotacao = "SELECT COUNT(*) as total FROM nutricionista_lotacao nl 
                                   INNER JOIN nutricionista n ON nl.nutricionista_id = n.id 
                                   INNER JOIN usuario u ON n.pessoa_id = u.pessoa_id 
                                   WHERE u.id = :usuario_id";
                    $stmtLotacao = $conn->prepare($sqlLotacao);
                    $stmtLotacao->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmtLotacao->execute();
                    $resultLotacao = $stmtLotacao->fetch(PDO::FETCH_ASSOC);
                    $tinhaLotacao = ($resultLotacao && $resultLotacao['total'] > 0);
                    
                    // Verificar backup
                    if (!$escolaAtiva) {
                        $sqlPessoa = "SELECT n.pessoa_id FROM nutricionista n 
                                     INNER JOIN usuario u ON n.pessoa_id = u.pessoa_id 
                                     WHERE u.id = :usuario_id LIMIT 1";
                        $stmtPessoa = $conn->prepare($sqlPessoa);
                        $stmtPessoa->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                        $stmtPessoa->execute();
                        $pessoaData = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
                        
                        if ($pessoaData && isset($pessoaData['pessoa_id'])) {
                            $sqlBackup = "SELECT COUNT(*) as total FROM escola_backup eb
                                         WHERE eb.revertido = 0 
                                         AND eb.excluido_permanentemente = 0
                                         AND EXISTS (
                                             SELECT 1 FROM nutricionista nu 
                                             WHERE nu.pessoa_id = :pessoa_id 
                                             AND JSON_CONTAINS(eb.dados_lotacoes, JSON_OBJECT('nutricionista_id', nu.id), '$.nutricionistas')
                                         )";
                            $stmtBackup = $conn->prepare($sqlBackup);
                            $stmtBackup->bindParam(':pessoa_id', $pessoaData['pessoa_id'], PDO::PARAM_INT);
                            $stmtBackup->execute();
                            $resultBackup = $stmtBackup->fetch(PDO::FETCH_ASSOC);
                            $estaNoBackup = ($resultBackup && $resultBackup['total'] > 0);
                        }
                    }
                }
                
                // Se não tem escola ativa E (já teve lotação OU está no backup), BLOQUEAR login
                if (!$escolaAtiva && ($tinhaLotacao || $estaNoBackup)) {
                    error_log("LOGIN BLOQUEADO - Usuário ID: $usuarioId, Tipo: $tipoUsuario - Escola inativa ou no backup");
                    return ['sem_escola' => true];
                }
            }
            
            // Se passou na verificação, continuar com o login
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
                    
                    if ($pessoaId) {
                        // Buscar o ID do gestor usando pessoa_id
                        $sqlGestor = "SELECT g.id as gestor_id
                                      FROM gestor g
                                      WHERE g.pessoa_id = :pessoa_id AND g.ativo = 1
                                      LIMIT 1";
                        $stmtGestor = $conn->prepare($sqlGestor);
                        $stmtGestor->bindParam(':pessoa_id', $pessoaId);
                        $stmtGestor->execute();
                        $gestorData = $stmtGestor->fetch(PDO::FETCH_ASSOC);
                        
                        if ($gestorData && isset($gestorData['gestor_id'])) {
                            $gestorId = (int)$gestorData['gestor_id'];
                            
                            // Buscar TODAS as escolas ativas do gestor
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
                            
                            // Pegar a primeira escola (já está ordenada por responsável)
                            if (!empty($todasEscolas)) {
                                $escolaData = $todasEscolas[0];
                                $escolaSelecionadaId = (int)$escolaData['escola_id'];
                                $escolaSelecionadaNome = $escolaData['escola_nome'];
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("ERRO ao buscar escola do gestor no login: " . $e->getMessage());
                }
            }
            
            // Definir escola na sessão (apenas se realmente tiver escola)
            if ($escolaSelecionadaId && $escolaSelecionadaNome) {
                $_SESSION['escola_atual'] = $escolaSelecionadaNome;
                $_SESSION['escola_selecionada_id'] = $escolaSelecionadaId;
                $_SESSION['escola_selecionada_nome'] = $escolaSelecionadaNome;
            } else {
                // Não definir escola padrão - deixar null se não tiver escola
                $_SESSION['escola_atual'] = null;
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
