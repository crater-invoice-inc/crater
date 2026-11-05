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

const COLOR_SALES = '#7C8DF5'
const COLOR_RECEIPTS = '#3FAE8C'
const COLOR_EXPENSES = '#E0785C'
const COLOR_INCOME = '#4B5563'

let myLineChart = null
const graph = ref(null)
const tooltip = ref(null)
const companyStore = useCompanyStore()
const defaultCurrency = computed(() => {
  return companyStore.selectedCompanyCurrency
})

function makeDataset(label, color, values) {
  return {
    label,
    fill: false,
    lineTension: 0.4,
    borderColor: color,
    borderWidth: 2,
    borderCapStyle: 'round',
    borderJoinStyle: 'round',
    pointBorderColor: color,
    pointBackgroundColor: '#fff',
    pointBorderWidth: 2,
    pointHoverRadius: 6,
    pointHoverBackgroundColor: color,
    pointHoverBorderColor: '#fff',
    pointHoverBorderWidth: 2,
    pointRadius: 3.5,
    pointHitRadius: 12,
    data: values.map((value) => value / 100),
  }
}

function buildDatasets() {
  let datasets = [
    makeDataset(t('dashboard.chart_info.total_sales'), COLOR_SALES, props.invoices),
    makeDataset(t('dashboard.chart_info.total_receipts'), COLOR_RECEIPTS, props.receipts),
    makeDataset(t('dashboard.chart_info.total_expense'), COLOR_EXPENSES, props.expenses),
  ]

  if (props.income && props.income.length) {
    datasets.push(
      makeDataset(t('dashboard.chart_info.net_income'), COLOR_INCOME, props.income)
    )
  }

  return datasets
}

function formatValue(value) {
  return utils.formatMoney(Math.round(value * 100), defaultCurrency.value)
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
            fontColor: '#9ca3af',
            fontSize: 12,
            padding: 8,
          },
        },
      ],
      yAxes: [
        {
          gridLines: {
            color: '#f3f4f6',
            drawBorder: false,
            borderDash: [4, 4],
            zeroLineColor: '#f3f4f6',
            zeroLineBorderDash: [4, 4],
          },
          ticks: {
            fontColor: '#9ca3af',
            fontSize: 12,
            padding: 8,
            maxTicksLimit: 6,
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
  })
})
</script>

<style>
/* Not scoped: the tooltip markup is injected via innerHTML, so it never
   receives the scoped-style data attribute. Classes are prefixed instead. */
.chart-tooltip {
  position: absolute;
  min-width: 190px;
  background: #fff;
  border: 1px solid #f3f4f6;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  padding: 12px 14px;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.12s ease;
  z-index: 20;
}

.chart-tooltip-title {
  font-weight: 600;
  font-size: 13px;
  color: #111827;
  margin-bottom: 8px;
}

.chart-tooltip-row {
  display: flex;
  align-items: center;
  font-size: 13px;
  color: #4b5563;
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
  color: #111827;
  margin-left: 24px;
}
</style>
