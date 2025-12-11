<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Apenas ADM pode acessar
if (!eAdm()) {
    header('Location: ../auth/login.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Processar ações
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'reverter_exclusao' && isset($_POST['backup_id'])) {
        $backupId = $_POST['backup_id'];
        
        // Buscar backup
        $stmt = $conn->prepare("SELECT * FROM escola_backup WHERE id = :id AND revertido = 0 AND excluido_permanentemente = 0");
        $stmt->bindParam(':id', $backupId, PDO::PARAM_INT);
        $stmt->execute();
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($backup) {
            try {
                $conn->beginTransaction();
                
                $dadosEscola = json_decode($backup['dados_escola'], true);
                $backupCompleto = json_decode($backup['dados_turmas'], true) ?: [];
                $dadosLotacoes = json_decode($backup['dados_lotacoes'], true) ?: [];
                
                // Extrair dados do backup completo
                $dadosTurmas = $backupCompleto['turmas'] ?? [];
                $dadosAlunos = $backupCompleto['alunos'] ?? [];
                $dadosAlunoTurma = $backupCompleto['aluno_turma'] ?? [];
                $dadosNotas = $backupCompleto['notas'] ?? [];
                $dadosFrequencia = $backupCompleto['frequencia'] ?? [];
                $dadosAvaliacao = $backupCompleto['avaliacao'] ?? [];
                $dadosBoletim = $backupCompleto['boletim'] ?? [];
                $dadosBoletimItem = $backupCompleto['boletim_item'] ?? [];
                $dadosEntrega = $backupCompleto['entrega'] ?? [];
                $dadosEntregaItem = $backupCompleto['entrega_item'] ?? [];
                $dadosCardapio = $backupCompleto['cardapio'] ?? [];
                $dadosCardapioItem = $backupCompleto['cardapio_item'] ?? [];
                $dadosConsumo = $backupCompleto['consumo_diario'] ?? [];
                $dadosConsumoItem = $backupCompleto['consumo_item'] ?? [];
                
                // Verificar se a escola já existe
                $stmtCheck = $conn->prepare("SELECT id FROM escola WHERE id = :id");
                $stmtCheck->bindParam(':id', $dadosEscola['id'], PDO::PARAM_INT);
                $stmtCheck->execute();
                $escolaExistente = $stmtCheck->fetch();
                
                if (!$escolaExistente) {
                    // Escola não existe - inserir
                    $sqlEscola = "INSERT INTO escola (id, codigo, nome, endereco, numero, complemento, bairro, municipio, estado, cep, telefone, telefone_secundario, email, site, cnpj, diretor_id, qtd_salas, obs, ativo, criado_em, atualizado_em, atualizado_por) 
                                 VALUES (:id, :codigo, :nome, :endereco, :numero, :complemento, :bairro, :municipio, :estado, :cep, :telefone, :telefone_secundario, :email, :site, :cnpj, :diretor_id, :qtd_salas, :obs, :ativo, :criado_em, :atualizado_em, :atualizado_por)";
                    $stmtEscola = $conn->prepare($sqlEscola);
                    $stmtEscola->bindValue(':id', $dadosEscola['id'] ?? null, PDO::PARAM_INT);
                    $stmtEscola->bindValue(':codigo', $dadosEscola['codigo'] ?? null);
                    $stmtEscola->bindValue(':nome', $dadosEscola['nome'] ?? null);
                    $stmtEscola->bindValue(':endereco', $dadosEscola['endereco'] ?? null);
                    $stmtEscola->bindValue(':numero', $dadosEscola['numero'] ?? null);
                    $stmtEscola->bindValue(':complemento', $dadosEscola['complemento'] ?? null);
                    $stmtEscola->bindValue(':bairro', $dadosEscola['bairro'] ?? null);
                    $stmtEscola->bindValue(':municipio', $dadosEscola['municipio'] ?? null);
                    $stmtEscola->bindValue(':estado', $dadosEscola['estado'] ?? null);
                    $stmtEscola->bindValue(':cep', $dadosEscola['cep'] ?? null);
                    $stmtEscola->bindValue(':telefone', $dadosEscola['telefone'] ?? null);
                    $stmtEscola->bindValue(':telefone_secundario', $dadosEscola['telefone_secundario'] ?? null);
                    $stmtEscola->bindValue(':email', $dadosEscola['email'] ?? null);
                    $stmtEscola->bindValue(':site', $dadosEscola['site'] ?? null);
                    $stmtEscola->bindValue(':cnpj', $dadosEscola['cnpj'] ?? null);
                    $stmtEscola->bindValue(':diretor_id', $dadosEscola['diretor_id'] ?? null, PDO::PARAM_INT);
                    $stmtEscola->bindValue(':qtd_salas', $dadosEscola['qtd_salas'] ?? null, PDO::PARAM_INT);
                    $stmtEscola->bindValue(':obs', $dadosEscola['obs'] ?? null);
                    $stmtEscola->bindValue(':ativo', 1, PDO::PARAM_INT);
                    $stmtEscola->bindValue(':criado_em', $dadosEscola['criado_em'] ?? date('Y-m-d H:i:s'));
                    $stmtEscola->bindValue(':atualizado_em', $dadosEscola['atualizado_em'] ?? date('Y-m-d H:i:s'));
                    $stmtEscola->bindValue(':atualizado_por', $dadosEscola['atualizado_por'] ?? null, PDO::PARAM_INT);
                    $stmtEscola->execute();
                } else {
                    // Escola existe - apenas garantir que está ativa
                    $stmtReativar = $conn->prepare("UPDATE escola SET ativo = 1 WHERE id = :id");
                    $stmtReativar->bindParam(':id', $dadosEscola['id'], PDO::PARAM_INT);
                    $stmtReativar->execute();
                }
                
                // Restaurar turmas
                if (!empty($dadosTurmas) && is_array($dadosTurmas)) {
                    foreach ($dadosTurmas as $turma) {
                        if (!is_array($turma)) continue;
                        
                        // Verificar se turma já existe
                        $stmtCheckTurma = $conn->prepare("SELECT id FROM turma WHERE id = :id");
                        $stmtCheckTurma->bindValue(':id', $turma['id'] ?? null, PDO::PARAM_INT);
                        $stmtCheckTurma->execute();
                        
                        if (!$stmtCheckTurma->fetch()) {
                            // Turma não existe - inserir (caso tenha sido excluída permanentemente antes)
                            try {
                                $sqlTurma = "INSERT INTO turma (id, escola_id, serie_id, ano_letivo, serie, letra, turno, capacidade, sala, coordenador_id, observacoes, ativo, criado_em, atualizado_em, atualizado_por) 
                                             VALUES (:id, :escola_id, :serie_id, :ano_letivo, :serie, :letra, :turno, :capacidade, :sala, :coordenador_id, :observacoes, :ativo, :criado_em, :atualizado_em, :atualizado_por)";
                                $stmtTurma = $conn->prepare($sqlTurma);
                                $stmtTurma->bindValue(':id', $turma['id'] ?? null, PDO::PARAM_INT);
                                $stmtTurma->bindValue(':escola_id', $turma['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtTurma->bindValue(':serie_id', $turma['serie_id'] ?? null, PDO::PARAM_INT);
                                $stmtTurma->bindValue(':ano_letivo', $turma['ano_letivo'] ?? null, PDO::PARAM_INT);
                                $stmtTurma->bindValue(':serie', $turma['serie'] ?? null);
                                $stmtTurma->bindValue(':letra', $turma['letra'] ?? null);
                                $stmtTurma->bindValue(':turno', $turma['turno'] ?? null);
                                $stmtTurma->bindValue(':capacidade', $turma['capacidade'] ?? null, PDO::PARAM_INT);
                                $stmtTurma->bindValue(':sala', $turma['sala'] ?? null);
                                $stmtTurma->bindValue(':coordenador_id', $turma['coordenador_id'] ?? null, PDO::PARAM_INT);
                                $stmtTurma->bindValue(':observacoes', $turma['observacoes'] ?? null);
                                $stmtTurma->bindValue(':ativo', $turma['ativo'] ?? 1, PDO::PARAM_INT);
                                $stmtTurma->bindValue(':criado_em', $turma['criado_em'] ?? date('Y-m-d H:i:s'));
                                $stmtTurma->bindValue(':atualizado_em', $turma['atualizado_em'] ?? date('Y-m-d H:i:s'));
                                $stmtTurma->bindValue(':atualizado_por', $turma['atualizado_por'] ?? null, PDO::PARAM_INT);
                                $stmtTurma->execute();
                            } catch (PDOException $e) {
                                // Logar erro mas continuar
                                error_log("Erro ao restaurar turma ID " . ($turma['id'] ?? 'N/A') . ": " . $e->getMessage());
                            }
                        }
                        // Se a turma já existe, não precisa fazer nada - os dados já estão preservados
                    }
                }
                
                // Restaurar lotações de professores
                if (!empty($dadosLotacoes['professores']) && is_array($dadosLotacoes['professores'])) {
                    foreach ($dadosLotacoes['professores'] as $lotacao) {
                        if (!is_array($lotacao)) continue;
                        
                        try {
                            // Verificar se a lotação já existe
                            $stmtCheck = $conn->prepare("SELECT id FROM professor_lotacao WHERE professor_id = :professor_id AND escola_id = :escola_id");
                            $stmtCheck->bindValue(':professor_id', $lotacao['professor_id'] ?? null, PDO::PARAM_INT);
                            $stmtCheck->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            
                            if (!$stmtCheck->fetch()) {
                                $sqlLotacao = "INSERT INTO professor_lotacao (professor_id, escola_id, inicio, fim, carga_horaria, observacao) 
                                               VALUES (:professor_id, :escola_id, :inicio, NULL, :carga_horaria, :observacao)";
                                $stmtLotacao = $conn->prepare($sqlLotacao);
                                $stmtLotacao->bindValue(':professor_id', $lotacao['professor_id'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':inicio', $lotacao['inicio'] ?? date('Y-m-d'));
                                $stmtLotacao->bindValue(':carga_horaria', $lotacao['carga_horaria'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':observacao', $lotacao['observacao'] ?? null);
                                $stmtLotacao->execute();
                            } else {
                                // Se já existe, atualizar para garantir que fim seja NULL
                                $stmtUpdate = $conn->prepare("UPDATE professor_lotacao SET fim = NULL WHERE professor_id = :professor_id AND escola_id = :escola_id");
                                $stmtUpdate->bindValue(':professor_id', $lotacao['professor_id'] ?? null, PDO::PARAM_INT);
                                $stmtUpdate->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtUpdate->execute();
                            }
                        } catch (PDOException $e) {
                            // Logar erro mas continuar
                            error_log("Erro ao restaurar lotação professor (professor_id: " . ($lotacao['professor_id'] ?? 'N/A') . ", escola_id: " . ($lotacao['escola_id'] ?? 'N/A') . "): " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar lotações de gestores
                if (!empty($dadosLotacoes['gestores']) && is_array($dadosLotacoes['gestores'])) {
                    foreach ($dadosLotacoes['gestores'] as $lotacao) {
                        if (!is_array($lotacao)) continue;
                        
                        try {
                            // Verificar se a lotação já existe
                            $stmtCheck = $conn->prepare("SELECT id FROM gestor_lotacao WHERE gestor_id = :gestor_id AND escola_id = :escola_id");
                            $stmtCheck->bindValue(':gestor_id', $lotacao['gestor_id'] ?? null, PDO::PARAM_INT);
                            $stmtCheck->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            
                            if (!$stmtCheck->fetch()) {
                                $sqlLotacao = "INSERT INTO gestor_lotacao (gestor_id, escola_id, inicio, fim, responsavel, tipo, observacoes) 
                                               VALUES (:gestor_id, :escola_id, :inicio, NULL, :responsavel, :tipo, :observacoes)";
                                $stmtLotacao = $conn->prepare($sqlLotacao);
                                $stmtLotacao->bindValue(':gestor_id', $lotacao['gestor_id'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':inicio', $lotacao['inicio'] ?? date('Y-m-d'));
                                $stmtLotacao->bindValue(':responsavel', $lotacao['responsavel'] ?? 0, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':tipo', $lotacao['tipo'] ?? null);
                                $stmtLotacao->bindValue(':observacoes', $lotacao['observacoes'] ?? null);
                                $stmtLotacao->execute();
                            } else {
                                // Se já existe, atualizar para garantir que fim seja NULL
                                $stmtUpdate = $conn->prepare("UPDATE gestor_lotacao SET fim = NULL WHERE gestor_id = :gestor_id AND escola_id = :escola_id");
                                $stmtUpdate->bindValue(':gestor_id', $lotacao['gestor_id'] ?? null, PDO::PARAM_INT);
                                $stmtUpdate->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtUpdate->execute();
                            }
                        } catch (PDOException $e) {
                            // Logar erro mas continuar
                            error_log("Erro ao restaurar lotação gestor (gestor_id: " . ($lotacao['gestor_id'] ?? 'N/A') . ", escola_id: " . ($lotacao['escola_id'] ?? 'N/A') . "): " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar lotações de nutricionistas
                if (!empty($dadosLotacoes['nutricionistas']) && is_array($dadosLotacoes['nutricionistas'])) {
                    foreach ($dadosLotacoes['nutricionistas'] as $lotacao) {
                        if (!is_array($lotacao)) continue;
                        
                        try {
                            // Verificar se a lotação já existe
                            $stmtCheck = $conn->prepare("SELECT id FROM nutricionista_lotacao WHERE nutricionista_id = :nutricionista_id AND escola_id = :escola_id");
                            $stmtCheck->bindValue(':nutricionista_id', $lotacao['nutricionista_id'] ?? null, PDO::PARAM_INT);
                            $stmtCheck->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            
                            if (!$stmtCheck->fetch()) {
                                $sqlLotacao = "INSERT INTO nutricionista_lotacao (nutricionista_id, escola_id, inicio, fim, responsavel, carga_horaria, observacoes) 
                                               VALUES (:nutricionista_id, :escola_id, :inicio, NULL, :responsavel, :carga_horaria, :observacoes)";
                                $stmtLotacao = $conn->prepare($sqlLotacao);
                                $stmtLotacao->bindValue(':nutricionista_id', $lotacao['nutricionista_id'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':inicio', $lotacao['inicio'] ?? date('Y-m-d'));
                                $stmtLotacao->bindValue(':responsavel', $lotacao['responsavel'] ?? 0, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':carga_horaria', $lotacao['carga_horaria'] ?? null, PDO::PARAM_INT);
                                $stmtLotacao->bindValue(':observacoes', $lotacao['observacoes'] ?? null);
                                $stmtLotacao->execute();
                            } else {
                                // Se já existe, atualizar para garantir que fim seja NULL
                                $stmtUpdate = $conn->prepare("UPDATE nutricionista_lotacao SET fim = NULL WHERE nutricionista_id = :nutricionista_id AND escola_id = :escola_id");
                                $stmtUpdate->bindValue(':nutricionista_id', $lotacao['nutricionista_id'] ?? null, PDO::PARAM_INT);
                                $stmtUpdate->bindValue(':escola_id', $lotacao['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmtUpdate->execute();
                            }
                        } catch (PDOException $e) {
                            // Logar erro mas continuar
                            error_log("Erro ao restaurar lotação nutricionista (nutricionista_id: " . ($lotacao['nutricionista_id'] ?? 'N/A') . ", escola_id: " . ($lotacao['escola_id'] ?? 'N/A') . "): " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar dados relacionados às turmas (se existirem no backup)
                $backupCompleto = json_decode($backup['dados_turmas'], true) ?: [];
                $dadosAlunoTurma = $backupCompleto['aluno_turma'] ?? [];
                $dadosNotas = $backupCompleto['notas'] ?? [];
                $dadosFrequencia = $backupCompleto['frequencia'] ?? [];
                $dadosAvaliacao = $backupCompleto['avaliacao'] ?? [];
                $dadosBoletim = $backupCompleto['boletim'] ?? [];
                $dadosBoletimItem = $backupCompleto['boletim_item'] ?? [];
                $dadosEntrega = $backupCompleto['entrega'] ?? [];
                $dadosEntregaItem = $backupCompleto['entrega_item'] ?? [];
                $dadosCardapio = $backupCompleto['cardapio'] ?? [];
                $dadosCardapioItem = $backupCompleto['cardapio_item'] ?? [];
                $dadosConsumo = $backupCompleto['consumo_diario'] ?? [];
                $dadosConsumoItem = $backupCompleto['consumo_item'] ?? [];
                $dadosAlunos = $backupCompleto['alunos'] ?? [];
                
                // Restaurar aluno_turma
                if (!empty($dadosAlunoTurma) && is_array($dadosAlunoTurma)) {
                    foreach ($dadosAlunoTurma as $at) {
                        if (!is_array($at)) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM aluno_turma WHERE aluno_id = :aluno_id AND turma_id = :turma_id");
                            $stmtCheck->bindValue(':aluno_id', $at['aluno_id'] ?? null, PDO::PARAM_INT);
                            $stmtCheck->bindValue(':turma_id', $at['turma_id'] ?? null, PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO aluno_turma (aluno_id, turma_id, ano_letivo, situacao, data_matricula, data_saida, observacoes) 
                                        VALUES (:aluno_id, :turma_id, :ano_letivo, :situacao, :data_matricula, :data_saida, :observacoes)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':aluno_id', $at['aluno_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':turma_id', $at['turma_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':ano_letivo', $at['ano_letivo'] ?? null);
                                $stmt->bindValue(':situacao', $at['situacao'] ?? 'MATRICULADO');
                                $stmt->bindValue(':data_matricula', $at['data_matricula'] ?? null);
                                $stmt->bindValue(':data_saida', $at['data_saida'] ?? null);
                                $stmt->bindValue(':observacoes', $at['observacoes'] ?? null);
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar aluno_turma: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar notas
                if (!empty($dadosNotas) && is_array($dadosNotas)) {
                    foreach ($dadosNotas as $nota) {
                        if (!is_array($nota) || !isset($nota['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM nota WHERE id = :id");
                            $stmtCheck->bindValue(':id', $nota['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO nota (id, aluno_id, turma_id, disciplina_id, avaliacao_id, nota, peso, observacoes, criado_em) 
                                        VALUES (:id, :aluno_id, :turma_id, :disciplina_id, :avaliacao_id, :nota, :peso, :observacoes, :criado_em)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $nota['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':aluno_id', $nota['aluno_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':turma_id', $nota['turma_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':disciplina_id', $nota['disciplina_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':avaliacao_id', $nota['avaliacao_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':nota', $nota['nota'] ?? null);
                                $stmt->bindValue(':peso', $nota['peso'] ?? null);
                                $stmt->bindValue(':observacoes', $nota['observacoes'] ?? null);
                                $stmt->bindValue(':criado_em', $nota['criado_em'] ?? date('Y-m-d H:i:s'));
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar nota: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar frequências
                if (!empty($dadosFrequencia) && is_array($dadosFrequencia)) {
                    foreach ($dadosFrequencia as $freq) {
                        if (!is_array($freq) || !isset($freq['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM frequencia WHERE id = :id");
                            $stmtCheck->bindValue(':id', $freq['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO frequencia (id, aluno_id, turma_id, data, presente, justificativa_id, observacoes) 
                                        VALUES (:id, :aluno_id, :turma_id, :data, :presente, :justificativa_id, :observacoes)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $freq['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':aluno_id', $freq['aluno_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':turma_id', $freq['turma_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':data', $freq['data'] ?? null);
                                $stmt->bindValue(':presente', $freq['presente'] ?? 1, PDO::PARAM_INT);
                                $stmt->bindValue(':justificativa_id', $freq['justificativa_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':observacoes', $freq['observacoes'] ?? null);
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar frequencia: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar avaliações
                if (!empty($dadosAvaliacao) && is_array($dadosAvaliacao)) {
                    foreach ($dadosAvaliacao as $av) {
                        if (!is_array($av) || !isset($av['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM avaliacao WHERE id = :id");
                            $stmtCheck->bindValue(':id', $av['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO avaliacao (id, turma_id, disciplina_id, tipo, data, descricao, peso, criado_em) 
                                        VALUES (:id, :turma_id, :disciplina_id, :tipo, :data, :descricao, :peso, :criado_em)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $av['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':turma_id', $av['turma_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':disciplina_id', $av['disciplina_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':tipo', $av['tipo'] ?? null);
                                $stmt->bindValue(':data', $av['data'] ?? null);
                                $stmt->bindValue(':descricao', $av['descricao'] ?? null);
                                $stmt->bindValue(':peso', $av['peso'] ?? null);
                                $stmt->bindValue(':criado_em', $av['criado_em'] ?? date('Y-m-d H:i:s'));
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar avaliacao: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar boletins
                if (!empty($dadosBoletim) && is_array($dadosBoletim)) {
                    foreach ($dadosBoletim as $boletim) {
                        if (!is_array($boletim) || !isset($boletim['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM boletim WHERE id = :id");
                            $stmtCheck->bindValue(':id', $boletim['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO boletim (id, aluno_id, turma_id, ano_letivo, bimestre, media_geral, situacao, observacoes, criado_em) 
                                        VALUES (:id, :aluno_id, :turma_id, :ano_letivo, :bimestre, :media_geral, :situacao, :observacoes, :criado_em)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $boletim['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':aluno_id', $boletim['aluno_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':turma_id', $boletim['turma_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':ano_letivo', $boletim['ano_letivo'] ?? null);
                                $stmt->bindValue(':bimestre', $boletim['bimestre'] ?? null);
                                $stmt->bindValue(':media_geral', $boletim['media_geral'] ?? null);
                                $stmt->bindValue(':situacao', $boletim['situacao'] ?? null);
                                $stmt->bindValue(':observacoes', $boletim['observacoes'] ?? null);
                                $stmt->bindValue(':criado_em', $boletim['criado_em'] ?? date('Y-m-d H:i:s'));
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar boletim: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar itens de boletim
                if (!empty($dadosBoletimItem) && is_array($dadosBoletimItem)) {
                    foreach ($dadosBoletimItem as $bi) {
                        if (!is_array($bi) || !isset($bi['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM boletim_item WHERE id = :id");
                            $stmtCheck->bindValue(':id', $bi['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO boletim_item (id, boletim_id, disciplina_id, nota_final, frequencia, situacao) 
                                        VALUES (:id, :boletim_id, :disciplina_id, :nota_final, :frequencia, :situacao)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $bi['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':boletim_id', $bi['boletim_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':disciplina_id', $bi['disciplina_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':nota_final', $bi['nota_final'] ?? null);
                                $stmt->bindValue(':frequencia', $bi['frequencia'] ?? null);
                                $stmt->bindValue(':situacao', $bi['situacao'] ?? null);
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar boletim_item: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar entregas
                if (!empty($dadosEntrega) && is_array($dadosEntrega)) {
                    foreach ($dadosEntrega as $entrega) {
                        if (!is_array($entrega) || !isset($entrega['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM entrega WHERE id = :id");
                            $stmtCheck->bindValue(':id', $entrega['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO entrega (id, escola_id, data_entrega, quantidade_total, observacoes, criado_em) 
                                        VALUES (:id, :escola_id, :data_entrega, :quantidade_total, :observacoes, :criado_em)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $entrega['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':escola_id', $entrega['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':data_entrega', $entrega['data_entrega'] ?? null);
                                $stmt->bindValue(':quantidade_total', $entrega['quantidade_total'] ?? null);
                                $stmt->bindValue(':observacoes', $entrega['observacoes'] ?? null);
                                $stmt->bindValue(':criado_em', $entrega['criado_em'] ?? date('Y-m-d H:i:s'));
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar entrega: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar itens de entrega
                if (!empty($dadosEntregaItem) && is_array($dadosEntregaItem)) {
                    foreach ($dadosEntregaItem as $ei) {
                        if (!is_array($ei) || !isset($ei['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM entrega_item WHERE id = :id");
                            $stmtCheck->bindValue(':id', $ei['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO entrega_item (id, entrega_id, item_id, quantidade, observacoes) 
                                        VALUES (:id, :entrega_id, :item_id, :quantidade, :observacoes)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $ei['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':entrega_id', $ei['entrega_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':item_id', $ei['item_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':quantidade', $ei['quantidade'] ?? null);
                                $stmt->bindValue(':observacoes', $ei['observacoes'] ?? null);
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar entrega_item: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar cardápios
                if (!empty($dadosCardapio) && is_array($dadosCardapio)) {
                    foreach ($dadosCardapio as $cardapio) {
                        if (!is_array($cardapio) || !isset($cardapio['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM cardapio WHERE id = :id");
                            $stmtCheck->bindValue(':id', $cardapio['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO cardapio (id, escola_id, data, tipo_refeicao, observacoes, criado_em) 
                                        VALUES (:id, :escola_id, :data, :tipo_refeicao, :observacoes, :criado_em)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $cardapio['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':escola_id', $cardapio['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':data', $cardapio['data'] ?? null);
                                $stmt->bindValue(':tipo_refeicao', $cardapio['tipo_refeicao'] ?? null);
                                $stmt->bindValue(':observacoes', $cardapio['observacoes'] ?? null);
                                $stmt->bindValue(':criado_em', $cardapio['criado_em'] ?? date('Y-m-d H:i:s'));
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar cardapio: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar itens de cardápio
                if (!empty($dadosCardapioItem) && is_array($dadosCardapioItem)) {
                    foreach ($dadosCardapioItem as $ci) {
                        if (!is_array($ci) || !isset($ci['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM cardapio_item WHERE id = :id");
                            $stmtCheck->bindValue(':id', $ci['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO cardapio_item (id, cardapio_id, alimento_id, quantidade, unidade) 
                                        VALUES (:id, :cardapio_id, :alimento_id, :quantidade, :unidade)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $ci['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':cardapio_id', $ci['cardapio_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':alimento_id', $ci['alimento_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':quantidade', $ci['quantidade'] ?? null);
                                $stmt->bindValue(':unidade', $ci['unidade'] ?? null);
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar cardapio_item: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar consumo diário
                if (!empty($dadosConsumo) && is_array($dadosConsumo)) {
                    foreach ($dadosConsumo as $consumo) {
                        if (!is_array($consumo) || !isset($consumo['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM consumo_diario WHERE id = :id");
                            $stmtCheck->bindValue(':id', $consumo['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO consumo_diario (id, escola_id, turma_id, data, tipo_refeicao, quantidade_alunos, observacoes) 
                                        VALUES (:id, :escola_id, :turma_id, :data, :tipo_refeicao, :quantidade_alunos, :observacoes)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $consumo['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':escola_id', $consumo['escola_id'] ?? $dadosEscola['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':turma_id', $consumo['turma_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':data', $consumo['data'] ?? null);
                                $stmt->bindValue(':tipo_refeicao', $consumo['tipo_refeicao'] ?? null);
                                $stmt->bindValue(':quantidade_alunos', $consumo['quantidade_alunos'] ?? null);
                                $stmt->bindValue(':observacoes', $consumo['observacoes'] ?? null);
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar consumo_diario: " . $e->getMessage());
                        }
                    }
                }
                
                // Restaurar itens de consumo
                if (!empty($dadosConsumoItem) && is_array($dadosConsumoItem)) {
                    foreach ($dadosConsumoItem as $cItem) {
                        if (!is_array($cItem) || !isset($cItem['id'])) continue;
                        try {
                            $stmtCheck = $conn->prepare("SELECT id FROM consumo_item WHERE id = :id");
                            $stmtCheck->bindValue(':id', $cItem['id'], PDO::PARAM_INT);
                            $stmtCheck->execute();
                            if (!$stmtCheck->fetch()) {
                                $sql = "INSERT INTO consumo_item (id, consumo_diario_id, alimento_id, quantidade, unidade) 
                                        VALUES (:id, :consumo_diario_id, :alimento_id, :quantidade, :unidade)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bindValue(':id', $cItem['id'], PDO::PARAM_INT);
                                $stmt->bindValue(':consumo_diario_id', $cItem['consumo_diario_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':alimento_id', $cItem['alimento_id'] ?? null, PDO::PARAM_INT);
                                $stmt->bindValue(':quantidade', $cItem['quantidade'] ?? null);
                                $stmt->bindValue(':unidade', $cItem['unidade'] ?? null);
                                $stmt->execute();
                            }
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar consumo_item: " . $e->getMessage());
                        }
                    }
                }
                
                // Atualizar alunos para vincular à escola novamente
                if (!empty($dadosAlunos) && is_array($dadosAlunos)) {
                    foreach ($dadosAlunos as $aluno) {
                        if (!is_array($aluno) || !isset($aluno['id'])) continue;
                        try {
                            $stmt = $conn->prepare("UPDATE aluno SET escola_id = :escola_id WHERE id = :id");
                            $stmt->bindValue(':escola_id', $dadosEscola['id'], PDO::PARAM_INT);
                            $stmt->bindValue(':id', $aluno['id'], PDO::PARAM_INT);
                            $stmt->execute();
                        } catch (PDOException $e) {
                            error_log("Erro ao restaurar escola_id do aluno: " . $e->getMessage());
                        }
                    }
                }
                
                // Marcar backup como revertido
                $usuarioId = $_SESSION['usuario_id'] ?? null;
                $stmtUpdate = $conn->prepare("UPDATE escola_backup SET revertido = 1, revertido_em = NOW(), revertido_por = :usuario_id WHERE id = :id");
                $stmtUpdate->bindParam(':id', $backupId, PDO::PARAM_INT);
                $stmtUpdate->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                $stmtUpdate->execute();
                
                $conn->commit();
                $mensagem = 'Exclusão revertida com sucesso! Todos os dados da escola foram restaurados.';
                $tipoMensagem = 'success';
            } catch (PDOException $e) {
                $conn->rollBack();
                $mensagem = 'Erro ao reverter exclusão: ' . $e->getMessage();
                $tipoMensagem = 'error';
            }
        } else {
            $mensagem = 'Backup não encontrado ou já foi revertido/excluído.';
            $tipoMensagem = 'error';
        }
    } elseif ($_POST['acao'] === 'excluir_permanentemente' && isset($_POST['backup_id'])) {
        $backupId = $_POST['backup_id'];
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        
        try {
            $stmt = $conn->prepare("UPDATE escola_backup SET excluido_permanentemente = 1 WHERE id = :id");
            $stmt->bindParam(':id', $backupId, PDO::PARAM_INT);
            $stmt->execute();
            
            $mensagem = 'Backup excluído permanentemente.';
            $tipoMensagem = 'success';
        } catch (PDOException $e) {
            $mensagem = 'Erro ao excluir permanentemente: ' . $e->getMessage();
            $tipoMensagem = 'error';
        }
    }
}

// Limpar backups antigos (mais de 30 dias e não revertidos)
try {
    $stmtLimpar = $conn->prepare("UPDATE escola_backup 
                                  SET excluido_permanentemente = 1 
                                  WHERE excluido_em < DATE_SUB(NOW(), INTERVAL 30 DAY) 
                                  AND revertido = 0 
                                  AND excluido_permanentemente = 0");
    $stmtLimpar->execute();
} catch (PDOException $e) {
    error_log("Erro ao limpar backups antigos: " . $e->getMessage());
}

// Buscar ações dos últimos 30 dias
$stmtAcoes = $conn->prepare("SELECT eb.*, 
                             p1.nome as excluido_por_nome,
                             p2.nome as revertido_por_nome,
                             DATEDIFF(NOW(), eb.excluido_em) as dias_restantes
                             FROM escola_backup eb
                             LEFT JOIN usuario u1 ON eb.excluido_por = u1.id
                             LEFT JOIN pessoa p1 ON u1.pessoa_id = p1.id
                             LEFT JOIN usuario u2 ON eb.revertido_por = u2.id
                             LEFT JOIN pessoa p2 ON u2.pessoa_id = p2.id
                             WHERE eb.excluido_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                             AND eb.excluido_permanentemente = 0
                             ORDER BY eb.excluido_em DESC");
$stmtAcoes->execute();
$acoes = $stmtAcoes->fetchAll(PDO::FETCH_ASSOC);

// Decodificar dados para exibição
foreach ($acoes as &$acao) {
    $dadosEscola = json_decode($acao['dados_escola'], true);
    $acao['escola_nome'] = $dadosEscola['nome'] ?? 'N/A';
    $acao['escola_codigo'] = $dadosEscola['codigo'] ?? 'N/A';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ações do Sistema - SIGAE</title>
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
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_adm.php'; ?>
    
    <main class="content-transition ml-0 lg:ml-64 min-h-screen">
        <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Ações do Sistema</h1>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 rounded-lg <?= $tipoMensagem === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Exclusões de Escolas (Últimos 30 dias)</h2>
                    <p class="text-gray-600 mb-6">
                        Escolas excluídas nos últimos 30 dias podem ser revertidas. Após 30 dias, serão excluídas permanentemente automaticamente.
                    </p>
                    
                    <?php if (empty($acoes)): ?>
                        <div class="text-center py-12">
                            <p class="text-gray-500">Nenhuma exclusão de escola nos últimos 30 dias.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Escola</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Excluído Por</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Data Exclusão</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Dias Restantes</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                        <th class="text-left py-3 px-4 font-semibold text-gray-700">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($acoes as $acao): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="py-3 px-4">
                                                <div>
                                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($acao['escola_nome']) ?></p>
                                                    <p class="text-sm text-gray-500">Código: <?= htmlspecialchars($acao['escola_codigo']) ?></p>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?= htmlspecialchars($acao['excluido_por_nome'] ?? 'Sistema') ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?= date('d/m/Y H:i', strtotime($acao['excluido_em'])) ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php 
                                                $diasRestantes = 30 - (int)$acao['dias_restantes'];
                                                if ($diasRestantes > 0) {
                                                    echo '<span class="text-orange-600 font-semibold">' . $diasRestantes . ' dias</span>';
                                                } else {
                                                    echo '<span class="text-red-600 font-semibold">Expirado</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if ($acao['revertido']): ?>
                                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Revertido
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Excluído
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if (!$acao['revertido']): ?>
                                                    <div class="flex space-x-2">
                                                        <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja reverter a exclusão desta escola?')">
                                                            <input type="hidden" name="acao" value="reverter_exclusao">
                                                            <input type="hidden" name="backup_id" value="<?= $acao['id'] ?>">
                                                            <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                                                Reverter
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir permanentemente? Esta ação não pode ser desfeita!')">
                                                            <input type="hidden" name="acao" value="excluir_permanentemente">
                                                            <input type="hidden" name="backup_id" value="<?= $acao['id'] ?>">
                                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                                                                Excluir Permanentemente
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-sm">Revertido em <?= date('d/m/Y H:i', strtotime($acao['revertido_em'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
            }
        };
    </script>
</body>
</html>

