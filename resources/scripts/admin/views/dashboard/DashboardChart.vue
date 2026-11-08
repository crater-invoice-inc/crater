<template>
  <div>
    <div
      v-if="dashboardStore.isDashboardDataLoaded"
      class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8"
    >
      <div class="lg:col-span-2 bg-surface rounded-2xl border border-line-light shadow-card p-6 sm:p-8">
        <!-- En-tête façon "Cash flow" InvoiceShelf -->
        <div class="flex justify-between items-center flex-col md:flex-row gap-3">
          <div class="flex items-center gap-3">
            <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary-50">
              <BaseIcon name="ChartSquareBarIcon" class="w-5 h-5 text-primary-500" />
            </span>
            <div>
              <h6 class="text-lg font-semibold text-heading leading-tight">
                {{ $t('dashboard.monthly_chart.title') }}
              </h6>
              <p v-if="selectedYear" class="mt-0.5 text-sm text-muted leading-tight">
                {{ $t('dashboard.monthly_chart.subtitle', { year: selectedYear }) }}
              </p>
            </div>
          </div>

          <div class="w-full md:w-40 h-10">
            <BaseMultiselect
              v-model="selectedYear"
              :options="dashboardStore.availableYears"
              :allow-empty="false"
              :show-labels="false"
              :placeholder="$t('dashboard.select_year')"
              :can-deselect="false"
            />
          </div>
        </div>

        <!-- Tuiles de stats (Sales / Receipts / Expenses / Net income) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8">
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
                  :amount="dashboardStore.totalSales"
                  :currency="companyStore.selectedCompanyCurrency"
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
                  :amount="dashboardStore.totalReceipts"
                  :currency="companyStore.selectedCompanyCurrency"
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
                  :amount="dashboardStore.totalExpenses"
                  :currency="companyStore.selectedCompanyCurrency"
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
                  :amount="dashboardStore.totalNetIncome"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </p>
            </div>
          </div>
        </div>

        <LineChart
          class="mt-6 sm:w-full"
          :invoices="dashboardStore.chartData.invoiceTotals"
          :expenses="dashboardStore.chartData.expenseTotals"
          :receipts="dashboardStore.chartData.receiptTotals"
          :labels="dashboardStore.chartData.months"
          :year="selectedYear"
        />
      </div>

      <div class="flex flex-col bg-surface rounded-2xl border border-line-light shadow-card p-6 sm:p-8">
        <h6 class="text-lg font-semibold text-heading mb-6">
          {{ $t('dashboard.gauge.title') }}
        </h6>
        <GaugeChart
          class="flex-1"
          :sales="dashboardStore.totalSales"
          :receipts="dashboardStore.totalReceipts"
        />
      </div>
    </div>

    <ChartPlaceholder v-else />
  </div>
</template>

<script setup>
import { ref, watch, nextTick, onMounted } from 'vue'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import LineChart from '@/scripts/admin/components/charts/LineChart.vue'
import GaugeChart from '@/scripts/admin/components/charts/GaugeChart.vue'
import ChartPlaceholder from './DashboardChartPlaceholder.vue'
import abilities from '@/scripts/admin/stub/abilities'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const dashboardStore = useDashboardStore()
const userStore = useUserStore()
const companyStore = useCompanyStore()
const selectedYear = ref(null)
let syncingDefaultYear = false

onMounted(() => {
  loadData()
})

// Sync the filter to the most recent year with data without re-triggering a load
watch(
  () => dashboardStore.availableYears,
  (years) => {
    if (years.length && selectedYear.value === null) {
      syncingDefaultYear = true
      selectedYear.value = Number(years[0])
      nextTick(() => {
        syncingDefaultYear = false
      })
    }
  }
)

watch(
  selectedYear,
  (year) => {
    if (year && !syncingDefaultYear) {
      loadData({ year })
    }
  }
)

async function loadData(params) {
  if (userStore.hasAbilities(abilities.DASHBOARD)) {
    await dashboardStore.loadData(params)
  }
}
</script>
