<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

// Set JSON content type
header('Content-Type: application/json; charset=utf-8');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Autenticação e permissão
$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Content-Type: text/plain; charset=UTF-8');
    http_response_code(401);
    echo 'Você não tem permissão para acessar essa página.';
    exit;
}

// Conexão com BD
$db = Database::getInstance();
$conn = $db->getConnection();

// === Early dispatch for alunos and professores, then exit
$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? '';

// Handle professores request
if ($tipo === 'professores') {
    try {
        // First, try to get the list of tables to determine the schema
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        // Check if we have a professors table (professor or professores)
        $hasProfessorTable = in_array('professor', $tables) || in_array('professores', $tables);
        $tableName = in_array('professor', $tables) ? 'professor' : (in_array('professores', $tables) ? 'professores' : null);
        
        if (!$hasProfessorTable) {
            throw new Exception("Tabela de professores não encontrada no banco de dados.");
        }
        
        // Check if we have a pessoa table for additional info
        $hasPessoaTable = in_array('pessoa', $tables);
        
        // Build the query based on available tables
        if ($hasPessoaTable) {
            // Join with pessoa table to get person details
            $query = "
                SELECT 
                    p.id,
                    p.nome,
                    p.cpf,
                    prof.matricula
                FROM professor prof
                LEFT JOIN pessoa p ON p.id = prof.pessoa_id
                WHERE prof.ativo = 1
                ORDER BY p.nome ASC
            ";
        } else {
            // Fallback if pessoa table doesn't exist (shouldn't happen in this case)
            $query = "
                SELECT 
                    id,
                    CONCAT(nome, ' (sem dados de pessoa)') as nome,
                    cpf,
                    matricula
                FROM professor
                WHERE ativo = 1
                ORDER BY nome ASC
            ";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($professores)) {
            throw new Exception("Nenhum professor cadastrado no sistema.");
        }

        // Format the data for the response
        $response = [
            'success' => true,
            'data' => $professores,
            'total' => count($professores),
            'gerado_em' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar dados dos professores: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Handle alunos request
if ($tipo === 'alunos') {
    try {
        // First, try to get the list of tables to determine the schema
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        // Check if we have a students table (aluno or alunos)
        $hasAlunoTable = in_array('aluno', $tables) || in_array('alunos', $tables);
        $tableName = in_array('aluno', $tables) ? 'aluno' : (in_array('alunos', $tables) ? 'alunos' : null);
        
        if (!$hasAlunoTable) {
            throw new Exception("Tabela de alunos não encontrada no banco de dados.");
        }
        
        // Check if we have a pessoa table for additional info
        $hasPessoaTable = in_array('pessoa', $tables);
        
        // Build the query based on available tables
        if ($hasPessoaTable) {
            // If we have a pessoa table, join with it to get additional info
            $query = "
                SELECT 
                    a.id,
                    p.nome,
                    p.cpf,
                    a.matricula,
                    p.email,
                    p.telefone,
                    p.data_nascimento
                FROM $tableName a
                LEFT JOIN pessoa p ON p.id = a.pessoa_id
                ORDER BY p.nome ASC
            ";
        } else {
            // If no pessoa table, assume all data is in the aluno table
            $query = "
                SELECT 
                    id,
                    nome,
                    cpf,
                    matricula,
                    email,
                    telefone,
                    data_nascimento
                FROM $tableName
                ORDER BY nome ASC
            ";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($alunos)) {
            throw new Exception("Nenhum aluno cadastrado no sistema.");
        }

        // Format the data for the response
        $response = [
            'success' => true,
            'data' => $alunos,
            'total' => count($alunos),
            'gerado_em' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar dados dos alunos: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Se chegou até aqui, é porque o tipo de relatório não é suportado
http_response_code(400);
echo json_encode([
    'success' => false,
    'error' => 'Tipo de relatório não suportado. Use "alunos" ou "professores".'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;

// Funções auxiliares (não usadas no momento, mas mantidas para referência)
function getDatabaseName(PDO $conn): ?string {
    $dbName = null;
    $query = $conn->query("SELECT DATABASE()");
    if ($query) {
        $dbName = $query->fetchColumn();
    }
    return $dbName;
}

function tableExists(PDO $conn, string $table): bool {
    $dbName = getDatabaseName($conn);
    if (!$dbName) return false;
    $stmt = $conn->prepare('
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl
    ');
    $stmt->execute([':db' => $dbName, ':tbl' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $conn, string $table, string $column): bool {
    $dbName = getDatabaseName($conn);
    if (!$dbName) return false;
    $stmt = $conn->prepare('
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col
    ');
    $stmt->execute([':db' => $dbName, ':tbl' => $table, ':col' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

// Build base SELECT
$baseSelect = "
    SELECT 
        u.id AS usuario_id,
        p.nome AS nome,
        p.cpf AS cpf,
        p.email AS email,
        p.telefone AS telefone
    FROM usuario u
    LEFT JOIN pessoa p ON p.id = u.pessoa_id
";

// Decide filter path
$sql = '';
if (columnExists($conn, 'usuario', 'tipo')) {
    // Original assumption: usuario.tipo exists
    $sql = $baseSelect . " WHERE u.tipo = 'PROFESSOR' ORDER BY p.nome ASC";
} elseif (tableExists($conn, 'professor')) {
    // Try to join with professor table
    $join = '';
    if (columnExists($conn, 'professor', 'usuario_id')) {
        $join = " LEFT JOIN professor pr ON pr.usuario_id = u.id ";
    } elseif (columnExists($conn, 'professor', 'pessoa_id')) {
        $join = " LEFT JOIN professor pr ON pr.pessoa_id = u.pessoa_id ";
    } else {
        header('Content-Type: text/plain; charset=UTF-8');
        http_response_code(500);
        echo "Tabela 'professor' encontrada, mas não possui colunas usuais para relacionar (usuario_id ou pessoa_id). Informe o esquema para ajustar o relatório.";
        exit;
    }
    // Assuming professor has a primary key 'id' (common). We filter by existence of the relationship.
    $idCol = columnExists($conn, 'professor', 'id') ? 'pr.id' : 'pr.usuario_id';
    $sql = $baseSelect . $join . " WHERE {$idCol} IS NOT NULL ORDER BY p.nome ASC";
} else {
    header('Content-Type: text/plain; charset=UTF-8');
    http_response_code(500);
    echo "Não foi possível identificar como filtrar professores. Falta a coluna 'usuario.tipo' e não existe a tabela 'professor'. Informe a estrutura das tabelas (usuario, pessoa e professor) para ajustar a consulta.";
    exit;
}
// ======= END: dynamic schema detection =======

if (!($stmt = $conn->prepare($sql))) {
    header('Content-Type: text/plain; charset=UTF-8');
    http_response_code(500);
    echo 'Erro ao preparar consulta de professores.';
    exit;
}

if (!$stmt->execute()) {
    header('Content-Type: text/plain; charset=UTF-8');
    http_response_code(500);
    echo 'Erro ao executar consulta de professores.';
    exit;
}

// REPLACE the entire result fetching block with PDO-safe fetching:
$rows = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = $row;
}
$stmt->closeCursor();

class ProfessoresPDF extends CustomFPDF {
    // NEW: column widths and helper to center the table
    public $colWidths = [15, 65, 30, 50, 20]; // Telefone widened (20mm), Email reduced (50mm) to keep total = 180mm

    function tableX() {
        $effective = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $tableWidth = array_sum($this->colWidths);
        $x = $this->lMargin + max(0, ($effective - $tableWidth) / 2);
        $this->SetX($x);
        return $x;
    }

    function Header() {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('SIGAE - Relatório de Professores'), 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, utf8_decode('Gerado em: ' . date('d/m/Y H:i')), 0, 1, 'C');
        $this->Ln(6);

        // Cabeçalho da tabela (centralizado e com estilo)
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);
        $this->SetDrawColor(180, 180, 180);

        $this->tableX();
        $this->Cell($this->colWidths[0], 9, utf8_decode('ID'),       1, 0, 'C', true);
        $this->Cell($this->colWidths[1], 9, utf8_decode('Nome'),     1, 0, 'L', true);
        $this->Cell($this->colWidths[2], 9, utf8_decode('CPF'),      1, 0, 'C', true);
        $this->Cell($this->colWidths[3], 9, utf8_decode('Email'),    1, 0, 'L', true);
        $this->Cell($this->colWidths[4], 9, utf8_decode('Telefone'), 1, 1, 'C', true);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ' . $this->PageNo() . '/{nb}'), 0, 0, 'C');
    }
}

$pdf = new ProfessoresPDF('P', 'mm', 'A4'); // CHANGED: Portrait (vertical)
// NEW: margins unchanged; they work well with the new widths
$pdf->SetMargins(15, 18, 15);
$pdf->SetAutoPageBreak(true, 18);

$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);

if (empty($rows)) {
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 8, utf8_decode('Nenhum professor encontrado.'), 0, 1, 'C');
} else {
    $fill = false; // NEW: zebra striping
    foreach ($rows as $r) {
        $id       = $r['usuario_id'] ?? '';
        $nome     = $r['nome'] ?? '';
        $cpf      = $r['cpf'] ?? '';
        $email    = $r['email'] ?? '';
        $telefone = $r['telefone'] ?? '';

        // NEW: centered table and subtle borders/fill
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);

        $pdf->tableX();
        $pdf->Cell($pdf->colWidths[0], 8, utf8_decode((string)$id),     1, 0, 'C', true);
        $pdf->Cell($pdf->colWidths[1], 8, utf8_decode($nome ?: '-'),    1, 0, 'L', true);
        $pdf->Cell($pdf->colWidths[2], 8, utf8_decode($cpf ?: '-'),     1, 0, 'C', true);
        $pdf->Cell($pdf->colWidths[3], 8, utf8_decode($email ?: '-'),   1, 0, 'L', true);
        $pdf->Cell($pdf->colWidths[4], 8, utf8_decode($telefone ?: '-'),1, 1, 'C', true);

        $fill = !$fill;
    }
}

// Envia o PDF ao navegador
$pdf->Output('I', 'relatorio_professores.pdf');

// === REMOVED: duplicate alunos route at the end (now handled above)