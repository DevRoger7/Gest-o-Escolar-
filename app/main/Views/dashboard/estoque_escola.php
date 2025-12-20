<?php
// Iniciar output buffering para evitar output antes do JSON
ob_start();

require_once('../../Models/sessao/sessions.php');
require_once('../../config/permissions_helper.php');
require_once('../../config/Database.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Verificar se é GESTÃO e tem escola vinculada
if ($_SESSION['tipo'] !== 'GESTAO') {
    header('Location: dashboard.php?erro=sem_permissao');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar escola do gestor logado
$escolaGestorId = null;
$escolaGestor = null;

if (isset($_SESSION['tipo']) && strtoupper($_SESSION['tipo']) === 'GESTAO') {
    $usuarioId = $_SESSION['usuario_id'] ?? null;
    
    if ($usuarioId) {
        try {
            $pessoaId = $_SESSION['pessoa_id'] ?? null;
            
            if ($pessoaId) {
                $sqlBuscarGestor = "SELECT g.id as gestor_id
                          FROM gestor g
                          WHERE g.pessoa_id = :pessoa_id AND g.ativo = 1
                          LIMIT 1";
                $stmtBuscarGestor = $conn->prepare($sqlBuscarGestor);
                $stmtBuscarGestor->bindParam(':pessoa_id', $pessoaId);
                $stmtBuscarGestor->execute();
                $gestorData = $stmtBuscarGestor->fetch(PDO::FETCH_ASSOC);
            } else {
                $gestorData = null;
            }
            
            if ($gestorData && isset($gestorData['gestor_id'])) {
                $gestorId = (int)$gestorData['gestor_id'];
                
                // Buscar todas as escolas do gestor
                $sqlEscolas = "SELECT gl.escola_id, e.nome as escola_nome, gl.responsavel, gl.inicio
                             FROM gestor_lotacao gl
                             INNER JOIN escola e ON gl.escola_id = e.id
                             WHERE gl.gestor_id = :gestor_id
                             AND (gl.fim IS NULL OR gl.fim = '' OR gl.fim = '0000-00-00' OR gl.fim >= CURDATE())
                             AND e.ativo = 1
                             ORDER BY gl.responsavel DESC, gl.inicio DESC";
                $stmtEscolas = $conn->prepare($sqlEscolas);
                $stmtEscolas->bindParam(':gestor_id', $gestorId);
                $stmtEscolas->execute();
                $escolasGestor = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);
                
                // Verificar se há escola selecionada na sessão
                if (isset($_SESSION['escola_selecionada_id']) && !empty($_SESSION['escola_selecionada_id'])) {
                    $escolaSelecionadaId = (int)$_SESSION['escola_selecionada_id'];
                    
                    // Verificar se a escola selecionada está na lista de escolas do gestor
                    foreach ($escolasGestor as $escola) {
                        if ((int)$escola['escola_id'] === $escolaSelecionadaId) {
                            $escolaGestorId = $escolaSelecionadaId;
                            $escolaGestor = $_SESSION['escola_selecionada_nome'] ?? $escola['escola_nome'];
                            break;
                        }
                    }
                }
                
                // Se não há escola selecionada ou a selecionada não é válida, usar a primeira (priorizando responsável)
                if (!$escolaGestorId && !empty($escolasGestor)) {
                    $escolaGestorId = (int)$escolasGestor[0]['escola_id'];
                    $escolaGestor = $escolasGestor[0]['escola_nome'];
                    
                    // Salvar na sessão
                    $_SESSION['escola_selecionada_id'] = $escolaGestorId;
                    $_SESSION['escola_selecionada_nome'] = $escolaGestor;
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar escola do gestor: " . $e->getMessage());
        }
    }
}

// Se não tem escola vinculada, redirecionar
if (!$escolaGestorId) {
    header('Location: dashboard.php?erro=sem_escola');
    exit;
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    // Limpar qualquer output anterior (incluindo warnings/notices)
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Desabilitar exibição de erros para não quebrar o JSON
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_GET['acao'] === 'verificar_escola') {
        // Retornar a escola atual da sessão
        $escolaId = isset($_SESSION['escola_selecionada_id']) && !empty($_SESSION['escola_selecionada_id']) 
                    ? (int)$_SESSION['escola_selecionada_id'] 
                    : (int)$escolaGestorId;
        
        $escolaNome = $_SESSION['escola_selecionada_nome'] ?? $escolaGestor ?? 'Escola não encontrada';
        
        echo json_encode([
            'success' => true, 
            'escola_id' => $escolaId,
            'escola_nome' => $escolaNome
        ]);
        exit;
    }
    
    if ($_GET['acao'] === 'listar_estoque') {
        // Usar a escola selecionada na sessão, ou a escola do gestor como fallback
        $escolaId = isset($_SESSION['escola_selecionada_id']) && !empty($_SESSION['escola_selecionada_id']) 
                    ? (int)$_SESSION['escola_selecionada_id'] 
                    : (int)$escolaGestorId;
        
        // Validar se a escola existe
        if (!$escolaId || $escolaId <= 0) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            echo json_encode([
                'success' => false, 
                'message' => 'Nenhuma escola selecionada',
                'estoque' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Verificar se a escola existe no banco
        try {
            $sqlCheck = "SELECT id FROM escola WHERE id = :escola_id AND ativo = 1 LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':escola_id', $escolaId, PDO::PARAM_INT);
            $stmtCheck->execute();
            if (!$stmtCheck->fetch()) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
                echo json_encode([
                    'success' => false, 
                    'message' => 'Escola não encontrada ou inativa',
                    'estoque' => []
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } catch (Exception $e) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            error_log("Erro ao validar escola: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao validar escola',
                'estoque' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Verificar se a coluna estoque_central_id existe
        try {
            $checkColumn = $conn->query("SHOW COLUMNS FROM pacote_escola_item LIKE 'estoque_central_id'");
            $columnExists = $checkColumn->rowCount() > 0;
        } catch (Exception $e) {
            $columnExists = false;
        }
        
        // Mostrar cada item separadamente (incluindo lotes diferentes)
        if ($columnExists) {
            $sql = "SELECT 
                        pei.id as item_id,
                        pei.produto_id,
                        pei.estoque_central_id,
                        p.nome as produto_nome,
                        p.unidade_medida,
                        pei.quantidade,
                        COALESCE(ec1.validade, NULL) as validade,
                        COALESCE(ec1.lote, 'Sem lote') as lote,
                        COALESCE(f1.nome, NULL) as fornecedor_nome,
                        pe.data_envio,
                        pe.id as pacote_id
                    FROM pacote_escola_item pei
                    INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                    INNER JOIN produto p ON pei.produto_id = p.id
                    LEFT JOIN estoque_central ec1 ON pei.estoque_central_id = ec1.id
                    LEFT JOIN fornecedor f1 ON ec1.fornecedor_id = f1.id
                    WHERE pe.escola_id = :escola_id
                    AND pei.quantidade > 0
                    ORDER BY p.nome ASC,
                             CASE WHEN ec1.validade IS NULL THEN 1 ELSE 0 END ASC,
                             ec1.validade ASC,
                             pei.id ASC";
        } else {
            $sql = "SELECT 
                        pei.id as item_id,
                        pei.produto_id,
                        NULL as estoque_central_id,
                        p.nome as produto_nome,
                        p.unidade_medida,
                        pei.quantidade,
                        NULL as validade,
                        'Sem lote' as lote,
                        NULL as fornecedor_nome,
                        pe.data_envio,
                        pe.id as pacote_id
                    FROM pacote_escola_item pei
                    INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                    INNER JOIN produto p ON pei.produto_id = p.id
                    WHERE pe.escola_id = :escola_id
                    AND pei.quantidade > 0
                    ORDER BY p.nome ASC, pei.id ASC";
        }
        
        $params = [':escola_id' => $escolaId];
        
        if (!empty($_GET['produto_id'])) {
            $sql .= " AND pei.produto_id = :produto_id";
            $params[':produto_id'] = $_GET['produto_id'];
        }
        
        // A ordenação padrão já está incluída na consulta SQL
        // Se a coluna existir, substituir a ordenação para incluir a validade
        if ($columnExists) {
            $sql = str_replace(
                "ORDER BY p.nome ASC, pei.id ASC", 
                "ORDER BY p.nome ASC, 
                 CASE WHEN ec1.validade IS NULL THEN 1 ELSE 0 END ASC,
                 ec1.validade ASC,
                 pei.id ASC", 
                $sql
            );
        }
        
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Erro ao preparar a consulta SQL: ' . implode(' ', $conn->errorInfo()));
            }
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao executar a consulta SQL: ' . implode(' ', $stmt->errorInfo()));
            }
            
            $estoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Limpar valores vazios e formatar dados
            foreach ($estoque as &$item) {
                if (isset($item['lote'])) {
                    $lote = trim($item['lote']);
                    if (empty($lote) || $lote === '' || $lote === 'Sem lote') {
                        $item['lote'] = null;
                    }
                }
                if (empty($item['fornecedor_nome'])) {
                    $item['fornecedor_nome'] = null;
                }
                // Manter item_id para identificação única
                if (!isset($item['id'])) {
                    $item['id'] = $item['item_id'] ?? null;
                }
                $item['criado_em'] = $item['data_envio'];
            }
            
            // Limpar qualquer saída anterior
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true, 
                'estoque' => $estoque,
                'debug' => [
                    'sql' => $sql,
                    'params' => $params,
                    'count' => count($estoque)
                ]
            ]);
            exit;
            
        } catch (Exception $e) {
            // Log do erro
            error_log('Erro ao listar estoque: ' . $e->getMessage());
            
            // Limpar qualquer saída anterior
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Retornar erro como JSON
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar o estoque. Por favor, tente novamente.',
                'error' => $e->getMessage(),
                'debug' => [
                    'sql' => $sql,
                    'params' => $params,
                    'trace' => $e->getTraceAsString()
                ]
            ]);
            exit;
        }
    }
}

