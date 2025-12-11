<?php
class sessions {
    
    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            // Configura o cookie da sessão para expirar em 24 horas
            $lifetime = 24 * 60 * 60; // 24 horas em segundos
            session_set_cookie_params($lifetime);
            session_start();
        }
    }
    
    public function autenticar_session() {
        // Verifica se o usuário está logado
        if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
            // Redireciona para a página de login se não estiver logado
            header('Location: ../auth/login.php');
            exit();
        }
        
        // Verificar se a escola do usuário ainda existe (se for gestor, professor, etc)
        $this->verificar_escola_usuario();
    }
    
    private function verificar_escola_usuario() {
        // Apenas verificar para usuários que têm escola associada
        $tipoUsuario = $_SESSION['tipo'] ?? '';
        
        // Tipos que podem ter escola associada
        $tiposComEscola = ['GESTAO', 'PROFESSOR', 'NUTRICIONISTA'];
        
        if (!in_array(strtoupper($tipoUsuario), $tiposComEscola)) {
            return; // Não precisa verificar para outros tipos
        }
        
        try {
            require_once("../../config/Database.php");
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $usuarioId = $_SESSION['usuario_id'] ?? null;
            if (!$usuarioId) {
                return;
            }
            
            // Verificar se o usuário tem uma lotação ATIVA com escola ATIVA
            // IMPORTANTE: Verificar tanto nas tabelas principais quanto no backup
            $escolaExiste = false;
            $tinhaLotacao = false;
            $estaNoBackup = false;
            
            if (strtoupper($tipoUsuario) === 'GESTAO') {
                // Verificar se tem lotação ATIVA com escola ATIVA
                $sql = "SELECT COUNT(*) as total 
                        FROM gestor_lotacao gl 
                        INNER JOIN escola e ON gl.escola_id = e.id 
                        INNER JOIN gestor g ON gl.gestor_id = g.id 
                        INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id 
                        WHERE u.id = :usuario_id 
                        AND e.ativo = 1 
                        AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00')";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $escolaExiste = ($result && $result['total'] > 0);
                
                // Verificar se já teve alguma lotação (mesmo que inativa)
                $sqlCheckLotacao = "SELECT COUNT(*) as total FROM gestor_lotacao gl 
                                   INNER JOIN gestor g ON gl.gestor_id = g.id 
                                   INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id 
                                   WHERE u.id = :usuario_id";
                $stmtCheck = $conn->prepare($sqlCheckLotacao);
                $stmtCheck->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmtCheck->execute();
                $resultCheckLotacao = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                $tinhaLotacao = ($resultCheckLotacao && $resultCheckLotacao['total'] > 0);
                
                // Se não tem escola ativa, verificar se está no backup
                if (!$escolaExiste) {
                    $sqlPessoa = "SELECT g.pessoa_id FROM gestor g 
                                 INNER JOIN usuario u ON g.pessoa_id = u.pessoa_id 
                                 WHERE u.id = :usuario_id LIMIT 1";
                    $stmtPessoa = $conn->prepare($sqlPessoa);
                    $stmtPessoa->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                    $stmtPessoa->execute();
                    $pessoaData = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
                    
                    if ($pessoaData && isset($pessoaData['pessoa_id'])) {
                        // Verificar backup de forma simplificada
                        $sqlBackup = "SELECT COUNT(*) as total FROM escola_backup eb
                                     WHERE eb.revertido = 0 
                                     AND eb.excluido_permanentemente = 0
                                     AND eb.dados_lotacoes LIKE CONCAT('%', :pessoa_id, '%')";
                        $stmtBackup = $conn->prepare($sqlBackup);
                        $stmtBackup->bindParam(':pessoa_id', $pessoaData['pessoa_id'], PDO::PARAM_INT);
                        $stmtBackup->execute();
                        $resultBackup = $stmtBackup->fetch(PDO::FETCH_ASSOC);
                        $estaNoBackup = ($resultBackup && $resultBackup['total'] > 0);
                    }
                }
                
            } elseif (strtoupper($tipoUsuario) === 'PROFESSOR') {
                $sql = "SELECT COUNT(*) as total 
                        FROM professor_lotacao pl 
                        INNER JOIN escola e ON pl.escola_id = e.id 
                        INNER JOIN professor p ON pl.professor_id = p.id 
                        INNER JOIN usuario u ON p.pessoa_id = u.pessoa_id 
                        WHERE u.id = :usuario_id 
                        AND e.ativo = 1 
                        AND (pl.fim IS NULL OR pl.fim = '' OR pl.fim = '0000-00-00')";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $escolaExiste = ($result && $result['total'] > 0);
                
                $sqlCheckLotacao = "SELECT COUNT(*) as total FROM professor_lotacao pl 
                                   INNER JOIN professor p ON pl.professor_id = p.id 
                                   INNER JOIN usuario u ON p.pessoa_id = u.pessoa_id 
                                   WHERE u.id = :usuario_id";
                $stmtCheck = $conn->prepare($sqlCheckLotacao);
                $stmtCheck->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmtCheck->execute();
                $resultCheckLotacao = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                $tinhaLotacao = ($resultCheckLotacao && $resultCheckLotacao['total'] > 0);
                
                // Verificar backup
                if (!$escolaExiste) {
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
                                     AND eb.dados_lotacoes LIKE CONCAT('%', :pessoa_id, '%')";
                        $stmtBackup = $conn->prepare($sqlBackup);
                        $stmtBackup->bindParam(':pessoa_id', $pessoaData['pessoa_id'], PDO::PARAM_INT);
                        $stmtBackup->execute();
                        $resultBackup = $stmtBackup->fetch(PDO::FETCH_ASSOC);
                        $estaNoBackup = ($resultBackup && $resultBackup['total'] > 0);
                    }
                }
                
            } elseif (strtoupper($tipoUsuario) === 'NUTRICIONISTA') {
                $sql = "SELECT COUNT(*) as total 
                        FROM nutricionista_lotacao nl 
                        INNER JOIN escola e ON nl.escola_id = e.id 
                        INNER JOIN nutricionista n ON nl.nutricionista_id = n.id 
                        INNER JOIN usuario u ON n.pessoa_id = u.pessoa_id 
                        WHERE u.id = :usuario_id 
                        AND e.ativo = 1 
                        AND (nl.fim IS NULL OR nl.fim = '' OR nl.fim = '0000-00-00')";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $escolaExiste = ($result && $result['total'] > 0);
                
                $sqlCheckLotacao = "SELECT COUNT(*) as total FROM nutricionista_lotacao nl 
                                   INNER JOIN nutricionista n ON nl.nutricionista_id = n.id 
                                   INNER JOIN usuario u ON n.pessoa_id = u.pessoa_id 
                                   WHERE u.id = :usuario_id";
                $stmtCheck = $conn->prepare($sqlCheckLotacao);
                $stmtCheck->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmtCheck->execute();
                $resultCheckLotacao = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                $tinhaLotacao = ($resultCheckLotacao && $resultCheckLotacao['total'] > 0);
                
                // Verificar backup
                if (!$escolaExiste) {
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
                                     AND eb.dados_lotacoes LIKE CONCAT('%', :pessoa_id, '%')";
                        $stmtBackup = $conn->prepare($sqlBackup);
                        $stmtBackup->bindParam(':pessoa_id', $pessoaData['pessoa_id'], PDO::PARAM_INT);
                        $stmtBackup->execute();
                        $resultBackup = $stmtBackup->fetch(PDO::FETCH_ASSOC);
                        $estaNoBackup = ($resultBackup && $resultBackup['total'] > 0);
                    }
                }
            }
            
            // Se não tem escola ativa E (já teve lotação OU está no backup), redirecionar para página de sem acesso
            if (!$escolaExiste && ($tinhaLotacao || $estaNoBackup)) {
                error_log("VERIFICAÇÃO SESSAO - Escola inativa ou no backup detectada para usuário ID: " . $usuarioId . ", Tipo: " . $tipoUsuario);
                $this->destruir_session();
                header('Location: ../auth/sem_acesso.php');
                exit();
            }
        } catch (Exception $e) {
            // Em caso de erro, logar mas não bloquear o acesso
            error_log("Erro ao verificar escola do usuário: " . $e->getMessage());
            // Mas se conseguir detectar que não tem escola, ainda assim redirecionar
            try {
                // Verificação simplificada em caso de erro
                if (isset($escolaExiste) && !$escolaExiste && isset($tinhaLotacao) && $tinhaLotacao) {
                    $this->destruir_session();
                    header('Location: ../auth/sem_acesso.php');
                    exit();
                }
            } catch (Exception $e2) {
                error_log("Erro na verificação de fallback: " . $e2->getMessage());
            }
        }
    }
    
    public function tempo_session() {
        // Define tempo limite da sessão (24 horas)
        $tempo_limite = 24 * 60 * 60; // 24 horas em segundos
        
        if (isset($_SESSION['ultimo_acesso'])) {
            $tempo_inativo = time() - $_SESSION['ultimo_acesso'];
            
            if ($tempo_inativo > $tempo_limite) {
                // Sessão expirou
                $this->destruir_session();
                header('Location: ../../auth/login.php?erro=sessao_expirada');
                exit();
            }
        }
        
        // Atualiza o último acesso
        $_SESSION['ultimo_acesso'] = time();
    }
    
    public function destruir_session() {
        // Destrói todas as variáveis de sessão
        $_SESSION = array();
        
        // Se for desejado destruir a sessão, também delete o cookie de sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finalmente, destrói a sessão
        session_destroy();
    }
    
    public function criar_session($dados_usuario) {
        $_SESSION['logado'] = true;
        $_SESSION['usuario_id'] = $dados_usuario['id'];
        $_SESSION['nome'] = $dados_usuario['nome'];
        $_SESSION['email'] = $dados_usuario['email'];
        $_SESSION['setor'] = $dados_usuario['setor'] ?? 'Professor';
        $_SESSION['ultimo_acesso'] = time();
        
        // Define permissões baseadas no setor/tipo do usuário
        $this->definir_permissoes($dados_usuario);
    }
    
    private function definir_permissoes($dados_usuario) {
        // Limpa permissões anteriores
        $permissoes = ['Gerenciador de Usuarios', 'Estoque', 'Biblioteca', 'Entrada/saída'];
        foreach ($permissoes as $permissao) {
            unset($_SESSION[$permissao]);
        }
        
        // Define permissões baseadas no setor ou tipo de usuário
        $setor = $dados_usuario['setor'] ?? '';
        $tipo = $dados_usuario['tipo'] ?? '';
        
        // Administradores têm acesso a tudo
        if ($setor === 'Administração' || $tipo === 'admin') {
            $_SESSION['Gerenciador de Usuarios'] = true;
            $_SESSION['Estoque'] = true;
            $_SESSION['Biblioteca'] = true;
            $_SESSION['Entrada/saída'] = true;
        }
        // Coordenadores têm acesso limitado
        elseif ($setor === 'Coordenação' || $tipo === 'coordenador') {
            $_SESSION['Estoque'] = true;
            $_SESSION['Biblioteca'] = true;
            $_SESSION['Entrada/saída'] = true;
        }
        // Professores têm acesso básico
        elseif ($setor === 'Professor' || $tipo === 'professor') {
            $_SESSION['Biblioteca'] = true;
        }
        // Funcionários da merenda
        elseif ($setor === 'Merenda' || $tipo === 'merenda') {
            $_SESSION['Estoque'] = true;
            $_SESSION['Entrada/saída'] = true;
        }
    }
}

// Verifica se foi solicitado logout
if (isset($_GET['sair'])) {
    // Inicia a sessão se não estiver iniciada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Destrói todas as variáveis de sessão
    $_SESSION = array();
    
    // Se for desejado destruir a sessão, também delete o cookie de sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finalmente, destrói a sessão
    session_destroy();
    
    // Redireciona para o login
    header('Location: ../../Views/auth/login.php');
    exit();
}
?>