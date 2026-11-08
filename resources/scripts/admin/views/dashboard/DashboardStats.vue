<template>
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:gap-8">
    <!-- Sales — carte vedette sur le dégradé de marque InvoiceShelf -->
    <div v-if="!loading" class="relative flex flex-col p-6 overflow-hidden rounded-2xl text-chrome-fg bg-gradient-to-br from-hero-from to-hero-to shadow-card">
      <div class="flex items-center justify-between">
        <span class="text-lg font-medium text-white text-opacity-90">
          {{ $t('dashboard.stats.sales') }}
        </span>
        <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/15">
          <BaseIcon name="TrendingUpIcon" class="w-6 h-6 text-white" />
        </span>
      </div>
      <span class="mt-4 text-4xl font-bold tracking-tight text-white">
        <BaseFormatMoney
          :amount="dashboardStore.totalSales"
          :currency="companyStore.selectedCompanyCurrency"
        />
      </span>
      <span class="mt-1.5 text-xs text-white text-opacity-70">
        {{ trendText(dashboardStore.totalSales, dashboardStore.previousYear.sales) }}
      </span>
    </div>

    <!-- Expenses -->
    <div v-if="!loading" class="flex flex-col p-6 bg-surface rounded-2xl border border-line-light shadow-card">
      <div class="flex items-center justify-between">
        <span class="text-lg font-medium text-heading">
          {{ $t('dashboard.stats.expenses') }}
        </span>
        <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-status-yellow-bg">
          <BaseIcon name="TrendingDownIcon" class="w-6 h-6 text-status-yellow" />
        </span>
      </div>
      <span class="mt-4 text-3xl font-bold tracking-tight text-heading">
        <BaseFormatMoney
          :amount="dashboardStore.totalExpenses"
          :currency="companyStore.selectedCompanyCurrency"
        />
      </span>
      <span class="mt-1.5 text-xs text-muted">
        {{ trendText(dashboardStore.totalExpenses, dashboardStore.previousYear.expenses) }}
      </span>
    </div>

    <!-- Net Income -->
    <div v-if="!loading" class="flex flex-col p-6 bg-surface rounded-2xl border border-line-light shadow-card">
      <div class="flex items-center justify-between">
        <span class="text-lg font-medium text-heading">
          {{ $t('dashboard.stats.net_income') }}
        </span>
        <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-status-green-bg">
          <BaseIcon name="CurrencyDollarIcon" class="w-6 h-6 text-status-green" />
        </span>
      </div>
      <span class="mt-4 text-3xl font-bold tracking-tight text-heading">
        <BaseFormatMoney
          :amount="dashboardStore.totalNetIncome"
          :currency="companyStore.selectedCompanyCurrency"
        />
      </span>
      <span class="mt-1.5 text-xs text-muted">
        {{ trendText(dashboardStore.totalNetIncome, dashboardStore.previousYear.netIncome) }}
      </span>
    </div>

    <!-- Skeletons -->
    <template v-if="loading">
      <div
        v-for="n in 3"
        :key="n"
        class="flex flex-col p-6 bg-surface rounded-2xl border border-line-light shadow-card animate-pulse"
      >
        <div class="h-4 w-24 rounded bg-surface-muted"></div>
        <div class="mt-4 h-8 w-40 rounded bg-surface-muted"></div>
        <div class="mt-2 h-3 w-32 rounded bg-surface-secondary"></div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import { useCompanyStore } from '@/scripts/admin/stores/company'

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
</script>
