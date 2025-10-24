<?php
// Limpar qualquer output anterior
ob_clean();

// Desabilitar exibição de erros para evitar HTML na resposta JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar método OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    session_start();
    require_once('../../../config/Database.php');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor']);
    exit;
}

// Verificar se o usuário está logado
error_log("Calendar API - Session check: " . json_encode($_SESSION));
error_log("Calendar API - Session keys: " . implode(', ', array_keys($_SESSION)));

// Verificação mais flexível da sessão
$isLoggedIn = false;
if (isset($_SESSION['tipo']) || isset($_SESSION['id']) || isset($_SESSION['usuario_id'])) {
    $isLoggedIn = true;
}

if (!$isLoggedIn) {
    error_log("Calendar API - User not authorized");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Função para buscar eventos
function getCalendarEvents($start, $end) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT 
                id,
                title,
                description,
                start_date as start,
                end_date as end,
                all_day,
                color,
                event_type
            FROM calendar_events 
            WHERE ativo = 1 
            AND (
                (start_date BETWEEN :start AND :end) 
                OR (end_date BETWEEN :start AND :end)
                OR (start_date <= :start AND end_date >= :end)
            )
            ORDER BY start_date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start', $start);
    $stmt->bindParam(':end', $end);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para adicionar evento
function addCalendarEvent($data) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    error_log("Calendar API - addCalendarEvent called with data: " . json_encode($data));
    
    $sql = "INSERT INTO calendar_events 
            (title, description, start_date, end_date, all_day, color, event_type, school_id, created_by) 
            VALUES (:title, :description, :start_date, :end_date, :all_day, :color, :event_type, :school_id, :created_by)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':start_date', $data['start_date']);
    $stmt->bindParam(':end_date', $data['end_date']);
    $stmt->bindParam(':all_day', $data['all_day'], PDO::PARAM_BOOL);
    $stmt->bindParam(':color', $data['color']);
    $stmt->bindParam(':event_type', $data['event_type']);
    $stmt->bindParam(':school_id', $data['school_id'] ?? null, PDO::PARAM_INT);
    $stmt->bindParam(':created_by', $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? 1, PDO::PARAM_INT);
    
    $result = $stmt->execute();
    $rowCount = $stmt->rowCount();
    
    error_log("Calendar API - addCalendarEvent result: " . ($result ? 'true' : 'false') . ", rows affected: $rowCount");
    
    if (!$result) {
        error_log("Calendar API - Error adding event: " . json_encode($stmt->errorInfo()));
    }
    
    return $result;
}

// Função para atualizar evento
function updateCalendarEvent($id, $data) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    error_log("Calendar API - updateCalendarEvent called with id=$id, data=" . json_encode($data));
    
    $sql = "UPDATE calendar_events SET 
            title = :title,
            description = :description,
            start_date = :start_date,
            end_date = :end_date,
            all_day = :all_day,
            color = :color,
            event_type = :event_type,
            school_id = :school_id,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND created_by = :created_by AND ativo = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':start_date', $data['start_date']);
    $stmt->bindParam(':end_date', $data['end_date']);
    $stmt->bindParam(':all_day', $data['all_day'], PDO::PARAM_BOOL);
    $stmt->bindParam(':color', $data['color']);
    $stmt->bindParam(':event_type', $data['event_type']);
    $stmt->bindParam(':school_id', $data['school_id'] ?? null);
    $stmt->bindParam(':created_by', $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? 1);
    
    $result = $stmt->execute();
    $rowCount = $stmt->rowCount();
    
    error_log("Calendar API - updateCalendarEvent result: " . ($result ? 'true' : 'false') . ", rows affected: $rowCount");
    
    if (!$result) {
        error_log("Calendar API - updateCalendarEvent error: " . json_encode($stmt->errorInfo()));
    }
    
    return $result;
}

// Função para deletar evento
function deleteCalendarEvent($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "UPDATE calendar_events 
            SET ativo = 0, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND created_by = :created_by";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':created_by', $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? 1);
    
    return $stmt->execute();
}

