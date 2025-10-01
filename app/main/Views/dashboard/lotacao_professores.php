<?php
session_start();

// Verificar se o usuário está logado e tem permissão
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'ADM') {
    header('Location: ../../auth/login.php');
    exit();
}

// Incluir arquivos necessários
require_once '../../Controllers/gestao/ProfessorController.php';
require_once '../../Controllers/gestao/EscolaController.php';

// Funções para buscar dados
function listarEscolas() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT id, nome, endereco, municipio FROM escola ORDER BY nome ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



function buscarLotacoesProfessor($professor_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT pl.id, pl.escola_id, e.nome as escola_nome, e.municipio,
                   pl.data_inicio as inicio, pl.data_fim as fim, pl.carga_horaria, pl.observacoes as observacao
            FROM professor_lotacao pl
            INNER JOIN escola e ON pl.escola_id = e.id
            WHERE pl.professor_id = :professor_id
            ORDER BY pl.data_inicio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':professor_id', $professor_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Processar dados recebidos via POST

// Processar formulário de lotação múltipla
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'lotar_multiplas') {
    $professor_id = $_POST['professor_id'];
    $escolas_selecionadas = $_POST['escolas'] ?? [];
    $data_inicio = $_POST['data_inicio'];
    $carga_horaria = $_POST['carga_horaria'] ?? 20;
    $observacao = $_POST['observacao'] ?? '';
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $conn->beginTransaction();
        
        $sucessos = 0;
        $erros = [];
        
        foreach ($escolas_selecionadas as $escola_id) {
            // Verificar se já existe lotação ativa
            $stmt = $conn->prepare("SELECT id FROM professor_lotacao 
                                   WHERE professor_id = :professor_id 
                                   AND escola_id = :escola_id 
                                   AND data_fim IS NULL");
            $stmt->bindParam(':professor_id', $professor_id);
            $stmt->bindParam(':escola_id', $escola_id);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $erros[] = "Professor já está lotado na escola ID: $escola_id";
                continue;
            }
            
            // Inserir nova lotação
            $stmt = $conn->prepare("INSERT INTO professor_lotacao 
                                   (professor_id, escola_id, data_inicio, carga_horaria, observacoes) 
                                   VALUES (:professor_id, :escola_id, :data_inicio, :carga_horaria, :observacoes)");
            $stmt->bindParam(':professor_id', $professor_id);
            $stmt->bindParam(':escola_id', $escola_id);
            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':carga_horaria', $carga_horaria);
            $stmt->bindParam(':observacoes', $observacao);
            $stmt->execute();
            
            $sucessos++;
        }
        
        $conn->commit();
        
        if ($sucessos > 0) {
            $mensagem_sucesso = "Professor lotado com sucesso em $sucessos escola(s)!";
        }
        if (!empty($erros)) {
            $mensagem_erro = implode('<br>', $erros);
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        $mensagem_erro = "Erro ao processar lotações: " . $e->getMessage();
    }
}

