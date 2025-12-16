 <?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../Models/academico/ProgramaModel.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar permissão usando o sistema de permissões
if (!temPermissao('gerenciar_escolas') && !eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../../config/Database.php');

if (isset($_POST['btngestor'])) {
    $escolaprofessor = $_POST['escola_professor'];
} else {
    # code...
}
//Lotar gestor no banco de dados

if (isset($_POST['btn-adicionar-gestor'])) {
    $tipo_gestor = $_POST['tipo_gestor'];
    $gestorid = $_POST['gestor_id'];
    $escolaid = $_POST['escola_id'];
    lotarGestor($gestorid, $escolaid,$tipo_gestor);
    
} else {
    $gestorid = null;
    $escolaid = null;
}

//funções para lotar gestor no banco de dados
function lotarGestor($gestorid, $escolaid,$tipo_gestor) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "INSERT INTO gestor_lotacao (`id`, `gestor_id`, `escola_id`, `inicio`, `fim`, `responsavel`, `tipo`) VALUES (NULL,:gestorid, :escolaid, CURRENT_TIMESTAMP, NULL, 1,:tipo)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':gestorid', $gestorid);
    $stmt->bindParam(':escolaid', $escolaid);
    $stmt->bindParam(':tipo', $tipo_gestor);
    $stmt->execute();
}

//função dados_gestor_lotacao
function dados_gestor_lotacao($gestorid, $escolaid) {
$db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT 
    p.nome,
    p.email,
    g.cargo as funcao
FROM gestor_lotacao gl
INNER JOIN gestor g ON gl.gestor_id = g.id
INNER JOIN pessoa p ON g.pessoa_id = p.id
WHERE gl.fim IS NULL;  -- Para pegar apenas lotações ativas";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':gestorid', $gestorid);
    $stmt->bindParam(':escolaid', $escolaid);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Funções para gerenciamento de escolas
function listarEscolas($busca = '')
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Usar subquery para buscar apenas um gestor responsável por escola (evitar duplicatas)
    $sql = "SELECT 
                e.id, 
                e.nome, 
                e.endereco, 
                e.telefone, 
                e.email, 
                e.municipio, 
                e.cep, 
                e.qtd_salas, 
                e.obs, 
                e.codigo, 
                e.criado_em as data_criacao,
                (SELECT p.nome 
                 FROM gestor_lotacao gl2
                 INNER JOIN gestor g2 ON gl2.gestor_id = g2.id AND g2.ativo = 1
                 INNER JOIN pessoa p ON g2.pessoa_id = p.id
                 WHERE gl2.escola_id = e.id 
                 AND gl2.responsavel = 1 
                 AND (gl2.fim IS NULL OR gl2.fim = '' OR gl2.fim = '0000-00-00')
                 ORDER BY gl2.inicio DESC
                 LIMIT 1) as gestor_nome,
                (SELECT p.email 
                 FROM gestor_lotacao gl3
                 INNER JOIN gestor g3 ON gl3.gestor_id = g3.id AND g3.ativo = 1
                 INNER JOIN pessoa p ON g3.pessoa_id = p.id
                 WHERE gl3.escola_id = e.id 
                 AND gl3.responsavel = 1 
                 AND (gl3.fim IS NULL OR gl3.fim = '' OR gl3.fim = '0000-00-00')
                 ORDER BY gl3.inicio DESC
                 LIMIT 1) as gestor_email
            FROM escola e
            WHERE e.ativo = 1";

    if (!empty($busca)) {
        $sql .= " AND (e.nome LIKE :busca OR e.endereco LIKE :busca OR e.email LIKE :busca OR e.municipio LIKE :busca 
                      OR EXISTS (
                          SELECT 1 
                          FROM gestor_lotacao gl4
                          INNER JOIN gestor g4 ON gl4.gestor_id = g4.id AND g4.ativo = 1
                          INNER JOIN pessoa p4 ON g4.pessoa_id = p4.id
                          WHERE gl4.escola_id = e.id 
                          AND gl4.responsavel = 1 
                          AND (gl4.fim IS NULL OR gl4.fim = '' OR gl4.fim = '0000-00-00')
                          AND p4.nome LIKE :busca
                      ))";
    }

    $sql .= " ORDER BY e.nome ASC";

    $stmt = $conn->prepare($sql);

    if (!empty($busca)) {
        $buscaParam = "%{$busca}%";
        $stmt->bindParam(':busca', $buscaParam);
    }

    $stmt->execute();
    $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Garantir que valores vazios sejam tratados como null
    foreach ($escolas as &$escola) {
        if (empty($escola['gestor_nome'])) {
            $escola['gestor_nome'] = null;
        }
        if (empty($escola['gestor_email'])) {
            $escola['gestor_email'] = null;
        }
    }
    
    return $escolas;
}

function buscarGestores($busca = '')
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT u.id, p.nome, p.email, p.telefone, u.role
            FROM usuario u 
            JOIN pessoa p ON u.pessoa_id = p.id 
            WHERE u.role = 'GESTAO' AND u.ativo = 1";

    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE :busca OR p.email LIKE :busca)";
    }

    $sql .= " ORDER BY p.nome ASC LIMIT 10";

    $stmt = $conn->prepare($sql);

    if (!empty($busca)) {
        $busca = "%{$busca}%";
        $stmt->bindParam(':busca', $busca);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarGestoresNovo(): array
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT 
            g.id AS gestor_id,
            p.nome AS nome_gestor,
            p.telefone AS telefone_gestor,
            p.email AS email_gestor,
            p.cpf,
            g.cargo,
            g.ativo
        FROM 
            gestor g
        INNER JOIN 
            pessoa p ON g.pessoa_id = p.id
        WHERE 
            g.ativo = 1
        ORDER BY 
            p.nome";
    $stmt = $conn->prepare($sql);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarProfessoresEscola($escolaId)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT p.id, p.nome, p.email, p.telefone, p.cpf, p.cargo, p.disciplina, p.criado_em
            FROM pessoa p
            JOIN professor_lotacao pl ON p.id = pl.pessoa_id
            WHERE pl.escola_id = :escola_id
            ORDER BY p.nome ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':escola_id', $escolaId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function removerProfessorEscola($escolaId, $professorId)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Remover lotação do professor na escola
        $stmt = $conn->prepare("DELETE FROM professor_lotacao WHERE escola_id = :escola_id AND pessoa_id = :professor_id");
        $stmt->bindParam(':escola_id', $escolaId);
        $stmt->bindParam(':professor_id', $professorId);
        $stmt->execute();

        $conn->commit();
        return ['status' => true, 'mensagem' => 'Professor removido da escola com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao remover professor: ' . $e->getMessage()];
    }
}

function buscarEscolaPorId($id)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT * FROM escola WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



function cadastrarEscola($dados)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Montar endereço completo
        $logradouro = $dados['logradouro'] ?? '';
        $numero = $dados['numero'] ?? '';
        $endereco = trim($logradouro . (!empty($numero) ? ', ' . $numero : ''));
        if (!empty($dados['complemento'])) {
            $endereco .= ', ' . $dados['complemento'];
        }
        if (!empty($dados['bairro'])) {
            $endereco .= ', ' . $dados['bairro'];
        }

        // Montar observações com dados do gestor (se houver gestor_id, buscar dados do gestor)
        $obs = '';
        if (!empty($dados['gestor_id'])) {
            // Buscar dados do gestor no banco
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT g.id, p.nome, p.cpf, p.email, g.cargo 
                                    FROM gestor g 
                                    INNER JOIN pessoa p ON g.pessoa_id = p.id 
                                    WHERE g.id = :gestor_id");
            $stmt->bindParam(':gestor_id', $dados['gestor_id']);
            $stmt->execute();
            $gestor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($gestor) {
                $obs .= "Gestor: " . $gestor['nome'];
                if (!empty($gestor['cpf'])) {
                    $obs .= " | CPF: " . $gestor['cpf'];
                }
                if (!empty($gestor['cargo'])) {
                    $obs .= " | Cargo: " . $gestor['cargo'];
                }
                if (!empty($gestor['email'])) {
                    $obs .= " | Email: " . $gestor['email'];
                }
            }
        }
        if (!empty($dados['inep'])) {
            $obs .= " | INEP Escola: " . $dados['inep'];
        }
        if (!empty($dados['tipo_escola'])) {
            $obs .= " | Tipo: " . $dados['tipo_escola'];
        }
        if (!empty($dados['cnpj'])) {
            $obs .= " | CNPJ: " . $dados['cnpj'];
        }

        // Gerar código único se não fornecido
        $codigo = !empty($dados['codigo']) ? $dados['codigo'] : 'ESC' . date('YmdHis');

        // Inserir escola
        $cnpj = !empty($dados['cnpj']) ? $dados['cnpj'] : null;
        
        // Processar múltiplos níveis de ensino (array de checkboxes)
        $niveisEnsino = [];
        if (!empty($dados['nivel_ensino']) && is_array($dados['nivel_ensino'])) {
            $niveisEnsino = $dados['nivel_ensino'];
        } elseif (!empty($dados['nivel_ensino'])) {
            // Se vier como string única, converter para array
            $niveisEnsino = [$dados['nivel_ensino']];
        }
        
        // Se nenhum nível foi selecionado, usar padrão
        if (empty($niveisEnsino)) {
            $niveisEnsino = ['ENSINO_FUNDAMENTAL'];
        }
        
        // Converter array para string separada por vírgula (formato SET do MySQL)
        $nivelEnsino = implode(',', $niveisEnsino);
        
        // Verificar se as colunas existem
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'nivel_ensino'");
        $colunaNivelExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'distrito'");
        $colunaDistritoExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'localidade_escola'");
        $colunaLocalidadeEscolaExiste = $stmtCheck->rowCount() > 0;
        
        // Função para normalizar localidade (remover acentos, exceto apóstrofos)
        function normalizarLocalidadeCadastro($localidade) {
            if (empty($localidade)) return '';
            // Remover acentos, mas manter apóstrofos
            $localidade = str_replace(
                ['á', 'à', 'ã', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 
                 'ó', 'ò', 'õ', 'ô', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ',
                 'Á', 'À', 'Ã', 'Â', 'Ä', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï',
                 'Ó', 'Ò', 'Õ', 'Ô', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç', 'Ñ'],
                ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i',
                 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n',
                 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I',
                 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C', 'N'],
                $localidade
            );
            // Normalizar apóstrofos para '
            $localidade = str_replace(['´', '`'], "'", $localidade);
            return strtolower(trim($localidade));
        }
        
        // Processar localidade: criar automaticamente se não existir
        $localidadeNomeFinal = null;
        if (!empty($dados['distrito']) && !empty($dados['localidade'])) {
            $localidadeInput = trim($dados['localidade']);
            // Primeira letra maiúscula
            $localidadeInput = ucfirst($localidadeInput);
            $localidadeNormalizada = normalizarLocalidadeCadastro($localidadeInput);
            
            // Buscar todas as localidades do distrito para comparar
            $stmtBuscarTodas = $conn->prepare("SELECT id, localidade FROM distrito_localidade WHERE distrito = :distrito AND ativo = 1");
            $stmtBuscarTodas->bindParam(':distrito', $dados['distrito']);
            $stmtBuscarTodas->execute();
            $todasLocalidades = $stmtBuscarTodas->fetchAll(PDO::FETCH_ASSOC);
            
            $localidadeExistente = null;
            foreach ($todasLocalidades as $loc) {
                if (normalizarLocalidadeCadastro($loc['localidade']) === $localidadeNormalizada) {
                    $localidadeExistente = $loc;
                    break;
                }
            }
            
            if ($localidadeExistente) {
                // Usar a localidade existente (com a grafia correta do banco)
                $localidadeNomeFinal = $localidadeExistente['localidade'];
            } else {
                // Criar localidade automaticamente com a grafia fornecida
                $usuarioId = $_SESSION['usuario_id'] ?? null;
                $criadoPor = null;
                if (!empty($usuarioId)) {
                    $stmtCheck = $conn->prepare("SELECT id FROM usuario WHERE id = :id");
                    $stmtCheck->bindParam(':id', $usuarioId, PDO::PARAM_INT);
                    $stmtCheck->execute();
                    if ($stmtCheck->fetch()) {
                        $criadoPor = $usuarioId;
                    }
                }
                
                $stmtCriarLocalidade = $conn->prepare("INSERT INTO distrito_localidade (distrito, localidade, cidade, estado, criado_por, ativo) 
                                                       VALUES (:distrito, :localidade, 'Maranguape', 'CE', :criado_por, 1)");
                $stmtCriarLocalidade->bindParam(':distrito', $dados['distrito']);
                $stmtCriarLocalidade->bindParam(':localidade', $localidadeInput);
                $stmtCriarLocalidade->bindValue(':criado_por', $criadoPor, PDO::PARAM_INT);
                $stmtCriarLocalidade->execute();
                $localidadeNomeFinal = $localidadeInput;
            }
        }
        
        // Verificar se as colunas de endereço separadas existem
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'numero'");
        $colunaNumeroExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'complemento'");
        $colunaComplementoExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'bairro'");
        $colunaBairroExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'telefone_secundario'");
        $colunaTelefoneSecundarioExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'site'");
        $colunaSiteExiste = $stmtCheck->rowCount() > 0;
        
        // Montar query dinamicamente baseado nas colunas existentes
        $campos = ['nome', 'endereco', 'telefone', 'email', 'municipio', 'cep', 'qtd_salas'];
        $valores = [':nome', ':endereco', ':telefone', ':email', ':municipio', ':cep', ':qtd_salas'];
        
        // Adicionar campos de endereço separados se existirem
        if ($colunaNumeroExiste) {
            $campos[] = 'numero';
            $valores[] = ':numero';
        }
        if ($colunaComplementoExiste) {
            $campos[] = 'complemento';
            $valores[] = ':complemento';
        }
        if ($colunaBairroExiste) {
            $campos[] = 'bairro';
            $valores[] = ':bairro';
        }
        
        // Adicionar telefone secundário se existir
        if ($colunaTelefoneSecundarioExiste) {
            $campos[] = 'telefone_secundario';
            $valores[] = ':telefone_secundario';
        }
        
        // Adicionar site se existir
        if ($colunaSiteExiste) {
            $campos[] = 'site';
            $valores[] = ':site';
        }
        
        if ($colunaNivelExiste) {
            $campos[] = 'nivel_ensino';
            $valores[] = ':nivel_ensino';
        }
        
        if ($colunaDistritoExiste) {
            $campos[] = 'distrito';
            $valores[] = ':distrito';
        }
        
        if ($colunaLocalidadeEscolaExiste) {
            $campos[] = 'localidade_escola';
            $valores[] = ':localidade_escola';
        }
        
        // Usar a localidade final (existente ou criada)
        if ($colunaLocalidadeEscolaExiste && $localidadeNomeFinal !== null) {
            $dados['localidade'] = $localidadeNomeFinal;
        }
        
        $campos[] = 'obs';
        $valores[] = ':obs';
        $campos[] = 'codigo';
        $valores[] = ':codigo';
        $campos[] = 'cnpj';
        $valores[] = ':cnpj';
        
        $sql = "INSERT INTO escola (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $valores) . ")";
        $stmt = $conn->prepare($sql);

        $telefone = $dados['telefone_fixo'] ?? '';
        $telefoneSecundario = $dados['telefone_movel'] ?? '';
        $municipio = $dados['municipio'] ?? 'MARANGUAPE';
        $qtdSalas = $dados['qtd_salas'] ?? 0;
        $site = $dados['site'] ?? '';
        
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':municipio', $municipio);
        $stmt->bindParam(':cep', $dados['cep']);
        $stmt->bindParam(':qtd_salas', $qtdSalas, PDO::PARAM_INT);
        
        // Bind dos campos de endereço separados
        if ($colunaNumeroExiste) {
            $numero = !empty($dados['numero']) ? $dados['numero'] : null;
            $stmt->bindParam(':numero', $numero);
        }
        if ($colunaComplementoExiste) {
            $complemento = !empty($dados['complemento']) ? $dados['complemento'] : null;
            $stmt->bindParam(':complemento', $complemento);
        }
        if ($colunaBairroExiste) {
            $bairro = !empty($dados['bairro']) ? $dados['bairro'] : null;
            $stmt->bindParam(':bairro', $bairro);
        }
        
        // Bind do telefone secundário
        if ($colunaTelefoneSecundarioExiste) {
            $telefoneSec = !empty($telefoneSecundario) ? $telefoneSecundario : null;
            $stmt->bindParam(':telefone_secundario', $telefoneSec);
        }
        
        // Bind do site
        if ($colunaSiteExiste) {
            $siteValue = !empty($site) ? $site : null;
            $stmt->bindParam(':site', $siteValue);
        }
        
        if ($colunaNivelExiste) {
            $stmt->bindParam(':nivel_ensino', $nivelEnsino);
        }
        if ($colunaDistritoExiste) {
            $distrito = !empty($dados['distrito']) ? $dados['distrito'] : null;
            $stmt->bindParam(':distrito', $distrito);
        }
        if ($colunaLocalidadeEscolaExiste) {
            $localidadeEscola = $localidadeNomeFinal ?? null;
            $stmt->bindParam(':localidade_escola', $localidadeEscola);
        }
        $stmt->bindParam(':obs', $obs);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':cnpj', $cnpj);

        $stmt->execute();
        $escolaId = $conn->lastInsertId();

        // Se um gestor foi selecionado, criar a lotação
        if (!empty($dados['gestor_id'])) {
            // O gestor_id já vem como ID do gestor diretamente (não precisa converter de usuario_id)
            $gestorId = (int)$dados['gestor_id'];
            
            // Verificar se o gestor existe e está ativo
            $stmt = $conn->prepare("SELECT id FROM gestor WHERE id = :gestor_id AND ativo = 1 LIMIT 1");
            $stmt->bindParam(':gestor_id', $gestorId, PDO::PARAM_INT);
            $stmt->execute();
            $gestorExiste = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$gestorExiste) {
                throw new PDOException('Gestor selecionado não possui cadastro válido em gestor.');
            }

            // Criar a lotação do gestor na escola
            $stmt = $conn->prepare("INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel) 
                                    VALUES (:gestor_id, :escola_id, CURDATE(), 1)");
            $stmt->bindParam(':gestor_id', $gestorId, PDO::PARAM_INT);
            $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmt->execute();
        }

        // Se programas foram selecionados, criar as relações na tabela escola_programa
        if (!empty($dados['programas']) && is_array($dados['programas'])) {
            // Verificar se a tabela escola_programa existe
            $stmtCheckTable = $conn->query("SHOW TABLES LIKE 'escola_programa'");
            $tabelaExiste = $stmtCheckTable->rowCount() > 0;
            
            if ($tabelaExiste) {
                // Verificar quais colunas existem na tabela
                $stmtCols = $conn->query("SHOW COLUMNS FROM escola_programa");
                $colunas = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
                $temColunaAtivo = in_array('ativo', $colunas);
                
                // Montar o INSERT baseado nas colunas disponíveis
                if ($temColunaAtivo) {
                    $stmt = $conn->prepare("INSERT INTO escola_programa (escola_id, programa_id, ativo) 
                                            VALUES (:escola_id, :programa_id, 1)");
                } else {
                    $stmt = $conn->prepare("INSERT INTO escola_programa (escola_id, programa_id) 
                                            VALUES (:escola_id, :programa_id)");
                }
                
                foreach ($dados['programas'] as $programaId) {
                    $programaId = (int)$programaId;
                    if ($programaId > 0) {
                        // Verificar se o programa existe e está ativo
                        $stmtCheck = $conn->prepare("SELECT id FROM programa WHERE id = :programa_id AND ativo = 1 LIMIT 1");
                        $stmtCheck->bindParam(':programa_id', $programaId, PDO::PARAM_INT);
                        $stmtCheck->execute();
                        $programaExiste = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                        
                        if ($programaExiste) {
                            try {
                                $stmt->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
                                $stmt->bindParam(':programa_id', $programaId, PDO::PARAM_INT);
                                $stmt->execute();
                            } catch (PDOException $e) {
                                // Ignorar erro de duplicata (UNIQUE constraint)
                                if ($e->getCode() != 23000) {
                                    throw $e;
                                }
                            }
                        }
                    }
                }
            }
        }

        $conn->commit();

        // Registrar log de criação de escola
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
        require_once(__DIR__ . '/../../Models/log/SystemLogger.php');
        $logger = SystemLogger::getInstance();
        $logger->logCriarEscola($usuarioLogadoId, $escolaId, $dados['nome']);

        return ['status' => true, 'mensagem' => 'Escola cadastrada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao cadastrar escola: ' . $e->getMessage()];
    }
}

