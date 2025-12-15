<?php
// Verificar se a sessão já foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurar headers para AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Verificar se o usuário está logado e tem permissão para acessar esta página
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ADM') {
    echo json_encode(['status' => false, 'mensagem' => 'Acesso não autorizado.']);
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => false, 'mensagem' => 'ID do usuário não fornecido.']);
    exit;
}

$idRecebido = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Log detalhado
error_log("=== UsuarioController - Requisição recebida ===");
error_log("ID recebido via GET: " . ($_GET['id'] ?? 'N/A'));
error_log("ID convertido para int: " . $idRecebido);
error_log("Timestamp: " . date('Y-m-d H:i:s'));

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

// Função para obter os dados do usuário
// Os dados editáveis estão na tabela PESSOA, não na tabela USUARIO
function obterUsuario($idRecebido) {
    error_log("=== obterUsuario chamada com ID: " . $idRecebido . " ===");
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $pessoaId = null;
    $usuarioId = null;
    
    // Primeiro, tentar como ID de usuário (mais comum)
    $stmt = $conn->prepare("SELECT id, pessoa_id FROM usuario WHERE id = :id");
    $stmt->bindParam(':id', $idRecebido, PDO::PARAM_INT);
    $stmt->execute();
    $usuarioEncontrado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuarioEncontrado) {
        // É ID de usuário
        $usuarioId = $idRecebido;
        $pessoaId = $usuarioEncontrado['pessoa_id'];
        error_log("✓ ID recebido é de USUÁRIO");
        error_log("  - usuario_id: " . $usuarioId);
        error_log("  - pessoa_id: " . $pessoaId);
    } else {
        // Tentar como ID de pessoa
        error_log("ID não encontrado como usuário, tentando como pessoa...");
        $stmt = $conn->prepare("SELECT id FROM pessoa WHERE id = :id");
        $stmt->bindParam(':id', $idRecebido, PDO::PARAM_INT);
        $stmt->execute();
        $pessoaEncontrada = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pessoaEncontrada) {
            // É ID de pessoa
            $pessoaId = $idRecebido;
            error_log("✓ ID recebido é de PESSOA");
            error_log("  - pessoa_id: " . $pessoaId);
            
            // Buscar o ID do usuário correspondente
            $stmt = $conn->prepare("SELECT id FROM usuario WHERE pessoa_id = :pessoa_id");
            $stmt->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
            $stmt->execute();
            $usuarioEncontrado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuarioEncontrado) {
                $usuarioId = $usuarioEncontrado['id'];
                error_log("  - usuario_id encontrado: " . $usuarioId);
            } else {
                error_log("✗ Usuário não encontrado para pessoa_id: " . $pessoaId);
                return ['status' => false, 'mensagem' => 'Usuário não encontrado para esta pessoa.'];
            }
        } else {
            error_log("✗ ID não encontrado nem como pessoa nem como usuário: " . $idRecebido);
            return ['status' => false, 'mensagem' => 'ID não encontrado nem como pessoa nem como usuário.'];
        }
    }
    
    // Agora buscar TODOS os dados da tabela PESSOA (onde estão os dados editáveis)
    $sql = "SELECT 
                id,
                nome,
                cpf,
                email,
                telefone,
                data_nascimento,
                sexo,
                whatsapp,
                telefone_secundario,
                endereco,
                numero,
                complemento,
                bairro,
                cidade,
                estado,
                cep,
                tipo,
                foto_url,
                observacoes,
                criado_em,
                atualizado_em,
                nome_social,
                raca
            FROM pessoa 
            WHERE id = :pessoa_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
    $stmt->execute();
    
    $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pessoa) {
        return ['status' => false, 'mensagem' => 'Dados da pessoa não encontrados.'];
    }
    
    // Buscar apenas os campos necessários da tabela USUARIO (username, role, ativo)
    $stmt = $conn->prepare("SELECT id, username, role, ativo FROM usuario WHERE id = :usuario_id");
    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        return ['status' => false, 'mensagem' => 'Dados do usuário não encontrados.'];
    }
    
    // Combinar os dados: dados da pessoa (editáveis) + dados do usuário (username, role, ativo)
    $resultado = array_merge($pessoa, [
        'id' => $usuario['id'], // ID do usuário (para atualização)
        'usuario_id' => $usuario['id'],
        'pessoa_id' => $pessoa['id'], // ID da pessoa (para atualização)
        'username' => $usuario['username'],
        'role' => $usuario['role'],
        'ativo' => $usuario['ativo']
    ]);
    
    // Log dos dados que serão retornados
    error_log("=== DADOS RETORNADOS ===");
    error_log("ID do usuário: " . $resultado['id']);
    error_log("ID da pessoa: " . $resultado['pessoa_id']);
    error_log("Nome: " . ($resultado['nome'] ?? 'N/A'));
    error_log("CPF: " . ($resultado['cpf'] ?? 'N/A'));
    error_log("Email: " . ($resultado['email'] ?? 'N/A'));
    error_log("Username: " . ($resultado['username'] ?? 'N/A'));
    error_log("Role: " . ($resultado['role'] ?? 'N/A'));
    error_log("=========================");
    
    return ['status' => true, 'usuario' => $resultado];
}

// Processar requisição
try {
    $resultado = obterUsuario($idRecebido);
    echo json_encode($resultado);
} catch (Exception $e) {
    error_log("Erro ao obter usuário: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['status' => false, 'mensagem' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>