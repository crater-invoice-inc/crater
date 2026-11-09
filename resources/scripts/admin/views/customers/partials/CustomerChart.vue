<template>
  <BaseCard class="flex flex-col mt-6">
    <ChartPlaceholder v-if="customerStore.isFetchingViewData" />

    <div v-else>
      <!-- En-tête façon "Cash flow" InvoiceShelf -->
      <div class="flex justify-between items-center flex-col md:flex-row gap-3">
        <div class="flex items-center gap-3">
          <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary-50">
            <BaseIcon name="ChartSquareBarIcon" class="w-5 h-5 text-primary-500" />
          </span>
          <h6 class="text-lg font-semibold text-heading leading-tight">
            {{ $t('dashboard.monthly_chart.title') }}
          </h6>
        </div>

        <div class="w-full md:w-40 h-10">
          <BaseMultiselect
            v-model="selectedYear"
            :options="years"
            :allow-empty="false"
            :show-labels="false"
            :placeholder="$t('dashboard.select_year')"
            :can-deselect="false"
            @select="onChangeYear"
          />
        </div>
      </div>

      <!-- Tuiles de stats (Sales / Receipts / Expenses / Net income) -->
      <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8">
        <div class="flex items-center gap-3">
          <span class="flex items-center justify-center w-11 h-11 shrink-0 rounded-xl bg-status-blue-bg">
            <BaseIcon name="DocumentTextIcon" class="w-5 h-5 text-status-blue" />
          </span>
          <div class="min-w-0">
            <p class="text-sm text-muted truncate">
              {{ $t('dashboard.chart_info.total_sales') }}
            </p>
            <p class="text-lg font-bold text-heading truncate">
              <BaseFormatMoney
                :amount="chartData.salesTotal"
                :currency="data.currency"
              />
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="flex items-center justify-center w-11 h-11 shrink-0 rounded-xl bg-status-green-bg">
            <BaseIcon name="CashIcon" class="w-5 h-5 text-status-green" />
          </span>
          <div class="min-w-0">
            <p class="text-sm text-muted truncate">
              {{ $t('dashboard.chart_info.total_receipts') }}
            </p>
            <p class="text-lg font-bold text-heading truncate">
              <BaseFormatMoney
                :amount="chartData.totalReceipts"
                :currency="data.currency"
              />
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="flex items-center justify-center w-11 h-11 shrink-0 rounded-xl bg-status-yellow-bg">
            <BaseIcon name="CreditCardIcon" class="w-5 h-5 text-status-yellow" />
          </span>
          <div class="min-w-0">
            <p class="text-sm text-muted truncate">
              {{ $t('dashboard.chart_info.total_expense') }}
            </p>
            <p class="text-lg font-bold text-heading truncate">
              <BaseFormatMoney
                :amount="chartData.totalExpenses"
                :currency="data.currency"
              />
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="flex items-center justify-center w-11 h-11 shrink-0 rounded-xl bg-status-purple-bg">
            <BaseIcon name="ScaleIcon" class="w-5 h-5 text-status-purple" />
          </span>
          <div class="min-w-0">
            <p class="text-sm text-muted truncate">
              {{ $t('dashboard.chart_info.net_income') }}
            </p>
            <p class="text-lg font-bold text-heading truncate">
              <BaseFormatMoney
                :amount="chartData.netProfit"
                :currency="data.currency"
              />
            </p>
          </div>
        </div>
      </div>

      <LineChart
        v-if="isLoading"
        class="mt-6 sm:w-full"
        :invoices="getChartInvoices"
        :expenses="getChartExpenses"
        :receipts="getReceiptTotals"
        :income="getNetProfits"
        :labels="getChartMonths"
      />
    </div>

    <CustomerInfo />
  </BaseCard>
</template>

<script setup>
import CustomerInfo from './CustomerInfo.vue'
import LineChart from '@/scripts/admin/components/charts/LineChart.vue'
import { ref, computed, watch, reactive, inject } from 'vue'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useRoute } from 'vue-router'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import ChartPlaceholder from './CustomerChartPlaceholder.vue'

const companyStore = useCompanyStore()
const customerStore = useCustomerStore()
const utils = inject('utils')

const route = useRoute()

let isLoading = ref(false)
let chartData = reactive({})
let data = reactive({})
let years = reactive(['This year', 'Previous year'])
let selectedYear = ref('This year')

const getChartExpenses = computed(() => {
  if (chartData.expenseTotals) {
    return chartData.expenseTotals
  }
  return []
})

const getNetProfits = computed(() => {
  if (chartData.netProfits) {
    return chartData.netProfits
  }
  return []
})

const getChartMonths = computed(() => {
  if (chartData && chartData.months) {
    return chartData.months
  }
  return []
})

const getReceiptTotals = computed(() => {
  if (chartData.receiptTotals) {
    return chartData.receiptTotals
  }
  return []
})

const getChartInvoices = computed(() => {
  if (chartData.invoiceTotals) {
    return chartData.invoiceTotals
  }

  return []
})

watch(
  route,
  () => {
    if (route.params.id) {
      loadCustomer()
    }
    selectedYear.value = 'This year'
  },
  { immediate: true }
)

async function loadCustomer() {
  isLoading.value = false
  let response = await customerStore.fetchViewCustomer({
    id: route.params.id,
  })

  if (response.data) {
    Object.assign(chartData, response.data.meta.chartData)
    Object.assign(data, response.data.data)
  }

  isLoading.value = true
}

async function onChangeYear(data) {
  let params = {
    id: route.params.id,
  }

  data === 'Previous year'
    ? (params.previous_year = true)
    : (params.this_year = true)

  let response = await customerStore.fetchViewCustomer(params)

  if (response.data.meta.chartData) {
    Object.assign(chartData, response.data.meta.chartData)
  }

  return true
}
</script>
