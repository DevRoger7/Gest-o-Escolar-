<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

if (!isset($_SESSION['tipo']) || strtolower($_SESSION['tipo']) !== 'professor') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

// BEGIN: Replace professor/turmas lookup to match frequencia_professor.php
$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar professor_id igual ao frequencia_professor.php
$professorId = null;
$pessoaId = $_SESSION['pessoa_id'] ?? null;
if ($pessoaId) {
    $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
    $stmtProfessor = $conn->prepare($sqlProfessor);
    $pessoaIdParam = $pessoaId;
    $stmtProfessor->bindParam(':pessoa_id', $pessoaIdParam);
    $stmtProfessor->execute();
    $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
    $professorId = $professor['id'] ?? null;
}

// Fallback: tentar obter pessoa_id via usuario_id e CPF se necessário
if (!$professorId) {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    if (!$pessoaId && $usuarioId) {
        $sqlPessoa = "SELECT pessoa_id FROM usuario WHERE id = :usuario_id LIMIT 1";
        $stmtPessoa = $conn->prepare($sqlPessoa);
        $usuarioIdParam = $usuarioId;
        $stmtPessoa->bindParam(':usuario_id', $usuarioIdParam);
        $stmtPessoa->execute();
        $usuario = $stmtPessoa->fetch(PDO::FETCH_ASSOC);
        $pessoaId = $usuario['pessoa_id'] ?? null;
    }
    if (!$pessoaId) {
        $cpf = $_SESSION['cpf'] ?? null;
        if ($cpf) {
            $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
            $sqlPessoaCpf = "SELECT id FROM pessoa WHERE cpf = :cpf LIMIT 1";
            $stmtPessoaCpf = $conn->prepare($sqlPessoaCpf);
            $stmtPessoaCpf->bindParam(':cpf', $cpfLimpo);
            $stmtPessoaCpf->execute();
            $pessoa = $stmtPessoaCpf->fetch(PDO::FETCH_ASSOC);
            $pessoaId = $pessoa['id'] ?? null;
        }
    }
    if ($pessoaId) {
        $sqlProfessor = "SELECT pr.id FROM professor pr WHERE pr.pessoa_id = :pessoa_id AND pr.ativo = 1 LIMIT 1";
        $stmtProfessor = $conn->prepare($sqlProfessor);
        $pessoaIdParam = $pessoaId;
        $stmtProfessor->bindParam(':pessoa_id', $pessoaIdParam);
        $stmtProfessor->execute();
        $professor = $stmtProfessor->fetch(PDO::FETCH_ASSOC);
        $professorId = $professor['id'] ?? null;
    }
}

// Buscar turmas e disciplinas do professor (idêntico ao frequencia_professor.php)
$turmasProfessor = [];
if ($professorId) {
    $sqlTurmas = "SELECT DISTINCT 
                    t.id as turma_id,
                    CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                    t.serie,
                    t.letra,
                    t.turno,
                    d.id as disciplina_id,
                    d.nome as disciplina_nome,
                    e.id as escola_id,
                    e.nome as escola_nome
                  FROM turma_professor tp
                  INNER JOIN turma t ON tp.turma_id = t.id
                  INNER JOIN disciplina d ON tp.disciplina_id = d.id
                  INNER JOIN escola e ON t.escola_id = e.id
                  WHERE tp.professor_id = :professor_id AND tp.fim IS NULL AND t.ativo = 1
                  ORDER BY t.serie, t.letra, d.nome";
    $stmtTurmas = $conn->prepare($sqlTurmas);
    $stmtTurmas->bindParam(':professor_id', $professorId);
    $stmtTurmas->execute();
    $turmasProfessor = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
}
// END: Replace professor/turmas lookup to match frequencia_professor.php