// Função para buscar evento por ID
function getCalendarEvent($id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT * FROM calendar_events 
            WHERE id = :id AND created_by = :created_by AND ativo = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':created_by', $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? 1);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Processar requisições
$method = $_SERVER['REQUEST_METHOD'];

// Debug: Log da sessão
error_log("Calendar API - Method: $method, Session: " . json_encode($_SESSION));

// Verificar se a tabela existe
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->query("SHOW TABLES LIKE 'calendar_events'");
    if ($stmt->rowCount() == 0) {
        throw new Exception('Tabela calendar_events não existe. Execute o SQL de configuração.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de configuração: ' . $e->getMessage()]);
    exit;
}

try {
    switch($method) {
        case 'GET':
            $start = $_GET['start'] ?? date('Y-m-d');
            $end = $_GET['end'] ?? date('Y-m-d', strtotime('+1 month'));
            
            error_log("Calendar API - GET request: start=$start, end=$end");
            
            $events = getCalendarEvents($start, $end);
            error_log("Calendar API - Events found: " . count($events));
            
            echo json_encode($events);
            break;
            
        case 'POST':
            $rawInput = file_get_contents('php://input');
            error_log("Calendar API - Raw POST input: " . $rawInput);
            
            $data = json_decode($rawInput, true);
            error_log("Calendar API - POST data: " . json_encode($data));
            
            if (!$data) {
                error_log("Calendar API - JSON decode failed: " . json_last_error_msg());
                throw new Exception('Dados inválidos: ' . json_last_error_msg());
            }
            
            // Validação básica
            if (empty($data['title'])) {
                throw new Exception('Título é obrigatório');
            }
            
            if (empty($data['start_date'])) {
                throw new Exception('Data de início é obrigatória');
            }
            
            // Definir valores padrão
            $data['all_day'] = $data['all_day'] ?? false;
            $data['color'] = $data['color'] ?? '#3B82F6';
            $data['event_type'] = $data['event_type'] ?? 'event';
            
            error_log("Calendar API - Final data: " . json_encode($data));
            
            if (addCalendarEvent($data)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Evento adicionado com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao adicionar evento');
            }
            break;
            
        case 'PUT':
            $id = $_GET['id'] ?? null;
            
            error_log("Calendar API - PUT request: id=$id");
            
            if (!$id) {
                throw new Exception('ID do evento é obrigatório');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            error_log("Calendar API - PUT data: " . json_encode($data));
            
            if (!$data) {
                throw new Exception('Dados inválidos');
            }
            
            // Verificar se o evento existe
            $event = getCalendarEvent($id);
            error_log("Calendar API - Event found: " . json_encode($event));
            
            if (!$event) {
                throw new Exception('Evento não encontrado');
            }
            
            // Validação básica
            if (empty($data['title'])) {
                throw new Exception('Título é obrigatório');
            }
            
            if (empty($data['start_date'])) {
                throw new Exception('Data de início é obrigatória');
            }
            
            // Definir valores padrão
            $data['all_day'] = $data['all_day'] ?? false;
            $data['color'] = $data['color'] ?? '#3B82F6';
            $data['event_type'] = $data['event_type'] ?? 'event';
            
            error_log("Calendar API - Calling updateCalendarEvent with id=$id");
            $updateResult = updateCalendarEvent($id, $data);
            error_log("Calendar API - Update result: " . ($updateResult ? 'true' : 'false'));
            
            if ($updateResult) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Evento atualizado com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao atualizar evento');
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID do evento é obrigatório');
            }
            
            // Verificar se o evento existe
            $event = getCalendarEvent($id);
            if (!$event) {
                throw new Exception('Evento não encontrado');
            }
            
            if (deleteCalendarEvent($id)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Evento excluído com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao excluir evento');
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados'
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}

// Garantir que sempre retornamos JSON válido
if (!headers_sent()) {
    header('Content-Type: application/json');
}
?>
