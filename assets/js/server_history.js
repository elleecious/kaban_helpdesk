// js/server_history.js  (needs jQuery and Chart.js loaded first)
(function () {
  const REFRESH_MS = 60000;
  let range = '6h';
  let chart = null;
  let timer = null;

  function dataset(label, data, color, extra) {
    return Object.assign({
      label: label,
      data: data,
      borderColor: color,
      backgroundColor: color,
      borderWidth: 2,
      pointRadius: 0,
      tension: 0.25,
      spanGaps: false
    }, extra || {});
  }

  function render(res) {
    const pts = res.points || [];
    const labels = pts.map(p => p.label);

    const sets = [
      dataset('CPU (load-based)', pts.map(p => p.cpu), '#7c3aed'),
      dataset('RAM', pts.map(p => p.ram), '#0ea5e9'),
      dataset('Disk', pts.map(p => p.disk), '#f59e0b'),
      dataset('Critical (90%)', pts.map(() => 90), '#ef4444', {
        borderWidth: 1, borderDash: [6, 4], tension: 0
      })
    ];

    if (!chart) {
      chart = new Chart(document.getElementById('server-history-chart'), {
        type: 'line',
        data: { labels: labels, datasets: sets },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          scales: {
            y: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
            x: { ticks: { maxTicksLimit: 10 } }
          },
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { 
              callbacks: { 
                label: c => c.dataset.label + ': ' + c.parsed.y + '%' 
              },
              displayColors: false, 
            }
          }
        }
      });
    } else {
      chart.data.labels = labels;
      chart.data.datasets = sets;
      chart.update();
    }

    if (!pts.length) {
      $('#server-history-summary').text('No readings yet. The collector saves one reading per minute.');
    } else {
      $('#server-history-summary').text(
        res.checks + ' readings, ' + res.degraded + ' degraded. Each point shows the peak in its interval.'
      );
    }
  }

  function load() {
    clearTimeout(timer);
    $.ajax({
      url: './api/server_metrics_history.php',
      data: { range: range },
      dataType: 'json',
      cache: false,
      timeout: 15000
    })
      .done(render)
      .fail(function (xhr) {
        $('#server-history-summary').text(
          xhr.status === 401 ? 'Session expired. Please log in again.' : 'Could not load history.'
        );
      })
      .always(function () {
        if (!document.hidden) timer = setTimeout(load, REFRESH_MS);
      });
  }

  $(function () {
    if (!document.getElementById('server-history-chart')) return;

    $('#server-history-ranges').on('click', 'button[data-range]', function () {
      range = $(this).data('range');
      $('#server-history-ranges button').removeClass('active');
      $(this).addClass('active');
      load();
    });

    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) load();
    });

    load();
  });
})();
