<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

// Tenta incluir FPDF via Composer autoload primeiro
$fpdfLoaded = false;
$composerAutoloads = [
    __DIR__ . '/../../../../../vendor/autoload.php', // projeto estagio\vendor
    __DIR__ . '/../../../../vendor/autoload.php',    // Gest-o-Escolar-\vendor (caso exista)
    'c:\\xampp\\htdocs\\projeto estagio\\vendor\\autoload.php'
];
foreach ($composerAutoloads as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once($autoloadPath);
        if (class_exists('FPDF', false)) {
            $fpdfLoaded = true;
            break;
        }
    }
}

// Se autoload não carregar FPDF, tenta caminhos diretos do fpdf.php
if (!$fpdfLoaded) {
    $fpdfCandidates = [
        // Possíveis vendors no projeto atual
        __DIR__ . '/../../../../../vendor/setasign/fpdf/fpdf.php', // projeto estagio\vendor\setasign\fpdf
        __DIR__ . '/../../../../../vendor/fpdf/fpdf.php',          // projeto estagio\vendor\fpdf

        // Vendors no subprojeto Gest-o-Escolar- (caso tenha seu próprio composer)
        __DIR__ . '/../../../../vendor/setasign/fpdf/fpdf.php',
        __DIR__ . '/../../../../vendor/fpdf/fpdf.php',

        // Caminhos absolutos comuns no Windows (XAMPP)
        'c:\\xampp\\htdocs\\projeto estagio\\vendor\\setasign\\fpdf\\fpdf.php',
        'c:\\xampp\\htdocs\\projeto estagio\\vendor\\fpdf\\fpdf.php',

        // Outros diretórios locais possíveis
        __DIR__ . '/../../../libs/fpdf/fpdf.php',
        __DIR__ . '/../../../library/fpdf/fpdf.php'
    ];

    foreach ($fpdfCandidates as $path) {
        if (file_exists($path)) {
            require_once($path);
            $fpdfLoaded = class_exists('FPDF', false);
            if ($fpdfLoaded) {
                break;
            }
        }
    }
}

if (!$fpdfLoaded) {
    header('Content-Type: text/plain; charset=UTF-8');
    http_response_code(500);
    echo 'Biblioteca FPDF não encontrada. Ajuste o caminho em gerar_relatorio.php.';
    exit;
}

// Autenticação e permissão
$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

// === CHANGE: accept both 'professores' and 'alunos'
$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? '';
if ($tipo !== 'professores' && $tipo !== 'alunos') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Tipo de relatório não suportado.';
    exit;
}

// Conexão com BD
$db = Database::getInstance();
$conn = $db->getConnection();

// === NEW: early dispatch for alunos, then exit
if ($tipo === 'alunos') {
    try {
        // FIX: use table 'aluno' (singular) and join with 'pessoa' to get nome/cpf
        $stmt = $conn->prepare("
            SELECT 
                a.id AS id,
                p.nome AS nome,
                p.cpf AS cpf,
                a.matricula AS matricula
            FROM aluno a
            LEFT JOIN pessoa p ON p.id = a.pessoa_id
            ORDER BY p.nome ASC
        ");
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/pdf');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();

        // Título
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, mb_convert_encoding('Relatório de Alunos', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->Ln(4);

        // Cabeçalho da tabela
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(20, 8, 'ID', 1);
        $pdf->Cell(80, 8, mb_convert_encoding('Nome', 'ISO-8859-1', 'UTF-8'), 1);
        $pdf->Cell(40, 8, 'CPF', 1);
        $pdf->Cell(40, 8, mb_convert_encoding('Matrícula', 'ISO-8859-1', 'UTF-8'), 1);
        $pdf->Ln();

        // Linhas
        $pdf->SetFont('Arial', '', 10);
        foreach ($alunos as $aluno) {
            $pdf->Cell(20, 8, (string)($aluno['id'] ?? ''), 1);
            $pdf->Cell(80, 8, mb_convert_encoding((string)($aluno['nome'] ?? ''), 'ISO-8859-1', 'UTF-8'), 1);
            $pdf->Cell(40, 8, mb_convert_encoding((string)($aluno['cpf'] ?? ''), 'ISO-8859-1', 'UTF-8'), 1);
            $pdf->Cell(40, 8, mb_convert_encoding((string)($aluno['matricula'] ?? ''), 'ISO-8859-1', 'UTF-8'), 1);
            $pdf->Ln();
        }

        // Exibe no navegador e encerra
        $pdf->Output('I', 'relatorio_alunos.pdf');
        exit;
    } catch (Throwable $e) {
        header('Content-Type: text/plain; charset=UTF-8');
        http_response_code(500);
        echo 'Erro ao gerar relatório de alunos: ' . $e->getMessage();
        exit;
    }
}

// Ajuste a consulta conforme seu esquema de dados.
// Esboço: professores como usuários com tipo = 'PROFESSOR' vinculados a pessoa.

// ======= BEGIN: dynamic schema detection to build the correct SQL =======
function getDatabaseName(PDO $conn): ?string {
    $stmt = $conn->query('SELECT DATABASE()');
    return $stmt ? $stmt->fetchColumn() : null;
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

class ProfessoresPDF extends FPDF {
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