<template>
  <div class="graph-container relative h-[320px]">
    <canvas id="graph" ref="graph" />
    <div ref="tooltip" class="chart-tooltip" />
  </div>
</template>

<script setup>
import Chart from 'chart.js'
import { ref, reactive, computed, onMounted, watchEffect, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const utils = inject('utils')
const { t } = useI18n()

const props = defineProps({
  labels: {
    type: Array,
    require: true,
    default: Array,
  },
  invoices: {
    type: Array,
    require: true,
    default: Array,
  },
  expenses: {
    type: Array,
    require: true,
    default: Array,
  },
  receipts: {
    type: Array,
    require: true,
    default: Array,
  },
  income: {
    type: Array,
    default: () => [],
  },
  year: {
    type: [Number, String],
    default: null,
  },
})

// Série InvoiceShelf (bleu / vert / orange, gris pour le résultat)
const COLOR_SALES = '#2A78D6'
const COLOR_RECEIPTS = '#1BAF7A'
const COLOR_EXPENSES = '#EB6834'
const COLOR_INCOME = '#646A7E'

const GRID_COLOR = '#E6E8EE'
const TICK_COLOR = '#646A7E'

let myLineChart = null
const graph = ref(null)
const tooltip = ref(null)
const companyStore = useCompanyStore()
const defaultCurrency = computed(() => {
  return companyStore.selectedCompanyCurrency
})

function hexToRgba(hex, alpha) {
  const value = hex.replace('#', '')
  const r = parseInt(value.substring(0, 2), 16)
  const g = parseInt(value.substring(2, 4), 16)
  const b = parseInt(value.substring(4, 6), 16)

  return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

// Dégradé vertical sous chaque courbe. Chart.js v2 résout les styles des
// datasets pendant update() : le hook beforeUpdate est le seul point où
// chartArea existe ET où la mutation est encore prise en compte.
function applyAreaGradients(chart) {
  const area = chart.chartArea

  if (!area) {
    return
  }

  chart.data.datasets.forEach((dataset) => {
    if (dataset.fill === false) {
      return
    }

    const gradient = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom)
    gradient.addColorStop(0, hexToRgba(dataset.borderColor, 0.16))
    gradient.addColorStop(1, hexToRgba(dataset.borderColor, 0))
    dataset.backgroundColor = gradient
  })
}

const areaGradient = {
  id: 'areaGradient',
  beforeUpdate(chart) {
    applyAreaGradients(chart)
  },
}

function makeDataset(label, color, values) {
  return {
    label,
    fill: true,
    lineTension: 0.4,
    borderColor: color,
    borderWidth: 2,
    borderCapStyle: 'round',
    borderJoinStyle: 'round',
    pointBackgroundColor: '#fff',
    pointBorderWidth: 0,
    pointHoverRadius: 5,
    pointHoverBackgroundColor: color,
    pointHoverBorderColor: '#fff',
    pointHoverBorderWidth: 2,
    pointRadius: 0,
    pointHitRadius: 12,
    data: values.map((value) => value / 100),
  }
}

// La courbe du résultat reste un simple trait gris : superposer son
// remplissage à ceux des ventes brouillerait les teintes du graphique.
function makeIncomeDataset(label, color, values) {
  const dataset = makeDataset(label, color, values)
  dataset.fill = false

  return dataset
}

function buildDatasets() {
  let datasets = [
    makeDataset(t('dashboard.chart_info.total_sales'), COLOR_SALES, props.invoices),
    makeDataset(t('dashboard.chart_info.total_receipts'), COLOR_RECEIPTS, props.receipts),
    makeDataset(t('dashboard.chart_info.total_expense'), COLOR_EXPENSES, props.expenses),
  ]

  if (props.income && props.income.length) {
    datasets.push(
      makeIncomeDataset(t('dashboard.chart_info.net_income'), COLOR_INCOME, props.income)
    )
  }

  return datasets
}

function formatValue(value) {
  return utils.formatMoney(Math.round(value * 100), defaultCurrency.value)
}

function compactTick(value) {
  const amount = value * 100

  if (Math.abs(amount) >= 1e6) {
    return `${amount / 1e6}M`
  }

  if (Math.abs(amount) >= 1e3) {
    return `${amount / 1e3}k`
  }

  return `${amount}`
}

function renderTooltip(model) {
  const el = tooltip.value

  if (!el) {
    return
  }

  if (model.opacity === 0 || !model.dataPoints || !model.dataPoints.length) {
    el.style.opacity = 0
    return
  }

  let html = `<div class="chart-tooltip-title">${model.title[0]} ${props.year || ''}</div>`

  model.dataPoints.forEach((point) => {
    let dataset = myLineChart.data.datasets[point.datasetIndex]
    html += `
      <div class="chart-tooltip-row">
        <span class="chart-tooltip-bar" style="background:${dataset.borderColor}"></span>
        <span class="chart-tooltip-label">${dataset.label}</span>
        <span class="chart-tooltip-value">${formatValue(point.value)}</span>
      </div>
    `
  })

  el.innerHTML = html
  el.style.opacity = 1

  const canvas = graph.value
  let left = canvas.offsetLeft + model.caretX + 16
  let top = canvas.offsetTop + model.caretY - el.offsetHeight - 16

  if (left + el.offsetWidth > canvas.offsetLeft + canvas.offsetWidth) {
    left = canvas.offsetLeft + model.caretX - el.offsetWidth - 16
  }
  if (top < 0) {
    top = canvas.offsetTop + model.caretY + 16
  }

  el.style.left = `${left}px`
  el.style.top = `${top}px`
}

watchEffect(() => {
  if (props.labels) {
    if (myLineChart) {
      myLineChart.data.labels = props.labels
      myLineChart.data.datasets = buildDatasets()
      myLineChart.update({
        lazy: true,
      })
    }
  }
})

onMounted(() => {
  let context = graph.value.getContext('2d')
  let options = reactive({
    responsive: true,
    maintainAspectRatio: false,
    tooltips: {
      enabled: false,
      mode: 'index',
      intersect: false,
      custom: renderTooltip,
    },
    legend: {
      display: false,
    },
    scales: {
      xAxes: [
        {
          gridLines: {
            display: false,
          },
          ticks: {
            fontColor: TICK_COLOR,
            fontSize: 12,
            padding: 8,
          },
        },
      ],
      yAxes: [
        {
          gridLines: {
            color: GRID_COLOR,
            drawBorder: false,
          },
          ticks: {
            fontColor: TICK_COLOR,
            fontSize: 12,
            padding: 8,
            maxTicksLimit: 5,
            callback: compactTick,
          },
        },
      ],
    },
  })

  myLineChart = new Chart(context, {
    type: 'line',
    data: {
      labels: props.labels,
      datasets: buildDatasets(),
    },
    options: options,
    plugins: [areaGradient],
  })

  // La zone du graphique n'existe qu'après la première passe : une seconde
  // update déclenche le hook beforeUpdate qui pose les dégradés.
  myLineChart.update()
})
</script>

<style>
/* Not scoped: the tooltip markup is injected via innerHTML, so it never
   receives the scoped-style data attribute. Classes are prefixed instead. */
.chart-tooltip {
  position: absolute;
  min-width: 190px;
  background: #fff;
  border: 1px solid #e6e8ee;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(28, 24, 69, 0.1);
  padding: 12px 14px;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.12s ease;
  z-index: 20;
}

.chart-tooltip-title {
  font-weight: 600;
  font-size: 13px;
  color: #161827;
  margin-bottom: 8px;
}

.chart-tooltip-row {
  display: flex;
  align-items: center;
  font-size: 13px;
  color: #3c4052;
  margin-top: 4px;
}

.chart-tooltip-bar {
  width: 3px;
  height: 14px;
  border-radius: 2px;
  margin-right: 8px;
}

.chart-tooltip-label {
  flex: 1;
}

.chart-tooltip-value {
  font-weight: 500;
  color: #161827;
  margin-left: 24px;
}
</style>
