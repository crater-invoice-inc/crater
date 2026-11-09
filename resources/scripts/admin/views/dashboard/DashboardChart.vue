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

const dashboardStore = useDashboardStore()
const userStore = useUserStore()
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
