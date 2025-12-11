<!-- ... existing code ... -->

<!-- Layout + styles to show the admin merenda sidebar and center the CTA -->
<style>
  .dashboard-layout {
    display: flex;
    min-height: 70vh;
    gap: 0;
  }
  .dashboard-sidebar {
    width: 260px;
    background: #f7f7f9;
    border-right: 1px solid #ddd;
  }
  .dashboard-content {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }
  .center-box {
    text-align: center;
    max-width: 560px;
  }
  .center-box h1 {
    margin-bottom: 16px;
    font-size: 24px;
  }
  .center-box .cta-btn {
    padding: 12px 18px;
    font-size: 16px;
    border: 1px solid #2c3e50;
    border-radius: 6px;
    background: #2c3e50;
    color: #fff;
    cursor: pointer;
  }
  .center-box .cta-btn:hover {
    background: #1f2c3a;
  }
</style>

<div class="dashboard-layout">
  <aside class="dashboard-sidebar">
    <?php
      // Replace with the correct absolute path to your admin merenda sidebar file
      // Example path (adjust as needed):
      // c:\xampp\htdocs\projeto estagio\Gest-o-Escolar-\app\main\Views\partials\sidebar_admin_merenda.php
      $sidebarPath = 'c:\xampp\htdocs\projeto estagio\Gest-o-Escolar-\app\main\Views\partials\sidebar_admin_merenda.php';
      if (file_exists($sidebarPath)) {
        include $sidebarPath;
      } else {
        echo '<div style="padding:16px;font-weight:600;">Sidebar Administrador Merenda</div>';
      }
    ?>
  </aside>

  <main class="dashboard-content">
    <div class="center-box">
      <h1>Gerar Relatório Financeiro</h1>
      <button type="button" class="cta-btn" id="btnGerarRelatorioFinanceiro">Gerar Relatório Financeiro</button>
    </div>
  </main>
</div>

<script>
  (function () {
    var btn = document.getElementById('btnGerarRelatorioFinanceiro');
    if (btn) {
      btn.addEventListener('click', function () {
        // TODO: replace this with your actual route or action to generate/download the report
        // Example: window.location.href = '/relatorios/merenda/financeiro';
        alert('Ação de geração do relatório financeiro.');
      });
    }
  })();
</script>

<!-- ... existing code ... -->