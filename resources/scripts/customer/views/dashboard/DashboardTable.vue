<template>
  <div class="grid grid-cols-1 gap-6 mt-10 xl:grid-cols-2">
    <!-- Due Invoices -->
    <div class="due-invoices">
      <div class="relative z-10 flex items-center justify-between mb-3">
        <h6 class="mb-0 text-xl font-semibold leading-normal">
          {{ $t('dashboard.recent_invoices_card.title') }}
        </h6>

        <BaseButton
          size="sm"
          variant="primary-outline"
          @click="$router.push({ name: 'invoices.dashboard' })"
        >
          {{ $t('dashboard.recent_invoices_card.view_all') }}
        </BaseButton>
      </div>
      <!-- Recent Invoice-->
      <BaseTable
        :data="dashboardStore.recentInvoices"
        :columns="dueInvoiceColumns"
        :loading="!globalStore.getDashboardDataLoaded"
      >
        <template #cell-invoice_number="{ row }">
          <router-link
            :to="{
              path: `/${globalStore.companySlug}/customer/invoices/${row.data.id}/view`,
            }"
            class="font-medium text-primary-500"
          >
            {{ row.data.invoice_number }}
          </router-link>
        </template>

        <template #cell-paid_status="{ row }">
          <BasePaidStatusBadge :status="row.data.paid_status">
            {{ row.data.paid_status }}
          </BasePaidStatusBadge>
        </template>

        <template #cell-due_amount="{ row }">
          <BaseFormatMoney
            :amount="row.data.due_amount"
            :currency="globalStore.currency"
          />
        </template>
      </BaseTable>
    </div>

    <!-- Recent Estimates -->
    <div class="recent-estimates">
      <div class="relative z-10 flex items-center justify-between mb-3">
        <h6 class="mb-0 text-xl font-semibold leading-normal">
          {{ $t('dashboard.recent_estimate_card.title') }}
        </h6>

        <BaseButton
          variant="primary-outline"
          size="sm"
          @click="$router.push({ name: 'estimates.dashboard' })"
        >
          {{ $t('dashboard.recent_estimate_card.view_all') }}
        </BaseButton>
      </div>

      <BaseTable
        :data="dashboardStore.recentEstimates"
        :columns="recentEstimateColumns"
        :loading="!globalStore.getDashboardDataLoaded"
      >
        <template #cell-estimate_number="{ row }">
          <router-link
            :to="{
              path: `/${globalStore.companySlug}/customer/estimates/${row.data.id}/view`,
            }"
            class="font-medium text-primary-500"
          >
            {{ row.data.estimate_number }}
          </router-link>
        </template>
        <template #cell-status="{ row }">
          <BaseEstimateStatusBadge :status="row.data.status" class="px-3 py-1">
            {{ $t(`estimates.${row.data.status.toLowerCase()}`) }}
          </BaseEstimateStatusBadge>
        </template>
        <template #cell-total="{ row }">
          <BaseFormatMoney
            :amount="row.data.total"
            :currency="globalStore.currency"
          />
        </template>
      </BaseTable>
    </div>
  </div>
</template>

<script setup>
import { computed, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useGlobalStore } from '@/scripts/customer/stores/global'
import { useDashboardStore } from '@/scripts/customer/stores/dashboard'
import BaseTable from '@/scripts/components/base/base-table/BaseTable.vue'

// store

const globalStore = useGlobalStore()
const dashboardStore = useDashboardStore()
const { tm, t } = useI18n()
const utils = inject('utils')
const route = useRoute()

//computed prop

const dueInvoiceColumns = computed(() => {
  return [
    {
      key: 'formattedDueDate',
      label: t('dashboard.recent_invoices_card.due_on'),
    },
    {
      key: 'invoice_number',
      label: t('invoices.number'),
    },
    { key: 'paid_status', label: t('invoices.status') },
    {
      key: 'due_amount',
      label: t('dashboard.recent_invoices_card.amount_due'),
    },
  ]
})
const recentEstimateColumns = computed(() => {
  return [
    {
      key: 'formattedEstimateDate',
      label: t('dashboard.recent_estimate_card.date'),
    },
    {
      key: 'estimate_number',
      label: t('estimates.number'),
    },
    { key: 'status', label: t('estimates.status') },
    {
      key: 'total',
      label: t('dashboard.recent_estimate_card.amount_due'),
    },
  ]
})
</script>