// === BEGIN: PDF handler (view/download report for a turma) ===
if (isset($_GET['action']) && $_GET['action'] === 'pdf') {
    // Iniciar output buffering para evitar qualquer output antes do PDF
    ob_start();
    
    // Desabilitar exibição de erros para evitar output indesejado
    $oldErrorReporting = error_reporting(0);
    ini_set('display_errors', 0);
    
    try {
        // Ensure DB connection is available
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
        $disciplinaId = isset($_GET['disciplina_id']) ? (int)$_GET['disciplina_id'] : 0;
        $modo = $_GET['modo'] ?? 'ver'; // 'ver' ou 'baixar'

        if ($turmaId <= 0) {
            ob_end_clean();
            header('Content-Type: text/plain; charset=UTF-8');
            http_response_code(400);
            echo 'Turma inválida.';
            exit;
        }

        // Validar se o professor tem acesso a esta turma
        if ($professorId) {
            $sqlValidar = "SELECT COUNT(*) as total
                          FROM turma_professor tp
                          WHERE tp.turma_id = :turma_id 
                          AND tp.professor_id = :professor_id 
                          AND (tp.fim IS NULL OR tp.fim = '' OR tp.fim = '0000-00-00')";
            if ($disciplinaId > 0) {
                $sqlValidar .= " AND tp.disciplina_id = :disciplina_id";
            }
            $stmtValidar = $conn->prepare($sqlValidar);
            $stmtValidar->bindParam(':turma_id', $turmaId, PDO::PARAM_INT);
            $stmtValidar->bindParam(':professor_id', $professorId, PDO::PARAM_INT);
            if ($disciplinaId > 0) {
                $stmtValidar->bindParam(':disciplina_id', $disciplinaId, PDO::PARAM_INT);
            }
            $stmtValidar->execute();
            $validacao = $stmtValidar->fetch(PDO::FETCH_ASSOC);
            
            if (!$validacao || $validacao['total'] == 0) {
                ob_end_clean();
                header('Content-Type: text/plain; charset=UTF-8');
                http_response_code(403);
                echo 'Você não tem permissão para acessar esta turma.';
                exit;
            }
        } else {
            ob_end_clean();
            header('Content-Type: text/plain; charset=UTF-8');
            http_response_code(401);
            echo 'Professor não identificado.';
            exit;
        }

        // Discover turma and disciplina names from $turmasProfessor
        $turmaNome = '';
        $disciplinaNome = '';
        $escolaNome = '';
        foreach ($turmasProfessor as $tp) {
            if ((int)$tp['turma_id'] === $turmaId && ((int)$tp['disciplina_id'] === $disciplinaId || $disciplinaId === 0)) {
                $turmaNome = $tp['turma_nome'] ?? '';
                $disciplinaNome = $tp['disciplina_nome'] ?? '';
                $escolaNome = $tp['escola_nome'] ?? '';
                break;
            }
        }
        
        // Se não encontrou nos dados do professor, buscar diretamente do banco
        if (empty($turmaNome)) {
            $sqlTurma = "SELECT 
                            CONCAT(t.serie, ' ', t.letra, ' - ', t.turno) as turma_nome,
                            d.nome as disciplina_nome,
                            e.nome as escola_nome
                         FROM turma t
                         LEFT JOIN disciplina d ON d.id = :disciplina_id
                         INNER JOIN escola e ON t.escola_id = e.id
                         WHERE t.id = :turma_id AND t.ativo = 1
                         LIMIT 1";
            $stmtTurma = $conn->prepare($sqlTurma);
            $stmtTurma->bindParam(':turma_id', $turmaId, PDO::PARAM_INT);
            $stmtTurma->bindParam(':disciplina_id', $disciplinaId, PDO::PARAM_INT);
            $stmtTurma->execute();
            $turmaData = $stmtTurma->fetch(PDO::FETCH_ASSOC);
            if ($turmaData) {
                $turmaNome = $turmaData['turma_nome'] ?? '';
                $disciplinaNome = $turmaData['disciplina_nome'] ?? '';
                $escolaNome = $turmaData['escola_nome'] ?? '';
            }
        }

        // Inicializar dataset vazio
        $dadosRelatorio = [];

        // === Populate $dadosRelatorio with alunos da turma ===
        // Helper functions
        $tableExists = function(PDO $c, string $t): bool {
            try {
                $q = "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t";
                $st = $c->prepare($q);
                $st->bindValue(':t', $t);
                $st->execute();
                return (int)$st->fetchColumn() > 0;
            } catch (Throwable $e) {
                error_log("Erro ao verificar tabela $t: " . $e->getMessage());
                return false;
            }
        };
        
        $columnExists = function(PDO $c, string $t, string $col): bool {
            try {
                $q = "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c";
                $st = $c->prepare($q);
                $st->bindValue(':t', $t);
                $st->bindValue(':c', $col);
                $st->execute();
                return (int)$st->fetchColumn() > 0;
            } catch (Throwable $e) {
                error_log("Erro ao verificar coluna $t.$col: " . $e->getMessage());
                return false;
            }
        };
    
        $queries = [];
    
        // REPLACED: aluno_turma uses `status` (not `situacao`) according to the backup
        if ($tableExists($conn, 'aluno_turma')) {
            $queries[] =
                "SELECT 
                     p.nome AS aluno_nome, 
                     a.id   AS aluno_id,
                     COALESCE(at.status, 'MATRICULADO') AS situacao
                 FROM aluno_turma at
                 INNER JOIN aluno a   ON at.aluno_id = a.id
                 INNER JOIN pessoa p  ON a.pessoa_id = p.id
                 WHERE 
                     at.turma_id = :turma_id
                     AND (at.fim IS NULL OR at.fim = '' OR at.fim = '0000-00-00')
                     AND a.ativo = 1
                 ORDER BY p.nome";
        }
    
        if ($tableExists($conn, 'turma_aluno')) {
            $queries[] =
                "SELECT 
                     p.nome AS aluno_nome, 
                     a.id   AS aluno_id,
                     COALESCE(ta.situacao, '') AS situacao
                 FROM turma_aluno ta
                 INNER JOIN aluno a   ON ta.aluno_id = a.id
                 INNER JOIN pessoa p  ON a.pessoa_id = p.id
                 WHERE ta.turma_id = :turma_id
                 ORDER BY p.nome";
        }

        if ($tableExists($conn, 'matricula')) {
            $queries[] =
                "SELECT 
                     p.nome AS aluno_nome, 
                     a.id   AS aluno_id,
                     COALESCE(m.situacao, '') AS situacao
                 FROM matricula m
                 INNER JOIN aluno a   ON m.aluno_id = a.id
                 INNER JOIN pessoa p  ON a.pessoa_id = p.id
                 WHERE m.turma_id = :turma_id
                 ORDER BY p.nome";
        }

        // Fallback: some schemas keep turma_id directly in aluno
        if ($columnExists($conn, 'aluno', 'turma_id')) {
            $queries[] =
                "SELECT 
                     p.nome AS aluno_nome, 
                     a.id   AS aluno_id,
                     '' AS situacao
                 FROM aluno a
                 INNER JOIN pessoa p ON a.pessoa_id = p.id
                 WHERE a.turma_id = :turma_id
                 ORDER BY p.nome";
        }

        // If no known table/column detected, still try the common names without strict filters
        if (empty($queries)) {
            $queries = [
                "SELECT p.nome AS aluno_nome, a.id AS aluno_id, COALESCE(at.situacao, '') AS situacao
                 FROM aluno_turma at
                 INNER JOIN aluno a   ON at.aluno_id = a.id
                 INNER JOIN pessoa p  ON a.pessoa_id = p.id
                 WHERE at.turma_id = :turma_id
                 ORDER BY p.nome",
                "SELECT p.nome AS aluno_nome, a.id AS aluno_id, COALESCE(ta.situacao, '') AS situacao
                 FROM turma_aluno ta
                 INNER JOIN aluno a   ON ta.aluno_id = a.id
                 INNER JOIN pessoa p  ON a.pessoa_id = p.id
                 WHERE ta.turma_id = :turma_id
                 ORDER BY p.nome",
                "SELECT p.nome AS aluno_nome, a.id AS aluno_id, COALESCE(m.situacao, '') AS situacao
                 FROM matricula m
                 INNER JOIN aluno a   ON m.aluno_id = a.id
                 INNER JOIN pessoa p  ON a.pessoa_id = p.id
                 WHERE m.turma_id = :turma_id
                 ORDER BY p.nome",
            ];
        }

        $usedSql = null;

        foreach ($queries as $sql) {
            try {
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $usedSql = $sql;
                    foreach ($rows as $row) {
                        // NEW: Fetch the most recent validated nota for this aluno/turma/disciplina
                        $notaStr = '-';
                        $notaValNumeric = null; // <<< ADDED: Keep numeric for circunstância
                        if ($disciplinaId > 0 && !empty($row['aluno_id'])) {
                            try {
                                // Tentar consulta com campos opcionais primeiro
                                $sqlNota = "SELECT ROUND(AVG(sub.nota), 1) as nota
                                    FROM (
                                        SELECT n.nota
                                        FROM nota n
                                        WHERE 
                                            n.disciplina_id = :disciplina_id
                                            AND n.aluno_id = :aluno_id
                                        ORDER BY 
                                            COALESCE(n.bimestre, 0) DESC,
                                            COALESCE(n.atualizado_em, n.lancado_em, '0000-00-00 00:00:00') DESC
                                        LIMIT 3
                                    ) as sub";
                                
                                $stmtNota = $conn->prepare($sqlNota);
                                // $stmtNota->bindValue(':turma_id', $turmaId, PDO::PARAM_INT); // Removido para buscar notas independente da turma
                                $stmtNota->bindValue(':disciplina_id', $disciplinaId, PDO::PARAM_INT);
                                $stmtNota->bindValue(':aluno_id', (int)$row['aluno_id'], PDO::PARAM_INT);
                                $stmtNota->execute();
                                $notaVal = $stmtNota->fetchColumn();
                                
                                if ($notaVal !== false && $notaVal !== null && $notaVal !== '') {
                                    $notaValNumeric = is_numeric($notaVal) ? (float)$notaVal : null;
                                    // Format as string (e.g., 7.5 -> "7,5")
                                    $notaStr = is_numeric($notaVal)
                                        ? number_format((float)$notaVal, 1, ',', '.')
                                        : (string)$notaVal;
                                }
                            } catch (PDOException $e2) {
                                // Log erro específico do PDO
                                error_log("Erro PDO ao buscar nota para aluno {$row['aluno_id']} na turma $turmaId: " . $e2->getMessage());
                            } catch (Throwable $e2) {
                                // Log erro genérico e mantém "-"
                                error_log("Erro ao buscar nota para aluno {$row['aluno_id']} na turma $turmaId: " . $e2->getMessage());
                            }
                        }

                        // <<< ADDED: compute "circunstancia" using nota numeric value
                        $circunstancia = '-';
                        if ($notaValNumeric !== null) {
                            $circunstancia = ($notaValNumeric >= 6.0) ? 'Aprovado' : 'Reprovado';
                        }

                        // <<< CHANGED: include nota_val and circunstancia in dataset
                        $dadosRelatorio[] = [
                            'aluno' => $row['aluno_nome'] ?? 'Aluno',
                            'nota' => $notaStr,
                            'nota_val' => $notaValNumeric,
                            'situacao' => isset($row['situacao']) && $row['situacao'] !== '' ? $row['situacao'] : 'Sem dados',
                            'circunstancia' => $circunstancia,
                        ];
                    }
                    // Se encontrou dados em uma consulta, não tenta as demais
                    break;
                }
            } catch (Throwable $e) {
                // Log do erro e continua tentando próxima consulta
                error_log("Erro ao executar consulta SQL para turma $turmaId: " . $e->getMessage());
                error_log("SQL: " . $sql);
            }
        }

        // Optional: quick debug endpoint to verify which query is used
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'turma_id' => $turmaId,
                'disciplina_id' => $disciplinaId,
                'query_used' => $usedSql,
                'count' => count($dadosRelatorio),
                'sample' => array_slice($dadosRelatorio, 0, 5),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // --- BEGIN: Load and use FPDF (like gerar_relatorio.php) ---
        $fpdfLoaded = false;
        
        // Caminho correto do autoload: app/main/Views/dashboard -> ../../../../vendor/autoload.php
        $baseDir = dirname(dirname(dirname(dirname(__DIR__)))); // Volta até a raiz do projeto
        $vendorAutoload = $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        
        $composerAutoloads = [
            $vendorAutoload, // Caminho calculado dinamicamente
            __DIR__ . '/../../../../vendor/autoload.php',    // Gest-o-Escolar-\vendor
            __DIR__ . '/../../../../../vendor/autoload.php', // projeto estagio\vendor (fallback)
            'c:\\xampp\\htdocs\\GitHub\\Gest-o-Escolar-\\vendor\\autoload.php', // Caminho absoluto
            'c:\\xampp\\htdocs\\projeto estagio\\vendor\\autoload.php' // Fallback antigo
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

        if (!$fpdfLoaded) {
            // Tentar carregar diretamente o arquivo fpdf.php
            $fpdfCandidates = [
                $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'setasign' . DIRECTORY_SEPARATOR . 'fpdf' . DIRECTORY_SEPARATOR . 'fpdf.php',
                __DIR__ . '/../../../../vendor/setasign/fpdf/fpdf.php',
                __DIR__ . '/../../../../../vendor/setasign/fpdf/fpdf.php',
                __DIR__ . '/../../../../../vendor/fpdf/fpdf.php',
                'c:\\xampp\\htdocs\\GitHub\\Gest-o-Escolar-\\vendor\\setasign\\fpdf\\fpdf.php',
                'c:\\xampp\\htdocs\\projeto estagio\\vendor\\setasign\\fpdf\\fpdf.php',
                'c:\\xampp\\htdocs\\projeto estagio\\vendor\\fpdf\\fpdf.php',
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
            
            // Se ainda não encontrou, tentar baixar automaticamente do GitHub
            if (!$fpdfLoaded) {
                $fpdfDir = $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'setasign' . DIRECTORY_SEPARATOR . 'fpdf';
                $fpdfFile = $fpdfDir . DIRECTORY_SEPARATOR . 'fpdf.php';
                
                if (!file_exists($fpdfFile)) {
                    // Tentar criar o diretório e baixar o arquivo
                    if (!is_dir($fpdfDir)) {
                        @mkdir($fpdfDir, 0755, true);
                    }
                    
                    if (is_dir($fpdfDir) && is_writable($fpdfDir)) {
                        // Baixar fpdf.php do GitHub (versão 1.8.2)
                        $fpdfUrl = 'https://raw.githubusercontent.com/Setasign/FPDF/1.8.2/fpdf.php';
                        $fpdfContent = @file_get_contents($fpdfUrl);
                        
                        if ($fpdfContent !== false && strlen($fpdfContent) > 1000) {
                            // Arquivo parece válido (mais de 1000 bytes)
                            @file_put_contents($fpdfFile, $fpdfContent);
                            
                            if (file_exists($fpdfFile)) {
                                require_once($fpdfFile);
                                $fpdfLoaded = class_exists('FPDF', false);
                            }
                        }
                    }
                } else {
                    // Arquivo existe, tentar carregar
                    require_once($fpdfFile);
                    $fpdfLoaded = class_exists('FPDF', false);
                }
            }
        }

        if (!$fpdfLoaded) {
            // Mensagem de erro mais informativa com debug
            header('Content-Type: text/html; charset=UTF-8');
            http_response_code(500);
            $debugInfo = '';
            if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                $debugInfo = '<h3>Caminhos de autoload testados:</h3><ul>';
                foreach ($composerAutoloads as $path) {
                    $exists = file_exists($path) ? '✓' : '✗';
                    $debugInfo .= '<li>' . $exists . ' ' . htmlspecialchars($path) . '</li>';
                }
                $debugInfo .= '</ul><h3>Arquivos FPDF testados:</h3><ul>';
                if (isset($fpdfCandidates)) {
                    foreach ($fpdfCandidates as $path) {
                        $exists = file_exists($path) ? '✓' : '✗';
                        $debugInfo .= '<li>' . $exists . ' ' . htmlspecialchars($path) . '</li>';
                    }
                }
                $debugInfo .= '</ul>';
                $debugInfo .= '<p><strong>__DIR__:</strong> ' . htmlspecialchars(__DIR__) . '</p>';
                $debugInfo .= '<p><strong>Base Dir calculado:</strong> ' . htmlspecialchars($baseDir ?? 'não definido') . '</p>';
            }
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erro - FPDF não encontrado</title></head><body>';
            echo '<h1>Biblioteca FPDF não encontrada</h1>';
            echo '<p>O sistema não conseguiu localizar a biblioteca FPDF necessária para gerar os relatórios em PDF.</p>';
            echo '<p><strong>Solução:</strong> Execute <code>composer install</code> na raiz do projeto para instalar as dependências.</p>';
            echo '<p>Ou adicione <code>&debug=1</code> à URL para ver os caminhos testados.</p>';
            echo $debugInfo;
            echo '</body></html>';
            exit;
        }

        // Classe para gerar relatório PDF com layout aprimorado
        class TurmaPDF extends FPDF {
            // Configurações de layout
            // Larguras ajustadas: Aluno (65), Nota (20), Situação (50), Circunstância (45) = 180mm
            public $colWidths = [65, 20, 50, 45]; 
            public $headerTitle = 'Relatório de Notas';
            public $turmaNome = '';
            public $disciplinaNome = '';
            public $escolaNome = '';
            private $fill = false; // Para alternar o preenchimento das linhas
            
            // Cores personalizadas (RGB)
            private $headerColor = [30, 90, 150];      // Azul mais escuro para melhor contraste
            private $headerTextColor = [255, 255, 255]; // Branco puro
            private $rowColor1 = [255, 255, 255];      // Branco puro
            private $rowColor2 = [240, 245, 250];      // Azul muito claro suave
            private $textColor = [40, 40, 40];         // Preto suave para melhor leitura
            private $borderColor = [200, 210, 220];    // Cor das bordas mais suave

        // Método auxiliar para preparar texto com codificação correta
        protected function prepareText($text) {
            if (!is_string($text)) {
                return $text;
            }
            
            // Converte diretamente para ISO-8859-1 sem tratamento especial
            return utf8_decode(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
        }
        
        // Sobrescrever _loadfont para garantir a codificação correta
        protected function _loadfont($font) {
            // Forçar codificação cp1252 para todas as fontes
            $defaultFont = [
                'name' => 'Helvetica',
                'type' => 'Core',
                'up' => -100,
                'ut' => 50,
                'cw' => array_fill(0, 256, 600),
                'enc' => 'cp1252',
                'file' => '',
                'desc' => array('Flags' => 32), // NORMAL
                'originalsize' => 0
            ];
            
            // Se não for uma fonte padrão, retorna a fonte padrão
            $standardFonts = ['courier', 'helvetica', 'times', 'symbol', 'zapfdingbats'];
            $fontBase = strtolower(preg_replace('/[^a-z0-9_]/', '', strtolower($font)));
            
            if (!in_array($fontBase, $standardFonts)) {
                return $defaultFont;
            }
            
            // Tenta carregar a fonte normalmente
            try {
                $fontData = parent::_loadfont($font);
                $fontData['enc'] = 'cp1252'; // Força a codificação
                return $fontData;
            } catch (Exception $e) {
                return $defaultFont;
            }
        }

        function tableX() {
            // Retorna a margem esquerda para alinhar tudo à esquerda
            return $this->lMargin;
        }

        function BasicTable($header, $data) {
            // Configurações básicas
            $this->SetFont('Helvetica', '', 10);
            $this->SetTextColor($this->textColor[0], $this->textColor[1], $this->textColor[2]);
            $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
            
            // Define margens e posição inicial
            $this->SetLeftMargin(15); // Margem esquerda fixa
            $this->SetRightMargin(15); // Margem direita fixa
            $x = 15; // Posição X inicial
            $this->SetX($x);
            
            // Larguras fixas para as colunas (aumentando a largura da coluna Situação)
            $this->colWidths = [100, 30, 60, 60]; // Ajuste conforme necessidade
            
            // Dados
            foreach($data as $row) {
                // Alterna a cor de fundo das linhas
                $this->fill = !$this->fill;
                $fillColor = $this->fill ? $this->rowColor2 : $this->rowColor1;
                $this->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
                
                // Prepara os textos
                $aluno = $this->prepareText($row[0]);
                $nota = $this->prepareText($row[1]);
                $situacao = $this->prepareText($row[2]);
                $circunstancia = $this->prepareText($row[3]);
                
                // Posição Y atual
                $y_inicial = $this->GetY();
                
                // Larguras totais
                $total_width = array_sum($this->colWidths);
                
                // Desenha a linha inteira de uma vez
                $this->SetX($x);
                
                // Célula do Aluno
                $this->SetFont('Helvetica', '', 9);
                $this->Cell($this->colWidths[0], 8, $aluno, 1, 0, 'L', true);
                
                // Célula da Nota
                $this->SetFont('Helvetica', 'B', 10);
                $this->Cell($this->colWidths[1], 8, $nota, 1, 0, 'L', true);
                
                // Célula da Situação (ajustada para justificar à esquerda)
                $this->SetFont('Helvetica', '', 9);
                $this->Cell($this->colWidths[2], 8, $situacao, 1, 0, 'L', true, 0, 0, true, 0, '', true);
                
                // Célula da Circunstância
                $this->SetFont('Helvetica', '', 8);
                $this->Cell($this->colWidths[3], 8, $circunstancia, 1, 1, 'L', true);
                
                // Pula para próxima linha
                $this->Ln(0);
                
                // Ajusta a posição Y para a próxima linha
                $this->SetY($y_inicial + $altura_linha_efetiva);
            }
            
            // Ajusta o espaçamento após a tabela
            $this->Ln(5);
        }
        
        // Função auxiliar para calcular número de linhas
        function NbLines($w, $txt) {
            $cw = &$this->CurrentFont['cw'];
            if($w == 0) {
                $w = $this->w - $this->rMargin - $this->x;
            }
            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
            $s = str_replace("\r", '', $txt);
            $nb = strlen($s);
            if($nb > 0 && $s[$nb-1] == "\n") {
                $nb--;
            }
            $sep = -1;
            $i = 0;
            $j = 0;
            $l = 0;
            $nl = 1;
            while($i < $nb) {
                $c = $s[$i];
                if($c == "\n") {
                    $i++;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                    continue;
                }
                if($c == ' ') {
                    $sep = $i;
                }
                $l += $cw[$c];
                if($l > $wmax) {
                    if($sep == -1) {
                        if($i == $j) {
                            $i++;
                        }
                    } else {
                        $i = $sep + 1;
                    }
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                } else {
                    $i++;
                }
            }
            return $nl;
        }

        function Header() {
            // Configuração do cabeçalho
            $this->SetMargins(15, 15, 15);
            $this->SetAutoPageBreak(true, 15);
            
            // Título principal
            $this->SetFont('Helvetica', 'B', 16);
            $this->SetTextColor($this->headerTextColor[0], $this->headerTextColor[1], $this->headerTextColor[2]);
            $this->SetFillColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
            $this->SetX(15);
            $this->Cell(0, 12, $this->prepareText($this->headerTitle), 0, 1, 'L', true);
            
            // Informações da escola
            $this->SetFont('Helvetica', 'B', 12);
            $this->SetTextColor($this->textColor[0], $this->textColor[1], $this->textColor[2]);
            $this->SetX(15);
            $this->Cell(0, 8, $this->prepareText($this->escolaNome), 0, 1, 'L');
            
            // Informações da turma e disciplina
            $this->SetFont('Helvetica', '', 10);
            $this->SetX(15);
            $this->Cell(0, 6, 'Turma: ' . $this->prepareText($this->turmaNome), 0, 1, 'L');
            $this->SetX(15);
            $this->Cell(0, 6, 'Disciplina: ' . $this->prepareText($this->disciplinaNome), 0, 1, 'L');
            
            // Data de geração
            date_default_timezone_set('America/Sao_Paulo');
            $this->SetX(15);
            $this->Cell(0, 6, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
            
            // Espaçamento
            $this->Ln(5);
            
            // Linha divisória mais fina e sutil
            $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
            $this->Line($this->lMargin, $this->GetY() + 3, $this->GetPageWidth() - $this->rMargin, $this->GetY() + 3);
            $this->Ln(6);
            
            // Cabeçalho da tabela já foi movido para cima
            
            // Cabeçalho da tabela
            $this->SetFont('Helvetica', 'B', 10);
            $this->SetTextColor(255, 255, 255); // Texto branco
            $this->SetFillColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
            $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
            
            // Posiciona a tabela centralizada
            $x = $this->tableX();
            $this->SetX($x);
            
            // Cabeçalhos das colunas com textos pré-definidos
            // Cabeçalho da tabela já está sendo desenhado acima
            
            // Desenha os cabeçalhos
            $this->SetX(15);
            $this->SetFont('Helvetica', 'B', 10);
            $this->SetFillColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
            $this->SetTextColor(255, 255, 255);
            
            // Usando prepareText para corrigir acentuação e alinhando centralizado (exceto nome)
            $this->Cell($this->colWidths[0], 8, $this->prepareText('Aluno'), 1, 0, 'L', true);
            $this->Cell($this->colWidths[1], 8, $this->prepareText('Nota'), 1, 0, 'C', true);
            $this->Cell($this->colWidths[2], 8, $this->prepareText('Situação'), 1, 0, 'C', true);
            $this->Cell($this->colWidths[3], 8, $this->prepareText('Circunstância'), 1, 1, 'C', true);
            
            // Reseta as cores
            $this->SetTextColor($this->textColor[0], $this->textColor[1], $this->textColor[2]);
            $this->SetFillColor($this->rowColor1[0], $this->rowColor1[1], $this->rowColor1[2]);
            $this->Ln();
            
            // Resetar cores para o conteúdo da tabela
            $this->SetTextColor($this->textColor[0], $this->textColor[1], $this->textColor[2]);
            $this->SetFillColor($this->rowColor1[0], $this->rowColor1[1], $this->rowColor1[2]);
        }

        function Footer() {
            // Posiciona a 1.5cm da parte inferior
            $this->SetY(-15);
            
            // Configura a fonte e cores
            $this->SetFont('Helvetica', 'I', 8);
            $this->SetTextColor(120, 120, 120); // Cinza mais escuro para melhor legibilidade
            
            // Linha superior do rodapé mais sutil
            $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
            $this->Line($this->lMargin, $this->GetY(), $this->GetPageWidth() - $this->rMargin, $this->GetY());
            
            // Texto do rodapé com fonte mais escura
            $this->SetFont('Helvetica', 'I', 9);
            $this->Cell(0, 10, $this->prepareText('Página '.$this->PageNo().' de {nb}'), 0, 0, 'C');
            
            // Data e hora de geração (usando a hora atual do servidor)
            date_default_timezone_set('America/Sao_Paulo');
            $this->SetX($this->lMargin);
            $this->SetFont('Helvetica', 'I', 9);
            $this->Cell(0, 10, $this->prepareText('Gerado em: ' . date('d/m/Y H:i:s')), 0, 0, 'L');
            
            // Nome do sistema
            $this->SetX($this->lMargin);
            $this->SetFont('Helvetica', 'BI', 8);
            $this->Cell(0, 10, $this->prepareText('SIGEA - Sistema de Gestão Escolar'), 0, 0, 'R');
        }
    }

        // Limpar qualquer output que possa ter sido gerado
        ob_end_clean();
        
        // Generate PDF using FPDF
        header('Content-Type: application/pdf');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Definir constante FPDF_FONTPATH como vazio antes de criar o PDF
        // Isso força o FPDF a usar apenas as fontes padrão (CoreFonts) sem tentar carregar arquivos externos
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', '');
        }
        
        $pdf = new TurmaPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 18, 15);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->turmaNome = (string)($turmaNome ?? '');
        $pdf->disciplinaNome = (string)($disciplinaNome ?? '');
        $pdf->escolaNome = (string)($escolaNome ?? ($_SESSION["escola_atual"] ?? 'Escola Municipal'));

        $pdf->AliasNbPages();
        $pdf->AddPage();
        // Usar fonte padrão Helvetica ao invés de Arial
        $pdf->SetFont('Helvetica', '', 9);

        if (empty($dadosRelatorio)) {
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 8, utf8_decode('Sem dados disponíveis para esta turma.'), 0, 1, 'C');
        } else {
            $fill = false;
            foreach ($dadosRelatorio as $item) {
                $aluno = (string)($item['aluno'] ?? 'Aluno');
                $nota = isset($item['nota']) ? (string)$item['nota'] : '-';
                $situacao = (string)($item['situacao'] ?? 'Sem dados');
                // <<< ADDED: pick circunstancia from dataset (computed earlier)
                $circunstancia = (string)($item['circunstancia'] ?? '-');

                $pdf->SetDrawColor(220, 220, 220);
                $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);

                $pdf->tableX();
                $pdf->Cell($pdf->colWidths[0], 8, utf8_decode($aluno),         1, 0, 'L', true);
                $pdf->Cell($pdf->colWidths[1], 8, utf8_decode($nota),          1, 0, 'C', true);
                // Situação alinhada à esquerda ('L') com um pequeno recuo visual (espaço no início)
                $pdf->Cell($pdf->colWidths[2], 8, ' ' . utf8_decode(trim($situacao)), 1, 0, 'L', true);
                // Circunstância também alinhada à esquerda com recuo, seguindo o padrão
                $pdf->Cell($pdf->colWidths[3], 8, ' ' . utf8_decode(trim($circunstancia)), 1, 1, 'L', true);

                $fill = !$fill;
            }
        }

        $filename = 'Relatorio_Turma_' . $turmaId . '.pdf';
        $dest = ($modo === 'baixar') ? 'D' : 'I';
        $pdf->Output($dest, $filename);
        exit;
    } catch (Throwable $e) {
        // Limpar buffer antes de enviar mensagem de erro
        ob_end_clean();
        
        // Restaurar configurações de erro
        error_reporting($oldErrorReporting);
        ini_set('display_errors', 1);
        
        // Erro ao gerar PDF
        error_log("Erro ao gerar PDF do relatório para turma $turmaId: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        header('Content-Type: text/html; charset=UTF-8');
        http_response_code(500);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erro ao gerar relatório</title></head><body>';
        echo '<h1>Erro ao gerar relatório</h1>';
        echo '<p>Ocorreu um erro ao gerar o relatório em PDF.</p>';
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            echo '<p><strong>Erro:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        echo '<p><a href="relatorios_professor.php">Voltar para relatórios</a></p>';
        echo '</body></html>';
        exit;
    } finally {
        // Garantir que o buffer seja limpo mesmo em caso de erro não capturado
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        // Restaurar configurações de erro
        error_reporting($oldErrorReporting);
        ini_set('display_errors', 1);
    }
}
// === END: PDF handler ===

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-transition { transition: all 0.3s ease-in-out; }
        .content-transition { transition: margin-left 0.3s ease-in-out; }
        .menu-item.active {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.12) 0%, rgba(45, 90, 39, 0.06) 100%);
            border-right: 3px solid #2D5A27;
        }
        .menu-item:hover {
            background: linear-gradient(90deg, rgba(45, 90, 39, 0.08) 0%, rgba(45, 90, 39, 0.04) 100%);
            transform: translateX(4px);
        }
        .mobile-menu-overlay {
            transition: opacity 0.3s ease-in-out;
        }
        @media (max-width: 1023px) {
            .sidebar-mobile {
                transform: translateX(-100%);
            }
            .sidebar-mobile.open {
                transform: translateX(0);
            }
        }
        .header-section {
            background: linear-gradient(135deg, #fff 0%, #eff6ff 100%);
        }
        .turma-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .turma-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-left-color: #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_professor.php'; ?>
    
    <!-- Main Content -->
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left flex items-center gap-3">
                        <div class="hidden lg:block p-2 bg-blue-100 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">Relatórios</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- School Info (Desktop Only) -->
                        <div class="hidden lg:block">
                            <?php if ($_SESSION['tipo'] === 'ADM') { ?>
                                <!-- Para ADM, texto simples com padding para alinhamento -->
                                <div class="text-right px-4 py-2">
                                    <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                    <p class="text-xs text-gray-500">Órgão Central</p>
                                </div>
                            <?php } else { ?>
                                <!-- Para outros usuários, card verde com ícone -->
                                <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span class="text-sm font-semibold">
                                            <?php echo !empty($_SESSION['escola_atual']) ? htmlspecialchars($_SESSION['escola_atual']) : 'N/A'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header Section -->
                <div class="header-section rounded-2xl p-6 mb-6 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Relatórios das Turmas</h2>
                            <p class="text-sm text-gray-600 mt-1">Visualize e baixe relatórios em PDF das suas turmas</p>
                        </div>
                    </div>
                </div>

                <!-- Turmas Section -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <?php if (empty($turmasProfessor)): ?>
                        <div class="text-center py-16">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma turma encontrada</h3>
                            <p class="text-gray-600">Você não possui turmas atribuídas no momento.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <?php foreach ($turmasProfessor as $turma): ?>
                                <div class="turma-card bg-white border-2 border-gray-200 rounded-xl p-5 hover:border-blue-300 shadow-sm">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="p-2 bg-blue-50 rounded-lg">
                                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                </div>
                                                <h3 class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($turma['turma_nome']) ?></h3>
                                            </div>
                                            <div class="pl-11 space-y-1">
                                                <p class="text-sm font-medium text-gray-700 flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                    </svg>
                                                    <?= htmlspecialchars($turma['disciplina_nome']) ?>
                                                </p>
                                                <p class="text-xs text-gray-500 flex items-center gap-2">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    <?= htmlspecialchars($turma['escola_nome']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 mt-4 pt-4 border-t border-gray-100">
                                        <a
                                            href="?action=pdf&turma_id=<?= (int)$turma['turma_id'] ?>&disciplina_id=<?= (int)$turma['disciplina_id'] ?>&modo=ver"
                                            target="_blank"
                                            class="flex-1 flex items-center justify-center gap-2 text-blue-600 hover:text-blue-700 font-medium text-sm py-2.5 px-4 border-2 border-blue-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-all duration-200"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Ver PDF
                                        </a>
                                        
                                        <a
                                            href="?action=pdf&turma_id=<?= (int)$turma['turma_id'] ?>&disciplina_id=<?= (int)$turma['disciplina_id'] ?>&modo=baixar"
                                            class="flex-1 flex items-center justify-center gap-2 text-green-600 hover:text-green-700 font-medium text-sm py-2.5 px-4 border-2 border-green-200 rounded-lg hover:bg-green-50 hover:border-green-300 transition-all duration-200"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            Baixar
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmar Saída</h3>
                    <p class="text-sm text-gray-600">Tem certeza que deseja sair do sistema?</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <button onclick="window.closeLogoutModal()" class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors duration-200">
                    Cancelar
                </button>
                <button onclick="window.logout()" class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition-colors duration-200">
                    Sim, Sair
                </button>
            </div>
        </div>
    </div>
    
    <script>
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };
        
        window.confirmLogout = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }
        };
        
        window.closeLogoutModal = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        };
        
        window.logout = function() {
            window.location.href = '../auth/logout.php';
        };
    </script>
</body>
</html>

