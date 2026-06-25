import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

const chartTextColor = '#b8c5d8';
const gridColor = 'rgba(148, 163, 184, 0.18)';
const moneyFormatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',
  maximumFractionDigits: 2,
});

function money(value) {
  return moneyFormatter.format(Number(value) || 0);
}

function chartElement(id) {
  return document.getElementById(id);
}

function readChartData() {
  const source = document.getElementById('finance-report-chart-data');

  if (!source) {
    return {};
  }

  try {
    return JSON.parse(source.textContent || '{}');
  } catch (error) {
    return {};
  }
}

function rowsFrom(section) {
  return Array.isArray(section?.rows)
    ? section.rows.filter((row) => Number(row.amount) > 0)
    : [];
}

function hasSeriesData(series) {
  return Array.isArray(series) && series.some((item) => Array.isArray(item.data) && item.data.some((value) => Number(value) !== 0));
}

function renderEmpty(id) {
  const element = chartElement(id);

  if (!element) {
    return;
  }

  element.innerHTML = '<div class="d-flex align-items-center justify-content-center text-muted h-100 py-5">Sin datos para graficar.</div>';
}

function renderDonut(id, section) {
  const element = chartElement(id);
  const rows = rowsFrom(section);

  if (!element) {
    return;
  }

  if (rows.length === 0) {
    renderEmpty(id);
    return;
  }

  new ApexCharts(element, {
    chart: {
      type: 'donut',
      height: 280,
      toolbar: { show: false },
      foreColor: chartTextColor,
    },
    series: rows.map((row) => Number(row.amount) || 0),
    labels: rows.map((row) => row.name),
    colors: rows.map((row) => row.color || '#64748b'),
    stroke: {
      width: 2,
      colors: ['#1f2933'],
    },
    dataLabels: {
      enabled: true,
      formatter: (value) => `${Number(value).toFixed(1)}%`,
    },
    legend: {
      position: 'bottom',
      fontSize: '13px',
      markers: { radius: 12 },
    },
    plotOptions: {
      pie: {
        donut: {
          size: '64%',
          labels: {
            show: true,
            total: {
              show: true,
              label: 'Total',
              formatter: (w) => money(w.globals.seriesTotals.reduce((sum, value) => sum + value, 0)),
            },
            value: {
              formatter: (value) => money(value),
            },
          },
        },
      },
    },
    tooltip: {
      theme: 'dark',
      y: { formatter: (value) => money(value) },
    },
    responsive: [{
      breakpoint: 576,
      options: {
        chart: { height: 240 },
        legend: { show: false },
      },
    }],
  }).render();
}

function renderHorizontalBar(id, section) {
  const element = chartElement(id);
  const rows = rowsFrom(section);

  if (!element) {
    return;
  }

  if (rows.length === 0) {
    renderEmpty(id);
    return;
  }

  new ApexCharts(element, {
    chart: {
      type: 'bar',
      height: Math.max(260, rows.length * 42 + 90),
      toolbar: { show: false },
      foreColor: chartTextColor,
    },
    series: [{
      name: 'Monto',
      data: rows.map((row) => Number(row.amount) || 0),
    }],
    colors: rows.map((row) => row.color || '#64748b'),
    plotOptions: {
      bar: {
        horizontal: true,
        distributed: true,
        borderRadius: 4,
        barHeight: '68%',
      },
    },
    dataLabels: {
      enabled: true,
      formatter: (value) => money(value),
      style: { colors: ['#e5edf8'] },
    },
    xaxis: {
      categories: rows.map((row) => row.name),
      labels: { formatter: (value) => money(value) },
    },
    yaxis: {
      labels: { maxWidth: 170 },
    },
    grid: {
      borderColor: gridColor,
      strokeDashArray: 4,
    },
    legend: { show: false },
    tooltip: {
      theme: 'dark',
      y: { formatter: (value) => money(value) },
    },
  }).render();
}

function renderYearPerspective(section) {
  const element = chartElement('reports-year-perspective-column');

  if (!element) {
    return;
  }

  if (!hasSeriesData(section?.series)) {
    renderEmpty('reports-year-perspective-column');
    return;
  }

  new ApexCharts(element, {
    chart: {
      type: 'bar',
      height: 320,
      toolbar: { show: false },
      foreColor: chartTextColor,
    },
    series: section.series,
    colors: section.colors || ['#22c55e', '#ef4444', '#60a5fa'],
    plotOptions: {
      bar: {
        columnWidth: '58%',
        borderRadius: 3,
      },
    },
    dataLabels: { enabled: false },
    xaxis: { categories: section.labels || [] },
    yaxis: { labels: { formatter: (value) => money(value) } },
    grid: {
      borderColor: gridColor,
      strokeDashArray: 4,
    },
    legend: { position: 'bottom' },
    tooltip: {
      theme: 'dark',
      y: { formatter: (value) => money(value) },
    },
  }).render();
}

function renderCoverage(section) {
  const element = chartElement('reports-coverage-bar');

  if (!element) {
    return;
  }

  if (!hasSeriesData(section?.series)) {
    renderEmpty('reports-coverage-bar');
    return;
  }

  new ApexCharts(element, {
    chart: {
      type: 'bar',
      height: 280,
      stacked: true,
      stackType: 'normal',
      toolbar: { show: false },
      foreColor: chartTextColor,
    },
    series: section.series,
    colors: section.colors || ['#22c55e', '#f59e0b', '#dc2626'],
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 4,
        barHeight: '58%',
      },
    },
    dataLabels: {
      enabled: true,
      formatter: (value) => (Number(value) > 0 ? money(value) : ''),
    },
    xaxis: {
      categories: section.labels || [],
      labels: { formatter: (value) => money(value) },
    },
    grid: {
      borderColor: gridColor,
      strokeDashArray: 4,
    },
    legend: { position: 'bottom' },
    tooltip: {
      theme: 'dark',
      y: { formatter: (value) => money(value) },
    },
  }).render();
}

document.addEventListener('DOMContentLoaded', () => {
  const data = readChartData();

  renderDonut('reports-real-distribution-donut', data.realDistribution);
  renderDonut('reports-expense-category-donut', data.expenseCategories);
  renderDonut('reports-obligation-mix-donut', data.obligationMix);
  renderHorizontalBar('reports-top-income-bar', data.topIncome);
  renderHorizontalBar('reports-top-expenses-bar', data.topExpenses);
  renderCoverage(data.coverage || {});
  renderYearPerspective(data.yearPerspective || {});
});
