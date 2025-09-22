<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Inicia a sessão se ainda não foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e tem permissão
if (!isset($_SESSION['logado']) || !$_SESSION['logado']) {
    http_response_code(401);
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

// Verificar permissões (apenas administradores podem gerenciar usuários)
if (!isset($_SESSION['cadastrar_pessoas']) || !$_SESSION['cadastrar_pessoas']) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sem permissão para gerenciar usuários']);
    exit;
}

require_once('../../Models/usuarios/ModelUsuarios.php');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$modelUsuarios = new ModelUsuarios();

try {
    switch ($method) {
        case 'GET':
            if ($action === 'listar') {
                $usuarios = $modelUsuarios->listarUsuarios();
                echo json_encode([
                    'sucesso' => true,
                    'usuarios' => $usuarios
                ]);
            } elseif ($action === 'obter' && isset($_GET['id'])) {
                $usuario = $modelUsuarios->obterUsuarioPorId($_GET['id']);
                if ($usuario) {
                    echo json_encode([
                        'sucesso' => true,
                        'usuario' => $usuario
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode(['erro' => 'Usuário não encontrado']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['erro' => 'Ação não especificada ou inválida']);
            }
            break;

        case 'POST':
            if ($action === 'cadastrar') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validar dados obrigatórios
                $camposObrigatorios = ['nome', 'cpf', 'email', 'tipo', 'senha'];
                foreach ($camposObrigatorios as $campo) {
                    if (empty($input[$campo])) {
                        http_response_code(400);
                        echo json_encode(['erro' => "Campo '$campo' é obrigatório"]);
                        exit;
                    }
                }
                
                // Limpar e validar CPF
                $cpf = preg_replace('/[^0-9]/', '', $input['cpf']);
                if (strlen($cpf) !== 11) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'CPF deve ter 11 dígitos']);
                    exit;
                }
                
                // Verificar se CPF já existe
                if ($modelUsuarios->verificarCpfExistente($cpf)) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'CPF já cadastrado no sistema']);
                    exit;
                }
                
                // Verificar se email já existe
                if ($modelUsuarios->verificarEmailExistente($input['email'])) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'Email já cadastrado no sistema']);
                    exit;
                }
                
                // Validar tipo de usuário
                $tiposValidos = ['admin', 'funcionario', 'nutricionista'];
                if (!in_array($input['tipo'], $tiposValidos)) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'Tipo de usuário inválido']);
                    exit;
                }
                
                // Preparar dados para cadastro
                $dadosPessoa = [
                    'nome' => trim($input['nome']),
                    'cpf' => $cpf,
                    'email' => trim($input['email']),
                    'telefone' => !empty($input['telefone']) ? trim($input['telefone']) : null,
                    'endereco' => !empty($input['endereco']) ? trim($input['endereco']) : null,
                    'data_nascimento' => !empty($input['data_nascimento']) ? $input['data_nascimento'] : null
                ];
                
                $dadosUsuario = [
                    'tipo' => $input['tipo'],
                    'senha' => $input['senha']
                ];
                
                $resultado = $modelUsuarios->cadastrarUsuario($dadosPessoa, $dadosUsuario);
                
                if ($resultado['sucesso']) {
                    echo json_encode($resultado);
                } else {
                    http_response_code(500);
                    echo json_encode($resultado);
                }
                
            } else {
                http_response_code(400);
                echo json_encode(['erro' => 'Ação não especificada ou inválida']);
            }
            break;

        case 'PUT':
            if ($action === 'status' && isset($_GET['id'])) {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['ativo'])) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'Status não especificado']);
                    exit;
                }
                
                $sucesso = $modelUsuarios->alterarStatusUsuario($_GET['id'], $input['ativo']);
                
                if ($sucesso) {
                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => 'Status do usuário atualizado com sucesso'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['erro' => 'Erro ao atualizar status do usuário']);
                }
                
            } else {
                http_response_code(400);
                echo json_encode(['erro' => 'Ação não especificada ou inválida']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro interno do servidor',
        'detalhes' => $e->getMessage()
    ]);
}

?>