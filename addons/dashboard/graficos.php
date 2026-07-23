<?php
// graficos.php — desenha 3 gráficos usando Chart.js

if (!isset($conn) || !($conn instanceof mysqli)) {
    return; // segurança
}

$meses_qtd = max(1, (int)($qtd_meses_graficos ?? 3));

// ------------- FATURAMENTO (últimos N meses) -------------
$labels = [];
$fatPago = [];
$fatAberto = [];

for ($i = $meses_qtd - 1; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $labels[] = date('m/Y', strtotime("$ym-01"));

    $ymEsc = $conn->real_escape_string($ym);

    // pago
    $rsP = mysqli_query(
        $conn,
        "SELECT COALESCE(SUM(valor),0) AS tot
           FROM sis_lanc
          WHERE status = 'pago'
            AND deltitulo = 0
            AND DATE_FORMAT(datavenc,'%Y-%m') = '$ymEsc'"
    );
    $fatPago[] = (float) (mysqli_fetch_assoc($rsP)['tot'] ?? 0);

    // em aberto
    $rsA = mysqli_query(
        $conn,
        "SELECT COALESCE(SUM(valor),0) AS tot
           FROM sis_lanc
          WHERE status <> 'pago'
            AND deltitulo = 0
            AND DATE_FORMAT(datavenc,'%Y-%m') = '$ymEsc'"
    );
    $fatAberto[] = (float) (mysqli_fetch_assoc($rsA)['tot'] ?? 0);
}

// ------------- CHAMADOS vs INSTALAÇÕES (últimos N meses) -------------
$chaMes = [];
$insMes = [];

for ($i = $meses_qtd - 1; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $ymEsc = $conn->real_escape_string($ym);

    $rC = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS tot FROM sis_suporte
          WHERE DATE_FORMAT(abertura,'%Y-%m') = '$ymEsc'"
    );
    $chaMes[] = (int) (mysqli_fetch_assoc($rC)['tot'] ?? 0);

    // conta instalação pela data da instalação (se nula, conta pela data da visita – mantém compatibilidade)
    $rI = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS tot FROM sis_solic
          WHERE (DATE_FORMAT(datainst,'%Y-%m') = '$ymEsc'
                 OR (datainst IS NULL AND DATE_FORMAT(visita,'%Y-%m') = '$ymEsc'))"
    );
    $insMes[] = (int) (mysqli_fetch_assoc($rI)['tot'] ?? 0);
}

// ------------- CLIENTES (snapshot atual) -------------
$tot_clientes   = (int) ($tot_clientes   ?? 0);
$cli_on         = (int) ($cli_on         ?? 0);
$cli_offline    = (int) ($cli_offline    ?? max(0, $tot_clientes - $cli_on));
$cli_bloq       = (int) ($cli_bloq       ?? 0);
$cli_livres     = max(0, $tot_clientes - $cli_bloq);

?>
<div class='row'>
  <!-- FATURAMENTO -->
  <div class='col-12 mb-2'>
    <div class='card border-light'>
      <div class='card-header text-uppercase'>Faturamento</div>
      <div class='card-body'>
        <canvas id="chartFaturamento" height="120"></canvas>
      </div>
    </div>
  </div>

  <!-- CLIENTES (pizza) -->
  <div class='col-12 mb-2'>
    <div class='card border-light'>
      <div class='card-header text-uppercase'>Clientes (Atual)</div>
      <div class='card-body'>
        <canvas id="chartClientes" width="120" height="120"></canvas>
      </div>
    </div>
  </div>

  <!-- CHAMADOS x INSTALAÇÕES -->
  <div class='col-12 mb-2'>
    <div class='card border-light'>
      <div class='card-header text-uppercase'>Chamados x Instalações</div>
      <div class='card-body'>
        <canvas id="chartChamados" height="120"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
// Protege contra falta do Chart.js
if (!window.Chart) {
  console.error('Chart.js não foi carregado.');
} else {
  // Dados vindos do PHP
  const lblMeses      = <?php echo json_encode($labels,    JSON_UNESCAPED_UNICODE); ?>;
  const fatPago       = <?php echo json_encode($fatPago,   JSON_NUMERIC_CHECK); ?>;
  const fatAberto     = <?php echo json_encode($fatAberto, JSON_NUMERIC_CHECK); ?>;

  const cliOn         = <?php echo (int)$cli_on; ?>;
  const cliOff        = <?php echo (int)$cli_offline; ?>;
  const cliBloq       = <?php echo (int)$cli_bloq; ?>;
  const cliLivres     = <?php echo (int)$cli_livres; ?>;

  const chaMes        = <?php echo json_encode($chaMes, JSON_NUMERIC_CHECK); ?>;
  const insMes        = <?php echo json_encode($insMes, JSON_NUMERIC_CHECK); ?>;

  // --------- FATURAMENTO (Barra empilhada) ---------
  new Chart(document.getElementById('chartFaturamento'), {
    type: 'bar',
    data: {
      labels: lblMeses,
      datasets: [
        {
          label: 'Pago',
          data: fatPago,
          backgroundColor: 'rgba(25, 135, 84, 0.8)'  // bootstrap .bg-success
        },
        {
          label: 'Em aberto',
          data: fatAberto,
          backgroundColor: 'rgba(220, 53, 69, 0.8)'  // bootstrap .bg-danger
        }
      ]
    },
    options: {
      responsive: true,
          maintainAspectRatio: false, // <- mantém no tamanho do container
      scales: {
        x: { stacked: true },
        y: { stacked: true, beginAtZero: true }
      },
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: (ctx) => 'R$ ' + (ctx.parsed.y || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})
          }
        }
      }
    }
  });

 new Chart(document.getElementById('chartClientes'), {
  type: 'doughnut',
  data: {
    labels: ['Online', 'Offline', 'Bloqueado', 'Livres'],
    datasets: [{
      data: [cliOn, cliOff, cliBloq, cliLivres],
      backgroundColor: [
        'rgba(25, 135, 84, 0.9)',  // verde
        'rgba(33, 37, 41, 0.9)',   // preto/cinza
        'rgba(220, 53, 69, 0.9)',  // vermelho
        'rgba(13, 110, 253, 0.9)'  // azul
      ],
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false, // <- mantém no tamanho do container
    plugins: {
      legend: { position: 'right' }
    }
  }
});

  // --------- CHAMADOS x INSTALAÇÕES (Linhas) ---------
  new Chart(document.getElementById('chartChamados'), {
    type: 'line',
    data: {
      labels: lblMeses,
      datasets: [
        {
          label: 'Chamados',
          data: chaMes,
          borderColor: 'rgba(13,110,253,1)',
          backgroundColor: 'rgba(13,110,253,0.2)',
          tension: 0.3,
          fill: true
        },
        {
          label: 'Instalações',
          data: insMes,
          borderColor: 'rgba(255,193,7,1)',
          backgroundColor: 'rgba(255,193,7,0.2)',
          tension: 0.3,
          fill: true
        }
      ]
    },
    options: {
      responsive: true,
          maintainAspectRatio: false, // <- mantém no tamanho do container
      scales: {
        y: { beginAtZero: true, precision: 0 }

      }
    }
  });
}
</script>
