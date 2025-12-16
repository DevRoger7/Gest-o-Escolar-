<?php
require_once('../../Models/sessao/sessions.php');
require_once('../../config/system_helper.php');

$session = new sessions();
$session->autenticar_session();
$session->tempo_session();

// Garantir acesso apenas para ADM_MERENDA
$tipo = strtolower($_SESSION['tipo'] ?? '');
if ($tipo !== 'adm_merenda') {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo getPageTitle('Relatório de Merenda'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="global-theme.css" rel="stylesheet">
    <script src="theme-manager.js"></script>

    <!-- Add minimal JS to toggle sidebar and handle sidebar actions -->
    <script>
        window.toggleSidebar = function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const main = document.querySelector('main');
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('hidden');
                if (main) main.classList.toggle('content-dimmed');
            }
        };
        window.confirmLogout = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            } else {
                console.error('Modal de logout não encontrado');
            }
        };
        
        window.closeLogoutModal = function() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        };
        
        window.logout = function() {
            try {
                window.location.href = '../auth/logout.php';
            } catch (e) {
                console.error('Erro ao fazer logout:', e);
            }
        };
        window.openUserProfile = function() {
            alert('Perfil do usuário');
        };
        window.closeUserProfile = function() {};

        // --- added: toggle helpers for report sections ---
        // Replace simple toggling with navigation that sets the ?report=... param
        window.showDailyReport = function() {
            const url = new URL(window.location.href);
            url.searchParams.set('report', 'daily');
            // default date: today, if not present
            if (!url.searchParams.get('date')) {
                const today = new Date();
                const yyyy_mm_dd = today.toISOString().slice(0, 10);
                url.searchParams.set('date', yyyy_mm_dd);
            }
            window.location.href = url.toString();
        };
        window.showMonthlyReport = function() {
            const url = new URL(window.location.href);
            url.searchParams.set('report', 'monthly');
            // default month: current YYYY-MM
            if (!url.searchParams.get('month')) {
                const today = new Date();
                const yyyy_mm = today.toISOString().slice(0, 7);
                url.searchParams.set('month', yyyy_mm);
            }
            window.location.href = url.toString();
        };
        window.showWasteReport = function() {
            const url = new URL(window.location.href);
            url.searchParams.set('report', 'waste');
            // default period: last 7 days
            if (!url.searchParams.get('start_date') || !url.searchParams.get('end_date')) {
                const today = new Date();
                const end = today.toISOString().slice(0, 10);
                const startDateObj = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                const start = startDateObj.toISOString().slice(0, 10);
                url.searchParams.set('start_date', start);
                url.searchParams.set('end_date', end);
            }
            window.location.href = url.toString();
        };
        // --- end added ---
    </script>

    <!-- FIXED: wrap auto-open code inside a script tag -->
    <script>
    // Auto-open the requested report section on page load
    document.addEventListener('DOMContentLoaded', function () {
        // --- FIX: prevent navigation here to avoid reload loop/flicker ---
        const p = new URLSearchParams(window.location.search).get('report');
        const daily = document.getElementById('dailyReport');
        const monthly = document.getElementById('monthlyReport');
        const waste = document.getElementById('wasteReport');

        // hide all first
        [daily, monthly, waste].forEach(el => { if (el) el.classList.add('hidden'); });

        // show the requested section only
        if (p === 'daily' && daily) {
            daily.classList.remove('hidden');
        } else if (p === 'monthly' && monthly) {
            monthly.classList.remove('hidden');
        } else if (p === 'waste' && waste) {
            waste.classList.remove('hidden');
        }
    });
    </script>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Include the ADM Merenda sidebar component -->
    <?php require_once('components/sidebar_merenda.php'); ?>

    <!-- --- added: server-side data bootstrap for reports --- -->
    <?php
    // Simple DB connection (adjust credentials if needed)
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = '';
    $dbName = 'escola_merenda';

    $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli && !$mysqli->connect_error) {
        $mysqli->set_charset('utf8mb4');
    } else {
        $mysqli = null;
    }

    // Load escola options (safe fallback if table/column differs)
    $escolas = [];
    if ($mysqli) {
        $rs = $mysqli->query("SELECT id, nome FROM escola ORDER BY nome");
        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                $escolas[] = ['id' => (int)$row['id'], 'nome' => $row['nome']];
            }
            $rs->free();
        }
    }

    // Helpers to get names safely
    function escolaNome($mysqli, $id) {
        if (!$mysqli || !$id) return "Escola #{$id}";
        $stmt = $mysqli->prepare("SELECT nome FROM escola WHERE id = ?");
        if (!$stmt) return "Escola #{$id}";
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) { $stmt->close(); return "Escola #{$id}"; }
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row && !empty($row['nome']) ? $row['nome'] : "Escola #{$id}";
    }
    function produtoNome($mysqli, $id) {
        if (!$mysqli || !$id) return "Produto #{$id}";
        $stmt = $mysqli->prepare("SELECT nome FROM produto WHERE id = ?");
        if (!$stmt) return "Produto #{$id}";
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) { $stmt->close(); return "Produto #{$id}"; }
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row && !empty($row['nome']) ? $row['nome'] : "Produto #{$id}";
    }
    function desperdicioKg($mysqli, $escolaId, $date, $turno = null) {
        if (!$mysqli) return 0.0;
        // CHANGED: use sargable range to support DATE/DATETIME and enable index usage
        $sql = "SELECT COALESCE(SUM(peso_kg), 0) AS total_kg
                FROM desperdicio
                WHERE escola_id = ? AND data >= ? AND data < DATE_ADD(?, INTERVAL 1 DAY)";
        $types = "iss";
        $params = [$escolaId, $date, $date];
        if ($turno && in_array($turno, ['MANHA','TARDE','NOITE','INTEGRAL'])) {
            $sql .= " AND turno = ?";
            $types .= "s";
            $params[] = $turno;
        }
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return 0.0;
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) { $stmt->close(); return 0.0; }
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return isset($row['total_kg']) ? (float)$row['total_kg'] : 0.0;
    }

    // --- added: helper to sum waste by period ---
    function desperdicioKgPeriodo($mysqli, $escolaId, $startDate, $endDate, $turno = null) {
        if (!$mysqli) return 0.0;
        // CHANGED: sargable date range [startDate, endDate] inclusive
        $sql = "SELECT COALESCE(SUM(peso_kg), 0) AS total_kg
                FROM desperdicio
                WHERE escola_id = ? AND data >= ? AND data < DATE_ADD(?, INTERVAL 1 DAY)";
        $types = "iss";
        $params = [$escolaId, $startDate, $endDate];

        if ($turno && in_array($turno, ['MANHA','TARDE','NOITE','INTEGRAL'])) {
            $sql .= " AND turno = ?";
            $types .= "s";
            $params[] = $turno;
        }

        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return 0.0;
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) { $stmt->close(); return 0.0; }
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return isset($row['total_kg']) ? (float)$row['total_kg'] : 0.0;
    }
    // --- end added ---

    // Read filters and fetch daily data
    $report = $_GET['report'] ?? null;

    $dailyRows = [];
    if ($report === 'daily' && $mysqli) {
        $date = $_GET['date'] ?? date('Y-m-d');
        $escolaId = isset($_GET['escola_id']) && $_GET['escola_id'] !== '' ? (int)$_GET['escola_id'] : null;
        $turno = $_GET['turno'] ?? null;

        // CHANGED: sargable condition instead of DATE(data) = ?
        $sql = "SELECT id, escola_id, turma_id, data, turno, total_alunos, alunos_atendidos, observacoes
                FROM consumo_diario
                WHERE data >= ? AND data < DATE_ADD(?, INTERVAL 1 DAY)";
        $types = "ss";
        $params = [$date, $date];

        if ($escolaId) { $sql .= " AND escola_id = ?"; $types .= "i"; $params[] = $escolaId; }
        if ($turno && in_array($turno, ['MANHA','TARDE','NOITE','INTEGRAL'])) { $sql .= " AND turno = ?"; $types .= "s"; $params[] = $turno; }

        $sql .= " ORDER BY escola_id, turno";

        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $dailyRows[] = $row;
                }
            }
            $stmt->close();
        }
    }

    // --- added: fetch monthly aggregated data for "Relatório Mensal" ---
    $monthlyRows = [];
    if ($report === 'monthly' && $mysqli) {
        $month = $_GET['month'] ?? date('Y-m');
        $startMonth = $month . '-01';
        $endMonth   = date('Y-m-t', strtotime($startMonth));
        $escolaIdM  = isset($_GET['escola_id']) && $_GET['escola_id'] !== '' ? (int)$_GET['escola_id'] : null;
        $turnoM     = $_GET['turno'] ?? null;

        // CHANGED: sargable range for data; keep COUNT(DISTINCT DATE(data)) for day counting
        $sqlCons = "SELECT escola_id, turno,
                           COUNT(DISTINCT DATE(data)) AS dias_registrados,
                           COALESCE(SUM(alunos_atendidos), 0) AS total_atendidos
                    FROM consumo_diario
                    WHERE data >= ? AND data < DATE_ADD(?, INTERVAL 1 DAY)";
        $typesCons  = "ss";
        $paramsCons = [$startMonth, $endMonth];

        if ($escolaIdM) { $sqlCons .= " AND escola_id = ?"; $typesCons .= "i"; $paramsCons[] = $escolaIdM; }
        if ($turnoM && in_array($turnoM, ['MANHA','TARDE','NOITE','INTEGRAL'])) { $sqlCons .= " AND turno = ?"; $typesCons .= "s"; $paramsCons[] = $turnoM; }

        $sqlCons .= " GROUP BY escola_id, turno ORDER BY escola_id, turno";

        $consData = [];
        $stmtC = $mysqli->prepare($sqlCons);
        if ($stmtC) {
            $stmtC->bind_param($typesCons, ...$paramsCons);
            if ($stmtC->execute()) {
                $resC = $stmtC->get_result();
                while ($rowC = $resC->fetch_assoc()) {
                    $key = $rowC['escola_id'] . '|' . $rowC['turno'];
                    $consData[$key] = $rowC;
                }
            }
            $stmtC->close();
        }

        // Aggregate desperdicio (waste) by escola/turno for the same month
        $sqlW = "SELECT escola_id, turno, COALESCE(SUM(peso_kg), 0) AS waste_kg
                 FROM desperdicio
                 WHERE DATE(data) BETWEEN ? AND ?";
        $typesW  = "ss";
        $paramsW = [$startMonth, $endMonth];

        if ($escolaIdM) { $sqlW .= " AND escola_id = ?"; $typesW .= "i"; $paramsW[] = $escolaIdM; }
        if ($turnoM && in_array($turnoM, ['MANHA','TARDE','NOITE','INTEGRAL'])) { $sqlW .= " AND turno = ?"; $typesW .= "s"; $paramsW[] = $turnoM; }

        $sqlW .= " GROUP BY escola_id, turno";

        $wData = [];
        $stmtW = $mysqli->prepare($sqlW);
        if ($stmtW) {
            $stmtW->bind_param($typesW, ...$paramsW);
            if ($stmtW->execute()) {
                $resW = $stmtW->get_result();
                while ($rowW = $resW->fetch_assoc()) {
                    $key = $rowW['escola_id'] . '|' . $rowW['turno'];
                    $wData[$key] = $rowW;
                }
            }
            $stmtW->close();
        }

        // Merge the two aggregates into $monthlyRows
        foreach ($consData as $key => $row) {
            $monthlyRows[] = [
                'escola_id'        => (int)$row['escola_id'],
                'turno'            => $row['turno'],
                'dias_registrados' => (int)$row['dias_registrados'],
                'total_atendidos'  => (int)$row['total_atendidos'],
                'waste_kg'         => isset($wData[$key]) ? (float)$wData[$key]['waste_kg'] : 0.0,
            ];
        }
    }
    // --- end added ---

    // Fetch desperdício data
    $wasteRows = [];
    if ($report === 'waste' && $mysqli) {
        $start = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $end   = $_GET['end_date'] ?? date('Y-m-d');
        $escolaIdW = isset($_GET['escola_id']) && $_GET['escola_id'] !== '' ? (int)$_GET['escola_id'] : null;
        $turnoW = $_GET['turno'] ?? null;
        $motivo = $_GET['motivo'] ?? null;

        // CHANGED: sargable range instead of DATE(data) BETWEEN
        $sql = "SELECT id, escola_id, data, turno, produto_id, quantidade, unidade_medida, peso_kg, motivo, motivo_detalhado, observacoes
                FROM desperdicio
                WHERE data >= ? AND data < DATE_ADD(?, INTERVAL 1 DAY)";
        $types = "ss";
        $params = [$start, $end];

        if ($escolaIdW) { $sql .= " AND escola_id = ?"; $types .= "i"; $params[] = $escolaIdW; }
        if ($turnoW && in_array($turnoW, ['MANHA','TARDE','NOITE','INTEGRAL'])) { $sql .= " AND turno = ?"; $types .= "s"; $params[] = $turnoW; }
        if ($motivo && in_array($motivo, ['EXCESSO_PREPARO','REJEICAO_ALUNOS','VALIDADE_VENCIDA','PREPARO_INCORRETO','OUTROS'])) {
            $sql .= " AND motivo = ?";
            $types .= "s";
            $params[] = $motivo;
        }

        $sql .= " ORDER BY data DESC, escola_id";

        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $wasteRows[] = $row;
                }
            }
            $stmt->close();
        }
    }
    ?>
    <!-- --- end added --- -->

    <!-- UPDATED: add lg:pl-64 to header and remove duplicated blocks -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30 lg:pl-64">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <button onclick="window.toggleSidebar()" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100" aria-label="Abrir menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="flex-1 text-center lg:text-left">
                    <h1 class="text-xl font-semibold text-gray-800">Relatórios de Merenda</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden lg:block">
                        <?php if (isset($_SESSION['tipo']) && (strtolower($_SESSION['tipo']) === 'adm' || strtolower($_SESSION['tipo']) === 'adm_merenda')) { ?>
                            <div class="text-right px-4 py-2">
                                <p class="text-sm font-medium text-gray-800">Secretaria Municipal da Educação</p>
                                <p class="text-xs text-gray-500">Órgão Central</p>
                            </div>
                        <?php } else { ?>
                            <div class="bg-primary-green text-white px-4 py-2 rounded-lg shadow-sm">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span class="text-sm font-semibold">
                                        <?php echo $_SESSION['escola_atual'] ?? 'Escola Municipal'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- REMOVED: duplicated header block and the misplaced standalone title -->
    <!-- (This removed section contained a second identical header area and the separate
         <h1 class="text-center ...">Relatório de Merenda</h1> outside the container.) -->

    <main class="p-8 lg:p-12 lg:ml-64 content-transition">
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Relatórios</h2>
            <p class="text-gray-600">Esta página será utilizada para visualizar e exportar relatórios de merenda.</p>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="card-hover rounded-xl p-4">
                    <h3 class="text-lg font-medium text-gray-800 mb-2">Relatório diário</h3>
                    <p class="text-gray-600 mb-3">Resumo das refeições servidas por escola.</p>
                    <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg"
                            onclick="window.showDailyReport()">
                        Visualizar
                    </button>
                </div>

                <div class="card-hover rounded-xl p-4">
                    <h3 class="text-lg font-medium text-gray-800 mb-2">Relatório mensal</h3>
                    <p class="text-gray-600 mb-3">Consolidado por mês e por unidade escolar.</p>
                    <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg"
                            onclick="window.showMonthlyReport()">
                        Visualizar
                    </button>
                </div>

                <!-- --- added: new card for waste report --- -->
                <div class="card-hover rounded-xl p-4">
                    <h3 class="text-lg font-medium text-gray-800 mb-2">Relatório de Desperdício</h3>
                    <p class="text-gray-600 mb-3">Itens desperdiçados por período e por escola.</p>
                    <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg"
                            onclick="window.showWasteReport()">
                        Visualizar
                    </button>
                </div>
                <!-- --- end added --- -->
            </div>
        </div>

        <div id="dailyReport" class="mt-8 bg-white rounded-2xl p-6 shadow-sm hidden">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Relatório Diário</h3>
            <form method="get" action="relatorio_merenda.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <input type="hidden" name="report" value="daily" />
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Data</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($_GET['date'] ?? date('Y-m-d')); ?>" class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Escola</label>
                    <select name="escola_id" class="w-full border rounded-lg px-3 py-2">
                        <option value="">Todas</option>
                        <?php foreach ($escolas as $e): ?>
                            <option value="<?php echo (int)$e['id']; ?>" <?php echo (isset($_GET['escola_id']) && $_GET['escola_id'] == $e['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Turno</label>
                    <select name="turno" class="w-full border rounded-lg px-3 py-2">
                        <option value="">Todos</option>
                        <option value="MANHA" <?php echo (($_GET['turno'] ?? '') === 'MANHA') ? 'selected' : ''; ?>>Manhã</option>
                        <option value="TARDE" <?php echo (($_GET['turno'] ?? '') === 'TARDE') ? 'selected' : ''; ?>>Tarde</option>
                        <option value="NOITE" <?php echo (($_GET['turno'] ?? '') === 'NOITE') ? 'selected' : ''; ?>>Noite</option>
                        <option value="INTEGRAL" <?php echo (($_GET['turno'] ?? '') === 'INTEGRAL') ? 'selected' : ''; ?>>Integral</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                        Gerar
                    </button>
                </div>
            </form>

            <!-- CHANGED: table -> cards for a cleaner, modern look -->
            <div>
                <?php if ($report === 'daily' && $mysqli): ?>
                    <?php if (count($dailyRows) === 0): ?>
                        <div class="flex items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8">
                            <div class="text-center">
                                <div class="mx-auto mb-3 w-10 h-10 rounded-full bg-green-100 text-green-700 flex items-center justify-center">ℹ️</div>
                                <p class="text-gray-600">Nenhum registro encontrado para os filtros informados.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <?php foreach ($dailyRows as $row): ?>
                                <?php
                                    $wkg = desperdicioKg($mysqli, (int)$row['escola_id'], $row['data'], $row['turno']);
                                    $dataFmt = date('Y-m-d', strtotime($row['data']));
                                ?>
                                <div class="rounded-xl border bg-gradient-to-br from-white to-gray-50 p-4 shadow-sm hover:shadow-md transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-base font-semibold text-gray-800">
                                            <?php echo htmlspecialchars(escolaNome($mysqli, (int)$row['escola_id'])); ?>
                                        </h4>
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                            <?php echo htmlspecialchars($row['turno'] ?? '-'); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 mb-3">Data: <?php echo htmlspecialchars($dataFmt); ?></p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-lg bg-white border p-3">
                                            <p class="text-xs text-gray-500">Refeições Servidas</p>
                                            <p class="text-lg font-semibold text-gray-800"><?php echo (int)$row['alunos_atendidos']; ?></p>
                                        </div>
                                        <div class="rounded-lg bg-white border p-3">
                                            <p class="text-xs text-gray-500">Desperdício (kg)</p>
                                            <p class="text-lg font-semibold text-gray-800"><?php echo number_format($wkg, 2, ',', '.'); ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($row['observacoes'])): ?>
                                        <p class="mt-3 text-sm text-gray-600">Obs: <?php echo htmlspecialchars($row['observacoes']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="flex items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8">
                        <p class="text-gray-600">Preencha os filtros acima e clique em "Gerar" para visualizar o relatório diário.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 flex gap-2">
                <!-- You can wire these to an export endpoint if needed -->
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg">Exportar PDF</button>
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg">Exportar CSV</button>
            </div>
        </div>

        <!-- --- added: monthly report wired to DB --- -->
        <div id="monthlyReport" class="mt-8 bg-white rounded-2xl p-6 shadow-sm hidden">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Relatório Mensal</h3>
            <form method="get" action="relatorio_merenda.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <input type="hidden" name="report" value="monthly" />
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Mês</label>
                    <input type="month" name="month" value="<?php echo htmlspecialchars($_GET['month'] ?? date('Y-m')); ?>" class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Escola</label>
                    <select name="escola_id" class="w-full border rounded-lg px-3 py-2">
                        <option value="">Todas</option>
                        <?php foreach ($escolas as $e): ?>
                            <option value="<?php echo (int)$e['id']; ?>" <?php echo (isset($_GET['escola_id']) && $_GET['escola_id'] == $e['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Turno</label>
                    <select name="turno" class="w-full border rounded-lg px-3 py-2">
                        <option value="">Todos</option>
                        <option value="MANHA" <?php echo (($_GET['turno'] ?? '') === 'MANHA') ? 'selected' : ''; ?>>Manhã</option>
                        <option value="TARDE" <?php echo (($_GET['turno'] ?? '') === 'TARDE') ? 'selected' : ''; ?>>Tarde</option>
                        <option value="NOITE" <?php echo (($_GET['turno'] ?? '') === 'NOITE') ? 'selected' : ''; ?>>Noite</option>
                        <option value="INTEGRAL" <?php echo (($_GET['turno'] ?? '') === 'INTEGRAL') ? 'selected' : ''; ?>>Integral</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                        Gerar
                    </button>
                </div>
            </form>

            <!-- CHANGED: table -> cards; added sleek summary -->
            <div>
                <?php if ($report === 'monthly' && $mysqli): ?>
                    <?php if (count($monthlyRows) === 0): ?>
                        <div class="flex items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8">
                            <div class="text-center">
                                <div class="mx-auto mb-3 w-10 h-10 rounded-full bg-green-100 text-green-700 flex items-center justify-center">ℹ️</div>
                                <p class="text-gray-600">Nenhum registro encontrado para os filtros informados.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php $sumKg = 0.0; $sumMeals = 0; $m = htmlspecialchars($_GET['month'] ?? date('Y-m')); ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <?php foreach ($monthlyRows as $row): ?>
                                <?php
                                    $sumKg    += (float)$row['waste_kg'];
                                    $sumMeals += (int)$row['total_atendidos'];
                                ?>
                                <div class="rounded-xl border bg-gradient-to-br from-white to-gray-50 p-4 shadow-sm hover:shadow-md transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-base font-semibold text-gray-800">
                                            <?php echo htmlspecialchars(escolaNome($mysqli, (int)$row['escola_id'])); ?>
                                        </h4>
                                        <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700">
                                            <?php echo htmlspecialchars($row['turno'] ?? '-'); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 mb-3">Mês: <?php echo $m; ?></p>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="rounded-lg bg-white border p-3">
                                            <p class="text-xs text-gray-500">Dias com registro</p>
                                            <p class="text-lg font-semibold text-gray-800"><?php echo (int)($row['dias_registrados'] ?? 0); ?></p>
                                        </div>
                                        <div class="rounded-lg bg-white border p-3">
                                            <p class="text-xs text-gray-500">Refeições</p>
                                            <p class="text-lg font-semibold text-gray-800"><?php echo (int)($row['total_atendidos'] ?? 0); ?></p>
                                        </div>
                                        <div class="rounded-lg bg-white border p-3">
                                            <p class="text-xs text-gray-500">Desperdício (kg)</p>
                                            <p class="text-lg font-semibold text-gray-800"><?php echo number_format((float)$row['waste_kg'], 2, ',', '.'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Summary footer -->
                        <div class="mt-6 rounded-xl border bg-white p-4 flex items-center justify-between">
                            <div class="text-sm text-gray-600">Total no mês selecionado</div>
                            <div class="flex gap-6">
                                <div>
                                    <div class="text-xs text-gray-500">Refeições</div>
                                    <div class="text-lg font-semibold text-gray-800"><?php echo (int)$sumMeals; ?></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Desperdício (kg)</div>
                                    <div class="text-lg font-semibold text-gray-800"><?php echo number_format((float)$sumKg, 2, ',', '.'); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="flex items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8">
                        <p class="text-gray-600">Selecione o mês e clique em "Gerar" para visualizar o relatório mensal.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 flex gap-2">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg">Exportar PDF</button>
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg">Exportar CSV</button>
            </div>
        </div>

        <!-- --- added: waste (desperdício) report wired to DB -->
        <div id="wasteReport" class="mt-8 bg-white rounded-2xl p-6 shadow-sm hidden">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Relatório de Desperdício</h3>
            <form method="get" action="relatorio_merenda.php" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <input type="hidden" name="report" value="waste" />
                <div>
                    <label class="block text-sm text-gray-700 mb-1">De</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'))); ?>" class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Até</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($_GET['end_date'] ?? date('Y-m-d')); ?>" class="w-full border rounded-lg px-3 py-2" />
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Escola</label>
                    <select name="escola_id" class="w-full border rounded-lg px-3 py-2">
                        <option value="">Todas</option>
                        <?php foreach ($escolas as $e): ?>
                            <option value="<?php echo (int)$e['id']; ?>" <?php echo (isset($_GET['escola_id']) && $_GET['escola_id'] == $e['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Turno</label>
                    <select name="turno" class="w-full border rounded-lg px-3 py-2">
                        <option value="">Todos</option>
                        <option value="MANHA" <?php echo (($_GET['turno'] ?? '') === 'MANHA') ? 'selected' : ''; ?>>Manhã</option>
                        <option value="TARDE" <?php echo (($_GET['turno'] ?? '') === 'TARDE') ? 'selected' : ''; ?>>Tarde</option>
                        <option value="NOITE" <?php echo (($_GET['turno'] ?? '') === 'NOITE') ? 'selected' : ''; ?>>Noite</option>
                        <option value="INTEGRAL" <?php echo (($_GET['turno'] ?? '') === 'INTEGRAL') ? 'selected' : ''; ?>>Integral</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-700 mb-1">Motivo</label>
                    <select name="motivo" class="w-full border rounded-lg px-3 py-2">
                        <option value="">Todos</option>
                        <option value="EXCESSO_PREPARO" <?php echo (($_GET['motivo'] ?? '') === 'EXCESSO_PREPARO') ? 'selected' : ''; ?>>Excesso de preparo</option>
                        <option value="REJEICAO_ALUNOS" <?php echo (($_GET['motivo'] ?? '') === 'REJEICAO_ALUNOS') ? 'selected' : ''; ?>>Rejeição dos alunos</option>
                        <option value="VALIDADE_VENCIDA" <?php echo (($_GET['motivo'] ?? '') === 'VALIDADE_VENCIDA') ? 'selected' : ''; ?>>Validade vencida</option>
                        <option value="PREPARO_INCORRETO" <?php echo (($_GET['motivo'] ?? '') === 'PREPARO_INCORRETO') ? 'selected' : ''; ?>>Preparo incorreto</option>
                        <option value="OUTROS" <?php echo (($_GET['motivo'] ?? '') === 'OUTROS') ? 'selected' : ''; ?>>Outros</option>
                    </select>
                </div>
                <div class="md:col-span-5 flex items-end">
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                        Gerar
                    </button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full border rounded-lg">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Unidade Escolar</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Data</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Turno</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Produto</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Quantidade</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Unidade</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Peso (kg)</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Motivo</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($report === 'waste' && $mysqli): ?>
                            <?php if (count($wasteRows) === 0): ?>
                                <tr class="border-t">
                                    <td colspan="9" class="px-4 py-3 text-gray-500">Nenhum registro encontrado para os filtros informados.</td>
                                </tr>
                            <?php else: ?>
                                <?php $totalKg = 0.0; ?>
                                <?php foreach ($wasteRows as $row): ?>
                                    <?php
                                        $totalKg += (float)($row['peso_kg'] ?? 0);
                                        $dataFmt = date('Y-m-d', strtotime($row['data'])); // CHANGED: normalize date display
                                    ?>
                                    <tr class="border-t">
                                        <td class="px-4 py-2"><?php echo htmlspecialchars(escolaNome($mysqli, (int)$row['escola_id'])); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($dataFmt); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['turno'] ?? '-'); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars(produtoNome($mysqli, (int)$row['produto_id'])); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['quantidade'] ?? ''); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['unidade_medida'] ?? ''); ?></td>
                                        <td class="px-4 py-2"><?php echo number_format((float)($row['peso_kg'] ?? 0), 2, ',', '.'); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['motivo'] ?? ''); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['observacoes'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr class="border-t">
                                <td colspan="9" class="px-4 py-3 text-gray-500">Preencha os filtros acima e clique em "Gerar" para visualizar o relatório de desperdício.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($report === 'waste' && $mysqli && count($wasteRows) > 0): ?>
                    <tfoot class="bg-gray-50">
                        <tr class="border-t">
                            <td colspan="6" class="px-4 py-2 text-right font-medium text-gray-700">Total (kg):</td>
                            <td class="px-4 py-2 font-semibold text-gray-900"><?php echo number_format($totalKg, 2, ',', '.'); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <div class="mt-4 flex gap-2">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg">Exportar PDF</button>
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg">Exportar CSV</button>
            </div>
        </div>

        <!-- ... any remaining content ... -->
    </main>
    
    <?php include(__DIR__ . '/components/logout_modal.php'); ?>
</body>
</html>