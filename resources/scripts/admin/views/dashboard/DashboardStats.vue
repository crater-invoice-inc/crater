<template>
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:gap-8">
    <!-- Sales -->
    <div v-if="!loading" class="flex flex-col p-6 bg-white rounded-xl border border-gray-100 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-500">
          {{ $t('dashboard.stats.sales') }}
        </span>
        <InvoiceIcon class="w-6 h-6 text-gray-400" />
      </div>
      <span class="mt-3 text-3xl font-bold tracking-tight text-gray-900">
        <BaseFormatMoney
          :amount="dashboardStore.totalSales"
          :currency="companyStore.selectedCompanyCurrency"
        />
      </span>
      <span class="mt-1 text-xs" :class="trendClass(dashboardStore.totalSales, dashboardStore.previousYear.sales)">
        {{ trendText(dashboardStore.totalSales, dashboardStore.previousYear.sales) }}
      </span>
    </div>

    <!-- Expenses -->
    <div v-if="!loading" class="flex flex-col p-6 bg-white rounded-xl border border-gray-100 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-500">
          {{ $t('dashboard.stats.expenses') }}
        </span>
        <DollarIcon class="w-6 h-6 text-gray-400" />
      </div>
      <span class="mt-3 text-3xl font-bold tracking-tight text-gray-900">
        <BaseFormatMoney
          :amount="dashboardStore.totalExpenses"
          :currency="companyStore.selectedCompanyCurrency"
        />
      </span>
      <span class="mt-1 text-xs" :class="trendClass(dashboardStore.totalExpenses, dashboardStore.previousYear.expenses)">
        {{ trendText(dashboardStore.totalExpenses, dashboardStore.previousYear.expenses) }}
      </span>
    </div>

    <!-- Net Income -->
    <div v-if="!loading" class="flex flex-col p-6 bg-white rounded-xl border border-gray-100 shadow-sm">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-500">
          {{ $t('dashboard.stats.net_income') }}
        </span>
        <PaymentIcon class="w-6 h-6 text-gray-400" />
      </div>
      <span class="mt-3 text-3xl font-bold tracking-tight text-gray-900">
        <BaseFormatMoney
          :amount="dashboardStore.totalNetIncome"
          :currency="companyStore.selectedCompanyCurrency"
        />
      </span>
      <span class="mt-1 text-xs" :class="trendClass(dashboardStore.totalNetIncome, dashboardStore.previousYear.netIncome)">
        {{ trendText(dashboardStore.totalNetIncome, dashboardStore.previousYear.netIncome) }}
      </span>
    </div>

    <!-- Skeletons -->
    <template v-if="loading">
      <div
        v-for="n in 3"
        :key="n"
        class="flex flex-col p-6 bg-white rounded-xl border border-gray-100 shadow-sm animate-pulse"
      >
        <div class="h-4 w-24 rounded bg-gray-100"></div>
        <div class="mt-4 h-8 w-40 rounded bg-gray-100"></div>
        <div class="mt-2 h-3 w-32 rounded bg-gray-50"></div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import InvoiceIcon from '@/scripts/components/icons/dashboard/InvoiceIcon.vue'
import DollarIcon from '@/scripts/components/icons/dashboard/DollarIcon.vue'
import PaymentIcon from '@/scripts/components/icons/dashboard/PaymentIcon.vue'

const { t } = useI18n()

const dashboardStore = useDashboardStore()
const companyStore = useCompanyStore()

const loading = computed(() => !dashboardStore.isDashboardDataLoaded)

function trendPercent(current, previous) {
  if (previous === null || previous === undefined || Number(previous) === 0) {
    return null
  }
  return Math.round(((Number(current) - Number(previous)) / Math.abs(Number(previous))) * 100)
}

function trendText(current, previous) {
  const pct = trendPercent(current, previous)

  if (pct === null) {
    return t('dashboard.stats.no_last_year_data')
  }

  if (pct === 0) {
    return t('dashboard.stats.stable')
  }

  const sign = pct > 0 ? '+' : ''
  return `${sign}${pct}% ${t('dashboard.stats.vs_last_year')}`
}

function trendClass(current, previous) {
  const pct = trendPercent(current, previous)

  if (pct === null || pct === 0) {
    return 'text-gray-400'
  }

  return pct > 0 ? 'text-green-500' : 'text-red-400'
}
</script>