function excluirEscolaForcado($id)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();
        
        // 0. Criar backup COMPLETO antes de excluir
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        
        // Buscar dados completos da escola
        $stmtEscola = $conn->prepare("SELECT * FROM escola WHERE id = :id");
        $stmtEscola->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtEscola->execute();
        $dadosEscola = $stmtEscola->fetch(PDO::FETCH_ASSOC);
        
        if (!$dadosEscola) {
            $conn->rollBack();
            return ['status' => false, 'mensagem' => 'Escola não encontrada.'];
        }
        
        // Buscar TODOS os dados relacionados para backup
        $backupData = [];
        
        // 1. Turmas
        $stmtTurmas = $conn->prepare("SELECT * FROM turma WHERE escola_id = :id");
        $stmtTurmas->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtTurmas->execute();
        $backupData['turmas'] = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Lotações
        $backupData['lotacoes'] = [];
        try {
            $stmtProfLotacao = $conn->prepare("SELECT * FROM professor_lotacao WHERE escola_id = :id");
            $stmtProfLotacao->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtProfLotacao->execute();
            $backupData['lotacoes']['professores'] = $stmtProfLotacao->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar professor_lotacao: " . $e->getMessage());
        }
        
        try {
            $stmtGestorLotacao = $conn->prepare("SELECT * FROM gestor_lotacao WHERE escola_id = :id");
            $stmtGestorLotacao->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtGestorLotacao->execute();
            $backupData['lotacoes']['gestores'] = $stmtGestorLotacao->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
        
        try {
            $stmtNutricionistaLotacao = $conn->prepare("SELECT * FROM nutricionista_lotacao WHERE escola_id = :id");
            $stmtNutricionistaLotacao->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtNutricionistaLotacao->execute();
            $backupData['lotacoes']['nutricionistas'] = $stmtNutricionistaLotacao->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
        
        try {
            $stmtFuncionarioLotacao = $conn->prepare("SELECT * FROM funcionario_lotacao WHERE escola_id = :id");
            $stmtFuncionarioLotacao->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtFuncionarioLotacao->execute();
            $backupData['lotacoes']['funcionarios'] = $stmtFuncionarioLotacao->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
        
        // 3. Alunos relacionados (apenas os que têm escola_id = esta escola)
        try {
            $stmtAlunos = $conn->prepare("SELECT * FROM aluno WHERE escola_id = :id");
            $stmtAlunos->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtAlunos->execute();
            $alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);
            $backupData['alunos'] = $alunos;
            
            // Buscar aluno_responsavel dos alunos desta escola
            if (!empty($alunos)) {
                $alunoIds = array_column($alunos, 'id');
                $alunoPlaceholders = implode(',', array_fill(0, count($alunoIds), '?'));
                try {
                    $stmtAlunoResp = $conn->prepare("SELECT * FROM aluno_responsavel WHERE aluno_id IN ($alunoPlaceholders)");
                    $stmtAlunoResp->execute($alunoIds);
                    $backupData['aluno_responsavel'] = $stmtAlunoResp->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log("Erro ao buscar aluno_responsavel: " . $e->getMessage());
                }
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar alunos: " . $e->getMessage());
        }
        
        // 4. Buscar IDs das turmas para buscar dados relacionados
        $turmaIds = array_column($backupData['turmas'], 'id');
        
        // 5. Dados relacionados às turmas (se houver turmas)
        if (!empty($turmaIds)) {
            $placeholders = implode(',', array_fill(0, count($turmaIds), '?'));
            
            try {
                $stmtAlunoTurma = $conn->prepare("SELECT * FROM aluno_turma WHERE turma_id IN ($placeholders)");
                $stmtAlunoTurma->execute($turmaIds);
                $backupData['aluno_turma'] = $stmtAlunoTurma->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {}
            
            try {
                $stmtNotas = $conn->prepare("SELECT * FROM nota WHERE turma_id IN ($placeholders)");
                $stmtNotas->execute($turmaIds);
                $backupData['notas'] = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {}
            
            try {
                $stmtFrequencia = $conn->prepare("SELECT * FROM frequencia WHERE turma_id IN ($placeholders)");
                $stmtFrequencia->execute($turmaIds);
                $backupData['frequencia'] = $stmtFrequencia->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {}
            
            try {
                $stmtAvaliacao = $conn->prepare("SELECT * FROM avaliacao WHERE turma_id IN ($placeholders)");
                $stmtAvaliacao->execute($turmaIds);
                $backupData['avaliacao'] = $stmtAvaliacao->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {}
            
            try {
                $stmtTurmaProfessor = $conn->prepare("SELECT * FROM turma_professor WHERE turma_id IN ($placeholders)");
                $stmtTurmaProfessor->execute($turmaIds);
                $backupData['turma_professor'] = $stmtTurmaProfessor->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {}
            
            try {
                $stmtObservacao = $conn->prepare("SELECT * FROM observacao_desempenho WHERE turma_id IN ($placeholders)");
                $stmtObservacao->execute($turmaIds);
                $backupData['observacao_desempenho'] = $stmtObservacao->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {}
            
            try {
                $stmtPlanoAula = $conn->prepare("SELECT * FROM plano_aula WHERE turma_id IN ($placeholders)");
                $stmtPlanoAula->execute($turmaIds);
                $backupData['plano_aula'] = $stmtPlanoAula->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {}
            
            try {
                $stmtBoletim = $conn->prepare("SELECT * FROM boletim WHERE turma_id IN ($placeholders)");
                $stmtBoletim->execute($turmaIds);
                $boletins = $stmtBoletim->fetchAll(PDO::FETCH_ASSOC);
                $backupData['boletim'] = $boletins;
                
                // Buscar itens de boletim
                if (!empty($boletins)) {
                    $boletimIds = array_column($boletins, 'id');
                    $boletimPlaceholders = implode(',', array_fill(0, count($boletimIds), '?'));
                    $stmtBoletimItem = $conn->prepare("SELECT * FROM boletim_item WHERE boletim_id IN ($boletimPlaceholders)");
                    $stmtBoletimItem->execute($boletimIds);
                    $backupData['boletim_item'] = $stmtBoletimItem->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {}
        }
        
        // 6. Dados relacionados diretamente à escola
        try {
            $stmtEntrega = $conn->prepare("SELECT * FROM entrega WHERE escola_id = :id");
            $stmtEntrega->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtEntrega->execute();
            $entregas = $stmtEntrega->fetchAll(PDO::FETCH_ASSOC);
            $backupData['entrega'] = $entregas;
            
            // Buscar itens de entrega
            if (!empty($entregas)) {
                $entregaIds = array_column($entregas, 'id');
                $entregaPlaceholders = implode(',', array_fill(0, count($entregaIds), '?'));
                $stmtEntregaItem = $conn->prepare("SELECT * FROM entrega_item WHERE entrega_id IN ($entregaPlaceholders)");
                $stmtEntregaItem->execute($entregaIds);
                $backupData['entrega_item'] = $stmtEntregaItem->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {}
        
        try {
            $stmtCardapio = $conn->prepare("SELECT * FROM cardapio WHERE escola_id = :id");
            $stmtCardapio->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCardapio->execute();
            $cardapios = $stmtCardapio->fetchAll(PDO::FETCH_ASSOC);
            $backupData['cardapio'] = $cardapios;
            
            // Buscar itens de cardápio
            if (!empty($cardapios)) {
                $cardapioIds = array_column($cardapios, 'id');
                $cardapioPlaceholders = implode(',', array_fill(0, count($cardapioIds), '?'));
                $stmtCardapioItem = $conn->prepare("SELECT * FROM cardapio_item WHERE cardapio_id IN ($cardapioPlaceholders)");
                $stmtCardapioItem->execute($cardapioIds);
                $backupData['cardapio_item'] = $stmtCardapioItem->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {}
        
        try {
            $stmtConsumo = $conn->prepare("SELECT * FROM consumo_diario WHERE escola_id = :id");
            $stmtConsumo->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtConsumo->execute();
            $consumos = $stmtConsumo->fetchAll(PDO::FETCH_ASSOC);
            $backupData['consumo_diario'] = $consumos;
            
            // Buscar itens de consumo
            if (!empty($consumos)) {
                $consumoIds = array_column($consumos, 'id');
                $consumoPlaceholders = implode(',', array_fill(0, count($consumoIds), '?'));
                $stmtConsumoItem = $conn->prepare("SELECT * FROM consumo_item WHERE consumo_diario_id IN ($consumoPlaceholders)");
                $stmtConsumoItem->execute($consumoIds);
                $backupData['consumo_item'] = $stmtConsumoItem->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {}
        
        // 7. Desperdício
        try {
            $stmtDesperdicio = $conn->prepare("SELECT * FROM desperdicio WHERE escola_id = :id");
            $stmtDesperdicio->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtDesperdicio->execute();
            $backupData['desperdicio'] = $stmtDesperdicio->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar desperdicio: " . $e->getMessage());
        }
        
        // 8. Histórico escolar
        try {
            $stmtHistorico = $conn->prepare("SELECT * FROM historico_escolar WHERE escola_id = :id");
            $stmtHistorico->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtHistorico->execute();
            $backupData['historico_escolar'] = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
        
        // 9. Indicadores nutricionais
        try {
            $stmtIndicador = $conn->prepare("SELECT * FROM indicador_nutricional WHERE escola_id = :id");
            $stmtIndicador->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtIndicador->execute();
            $backupData['indicador_nutricional'] = $stmtIndicador->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
        
        // 10. Pareceres técnicos
        try {
            $stmtParecer = $conn->prepare("SELECT * FROM parecer_tecnico WHERE escola_id = :id");
            $stmtParecer->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtParecer->execute();
            $backupData['parecer_tecnico'] = $stmtParecer->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
        
        // 11. Pedidos de cesta
        try {
            $stmtPedido = $conn->prepare("SELECT * FROM pedido_cesta WHERE escola_id = :id");
            $stmtPedido->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtPedido->execute();
            $pedidos = $stmtPedido->fetchAll(PDO::FETCH_ASSOC);
            $backupData['pedido_cesta'] = $pedidos;
            
            // Buscar itens de pedido
            if (!empty($pedidos)) {
                $pedidoIds = array_column($pedidos, 'id');
                $pedidoPlaceholders = implode(',', array_fill(0, count($pedidoIds), '?'));
                $stmtPedidoItem = $conn->prepare("SELECT * FROM pedido_item WHERE pedido_id IN ($pedidoPlaceholders)");
                $stmtPedidoItem->execute($pedidoIds);
                $backupData['pedido_item'] = $stmtPedidoItem->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {}
        
        // 12. Relatórios
        try {
            $stmtRelatorio = $conn->prepare("SELECT * FROM relatorio WHERE escola_id = :id");
            $stmtRelatorio->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtRelatorio->execute();
            $backupData['relatorio'] = $stmtRelatorio->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
        
        // 13. Comunicados da escola
        try {
            $stmtComunicado = $conn->prepare("SELECT * FROM comunicado WHERE escola_id = :id");
            $stmtComunicado->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtComunicado->execute();
            $comunicados = $stmtComunicado->fetchAll(PDO::FETCH_ASSOC);
            $backupData['comunicado'] = $comunicados;
            
            // Buscar respostas de comunicados
            if (!empty($comunicados)) {
                $comunicadoIds = array_column($comunicados, 'id');
                $comunicadoPlaceholders = implode(',', array_fill(0, count($comunicadoIds), '?'));
                $stmtComunicadoResp = $conn->prepare("SELECT * FROM comunicado_resposta WHERE comunicado_id IN ($comunicadoPlaceholders)");
                $stmtComunicadoResp->execute($comunicadoIds);
                $backupData['comunicado_resposta'] = $stmtComunicadoResp->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {}
        
        // 14. Escola programa
        try {
            $stmtEscolaPrograma = $conn->prepare("SELECT * FROM escola_programa WHERE escola_id = :id");
            $stmtEscolaPrograma->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtEscolaPrograma->execute();
            $backupData['escola_programa'] = $stmtEscolaPrograma->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
        
        // 15. Eventos do calendário (calendar_events)
        try {
            $stmtCalendarEvents = $conn->prepare("SELECT * FROM calendar_events WHERE school_id = :id");
            $stmtCalendarEvents->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCalendarEvents->execute();
            $calendarEvents = $stmtCalendarEvents->fetchAll(PDO::FETCH_ASSOC);
            $backupData['calendar_events'] = $calendarEvents;
            
            // Buscar participantes dos eventos
            if (!empty($calendarEvents)) {
                $eventIds = array_column($calendarEvents, 'id');
                $eventPlaceholders = implode(',', array_fill(0, count($eventIds), '?'));
                try {
                    $stmtCalendarParticipants = $conn->prepare("SELECT * FROM calendar_event_participants WHERE event_id IN ($eventPlaceholders)");
                    $stmtCalendarParticipants->execute($eventIds);
                    $backupData['calendar_event_participants'] = $stmtCalendarParticipants->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log("Erro ao buscar calendar_event_participants: " . $e->getMessage());
                }
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar calendar_events: " . $e->getMessage());
        }
        
        // 16. Custo merenda
        try {
            $stmtCustoMerenda = $conn->prepare("SELECT * FROM custo_merenda WHERE escola_id = :id");
            $stmtCustoMerenda->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCustoMerenda->execute();
            $backupData['custo_merenda'] = $stmtCustoMerenda->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar custo_merenda: " . $e->getMessage());
        }
        
        // 17. Pacotes da escola
        try {
            $stmtPacoteEscola = $conn->prepare("SELECT * FROM pacote_escola WHERE escola_id = :id");
            $stmtPacoteEscola->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtPacoteEscola->execute();
            $pacotesEscola = $stmtPacoteEscola->fetchAll(PDO::FETCH_ASSOC);
            $backupData['pacote_escola'] = $pacotesEscola;
            
            // Buscar itens dos pacotes
            if (!empty($pacotesEscola)) {
                $pacoteIds = array_column($pacotesEscola, 'id');
                $pacotePlaceholders = implode(',', array_fill(0, count($pacoteIds), '?'));
                try {
                    $stmtPacoteItem = $conn->prepare("SELECT * FROM pacote_escola_item WHERE pacote_id IN ($pacotePlaceholders)");
                    $stmtPacoteItem->execute($pacoteIds);
                    $backupData['pacote_escola_item'] = $stmtPacoteItem->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    error_log("Erro ao buscar pacote_escola_item: " . $e->getMessage());
                }
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar pacote_escola: " . $e->getMessage());
        }
        
        // Salvar backup COMPLETO
        try {
            $stmtBackup = $conn->prepare("INSERT INTO escola_backup 
                                         (escola_id_original, dados_escola, dados_turmas, dados_lotacoes, excluido_por) 
                                         VALUES (:escola_id, :dados_escola, :dados_turmas, :dados_lotacoes, :excluido_por)");
            $stmtBackup->bindParam(':escola_id', $id, PDO::PARAM_INT);
            $stmtBackup->bindValue(':dados_escola', json_encode($dadosEscola, JSON_UNESCAPED_UNICODE));
            // Salvar TODOS os dados no campo dados_turmas (incluindo dados relacionados)
            $stmtBackup->bindValue(':dados_turmas', json_encode($backupData, JSON_UNESCAPED_UNICODE));
            $stmtBackup->bindValue(':dados_lotacoes', json_encode($backupData['lotacoes'], JSON_UNESCAPED_UNICODE));
            $stmtBackup->bindParam(':excluido_por', $usuarioId, PDO::PARAM_INT);
            $stmtBackup->execute();
        } catch (PDOException $e) {
            error_log("Erro ao salvar backup da escola: " . $e->getMessage());
            throw $e; // Se não conseguir salvar backup, não deve excluir
        }

        // 1. EXCLUIR TODOS OS DADOS RELACIONADOS (HARD DELETE)
        // Ordem de exclusão respeitando foreign keys
        
        // Excluir itens relacionados primeiro
        if (!empty($turmaIds)) {
            $placeholders = implode(',', array_fill(0, count($turmaIds), '?'));
            
            // Excluir itens de boletim
            try {
                if (!empty($backupData['boletim_item'])) {
                    $boletimItemIds = array_column($backupData['boletim_item'], 'id');
                    if (!empty($boletimItemIds)) {
                        $biPlaceholders = implode(',', array_fill(0, count($boletimItemIds), '?'));
                        $stmt = $conn->prepare("DELETE FROM boletim_item WHERE id IN ($biPlaceholders)");
                        $stmt->execute($boletimItemIds);
                    }
                }
            } catch (PDOException $e) {}
            
            // Excluir boletins
            try {
                if (!empty($backupData['boletim'])) {
                    $boletimIds = array_column($backupData['boletim'], 'id');
                    if (!empty($boletimIds)) {
                        $bPlaceholders = implode(',', array_fill(0, count($boletimIds), '?'));
                        $stmt = $conn->prepare("DELETE FROM boletim WHERE id IN ($bPlaceholders)");
                        $stmt->execute($boletimIds);
                    }
                }
            } catch (PDOException $e) {}
            
            // Excluir notas
            try {
                $stmt = $conn->prepare("DELETE FROM nota WHERE turma_id IN ($placeholders)");
                $stmt->execute($turmaIds);
            } catch (PDOException $e) {}
            
            // Excluir frequências
            try {
                $stmt = $conn->prepare("DELETE FROM frequencia WHERE turma_id IN ($placeholders)");
                $stmt->execute($turmaIds);
            } catch (PDOException $e) {}
            
            // Excluir avaliações
            try {
                $stmt = $conn->prepare("DELETE FROM avaliacao WHERE turma_id IN ($placeholders)");
                $stmt->execute($turmaIds);
            } catch (PDOException $e) {}
            
            // Excluir aluno_turma
            try {
                $stmt = $conn->prepare("DELETE FROM aluno_turma WHERE turma_id IN ($placeholders)");
                $stmt->execute($turmaIds);
            } catch (PDOException $e) {}
            
            // Excluir outras tabelas relacionadas a turmas
            try {
                $stmt = $conn->prepare("DELETE FROM observacao_desempenho WHERE turma_id IN ($placeholders)");
                $stmt->execute($turmaIds);
            } catch (PDOException $e) {}
            
            try {
                $stmt = $conn->prepare("DELETE FROM plano_aula WHERE turma_id IN ($placeholders)");
                $stmt->execute($turmaIds);
            } catch (PDOException $e) {}
            
            try {
                $stmt = $conn->prepare("DELETE FROM turma_professor WHERE turma_id IN ($placeholders)");
                $stmt->execute($turmaIds);
            } catch (PDOException $e) {}
            
            try {
                $stmt = $conn->prepare("DELETE FROM comunicado WHERE turma_id IN ($placeholders)");
                $stmt->execute($turmaIds);
            } catch (PDOException $e) {}
        }
        
        // Excluir itens de entrega
        try {
            if (!empty($backupData['entrega_item'])) {
                $entregaItemIds = array_column($backupData['entrega_item'], 'id');
                if (!empty($entregaItemIds)) {
                    $eiPlaceholders = implode(',', array_fill(0, count($entregaItemIds), '?'));
                    $stmt = $conn->prepare("DELETE FROM entrega_item WHERE id IN ($eiPlaceholders)");
                    $stmt->execute($entregaItemIds);
                }
            }
        } catch (PDOException $e) {}
        
        // Excluir entregas
        try {
            $stmt = $conn->prepare("DELETE FROM entrega WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        // Excluir itens de cardápio
        try {
            if (!empty($backupData['cardapio_item'])) {
                $cardapioItemIds = array_column($backupData['cardapio_item'], 'id');
                if (!empty($cardapioItemIds)) {
                    $ciPlaceholders = implode(',', array_fill(0, count($cardapioItemIds), '?'));
                    $stmt = $conn->prepare("DELETE FROM cardapio_item WHERE id IN ($ciPlaceholders)");
                    $stmt->execute($cardapioItemIds);
                }
            }
        } catch (PDOException $e) {}
        
        // Excluir cardápios
        try {
            $stmt = $conn->prepare("DELETE FROM cardapio WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        // Excluir itens de consumo
        try {
            if (!empty($backupData['consumo_item'])) {
                $consumoItemIds = array_column($backupData['consumo_item'], 'id');
                if (!empty($consumoItemIds)) {
                    $cItemPlaceholders = implode(',', array_fill(0, count($consumoItemIds), '?'));
                    $stmt = $conn->prepare("DELETE FROM consumo_item WHERE id IN ($cItemPlaceholders)");
                    $stmt->execute($consumoItemIds);
                }
            }
        } catch (PDOException $e) {}
        
        // Excluir consumo diário
        try {
            $stmt = $conn->prepare("DELETE FROM consumo_diario WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        // Excluir outras tabelas relacionadas à escola
        try {
            $stmt = $conn->prepare("DELETE FROM desperdicio WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        try {
            $stmt = $conn->prepare("DELETE FROM historico_escolar WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        try {
            $stmt = $conn->prepare("DELETE FROM indicador_nutricional WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        try {
            $stmt = $conn->prepare("DELETE FROM parecer_tecnico WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        // Excluir itens de pedido antes de excluir pedidos
        try {
            if (!empty($backupData['pedido_item'])) {
                $pedidoItemIds = array_column($backupData['pedido_item'], 'id');
                if (!empty($pedidoItemIds)) {
                    $piPlaceholders = implode(',', array_fill(0, count($pedidoItemIds), '?'));
                    $stmt = $conn->prepare("DELETE FROM pedido_item WHERE id IN ($piPlaceholders)");
                    $stmt->execute($pedidoItemIds);
                }
            }
        } catch (PDOException $e) {}
        
        try {
            $stmt = $conn->prepare("DELETE FROM pedido_cesta WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        try {
            $stmt = $conn->prepare("DELETE FROM relatorio WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        // Excluir turmas
        try {
            $stmt = $conn->prepare("DELETE FROM turma WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao excluir turmas: " . $e->getMessage());
            throw $e;
        }
        
        // Excluir alunos da escola (já foram salvos no backup)
        // IMPORTANTE: Os alunos são excluídos porque foram salvos no backup
        // Quando reverter, serão restaurados do backup
        try {
            if (!empty($backupData['alunos'])) {
                $alunoIds = array_column($backupData['alunos'], 'id');
                if (!empty($alunoIds)) {
                    $alunoPlaceholders = implode(',', array_fill(0, count($alunoIds), '?'));
                    
                    // Excluir aluno_responsavel primeiro (foreign key)
                    try {
                        $stmt = $conn->prepare("DELETE FROM aluno_responsavel WHERE aluno_id IN ($alunoPlaceholders)");
                        $stmt->execute($alunoIds);
                    } catch (PDOException $e) {}
                    
                    // Excluir alunos
                    $stmt = $conn->prepare("DELETE FROM aluno WHERE id IN ($alunoPlaceholders)");
                    $stmt->execute($alunoIds);
                }
            }
        } catch (PDOException $e) {
            error_log("Erro ao excluir alunos: " . $e->getMessage());
        }
        
        // Excluir lotações
        try {
            $stmt = $conn->prepare("DELETE FROM professor_lotacao WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        try {
            $stmt = $conn->prepare("DELETE FROM gestor_lotacao WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        try {
            $stmt = $conn->prepare("DELETE FROM nutricionista_lotacao WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        try {
            $stmt = $conn->prepare("DELETE FROM funcionario_lotacao WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        try {
            $stmt = $conn->prepare("DELETE FROM escola_programa WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        // Excluir respostas de comunicados ANTES de excluir comunicados (respeitando foreign key)
        try {
            if (!empty($backupData['comunicado_resposta'])) {
                $comunicadoRespIds = array_column($backupData['comunicado_resposta'], 'id');
                if (!empty($comunicadoRespIds)) {
                    $crPlaceholders = implode(',', array_fill(0, count($comunicadoRespIds), '?'));
                    $stmt = $conn->prepare("DELETE FROM comunicado_resposta WHERE id IN ($crPlaceholders)");
                    $stmt->execute($comunicadoRespIds);
                }
            }
        } catch (PDOException $e) {}
        
        // Excluir comunicados gerais da escola
        try {
            $stmt = $conn->prepare("DELETE FROM comunicado WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {}
        
        // Excluir participantes de eventos do calendário antes de excluir eventos
        try {
            if (!empty($backupData['calendar_event_participants'])) {
                $participantIds = array_column($backupData['calendar_event_participants'], 'id');
                if (!empty($participantIds)) {
                    $participantPlaceholders = implode(',', array_fill(0, count($participantIds), '?'));
                    $stmt = $conn->prepare("DELETE FROM calendar_event_participants WHERE id IN ($participantPlaceholders)");
                    $stmt->execute($participantIds);
                }
            }
        } catch (PDOException $e) {
            error_log("Erro ao excluir calendar_event_participants: " . $e->getMessage());
        }
        
        // Excluir eventos do calendário
        try {
            $stmt = $conn->prepare("DELETE FROM calendar_events WHERE school_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao excluir calendar_events: " . $e->getMessage());
        }
        
        // Excluir custo merenda
        try {
            $stmt = $conn->prepare("DELETE FROM custo_merenda WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao excluir custo_merenda: " . $e->getMessage());
        }
        
        // Excluir itens de pacote antes de excluir pacotes
        try {
            if (!empty($backupData['pacote_escola_item'])) {
                $pacoteItemIds = array_column($backupData['pacote_escola_item'], 'id');
                if (!empty($pacoteItemIds)) {
                    $pacoteItemPlaceholders = implode(',', array_fill(0, count($pacoteItemIds), '?'));
                    $stmt = $conn->prepare("DELETE FROM pacote_escola_item WHERE id IN ($pacoteItemPlaceholders)");
                    $stmt->execute($pacoteItemIds);
                }
            }
        } catch (PDOException $e) {
            error_log("Erro ao excluir pacote_escola_item: " . $e->getMessage());
        }
        
        // Excluir pacotes da escola
        try {
            $stmt = $conn->prepare("DELETE FROM pacote_escola WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao excluir pacote_escola: " . $e->getMessage());
        }
        
        // Por último, excluir a escola
        try {
            $stmt = $conn->prepare("DELETE FROM escola WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao excluir escola: " . $e->getMessage());
            throw $e;
        }
        
        $conn->commit();

        return ['status' => true, 'mensagem' => 'Escola excluída com sucesso! Todos os dados foram movidos para backup e removidos das tabelas principais. A escola pode ser restaurada através da reversão.'];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Erro ao excluir escola: " . $e->getMessage());
        return ['status' => false, 'mensagem' => 'Erro ao excluir escola: ' . $e->getMessage()];
    }
}

function excluirEscola($id)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Verificar e listar todas as relações que impedem a exclusão
        $relacoes = [];
        
        // Verificar entregas
        $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM entrega WHERE escola_id = :id");
        $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtCheck->execute();
        $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['total'] > 0) {
            $relacoes[] = $result['total'] . ' entrega(s) de merenda';
        }
        
        // Verificar turmas
        try {
            $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM turma WHERE escola_id = :id");
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['total'] > 0) {
                $relacoes[] = $result['total'] . ' turma(s)';
            }
        } catch (PDOException $e) {
            // Tabela pode não existir, ignorar
        }
        
        // Verificar alunos (mesmo que seja SET NULL, vamos informar)
        try {
            $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM aluno WHERE escola_id = :id");
            $stmtCheck->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($result && $result['total'] > 0) {
                $relacoes[] = $result['total'] . ' aluno(s)';
            }
        } catch (PDOException $e) {
            // Tabela pode não existir, ignorar
        }
        
        // Se houver relações, retornar mensagem detalhada
        if (!empty($relacoes)) {
            $conn->rollBack();
            $mensagem = 'Não é possível excluir esta escola pois ela possui: ' . implode(', ', $relacoes) . '.';
            $mensagem .= ' Por favor, remova ou transfira esses registros antes de excluir a escola.';
            return ['status' => false, 'mensagem' => $mensagem];
        }

        // Remover entregas e seus itens primeiro
        try {
            // Excluir itens das entregas
            $stmt = $conn->prepare("DELETE ei FROM entrega_item ei 
                                    INNER JOIN entrega e ON ei.entrega_id = e.id 
                                    WHERE e.escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Excluir entregas
            $stmt = $conn->prepare("DELETE FROM entrega WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            // Se der erro, pode ser que não existam entregas, continuar
        }

        // Remover lotações de professores
        $stmt = $conn->prepare("DELETE FROM professor_lotacao WHERE escola_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Remover lotações de gestores
        $stmt = $conn->prepare("DELETE FROM gestor_lotacao WHERE escola_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Remover relações com programas (se a tabela existir)
        try {
            $stmt = $conn->prepare("DELETE FROM escola_programa WHERE escola_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            // Tabela pode não existir, ignorar
        }

        // Buscar nome da escola antes de excluir para o log
        $stmtNome = $conn->prepare("SELECT nome FROM escola WHERE id = :id");
        $stmtNome->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtNome->execute();
        $escola = $stmtNome->fetch(PDO::FETCH_ASSOC);
        $nomeEscola = $escola['nome'] ?? null;

        // Excluir a escola
        $stmt = $conn->prepare("DELETE FROM escola WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $conn->commit();

        // Registrar log de exclusão de escola
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
        require_once(__DIR__ . '/../../Models/log/SystemLogger.php');
        $logger = SystemLogger::getInstance();
        $logger->logExcluirEscola($usuarioLogadoId, $id, $nomeEscola);

        return ['status' => true, 'mensagem' => 'Escola excluída com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Erro ao excluir escola: " . $e->getMessage());
        
        // Mensagem mais amigável para constraint violation
        if ($e->getCode() == 23000) {
            $mensagemErro = $e->getMessage();
            
            if (strpos($mensagemErro, 'entrega') !== false) {
                return ['status' => false, 'mensagem' => 'Não é possível excluir esta escola pois ela possui entregas de merenda associadas. Por favor, exclua as entregas primeiro.'];
            } elseif (strpos($mensagemErro, 'turma') !== false) {
                return ['status' => false, 'mensagem' => 'Não é possível excluir esta escola pois ela possui turmas associadas. Por favor, remova ou transfira as turmas primeiro.'];
            } elseif (strpos($mensagemErro, 'aluno') !== false) {
                return ['status' => false, 'mensagem' => 'Não é possível excluir esta escola pois ela possui alunos associados. Por favor, transfira os alunos para outra escola primeiro.'];
            } elseif (strpos($mensagemErro, 'professor_lotacao') !== false) {
                return ['status' => false, 'mensagem' => 'Não é possível excluir esta escola pois ela possui professores lotados. Por favor, remova os professores primeiro.'];
            } elseif (strpos($mensagemErro, 'gestor_lotacao') !== false) {
                return ['status' => false, 'mensagem' => 'Não é possível excluir esta escola pois ela possui gestores lotados. Por favor, remova os gestores primeiro.'];
            } else {
                return ['status' => false, 'mensagem' => 'Não é possível excluir esta escola pois ela possui registros relacionados em outras tabelas. Erro: ' . substr($mensagemErro, 0, 200)];
            }
        }
        
        return ['status' => false, 'mensagem' => 'Erro ao excluir escola: ' . $e->getMessage()];
    }
}

function atualizarEscola($id, $dados)
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        // Atualizar dados da escola
        $cnpj = !empty($dados['cnpj']) ? $dados['cnpj'] : null;
        
        // Processar múltiplos níveis de ensino (array de checkboxes)
        $niveisEnsino = [];
        if (!empty($dados['nivel_ensino']) && is_array($dados['nivel_ensino'])) {
            $niveisEnsino = $dados['nivel_ensino'];
        } elseif (!empty($dados['nivel_ensino'])) {
            // Se vier como string única, converter para array
            $niveisEnsino = [$dados['nivel_ensino']];
        }
        
        // Se nenhum nível foi selecionado, usar padrão
        if (empty($niveisEnsino)) {
            $niveisEnsino = ['ENSINO_FUNDAMENTAL'];
        }
        
        // Converter array para string separada por vírgula (formato SET do MySQL)
        $nivelEnsino = implode(',', $niveisEnsino);
        
        // Verificar se as colunas existem
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'nivel_ensino'");
        $colunaNivelExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'distrito'");
        $colunaDistritoExiste = $stmtCheck->rowCount() > 0;
        
        // Função para normalizar localidade (remover acentos, exceto apóstrofos)
        function normalizarLocalidadeEdicao($localidade) {
            if (empty($localidade)) return '';
            // Remover acentos, mas manter apóstrofos
            $localidade = str_replace(
                ['á', 'à', 'ã', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 
                 'ó', 'ò', 'õ', 'ô', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ',
                 'Á', 'À', 'Ã', 'Â', 'Ä', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï',
                 'Ó', 'Ò', 'Õ', 'Ô', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç', 'Ñ'],
                ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i',
                 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n',
                 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I',
                 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C', 'N'],
                $localidade
            );
            // Normalizar apóstrofos para '
            $localidade = str_replace(['´', '`'], "'", $localidade);
            return strtolower(trim($localidade));
        }
        
        // Processar localidade: criar automaticamente se não existir
        $localidadeNomeFinal = null;
        if (!empty($dados['distrito']) && !empty($dados['localidade'])) {
            $localidadeInput = trim($dados['localidade']);
            // Primeira letra maiúscula
            $localidadeInput = ucfirst($localidadeInput);
            $localidadeNormalizada = normalizarLocalidadeEdicao($localidadeInput);
            
            // Buscar todas as localidades do distrito para comparar
            $stmtBuscarTodas = $conn->prepare("SELECT id, localidade FROM distrito_localidade WHERE distrito = :distrito AND ativo = 1");
            $stmtBuscarTodas->bindParam(':distrito', $dados['distrito']);
            $stmtBuscarTodas->execute();
            $todasLocalidades = $stmtBuscarTodas->fetchAll(PDO::FETCH_ASSOC);
            
            $localidadeExistente = null;
            foreach ($todasLocalidades as $loc) {
                if (normalizarLocalidadeEdicao($loc['localidade']) === $localidadeNormalizada) {
                    $localidadeExistente = $loc;
                    break;
                }
            }
            
            if ($localidadeExistente) {
                // Usar a localidade existente (com a grafia correta do banco)
                $localidadeNomeFinal = $localidadeExistente['localidade'];
            } else {
                // Criar localidade automaticamente com a grafia fornecida
                $usuarioId = $_SESSION['usuario_id'] ?? null;
                $criadoPor = null;
                if (!empty($usuarioId)) {
                    $stmtCheck = $conn->prepare("SELECT id FROM usuario WHERE id = :id");
                    $stmtCheck->bindParam(':id', $usuarioId, PDO::PARAM_INT);
                    $stmtCheck->execute();
                    if ($stmtCheck->fetch()) {
                        $criadoPor = $usuarioId;
                    }
                }
                
                $stmtCriarLocalidade = $conn->prepare("INSERT INTO distrito_localidade (distrito, localidade, cidade, estado, criado_por, ativo) 
                                                       VALUES (:distrito, :localidade, 'Maranguape', 'CE', :criado_por, 1)");
                $stmtCriarLocalidade->bindParam(':distrito', $dados['distrito']);
                $stmtCriarLocalidade->bindParam(':localidade', $localidadeInput);
                $stmtCriarLocalidade->bindValue(':criado_por', $criadoPor, PDO::PARAM_INT);
                $stmtCriarLocalidade->execute();
                $localidadeNomeFinal = $localidadeInput;
            }
        }
        
        // Verificar se as colunas de endereço separadas existem
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'numero'");
        $colunaNumeroExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'complemento'");
        $colunaComplementoExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'bairro'");
        $colunaBairroExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'telefone_secundario'");
        $colunaTelefoneSecundarioExiste = $stmtCheck->rowCount() > 0;
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'site'");
        $colunaSiteExiste = $stmtCheck->rowCount() > 0;
        
        // Montar query dinamicamente
        $campos = ['nome = :nome', 'endereco = :endereco', 'telefone = :telefone', 'email = :email', 
                   'municipio = :municipio', 'cep = :cep', 'qtd_salas = :qtd_salas', 
                   'obs = :obs', 'codigo = :codigo', 'cnpj = :cnpj'];
        
        // Adicionar campos de endereço separados se existirem
        if ($colunaNumeroExiste) {
            $campos[] = 'numero = :numero';
        }
        if ($colunaComplementoExiste) {
            $campos[] = 'complemento = :complemento';
        }
        if ($colunaBairroExiste) {
            $campos[] = 'bairro = :bairro';
        }
        
        // Adicionar telefone secundário se existir
        if ($colunaTelefoneSecundarioExiste) {
            $campos[] = 'telefone_secundario = :telefone_secundario';
        }
        
        // Adicionar site se existir
        if ($colunaSiteExiste) {
            $campos[] = 'site = :site';
        }
        
        if ($colunaNivelExiste) {
            $campos[] = 'nivel_ensino = :nivel_ensino';
        }
        
        $stmtCheck = $conn->query("SHOW COLUMNS FROM escola LIKE 'localidade_escola'");
        $colunaLocalidadeEscolaExiste = $stmtCheck->rowCount() > 0;
        
        if ($colunaDistritoExiste) {
            $campos[] = 'distrito = :distrito';
        }
        
        if ($colunaLocalidadeEscolaExiste) {
            $campos[] = 'localidade_escola = :localidade_escola';
        }
        
        $sql = "UPDATE escola SET " . implode(', ', $campos) . " WHERE id = :id";
        $stmt = $conn->prepare($sql);

        // Montar endereço (logradouro apenas)
        $endereco = $dados['endereco'] ?? '';
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':telefone', $dados['telefone']);
        $stmt->bindParam(':email', $dados['email']);
        $stmt->bindParam(':municipio', $dados['municipio']);
        $stmt->bindParam(':cep', $dados['cep']);
        $stmt->bindParam(':qtd_salas', $dados['qtd_salas']);
        $stmt->bindParam(':obs', $dados['obs']);
        $stmt->bindParam(':codigo', $dados['codigo']);
        $stmt->bindParam(':cnpj', $cnpj);
        
        // Bind dos campos de endereço separados
        if ($colunaNumeroExiste) {
            $numero = !empty($dados['numero']) ? $dados['numero'] : null;
            $stmt->bindParam(':numero', $numero);
        }
        if ($colunaComplementoExiste) {
            $complemento = !empty($dados['complemento']) ? $dados['complemento'] : null;
            $stmt->bindParam(':complemento', $complemento);
        }
        if ($colunaBairroExiste) {
            $bairro = !empty($dados['bairro']) ? $dados['bairro'] : null;
            $stmt->bindParam(':bairro', $bairro);
        }
        
        // Bind do telefone secundário
        if ($colunaTelefoneSecundarioExiste) {
            $telefoneSecundario = !empty($dados['telefone_secundario']) ? $dados['telefone_secundario'] : null;
            $stmt->bindParam(':telefone_secundario', $telefoneSecundario);
        }
        
        // Bind do site
        if ($colunaSiteExiste) {
            $site = !empty($dados['site']) ? $dados['site'] : null;
            $stmt->bindParam(':site', $site);
        }
        
        if ($colunaNivelExiste) {
            $stmt->bindParam(':nivel_ensino', $nivelEnsino);
        }
        if ($colunaDistritoExiste) {
            $distrito = !empty($dados['distrito']) ? $dados['distrito'] : null;
            $stmt->bindParam(':distrito', $distrito);
        }
        
        if ($colunaLocalidadeEscolaExiste) {
            $localidadeEscola = $localidadeNomeFinal ?? null;
            $stmt->bindParam(':localidade_escola', $localidadeEscola);
        }

        $stmt->execute();

        // Gerenciar lotação do gestor
        // Primeiro, remover lotação atual (se houver)
        $stmt = $conn->prepare("DELETE FROM gestor_lotacao WHERE escola_id = :escola_id AND responsavel = 1");
        $stmt->bindParam(':escola_id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Se um novo gestor foi selecionado, criar a lotação
        if (!empty($dados['gestor_id'])) {
            // O gestor_id já vem como ID do gestor diretamente (não precisa converter de usuario_id)
            $gestorId = (int)$dados['gestor_id'];
            
            // Verificar se o gestor existe e está ativo
            $stmt = $conn->prepare("SELECT id FROM gestor WHERE id = :gestor_id AND ativo = 1 LIMIT 1");
            $stmt->bindParam(':gestor_id', $gestorId, PDO::PARAM_INT);
            $stmt->execute();
            $gestorExiste = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$gestorExiste) {
                throw new PDOException('Gestor selecionado não possui cadastro válido em gestor.');
            }

            // Criar a lotação do gestor na escola
            $stmt = $conn->prepare("INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, responsavel) 
                                    VALUES (:gestor_id, :escola_id, CURDATE(), 1)");
            $stmt->bindParam(':gestor_id', $gestorId, PDO::PARAM_INT);
            $stmt->bindParam(':escola_id', $id, PDO::PARAM_INT);
            $stmt->execute();
        }

        $conn->commit();

        // Buscar nome da escola para o log
        $stmtNome = $conn->prepare("SELECT nome FROM escola WHERE id = :id");
        $stmtNome->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtNome->execute();
        $escola = $stmtNome->fetch(PDO::FETCH_ASSOC);
        $nomeEscola = $escola['nome'] ?? null;

        // Registrar log de edição de escola
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
        require_once(__DIR__ . '/../../Models/log/SystemLogger.php');
        $logger = SystemLogger::getInstance();
        $logger->logEditarEscola($usuarioLogadoId, $id, $nomeEscola);

        return ['status' => true, 'mensagem' => 'Escola atualizada com sucesso!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['status' => false, 'mensagem' => 'Erro ao atualizar escola: ' . $e->getMessage()];
    }
}

// Processar formulários
$mensagem = '';
$tipoMensagem = '';

// Processamento AJAX para buscar professores e escola
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    // Endpoint para buscar localidades do distrito
    if ($_GET['acao'] === 'buscar_localidades_distrito' && isset($_GET['distrito'])) {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();
        
        $distrito = $_GET['distrito'] ?? '';
        if (empty($distrito)) {
            echo json_encode(['status' => false, 'mensagem' => 'Distrito não informado']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("SELECT DISTINCT localidade FROM distrito_localidade WHERE distrito = :distrito AND ativo = 1 ORDER BY localidade ASC");
            $stmt->bindParam(':distrito', $distrito);
            $stmt->execute();
            $localidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $localidadesList = array_column($localidades, 'localidade');
            echo json_encode(['status' => true, 'localidades' => $localidadesList]);
        } catch (PDOException $e) {
            echo json_encode(['status' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_professores' && isset($_GET['escola_id'])) {
        $professores = buscarProfessoresEscola($_GET['escola_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'professores' => $professores]);
        exit;
    }
    
    if ($_GET['acao'] === 'buscar_escola' && isset($_GET['id'])) {
        $escola = buscarEscolaPorId($_GET['id']);
        header('Content-Type: application/json');
        if ($escola) {
            echo json_encode(['success' => true, 'escola' => $escola]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Escola não encontrada']);
        }
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        // Cadastrar nova escola
        if ($_POST['acao'] === 'cadastrar') {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'logradouro' => $_POST['logradouro'] ?? '',
                'numero' => $_POST['numero'] ?? '',
                'complemento' => $_POST['complemento'] ?? '',
                'bairro' => $_POST['bairro'] ?? '',
                'telefone_fixo' => $_POST['telefone_fixo'] ?? '',
                'telefone_movel' => $_POST['telefone_movel'] ?? '',
                'email' => $_POST['email'] ?? '',
                'municipio' => $_POST['municipio'] ?? 'MARANGUAPE',
                'cep' => $_POST['cep'] ?? '',
                'qtd_salas' => $_POST['qtd_salas'] ?? null,
                'nivel_ensino' => $_POST['nivel_ensino'] ?? [], // Array de checkboxes
                'obs' => '',
                'codigo' => $_POST['codigo'] ?? '',
                'gestor_id' => $_POST['gestor_id'] ?? null,
                'inep' => $_POST['inep'] ?? '',
                'tipo_escola' => $_POST['tipo_escola'] ?? 'NORMAL',
                'cnpj' => $_POST['cnpj'] ?? '',
                'programas' => $_POST['programas'] ?? [],
                'distrito' => $_POST['distrito'] ?? null,
                'localidade' => $_POST['localidade'] ?? null
            ];

            $resultado = cadastrarEscola($dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }

        // Editar escola
        if ($_POST['acao'] === 'editar' && isset($_POST['id'])) {
            // Montar observações com dados do gestor (preservar dados existentes)
            $obs = $_POST['obs'] ?? '';
            
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'endereco' => $_POST['logradouro'] ?? '', // Apenas logradouro
                'numero' => $_POST['numero'] ?? '',
                'complemento' => $_POST['complemento'] ?? '',
                'bairro' => $_POST['bairro'] ?? '',
                'telefone' => $_POST['telefone_fixo'] ?? '',
                'telefone_secundario' => $_POST['telefone_movel'] ?? '',
                'email' => $_POST['email'] ?? '',
                'site' => $_POST['site'] ?? '',
                'municipio' => $_POST['municipio'] ?? 'MARANGUAPE',
                'cep' => $_POST['cep'] ?? '',
                'qtd_salas' => $_POST['qtd_salas'] ?? null,
                'nivel_ensino' => $_POST['nivel_ensino'] ?? [], // Array de checkboxes
                'obs' => $obs,
                'codigo' => $_POST['codigo'] ?? '',
                'gestor_id' => $_POST['gestor_id'] ?? null,
                'cnpj' => $_POST['cnpj'] ?? '',
                'distrito' => $_POST['distrito'] ?? null,
                'localidade' => $_POST['localidade'] ?? null
            ];

            $resultado = atualizarEscola($_POST['id'], $dados);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }

        // Excluir escola
        if ($_POST['acao'] === 'excluir' && isset($_POST['id'])) {
            // Verificar se é exclusão forçada
            $forcado = isset($_POST['forcado']) && $_POST['forcado'] === 'true';
            if ($forcado) {
                $resultado = excluirEscolaForcado($_POST['id']);
            } else {
                $resultado = excluirEscola($_POST['id']);
            }
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }

        // Remover professor da escola
        if ($_POST['acao'] === 'remover_professor' && isset($_POST['escola_id']) && isset($_POST['professor_id'])) {
            $resultado = removerProfessorEscola($_POST['escola_id'], $_POST['professor_id']);
            $mensagem = $resultado['mensagem'];
            $tipoMensagem = $resultado['status'] ? 'success' : 'error';
        }
    }
}

// Buscar escolas
$busca = $_GET['busca'] ?? '';
$escolas = listarEscolas($busca);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Escolas - SIGEA</title>
    
    <!-- Favicon -->
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#2D5A27',
                        'secondary-green': '#4A7C59',
                        'accent-orange': '#FF6B35',
                        'accent-red': '#D62828',
                        'light-green': '#A8D5BA',
                        'warm-orange': '#FF8C42'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }

        // ===== PREVENÇÃO DE ERROS DE EXTENSÕES =====
        // Capturar e suprimir erros de extensões do navegador
        window.addEventListener('error', function(e) {
            if (e.message && (
                e.message.includes('content-all.js') ||
                e.message.includes('Could not establish connection') ||
                e.message.includes('Receiving end does not exist') ||
                e.message.includes('message channel closed')
            )) {
                e.preventDefault();
                console.warn('Erro de extensão do navegador suprimido:', e.message);
                return false;
            }
        });

        window.addEventListener('unhandledrejection', function(e) {
            if (e.reason && (
                e.reason.message && (
                    e.reason.message.includes('content-all.js') ||
                    e.reason.message.includes('Could not establish connection') ||
                    e.reason.message.includes('Receiving end does not exist') ||
                    e.reason.message.includes('message channel closed')
                )
            )) {
                e.preventDefault();
                console.warn('Promise rejection de extensão suprimida:', e.reason);
                return false;
            }
        });

        // Função toggleSidebar já definida globalmente
        
        // Definir funções de modal no escopo global imediatamente
        // Isso garante que as funções estejam disponíveis quando o HTML for renderizado
        window.abrirModalEdicaoEscola = function(id, nome) {
            function abrirModal() {
                const editEscolaId = document.getElementById('edit_escola_id');
                const tituloModal = document.getElementById('tituloModalEdicao');
                const modalEdicao = document.getElementById('modalEdicaoEscola');
                
                if (editEscolaId && tituloModal && modalEdicao) {
                    editEscolaId.value = id;
                    tituloModal.textContent = `Editar Escola - ${nome}`;
                    
                    // Remover hidden e garantir que está visível
                    modalEdicao.classList.remove('hidden');
                    // Forçar display flex para garantir visibilidade (modal usa flex)
                    modalEdicao.style.display = 'flex';
                    modalEdicao.style.visibility = 'visible';
                    modalEdicao.style.opacity = '1';
                    modalEdicao.style.zIndex = '9999';
                    
                    if (typeof carregarDadosEscola === 'function') {
                        carregarDadosEscola(id);
                    }
                    return true;
                }
                return false;
            }
            
            // Tentar imediatamente
            if (abrirModal()) {
                return;
            }
            
            // Aguardar carregamento completo
            const tentarAposCarregamento = () => {
                let tentativas = 0;
                const maxTentativas = 20;
                const intervalId = setInterval(() => {
                    tentativas++;
                    if (abrirModal() || tentativas >= maxTentativas) {
                        clearInterval(intervalId);
                    }
                }, 50);
            };
            
            if (document.readyState === 'complete') {
                tentarAposCarregamento();
            } else {
                window.addEventListener('load', tentarAposCarregamento, {once: true});
                document.addEventListener('DOMContentLoaded', tentarAposCarregamento, {once: true});
            }
        };
        
        window.abrirModalExclusaoEscola = function(id, nome) {
            function abrirModal() {
                const idEscolaExclusao = document.getElementById('idEscolaExclusao');
                const nomeEscolaExclusao = document.getElementById('nomeEscolaExclusao');
                const modalExclusao = document.getElementById('modalExclusaoEscola');
                const excluirForcado = document.getElementById('excluirForcado');
                const forcadoExclusao = document.getElementById('forcadoExclusao');
                
                if (idEscolaExclusao && nomeEscolaExclusao && modalExclusao) {
                    idEscolaExclusao.value = id;
                    nomeEscolaExclusao.textContent = nome;
                    
                    // Resetar checkbox e campo hidden
                    if (excluirForcado) {
                        excluirForcado.checked = false;
                    }
                    if (forcadoExclusao) {
                        forcadoExclusao.value = 'false';
                    }
                    
                    // Adicionar listener ao checkbox
                    if (excluirForcado && forcadoExclusao) {
                        excluirForcado.onchange = function() {
                            forcadoExclusao.value = this.checked ? 'true' : 'false';
                        };
                    }
                    
                    // Remover hidden e garantir que está visível
                    modalExclusao.classList.remove('hidden');
                    modalExclusao.style.display = 'flex';
                    modalExclusao.style.visibility = 'visible';
                    modalExclusao.style.opacity = '1';
                    modalExclusao.style.zIndex = '9999';
                    
                    return true;
                }
                return false;
            }
            
            // Tentar imediatamente
            if (abrirModal()) {
                return;
            }
            
            // Aguardar carregamento completo
            const tentarAposCarregamento = () => {
                let tentativas = 0;
                const maxTentativas = 20;
                const intervalId = setInterval(() => {
                    tentativas++;
                    if (abrirModal() || tentativas >= maxTentativas) {
                        clearInterval(intervalId);
                    }
                }, 50);
            };
            
            if (document.readyState === 'complete') {
                tentarAposCarregamento();
            } else {
                window.addEventListener('load', tentarAposCarregamento, {once: true});
                document.addEventListener('DOMContentLoaded', tentarAposCarregamento, {once: true});
            }
        };
        
        // Função para mostrar tab - definida no escopo global para garantir disponibilidade
        window.showTab = function(tabId) {
            try {
                // Esconder todas as tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                    tab.classList.add('hidden');
                });
                
                // Remover classe ativa de todos os botões
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('tab-active');
                });
                
                // Mostrar a tab selecionada
                const tabSelecionada = document.getElementById(tabId);
                if (tabSelecionada) {
                    tabSelecionada.classList.remove('hidden');
                    tabSelecionada.classList.add('active');
                }
                
                // Adicionar classe ativa ao botão clicado
                if (event && event.currentTarget) {
                    event.currentTarget.classList.add('tab-active');
                }
                
                // Resetar formulário quando mudar para a aba de adicionar gestor
                if (tabId === 'tab-adicionar-gestor') {
                    setTimeout(() => {
                        try {
                            const elementosGestor = [
                                'escola_gestor',
                                'buscar_escola_gestor',
                                'info-escola-gestor', 
                                'passo-selecionar-gestor'
                            ];
                            
                            elementosGestor.forEach(id => {
                                const elemento = document.getElementById(id);
                                if (elemento) {
                                    if (id === 'escola_gestor' || id === 'buscar_escola_gestor') {
                                        elemento.value = '';
                                        if (id === 'escola_gestor') {
                                            elemento.size = 1;
                                        }
                                    } else {
                                        elemento.classList.add('hidden');
                                    }
                                }
                            });
                            
                            if (document.getElementById('buscar_gestor') && typeof limparSelecaoGestor === 'function') {
                                limparSelecaoGestor();
                            }
                        } catch (e) {
                            console.log('Elementos de gestor ainda não carregados');
                        }
                    }, 100);
                }
                
                // Resetar formulário quando mudar para a aba de lotação
                if (tabId === 'tab-lotacao') {
                    setTimeout(() => {
                        try {
                            const elementosLotacao = [
                                'escola_lotacao',
                                'buscar_escola_lotacao',
                                'info-escola-lotacao',
                                'secao-lotacao'
                            ];
                            
                            elementosLotacao.forEach(id => {
                                const elemento = document.getElementById(id);
                                if (elemento) {
                                    if (id === 'escola_lotacao' || id === 'buscar_escola_lotacao') {
                                        elemento.value = '';
                                        if (id === 'escola_lotacao') {
                                            elemento.size = 1;
                                        }
                                    } else {
                                        elemento.classList.add('hidden');
                                    }
                                }
                            });
                        } catch (e) {
                            console.log('Elementos de lotação ainda não carregados');
                        }
                    }, 100);
                }
            } catch (error) {
                console.error('Erro na função showTab:', error);
            }
        };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="global-theme.css" rel="stylesheet">
    
    <!-- Theme Manager -->
    <script src="theme-manager.js"></script>
    
    <!-- VLibras -->
    <div id="vlibras-widget" vw class="enabled">
        <div vw-access-button class="active"></div>
        <div vw-plugin-wrapper>
            <div class="vw-plugin-top-wrapper"></div>
        </div>
    </div>
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        // Inicializar VLibras apenas se estiver habilitado
        function initializeVLibras() {
            if (localStorage.getItem('vlibras-enabled') !== 'false') {
                if (window.VLibras) {
                    new window.VLibras.Widget('https://vlibras.gov.br/app');
                }
            }
        }
        
        // Aguardar o carregamento do script
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeVLibras);
        } else {
            initializeVLibras();
        }
    </script>
    
    <style>
        .tab-active {
            border-bottom: 2px solid #2D5A27;
            color: #2D5A27;
            font-weight: 600;
        }

        /* VLibras - Estilos para controle */
        #vlibras-widget.disabled {
            display: none !important;
        }
        
        #vlibras-widget.enabled {
            display: block !important;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Estilos para botões de salvar */
        button[type="submit"]:disabled {
            animation: none;
            box-shadow: none;
        }
        
        /* Estilos para Modal de Sucesso */
        @keyframes slideInDown {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes checkmark {
            0% {
                stroke-dashoffset: 100;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }
        
        @keyframes scaleIn {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .modal-sucesso-show {
            animation: slideInDown 0.4s ease-out;
        }
        
        .checkmark-circle {
            animation: scaleIn 0.5s ease-out;
        }
        
        .checkmark-check {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: checkmark 0.6s ease-out 0.3s forwards;
        }
        
        /* Estilos para o menu lateral */
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }

        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }

        #sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-right: 1px solid #e2e8f0;
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
        }

        /* Classe para reduzir opacidade do conteúdo principal quando menu está aberto */
        .content-dimmed {
            opacity: 0.5 !important;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }

        /* Tema Escuro */
        [data-theme="dark"] {
            --bg-primary: #0a0a0a;
            --bg-secondary: #1a1a1a;
            --bg-tertiary: #2a2a2a;
            --bg-quaternary: #3a3a3a;
            --text-primary: #ffffff;
            --text-secondary: #e0e0e0;
            --text-muted: #b0b0b0;
            --text-accent: #d0d0d0;
            --border-color: #404040;
            --border-light: #505050;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.6);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.7);
            --primary-green: #4ade80;
            --primary-green-hover: #22c55e;
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }

        [data-theme="dark"] .bg-white {
            background: linear-gradient(145deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        [data-theme="dark"] .text-gray-800 {
            color: #ffffff !important;
        }

        [data-theme="dark"] .text-gray-600 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .text-gray-500 {
            color: #c0c0c0 !important;
        }

        [data-theme="dark"] .text-gray-400 {
            color: #a0a0a0 !important;
        }

        [data-theme="dark"] .text-gray-300 {
            color: #d0d0d0 !important;
        }

        [data-theme="dark"] .text-gray-200 {
            color: #e8e8e8 !important;
        }

        [data-theme="dark"] .text-gray-100 {
            color: #f0f0f0 !important;
        }

        [data-theme="dark"] .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] .text-gray-700 {
            color: #d0d0d0 !important;
        }

        /* Corrigir hovers brancos no modo escuro */
        [data-theme="dark"] .hover\:bg-white:hover {
            background-color: #2a2a2a !important;
        }

        [data-theme="dark"] .hover\:bg-gray-50:hover {
            background-color: #333333 !important;
        }

        [data-theme="dark"] .hover\:bg-gray-100:hover {
            background-color: #3a3a3a !important;
        }

        [data-theme="dark"] .hover\:text-gray-900:hover {
            color: #ffffff !important;
        }

        [data-theme="dark"] .hover\:text-gray-800:hover {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .border-gray-200 {
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .border-gray-300 {
            border-color: var(--border-light) !important;
        }

        [data-theme="dark"] .border-gray-400 {
            border-color: var(--border-light) !important;
        }

        [data-theme="dark"] .bg-gray-50 {
            background: #2a2a2a !important;
            border: 1px solid #555555 !important;
        }

        [data-theme="dark"] .bg-gray-100 {
            background-color: #333333 !important;
        }

        [data-theme="dark"] .bg-gray-200 {
            background-color: #3a3a3a !important;
        }

        [data-theme="dark"] .bg-gray-300 {
            background-color: #404040 !important;
        }

        [data-theme="dark"] .shadow-lg {
            box-shadow: var(--shadow-lg) !important;
        }

        [data-theme="dark"] .shadow-sm {
            box-shadow: var(--shadow) !important;
        }

        [data-theme="dark"] #sidebar {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--border-color);
        }

        [data-theme="dark"] .menu-item {
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] .menu-item:hover {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .menu-item.active {
            background: linear-gradient(90deg, rgba(34, 197, 94, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
            border-right: 3px solid var(--primary-green);
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] header {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border-bottom: 1px solid var(--border-color);
        }

        [data-theme="dark"] input,
        [data-theme="dark"] select,
        [data-theme="dark"] textarea {
            background-color: #2d2d2d !important;
            border-color: #555555 !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] input::placeholder,
        [data-theme="dark"] textarea::placeholder {
            color: #a0a0a0 !important;
        }

        [data-theme="dark"] input:focus,
        [data-theme="dark"] select:focus,
        [data-theme="dark"] textarea:focus {
            border-color: var(--primary-green) !important;
            box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.3) !important;
            background-color: #333333 !important;
        }

        /* Corrigir elementos específicos problemáticos */
        [data-theme="dark"] .bg-white {
            background-color: #2a2a2a !important;
        }

        [data-theme="dark"] .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] .text-gray-800 {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .text-gray-700 {
            color: #d0d0d0 !important;
        }

        /* Corrigir tabelas */
        [data-theme="dark"] table {
            background-color: #2a2a2a !important;
        }

        [data-theme="dark"] th {
            background-color: #333333 !important;
            color: #ffffff !important;
        }

        [data-theme="dark"] td {
            background-color: #2a2a2a !important;
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] tr:hover td {
            background-color: #333333 !important;
        }

        /* Estilos para o formulário de cadastro no modo escuro */
        [data-theme="dark"] #tab-cadastrar .bg-white {
            background-color: var(--bg-secondary) !important;
        }
        [data-theme="dark"] #tab-cadastrar .text-gray-900 {
            color: var(--text-primary) !important;
        }
        [data-theme="dark"] #tab-cadastrar .text-gray-600 {
            color: var(--text-secondary) !important;
        }
        [data-theme="dark"] #tab-cadastrar .border-gray-200 {
            border-color: var(--border-color) !important;
        }
        [data-theme="dark"] #tab-cadastrar .hover\:bg-gray-50:hover {
            background-color: var(--bg-tertiary) !important;
        }
        [data-theme="dark"] #tab-cadastrar input,
        [data-theme="dark"] #tab-cadastrar select {
            background-color: var(--bg-tertiary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }
        [data-theme="dark"] #tab-cadastrar input::placeholder {
            color: var(--text-muted) !important;
        }



        /* Estilos específicos para o card do gestor no tema escuro */
        [data-theme="dark"] #gestor-atual-info {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%) !important;
            border-color: var(--border-color) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3) !important;
        }

        [data-theme="dark"] #gestor-atual-info:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4) !important;
        }

        [data-theme="dark"] #gestor-atual-info .text-gray-900 {
            color: #ffffff !important;
        }

        [data-theme="dark"] #gestor-atual-info .text-gray-600 {
            color: #d1d5db !important;
        }

        [data-theme="dark"] #gestor-atual-info .text-gray-500 {
            color: #9ca3af !important;
        }

        [data-theme="dark"] #gestor-atual-info button {
            background-color: rgba(220, 38, 38, 0.1) !important;
            border-color: #dc2626 !important;
            color: #fca5a5 !important;
        }

        [data-theme="dark"] #gestor-atual-info button:hover {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            border-color: #dc2626 !important;
        }

        /* ===== MELHORIAS DE RESPONSIVIDADE ===== */
        
        /* Mobile First - Breakpoints */
        @media (max-width: 640px) {
            /* Sidebar mobile */
            #sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 50;
            }
            
            #sidebar.mobile-open {
                transform: translateX(0);
            }
            
        /* Header mobile - FORÇA VISIBILIDADE */
            header {
            padding: 0.75rem 1rem !important;
            position: relative !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            background: white !important;
            border-bottom: 1px solid #e5e7eb !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
        }
        
        header .flex {
            min-height: 48px !important;
            align-items: center !important;
            display: flex !important;
            visibility: visible !important;
        }
        
        /* Botão menu MOBILE - FORÇA VISIBILIDADE */
        .mobile-menu-btn {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 999 !important;
            background: white !important;
            border: 1px solid #e5e7eb !important;
            position: relative !important;
            width: 40px !important;
            height: 40px !important;
        }
        
        /* Título centralizado */
        header h1 {
            font-size: 1.125rem !important;
            font-weight: 600 !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            }
            
            /* Cards responsivos */
            .card-hover {
                margin-bottom: 1rem;
            }
            
            /* Tabelas responsivas */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table-responsive table {
                min-width: 600px;
            }
            
            /* Modais mobile */
            .modal-content {
                margin: 1rem;
                max-height: calc(100vh - 2rem);
                overflow-y: auto;
            }
            
            /* Formulários mobile */
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            /* Botões mobile */
            .btn-mobile {
                width: 100%;
                padding: 0.75rem;
                font-size: 1rem;
            }
        }
        
        /* CSS GLOBAL - FORÇA VISIBILIDADE DO HEADER MOBILE */
        @media (max-width: 1023px) {
            header {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 100 !important;
                background: white !important;
            }
            
            .mobile-menu-btn {
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        }

        /* Desktop - esconder botão menu */
        @media (min-width: 1024px) {
            .mobile-menu-btn {
                display: none !important;
            }
        }
        
        @media (min-width: 641px) and (max-width: 1024px) {
            /* Tablet */
            #sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
            
            .card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1025px) {
            /* Desktop */
            .card-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* ===== COMPONENTES RESPONSIVOS ===== */
        
        /* Grid responsivo para cards */
        .card-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: 1fr;
        }
        
        @media (min-width: 640px) {
            .card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .card-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Tabelas responsivas */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .table-responsive table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-responsive th,
        .table-responsive td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-responsive th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        /* Formulários responsivos */
        .form-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }
        
        @media (min-width: 640px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Botões responsivos */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        @media (min-width: 640px) {
            .btn-group {
                flex-direction: row;
            }
        }
        
        /* ===== MELHORIAS DE UX ===== */
        
        /* Loading states */
        .loading {
            position: relative;
            overflow: hidden;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Feedback visual */
        .success-feedback {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        .error-feedback {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        /* Estados de foco melhorados */
        .focus-visible {
            outline: 2px solid #2D5A27;
            outline-offset: 2px;
        }
        
        /* Microinterações */
        .micro-interaction {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .micro-interaction:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .micro-interaction:active {
            transform: translateY(0);
        }
        
        /* Estilos para autocomplete de localidade */
        .autocomplete-container {
            position: relative;
        }
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 4px;
            display: none;
        }
        .autocomplete-dropdown.show {
            display: block;
        }
        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            transition: background-color 0.15s;
            border-bottom: 1px solid #f3f4f6;
        }
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        .autocomplete-item:hover,
        .autocomplete-item.selected {
            background-color: #f3f4f6;
        }
        .autocomplete-item .distrito-nome {
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }
        .autocomplete-item:hover .distrito-nome,
        .autocomplete-item.selected .distrito-nome {
            color: #1f2937;
        }
    </style>
    <!-- User Profile Modal CSS -->
</head>
<body class="bg-gray-50 font-sans">
    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden mobile-menu-overlay lg:hidden"></div>
    
    <!-- Sidebar -->
    <?php if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'ADM') { ?>
        <?php include('components/sidebar_adm.php'); ?>
    <?php } else { ?>
        <!-- Sidebar padrão para outros tipos de usuário -->
        <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg sidebar-transition z-50 lg:translate-x-0 sidebar-mobile">
            <!-- Logo e Header -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" alt="Brasão de Maranguape" class="w-10 h-10 object-contain">
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">SIGEA</h1>
                        <p class="text-xs text-gray-500">Maranguape</p>
                    </div>
                </div>
            </div>

            <!-- User Info -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center flex-shrink-0" style="aspect-ratio: 1; min-width: 2.5rem; min-height: 2.5rem; overflow: hidden;">
                        <span class="text-sm font-bold text-white">
                            <?php
                            $nome = $_SESSION['nome'] ?? '';
                            $iniciais = '';
                            if (strlen($nome) >= 2) {
                                $iniciais = strtoupper(substr($nome, 0, 2));
                            } elseif (strlen($nome) == 1) {
                                $iniciais = strtoupper($nome);
                            } else {
                                $iniciais = 'US';
                            }
                            echo $iniciais;
                            ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800"><?= $_SESSION['nome'] ?? 'Usuário' ?></p>
                        <p class="text-xs text-gray-500"><?= $_SESSION['tipo'] ?? 'Funcionário' ?></p>
                    </div>
                </div>
            </div>

            <nav class="p-4 overflow-y-auto sidebar-nav" style="max-height: calc(100vh - 200px); scroll-behavior: smooth;">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['tipo'] === 'GESTAO') { ?>
                    <li>
                        <a href="gestao_escolar.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            <span>Gestão Escolar</span>
                        </a>
                    </li>
                    <?php } ?>
                    <?php if (isset($_SESSION['Gerenciador de Usuarios'])) { ?>
                    <li>
                        <a href="../../subsystems/gerenciador_usuario/index.php" class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>Gerenciador de Usuários</span>
                        </a>
                    </li>
                    <?php } ?>
                    <li>
                        <button onclick="window.confirmLogout()" class="menu-item w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span>Sair</span>
                        </button>
                    </li>
                </ul>
            </nav>
        </aside>
    <?php } ?>

    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30 ml-0 lg:ml-64 content-transition">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Mobile Menu Button -->
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-green" aria-label="Abrir menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Título centralizado -->
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Gestão de Escolas</h1>
                    </div>
                    
                    <!-- Área direita -->
                    <div class="flex items-center space-x-4">
                        <!-- Escola atual (desktop) -->
                        <div class="text-right hidden lg:block">
                            <p class="text-sm font-medium text-gray-800" id="currentSchool">
                                <?php
                                if ($_SESSION['tipo'] === 'ADM') {
                                    echo 'Secretaria Municipal da Educação';
                                } else {
                                    echo !empty($_SESSION['escola_atual']) ? htmlspecialchars($_SESSION['escola_atual']) : 'N/A';
                                }
?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php
if ($_SESSION['tipo'] === 'ADM') {
    echo 'Órgão Central';
} else {
    echo 'Escola Atual';
}
?>
                            </p>
                        </div>
                        
                        <!-- User Profile Button -->
                        <div class="p-2 text-gray-600 bg-gray-100 rounded-full" title="Perfil do Usuário">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="ml-0 lg:ml-64 content-transition px-4 sm:px-6 lg:px-8 py-8">
            <?php if (!empty($mensagem)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $tipoMensagem === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <div class="flex space-x-8">
                    <button onclick="showTab('tab-listar')" class="tab-btn tab-active py-4 px-1 focus:outline-none">
                        Listar Escolas
                    </button>
                    <button onclick="showTab('tab-cadastrar')" class="tab-btn py-4 px-1 focus:outline-none">
                        Cadastrar Nova Escola
                    </button>
                    <button onclick="showTab('tab-adicionar-gestor')" class="tab-btn py-4 px-1 focus:outline-none">
                        Adicionar Gestor
                    </button>
                    <button onclick="showTab('tab-lotacao')" class="tab-btn py-4 px-1 focus:outline-none">
                        Lotação do Corpo Docente
                    </button>
                </div>
            </div>
            
            <!-- Tab Contents -->
            <div id="tab-listar" class="tab-content active">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Lista de Escolas</h2>
                    
                    <!-- Search Box -->
                    <form method="GET" class="mb-6">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" name="busca" placeholder="Buscar por nome, endereço ou gestor..." 
                                   value="<?php echo htmlspecialchars($busca); ?>"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-green focus:border-primary-green">
                        </div>
                    </form>
                    
                    <!-- Tabela de Escolas -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código INEP</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endereço</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gestor</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contato</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salas</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Criação</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($escolas)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Nenhuma escola encontrada
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($escolas as $escola): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-primary-green rounded-full flex items-center justify-center">
                                                    <span class="text-white font-medium"><?php echo substr($escola['nome'], 0, 1); ?></span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($escola['nome']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $escola['codigo'] ? htmlspecialchars($escola['codigo']) : 'N/A'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($escola['endereco']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $escola['gestor_nome'] ? htmlspecialchars($escola['gestor_nome']) : 'Não definido'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>
                                                <div><?php echo htmlspecialchars($escola['telefone']); ?></div>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($escola['email']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $escola['qtd_salas'] ? $escola['qtd_salas'] : 'N/A'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($escola['data_criacao'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="abrirModalEdicaoEscola(<?php echo $escola['id']; ?>, '<?php echo htmlspecialchars($escola['nome']); ?>')" class="text-blue-600 hover:text-blue-900">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="abrirModalExclusaoEscola(<?php echo $escola['id']; ?>, '<?php echo htmlspecialchars($escola['nome']); ?>')" class="text-red-600 hover:text-red-900">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Cadastrar -->
            <div id="tab-cadastrar" class="tab-content hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Cadastrar Nova Escola</h2>
                    <form method="POST" class="space-y-8">
                        <input type="hidden" name="acao" value="cadastrar">
                        <!-- Seção: Identificação da Escola -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Identificação da Escola</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">Nome da Escola *</label>
                                    <input type="text" id="nome" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="Ex: Escola Municipal João Silva">
                                </div>
                                <div>
                                    <label for="inep" class="block text-sm font-medium text-gray-700 mb-2">Código INEP</label>
                                    <input type="text" id="inep" name="inep" maxlength="8" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="Ex: 15663883">
                                </div>
                                <div>
                                    <label for="nome_curto" class="block text-sm font-medium text-gray-700 mb-2">Nome Curto</label>
                                    <input type="text" id="nome_curto" name="nome_curto" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="Ex: EM João Silva">
                                </div>
                                <div>
                                    <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">Código da Escola</label>
                                    <input type="text" id="codigo" name="codigo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="Deixe vazio para gerar automaticamente">
                                </div>
                                <div>
                                    <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-2">CNPJ</label>
                                    <input type="text" id="cnpj" name="cnpj" maxlength="18" 
                                           oninput="formatarCNPJ(this)" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" 
                                           placeholder="00.000.000/0000-00">
                                </div>
                            </div>
                        </div>
                        <!-- Seção: Classificação -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Classificação</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="tipo_escola" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Escola *</label>
                                    <select id="tipo_escola" name="tipo_escola" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors">
                                        <option value="NORMAL">REGULAR</option>
                                        <option value="ESPECIAL">ESPECIAL</option>
                                        <option value="INDIGENA">INDÍGENA</option>
                                        <option value="QUILOMBOLA">QUILOMBOLA</option>
                                        <option value="INTEGRAL">INTEGRAL</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="qtd_salas" class="block text-sm font-medium text-gray-700 mb-2">Quantidade de Salas</label>
                                    <input type="number" id="qtd_salas" name="qtd_salas" min="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="Ex: 12">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nível de Ensino *</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="nivel_ensino[]" value="EDUCACAO_INFANTIL" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                            <span class="text-gray-700">Educação Infantil</span>
                                        </label>
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="nivel_ensino[]" value="ENSINO_FUNDAMENTAL" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                            <span class="text-gray-700">Ensino Fundamental</span>
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Selecione um ou ambos os níveis de ensino oferecidos pela escola</p>
                                </div>
                            </div>
                        </div>
                        <!-- Seção: Endereço -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Endereço</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label for="cep" class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                                    <input type="text" id="cep" name="cep" maxlength="9" 
                                           oninput="formatarCEPCadastro(this)" 
                                           onblur="buscarCEPCadastro(this.value)"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" 
                                           placeholder="67.030-180">
                                    <div id="resultadoCEPCadastro" class="mt-2 text-sm hidden"></div>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="logradouro" class="block text-sm font-medium text-gray-700 mb-2">Logradouro</label>
                                    <input type="text" id="logradouro" name="logradouro" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="Ex: AVENIDA ZACARIAS DE ASSUNÇÃO">
                                </div>
                                <div>
                                    <label for="numero" class="block text-sm font-medium text-gray-700 mb-2">Número</label>
                                    <input type="text" id="numero" name="numero" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="Ex: 30">
                                </div>
                                <div>
                                    <label for="complemento" class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                    <input type="text" id="complemento" name="complemento" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="Ex: Próximo ao centro">
                                </div>
                                <div>
                                    <label for="bairro" class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
                                    <input type="text" id="bairro" name="bairro" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="Ex: CENTRO">
                                </div>
                                <div>
                                    <label for="distrito" class="block text-sm font-medium text-gray-700 mb-2">Distrito *</label>
                                    <select id="distrito" name="distrito" required onchange="carregarLocalidadesCadastro()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors">
                                        <option value="">Selecione o distrito</option>
                                        <option value="Amanari">Amanari</option>
                                        <option value="Antônio Marques">Antônio Marques</option>
                                        <option value="Cachoeira">Cachoeira</option>
                                        <option value="Itapebussu">Itapebussu</option>
                                        <option value="Jubaia">Jubaia</option>
                                        <option value="Ladeira Grande">Ladeira Grande</option>
                                        <option value="Lages">Lages</option>
                                        <option value="Lagoa do Juvenal">Lagoa do Juvenal</option>
                                        <option value="Manoel Guedes">Manoel Guedes</option>
                                        <option value="Sede">Sede</option>
                                        <option value="Papara">Papara</option>
                                        <option value="Penedo">Penedo</option>
                                        <option value="Sapupara">Sapupara</option>
                                        <option value="São João do Amanari">São João do Amanari</option>
                                        <option value="Tanques">Tanques</option>
                                        <option value="Umarizeiras">Umarizeiras</option>
                                        <option value="Vertentes do Lagedo">Vertentes do Lagedo</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Distrito onde a escola está localizada (obrigatório para criação de rotas)</p>
                                </div>
                                <div>
                                    <label for="localidade" class="block text-sm font-medium text-gray-700 mb-2">Localidade</label>
                                    <div class="autocomplete-container">
                                        <input type="text" id="localidade" name="localidade" 
                                               oninput="formatarLocalidade(this)" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Digite o nome da localidade..." autocomplete="off">
                                        <input type="hidden" id="localidade_id" name="localidade_id">
                                        <div id="autocomplete-dropdown-localidade" class="autocomplete-dropdown"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Localidade específica dentro do distrito. Se não existir, será criada automaticamente.</p>
                                </div>
                            </div>
                        </div>
                        <!-- Seção: Contatos -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Contatos</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="telefone_fixo" class="block text-sm font-medium text-gray-700 mb-2">Telefone Fixo</label>
                                    <input type="tel" id="telefone_fixo" name="telefone_fixo" onkeyup="formatarTelefone(this)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="(85) 3333-4444">
                                </div>
                                <div>
                                    <label for="telefone_movel" class="block text-sm font-medium text-gray-700 mb-2">Telefone Móvel</label>
                                    <input type="tel" id="telefone_movel" name="telefone_movel" onkeyup="formatarTelefone(this)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="(85) 99999-9999">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                                    <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="escola@maranguape.ce.gov.br">
                                </div>
                                <div>
                                    <label for="site" class="block text-sm font-medium text-gray-700 mb-2">Site</label>
                                    <input type="url" id="site" name="site" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" placeholder="https://www.escola.com.br">
                                </div>
                            </div>
                        </div>
                        <!-- Seção: Dados do Gestor -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Dados do Gestor</h3>
                            <div class="space-y-6">
                                <!-- Campo de Busca do Gestor -->
                                <div>
                                    <label for="buscar_gestor_cadastro" class="block text-sm font-medium text-gray-700 mb-2">
                                        Buscar Gestor <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="buscar_gestor_cadastro" 
                                               class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors" 
                                               placeholder="Digite o nome ou CPF do gestor..."
                                               autocomplete="off"
                                               oninput="buscarGestorCadastro(this.value)"
                                               onfocus="mostrarSugestoesGestorCadastro()"
                                               onblur="esconderSugestoesGestorCadastro()"
                                               onkeydown="navegarSugestoesGestorCadastro(event)">
                                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <!-- Lista de sugestões -->
                                        <div id="sugestoes_gestor_cadastro" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                                            <!-- Sugestões serão inseridas aqui -->
                                        </div>
                                    </div>
                                    <input type="hidden" id="gestor_id" name="gestor_id">
                                </div>
                                
                                <!-- Campos de Dados do Gestor (Readonly) -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="gestor_cpf" class="block text-sm font-medium text-gray-700 mb-2">CPF do Gestor</label>
                                        <input type="text" id="gestor_cpf" name="gestor_cpf" readonly class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed" placeholder="Selecione um gestor acima">
                                    </div>
                                    <div>
                                        <label for="gestor_nome" class="block text-sm font-medium text-gray-700 mb-2">Nome do Gestor</label>
                                        <input type="text" id="gestor_nome" name="gestor_nome" readonly class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed" placeholder="Selecione um gestor acima">
                                    </div>
                                    <div>
                                        <label for="gestor_email" class="block text-sm font-medium text-gray-700 mb-2">E-mail do Gestor</label>
                                        <input type="email" id="gestor_email" name="gestor_email" readonly class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed" placeholder="Selecione um gestor acima">
                                    </div>
                                    <div>
                                        <label for="gestor_telefone" class="block text-sm font-medium text-gray-700 mb-2">Telefone do Gestor</label>
                                        <input type="text" id="gestor_telefone" name="gestor_telefone" readonly class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed" placeholder="Selecione um gestor acima">
                                    </div>
                                    <div>
                                        <label for="gestor_cargo" class="block text-sm font-medium text-gray-700 mb-2">Cargo</label>
                                        <input type="text" id="gestor_cargo" name="gestor_cargo" readonly class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed" placeholder="Selecione um gestor acima">
                                    </div>
                                    <div>
                                        <label for="gestor_tipo_acesso" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Acesso</label>
                                        <input type="text" id="gestor_tipo_acesso" name="gestor_tipo_acesso" readonly class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed" placeholder="Selecione um gestor acima">
                                    </div>
                                    <div>
                                        <label for="gestor_criterio_acesso" class="block text-sm font-medium text-gray-700 mb-2">Critério de Acesso</label>
                                        <input type="text" id="gestor_criterio_acesso" name="gestor_criterio_acesso" readonly class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed" placeholder="Selecione um gestor acima">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Seção: Programas Educacionais -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Programas Educacionais</h3>
                            <p class="text-sm text-gray-600 mb-4">Selecione os programas educacionais dos quais esta escola faz parte:</p>
                            
                            <div class="space-y-3 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50" id="lista-programas-escola">
                                <?php
                                $programaModel = new ProgramaModel();
                                $programas = $programaModel->listar(['ativo' => 1]);
                                
                                if (empty($programas)): ?>
                                    <p class="text-sm text-gray-500 text-center py-4">Nenhum programa disponível. Crie programas na seção "Gestão de Programas".</p>
                                <?php else: ?>
                                    <?php foreach ($programas as $programa): ?>
                                        <label class="flex items-center space-x-3 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                            <input type="checkbox" 
                                                   name="programas[]" 
                                                   value="<?= $programa['id'] ?>" 
                                                   class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($programa['nome']) ?></div>
                                                <?php if (!empty($programa['descricao'])): ?>
                                                    <div class="text-sm text-gray-600"><?= htmlspecialchars($programa['descricao']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="reset" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green">
                                Limpar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green">
                                Cadastrar Escola
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab Adicionar Gestor -->
            <div id="tab-adicionar-gestor" class="tab-content hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Adicionar Gestor à Escola</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-blue-800">
                                <strong>Importante:</strong> Selecione uma escola primeiro e depois escolha o gestor desejado.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Passo 1: Seleção da Escola -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center space-x-2 mb-4">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">1</div>
                                <h3 class="text-lg font-medium text-gray-900">Selecionar Escola</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label for="escola_gestor" class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                                    <div class="relative">
                                        <input type="text" id="buscar_escola_gestor" placeholder="Digite o nome da escola..."
                                               class="block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green"
                                               autocomplete="off"
                                               oninput="buscarEscolasGestor(this.value)"
                                               onfocus="mostrarSugestoesGestor()"
                                               onblur="esconderSugestoesGestor()"
                                               onkeydown="navegarSugestoesGestor(event)">
                                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <!-- Lista de sugestões -->
                                        <div id="sugestoes_gestor" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                                            <!-- Sugestões serão inseridas aqui -->
                                        </div>
                                    </div>
                                    <input type="hidden" id="escola_gestor" name="escola_gestor" required>
                                </div>
                                
                                <!-- Informações da Escola Selecionada -->
                                <div id="info-escola-gestor" class="hidden">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Informações da Escola</h4>
                                    <div id="detalhes-escola-gestor" class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-600">
                                        <!-- Detalhes serão carregados aqui -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Passo 2: Seleção do Gestor -->
                        <div id="passo-selecionar-gestor" class="hidden bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center space-x-2 mb-4">
                                <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">2</div>
                                <h3 class="text-lg font-medium text-gray-900">Selecionar Gestor</h3>
                            </div>
                            
                            <form method="POST" id="form-adicionar-gestor">
                                <input type="hidden" name="acao" value="adicionar_gestor">
                                <input type="hidden" id="escola_id_gestor" name="escola_id">
                                
                                <div class="space-y-4">
                                    <!-- Busca de Gestores -->
                                    <div>
                                        <label for="buscar_gestor" class="block text-sm font-medium text-gray-700 mb-2">Buscar Gestor</label>
                                        <div class="relative">
                                            <input type="text" id="buscar_gestor" placeholder="Digite o nome do gestor..."
                                                   class="block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                                   oninput="buscarGestores(this.value)">
                                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                     <!-- Lista de Gestores Disponíveis -->
                                     <div>
                                         <h4 class="text-sm font-medium text-gray-700 mb-3">Gestores Disponíveis</h4>
                                         <div id="lista-gestores" class="space-y-3 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                                             <?php
                                             $gestores = buscarGestoresNovo();
                                             foreach ($gestores as $gestor):
                                             ?>
                                                 <div class="gestor-item group flex items-center space-x-3 p-4 bg-white border border-gray-200 rounded-lg hover:border-gray-300 hover:shadow-sm cursor-pointer transition-all duration-200"
                                                      onclick="selecionarGestor(<?php echo $gestor['gestor_id']; ?>, '<?php echo htmlspecialchars($gestor['nome_gestor']); ?>')">
                                                     <input type="radio" name="gestor_id" value="<?php echo $gestor['gestor_id']; ?>" 
                                                            id="gestor_<?php echo $gestor['gestor_id']; ?>" class="gestor-radio hidden">
                                                     
                                                     <!-- Avatar do Gestor -->
                                                     <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                                         <?php echo strtoupper(substr($gestor['nome_gestor'], 0, 2)); ?>
                                                     </div>
                                                     
                                                     <!-- Informações do Gestor -->
                                                     <div class="flex-1">
                                                         <div class="font-medium text-gray-900 group-hover:text-blue-600 transition-colors">
                                                             <?php echo htmlspecialchars($gestor['nome_gestor']); ?>
                                                         </div>
                                                         <div class="text-sm text-gray-500">ID: <?php echo $gestor['gestor_id']; ?></div>
                                                     </div>
                                                     
                                                     <!-- Ícone de seleção -->
                                                     <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center group-hover:border-blue-500 transition-colors">
                                                         <div class="w-2 h-2 bg-transparent rounded-full group-hover:bg-blue-500 transition-colors"></div>
                                                     </div>
                                                 </div>
                                             <?php endforeach; ?>
                                         </div>
                                     </div>
                                    
                                     <!-- Gestor Selecionado -->
                                     <div id="gestor-selecionado" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                                         <div class="flex items-center space-x-3">
                                             <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                                 <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                 </svg>
                                             </div>
                                             <div>
                                                 <div class="text-sm font-medium text-blue-800">Gestor selecionado:</div>
                                                 <div id="nome-gestor-selecionado" class="text-sm font-semibold text-blue-900"></div>
                                             </div>
                                         </div>
                                         <div class="mt-2 text-xs text-blue-600">
                                             💡 Dica: Clique novamente no gestor para deselecionar
                                         </div>
                                     </div>
                                </div>
                                
                                <!-- Tipo de Gestor -->
                                <div class="mt-6">
                                    <label for="tipo_gestor" class="block text-sm font-medium text-gray-700 mb-3">
                                        <span class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span>Tipo de Gestor</span>
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <select id="tipo_gestor" name="tipo_gestor" required
                                                class="block w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-primary-green focus:border-primary-green transition-all duration-200 appearance-none cursor-pointer hover:border-gray-400"
                                                onchange="validarSelecaoGestor()">
                                            <option value="">Selecione o tipo de gestor</option>
                                            <option value="Diretor" class="py-2">
                                                <span class="flex items-center space-x-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                                    </svg>
                                                    Diretor
                                                </span>
                                            </option>
                                            <option value="Vice-diretor">Vice-Diretor</option>
                                            <option value="Coordenador Pedagógico">Coordenador Pedagógico</option>
                                            <option value="Secretário Escolar">Secretário Escolar</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Selecione o cargo que o gestor exercerá na escola
                                    </div>
                                </div>

                                <!-- Botões de Ação -->
                                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                                    <button type="button" onclick="limparSelecaoGestor()" 
                                            class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 font-medium">
                                        <span class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <span>Limpar Seleção</span>
                                        </span>
                                    </button>
                                    <button type="submit" id="btn-adicionar-gestor" disabled name="btn-adicionar-gestor"
                                            class="px-6 py-2.5 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium shadow-sm hover:shadow-md">
                                        <span class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            <span>Adicionar Gestor</span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Lotação do Corpo Docente -->
            <div id="tab-lotacao" class="tab-content hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Lotação do Corpo Docente</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-blue-800">
                                <strong>Importante:</strong> Selecione uma escola para visualizar e gerenciar a lotação de professores e gestores.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Passo 1: Seleção da Escola -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center space-x-2 mb-4">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">1</div>
                                <h3 class="text-lg font-medium text-gray-900">Selecionar Escola</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label for="escola_lotacao" class="block text-sm font-medium text-gray-700 mb-2">Escola *</label>
                                    <div class="relative">
                                        <input type="text" id="buscar_escola_lotacao" placeholder="Digite o nome da escola..."
                                               class="block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-primary-green focus:border-primary-green"
                                               autocomplete="off"
                                               oninput="buscarEscolasLotacao(this.value)"
                                               onfocus="mostrarSugestoesLotacao()"
                                               onblur="esconderSugestoesLotacao()"
                                               onkeydown="navegarSugestoesLotacao(event)">
                                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <!-- Lista de sugestões -->
                                        <div id="sugestoes_lotacao" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                                            <!-- Sugestões serão inseridas aqui -->
                                        </div>
                                    </div>
                                    <input type="hidden" id="escola_lotacao" name="escola_lotacao" required>
                                </div>
                                
                                <!-- Informações da Escola Selecionada -->
                                <div id="info-escola-lotacao" class="hidden">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Informações da Escola</h4>
                                    <div id="detalhes-escola-lotacao" class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-600">
                                        <!-- Detalhes serão carregados aqui -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Passo 2: Gerenciamento da Lotação -->
                        <div id="secao-lotacao" class="hidden bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex items-center space-x-2 mb-6">
                                <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">2</div>
                                <h3 class="text-lg font-medium text-gray-900">Gerenciar Lotação</h3>
                            </div>
                            
                            <!-- Título da Seção -->
                            <div class="border-b border-gray-200 mb-6">
                                <div class="flex items-center space-x-2 pb-4">
                                    <svg class="w-5 h-5 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900">Gerenciar Professores</h3>
                                </div>
                            </div>

                            <!-- Conteúdo da aba Professores -->
                            <div id="lotacao-professores" class="lotacao-tab-content">
                                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                                    <!-- Adicionar Professor -->
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                                        <div class="flex items-center space-x-2 mb-4">
                                            <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                            </div>
                                            <h4 class="text-lg font-medium text-gray-900">Adicionar Professor</h4>
                                        </div>
                                        
                                        <div class="space-y-4">
                                            <div>
                                                <label for="buscar_professor_lotacao" class="block text-sm font-medium text-gray-700 mb-2">Buscar Professor</label>
                                                <div class="relative">
                                                    <input type="text" id="buscar_professor_lotacao" placeholder="Digite o nome do professor..."
                                                           class="block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                                           oninput="buscarProfessoresLotacao(this.value)">
                                                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                    </svg>
                                                </div>
                                                <div id="resultados_professores_lotacao" class="mt-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg hidden">
                                                    <!-- Resultados da busca serão carregados aqui -->
                                                </div>
                                            </div>

                                            <div>
                                                <label for="disciplina_professor_lotacao" class="block text-sm font-medium text-gray-700 mb-2">Disciplina</label>
                                                <select id="disciplina_professor_lotacao" 
                                                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                    <option value="">Selecione uma disciplina...</option>
                                                    <option value="matematica">Matemática</option>
                                                    <option value="portugues">Português</option>
                                                    <option value="ciencias">Ciências</option>
                                                    <option value="historia">História</option>
                                                    <option value="geografia">Geografia</option>
                                                    <option value="educacao_fisica">Educação Física</option>
                                                    <option value="artes">Artes</option>
                                                    <option value="ingles">Inglês</option>
                                                    <option value="espanhol">Espanhol</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label for="data_inicio_professor" class="block text-sm font-medium text-gray-700 mb-2">Data de Início</label>
                                                <input type="date" id="data_inicio_professor" 
                                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                                       required>
                                            </div>

                                            <button type="button" onclick="lotarProfessor()" 
                                                    class="w-full bg-primary-green text-white px-4 py-2.5 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium flex items-center justify-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                <span>Lotar Professor</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Lista de Professores Lotados -->
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                                        <div class="flex items-center space-x-2 mb-4">
                                            <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <h4 class="text-lg font-medium text-gray-900">Professores Lotados</h4>
                                        </div>
                                        <div id="lista-professores-lotados" class="space-y-3 max-h-96 overflow-y-auto">
                                            <!-- Lista será carregada aqui -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal de Exclusão de Escola -->
    <div id="modalExclusaoEscola" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirmar Exclusão</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Tem certeza que deseja excluir a escola <strong id="nomeEscolaExclusao"></strong>?
                </p>
                <p class="text-xs text-red-600 mb-4">
                    ⚠️ Esta ação não pode ser desfeita. Todos os dados relacionados à escola serão perdidos permanentemente.
                </p>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                    <label class="flex items-start space-x-2 cursor-pointer">
                        <input type="checkbox" id="excluirForcado" name="forcado" value="true" class="mt-1">
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-yellow-800">Excluir mesmo com dados relacionados</p>
                            <p class="text-xs text-yellow-700 mt-1">
                                Se marcado, a escola será desativada (soft delete) mantendo todos os dados preservados. 
                                Turmas, notas, frequências, alunos e todos os dados relacionados permanecerão no banco.
                            </p>
                        </div>
                    </label>
                </div>
                
                <div class="flex space-x-3 justify-center">
                    <button onclick="fecharModalExclusaoEscola()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                        Cancelar
                    </button>
                    <form id="formExclusaoEscola" method="POST" class="inline">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" id="idEscolaExclusao">
                        <input type="hidden" name="forcado" id="forcadoExclusao" value="false">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Sim, Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Edição de Escola (Full Screen) -->
    <div id="modalEdicaoEscola" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white w-full h-full overflow-hidden flex flex-col">
            <!-- Header do Modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary-green rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900" id="tituloModalEdicao">Editar Escola</h3>
                        <p class="text-sm text-gray-600">Gerencie as informações e corpo docente da escola</p>
                    </div>
                </div>
                <button onclick="fecharModalEdicaoEscola()" class="p-2 hover:bg-gray-200 rounded-full transition-colors duration-200">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conteúdo do Modal -->
            <div class="flex-1 overflow-y-auto p-6 flex flex-col">
                <form id="formEdicaoEscola" method="POST" class="flex flex-col flex-1 space-y-8">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" id="edit_escola_id" value="">
                    
                    <!-- Tabs de Navegação -->
                    <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button type="button" onclick="mostrarAbaEdicao('dados-basicos')" id="tab-dados-basicos" class="tab-edicao active py-2 px-1 border-b-2 border-primary-green font-medium text-sm text-primary-green">
                        Dados Básicos
                    </button>
                    <button type="button" onclick="mostrarAbaEdicao('gestor')" id="tab-gestor" class="tab-edicao py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Gestor
                    </button>
                    <button type="button" onclick="mostrarAbaEdicao('corpo-docente')" id="tab-corpo-docente" class="tab-edicao py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Corpo Docente
                    </button>
                </nav>
                    </div>
                    
                    <!-- Aba Dados Básicos -->
                    <div id="aba-dados-basicos" class="aba-edicao flex-1 flex flex-col">
                        <div class="space-y-8">
                            <!-- Seção: Identificação da Escola -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Identificação da Escola</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="edit_nome" class="block text-sm font-medium text-gray-700 mb-2">
                                            Nome da Escola *
                                        </label>
                                        <input type="text" id="edit_nome" name="nome"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Ex: Escola Municipal João Silva">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_inep" class="block text-sm font-medium text-gray-700 mb-2">
                                            Código INEP
                                        </label>
                                        <input type="text" id="edit_inep" name="inep" maxlength="8"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Ex: 15663883">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_nome_curto" class="block text-sm font-medium text-gray-700 mb-2">
                                            Nome Curto
                                        </label>
                                        <input type="text" id="edit_nome_curto" name="nome_curto"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Ex: EM João Silva">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_codigo" class="block text-sm font-medium text-gray-700 mb-2">
                                            Código da Escola
                                        </label>
                                        <input type="text" id="edit_codigo" name="codigo"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Código da escola">
                                    </div>
                                    <div>
                                        <label for="edit_cnpj" class="block text-sm font-medium text-gray-700 mb-2">
                                            CNPJ
                                        </label>
                                        <input type="text" id="edit_cnpj" name="cnpj" maxlength="18" 
                                               oninput="formatarCNPJ(this)"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="00.000.000/0000-00">
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Classificação -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Classificação</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="edit_tipo_escola" class="block text-sm font-medium text-gray-700 mb-2">
                                            Tipo de Escola *
                                        </label>
                                        <select id="edit_tipo_escola" name="tipo_escola"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors">
                                            <option value="NORMAL">NORMAL</option>
                                            <option value="ESPECIAL">ESPECIAL</option>
                                            <option value="INDIGENA">INDÍGENA</option>
                                            <option value="QUILOMBOLA">QUILOMBOLA</option>
                                            <option value="INTEGRAL">INTEGRAL</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="edit_qtd_salas" class="block text-sm font-medium text-gray-700 mb-2">
                                            Quantidade de Salas
                                        </label>
                                        <input type="number" id="edit_qtd_salas" name="qtd_salas" min="1"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Ex: 12">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Nível de Ensino *
                                        </label>
                                        <div class="space-y-2">
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" id="edit_nivel_ensino_infantil" name="nivel_ensino[]" value="EDUCACAO_INFANTIL" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                                <span class="text-gray-700">Educação Infantil</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" id="edit_nivel_ensino_fundamental" name="nivel_ensino[]" value="ENSINO_FUNDAMENTAL" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                                <span class="text-gray-700">Ensino Fundamental</span>
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Selecione um ou ambos os níveis de ensino oferecidos pela escola</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Endereço -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Endereço</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <label for="edit_cep" class="block text-sm font-medium text-gray-700 mb-2">
                                            CEP
                                        </label>
                                        <input type="text" id="edit_cep" name="cep" maxlength="9" 
                                               oninput="formatarCEPEdicao(this)"
                                               onblur="buscarCEPEdicao(this.value)"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="67.030-180">
                                        <div id="resultadoCEPEdicao" class="mt-2 text-sm hidden"></div>
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label for="edit_logradouro" class="block text-sm font-medium text-gray-700 mb-2">
                                            Logradouro
                                        </label>
                                        <input type="text" id="edit_logradouro" name="logradouro"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Ex: AVENIDA ZACARIAS DE ASSUNÇÃO">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_numero" class="block text-sm font-medium text-gray-700 mb-2">
                                            Número
                                        </label>
                                        <input type="text" id="edit_numero" name="numero"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Ex: 30">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_complemento" class="block text-sm font-medium text-gray-700 mb-2">
                                            Complemento
                                        </label>
                                        <input type="text" id="edit_complemento" name="complemento"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Ex: Próximo ao centro">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_bairro" class="block text-sm font-medium text-gray-700 mb-2">
                                            Bairro
                                        </label>
                                        <input type="text" id="edit_bairro" name="bairro"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="Ex: CENTRO">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_distrito" class="block text-sm font-medium text-gray-700 mb-2">
                                            Distrito
                                        </label>
                                        <select id="edit_distrito" name="distrito" onchange="carregarLocalidadesEdicao()"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors">
                                            <option value="">Selecione o distrito</option>
                                            <option value="Amanari">Amanari</option>
                                            <option value="Antônio Marques">Antônio Marques</option>
                                            <option value="Cachoeira">Cachoeira</option>
                                            <option value="Itapebussu">Itapebussu</option>
                                            <option value="Jubaia">Jubaia</option>
                                            <option value="Ladeira Grande">Ladeira Grande</option>
                                            <option value="Lages">Lages</option>
                                            <option value="Lagoa do Juvenal">Lagoa do Juvenal</option>
                                            <option value="Manoel Guedes">Manoel Guedes</option>
                                            <option value="Sede">Sede</option>
                                            <option value="Papara">Papara</option>
                                            <option value="Penedo">Penedo</option>
                                            <option value="Sapupara">Sapupara</option>
                                            <option value="São João do Amanari">São João do Amanari</option>
                                            <option value="Tanques">Tanques</option>
                                            <option value="Umarizeiras">Umarizeiras</option>
                                            <option value="Vertentes do Lagedo">Vertentes do Lagedo</option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Distrito onde a escola está localizada</p>
                                    </div>
                                    
                                    <div>
                                        <label for="edit_localidade" class="block text-sm font-medium text-gray-700 mb-2">
                                            Localidade
                                        </label>
                                        <div class="autocomplete-container">
                                            <input type="text" id="edit_localidade" name="localidade" 
                                                   oninput="formatarLocalidade(this)" 
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                                   placeholder="Digite o nome da localidade..." autocomplete="off">
                                            <input type="hidden" id="edit_localidade_id" name="localidade_id">
                                            <div id="autocomplete-dropdown-localidade-edicao" class="autocomplete-dropdown"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Localidade específica dentro do distrito. Se não existir, será criada automaticamente.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Contatos -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contatos</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="edit_telefone_fixo" class="block text-sm font-medium text-gray-700 mb-2">
                                            Telefone Fixo
                                        </label>
                                        <input type="tel" id="edit_telefone_fixo" name="telefone_fixo" onkeyup="formatarTelefone(this)"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="(85) 3333-4444">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_telefone_movel" class="block text-sm font-medium text-gray-700 mb-2">
                                            Telefone Móvel
                                        </label>
                                        <input type="tel" id="edit_telefone_movel" name="telefone_movel" onkeyup="formatarTelefone(this)"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="(85) 99999-9999">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-2">
                                            E-mail
                                        </label>
                                        <input type="email" id="edit_email" name="email"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="escola@maranguape.ce.gov.br">
                                    </div>
                                    
                                    <div>
                                        <label for="edit_site" class="block text-sm font-medium text-gray-700 mb-2">
                                            Site
                                        </label>
                                        <input type="url" id="edit_site" name="site"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent transition-colors"
                                               placeholder="https://www.escola.com.br">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação - Dados Básicos -->
                        <div id="botoes-dados-basicos" class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                            <button type="button" onclick="fecharModalEdicaoEscola()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                                Cancelar
                            </button>
                            <button type="submit" id="btn-salvar-dados-basicos" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green transition-colors duration-200">
                                Salvar Alterações
                            </button>
                        </div>
                    </div>
                    
                    <!-- Aba Gestor -->
                    <div id="aba-gestor" class="aba-edicao hidden flex-1 flex flex-col">
                        <div class="space-y-6">
                            <!-- Gestor Atual -->
                            <div id="gestor-atual-section" class="hidden">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Gestor Atual</h4>
                                <div id="gestor-atual-info" class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-14 h-14 bg-gradient-to-br from-primary-green to-green-600 rounded-full flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-lg" id="gestor-atual-iniciais">-</span>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-semibold text-gray-900 text-lg" id="gestor-atual-nome">-</h5>
                                                <p class="text-sm text-gray-600 mb-1" id="gestor-atual-email">-</p>
                                                <p class="text-xs text-gray-500" id="gestor-atual-cpf">CPF: -</p>
                                                <p class="text-xs text-gray-500" id="gestor-atual-cargo">Cargo: -</p>
                                            </div>
                                        </div>
                                        <button type="button" onclick="removerGestorAtual()" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 hover:bg-red-100 hover:border-red-300 rounded-lg transition-all duration-200 flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            <span>Remover</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Mensagem quando não há gestor -->
                            <div id="nenhum-gestor-section">
                                <div class="text-center py-8">
                                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Esta escola não possui gestor</h4>
                                    <p class="text-gray-600 mb-4">Esta escola ainda não possui um gestor (diretor) definido.</p>
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <p class="text-sm text-blue-800">
                                                <strong>Nota:</strong> Para adicionar um gestor, use a aba "Adicionar Gestor" na página principal.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <!-- Aba Corpo Docente -->
                    <div id="aba-corpo-docente" class="aba-edicao hidden flex-1 flex flex-col">
                        <div class="space-y-6 flex-1 flex flex-col">
                            <div class="flex items-center justify-between">
                                <h4 class="text-lg font-medium text-gray-900">Professores da Escola</h4>
                                <button type="button" onclick="mostrarAdicionarProfessores()" class="bg-primary-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span>Adicionar Professor</span>
                                </button>
                            </div>
                            
                            <!-- Lista de Professores Atuais -->
                            <div id="lista-professores" class="space-y-3">
                                <!-- Professores serão carregados aqui via JavaScript -->
                            </div>

                            <!-- Seção Adicionar Professores (inicialmente oculta) -->
                            <div id="secao-adicionar-professores" class="hidden flex-1 flex flex-col">
                                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 flex-1 flex flex-col">
                                    <div class="flex items-center justify-between mb-4">
                                        <h5 class="text-lg font-semibold text-gray-900">Selecionar Professores</h5>
                                        <button type="button" onclick="ocultarAdicionarProfessores()" class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Search and Filter -->
                                    <div class="mb-6">
                                        <div class="flex flex-col sm:flex-row gap-4">
                                            <div class="flex-1">
                                                <div class="relative">
                                                    <input type="text" id="buscaProfessorEdicao" placeholder="Buscar professor por nome..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="sm:w-64">
                                                <select id="filtroDisciplinaEdicao" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                                                    <option value="">Todas as disciplinas</option>
                                                    <!-- Disciplinas serão carregadas dinamicamente do backend -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Teachers List -->
                                    <div class="mb-6 flex-1 flex flex-col">
                                        <div class="flex items-center justify-between mb-4">
                                            <h6 class="text-md font-semibold text-gray-900">Professores Disponíveis</h6>
                                            <div class="flex items-center space-x-2">
                                                <input type="checkbox" id="selecionarTodosEdicao" class="w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green">
                                                <label for="selecionarTodosEdicao" class="text-sm text-gray-600">Selecionar todos</label>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-1 overflow-y-auto border border-gray-200 rounded-lg" id="listaProfessoresDisponiveisEdicao">
                                            <!-- Lista de professores será carregada aqui -->
                                        </div>
                                    </div>

                                    <!-- Selected Teachers Summary -->
                                    <div id="resumoProfessoresSelecionadosEdicao" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg hidden">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-green-800">Professores selecionados:</span>
                                        </div>
                                        <div id="listaProfessoresSelecionadosEdicao" class="text-sm text-green-700">
                                            <!-- Lista dos professores selecionados -->
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        
                        <!-- Botões de Ação - Corpo Docente -->
                        <div id="botoes-corpo-docente" class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6 hidden">
                            <button type="button" onclick="fecharModalEdicaoEscola()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                                Cancelar
                            </button>
                            <button type="submit" id="btn-salvar-corpo-docente" class="px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-green transition-colors duration-200">
                                Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso -->
    <div id="modalSucesso" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl modal-sucesso-show">
            <!-- Ícone de Sucesso -->
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <div class="checkmark-circle w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path class="checkmark-check" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Mensagem -->
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-3">Sucesso!</h3>
                <p class="text-gray-600 text-lg mb-6">
                    Escola atualizada com sucesso!
                </p>
                
                <button onclick="fecharModalSucesso()" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                    Entendi
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Funções abrirModalEdicaoEscola e abrirModalExclusaoEscola já estão definidas no início do script
        // Não é necessário redefinir aqui
        
        // Funções para autocomplete de localidade (Cadastro) - Definir no escopo global
        window.localidadesDisponiveisCadastro = [];
        window.filteredLocalidadesCadastro = [];
        window.selectedIndexLocalidadeCadastro = -1;
        
        window.carregarLocalidadesCadastro = function() {
            const distrito = document.getElementById('distrito');
            const inputLocalidade = document.getElementById('localidade');
            
            if (!distrito || !inputLocalidade) return;
            
            if (!distrito.value) {
                inputLocalidade.placeholder = 'Digite o nome da localidade...';
                localidadesDisponiveisCadastro = [];
                return;
            }
            
            inputLocalidade.placeholder = 'Digite o nome da localidade...';
            
            fetch(`gestao_escolas.php?acao=buscar_localidades_distrito&distrito=${encodeURIComponent(distrito.value)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        window.localidadesDisponiveisCadastro = data.localidades;
                    } else {
                        window.localidadesDisponiveisCadastro = [];
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    window.localidadesDisponiveisCadastro = [];
                });
        };
        
        // Funções para autocomplete de localidade (Edição) - Definir no escopo global
        window.localidadesDisponiveisEdicao = [];
        window.filteredLocalidadesEdicao = [];
        window.selectedIndexLocalidadeEdicao = -1;
        
        window.carregarLocalidadesEdicao = function() {
            const distrito = document.getElementById('edit_distrito');
            const inputLocalidade = document.getElementById('edit_localidade');
            
            if (!distrito || !inputLocalidade) return;
            
            if (!distrito.value) {
                inputLocalidade.placeholder = 'Digite o nome da localidade...';
                localidadesDisponiveisEdicao = [];
                return;
            }
            
            inputLocalidade.placeholder = 'Digite o nome da localidade...';
            
            fetch(`gestao_escolas.php?acao=buscar_localidades_distrito&distrito=${encodeURIComponent(distrito.value)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        window.localidadesDisponiveisEdicao = data.localidades;
                    } else {
                        window.localidadesDisponiveisEdicao = [];
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    window.localidadesDisponiveisEdicao = [];
                });
        };
        
        // Função para fechar modal de exclusão de escola (definir no escopo global)
        window.fecharModalExclusaoEscola = function() {
            const modal = document.getElementById('modalExclusaoEscola');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        };
        
        // Fechar modal clicando fora dele
        document.addEventListener('DOMContentLoaded', function() {
            const modalExclusao = document.getElementById('modalExclusaoEscola');
            if (modalExclusao) {
                modalExclusao.addEventListener('click', function(e) {
                    if (e.target === this) {
                        window.fecharModalExclusaoEscola();
                    }
                });
            }
        });
        
        // Função para buscar gestores
        function buscarGestores(termo) {
            if (termo.length < 2) {
                document.getElementById('gestor_results').classList.add('hidden');
                return;
            }
            
            fetch(`../../Controllers/gestao/GestorController.php?busca=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    const results = document.getElementById('gestor_results');
                    results.innerHTML = '';
                    
                    if (data.length === 0) {
                        results.innerHTML = '<div class="p-3 text-sm text-gray-500">Nenhum gestor encontrado</div>';
                    } else {
                        data.forEach(gestor => {
                            const div = document.createElement('div');
                            div.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0';
                            div.innerHTML = `
                                <div class="font-medium text-gray-900">${gestor.nome}</div>
                                <div class="text-sm text-gray-500">${gestor.email}</div>
                            `;
                            div.onclick = () => selecionarGestor(gestor);
                            results.appendChild(div);
                        });
                    }
                    
                    results.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erro ao buscar gestores:', error);
                });
        }
        
        // Função para selecionar gestor
        function selecionarGestor(gestor) {
            document.getElementById('gestor_id').value = gestor.id;
            document.getElementById('gestor_search').value = gestor.nome; // Mostrar o nome no input
            document.getElementById('gestor_nome_selecionado').textContent = gestor.nome;
            document.getElementById('gestor_email_selecionado').textContent = gestor.email;
            document.getElementById('gestor_results').classList.add('hidden');
            document.getElementById('gestor_selected').classList.remove('hidden');
        }
        
        // Função para remover gestor selecionado
        function removerGestor() {
            document.getElementById('gestor_id').value = '';
            document.getElementById('gestor_search').value = '';
            document.getElementById('gestor_selected').classList.add('hidden');
        }
        
        // Funções do Modal de Edição (já definida acima no escopo global)
        // A função abrirModalEdicaoEscola já está definida no início do script
        
        // Função para fechar modal de edição (definir no escopo global)
        window.fecharModalEdicaoEscola = function() {
            const modal = document.getElementById('modalEdicaoEscola');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                
                // Resetar estado dos botões
                if (typeof desabilitarBotoesSalvar === 'function') {
                    desabilitarBotoesSalvar();
                }
                
                // Resetar seleção de professores
                if (typeof ocultarAdicionarProfessores === 'function') {
                    ocultarAdicionarProfessores();
                }
                
                // Voltar para a primeira aba
                if (typeof mostrarAbaEdicao === 'function') {
                    mostrarAbaEdicao('dados-basicos');
                }
            }
        };

        function carregarProfessoresEscola(escolaId) {
            fetch(`../../Controllers/gestao/EscolaController.php?acao=buscar_professores&escola_id=${escolaId}`)
                .then(response => response.json())
                .then(data => {
                    const listaProfessores = document.getElementById('lista-professores');
                    
                    if (data.success && data.professores && data.professores.length > 0) {
                        let html = '';
                        data.professores.forEach(professor => {
                            // Gerar iniciais do nome
                            const iniciais = professor.nome.split(' ').map(n => n.charAt(0)).join('').toUpperCase().substring(0, 2);
                            
                            html += `
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow duration-200 mb-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-14 h-14 bg-gradient-to-br from-primary-green to-green-600 rounded-full flex items-center justify-center shadow-lg">
                                                <span class="text-white font-bold text-lg">${iniciais}</span>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="font-semibold text-gray-900 text-lg">${professor.nome}</h5>
                                                <p class="text-sm text-gray-600 mb-1">${professor.email || 'Sem e-mail'}</p>
                                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                    <span>📞 ${professor.telefone || 'Sem telefone'}</span>
                                                    <span>📚 ${professor.disciplina || 'Sem disciplina'}</span>
                                                    ${professor.matricula ? `<span>🎓 Matrícula: ${professor.matricula}</span>` : ''}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button type="button" onclick="editarProfessor(${professor.id})" 
                                                    class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 hover:bg-blue-100 hover:border-blue-300 rounded-lg transition-all duration-200 flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                <span>Editar</span>
                                            </button>
                                            <button type="button" onclick="removerProfessorEscola(${professor.id}, '${professor.nome}')" 
                                                    class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 hover:bg-red-100 hover:border-red-300 rounded-lg transition-all duration-200 flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                <span>Remover</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        listaProfessores.innerHTML = html;
                    } else {
                        listaProfessores.innerHTML = `
                            <div class="text-center py-8">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Esta escola não possui professores</h4>
                                <p class="text-gray-600 mb-4">Nenhum professor foi cadastrado nesta escola ainda.</p>
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-sm text-blue-800">
                                            <strong>Nota:</strong> Para adicionar professores, use o botão "Adicionar Professor" acima.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar professores:', error);
                    document.getElementById('lista-professores').innerHTML = `
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto mb-4 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <h4 class="text-lg font-semibold text-red-900 mb-2">Erro ao carregar professores</h4>
                            <p class="text-red-600">Não foi possível carregar a lista de professores.</p>
                        </div>
                    `;
                });
        }

        function editarProfessor(professorId) {
            // Implementar modal de edição de professor
            alert(`Editar professor ID: ${professorId}\n\nFuncionalidade será implementada em breve.`);
        }

        function removerProfessorEscola(professorId, nomeProfessor) {
            if (confirm(`Tem certeza que deseja remover o professor "${nomeProfessor}" desta escola?`)) {
                fetch('../../Controllers/gestao/EscolaController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `acao=remover_professor&professor_id=${professorId}&escola_id=${document.getElementById('edit_escola_id').value}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recarregar lista de professores
                        const escolaId = document.getElementById('edit_escola_id').value;
                        carregarProfessoresEscola(escolaId);
                        
                        // Mostrar mensagem de sucesso
                        alert('Professor removido com sucesso!');
                    } else {
                        alert('Erro ao remover professor: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao remover professor.');
                });
            }
        }
        
        // Funções para Modal de Sucesso
        function mostrarModalSucesso() {
            const modal = document.getElementById('modalSucesso');
            modal.classList.remove('hidden');
            
            // Fechar automaticamente após 3 segundos
            setTimeout(() => {
                window.fecharModalSucesso();
            }, 3000);
        }
        
        // Função para fechar modal de sucesso (definir no escopo global)
        window.fecharModalSucesso = function() {
            const modal = document.getElementById('modalSucesso');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                // Recarregar a página após fechar o modal
                window.location.reload();
            }
        };
        
        // Variável global para armazenar dados originais
        let dadosOriginaisEscola = {};

        function carregarDadosEscola(id) {
            if (!id) {
                console.error('ID da escola não informado');
                alert('Erro: ID da escola não informado.');
                return;
            }
            
            // Mostrar loading
            const loadingElement = document.getElementById('loading-escola');
            if (loadingElement) {
                loadingElement.classList.remove('hidden');
            }
            
            // Buscar dados da escola diretamente via PHP
            fetch(`../../Controllers/gestao/EscolaController.php?acao=buscar_escola&id=${encodeURIComponent(id)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            // Log para debug
                            console.log('Dados da escola recebidos:', data);
                            return data;
                        } catch (e) {
                            console.error('Erro ao fazer parse do JSON:', e);
                            console.error('Texto recebido:', text);
                            alert('Erro ao processar resposta do servidor. Verifique o console para mais detalhes.');
                            throw new Error('Resposta inválida do servidor: ' + text.substring(0, 200));
                        }
                    });
                })
                .then(data => {
                    
                    // Ocultar loading
                    if (loadingElement) {
                        loadingElement.classList.add('hidden');
                    }
                    
                    if (!data) {
                        console.error('Resposta vazia do servidor');
                        alert('Erro ao carregar dados da escola. Resposta vazia do servidor.');
                        return;
                    }
                    
                    if (!data.success) {
                        console.error('Erro na resposta:', data.message || 'Erro desconhecido');
                        
                        // Se a escola está inativa ou não encontrada, redirecionar para sem_acesso.php
                        if (data.escolaInativa === true || 
                            (data.message && (data.message.includes('Escola não encontrada') || data.message.includes('escola não encontrada')))) {
                            // Destruir sessão e redirecionar
                            window.location.href = '../../Views/auth/sem_acesso.php';
                            return;
                        }
                        
                        alert('Erro ao carregar dados da escola: ' + (data.message || 'Erro desconhecido'));
                        return;
                    }
                    
                    if (!data.escola) {
                        console.error('Escola não encontrada nos dados');
                        // Se escola não encontrada, pode ser que foi desativada
                        // Redirecionar para página de sem acesso
                        window.location.href = '../../Views/auth/sem_acesso.php';
                        return;
                    }
                    const escola = data.escola;
                    
                    // Preencher campos básicos
                    document.getElementById('edit_nome').value = escola.nome || '';
                    document.getElementById('edit_email').value = escola.email || '';
                    document.getElementById('edit_cep').value = escola.cep || '';
                    document.getElementById('edit_qtd_salas').value = escola.qtd_salas || '';
                    document.getElementById('edit_codigo').value = escola.codigo || '';
                    document.getElementById('edit_cnpj').value = escola.cnpj || '';
                    
                    // Preencher nível de ensino
                    // Processar múltiplos níveis de ensino (SET retorna string separada por vírgula)
                    const editNivelEnsinoInfantil = document.getElementById('edit_nivel_ensino_infantil');
                    const editNivelEnsinoFundamental = document.getElementById('edit_nivel_ensino_fundamental');
                    
                    if (escola.nivel_ensino) {
                        // Converter string SET para array (ex: "EDUCACAO_INFANTIL,ENSINO_FUNDAMENTAL")
                        const niveis = escola.nivel_ensino.split(',').map(n => n.trim());
                        
                        if (editNivelEnsinoInfantil) {
                            editNivelEnsinoInfantil.checked = niveis.includes('EDUCACAO_INFANTIL');
                        }
                        if (editNivelEnsinoFundamental) {
                            editNivelEnsinoFundamental.checked = niveis.includes('ENSINO_FUNDAMENTAL');
                        }
                    } else {
                        // Padrão: apenas Ensino Fundamental
                        if (editNivelEnsinoInfantil) {
                            editNivelEnsinoInfantil.checked = false;
                        }
                        if (editNivelEnsinoFundamental) {
                            editNivelEnsinoFundamental.checked = true;
                        }
                    }
                    
                    // Preencher campos de endereço
                    // O banco tem campos separados: endereco, numero, complemento, bairro
                    if (escola.endereco) {
                        // Se o endereço está tudo junto (formato antigo), tentar separar
                        if (escola.endereco.includes(', ')) {
                            const enderecoParts = escola.endereco.split(', ');
                            document.getElementById('edit_logradouro').value = enderecoParts[0] || '';
                            // Se não tem campo numero separado, tentar pegar da string
                            if (!escola.numero && enderecoParts[1]) {
                                document.getElementById('edit_numero').value = enderecoParts[1] || '';
                            }
                        } else {
                            document.getElementById('edit_logradouro').value = escola.endereco || '';
                        }
                    } else {
                        document.getElementById('edit_logradouro').value = '';
                    }
                    
                    // Campos separados do banco
                    document.getElementById('edit_numero').value = escola.numero || '';
                    document.getElementById('edit_complemento').value = escola.complemento || '';
                    document.getElementById('edit_bairro').value = escola.bairro || '';
                    
                    // Preencher telefones (campos separados do banco)
                    document.getElementById('edit_telefone_fixo').value = escola.telefone || '';
                    document.getElementById('edit_telefone_movel').value = escola.telefone_secundario || '';
                    
                    // Preencher site
                    document.getElementById('edit_site').value = escola.site || '';
                    
                    // Preencher distrito e localidade
                    if (escola.distrito) {
                        document.getElementById('edit_distrito').value = escola.distrito;
                        // Verificar se a função existe antes de chamar
                        if (typeof window.carregarLocalidadesEdicao === 'function') {
                            window.carregarLocalidadesEdicao();
                        } else if (typeof carregarLocalidadesEdicao === 'function') {
                            carregarLocalidadesEdicao();
                        }
                    } else {
                        document.getElementById('edit_distrito').value = '';
                    }
                    
                    // Verificar se existe localidade_escola (campo varchar) ou usar null
                    if (escola.localidade_escola) {
                        document.getElementById('edit_localidade').value = escola.localidade_escola;
                    } else {
                        document.getElementById('edit_localidade').value = '';
                    }
                    
                    // Extrair dados do campo obs para preencher campos adicionais
                    if (escola.obs) {
                        const obs = escola.obs;
                        
                        // Extrair INEP da escola
                        const inepMatch = obs.match(/INEP Escola:\s*([^|]+)/);
                        if (inepMatch) {
                            document.getElementById('edit_inep').value = inepMatch[1].trim();
                        } else {
                            document.getElementById('edit_inep').value = '';
                        }
                        
                        // Extrair nome curto (assumindo que está no início do nome)
                        const nomeCurto = escola.nome ? escola.nome.split(' ').slice(0, 3).join(' ') : '';
                        document.getElementById('edit_nome_curto').value = nomeCurto;
                        
                        // Extrair tipo de escola
                        const tipoMatch = obs.match(/Tipo:\s*([^|]+)/);
                        if (tipoMatch) {
                            document.getElementById('edit_tipo_escola').value = tipoMatch[1].trim();
                        } else {
                            document.getElementById('edit_tipo_escola').value = 'NORMAL';
                        }
                    } else {
                        // Se não tem obs, limpar campos extras
                        document.getElementById('edit_nome_curto').value = '';
                        document.getElementById('edit_inep').value = '';
                        document.getElementById('edit_tipo_escola').value = 'NORMAL';
                    }
                    
                    // Preencher dados do gestor usando os dados que vêm do banco
                    if (escola.gestor_nome || escola.gestor_email || escola.gestor_id) {
                        // Mostrar seção do gestor
                        document.getElementById('gestor-atual-section').classList.remove('hidden');
                        document.getElementById('nenhum-gestor-section').classList.add('hidden');
                        
                        // Preencher dados do gestor
                        if (escola.gestor_nome) {
                            document.getElementById('gestor-atual-nome').textContent = escola.gestor_nome;
                            
                            // Gerar iniciais
                            const iniciais = escola.gestor_nome.split(' ').map(n => n.charAt(0)).join('').toUpperCase().substring(0, 2);
                            document.getElementById('gestor-atual-iniciais').textContent = iniciais;
                        }
                        
                        if (escola.gestor_cpf) {
                            document.getElementById('gestor-atual-cpf').textContent = 'CPF: ' + escola.gestor_cpf;
                        } else {
                            document.getElementById('gestor-atual-cpf').textContent = '';
                        }
                        
                        if (escola.gestor_email) {
                            document.getElementById('gestor-atual-email').textContent = escola.gestor_email;
                        } else {
                            document.getElementById('gestor-atual-email').textContent = 'Sem e-mail';
                        }
                        
                        if (escola.gestor_cargo) {
                            document.getElementById('gestor-atual-cargo').textContent = 'Cargo: ' + escola.gestor_cargo;
                        } else {
                            document.getElementById('gestor-atual-cargo').textContent = '';
                        }
                    } else {
                        // Não há gestor
                        document.getElementById('gestor-atual-section').classList.add('hidden');
                        document.getElementById('nenhum-gestor-section').classList.remove('hidden');
                    }
                    
                    // Armazenar dados originais para comparação (usar valores dos campos do formulário)
                    dadosOriginaisEscola = {
                        nome: document.getElementById('edit_nome').value || '',
                        inep: document.getElementById('edit_inep').value || '',
                        nome_curto: document.getElementById('edit_nome_curto').value || '',
                        codigo: document.getElementById('edit_codigo').value || '',
                        cnpj: document.getElementById('edit_cnpj').value || '',
                        tipo_escola: document.getElementById('edit_tipo_escola').value || '',
                        qtd_salas: document.getElementById('edit_qtd_salas').value || '',
                        nivel_ensino_infantil: document.getElementById('edit_nivel_ensino_infantil').checked ? 'EDUCACAO_INFANTIL' : '',
                        nivel_ensino_fundamental: document.getElementById('edit_nivel_ensino_fundamental').checked ? 'ENSINO_FUNDAMENTAL' : '',
                        cep: document.getElementById('edit_cep').value || '',
                        logradouro: document.getElementById('edit_logradouro').value || '',
                        numero: document.getElementById('edit_numero').value || '',
                        complemento: document.getElementById('edit_complemento').value || '',
                        bairro: document.getElementById('edit_bairro').value || '',
                        telefone_fixo: document.getElementById('edit_telefone_fixo').value || '',
                        telefone_movel: document.getElementById('edit_telefone_movel').value || '',
                        email: document.getElementById('edit_email').value || '',
                        site: document.getElementById('edit_site').value || ''
                    };
                    
                    // Carregar professores da escola
                    carregarProfessoresEscola(id);
                    
                    // Configurar monitoramento de mudanças
                    configurarMonitoramentoMudancas();
                    
                    // Desabilitar botões inicialmente
                    desabilitarBotoesSalvar();
                })
                .catch(err => {
                    console.error('Erro ao carregar dados da escola:', err);
                    
                    // Ocultar loading
                    const loadingElement = document.getElementById('loading-escola');
                    if (loadingElement) {
                        loadingElement.classList.add('hidden');
                    }
                    
                    alert('Erro ao carregar dados da escola. Tente novamente.');
                });
        }
        
        function mostrarAbaEdicao(abaId) {
            // Esconder todas as abas
            document.querySelectorAll('.aba-edicao').forEach(aba => {
                aba.classList.add('hidden');
            });
            
            // Remover classe ativa de todos os botões
            document.querySelectorAll('.tab-edicao').forEach(btn => {
                btn.classList.remove('active', 'border-primary-green', 'text-primary-green');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Mostrar a aba selecionada
            document.getElementById(`aba-${abaId}`).classList.remove('hidden');
            
            // Adicionar classe ativa ao botão clicado
            const botaoAtivo = document.getElementById(`tab-${abaId}`);
            botaoAtivo.classList.add('active', 'border-primary-green', 'text-primary-green');
            botaoAtivo.classList.remove('border-transparent', 'text-gray-500');
            
            // Se a aba for "corpo-docente", garantir que os professores sejam carregados
            if (abaId === 'corpo-docente') {
                const escolaId = document.getElementById('edit_escola_id').value;
                if (escolaId) {
                    carregarProfessoresEscola(escolaId);
                }
            }
        }
        
        // Função para configurar monitoramento de mudanças nos campos
        function configurarMonitoramentoMudancas() {
            const campos = [
                'edit_nome', 'edit_inep', 'edit_nome_curto', 'edit_codigo', 'edit_cnpj',
                'edit_tipo_escola', 'edit_qtd_salas', 'edit_nivel_ensino_infantil', 'edit_nivel_ensino_fundamental',
                'edit_cep', 'edit_logradouro', 'edit_numero', 'edit_complemento', 'edit_bairro',
                'edit_telefone_fixo', 'edit_telefone_movel', 'edit_email', 'edit_site'
            ];
            
            campos.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo) {
                    // Remove listeners anteriores
                    campo.removeEventListener('input', verificarMudancas);
                    campo.removeEventListener('change', verificarMudancas);
                    
                    // Adiciona novos listeners
                    if (campo.type === 'checkbox') {
                        campo.addEventListener('change', verificarMudancas);
                    } else {
                    campo.addEventListener('input', verificarMudancas);
                    }
                }
            });
        }
        
        // Função para verificar se houve mudanças
        function verificarMudancas() {
            const camposAtuais = {
                nome: document.getElementById('edit_nome').value || '',
                inep: document.getElementById('edit_inep').value || '',
                nome_curto: document.getElementById('edit_nome_curto').value || '',
                codigo: document.getElementById('edit_codigo').value || '',
                cnpj: document.getElementById('edit_cnpj').value || '',
                tipo_escola: document.getElementById('edit_tipo_escola').value || '',
                qtd_salas: document.getElementById('edit_qtd_salas').value || '',
                nivel_ensino_infantil: document.getElementById('edit_nivel_ensino_infantil').checked ? 'EDUCACAO_INFANTIL' : '',
                nivel_ensino_fundamental: document.getElementById('edit_nivel_ensino_fundamental').checked ? 'ENSINO_FUNDAMENTAL' : '',
                cep: document.getElementById('edit_cep').value || '',
                logradouro: document.getElementById('edit_logradouro').value || '',
                numero: document.getElementById('edit_numero').value || '',
                complemento: document.getElementById('edit_complemento').value || '',
                bairro: document.getElementById('edit_bairro').value || '',
                telefone_fixo: document.getElementById('edit_telefone_fixo').value || '',
                telefone_movel: document.getElementById('edit_telefone_movel').value || '',
                email: document.getElementById('edit_email').value || '',
                site: document.getElementById('edit_site').value || ''
            };
            
            // Comparar com dados originais
            let houveAlteracao = false;
            for (let campo in dadosOriginaisEscola) {
                if (camposAtuais[campo] !== dadosOriginaisEscola[campo]) {
                    houveAlteracao = true;
                    break;
                }
            }
            
            // Obter botão de salvar da aba Dados Básicos
            const botaoSalvarDadosBasicos = document.getElementById('btn-salvar-dados-basicos');
            
            if (houveAlteracao) {
                habilitarBotaoSalvar(botaoSalvarDadosBasicos);
            } else {
                desabilitarBotaoSalvar(botaoSalvarDadosBasicos);
            }
        }
        
        // Função para habilitar botão de salvar
        function habilitarBotaoSalvar(botao) {
            if (botao) {
                botao.disabled = false;
                botao.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-primary-green');
                botao.classList.add('bg-green-600', 'hover:bg-green-700', 'shadow-lg', 'transform', 'hover:scale-105', 'transition-all');
                botao.style.cursor = 'pointer';
            }
        }
        
        // Função para desabilitar botão de salvar
        function desabilitarBotaoSalvar(botao) {
            if (botao) {
                botao.disabled = true;
                botao.classList.remove('bg-green-600', 'hover:bg-green-700', 'shadow-lg', 'transform', 'hover:scale-105');
                botao.classList.add('opacity-50', 'cursor-not-allowed', 'bg-primary-green');
                botao.style.cursor = 'not-allowed';
            }
        }
        
        // Função para desabilitar todos os botões de salvar
        function desabilitarBotoesSalvar() {
            const botaoDadosBasicos = document.getElementById('btn-salvar-dados-basicos');
            desabilitarBotaoSalvar(botaoDadosBasicos);
            
            // Ocultar botões da aba Corpo Docente inicialmente
            const botoesCorpoDocente = document.getElementById('botoes-corpo-docente');
            if (botoesCorpoDocente) {
                botoesCorpoDocente.classList.add('hidden');
            }
        }
        
        function mostrarAdicionarProfessores() {
            // Mostrar seção de adicionar professores
            document.getElementById('secao-adicionar-professores').classList.remove('hidden');
            carregarDisciplinas();
            carregarProfessoresDisponiveisEdicao();
        }

        function carregarDisciplinas() {
            const selectDisciplinas = document.getElementById('filtroDisciplinaEdicao');
            
            // Limpar opções existentes (exceto "Todas as disciplinas")
            selectDisciplinas.innerHTML = '<option value="">Todas as disciplinas</option>';
            
            // Aqui você faria a requisição para o backend
            // fetch('buscar_disciplinas.php')
            //     .then(response => response.json())
            //     .then(disciplinas => {
            //         disciplinas.forEach(disciplina => {
            //             const option = document.createElement('option');
            //             option.value = disciplina.id;
            //             option.textContent = disciplina.nome;
            //             selectDisciplinas.appendChild(option);
            //         });
            //     })
            //     .catch(error => {
            //         console.error('Erro ao carregar disciplinas:', error);
            //     });
        }

        function ocultarAdicionarProfessores() {
            // Ocultar seção de adicionar professores
            document.getElementById('secao-adicionar-professores').classList.add('hidden');
            resetarSelecaoProfessores();
        }

        function resetarSelecaoProfessores() {
            // Reset form
            document.getElementById('buscaProfessorEdicao').value = '';
            document.getElementById('filtroDisciplinaEdicao').value = '';
            document.getElementById('selecionarTodosEdicao').checked = false;
            
            // Clear selections
            document.querySelectorAll('.checkbox-professor-edicao').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Hide summary
            document.getElementById('resumoProfessoresSelecionadosEdicao').classList.add('hidden');
            
            // Ocultar botões de ação
            const botoesCorpoDocente = document.getElementById('botoes-corpo-docente');
            if (botoesCorpoDocente) {
                botoesCorpoDocente.classList.add('hidden');
            }
        }

        function carregarProfessoresDisponiveisEdicao() {
            const container = document.getElementById('listaProfessoresDisponiveisEdicao');
            container.innerHTML = '';

            // Mostrar loading
            container.innerHTML = `
                <div class="p-8 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green mx-auto mb-4"></div>
                    <p class="text-gray-600">Carregando professores disponíveis...</p>
                </div>
            `;

            // Buscar professores no backend
            fetch('../../Controllers/gestao/ProfessorController.php')
                .then(resp => resp.json())
                .then(data => {
                    if (data && data.status && Array.isArray(data.professores)) {
                        renderizarProfessores(data.professores);
                    } else {
                        container.innerHTML = `
                            <div class="p-8 text-center">
                                <p class="text-gray-600">Nenhum professor disponível</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar professores:', error);
                    container.innerHTML = `
                        <div class="p-8 text-center">
                            <p class="text-red-600">Erro ao carregar professores</p>
                        </div>
                    `;
                });
        }

        function renderizarProfessores(professores) {
            const container = document.getElementById('listaProfessoresDisponiveisEdicao');
            container.innerHTML = '';

            if (professores.length === 0) {
                container.innerHTML = `
                    <div class="p-8 text-center">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <p class="text-gray-600">Nenhum professor disponível</p>
                    </div>
                `;
                return;
            }

            professores.forEach(professor => {
                const professorCard = document.createElement('div');
                professorCard.className = 'p-4 border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200';
                professorCard.innerHTML = `
                    <div class="flex items-center space-x-4">
                        <input type="checkbox" class="checkbox-professor-edicao w-4 h-4 text-primary-green border-gray-300 rounded focus:ring-primary-green" 
                               data-professor-id="${professor.id}" data-professor-nome="${professor.nome}" data-professor-disciplina="${professor.disciplina || ''}">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="font-medium text-gray-900">${professor.nome}</h5>
                                    <p class="text-sm text-gray-600">${professor.disciplina ? obterNomeDisciplina(professor.disciplina) : 'Sem disciplina definida'}</p>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <p>${professor.email || 'Email não informado'}</p>
                                    <p>${professor.telefone || 'Telefone não informado'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(professorCard);
            });

            // Add event listeners
            configurarEventListenersProfessoresEdicao();
        }

        function obterNomeDisciplina(disciplina) {
            // Retorna o nome da disciplina como está no banco de dados
            // ou capitaliza a primeira letra se não houver mapeamento específico
            if (!disciplina) return 'Sem disciplina definida';
            return disciplina.charAt(0).toUpperCase() + disciplina.slice(1);
        }

        function configurarEventListenersProfessoresEdicao() {
            // Search functionality
            document.getElementById('buscaProfessorEdicao').addEventListener('input', filtrarProfessoresEdicao);
            document.getElementById('filtroDisciplinaEdicao').addEventListener('change', filtrarProfessoresEdicao);
            
            // Select all functionality
            document.getElementById('selecionarTodosEdicao').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.checkbox-professor-edicao');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                atualizarResumoProfessoresSelecionadosEdicao();
            });

            // Individual checkbox functionality
            document.querySelectorAll('.checkbox-professor-edicao').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    atualizarResumoProfessoresSelecionadosEdicao();
                    atualizarCheckboxSelecionarTodosEdicao();
                });
            });
        }

        function filtrarProfessoresEdicao() {
            const termoBusca = document.getElementById('buscaProfessorEdicao').value.toLowerCase();
            const filtroDisciplina = document.getElementById('filtroDisciplinaEdicao').value;
            const cardsProfessores = document.querySelectorAll('#listaProfessoresDisponiveisEdicao > div');

            cardsProfessores.forEach(card => {
                const nomeProfessor = card.querySelector('h5').textContent.toLowerCase();
                const disciplinaProfessor = card.querySelector('.checkbox-professor-edicao').dataset.professorDisciplina;
                
                const correspondeBusca = nomeProfessor.includes(termoBusca);
                const correspondeDisciplina = !filtroDisciplina || disciplinaProfessor === filtroDisciplina;
                
                if (correspondeBusca && correspondeDisciplina) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function atualizarResumoProfessoresSelecionadosEdicao() {
            const checkboxesSelecionados = document.querySelectorAll('.checkbox-professor-edicao:checked');
            const resumoDiv = document.getElementById('resumoProfessoresSelecionadosEdicao');
            const listaDiv = document.getElementById('listaProfessoresSelecionadosEdicao');
            
            // Obter container de botões da aba Corpo Docente
            const botoesCorpoDocente = document.getElementById('botoes-corpo-docente');
            const botaoSalvar = document.getElementById('btn-salvar-corpo-docente');

            if (checkboxesSelecionados.length > 0) {
                resumoDiv.classList.remove('hidden');
                // Converter NodeList para Array antes de usar map
                listaDiv.innerHTML = Array.from(checkboxesSelecionados).map(checkbox => 
                    `<span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs mr-2 mb-1">${checkbox.dataset.professorNome}</span>`
                ).join('');
                
                // Mostrar botões de salvar quando há professores selecionados
                if (botoesCorpoDocente) {
                    botoesCorpoDocente.classList.remove('hidden');
                    habilitarBotaoSalvar(botaoSalvar);
                }
            } else {
                resumoDiv.classList.add('hidden');
                
                // Ocultar botões quando não há professores selecionados
                if (botoesCorpoDocente) {
                    botoesCorpoDocente.classList.add('hidden');
                }
            }
        }

        function atualizarCheckboxSelecionarTodosEdicao() {
            const todosCheckboxes = document.querySelectorAll('.checkbox-professor-edicao');
            const checkboxesMarcados = document.querySelectorAll('.checkbox-professor-edicao:checked');
            const checkboxSelecionarTodos = document.getElementById('selecionarTodosEdicao');
            
            checkboxSelecionarTodos.checked = todosCheckboxes.length === checkboxesMarcados.length;
        }

        function adicionarProfessoresSelecionadosEdicao() {
            const checkboxesSelecionados = document.querySelectorAll('.checkbox-professor-edicao:checked');
            
            if (checkboxesSelecionados.length === 0) {
                alert('Por favor, selecione pelo menos um professor.');
                return;
            }

            const professoresSelecionados = Array.from(checkboxesSelecionados).map(checkbox => ({
                id: checkbox.dataset.professorId,
                nome: checkbox.dataset.professorNome,
                disciplina: checkbox.dataset.professorDisciplina
            }));

            // Aqui você faria a requisição para o backend
            console.log('Professores selecionados:', professoresSelecionados);
            
            // Simular sucesso
            alert(`${professoresSelecionados.length} professor(es) adicionado(s) com sucesso!`);
            ocultarAdicionarProfessores();
            
            // Recarregar a lista de professores da escola
            // carregarProfessoresEscola();
        }

        // Função para processar professores selecionados quando salvar
        function processarProfessoresSelecionados() {
            const checkboxesSelecionados = document.querySelectorAll('.checkbox-professor-edicao:checked');
            
            if (checkboxesSelecionados.length > 0) {
                const professoresSelecionados = Array.from(checkboxesSelecionados).map(checkbox => ({
                    id: checkbox.dataset.professorId,
                    nome: checkbox.dataset.professorNome,
                    disciplina: checkbox.dataset.professorDisciplina
                }));

                console.log('Professores a serem adicionados:', professoresSelecionados);
                // Aqui você faria a requisição para o backend para adicionar os professores
                
                return professoresSelecionados;
            }
            
            return [];
        }

        // Funções de CEP para edição
        function formatarCEPEdicao(input) {
            let valor = input.value.replace(/\D/g, '');
            // Limitar a 8 dígitos
            if (valor.length > 8) {
                valor = valor.substring(0, 8);
            }
            // Aplicar máscara: 00000-000
            if (valor.length > 5) {
                valor = valor.replace(/(\d{5})(\d{0,3})/, '$1-$2');
            }
            input.value = valor;
            
            // Se o CEP tiver 9 caracteres (8 dígitos + hífen), buscar automaticamente
            if (input.value.length === 9) {
                buscarCEPEdicao(input.value);
            }
        }

        async function buscarCEPEdicao(cep) {
            const resultadoCEP = document.getElementById('resultadoCEPEdicao');
            
            if (!cep) {
                if (resultadoCEP) {
                    resultadoCEP.classList.add('hidden');
                }
                return;
            }

            // Limpar CEP para busca
            const cepLimpo = cep.replace(/\D/g, '');
            
            if (cepLimpo.length !== 8) {
                if (resultadoCEP) {
                    resultadoCEP.innerHTML = '<span class="text-red-600">CEP deve ter 8 dígitos</span>';
                    resultadoCEP.classList.remove('hidden');
                }
                return;
            }

            try {
                if (resultadoCEP) {
                    resultadoCEP.innerHTML = '<span class="text-blue-600">Buscando CEP...</span>';
                    resultadoCEP.classList.remove('hidden');
                }

                const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                const data = await response.json();

                if (data.erro) {
                    if (resultadoCEP) {
                        resultadoCEP.innerHTML = '<span class="text-red-600">CEP não encontrado</span>';
                    }
                } else {
                    // Preencher campos automaticamente
                    const logradouroField = document.getElementById('edit_logradouro');
                    const bairroField = document.getElementById('edit_bairro');
                    
                    if (logradouroField) {
                        logradouroField.value = data.logradouro || '';
                    }
                    if (bairroField) {
                        bairroField.value = data.bairro || '';
                    }
                    
                    if (resultadoCEP) {
                        resultadoCEP.innerHTML = `
                            <span class="text-green-600">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Endereço preenchido: ${data.logradouro || ''} - ${data.bairro || ''}, ${data.localidade || ''}/${data.uf || ''}
                            </span>
                        `;
                        // Ocultar mensagem após 5 segundos
                        setTimeout(() => {
                            if (resultadoCEP) {
                                resultadoCEP.classList.add('hidden');
                            }
                        }, 5000);
                    }
                }
            } catch (error) {
                if (resultadoCEP) {
                    resultadoCEP.innerHTML = '<span class="text-red-600">Erro ao buscar CEP. Tente novamente.</span>';
                }
                console.error('Erro na busca do CEP:', error);
            }
        }

        // Funções de CEP para o formulário de cadastro
        function formatarCEPCadastro(input) {
            let valor = input.value.replace(/\D/g, '');
            // Limitar a 8 dígitos
            if (valor.length > 8) {
                valor = valor.substring(0, 8);
            }
            // Aplicar máscara: 00000-000
            if (valor.length > 5) {
                valor = valor.replace(/(\d{5})(\d{0,3})/, '$1-$2');
            }
            input.value = valor;
            
            // Se o CEP tiver 9 caracteres (8 dígitos + hífen), buscar automaticamente
            if (input.value.length === 9) {
                buscarCEPCadastro(input.value);
            }
        }

        // Máscaras para os campos do gestor
        function formatarCPF(input) {
            let valor = input.value.replace(/\D/g, '');
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
            valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            input.value = valor;
        }

        function formatarCNPJ(input) {
            let valor = input.value.replace(/\D/g, '');
            // Limitar a 14 dígitos
            if (valor.length > 14) {
                valor = valor.substring(0, 14);
            }
            // Aplicar máscara: 00.000.000/0000-00
            if (valor.length <= 2) {
                input.value = valor;
            } else if (valor.length <= 5) {
                input.value = valor.replace(/(\d{2})(\d+)/, '$1.$2');
            } else if (valor.length <= 8) {
                input.value = valor.replace(/(\d{2})(\d{3})(\d+)/, '$1.$2.$3');
            } else if (valor.length <= 12) {
                input.value = valor.replace(/(\d{2})(\d{3})(\d{3})(\d+)/, '$1.$2.$3/$4');
            } else {
                input.value = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }
        }

        function formatarTelefone(input) {
            let valor = input.value.replace(/\D/g, '');
            if (valor.length <= 10) {
                valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
                valor = valor.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
                valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
            }
            input.value = valor;
        }

        async function buscarCEPCadastro(cep) {
            const resultadoCEP = document.getElementById('resultadoCEPCadastro');
            
            if (!cep) {
                if (resultadoCEP) {
                    resultadoCEP.classList.add('hidden');
                }
                return;
            }

            // Limpar CEP para busca
            const cepLimpo = cep.replace(/\D/g, '');
            
            if (cepLimpo.length !== 8) {
                if (resultadoCEP) {
                    resultadoCEP.innerHTML = '<span class="text-red-600">CEP deve ter 8 dígitos</span>';
                    resultadoCEP.classList.remove('hidden');
                }
                return;
            }

            try {
                if (resultadoCEP) {
                    resultadoCEP.innerHTML = '<span class="text-blue-600">Buscando CEP...</span>';
                    resultadoCEP.classList.remove('hidden');
                }

                const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                const data = await response.json();

                if (data.erro) {
                    if (resultadoCEP) {
                        resultadoCEP.innerHTML = '<span class="text-red-600">CEP não encontrado</span>';
                    }
                } else {
                    // Preencher campos automaticamente
                    const logradouroField = document.getElementById('logradouro');
                    const bairroField = document.getElementById('bairro');
                    
                    if (logradouroField) {
                        logradouroField.value = data.logradouro || '';
                    }
                    if (bairroField) {
                        bairroField.value = data.bairro || '';
                    }
                    
                    if (resultadoCEP) {
                        resultadoCEP.innerHTML = `
                            <span class="text-green-600">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Endereço preenchido automaticamente: ${data.logradouro || ''} - ${data.bairro || ''}, ${data.localidade || ''}/${data.uf || ''}
                            </span>
                        `;
                        // Ocultar mensagem após 5 segundos
                        setTimeout(() => {
                            if (resultadoCEP) {
                                resultadoCEP.classList.add('hidden');
                            }
                        }, 5000);
                    }
                }
            } catch (error) {
                if (resultadoCEP) {
                    resultadoCEP.innerHTML = '<span class="text-red-600">Erro ao buscar CEP. Tente novamente.</span>';
                }
                console.error('Erro na busca do CEP:', error);
            }
        }

        // ===== FUNÇÕES PARA BUSCA E SELEÇÃO DE GESTOR NO CADASTRO =====
        let sugestaoAtivaGestorCadastro = -1;

        function buscarGestorCadastro(termo) {
            const sugestoes = document.getElementById('sugestoes_gestor_cadastro');
            const termoLower = termo.toLowerCase().trim();
            
            // Limpar seleção anterior
            document.getElementById('gestor_id').value = '';
            limparCamposGestor();
            
            if (termo.length < 2) {
                sugestoes.classList.add('hidden');
                return;
            }
            
            fetch(`../../Controllers/gestao/GestorController.php?busca=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status && data.gestores && data.gestores.length > 0) {
                        let htmlSugestoes = '';
                        data.gestores.forEach((gestor, index) => {
                            const nomeGestor = gestor.nome || '';
                            const termoRegex = new RegExp(`(${termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                            const nomeDestacado = nomeGestor.replace(termoRegex, '<span style="color: #059669; font-weight: bold;">$1</span>');
                            
                            htmlSugestoes += `
                                <div class="sugestao-item px-4 py-2 cursor-pointer hover:bg-gray-50 border-b border-gray-100 last:border-b-0 transition-all duration-200" 
                                     data-index="${index}" 
                                     data-id="${gestor.gestor_id}" 
                                     data-nome="${nomeGestor}"
                                     onclick="selecionarGestorCadastro('${gestor.gestor_id}')">
                                    <div class="font-medium text-gray-900">${nomeDestacado}</div>
                                    <div class="text-sm text-gray-500">${gestor.email || 'Sem e-mail'} ${gestor.cpf ? '| CPF: ' + gestor.cpf : ''}</div>
                                </div>
                            `;
                        });
                        sugestoes.innerHTML = htmlSugestoes;
                        sugestoes.classList.remove('hidden');
                        sugestaoAtivaGestorCadastro = -1;
                    } else {
                        sugestoes.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">Nenhum gestor encontrado</div>';
                        sugestoes.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar gestores:', error);
                    sugestoes.innerHTML = '<div class="p-3 text-sm text-red-500 text-center">Erro ao buscar gestores</div>';
                    sugestoes.classList.remove('hidden');
                });
        }

        function selecionarGestorCadastro(gestorId) {
            document.getElementById('gestor_id').value = gestorId;
            document.getElementById('sugestoes_gestor_cadastro').classList.add('hidden');
            
            // Buscar dados completos do gestor
            fetch(`../../Controllers/gestao/GestorController.php?acao=buscar_por_id&gestor_id=${gestorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status && data.gestor) {
                        preencherCamposGestor(data.gestor);
                    } else {
                        alert('Erro ao carregar dados do gestor.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar dados do gestor:', error);
                    alert('Erro ao carregar dados do gestor.');
                });
        }

        function preencherCamposGestor(gestor) {
            // Preencher campos com dados do gestor ou "Não informado"
            document.getElementById('gestor_cpf').value = gestor.cpf ? formatarCPFTexto(gestor.cpf) : 'Não informado';
            document.getElementById('gestor_nome').value = gestor.nome || 'Não informado';
            document.getElementById('gestor_email').value = gestor.email || 'Não informado';
            document.getElementById('gestor_telefone').value = gestor.telefone ? formatarTelefoneTexto(gestor.telefone) : 'Não informado';
            document.getElementById('gestor_cargo').value = gestor.cargo || 'Não informado';
            document.getElementById('gestor_tipo_acesso').value = 'Não informado';
            document.getElementById('gestor_criterio_acesso').value = 'Não informado';
            
            // Atualizar campo de busca com o nome do gestor
            document.getElementById('buscar_gestor_cadastro').value = gestor.nome || '';
        }

        function limparCamposGestor() {
            document.getElementById('gestor_cpf').value = '';
            document.getElementById('gestor_nome').value = '';
            document.getElementById('gestor_email').value = '';
            document.getElementById('gestor_telefone').value = '';
            document.getElementById('gestor_cargo').value = '';
            document.getElementById('gestor_tipo_acesso').value = '';
            document.getElementById('gestor_criterio_acesso').value = '';
        }

        function formatarCPFTexto(cpf) {
            if (!cpf) return '';
            const cpfLimpo = cpf.replace(/\D/g, '');
            if (cpfLimpo.length === 11) {
                return cpfLimpo.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            }
            return cpf;
        }

        function formatarTelefoneTexto(telefone) {
            if (!telefone) return '';
            const telLimpo = telefone.replace(/\D/g, '');
            if (telLimpo.length === 11) {
                return telLimpo.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (telLimpo.length === 10) {
                return telLimpo.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            }
            return telefone;
        }

        function mostrarSugestoesGestorCadastro() {
            const termo = document.getElementById('buscar_gestor_cadastro').value;
            if (termo.length >= 2) {
                buscarGestorCadastro(termo);
            }
        }

        function esconderSugestoesGestorCadastro() {
            setTimeout(() => {
                document.getElementById('sugestoes_gestor_cadastro').classList.add('hidden');
            }, 200);
        }

        function navegarSugestoesGestorCadastro(event) {
            const sugestoes = document.getElementById('sugestoes_gestor_cadastro');
            const itens = sugestoes.querySelectorAll('.sugestao-item');
            
            if (itens.length === 0) return;
            
            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    sugestaoAtivaGestorCadastro = Math.min(sugestaoAtivaGestorCadastro + 1, itens.length - 1);
                    atualizarDestaqueGestorCadastro();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    sugestaoAtivaGestorCadastro = Math.max(sugestaoAtivaGestorCadastro - 1, -1);
                    atualizarDestaqueGestorCadastro();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (sugestaoAtivaGestorCadastro >= 0 && sugestaoAtivaGestorCadastro < itens.length) {
                        const item = itens[sugestaoAtivaGestorCadastro];
                        const id = item.getAttribute('data-id');
                        selecionarGestorCadastro(id);
                    }
                    break;
                case 'Escape':
                    sugestoes.classList.add('hidden');
                    sugestaoAtivaGestorCadastro = -1;
                    break;
            }
        }

        function atualizarDestaqueGestorCadastro() {
            const itens = document.querySelectorAll('#sugestoes_gestor_cadastro .sugestao-item');
            itens.forEach((item, index) => {
                if (index === sugestaoAtivaGestorCadastro) {
                    item.classList.add('bg-gray-100');
                    item.classList.remove('hover:bg-gray-50');
                } else {
                    item.classList.remove('bg-gray-100');
                    item.classList.add('hover:bg-gray-50');
                }
            });
        }
        
        // Função para formatar localidade (primeira letra maiúscula)
        function formatarLocalidade(input) {
            let valor = input.value;
            if (valor.length > 0) {
                // Primeira letra maiúscula, resto mantém como está
                valor = valor.charAt(0).toUpperCase() + valor.slice(1);
                input.value = valor;
            }
        }
        
        // As funções carregarLocalidadesCadastro e carregarLocalidadesEdicao já foram definidas no início do script (linha ~4180)
        // Usar as variáveis globais já definidas
        // Autocomplete para localidade no cadastro
        document.addEventListener('DOMContentLoaded', function() {
            const inputLocalidadeCadastro = document.getElementById('localidade');
            const dropdownLocalidadeCadastro = document.getElementById('autocomplete-dropdown-localidade');
            
            if (inputLocalidadeCadastro && dropdownLocalidadeCadastro) {
                inputLocalidadeCadastro.addEventListener('input', function() {
                    if (this.disabled) return;
                    
                    const query = this.value.trim().toLowerCase();
                    window.selectedIndexLocalidadeCadastro = -1;
                    
                    if (query.length === 0) {
                        dropdownLocalidadeCadastro.classList.remove('show');
                        return;
                    }
                    
                    window.filteredLocalidadesCadastro = window.localidadesDisponiveisCadastro.filter(localidade => 
                        localidade.toLowerCase().includes(query)
                    );
                    
                    if (window.filteredLocalidadesCadastro.length === 0) {
                        dropdownLocalidadeCadastro.classList.remove('show');
                        return;
                    }
                    
                    renderDropdownLocalidadeCadastro();
                    dropdownLocalidadeCadastro.classList.add('show');
                });
                
                inputLocalidadeCadastro.addEventListener('keydown', function(e) {
                    if (this.disabled) return;
                    if (!dropdownLocalidadeCadastro.classList.contains('show')) return;
                    
                    const items = dropdownLocalidadeCadastro.querySelectorAll('.autocomplete-item');
                    
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        window.selectedIndexLocalidadeCadastro = Math.min(window.selectedIndexLocalidadeCadastro + 1, items.length - 1);
                        updateSelectionLocalidadeCadastro(items);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        window.selectedIndexLocalidadeCadastro = Math.max(window.selectedIndexLocalidadeCadastro - 1, -1);
                        updateSelectionLocalidadeCadastro(items);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (window.selectedIndexLocalidadeCadastro >= 0 && window.filteredLocalidadesCadastro[window.selectedIndexLocalidadeCadastro]) {
                            selecionarLocalidadeCadastro(window.filteredLocalidadesCadastro[window.selectedIndexLocalidadeCadastro]);
                        }
                    } else if (e.key === 'Escape') {
                        dropdownLocalidadeCadastro.classList.remove('show');
                    }
                });
                
                document.addEventListener('click', function(e) {
                    if (!inputLocalidadeCadastro.contains(e.target) && !dropdownLocalidadeCadastro.contains(e.target)) {
                        dropdownLocalidadeCadastro.classList.remove('show');
                    }
                });
                
                function renderDropdownLocalidadeCadastro() {
                    dropdownLocalidadeCadastro.innerHTML = window.filteredLocalidadesCadastro.map((localidade, index) => `
                        <div class="autocomplete-item ${index === window.selectedIndexLocalidadeCadastro ? 'selected' : ''}" 
                             data-index="${index}" 
                             onclick="selecionarLocalidadeCadastro('${localidade.replace(/'/g, "\\'")}')">
                            <div class="distrito-nome">${localidade}</div>
                        </div>
                    `).join('');
                }
                
                function updateSelectionLocalidadeCadastro(items) {
                    items.forEach((item, index) => {
                        if (index === window.selectedIndexLocalidadeCadastro) {
                            item.classList.add('selected');
                            item.scrollIntoView({ block: 'nearest' });
                        } else {
                            item.classList.remove('selected');
                        }
                    });
                }
                
                window.selecionarLocalidadeCadastro = function(localidade) {
                    inputLocalidadeCadastro.value = localidade;
                    dropdownLocalidadeCadastro.classList.remove('show');
                };
            }
            
            // Autocomplete para localidade na edição
            const inputLocalidadeEdicao = document.getElementById('edit_localidade');
            const dropdownLocalidadeEdicao = document.getElementById('autocomplete-dropdown-localidade-edicao');
            
            if (inputLocalidadeEdicao && dropdownLocalidadeEdicao) {
                inputLocalidadeEdicao.addEventListener('input', function() {
                    if (this.disabled) return;
                    
                    const query = this.value.trim().toLowerCase();
                    window.selectedIndexLocalidadeEdicao = -1;
                    
                    if (query.length === 0) {
                        dropdownLocalidadeEdicao.classList.remove('show');
                        return;
                    }
                    
                    window.filteredLocalidadesEdicao = window.localidadesDisponiveisEdicao.filter(localidade => 
                        localidade.toLowerCase().includes(query)
                    );
                    
                    if (window.filteredLocalidadesEdicao.length === 0) {
                        dropdownLocalidadeEdicao.classList.remove('show');
                        return;
                    }
                    
                    renderDropdownLocalidadeEdicao();
                    dropdownLocalidadeEdicao.classList.add('show');
                });
                
                inputLocalidadeEdicao.addEventListener('keydown', function(e) {
                    if (this.disabled) return;
                    if (!dropdownLocalidadeEdicao.classList.contains('show')) return;
                    
                    const items = dropdownLocalidadeEdicao.querySelectorAll('.autocomplete-item');
                    
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        window.selectedIndexLocalidadeEdicao = Math.min(window.selectedIndexLocalidadeEdicao + 1, items.length - 1);
                        updateSelectionLocalidadeEdicao(items);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        window.selectedIndexLocalidadeEdicao = Math.max(window.selectedIndexLocalidadeEdicao - 1, -1);
                        updateSelectionLocalidadeEdicao(items);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (window.selectedIndexLocalidadeEdicao >= 0 && window.filteredLocalidadesEdicao[window.selectedIndexLocalidadeEdicao]) {
                            selecionarLocalidadeEdicao(window.filteredLocalidadesEdicao[window.selectedIndexLocalidadeEdicao]);
                        }
                    } else if (e.key === 'Escape') {
                        dropdownLocalidadeEdicao.classList.remove('show');
                    }
                });
                
                document.addEventListener('click', function(e) {
                    if (!inputLocalidadeEdicao.contains(e.target) && !dropdownLocalidadeEdicao.contains(e.target)) {
                        dropdownLocalidadeEdicao.classList.remove('show');
                    }
                });
                
                function renderDropdownLocalidadeEdicao() {
                    dropdownLocalidadeEdicao.innerHTML = window.filteredLocalidadesEdicao.map((localidade, index) => `
                        <div class="autocomplete-item ${index === window.selectedIndexLocalidadeEdicao ? 'selected' : ''}" 
                             data-index="${index}" 
                             onclick="selecionarLocalidadeEdicao('${localidade.replace(/'/g, "\\'")}')">
                            <div class="distrito-nome">${localidade}</div>
                        </div>
                    `).join('');
                }
                
                function updateSelectionLocalidadeEdicao(items) {
                    items.forEach((item, index) => {
                        if (index === window.selectedIndexLocalidadeEdicao) {
                            item.classList.add('selected');
                            item.scrollIntoView({ block: 'nearest' });
                        } else {
                            item.classList.remove('selected');
                        }
                    });
                }
                
                window.selecionarLocalidadeEdicao = function(localidade) {
                    inputLocalidadeEdicao.value = localidade;
                    dropdownLocalidadeEdicao.classList.remove('show');
                };
            }
        });

        // Event listener para o formulário de cadastro
        document.addEventListener('DOMContentLoaded', function() {
            const formCadastro = document.querySelector('form[method="POST"]');
            if (formCadastro && formCadastro.querySelector('input[name="acao"][value="cadastrar"]')) {
                formCadastro.addEventListener('submit', function(e) {
                    // Validar se um gestor foi selecionado
                    const gestorIdField = document.getElementById('gestor_id');
                    if (gestorIdField) {
                        const gestorId = gestorIdField.value;
                        if (!gestorId) {
                            e.preventDefault();
                            alert('Por favor, selecione um gestor para a escola.');
                            const gestorSearchField = document.getElementById('buscar_gestor_cadastro');
                            if (gestorSearchField) gestorSearchField.focus();
                            return false;
                        }
                    }
                });
            }
        });

        // Event listener para o formulário de edição
        document.addEventListener('DOMContentLoaded', function() {
            const formEdicao = document.getElementById('formEdicaoEscola');
            if (formEdicao) {
                formEdicao.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Garantir que a aba de dados básicos esteja visível para validação
                    mostrarAbaEdicao('dados-basicos');
                    
                    // Aguardar um momento para garantir que os campos estejam visíveis
                    setTimeout(() => {
                        // Validar campos obrigatórios antes de enviar
                        const nome = document.getElementById('edit_nome').value.trim();
                        if (!nome) {
                            alert('O nome da escola é obrigatório.');
                            document.getElementById('edit_nome').focus();
                            return;
                        }
                        
                        const tipoEscola = document.getElementById('edit_tipo_escola').value;
                        if (!tipoEscola) {
                            alert('O tipo de escola é obrigatório.');
                            document.getElementById('edit_tipo_escola').focus();
                            return;
                        }
                        
                        // Se chegou até aqui, enviar o formulário
                        enviarFormularioEdicao();
                    }, 100);
                });
                
                function enviarFormularioEdicao() {
                    // Coletar dados do formulário
                    const formData = new FormData();
                    formData.append('acao', 'editar');
                    formData.append('id', document.getElementById('edit_escola_id').value);
                    formData.append('nome', document.getElementById('edit_nome').value);
                    formData.append('inep', document.getElementById('edit_inep').value);
                    formData.append('nome_curto', document.getElementById('edit_nome_curto').value);
                    formData.append('tipo_escola', document.getElementById('edit_tipo_escola').value);
                    formData.append('logradouro', document.getElementById('edit_logradouro').value);
                    formData.append('numero', document.getElementById('edit_numero').value);
                    formData.append('complemento', document.getElementById('edit_complemento').value);
                    formData.append('bairro', document.getElementById('edit_bairro').value);
                    formData.append('telefone_fixo', document.getElementById('edit_telefone_fixo').value);
                    formData.append('telefone_movel', document.getElementById('edit_telefone_movel').value);
                    formData.append('email', document.getElementById('edit_email').value);
                    formData.append('site', document.getElementById('edit_site').value);
                    formData.append('municipio', 'MARANGUAPE');
                    formData.append('cep', document.getElementById('edit_cep').value);
                    formData.append('qtd_salas', document.getElementById('edit_qtd_salas').value);
                    // Coletar checkboxes de nível de ensino
                    const editNivelEnsinoInfantil = document.getElementById('edit_nivel_ensino_infantil');
                    const editNivelEnsinoFundamental = document.getElementById('edit_nivel_ensino_fundamental');
                    
                    if (editNivelEnsinoInfantil && editNivelEnsinoInfantil.checked) {
                        formData.append('nivel_ensino[]', 'EDUCACAO_INFANTIL');
                    }
                    if (editNivelEnsinoFundamental && editNivelEnsinoFundamental.checked) {
                        formData.append('nivel_ensino[]', 'ENSINO_FUNDAMENTAL');
                    }
                    
                    // Validar que pelo menos um nível foi selecionado
                    if (!editNivelEnsinoInfantil?.checked && !editNivelEnsinoFundamental?.checked) {
                        alert('Por favor, selecione pelo menos um nível de ensino.');
                        return;
                    }
                    formData.append('obs', '');
                    formData.append('codigo', document.getElementById('edit_codigo').value);
                    formData.append('cnpj', document.getElementById('edit_cnpj').value);
                    
                    const gestorIdField = document.getElementById('edit_gestor_id');
                    formData.append('gestor_id', gestorIdField ? gestorIdField.value || '' : '');
                    
                    // Enviar dados para o servidor
                    fetch('gestao_escolas.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Fechar modal de edição
                        fecharModalEdicaoEscola();
                        
                        // Mostrar modal de sucesso
                        mostrarModalSucesso();
                    })
                    .catch(error => {
                        console.error('Erro ao salvar alterações:', error);
                        alert('Erro ao salvar alterações. Tente novamente.');
                    });
                }
            }
        });
        
        // Funções para busca de gestor na edição
        function buscarGestoresEdicao(termo) {
            if (termo.length < 2) {
                document.getElementById('edit_gestor_results').classList.add('hidden');
                return;
            }
            
            fetch(`../../Controllers/gestao/GestorController.php?busca=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    const results = document.getElementById('edit_gestor_results');
                    results.innerHTML = '';
                    
                    if (data.length === 0) {
                        results.innerHTML = '<div class="p-3 text-sm text-gray-500">Nenhum gestor encontrado</div>';
                    } else {
                        data.forEach(gestor => {
                            const div = document.createElement('div');
                            div.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0';
                            div.innerHTML = `
                                <div class="font-medium text-gray-900">${gestor.nome}</div>
                                <div class="text-sm text-gray-500">${gestor.email}</div>
                            `;
                            div.onclick = () => selecionarGestorEdicao(gestor);
                            results.appendChild(div);
                        });
                    }
                    
                    results.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Erro ao buscar gestores:', error);
                });
        }
        
        function selecionarGestorEdicao(gestor) {
            document.getElementById('edit_gestor_id').value = gestor.id;
            document.getElementById('edit_gestor_nome_selecionado').textContent = gestor.nome;
            document.getElementById('edit_gestor_email_selecionado').textContent = gestor.email;
            document.getElementById('edit_gestor_search').value = '';
            document.getElementById('edit_gestor_results').classList.add('hidden');
            document.getElementById('edit_gestor_selected').classList.remove('hidden');
        }
        
        function removerGestorEdicao() {
            document.getElementById('edit_gestor_id').value = '';
            document.getElementById('edit_gestor_search').value = '';
            document.getElementById('edit_gestor_selected').classList.add('hidden');
        }
        
        // Máscaras para campos - com verificação de existência
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara para CEP (cadastro)
            const cepField = document.getElementById('cep');
            if (cepField) {
                cepField.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 8) value = value.slice(0, 8);
                    
                    if (value.length > 5) {
                        value = value.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
                    }
                    
                    e.target.value = value;
                });
            }
            
            // Máscara para telefone (cadastro)
            const telefoneField = document.getElementById('telefone');
            if (telefoneField) {
                telefoneField.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 11) value = value.slice(0, 11);
                    
                    if (value.length > 10) {
                        value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
                    } else if (value.length > 6) {
                        value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                    } else if (value.length > 2) {
                        value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
                    }
                    
                    e.target.value = value;
                });
            }
            
            // Máscaras para campos de edição
            const editTelefoneField = document.getElementById('edit_telefone');
            if (editTelefoneField) {
                editTelefoneField.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 11) value = value.slice(0, 11);
                    
                    if (value.length > 10) {
                        value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
                    } else if (value.length > 6) {
                        value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                    } else if (value.length > 2) {
                        value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
                    }
                    
                    e.target.value = value;
                });
            }
            
            const editCepField = document.getElementById('edit_cep');
            if (editCepField) {
                editCepField.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 8) value = value.slice(0, 8);
                    
                    if (value.length > 5) {
                        value = value.replace(/^(\d{5})(\d{0,3}).*/, '$1-$2');
                    }
                    
                    e.target.value = value;
                });
            }
        });
        
        // FORÇA VISIBILIDADE DO HEADER MOBILE
        // Event listeners simples
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('mobileOverlay');
            
            // Event listener para fechar sidebar ao clicar no overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    const sidebar = document.getElementById('sidebar');
                    const main = document.querySelector('main');
                    
                    if (sidebar && sidebar.classList.contains('open')) {
                        sidebar.classList.remove('open');
                        overlay.classList.add('hidden');
                        
                        // Remover opacidade do conteúdo principal
                        if (main) {
                            main.classList.remove('content-dimmed');
                        }
                    }
                });
            }
            
            
            // Event listeners para busca de gestores
            const gestorSearch = document.getElementById('gestor_search');
            if (gestorSearch) {
                gestorSearch.addEventListener('input', function(e) {
                    buscarGestores(e.target.value);
                });
            }
            
            // Event listeners para busca de gestores na edição
            const editGestorSearch = document.getElementById('edit_gestor_search');
            if (editGestorSearch) {
                editGestorSearch.addEventListener('input', function(e) {
                    buscarGestoresEdicao(e.target.value);
                });
            }
            
            // Fechar modal de edição clicando fora dele
            const modalEdicao = document.getElementById('modalEdicaoEscola');
            if (modalEdicao) {
                modalEdicao.addEventListener('click', function(e) {
                    if (e.target === this) {
                        fecharModalEdicaoEscola();
                    }
                });
            }
        });
        
        // Fechar resultados ao clicar fora
        document.addEventListener('click', function(e) {
            const gestorResults = document.getElementById('gestor_results');
            if (gestorResults && !e.target.closest('#gestor_search') && !e.target.closest('#gestor_results')) {
                gestorResults.classList.add('hidden');
            }
            
            const editGestorResults = document.getElementById('edit_gestor_results');
            if (editGestorResults && !e.target.closest('#edit_gestor_search') && !e.target.closest('#edit_gestor_results')) {
                editGestorResults.classList.add('hidden');
            }
        });
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar event listeners para o menu lateral
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Se estiver no mobile, fechar o menu lateral
                    if (window.innerWidth < 1024) {
                        toggleSidebar();
                    }
                });
            });
        });

        // User Profile Modal Functions - Using standardized component



        // Configurar modais após DOM carregar
        document.addEventListener('DOMContentLoaded', function() {

            
            // Close modal de sucesso when clicking outside
            const modalSucesso = document.getElementById('modalSucesso');
            if (modalSucesso) {
                modalSucesso.addEventListener('click', function(e) {
                    if (e.target === this) {
                        window.fecharModalSucesso();
                    }
                });
            }
        });

        // Accessibility Functions
        function setContrast(contrast) {
            document.documentElement.setAttribute('data-contrast', contrast);

            // Update button states
            document.querySelectorAll('[id^="contrast-"]').forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
                btn.classList.add('border-gray-300', 'text-gray-700');
            });

            const activeBtn = document.getElementById(`contrast-${contrast}`);
            if (activeBtn) {
                activeBtn.classList.remove('border-gray-300', 'text-gray-700');
                activeBtn.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
            }

            // Save to localStorage
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.contrast = contrast;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }

        function setFontSize(size) {
            document.documentElement.setAttribute('data-font-size', size);

            // Update button states
            document.querySelectorAll('[id^="font-"]').forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-500');
                btn.classList.add('border-gray-300', 'text-gray-700');
            });

            const activeBtn = document.getElementById(`font-${size}`);
            if (activeBtn) {
                activeBtn.classList.remove('border-gray-300', 'text-gray-700');
                activeBtn.classList.add('bg-blue-500', 'text-white', 'border-blue-500');
            }

            // Save to localStorage
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.fontSize = size;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }

        function setReduceMotion(enabled) {
            if (enabled) {
                document.documentElement.setAttribute('data-reduce-motion', 'true');
                // Apply reduced motion styles
                const style = document.createElement('style');
                style.id = 'reduce-motion-styles';
                style.textContent = `
                    *, *::before, *::after {
                        animation-duration: 0.01ms !important;
                        animation-iteration-count: 1 !important;
                        transition-duration: 0.01ms !important;
                        scroll-behavior: auto !important;
                    }
                `;
                document.head.appendChild(style);
            } else {
                document.documentElement.removeAttribute('data-reduce-motion');
                const style = document.getElementById('reduce-motion-styles');
                if (style) {
                    style.remove();
                }
            }

            // Save to localStorage
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.reduceMotion = enabled;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }

        function toggleVLibras() {
            const vlibrasWidget = document.getElementById('vlibras-widget');
            const toggle = document.getElementById('vlibras-toggle');
            
            if (toggle.checked) {
                // Ativar VLibras
                vlibrasWidget.style.display = 'block';
                vlibrasWidget.classList.remove('disabled');
                vlibrasWidget.classList.add('enabled');
                localStorage.setItem('vlibras-enabled', 'true');
                
                // Reinicializar o widget se necessário
                if (window.VLibras && !window.vlibrasInstance) {
                    window.vlibrasInstance = new window.VLibras.Widget('https://vlibras.gov.br/app');
                }
            } else {
                // Desativar VLibras
                vlibrasWidget.style.display = 'none';
                vlibrasWidget.classList.remove('enabled');
                vlibrasWidget.classList.add('disabled');
                localStorage.setItem('vlibras-enabled', 'false');
                
                // Limpar instância se existir
                if (window.vlibrasInstance) {
                    window.vlibrasInstance = null;
                }
            }
        }

        function setKeyboardNavigation(enabled) {
            if (enabled) {
                document.documentElement.setAttribute('data-keyboard-nav', 'true');
                // Apply keyboard navigation styles
                const style = document.createElement('style');
                style.id = 'keyboard-nav-styles';
                style.textContent = `
                    .keyboard-nav button:focus,
                    .keyboard-nav a:focus,
                    .keyboard-nav input:focus,
                    .keyboard-nav select:focus,
                    .keyboard-nav textarea:focus {
                        outline: 3px solid #3b82f6 !important;
                        outline-offset: 2px !important;
                    }
                `;
                document.head.appendChild(style);
            } else {
                document.documentElement.removeAttribute('data-keyboard-nav');
                const style = document.getElementById('keyboard-nav-styles');
                if (style) {
                    style.remove();
                }
            }

            // Save to localStorage
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            settings.keyboardNav = enabled;
            localStorage.setItem('accessibilitySettings', JSON.stringify(settings));
        }

        // Load accessibility settings on page load
        function loadAccessibilitySettings() {
            const settings = JSON.parse(localStorage.getItem('accessibilitySettings') || '{}');
            
            // Load contrast setting
            if (settings.contrast) {
                setContrast(settings.contrast);
            }
            
            // Load font size setting
            if (settings.fontSize) {
                setFontSize(settings.fontSize);
            }
            
            // Load reduce motion setting
            if (settings.reduceMotion) {
                document.getElementById('reduce-motion').checked = true;
                setReduceMotion(true);
            }
            
            // Load keyboard navigation setting
            if (settings.keyboardNav) {
                document.getElementById('keyboard-nav').checked = true;
                setKeyboardNavigation(true);
            }
            
            // Load VLibras setting
            const vlibrasEnabled = localStorage.getItem('vlibras-enabled');
            const vlibrasToggle = document.getElementById('vlibras-toggle');
            const vlibrasWidget = document.getElementById('vlibras-widget');
            
            if (vlibrasToggle) {
                if (vlibrasEnabled === 'false') {
                    vlibrasToggle.checked = false;
                    vlibrasWidget.style.display = 'none';
                    vlibrasWidget.classList.remove('enabled');
                    vlibrasWidget.classList.add('disabled');
                } else {
                    vlibrasToggle.checked = true;
                    vlibrasWidget.style.display = 'block';
                    vlibrasWidget.classList.remove('disabled');
                    vlibrasWidget.classList.add('enabled');
                }
            }
        }

        // Initialize accessibility settings when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadAccessibilitySettings();
        });

        // Variáveis globais para lotação
        let professorSelecionadoLotacao = null;
        let gestorSelecionadoLotacao = null;
        let escolaAtualLotacao = null;

        // Função para alternar entre as abas de lotação (professores/gestores)
        function showLotacaoTab(tipo) {
            // Remover classe ativa de todos os botões
            document.querySelectorAll('.lotacao-tab-btn').forEach(btn => {
                btn.classList.remove('active', 'border-primary-green', 'text-primary-green');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Esconder todos os conteúdos
            document.querySelectorAll('.lotacao-tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Mostrar o conteúdo selecionado
            document.getElementById(`lotacao-${tipo}`).classList.remove('hidden');
            
            // Ativar o botão selecionado
            const btnAtivo = document.getElementById(`tab-${tipo}-btn`);
            btnAtivo.classList.add('active', 'border-primary-green', 'text-primary-green');
            btnAtivo.classList.remove('border-transparent', 'text-gray-500');
        }

        // Função para carregar informações da escola e lotação
        function carregarLotacaoEscola(escolaId) {
            if (!escolaId) {
                document.getElementById('info-escola-lotacao').classList.add('hidden');
                document.getElementById('secao-lotacao').classList.add('hidden');
                return;
            }

            escolaAtualLotacao = escolaId;

            // Obter o nome da escola selecionada do campo de busca
            const campoBusca = document.getElementById('buscar_escola_lotacao');
            const nomeEscola = campoBusca.value;
            
            // Carregar informações da escola
            const detalhesEscola = document.getElementById('detalhes-escola-lotacao');
            detalhesEscola.innerHTML = `
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-sm"><strong>Nome:</strong> ${nomeEscola}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        <span class="text-sm"><strong>Município:</strong> Maranguape - CE</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                        <span class="text-sm"><strong>ID:</strong> ${escolaId}</span>
                    </div>
                </div>
            `;
            
            // Mostrar informações da escola
            document.getElementById('info-escola-lotacao').classList.remove('hidden');
            
            // Mostrar seção de lotação
            document.getElementById('secao-lotacao').classList.remove('hidden');

            // Carregar listas imediatamente para garantir exibição mesmo se EscolaController falhar
            carregarProfessoresLotados();
            carregarGestoresLotados();
            
            // Carregar dados da escola via AJAX (opcional)
            fetch(`../../Controllers/gestao/EscolaController.php?id=${escolaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const escola = data.escola;
                        // Atualizar com dados reais se disponíveis
                        detalhesEscola.innerHTML = `
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-sm"><strong>Nome:</strong> ${escola.nome}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-sm"><strong>Município:</strong> Maranguape - CE</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span class="text-sm"><strong>Código INEP:</strong> ${escola.codigo_inep || 'Não informado'}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                    <span class="text-sm"><strong>ID:</strong> ${escolaId}</span>
                                </div>
                            </div>
                        `;
                        
                        // Dados da escola carregados com sucesso
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar escola:', error);
                    alert('Erro ao carregar informações da escola');
                });
        }

        // Função para buscar professores disponíveis para lotação
        function buscarProfessoresLotacao(termo) {
            if (termo.length < 2) {
                document.getElementById('resultados_professores_lotacao').classList.add('hidden');
                return;
            }

            fetch(`../../Controllers/gestao/ProfessorLotacaoController.php?acao=buscar_disponiveis&termo=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    const resultados = document.getElementById('resultados_professores_lotacao');
                    
                    if (data.success && data.professores.length > 0) {
                        let html = '';
                        data.professores.forEach(professor => {
                            html += `
                                <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100" 
                                     onclick="selecionarProfessorLotacao(${professor.id}, '${professor.nome}')">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900">${professor.nome}</p>
                                            <p class="text-sm text-gray-600">${professor.email}</p>
                                        </div>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Professor</span>
                                    </div>
                                </div>
                            `;
                        });
                        resultados.innerHTML = html;
                        resultados.classList.remove('hidden');
                    } else {
                        resultados.innerHTML = '<div class="p-3 text-gray-500 text-center">Nenhum professor encontrado</div>';
                        resultados.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar professores:', error);
                });
        }

        // Função para selecionar professor para lotação
        function selecionarProfessorLotacao(id, nome) {
            professorSelecionadoLotacao = { id, nome };
            document.getElementById('buscar_professor_lotacao').value = nome;
            document.getElementById('resultados_professores_lotacao').classList.add('hidden');
        }

        // Função para buscar gestores disponíveis para lotação
        function buscarGestoresLotacao(termo) {
            if (termo.length < 2) {
                document.getElementById('resultados_gestores_lotacao').classList.add('hidden');
                return;
            }

            fetch(`../../Controllers/gestao/GestorLotacaoController.php?acao=buscar_disponiveis&termo=${encodeURIComponent(termo)}`)
                .then(response => response.json())
                .then(data => {
                    const resultados = document.getElementById('resultados_gestores_lotacao');
                    
                    if (data.success && data.gestores.length > 0) {
                        let html = '';
                        data.gestores.forEach(gestor => {
                            html += `
                                <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100" 
                                     onclick="selecionarGestorLotacao(${gestor.id}, '${gestor.nome}')">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900">${gestor.nome}</p>
                                            <p class="text-sm text-gray-600">${gestor.email}</p>
                                        </div>
                                        <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">Gestor</span>
                                    </div>
                                </div>
                            `;
                        });
                        resultados.innerHTML = html;
                        resultados.classList.remove('hidden');
                    } else {
                        resultados.innerHTML = '<div class="p-3 text-gray-500 text-center">Nenhum gestor encontrado</div>';
                        resultados.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar gestores:', error);
                });
        }

        // Função para selecionar gestor para lotação
        function selecionarGestorLotacao(id, nome) {
            gestorSelecionadoLotacao = { id, nome };
            document.getElementById('buscar_gestor_lotacao').value = nome;
            document.getElementById('resultados_gestores_lotacao').classList.add('hidden');
        }

        // Função para lotar professor
        function lotarProfessor() {
            if (!professorSelecionadoLotacao) {
                alert('Selecione um professor primeiro');
                return;
            }

            if (!escolaAtualLotacao) {
                alert('Selecione uma escola primeiro');
                return;
            }

            const dataInicio = document.getElementById('data_inicio_professor').value;
            if (!dataInicio) {
                alert('Informe a data de início');
                return;
            }

            const formData = new FormData();
            formData.append('acao', 'lotar');
            formData.append('professor_id', professorSelecionadoLotacao.id);
            formData.append('escola_id', escolaAtualLotacao);
            formData.append('data_inicio', dataInicio);

            fetch('../../Controllers/gestao/ProfessorLotacaoController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Professor lotado com sucesso!');
                    // Limpar campos
                    document.getElementById('buscar_professor_lotacao').value = '';
                    document.getElementById('data_inicio_professor').value = '';
                    professorSelecionadoLotacao = null;
                    // Recarregar lista
                    carregarProfessoresLotados();
                } else {
                    alert('Erro ao lotar professor: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao lotar professor:', error);
                alert('Erro ao lotar professor');
            });
        }

        // Função para lotar gestor
        function lotarGestor() {
            if (!gestorSelecionadoLotacao) {
                alert('Selecione um gestor primeiro');
                return;
            }

            if (!escolaAtualLotacao) {
                alert('Selecione uma escola primeiro');
                return;
            }

            const cargo = document.getElementById('cargo_gestor').value;
            const dataInicio = document.getElementById('data_inicio_gestor').value;
            
            if (!cargo) {
                alert('Selecione o cargo');
                return;
            }

            if (!dataInicio) {
                alert('Informe a data de início');
                return;
            }

            const formData = new FormData();
            formData.append('acao', 'lotar');
            formData.append('gestor_id', gestorSelecionadoLotacao.id);
            formData.append('escola_id', escolaAtualLotacao);
            formData.append('cargo', cargo);
            formData.append('data_inicio', dataInicio);

            fetch('../../Controllers/gestao/GestorLotacaoController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Gestor lotado com sucesso!');
                    // Limpar campos
                    document.getElementById('buscar_gestor_lotacao').value = '';
                    document.getElementById('cargo_gestor').value = '';
                    document.getElementById('data_inicio_gestor').value = '';
                    gestorSelecionadoLotacao = null;
                    // Recarregar lista
                    carregarGestoresLotados();
                } else {
                    alert('Erro ao lotar gestor: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao lotar gestor:', error);
                alert('Erro ao lotar gestor');
            });
        }

        // Função para carregar professores lotados
        function carregarProfessoresLotados() {
            if (!escolaAtualLotacao) {
                return;
            }

            const url = `../../Controllers/gestao/ProfessorLotacaoController.php?acao=listar_lotados&escola_id=${escolaAtualLotacao}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const lista = document.getElementById('lista-professores-lotados');
                    
                    if (data.success && data.professores && data.professores.length > 0) {
                        let html = '';
                        data.professores.forEach(professor => {
                            html += `
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900">${professor.nome}</p>
                                            <p class="text-sm text-gray-600">Início: ${professor.data_inicio}</p>
                                            ${professor.data_fim ? `<p class="text-sm text-red-600">Fim: ${professor.data_fim}</p>` : ''}
                                        </div>
                                        <div class="flex space-x-2">
                                            ${professor.ativo == 1 ? `
                                                <button onclick="removerLotacaoProfessor(${professor.id})" 
                                                        class="text-red-600 hover:text-red-800 text-sm">
                                                    Remover
                                                </button>
                                            ` : `
                                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Inativo</span>
                                            `}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        lista.innerHTML = html;
                    } else {
                        lista.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhum professor lotado</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar professores lotados:', error);
                });
        }

        // Função para carregar gestores lotados
        function carregarGestoresLotados() {
            if (!escolaAtualLotacao) return;

            fetch(`../../Controllers/gestao/GestorLotacaoController.php?acao=listar_lotados&escola_id=${escolaAtualLotacao}`)
                .then(response => response.json())
                .then(data => {
                    const lista = document.getElementById('lista-gestores-lotados');
                    
                    if (data.success && data.gestores.length > 0) {
                        let html = '';
                        data.gestores.forEach(gestor => {
                            html += `
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900">${gestor.nome}</p>
                                            <p class="text-sm text-gray-600">Cargo: ${gestor.cargo}</p>
                                            <p class="text-sm text-gray-600">Início: ${gestor.data_inicio}</p>
                                            ${gestor.data_fim ? `<p class="text-sm text-red-600">Fim: ${gestor.data_fim}</p>` : ''}
                                        </div>
                                        <div class="flex space-x-2">
                                            ${gestor.ativo == 1 ? `
                                                <button onclick="removerLotacaoGestor(${gestor.id})" 
                                                        class="text-red-600 hover:text-red-800 text-sm">
                                                    Remover
                                                </button>
                                            ` : `
                                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Inativo</span>
                                            `}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        lista.innerHTML = html;
                    } else {
                        lista.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhum gestor lotado</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar gestores lotados:', error);
                });
        }

        // Função para remover lotação de professor
        function removerLotacaoProfessor(lotacaoId) {
            if (!confirm('Tem certeza que deseja remover esta lotação?')) {
                return;
            }

            const formData = new FormData();
            formData.append('acao', 'finalizar');
            formData.append('lotacao_id', lotacaoId);

            fetch('../../Controllers/gestao/ProfessorLotacaoController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Lotação removida com sucesso!');
                    carregarProfessoresLotados();
                } else {
                    alert('Erro ao remover lotação: ' + (data.message || data.mensagem || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro ao remover lotação:', error);
                alert('Erro ao remover lotação');
            });
        }

        // Função para remover lotação de gestor
        function removerLotacaoGestor(lotacaoId) {
            if (!confirm('Tem certeza que deseja remover esta lotação?')) {
                return;
            }

            const formData = new FormData();
            formData.append('acao', 'remover');
            formData.append('lotacao_id', lotacaoId);

            fetch('../../Controllers/gestao/GestorLotacaoController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Lotação removida com sucesso!');
                    carregarGestoresLotados();
                } else {
                    alert('Erro ao remover lotação: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao remover lotação:', error);
                alert('Erro ao remover lotação');
            });
        }

        // ===== FUNÇÕES PARA ADICIONAR GESTOR À ESCOLA =====
        
        // Função para carregar informações da escola selecionada
        function carregarInfoEscolaGestor(escolaId) {
            if (!escolaId) {
                document.getElementById('info-escola-gestor').classList.add('hidden');
                document.getElementById('passo-selecionar-gestor').classList.add('hidden');
                return;
            }

            // Obter o nome da escola selecionada do campo de busca
            const campoBusca = document.getElementById('buscar_escola_gestor');
            const nomeEscola = campoBusca.value;
             
             // Simular carregamento das informações da escola
             const detalhesEscola = document.getElementById('detalhes-escola-gestor');
             detalhesEscola.innerHTML = `
                 <div class="space-y-2">
                     <div class="flex items-center space-x-2">
                         <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                         <span class="text-sm"><strong>Nome:</strong> ${nomeEscola}</span>
                     </div>
                     <div class="flex items-center space-x-2">
                         <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                         <span class="text-sm"><strong>Município:</strong> Maranguape - CE</span>
                     </div>
                     <div class="flex items-center space-x-2">
                         <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                         <span class="text-sm"><strong>ID:</strong> ${escolaId}</span>
                     </div>
                 </div>
             `;
            
            // Mostrar informações da escola
            document.getElementById('info-escola-gestor').classList.remove('hidden');
            
            // Mostrar passo 2 (seleção do gestor)
            document.getElementById('passo-selecionar-gestor').classList.remove('hidden');
            
            // Definir o ID da escola no formulário
            document.getElementById('escola_id_gestor').value = escolaId;
        }

        // Função para buscar gestores
        function buscarGestores(termo) {
            const gestorItems = document.querySelectorAll('.gestor-item');
            const termoLower = termo.toLowerCase();
            
            gestorItems.forEach(item => {
                const nomeGestor = item.querySelector('.font-medium').textContent.toLowerCase();
                if (nomeGestor.includes(termoLower)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Função para selecionar/deselecionar um gestor
        function selecionarGestor(gestorId, nomeGestor) {
            const radioSelecionado = document.getElementById(`gestor_${gestorId}`);
            
            // Verificar se o gestor já está selecionado
            if (radioSelecionado && radioSelecionado.checked) {
                // Se já está selecionado, deselecionar
                deselecionarGestor();
                return;
            }
            
            // Desmarcar todos os radio buttons e remover estilos de seleção
            document.querySelectorAll('.gestor-radio').forEach(radio => {
                radio.checked = false;
                const gestorItem = radio.closest('.gestor-item');
                if (gestorItem) {
                    gestorItem.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50', 'border-blue-300');
                    gestorItem.classList.add('border-gray-200');
                    
                    // Resetar ícone de seleção
                    const iconContainer = gestorItem.querySelector('.w-6.h-6');
                    if (iconContainer) {
                        iconContainer.classList.remove('border-blue-500', 'bg-blue-500');
                        iconContainer.classList.add('border-gray-300');
                        const dot = iconContainer.querySelector('.w-2.h-2');
                        if (dot) {
                            dot.classList.remove('bg-white');
                            dot.classList.add('bg-transparent');
                        }
                    }
                }
            });
            
            // Marcar o selecionado
            if (radioSelecionado) {
                radioSelecionado.checked = true;
                const gestorItem = radioSelecionado.closest('.gestor-item');
                if (gestorItem) {
                    gestorItem.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50', 'border-blue-300');
                    gestorItem.classList.remove('border-gray-200');
                    
                    // Atualizar ícone de seleção
                    const iconContainer = gestorItem.querySelector('.w-6.h-6');
                    if (iconContainer) {
                        iconContainer.classList.add('border-blue-500', 'bg-blue-500');
                        iconContainer.classList.remove('border-gray-300');
                        const dot = iconContainer.querySelector('.w-2.h-2');
                        if (dot) {
                            dot.classList.add('bg-white');
                            dot.classList.remove('bg-transparent');
                        }
                    }
                }
            }
            
            // Mostrar gestor selecionado
            document.getElementById('nome-gestor-selecionado').textContent = nomeGestor;
            document.getElementById('gestor-selecionado').classList.remove('hidden');
            
            // Validar seleção completa
            validarSelecaoGestor();
        }

        // Função para deselecionar o gestor atual
        function deselecionarGestor() {
            // Desmarcar todos os radio buttons
            document.querySelectorAll('.gestor-radio').forEach(radio => {
                radio.checked = false;
                const gestorItem = radio.closest('.gestor-item');
                if (gestorItem) {
                    gestorItem.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50', 'border-blue-300');
                    gestorItem.classList.add('border-gray-200');
                    
                    // Resetar ícone de seleção
                    const iconContainer = gestorItem.querySelector('.w-6.h-6');
                    if (iconContainer) {
                        iconContainer.classList.remove('border-blue-500', 'bg-blue-500');
                        iconContainer.classList.add('border-gray-300');
                        const dot = iconContainer.querySelector('.w-2.h-2');
                        if (dot) {
                            dot.classList.remove('bg-white');
                            dot.classList.add('bg-transparent');
                        }
                    }
                }
            });
            
            // Ocultar gestor selecionado
            document.getElementById('gestor-selecionado').classList.add('hidden');
            
            // Validar seleção completa
            validarSelecaoGestor();
        }

        // Função para limpar seleção
        function limparSelecaoGestor() {
            try {
                // Primeiro deselecionar qualquer gestor selecionado
                deselecionarGestor();
                
                // Limpar campo de busca
                const buscarGestor = document.getElementById('buscar_gestor');
                if (buscarGestor) {
                    buscarGestor.value = '';
                }
                
                // Limpar tipo de gestor
                const tipoGestor = document.getElementById('tipo_gestor');
                if (tipoGestor) {
                    tipoGestor.value = '';
                }
                
                // Mostrar todos os gestores novamente
                document.querySelectorAll('.gestor-item').forEach(item => {
                    item.style.display = 'flex';
                });
                
                // Validar seleção
                validarSelecaoGestor();
            } catch (error) {
                console.error('Erro na função limparSelecaoGestor:', error);
            }
        }

        // Função para carregar gestor atual da escola
        function carregarGestorAtualEscola(escolaId) {
            // Simular carregamento do gestor atual
            // Em uma implementação real, você faria uma requisição AJAX aqui
            const gestorAtualDiv = document.getElementById('gestor-atual-escola');
            const infoGestorDiv = document.getElementById('info-gestor-atual');
            
            // Simular dados do gestor atual (substitua por dados reais)
            const gestorAtual = {
                nome: "João Silva",
                cargo: "Diretor",
                dataInicio: "2023-01-15"
            };
            
            if (gestorAtual.nome) {
                infoGestorDiv.innerHTML = `
                    <div class="space-y-1">
                        <div><strong>Nome:</strong> ${gestorAtual.nome}</div>
                        <div><strong>Cargo:</strong> ${gestorAtual.cargo}</div>
                        <div><strong>Desde:</strong> ${new Date(gestorAtual.dataInicio).toLocaleDateString('pt-BR')}</div>
                    </div>
                `;
                gestorAtualDiv.classList.remove('hidden');
                
                // Desabilitar opção de diretor se já existir um
                if (gestorAtual.cargo.toLowerCase() === 'diretor') {
                    document.getElementById('opcao-diretor').disabled = true;
                    document.getElementById('opcao-diretor').textContent = 'Diretor (já existe)';
                }
            } else {
                gestorAtualDiv.classList.add('hidden');
                // Habilitar opção de diretor se não existir
                document.getElementById('opcao-diretor').disabled = false;
                document.getElementById('opcao-diretor').textContent = 'Diretor';
            }
        }

        // Função para verificar cargo do gestor
        function verificarCargoGestor() {
            const cargoSelect = document.getElementById('cargo_gestor');
            const avisoDiv = document.getElementById('aviso-diretor');
            const opcaoDiretor = document.getElementById('opcao-diretor');
            
            if (cargoSelect.value === 'diretor' && opcaoDiretor.disabled) {
                avisoDiv.classList.remove('hidden');
                cargoSelect.value = ''; // Limpar seleção
            } else {
                avisoDiv.classList.add('hidden');
            }
        }

        // Array com todas as escolas para autocomplete
        let todasEscolas = [
            <?php
            $escolas = listarEscolas();
            $escolasJson = [];
            foreach ($escolas as $escola) {
                $escolasJson[] = '{id: ' . $escola['id'] . ', nome: "' . htmlspecialchars($escola['nome']) . '"}';
            }
            echo implode(',', $escolasJson);
            ?>
        ];

        // Variáveis para controle do autocomplete
        let sugestaoAtivaGestor = -1;
        let sugestaoAtivaLotacao = -1;

        // Função para buscar escolas na aba de gestor (autocomplete)
        function buscarEscolasGestor(termo) {
            const campoBusca = document.getElementById('buscar_escola_gestor');
            const sugestoes = document.getElementById('sugestoes_gestor');
            const termoLower = termo.toLowerCase().trim();
            
            // Limpar seleção anterior
            document.getElementById('escola_gestor').value = '';
            
            if (termo.length === 0) {
                sugestoes.classList.add('hidden');
                return;
            }
            
            // Filtrar escolas
            const escolasFiltradas = todasEscolas.filter(escola => 
                escola.nome.toLowerCase().includes(termoLower)
            ).sort((a, b) => {
                // Ordenar por posição do match
                const posA = a.nome.toLowerCase().indexOf(termoLower);
                const posB = b.nome.toLowerCase().indexOf(termoLower);
                return posA - posB;
            });
            
            if (escolasFiltradas.length === 0) {
                sugestoes.classList.add('hidden');
                return;
            }
            
            // Criar HTML das sugestões
            let htmlSugestoes = '';
            escolasFiltradas.forEach((escola, index) => {
                const nomeEscola = escola.nome;
                const termoRegex = new RegExp(`(${termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                const nomeDestacado = nomeEscola.replace(termoRegex, '<span style="color: #059669; font-weight: bold;">$1</span>');
                
                htmlSugestoes += `
                    <div class="sugestao-item px-4 py-2 cursor-pointer hover:bg-black hover:bg-opacity-5 dark:hover:bg-white dark:hover:bg-opacity-10 border-b border-gray-100 last:border-b-0 transition-all duration-200" 
                         data-index="${index}" 
                         data-id="${escola.id}" 
                         data-nome="${nomeEscola}"
                         onclick="selecionarEscolaGestor('${escola.id}', '${nomeEscola}')">
                        ${nomeDestacado}
                    </div>
                `;
            });
            
            sugestoes.innerHTML = htmlSugestoes;
            sugestoes.classList.remove('hidden');
            sugestaoAtivaGestor = -1;
        }

        // Função para selecionar escola no autocomplete gestor
        function selecionarEscolaGestor(id, nome) {
            document.getElementById('buscar_escola_gestor').value = nome;
            document.getElementById('escola_gestor').value = id;
            document.getElementById('sugestoes_gestor').classList.add('hidden');
            carregarInfoEscolaGestor(id);
        }

        // Função para mostrar sugestões gestor
        function mostrarSugestoesGestor() {
            const termo = document.getElementById('buscar_escola_gestor').value;
            if (termo.length > 0) {
                buscarEscolasGestor(termo);
            }
        }

        // Função para esconder sugestões gestor
        function esconderSugestoesGestor() {
            setTimeout(() => {
                document.getElementById('sugestoes_gestor').classList.add('hidden');
            }, 200);
        }

        // Função para navegar nas sugestões com teclado gestor
        function navegarSugestoesGestor(event) {
            const sugestoes = document.getElementById('sugestoes_gestor');
            const itens = sugestoes.querySelectorAll('.sugestao-item');
            
            if (itens.length === 0) return;
            
            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    sugestaoAtivaGestor = Math.min(sugestaoAtivaGestor + 1, itens.length - 1);
                    atualizarDestaqueGestor();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    sugestaoAtivaGestor = Math.max(sugestaoAtivaGestor - 1, -1);
                    atualizarDestaqueGestor();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (sugestaoAtivaGestor >= 0 && sugestaoAtivaGestor < itens.length) {
                        const item = itens[sugestaoAtivaGestor];
                        const id = item.getAttribute('data-id');
                        const nome = item.getAttribute('data-nome');
                        selecionarEscolaGestor(id, nome);
                    }
                    break;
                case 'Escape':
                    sugestoes.classList.add('hidden');
                    sugestaoAtivaGestor = -1;
                    break;
            }
        }

        // Função para atualizar destaque das sugestões gestor
        function atualizarDestaqueGestor() {
            const itens = document.querySelectorAll('#sugestoes_gestor .sugestao-item');
            itens.forEach((item, index) => {
                if (index === sugestaoAtivaGestor) {
                    item.classList.add('bg-black', 'bg-opacity-10', 'dark:bg-white', 'dark:bg-opacity-20');
                    item.classList.remove('hover:bg-black', 'hover:bg-opacity-5', 'dark:hover:bg-white', 'dark:hover:bg-opacity-10');
                } else {
                    item.classList.remove('bg-black', 'bg-opacity-10', 'dark:bg-white', 'dark:bg-opacity-20');
                    item.classList.add('hover:bg-black', 'hover:bg-opacity-5', 'dark:hover:bg-white', 'dark:hover:bg-opacity-10');
                }
            });
        }

        // Função para buscar escolas na aba de lotação (autocomplete)
        function buscarEscolasLotacao(termo) {
            const campoBusca = document.getElementById('buscar_escola_lotacao');
            const sugestoes = document.getElementById('sugestoes_lotacao');
            const termoLower = termo.toLowerCase().trim();
            
            // Limpar seleção anterior
            document.getElementById('escola_lotacao').value = '';
            
            if (termo.length === 0) {
                sugestoes.classList.add('hidden');
                return;
            }
            
            // Filtrar escolas
            const escolasFiltradas = todasEscolas.filter(escola => 
                escola.nome.toLowerCase().includes(termoLower)
            ).sort((a, b) => {
                // Ordenar por posição do match
                const posA = a.nome.toLowerCase().indexOf(termoLower);
                const posB = b.nome.toLowerCase().indexOf(termoLower);
                return posA - posB;
            });
            
            if (escolasFiltradas.length === 0) {
                sugestoes.classList.add('hidden');
                return;
            }
            
            // Criar HTML das sugestões
            let htmlSugestoes = '';
            escolasFiltradas.forEach((escola, index) => {
                const nomeEscola = escola.nome;
                const termoRegex = new RegExp(`(${termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                const nomeDestacado = nomeEscola.replace(termoRegex, '<span style="color: #059669; font-weight: bold;">$1</span>');
                
                htmlSugestoes += `
                    <div class="sugestao-item px-4 py-2 cursor-pointer hover:bg-black hover:bg-opacity-5 dark:hover:bg-white dark:hover:bg-opacity-10 border-b border-gray-100 last:border-b-0 transition-all duration-200" 
                         data-index="${index}" 
                         data-id="${escola.id}" 
                         data-nome="${nomeEscola}"
                         onclick="selecionarEscolaLotacao('${escola.id}', '${nomeEscola}')">
                        ${nomeDestacado}
                    </div>
                `;
            });
            
            sugestoes.innerHTML = htmlSugestoes;
            sugestoes.classList.remove('hidden');
            sugestaoAtivaLotacao = -1;
        }

        // Função para selecionar escola no autocomplete lotação
        function selecionarEscolaLotacao(id, nome) {
            document.getElementById('buscar_escola_lotacao').value = nome;
            document.getElementById('escola_lotacao').value = id;
            document.getElementById('sugestoes_lotacao').classList.add('hidden');
            carregarLotacaoEscola(id);
        }

        // Função para mostrar sugestões lotação
        function mostrarSugestoesLotacao() {
            const termo = document.getElementById('buscar_escola_lotacao').value;
            if (termo.length > 0) {
                buscarEscolasLotacao(termo);
            }
        }

        // Função para esconder sugestões lotação
        function esconderSugestoesLotacao() {
            setTimeout(() => {
                document.getElementById('sugestoes_lotacao').classList.add('hidden');
            }, 200);
        }

        // Função para navegar nas sugestões com teclado lotação
        function navegarSugestoesLotacao(event) {
            const sugestoes = document.getElementById('sugestoes_lotacao');
            const itens = sugestoes.querySelectorAll('.sugestao-item');
            
            if (itens.length === 0) return;
            
            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    sugestaoAtivaLotacao = Math.min(sugestaoAtivaLotacao + 1, itens.length - 1);
                    atualizarDestaqueLotacao();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    sugestaoAtivaLotacao = Math.max(sugestaoAtivaLotacao - 1, -1);
                    atualizarDestaqueLotacao();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (sugestaoAtivaLotacao >= 0 && sugestaoAtivaLotacao < itens.length) {
                        const item = itens[sugestaoAtivaLotacao];
                        const id = item.getAttribute('data-id');
                        const nome = item.getAttribute('data-nome');
                        selecionarEscolaLotacao(id, nome);
                    }
                    break;
                case 'Escape':
                    sugestoes.classList.add('hidden');
                    sugestaoAtivaLotacao = -1;
                    break;
            }
        }

        // Função para atualizar destaque das sugestões lotação
        function atualizarDestaqueLotacao() {
            const itens = document.querySelectorAll('#sugestoes_lotacao .sugestao-item');
            itens.forEach((item, index) => {
                if (index === sugestaoAtivaLotacao) {
                    item.classList.add('bg-black', 'bg-opacity-10', 'dark:bg-white', 'dark:bg-opacity-20');
                    item.classList.remove('hover:bg-black', 'hover:bg-opacity-5', 'dark:hover:bg-white', 'dark:hover:bg-opacity-10');
                } else {
                    item.classList.remove('bg-black', 'bg-opacity-10', 'dark:bg-white', 'dark:bg-opacity-20');
                    item.classList.add('hover:bg-black', 'hover:bg-opacity-5', 'dark:hover:bg-white', 'dark:hover:bg-opacity-10');
                }
            });
        }


        // Função para validar seleção de gestor
        function validarSelecaoGestor() {
            const gestorSelecionado = document.getElementById('gestor-selecionado');
            const tipoGestor = document.getElementById('tipo_gestor');
            const btnAdicionar = document.getElementById('btn-adicionar-gestor');
            
            if (gestorSelecionado && tipoGestor && btnAdicionar) {
                const gestorValido = !gestorSelecionado.classList.contains('hidden');
                const tipoValido = tipoGestor.value !== '';
                
                if (gestorValido && tipoValido) {
                    btnAdicionar.disabled = false;
                    btnAdicionar.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    btnAdicionar.disabled = true;
                    btnAdicionar.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        }

        // Função para mostrar tab (já definida no início do script no escopo global)
        // A função principal está definida no primeiro script para garantir disponibilidade imediata
        // Esta definição serve apenas como backup/atualização se necessário
        if (typeof window.showTab === 'undefined') {
            window.showTab = function(tabId) {
                try {
                    // Esconder todas as tabs
                    document.querySelectorAll('.tab-content').forEach(tab => {
                        tab.classList.remove('active');
                        tab.classList.add('hidden');
                    });
                    
                    // Remover classe ativa de todos os botões
                    document.querySelectorAll('.tab-btn').forEach(btn => {
                        btn.classList.remove('tab-active');
                    });
                    
                    // Mostrar a tab selecionada
                    const tabSelecionada = document.getElementById(tabId);
                    if (tabSelecionada) {
                        tabSelecionada.classList.remove('hidden');
                        tabSelecionada.classList.add('active');
                    }
                    
                    // Adicionar classe ativa ao botão clicado
                    if (event && event.currentTarget) {
                        event.currentTarget.classList.add('tab-active');
                    }
                    
                    // Resetar formulário quando mudar para a aba de adicionar gestor
                    if (tabId === 'tab-adicionar-gestor') {
                        setTimeout(() => {
                            try {
                                const elementosGestor = [
                                    'escola_gestor',
                                    'buscar_escola_gestor',
                                    'info-escola-gestor', 
                                    'passo-selecionar-gestor'
                                ];
                                
                                elementosGestor.forEach(id => {
                                    const elemento = document.getElementById(id);
                                    if (elemento) {
                                        if (id === 'escola_gestor' || id === 'buscar_escola_gestor') {
                                            elemento.value = '';
                                            if (id === 'escola_gestor') {
                                                elemento.size = 1;
                                            }
                                        } else {
                                            elemento.classList.add('hidden');
                                        }
                                    }
                                });
                                
                                if (document.getElementById('buscar_gestor') && typeof limparSelecaoGestor === 'function') {
                                    limparSelecaoGestor();
                                }
                            } catch (e) {
                                console.log('Elementos de gestor ainda não carregados');
                            }
                        }, 100);
                    }
                    
                    // Resetar formulário quando mudar para a aba de lotação
                    if (tabId === 'tab-lotacao') {
                        setTimeout(() => {
                            try {
                                const elementosLotacao = [
                                    'escola_lotacao',
                                    'buscar_escola_lotacao',
                                    'info-escola-lotacao',
                                    'secao-lotacao'
                                ];
                                
                                elementosLotacao.forEach(id => {
                                    const elemento = document.getElementById(id);
                                    if (elemento) {
                                        if (id === 'escola_lotacao' || id === 'buscar_escola_lotacao') {
                                            elemento.value = '';
                                            if (id === 'escola_lotacao') {
                                                elemento.size = 1;
                                            }
                                        } else {
                                            elemento.classList.add('hidden');
                                        }
                                    }
                                });
                            } catch (e) {
                                console.log('Elementos de lotação ainda não carregados');
                            }
                        }, 100);
                    }
                } catch (error) {
                    console.error('Erro na função showTab:', error);
                }
            };
        }
    </script>

    <!-- Script para toggleSidebar global -->
    <script>
        // Função SIMPLES para toggleSidebar
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const main = document.querySelector('main');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
                
                // Adicionar/remover opacidade no conteúdo principal (incluindo header)
                if (main) {
                    main.classList.toggle('content-dimmed');
                }
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
        
        // Fechar sidebar ao clicar no overlay
        const overlay = document.getElementById('mobileOverlay');
        if (overlay) {
            overlay.addEventListener('click', function() {
                window.toggleSidebar();
            });
        }
        
        // Manter posição do scroll do sidebar ao navegar
        (function() {
            const sidebarNav = document.querySelector('.sidebar-nav') || document.querySelector('nav');
            if (!sidebarNav) return;
            
            // Salvar posição do scroll antes de navegar
            const sidebarLinks = sidebarNav.querySelectorAll('a[href]');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Salvar posição do scroll no sessionStorage
                    sessionStorage.setItem('sidebarScroll', sidebarNav.scrollTop);
                });
            });
            
            // Restaurar posição do scroll após carregar a página
            window.addEventListener('load', function() {
                const savedScroll = sessionStorage.getItem('sidebarScroll');
                if (savedScroll !== null) {
                    sidebarNav.scrollTop = parseInt(savedScroll, 10);
                }
            });
            
            // Também restaurar no DOMContentLoaded para ser mais rápido
            document.addEventListener('DOMContentLoaded', function() {
                const savedScroll = sessionStorage.getItem('sidebarScroll');
                if (savedScroll !== null) {
                    sidebarNav.scrollTop = parseInt(savedScroll, 10);
                }
            });
        })();
    </script>
    
    <!-- Modal de Logout -->
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

    <!-- User Profile Modal -->
    <!-- User Profile Modal Component -->

</body>
</html>
