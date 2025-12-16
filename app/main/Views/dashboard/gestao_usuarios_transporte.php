<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Apenas ADM e ADM_TRANSPORTE podem acessar
$tipoUsuario = $_SESSION['tipo'] ?? '';
$tipoUsuarioUpper = strtoupper(trim($tipoUsuario));
if (!eAdm() && $tipoUsuarioUpper !== 'ADM_TRANSPORTE') {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$usuarioId = $_SESSION['usuario_id'] ?? null;

// Processar requisições GET para buscar localidades
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'buscar_localidades') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    
    $distrito = $_GET['distrito'] ?? null;
    if (empty($distrito)) {
        echo json_encode(['success' => false, 'message' => 'Distrito não informado']);
        exit;
    }
    
    try {
        $sql = "SELECT DISTINCT localidade FROM distrito_localidade WHERE distrito = :distrito ORDER BY localidade ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':distrito', $distrito);
        $stmt->execute();
        $localidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'localidades' => $localidades]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar localidades: ' . $e->getMessage()]);
    }
    exit;
}

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    
    $acao = $_POST['acao'];
    $resposta = ['status' => false, 'mensagem' => 'Ação não reconhecida'];
    
    try {
        // Listar Alunos que precisam de transporte (com filtros)
        if ($acao === 'listar_alunos_transporte') {
            $busca = $_POST['busca'] ?? '';
            $distrito = $_POST['distrito'] ?? '';
            $localidade = $_POST['localidade'] ?? '';
            
            // Verificar se as colunas de transporte existem
            $colunaPrecisaExiste = false;
            $colunaDistritoExiste = false;
            $colunaLocalidadeExiste = false;
            try {
                $checkColPrecisa = $conn->query("SHOW COLUMNS FROM aluno LIKE 'precisa_transporte'");
                $colunaPrecisaExiste = $checkColPrecisa->rowCount() > 0;
                
                $checkColDistrito = $conn->query("SHOW COLUMNS FROM aluno LIKE 'distrito_transporte'");
                $colunaDistritoExiste = $checkColDistrito->rowCount() > 0;
                
                $checkColLocalidade = $conn->query("SHOW COLUMNS FROM aluno LIKE 'localidade_transporte'");
                $colunaLocalidadeExiste = $checkColLocalidade->rowCount() > 0;
            } catch (Exception $e) {
                $colunaPrecisaExiste = false;
                $colunaDistritoExiste = false;
                $colunaLocalidadeExiste = false;
            }
            
            // Query corrigida: distrito e localidade sempre vêm da tabela aluno, nunca misturados
            $sql = "SELECT a.id, a.matricula, " . 
                   ($colunaPrecisaExiste ? "a.precisa_transporte, " : "1 as precisa_transporte, ") . 
                   // DISTRITO: SEMPRE da coluna distrito_transporte da tabela aluno
                   ($colunaDistritoExiste ? 
                       "IFNULL(NULLIF(TRIM(a.distrito_transporte), ''), '-') as distrito_transporte" : 
                       "'-' as distrito_transporte"
                   ) . ", " .
                   // LOCALIDADE: SEMPRE da coluna localidade_transporte da tabela aluno (NUNCA do distrito)
                   ($colunaLocalidadeExiste ? 
                       "IFNULL(NULLIF(TRIM(a.localidade_transporte), ''), COALESCE(NULLIF(TRIM(ga.bairro), ''), NULLIF(TRIM(ga.endereco), ''), NULLIF(TRIM(p.bairro), ''), '-')) as localidade" : 
                       "COALESCE(NULLIF(TRIM(ga.bairro), ''), NULLIF(TRIM(ga.endereco), ''), NULLIF(TRIM(p.bairro), ''), '-') as localidade"
                   ) . ",
                           p.nome, p.cpf, p.email, p.telefone,
                           e.nome as escola_nome, e.id as escola_id,
                           CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                           ga.latitude, ga.longitude,
                           CASE WHEN ar.id IS NOT NULL THEN 1 ELSE 0 END as ja_lotado,
                           r.nome as rota_nome
                    FROM aluno a
                    INNER JOIN pessoa p ON a.pessoa_id = p.id
                    LEFT JOIN escola e ON a.escola_id = e.id
                    LEFT JOIN aluno_turma at ON a.id = at.aluno_id AND (at.fim IS NULL OR at.fim = '' OR at.fim = '0000-00-00')
                    LEFT JOIN turma t ON at.turma_id = t.id AND t.ativo = 1
                    LEFT JOIN geolocalizacao_aluno ga ON a.id = ga.aluno_id AND ga.principal = 1
                    LEFT JOIN aluno_rota ar ON a.id = ar.aluno_id AND ar.status = 'ATIVO'
                    LEFT JOIN rota r ON ar.rota_id = r.id AND r.ativo = 1
                    WHERE a.ativo = 1";
            
            // Adicionar filtro de precisa_transporte ou distrito_transporte se as colunas existirem
            if ($colunaPrecisaExiste) {
                $sql .= " AND a.precisa_transporte = 1";
            } elseif ($colunaDistritoExiste) {
                $sql .= " AND (a.distrito_transporte IS NOT NULL AND a.distrito_transporte != '')";
            } else {
                // Se nenhuma coluna existe, mostrar todos os alunos ativos (fallback)
                $sql .= " AND 1=1";
            }
            
            $params = [];
            if (!empty($busca)) {
                $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR a.matricula LIKE :busca";
                if ($colunaDistritoExiste) {
                    $sql .= " OR a.distrito_transporte LIKE :busca";
                }
                $sql .= ")";
                $params[':busca'] = "%{$busca}%";
            }
            
            if (!empty($distrito) && $colunaDistritoExiste) {
                $sql .= " AND a.distrito_transporte = :distrito";
                $params[':distrito'] = $distrito;
            }
            
            if (!empty($localidade)) {
                if ($colunaLocalidadeExiste) {
                    $sql .= " AND (a.localidade_transporte LIKE :localidade OR COALESCE(ga.bairro, ga.endereco, p.bairro) LIKE :localidade)";
                } else {
                    $sql .= " AND (COALESCE(ga.bairro, ga.endereco, p.bairro) LIKE :localidade)";
                }
                $params[':localidade'] = "%{$localidade}%";
            }
            
            if ($colunaDistritoExiste) {
                $sql .= " ORDER BY a.distrito_transporte ASC, localidade ASC, p.nome ASC";
            } else {
                $sql .= " ORDER BY localidade ASC, p.nome ASC";
            }
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resposta = ['status' => true, 'dados' => $alunos];
        }
        
        // Buscar distritos únicos
        elseif ($acao === 'listar_distritos') {
            // Verificar se as colunas existem
            $colunaPrecisaExiste = false;
            $colunaDistritoExiste = false;
            try {
                $checkColPrecisa = $conn->query("SHOW COLUMNS FROM aluno LIKE 'precisa_transporte'");
                $colunaPrecisaExiste = $checkColPrecisa->rowCount() > 0;
                
                $checkColDistrito = $conn->query("SHOW COLUMNS FROM aluno LIKE 'distrito_transporte'");
                $colunaDistritoExiste = $checkColDistrito->rowCount() > 0;
            } catch (Exception $e) {
                $colunaPrecisaExiste = false;
                $colunaDistritoExiste = false;
            }
            
            if (!$colunaDistritoExiste) {
                // Se a coluna não existe, retornar lista vazia ou usar lista estática
                $resposta = ['status' => true, 'dados' => []];
            } else {
                $sql = "SELECT DISTINCT distrito_transporte as distrito, COUNT(*) as total_alunos
                        FROM aluno
                        WHERE ativo = 1";
                if ($colunaPrecisaExiste) {
                    $sql .= " AND precisa_transporte = 1";
                }
                $sql .= " AND distrito_transporte IS NOT NULL AND distrito_transporte != ''
                        GROUP BY distrito_transporte
                        ORDER BY distrito_transporte ASC";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $resposta = ['status' => true, 'dados' => $distritos];
            }
        }
        
        // Sugestão de Lotação (IA)
        elseif ($acao === 'sugerir_lotacao') {
            $distrito = $_POST['distrito'] ?? '';
            $localidade = $_POST['localidade'] ?? '';
            
            // Verificar se as colunas existem
            $colunaPrecisaExiste = false;
            $colunaDistritoExiste = false;
            try {
                $checkColPrecisa = $conn->query("SHOW COLUMNS FROM aluno LIKE 'precisa_transporte'");
                $colunaPrecisaExiste = $checkColPrecisa->rowCount() > 0;
                
                $checkColDistrito = $conn->query("SHOW COLUMNS FROM aluno LIKE 'distrito_transporte'");
                $colunaDistritoExiste = $checkColDistrito->rowCount() > 0;
            } catch (Exception $e) {
                $colunaPrecisaExiste = false;
                $colunaDistritoExiste = false;
            }
            
            // Verificar se coluna localidade_transporte existe
            $colunaLocalidadeExiste = false;
            try {
                $checkColLocalidade = $conn->query("SHOW COLUMNS FROM aluno LIKE 'localidade_transporte'");
                $colunaLocalidadeExiste = $checkColLocalidade->rowCount() > 0;
            } catch (Exception $e) {
                $colunaLocalidadeExiste = false;
            }
            
            // Buscar alunos do distrito/localidade que ainda não estão lotados
            $sql = "SELECT a.id, a.matricula, " . 
                   // DISTRITO: SEMPRE da coluna distrito_transporte da tabela aluno
                   ($colunaDistritoExiste ? 
                       "IFNULL(NULLIF(TRIM(a.distrito_transporte), ''), '-') as distrito_transporte" : 
                       "'-' as distrito_transporte"
                   ) . ", " .
                   // LOCALIDADE: SEMPRE da coluna localidade_transporte da tabela aluno (NUNCA do distrito)
                   ($colunaLocalidadeExiste ? 
                       "IFNULL(NULLIF(TRIM(a.localidade_transporte), ''), COALESCE(NULLIF(TRIM(ga.bairro), ''), NULLIF(TRIM(ga.endereco), ''), NULLIF(TRIM(p.bairro), ''), '-')) as localidade" : 
                       "COALESCE(NULLIF(TRIM(ga.bairro), ''), NULLIF(TRIM(ga.endereco), ''), NULLIF(TRIM(p.bairro), ''), '-') as localidade"
                   ) . ",
                           p.nome, p.cpf,
                           e.nome as escola_nome,
                           ga.latitude, ga.longitude
                    FROM aluno a
                    INNER JOIN pessoa p ON a.pessoa_id = p.id
                    LEFT JOIN escola e ON a.escola_id = e.id
                    LEFT JOIN geolocalizacao_aluno ga ON a.id = ga.aluno_id AND ga.principal = 1
                    LEFT JOIN aluno_rota ar ON a.id = ar.aluno_id AND ar.status = 'ATIVO'
                    WHERE a.ativo = 1";
            
            if ($colunaPrecisaExiste) {
                $sql .= " AND a.precisa_transporte = 1";
            } elseif ($colunaDistritoExiste) {
                $sql .= " AND (a.distrito_transporte IS NOT NULL AND a.distrito_transporte != '')";
            } else {
                $sql .= " AND 1=1";
            }
            
            $sql .= " AND ar.id IS NULL";
            
            $params = [];
            if (!empty($distrito) && $colunaDistritoExiste) {
                $sql .= " AND a.distrito_transporte = :distrito";
                $params[':distrito'] = $distrito;
            }
            
            if (!empty($localidade)) {
                if ($colunaLocalidadeExiste) {
                    $sql .= " AND (a.localidade_transporte LIKE :localidade OR COALESCE(ga.bairro, ga.endereco, p.bairro) LIKE :localidade)";
                } else {
                    $sql .= " AND (COALESCE(ga.bairro, ga.endereco, p.bairro) LIKE :localidade)";
                }
                $params[':localidade'] = "%{$localidade}%";
            }
            
            if ($colunaDistritoExiste) {
                $sql .= " ORDER BY a.distrito_transporte ASC, localidade ASC, p.nome ASC";
            } else {
                $sql .= " ORDER BY localidade ASC, p.nome ASC";
            }
            
            $stmt = $conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar por distrito e localidade
            $agrupados = [];
            foreach ($alunos as $aluno) {
                $chave = ($aluno['distrito_transporte'] ?? 'Sem distrito') . '|' . ($aluno['localidade'] ?? 'Sem localidade');
                if (!isset($agrupados[$chave])) {
                    $agrupados[$chave] = [
                        'distrito' => $aluno['distrito_transporte'] ?? 'Sem distrito',
                        'localidade' => $aluno['localidade'] ?? 'Sem localidade',
                        'alunos' => [],
                        'total' => 0
                    ];
                }
                $agrupados[$chave]['alunos'][] = $aluno;
                $agrupados[$chave]['total']++;
            }
            
            // Buscar veículos disponíveis
            $sqlVeiculos = "SELECT v.*, 
                                   COUNT(DISTINCT r.id) as rotas_ativas,
                                   COUNT(DISTINCT ar.aluno_id) as alunos_lotados
                            FROM veiculo v
                            LEFT JOIN rota r ON v.id = r.veiculo_id AND r.ativo = 1
                            LEFT JOIN aluno_rota ar ON r.id = ar.rota_id AND ar.status = 'ATIVO'
                            WHERE v.ativo = 1
                            GROUP BY v.id
                            ORDER BY v.tipo ASC, v.capacidade_maxima DESC";
            $stmtVeiculos = $conn->prepare($sqlVeiculos);
            $stmtVeiculos->execute();
            $veiculos = $stmtVeiculos->fetchAll(PDO::FETCH_ASSOC);
            
            // Sugerir veículos para cada grupo
            $sugestoes = [];
            foreach ($agrupados as $grupo) {
                $totalAlunos = $grupo['total'];
                $sugestao = [
                    'distrito' => $grupo['distrito'],
                    'localidade' => $grupo['localidade'],
                    'total_alunos' => $totalAlunos,
                    'alunos' => $grupo['alunos'],
                    'veiculos_sugeridos' => [],
                    'tipo_recomendado' => $totalAlunos <= 20 ? 'VAN' : 'ONIBUS'
                ];
                
                // Filtrar veículos adequados
                foreach ($veiculos as $veiculo) {
                    $capacidadeMax = (int)$veiculo['capacidade_maxima'];
                    $capacidadeMin = (int)($veiculo['capacidade_minima'] ?? 0);
                    $tipo = $veiculo['tipo'];
                    
                    // Verificar se o veículo pode atender
                    if ($totalAlunos <= $capacidadeMax && ($capacidadeMin == 0 || $totalAlunos >= $capacidadeMin)) {
                        $sugestao['veiculos_sugeridos'][] = [
                            'id' => $veiculo['id'],
                            'placa' => $veiculo['placa'],
                            'tipo' => $veiculo['tipo'],
                            'capacidade_maxima' => $capacidadeMax,
                            'capacidade_minima' => $capacidadeMin,
                            'marca' => $veiculo['marca'],
                            'modelo' => $veiculo['modelo'],
                            'rotas_ativas' => (int)$veiculo['rotas_ativas'],
                            'alunos_lotados' => (int)$veiculo['alunos_lotados'],
                            'disponibilidade' => $capacidadeMax - (int)$veiculo['alunos_lotados']
                        ];
                    }
                }
                
                // Ordenar veículos sugeridos por adequação
                usort($sugestao['veiculos_sugeridos'], function($a, $b) use ($totalAlunos) {
                    // Priorizar veículos com capacidade próxima ao necessário
                    $diffA = abs($a['capacidade_maxima'] - $totalAlunos);
                    $diffB = abs($b['capacidade_maxima'] - $totalAlunos);
                    if ($diffA != $diffB) {
                        return $diffA <=> $diffB;
                    }
                    // Se empate, priorizar maior disponibilidade
                    return $b['disponibilidade'] <=> $a['disponibilidade'];
                });
                
                $sugestoes[] = $sugestao;
            }
            
            $resposta = ['status' => true, 'sugestoes' => $sugestoes, 'veiculos_disponiveis' => $veiculos];
        }
        
        // Listar Motoristas
        elseif ($acao === 'listar_motoristas') {
            $busca = $_POST['busca'] ?? '';
            $sql = "SELECT m.*, p.nome, p.cpf, p.email, p.telefone, u.username as criado_por_nome 
                    FROM motorista m 
                    INNER JOIN pessoa p ON m.pessoa_id = p.id 
                    LEFT JOIN usuario u ON m.criado_por = u.id 
                    WHERE 1=1";
            if (!empty($busca)) {
                $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR m.cnh LIKE :busca)";
            }
            $sql .= " ORDER BY p.nome ASC";
            $stmt = $conn->prepare($sql);
            if (!empty($busca)) {
                $buscaParam = "%{$busca}%";
                $stmt->bindParam(':busca', $buscaParam);
            }
            $stmt->execute();
            $motoristas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resposta = ['status' => true, 'dados' => $motoristas];
        }
        
        // Listar Usuários de Transporte
        elseif ($acao === 'listar_usuarios_transporte') {
            $busca = $_POST['busca'] ?? '';
            $sql = "SELECT u.id, u.username, u.role as tipo, u.ativo, u.ultimo_login, u.created_at as data_criacao,
                           p.nome, p.cpf, p.email, p.telefone
                    FROM usuario u 
                    INNER JOIN pessoa p ON u.pessoa_id = p.id 
                    WHERE u.role IN ('ADM_TRANSPORTE', 'TRANSPORTE_ALUNO')";
            if (!empty($busca)) {
                $sql .= " AND (p.nome LIKE :busca OR p.cpf LIKE :busca OR p.email LIKE :busca OR u.username LIKE :busca)";
            }
            $sql .= " ORDER BY p.nome ASC";
            $stmt = $conn->prepare($sql);
            if (!empty($busca)) {
                $buscaParam = "%{$busca}%";
                $stmt->bindParam(':busca', $buscaParam);
            }
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resposta = ['status' => true, 'dados' => $usuarios];
        }
        
        // Criar Usuário ADM Transporte
        elseif ($acao === 'criar_usuario_adm_transporte') {
            $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            
            // Verificar se CPF já existe
            $stmt = $conn->prepare("SELECT id FROM pessoa WHERE cpf = :cpf");
            $stmt->bindParam(':cpf', $cpf);
            $stmt->execute();
            if ($stmt->fetch()) {
                $resposta = ['status' => false, 'mensagem' => 'CPF já cadastrado no sistema.'];
            } else {
                $conn->beginTransaction();
                
                // Criar pessoa
                $stmt = $conn->prepare("INSERT INTO pessoa (nome, cpf, email, telefone, tipo) 
                                       VALUES (:nome, :cpf, :email, :telefone, 'FUNCIONARIO')");
                $stmt->bindParam(':nome', $_POST['nome']);
                $stmt->bindParam(':cpf', $cpf);
                $stmt->bindValue(':email', $_POST['email'] ?? null);
                $stmt->bindValue(':telefone', $_POST['telefone'] ?? null);
                $stmt->execute();
                $pessoaId = $conn->lastInsertId();
                
                // Criar usuário
                $username = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['cpf'])));
                $senhaHash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO usuario (pessoa_id, username, senha, role, ativo, criado_por) 
                                       VALUES (:pessoa_id, :username, :senha, 'ADM_TRANSPORTE', 1, :criado_por)");
                $stmt->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':senha', $senhaHash);
                $stmt->bindParam(':criado_por', $usuarioId, PDO::PARAM_INT);
                $stmt->execute();
                
                $conn->commit();
                $resposta = ['status' => true, 'mensagem' => 'Usuário ADM Transporte criado com sucesso!'];
            }
        }
        
        // Criar Usuário Transporte Aluno
        elseif ($acao === 'criar_usuario_transporte_aluno') {
            $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
            
            // Verificar se CPF já existe
            $stmt = $conn->prepare("SELECT id FROM pessoa WHERE cpf = :cpf");
            $stmt->bindParam(':cpf', $cpf);
            $stmt->execute();
            if ($stmt->fetch()) {
                $resposta = ['status' => false, 'mensagem' => 'CPF já cadastrado no sistema.'];
            } else {
                $conn->beginTransaction();
                
                // Criar pessoa
                $stmt = $conn->prepare("INSERT INTO pessoa (nome, cpf, email, telefone, tipo) 
                                       VALUES (:nome, :cpf, :email, :telefone, 'FUNCIONARIO')");
                $stmt->bindParam(':nome', $_POST['nome']);
                $stmt->bindParam(':cpf', $cpf);
                $stmt->bindValue(':email', $_POST['email'] ?? null);
                $stmt->bindValue(':telefone', $_POST['telefone'] ?? null);
                $stmt->execute();
                $pessoaId = $conn->lastInsertId();
                
                // Criar usuário
                $username = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['cpf'])));
                $senhaHash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO usuario (pessoa_id, username, senha, role, ativo, criado_por) 
                                       VALUES (:pessoa_id, :username, :senha, 'TRANSPORTE_ALUNO', 1, :criado_por)");
                $stmt->bindParam(':pessoa_id', $pessoaId, PDO::PARAM_INT);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':senha', $senhaHash);
                $stmt->bindParam(':criado_por', $usuarioId, PDO::PARAM_INT);
                $stmt->execute();
                
                $conn->commit();
                $resposta = ['status' => true, 'mensagem' => 'Usuário Transporte Aluno criado com sucesso!'];
            }
        }
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Erro: " . $e->getMessage());
        $resposta = ['status' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
    }
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}

