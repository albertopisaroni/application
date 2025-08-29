import Chart from 'chart.js/auto';

export function initDashboardCharts() {
  console.log('Initializing dashboard charts...');
  
  // Distruggi tutti i grafici esistenti prima di crearne di nuovi
  if (window._invoiceChart) {
    window._invoiceChart.destroy();
    window._invoiceChart = null;
  }
  if (window._subscriptionChart) {
    window._subscriptionChart.destroy();
    window._subscriptionChart = null;
  }
  if (window._netChart) {
    window._netChart.destroy();
    window._netChart = null;
  }
    
    // Invoice
    const invCanvas = document.getElementById('invoiceChart');
    if (invCanvas) {
      // prendi i dati dai data-attributes
      const months = JSON.parse(invCanvas.dataset.months || '[]');
      const invData = JSON.parse(invCanvas.dataset.invData || '[]');
      const invSubtotal = JSON.parse(invCanvas.dataset.invSubtotal || '[]');
      const granularity = invCanvas.dataset.granularity || 'monthly';
      
      console.log('Invoice chart data:', { months, invData, invSubtotal, granularity });
      
      // Se non ci sono dati, non creare il grafico
      if (months.length === 0) {
        console.log('No data for invoice chart, skipping...');
        return;
      }
  
      // distruggi se esiste già
      if (window._invoiceChart) window._invoiceChart.destroy();
  
      window._invoiceChart = new Chart(
        invCanvas.getContext('2d'),
        {
          type: 'line',
          data: {
            labels: months,
            datasets: [
              {
                label:           'Totale con IVA (€)',
                data:            invData,
                borderColor:     '#AD96FF',
                backgroundColor: 'rgba(173,150,255,0.3)',
                fill:            true,
                tension:         0.3
              },
              {
                label:           'Netto senza IVA (€)',
                data:            invSubtotal,
                borderColor:     '#8B5CF6',
                backgroundColor: 'rgba(139,92,246,0.1)',
                fill:            false,
                tension:         0.3,
                borderDash:      [3, 3]
              }
            ]
          },
          options: {
            scales: { 
              y: { 
                beginAtZero: false, // Permette valori negativi
                ticks: {
                  callback: function(value) {
                    return '€' + value.toLocaleString('it-IT', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                  }
                }
              },
              x: {
                ticks: {
                  maxRotation: granularity === 'weekly' ? 45 : 0,
                  minRotation: granularity === 'weekly' ? 45 : 0
                }
              }
            },
            plugins: { 
              legend: { 
                display: true,
                position: 'bottom'
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return context.dataset.label + ': €' + context.parsed.y.toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                  }
                }
              }
            }
          }
        }
      );
    }


    // Subscription
    const subCanvas = document.getElementById('subscriptionChart');
    if (subCanvas) {
    const months       = JSON.parse(subCanvas.dataset.months || '[]');
    const subData      = JSON.parse(subCanvas.dataset.subData || '[]');
    const forecastData = JSON.parse(subCanvas.dataset.subForecast || '[]');
    const granularity  = subCanvas.dataset.granularity || 'monthly';
    
    console.log('Subscription chart data:', { months, subData, forecastData, granularity });
    
    // Se non ci sono dati, non creare il grafico
    if (months.length === 0) {
      console.log('No data for subscription chart, skipping...');
      return;
    }

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
            y: { 
                beginAtZero: false, // Permette valori negativi
                ticks: {
                    callback: function(value) {
                        return '€' + value.toLocaleString('it-IT', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                    }
                }
            },
            x: {
                ticks: {
                    maxRotation: granularity === 'weekly' ? 45 : 0,
                    minRotation: granularity === 'weekly' ? 45 : 0
                }
            }
            },
            plugins: {
            legend: { 
                display: true,
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': €' + context.parsed.y.toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
            }
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
    const granularity = ivaCanvas.dataset.granularity || 'monthly';

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
          scales: { 
            y: { 
              beginAtZero: false, // Permette valori negativi
              ticks: {
                callback: function(value) {
                  return '€' + value.toLocaleString('it-IT', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                }
              }
            },
            x: {
              ticks: {
                maxRotation: granularity === 'weekly' ? 45 : 0,
                minRotation: granularity === 'weekly' ? 45 : 0
              }
            }
          },
          plugins: { 
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': €' + context.parsed.y.toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
              }
            }
          }
        }
      }
    );
  }

  // Profit Chart
  const profitCanvas = document.getElementById('profitChart');
  if (profitCanvas) {
    const months = JSON.parse(profitCanvas.dataset.months);
    const profitData = JSON.parse(profitCanvas.dataset.profitData);
    const granularity = profitCanvas.dataset.granularity || 'monthly';

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
          scales: { 
            y: { 
              beginAtZero: false,
              ticks: {
                callback: function(value) {
                  return '€' + value.toLocaleString('it-IT', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                }
              }
            },
            x: {
              ticks: {
                maxRotation: granularity === 'weekly' ? 45 : 0,
                minRotation: granularity === 'weekly' ? 45 : 0
              }
            }
          },
          plugins: { 
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': €' + context.parsed.y.toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
              }
            }
          }
        }
      }
    );
  }

  // Net Chart (Fatture - IVA - Spese)
  const netCanvas = document.getElementById('netChart');
  if (netCanvas) {
    const months = JSON.parse(netCanvas.dataset.months || '[]');
    const netData = JSON.parse(netCanvas.dataset.netData || '[]');
    const netForecast = JSON.parse(netCanvas.dataset.netForecast || '[]');
    const granularity = netCanvas.dataset.granularity || 'monthly';
    
    console.log('Net chart data:', { months, netData, netForecast, granularity });
    
    // Se non ci sono dati, non creare il grafico
    if (months.length === 0) {
      console.log('No data for net chart, skipping...');
      return;
    }

    // distruggi se esiste già
    if (window._netChart) window._netChart.destroy();

    window._netChart = new Chart(
      netCanvas.getContext('2d'),
      {
        type: 'line',
        data: {
          labels: months,
          datasets: [
            {
              label:           'Netto Reale (€)',
              data:            netData,
              borderColor:     '#10B981', // Verde
              backgroundColor: 'rgba(16,185,129,0.1)',
              fill:            true,
              tension:         0.3
            },
            {
              label:           'Netto Previsto (€)',
              data:            netForecast,
              borderColor:     '#F59E0B', // Arancione
              backgroundColor: 'rgba(245,158,11,0.1)',
              fill:            false,
              tension:         0.3,
              borderDash:      [5, 5] // Linea tratteggiata
            }
          ]
        },
        options: {
          scales: { 
            y: { 
              beginAtZero: false, // Permette valori negativi per il netto
              ticks: {
                callback: function(value) {
                  return '€' + value.toLocaleString('it-IT', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                }
              }
            },
            x: {
              ticks: {
                maxRotation: granularity === 'weekly' ? 45 : 0,
                minRotation: granularity === 'weekly' ? 45 : 0
              }
            }
          },
          plugins: { 
            legend: { 
              display: true,
              position: 'bottom'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': €' + context.parsed.y.toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
              }
            }
          }
        }
      }
    );
  }
}