$professores = listarProfessores();
$escolas = listarEscolas();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lotação de Professores - Sistema Escolar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-green': '#22c55e',
                        'primary-blue': '#3b82f6'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Lotação de Professores</h1>
                </div>
                <div class="text-sm text-gray-600">
                    Usuário: <?php echo htmlspecialchars($_SESSION['nome_usuario']); ?>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Mensagens -->
        <?php if (isset($mensagem_sucesso)): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <?php echo $mensagem_sucesso; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($mensagem_erro)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <?php echo $mensagem_erro; ?>
            </div>
        <?php endif; ?>



        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Formulário de Lotação Múltipla -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Nova Lotação Múltipla</h2>
                    <p class="text-sm text-gray-600 mt-1">Associe um professor a múltiplas escolas</p>
                </div>

                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="acao" value="lotar_multiplas">

                    <!-- Seleção do Professor -->
                    <div>
                        <label for="professor_busca" class="block text-sm font-medium text-gray-700 mb-2">
                            Professor *
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   id="professor_busca" 
                                   placeholder="Digite o nome do professor para buscar..."
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                   oninput="buscarProfessores(this.value)"
                                   autocomplete="off">
                            <input type="hidden" id="professor_id" name="professor_id" required>
                            
                            <!-- Dropdown de resultados -->
                            <div id="professor_dropdown" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden">
                                <div class="p-3 text-gray-500 text-sm">Digite para buscar professores...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Seleção de Escolas -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Escolas * <span class="text-xs text-gray-500">(Selecione uma ou mais)</span>
                        </label>
                        <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-lg p-3 space-y-2">
                            <?php foreach ($escolas as $escola): ?>
                                <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                    <input type="checkbox" name="escolas[]" value="<?php echo $escola['id']; ?>"
                                           class="h-4 w-4 text-primary-green focus:ring-primary-green border-gray-300 rounded">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($escola['nome']); ?>
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            <?php echo htmlspecialchars($escola['endereco']); ?> - <?php echo htmlspecialchars($escola['municipio']); ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Data de Início -->
                    <div>
                        <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-2">
                            Data de Início *
                        </label>
                        <input type="date" id="data_inicio" name="data_inicio" required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                    </div>

                    <!-- Carga Horária -->
                    <div>
                        <label for="carga_horaria" class="block text-sm font-medium text-gray-700 mb-2">
                            Carga Horária (horas/semana)
                        </label>
                        <input type="number" id="carga_horaria" name="carga_horaria" min="1" max="40"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent">
                    </div>

                    <!-- Observações -->
                    <div>
                        <label for="observacao" class="block text-sm font-medium text-gray-700 mb-2">
                            Observações
                        </label>
                        <textarea id="observacao" name="observacao" rows="3"
                                  class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-green focus:border-transparent"
                                  placeholder="Observações sobre a lotação..."></textarea>
                    </div>

                    <!-- Botão de Submissão -->
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-primary-green text-white px-6 py-2.5 rounded-lg hover:bg-green-600 transition-colors duration-200 font-medium flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>Criar Lotações</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lotações Atuais do Professor -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Lotações Atuais</h2>
                    <p class="text-sm text-gray-600 mt-1">Visualize as lotações do professor selecionado</p>
                </div>

                <div id="lotacoes-professor" class="p-6">
                    <div class="text-center text-gray-500 py-8">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p>Selecione um professor para visualizar suas lotações</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, inicializando scripts...');
            
        function carregarLotacoesProfessor(professorId) {
            const container = document.getElementById('lotacoes-professor');
            
            if (!professorId) {
                container.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p>Selecione um professor para visualizar suas lotações</p>
                    </div>
                `;
                return;
            }

            // Mostrar loading
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-green mx-auto"></div>
                    <p class="text-gray-600 mt-2">Carregando lotações...</p>
                </div>
            `;

            // Buscar lotações via AJAX
            fetch(`../../Controllers/gestao/ProfessorLotacaoController.php?acao=buscar_lotacoes&professor_id=${professorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.lotacoes.length > 0) {
                        let html = '<div class="space-y-3">';
                        data.lotacoes.forEach(lotacao => {
                            const status = lotacao.fim ? 'Finalizada' : 'Ativa';
                            const statusClass = lotacao.fim ? 'bg-gray-100 text-gray-800' : 'bg-green-100 text-green-800';
                            
                            html += `
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900">${lotacao.escola_nome}</h4>
                                        <span class="text-xs px-2 py-1 rounded-full ${statusClass}">${status}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">${lotacao.municipio}</p>
                                    <div class="text-xs text-gray-500 space-y-1">
                                        <p><strong>Início:</strong> ${new Date(lotacao.inicio).toLocaleDateString('pt-BR')}</p>
                                        ${lotacao.fim ? `<p><strong>Fim:</strong> ${new Date(lotacao.fim).toLocaleDateString('pt-BR')}</p>` : ''}
                                        ${lotacao.carga_horaria ? `<p><strong>Carga Horária:</strong> ${lotacao.carga_horaria}h/semana</p>` : ''}
                                        ${lotacao.observacao ? `<p><strong>Obs:</strong> ${lotacao.observacao}</p>` : ''}
                                    </div>
                                    ${!lotacao.fim ? `
                                        <div class="mt-3 pt-3 border-t border-gray-100">
                                            <button onclick="finalizarLotacao(${lotacao.id})" 
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                Finalizar Lotação
                                            </button>
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        });
                        html += '</div>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div class="text-center text-gray-500 py-8">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p>Nenhuma lotação encontrada para este professor</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar lotações:', error);
                    container.innerHTML = `
                        <div class="text-center text-red-500 py-8">
                            <p>Erro ao carregar lotações</p>
                        </div>
                    `;
                });
        }

        function finalizarLotacao(lotacaoId) {
            if (!confirm('Tem certeza que deseja finalizar esta lotação?')) {
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
                    alert('Lotação finalizada com sucesso!');
                    // Recarregar lotações
                    const professorId = document.getElementById('professor_id').value;
                    if (professorId) {
                        carregarLotacoesProfessor(professorId);
                    }
                } else {
                    alert('Erro ao finalizar lotação: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao finalizar lotação:', error);
                alert('Erro ao finalizar lotação');
            });
        }

        // Função para buscar professores dinamicamente
        let timeoutBusca = null;
        function buscarProfessores(termo) {
            const dropdown = document.getElementById('professor_dropdown');
            const professorIdInput = document.getElementById('professor_id');
            
            // Limpar timeout anterior
            if (timeoutBusca) {
                clearTimeout(timeoutBusca);
            }
            
            // Se o termo estiver vazio, esconder dropdown
            if (!termo.trim()) {
                dropdown.classList.add('hidden');
                professorIdInput.value = '';
                carregarLotacoesProfessor('');
                return;
            }
            
            // Mostrar dropdown com loading
            dropdown.classList.remove('hidden');
            dropdown.innerHTML = '<div class="p-3 text-gray-500 text-sm">Buscando...</div>';
            
            // Fazer busca com delay para evitar muitas requisições
            timeoutBusca = setTimeout(() => {
                fetch(`../../Controllers/gestao/ProfessorLotacaoController.php?busca=${encodeURIComponent(termo)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status && data.professores.length > 0) {
                            let html = '';
                            data.professores.forEach(professor => {
                                html += `
                                    <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" 
                                         onclick="selecionarProfessor(${professor.id}, '${professor.nome.replace(/'/g, "\\'")}')">
                                        <div class="font-medium text-gray-900">${professor.nome}</div>
                                        <div class="text-sm text-gray-600">${professor.email}</div>
                                        <div class="text-xs text-gray-500">${professor.status_lotacao}</div>
                                    </div>
                                `;
                            });
                            dropdown.innerHTML = html;
                        } else {
                            dropdown.innerHTML = '<div class="p-3 text-gray-500 text-sm">Nenhum professor encontrado</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar professores:', error);
                        dropdown.innerHTML = '<div class="p-3 text-red-500 text-sm">Erro ao buscar professores</div>';
                    });
            }, 300);
        }
        
        // Função para selecionar um professor
        function selecionarProfessor(id, nome) {
            document.getElementById('professor_busca').value = nome;
            document.getElementById('professor_id').value = id;
            document.getElementById('professor_dropdown').classList.add('hidden');
            carregarLotacoesProfessor(id);
        }
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('professor_dropdown');
            const input = document.getElementById('professor_busca');
            
            if (!dropdown.contains(event.target) && !input.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Validação do formulário
        const form = document.querySelector('form');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const professorId = document.getElementById('professor_id').value;
                const dataInicio = document.querySelector('input[name="data_inicio"]').value;
                const escolasSelecionadas = document.querySelectorAll('input[name="escolas[]"]:checked');
                
                if (!professorId) {
                    alert('Por favor, selecione um professor.');
                    e.preventDefault();
                    return false;
                }
                
                if (!dataInicio) {
                    alert('Por favor, informe a data de início.');
                    e.preventDefault();
                    return false;
                }
                
                if (escolasSelecionadas.length === 0) {
                    alert('Por favor, selecione pelo menos uma escola.');
                    e.preventDefault();
                    return false;
                }
            });
        }
         
        }); // Fim do DOMContentLoaded
     </script>
</body>
</html>