// Lista de distritos de Maranguape (mesma lista da gestão de localidades)
$distritosLista = [
    'Amanari', 'Antônio Marques', 'Cachoeira', 'Itapebussu', 'Jubaia',
    'Ladeira Grande', 'Lages', 'Lagoa do Juvenal', 'Manoel Guedes',
    'Sede', 'Papara', 'Penedo', 'Sapupara', 'São João do Amanari',
    'Tanques', 'Umarizeiras', 'Vertentes do Lagedo'
];

// Buscar distritos com contagem de alunos para exibição
$distritos = [];
try {
    $stmt = $conn->query("SELECT DISTINCT distrito_transporte as distrito, COUNT(*) as total_alunos
                          FROM aluno
                          WHERE ativo = 1 AND precisa_transporte = 1 AND distrito_transporte IS NOT NULL AND distrito_transporte != ''
                          GROUP BY distrito_transporte
                          ORDER BY distrito_transporte ASC");
    $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar distritos: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - Transporte Escolar - SIGAE</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #2D5A27;
            --primary-green-light: #3d7a35;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        * {
            scroll-behavior: smooth;
        }
        
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }
        .menu-item {
            transition: all 0.2s ease;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        .menu-item.active svg {
            color: #2D5A27;
        }
        
        /* Tabs melhoradas */
        .tab-button {
            transition: all 0.2s ease;
            position: relative;
        }
        .tab-button::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-green);
            transform: scaleX(0);
            transition: transform 0.2s ease;
        }
        .tab-button.tab-active {
            color: var(--primary-green);
            font-weight: 600;
        }
        .tab-button.tab-active::after {
            transform: scaleX(1);
        }
        .tab-button:hover {
            color: var(--primary-green);
        }
        
        /* Cards clean */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06);
        }
        
        /* Inputs melhorados */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            transition: all 0.2s ease;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            ring: 2px;
            ring-color: var(--primary-green);
            border-color: var(--primary-green);
        }
        
        /* Botões melhorados */
        .btn-primary {
            background: var(--primary-green);
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background: var(--primary-green-light);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(45, 90, 39, 0.2);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* Tabelas responsivas */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table-row {
            transition: background-color 0.15s ease;
        }
        .table-row:hover {
            background-color: var(--gray-50);
        }
        
        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Autocomplete customizado */
        .autocomplete-container {
            position: relative;
        }
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 4px;
            display: none;
            animation: fadeIn 0.2s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .autocomplete-dropdown.show {
            display: block;
        }
        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            transition: background-color 0.15s;
            border-bottom: 1px solid var(--gray-100);
        }
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        .autocomplete-item:hover,
        .autocomplete-item.selected {
            background-color: var(--gray-50);
        }
        .autocomplete-item .distrito-nome {
            font-size: 14px;
            color: var(--gray-700);
            font-weight: 500;
        }
        
        /* Loading states */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--primary-green);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Empty states */
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--gray-500);
        }
        .empty-state-icon {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }
        
        /* Modal melhorado */
        .modal-overlay {
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        }
        .modal-content {
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Mobile Card View */
        .mobile-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
            transition: all 0.2s ease;
        }
        .mobile-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }
        .mobile-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--gray-100);
        }
        .mobile-card-body {
            display: grid;
            gap: 0.5rem;
        }
        .mobile-card-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }
        .mobile-card-label {
            font-weight: 600;
            color: var(--gray-600);
            font-size: 0.875rem;
        }
        .mobile-card-value {
            color: var(--gray-900);
            font-size: 0.875rem;
            text-align: right;
            flex: 1;
            margin-left: 1rem;
        }
        
        /* Responsividade Mobile */
        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 999 !important;
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                height: 100vh !important;
                width: 16rem !important;
            }
            .sidebar-mobile.open {
                transform: translateX(0) !important;
                z-index: 999 !important;
            }
            
            main {
                margin-left: 0 !important;
            }
            
            .p-6 {
                padding: 1rem;
            }
            
            h1 {
                font-size: 1.5rem !important;
            }
            
            .tabs-container {
                padding: 0.5rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .tab-button {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                white-space: nowrap;
            }
            
            .grid {
                grid-template-columns: 1fr !important;
            }
            
            .desktop-table-view {
                display: none;
            }
            
            .mobile-card-view {
                display: block;
            }
            
            .card {
                padding: 1rem;
            }
            
            .modal-content {
                width: 95%;
                max-width: 95%;
                margin: 1rem auto;
            }
        }
        
        @media (min-width: 768px) {
            .mobile-card-view {
                display: none;
            }
            
            .desktop-table-view {
                display: block;
            }
        }
        
        /* Animações suaves */
        .tab-content {
            animation: fadeIn 0.3s ease;
        }
        
        /* Scrollbar customizada */
        .autocomplete-dropdown::-webkit-scrollbar {
            width: 6px;
        }
        .autocomplete-dropdown::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 3px;
        }
        .autocomplete-dropdown::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 3px;
        }
        .autocomplete-dropdown::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php 
    // Incluir sidebar correta baseada no tipo de usuário
    $tipoUsuario = $_SESSION['tipo'] ?? '';
    $tipoUsuarioUpper = strtoupper(trim($tipoUsuario));
    
    if ($tipoUsuarioUpper === 'ADM_TRANSPORTE') {
        include 'components/sidebar_transporte.php';
    } elseif ($tipoUsuarioUpper === 'TRANSPORTE_ALUNO') {
        include 'components/sidebar_transporte_aluno.php';
    } elseif (eAdm()) {
        include 'components/sidebar_adm.php';
    } else {
        include 'components/sidebar_adm.php'; // Fallback
    }
    ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen bg-gray-50">
        <div class="p-4 md:p-6">
            <div class="mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Gestão de Usuários - Transporte</h1>
                <p class="text-sm md:text-base text-gray-600">Gerenciar alunos, motoristas e usuários do transporte escolar</p>
            </div>
            
            <!-- Tabs -->
            <div class="card p-2 md:p-4 mb-6 tabs-container">
                <div class="flex space-x-2 md:space-x-8 overflow-x-auto border-b border-gray-200 pb-0">
                    <button onclick="showTab('alunos', this)" class="tab-button tab-active py-3 px-3 md:px-4 font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">
                        <i class="fas fa-user-graduate mr-1 md:mr-2"></i><span class="hidden sm:inline">Alunos</span><span class="sm:hidden">Alunos</span>
                    </button>
                    <button onclick="showTab('lotacao', this)" class="tab-button py-3 px-3 md:px-4 font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">
                        <i class="fas fa-robot mr-1 md:mr-2"></i><span class="hidden sm:inline">Sugestão de Lotação</span><span class="sm:hidden">Lotação</span>
                    </button>
                    <button onclick="showTab('motoristas', this)" class="tab-button py-3 px-3 md:px-4 font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">
                        <i class="fas fa-id-card mr-1 md:mr-2"></i><span class="hidden sm:inline">Motoristas</span><span class="sm:hidden">Motoristas</span>
                    </button>
                    <button onclick="showTab('usuarios', this)" class="tab-button py-3 px-3 md:px-4 font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">
                        <i class="fas fa-users mr-1 md:mr-2"></i><span class="hidden sm:inline">Usuários do Transporte</span><span class="sm:hidden">Usuários</span>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <!-- Tab: Alunos -->
            <div id="tab-alunos" class="tab-content">
                <div class="card p-4 md:p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-gray-900">Alunos que Precisam de Transporte</h2>
                            <p class="text-sm text-gray-600 mt-1 hidden md:block">Visualize e gerencie os alunos cadastrados</p>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
                        <div>
                            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1.5">Buscar</label>
                            <input type="text" id="buscar-aluno-transporte" placeholder="Nome, CPF, matrícula..." 
                                   class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1.5">Distrito</label>
                            <div class="autocomplete-container">
                                <input type="text" id="filtro-distrito" placeholder="Digite o nome do distrito..." 
                                       class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all" autocomplete="off">
                                <div id="autocomplete-dropdown-distrito" class="autocomplete-dropdown"></div>
                            </div>
                            <input type="hidden" id="filtro-distrito-value">
                        </div>
                        <div>
                            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1.5">Localidade</label>
                            <input type="text" id="filtro-localidade" placeholder="Bairro, endereço..." 
                                   class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all">
                        </div>
                        <div class="flex items-end">
                            <button onclick="carregarAlunosTransporte()" class="w-full btn-primary px-4 py-2 text-white rounded-lg text-sm font-medium">
                                <i class="fas fa-search mr-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Desktop Table View -->
                    <div class="desktop-table-view table-responsive">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Nome</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Escola</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Distrito</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Localidade</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody id="lista-alunos-transporte" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="empty-state">
                                            <div class="loading mx-auto mb-4"></div>
                                            <p class="text-gray-500 font-medium">Carregando alunos...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Card View -->
                    <div id="lista-alunos-transporte-mobile" class="mobile-card-view">
                        <div class="empty-state">
                            <div class="loading mx-auto mb-4"></div>
                            <p class="text-gray-500 font-medium">Carregando alunos...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Sugestão de Lotação -->
            <div id="tab-lotacao" class="tab-content hidden">
                <div class="card p-4 md:p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-gray-900">Sugestão Inteligente de Lotação</h2>
                            <p class="text-sm text-gray-600 mt-1 hidden md:block">Sistema agrupa alunos por distrito e localidade e sugere veículos adequados</p>
                        </div>
                        <button onclick="gerarSugestoes()" class="btn-primary px-4 md:px-6 py-2.5 text-white rounded-lg text-sm font-medium whitespace-nowrap">
                            <i class="fas fa-magic mr-2"></i>Gerar Sugestões
                        </button>
                    </div>
                    
                    <!-- Filtros para sugestão -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4 mb-6">
                        <div>
                            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1.5">Distrito (opcional)</label>
                            <div class="autocomplete-container">
                                <input type="text" id="sugestao-distrito" placeholder="Digite o nome do distrito..." 
                                       class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all" autocomplete="off">
                                <div id="autocomplete-dropdown-sugestao-distrito" class="autocomplete-dropdown"></div>
                            </div>
                            <input type="hidden" id="sugestao-distrito-value">
                        </div>
                        <div>
                            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1.5">Localidade (opcional)</label>
                            <div class="autocomplete-container">
                                <input type="text" id="sugestao-localidade" placeholder="Digite a localidade..." 
                                       class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all" 
                                       autocomplete="off"
                                       oninput="buscarLocalidadesSugestao(this.value)"
                                       disabled>
                                <div id="autocomplete-dropdown-sugestao-localidade" class="autocomplete-dropdown"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Selecione primeiro um distrito para habilitar</p>
                        </div>
                    </div>
                    
                    <div id="sugestoes-container" class="space-y-4">
                        <div class="empty-state bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                            <i class="fas fa-lightbulb empty-state-icon text-yellow-400"></i>
                            <p class="text-gray-600 font-medium">Clique em "Gerar Sugestões" para ver as recomendações</p>
                            <p class="text-sm text-gray-500 mt-2">O sistema analisará os alunos disponíveis e sugerirá os veículos mais adequados</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Motoristas -->
            <div id="tab-motoristas" class="tab-content hidden">
                <div class="card p-4 md:p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-gray-900">Motoristas</h2>
                            <p class="text-sm text-gray-600 mt-1 hidden md:block">Gerencie os motoristas do transporte escolar</p>
                        </div>
                    </div>
                    
                    <div class="mb-4 md:mb-6">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="buscar-motorista-transporte" placeholder="Buscar por nome, CPF ou CNH..." 
                                   class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all">
                        </div>
                    </div>
                    
                    <!-- Desktop Table View -->
                    <div class="desktop-table-view table-responsive">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Nome</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">CPF</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">CNH</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Categoria</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Telefone</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody id="lista-motoristas-transporte" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="empty-state">
                                            <div class="loading mx-auto mb-4"></div>
                                            <p class="text-gray-500 font-medium">Carregando motoristas...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Card View -->
                    <div id="lista-motoristas-transporte-mobile" class="mobile-card-view">
                        <div class="empty-state">
                            <div class="loading mx-auto mb-4"></div>
                            <p class="text-gray-500 font-medium">Carregando motoristas...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Usuários -->
            <div id="tab-usuarios" class="tab-content hidden">
                <div class="card p-4 md:p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-gray-900">Usuários do Transporte</h2>
                            <p class="text-sm text-gray-600 mt-1 hidden md:block">Gerencie os usuários do sistema de transporte</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <button onclick="abrirModalCriarUsuario('ADM_TRANSPORTE')" class="btn-primary px-4 py-2 text-white rounded-lg text-sm font-medium whitespace-nowrap">
                                <i class="fas fa-plus mr-2"></i><span class="hidden sm:inline">Criar ADM Transporte</span><span class="sm:hidden">ADM</span>
                            </button>
                            <button onclick="abrirModalCriarUsuario('TRANSPORTE_ALUNO')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all text-sm font-medium whitespace-nowrap">
                                <i class="fas fa-plus mr-2"></i><span class="hidden sm:inline">Criar Transporte Aluno</span><span class="sm:hidden">Aluno</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-4 md:mb-6">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="buscar-usuario-transporte" placeholder="Buscar usuários..." 
                                   class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all">
                        </div>
                    </div>
                    
                    <!-- Desktop Table View -->
                    <div class="desktop-table-view table-responsive">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Nome</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tipo</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Email</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="lista-usuarios-transporte" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="empty-state">
                                            <div class="loading mx-auto mb-4"></div>
                                            <p class="text-gray-500 font-medium">Carregando usuários...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Card View -->
                    <div id="lista-usuarios-transporte-mobile" class="mobile-card-view">
                        <div class="empty-state">
                            <div class="loading mx-auto mb-4"></div>
                            <p class="text-gray-500 font-medium">Carregando usuários...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Criar Usuário -->
    <div id="modalCriarUsuario" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-xl p-4 md:p-6 max-w-md w-full modal-content shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg md:text-xl font-bold text-gray-900" id="modalTituloUsuario">Criar Usuário</h3>
                <button onclick="fecharModalCriarUsuario()" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="formCriarUsuario" onsubmit="criarUsuario(event)">
                <input type="hidden" id="tipo-usuario-modal" name="tipo">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nome Completo *</label>
                        <input type="text" name="nome" required class="w-full px-3 md:px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">CPF *</label>
                        <input type="text" name="cpf" id="cpf-criar-usuario" required class="w-full px-3 md:px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all" placeholder="000.000.000-00" maxlength="14" oninput="formatarCPF(this)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email *</label>
                        <input type="email" name="email" required class="w-full px-3 md:px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Telefone</label>
                        <input type="text" name="telefone" id="telefone-criar-usuario" class="w-full px-3 md:px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all" placeholder="(85) 99999-9999" maxlength="15" oninput="formatarTelefone(this)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Senha *</label>
                        <div class="relative">
                            <input type="password" name="senha" id="senha-criar-usuario" required class="w-full px-3 md:px-4 py-2.5 pr-10 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-all">
                            <button type="button" onclick="toggleSenha('senha-criar-usuario', 'toggle-senha-criar-usuario')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                <i id="toggle-senha-criar-usuario" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
                    <button type="button" onclick="fecharModalCriarUsuario()" class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all text-sm font-medium">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary px-4 py-2.5 text-white rounded-lg text-sm font-medium">
                        <i class="fas fa-check mr-2"></i>Criar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let tipoUsuarioAtual = '';
        
        // Função para alternar tabs
        function showTab(tabName, buttonElement = null) {
            // Esconder todas as tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remover classe active de todos os botões
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('tab-active');
            });
            
            // Mostrar tab selecionada
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            
            // Adicionar classe active ao botão
            if (buttonElement) {
                buttonElement.classList.add('tab-active');
            }
            
            // Carregar dados da tab
            if (tabName === 'alunos') {
                carregarAlunosTransporte();
            } else if (tabName === 'motoristas') {
                carregarMotoristasTransporte();
            } else if (tabName === 'usuarios') {
                carregarUsuariosTransporte();
            }
        }

        // Funções de formatação
        function formatarCPF(input) {
            let valor = input.value.replace(/\D/g, '');
            if (valor.length <= 11) {
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                input.value = valor;
            }
        }
        
        function formatarTelefone(input) {
            let valor = input.value.replace(/\D/g, '');
            if (valor.length <= 11) {
                if (valor.length <= 10) {
                    valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
                    valor = valor.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
                    valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
                }
                input.value = valor;
            }
        }
        
        function toggleSenha(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Modais
        function abrirModalCriarUsuario(tipo) {
            tipoUsuarioAtual = tipo;
            document.getElementById('tipo-usuario-modal').value = tipo;
            const titulo = tipo === 'ADM_TRANSPORTE' ? 'Criar Administrador de Transporte' : 'Criar Usuário Transporte Aluno';
            document.getElementById('modalTituloUsuario').textContent = titulo;
            document.getElementById('modalCriarUsuario').classList.remove('hidden');
            // Resetar campos e formatações
            const form = document.getElementById('formCriarUsuario');
            if (form) {
                form.reset();
                // Resetar ícone de senha
                const senhaIcon = document.getElementById('toggle-senha-criar-usuario');
                if (senhaIcon) {
                    const senhaInput = document.getElementById('senha-criar-usuario');
                    if (senhaInput) {
                        senhaInput.type = 'password';
                        senhaIcon.classList.remove('fa-eye-slash');
                        senhaIcon.classList.add('fa-eye');
                    }
                }
            }
        }

        function fecharModalCriarUsuario() {
            const modal = document.getElementById('modalCriarUsuario');
            const form = document.getElementById('formCriarUsuario');
            if (modal) {
                modal.classList.add('hidden');
            }
            if (form) {
                form.reset();
                tipoUsuarioAtual = null;
                // Resetar ícone de senha
                const senhaIcon = document.getElementById('toggle-senha-criar-usuario');
                if (senhaIcon) {
                    const senhaInput = document.getElementById('senha-criar-usuario');
                    if (senhaInput) {
                        senhaInput.type = 'password';
                        senhaIcon.classList.remove('fa-eye-slash');
                        senhaIcon.classList.add('fa-eye');
                    }
                }
            }
        }
        
        // Fechar modal ao clicar no overlay e com ESC (será adicionado no DOMContentLoaded principal)

        // Criar Usuário
        function criarUsuario(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const tipo = tipoUsuarioAtual;
            const acao = tipo === 'ADM_TRANSPORTE' ? 'criar_usuario_adm_transporte' : 'criar_usuario_transporte_aluno';
            
            // Remover formatação do CPF e Telefone antes de enviar
            const cpfInput = document.getElementById('cpf-criar-usuario');
            const telefoneInput = document.getElementById('telefone-criar-usuario');
            if (cpfInput) {
                formData.set('cpf', cpfInput.value.replace(/\D/g, ''));
            }
            if (telefoneInput && telefoneInput.value) {
                formData.set('telefone', telefoneInput.value.replace(/\D/g, ''));
            }
            
            formData.append('acao', acao);
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Criando...';
            }
            
            fetch('gestao_usuarios_transporte.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.status) {
                    // Mostrar mensagem de sucesso
                    const successMsg = data.mensagem || 'Usuário criado com sucesso!';
                    alert(successMsg);
                    fecharModalCriarUsuario();
                    carregarUsuariosTransporte();
                } else {
                    alert('Erro: ' + (data.mensagem || 'Não foi possível criar o usuário'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao criar usuário: ' + error.message);
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        }

        // Lista de distritos de Maranguape (mesma da gestão de localidades)
        const distritosMaranguape = <?= json_encode($distritosLista) ?>;
        
        // Autocomplete para distrito
        const inputDistrito = document.getElementById('filtro-distrito');
        const dropdownDistrito = document.getElementById('autocomplete-dropdown-distrito');
        let filteredDistritos = [];
        let selectedIndexDistrito = -1;
        
        if (inputDistrito && dropdownDistrito) {
            inputDistrito.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                selectedIndexDistrito = -1;
                
                if (query.length === 0) {
                    dropdownDistrito.classList.remove('show');
                    document.getElementById('filtro-distrito-value').value = '';
                    return;
                }
                
                filteredDistritos = distritosMaranguape.filter(distrito => 
                    distrito.toLowerCase().includes(query)
                );
                
                if (filteredDistritos.length === 0) {
                    dropdownDistrito.classList.remove('show');
                    return;
                }
                
                renderDropdownDistrito();
                dropdownDistrito.classList.add('show');
            });
            
            inputDistrito.addEventListener('keydown', function(e) {
                if (!dropdownDistrito.classList.contains('show')) return;
                
                const items = dropdownDistrito.querySelectorAll('.autocomplete-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndexDistrito = Math.min(selectedIndexDistrito + 1, items.length - 1);
                    updateSelectionDistrito(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndexDistrito = Math.max(selectedIndexDistrito - 1, -1);
                    updateSelectionDistrito(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndexDistrito >= 0 && filteredDistritos[selectedIndexDistrito]) {
                        selecionarDistrito(filteredDistritos[selectedIndexDistrito]);
                    }
                } else if (e.key === 'Escape') {
                    dropdownDistrito.classList.remove('show');
                }
            });
            
            document.addEventListener('click', function(e) {
                if (!inputDistrito.contains(e.target) && !dropdownDistrito.contains(e.target)) {
                    dropdownDistrito.classList.remove('show');
                }
            });
            
            function renderDropdownDistrito() {
                dropdownDistrito.innerHTML = filteredDistritos.map((distrito, index) => {
                    const distritoEscapado = distrito.replace(/'/g, "\\'");
                    return `
                    <div class="autocomplete-item ${index === selectedIndexDistrito ? 'selected' : ''}" 
                         data-index="${index}" 
                         data-distrito="${distritoEscapado}"
                         onclick="selecionarDistrito('${distritoEscapado}')">
                        <div class="distrito-nome">${distrito}</div>
                    </div>
                `;
                }).join('');
            }
            
            function updateSelectionDistrito(items) {
                items.forEach((item, index) => {
                    if (index === selectedIndexDistrito) {
                        item.classList.add('selected');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('selected');
                    }
                });
            }
            
            window.selecionarDistrito = function(distrito) {
                document.getElementById('filtro-distrito-value').value = distrito;
                inputDistrito.value = distrito;
                dropdownDistrito.classList.remove('show');
            };
        }
        
        // Autocomplete para distrito na aba de sugestão
        const inputSugestaoDistrito = document.getElementById('sugestao-distrito');
        const dropdownSugestaoDistrito = document.getElementById('autocomplete-dropdown-sugestao-distrito');
        let filteredSugestaoDistritos = [];
        let selectedIndexSugestaoDistrito = -1;
        
        if (inputSugestaoDistrito && dropdownSugestaoDistrito) {
            inputSugestaoDistrito.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                selectedIndexSugestaoDistrito = -1;
                
                if (query.length === 0) {
                    dropdownSugestaoDistrito.classList.remove('show');
                    document.getElementById('sugestao-distrito-value').value = '';
                    return;
                }
                
                filteredSugestaoDistritos = distritosMaranguape.filter(distrito => 
                    distrito.toLowerCase().includes(query)
                );
                
                if (filteredSugestaoDistritos.length === 0) {
                    dropdownSugestaoDistrito.classList.remove('show');
                    return;
                }
                
                renderDropdownSugestaoDistrito();
                dropdownSugestaoDistrito.classList.add('show');
            });
            
            inputSugestaoDistrito.addEventListener('keydown', function(e) {
                if (!dropdownSugestaoDistrito.classList.contains('show')) return;
                
                const items = dropdownSugestaoDistrito.querySelectorAll('.autocomplete-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndexSugestaoDistrito = Math.min(selectedIndexSugestaoDistrito + 1, items.length - 1);
                    updateSelectionSugestaoDistrito(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndexSugestaoDistrito = Math.max(selectedIndexSugestaoDistrito - 1, -1);
                    updateSelectionSugestaoDistrito(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndexSugestaoDistrito >= 0 && filteredSugestaoDistritos[selectedIndexSugestaoDistrito]) {
                        selecionarSugestaoDistrito(filteredSugestaoDistritos[selectedIndexSugestaoDistrito]);
                    }
                } else if (e.key === 'Escape') {
                    dropdownSugestaoDistrito.classList.remove('show');
                }
            });
            
            document.addEventListener('click', function(e) {
                if (!inputSugestaoDistrito.contains(e.target) && !dropdownSugestaoDistrito.contains(e.target)) {
                    dropdownSugestaoDistrito.classList.remove('show');
                }
            });
            
            function renderDropdownSugestaoDistrito() {
                dropdownSugestaoDistrito.innerHTML = filteredSugestaoDistritos.map((distrito, index) => {
                    const distritoEscapado = distrito.replace(/'/g, "\\'");
                    return `
                    <div class="autocomplete-item ${index === selectedIndexSugestaoDistrito ? 'selected' : ''}" 
                         data-index="${index}" 
                         data-distrito="${distritoEscapado}"
                         onclick="selecionarSugestaoDistrito('${distritoEscapado}')">
                        <div class="distrito-nome">${distrito}</div>
                    </div>
                `;
                }).join('');
            }
            
            function updateSelectionSugestaoDistrito(items) {
                items.forEach((item, index) => {
                    if (index === selectedIndexSugestaoDistrito) {
                        item.classList.add('selected');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('selected');
                    }
                });
            }
            
            window.selecionarSugestaoDistrito = function(distrito) {
                document.getElementById('sugestao-distrito-value').value = distrito;
                inputSugestaoDistrito.value = distrito;
                dropdownSugestaoDistrito.classList.remove('show');
                
                // Habilitar campo de localidade e carregar localidades do distrito
                const inputLocalidade = document.getElementById('sugestao-localidade');
                if (inputLocalidade) {
                    inputLocalidade.disabled = false;
                    inputLocalidade.placeholder = 'Digite a localidade...';
                    inputLocalidade.value = ''; // Limpar valor anterior
                    carregarLocalidadesSugestao(distrito);
                }
            };
        }

        // Função auxiliar para escapar HTML
        function escapeHtml(text) {
            if (!text) return '-';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Carregar Alunos
        function carregarAlunosTransporte() {
            const busca = document.getElementById('buscar-aluno-transporte')?.value || '';
            const distrito = document.getElementById('filtro-distrito-value')?.value || '';
            const localidade = document.getElementById('filtro-localidade')?.value || '';
            const formData = new FormData();
            formData.append('acao', 'listar_alunos_transporte');
            formData.append('busca', busca);
            formData.append('distrito', distrito);
            formData.append('localidade', localidade);
            
            const tbody = document.getElementById('lista-alunos-transporte');
            const mobileContainer = document.getElementById('lista-alunos-transporte-mobile');
            
            // Loading state
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-12 text-center"><div class="empty-state"><div class="loading mx-auto mb-4"></div><p class="text-gray-500 font-medium">Carregando alunos...</p></div></td></tr>';
            }
            if (mobileContainer) {
                mobileContainer.innerHTML = '<div class="empty-state"><div class="loading mx-auto mb-4"></div><p class="text-gray-500 font-medium">Carregando alunos...</p></div>';
            }
            
            fetch('gestao_usuarios_transporte.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.status) {
                    const emptyState = '<div class="empty-state"><i class="fas fa-user-graduate empty-state-icon"></i><p class="text-gray-500 font-medium">Nenhum aluno encontrado</p><p class="text-sm text-gray-400 mt-1">Tente ajustar os filtros de busca</p></div>';
                    
                    if (data.dados.length === 0) {
                        if (tbody) {
                            tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center">${emptyState}</td></tr>`;
                        }
                        if (mobileContainer) {
                            mobileContainer.innerHTML = emptyState;
                        }
                        return;
                    }
                    
                    // Desktop table view
                    if (tbody) {
                        tbody.innerHTML = data.dados.map(a => {
                            // Garantir que distrito e localidade estão corretos
                            const distrito = (a.distrito_transporte && a.distrito_transporte !== '-' && a.distrito_transporte !== null && a.distrito_transporte !== '') ? a.distrito_transporte : '-';
                            const localidade = (a.localidade && a.localidade !== '-' && a.localidade !== null && a.localidade !== '') ? a.localidade : '-';
                            
                            return `
                            <tr class="table-row">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(a.nome)}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(a.escola_nome)}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(distrito)}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(localidade)}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    ${a.ja_lotado == 1 ? `
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800" title="Rota: ${escapeHtml(a.rota_nome || 'N/A')}">
                                            <i class="fas fa-check-circle mr-1"></i>Lotado
                                        </span>
                                    ` : `
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>Pendente
                                        </span>
                                    `}
                                </td>
                            </tr>
                            `;
                        }).join('');
                    }
                    
                    // Mobile card view
                    if (mobileContainer) {
                        mobileContainer.innerHTML = data.dados.map(a => {
                            // Garantir que distrito e localidade estão corretos
                            const distrito = (a.distrito_transporte && a.distrito_transporte !== '-' && a.distrito_transporte !== null && a.distrito_transporte !== '') ? a.distrito_transporte : '-';
                            const localidade = (a.localidade && a.localidade !== '-' && a.localidade !== null && a.localidade !== '') ? a.localidade : '-';
                            
                            return `
                            <div class="mobile-card">
                                <div class="mobile-card-header">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 truncate">${escapeHtml(a.nome)}</div>
                                        <div class="text-xs text-gray-500">${escapeHtml(a.escola_nome)}</div>
                                    </div>
                                    ${a.ja_lotado == 1 ? `
                                        <span class="badge bg-blue-100 text-blue-800 border border-blue-200 flex-shrink-0">
                                            <i class="fas fa-check-circle mr-1"></i>Lotado
                                        </span>
                                    ` : `
                                        <span class="badge bg-yellow-100 text-yellow-800 border border-yellow-200 flex-shrink-0">
                                            <i class="fas fa-clock mr-1"></i>Pendente
                                        </span>
                                    `}
                                </div>
                                <div class="mobile-card-body">
                                    <div class="mobile-card-row">
                                        <span class="mobile-card-label"><i class="fas fa-map-marker-alt mr-1"></i>Distrito</span>
                                        <span class="mobile-card-value">${escapeHtml(distrito)}</span>
                                    </div>
                                    <div class="mobile-card-row">
                                        <span class="mobile-card-label"><i class="fas fa-home mr-1"></i>Localidade</span>
                                        <span class="mobile-card-value">${escapeHtml(localidade)}</span>
                                    </div>
                                </div>
                            </div>
                            `;
                        }).join('');
                    }
                } else {
                    throw new Error(data.mensagem || 'Erro ao carregar alunos');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                const errorState = '<div class="empty-state"><i class="fas fa-exclamation-circle empty-state-icon text-red-400"></i><p class="text-red-500 font-medium">Erro ao carregar dados</p><p class="text-sm text-gray-400 mt-1">' + escapeHtml(error.message) + '</p></div>';
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center">${errorState}</td></tr>`;
                }
                if (mobileContainer) {
                    mobileContainer.innerHTML = errorState;
                }
            });
        }

        // Gerar Sugestões de Lotação
        function gerarSugestoes() {
            const distrito = document.getElementById('sugestao-distrito-value')?.value || '';
            const localidade = document.getElementById('sugestao-localidade')?.value || '';
            const formData = new FormData();
            formData.append('acao', 'sugerir_lotacao');
            formData.append('distrito', distrito);
            formData.append('localidade', localidade);
            
            const container = document.getElementById('sugestoes-container');
            container.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i><p class="mt-2 text-gray-600">Gerando sugestões...</p></div>';
            
            fetch('gestao_usuarios_transporte.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    if (data.sugestoes.length === 0) {
                        container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum aluno pendente de lotação encontrado com os filtros selecionados.</p>';
                        return;
                    }
                    
                    container.innerHTML = data.sugestoes.map(sugestao => `
                        <div class="border border-gray-200 rounded-lg p-6 bg-gradient-to-r from-green-50 to-blue-50">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">${sugestao.distrito} - ${sugestao.localidade}</h3>
                                    <p class="text-sm text-gray-600 mt-1">${sugestao.total_alunos} aluno(s) precisam de transporte</p>
                                </div>
                                <span class="px-3 py-1 text-sm rounded-full ${sugestao.tipo_recomendado === 'VAN' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'}">
                                    <i class="fas fa-${sugestao.tipo_recomendado === 'VAN' ? 'car' : 'bus'} mr-1"></i>
                                    Recomendado: ${sugestao.tipo_recomendado}
                                </span>
                            </div>
                            
                            ${sugestao.veiculos_sugeridos.length > 0 ? `
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Veículos Sugeridos:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        ${sugestao.veiculos_sugeridos.slice(0, 3).map(v => `
                                            <div class="bg-white rounded-lg p-3 border border-gray-200">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-sm font-semibold text-gray-900">${v.placa}</span>
                                                    <span class="px-2 py-1 text-xs rounded-full ${v.tipo === 'VAN' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'}">${v.tipo}</span>
                                                </div>
                                                <p class="text-xs text-gray-600">${v.marca || ''} ${v.modelo || ''}</p>
                                                <p class="text-xs text-gray-600 mt-1">Capacidade: ${v.capacidade_maxima} lugares</p>
                                                <p class="text-xs ${v.disponibilidade >= sugestao.total_alunos ? 'text-green-600' : 'text-orange-600'} mt-1">
                                                    Disponibilidade: ${v.disponibilidade} lugares
                                                </p>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : `
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                                    <p class="text-sm text-yellow-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Nenhum veículo disponível com capacidade adequada para este grupo.
                                    </p>
                                </div>
                            `}
                            
                            <details class="mt-4">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                                    Ver lista de alunos (${sugestao.alunos.length})
                                </summary>
                                <div class="mt-2 max-h-48 overflow-y-auto">
                                    <ul class="space-y-1">
                                        ${sugestao.alunos.map(aluno => `
                                            <li class="text-sm text-gray-600 flex items-center justify-between py-1 px-2 hover:bg-gray-50 rounded">
                                                <span>${aluno.nome}</span>
                                                <span class="text-xs text-gray-500">${aluno.escola_nome || '-'}</span>
                                            </li>
                                        `).join('')}
                                    </ul>
                                </div>
                            </details>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p class="text-red-500 text-center py-8">Erro ao gerar sugestões: ' + (data.mensagem || 'Erro desconhecido') + '</p>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                container.innerHTML = '<p class="text-red-500 text-center py-8">Erro ao gerar sugestões. Tente novamente.</p>';
            });
        }

         // Carregar Motoristas
         function carregarMotoristasTransporte() {
             const busca = document.getElementById('buscar-motorista-transporte')?.value || '';
             const formData = new FormData();
             formData.append('acao', 'listar_motoristas');
             formData.append('busca', busca);
             
             const tbody = document.getElementById('lista-motoristas-transporte');
             const mobileContainer = document.getElementById('lista-motoristas-transporte-mobile');
             
             // Loading state
             tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center"><div class="empty-state"><div class="loading mx-auto mb-4"></div><p class="text-gray-500 font-medium">Carregando...</p></div></td></tr>';
             if (mobileContainer) mobileContainer.innerHTML = '<div class="empty-state"><div class="loading mx-auto mb-4"></div><p class="text-gray-500 font-medium">Carregando...</p></div>';
             
             fetch('gestao_usuarios_transporte.php', {
                 method: 'POST',
                 body: formData
             })
             .then(response => response.json())
             .then(data => {
                 if (data.status) {
                     const emptyState = `
                         <div class="empty-state">
                             <i class="fas fa-user-slash empty-state-icon"></i>
                             <p class="text-gray-500 font-medium">Nenhum motorista encontrado</p>
                             <p class="text-sm text-gray-400 mt-1">Tente ajustar os filtros de busca</p>
                         </div>
                     `;
                     
                     if (data.dados.length === 0) {
                         tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center">${emptyState}</td></tr>`;
                         if (mobileContainer) mobileContainer.innerHTML = emptyState;
                         return;
                     }
                     
                     // Desktop table view
                     tbody.innerHTML = data.dados.map(m => `
                         <tr class="table-row">
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                 <div class="flex items-center">
                                     <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-bold text-sm mr-3">
                                         ${(m.nome || '').charAt(0).toUpperCase()}
                                     </div>
                                     <div class="text-sm font-semibold text-gray-900">${m.nome || '-'}</div>
                                 </div>
                             </td>
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">${m.cpf || '-'}</td>
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                 <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-200">
                                     <i class="fas fa-id-card mr-1"></i>${m.cnh || '-'}
                                 </span>
                             </td>
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                 ${m.categoria_cnh ? `<span class="badge bg-purple-50 text-purple-700 border border-purple-200">${m.categoria_cnh}</span>` : '-'}
                             </td>
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                 <i class="fas fa-phone text-gray-400 mr-2"></i>${m.telefone || '-'}
                             </td>
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                 <span class="badge ${m.ativo ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'}">
                                     <i class="fas fa-${m.ativo ? 'check' : 'times'}-circle mr-1"></i>${m.ativo ? 'Ativo' : 'Inativo'}
                                 </span>
                             </td>
                         </tr>
                     `).join('');
                     
                     // Mobile card view
                     if (mobileContainer) {
                         mobileContainer.innerHTML = data.dados.map(m => `
                             <div class="mobile-card">
                                 <div class="mobile-card-header">
                                     <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-bold text-sm mr-3">
                                         ${(m.nome || '').charAt(0).toUpperCase()}
                                     </div>
                                     <div class="flex-1 min-w-0">
                                         <div class="text-sm font-semibold text-gray-900 truncate">${m.nome || '-'}</div>
                                     </div>
                                     <span class="badge ${m.ativo ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'} flex-shrink-0">
                                         <i class="fas fa-${m.ativo ? 'check' : 'times'}-circle mr-1"></i>${m.ativo ? 'Ativo' : 'Inativo'}
                                     </span>
                                 </div>
                                 <div class="mobile-card-body">
                                     <div class="mobile-card-row">
                                         <span class="mobile-card-label"><i class="fas fa-id-card mr-1"></i>CPF</span>
                                         <span class="mobile-card-value font-mono">${m.cpf || '-'}</span>
                                     </div>
                                     <div class="mobile-card-row">
                                         <span class="mobile-card-label"><i class="fas fa-car mr-1"></i>CNH</span>
                                         <span class="mobile-card-value">${m.cnh || '-'}</span>
                                     </div>
                                     ${m.categoria_cnh ? `
                                     <div class="mobile-card-row">
                                         <span class="mobile-card-label">Categoria</span>
                                         <span class="mobile-card-value">${m.categoria_cnh}</span>
                                     </div>
                                     ` : ''}
                                     <div class="mobile-card-row">
                                         <span class="mobile-card-label"><i class="fas fa-phone mr-1"></i>Telefone</span>
                                         <span class="mobile-card-value">${m.telefone || '-'}</span>
                                     </div>
                                 </div>
                             </div>
                         `).join('');
                     }
                 }
             })
             .catch(error => {
                 console.error('Erro:', error);
                 const errorState = '<div class="empty-state"><i class="fas fa-exclamation-circle empty-state-icon text-red-400"></i><p class="text-red-500 font-medium">Erro ao carregar dados</p></div>';
                 tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center">${errorState}</td></tr>`;
                 if (mobileContainer) mobileContainer.innerHTML = errorState;
             });
         }

         // Carregar Usuários
         function carregarUsuariosTransporte() {
             const busca = document.getElementById('buscar-usuario-transporte')?.value || '';
             const formData = new FormData();
             formData.append('acao', 'listar_usuarios_transporte');
             formData.append('busca', busca);
             
             const tbody = document.getElementById('lista-usuarios-transporte');
             const mobileContainer = document.getElementById('lista-usuarios-transporte-mobile');
             
             // Loading state
             tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-12 text-center"><div class="empty-state"><div class="loading mx-auto mb-4"></div><p class="text-gray-500 font-medium">Carregando...</p></div></td></tr>';
             if (mobileContainer) mobileContainer.innerHTML = '<div class="empty-state"><div class="loading mx-auto mb-4"></div><p class="text-gray-500 font-medium">Carregando...</p></div>';
             
             fetch('gestao_usuarios_transporte.php', {
                 method: 'POST',
                 body: formData
             })
             .then(response => response.json())
             .then(data => {
                 if (data.status) {
                     // Mapeamento de tipos para nomes amigáveis
                     const tipoNomes = {
                         'ADM_TRANSPORTE': 'Administrador de Transporte',
                         'TRANSPORTE_ALUNO': 'Transporte Aluno'
                     };
                     
                     const emptyState = `
                         <div class="empty-state">
                             <i class="fas fa-users-slash empty-state-icon"></i>
                             <p class="text-gray-500 font-medium">Nenhum usuário encontrado</p>
                             <p class="text-sm text-gray-400 mt-1">Tente ajustar os filtros de busca</p>
                         </div>
                     `;
                     
                     if (data.dados.length === 0) {
                         tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center">${emptyState}</td></tr>`;
                         if (mobileContainer) mobileContainer.innerHTML = emptyState;
                         return;
                     }
                     
                     // Desktop table view
                     tbody.innerHTML = data.dados.map(u => `
                         <tr class="table-row">
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                 <div class="flex items-center">
                                     <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm mr-3">
                                         ${(u.nome || '').charAt(0).toUpperCase()}
                                     </div>
                                     <div>
                                         <div class="text-sm font-semibold text-gray-900">${u.nome || '-'}</div>
                                         ${u.username ? `<div class="text-xs text-gray-500">@${u.username}</div>` : ''}
                                     </div>
                                 </div>
                             </td>
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                 <span class="badge ${u.tipo === 'ADM_TRANSPORTE' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-blue-50 text-blue-700 border border-blue-200'}">
                                     <i class="fas fa-${u.tipo === 'ADM_TRANSPORTE' ? 'user-shield' : 'user-graduate'} mr-1"></i>
                                     ${tipoNomes[u.tipo] || u.tipo}
                                 </span>
                             </td>
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                 <i class="fas fa-envelope text-gray-400 mr-2"></i>${u.email || '-'}
                             </td>
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                 <span class="badge ${u.ativo ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'}">
                                     <i class="fas fa-${u.ativo ? 'check' : 'times'}-circle mr-1"></i>${u.ativo ? 'Ativo' : 'Inativo'}
                                 </span>
                             </td>
                             <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                 <div class="flex items-center space-x-2">
                                     <button onclick="editarUsuarioTransporte(${u.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Editar">
                                         <i class="fas fa-edit"></i>
                                     </button>
                                     <button onclick="excluirUsuarioTransporte(${u.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Excluir">
                                         <i class="fas fa-trash"></i>
                                     </button>
                                 </div>
                             </td>
                         </tr>
                     `).join('');
                     
                     // Mobile card view
                     if (mobileContainer) {
                         mobileContainer.innerHTML = data.dados.map(u => `
                             <div class="mobile-card">
                                 <div class="mobile-card-header">
                                     <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm mr-3">
                                         ${(u.nome || '').charAt(0).toUpperCase()}
                                     </div>
                                     <div class="flex-1 min-w-0">
                                         <div class="text-sm font-semibold text-gray-900 truncate">${u.nome || '-'}</div>
                                         ${u.username ? `<div class="text-xs text-gray-500">@${u.username}</div>` : ''}
                                     </div>
                                     <span class="badge ${u.ativo ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'} flex-shrink-0">
                                         <i class="fas fa-${u.ativo ? 'check' : 'times'}-circle mr-1"></i>${u.ativo ? 'Ativo' : 'Inativo'}
                                     </span>
                                 </div>
                                 <div class="mobile-card-body">
                                     <div class="mobile-card-row">
                                         <span class="mobile-card-label"><i class="fas fa-tag mr-1"></i>Tipo</span>
                                         <span class="mobile-card-value">${tipoNomes[u.tipo] || u.tipo}</span>
                                     </div>
                                     <div class="mobile-card-row">
                                         <span class="mobile-card-label"><i class="fas fa-envelope mr-1"></i>Email</span>
                                         <span class="mobile-card-value truncate">${u.email || '-'}</span>
                                     </div>
                                     <div class="mobile-card-row">
                                         <span class="mobile-card-label">Ações</span>
                                         <span class="mobile-card-value">
                                             <button onclick="editarUsuarioTransporte(${u.id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all mr-2" title="Editar">
                                                 <i class="fas fa-edit"></i>
                                             </button>
                                             <button onclick="excluirUsuarioTransporte(${u.id})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Excluir">
                                                 <i class="fas fa-trash"></i>
                                             </button>
                                         </span>
                                     </div>
                                 </div>
                             </div>
                         `).join('');
                     }
                 }
             })
             .catch(error => {
                 console.error('Erro:', error);
                 const errorState = '<div class="empty-state"><i class="fas fa-exclamation-circle empty-state-icon text-red-400"></i><p class="text-red-500 font-medium">Erro ao carregar dados</p></div>';
                 tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center">${errorState}</td></tr>`;
                 if (mobileContainer) mobileContainer.innerHTML = errorState;
             });
         }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Event listeners
        document.getElementById('buscar-aluno-transporte')?.addEventListener('input', debounce(carregarAlunosTransporte, 500));
        document.getElementById('buscar-motorista-transporte')?.addEventListener('input', debounce(carregarMotoristasTransporte, 500));
        document.getElementById('buscar-usuario-transporte')?.addEventListener('input', debounce(carregarUsuariosTransporte, 500));

        // Variáveis globais para sugestão de lotação
        let distritoSugestaoSelecionado = null;
        let localidadesSugestaoDisponiveis = [];
        
        // Funções para carregar localidades na sugestão de lotação
        function carregarLocalidadesSugestao(distrito) {
            if (!distrito) {
                const inputLocalidade = document.getElementById('sugestao-localidade');
                if (inputLocalidade) {
                    inputLocalidade.disabled = true;
                    inputLocalidade.value = '';
                    inputLocalidade.placeholder = 'Selecione primeiro um distrito';
                }
                return;
            }
            
            fetch(`?acao=buscar_localidades&distrito=${encodeURIComponent(distrito)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.localidades && data.localidades.length > 0) {
                        localidadesSugestaoDisponiveis = data.localidades;
                        distritoSugestaoSelecionado = distrito;
                    } else {
                        localidadesSugestaoDisponiveis = [];
                        distritoSugestaoSelecionado = null;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar localidades:', error);
                    localidadesSugestaoDisponiveis = [];
                });
        }
        
        function buscarLocalidadesSugestao(query) {
            const input = document.getElementById('sugestao-localidade');
            const dropdown = document.getElementById('autocomplete-dropdown-sugestao-localidade');
            if (!input || !dropdown || !distritoSugestaoSelecionado) return;
            
            const queryLower = query.trim().toLowerCase();
            
            if (queryLower.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            const filteredLocalidades = localidadesSugestaoDisponiveis.filter(loc => 
                loc.localidade.toLowerCase().includes(queryLower)
            );
            
            if (filteredLocalidades.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            
            dropdown.innerHTML = filteredLocalidades.map((loc) => `
                <div class="autocomplete-item" onclick="selecionarLocalidadeSugestao('${loc.localidade.replace(/'/g, "\\'")}')">
                    <div>${loc.localidade}</div>
                </div>
            `).join('');
            dropdown.classList.add('show');
        }
        
        window.selecionarLocalidadeSugestao = function(localidade) {
            document.getElementById('sugestao-localidade').value = localidade;
            document.getElementById('autocomplete-dropdown-sugestao-localidade').classList.remove('show');
        };
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('autocomplete-dropdown-sugestao-localidade');
            const input = document.getElementById('sugestao-localidade');
            if (dropdown && input && !input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Carregar dados iniciais e configurar eventos
        document.addEventListener('DOMContentLoaded', function() {
            // Fechar modal ao clicar no overlay
            const modal = document.getElementById('modalCriarUsuario');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        fecharModalCriarUsuario();
                    }
                });
            }
            
            // Fechar modal com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('modalCriarUsuario');
                    if (modal && !modal.classList.contains('hidden')) {
                        fecharModalCriarUsuario();
                    }
                }
            });
            
            // Carregar dados da aba ativa
            const tabAtiva = document.querySelector('.tab-button.active');
            if (tabAtiva) {
                const tabId = tabAtiva.getAttribute('onclick')?.match(/showTab\('([^']+)'\)/)?.[1];
                if (tabId === 'alunos') {
                    carregarAlunosTransporte();
                } else if (tabId === 'motoristas') {
                    carregarMotoristasTransporte();
                } else if (tabId === 'usuarios') {
                    carregarUsuariosTransporte();
                } else {
                    carregarAlunosTransporte();
                }
            } else {
                // Por padrão, carregar alunos
                carregarAlunosTransporte();
            }
        });
    </script>
        </div>
    </main>
</body>
</html>
