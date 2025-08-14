import Chart from 'chart.js/auto';

export function initDashboardCharts() {
  
    
    // Invoice
    const invCanvas = document.getElementById('invoiceChart');
    if (invCanvas) {
      // prendi i dati dai data-attributes
      const months = JSON.parse(invCanvas.dataset.months);
      const invData = JSON.parse(invCanvas.dataset.invData);
  
      // distruggi se esiste già
      if (window._invoiceChart) window._invoiceChart.destroy();
  
      window._invoiceChart = new Chart(
        invCanvas.getContext('2d'),
        {
          type: 'line',
          data: {
            labels: months,
            datasets: [{
              label:           'Totale Fatturato (€)',
              data:            invData,
              borderColor:     '#AD96FF',
              backgroundColor: 'rgba(173,150,255,0.3)',
              fill:            true,
              tension:         0.3
            }]
          },
          options: {
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
          }
        }
      );
    }


    // Subscription
    const subCanvas = document.getElementById('subscriptionChart');
    if (subCanvas) {
    const months       = JSON.parse(subCanvas.dataset.months);
    const subData      = JSON.parse(subCanvas.dataset.subData);
    const forecastData = JSON.parse(subCanvas.dataset.subForecast);

    if (window._subscriptionChart) window._subscriptionChart.destroy();

    window._subscriptionChart = new Chart(
        subCanvas.getContext('2d'),
        {
        type: 'line',
        data: {
            labels: months,
            datasets: [
            {
                label:           'Totale Abbonamenti (€)',
                data:            subData,
                borderColor:     '#AD96FF',
                backgroundColor: 'rgba(173,150,255,0.3)',
                fill:            true,
                tension:         0.3
            },
            {
                label:           'Previsioni (€)',
                data:            forecastData,
                borderColor:     '#AD96FF',
                backgroundColor: 'rgba(173,150,255,0.1)',
                fill:            true,
                borderDash:      [6, 2],
                tension:         0.3
            }
            ]
        },
        options: {
            scales: {
            y: { beginAtZero: true }
            },
            plugins: {
            legend: { display: true }
            }
        }
        }
    );
  }

  // IVA Chart
  const ivaCanvas = document.getElementById('ivaChart');
  if (ivaCanvas) {
    const months = JSON.parse(ivaCanvas.dataset.months);
    const ivaData = JSON.parse(ivaCanvas.dataset.ivaData);

    if (window._ivaChart) window._ivaChart.destroy();

    window._ivaChart = new Chart(
      ivaCanvas.getContext('2d'),
      {
        type: 'bar',
        data: {
          labels: months,
          datasets: [{
            label:           'IVA da Versare (€)',
            data:            ivaData,
            borderColor:     '#ef4444',
            backgroundColor: 'rgba(239,68,68,0.3)',
            fill:            true,
          }]
        },
        options: {
          scales: { y: { beginAtZero: true } },
          plugins: { legend: { display: false } }
        }
      }
    );
  }

  // Profit Chart
  const profitCanvas = document.getElementById('profitChart');
  if (profitCanvas) {
    const months = JSON.parse(profitCanvas.dataset.months);
    const profitData = JSON.parse(profitCanvas.dataset.profitData);

    if (window._profitChart) window._profitChart.destroy();

    window._profitChart = new Chart(
      profitCanvas.getContext('2d'),
      {
        type: 'line',
        data: {
          labels: months,
          datasets: [{
            label:           'Utile Netto (€)',
            data:            profitData,
            borderColor:     '#a855f7',
            backgroundColor: 'rgba(168,85,247,0.3)',
            fill:            true,
            tension:         0.3
          }]
        },
        options: {
          scales: { y: { beginAtZero: false } },
          plugins: { legend: { display: false } }
        }
      }
    );
  }
}