// Buscar produtos para filtro (usar a escola selecionada na sessão)
$escolaIdParaProdutos = isset($_SESSION['escola_selecionada_id']) && !empty($_SESSION['escola_selecionada_id']) 
                        ? (int)$_SESSION['escola_selecionada_id'] 
                        : (int)$escolaGestorId;

$sqlProdutos = "SELECT DISTINCT p.id, p.nome 
                FROM produto p
                INNER JOIN pacote_escola_item pei ON p.id = pei.produto_id
                INNER JOIN pacote_escola pe ON pei.pacote_id = pe.id
                WHERE pe.escola_id = :escola_id AND p.ativo = 1
                ORDER BY p.nome ASC";
$stmtProdutos = $conn->prepare($sqlProdutos);
$stmtProdutos->bindParam(':escola_id', $escolaIdParaProdutos, PDO::PARAM_INT);
$stmtProdutos->execute();
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque - SIGEA</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/1/19/Bras%C3%A3o_de_Maranguape.png/250px-Bras%C3%A3o_de_Maranguape.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="global-theme.css">
    <script>
        // 1. Primeiro, definimos a função filtrarEstoque
function filtrarEstoque() {
    const tbody = document.getElementById('lista-estoque');
    const produtoSelect = document.getElementById('filtro-produto');
    
    if (!produtoSelect) {
        console.error('Elemento filtro-produto não encontrado');
        return;
    }
    
    const produtoId = produtoSelect.value;
    
    // Mostrar indicador de carregamento
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-16">
                <div class="flex flex-col items-center justify-center text-gray-400">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-green mb-4"></div>
                    <p class="text-lg font-medium">Carregando estoque...</p>
                </div>
            </td>
        </tr>`;
    
    // Construir URL da requisição
    let url = 'estoque_escola.php?acao=listar_estoque';
    if (produtoId) {
        url += '&produto_id=' + encodeURIComponent(produtoId);
    }
    
    // Adicionar timestamp para evitar cache
    url += '&_=' + new Date().getTime();
    
    console.log('Fazendo requisição para:', url);
    
    // Fazer a requisição
    fetch(url, {
        headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache',
            'Expires': '0'
        }
    })
    .then(async response => {
        console.log('Resposta recebida. Status:', response.status);
        const text = await response.text();
        console.log('Conteúdo da resposta:', text.substring(0, 500)); // Mostrar os primeiros 500 caracteres
        
        // Tentar fazer parse do JSON
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Erro ao fazer parse do JSON:', e);
            throw new Error('Resposta inválida do servidor');
        }
    })
    .then(data => {
        console.log('Dados processados:', data);
        
        if (data.success && Array.isArray(data.estoque)) {
            // Limpar a tabela
            tbody.innerHTML = '';
            
            if (data.estoque.length > 0) {
                // Preencher a tabela com os itens do estoque
                data.estoque.forEach((item, index) => {
                    const validade = item.validade ? new Date(item.validade + 'T00:00:00') : null;
                    const hoje = new Date();
                    hoje.setHours(0, 0, 0, 0);
                    
                    // Determinar a classe de cor com base na validade
                    let corValidade = '';
                    let iconeValidade = '';
                    let textoValidade = item.validade ? new Date(item.validade + 'T00:00:00').toLocaleDateString('pt-BR') : '-';
                    
                    if (validade) {
                        validade.setHours(0, 0, 0, 0);
                        const diffTime = validade - hoje;
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        
                        if (diffDays < 0) {
                            corValidade = 'bg-red-100 text-red-800 border-red-200';
                            iconeValidade = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                        } else if (diffDays <= 7) {
                            corValidade = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                            iconeValidade = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                        } else {
                            corValidade = 'bg-green-100 text-green-800 border-green-200';
                            iconeValidade = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                        }
                    } else {
                        corValidade = 'bg-gray-100 text-gray-800 border-gray-200';
                        iconeValidade = '';
                    }
                    
                    // Formatar data de entrada
                    const dataEntrada = item.data_envio ? new Date(item.data_envio + 'T00:00:00').toLocaleDateString('pt-BR') : '-';
                    
                    // Adicionar linha à tabela
                    tbody.innerHTML += `
                        <tr class="hover:bg-gray-50 transition-colors duration-150 ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
                            <td class="py-4 px-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-10 h-10 bg-primary-green bg-opacity-10 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-primary-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">${escapeHtml(item.produto_nome || '-')}</div>
                                        <div class="text-sm text-gray-500 mt-0.5">${escapeHtml(item.unidade_medida || '-')}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold text-sm border border-blue-200">
                                    ${formatarQuantidade(item.quantidade || 0, item.unidade_medida)}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                ${item.lote ? `<span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 rounded-lg text-sm font-medium">${escapeHtml(item.lote)}</span>` : '<span class="text-gray-400">-</span>'}
                            </td>
                            <td class="py-4 px-6">
                                ${item.fornecedor_nome ? `<span class="text-gray-700">${escapeHtml(item.fornecedor_nome)}</span>` : '<span class="text-gray-400">-</span>'}
                            </td>
                            <td class="py-4 px-6 text-center">
                                ${item.validade ? `<span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium border ${corValidade}">${iconeValidade}${textoValidade}</span>` : '<span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 rounded-lg text-sm font-medium border border-gray-200">-</span>'}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-gray-600 font-medium">${dataEntrada}</span>
                            </td>
                        </tr>`;
                });
            } else {
                // Exibir mensagem quando não há itens
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-16">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-lg font-medium">Nenhum item encontrado no estoque</p>
                                <p class="text-sm mt-2">${produtoId ? 'Nenhum item encontrado para o produto selecionado' : 'Tente alterar os filtros de busca'}</p>
                            </div>
                        </td>
                    </tr>`;
            }
        } else {
            throw new Error(data.message || 'Resposta inválida do servidor');
        }
    })
    .catch(error => {
        console.error('Erro ao carregar estoque:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-16">
                    <div class="flex flex-col items-center justify-center text-red-500">
                        <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-lg font-medium">Erro ao carregar o estoque</p>
                        <p class="text-sm mt-2">${error.message || 'Tente novamente mais tarde'}</p>
                        <button onclick="filtrarEstoque()" class="mt-4 px-4 py-2 bg-primary-green text-white rounded-lg hover:bg-green-600 transition-colors">
                            Tentar novamente
                        </button>
                        <button onclick="window.location.reload()" class="mt-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm">
                            Recarregar a página
                        </button>
                    </div>
                </td>
            </tr>`;
    });
}

// Função auxiliar para escapar HTML
function escapeHtml(unsafe) {
    return unsafe
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Função auxiliar para formatar quantidade
function formatarQuantidade(quantidade, unidadeMedida) {
    if (!quantidade && quantidade !== 0) return '0';
    
    const unidade = (unidadeMedida || '').toUpperCase().trim();
    const qtd = parseFloat(quantidade);
    
    // Se for número inteiro, mostrar sem casas decimais
    if (Number.isInteger(qtd)) {
        return qtd.toLocaleString('pt-BR') + (unidade ? ` ${unidade}` : '');
    }
    
    // Se for número decimal, mostrar com 2 casas decimais
    return qtd.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + (unidade ? ` ${unidade}` : '');
}

// Chamar a função quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página carregada, chamando filtrarEstoque()');
    filtrarEstoque();
    
    // Adicionar evento de clique ao botão de filtrar
    const btnFiltrar = document.querySelector('button[onclick="filtrarEstoque()"]');
    if (btnFiltrar) {
        btnFiltrar.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botão de filtrar clicado');
            filtrarEstoque();
        });
    }
});</script>
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
        .mobile-menu-overlay { transition: opacity 0.3s ease-in-out; }
        @media (max-width: 1023px) {
            .sidebar-mobile { transform: translateX(-100%); }
            .sidebar-mobile.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'components/sidebar_gestao.php'; ?>
    
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
                    <div class="flex-1 text-center lg:text-left">
                        <h1 class="text-xl font-semibold text-gray-800">Estoque de Alimentos</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <span id="nome-escola-estoque" class="text-sm font-semibold"><?= htmlspecialchars($escolaGestor) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="p-8">
            <div class="max-w-[95%] mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Estoque de Produtos</h2>
                        <p id="subtitulo-estoque" class="text-gray-600 mt-1">Visualize os produtos disponíveis na sua escola</p>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="bg-white rounded-2xl p-6 shadow-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produto</label>
                            <select id="filtro-produto" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="filtrarEstoque()">
                                <option value="">Todos os produtos</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?= $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Estoque -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1200px]">
                            <thead>
                                <tr class="bg-gradient-to-r from-primary-green to-green-700 text-white">
                                    <th class="text-left py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                            Produto
                                        </div>
                                    </th>
                                    <th class="text-center py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center justify-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-5m-6 5v-5m6 5h-3m-3 0h3m-3-10h3m-3 0H9m0 0V7m0 10v-5"></path>
                                            </svg>
                                            Quantidade
                                        </div>
                                    </th>
                                    <th class="text-left py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Lote
                                        </div>
                                    </th>
                                    <th class="text-left py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            Fornecedor
                                        </div>
                                    </th>
                                    <th class="text-center py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center justify-center">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Validade
                                        </div>
                                    </th>
                                    <th class="text-center py-4 px-6 font-bold text-sm uppercase tracking-wider">
                                        <div class="flex items-center justify-center">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Data de Entrada
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="lista-estoque" class="divide-y divide-gray-100">
                                <tr>
                                    <td colspan="6" class="text-center py-16">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                            </svg>
                                            <p class="text-lg font-medium">Carregando estoque...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Função para formatar quantidade baseado na unidade de medida
        function formatarQuantidade(quantidade, unidadeMedida) {
            if (!quantidade && quantidade !== 0) return '0';
            
            const unidade = (unidadeMedida || '').toUpperCase().trim();
            // Unidades que permitem decimais (líquidas e de peso)
            const permiteDecimal = ['ML', 'L', 'G', 'KG', 'LT', 'LITRO', 'LITROS', 'MILILITRO', 'MILILITROS', 'GRAMA', 'GRAMAS', 'QUILO', 'QUILOS'].includes(unidade);
            const casasDecimais = permiteDecimal ? 3 : 0;
            
            return parseFloat(quantidade).toLocaleString('pt-BR', {
                minimumFractionDigits: casasDecimais,
                maximumFractionDigits: casasDecimais
            });
        }
        
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
            } else {
                // Criar modal dinamicamente se não existir
                createLogoutModal();
            }
        };
        
        window.createLogoutModal = function() {
            let existingModal = document.getElementById('logoutModal');
            if (existingModal) {
                existingModal.style.display = 'flex';
                existingModal.classList.remove('hidden');
                return;
            }
            
            const modal = document.createElement('div');
            modal.id = 'logoutModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[60] items-center justify-center p-4';
            modal.style.display = 'flex';
            modal.innerHTML = `
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
            `;
            document.body.appendChild(modal);
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
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('mobileOverlay');
            if (overlay) {
                overlay.addEventListener('click', function() {
                    window.toggleSidebar();
                });
            }
            
            // Atualizar nome da escola exibido
            atualizarNomeEscola();
            
            // Função para inicializar o carregamento do estoque
            function inicializarCarregamentoEstoque() {
                const filtroProduto = document.getElementById('filtro-produto');
                if (filtroProduto) {
                    // Carregar estoque inicial
                    filtrarEstoque();
                    
                    // Verificar mudanças na escola a cada 2 segundos (quando a escola é alterada no dashboard)
                    setInterval(function() {
                        verificarMudancaEscola();
                    }, 2000);
                } else {
                    // Se o filtro de produto ainda não estiver disponível, tentar novamente em 100ms
                    setTimeout(inicializarCarregamentoEstoque, 100);
                }
            }
            
            // Iniciar o carregamento do estoque
            inicializarCarregamentoEstoque();
        });
        
        let ultimaEscolaId = <?= $escolaGestorId ?>;
        
        function verificarMudancaEscola() {
            // Buscar a escola atual da sessão via AJAX
            fetch('estoque_escola.php?acao=verificar_escola')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.escola_id && data.escola_id !== ultimaEscolaId) {
                        ultimaEscolaId = data.escola_id;
                        atualizarNomeEscola();
                        
                        // Verificar se o filtro de produto existe antes de chamar filtrarEstoque
                        const filtroProduto = document.getElementById('filtro-produto');
                        if (filtroProduto) {
                            filtrarEstoque();
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar mudança de escola:', error);
                });
        }
        
        function atualizarNomeEscola() {
            const nomeEscola = document.getElementById('nome-escola-estoque');
            const subtitulo = document.getElementById('subtitulo-estoque');
            if (nomeEscola && subtitulo) {
                fetch('estoque_escola.php?acao=verificar_escola')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.escola_nome) {
                            nomeEscola.textContent = data.escola_nome;
                            subtitulo.textContent = `Visualize os produtos disponíveis em: ${data.escola_nome}`;
                            ultimaEscolaId = data.escola_id;
                        }
                    })
                    .catch(error => {
                        // Silenciar erros
                    });
            }
        }
        
   
// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado - Iniciando carregamento do estoque');
    inicializarCarregamentoEstoque();
});
function inicializarCarregamentoEstoque() {
    console.log('Inicializando carregamento do estoque...');
    const filtroProduto = document.getElementById('filtro-produto');
    
    if (filtroProduto) {
        console.log('Filtro de produto encontrado, carregando estoque...');
        // Carregar estoque inicial
        filtrarEstoque();
        
        // Verificar mudanças na escola a cada 2 segundos (quando a escola é alterada no dashboard)
        setInterval(function() {
            verificarMudancaEscola();
        }, 2000);
    } else {
        console.log('Filtro de produto não encontrado, tentando novamente em 100ms...');
        // Se o filtro de produto ainda não estiver disponível, tentar novamente em 100ms
        setTimeout(inicializarCarregamentoEstoque, 100);
    }
}
function verificarMudancaEscola() {
    console.log('Verificando mudança de escola...');
    // Buscar a escola atual da sessão via AJAX
    fetch('estoque_escola.php?acao=verificar_escola')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.escola_id && data.escola_id !== ultimaEscolaId) {
                console.log('Escola alterada para:', data.escola_id);
                ultimaEscolaId = data.escola_id;
                atualizarNomeEscola();
                
                // Verificar se o filtro de produto existe antes de chamar filtrarEstoque
                const filtroProduto = document.getElementById('filtro-produto');
                if (filtroProduto) {
                    console.log('Atualizando estoque para a nova escola...');
                    filtrarEstoque();
                }
            }
        })
        .catch(error => {
            console.error('Erro ao verificar mudança de escola:', error);
        });
}
  
        // Logout Confirmation Modal
        <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar Saída</h3>
                        <p class="text-sm text-gray-600">Tem certeza que deseja sair do sistema?</p>
                    </div>
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
</body>
</html>

