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
        
        // Se usuário não encontrado, retornar false
        if (!$resultado) {
            // Registrar tentativa de login com usuário inexistente
            require_once(__DIR__ . '/../log/SystemLogger.php');
            $logger = SystemLogger::getInstance();
            $logger->logLoginFalha($cpfOuEmail, 'Usuário não encontrado');
            return false;
        }
        
        // Verificar se é aluno tentando fazer login com email - alunos só podem usar CPF
        $tipoUsuario = $resultado['role'] ?? '';
        if (strtoupper($tipoUsuario) === 'ALUNO' && $isEmail) {
            // Aluno tentou fazer login com email - não permitido
            require_once(__DIR__ . '/../log/SystemLogger.php');
            $logger = SystemLogger::getInstance();
            $logger->logLoginFalha($cpfOuEmail, 'Aluno tentou fazer login com email (apenas CPF permitido)');
            return false;
        }
        
        // Verificar se a conta está bloqueada por tentativas
        $tentativasLogin = (int)($resultado['tentativas_login'] ?? 0);
        $bloqueadoAte = $resultado['bloqueado_ate'] ?? null;
        
        // Se está bloqueado, verificar se o bloqueio já expirou
        if ($bloqueadoAte) {
            $dataBloqueio = strtotime($bloqueadoAte);
            $dataAtual = time();
            
            // Se ainda está bloqueado, retornar erro
            if ($dataBloqueio > $dataAtual) {
                $tempoRestante = $dataBloqueio - $dataAtual;
                $minutosRestantes = ceil($tempoRestante / 60);
                error_log("Tentativa de login bloqueada para usuário ID: " . $resultado['id'] . " - Bloqueado até: " . $bloqueadoAte);
                
                // Registrar tentativa de login em conta bloqueada
                require_once(__DIR__ . '/../log/SystemLogger.php');
                $logger = SystemLogger::getInstance();
                $logger->logLoginFalha($cpfOuEmail, "Conta bloqueada - {$minutosRestantes} minutos restantes");
                
                return ['bloqueado' => true, 'minutos_restantes' => $minutosRestantes];
            } else {
                // Bloqueio expirou, resetar tentativas
                $sqlResetBloqueio = "UPDATE usuario SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id";
                $stmtResetBloqueio = $conn->prepare($sqlResetBloqueio);
                $stmtResetBloqueio->bindParam(':id', $resultado['id'], PDO::PARAM_INT);
                $stmtResetBloqueio->execute();
                $tentativasLogin = 0;
            }
        }
        
        // Verificar se é aluno - alunos usam CPF como senha também
        $isAluno = (strtoupper($tipoUsuario) === 'ALUNO');
        
        // Para alunos, validar se a senha digitada é o CPF (sem formatação)
        $senhaValida = false;
        if ($isAluno) {
            // Remove formatação do CPF da senha digitada
            $senhaCpf = preg_replace('/[^0-9]/', '', $senha);
            // Remove formatação do CPF do usuário
            $cpfUsuario = preg_replace('/[^0-9]/', '', $resultado['cpf']);
            // Comparar CPFs
            $senhaValida = ($senhaCpf === $cpfUsuario && strlen($senhaCpf) === 11);
        } else {
            // Para outros usuários, usar validação normal de senha
            $senhaValida = password_verify($senha, $resultado['senha_hash']);
        }
        
        // Verificar se o usuário existe e se a senha está correta
        if ($senhaValida) {
            // Senha correta, mas ANTES de criar a sessão, verificar se a escola está ativa
            
            // Nutricionistas não têm escola fixa, trabalham para todas as escolas
            $tiposComEscola = ['GESTAO', 'PROFESSOR'];
            $usuarioId = $resultado['id'];
            
            // Verificar ANTES de criar sessão se o usuário tem escola ativa
            // IMPORTANTE: Verificar tanto nas tabelas principais quanto no backup
            // NUTRICIONISTA não precisa verificar porque trabalha para todas as escolas
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
                        // Buscar gestor_id do gestor
                        $sqlGestor = "SELECT g.id as gestor_id FROM gestor g 
                                     INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id 
                                     WHERE u.id = :usuario_id LIMIT 1";
                        $stmtGestor = $conn->prepare($sqlGestor);
                        $stmtGestor->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                        $stmtGestor->execute();
                        $gestorData = $stmtGestor->fetch(PDO::FETCH_ASSOC);
                        
                        if ($gestorData && isset($gestorData['gestor_id'])) {
                            $gestorId = (int)$gestorData['gestor_id'];
                            
                            // Buscar todos os backups não revertidos e verificar se contém este gestor
                            $sqlBackup = "SELECT dados_lotacoes FROM escola_backup eb
                                         WHERE eb.revertido = 0 
                                         AND eb.excluido_permanentemente = 0";
                            $stmtBackup = $conn->prepare($sqlBackup);
                            $stmtBackup->execute();
                            $backups = $stmtBackup->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($backups as $backup) {
                                $lotacoes = json_decode($backup['dados_lotacoes'], true);
                                if ($lotacoes && isset($lotacoes['gestores']) && is_array($lotacoes['gestores'])) {
                                    foreach ($lotacoes['gestores'] as $lotacao) {
                                        if (isset($lotacao['gestor_id']) && (int)$lotacao['gestor_id'] === $gestorId) {
                                            $estaNoBackup = true;
                                            break 2;
                                        }
                                    }
                                }
                            }
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
                        // Buscar professor_id do professor
                        $sqlProfessor = "SELECT p.id as professor_id FROM professor p 
                                     INNER JOIN usuario u ON p.pessoa_id = u.pessoa_id 
                                     WHERE u.id = :usuario_id LIMIT 1";
                        $stmtProfessor = $conn->prepare($sqlProfessor);
                        $stmtProfessor->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                        $stmtProfessor->execute();
                        $professorData = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
                        
                        if ($professorData && isset($professorData['professor_id'])) {
                            $professorId = (int)$professorData['professor_id'];
                            
                            // Buscar todos os backups não revertidos e verificar se contém este professor
                            $sqlBackup = "SELECT dados_lotacoes FROM escola_backup eb
                                         WHERE eb.revertido = 0 
                                         AND eb.excluido_permanentemente = 0";
                            $stmtBackup = $conn->prepare($sqlBackup);
                            $stmtBackup->execute();
                            $backups = $stmtBackup->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($backups as $backup) {
                                $lotacoes = json_decode($backup['dados_lotacoes'], true);
                                if ($lotacoes && isset($lotacoes['professores']) && is_array($lotacoes['professores'])) {
                                    foreach ($lotacoes['professores'] as $lotacao) {
                                        if (isset($lotacao['professor_id']) && (int)$lotacao['professor_id'] === $professorId) {
                                            $estaNoBackup = true;
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                }
                // NUTRICIONISTA não precisa verificar escola - trabalha para todas as escolas
                
                // Se não tem escola ativa E (já teve lotação OU está no backup), BLOQUEAR login
                if (!$escolaAtiva && ($tinhaLotacao || $estaNoBackup)) {
                    error_log("LOGIN BLOQUEADO - Usuário ID: $usuarioId, Tipo: $tipoUsuario - Escola inativa ou no backup");
                    return ['sem_escola' => true];
                }
            }
            
            // Se passou na verificação, continuar com o login
            // Definir o fuso horário para América/Sao_Paulo (GMT-3)
            date_default_timezone_set('America/Sao_Paulo');
            
            // Atualizar o campo ultimo_login, ultimo_acesso, zerar tentativas_login e remover bloqueio
            date_default_timezone_set('America/Sao_Paulo');
            $dataHoraAtual = date('Y-m-d H:i:s');
            
            $sqlAtualizarLogin = "UPDATE usuario SET 
                                    ultimo_login = :ultimo_login,
                                    ultimo_acesso = :ultimo_acesso,
                                    tentativas_login = 0,
                                    bloqueado_ate = NULL
                                  WHERE id = :id";
            $stmtAtualizarLogin = $conn->prepare($sqlAtualizarLogin);
            $stmtAtualizarLogin->bindParam(':ultimo_login', $dataHoraAtual);
            $stmtAtualizarLogin->bindParam(':ultimo_acesso', $dataHoraAtual);
            $stmtAtualizarLogin->bindParam(':id', $resultado['id'], PDO::PARAM_INT);
            $stmtAtualizarLogin->execute();
            
            error_log("Login bem-sucedido - Usuário ID: " . $resultado['id'] . " - Tentativas resetadas");
            
            // Registrar login bem-sucedido no log do sistema
            require_once(__DIR__ . '/../log/SystemLogger.php');
            $logger = SystemLogger::getInstance();
            $logger->logLogin($resultado['id'], $resultado['username'] ?? $resultado['nome']);
            
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
            } elseif (strtoupper($resultado['role'] ?? '') === 'ALUNO') {
                try {
                    $pessoaId = $resultado['pessoa_id'] ?? null;
                    
                    if ($pessoaId) {
                        // Buscar a escola do aluno: primeiro do campo escola_id, depois da turma
                        $sqlEscolaAluno = "SELECT COALESCE(e_direta.id, e_turma.id) as escola_id,
                                                  COALESCE(e_direta.nome, e_turma.nome) as escola_nome
                                           FROM aluno a
                                           LEFT JOIN escola e_direta ON a.escola_id = e_direta.id
                                           LEFT JOIN (
                                               SELECT at1.aluno_id, at1.turma_id
                                               FROM aluno_turma at1
                                               INNER JOIN (
                                                   SELECT aluno_id, MAX(inicio) as max_inicio
                                                   FROM aluno_turma
                                                   GROUP BY aluno_id
                                               ) at_max ON at1.aluno_id = at_max.aluno_id AND at1.inicio = at_max.max_inicio
                                           ) at ON a.id = at.aluno_id
                                           LEFT JOIN turma t ON at.turma_id = t.id
                                           LEFT JOIN escola e_turma ON t.escola_id = e_turma.id
                                           WHERE a.pessoa_id = :pessoa_id 
                                           AND a.ativo = 1
                                           LIMIT 1";
                        $stmtEscolaAluno = $conn->prepare($sqlEscolaAluno);
                        $stmtEscolaAluno->bindParam(':pessoa_id', $pessoaId);
                        $stmtEscolaAluno->execute();
                        $escolaAluno = $stmtEscolaAluno->fetch(PDO::FETCH_ASSOC);
                        
                        if ($escolaAluno && !empty($escolaAluno['escola_id']) && !empty($escolaAluno['escola_nome'])) {
                            $escolaSelecionadaId = (int)$escolaAluno['escola_id'];
                            $escolaSelecionadaNome = $escolaAluno['escola_nome'];
                        }
                    }
                } catch (Exception $e) {
                    error_log("ERRO ao buscar escola do aluno no login: " . $e->getMessage());
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
            // Senha incorreta - incrementar tentativas de login
            $tentativasLogin++;
            $maxTentativas = 5; // Máximo de tentativas antes de bloquear
            $tempoBloqueioMinutos = 30; // Tempo de bloqueio em minutos
            
            date_default_timezone_set('America/Sao_Paulo');
            $dataHoraAtual = date('Y-m-d H:i:s');
            
            // Registrar tentativa de login falha
            require_once(__DIR__ . '/../log/SystemLogger.php');
            $logger = SystemLogger::getInstance();
            
            if ($tentativasLogin >= $maxTentativas) {
                // Bloquear conta por 30 minutos
                $dataBloqueio = date('Y-m-d H:i:s', strtotime("+{$tempoBloqueioMinutos} minutes"));
                
                $sqlAtualizarTentativas = "UPDATE usuario SET 
                                            tentativas_login = :tentativas,
                                            bloqueado_ate = :bloqueado_ate
                                          WHERE id = :id";
                $stmtAtualizarTentativas = $conn->prepare($sqlAtualizarTentativas);
                $stmtAtualizarTentativas->bindParam(':tentativas', $tentativasLogin, PDO::PARAM_INT);
                $stmtAtualizarTentativas->bindParam(':bloqueado_ate', $dataBloqueio);
                $stmtAtualizarTentativas->bindParam(':id', $resultado['id'], PDO::PARAM_INT);
                $stmtAtualizarTentativas->execute();
                
                error_log("Conta bloqueada após " . $tentativasLogin . " tentativas falhas - Usuário ID: " . $resultado['id'] . " - Bloqueado até: " . $dataBloqueio);
                
                // Registrar bloqueio de conta
                $logger->logBloqueioConta($resultado['id'], "{$tentativasLogin} tentativas falhas de login");
                $logger->logLoginFalha($cpfOuEmail, "Senha incorreta - Conta bloqueada por {$tempoBloqueioMinutos} minutos");
                
                return ['bloqueado' => true, 'tentativas' => $tentativasLogin, 'minutos_restantes' => $tempoBloqueioMinutos];
            } else {
                // Apenas incrementar tentativas
                $sqlAtualizarTentativas = "UPDATE usuario SET tentativas_login = :tentativas WHERE id = :id";
                $stmtAtualizarTentativas = $conn->prepare($sqlAtualizarTentativas);
                $stmtAtualizarTentativas->bindParam(':tentativas', $tentativasLogin, PDO::PARAM_INT);
                $stmtAtualizarTentativas->bindParam(':id', $resultado['id'], PDO::PARAM_INT);
                $stmtAtualizarTentativas->execute();
                
                error_log("Tentativa de login falhou - Usuário ID: " . $resultado['id'] . " - Tentativas: " . $tentativasLogin . "/" . $maxTentativas);
                
                // Registrar tentativa de login falha
                $logger->logLoginFalha($cpfOuEmail, "Senha incorreta - Tentativa {$tentativasLogin}/{$maxTentativas}");
            }
            
            return false;
        }
    }
    
    // Método removido - agora usa PermissionManager
    // As permissões são definidas em Models/permissions/PermissionManager.php
}

?>
