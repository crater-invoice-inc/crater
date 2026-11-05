<template>
  <div class="graph-container h-[320px]">
    <canvas id="graph" ref="graph" />
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
})

const COLOR_SALES = '#7C8DF5'
const COLOR_RECEIPTS = '#3FAE8C'
const COLOR_EXPENSES = '#E0785C'
const COLOR_INCOME = '#4B5563'

let myLineChart = null
const graph = ref(null)
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
      enabled: true,
      mode: 'index',
      intersect: false,
      backgroundColor: '#ffffff',
      titleFontColor: '#111827',
      titleFontSize: 13,
      titleFontStyle: '600',
      bodyFontColor: '#4b5563',
      bodyFontSize: 13,
      bodySpacing: 6,
      xPadding: 14,
      yPadding: 12,
      cornerRadius: 10,
      displayColors: true,
      boxWidth: 8,
      boxHeight: 8,
      borderWidth: 1,
      borderColor: '#f3f4f6',
      callbacks: {
        label: function (tooltipItem, data) {
          return ` ${utils.formatMoney(
            Math.round(tooltipItem.value * 100),
            defaultCurrency.value
          )}`
        },
      },
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
