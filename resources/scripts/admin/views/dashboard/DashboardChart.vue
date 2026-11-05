<template>
  <div>
    <div
      v-if="dashboardStore.isDashboardDataLoaded"
      class="mt-8 bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-8"
    >
      <div class="flex justify-between items-center mb-6 flex-col md:flex-row gap-3">
        <div>
          <h6 class="text-lg font-semibold text-gray-900">
            {{ $t('dashboard.monthly_chart.title') }}
          </h6>
          <p class="mt-0.5 text-sm text-gray-400">
            {{ $t('dashboard.monthly_chart.subtitle', { year: selectedYear }) }}
          </p>
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
        :invoices="dashboardStore.chartData.invoiceTotals"
        :expenses="dashboardStore.chartData.expenseTotals"
        :receipts="dashboardStore.chartData.receiptTotals"
        :labels="dashboardStore.chartData.months"
        class="sm:w-full"
      />
    </div>

    <ChartPlaceholder v-else />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import LineChart from '@/scripts/admin/components/charts/LineChart.vue'
import ChartPlaceholder from './DashboardChartPlaceholder.vue'
import abilities from '@/scripts/admin/stub/abilities'
import { useUserStore } from '@/scripts/admin/stores/user'

const dashboardStore = useDashboardStore()
const userStore = useUserStore()
const selectedYear = ref(null)

watch(
  () => dashboardStore.availableYears,
  (years) => {
    if (years.length && selectedYear.value === null) {
      selectedYear.value = years[0]
    }
  },
  { immediate: true }
)

watch(
  selectedYear,
  (year) => {
    if (year) {
      loadData({ year })
    }
  },
  { immediate: true }
)

async function loadData(params) {
  if (userStore.hasAbilities(abilities.DASHBOARD)) {
    await dashboardStore.loadData(params)
  }
}
</script>
