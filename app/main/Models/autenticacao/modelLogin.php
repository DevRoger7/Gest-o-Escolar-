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
            $_SESSION['escola_atual'] = 'Escola Municipal';
            
            // Definir permissões baseadas no tipo de usuário usando PermissionManager
            require_once("../../Models/permissions/PermissionManager.php");
            PermissionManager::definirPermissoes($resultado['role'] ?? '');
            
            // Verificar se a escola do usuário ainda existe (antes de permitir login)
            $tipoUsuario = $resultado['role'] ?? '';
            $tiposComEscola = ['GESTAO', 'PROFESSOR', 'NUTRICIONISTA'];
            
            if (in_array(strtoupper($tipoUsuario), $tiposComEscola)) {
                $escolaExiste = false;
                $usuarioId = $resultado['id'];
                
                if (strtoupper($tipoUsuario) === 'GESTAO') {
                    $sql = "SELECT COUNT(*) as total 
                            FROM gestor_lotacao gl 
                            INNER JOIN escola e ON gl.escola_id = e.id 
                            INNER JOIN gestor g ON gl.gestor_id = g.id 
                            INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id 
                            WHERE u.id = :usuario_id AND e.ativo = 1 
                            AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmt->execute();
                    $resultCheck = $stmt->fetch(PDO::FETCH_ASSOC);
                    $escolaExiste = ($resultCheck && $resultCheck['total'] > 0);
                } elseif (strtoupper($tipoUsuario) === 'PROFESSOR') {
                    $sql = "SELECT COUNT(*) as total 
                            FROM professor_lotacao pl 
                            INNER JOIN escola e ON pl.escola_id = e.id 
                            INNER JOIN professor p ON pl.professor_id = p.id 
                            INNER JOIN usuario u ON p.pessoa_id = u.pessoa_id 
                            WHERE u.id = :usuario_id AND e.ativo = 1 
                            AND (pl.fim IS NULL OR pl.fim = '' OR pl.fim = '0000-00-00')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmt->execute();
                    $resultCheck = $stmt->fetch(PDO::FETCH_ASSOC);
                    $escolaExiste = ($resultCheck && $resultCheck['total'] > 0);
                } elseif (strtoupper($tipoUsuario) === 'NUTRICIONISTA') {
                    $sql = "SELECT COUNT(*) as total 
                            FROM nutricionista_lotacao nl 
                            INNER JOIN escola e ON nl.escola_id = e.id 
                            INNER JOIN nutricionista n ON nl.nutricionista_id = n.id 
                            INNER JOIN usuario u ON n.pessoa_id = u.pessoa_id 
                            WHERE u.id = :usuario_id AND e.ativo = 1 
                            AND (nl.fim IS NULL OR nl.fim = '' OR nl.fim = '0000-00-00')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmt->execute();
                    $resultCheck = $stmt->fetch(PDO::FETCH_ASSOC);
                    $escolaExiste = ($resultCheck && $resultCheck['total'] > 0);
                }
                
                // Se não tem escola ativa, retornar código especial
                if (!$escolaExiste) {
                    // Destruir sessão parcial criada
                    $_SESSION = array();
                    session_destroy();
                    return ['sem_escola' => true]; // Retorna código especial
                }
            }
            
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
